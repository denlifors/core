const fs = require("fs");
const path = require("path");

function loadEnv(filePath){
  if(!fs.existsSync(filePath)) return;
  const lines = fs.readFileSync(filePath, "utf8").split(/\r?\n/);
  for (const raw of lines) {
    const line = raw.trim();
    if (!line || line.startsWith("#")) continue;
    const i = line.indexOf("=");
    if (i < 0) continue;
    const k = line.slice(0, i).trim();
    let v = line.slice(i + 1).trim();
    if ((v.startsWith('"') && v.endsWith('"')) || (v.startsWith("'") && v.endsWith("'"))) {
      v = v.slice(1, -1);
    }
    process.env[k] = v;
  }
}

loadEnv(path.join(process.cwd(), ".env"));

(async () => {
  const { PrismaClient } = require("@prisma/client");
  const db = new PrismaClient();
  try {
    await db.$connect();
    console.log("CORE DB: OK");
  } catch (e) {
    console.log("CORE DB: FAIL");
    console.error(e.message);
    process.exitCode = 1;
  } finally {
    try { await db.$disconnect(); } catch {}
  }
})();
