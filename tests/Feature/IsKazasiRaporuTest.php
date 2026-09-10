<?php

namespace Tests\Feature;

use App\Filament\Pages\IsKazasiRaporu as KazaSayfasi;
use App\Models\Calisan;
use App\Models\Firma;
use App\Models\IsgProfesyoneli;
use App\Models\IsKazasiRaporu;
use App\Models\User;
use App\Support\IsKazasiRaporuUretici;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Tests\TestCase;

class IsKazasiRaporuTest extends TestCase
{
    use RefreshDatabase;

    private User $uzman;

    protected function setUp(): void
    {
        parent::setUp();
        $this->uzman = User::factory()->create();
        $this->actingAs($this->uzman);
    }

    public function test_firma_calisanindan_hizli_secim_alanlari_doldurur(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();
        $c = Calisan::factory()->for($firma)->create(['ad_soyad' => 'Ahmet Yılmaz', 'tc' => '12345678901', 'gorev' => 'Operatör']);

        $component = Livewire::test(KazaSayfasi::class)
            ->set('firmaId', $firma->id)
            ->set('kazazedeHizliSecId', $c->id);

        $this->assertSame('Ahmet Yılmaz', $component->get('kazazedeAdSoyad'));
        $this->assertSame('12345678901', $component->get('kazazedeTc'));
        $this->assertSame('Operatör', $component->get('kazazedeGorev'));
    }

    public function test_firma_secilince_rapor_hazirlayan_otomatik_dolar(): void
    {
        $igu = IsgProfesyoneli::factory()->for($this->uzman)->create(['ad_soyad' => 'İGU Ayşe']);
        $firma = Firma::factory()->for($this->uzman)->create(['igu_id' => $igu->id]);

        $component = Livewire::test(KazaSayfasi::class)->set('firmaId', $firma->id);

        $this->assertSame('İGU Ayşe', $component->get('raporHazirlayan'));
    }

    public function test_tanik_eklenir_ve_silinir(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();

        $component = Livewire::test(KazaSayfasi::class)
            ->set('firmaId', $firma->id)
            ->set('yeniTanikAd', 'Mehmet Demir')
            ->set('yeniTanikGorev', 'Şantiye Şefi')
            ->call('tanikEkle');

        $this->assertCount(1, $component->get('taniklar'));

        $component->call('tanikSil', 0);
        $this->assertCount(0, $component->get('taniklar'));
    }

    public function test_kazazede_veya_tanim_olmadan_kaydedilemez(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();

        Livewire::test(KazaSayfasi::class)
            ->set('firmaId', $firma->id)
            ->callAction('raporuTamamla');

        $this->assertDatabaseCount('is_kazasi_raporlari', 0);
    }

    public function test_pdf_aksiyonu_kayit_olusturur_ve_kase_snapshotlanir(): void
    {
        $igu = IsgProfesyoneli::factory()->for($this->uzman)->create(['kase_gorseli' => 'isg-profesyonel-kase/x.png']);
        $firma = Firma::factory()->for($this->uzman)->create(['igu_id' => $igu->id]);

        Livewire::test(KazaSayfasi::class)
            ->set('firmaId', $firma->id)
            ->set('kazazedeAdSoyad', 'Ahmet Yılmaz')
            ->set('kazaTanimi', 'Merdivenden düşme.')
            ->set('kazaTuru', 'dusme')
            ->set('agirlikDerecesi', 'yarali')
            ->set('kokNedenKategorileri', ['kkd_kullanilmamasi'])
            ->set('sgkBildirimiYapildi', true)
            ->set('sgkBildirimTarihi', '2026-09-05')
            ->callAction('raporuTamamla');

        $r = IsKazasiRaporu::where('firma_id', $firma->id)->firstOrFail();
        $this->assertSame('Ahmet Yılmaz', $r->kazazede_ad_soyad);
        $this->assertSame(['kkd_kullanilmamasi'], $r->kok_neden_kategorileri);
        $this->assertTrue($r->sgk_bildirimi_yapildi);
        $this->assertSame('isg-profesyonel-kase/x.png', $r->rapor_hazirlayan_kase);
        $this->assertStringStartsWith('IKR-'.now()->year.'-', $r->belge_no);
    }

    public function test_pdf_uretilir(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();
        $r = IsKazasiRaporu::create([
            'firma_id' => $firma->id,
            'kazazede_ad_soyad' => 'Test Kişi',
            'kaza_tanimi' => 'Test tanım',
        ]);

        $yanit = IsKazasiRaporuUretici::pdf($r);

        $this->assertInstanceOf(StreamedResponse::class, $yanit);
        ob_start();
        $yanit->sendContent();
        $icerik = ob_get_clean();
        $this->assertStringStartsWith('%PDF', $icerik);
    }

    public function test_fotograflar_yuklenir_kaydedilince_saklanir_ve_silinebilir(): void
    {
        Storage::fake('public');
        $firma = Firma::factory()->for($this->uzman)->create();

        $component = Livewire::test(KazaSayfasi::class)
            ->set('firmaId', $firma->id)
            ->set('kazazedeAdSoyad', 'Ahmet Yılmaz')
            ->set('kazaTanimi', 'Merdivenden düşme.')
            ->set('yeniFotograflar', [
                UploadedFile::fake()->image('olay-1.jpg'),
                UploadedFile::fake()->image('olay-2.jpg'),
            ]);

        $this->assertCount(2, $component->get('yeniFotograflar'));

        $component->call('fotoSil', 0);
        $this->assertCount(1, $component->get('yeniFotograflar'));

        $component->callAction('raporuTamamla');

        $r = IsKazasiRaporu::where('firma_id', $firma->id)->firstOrFail();
        $this->assertCount(1, $r->fotograflar);
        Storage::disk('public')->assertExists($r->fotograflar[0]);
    }

    public function test_fotografli_is_kazasi_pdfinde_kanit_sayfasi_olusur(): void
    {
        Storage::fake('public');
        $yol = UploadedFile::fake()->image('kanit.jpg')->store('is-kazasi-foto', 'public');
        $yol2 = UploadedFile::fake()->image('kanit2.jpg')->store('is-kazasi-foto', 'public');

        $firma = Firma::factory()->for($this->uzman)->create();
        $r = IsKazasiRaporu::create([
            'firma_id' => $firma->id,
            'kazazede_ad_soyad' => 'Test Kişi',
            'kaza_tanimi' => 'Test tanım',
            'fotograflar' => [$yol, $yol2],
        ]);

        $html = view('pdf.is-kazasi-raporu', ['rapor' => $r, 'firma' => $firma])->render();

        $this->assertStringContainsString('KAZA YERİ FOTOĞRAFI 1', $html);
        $this->assertStringContainsString('KAZA YERİ FOTOĞRAFI 2', $html);
        $this->assertStringContainsString($yol, $html);
        $this->assertStringContainsString($yol2, $html);
    }

    public function test_5_neden_ve_balik_kilcigi_ve_dof_kaydedilir_ve_pdfe_yansir(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();

        Livewire::test(KazaSayfasi::class)
            ->set('firmaId', $firma->id)
            ->set('kazazedeAdSoyad', 'Ali Veli')
            ->set('kazazedeKidem', '4 yıl')
            ->set('kazaYeri', 'Pres bölümü')
            ->set('kazaTanimi', 'Operatörün eli preste sıkıştı.')
            ->set('besNeden', ['Eli sıkıştı', 'Çift el kumanda yoktu', 'Risk analizi güncel değil', 'Periyodik denetim yapılmadı', 'İş güvenliği yönetim sistemi eksik'])
            ->set('kokNedenKategorileri', ['ekipman_arizasi', 'yonetim_sistemi'])
            ->call('balikKilcigiOtomatik')
            ->call('dofEkle')
            ->set('dofMaddeleri.0.tip', 'teknik')
            ->set('dofMaddeleri.0.aciklama', 'Prese çift el kumanda ve ışık bariyeri montajı')
            ->set('dofMaddeleri.0.sorumlu', 'Bakım Md.')
            ->set('dofMaddeleri.0.hedef_tarih', '2026-11-01')
            ->callAction('raporuTamamla');

        $r = IsKazasiRaporu::where('firma_id', $firma->id)->firstOrFail();

        $this->assertSame('tamamlandi', $r->durum);
        $this->assertCount(5, $r->bes_neden);
        $this->assertSame('İş güvenliği yönetim sistemi eksik', $r->bes_neden[4]);
        $this->assertNotEmpty($r->balik_kilcigi['makine']);   // ekipman_arizasi → makine
        $this->assertCount(1, $r->dof_maddeleri);
        $this->assertSame('Bakım Md.', $r->dof_maddeleri[0]['sorumlu']);

        $html = view('pdf.is-kazasi-raporu', ['rapor' => $r, 'firma' => $firma])->render();
        $this->assertStringContainsString('Neden sistem bunu engellemedi?', $html);
        $this->assertStringContainsString('Prese çift el kumanda', $html);
        $this->assertStringContainsString('BALIK KILÇIĞI', $html);
    }

    public function test_taslak_kaydet_durumu_taslak_birakir_pdf_uretmez(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();

        $yanit = Livewire::test(KazaSayfasi::class)
            ->set('firmaId', $firma->id)
            ->set('kazazedeAdSoyad', 'Ali Veli')
            ->set('kazaTanimi', 'Kısa özet')
            ->callAction('taslakKaydet');

        $r = IsKazasiRaporu::where('firma_id', $firma->id)->firstOrFail();
        $this->assertSame('taslak', $r->durum);
    }

    public function test_gecmis_kayit_duzenlemeye_alinir(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();
        $r = IsKazasiRaporu::create([
            'firma_id' => $firma->id, 'kazazede_ad_soyad' => 'Eski Kayıt', 'kaza_tanimi' => 'Eski',
            'bes_neden' => ['a', 'b', 'c', 'd', 'e'],
        ]);

        Livewire::test(KazaSayfasi::class)
            ->set('firmaId', $firma->id)
            ->call('duzenle', $r->id)
            ->assertSet('duzenlenenId', $r->id)
            ->assertSet('kazazedeAdSoyad', 'Eski Kayıt')
            ->assertSet('besNeden.2', 'c');
    }

    public function test_gecmis_kayit_silinir(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();
        $r = IsKazasiRaporu::create(['firma_id' => $firma->id, 'kazazede_ad_soyad' => 'X', 'kaza_tanimi' => 'Y']);

        Livewire::test(KazaSayfasi::class)
            ->set('firmaId', $firma->id)
            ->call('gecmisSil', $r->id);

        $this->assertDatabaseMissing('is_kazasi_raporlari', ['id' => $r->id]);
    }

    public function test_baska_uzmanin_firmasi_secilemez(): void
    {
        $baskaUzman = User::factory()->create();
        $baskaFirma = Firma::factory()->for($baskaUzman)->create();

        $firmalar = Livewire::test(KazaSayfasi::class)->instance()->firmalar();

        $this->assertArrayNotHasKey($baskaFirma->id, $firmalar);
    }
}
