<?php

namespace App\Support;

use App\Models\Firma;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;
use ZipArchive;

/**
 * Firma evrak paketi — kayıtlı evraklar (FirmaEvrakZipUretici) + firmanın bilgilerinden
 * üretilen, kişi/katılımcı alanları boş "elle doldurulacak" formlar; kullanıcı seçer, tek
 * ZIP iner (kullanıcı 15.10.2026: "firmalara tanımlanan evrakları toplu indirmek, hangi
 * evrakları istediğimi seçeyim"; "firma atanınca dosyasında bulunsun").
 */
class FirmaEvrakPaketi
{
    /** Boş form gruplarının seçim alanı => başlık (modal ve Dosya Fihristi ortak). */
    public const GRUPLAR = [
        'atama' => 'Atama Yazıları (boş — kalemle doldurulacak)',
        'egitim' => 'Eğitim Katılım Formları (boş — katılımcısız)',
        'diger' => 'Diğer Boş Formlar',
    ];

    /** Ekip rolünde boş atama yazısında kaç kişilik sayfa basılır. */
    private const EKIP_SAYFASI = 4;

    /** @return array<string, array<string, string>> grup => [anahtar => etiket] */
    public static function bosFormlar(): array
    {
        return [
            'atama' => collect(config('isg.atama.roller'))->map(fn (array $r) => $r['ad'])->all(),
            'egitim' => EgitimIcerikOlusturucu::basliklar(),
            'diger' => [
                'calisan_temsilcisi_secim' => 'Çalışan Temsilcisi Seçim Seti (duyuru, başvuru, aday listesi, oy pusulası, tutanak)',
                'isbasi_katilim' => 'İşbaşı Eğitimi Katılım Formu',
            ],
        ];
    }

    /**
     * @param  array<string, array<int, string>>  $secim  ['kayitli' => [...], 'atama' => [...], 'egitim' => [...], 'diger' => [...]]
     */
    public static function zip(Firma $firma, array $secim): StreamedResponse
    {
        $dosyalar = FirmaEvrakZipUretici::icerikler($firma, $secim['kayitli'] ?? []);

        foreach ($secim['atama'] ?? [] as $rol) {
            if (config('isg.atama.roller.'.$rol)) {
                $ekip = config('isg.atama.roller.'.$rol.'.tip') === 'ekip';
                $kayit = AtamaYazisiUretici::bosKayit($firma, $rol, $ekip ? self::EKIP_SAYFASI : 1);
                $dosyalar['bos-formlar/atama-'.Str::slug($kayit->rolEtiketi()).'.pdf'] = self::icerik(AtamaYazisiUretici::pdf($kayit, false));
            }
        }

        $basliklar = EgitimIcerikOlusturucu::basliklar();
        foreach ($secim['egitim'] ?? [] as $baslik) {
            if (isset($basliklar[$baslik])) {
                $yanit = EgitimKatilimUretici::bosFormPdf(
                    $baslik,
                    $baslik === 'genel' ? EgitimIcerikOlusturucu::naceSektoru($firma->nace_kodu) : null,
                    $firma->tehlike_sinifi ?: 'az_tehlikeli',
                    'ilk',
                    $firma,
                );
                $dosyalar['bos-formlar/egitim-'.Str::slug($basliklar[$baslik]).'.pdf'] = self::icerik($yanit);
            }
        }

        if (in_array('calisan_temsilcisi_secim', $secim['diger'] ?? [], true)) {
            foreach (CalisanTemsilcisiSecimiUretici::bosBelgeler($firma) as $ad => $icerik) {
                $dosyalar['bos-formlar/calisan-temsilcisi/'.$ad] = $icerik;
            }
        }

        if (in_array('isbasi_katilim', $secim['diger'] ?? [], true)) {
            $dosyalar['bos-formlar/isbasi-egitimi-katilim-formu.pdf'] = self::icerik(
                IsbasiEgitimTutanagiUretici::katilimFormuPdf($firma, ['katilimcilar' => [], 'konular' => []])
            );
        }

        $zipYolu = tempnam(sys_get_temp_dir(), 'mehse-paket').'.zip';
        $zip = new ZipArchive;
        $zip->open($zipYolu, ZipArchive::CREATE | ZipArchive::OVERWRITE);

        foreach ($dosyalar as $ad => $icerik) {
            $zip->addFromString($ad, $icerik);
        }

        $zip->close();

        return response()->streamDownload(function () use ($zipYolu) {
            echo file_get_contents($zipYolu);
            @unlink($zipYolu);
        }, Str::slug($firma->unvan).'-evrak-paketi.zip');
    }

    private static function icerik(StreamedResponse $yanit): string
    {
        ob_start();
        $yanit->sendContent();

        return (string) ob_get_clean();
    }
}
