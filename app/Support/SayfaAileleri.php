<?php

namespace App\Support;

use App\Filament\Pages;
use App\Filament\Resources\EgitimPaketis\EgitimPaketiResource;
use App\Filament\Resources\RiskDegerlendirmesis\RiskDegerlendirmesiResource;
use Filament\Resources\Resource;

/**
 * Menü sadeleştirmesi (11.10.2026, isgpratik karşılaştırması sonrası): aynı işi
 * yapan sayfalar "aile" olarak gruplanır. Ailenin ilk sayfası sol menüde görünür,
 * diğerleri menüden gizlenir ($shouldRegisterNavigation = false) ve her aile
 * sayfasının üstünde ortak sekme şeridinden açılır
 * (resources/views/filament/components/sayfa-sekmeleri.blade.php).
 *
 * URL'ler, yetki anahtarları (SinirliErisim) ve kayıtlı veriler değişmez —
 * yalnız menüdeki yer değişir.
 */
class SayfaAileleri
{
    /** @return array<string, array<int, array{0: class-string, 1: string}>> */
    public static function aileler(): array
    {
        return [
            'kontrol' => [
                [Pages\KontrolMerkezi::class, 'Kontrol Merkezi'],
                [Pages\IsyeriDurumMerkezi::class, 'İşyeri Durumu'],
                [Pages\TesisUygunlukOzeti::class, 'Tesis Uygunluğu'],
                [Pages\UzmanRaporMerkezi::class, 'Uzman Raporları'],
                [Pages\RiskMerkezi::class, 'Risk Merkezi'],
            ],
            'saha' => [
                [Pages\HizliSahaBulgusu::class, 'Bulgular'],
                [Pages\ZiyaretModu::class, 'Bugün (Ziyaret Modu)'],
                [Pages\SahaDenetimi::class, 'Saha Denetimi'],
                [Pages\AiSahaAnalizi::class, 'Gözlem Raporu (AI)'],
                [Pages\DofOlustur::class, 'DÖF'],
                [Pages\TespitOneriDefteri::class, 'Tespit Öneri Defteri'],
            ],
            'risk' => [
                [Pages\RiskSihirbazi::class, 'Yeni Değerlendirme'],
                [RiskDegerlendirmesiResource::class, 'Kayıtlı Değerlendirmeler'],
            ],
            'acil' => [
                [Pages\AcilDurumPlani::class, 'Acil Durum Planı'],
                [Pages\AcilDurumKrokisi::class, 'Kroki'],
                [Pages\AcilDurumEkipleri::class, 'Ekipler'],
            ],
            'egitim_katilim' => [
                [Pages\EgitimKatilim::class, 'Eğitim Katılım'],
                [Pages\SertifikaOlustur::class, 'Sertifika'],
            ],
            'uzaktan' => [
                [EgitimPaketiResource::class, 'Paketler'],
                [Pages\UzaktanEgitimAtama::class, 'Atama'],
            ],
            'soru' => [
                [Pages\SoruBankasi::class, 'Soru Bankası'],
                [Pages\EgitimSorulari::class, 'AI ile Soru / Sınav'],
            ],
            'saglik' => [
                [Pages\SaglikGozetimi::class, 'Sağlık Gözetimi'],
                [Pages\MuayeneFormu::class, 'Muayene Formu (EK-2)'],
                [Pages\IseDonusBelgesi::class, 'İşe Dönüş Belgesi'],
                [Pages\MeslekHastaligiBildirimi::class, 'Meslek Hastalığı Bildirimi'],
            ],
            'olay' => [
                [Pages\OlayKayitlari::class, 'Olay Kayıtları'],
                [Pages\IsKazasiRaporu::class, 'Kaza İnceleme Raporu'],
                [Pages\KazaIstatistikleri::class, 'İstatistikler'],
            ],
            'kkd' => [
                [Pages\KkdTakip::class, 'Zimmet & Stok'],
                [Pages\KkdFormu::class, 'KKD Formu'],
                [Pages\KkdSecimMatrisi::class, 'Seçim Matrisi'],
            ],
            'arsiv' => [
                [Pages\DokumanYonetimi::class, 'Arşiv'],
                [Pages\OnayliDefterNushalari::class, 'Onaylı Defter Nüshaları'],
            ],
            'araclar' => [
                [Pages\Araclar::class, 'Araçlar'],
                [Pages\EReetem::class, 'Ücretsiz E-Reçetem'],
            ],
        ];
    }

    /**
     * Render hook kapsamlarına (sayfa sınıfı; Resource sayfasında + Resource
     * sınıfı) göre ailenin sekmeleri. Aile yoksa boş dizi.
     *
     * @param  array<int, string>  $kapsamlar
     * @return array<int, array{etiket: string, url: string, aktif: bool}>
     */
    public static function sekmeler(array $kapsamlar): array
    {
        foreach (static::aileler() as $uyeler) {
            $aktifSinif = collect($uyeler)->pluck(0)->first(fn (string $s) => in_array($s, $kapsamlar, true));

            if (! $aktifSinif) {
                continue;
            }

            $firma = request()->integer('firma') ?: null;

            return collect($uyeler)
                ->filter(fn (array $u) => $u[0]::canAccess())
                ->map(fn (array $u) => [
                    'etiket' => $u[1],
                    'url' => static::url($u[0], $firma),
                    'aktif' => $u[0] === $aktifSinif,
                ])
                ->values()
                ->all();
        }

        return [];
    }

    /**
     * Ana sayfanın menü vurgusu için ailedeki tüm sayfaların rota desenleri;
     * sınıf bir ailenin ana sayfası değilse boş dizi.
     *
     * @param  class-string  $anaSinif
     * @return array<int, string>
     */
    public static function aktifRotalar(string $anaSinif): array
    {
        foreach (static::aileler() as $uyeler) {
            if ($uyeler[0][0] !== $anaSinif) {
                continue;
            }

            return collect($uyeler)
                ->map(fn (array $u) => is_subclass_of($u[0], Resource::class)
                    ? $u[0]::getRouteBaseName().'.*'
                    : $u[0]::getRouteName())
                ->all();
        }

        return [];
    }

    /** @param  class-string  $sinif */
    private static function url(string $sinif, ?int $firma): string
    {
        $parametre = $firma ? ['firma' => $firma] : [];

        return is_subclass_of($sinif, Resource::class)
            ? $sinif::getUrl('index', $parametre)
            : $sinif::getUrl($parametre);
    }
}
