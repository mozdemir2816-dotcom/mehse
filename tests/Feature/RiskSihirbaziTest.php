<?php

namespace Tests\Feature;

use App\Filament\Pages\RiskSihirbazi;
use App\Models\Firma;
use App\Models\RiskDegerlendirmesi;
use App\Models\Tehlike;
use App\Models\RiskSablonu;
use App\Models\User;
use App\Support\RiskUretici;
use Database\Seeders\TehlikeKutuphanesiSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;

class RiskSihirbaziTest extends TestCase
{
    use RefreshDatabase;

    private User $uzman;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(TehlikeKutuphanesiSeeder::class);
        $this->uzman = User::factory()->create();
        $this->actingAs($this->uzman);
    }

    public function test_sayfa_acilir_ve_ilk_adimda_baslar(): void
    {
        Livewire::test(RiskSihirbazi::class)
            ->assertOk()
            ->assertSet('adim', 1)
            ->assertSet('raporTarihi', now()->toDateString());
    }

    public function test_gecerlilik_tarihi_tehlike_sinifina_gore_hesaplanir(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create(['tehlike_sinifi' => 'cok_tehlikeli']);

        Livewire::test(RiskSihirbazi::class)
            ->set('raporTarihi', '2026-01-01')
            ->set('firmaId', $firma->id)
            ->assertSet('gecerlilikTarihi', '2028-01-01'); // +2 yıl
    }

    public function test_firma_ve_yontem_secilmeden_ilerlenemez(): void
    {
        Livewire::test(RiskSihirbazi::class)
            ->call('ileri')
            ->assertSet('adim', 1)                  // firma yok
            ->set('firmaId', Firma::factory()->for($this->uzman)->create()->id)
            ->call('ileri')
            ->assertSet('adim', 2)
            ->call('yontemSec', 'kayitli')           // hazır değil
            ->assertSet('yontemSecim', null)
            ->call('yontemSec', 'manuel')
            ->assertSet('yontemSecim', 'manuel')
            ->call('ileri')
            ->assertSet('adim', 3);
    }

    public function test_kutuphaneden_tehlike_eklenir_ve_mukerrer_engellenir(): void
    {
        $tehlike = Tehlike::first();

        $component = Livewire::test(RiskSihirbazi::class)
            ->call('tehlikeEkle', $tehlike->id)
            ->call('tehlikeEkle', $tehlike->id);

        $this->assertCount(1, $component->get('secilenler'));
        $this->assertSame($tehlike->tehlike, $component->get('secilenler.0.tehlike'));
    }

    public function test_kategori_tumunu_ekle(): void
    {
        $kategori = Tehlike::first()->kategori;

        $component = Livewire::test(RiskSihirbazi::class)
            ->call('kategoriTumunuEkle', $kategori->id);

        $this->assertCount($kategori->tehlikeler()->count(), $component->get('secilenler'));
    }

    public function test_puansiz_madde_ile_6_adima_gecilemez(): void
    {
        Livewire::test(RiskSihirbazi::class)
            ->call('tehlikeEkle', Tehlike::first()->id)
            ->set('adim', 5)
            ->call('ileri')
            ->assertSet('adim', 5); // puan eksik
    }

    public function test_uctan_uca_kayit_olusturur(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create(['tehlike_sinifi' => 'tehlikeli']);
        $t1 = Tehlike::all()->get(0);
        $t2 = Tehlike::all()->get(1);

        Livewire::test(RiskSihirbazi::class)
            ->set('firmaId', $firma->id)
            ->set('yontem', 'matris_5x5')
            ->call('ileri')                        // 1 -> 2
            ->call('yontemSec', 'manuel')
            ->call('ileri')                        // 2 -> 3
            ->call('tehlikeEkle', $t1->id)
            ->call('tehlikeEkle', $t2->id)
            ->call('ileri')                        // 3 -> 4
            ->set('varsayilanTermin', 'Sürekli')
            ->call('ileri')                        // 4 -> 5
            ->set('secilenler.0.olasilik', '3')
            ->set('secilenler.0.siddet', '5')
            ->set('secilenler.1.olasilik', '2')
            ->set('secilenler.1.siddet', '2')
            ->call('ileri')                        // 5 -> 6
            ->assertSet('adim', 6)
            ->call('kaydet')
            ->assertRedirect();

        $rd = RiskDegerlendirmesi::firstOrFail();
        $this->assertSame($firma->id, $rd->firma_id);
        $this->assertSame('matris_5x5', $rd->yontem);
        $this->assertCount(2, $rd->maddeler);

        $ilk = $rd->maddeler()->orderBy('sira')->first();
        $this->assertSame(15.0, $ilk->puan);
        $this->assertSame('Yüksek Risk', $ilk->duzey);
        $this->assertSame('Sürekli', $ilk->termin);
    }

    /*
    |--------------------------------------------------------------------------
    | Yapay Zeka (kural tabanlı) akışı — isgpratik 103-115
    |--------------------------------------------------------------------------
    */

    public function test_ai_yontemi_sektor_ve_alt_kategori_akisi(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();

        Livewire::test(RiskSihirbazi::class)
            ->set('firmaId', $firma->id)
            ->call('ileri')
            ->call('yontemSec', 'ai')
            ->assertSet('yontemSecim', 'ai')
            ->call('ileri')
            ->assertSet('adim', 3)
            ->call('aiBaslat')
            ->assertSet('aiAsama', 'sektor')
            ->call('aiSektorSec', 'insaat')
            ->call('aiSektorOnayla')
            ->assertSet('aiAsama', 'altkategori')
            ->call('aiAltKategoriToggle', 0)
            ->call('aiAltKategoriOnayla')
            ->assertSet('aiAsama', 'sohbet');
    }

    public function test_ai_sohbet_cevaplari_aday_risk_uretir_ve_maddelere_eklenir(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();

        $component = Livewire::test(RiskSihirbazi::class)
            ->set('firmaId', $firma->id)
            ->call('ileri')->call('yontemSec', 'ai')->call('ileri')
            ->call('aiBaslat')
            ->call('aiSektorSec', 'ofis')
            ->call('aiSektorOnayla')
            ->call('aiAltKategoriOnayla');

        // Soruları cevapla — bazı cevaplar eksiklik → risk tetikler
        $cevaplar = [
            'calisan_sayisi' => 'mikro',
            'vardiya' => 'uc',              // gece vardiyası riski
            'tatbikat' => 'hic',            // tatbikat riski
            'yangin_altyapi' => 'yok',      // yangın altyapı riski
            'elektrik' => 'tam',
            'havalandirma' => 'yetersiz',   // havalandırma riski
            'kaza_gecmisi' => 'yok',
            'psikososyal' => 'yonetiliyor',
        ];

        foreach ($cevaplar as $anahtar => $deger) {
            $component->call('aiCevapla', $anahtar, $deger, false);
        }
        // çoklu soru: egitimler → "hicbiri"
        $component->call('aiCevapla', 'egitimler', 'hicbiri', true)
            ->call('aiCokluGonder', 'egitimler');

        $component->assertSet('aiAsama', 'sonuc');
        $this->assertNotEmpty($component->get('aiAdaylar'));

        // "ai" kaynaklı adaylar varsayılan seçili
        $secili = $component->get('aiSecilenAdaylar');
        $this->assertNotEmpty($secili);

        $component->call('aiAdaylariEkle')
            ->assertSet('adim', 4);

        $this->assertNotEmpty($component->get('secilenler'));
        // gece vardiyası riski eklendi mi?
        $tehlikeler = collect($component->get('secilenler'))->pluck('tehlike')->implode(' | ');
        $this->assertStringContainsString('Gece çalışması', $tehlikeler);
    }

    public function test_risk_uretici_eksik_egitim_riskini_tetikler(): void
    {
        $adaylar = RiskUretici::uret('ofis', [], ['egitimler' => ['hicbiri']]);

        $this->assertTrue(
            collect($adaylar)->contains(fn ($a) => str_contains($a['tehlike'], 'eğitimi verilmemiş')),
        );
        // Kütüphane baz riskleri de gelir (genel_isyeri)
        $this->assertTrue(collect($adaylar)->contains('kaynak', 'kutuphane'));
    }

    public function test_risk_uretici_gemini_aktifse_llm_onerilerini_ekler(): void
    {
        config(['services.gemini.key' => 'test-anahtar']);

        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [['content' => ['parts' => [['text' => json_encode([
                    ['tehlike' => 'Çağrı merkezinde sesli tehdit ve psikososyal risk', 'risk' => 'Tükenmişlik',
                        'oneri' => 'Psikososyal risk değerlendirmesi yapılır.', 'mevzuat' => '6331 SK', 'olasilik' => 2, 'siddet' => 2],
                ])]]]]],
            ]),
        ]);

        $adaylar = RiskUretici::uret('ofis', ['Çağrı merkezi'], []);

        $this->assertTrue(collect($adaylar)->contains(
            fn ($a) => $a['kaynak'] === 'llm' && str_contains($a['tehlike'], 'psikososyal risk'),
        ));
    }

    public function test_ai_soru_atlanabilir(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();

        Livewire::test(RiskSihirbazi::class)
            ->set('firmaId', $firma->id)
            ->call('ileri')->call('yontemSec', 'ai')->call('ileri')
            ->call('aiBaslat')->call('aiSektorSec', 'ofis')->call('aiSektorOnayla')->call('aiAltKategoriOnayla')
            ->call('aiSoruAtla', 'calisan_sayisi')
            ->assertSet('aiAtlananlar', ['calisan_sayisi']);
    }

    public function test_excel_yontemi_dosyayi_okur_ve_secilenlere_ekler(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();

        $kitap = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $kitap->getActiveSheet()->fromArray([
            ['Bölüm', 'Tehlike', 'Risk', 'Olasılık', 'Şiddet'],
            ['Şantiye', 'Korkuluksuz kenar', 'Yüksekten düşme', 4, 5],
            ['Şantiye', 'İksasız kazı', 'Göçük', 3, 5],
        ], null, 'A1');
        $yol = tempnam(sys_get_temp_dir(), 'xlsx').'.xlsx';
        (new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($kitap))->save($yol);

        Livewire::test(RiskSihirbazi::class)
            ->set('firmaId', $firma->id)
            ->call('ileri')->call('yontemSec', 'excel')->call('ileri')
            ->set('excelDosya', \Illuminate\Http\UploadedFile::fake()->createWithContent('riskler.xlsx', file_get_contents($yol)))
            ->call('excelIceAktar')
            ->assertSet('excelAdaylar', fn ($adaylar) => count($adaylar) === 2)
            ->call('excelSecilenleriEkle')
            ->assertSet('secilenler', fn ($secilenler) => count($secilenler) === 2)
            ->assertSet('adim', 4);

        unlink($yol);
    }

    /*
    |--------------------------------------------------------------------------
    | Sektörel şablonlar (toplu ekleme + tekrar kullanım)
    |--------------------------------------------------------------------------
    */

    public function test_adim_6_secili_riskler_sektor_sablonu_olarak_kaydedilir(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();

        Livewire::test(RiskSihirbazi::class)
            ->set('firmaId', $firma->id)
            ->call('ileri')->call('yontemSec', 'manuel')->call('ileri')
            ->call('tehlikeEkle', Tehlike::all()->get(0)->id)
            ->call('tehlikeEkle', Tehlike::all()->get(1)->id)
            ->set('adim', 6)
            ->set('sablonAd', 'Küçük ofis seti')
            ->set('sablonSektor', 'ofis')
            ->call('sablonlaKaydet');

        $sablon = RiskSablonu::firstOrFail();
        $this->assertSame($this->uzman->id, $sablon->user_id);
        $this->assertSame('ofis', $sablon->sektor);
        $this->assertSame('Ofis / Hizmet / Finans', $sablon->sektorEtiketi());
        $this->assertCount(2, $sablon->maddeler);
    }

    public function test_sablon_yontemi_sektore_gore_gruplu_ve_uygulanabilir(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();
        $sablon = RiskSablonu::olustur($this->uzman, 'İnşaat temel', 'insaat', null, 'matris_5x5', [
            ['anahtar' => 'x1', 'tehlike' => 'Yüksekten düşme', 'bolum' => 'Saha', 'faaliyet' => 'Kalıp', 'olasilik' => 3, 'siddet' => 5],
            ['anahtar' => 'x2', 'tehlike' => 'Malzeme düşmesi', 'bolum' => 'Saha', 'faaliyet' => 'Kaldırma', 'olasilik' => 3, 'siddet' => 4],
        ]);

        $component = Livewire::test(RiskSihirbazi::class)
            ->set('firmaId', $firma->id)
            ->call('ileri')->call('yontemSec', 'sablon')->call('ileri')
            ->assertSet('adim', 3);

        $gruplar = $component->instance()->sablonlar();
        $this->assertArrayHasKey('İnşaat / Yapı', $gruplar->toArray());

        $component->call('sablonUygula', $sablon->id)
            ->assertSet('adim', 4);

        $this->assertCount(2, $component->get('secilenler'));
        $this->assertSame(1, $sablon->fresh()->kullanim_sayisi);
    }

    public function test_ai_akisinda_ayni_sektorun_sablonu_kisayoldan_uygulanir(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();
        $sablon = RiskSablonu::olustur($this->uzman, 'Ofis hazır', 'ofis', null, 'matris_5x5', [
            ['anahtar' => 'o1', 'tehlike' => 'Ekranlı çalışma zorlanması', 'olasilik' => 3, 'siddet' => 2],
        ]);

        $component = Livewire::test(RiskSihirbazi::class)
            ->set('firmaId', $firma->id)
            ->call('ileri')->call('yontemSec', 'ai')->call('ileri')
            ->call('aiBaslat')
            ->call('aiSektorSec', 'ofis');

        // sektör seçilince kısayol şablonları görünür
        $this->assertCount(1, $component->instance()->aiSektorSablonlari());

        $component->call('aiSablonKullan', $sablon->id)
            ->assertSet('adim', 4);

        $this->assertCount(1, $component->get('secilenler'));
    }

    public function test_sablon_resource_sayfalari_acilir(): void
    {
        $sablon = RiskSablonu::olustur($this->uzman, 'Test', 'ofis', null, 'matris_5x5', [['anahtar' => 'a', 'tehlike' => 'x']]);

        Livewire::test(\App\Filament\Resources\RiskSablonus\Pages\ListRiskSablonus::class)
            ->assertOk()
            ->assertCanSeeTableRecords([$sablon]);

        Livewire::test(\App\Filament\Resources\RiskSablonus\Pages\EditRiskSablonu::class, ['record' => $sablon->getRouteKey()])
            ->assertOk();
    }

    public function test_paylasilan_sablon_baska_uzmanda_gorunur_kendi_olmayan_silinemez(): void
    {
        $baskasi = User::factory()->create();
        $paylasik = RiskSablonu::olustur($baskasi, 'Paylaşık', 'depo', null, 'matris_5x5', [
            ['anahtar' => 'd1', 'tehlike' => 'Raf devrilmesi', 'olasilik' => 2, 'siddet' => 4],
        ]);
        $paylasik->update(['paylasildi' => true]);

        $ozel = RiskSablonu::olustur($baskasi, 'Özel', 'depo', null, 'matris_5x5', [['anahtar' => 'z', 'tehlike' => 'x']]);

        $gorunur = RiskSablonu::gorunur($this->uzman->id)->pluck('id');
        $this->assertTrue($gorunur->contains($paylasik->id));
        $this->assertFalse($gorunur->contains($ozel->id));
    }

    public function test_baska_uzmanin_firmasi_listede_gorunmez(): void
    {
        $benim = Firma::factory()->for($this->uzman)->create();
        $baskasi = Firma::factory()->create();

        $firmalar = Livewire::test(RiskSihirbazi::class)->instance()->firmalar();

        $this->assertArrayHasKey($benim->id, $firmalar);
        $this->assertArrayNotHasKey($baskasi->id, $firmalar);
    }
}
