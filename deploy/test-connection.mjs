// Salt-okunur bağlantı testi: giriş yapar, kök dizini listeler, çıkar. Hiçbir şey yüklemez/silmez.
import { Client } from 'basic-ftp';
import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const envPath = path.join(__dirname, '..', '.env.deploy');
const env = {};
for (const line of fs.readFileSync(envPath, 'utf8').split(/\r?\n/)) {
    const t = line.trim();
    if (!t || t.startsWith('#')) continue;
    const i = t.indexOf('=');
    if (i === -1) continue;
    env[t.slice(0, i).trim()] = t.slice(i + 1).trim();
}

const client = new Client(20_000);
try {
    await client.access({
        host: env.FTP_HOST,
        port: Number(env.FTP_PORT || 21),
        user: env.FTP_USER,
        password: env.FTP_PASSWORD,
        secure: env.FTP_SECURE === 'false' ? false : true,
        secureOptions: { rejectUnauthorized: false },
    });
    console.log('PWD:', await client.pwd());
    console.log('BAGLANTI OK. Kok dizin icerigi:');
    const list = await client.list();
    for (const item of list) console.log(' ', item.type === 2 ? '[DIR]' : '     ', item.name);

    const target = process.argv[2];
    if (target) {
        console.log(`\n--- ${target} icerigi ---`);
        const sub = await client.list(target);
        for (const item of sub) console.log(' ', item.type === 2 ? '[DIR]' : '     ', item.name);
    }
} catch (err) {
    console.error('BAGLANTI HATASI:', err.message);
    process.exitCode = 1;
} finally {
    client.close();
}
