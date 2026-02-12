const { prisma } = require("../src/prisma/client");

async function main() {
  const email = process.argv[2];
  if (!email) {
    throw new Error("Usage: node scripts/reset-demo-user.js <email>");
  }

  const user = await prisma.user.findUnique({
    where: { email },
    include: { partner: true, customer: true },
  });

  if (!user) {
    console.log(`Core user not found for ${email}`);
    return;
  }

  const partnerId = user.partner?.id || null;
  const customerId = user.customer?.id || null;

  if (partnerId || customerId) {
    await prisma.$transaction(async (tx) => {
      await tx.orderItem.deleteMany({
        where: {
          order: {
            OR: [
              partnerId ? { buyerPartnerId: partnerId } : undefined,
              customerId ? { customerId } : undefined,
            ].filter(Boolean),
          },
        },
      });

      await tx.order.deleteMany({
        where: {
          OR: [
            partnerId ? { buyerPartnerId: partnerId } : undefined,
            customerId ? { customerId } : undefined,
          ].filter(Boolean),
        },
      });

      if (partnerId) {
        const nodeIds = (
          await tx.binaryNode.findMany({ where: { partnerId }, select: { id: true } })
        ).map((x) => x.id);

        const walletIds = (
          await tx.wallet.findMany({ where: { partnerId }, select: { id: true } })
        ).map((x) => x.id);
        if (walletIds.length > 0) {
          await tx.walletTransaction.deleteMany({ where: { walletId: { in: walletIds } } });
        }
        await tx.wallet.deleteMany({ where: { partnerId } });

        const cbw = await tx.partnerCashbackWallet.findUnique({ where: { partnerId } });
        if (cbw) {
          await tx.cashbackTransaction.deleteMany({ where: { walletId: cbw.id } });
          await tx.partnerCashbackWallet.delete({ where: { id: cbw.id } });
        }

        await tx.weeklyBinaryStats.deleteMany({ where: { partnerId } });
        await tx.partnerMonthlyStats.deleteMany({ where: { partnerId } });
        await tx.rankHistory.deleteMany({ where: { partnerId } });
        await tx.bonusLedger.deleteMany({ where: { partnerId } });
        await tx.volumeLedger.deleteMany({ where: { partnerId } });
        await tx.annualGlobalBonusPayout.deleteMany({ where: { partnerId } });

        if (nodeIds.length > 0) {
          await tx.binaryNode.updateMany({
            where: { parentId: { in: nodeIds } },
            data: { parentId: null },
          });
        }
        await tx.binaryNode.deleteMany({ where: { partnerId } });
        await tx.partner.updateMany({
          where: { sponsorId: partnerId },
          data: { sponsorId: null },
        });

        await tx.partner.deleteMany({ where: { id: partnerId } });
      }

      if (customerId) {
        const cWallet = await tx.customerCashbackWallet.findUnique({ where: { customerId } });
        if (cWallet) {
          await tx.customerCashbackTransaction.deleteMany({ where: { walletId: cWallet.id } });
          await tx.customerCashbackWallet.delete({ where: { id: cWallet.id } });
        }

        await tx.customer.updateMany({
          where: { referrerId: customerId },
          data: { referrerId: null },
        });

        await tx.customer.deleteMany({ where: { id: customerId } });
      }

      await tx.user.update({
        where: { id: user.id },
        data: { role: "CUSTOMER" },
      });

      await tx.user.delete({ where: { id: user.id } });
    });
  }

  console.log(
    JSON.stringify(
      {
        reset: true,
        email,
        coreUserId: user.id,
        removedPartnerId: partnerId,
        removedCustomerId: customerId,
      },
      null,
      2
    )
  );
}

main()
  .catch((e) => {
    console.error(e);
    process.exit(1);
  })
  .finally(async () => {
    await prisma.$disconnect();
  });

