<?php

namespace App\Support;

use App\Models\Firma;
use App\Models\YillikPlan;
use Illuminate\Support\Collection;

/**
 * İşyerinde yapılan gerçek iş kalemleri (`config('isg.kkd_matris.is_kalemleri')`) üzerinden
 * Yıllık Eğitim Planı 4. Bölümünü, Eğitim Katılım Formu ekip/iş kolu seçeneklerini ve firma
 * bazlı müfredatı tek merkezden besler.
 */
class IsKalemiKutuphanesi
{
    /** Sektör anahtarı → öncelikli gösterilecek iş kalemi grubu. */
    private const SEKTOR_ANA_GRUP = [
        'insaat' => 'İnşaat / Şantiye',
        'maden' => 'İnşaat / Şantiye',
        'asansor' => 'İnşaat / Şantiye',
        'enerji_elektrik' => 'İnşaat / Şantiye',
        'cimento_beton' => 'İnşaat / Şantiye',
        'nace_metal' => 'İmalat / Fabrika',
        'nace_plastik' => 'İmalat / Fabrika',
        'nace_kimya' => 'İmalat / Fabrika',
        'nace_ahsap' => 'İmalat / Fabrika',
        'nace_basim' => 'İmalat / Fabrika',
        'tekstil' => 'İmalat / Fabrika',
        'nace_gida' => 'Gıda / Tarım / Ormancılık',
        'nace_tarim' => 'Gıda / Tarım / Ormancılık',
        'nakliye_tasima' => 'Genel / Hizmet / Lojistik',
        'nace_ofis' => 'Genel / Hizmet / Lojistik',
        'nace_ticaret' => 'Genel / Hizmet / Lojistik',
        'nace_temizlik' => 'Genel / Hizmet / Lojistik',
        'nace_konaklama' => 'Genel / Hizmet / Lojistik',
        'nace_egitim' => 'Genel / Hizmet / Lojistik',
        'nace_saglik' => 'Genel / Hizmet / Lojistik',
        'nace_atik' => 'Genel / Hizmet / Lojistik',
    ];

    /**
     * Grup adı => [anahtar => ['anahtar', 'ad', 'grup', 'egitim_konusu']]
     *
     * @return array<string, array<string, array{anahtar: string, ad: string, grup: string, egitim_konusu: string}>>
     */
    public static function gruplar(): array
    {
        $sonuc = [];

        foreach (config('isg.kkd_matris.is_kalemleri', []) as $grup => $kalemler) {
            foreach ($kalemler as $k) {
                $anahtar = trim((string) ($k['anahtar'] ?? ''));
                $konu = trim((string) ($k['egitim_konusu'] ?? ''));
                if ($anahtar === '' || $konu === '') {
                    continue;
                }

                $sonuc[$grup][$anahtar] = [
                    'anahtar' => $anahtar,
                    'ad' => (string) ($k['ad'] ?? $anahtar),
                    'grup' => (string) $grup,
                    'egitim_konusu' => $konu,
                ];
            }
        }

        return $sonuc;
    }

    /** @return Collection<string, array{anahtar: string, ad: string, grup: string, egitim_konusu: string}> */
    public static function tumu(): Collection
    {
        $duz = [];
        foreach (static::gruplar() as $kalemler) {
            foreach ($kalemler as $anahtar => $veri) {
                $duz[$anahtar] = $veri;
            }
        }

        return collect($duz);
    }

    /** Verilen sektör (veya firmanın etkin grubu) için öncelikli iş kalemi grubu adı. */
    public static function anaGrup(?string $sektorAnahtari, ?Firma $firma = null): string
    {
        $sektor = $sektorAnahtari ?: ($firma ? IseOzguSenkron::etkinGrup($firma) : null);

        return self::SEKTOR_ANA_GRUP[$sektor] ?? 'İnşaat / Şantiye';
    }

    /**
     * Eğitim Katılım Formu "Çalışan Grubu / İş Kolu" butonları için seçenekler:
     *  - Firmada `is_kalemleri` seçiliyse YALNIZ firmanın seçili iş kalemleri döner ($tumunuGoster = false iken).
     *  - Seçili değilse aktif sektörün ana grubundaki tüm iş kalemleri döner.
     *
     * @return array<string, string> anahtar => ad
     */
    public static function katilimSecenekleri(?Firma $firma, ?string $sektorAnahtari, bool $tumunuGoster = false): array
    {
        $tumu = static::tumu();
        $firmaKalemleri = array_values(array_filter((array) ($firma?->is_kalemleri ?? [])));

        if (! $tumunuGoster && $firmaKalemleri !== []) {
            $secili = [];
            foreach ($firmaKalemleri as $a) {
                if ($tumu->has($a)) {
                    $secili[$a] = $tumu[$a]['ad'];
                }
            }
            if ($secili !== []) {
                return $secili;
            }
        }

        if ($tumunuGoster) {
            return $tumu->mapWithKeys(fn (array $k) => [$k['anahtar'] => $k['ad']])->all();
        }

        $anaGrup = static::anaGrup($sektorAnahtari, $firma);
        $grupKalemleri = static::gruplar()[$anaGrup] ?? [];

        return collect($grupKalemleri)->mapWithKeys(fn (array $k) => [$k['anahtar'] => $k['ad']])->all();
    }

    /**
     * Seçilen iş kalemlerinden Yıllık Eğitim Planı 4. Bölüm (`ise_ozgu`) satırlarını üretir.
     * Mevcut plandaki tamamlanmış (G) ayları konu/anahtar eşleşmesiyle korur.
     *
     * @param  array<int, string>  $anahtarlar
     * @param  array<int, array<string, mixed>>  $mevcutEgitimler
     * @return array<int, array<string, mixed>>
     */
    public static function planSatirlari(array $anahtarlar, int $kilitAy = 0, array $mevcutEgitimler = []): array
    {
        $tumu = static::tumu();

        // Mevcut 4. bölüm tamamlandı (G) aylarını konu adı veya kütüphane anahtarıyla koru
        $mevcutG = [];
        foreach (collect($mevcutEgitimler)->where('kategori', 'ise_ozgu') as $m) {
            $norm = EgitimKonulari::normalize((string) ($m['konu'] ?? ''));
            $kAnahtar = (string) ($m['kutuphane_anahtari'] ?? '');
            foreach (($m['aylar'] ?? []) as $ay => $durum) {
                if ($durum === 'tamamlandi') {
                    $mevcutG[$norm][$ay] = true;
                    if ($kAnahtar !== '') {
                        $mevcutG[$kAnahtar][$ay] = true;
                    }
                }
            }
        }

        $satirlar = [];
        foreach (array_values(array_unique($anahtarlar)) as $a) {
            $k = $tumu->get($a);
            if (! $k) {
                continue;
            }

            $kutuphaneAnahtari = 'kalem_'.$a;
            $satir = YillikPlan::maddeAylarIle([[
                'konu' => $k['ad'],
                'kategori' => 'ise_ozgu',
                'hedef' => $k['egitim_konusu'],
                'hedef_kitle' => $k['ad'],
                'egitici' => IseOzguEgitimKutuphanesi::VARSAYILAN_EGITICI,
                'hafta' => 2,
                'varsayilan_aylar' => range(0, 11),
                'kutuphane_anahtari' => $kutuphaneAnahtari,
            ]], $kilitAy)[0];

            $norm = EgitimKonulari::normalize($k['ad']);
            foreach (array_keys(($mevcutG[$kutuphaneAnahtari] ?? []) + ($mevcutG[$norm] ?? [])) as $ay) {
                $satir['aylar'][$ay] = 'tamamlandi';
            }

            $satirlar[] = $satir;
        }

        return $satirlar;
    }
}
