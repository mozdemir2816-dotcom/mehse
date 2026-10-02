<?php

namespace App\Support;

use App\Filament\Pages\AcilDurumPlani as AcilDurumSayfasi;
use App\Filament\Pages\KimyasalSicili;
use App\Filament\Pages\OrtamOlcumleri;
use App\Filament\Pages\PeriyodikKontrol;
use App\Filament\Resources\Calisans\CalisanResource;
use App\Filament\Resources\Firmas\FirmaResource;
use App\Filament\Resources\RiskDegerlendirmesis\RiskDegerlendirmesiResource;
use App\Models\AcilDurumPlani;
use App\Models\Firma;
use App\Models\IsEkipmani;
use App\Models\KimyasalUrun;
use App\Models\OlayKaydi;
use App\Models\OrtamOlcumu;
use App\Models\RiskDegerlendirmesi;
use App\Models\RiskMaddesi;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Throwable;

/**
 * Risk Merkezi (isgsuite "Risk Değerlendirme — Merkez / Aksiyon / NACE Yol
 * Haritası"): bir işyerinin risk değerlendirmesinin panosu — göstergeler,
 * öncelikli riskler, ısı haritası, bölüm yoğunluğu, yenileme takibi,
 * önlem (aksiyon) termin takibi, NACE'ye göre kontrol edilecek risk
 * alanları, rapor kontrol maddeleri ve uygulama yol haritası.
 * Kayıt üretmez; uzmanı ilgili modüle yönlendirir.
 */
class RiskMerkeziVerisi
{
    public const GRUPLAR = ['cok_yuksek' => 'Çok yüksek', 'yuksek' => 'Yüksek', 'orta' => 'Orta', 'dusuk' => 'Düşük / Kabul'];

    /** Yöntemin bant sırasına göre seviye grubu (ilk bant = çok yüksek). */
    public static function seviyeGrubu(string $yontem, int|float|null $puan): ?string
    {
        if (! $puan) {
            return null;
        }

        foreach (array_values(config('isg.risk_'.$yontem.'.bantlar', [])) as $i => $bant) {
            if ($puan >= $bant['min']) {
                return ['cok_yuksek', 'yuksek', 'orta'][$i] ?? 'dusuk';
            }
        }

        return 'dusuk';
    }

    /** Termin metnini (tarih ya da "Sürekli") tarihe çevirir. */
    public static function terminTarihi(?string $termin): ?Carbon
    {
        $termin = trim((string) $termin);

        if ($termin === '' || ! preg_match('/\d/', $termin)) {
            return null;
        }

        // Excel'den aktarılmış seri gün numarası (ör. 44927 = 01.01.2023)
        if (preg_match('/^\d{5}$/', $termin) && (int) $termin > 30000 && (int) $termin < 80000) {
            return Carbon::create(1899, 12, 30)->addDays((int) $termin)->startOfDay();
        }

        foreach (['Y-m-d', 'd.m.Y', 'd/m/Y', 'd-m-Y'] as $bicim) {
            try {
                $t = Carbon::createFromFormat($bicim, $termin);
                if ($t !== false && $t->format($bicim) === $termin) {
                    return $t->startOfDay();
                }
            } catch (Throwable) {
            }
        }

        return null;
    }

    public static function acikMi(RiskMaddesi $m): bool
    {
        return $m->durum !== 'kapali';
    }

    /** @return array<string, mixed> */
    public static function ozet(RiskDegerlendirmesi $rd): array
    {
        $maddeler = $rd->maddeler()->get();
        $bugun = Carbon::today();
        $acik = $maddeler->filter(fn (RiskMaddesi $m) => static::acikMi($m));
        $grup = fn (RiskMaddesi $m) => static::seviyeGrubu($rd->yontem, $m->puan);

        $dagilim = collect(array_keys(static::GRUPLAR))->mapWithKeys(fn ($g) => [$g => $maddeler->filter(fn ($m) => $grup($m) === $g)->count()])->all();

        $matris = [];
        if ($rd->yontem === 'matris_5x5') {
            foreach ($maddeler as $m) {
                if ($m->olasilik && $m->siddet) {
                    $matris[(int) $m->olasilik][(int) $m->siddet] = ($matris[(int) $m->olasilik][(int) $m->siddet] ?? 0) + 1;
                }
            }
        }

        $aksiyon = $acik->filter(fn ($m) => filled($m->oneri));
        $geciken = $aksiyon->filter(fn ($m) => static::terminTarihi($m->termin)?->lt($bugun));

        return [
            'toplam' => $maddeler->count(),
            'acik' => $acik->count(),
            'kapali' => $maddeler->count() - $acik->count(),
            'cok_yuksek_acik' => $acik->filter(fn ($m) => $grup($m) === 'cok_yuksek')->count(),
            'yuksek_acik' => $acik->filter(fn ($m) => $grup($m) === 'yuksek')->count(),
            'aksiyon_acik' => $aksiyon->count(),
            'geciken' => $geciken->count(),
            'puansiz' => $maddeler->filter(fn ($m) => ! $m->puan)->count(),
            'dagilim' => $dagilim,
            'matris' => $matris,
            'bolumler' => $maddeler->groupBy(fn ($m) => trim((string) $m->bolum) ?: 'Bölüm belirtilmemiş')->map->count()->sortDesc()->all(),
            'oncelikli' => $acik->filter(fn ($m) => in_array($grup($m), ['cok_yuksek', 'yuksek'], true))->sortByDesc('puan')->take(10)->values(),
            'son' => $maddeler->sortByDesc('updated_at')->take(6)->values(),
        ];
    }

    /** Yenileme takibi uyarısı (Risk Değerlendirmesi Yönetmeliği Md.12 süreleri). */
    public static function yenileme(Firma $firma, ?RiskDegerlendirmesi $rd): array
    {
        $yil = (int) config('isg.risk_gecerlilik_yili.'.$firma->tehlike_sinifi, 4);

        if (! $rd) {
            return ['durum' => 'yok', 'metin' => "Bu işyeri için risk değerlendirmesi yok. {$firma->tehlikeSinifiEtiketi()} işyeri {$yil} yılda bir yenilenir."];
        }
        if (! $rd->rapor_tarihi || ! $rd->gecerlilik_tarihi) {
            return ['durum' => 'eksik', 'metin' => "Risk değerlendirmesi tarihi girilmemiş. Bu işyeri {$yil} yılda bir yenileme kapsamındadır; belge tarihini girince yenileme takibi başlar."];
        }

        $kalan = (int) Carbon::today()->diffInDays($rd->gecerlilik_tarihi, false);

        return match (true) {
            $kalan < 0 => ['durum' => 'gecti', 'metin' => 'Geçerlilik '.$rd->gecerlilik_tarihi->format('d.m.Y').' tarihinde doldu ('.abs($kalan).' gün geçti). Değerlendirme yenilenmelidir.'],
            $kalan <= 90 => ['durum' => 'yakin', 'metin' => 'Geçerlilik '.$rd->gecerlilik_tarihi->format('d.m.Y').' tarihinde doluyor ('.$kalan.' gün kaldı).'],
            default => ['durum' => 'gecerli', 'metin' => 'Geçerli — yenileme '.$rd->gecerlilik_tarihi->format('d.m.Y').' ('.$kalan.' gün). Tehlike, proses, ekipman değişikliği veya kaza sonrası süre beklenmeden yenilenir.'],
        };
    }

    /**
     * Önlem (aksiyon) takibi — işyerinin tüm değerlendirmelerinde önerisi
     * olan açık maddeler.
     *
     * @return Collection<int, array{madde: RiskMaddesi, rd: RiskDegerlendirmesi, termin: ?Carbon, gecikti: bool, grup: ?string}>
     */
    public static function aksiyonlar(Firma $firma): Collection
    {
        $bugun = Carbon::today();

        return RiskMaddesi::query()
            ->whereHas('riskDegerlendirmesi', fn ($q) => $q->where('firma_id', $firma->id))
            ->with('riskDegerlendirmesi:id,firma_id,yontem,belge_no,rapor_tarihi')
            ->whereNotNull('oneri')->where('oneri', '!=', '')
            ->get()
            ->map(function (RiskMaddesi $m) use ($bugun) {
                $t = static::terminTarihi($m->termin);

                return [
                    'madde' => $m,
                    'rd' => $m->riskDegerlendirmesi,
                    'termin' => $t,
                    'gecikti' => static::acikMi($m) && $t?->lt($bugun),
                    'grup' => static::seviyeGrubu($m->riskDegerlendirmesi->yontem, $m->puan),
                ];
            })
            ->sortBy(fn ($a) => [static::acikMi($a['madde']) ? 0 : 1, $a['gecikti'] ? 0 : 1, $a['termin']?->timestamp ?? PHP_INT_MAX, -((float) $a['madde']->puan)])
            ->values();
    }

    /** NACE kodunun ilk iki hanesine göre grup. */
    public static function naceGrubu(Firma $firma): ?array
    {
        $bolum = substr(preg_replace('/\D/', '', (string) $firma->nace_kodu), 0, 2);

        if (strlen($bolum) < 2) {
            return null;
        }

        foreach (config('risk_nace.gruplar') as $anahtar => $g) {
            if (in_array($bolum, $g['bolumler'], true)) {
                return ['anahtar' => $anahtar, 'bolum' => $bolum, ...$g];
            }
        }

        return null;
    }

    /**
     * Risk analizi raporunda bulunması gerekenler — İSG Risk Değerlendirmesi
     * Yönetmeliği başlıklarına göre kayıtlardan kontrol.
     *
     * @return array<int, array{baslik: string, aciklama: string, dayanak: string, tamam: ?bool, sonuc: string, url: ?string}>
     */
    public static function raporKontrolleri(Firma $firma, ?RiskDegerlendirmesi $rd): array
    {
        $maddeler = $rd ? $rd->maddeler()->get() : collect();
        $ekip = collect($rd?->ekip ?? []);
        $url = fn (callable $f) => rescue($f, null, false);

        $k = [
            ['İşyeri kapsamı ve NACE kimliği', 'İşyeri unvanı, adres, SGK sicil, tehlike sınıfı ve NACE kodu.', '6331 Md.10; Risk Değ. Yön. Md.8',
                filled($firma->nace_kodu) && filled($firma->adres) && filled($firma->sgk_sicil_no), 'NACE '.($firma->nace_kodu ?: '—').' · SGK '.($firma->sgk_sicil_no ?: '—'), $url(fn () => FirmaResource::getUrl('edit', ['record' => $firma]))],
            ['Risk değerlendirme ekibi', 'İşveren / vekili, İSG uzmanı, işyeri hekimi, çalışan temsilcisi, destek elemanı ve bilgi sahibi çalışan.', 'Risk Değ. Yön. Md.6',
                $ekip->count() >= 4, $ekip->count().' ekip üyesi kayıtlı', $url(fn () => $rd ? RiskDegerlendirmesiResource::getUrl('edit', ['record' => $rd]) : null)],
            ['Bölüm, faaliyet ve iş akışları', 'Her bölüm / faaliyet için normal, bakım-temizlik ve olağan dışı çalışmalar.', 'Risk Değ. Yön. Md.7-8',
                $maddeler->filter(fn ($m) => filled($m->bolum))->count() > 0, $maddeler->pluck('bolum')->filter()->unique()->count().' bölüm, '.$maddeler->pluck('faaliyet')->filter()->unique()->count().' faaliyet', null],
            ['Tehlikelerin tanımlanması', 'Tehlike, risk ve etkilenen kişiler (çalışan / taşeron / ziyaretçi).', 'Risk Değ. Yön. Md.7',
                $maddeler->count() > 0, $maddeler->count().' risk maddesi', null],
            ['Risklerin seçilen yöntemle puanlanması', 'Mevcut önlemler dikkate alınarak olasılık / şiddet (ve frekans) ile puan ve düzey.', 'Risk Değ. Yön. Md.9',
                $maddeler->count() > 0 && $maddeler->every(fn ($m) => $m->puan), $maddeler->filter(fn ($m) => ! $m->puan)->count().' puansız madde', null],
            ['Kontrol tedbirleri (önlem, sorumlu, termin)', 'Kontrol hiyerarşisine göre önerilen önlem, sorumlu ve termin.', 'Risk Değ. Yön. Md.10',
                $maddeler->count() > 0 && $maddeler->every(fn ($m) => filled($m->oneri) && filled($m->termin)), $maddeler->filter(fn ($m) => blank($m->oneri) || blank($m->termin))->count().' maddede önlem / termin eksik', null],
            ['Artık (rezidüel) risk', 'Önlem sonrası riskin yeniden değerlendirilmesi.', 'Risk Değ. Yön. Md.10-11',
                $maddeler->count() > 0 && $maddeler->every(fn ($m) => $m->son_puan), $maddeler->filter(fn ($m) => ! $m->son_puan)->count().' maddede rezidüel puan yok', null],
            ['Makine, ekipman ve periyodik kontroller', 'İş ekipmanları envanteri ve periyodik kontrol kanıtları.', 'İş Ekipmanları Yön.',
                IsEkipmani::query()->where('firma_id', $firma->id)->exists() ? true : null, IsEkipmani::query()->where('firma_id', $firma->id)->count().' ekipman kaydı', $url(fn () => PeriyodikKontrol::getUrl(['firma' => $firma->id]))],
            ['Kimyasallar ve SDS', 'Kullanılan / depolanan kimyasallar, SDS ve etiketler.', 'Kimyasal Maddeler Yön.',
                KimyasalUrun::query()->where('firma_id', $firma->id)->exists() ? true : null, KimyasalUrun::query()->where('firma_id', $firma->id)->count().' kimyasal kaydı', $url(fn () => KimyasalSicili::getUrl(['firma' => $firma->id]))],
            ['Ölçüm ve geçmiş olay kanıtları', 'Ortam ölçümleri, kaza / ramak kala kayıtlarının özeti.', '6331 Md.10',
                OrtamOlcumu::query()->where('firma_id', $firma->id)->whereNotNull('olcumler')->exists() || OlayKaydi::query()->where('firma_id', $firma->id)->exists() ? true : null,
                OlayKaydi::query()->where('firma_id', $firma->id)->count().' olay kaydı', $url(fn () => OrtamOlcumleri::getUrl(['firma' => $firma->id]))],
            ['Acil durum ve yangın senaryoları', 'Acil durum planı ile ilişkilendirme.', 'Risk Değ. Yön. Md.11; Acil Durumlar Yön.',
                AcilDurumPlani::query()->where('firma_id', $firma->id)->whereNotNull('rapor_tarihi')->exists(), AcilDurumPlani::query()->where('firma_id', $firma->id)->whereNotNull('rapor_tarihi')->exists() ? 'Acil durum planı var' : 'Acil durum planı yok', $url(fn () => AcilDurumSayfasi::getUrl(['firma' => $firma->id]))],
            ['Özel politika gerektiren gruplar', 'Genç, yaşlı, gebe, engelli, kadın çalışanlar vb.', 'Risk Değ. Yön. Md.8; 6331 Md.10',
                $firma->calisanlar()->where('aktif', true)->exists() ? true : null, $firma->calisanlar()->where('aktif', true)->count().' aktif çalışan kaydı', $url(fn () => CalisanResource::getUrl('index'))],
            ['Belge künyesi, onay ve yenileme', 'Rapor tarihi, geçerlilik, belge / revizyon no, revizyon nedeni ve işveren onayı.', 'Risk Değ. Yön. Md.11-12',
                $rd && $rd->rapor_tarihi && $rd->gecerlilik_tarihi && $rd->durum === 'yayinlandi', $rd ? ('Durum: '.($rd->durum === 'yayinlandi' ? 'yayınlandı' : 'taslak').' · Rev. '.($rd->revizyon_no ?: '00')) : 'Değerlendirme yok', $url(fn () => $rd ? RiskDegerlendirmesiResource::getUrl('edit', ['record' => $rd]) : null)],
        ];

        return collect($k)->map(fn ($r) => ['baslik' => $r[0], 'aciklama' => $r[1], 'dayanak' => $r[2], 'tamam' => $r[3], 'sonuc' => $r[4], 'url' => $r[5]])->all();
    }

    /** Uygulama yol haritası (kapsamdan izlemeye 10 adım) — rapor kontrollerinden türetilir. */
    public static function yolHaritasi(array $kontroller, array $ozet): array
    {
        $t = fn (string $baslik) => collect($kontroller)->firstWhere('baslik', $baslik)['tamam'] ?? false;

        return [
            ['NACE ve işyeri kapsamını doğrula', 'Tam NACE kodu, faaliyet, tehlike sınıfı ve değerlendirmeye dahil işyeri.', $t('İşyeri kapsamı ve NACE kimliği')],
            ['Bölüm, faaliyet ve iş akışını çıkar', 'Normal, bakım / temizlik ve olağan dışı çalışmalar sahada doğrulanır.', $t('Bölüm, faaliyet ve iş akışları')],
            ['Ekipman, madde ve atık envanterini tamamla', 'Makine, kaldırma, elektrik, kimyasal / SDS, depolama ve atıklar.', $t('Makine, ekipman ve periyodik kontroller') && $t('Kimyasallar ve SDS')],
            ['Ekibi kur, çalışan görüşlerini al', 'Çalışan temsilcisi, destek elemanı ve özel politika gerektiren gruplar.', $t('Risk değerlendirme ekibi')],
            ['NACE teknik risklerini saha gözlemiyle doğrula', 'NACE listesindeki başlıklar bölüm / faaliyet bazında risk kaydına dönüşür.', $ozet['toplam'] > 0],
            ['Ölçüm, sağlık, acil durum ve geçmiş kayıtları bağla', 'Ortam ölçümleri, periyodik kontroller, kaza / ramak kala ve tatbikat.', $t('Ölçüm ve geçmiş olay kanıtları') && $t('Acil durum ve yangın senaryoları')],
            ['Riskleri seçilen yöntemle puanla', 'Mevcut önlemlerle olasılık / şiddet ve artık risk.', $t('Risklerin seçilen yöntemle puanlanması')],
            ['Kontrol hiyerarşisine göre önlem planla', 'Önlem, sorumlu, termin; tamamlanınca artık risk yeniden değerlendirilir.', $t('Kontrol tedbirleri (önlem, sorumlu, termin)')],
            ['Ekip incelemesi, işveren onayı ve belge kontrolü', 'Belge no, revizyon, kapsam notu ve onay.', $t('Belge künyesi, onay ve yenileme')],
            ['Yenileme ve değişiklik tetikleyicilerini izle', 'Süre, kaza, proses / ekipman değişikliği, taşınma, yeni tehlike ve mevzuat değişikliği.', $ozet['geciken'] === 0 && $t('Belge künyesi, onay ve yenileme')],
        ];
    }
}
