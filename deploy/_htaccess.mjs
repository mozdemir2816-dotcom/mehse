import { Client } from 'basic-ftp';
import fs from 'node:fs';
const env = {};
for (const l of fs.readFileSync('.env.deploy', 'utf8').split(/\r?\n/)) { const i = l.indexOf('='); if (i > 0 && !l.trim().startsWith('#')) env[l.slice(0, i).trim()] = l.slice(i + 1).trim().replace(/^["']|["']$/g, ''); }
const c = new Client(30000);
try {
  await c.access({ host: env.FTP_HOST, port: Number(env.FTP_PORT || 21), user: env.FTP_USER, password: env.FTP_PASSWORD, secure: true, secureOptions: { rejectUnauthorized: false } });
  await c.cd('/');
  const [mod, a, b] = process.argv.slice(2);
  if (mod === 'indir') await c.downloadTo(a, '.htaccess');
  if (mod === 'yukle') await c.uploadFrom(a, '.htaccess');
} finally { c.close(); }
