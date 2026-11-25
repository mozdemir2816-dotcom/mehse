<?php

namespace Tests\Feature;

use App\Filament\Pages\EgitimKatilim as EgitimSayfasi;
use App\Filament\Pages\IseOzguEgitimKonulari;
use App\Filament\Pages\YillikPlan\YillikEgitimPlani;
use App\Models\Calisan;
use App\Models\EgitimKatilim;
use App\Models\Firma;
use App\Models\IseOzguEgitimKonusu;
use App\Models\User;
use App\Models\YillikPlan;
use App\Support\GeminiIseOzguEgitimUretici;
use App\Support\IseOzguSenkron;
use App\Support\YillikEgitimPlanlayici;
use App\Support\YillikPlanExcelUretici;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Kullanıcı talepleri (24.11.2026):
 *  - Tehlikeli sınıf: 8s Online + 4s Yüz Yüze (4. bölüm = 4s) = 12s; Tekrar = 4s Online + 4s Yüz Yüze = 8s.
 *  - 3 ayrı Eğitim Katılım Formu modu: Tamamı Yüz Yüze | Uzaktan (Online) | Yüz Yüze Tamamlama (Karma).
 *  - Yıllık Eğitim Planı 4. bölüm ↔ Eğitim Katılım Formu birebir eşitleme.
 *  - NACE / Elle "Gerçek İş Grubu" + Aynı İş Grubundaki Firmalara Eşitleme (G ayları korunur).
 *  - Kalıp Ekibi vs Sıva Ekibi ayrımı + Görev bazlı çalışan filtresi + Excel [EKİP: ...] etiketi.
 */
class EgitimKatilimYillikPlanSenkronTest extends TestCase
{
    use RefreshDatabase;

    private User $uzman;

    protected function setUp(): void
    {
        parent::setUp();
        $this->uzman = User::factory()->create();
        $this->actingAs($this->uzman);
    }

    public function test_tehlikeli_sinifta_dorduncu_bolum_4_saat_ve_tekrar_4_online_4_yuz_yuze_planlanir(): void
    {
        $teh = Firma::factory()->for($this->uzman)->create(['tehlike_sinifi' => 'tehlikeli', 'nace_kodu' => '25.62.01']);
        $dagilim = YillikEgitimPlanlayici::bolumDagilimi($teh, 'karma_lms');

        // İlk defa: 8s Online (2+2+4) + 4s Yüz Yüze (4. Bölüm 4s) = 12s
        $this->assertSame(8.0, $dagilim['ilk_online']);
        $this->assertSame(4.0, $dagilim['ilk_yuz_yuze']);
        $this->assertSame(12.0, $dagilim['ilk_toplam']);
        $this->assertSame(4.0, $dagilim['bolumler']['ise_ozgu']['ilk_yuz_yuze']);
        $this->assertSame(0.0, $dagilim['bolumler']['teknik']['ilk_yuz_yuze']);

        // Tekrar: 4s Online (1+1+2) + 4s Yüz Yüze (4. Bölüm 4s) = 8s
        $this->assertSame(4.0, $dagilim['tekrar_online']);
        $this->assertSame(4.0, $dagilim['tekrar_yuz_yuze']);
        $this->assertSame(8.0, $dagilim['tekrar_toplam']);
        $this->assertSame(4.0, $dagilim['bolumler']['ise_ozgu']['tekrar_yuz_yuze']);
    }

    public function test_uc_ayri_egitim_katilim_formu_modu_konu_saat_ve_pdf_basliklariyla_calisir(): void
    {
        $teh = Firma::factory()->for($this->uzman)->create([
            'unvan' => 'Örnek Metal A.Ş.',
            'tehlike_sinifi' => 'tehlikeli',
            'nace_kodu' => '25.62.01',
        ]);
        Calisan::factory()->for($teh)->create(['ad_soyad' => 'Ahmet Yılmaz']);

        // 1) Tamamı Yüz Yüze (12 saat, 2 gün, tüm 4 blok dahil)
        $sayfa = Livewire::test(EgitimSayfasi::class)
            ->set('firmaId', $teh->id)
            ->assertSet('egitimSekli', 'yuz_yuze')
            ->assertSet('dersSaati', 12)
            ->assertSet('sureGun', 2);
        $this->assertNull($sayfa->instance()->sureYasalUyarisi);

        $icerikYy = $sayfa->get('icerik');
        $this->assertNotEmpty(collect($icerikYy['genel_konular'])->where('dahil', true));
        $this->assertNotEmpty(collect($icerikYy['isyerine_ozgu']['maddeler'])->where('dahil', true));

        // 2) Uzaktan / Online Verilen Eğitim Formu (8 saat, 1 gün, 1-3. blok dahil, 4. blok hariç)
        $sayfa->set('egitimSekli', 'uzaktan')
            ->assertSet('dersSaati', 8)
            ->assertSet('sureGun', 1);
        $this->assertNull($sayfa->instance()->sureYasalUyarisi);

        $icerikOn = $sayfa->get('icerik');
        $this->assertNotEmpty(collect($icerikOn['genel_konular'])->where('dahil', true));
        $this->assertEmpty(collect($icerikOn['isyerine_ozgu']['maddeler'])->where('dahil', true));
        $sayfa->callAction('sadeceKaydet');
        $onlineKayit = EgitimKatilim::where('firma_id', $teh->id)->latest('id')->firstOrFail();
        $onlineHtml = view('pdf.egitim-katilim', [
            'kayit' => $onlineKayit->load('firma'),
            'firma' => $teh,
            'icerik' => $onlineKayit->konu_secimleri,
            'imzali' => true,
        ])->render();
        $this->assertStringContainsString('UZAKTAN (ONLİNE) EĞİTİM KATILIM FORMU', $onlineHtml);
        $this->assertStringContainsString('yüz yüze tamamlama oturumunda ayrı tutanak', $onlineHtml);

        // 3) Yüz Yüze Tamamlama Formu — Karma (4 saat, 1 gün, yalnız 4. blok dahil, 1-3 hariç)
        $sayfa->call('kayitUnut')
            ->set('egitimSekli', 'karma')
            ->assertSet('dersSaati', 4)
            ->assertSet('sureGun', 1);
        $this->assertNull($sayfa->instance()->sureYasalUyarisi);

        $icerikKarma = $sayfa->get('icerik');
        $this->assertEmpty(collect($icerikKarma['genel_konular'])->where('dahil', true));
        $this->assertNotEmpty(collect($icerikKarma['isyerine_ozgu']['maddeler'])->where('dahil', true));
        $this->assertSame(180, collect($icerikKarma['isyerine_ozgu']['maddeler'])->where('dahil', true)->sum('dakika'));

        $sayfa->callAction('sadeceKaydet');
        $karmaKayit = EgitimKatilim::where('firma_id', $teh->id)->latest('id')->firstOrFail();
        $karmaHtml = view('pdf.egitim-katilim', [
            'kayit' => $karmaKayit->load('firma'),
            'firma' => $teh,
            'icerik' => $karmaKayit->konu_secimleri,
            'imzali' => true,
        ])->render();
        $this->assertStringContainsString('YÜZ YÜZE TAMAMLAMA EĞİTİM KATILIM FORMU (KARMA)', $karmaHtml);
        $this->assertStringContainsString('uzaktan (Online / LMS) eğitim oturumunda tamamlanmıştır', $karmaHtml);

        // 4) Çok Tehlikeli ilk defa Karma = 8 saat (Sağlık S4 + Teknik T4/T5/T10/T12 + 4. Bölüm 4s)
        $cok = Firma::factory()->for($this->uzman)->create(['tehlike_sinifi' => 'cok_tehlikeli', 'nace_kodu' => '41.00.01']);
        $sayfaCok = Livewire::test(EgitimSayfasi::class)
            ->set('firmaId', $cok->id)
            ->set('egitimSekli', 'karma')
            ->assertSet('dersSaati', 8)
            ->assertSet('sureGun', 1);
        $this->assertNull($sayfaCok->instance()->sureYasalUyarisi);
        $this->assertCount(1, collect($sayfaCok->get('icerik')['saglik_konulari'])->where('dahil', true));
        $this->assertCount(4, collect($sayfaCok->get('icerik')['teknik_konular'])->where('dahil', true));
    }

    public function test_yillik_plan_ile_egitim_katilim_dorduncu_bolumu_birebir_senkron_calisir(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create(['tehlike_sinifi' => 'tehlikeli', 'nace_kodu' => '25.62.01']);
        $plan = YillikPlan::firmaYilIcin($firma, (int) now()->year);

        // Planda 4. bölümü özel 2 konuyla güncelle
        $egitimler = collect($plan->egitimler)->reject(fn ($e) => ($e['kategori'] ?? null) === 'ise_ozgu')->values()->all();
        $egitimler[] = ['konu' => 'CNC Torna Güvenliği', 'kategori' => 'ise_ozgu', 'hedef' => '', 'egitici' => 'İGU', 'hafta' => 2, 'aylar' => array_fill(0, 12, 'planlandi')];
        $egitimler[] = ['konu' => 'Taşlama Çapağı Koruma', 'kategori' => 'ise_ozgu', 'hedef' => '', 'egitici' => 'İGU', 'hafta' => 2, 'aylar' => array_fill(0, 12, 'planlandi')];
        $plan->update(['egitimler' => $egitimler]);

        // Eğitim Katılım Formu açıldığında birebir aynı 2 konu gelmeli
        $sayfa = Livewire::test(EgitimSayfasi::class)->set('firmaId', $firma->id);
        $formKonulari = collect($sayfa->get('icerik')['isyerine_ozgu']['maddeler'])->pluck('madde')->all();
        $this->assertSame(['CNC Torna Güvenliği', 'Taşlama Çapağı Koruma'], $formKonulari);

        // Uzaktan (online) form kaydedilse bile planın 4. bölümü silinmemeli
        $sayfa->set('egitimSekli', 'uzaktan')->callAction('sadeceKaydet');
        $planKonulari = collect($plan->fresh()->egitimler)->where('kategori', 'ise_ozgu')->pluck('konu')->values()->all();
        $this->assertSame(['CNC Torna Güvenliği', 'Taşlama Çapağı Koruma'], $planKonulari);
    }

    public function test_elle_gercek_is_grubu_secimi_ve_ayni_gruptaki_firmalara_esitleme_g_aylarini_korur(): void
    {
        // NACE'si ticaret (46.90) ama gerçekte mobilya işi yapan 2 firma + gerçekte metal işi yapan 3. firma
        $mob1 = Firma::factory()->for($this->uzman)->create(['unvan' => 'Mobilya Bir', 'nace_kodu' => '46.90.01', 'tehlike_sinifi' => 'tehlikeli']);
        $mob2 = Firma::factory()->for($this->uzman)->create(['unvan' => 'Mobilya İki', 'nace_kodu' => '46.90.02', 'tehlike_sinifi' => 'cok_tehlikeli']);
        $metal = Firma::factory()->for($this->uzman)->create(['unvan' => 'Metal Üç', 'nace_kodu' => '25.62.01', 'tehlike_sinifi' => 'tehlikeli']);

        // Her iki mobilya firmasına elle "nace_agac_mobilya" (Gerçek İş Grubu) ata
        IseOzguSenkron::firmaGrubuKaydet($mob1, 'nace_agac_mobilya');
        IseOzguSenkron::firmaGrubuKaydet($mob2, 'nace_agac_mobilya');

        $plan1 = YillikPlan::firmaYilIcin($mob1, 2026);
        $plan2 = YillikPlan::firmaYilIcin($mob2, 2026);
        $planMetal = YillikPlan::firmaYilIcin($metal, 2026);

        // Mobilya Bir'de 4. bölüm konularını özelleştir
        $e1 = collect($plan1->egitimler)->reject(fn ($e) => ($e['kategori'] ?? null) === 'ise_ozgu')->values()->all();
        $e1[] = ['konu' => 'Yatar Daire Testere Güvenliği', 'kategori' => 'ise_ozgu', 'hedef' => 'Bıçak koruyucu', 'egitici' => 'İGU', 'hafta' => 2, 'aylar' => array_fill(0, 12, 'planlandi')];
        $e1[] = ['konu' => 'Ahşap Tozu ve Vernik Riskleri', 'kategori' => 'ise_ozgu', 'hedef' => 'Maske', 'egitici' => 'İGU', 'hafta' => 2, 'aylar' => array_fill(0, 12, 'planlandi')];
        $plan1->update(['egitimler' => $e1]);

        // Mobilya İki'de Ocak ayı 'tamamlandi' (G) olan aynı konuyu yerleştir
        $aylarG = array_fill(0, 12, 'planlandi');
        $aylarG[0] = 'tamamlandi';
        $e2 = collect($plan2->egitimler)->reject(fn ($e) => ($e['kategori'] ?? null) === 'ise_ozgu')->values()->all();
        $e2[] = ['konu' => 'Yatar Daire Testere Güvenliği', 'kategori' => 'ise_ozgu', 'hedef' => '', 'egitici' => 'İGU', 'hafta' => 2, 'aylar' => $aylarG];
        $plan2->update(['egitimler' => $e2]);

        // Yıllık Eğitim Planı sayfasından "Aynı İş Grubundaki Firmalara Eşitle" çağır
        Livewire::test(YillikEgitimPlani::class)
            ->set('firmaId', $mob1->id)
            ->set('yil', 2026)
            ->assertSet('firmaEgitimGrubu', 'nace_agac_mobilya')
            ->call('dorduncuBolumuGrubaEsitle')
            ->assertNotified();

        $guncel2 = collect($plan2->fresh()->egitimler)->where('kategori', 'ise_ozgu')->values();
        $this->assertSame(['Yatar Daire Testere Güvenliği', 'Ahşap Tozu ve Vernik Riskleri'], $guncel2->pluck('konu')->all());
        // Mobilya İki'nin Ocak ayındaki G (tamamlandi) işareti korunmalı!
        $this->assertSame('tamamlandi', $guncel2->firstWhere('konu', 'Yatar Daire Testere Güvenliği')['aylar'][0]);

        // Metal firmasının 4. bölümü kesinlikle etkilenmemeli
        $this->assertNotContains('Yatar Daire Testere Güvenliği', collect($planMetal->fresh()->egitimler)->pluck('konu')->all());
    }

    public function test_kalip_ve_siva_ekibi_ayri_konu_gorev_filtresi_ve_yillik_plan_ekip_etiketi(): void
    {
        $insaat = Firma::factory()->for($this->uzman)->create([
            'unvan' => 'Anadolu Şantiye A.Ş.',
            'tehlike_sinifi' => 'cok_tehlikeli',
            'nace_kodu' => '41.00.01',
        ]);
        $kalipci = Calisan::factory()->for($insaat)->create(['ad_soyad' => 'Ali Kalıpçı', 'gorev' => 'Kalıpçı', 'sube' => 'Şantiye A']);
        $sivaci = Calisan::factory()->for($insaat)->create(['ad_soyad' => 'Veli Sıvacı', 'gorev' => 'Sıvacı', 'sube' => 'Şantiye A']);

        // 1) Kalıp Ekibi — Görev filtresiyle yalnız Kalıpçı seçilir
        $sayfa = Livewire::test(EgitimSayfasi::class)
            ->set('firmaId', $insaat->id)
            ->call('isKoluToggle', 'kalip_iskele_demir')
            ->set('gorevFiltre', 'Kalıpçı')
            ->call('filtredekileriSec')
            ->callAction('sadeceKaydet');

        $kalipKayit = EgitimKatilim::where('firma_id', $insaat->id)->latest('id')->firstOrFail();
        $this->assertSame([$kalipci->ad_soyad], array_column($kalipKayit->katilimcilar, 'ad_soyad'));
        $kalipKonular = collect($kalipKayit->konu_secimleri['isyerine_ozgu']['maddeler'])->pluck('madde')->implode(' | ');
        $this->assertStringContainsString('kalıp', mb_strtolower($kalipKonular));
        $this->assertStringNotContainsString('sıva', mb_strtolower($kalipKonular));

        // 2) Sıva Ekibi — Görev filtresiyle yalnız Sıvacı seçilir, kalıp konuları karışmaz
        $sayfa->call('kayitUnut')
            ->call('isKoluToggle', 'kalip_iskele_demir')
            ->call('isKoluToggle', 'siva_boya')
            ->set('gorevFiltre', 'Sıvacı')
            ->call('filtredekileriSec')
            ->callAction('sadeceKaydet');

        $sivaKayit = EgitimKatilim::where('firma_id', $insaat->id)->latest('id')->firstOrFail();
        $this->assertSame([$sivaci->ad_soyad], array_column($sivaKayit->katilimcilar, 'ad_soyad'));
        $sivaKonular = collect($sivaKayit->konu_secimleri['isyerine_ozgu']['maddeler'])->pluck('madde')->implode(' | ');
        $this->assertStringContainsString('sıva', mb_strtolower($sivaKonular));
        $this->assertStringNotContainsString('kalıp', mb_strtolower($sivaKonular));

        // 3) Her iki ekip de Yıllık Eğitim Planı 4. bölümüne kendi hedef_kitle etiketiyle yazılır ve Excel'e [EKİP: ...] basılır
        $plan = YillikPlan::where('firma_id', $insaat->id)->where('yil', now()->year)->firstOrFail();
        $ozgu = collect($plan->egitimler)->where('kategori', 'ise_ozgu');
        $this->assertTrue($ozgu->contains(fn ($e) => filled($e['hedef_kitle'] ?? null) && str_contains((string) $e['hedef_kitle'], 'Kalıp')));
        $this->assertTrue($ozgu->contains(fn ($e) => filled($e['hedef_kitle'] ?? null) && str_contains((string) $e['hedef_kitle'], 'Sıva')));

        $excel = YillikPlanExcelUretici::egitimDoldur($plan)->getSheet(0);
        $cSutunu = collect(range(27, 40))->map(fn ($r) => (string) $excel->getCell("C{$r}")->getValue())->implode("\n");
        $this->assertStringContainsString('[EKİP:', $cSutunu);
    }

    public function test_konu_eklendikce_ve_cikarildikca_egitim_katilim_ve_yillik_plan_sureleri_otomatik_guncellenir(): void
    {
        $teh = Firma::factory()->for($this->uzman)->create(['tehlike_sinifi' => 'tehlikeli', 'nace_kodu' => '25.62.01']);

        // 1) Eğitim Katılım Karma (Tehlikeli 4 saat = 180 dk): konu ekledikçe ve çıkardıkça toplam HEP 180 dk kalır!
        $sayfa = Livewire::test(EgitimSayfasi::class)
            ->set('firmaId', $teh->id)
            ->set('egitimSekli', 'karma')
            ->assertSet('dersSaati', 4);

        $baslangicAdet = count($sayfa->get('icerik')['isyerine_ozgu']['maddeler']);
        $this->assertSame(180, collect($sayfa->get('icerik')['isyerine_ozgu']['maddeler'])->where('dahil', true)->sum('dakika'));

        // +1 yeni konu ekle → dakika toplamı hâlâ tam 180 dk!
        $sayfa->call('isyerineOzguMaddeEkle');
        $maddeler = $sayfa->get('icerik')['isyerine_ozgu']['maddeler'];
        $this->assertCount($baslangicAdet + 1, $maddeler);
        $this->assertSame(180, collect($maddeler)->where('dahil', true)->sum('dakika'));

        // 1 konuyu hariç bırak (dahil = false) → kalan aktif konuların toplamı hâlâ tam 180 dk!
        $sayfa->set('icerik.isyerine_ozgu.maddeler.0.dahil', false);
        $this->assertSame(180, collect($sayfa->get('icerik')['isyerine_ozgu']['maddeler'])->where('dahil', true)->sum('dakika'));

        // Konuyu sil → kalan aktif konuların toplamı hâlâ tam 180 dk!
        $sayfa->call('isyerineOzguMaddeCikar', 0);
        $this->assertSame(180, collect($sayfa->get('icerik')['isyerine_ozgu']['maddeler'])->where('dahil', true)->sum('dakika'));

        // 2) Yıllık Eğitim Planı: 4. bölüme yeni konu eklenince ve silinince 4. bölüm toplamı HEP 4.0 saat kalır!
        $planSayfa = Livewire::test(YillikEgitimPlani::class)
            ->set('firmaId', $teh->id)
            ->set('yil', 2026)
            ->set('yeniEgitimKategori', 'ise_ozgu')
            ->set('yeniEgitimKonu', 'Lazer Kesim Tezgâhı Güvenliği')
            ->call('egitimEkle');

        $plan = YillikPlan::where('firma_id', $teh->id)->where('yil', 2026)->firstOrFail();
        $ozguSatirlar = collect($plan->egitimler)->where('kategori', 'ise_ozgu');
        $this->assertEqualsWithDelta(4.0, (float) $ozguSatirlar->sum('yuz_yuze_saat'), 0.01);
        $this->assertEqualsWithDelta(4.0, (float) $ozguSatirlar->sum('sure_saat'), 0.01);

        $sonIdx = collect($plan->egitimler)->search(fn ($e) => ($e['konu'] ?? null) === 'Lazer Kesim Tezgâhı Güvenliği');
        $planSayfa->call('egitimSil', (int) $sonIdx);
        $ozguSonra = collect($plan->fresh()->egitimler)->where('kategori', 'ise_ozgu');
        $this->assertEqualsWithDelta(4.0, (float) $ozguSonra->sum('yuz_yuze_saat'), 0.01);
    }

    public function test_gemini_api_tum_sektorler_icin_ise_ozgu_konu_uretir_kutuphaneye_yazar_ve_sureleri_dagitir(): void
    {
        config(['services.gemini.key' => 'test-key-123']);

        // Tüm 21 sektörün NACE kapsamı tanımlı olmalı
        $sektorler = GeminiIseOzguEgitimUretici::sektorSecenekleri();
        $this->assertGreaterThanOrEqual(20, count($sektorler));
        foreach (array_keys($sektorler) as $anahtar) {
            $this->assertNotNull(GeminiIseOzguEgitimUretici::sektorNaceOnekleri($anahtar), $anahtar.' için NACE ön eki olmalı');
        }

        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [[
                    'content' => [
                        'parts' => [[
                            'text' => json_encode([
                                ['ad' => 'CNC Torna Ayna ve Talaş Koruma', 'hedef' => 'Dönen parçalardan korunma ve kapak kilidi kullanımı.', 'egitici' => 'İŞ GÜVENLİĞİ UZMANI'],
                                ['ad' => 'Bor Yağı ve Kesme Sıvısı Hijyeni', 'hedef' => 'Cilt dermatiti ve aerosol maruziyetini önleme.', 'egitici' => 'İŞ GÜVENLİĞİ UZMANI / İŞYERİ HEKİMİ'],
                                ['ad' => 'Köprülü Vinç Sapanlama Kuralları', 'hedef' => 'Yük dengesi ve sapan kontrolü.', 'egitici' => 'İŞ GÜVENLİĞİ UZMANI'],
                            ], JSON_UNESCAPED_UNICODE),
                        ]],
                    ],
                ]],
            ], 200),
        ]);

        // 1) Kütüphane ekranından çoklu sektör API üretimi
        Livewire::test(IseOzguEgitimKonulari::class)
            ->callAction('aiSektorKonulariUret', [
                'kapsam' => 'secili',
                'sektorler' => ['nace_metal', 'nace_gida'],
                'adet' => 3,
            ])
            ->assertNotified();

        $this->assertGreaterThanOrEqual(3, IseOzguEgitimKonusu::where('user_id', $this->uzman->id)->count());

        // 2) Eğitim Katılım ekranında AI ile konu üret → eklenen konular 180 dk (4s) toplamına tam dağıtılır!
        $metal = Firma::factory()->for($this->uzman)->create(['tehlike_sinifi' => 'tehlikeli', 'nace_kodu' => '25.62.01']);
        $sayfa = Livewire::test(EgitimSayfasi::class)
            ->set('firmaId', $metal->id)
            ->set('egitimSekli', 'karma')
            ->callAction('aiIseOzguKonuUret', [
                'sektor' => 'nace_metal',
                'adet' => 3,
                'ekleme_sekli' => 'degistir',
                'kutuphaneye_kaydet' => false,
            ])
            ->assertNotified();

        $maddeler = $sayfa->get('icerik')['isyerine_ozgu']['maddeler'];
        $this->assertCount(3, $maddeler);
        $this->assertSame(180, collect($maddeler)->where('dahil', true)->sum('dakika'));
        $this->assertSame([60, 60, 60], collect($maddeler)->pluck('dakika')->all());

        // 3) Yıllık Eğitim Planı ekranında AI ile 4. bölüm üret → 4. bölüm toplamı tam 4.0 saat dağıtılır!
        Livewire::test(YillikEgitimPlani::class)
            ->set('firmaId', $metal->id)
            ->set('yil', 2026)
            ->callAction('aiIseOzguUret', [
                'sektor' => 'nace_metal',
                'adet' => 3,
                'ekleme_sekli' => 'degistir',
                'kutuphaneye_kaydet' => false,
            ])
            ->assertNotified();

        $plan = YillikPlan::where('firma_id', $metal->id)->where('yil', 2026)->firstOrFail();
        $ozgu = collect($plan->egitimler)->where('kategori', 'ise_ozgu');
        $this->assertCount(3, $ozgu);
        $this->assertEqualsWithDelta(4.0, (float) $ozgu->sum('yuz_yuze_saat'), 0.01);
    }

    public function test_kutuphaneden_silinen_ai_konusu_yillik_plandan_da_silinir_ve_toplu_ai_planlama_katilima_aktarilir(): void
    {
        config(['services.gemini.key' => 'test-key-456']);

        $f1 = Firma::factory()->for($this->uzman)->create(['unvan' => 'Metal A', 'tehlike_sinifi' => 'tehlikeli', 'nace_kodu' => '25.62.01']);
        $f2 = Firma::factory()->for($this->uzman)->create(['unvan' => 'Metal B', 'tehlike_sinifi' => 'cok_tehlikeli', 'nace_kodu' => '25.62.02']);

        // 1) Kütüphaneye özel bir konu ekleyip yıllık plana yerleştir, sonra kütüphaneden sil → yıllık plandan da silinmeli!
        $ozel = IseOzguEgitimKonusu::create([
            'user_id' => $this->uzman->id,
            'ad' => 'Silinecek Eski AI Konusu',
            'nace_onekleri' => ',25,',
        ]);
        $plan1 = YillikPlan::firmaYilIcin($f1, (int) now()->year);
        $this->assertContains('Silinecek Eski AI Konusu', collect($plan1->egitimler)->pluck('konu')->all());

        Livewire::test(IseOzguEgitimKonulari::class)
            ->call('kaldir', 'ozel_'.$ozel->id)
            ->assertNotified();

        $this->assertNotContains('Silinecek Eski AI Konusu', collect($plan1->fresh()->egitimler)->pluck('konu')->all());
        $this->assertEqualsWithDelta(4.0, (float) collect($plan1->fresh()->egitimler)->where('kategori', 'ise_ozgu')->sum('yuz_yuze_saat'), 0.01);

        // 2) AI ile Tüm Firmaları Planla: aynı iş grubundaki Metal A (Tehlikeli) & Metal B (Çok Tehlikeli) tek API çağrısıyla planlanır
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [[
                    'content' => [
                        'parts' => [[
                            'text' => json_encode([
                                ['ad' => 'Giyotin Makas ve Abkant Güvenliği', 'hedef' => 'Işık perdesi ve çift el kumanda.', 'egitici' => 'İŞ GÜVENLİĞİ UZMANI'],
                                ['ad' => 'Gazaltı Kaynak Dumanı Kontrolü', 'hedef' => 'Lokal emiş ve maske kullanımı.', 'egitici' => 'İŞ GÜVENLİĞİ UZMANI'],
                                ['ad' => 'Tavan Vinci Kaldırma Aksesuarları', 'hedef' => 'Sapan ve kanca kontrolü.', 'egitici' => 'İŞ GÜVENLİĞİ UZMANI'],
                                ['ad' => 'Taşlama ve Çapak Sıçraması Önlemleri', 'hedef' => 'Siperlik ve devir kontrolü.', 'egitici' => 'İŞ GÜVENLİĞİ UZMANI'],
                            ], JSON_UNESCAPED_UNICODE),
                        ]],
                    ],
                ]],
            ], 200),
        ]);

        Livewire::test(YillikEgitimPlani::class)
            ->set('firmaId', $f1->id)
            ->set('yil', (int) now()->year)
            ->callAction('aiTopluFirmaPlanla', [
                'kapsam' => 'tumu',
                'adet' => 4,
                'mod' => 'karma_lms',
            ])
            ->assertNotified();

        // Aynı iş grubu olduğu için 2 firmaya rağmen yalnız 1 API çağrısı yapılmış olmalı
        Http::assertSentCount(1);

        // Eğitim Katılım açıldığında Metal A'da Yıllık Plandaki tam 4 konu ve tam 540 dk (12 saat = 90+90+180+180) gelmeli!
        $sayfa = Livewire::test(EgitimSayfasi::class)->set('firmaId', $f1->id);
        $icerik = $sayfa->get('icerik');
        $ozguAdlar = collect($icerik['isyerine_ozgu']['maddeler'])->pluck('madde')->all();
        $this->assertSame([
            'Giyotin Makas ve Abkant Güvenliği',
            'Gazaltı Kaynak Dumanı Kontrolü',
            'Tavan Vinci Kaldırma Aksesuarları',
            'Taşlama ve Çapak Sıçraması Önlemleri',
        ], $ozguAdlar);
        $toplamFiili = collect($icerik['genel_konular'])->sum('dakika')
            + collect($icerik['saglik_konulari'])->sum('dakika')
            + collect($icerik['teknik_konular'])->sum('dakika')
            + collect($icerik['isyerine_ozgu']['maddeler'])->sum('dakika');
        $this->assertSame(540, $toplamFiili, 'Tehlikeli 12 saat = tam 540 dk fiili olmalı (eski/yeni karışmamalı)');
        $this->assertSame(180, collect($icerik['isyerine_ozgu']['maddeler'])->sum('dakika'));

        // Ekrandan 1 konu silindiğinde toplam hâlâ tam 180 dk kalır (60+60+60) ve silinen konu geri gelmez!
        $sayfa->call('isyerineOzguMaddeCikar', 0);
        $sonra = $sayfa->get('icerik')['isyerine_ozgu']['maddeler'];
        $this->assertCount(3, $sonra);
        $this->assertSame([60, 60, 60], collect($sonra)->pluck('dakika')->all());
    }

    public function test_bos_egitim_katilim_pdfinde_isveren_yerine_isyeri_hekimi_imza_alani_yer_alir(): void
    {
        $igu = \App\Models\IsgProfesyoneli::create([
            'user_id' => $this->uzman->id,
            'tip' => 'igu',
            'ad_soyad' => 'Mehmet Uzman',
            'sertifika_no' => 'IGU-123',
        ]);
        $hekim = \App\Models\IsgProfesyoneli::create([
            'user_id' => $this->uzman->id,
            'tip' => 'isyeri_hekimi',
            'ad_soyad' => 'Dr. Ayşe Hekim',
            'sertifika_no' => 'IH-456',
        ]);
        $firma = Firma::factory()->for($this->uzman)->create([
            'igu_id' => $igu->id,
            'isyeri_hekimi_id' => $hekim->id,
            'isveren_ad' => 'Patron Bey',
        ]);

        $icerik = \App\Support\EgitimIcerikOlusturucu::olustur('genel', 'insaat', 'tehlikeli');
        $bosKayit = new EgitimKatilim([
            'baslik_anahtari' => 'genel',
            'egitim_turu' => 'ilk',
            'egitim_sekli' => 'yuz_yuze',
            'sure_gun' => 2,
            'isg_uzmani_var' => true,
            'isg_uzmani_adi' => $igu->ad_soyad,
            'isyeri_hekimi_var' => true,
            'isyeri_hekimi_adi' => $hekim->ad_soyad,
            'katilimcilar' => [],
        ]);
        $bosKayit->belge_no = 'BOŞ FORM';

        $html = view('pdf.egitim-katilim', [
            'kayit' => $bosKayit,
            'firma' => $firma->load(['igu', 'isyeriHekimi']),
            'icerik' => $icerik,
            'imzali' => true,
        ])->render();

        $this->assertStringContainsString('Eğitimi Veren — İş Güvenliği Uzmanı', $html);
        $this->assertStringContainsString('Mehmet Uzman', $html);
        $this->assertStringContainsString('Eğitimi Veren — İşyeri Hekimi', $html);
        $this->assertStringContainsString('Dr. Ayşe Hekim', $html);
        $this->assertStringNotContainsString('İşveren / İşveren Vekili', $html);

        // bosFormPdf gerçek PDF çıktısını sorunsuz üretmeli
        $yanit = \App\Support\EgitimKatilimUretici::bosFormPdf('genel', 'insaat', 'tehlikeli', 'ilk', $firma, $icerik);
        ob_start();
        $yanit->sendContent();
        $this->assertStringStartsWith('%PDF', ob_get_clean());
    }
}
