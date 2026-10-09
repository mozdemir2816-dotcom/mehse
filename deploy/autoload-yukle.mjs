// Sunucu vendor/composer + vendor/autoload.php dosyalarını --no-dev kurulumdan yükler./n// deploy.mjs bunları bilerek atlar: yerel autoload dev paketlerini ister, sunucuda/n// yoklar -> site 500 (10.10.2026, smalot/pdfparser eklenince yaşandı)./n// composer.lock değiştiğinde (paket ekleme/güncelleme), deploy.mjs ile birlikte:/n//   1) geçici klasöre composer.json, composer.lock, app/, database/ kopyala/n//   2) orada: php composer.phar install --no-dev --no-scripts --no-interaction/n//   3) node deploy/autoload-yukle.mjs <geçici klasör>/nimport { Client } from 'basic-ftp';
import fs from 'node:fs';
const kaynak = process.argv[2];
const env = {};
for (const line of fs.readFileSync('.env.deploy', 'utf8').split(/\r?\n/)) { const t = line.trim(); if (!t || t.startsWith('#')) continue; const i = t.indexOf('='); if (i > 0) env[t.slice(0, i).trim()] = t.slice(i + 1).trim(); }
const dosyalar = ['composer/autoload_static.php', 'composer/autoload_real.php', 'autoload.php', 'composer/autoload_classmap.php', 'composer/autoload_files.php', 'composer/autoload_namespaces.php', 'composer/autoload_psr4.php', 'composer/ClassLoader.php', 'composer/InstalledVersions.php', 'composer/installed.json', 'composer/installed.php', 'composer/platform_check.php'];
const c = new Client(60000);
try {
  await c.access({ host: env.FTP_HOST, port: Number(env.FTP_PORT || 21), user: env.FTP_USER, password: env.FTP_PASSWORD, secure: true, secureOptions: { rejectUnauthorized: false } });
  for (const d of dosyalar) { await c.uploadFrom(kaynak + '/vendor/' + d, 'mehse-app/vendor/' + d); console.log('yüklendi ' + d); }
} catch (e) { console.error('HATA', e.message); process.exitCode = 1; } finally { c.close(); }
