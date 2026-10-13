// Tek seferlik: canlı laravel.log'u indirir (node deploy/_log-indir.mjs <hedef>).
import { Client } from 'basic-ftp';
import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';
const ROOT = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '..');
const env = {};
for (const l of fs.readFileSync(path.join(ROOT, '.env.deploy'), 'utf8').split(/\r?\n/)) { const i = l.indexOf('='); if (i > 0 && !l.trim().startsWith('#')) env[l.slice(0, i).trim()] = l.slice(i + 1).trim(); }
const c = new Client(30_000);
try {
    await c.access({ host: env.FTP_HOST, port: Number(env.FTP_PORT || 21), user: env.FTP_USER, password: env.FTP_PASSWORD, secure: true, secureOptions: { rejectUnauthorized: false } });
    const liste = await c.list('mehse-app/storage/logs');
    console.log(liste.map(f => f.name + ' ' + f.size).join('\n'));
    await c.downloadTo(process.argv[2], 'mehse-app/storage/logs/laravel.log');
} finally { c.close(); }
