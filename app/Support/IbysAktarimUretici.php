<?php

namespace App\Support;

use App\Models\EgitimKatilim;
use App\Models\Firma;
use App\Models\SaglikGozetimi;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * ÇSGB İBYS Uyumlu Standart Aktarım Paketi Üreticisi (XML / JSON — K6).
 *
 * Not: Canlı Bakanlık İBYS sunucularına doğrudan API gönderimi ÇSGB İBYS
 * Entegratör Yetkilendirmesi ve e-İmza gerektirir. Bu sınıf mevcut verileri
 * resmî İBYS veri sözlüğüne uygun standart XML/JSON paketleri hâlinde dışa aktarır.
 */
class IbysAktarimUretici
{
    /** @return array<string, mixed> */
    public static function veri(Firma $firma): array
    {
        $egitimler = EgitimKatilim::query()
            ->where('firma_id', $firma->id)
            ->latest('belge_tarihi')
            ->latest('id')
            ->get()
            ->map(fn (EgitimKatilim $e) => [
                'belge_no' => $e->belge_no,
                'tarih' => $e->belge_tarihi?->toDateString(),
                'konu' => $e->basliklarEtiketi(),
                'sure_saat' => $e->sure_saat ?: (($e->sure_gun ?? 1) * 4),
                'katilimci_sayisi' => count((array) ($e->katilimcilar ?? [])),
                'katilimcilar' => collect($e->katilimcilar ?? [])->map(fn ($k) => [
                    'ad_soyad' => $k['ad_soyad'] ?? '',
                    'tc' => $k['tc'] ?? '',
                    'gorev' => $k['gorev'] ?? '',
                ])->values()->all(),
            ])->all();

        $saglik = SaglikGozetimi::query()->where('firma_id', $firma->id)->first();
        $muayeneler = collect($saglik?->satirlar ?? [])->map(fn ($s) => [
            'ad_soyad' => $s['ad_soyad'] ?? '',
            'tc' => $s['tc'] ?? '',
            'tetkik_turu' => $s['tetkik_turu'] ?? 'periyodik',
            'tarih' => $s['tarih'] ?? null,
            'sonraki_tarih' => $s['sonraki_tarih'] ?? null,
            'sonuc' => $s['sonuc'] ?? 'uygun',
            'rapor_no' => $s['rapor_no'] ?? null,
        ])->values()->all();

        return [
            'sema' => 'MEHSE-IBYS-PAKET-v1',
            'olusturma_zamani' => now()->toIso8601String(),
            'isyeri' => [
                'unvan' => $firma->unvan,
                'sgk_sicil_no' => $firma->sgk_sicil_no,
                'katip_no' => $firma->katip_no,
                'vergi_no' => $firma->vergi_no,
                'nace_kodu' => $firma->nace_kodu,
                'tehlike_sinifi' => $firma->tehlike_sinifi,
                'calisan_sayisi' => (int) $firma->calisan_sayisi,
            ],
            'hizmet_sozlesmesi' => [
                'baslangic' => $firma->sozlesme_baslangic?->toDateString(),
                'bitis' => $firma->sozlesme_bitis?->toDateString(),
                'igu' => $firma->igu?->ad_soyad,
                'igu_belge_no' => $firma->igu?->belge_no,
                'isyeri_hekimi' => $firma->isyeriHekimi?->ad_soyad,
                'hekim_belge_no' => $firma->isyeriHekimi?->belge_no,
                'dsp' => $firma->dsp?->ad_soyad,
            ],
            'egitim_bildirimleri' => $egitimler,
            'muayene_bildirimleri' => $muayeneler,
        ];
    }

    public static function xmlMetni(Firma $firma): string
    {
        $v = static::veri($firma);
        $xml = new \SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><IbysAktarimPaketi/>');
        $xml->addAttribute('sema', $v['sema']);
        $xml->addAttribute('olusturmaZamani', $v['olusturma_zamani']);

        $isyeri = $xml->addChild('Isyeri');
        foreach ($v['isyeri'] as $k => $val) {
            $isyeri->addChild($k, htmlspecialchars((string) ($val ?? ''), ENT_XML1 | ENT_QUOTES, 'UTF-8'));
        }

        $hizmet = $xml->addChild('HizmetSozlesmesi');
        foreach ($v['hizmet_sozlesmesi'] as $k => $val) {
            $hizmet->addChild($k, htmlspecialchars((string) ($val ?? ''), ENT_XML1 | ENT_QUOTES, 'UTF-8'));
        }

        $egitimNode = $xml->addChild('EgitimBildirimleri');
        foreach ($v['egitim_bildirimleri'] as $e) {
            $item = $egitimNode->addChild('Egitim');
            $item->addChild('belge_no', htmlspecialchars((string) ($e['belge_no'] ?? ''), ENT_XML1, 'UTF-8'));
            $item->addChild('tarih', htmlspecialchars((string) ($e['tarih'] ?? ''), ENT_XML1, 'UTF-8'));
            $item->addChild('konu', htmlspecialchars((string) ($e['konu'] ?? ''), ENT_XML1, 'UTF-8'));
            $item->addChild('sure_saat', (string) ($e['sure_saat'] ?? ''));
            $katNode = $item->addChild('Katilimcilar');
            foreach ($e['katilimcilar'] as $kat) {
                $kItem = $katNode->addChild('Katilimci');
                $kItem->addChild('ad_soyad', htmlspecialchars((string) $kat['ad_soyad'], ENT_XML1, 'UTF-8'));
                $kItem->addChild('tc', htmlspecialchars((string) $kat['tc'], ENT_XML1, 'UTF-8'));
            }
        }

        $muayeneNode = $xml->addChild('MuayeneBildirimleri');
        foreach ($v['muayene_bildirimleri'] as $m) {
            $item = $muayeneNode->addChild('Muayene');
            foreach ($m as $k => $val) {
                $item->addChild($k, htmlspecialchars((string) ($val ?? ''), ENT_XML1, 'UTF-8'));
            }
        }

        return (string) $xml->asXML();
    }

    public static function xmlIndir(Firma $firma): StreamedResponse
    {
        $icerik = static::xmlMetni($firma);
        $dosya = 'ibys-paketi-'.Str::slug($firma->unvan ?: 'firma').'-'.now()->format('Ymd').'.xml';

        return response()->streamDownload(fn () => print($icerik), $dosya, [
            'Content-Type' => 'application/xml; charset=utf-8',
        ]);
    }

    public static function jsonIndir(Firma $firma): StreamedResponse
    {
        $icerik = json_encode(static::veri($firma), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        $dosya = 'ibys-paketi-'.Str::slug($firma->unvan ?: 'firma').'-'.now()->format('Ymd').'.json';

        return response()->streamDownload(fn () => print((string) $icerik), $dosya, [
            'Content-Type' => 'application/json; charset=utf-8',
        ]);
    }
}
