const { prisma } = require("../src/prisma/client");

async function main() {
  const email = process.argv[2];
  if (!email) {
    throw new Error("Usage: node scripts/inspect-user.js <email>");
  }

  const user = await prisma.user.findUnique({
    where: { email },
    include: { partner: true, customer: true },
  });
  console.log(JSON.stringify(user, null, 2));

  if (user?.customer?.id) {
    const agg = await prisma.order.aggregate({
      where: { customerId: user.customer.id },
      _sum: { dv: true },
    });
    console.log("customer_dv_sum:", Number(agg._sum.dv || 0));
  }

  if (user?.partner?.id) {
    const agg = await prisma.order.aggregate({
      where: { buyerPartnerId: user.partner.id },
      _sum: { dv: true },
    });
    console.log("partner_dv_sum:", Number(agg._sum.dv || 0));
  }
}

main()
  .catch((e) => {
    console.error(e);
    process.exit(1);
  })
  .finally(async () => {
    await prisma.$disconnect();
  });

