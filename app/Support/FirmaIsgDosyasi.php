<?php

namespace App\Support;

use App\Filament\Pages\AcilDurumEkipleri;
use App\Filament\Pages\AcilDurumKrokisi as KrokiSayfasi;
use App\Filament\Pages\AcilDurumPlani as AdepSayfasi;
use App\Filament\Pages\CalisanTemsilcisiSecimi as TemsilciSayfasi;
use App\Filament\Pages\DofOlustur;
use App\Filament\Pages\EgitimKatilim;
use App\Filament\Pages\EgitimYenilemeTakibi;
use App\Filament\Pages\KkdTakip;
use App\Filament\Pages\SahaDenetimi as SahaSayfasi;
use App\Filament\Pages\SertifikaOlustur;
use App\Filament\Resources\Calisans\CalisanResource;
use App\Filament\Resources\RiskDegerlendirmesis\RiskDegerlendirmesiResource;
use App\Models\AcilDurumKrokisi;
use App\Models\AcilDurumPlani;
use App\Models\AcilEkip;
use App\Models\EgitimKatilim as EgitimKatilimModel;
use App\Models\Firma;
use App\Models\IsbasiEgitimTutanagi;
use App\Models\KkdZimmetFormu;
use App\Models\RiskDegerlendirmesi;
use App\Models\SahaBulgusu;
use App\Models\SahaDenetimi;
use App\Models\Sertifika;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;
use ZipArchive;

/**
 * Firma Dijital İSG Dosyası — bir firmanın evraklarını 5 grupta toplar (risk, ADEP, eğitim, çalışan/temsilci,
 * saha/DÖF): son kayıt, geçerlilik durumu, PDF ve düzenleme bağlantısı. Salt okunur özet + Teftiş Paketi ZIP.
 */
class FirmaIsgDosyasi
{
    /**
     * Satır: ad, bilgi, durum (ok|uyari|yok), etiket, pdf (anahtar|null), duzenle (url|null), whatsapp (bool).
     *
     * @return array<string, array{baslik: string, satirlar: array<int, array<string, mixed>>}>
     */
    public static function gruplar(Firma $firma): array
    {
        $bugun = Carbon::today();
        $gun = fn (?Carbon $t) => $t ? (int) $bugun->diffInDays($t, false) : null;
        $satir = fn (string $ad, string $bilgi, string $durum, string $etiket, ?string $pdf, ?string $duzenle, bool $whatsapp = false) => compact('ad', 'bilgi', 'durum', 'etiket', 'pdf', 'duzenle', 'whatsapp');
        $fid = ['firma' => $firma->id];

        // a) Risk değerlendirmesi
        $risk = RiskDegerlendirmesi::where('firma_id', $firma->id)->latest('rapor_tarihi')->latest('id')->first();
        $riskSatiri = $risk
            ? $satir(
                'Risk Değerlendirmesi',
                'Rapor No: '.($risk->belge_no ?? '—').' · Tarih: '.($risk->rapor_tarihi?->format('d.m.Y') ?? '—')
                    .' · Geçerlilik: '.($risk->gecerlilik_tarihi?->format('d.m.Y') ?? '—').' ('.config('isg.risk_gecerlilik_yili.'.$firma->tehlike_sinifi, 4).' yıl)',
                $risk->gecerlilikGecti() ? 'yok' : 'ok',
                $risk->gecerlilikGecti() ? 'Yenileme Gerekli' : 'Aktif / Geçerli',
                'risk:'.$risk->id,
                RiskDegerlendirmesiResource::getUrl('edit', ['record' => $risk->id]),
                true,
            )
            : $satir('Risk Değerlendirmesi', 'Kayıt yok', 'yok', 'Eksik', null, RiskDegerlendirmesiResource::getUrl('create'));

        // b) ADEP
        $plan = AcilDurumPlani::where('firma_id', $firma->id)->latest('id')->first();
        $ekipSayisi = AcilEkip::where('firma_id', $firma->id)->whereHas('uyeler')->count();
        $kroki = AcilDurumKrokisi::where('firma_id', $firma->id)->first();
        $krokiVar = $kroki && (filled($kroki->arka_plan_gorseli) || ! empty($kroki->duvarlar) || ! empty($kroki->semboller) || ! empty($kroki->kroki3b));

        $adep = [
            $plan
                ? $satir(
                    'ADEP Raporu',
                    'Rapor: '.($plan->rapor_tarihi?->format('d.m.Y') ?? '—').' · Geçerlilik: '.($plan->gecerlilik_tarihi?->format('d.m.Y') ?? '—'),
                    $plan->gecerlilik_tarihi?->isPast() ? 'uyari' : 'ok',
                    $plan->gecerlilik_tarihi?->isPast() ? 'Süresi Geçmiş' : 'Plan Mevcut',
                    'adep:'.$plan->id,
                    AdepSayfasi::getUrl($fid),
                )
                : $satir('ADEP Raporu', 'Kayıt yok', 'yok', 'Eksik', null, AdepSayfasi::getUrl($fid)),
            $satir('Ekipler Tebellüğ Listesi', $ekipSayisi.' ekip (üyeli)', $ekipSayisi ? 'ok' : 'yok', $ekipSayisi ? 'Plan Mevcut' : 'Eksik', $ekipSayisi ? 'tebellug' : null, AcilDurumEkipleri::getUrl($fid)),
            $satir('Tahliye Krokisi', $krokiVar ? 'Hazırlanma: '.($kroki->hazirlanma_tarihi?->format('d.m.Y') ?? '—') : 'Kayıt yok', $krokiVar ? 'ok' : 'yok', $krokiVar ? 'Plan Mevcut' : 'Eksik', $krokiVar ? 'kroki' : null, KrokiSayfasi::getUrl($fid)),
        ];

        // c) Eğitimler
        $katilim = EgitimKatilimModel::where('firma_id', $firma->id)->where('baslik_anahtari', 'genel')->latest('belge_tarihi')->latest('id')->first();
        $yil = (int) config('isg.egitim_yenileme_yili.'.$firma->tehlike_sinifi, 3);
        $egitimTarih = $katilim ? EgitimTakibi::katilimTarihi($katilim) : null;
        $yenileme = $egitimTarih?->copy()->addYears($yil);
        $kalan = $gun($yenileme);
        $sertifika = $firma->sertifikalar()->latest()->first();
        $isbasi = $firma->isbasiEgitimTutanaklari()->latest()->first();

        $egitim = [
            $katilim && $egitimTarih
                ? $satir(
                    'Son Temel Eğitim Katılım Tutanağı',
                    'Eğitim: '.$egitimTarih->format('d.m.Y').' · Yenileme: '.$yenileme->format('d.m.Y'),
                    $kalan < 0 ? 'yok' : ($kalan <= 60 ? 'uyari' : 'ok'),
                    $kalan < 0 ? abs($kalan).' gün geçti' : $kalan.' gün kaldı',
                    'katilim:'.$katilim->id,
                    EgitimKatilim::getUrl($fid),
                )
                : $satir('Son Temel Eğitim Katılım Tutanağı', 'Kayıt yok', 'yok', 'Eksik', null, EgitimKatilim::getUrl($fid)),
            $sertifika
                ? $satir('Eğitim Sertifikaları (Yıldız Grup dahil)', $firma->sertifikalar()->count().' sertifika · son: '.$sertifika->created_at->format('d.m.Y'), 'ok', 'Mevcut', 'sertifika:'.$sertifika->id, SertifikaOlustur::getUrl($fid))
                : $satir('Eğitim Sertifikaları (Yıldız Grup dahil)', 'Kayıt yok', 'yok', 'Eksik', null, SertifikaOlustur::getUrl($fid)),
            $isbasi
                ? $satir('İşbaşı Eğitim Tutanakları', $firma->isbasiEgitimTutanaklari()->count().' tutanak · son: '.$isbasi->created_at->format('d.m.Y'), 'ok', 'Mevcut', 'isbasi:'.$isbasi->id, EgitimYenilemeTakibi::getUrl($fid))
                : $satir('İşbaşı Eğitim Tutanakları', 'Kayıt yok', 'uyari', 'Yok', null, EgitimYenilemeTakibi::getUrl($fid)),
        ];

        // d) Çalışan, temsilci, KKD
        $calisan = $firma->calisanlar()->where('aktif', true)->count();
        $secim = $firma->calisanTemsilcisiSecimi;
        $gorevlendirildi = $secim && $secim->gorevlendirme_tarihi;
        $zimmet = $firma->kkdZimmetFormlari()->latest()->first();

        $calisanGrubu = [
            $satir('Aktif Çalışan', $calisan.' kişi', $calisan ? 'ok' : 'uyari', $calisan ? 'Kayıtlı' : 'Çalışan yok', null, CalisanResource::getUrl('index')),
            $satir('Çalışan Temsilcisi Görevlendirme Tutanağı', $gorevlendirildi ? 'Görevlendirme: '.$secim->gorevlendirme_tarihi->format('d.m.Y') : 'Görevlendirme yok', $gorevlendirildi ? 'ok' : 'yok', $gorevlendirildi ? 'Mevcut' : 'Eksik', null, TemsilciSayfasi::getUrl($fid)),
            $zimmet
                ? $satir('KKD Teslim (Zimmet) Formları', $firma->kkdZimmetFormlari()->count().' form · son teslim: '.($zimmet->teslim_tarihi?->format('d.m.Y') ?? '—'), 'ok', 'Mevcut', 'zimmet:'.$zimmet->id, KkdTakip::getUrl($fid))
                : $satir('KKD Teslim (Zimmet) Formları', 'Kayıt yok', 'yok', 'Eksik', null, KkdTakip::getUrl($fid)),
        ];

        // e) Saha denetimi ve DÖF
        $acik = SahaBulgusu::where('firma_id', $firma->id)->where('durum', '!=', 'kapandi')->count();
        $kapali = SahaBulgusu::where('firma_id', $firma->id)->where('durum', 'kapandi')->count();
        $saha = SahaDenetimi::where('firma_id', $firma->id)->latest('id')->first();

        $sahaGrubu = [
            $satir('DÖF / Saha Bulguları', "Açık: {$acik} · Kapanan: {$kapali}", $acik ? 'uyari' : 'ok', $acik ? "{$acik} açık" : 'Açık DÖF yok', null, DofOlustur::getUrl($fid)),
            $saha
                ? $satir('Son Saha Gözlem (Denetim) Raporu', 'Tarih: '.$saha->created_at->format('d.m.Y'), 'ok', 'Mevcut', 'saha:'.$saha->id, SahaSayfasi::getUrl($fid))
                : $satir('Son Saha Gözlem (Denetim) Raporu', 'Kayıt yok', 'yok', 'Eksik', null, SahaSayfasi::getUrl($fid)),
        ];

        return [
            'risk' => ['baslik' => 'Risk Değerlendirmesi', 'satirlar' => [$riskSatiri]],
            'adep' => ['baslik' => 'Acil Durum ve Yangın (ADEP)', 'satirlar' => $adep],
            'egitim' => ['baslik' => 'İSG Eğitimleri', 'satirlar' => $egitim],
            'calisan' => ['baslik' => 'Çalışan ve Temsilci Evrakları', 'satirlar' => $calisanGrubu],
            'saha' => ['baslik' => 'Saha Denetim ve DÖF', 'satirlar' => $sahaGrubu],
        ];
    }

    /** Satır anahtarından tek PDF — kayıt bu firmaya ait değilse null. */
    public static function pdf(Firma $firma, string $anahtar): ?StreamedResponse
    {
        [$tur, $id] = array_pad(explode(':', $anahtar, 2), 2, null);

        if ($tur === 'tebellug') {
            return AcilEkipUretici::tebellugPdf($firma, AcilEkip::where('firma_id', $firma->id)->with('uyeler')->get());
        }

        if ($tur === 'kroki') {
            $kroki = AcilDurumKrokisi::where('firma_id', $firma->id)->first();

            return $kroki ? AcilDurumKrokisiUretici::pdf($kroki) : null;
        }

        $model = match ($tur) {
            'risk' => RiskDegerlendirmesi::class,
            'adep' => AcilDurumPlani::class,
            'katilim' => EgitimKatilimModel::class,
            'sertifika' => Sertifika::class,
            'isbasi' => IsbasiEgitimTutanagi::class,
            'zimmet' => KkdZimmetFormu::class,
            'saha' => SahaDenetimi::class,
            default => null,
        };

        $kayit = $model ? $model::where('firma_id', $firma->id)->find($id) : null;

        if (! $kayit) {
            return null;
        }

        $kayit->load('firma');

        return match ($tur) {
            'risk' => RiskDegerlendirmesiUretici::pdf($kayit),
            'adep' => AcilDurumPlaniUretici::pdf($kayit),
            'katilim' => EgitimKatilimUretici::pdf($kayit),
            'sertifika' => SertifikaUretici::pdf($kayit),
            'isbasi' => IsbasiEgitimTutanagiUretici::pdf($kayit),
            'zimmet' => KkdZimmetFormuUretici::pdf($kayit),
            'saha' => SahaDenetimiUretici::pdf($kayit),
        };
    }

    /** Teftiş Paketi: son risk, ADEP, ekip tebellüğ ve son eğitim katılım tutanağı tek ZIP'te. */
    public static function teftisPaketi(Firma $firma): ?StreamedResponse
    {
        $secimler = [
            '1-risk-degerlendirmesi.pdf' => 'risk:'.RiskDegerlendirmesi::where('firma_id', $firma->id)->latest('rapor_tarihi')->latest('id')->value('id'),
            '2-adep-plani.pdf' => 'adep:'.AcilDurumPlani::where('firma_id', $firma->id)->latest('id')->value('id'),
            '3-ekipler-tebellug-listesi.pdf' => 'tebellug',
            '4-egitim-katilim-tutanagi.pdf' => 'katilim:'.EgitimKatilimModel::where('firma_id', $firma->id)->where('baslik_anahtari', 'genel')->latest('belge_tarihi')->latest('id')->value('id'),
        ];

        $icerikler = [];

        foreach ($secimler as $ad => $anahtar) {
            if ($yanit = static::pdf($firma, $anahtar)) {
                $icerikler[$ad] = static::govde($yanit);
            }
        }

        if (! $icerikler) {
            return null;
        }

        $zipYolu = tempnam(sys_get_temp_dir(), 'teftis').'.zip';
        $zip = new ZipArchive;
        $zip->open($zipYolu, ZipArchive::CREATE | ZipArchive::OVERWRITE);

        foreach ($icerikler as $ad => $icerik) {
            $zip->addFromString($ad, $icerik);
        }

        $zip->close();

        return response()->streamDownload(function () use ($zipYolu) {
            echo file_get_contents($zipYolu);
            @unlink($zipYolu);
        }, 'teftis-paketi-'.Str::slug($firma->unvan).'.zip');
    }

    private static function govde(StreamedResponse $yanit): string
    {
        ob_start();
        $yanit->sendContent();

        return (string) ob_get_clean();
    }
}
