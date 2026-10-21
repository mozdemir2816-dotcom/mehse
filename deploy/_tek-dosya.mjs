import { Client } from 'basic-ftp';
import fs from 'node:fs';
const env = {};
for (const l of fs.readFileSync('.env.deploy', 'utf8').split(/\r?\n/)) { const i = l.indexOf('='); if (i > 0 && !l.trim().startsWith('#')) env[l.slice(0, i).trim()] = l.slice(i + 1).trim().replace(/^["']|["']$/g, ''); }
const c = new Client(30000);
await c.access({ host: env.FTP_HOST, port: Number(env.FTP_PORT || 21), user: env.FTP_USER, password: env.FTP_PASSWORD, secure: true, secureOptions: { rejectUnauthorized: false } });
await c.cd('/');
await c.ensureDir('mehse-app/resources/views/kroki3b');
await c.uploadFrom('resources/views/kroki3b/editor.blade.php', 'editor.blade.php');
console.log('yüklendi', (await c.size('editor.blade.php')));
c.close();
