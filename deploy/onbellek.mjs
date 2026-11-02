// Canlı sunucuda Laravel önbelleklerini yeniler (config, rota, view, event, Filament bileşen + Blade ikon).
//
// Kullanım:
//   node deploy/onbellek.mjs            -> php artisan optimize        (önbellekleri kur / yenile)
//   node deploy/onbellek.mjs --temizle  -> php artisan optimize:clear  (geri al: önbelleksiz çalış)
//   node deploy/onbellek.mjs --komut "risk:puan-tamamla --rapor=RD-2026-127"
//                                       -> canlıda tek bir artisan komutu (yalnız risk:* komutları, 02.11.2026)
//
// deploy.mjs her yüklemenin sonunda bunu kendiliğinden çağırır (--onbelleksiz ile atlanır).
// Önbellek kuruluyken config/*.php değişiklikleri ancak bu yenilemeyle canlıya yansır.
//
// Paylaşımlı sunucuda SSH yok: rastgele adlı, gizli anahtarlı tek seferlik bir PHP dosyası
// web köküne yüklenir, HTTPS ile çağrılır, dosya ilk satırda kendini siler; ayrıca finally'de
// FTP ile de silinir. Anahtar yanlışsa 404 döner. (02.11.2026 — canlı yavaşlık incelemesi.)

import { Client } from 'basic-ftp';
import crypto from 'node:crypto';
import fs from 'node:fs';
import os from 'node:os';
import path from 'node:path';
import { fileURLToPath, pathToFileURL } from 'node:url';

const ROOT = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '..');

function envDeploy() {
    const env = {};
    for (const l of fs.readFileSync(path.join(ROOT, '.env.deploy'), 'utf8').split(/\r?\n/)) {
        const i = l.indexOf('=');
        if (i > 0 && !l.trim().startsWith('#')) env[l.slice(0, i).trim()] = l.slice(i + 1).trim();
    }
    return env;
}

function phpBetik(anahtar, komut) {
    return `<?php
// mehse — tek seferlik önbellek işlemi (deploy/onbellek.mjs yükler, çağırır, siler).
if (!hash_equals('${anahtar}', (string) ($_GET['t'] ?? ''))) { http_response_code(404); exit; }
@unlink(__FILE__);
@set_time_limit(300);
header('Content-Type: text/plain; charset=utf-8');
define('LARAVEL_START', microtime(true));
require __DIR__.'/mehse-app/vendor/autoload.php';
$app = require __DIR__.'/mehse-app/bootstrap/app.php';
$app->make(Illuminate\\Contracts\\Console\\Kernel::class)->bootstrap();
try {
    $kod = Illuminate\\Support\\Facades\\Artisan::call('${komut}');
    echo preg_replace('/\\e\\[[0-9;]*m/', '', Illuminate\\Support\\Facades\\Artisan::output());
    echo "\\nSONUC=".$kod."\\n";
} catch (Throwable $e) {
    echo 'HATA: '.get_class($e).': '.$e->getMessage()."\\nSONUC=1\\n";
}
if (function_exists('opcache_reset')) { echo 'OPCACHE='.(@opcache_reset() ? 'sifirlandi' : 'kapali')."\\n"; }
`;
}

/** Önbelleği yeniler (temizle=true ise kaldırır). Başarılıysa true döner. */
export async function onbellekYenile({ temizle = false, sessiz = false, ozelKomut = null } = {}) {
    const env = envDeploy();
    const site = (env.SITE_URL || 'https://mehse.com').replace(/\/$/, '');
    if (ozelKomut && !/^risk:[a-z:-]+( --?[a-zA-Z-]+(=[A-Za-z0-9._-]+)?)*$/.test(ozelKomut)) {
        throw new Error(`İzin verilmeyen komut: ${ozelKomut} (yalnız "risk:..." ve basit seçenekler)`);
    }
    const komut = ozelKomut ?? (temizle ? 'optimize:clear' : 'optimize');
    const anahtar = crypto.randomBytes(24).toString('hex');
    const dosya = `onbellek-${crypto.randomBytes(8).toString('hex')}.php`;
    const yerel = path.join(os.tmpdir(), dosya);
    fs.writeFileSync(yerel, phpBetik(anahtar, komut));

    const c = new Client(30_000);
    const log = (...a) => { if (!sessiz) console.log(...a); };
    try {
        await c.access({
            host: env.FTP_HOST, port: Number(env.FTP_PORT || 21), user: env.FTP_USER, password: env.FTP_PASSWORD,
            secure: env.FTP_SECURE === 'false' ? false : true, secureOptions: { rejectUnauthorized: false },
        });
        await c.uploadFrom(yerel, dosya);
        log(`[onbellek] php artisan ${komut} çalıştırılıyor...`);

        const yanit = await fetch(`${site}/${dosya}?t=${anahtar}`, { signal: AbortSignal.timeout(240_000) });
        const metin = await yanit.text();
        const basarili = yanit.ok && /SONUC=0/.test(metin);
        log(metin.split('\n').filter((s) => s.trim()).map((s) => '[onbellek]   ' + s.trim()).join('\n'));
        log(basarili ? '[onbellek] tamamlandı.' : `[onbellek] UYARI: başarısız (HTTP ${yanit.status}). Geri almak için: node deploy/onbellek.mjs --temizle`);

        return basarili;
    } finally {
        try { await c.remove(dosya); } catch { /* betik kendini zaten sildi */ }
        c.close();
        fs.rmSync(yerel, { force: true });
    }
}

// Doğrudan çalıştırıldıysa
if (import.meta.url === pathToFileURL(process.argv[1]).href) {
    const i = process.argv.indexOf('--komut');
    const ok = await onbellekYenile({ temizle: process.argv.includes('--temizle'), ozelKomut: i > -1 ? process.argv[i + 1] : null });
    process.exit(ok ? 0 : 1);
}
