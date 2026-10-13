<?php

namespace App\Support;

use App\Models\PushAbonelik;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Throwable;

/**
 * Telefon bildirimi (Web Push, 13.10.2026) — kütüphanesiz, yalnız PHP openssl.
 *
 * - Yük şifreleme: RFC 8291 (aes128gcm, RFC 8188 tek kayıt).
 * - Sunucu kimliği: RFC 8292 VAPID (ES256 JWT).
 *
 * VAPID anahtarı ilk kullanımda üretilip storage/app/private/webpush-vapid.json'a
 * yazılır (sunucuda .env düzenlemek gerekmesin). Anahtar değişirse tüm
 * abonelikler geçersiz olur — dosyayı silmeyin.
 *
 * Bildirim içeriği Google/Apple sunucularından şifreli geçer; yine de içerikte
 * çalışan adı / sağlık bilgisi olmamalı (BildirimTarayici bunu zaten sağlıyor).
 */
class WebPush
{
    private const ANAHTAR_DOSYASI = 'webpush-vapid.json';

    /** P-256 açık anahtarının DER SubjectPublicKeyInfo öneki (ardından 65 bayt ham anahtar). */
    private const P256_SPKI_ONEKI = '3059301306072a8648ce3d020106082a8648ce3d030107034200';

    /** Tarayıcının pushManager.subscribe() için istediği açık anahtar (base64url). */
    public static function acikAnahtar(): string
    {
        return static::b64(static::vapid()['acik']);
    }

    /**
     * @param  array{title: string, body?: string, url?: string, tag?: string}  $yuk
     * @return string 'gonderildi' | 'gecersiz' (abonelik silindi) | 'hata'
     */
    public static function gonder(PushAbonelik $abonelik, array $yuk, int $ttl = 86400): string
    {
        try {
            $govde = static::sifrele(
                json_encode($yuk, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                static::b64c($abonelik->p256dh),
                static::b64c($abonelik->auth),
            );

            $yanit = Http::withHeaders([
                'TTL' => (string) $ttl,
                'Urgency' => 'normal',
                'Content-Encoding' => 'aes128gcm',
                'Authorization' => static::vapidBasligi($abonelik->endpoint),
            ])->withBody($govde, 'application/octet-stream')->timeout(15)->post($abonelik->endpoint);
        } catch (Throwable $e) {
            report($e);
            $abonelik->increment('hata_sayisi');

            return 'hata';
        }

        // 404/410: tarayıcı aboneliği bıraktı (uygulama silindi, izin kaldırıldı).
        if (in_array($yanit->status(), [404, 410], true)) {
            $abonelik->delete();

            return 'gecersiz';
        }

        if (! $yanit->successful()) {
            $abonelik->increment('hata_sayisi');

            return 'hata';
        }

        $abonelik->forceFill(['son_basari_at' => now(), 'hata_sayisi' => 0])->save();

        return 'gonderildi';
    }

    /** RFC 8291: yükü alıcının p256dh + auth anahtarıyla şifreler, aes128gcm gövdesi döner. */
    public static function sifrele(string $yuk, string $aliciAcik, string $authSirri, ?string $tuz = null, mixed $gecici = null): string
    {
        if (strlen($aliciAcik) !== 65 || strlen($authSirri) !== 16) {
            throw new RuntimeException('Geçersiz push aboneliği anahtarı.');
        }

        $gecici ??= static::yeniAnahtar();
        $geciciAcik = static::hamAcik($gecici);
        $tuz ??= random_bytes(16);

        $ortak = openssl_pkey_derive(static::acikPem($aliciAcik), $gecici, 32);
        if ($ortak === false) {
            throw new RuntimeException('ECDH başarısız: '.openssl_error_string());
        }

        $ikm = hash_hkdf('sha256', $ortak, 32, "WebPush: info\0".$aliciAcik.$geciciAcik, $authSirri);
        $cek = hash_hkdf('sha256', $ikm, 16, "Content-Encoding: aes128gcm\0", $tuz);
        $nonce = hash_hkdf('sha256', $ikm, 12, "Content-Encoding: nonce\0", $tuz);

        // Tek (son) kayıt: yük + 0x02 sınırlayıcı.
        $sifreli = openssl_encrypt($yuk."\x02", 'aes-128-gcm', $cek, OPENSSL_RAW_DATA, $nonce, $etiket, '', 16);

        return $tuz.pack('N', 4096).chr(65).$geciciAcik.$sifreli.$etiket;
    }

    /** "vapid t=<JWT>, k=<açık anahtar>" — aud = abonelik adresinin kökü. */
    public static function vapidBasligi(string $endpoint, ?int $zaman = null): string
    {
        $parca = parse_url($endpoint);
        $aud = $parca['scheme'].'://'.$parca['host'].(isset($parca['port']) ? ':'.$parca['port'] : '');

        $baslik = static::b64(json_encode(['typ' => 'JWT', 'alg' => 'ES256']));
        $iddia = static::b64(json_encode([
            'aud' => $aud,
            'exp' => ($zaman ?? time()) + 12 * 3600,
            // Apple yalnız https: veya mailto: kabul eder; canlıda APP_URL https://mehse.com.
            'sub' => str_starts_with((string) config('app.url'), 'https://')
                ? rtrim((string) config('app.url'), '/')
                : 'mailto:'.config('mail.from.address', 'bilgi@mehse.com'),
        ], JSON_UNESCAPED_SLASHES));

        $vapid = static::vapid();
        openssl_sign("$baslik.$iddia", $der, $vapid['ozel'], OPENSSL_ALGO_SHA256);

        return 'vapid t='.$baslik.'.'.$iddia.'.'.static::b64(static::derdenHam($der)).', k='.static::b64($vapid['acik']);
    }

    /** @return array{ozel: \OpenSSLAsymmetricKey, acik: string} */
    private static function vapid(): array
    {
        static $onbellek = null;
        if ($onbellek !== null) {
            return $onbellek;
        }

        $disk = Storage::disk('local');
        if (! $disk->exists(self::ANAHTAR_DOSYASI)) {
            $anahtar = static::yeniAnahtar();
            openssl_pkey_export($anahtar, $pem, null, static::opensslAyari());
            $disk->put(self::ANAHTAR_DOSYASI, json_encode(['ozel_pem' => $pem, 'olusturma' => now()->toIso8601String()]));
        }

        $ozel = openssl_pkey_get_private(json_decode($disk->get(self::ANAHTAR_DOSYASI), true)['ozel_pem']);
        if ($ozel === false) {
            throw new RuntimeException('VAPID anahtarı okunamadı.');
        }

        return $onbellek = ['ozel' => $ozel, 'acik' => static::hamAcik($ozel)];
    }

    private static function yeniAnahtar(): \OpenSSLAsymmetricKey
    {
        $anahtar = openssl_pkey_new(static::opensslAyari() + ['curve_name' => 'prime256v1', 'private_key_type' => OPENSSL_KEYTYPE_EC]);
        if ($anahtar === false) {
            throw new RuntimeException('EC anahtarı üretilemedi: '.openssl_error_string());
        }

        return $anahtar;
    }

    /** Windows XAMPP'te openssl.cnf varsayılan yolda değil; Linux sunucuda gerekmez. */
    private static function opensslAyari(): array
    {
        $ini = php_ini_loaded_file();
        $cnf = $ini ? dirname($ini).'/extras/ssl/openssl.cnf' : null;

        return PHP_OS_FAMILY === 'Windows' && $cnf && is_file($cnf) ? ['config' => $cnf] : [];
    }

    /** 0x04 || X || Y (65 bayt). */
    private static function hamAcik(\OpenSSLAsymmetricKey $anahtar): string
    {
        $ec = openssl_pkey_get_details($anahtar)['ec'];

        return "\x04".str_pad($ec['x'], 32, "\0", STR_PAD_LEFT).str_pad($ec['y'], 32, "\0", STR_PAD_LEFT);
    }

    private static function acikPem(string $ham): string
    {
        return "-----BEGIN PUBLIC KEY-----\n"
            .chunk_split(base64_encode(hex2bin(self::P256_SPKI_ONEKI).$ham), 64, "\n")
            ."-----END PUBLIC KEY-----\n";
    }

    /** openssl_sign DER (SEQUENCE{INTEGER r, INTEGER s}) → JWS ham r||s (64 bayt). */
    private static function derdenHam(string $der): string
    {
        $konum = 2;
        $ham = '';
        for ($i = 0; $i < 2; $i++) {
            $uzunluk = ord($der[$konum + 1]);
            $tamsayi = ltrim(substr($der, $konum + 2, $uzunluk), "\0");
            $ham .= str_pad($tamsayi, 32, "\0", STR_PAD_LEFT);
            $konum += 2 + $uzunluk;
        }

        return $ham;
    }

    public static function b64(string $ham): string
    {
        return rtrim(strtr(base64_encode($ham), '+/', '-_'), '=');
    }

    public static function b64c(string $metin): string
    {
        return base64_decode(strtr($metin, '-_', '+/'));
    }
}
