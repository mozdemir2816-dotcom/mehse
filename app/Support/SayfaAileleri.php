<?php

namespace App\Support;

use App\Filament\Pages;
use App\Filament\Resources\EgitimPaketis\EgitimPaketiResource;
use App\Filament\Resources\RiskDegerlendirmesis\RiskDegerlendirmesiResource;
use App\Filament\Resources\RiskProsedurs\RiskProsedurResource;
use App\Filament\Resources\RiskSablonus\RiskSablonuResource;
use App\Filament\Resources\Tehlikes\TehlikeResource;
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
                // Komuta Merkezi 5 sekmeden 3'e indi (29.10.2026): İşyeri Durumu + Tesis Uygunluğu + Uzman Raporları
                // "Firma Denetim & Operasyon" altında birleşti; eski adresler geriye uyum için çalışmaya devam eder.
                [Pages\KontrolMerkezi::class, 'Portföy Genel Bakış'],
                [Pages\FirmaDenetimOperasyon::class, 'Firma Denetim & Operasyon'],
                [Pages\RiskMerkezi::class, 'Risk & Tehlike Analitiği'],
            ],
            'saha' => [
                [Pages\HizliSahaBulgusu::class, 'Bulgular'],
                [Pages\ZiyaretModu::class, 'Bugün (Ziyaret Modu)'],
                [Pages\SahaDenetimi::class, 'Saha Denetimi'],
                [Pages\AiSahaAnalizi::class, 'Gözlem Raporu (AI)'],
                [Pages\DofOlustur::class, 'DÖF'],
                [Pages\TespitOneriDefteri::class, 'Tespit Öneri Defteri'],
                [Pages\OnBilgilendirmeler::class, 'Ön Bilgilendirme / Tebliğ'],
            ],
            // Risk & Tehlike Yönetimi: 12 sayfa → 3 ana sekme (30.10.2026); GRUPLAR['risk'] bkz.
            'risk' => [
                [Pages\RiskSihirbazi::class, 'Yeni Değerlendirme'],
                [RiskDegerlendirmesiResource::class, 'Kayıtlı Değerlendirmeler'],
                [Pages\JsaDegerlendirmesi::class, 'JSA (İşe Özgü Risk)'],
                [Pages\KimyasalYonetimi::class, 'Kimyasal Yönetimi'],
                [Pages\KimyasalRiskDegerlendirmesi::class, 'Kimyasal Risk Değ.'],
                [Pages\KimyasalSicili::class, 'Kimyasal Sicili / Afiş'],
                [Pages\TehlikeRiskAnalitigi::class, 'Tehlike ve Risk Analitiği'],
                [TehlikeResource::class, 'Tehlike Kütüphanesi'],
                [RiskSablonuResource::class, 'Sektör Şablonları'],
                [RiskProsedurResource::class, 'Risk Prosedürleri'],
                [Pages\TehlikeCakismalari::class, 'Kütüphane Çakışmaları'],
            ],
            'acil' => [
                [Pages\AcilDurumPlani::class, 'Acil Durum Planı'],
                [Pages\AcilDurumKrokisi::class, 'Kroki'],
                [Pages\AcilDurumEkipleri::class, 'Ekipler'],
                [Pages\YanginGuvenligi::class, 'Yangın Güvenliği'],
                [Pages\TatbikatTutanagi::class, 'Tatbikat Tutanağı'],
            ],
            // Eğitim & Yetkinlik Yönetim Merkezi: 9 sayfa → 3 ana sekme, tek menü girişi (30.10.2026); GRUPLAR['egitim'] bkz.
            'egitim' => [
                [Pages\EgitimKatilim::class, 'Eğitim Katılım'],
                [Pages\IsbasiEgitim::class, 'İşbaşı Eğitimi'],
                [Pages\EgitimYenilemeTakibi::class, 'Eğitim Takibi (Yenileme)'],
                [Pages\SertifikaOlustur::class, 'Sertifika'],
                [EgitimPaketiResource::class, 'Paketler'],
                [Pages\UzaktanEgitimAtama::class, 'Atama'],
                [Pages\SoruBankasi::class, 'Soru Bankası'],
                [Pages\EgitimSorulari::class, 'AI ile Soru / Sınav'],
                [Pages\ToolboxKonusmalari::class, 'Toolbox Konuşmaları'],
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
     * Üst sekme grupları: bir ailenin sayfaları az sayıda ana sekmede toplanır (29.10.2026, Saha Yönetim & Denetim).
     * Sayfalar eski adreslerinde kalır; ana sekme grubun ilk sayfasını açar, grubun diğer sayfaları alt hap şeridinden.
     *
     * @var array<string, array<int, array{etiket: string, ikon: string, siniflar: array<int, class-string>}>>
     */
    public const GRUPLAR = [
        'egitim' => [
            [
                'etiket' => 'Yasal Eğitim Takibi & Tutanaklar',
                'ikon' => 'heroicon-o-clipboard-document-list',
                'siniflar' => [Pages\EgitimKatilim::class, Pages\IsbasiEgitim::class, Pages\EgitimYenilemeTakibi::class, Pages\SertifikaOlustur::class],
            ],
            [
                'etiket' => 'Uzaktan Eğitim (LMS) & Sınav Portalı',
                'ikon' => 'heroicon-o-computer-desktop',
                'siniflar' => [EgitimPaketiResource::class, Pages\UzaktanEgitimAtama::class],
            ],
            [
                'etiket' => 'Soru & İçerik Bankası',
                'ikon' => 'heroicon-o-book-open',
                'siniflar' => [Pages\SoruBankasi::class, Pages\EgitimSorulari::class, Pages\ToolboxKonusmalari::class],
            ],
        ],
        // Acil Durum & Yangın: 5 sayfa → 3 ana sekme, tek menü girişi (30.10.2026)
        'acil' => [
            [
                'etiket' => 'Acil Durum Eylem Planı (ADEP)',
                'ikon' => 'heroicon-o-exclamation-triangle',
                'siniflar' => [Pages\AcilDurumPlani::class, Pages\AcilDurumEkipleri::class, Pages\AcilDurumKrokisi::class],
            ],
            [
                'etiket' => 'Yangın Güvenliği & Risk Belirleme',
                'ikon' => 'heroicon-o-fire',
                'siniflar' => [Pages\YanginGuvenligi::class],
            ],
            [
                'etiket' => 'Tatbikat Tutanağı & Senaryolar',
                'ikon' => 'heroicon-o-flag',
                'siniflar' => [Pages\TatbikatTutanagi::class],
            ],
        ],
        'risk' => [
            [
                'etiket' => 'Risk Analiz Sihirbazı & Değerlendirmeler',
                'ikon' => 'heroicon-o-bolt',
                'siniflar' => [Pages\RiskSihirbazi::class, RiskDegerlendirmesiResource::class, Pages\JsaDegerlendirmesi::class],
            ],
            [
                'etiket' => 'Kimyasal & Tehlike Analitiği',
                'ikon' => 'heroicon-o-beaker',
                'siniflar' => [Pages\KimyasalYonetimi::class, Pages\KimyasalRiskDegerlendirmesi::class, Pages\KimyasalSicili::class, Pages\TehlikeRiskAnalitigi::class],
            ],
            [
                'etiket' => 'Merkezi Kütüphane & Şablonlar',
                'ikon' => 'heroicon-o-book-open',
                'siniflar' => [TehlikeResource::class, RiskSablonuResource::class, RiskProsedurResource::class, Pages\TehlikeCakismalari::class],
            ],
        ],
        'saha' => [
            [
                'etiket' => 'Aktif Saha Denetimi & Ziyaret Modu',
                'ikon' => 'heroicon-o-bolt',
                'siniflar' => [Pages\ZiyaretModu::class, Pages\HizliSahaBulgusu::class, Pages\SahaDenetimi::class],
            ],
            [
                'etiket' => 'Bulgu & DÖF Takip Havuzu',
                'ikon' => 'heroicon-o-clipboard-document-check',
                'siniflar' => [Pages\DofOlustur::class],
            ],
            [
                'etiket' => 'Yasal Rapor & Defter Çıktıları',
                'ikon' => 'heroicon-o-document-text',
                'siniflar' => [Pages\TespitOneriDefteri::class, Pages\AiSahaAnalizi::class, Pages\OnBilgilendirmeler::class],
            ],
        ],
    ];

    /**
     * Gruplu ailenin sekmeleri: `ust` ana sekmeler (grubun ilk erişilebilir sayfasına gider), `alt` aktif grubun
     * birden çok sayfası varsa o sayfalar. Aile gruplu değilse (veya sayfa ailede yoksa) null — düz `sekmeler()` kullanılır.
     *
     * @param  array<int, string>  $kapsamlar
     * @return array{aile: string, ust: array<int, array{etiket: string, ikon: string, url: string, aktif: bool}>, alt: array<int, array{etiket: string, url: string, aktif: bool}>}|null
     */
    public static function sekmeGruplari(array $kapsamlar): ?array
    {
        foreach (static::GRUPLAR as $aile => $gruplar) {
            $uyeler = static::aileler()[$aile] ?? [];
            $aktifSinif = collect($uyeler)->pluck(0)->first(fn (string $s) => in_array($s, $kapsamlar, true));

            if (! $aktifSinif) {
                continue;
            }

            $etiketler = collect($uyeler)->mapWithKeys(fn (array $u) => [$u[0] => $u[1]]);
            $firma = request()->integer('firma') ?: null;
            $ust = $alt = [];

            foreach ($gruplar as $grup) {
                $erisilebilir = collect($grup['siniflar'])->filter(fn (string $s) => $s::canAccess())->values();

                if ($erisilebilir->isEmpty()) {
                    continue;
                }

                $aktif = in_array($aktifSinif, $grup['siniflar'], true);
                $ust[] = ['etiket' => $grup['etiket'], 'ikon' => $grup['ikon'], 'url' => static::url($erisilebilir->first(), $firma), 'aktif' => $aktif];

                if ($aktif && $erisilebilir->count() > 1) {
                    $alt = $erisilebilir->map(fn (string $s) => [
                        'etiket' => $etiketler[$s] ?? class_basename($s),
                        'url' => static::url($s, $firma),
                        'aktif' => $s === $aktifSinif,
                    ])->all();
                }
            }

            return ['aile' => $aile, 'ust' => $ust, 'alt' => $alt];
        }

        return null;
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
