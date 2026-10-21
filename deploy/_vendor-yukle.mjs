// Tek seferlik (20.10.2026): composer güncellemesinde değişen paketleri + public/ filament asset'lerini yükler.
// node deploy/_vendor-yukle.mjs <eski.lock> <--no-dev kurulum klasörü> [--gercek]
import { Client } from 'basic-ftp';
import fs from 'node:fs';
import path from 'node:path';
import { execSync } from 'node:child_process';
const [eskiLock, kaynak] = process.argv.slice(2);
const GERCEK = process.argv.includes('--gercek');
const o = JSON.parse(fs.readFileSync(eskiLock, 'utf8')), n = JSON.parse(fs.readFileSync('composer.lock', 'utf8'));
const eski = new Map(o.packages.map(p => [p.name, p.version]));
const degisen = n.packages.filter(p => eski.get(p.name) !== p.version).map(p => p.name);
const ATLA = new Set(['.git', 'tests', 'Tests', 'docs', '.github']);
const liste = [];
const gez = (yerel, uzak) => { for (const e of fs.readdirSync(yerel, { withFileTypes: true })) { if (ATLA.has(e.name) || e.isSymbolicLink()) continue; const y = path.join(yerel, e.name), u = uzak + '/' + e.name; e.isDirectory() ? gez(y, u) : liste.push([y, u]); } };
for (const p of degisen) { if (!fs.existsSync(`${kaynak}/vendor/${p}`)) { console.error('YOK', p); process.exit(1); } gez(`${kaynak}/vendor/${p}`, `mehse-app/vendor/${p}`); }
// composer/class-map-generator (yeni paket) vendor/composer altında durur
if (fs.existsSync(`${kaynak}/vendor/composer/class-map-generator`)) gez(`${kaynak}/vendor/composer/class-map-generator`, 'mehse-app/vendor/composer/class-map-generator');
const pub = execSync('git diff --name-only HEAD~1 HEAD -- public', { encoding: 'utf8' }).split('\n').filter(Boolean);
for (const f of pub) liste.push([f, f.replace(/^public\//, '')]);
const autoload = ['composer/autoload_static.php', 'composer/autoload_real.php', 'autoload.php', 'composer/autoload_classmap.php', 'composer/autoload_files.php', 'composer/autoload_namespaces.php', 'composer/autoload_psr4.php', 'composer/ClassLoader.php', 'composer/InstalledVersions.php', 'composer/installed.json', 'composer/installed.php', 'composer/platform_check.php'];
for (const a of autoload) liste.push([`${kaynak}/vendor/${a}`, `mehse-app/vendor/${a}`]);
console.log(`paket: ${degisen.length}, public: ${pub.length}, toplam dosya: ${liste.length}`);
if (!GERCEK) process.exit(0);
const env = {};
for (const l of fs.readFileSync('.env.deploy', 'utf8').split(/\r?\n/)) { const i = l.indexOf('='); if (i > 0 && !l.trim().startsWith('#')) env[l.slice(0, i).trim()] = l.slice(i + 1).trim().replace(/^["']|["']$/g, ''); }
const baglan = async () => { const c = new Client(60000); await c.access({ host: env.FTP_HOST, port: Number(env.FTP_PORT || 21), user: env.FTP_USER, password: env.FTP_PASSWORD, secure: true, secureOptions: { rejectUnauthorized: false } }); return c; };
let c = await baglan(), yuklenen = 0, cur = null;
for (const [y, u] of liste) {
  for (let deneme = 1; ; deneme++) {
    try { const d = path.posix.dirname(u); if (d !== cur) { await c.ensureDir('/' + d); cur = d; } await c.uploadFrom(y, path.posix.basename(u)); break; }
    catch (e) { if (deneme >= 5) { console.error('HATA', u, e.message); process.exit(1); } try { c.close(); } catch {} await new Promise(r => setTimeout(r, 2000)); c = await baglan(); cur = null; }
  }
  if (++yuklenen % 200 === 0) console.log(`${yuklenen}/${liste.length}`);
}
c.close(); console.log('BITTI', yuklenen);
