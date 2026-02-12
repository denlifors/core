const { prisma } = require("../src/prisma/client");

async function main() {
  const fromPartnerId = process.argv[2];
  const ownerPartnerId = process.argv[3];

  if (!fromPartnerId || !ownerPartnerId) {
    throw new Error(
      "Usage: node scripts/cleanup-referral-test-effects.js <fromPartnerId> <ownerPartnerId>"
    );
  }

  const ledgers = await prisma.bonusLedger.findMany({
    where: { note: { contains: `from=${fromPartnerId}` } },
    select: { id: true, partnerId: true, amount: true },
  });

  const deltaByPartner = new Map();
  for (const row of ledgers) {
    deltaByPartner.set(row.partnerId, (deltaByPartner.get(row.partnerId) || 0) + row.amount);
  }

  await prisma.$transaction(async (tx) => {
    if (ledgers.length > 0) {
      await tx.bonusLedger.deleteMany({ where: { id: { in: ledgers.map((x) => x.id) } } });
    }

    for (const [partnerId, amount] of deltaByPartner.entries()) {
      const wallet = await tx.wallet.findFirst({
        where: { partnerId },
        select: { id: true, balance: true },
      });
      if (!wallet) continue;

      const nextBalance = Math.max(0, (wallet.balance || 0) - amount);
      await tx.wallet.update({
        where: { id: wallet.id },
        data: { balance: nextBalance },
      });

      await tx.walletTransaction.deleteMany({
        where: { walletId: wallet.id, note: { contains: `from=${fromPartnerId}` } },
      });
    }

    // Сбрасываем накопленные объёмы владельца структуры,
    // чтобы предвидео-тест начинался без следов тех.прогона.
    await tx.binaryNode.updateMany({
      where: { partnerId: ownerPartnerId },
      data: { leftVolume: 0, rightVolume: 0 },
    });
    await tx.weeklyBinaryStats.deleteMany({ where: { partnerId: ownerPartnerId } });
  });

  const residualLedgers = await prisma.bonusLedger.count({
    where: { note: { contains: `from=${fromPartnerId}` } },
  });
  const ownerNode = await prisma.binaryNode.findFirst({
    where: { partnerId: ownerPartnerId },
    select: { leftVolume: true, rightVolume: true },
  });

  console.log(
    JSON.stringify(
      {
        cleaned: true,
        fromPartnerId,
        ownerPartnerId,
        removedBonusLedgerRows: ledgers.length,
        residualLedgerRows: residualLedgers,
        ownerNode,
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

