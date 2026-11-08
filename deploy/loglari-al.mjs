// Canlıdaki günlükleri (yalnız okuma) yerel klasöre indirir — teşhis için (08.11.2026).
// Kullanım: node deploy/loglari-al.mjs <hedef-klasör>
import { Client } from 'basic-ftp';
import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const ROOT = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '..');
const env = {};
for (const l of fs.readFileSync(path.join(ROOT, '.env.deploy'), 'utf8').split(/\r?\n/)) {
    const i = l.indexOf('=');
    if (i > 0 && !l.trim().startsWith('#')) env[l.slice(0, i).trim()] = l.slice(i + 1).trim();
}
const hedef = process.argv[2] || '.';
fs.mkdirSync(hedef, { recursive: true });

const c = new Client(30000);
try {
    await c.access({ host: env.FTP_HOST, port: Number(env.FTP_PORT || 21), user: env.FTP_USER, password: env.FTP_PASSWORD,
        secure: env.FTP_SECURE === 'false' ? false : true, secureOptions: { rejectUnauthorized: false } });
    const kok = env.FTP_APP_DIR || 'mehse-app';
    for (const dizin of [`${kok}/storage/logs`, 'logs', '.']) {
        try {
            const liste = await c.list(dizin);
            for (const f of liste) {
                if (f.isFile && /(\.log|error_log)$/.test(f.name)) {
                    console.log(`${dizin}/${f.name}\t${f.size}\t${f.rawModifiedAt}`);
                    if (f.size < 30_000_000) await c.downloadTo(path.join(hedef, dizin.replace(/[\/.]/g, '_') + '__' + f.name), `${dizin}/${f.name}`);
                }
            }
        } catch (e) { console.log(`(${dizin}: ${e.message})`); }
    }
} finally {
    c.close();
}
