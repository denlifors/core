const http = require('http');
const fs = require('fs');
const path = require('path');
const { prisma } = require('./prisma/client');

function readJson(req) {
  return new Promise((resolve, reject) => {
    let data = '';
    req.on('data', (c) => (data += c));
    req.on('end', () => {
      try {
        resolve(data ? JSON.parse(data) : {});
      } catch (e) {
        reject(e);
      }
    });
  });
}
function getWeekStartUTC(d = new Date()) {
  const dt = new Date(Date.UTC(d.getUTCFullYear(), d.getUTCMonth(), d.getUTCDate()));
  const day = dt.getUTCDay(); // 0=Sun..6=Sat
  const diff = (day === 0 ? -6 : 1) - day; // to Monday
  dt.setUTCDate(dt.getUTCDate() + diff);
  return dt; // Monday 00:00 UTC
}
async function creditPartnerWallet(tx, partnerId, { type, amount, note }) {
  if (!type) throw new Error('creditPartnerWallet: type required');
  if (typeof amount !== 'number') throw new Error('creditPartnerWallet: amount must be number');

  let wallet = await tx.wallet.findFirst({ where: { partnerId } });
  if (!wallet) {
    wallet = await tx.wallet.create({ data: { partnerId, balance: 0 } });
  }

  // 1) деньги
  await tx.wallet.update({
    where: { id: wallet.id },
    data: { balance: { increment: amount } },
  });

  // 2) транзакция кошелька
  await tx.walletTransaction.create({
    data: {
      walletId: wallet.id,
      amount,
      type,
      note,
    },
  });

  // 3) журнал бонусов
  await tx.bonusLedger.create({
    data: {
      partnerId,
      type,
      amount,
      note,
    },
  });

  return wallet.id;
}

// ===== Partner status helpers (маркетинг) =====
// Правила:
// - Активировать партнёрство: ЛЗ за текущий месяц >= 200 DV (первый вход)
// - Быть активным в месяце: ЛЗ за месяц >= 100 DV (иначе INACTIVE до выполнения 100 DV)
// - Круги/бонусы платим только ACTIVE
async function ensurePartnerMonthlyStatus(tx, partnerId, atDate = new Date()) {
  const p = await tx.partner.findUnique({
    where: { id: partnerId },
    select: { id: true, status: true, activatedAt: true, deactivatedAt: true },
  });
  if (!p) return null;

  const selfMonthDv = await getSelfMonthDv(tx, partnerId, atDate);

  // 1) Никогда не активировался -> нужен вход 200 DV
  if (!p.activatedAt) {
    if (selfMonthDv >= 200) {
      return tx.partner.update({
        where: { id: partnerId },
        data: { status: 'ACTIVE', activatedAt: atDate, deactivatedAt: null },
      });
    }
    // остаётся pending
    if (p.status !== 'PENDING') {
      return tx.partner.update({
        where: { id: partnerId },
        data: { status: 'PENDING' },
      });
    }
    return p;
  }

  // 2) Уже был активирован когда-то -> каждый месяц нужно >=100 DV
  if (selfMonthDv >= 100) {
    if (p.status !== 'ACTIVE') {
      return tx.partner.update({
        where: { id: partnerId },
        data: { status: 'ACTIVE', deactivatedAt: null },
      });
    }
    return p;
  }

  // 3) В этом месяце ещё нет 100 DV -> INACTIVE до покупки
  if (p.status !== 'INACTIVE') {
    return tx.partner.update({
      where: { id: partnerId },
      data: { status: 'INACTIVE', deactivatedAt: atDate },
    });
  }
  return p;
}


function getMonthInfluenceLevel(selfMonthDv) {
  if (selfMonthDv >= 300) return 3;
  if (selfMonthDv >= 200) return 2;
  if (selfMonthDv >= 100) return 1;
  return 0;
}

// ===== MONTH HELPERS (для маркетинга: ЛЗ по месяцу) =====
function getMonthStartUTC(d = new Date()) {
  return new Date(Date.UTC(d.getUTCFullYear(), d.getUTCMonth(), 1, 0, 0, 0));
}

function getNextMonthStartUTC(d = new Date()) {
  return new Date(Date.UTC(d.getUTCFullYear(), d.getUTCMonth() + 1, 1, 0, 0, 0));
}

async function getWallet(tx, partnerId) {
  let w = await tx.wallet.findFirst({ where: { partnerId } });
  if (!w) {
    w = await tx.wallet.create({ data: { partnerId, balance: 0 } });
  }
  return w;
}


async function creditPartnerWallet(tx, partnerId, { type, amount, note }) {
  // 1) ledger
  await tx.bonusLedger.create({
    data: { partnerId, type, amount, note },
  });

  // 2) wallet + tx
  const w = await getWallet(tx, partnerId);

  await tx.wallet.update({
    where: { id: w.id },
    data: { balance: { increment: amount } },
  });

  await tx.walletTransaction.create({
    data: { walletId: w.id, amount, type, note },
  });
}


// ЛЗ партнёра за месяц = сумма DV по его ЛИЧНЫМ заказам (Order.buyerPartnerId)
async function getSelfMonthDv(tx, partnerId, d = new Date()) {
  const from = getMonthStartUTC(d);
  const to = getNextMonthStartUTC(d);

  const agg = await tx.order.aggregate({
    where: {
      buyerPartnerId: partnerId,
      createdAt: { gte: from, lte: d },

    },
    _sum: { dv: true },
  });

  return Number(agg._sum.dv || 0);
}

// ЛЗ клиента за месяц = сумма DV по его заказам (Order.customerId)
async function getCustomerMonthDv(tx, customerId, d = new Date()) {
  const from = getMonthStartUTC(d);
  const to = getNextMonthStartUTC(d);

  const agg = await tx.order.aggregate({
    where: {
      customerId,
      createdAt: { gte: from, lte: d },
    },
    _sum: { dv: true },
  });

  return Number(agg._sum.dv || 0);
}

// сколько кругов открыто по ЛЗ (100/200/300 DV)
function getMaxInfluenceCircle(selfMonthDv) {
  if (selfMonthDv >= 300) return 3;
  if (selfMonthDv >= 200) return 2;
  if (selfMonthDv >= 100) return 1;
  return 0;
}

const growthRankRules = [
  { code: 'GENERAL_DIRECTOR', minSmallLegDv: 480000, minPersonalDv: 4800, depth: 9 },
  { code: 'COMMERCIAL_DIRECTOR', minSmallLegDv: 240000, minPersonalDv: 2400, depth: 8 },
  { code: 'EXECUTIVE_DIRECTOR', minSmallLegDv: 120000, minPersonalDv: 1200, depth: 7 },
  { code: 'DIRECTOR', minSmallLegDv: 48000, minPersonalDv: 900, depth: 6 },
  { code: 'DIAMOND', minSmallLegDv: 24000, minPersonalDv: 600, depth: 5 },
  { code: 'PLATINUM', minSmallLegDv: 12000, minPersonalDv: 300, depth: 4 },
  { code: 'GOLD', minSmallLegDv: 6000, minPersonalDv: 200, depth: 3 },
  { code: 'SILVER', minSmallLegDv: 3000, minPersonalDv: 200, depth: 2 },
  { code: 'BRONZE', minSmallLegDv: 1000, minPersonalDv: 100, depth: 1 },
];

function resolveGrowthRankCode({ partnerStatus, personalMonthDv, smallLegDv }) {
  if (partnerStatus !== 'ACTIVE') return 'PARTNER';
  for (const rule of growthRankRules) {
    if (smallLegDv >= rule.minSmallLegDv && personalMonthDv >= rule.minPersonalDv) {
      return rule.code;
    }
  }
  return 'PARTNER';
}

function getPartnerCashbackPercentByMonthDv(personalMonthDv) {
  if (personalMonthDv >= 1200) return 5;
  if (personalMonthDv >= 600) return 3;
  if (personalMonthDv >= 300) return 2;
  return 0;
}

async function getSelfWeekDv(tx, partnerId, d = new Date()) {
  const from = getWeekStartUTC(d);
  const agg = await tx.volumeLedger.aggregate({
    where: {
      partnerId,
      orderId: { not: null },
      createdAt: { gte: from, lte: d },
    },
    _sum: { dv: true },
  });
  return Number(agg._sum.dv || 0);
}

function getBaseUrl(req) {
  const proto = (req.headers['x-forwarded-proto'] || 'http').split(',')[0].trim();
  const host = req.headers.host || 'localhost';
  return `${proto}://${host}`;
}

function buildReferralLink(req, partnerId) {
  const base = process.env.REFERRAL_BASE_URL || getBaseUrl(req);
  return `${base}/register.php?ref=${partnerId}`;
}


async function placeInBinary(tx, sponsorPartnerId, side, newPartnerId) {
    console.log('[placeInBinary]', { sponsorPartnerId, side, newPartnerId });
  
    // если спонсора нет — корень
    if (!sponsorPartnerId) {
      return tx.binaryNode.create({
        data: { partnerId: newPartnerId, parentId: null, side: side || 'LEFT' },
      });
    }
  
    const sponsorNode = await tx.binaryNode.findUnique({
      where: { partnerId: sponsorPartnerId },
    });
    if (!sponsorNode) throw new Error('Sponsor has no binary node');
  
    const queue = [sponsorNode.id];
  
    while (queue.length) {
      const parentId = queue.shift();
  
      const children = await tx.binaryNode.findMany({
        where: { parentId },
        select: { id: true, side: true },
      });
  
      const hasLeft = children.some((c) => c.side === 'LEFT');
      const hasRight = children.some((c) => c.side === 'RIGHT');
  
      // на первом уровне пытаемся занять нужную сторону
      if (parentId === sponsorNode.id) {
        if (side === 'LEFT' && !hasLeft) {
          return tx.binaryNode.create({ data: { partnerId: newPartnerId, parentId, side: 'LEFT' } });
        }
        if (side === 'RIGHT' && !hasRight) {
          return tx.binaryNode.create({ data: { partnerId: newPartnerId, parentId, side: 'RIGHT' } });
        }
      }
  
      // дальше — первое свободное место в ширину
      if (!hasLeft) {
        return tx.binaryNode.create({ data: { partnerId: newPartnerId, parentId, side: 'LEFT' } });
      }
      if (!hasRight) {
        return tx.binaryNode.create({ data: { partnerId: newPartnerId, parentId, side: 'RIGHT' } });
      }
  
      children.forEach((c) => queue.push(c.id));
    }
  
    throw new Error('No free spot found');
  }
  async function getSponsorChain3(tx, startPartnerId) {
    const chain = [];
    let cur = await tx.partner.findUnique({
      where: { id: startPartnerId },
      select: { sponsorId: true },
    });
  
    for (let level = 1; level <= 3; level++) {
      const sponsorId = cur?.sponsorId;
      if (!sponsorId) break;
      chain.push({ level, partnerId: sponsorId });
      cur = await tx.partner.findUnique({
        where: { id: sponsorId },
        select: { sponsorId: true },
      });
    }
    return chain;
  }
    
const server = http.createServer(async (req, res) => {
  try {
    // health
    if (req.method === 'GET' && req.url === '/health') {
      res.writeHead(200, { 'Content-Type': 'application/json' });
      return res.end(JSON.stringify({ ok: true }));
    }

    // POST /bootstrap/partner
    // body: { email, password }
    // allowed only if no partners exist yet
    if (req.method === 'POST' && req.url === '/bootstrap/partner') {
      const body = await readJson(req);
      const { email, password } = body;

      if (!email || !password) {
        res.writeHead(400, { 'Content-Type': 'application/json' });
        return res.end(JSON.stringify({ error: 'email and password required' }));
      }

      const result = await prisma.$transaction(async (tx) => {
        const totalPartners = await tx.partner.count();
        if (totalPartners > 0) {
          throw new Error('bootstrap is allowed only when no partners exist');
        }

        const user = await tx.user.create({
          data: { email, password, role: 'PARTNER' },
        });

        const partner = await tx.partner.create({
          data: { userId: user.id, sponsorId: null },
        });

        const wallet = await tx.wallet.create({
          data: { partnerId: partner.id, balance: 0 },
        });

        const node = await placeInBinary(tx, null, 'LEFT', partner.id);

        return { user, partner, wallet, node };
      });

      res.writeHead(201, { 'Content-Type': 'application/json' });
      return res.end(JSON.stringify({
        ...result,
        referralLink: buildReferralLink(req, result.partner.id),
      }));
    }

    // POST /register-partner
    // body: { email, password, sponsorPartnerId, side: "LEFT"|"RIGHT" }
    if (req.method === 'POST' && req.url === '/register-partner') {
      const body = await readJson(req);
      const { email, password, sponsorPartnerId, side = 'LEFT' } = body;

      if (!email || !password || !sponsorPartnerId) {
        res.writeHead(400, { 'Content-Type': 'application/json' });
        return res.end(JSON.stringify({ error: 'email, password, sponsorPartnerId required' }));
      }

      const result = await prisma.$transaction(async (tx) => {
        const sponsor = await tx.partner.findUnique({
          where: { id: sponsorPartnerId },
          select: { id: true },
        });
        if (!sponsor) throw new Error('sponsor not found');

        // Если пользователь уже есть — используем его
        const existingUser = await tx.user.findUnique({
          where: { email },
          include: { partner: true },
        });

        if (existingUser) {
          let partner = existingUser.partner;
          if (!partner) {
            partner = await tx.partner.create({
              data: { userId: existingUser.id, sponsorId: sponsorPartnerId },
            });
          }

          let wallet = await tx.wallet.findFirst({ where: { partnerId: partner.id } });
          if (!wallet) {
            wallet = await tx.wallet.create({ data: { partnerId: partner.id, balance: 0 } });
          }

          let node = await tx.binaryNode.findUnique({ where: { partnerId: partner.id } });
          if (!node) {
            node = await placeInBinary(tx, sponsorPartnerId, side, partner.id);
          }

          return { user: existingUser, partner, wallet, node };
        }

        const user = await tx.user.create({
          data: { email, password, role: 'PARTNER' },
        });

        const partner = await tx.partner.create({
          data: { userId: user.id, sponsorId: sponsorPartnerId },
        });

        const wallet = await tx.wallet.create({
          data: { partnerId: partner.id, balance: 0 },
        });

        const node = await placeInBinary(tx, sponsorPartnerId, side, partner.id);

        return { user, partner, wallet, node };
      });

      res.writeHead(201, { 'Content-Type': 'application/json' });
      return res.end(JSON.stringify({
        ...result,
        referralLink: buildReferralLink(req, result.partner.id),
      }));
    }

    // POST /register-customer
    // body: { email, password, sponsorPartnerId? }
    if (req.method === 'POST' && req.url === '/register-customer') {
      const body = await readJson(req);
      const { email, password, sponsorPartnerId = null } = body;

      if (!email || !password) {
        res.writeHead(400, { 'Content-Type': 'application/json' });
        return res.end(JSON.stringify({ error: 'email and password required' }));
      }

      const result = await prisma.$transaction(async (tx) => {
        let resolvedSponsorId = sponsorPartnerId || null;

        if (resolvedSponsorId) {
          const sponsorNode = await tx.binaryNode.findUnique({
            where: { partnerId: resolvedSponsorId },
            select: { partnerId: true },
          });
          if (!sponsorNode) {
            resolvedSponsorId = null;
          }
        }

        if (!resolvedSponsorId) {
          const anyRootedPartner = await tx.binaryNode.findFirst({
            select: { partnerId: true },
            orderBy: { id: 'asc' },
          });
          if (anyRootedPartner?.partnerId) {
            resolvedSponsorId = anyRootedPartner.partnerId;
          }
        }

        if (!resolvedSponsorId) {
          const systemEmail = 'system.sponsor@denlifors.local';
          let systemUser = await tx.user.findUnique({
            where: { email: systemEmail },
            include: { partner: true },
          });

          if (!systemUser) {
            systemUser = await tx.user.create({
              data: { email: systemEmail, password: 'system_sponsor_bootstrap', role: 'PARTNER' },
              include: { partner: true },
            });
          }

          let systemPartner = systemUser.partner;
          if (!systemPartner) {
            systemPartner = await tx.partner.create({
              data: { userId: systemUser.id, sponsorId: null, status: 'ACTIVE', activatedAt: new Date() },
            });
            await tx.wallet.create({ data: { partnerId: systemPartner.id, balance: 0 } });
          }

          const node = await tx.binaryNode.findUnique({ where: { partnerId: systemPartner.id } });
          if (!node) {
            await placeInBinary(tx, null, 'LEFT', systemPartner.id);
          }

          resolvedSponsorId = systemPartner.id;
        }

        let user = await tx.user.findUnique({
          where: { email },
          include: { customer: true },
        });

        if (!user) {
          user = await tx.user.create({
            data: { email, password, role: 'CUSTOMER' },
            include: { customer: true },
          });
        } else if (user.role !== 'CUSTOMER') {
          user = await tx.user.update({
            where: { id: user.id },
            data: { role: 'CUSTOMER' },
            include: { customer: true },
          });
        }

        let customer = user.customer;
        if (!customer) {
          customer = await tx.customer.create({
            data: { userId: user.id, partnerId: resolvedSponsorId },
          });
        } else if (customer.partnerId !== resolvedSponsorId) {
          customer = await tx.customer.update({
            where: { id: customer.id },
            data: { partnerId: resolvedSponsorId },
          });
        }

        return { user, customer, sponsorPartnerId: resolvedSponsorId };
      });

      res.writeHead(201, { 'Content-Type': 'application/json' });
      return res.end(JSON.stringify(result));
    }
    // GET /referral-link?partnerId=...
    if (req.method === 'GET' && req.url.startsWith('/referral-link')) {
      const url = new URL(req.url, 'http://localhost');
      const partnerId = url.searchParams.get('partnerId');

      if (!partnerId) {
        res.writeHead(400, { 'Content-Type': 'application/json' });
        return res.end(JSON.stringify({ error: 'partnerId required' }));
      }

      const partner = await prisma.partner.findUnique({
        where: { id: partnerId },
        select: { id: true },
      });
      if (!partner) {
        res.writeHead(404, { 'Content-Type': 'application/json' });
        return res.end(JSON.stringify({ error: 'partner not found' }));
      }

      res.writeHead(200, { 'Content-Type': 'application/json' });
      return res.end(JSON.stringify({
        partnerId,
        referralLink: buildReferralLink(req, partnerId),
      }));
    }
    // GET /partner-summary?partnerId=...
    if (req.method === 'GET' && req.url.startsWith('/partner-summary')) {
      const url = new URL(req.url, 'http://localhost');
      const partnerId = url.searchParams.get('partnerId');

      if (!partnerId) {
        res.writeHead(400, { 'Content-Type': 'application/json' });
        return res.end(JSON.stringify({ error: 'partnerId required' }));
      }

      const partner = await prisma.partner.findUnique({
        where: { id: partnerId },
        select: {
          id: true,
          sponsorId: true,
          user: { select: { id: true, email: true } },
        },
      });

      if (!partner) {
        res.writeHead(404, { 'Content-Type': 'application/json' });
        return res.end(JSON.stringify({ error: 'partner not found' }));
      }

      res.writeHead(200, { 'Content-Type': 'application/json' });
      return res.end(JSON.stringify({
        partnerId: partner.id,
        sponsorId: partner.sponsorId,
        user: partner.user,
      }));
    }
    // GET /partner-marketing-summary?partnerId=...
    if (req.method === 'GET' && req.url.startsWith('/partner-marketing-summary')) {
      const url = new URL(req.url, 'http://localhost');
      const partnerId = url.searchParams.get('partnerId');
      if (!partnerId) {
        res.writeHead(400, { 'Content-Type': 'application/json' });
        return res.end(JSON.stringify({ error: 'partnerId required' }));
      }

      const now = new Date();
      const partner = await prisma.partner.findUnique({
        where: { id: partnerId },
        select: {
          id: true,
          sponsorId: true,
          status: true,
          activatedAt: true,
          deactivatedAt: true,
          officeCity: true,
          officeOpenedAt: true,
          binary: { select: { leftVolume: true, rightVolume: true } },
          user: { select: { id: true, email: true } },
        },
      });

      if (!partner) {
        res.writeHead(404, { 'Content-Type': 'application/json' });
        return res.end(JSON.stringify({ error: 'partner not found' }));
      }

      const personalMonthDv = await getSelfMonthDv(prisma, partnerId, now);
      const personalWeekDv = await getSelfWeekDv(prisma, partnerId, now);
      const leftDv = Number(partner.binary?.leftVolume || 0);
      const rightDv = Number(partner.binary?.rightVolume || 0);
      const smallLegDv = Math.min(leftDv, rightDv);
      const influenceCircles = getMaxInfluenceCircle(personalMonthDv);
      const rankCode = resolveGrowthRankCode({
        partnerStatus: partner.status,
        personalMonthDv,
        smallLegDv,
      });
      const partnerCashbackPercent = getPartnerCashbackPercentByMonthDv(personalMonthDv);

      res.writeHead(200, { 'Content-Type': 'application/json' });
      return res.end(JSON.stringify({
        partnerId: partner.id,
        sponsorId: partner.sponsorId,
        user: partner.user,
        status: partner.status,
        activatedAt: partner.activatedAt,
        deactivatedAt: partner.deactivatedAt,
        personalMonthDv,
        personalWeekDv,
        leftDv,
        rightDv,
        smallLegDv,
        influenceCircles,
        partnerCashbackPercent,
        rankCode,
        rankDepth: growthRankRules.find((x) => x.code === rankCode)?.depth || 0,
        officeCity: partner.officeCity,
        officeOpenedAt: partner.officeOpenedAt,
      }));
    }
    // GET /partner-bonus-history?partnerId=...&from=YYYY-MM-DD&to=YYYY-MM-DD&type=...
    if (req.method === 'GET' && req.url.startsWith('/partner-bonus-history')) {
      const url = new URL(req.url, 'http://localhost');
      const partnerId = url.searchParams.get('partnerId');
      const type = (url.searchParams.get('type') || 'all').trim();
      const from = (url.searchParams.get('from') || '').trim();
      const to = (url.searchParams.get('to') || '').trim();
      const page = Math.max(1, Number(url.searchParams.get('page') || 1));
      const perPage = Math.min(100, Math.max(1, Number(url.searchParams.get('perPage') || 50)));

      if (!partnerId) {
        res.writeHead(400, { 'Content-Type': 'application/json' });
        return res.end(JSON.stringify({ error: 'partnerId required' }));
      }

      const where = { partnerId };
      if (type && type !== 'all') {
        where.type = type;
      }
      if (from || to) {
        where.createdAt = {};
        if (from) {
          const fromDate = new Date(`${from}T00:00:00.000Z`);
          if (!Number.isNaN(fromDate.getTime())) where.createdAt.gte = fromDate;
        }
        if (to) {
          const toDate = new Date(`${to}T23:59:59.999Z`);
          if (!Number.isNaN(toDate.getTime())) where.createdAt.lte = toDate;
        }
      }

      const [total, rows] = await Promise.all([
        prisma.bonusLedger.count({ where }),
        prisma.bonusLedger.findMany({
          where,
          orderBy: { createdAt: 'desc' },
          skip: (page - 1) * perPage,
          take: perPage,
          select: { id: true, type: true, amount: true, note: true, createdAt: true },
        }),
      ]);

      res.writeHead(200, { 'Content-Type': 'application/json' });
      return res.end(JSON.stringify({
        total,
        page,
        perPage,
        items: rows.map((r) => ({
          id: r.id,
          type: r.type,
          amountRub: r.amount,
          amountDv: Number((r.amount / 30).toFixed(2)),
          note: r.note,
          createdAt: r.createdAt,
        })),
      }));
    }
// GET /debug/partner-id?email=...
if (req.method === 'GET' && req.url.startsWith('/debug/partner-id')) {
    const url = new URL(req.url, 'http://localhost');
    const email = url.searchParams.get('email');
  
    if (!email) {
      res.writeHead(400, { 'Content-Type': 'application/json' });
      return res.end(JSON.stringify({ error: 'email required' }));
    }
  
    const user = await prisma.user.findUnique({
      where: { email },
      include: { partner: true },
    });
  
    if (!user || !user.partner) {
      res.writeHead(404, { 'Content-Type': 'application/json' });
      return res.end(JSON.stringify({ error: 'partner not found for email' }));
    }
  
    res.writeHead(200, { 'Content-Type': 'application/json' });
    return res.end(JSON.stringify({ partnerId: user.partner.id }));
  }
 // GET /debug/tree?partnerId=...&depth=2
if (req.method === 'GET' && req.url.startsWith('/debug/tree')) {
    const url = new URL(req.url, 'http://localhost');
    const partnerId = url.searchParams.get('partnerId');
    const depth = Number(url.searchParams.get('depth') || 2);
  
    if (!partnerId) {
      res.writeHead(400, { 'Content-Type': 'application/json' });
      return res.end(JSON.stringify({ error: 'partnerId required' }));
    }
  
    const root = await prisma.binaryNode.findUnique({
        where: { partnerId },
        select: { id: true, partnerId: true, parentId: true, side: true, leftVolume: true, rightVolume: true },
      });
      
  
    if (!root) {
      res.writeHead(404, { 'Content-Type': 'application/json' });
      return res.end(JSON.stringify({ error: 'binary node not found' }));
    }
  
    async function build(nodeId, d) {
      const node = await prisma.binaryNode.findUnique({
        where: { id: nodeId },
        select: { id: true, partnerId: true, parentId: true, side: true, leftVolume: true, rightVolume: true },
      });
  
      if (d <= 0) return { ...node, children: [] };
  
      const children = await prisma.binaryNode.findMany({
        where: { parentId: nodeId },
        select: { id: true, partnerId: true, parentId: true, side: true, leftVolume: true, rightVolume: true },
        orderBy: { side: 'asc' },
      });
  
      const built = [];
      for (const c of children) built.push(await build(c.id, d - 1));
  
      return { ...node, children: built };
    }
  
    const tree = await build(root.id, depth);
  
    res.writeHead(200, { 'Content-Type': 'application/json' });
    return res.end(JSON.stringify(tree));
  }
 // POST /debug/add-volume
// body: { partnerId, dv, note? }
if (req.method === 'POST' && req.url === '/debug/add-volume') {
    const body = await readJson(req);
    const { partnerId, dv, note = null } = body;
  
    if (!partnerId || typeof dv !== 'number' || dv <= 0) {
      res.writeHead(400, { 'Content-Type': 'application/json' });
      return res.end(JSON.stringify({ error: 'partnerId and dv(number>0) required' }));
    }
  
    const result = await prisma.$transaction(async (tx) => {
      // 1) ledger
      const ledger = await tx.volumeLedger.create({
        data: { partnerId, dv, note },
      });
  
      // 2) find node
      const startNode = await tx.binaryNode.findUnique({
        where: { partnerId },
        select: { id: true, parentId: true, side: true },
      });
      if (!startNode) throw new Error('binary node not found for partnerId');
  
      // 3) walk up
const updates = [];
const weekStart = getWeekStartUTC(new Date()); // Пн 00:00 UTC

let current = startNode;

while (current.parentId) {
  const parentId = current.parentId;

  const parentNode = await tx.binaryNode.findUnique({
    where: { id: parentId },
    select: { partnerId: true, parentId: true, side: true },
  });
  if (!parentNode) break;

  if (current.side === 'LEFT') {
    await tx.binaryNode.update({
      where: { id: parentId },
      data: { leftVolume: { increment: dv } },
    });

    await tx.weeklyBinaryStats.upsert({
      where: { partnerId_weekStart: { partnerId: parentNode.partnerId, weekStart } },
      update: { leftDv: { increment: dv } },
      create: { partnerId: parentNode.partnerId, weekStart, leftDv: dv, rightDv: 0 },
    });
  } else {
    await tx.binaryNode.update({
      where: { id: parentId },
      data: { rightVolume: { increment: dv } },
    });

    await tx.weeklyBinaryStats.upsert({
      where: { partnerId_weekStart: { partnerId: parentNode.partnerId, weekStart } },
      update: { rightDv: { increment: dv } },
      create: { partnerId: parentNode.partnerId, weekStart, leftDv: 0, rightDv: dv },
    });
  }

  updates.push({ parentId, side: current.side, added: dv });

  current = {
    id: parentId,
    parentId: parentNode.parentId,
    side: parentNode.side,
  };
}
      return { ledger, updatesCount: updates.length, updates };
    });
  
    res.writeHead(200, { 'Content-Type': 'application/json' });
    return res.end(JSON.stringify(result));
  }
   // POST /purchase
// body: { buyerType:"PARTNER"|"CUSTOMER", buyerId, items:[{name, priceRub, dv}], useCashbackRub?: number }
if (req.method === 'POST' && req.url === '/purchase') {
  const body = await readJson(req);
  const { buyerType, buyerId, items = [], useCashbackRub = 0 } = body;

  if (!buyerType || !buyerId || !Array.isArray(items) || items.length === 0) {
    res.writeHead(400, { 'Content-Type': 'application/json' });
    return res.end(JSON.stringify({ error: 'buyerType, buyerId, items[] required' }));
  }

  const totalPrice = items.reduce((s, i) => s + (Number(i.priceRub) || 0), 0);
  const totalDv = items.reduce((s, i) => s + (Number(i.dv) || 0), 0);

  if (totalPrice <= 0 || totalDv <= 0) {
    res.writeHead(400, { 'Content-Type': 'application/json' });
    return res.end(JSON.stringify({ error: 'totalPrice and totalDv must be > 0' }));
  }

  const result = await prisma.$transaction(async (tx) => {
    // resolve partnerId for sponsor payouts + binary
    let partnerId = null;
    let customerId = null;
    let buyerPartnerId = null;
    let customer = null;
    let upgradedPartner = null;

    if (buyerType === 'PARTNER') {
      partnerId = buyerId;
      buyerPartnerId = buyerId;
    } else if (buyerType === 'CUSTOMER') {
      customer = await tx.customer.findUnique({
        where: { id: buyerId },
        select: { id: true, userId: true, partnerId: true },
      });
      if (!customer) throw new Error('customer not found');
      customerId = customer.id;
      partnerId = customer.partnerId; // sponsor (if any)
    } else {
      throw new Error('buyerType must be PARTNER or CUSTOMER');
    }

    // create Order (теперь схема позволяет)
    const order = await tx.order.create({
      data: {
        customerId,
        buyerPartnerId,
        totalPrice,
        dv: totalDv,
        items: { create: items.map(i => ({ product: i.name, price: i.priceRub, dv: i.dv })) },
      },
      include: { items: true },
    });

// ===== Partner activation / monthly keep =====
// Маркетинг:
// - чтобы активировать партнёрство: ЛЗ за месяц >= 200 DV
// - чтобы оставаться активным: ЛЗ за месяц >= 100 DV
if (buyerType === 'PARTNER') {
  const p = await tx.partner.findUnique({
    where: { id: partnerId },
    select: { id: true, activatedAt: true, status: true },
  });

  const selfMonthDvBuyer = await getSelfMonthDv(tx, partnerId, order.createdAt);

  // активация (первый вход в партнёрку)
  if (!p.activatedAt && selfMonthDvBuyer >= 200) {
    await tx.partner.update({
      where: { id: partnerId },
      data: { status: 'ACTIVE', activatedAt: order.createdAt, deactivatedAt: null },
    });
  }

  // мягкая реактивация (если был INACTIVE и снова сделал ЛЗ >= 100 в этом месяце)
  if (p.activatedAt && p.status === 'INACTIVE' && selfMonthDvBuyer >= 100) {
    await tx.partner.update({
      where: { id: partnerId },
      data: { status: 'ACTIVE', deactivatedAt: null },
    });
  }
}

// ===== Customer upgrade to Partner =====
// Если клиент набрал >=200 DV за месяц, создаем партнера
if (buyerType === 'CUSTOMER') {
  const selfMonthDvCustomer = await getCustomerMonthDv(tx, customerId, order.createdAt);
  if (selfMonthDvCustomer >= 200) {
    const existingPartner = await tx.partner.findFirst({
      where: { userId: customer.userId },
      select: { id: true },
    });

    if (!existingPartner) {
      const partner = await tx.partner.create({
        data: { userId: customer.userId, sponsorId: customer.partnerId },
      });

      await tx.wallet.create({
        data: { partnerId: partner.id, balance: 0 },
      });

      await placeInBinary(tx, customer.partnerId, 'LEFT', partner.id);

      await tx.user.update({
        where: { id: customer.userId },
        data: { role: 'PARTNER' },
      });

      await tx.order.update({
        where: { id: order.id },
        data: { buyerPartnerId: partner.id },
      });

      upgradedPartner = partner;
      partnerId = partner.id; // теперь это личная покупка партнера

      await ensurePartnerMonthlyStatus(tx, partner.id, order.createdAt);
    }
  }
}


    if (partnerId) {
      // Volume ledger (DV event)
      await tx.volumeLedger.create({
        data: { partnerId, orderId: order.id, dv: totalDv, note: 'purchase' },
      });
    }
// ===== update buyer partner status (активация/продление) =====
if (buyerType === 'PARTNER') {
  await ensurePartnerMonthlyStatus(tx, buyerId, order.createdAt);
}

    // Binary: accumulate volume upwards
    if (partnerId) {
      const startNode = await tx.binaryNode.findUnique({
        where: { partnerId },
        select: { id: true, parentId: true, side: true },
      });
      if (!startNode) throw new Error('binary node not found for partner');
      const weekStart = getWeekStartUTC(new Date());


    let current = startNode;
const visitedNodeIds = new Set();
let hops = 0;
while (current.parentId) {
  hops += 1;
  if (hops > 512) {
    // Защита от аномально длинной/поврежденной цепочки родителей.
    break;
  }
  if (visitedNodeIds.has(current.parentId)) {
    // Защита от циклов в бинарном дереве.
    break;
  }
  visitedNodeIds.add(current.parentId);
  const parentNodeId = current.parentId;

  // берём родителя, чтобы знать его partnerId и идти выше
  const parentNode = await tx.binaryNode.findUnique({
    where: { id: parentNodeId },
    select: { id: true, partnerId: true, parentId: true, side: true },
  });
  if (!parentNode) break;

  if (current.side === 'LEFT') {
    // общий накопитель (можно оставить)
    await tx.binaryNode.update({
      where: { id: parentNodeId },
      data: { leftVolume: { increment: totalDv } },
    });

    // недельный накопитель
    await tx.weeklyBinaryStats.upsert({
      where: { partnerId_weekStart: { partnerId: parentNode.partnerId, weekStart } },
      update: { leftDv: { increment: totalDv } },
      create: { partnerId: parentNode.partnerId, weekStart, leftDv: totalDv, rightDv: 0 },
    });
  } else {
    await tx.binaryNode.update({
      where: { id: parentNodeId },
      data: { rightVolume: { increment: totalDv } },
    });

    await tx.weeklyBinaryStats.upsert({
      where: { partnerId_weekStart: { partnerId: parentNode.partnerId, weekStart } },
      update: { rightDv: { increment: totalDv } },
      create: { partnerId: parentNode.partnerId, weekStart, leftDv: 0, rightDv: totalDv },
    });
  }

  // двигаемся вверх
  current = parentNode;
}
    }

 // ===== 3 круга влияния (маркетинг) =====
const chain = await getSponsorChain3(tx, partnerId);

// важно: now должен быть определён
const now = order?.createdAt ? new Date(order.createdAt) : new Date();

const pctByLevel = { 1: 5, 2: 3, 3: 2 };

function getMonthInfluenceLevel(selfMonthDv) {
  if (selfMonthDv >= 300) return 3;
  if (selfMonthDv >= 200) return 2;
  if (selfMonthDv >= 100) return 1;
  return 0;
}

const influence = [];

for (const c of chain) {
  // 1) статус спонсора (ACTIVE обязателен)
  const sponsor = await ensurePartnerMonthlyStatus(tx, c.partnerId, now);
  if (!sponsor || sponsor.status !== 'ACTIVE') continue;

  // 2) сколько кругов открыто у спонсора в этом месяце
  const selfMonthDv = await getSelfMonthDv(tx, c.partnerId, now);
  const sponsorLevel = getMonthInfluenceLevel(selfMonthDv);

  // ✅ ключевая проверка: круг должен быть открыт
  if (c.level > sponsorLevel) continue;

  // 3) расчет и начисление
  const amount = Math.floor((totalDv * 30 * (pctByLevel[c.level] || 0)) / 100);

  await creditPartnerWallet(tx, c.partnerId, {
    type: `INFLUENCE_L${c.level}`,
    amount,
    note: `from=${partnerId} order=${order.id} selfMonthDv=${selfMonthDv}`,
  });

  influence.push({ level: c.level, sponsorPartnerId: c.partnerId, amount, selfMonthDv });
}







    // Partner cashback wallet: начисление по ЛЗ (2/3/5%) + списание при оплате
    let cashback = null;
    if (buyerType === 'PARTNER') {
      let cbw = await tx.partnerCashbackWallet.findUnique({ where: { partnerId } });
      if (!cbw) cbw = await tx.partnerCashbackWallet.create({ data: { partnerId, balance: 0 } });

      const personalMonthDv = await getSelfMonthDv(tx, partnerId, now);
      const cashbackPercent = getPartnerCashbackPercentByMonthDv(personalMonthDv);
      const earned = cashbackPercent > 0 ? Math.floor((totalPrice * cashbackPercent) / 100) : 0;
      if (earned > 0) {
        cbw = await tx.partnerCashbackWallet.update({
          where: { id: cbw.id },
          data: { balance: { increment: earned } },
        });
        await tx.cashbackTransaction.create({
          data: { walletId: cbw.id, amount: earned, type: 'CASHBACK_EARN', note: `order=${order.id} pct=${cashbackPercent}` },
        });
        await tx.bonusLedger.create({
          data: { partnerId, type: 'PARTNER_CASHBACK', amount: earned, note: `order=${order.id} pct=${cashbackPercent}` },
        });
      }

      const spend = Math.max(0, Math.floor(useCashbackRub || 0));
      if (spend > 0) {
        if (cbw.balance < spend) throw new Error('not enough cashback balance');
        cbw = await tx.partnerCashbackWallet.update({ where: { id: cbw.id }, data: { balance: { decrement: spend } } });
        await tx.cashbackTransaction.create({ data: { walletId: cbw.id, amount: -spend, type: 'CASHBACK_SPEND', note: `order=${order.id}` } });
      }

      cashback = { walletBalance: cbw.balance, spent: spend, earned, cashbackPercent };
    }

  return { order, influence, cashback, dv: totalDv, totalPrice, upgradedPartner };
  }, {
    // В /purchase много последовательных операций (order + binary + бонусы),
    // дефолтного таймаута интерактивной транзакции Prisma иногда не хватает.
    maxWait: 10000,
    timeout: 60000,
  });

  res.writeHead(201, { 'Content-Type': 'application/json' });
  return res.end(JSON.stringify(result));
}
 // POST /jobs/weekly-balance
// body: { weekStart?: "YYYY-MM-DDTHH:mm:ss.sssZ" }  // optional, default = current week start
if (req.method === 'POST' && req.url === '/jobs/weekly-balance') {
  const body = await readJson(req);
  const weekStart = body?.weekStart ? new Date(body.weekStart) : getWeekStartUTC(new Date());

  const result = await prisma.$transaction(async (tx) => {
    // 1) Берём все weekly-строки за эту неделю
    const rows = await tx.weeklyBinaryStats.findMany({
      where: { weekStart },
      select: { id: true, partnerId: true, leftDv: true, rightDv: true },
    });

    const payouts = [];
    for (const r of rows) {
      const left = r.leftDv || 0;
      const right = r.rightDv || 0;
      const pairDv = Math.min(left, right);
      if (pairDv <= 0) continue;

      // 2) ЛЗ партнёра за эту же неделю (сумма DV его покупок)
      const selfWeekDvAgg = await tx.volumeLedger.aggregate({
        where: {
          partnerId: r.partnerId,
          createdAt: { gte: weekStart },
          orderId: { not: null }, // ЛЗ считаем ТОЛЬКО по покупкам
        },
        _sum: { dv: true },
      });
      const selfWeekDv = Number(selfWeekDvAgg._sum.dv || 0);

      // 3) Процент по ЛЗ
      let percent = 0;
      if (selfWeekDv >= 300) percent = 5;
      else if (selfWeekDv >= 200) percent = 3;
      else if (selfWeekDv >= 100) percent = 2;

      if (percent <= 0) continue;

      const amount = Math.floor((pairDv * 30 * percent) / 100); // рубли

      // 4) Начисляем деньги в wallet
      const wallet = await tx.wallet.findFirst({
        where: { partnerId: r.partnerId },
      });      
      if (!wallet) continue;

      await tx.wallet.update({
        where: { id: wallet.id },
        data: { balance: { increment: amount } },
      });

      await tx.walletTransaction.create({
        data: { walletId: wallet.id, amount, type: 'BALANCE_WEEKLY', note: `weekStart=${weekStart.toISOString()} pairDv=${pairDv} percent=${percent}` },
      });

      await tx.bonusLedger.create({
        data: { partnerId: r.partnerId, type: 'BALANCE_WEEKLY', amount, note: `weekStart=${weekStart.toISOString()} pairDv=${pairDv} percent=${percent} selfWeekDv=${selfWeekDv}` },
      });

      // 5) Carry: списываем pairDv с обеих ног
      const newLeft = left - pairDv;
      const newRight = right - pairDv;

      await tx.weeklyBinaryStats.update({
        where: { id: r.id },
        data: { leftDv: newLeft, rightDv: newRight },
      });

      payouts.push({
        partnerId: r.partnerId,
        pairDv,
        percent,
        amount,
        selfWeekDv,
        after: { leftDv: newLeft, rightDv: newRight },
      });
    }

    return { weekStart: weekStart.toISOString(), processed: rows.length, payoutsCount: payouts.length, payouts };
  });

  res.writeHead(200, { 'Content-Type': 'application/json' });
  return res.end(JSON.stringify(result));
}
// GET /debug/wallet?partnerId=...
if (req.method === 'GET' && req.url.startsWith('/debug/wallet')) {
  const url = new URL(req.url, 'http://localhost');
  const partnerId = url.searchParams.get('partnerId');
  if (!partnerId) {
    res.writeHead(400, { 'Content-Type': 'application/json' });
    return res.end(JSON.stringify({ error: 'partnerId required' }));
  }

  const wallet = await prisma.wallet.findFirst({ where: { partnerId } });
  if (!wallet) {
    res.writeHead(404, { 'Content-Type': 'application/json' });
    return res.end(JSON.stringify({ error: 'wallet not found' }));
  }

  res.writeHead(200, { 'Content-Type': 'application/json' });
  return res.end(JSON.stringify(wallet));
}

    // Static files: dashboard
    if (req.method === 'GET' && req.url === '/partner/dashboard') {
      const filePath = path.join(__dirname, '..', 'public', 'partner', 'dashboard.html');
      try {
        const content = fs.readFileSync(filePath, 'utf8');
        res.writeHead(200, { 'Content-Type': 'text/html; charset=utf-8' });
        return res.end(content);
      } catch (e) {
        res.writeHead(404, { 'Content-Type': 'text/plain' });
        return res.end('Dashboard not found');
      }
    }

    // Static files: CSS
    if (req.method === 'GET' && req.url.startsWith('/css/')) {
      const filePath = path.join(__dirname, '..', 'public', req.url);
      try {
        const content = fs.readFileSync(filePath);
        res.writeHead(200, { 'Content-Type': 'text/css' });
        return res.end(content);
      } catch (e) {
        res.writeHead(404, { 'Content-Type': 'text/plain' });
        return res.end('CSS not found');
      }
    }

    // Static files: images
    if (req.method === 'GET' && req.url.startsWith('/img/')) {
      const filePath = path.join(__dirname, '..', 'public', req.url);
      try {
        const content = fs.readFileSync(filePath);
        const ext = path.extname(filePath).toLowerCase();
        const mimeTypes = {
          '.png': 'image/png',
          '.jpg': 'image/jpeg',
          '.jpeg': 'image/jpeg',
          '.svg': 'image/svg+xml',
          '.gif': 'image/gif'
        };
        res.writeHead(200, { 'Content-Type': mimeTypes[ext] || 'image/png' });
        return res.end(content);
      } catch (e) {
        res.writeHead(404, { 'Content-Type': 'text/plain' });
        return res.end('Image not found');
      }
    }

    res.writeHead(404, { 'Content-Type': 'application/json' });
    res.end(JSON.stringify({ error: 'not found' }));
  } catch (e) {
    console.error(e);
    res.writeHead(500, { 'Content-Type': 'application/json' });
    res.end(JSON.stringify({ error: 'server error' }));
  }
});

server.listen(3000, () => {
  console.log('Server on http://localhost:3000');
});
