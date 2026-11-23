<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Opsiyonel SMS bildirim altyapısı (D27 — WhatsApp'ın yanında Netgsm / İleti Merkezi / Log sürücüsü).
 * Kullanıcı bazlı ayarlar `users.ayarlar['sms_ayar']` içinde saklanır; API bilgileri girilmemişse
 * güvenli şekilde log modunda çalışır ve uygulamayı çökertmez.
 */
class SmsGonderici
{
    public const SURUCULER = [
        'log' => 'Test / Günlük (Gerçek SMS göndermez, loglar)',
        'netgsm' => 'Netgsm HTTP API',
        'iletimerkezi' => 'İleti Merkezi API',
    ];

    public static function sifreSifrele(string $duz): string
    {
        $duz = trim($duz);

        return $duz === '' ? '' : 'enc:'.Crypt::encryptString($duz);
    }

    public static function sifreCoz(string $sakli): string
    {
        if ($sakli === '') {
            return '';
        }

        if (str_starts_with($sakli, 'enc:')) {
            try {
                return Crypt::decryptString(substr($sakli, 4));
            } catch (\Throwable) {
                return '';
            }
        }

        return $sakli;
    }

    /** @return array{surucu: string, kullanici: string, sifre: string, sifre_kayitli: bool, baslik: string} */
    public static function ayarlar(User $user): array
    {
        $ham = (array) (($user->ayarlar ?? [])['sms_ayar'] ?? []);
        $cozulmus = self::sifreCoz((string) ($ham['sifre'] ?? ''));

        return [
            'surucu' => (string) ($ham['surucu'] ?? 'log'),
            'kullanici' => (string) ($ham['kullanici'] ?? ''),
            'sifre' => $cozulmus,
            'sifre_kayitli' => $cozulmus !== '',
            'baslik' => (string) ($ham['baslik'] ?? 'MEHSE ISG'),
        ];
    }

    /** @return array{basarili: bool, mesaj: string} */
    public static function gonder(User $user, string $telefon, string $metin): array
    {
        $tel = self::telefonTemizle($telefon);
        if ($tel === null) {
            return ['basarili' => false, 'mesaj' => 'Geçerli bir cep telefonu numarası girilmedi.'];
        }

        $ayar = self::ayarlar($user);

        if ($ayar['surucu'] === 'log' || $ayar['kullanici'] === '' || $ayar['sifre'] === '') {
            Log::info('[MEHSE SMS LOG]', ['user_id' => $user->id, 'telefon' => $tel, 'baslik' => $ayar['baslik'], 'metin' => $metin]);

            return ['basarili' => true, 'mesaj' => 'SMS günlük (test) modunda kaydedildi: '.$tel];
        }

        try {
            if ($ayar['surucu'] === 'netgsm') {
                $yanit = Http::timeout(8)->asForm()->post('https://api.netgsm.com.tr/sms/send/get', [
                    'usercode' => $ayar['kullanici'],
                    'password' => $ayar['sifre'],
                    'gsmno' => $tel,
                    'message' => $metin,
                    'msgheader' => $ayar['baslik'],
                    'dil' => 'TR',
                ]);
                $govde = trim($yanit->body());
                $ok = $yanit->successful() && (str_starts_with($govde, '00') || str_starts_with($govde, '01') || str_starts_with($govde, '02'));

                return ['basarili' => $ok, 'mesaj' => $ok ? 'Netgsm SMS iletildi ('.$govde.')' : 'Netgsm hatası: '.$govde];
            }

            if ($ayar['surucu'] === 'iletimerkezi') {
                $yanit = Http::timeout(8)->asForm()->post('https://api.iletimerkezi.com/v1/send-sms/get/', [
                    'key' => $ayar['kullanici'],
                    'hash' => $ayar['sifre'],
                    'text' => $metin,
                    'receipents' => $tel,
                    'sender' => $ayar['baslik'],
                    'iys' => '0',
                ]);

                return [
                    'basarili' => $yanit->successful(),
                    'mesaj' => $yanit->successful() ? 'İleti Merkezi SMS isteği iletildi.' : 'İleti Merkezi hatası: HTTP '.$yanit->status(),
                ];
            }
        } catch (\Throwable $e) {
            Log::warning('[MEHSE SMS HATA] '.$e->getMessage());

            return ['basarili' => false, 'mesaj' => 'SMS servisine bağlanılamadı: '.$e->getMessage()];
        }

        return ['basarili' => false, 'mesaj' => 'Desteklenmeyen SMS sürücüsü.'];
    }

    public static function telefonTemizle(string $telefon): ?string
    {
        $rakam = preg_replace('/\D/', '', $telefon);
        if (str_starts_with($rakam, '90') && strlen($rakam) === 12) {
            return $rakam;
        }
        if (str_starts_with($rakam, '05') && strlen($rakam) === 11) {
            return '9'.$rakam;
        }
        if (str_starts_with($rakam, '5') && strlen($rakam) === 10) {
            return '90'.$rakam;
        }

        return null;
    }
}
