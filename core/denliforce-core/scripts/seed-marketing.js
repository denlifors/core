const { PrismaClient } = require("@prisma/client");

const db = new PrismaClient();

async function upsertSingle(model, data) {
  const existing = await model.findFirst();
  if (existing) {
    return model.update({ where: { id: existing.id }, data });
  }
  return model.create({ data });
}

async function main() {
  await upsertSingle(db.marketingConfig, {
    dvToRubRate: 30,
    partnerEntryDv: 200,
    partnerActiveDv: 100,
  });

  await db.cashbackTier.deleteMany();
  await db.cashbackTier.createMany({
    data: [
      { minRub: 9000, maxRub: 18000, percent: 2 },
      { minRub: 18000, maxRub: 36000, percent: 3 },
      { minRub: 36000, maxRub: null, percent: 5 },
    ],
  });

  await db.influenceTier.deleteMany();
  await db.influenceTier.createMany({
    data: [
      { level: 1, percent: 5, minSelfDv: 100 },
      { level: 2, percent: 3, minSelfDv: 200 },
      { level: 3, percent: 2, minSelfDv: 300 },
    ],
  });

  await db.binaryBonusTier.deleteMany();
  await db.binaryBonusTier.createMany({
    data: [
      { minWeekDv: 100, percent: 2 },
      { minWeekDv: 200, percent: 3 },
      { minWeekDv: 300, percent: 5 },
    ],
  });

  await db.rankRequirement.deleteMany();
  await db.rankRequirement.createMany({
    data: [
      { code: "BRONZE", name: "Бронзовый лидер", minMinorLegDv: 1000, minSelfDv: 100, depthLevels: 1 },
      { code: "SILVER", name: "Серебряный", minMinorLegDv: 3000, minSelfDv: 200, depthLevels: 2 },
      { code: "GOLD", name: "Золотой", minMinorLegDv: 6000, minSelfDv: 200, depthLevels: 3 },
      { code: "PLATINUM", name: "Платиновый", minMinorLegDv: 12000, minSelfDv: 300, depthLevels: 4 },
      { code: "DIAMOND", name: "Бриллиантовый", minMinorLegDv: 24000, minSelfDv: 600, depthLevels: 5 },
      { code: "DIRECTOR", name: "Директор", minMinorLegDv: 48000, minSelfDv: 900, depthLevels: 6 },
      { code: "EXECUTIVE_DIRECTOR", name: "Исполнительный директор", minMinorLegDv: 120000, minSelfDv: 1200, depthLevels: 7 },
      { code: "COMMERCIAL_DIRECTOR", name: "Коммерческий директор", minMinorLegDv: 240000, minSelfDv: 2400, depthLevels: 8 },
      { code: "GENERAL_DIRECTOR", name: "Генеральный директор", minMinorLegDv: 480000, minSelfDv: 4800, depthLevels: 9 },
    ],
  });

  await db.globalBonusConfig.deleteMany();
  await db.globalBonusConfig.create({ data: { percent: 1 } });

  await db.representativeBonusConfig.deleteMany();
  await db.representativeBonusConfig.create({
    data: { percent: 1, minRankCode: "EXECUTIVE_DIRECTOR", requiresOffice: true },
  });

  await db.customerCashbackConfig.deleteMany();
  await db.customerCashbackConfig.create({
    data: { productPercent: 15, referralPercent: 10, maxSpendPercent: 50 },
  });
}

main()
  .catch((e) => {
    console.error(e);
    process.exitCode = 1;
  })
  .finally(async () => {
    await db.$disconnect();
  });
