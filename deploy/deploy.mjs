// Otomatik FTPS deploy scripti — mehse İSG
//
// Kullanım:
//   node deploy/deploy.mjs             -> build + değişen dosyaları yükle
//   node deploy/deploy.mjs --dry-run   -> hiçbir şey yüklemeden, ne yükleneceğini listele
//   node deploy/deploy.mjs --full      -> state dosyasını yok say, her şeyi yükle
//   node deploy/deploy.mjs --skip-build -> `npm run build` adımını atla
//
// Sunucu düzeni (root = FTP ana dizini = public_html; FTP hesabı deploy@mehse.com):
//   /index.php, /.htaccess, /build, /css, /js, /fonts, /favicon.ico, /robots.txt  (public/ içeriği)
//   /mehse-app/...                                                                (Laravel uygulaması)
//   /mehse-app/public/build                                    (manifest buradan okunur, bkz. collectFiles)
//
// FTP hesabının dizini public_html OLMALI. 30.09.2026'ya kadar kullanılan
// kelmemet@mehse.com başka bir klasöre bakıyordu, yüklemeler canlıya hiç ulaşmadı.
// Doğrulama: curl .../admin/login | grep theme-  → yeni build'in hash'i görünmeli.
//
// Güvenlik notları:
//   - .env hiçbir zaman yüklenmez (sunucudaki üretim .env'i local .env ile ezmemek için).
//   - storage/ hiçbir zaman yüklenmez (üretimdeki oturum/cache/yüklenen dosyalar silinmesin diye).
//   - Script hiçbir zaman sunucudan dosya SİLMEZ, sadece yeni/değişen dosyaları yükler.

import { Client } from 'basic-ftp';
import { execSync } from 'node:child_process';
import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const ROOT = path.resolve(__dirname, '..');

const args = process.argv.slice(2);
const BILINEN = ['--dry-run', '--full', '--skip-build'];
const bilinmeyen = args.filter((a) => !BILINEN.includes(a));
if (bilinmeyen.length) {
    // Yanlış yazılmış bayrak (ör. --dry) sessizce gerçek deploy'a dönüşmesin.
    console.error(`[deploy] bilinmeyen argüman: ${bilinmeyen.join(' ')} — geçerli: ${BILINEN.join(' ')}`);
    process.exit(1);
}
const DRY_RUN = args.includes('--dry-run');
const FULL = args.includes('--full');
const SKIP_BUILD = args.includes('--skip-build');

const STATE_FILE = path.join(ROOT, '.deploy-state.json');
const ENV_DEPLOY_FILE = path.join(ROOT, '.env.deploy');

function loadEnvDeploy() {
    if (!fs.existsSync(ENV_DEPLOY_FILE)) {
        throw new Error(`.env.deploy bulunamadı: ${ENV_DEPLOY_FILE}`);
    }
    const out = {};
    for (const line of fs.readFileSync(ENV_DEPLOY_FILE, 'utf8').split(/\r?\n/)) {
        const trimmed = line.trim();
        if (!trimmed || trimmed.startsWith('#')) continue;
        const idx = trimmed.indexOf('=');
        if (idx === -1) continue;
        const key = trimmed.slice(0, idx).trim();
        let value = trimmed.slice(idx + 1).trim();
        if ((value.startsWith('"') && value.endsWith('"')) || (value.startsWith("'") && value.endsWith("'"))) {
            value = value.slice(1, -1);
        }
        out[key] = value;
    }
    return out;
}

function loadState() {
    if (FULL || !fs.existsSync(STATE_FILE)) return { lastDeployAt: 0 };
    try {
        return JSON.parse(fs.readFileSync(STATE_FILE, 'utf8'));
    } catch {
        return { lastDeployAt: 0 };
    }
}

function saveState(state) {
    fs.writeFileSync(STATE_FILE, JSON.stringify(state, null, 2));
}

// --- Yüklenecek dosyaları belirle -----------------------------------------

const APP_INCLUDE_TOP_LEVEL = [
    'app',
    'bootstrap',
    'config',
    'database',
    'resources',
    'routes',
    'vendor',
    'artisan',
    'composer.json',
    'composer.lock',
];

const SKIP_NAMES = new Set(['.DS_Store', 'Thumbs.db', '.gitkeep']);
const SKIP_EXTENSIONS = new Set(['.log']);

function isSkippedEntry(name) {
    if (SKIP_NAMES.has(name)) return true;
    if (SKIP_EXTENSIONS.has(path.extname(name))) return true;
    return false;
}

/**
 * localDir altını gezip {localPath, remoteRelPath} listesi döner.
 * remoteRelPath, remoteBase'e göre POSIX ayracıyla verilir.
 */
function walk(localDir, remoteBase, out = []) {
    for (const entry of fs.readdirSync(localDir, { withFileTypes: true })) {
        if (isSkippedEntry(entry.name)) continue;
        const localPath = path.join(localDir, entry.name);
        const remoteRelPath = remoteBase ? `${remoteBase}/${entry.name}` : entry.name;
        if (entry.isSymbolicLink()) continue; // public/storage sembolik linki gibi şeyleri atla
        // Yerel önbellek (services/packages.php) dev paketlerine referans verebilir;
        // sunucu kendi önbelleğini provider listesi değişince kendisi yeniden üretir.
        if (remoteRelPath === 'mehse-app/bootstrap/cache') continue;
        // Sunucu vendor'ı --no-dev kurulu; yereldeki autoload dev paketlerini
        // (phpunit, collision…) ister ve başka bir sınıf soneki taşır. Yüklenirse
        // site 500 verir (10.10.2026). Bunları deploy/autoload-yukle.mjs yükler.
        if (remoteRelPath === 'mehse-app/vendor/composer' || remoteRelPath === 'mehse-app/vendor/autoload.php') continue;
        if (entry.isDirectory()) {
            walk(localPath, remoteRelPath, out);
        } else if (entry.isFile()) {
            out.push({ localPath, remoteRelPath });
        }
    }
    return out;
}

function collectFiles() {
    const files = [];

    // --- public/ -> FTP kökü ---
    const publicDir = path.join(ROOT, 'public');
    for (const entry of fs.readdirSync(publicDir, { withFileTypes: true })) {
        if (entry.name === 'storage') continue; // symlink, üretimde ayrı yönetiliyor
        if (entry.name === 'index.php') continue; // aşağıda özel işleniyor
        if (isSkippedEntry(entry.name)) continue;
        const localPath = path.join(publicDir, entry.name);
        if (entry.isDirectory()) {
            walk(localPath, entry.name, files);
        } else if (entry.isFile()) {
            files.push({ localPath, remoteRelPath: entry.name });
        }
    }

    // --- mehse-app/ altına giden proje dosyaları ---
    for (const name of APP_INCLUDE_TOP_LEVEL) {
        const localPath = path.join(ROOT, name);
        if (!fs.existsSync(localPath)) continue;
        const stat = fs.statSync(localPath);
        if (stat.isDirectory()) {
            walk(localPath, `mehse-app/${name}`, files);
        } else {
            files.push({ localPath, remoteRelPath: `mehse-app/${name}` });
        }
    }

    // --- Vite build'i mehse-app/public/build'e de ---
    // Tarayıcı CSS/JS'i kökteki /build/assets'ten çeker, ama Laravel manifest'i
    // public_path() = mehse-app/public altından okur. Yalnız köke yüklenirse
    // canlı sayfa eski hash'li CSS'e işaret etmeye devam eder. Bu blok en sonda:
    // manifest, kökteki asset'ler yüklendikten SONRA değişsin.
    walk(path.join(ROOT, 'public', 'build'), 'mehse-app/public/build', files);

    return files;
}

/** public/index.php içeriğini mehse-app/ alt dizinine göre yeniden yazar. */
function buildRootIndexPhp() {
    const original = fs.readFileSync(path.join(ROOT, 'public', 'index.php'), 'utf8');
    return original
        .replaceAll("__DIR__.'/../storage/framework/maintenance.php'", "__DIR__.'/mehse-app/storage/framework/maintenance.php'")
        .replaceAll("__DIR__.'/../vendor/autoload.php'", "__DIR__.'/mehse-app/vendor/autoload.php'")
        .replaceAll("__DIR__.'/../bootstrap/app.php'", "__DIR__.'/mehse-app/bootstrap/app.php'");
}

// --- Ana akış ---------------------------------------------------------------

async function main() {
    console.log(`[deploy] proje kökü: ${ROOT}`);

    if (!SKIP_BUILD) {
        console.log('[deploy] npm run build çalıştırılıyor...');
        execSync('npm run build', { cwd: ROOT, stdio: 'inherit' });
    }

    const state = loadState();
    const runStartedAt = Date.now();

    const allFiles = collectFiles();
    const generatedIndexPhp = buildRootIndexPhp();

    if (fs.statSync(path.join(ROOT, 'composer.lock')).mtimeMs > state.lastDeployAt) {
        console.warn('[deploy] UYARI: composer.lock son deploy\'dan sonra değişti. Yeni paket dosyaları yüklenir ama');
        console.warn('[deploy] vendor/composer yüklenmez: deploy/autoload-yukle.mjs başındaki adımlarla --no-dev autoload\'u ayrıca yükleyin.');
    }

    const alwaysUpload = new Set(['.htaccess', 'composer.lock']);

    const toUpload = allFiles.filter(({ localPath, remoteRelPath }) => {
        if (alwaysUpload.has(remoteRelPath)) return true;
        if (FULL) return true;
        const mtime = fs.statSync(localPath).mtimeMs;
        return mtime > state.lastDeployAt;
    });

    // index.php her zaman ayrı ve küçük olduğu için her seferinde güncellenir
    const uploadIndexPhp = true;

    console.log(`[deploy] toplam aday dosya: ${allFiles.length}, yüklenecek: ${toUpload.length + 1} (index.php dahil)`);

    if (DRY_RUN) {
        console.log('[deploy] --dry-run: hiçbir dosya yüklenmedi. Yüklenecekler:');
        console.log('  /index.php  (mehse-app/ yollarına göre yeniden yazıldı)');
        for (const f of toUpload) console.log(`  /${f.remoteRelPath}`);
        return;
    }

    const env = loadEnvDeploy();
    if (!env.FTP_HOST || !env.FTP_USER || !env.FTP_PASSWORD) {
        throw new Error('.env.deploy içinde FTP_HOST / FTP_USER / FTP_PASSWORD eksik');
    }

    const sleep = (ms) => new Promise((resolve) => setTimeout(resolve, ms));

    async function connect() {
        const c = new Client(30_000);
        c.ftp.verbose = false;
        await c.access({
            host: env.FTP_HOST,
            port: Number(env.FTP_PORT || 21),
            user: env.FTP_USER,
            password: env.FTP_PASSWORD,
            secure: env.FTP_SECURE === 'false' ? false : true, // explicit FTPS
            // Paylaşımlı hostingin FTPS sertifikası panel adına ait (ör. cpls25.srvpanel.com),
            // ftp.mehse.com'a değil — bağlantı yine TLS ile şifreleniyor, sadece hostname
            // doğrulaması gevşetiliyor. bkz. deploy/test-connection.mjs ile doğrulanan durum.
            secureOptions: { rejectUnauthorized: false },
        });
        return c;
    }

    let client = await connect();
    console.log('[deploy] FTPS bağlantısı kuruldu, yükleniyor...');

    const MAX_ATTEMPTS = 6;

    /** remoteDir/remoteName'i, kopan bağlantıyı otomatik yeniden kurarak yükler. */
    async function uploadWithRetry(localPath, remoteDir, remoteName, getCurrentDir, setCurrentDir) {
        for (let attempt = 1; ; attempt++) {
            try {
                if (getCurrentDir() !== remoteDir) {
                    await client.cd('/');
                    if (remoteDir !== '.') await client.ensureDir(remoteDir);
                    setCurrentDir(remoteDir);
                }
                await client.uploadFrom(localPath, remoteName);
                return;
            } catch (err) {
                if (attempt >= MAX_ATTEMPTS) throw err;
                console.warn(`[deploy] "${remoteDir}/${remoteName}" yüklenemedi (deneme ${attempt}/${MAX_ATTEMPTS}): ${err.message}`);
                try { client.close(); } catch { /* zaten kopmuş olabilir */ }
                await sleep(1000 * attempt);
                client = await connect();
                setCurrentDir(null); // yeniden bağlanınca dizin bilgisi geçersiz
            }
        }
    }

    try {
        let currentRemoteDir = null;
        let uploaded = 0;

        for (const { localPath, remoteRelPath } of toUpload) {
            const remoteDir = path.posix.dirname(remoteRelPath);
            const remoteName = path.posix.basename(remoteRelPath);

            await uploadWithRetry(
                localPath,
                remoteDir,
                remoteName,
                () => currentRemoteDir,
                (v) => { currentRemoteDir = v; },
            );

            uploaded += 1;
            if (uploaded % 50 === 0) console.log(`[deploy] ${uploaded}/${toUpload.length}...`);
        }

        // index.php'yi en son yükle (kökte, mehse-app/ hazır olduktan sonra)
        const tmpIndex = path.join(ROOT, '.deploy-index.tmp.php');
        fs.writeFileSync(tmpIndex, generatedIndexPhp);
        try {
            await uploadWithRetry(tmpIndex, '.', 'index.php', () => currentRemoteDir, (v) => { currentRemoteDir = v; });
        } finally {
            fs.unlinkSync(tmpIndex);
        }

        console.log(`[deploy] tamamlandı: ${uploaded + 1} dosya yüklendi.`);
        saveState({ lastDeployAt: runStartedAt });
    } finally {
        try { client.close(); } catch { /* zaten kopmuş olabilir */ }
    }
}

main().catch((err) => {
    console.error('[deploy] HATA:', err.message);
    process.exitCode = 1;
});
