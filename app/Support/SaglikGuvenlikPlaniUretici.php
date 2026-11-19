<?php

namespace App\Support;

use App\Models\Firma;
use Barryvdh\DomPDF\Facade\Pdf;
use Closure;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Yapı İşlerinde İş Sağlığı ve Güvenliği Yönetmeliği (m.5, m.7, m.8, Ek-1, Ek-2, Ek-3)
 * kapsamında Sağlık ve Güvenlik Planı (SGP) PDF üretici.
 */
class SaglikGuvenlikPlaniUretici
{
    /**
     * Yapı İşlerinde İSG Yönetmeliği Ek-1 & Ek-2 kapsamındaki standart yapı iş fazları.
     *
     * @return array<string, array{baslik: string, tehlikeler: string, onlemler: string, kkd: string}>
     */
    public static function isFazlari(): array
    {
        return [
            'santiye_kurulum' => [
                'baslik' => '1. Şantiye Kurulumu, Geçici Elektrik ve Çevre Güvenliği',
                'tehlikeler' => 'Yetkisiz giriş, kaçak elektrik akımı, şantiye içi araç-yaya çarpışması, yangın',
                'onlemler' => 'Şantiye çevresi en az 2 m perde ile kapatılır, giriş-çıkış kontrol altına alınır. Tüm tali panolarda 30 mA kaçak akım rölesi bulundurulur ve topraklama ölçümü yapılır. Araç ve yaya yolları ayrılır.',
                'kkd' => 'Baret (EN 397), Reflektörlü Yelek, Çelik Burunlu Ayakkabı (S3)',
            ],
            'kazi_temel' => [
                'baslik' => '2. Kazı, Hafriyat, İksa ve Temel Çalışmaları (Ek-2 Özel Risk)',
                'tehlikeler' => 'Toprak kayması / göçük, yeraltı enerji ve doğalgaz hatları, kazı çukuruna düşme',
                'onlemler' => '1,50 metreden derin kazılarda yan yüzeyler şevlendirilir veya iksa sistemi kurulur. Kazı kenarına 1 m mesafede malzeme istiflenmez, kenar korkulukları ve gece ikaz lambaları tesis edilir. Kazı öncesi altyapı planları kontrol edilir.',
                'kkd' => 'Baret (EN 397), S3 İş Ayakkabısı, Toz Maskesi (FFP2), Koruyucu Gözlük',
            ],
            'kalip_demir_beton' => [
                'baslik' => '3. Kalıp, Donatı (Demir) ve Beton Döküm İşleri',
                'tehlikeler' => 'Filiz demirlerine saplanma, kalıp çökmesi, beton pompası bom teması, yüksekten düşme',
                'onlemler' => 'Açıkta kalan filiz demirlerine mantar koruyucu başlık takılır. Kalıp sökümü yetkili teknik nezaretçi kontrolünde yapılır. Beton pompası ayakları tam açılır, enerji nakil hatlarına güvenli mesafe korunur.',
                'kkd' => 'Baret, Kesilmeye Dirençli Eldiven (EN 388), S3 Çelik Tabanlı Bot, Emniyet Kemeri',
            ],
            'yuksekte_iskele' => [
                'baslik' => '4. Yüksekte Çalışma, Cephe İskelesi, Çatı ve Kenar Koruma (Ek-2 Özel Risk)',
                'tehlikeler' => 'Döşeme boşluklarından, iskeleden veya çatıdan düşme, yukarıdan malzeme düşmesi',
                'onlemler' => 'Asansör, aydınlık ve şaft boşlukları sağlam korkuluk (110 cm üst, 55 cm ara, 15 cm topuk levhası) ve güvenlik ağı ile kapatılır. Cephe iskeleleri TS EN 12810/12811 standardına uygun kurulur ve yeşil/kırmızı etiketle kontrol edilir.',
                'kkd' => 'Paraşüt Tipi Tam Vücut Emniyet Kemeri (EN 361), Çift Kollu Şok Emicili Lanyard, Çeneantlı Baret',
            ],
            'vinc_kaldirma' => [
                'baslik' => '5. Kule Vinç, Mobil Vinç ve Ağır Yük Kaldırma Operasyonları',
                'tehlikeler' => 'Yükün devrilmesi / düşmesi, sapan kopması, rüzgar etkisi, bom çarpışması',
                'onlemler' => 'Kaldırma ekipmanlarının yılda en az 1 kez periyodik kontrolü yapılır. Yük altında insan bulundurulmaz, eğitimli işaretçi/sapancı görevlendirilir. Rüzgar hızı 50 km/s üzerinde vinç operasyonu durdurulur.',
                'kkd' => 'Baret, Mekanik Koruyucu Eldiven, Reflektörlü Yelek, S3 Ayakkabı',
            ],
            'elektrik_mekanik' => [
                'baslik' => '6. Elektrik Tesisatı, Mekanik Montaj ve Sıcak İş / Kaynak Çalışmaları',
                'tehlikeler' => 'Elektrik çarpması, ark parlaması, yangın/patlama, kaynak gazı solunumu',
                'onlemler' => 'Sıcak iş ve enerji kesme (EKED/LOTO) için İş İzin Formu (PTW) düzenlenir. Oksi-asetilen tüplerinde alev geri tepme valfi kullanılır, çalışma alanında yangın tüpü bulundurulur.',
                'kkd' => 'Kaynak Maskesi, Yanmaz Deri Önlük/Eldiven, İzole Eldiven ve Ayakkabı',
            ],
            'ince_isler_boya' => [
                'baslik' => '7. Duvar, Sıva, Boya, Kimyasal ve İnce İşler',
                'tehlikeler' => 'Kimyasal buhar/toz maruziyeti, seyyar merdivenden düşme, yetersiz aydınlatma',
                'onlemler' => 'Kimyasalların GBF (MSDS) formları sahada bulundurulur, kapalı alanlarda cebri havalandırma sağlanır. Seyyar A tipi merdivenler yerine korkuluklu platform tercih edilir.',
                'kkd' => 'Organik Buhar/Toz Maskesi, Kimyasal Eldiven (EN 374), Koruyucu Gözlük',
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function fazSecenekleri(): array
    {
        return collect(static::isFazlari())
            ->mapWithKeys(fn (array $f, string $k) => [$k => $f['baslik']])
            ->all();
    }

    /**
     * Yapı İşlerinde İSG Yönetmeliği m.8 uyarınca Ek-3 Bildirim zorunluluğu hesabı.
     * (>30 iş günü ve devamlı >20 çalışan) VEYA (>500 yevmiye).
     */
    public static function ek3BildirimZorunluMu(int $sureGun, int $azamiCalisan, int $toplamYevmiye): bool
    {
        return ($sureGun > 30 && $azamiCalisan > 20) || $toplamYevmiye > 500;
    }

    /**
     * @param  array{
     *     santiye_adi?: ?string,
     *     santiye_adresi?: ?string,
     *     proje_muduru?: ?string,
     *     hazirlik_koordinatoru?: ?string,
     *     uygulama_koordinatoru?: ?string,
     *     baslangic_tarihi?: ?string,
     *     tahmini_sure_gun?: int|string|null,
     *     azami_calisan?: int|string|null,
     *     toplam_yevmiye?: int|string|null,
     *     taseron_sayisi?: int|string|null,
     *     fazlar?: array<int, string>,
     *     ozel_kurallar?: ?string,
     * }  $veri
     */
    public static function pdf(Firma $firma, array $veri = []): StreamedResponse
    {
        $firma->loadMissing(['igu', 'isyeriHekimi', 'dsp', 'taseronlar', 'acilEkipler.uyeler']);

        $sureGun = max(1, (int) ($veri['tahmini_sure_gun'] ?? 90));
        $azamiCalisan = max(1, (int) ($veri['azami_calisan'] ?? ($firma->calisan_sayisi ?: 25)));
        $toplamYevmiye = max(1, (int) ($veri['toplam_yevmiye'] ?? ($sureGun * $azamiCalisan)));

        $secilenFazAnahtarlari = ! empty($veri['fazlar']) && is_array($veri['fazlar'])
            ? $veri['fazlar']
            : array_keys(static::isFazlari());

        $tumFazlar = static::isFazlari();
        $fazlar = collect($secilenFazAnahtarlari)
            ->map(fn (string $k) => $tumFazlar[$k] ?? null)
            ->filter()
            ->values()
            ->all();

        $sonRisk = $firma->riskDegerlendirmeleri()
            ->with('maddeler')
            ->latest('rapor_tarihi')
            ->latest('id')
            ->first();

        $oncelikliRiskler = $sonRisk
            ? $sonRisk->maddeler->sortByDesc(fn ($m) => (int) $m->olasilik * (int) $m->siddet)->take(12)->values()
            : collect();

        $pdf = Pdf::loadView('pdf.saglik-guvenlik-plani', [
            'firma' => $firma,
            'veri' => [
                'santiye_adi' => $veri['santiye_adi'] ?: ($firma->unvan.' Şantiyesi'),
                'santiye_adresi' => $veri['santiye_adresi'] ?: ($firma->adres ?: $firma->il),
                'proje_muduru' => $veri['proje_muduru'] ?: ($firma->isveren_ad_soyad ?: '—'),
                'hazirlik_koordinatoru' => $veri['hazirlik_koordinatoru'] ?: ($firma->igu?->ad_soyad ?: '—'),
                'uygulama_koordinatoru' => $veri['uygulama_koordinatoru'] ?: ($firma->igu?->ad_soyad ?: '—'),
                'baslangic_tarihi' => $veri['baslangic_tarihi'] ?: now()->format('d.m.Y'),
                'tahmini_sure_gun' => $sureGun,
                'azami_calisan' => $azamiCalisan,
                'toplam_yevmiye' => $toplamYevmiye,
                'taseron_sayisi' => (int) ($veri['taseron_sayisi'] ?? $firma->taseronlar->where('aktif', true)->count()),
                'ek3_zorunlu' => static::ek3BildirimZorunluMu($sureGun, $azamiCalisan, $toplamYevmiye),
                'ozel_kurallar' => $veri['ozel_kurallar'] ?? null,
            ],
            'fazlar' => $fazlar,
            'taseronlar' => $firma->taseronlar->where('aktif', true)->values(),
            'oncelikliRiskler' => $oncelikliRiskler,
        ])->setPaper('a4');

        $dosyaAdi = 'saglik-guvenlik-plani-'.Str::slug($firma->kisaEtiket()).'.pdf';

        return response()->streamDownload(fn () => print ($pdf->output()), $dosyaAdi);
    }

    /**
     * Sayfa başlıklarında kullanılabilecek hazır Filament modal aksiyonu.
     *
     * @param  ?Closure(): ?Firma  $seciliFirma
     */
    public static function filamentAction(?Closure $seciliFirma = null): Action
    {
        return Action::make('saglikGuvenlikPlani')
            ->label('Sağlık ve Güvenlik Planı (SGP)')
            ->icon('heroicon-o-building-office-2')
            ->color('warning')
            ->modalWidth('2xl')
            ->modalHeading('🏗️ Yapı İşleri Sağlık ve Güvenlik Planı (SGP) Oluştur')
            ->modalDescription('Yapı İşlerinde İSG Yönetmeliği (m.5, m.7, m.8, Ek-2 ve Ek-3) kapsamında firmanın İGU/İH atamalarını, alt işverenlerini, öncelikli risk maddelerini ve seçilen yapı iş fazlarını içeren resmi SGP PDF çıktısını üretir.')
            ->modalSubmitActionLabel('SGP PDF İndir')
            ->schema([
                Select::make('firma_id')
                    ->label('Firma / İşyeri')
                    ->options(fn () => Firma::query()
                        ->where('user_id', Filament::auth()->id())
                        ->orderBy('unvan')
                        ->secenekler()
                        ->all())
                    ->default(fn () => $seciliFirma ? $seciliFirma()?->id : null)
                    ->searchable()
                    ->required(),
                TextInput::make('santiye_adi')
                    ->label('Proje / Şantiye Adı')
                    ->placeholder('Boş bırakılırsa firma ünvanı kullanılır'),
                TextInput::make('santiye_adresi')
                    ->label('Şantiye Adresi')
                    ->placeholder('Boş bırakılırsa firma adresi kullanılır'),
                TextInput::make('proje_muduru')
                    ->label('İşveren / Proje Müdürü')
                    ->placeholder('Boş bırakılırsa işveren adı kullanılır'),
                TextInput::make('uygulama_koordinatoru')
                    ->label('Sağlık ve Güvenlik Koordinatörü')
                    ->placeholder('Boş bırakılırsa atanan İGU adı kullanılır'),
                TextInput::make('tahmini_sure_gun')
                    ->label('Tahmini İş Süresi (İş Günü)')
                    ->numeric()
                    ->default(90)
                    ->helperText('30 iş günü üzeri ve >20 çalışan (veya >500 yevmiye) işlerde Ek-3 Yapı İşleri Bildirimi zorunluluğu otomatik işaretlenir.'),
                TextInput::make('azami_calisan')
                    ->label('Aynı Anda Azami Çalışan Sayısı')
                    ->numeric()
                    ->default(fn () => $seciliFirma && $seciliFirma()?->calisan_sayisi ? $seciliFirma()->calisan_sayisi : 25),
                TextInput::make('toplam_yevmiye')
                    ->label('Toplam Yevmiye (Adam × Gün)')
                    ->numeric()
                    ->default(600),
                CheckboxList::make('fazlar')
                    ->label('Kapsamdaki Yapı İş Fazları ve Ek-2 Özel Riskli İşler')
                    ->options(static::fazSecenekleri())
                    ->default(array_keys(static::isFazlari()))
                    ->columns(1)
                    ->required(),
                Textarea::make('ozel_kurallar')
                    ->label('Projeye / Şantiyeye Özel Ek Kurallar (İsteğe Bağlı)')
                    ->rows(2),
            ])
            ->action(function (array $data) {
                $firma = Firma::where('user_id', Filament::auth()->id())->findOrFail($data['firma_id']);

                return static::pdf($firma, $data);
            });
    }
}
