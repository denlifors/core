const { PrismaClient } = require("@prisma/client");

(async () => {
  const db = new PrismaClient();
  try {
    const partners = await db.partner.findMany({
      select: {
        id: true,
        createdAt: true,
        user: { select: { email: true } },
      },
      orderBy: { createdAt: "desc" },
      take: 5,
    });
    console.log(JSON.stringify(partners, null, 2));
  } catch (e) {
    console.error(e);
    process.exitCode = 1;
  } finally {
    await db.$disconnect();
  }
})();
