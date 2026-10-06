<?php

namespace Tests\Feature;

use App\Filament\Pages\KurulToplantisi as KurulSayfasi;
use App\Models\Calisan;
use App\Models\Firma;
use App\Models\KurulToplantisi;
use App\Models\User;
use App\Support\GeminiKararDanismani;
use App\Support\KurulToplantisiUretici;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Tests\TestCase;

class KurulToplantisiTest extends TestCase
{
    use RefreshDatabase;

    private User $uzman;

    protected function setUp(): void
    {
        parent::setUp();
        $this->uzman = User::factory()->create();
        $this->actingAs($this->uzman);
    }

    public function test_yeni_toplanti_olusturulur(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();

        // Başkan, "baskan" rolündeki kurul üyesinden gelir; katılımcılar üyelerden kopyalanır.
        $firma->kurulUyeleri()->create(['rol' => 'baskan', 'ad_soyad' => 'Ali Veli', 'gorev' => 'İşveren']);

        $component = Livewire::test(KurulSayfasi::class)
            ->set('firmaId', $firma->id)
            ->callAction('toplantiPlanla', ['yer' => 'Toplantı Salonu', 'gundem_ek' => "Ek madde 1\nEk madde 2"])
            ->assertHasNoActionErrors();

        $this->assertDatabaseHas('kurul_toplantilari', [
            'firma_id' => $firma->id,
            'yer' => 'Toplantı Salonu',
            'baskan' => 'Ali Veli',
        ]);
        $toplanti = $firma->kurulToplantilari()->firstOrFail();
        $this->assertSame('Ali Veli', $toplanti->katilimcilar[0]['ad_soyad']);
        $this->assertSame('baskan', $toplanti->katilimcilar[0]['rol']);
        $this->assertContains('Ek madde 2', $toplanti->gundem);
        $this->assertSame($toplanti->id, $component->get('toplantiId'));
    }

    public function test_katilimci_eklenir_ve_katilim_durumu_degistirilir(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();
        $toplanti = $firma->kurulToplantilari()->create(['tarih' => now(), 'katilimcilar' => [], 'gundem' => [], 'kararlar' => []]);

        $component = Livewire::test(KurulSayfasi::class)
            ->set('firmaId', $firma->id)
            ->call('toplantiSec', $toplanti->id)
            ->set('yeniKatilimciAd', 'Ahmet Yılmaz')
            ->set('yeniKatilimciGorev', 'İş Güvenliği Uzmanı')
            ->call('katilimciEkle');

        $toplanti->refresh();
        $this->assertCount(1, $toplanti->katilimcilar);
        $this->assertTrue($toplanti->katilimcilar[0]['katildi']);

        $component->call('katilimToggle', 0);
        $toplanti->refresh();
        $this->assertFalse($toplanti->katilimcilar[0]['katildi']);
    }

    public function test_firma_calisanindan_hizli_katilimci_eklenir(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();
        $calisan = Calisan::factory()->for($firma)->create(['ad_soyad' => 'Zeynep Kaya']);
        $toplanti = $firma->kurulToplantilari()->create(['tarih' => now(), 'katilimcilar' => [], 'gundem' => [], 'kararlar' => []]);

        Livewire::test(KurulSayfasi::class)
            ->set('firmaId', $firma->id)
            ->call('toplantiSec', $toplanti->id)
            ->call('katilimHizliEkle', $calisan->id);

        $toplanti->refresh();
        $this->assertSame('Zeynep Kaya', $toplanti->katilimcilar[0]['ad_soyad']);
    }

    public function test_gundem_manuel_ve_hazir_maddeyle_eklenir(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();
        $toplanti = $firma->kurulToplantilari()->create(['tarih' => now(), 'katilimcilar' => [], 'gundem' => [], 'kararlar' => []]);

        $hazirMadde = config('isg.kurul_toplantisi.hazir_gundem_maddeleri.Genel.0');

        Livewire::test(KurulSayfasi::class)
            ->set('firmaId', $firma->id)
            ->call('toplantiSec', $toplanti->id)
            ->set('yeniGundemMaddesi', 'Manuel gündem maddesi')
            ->call('gundemEkle')
            ->call('hazirGundemEkle', $hazirMadde);

        $toplanti->refresh();
        $this->assertContains('Manuel gündem maddesi', $toplanti->gundem);
        $this->assertContains($hazirMadde, $toplanti->gundem);
    }

    public function test_gundem_maddesinden_karar_eklenir(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();
        $toplanti = $firma->kurulToplantilari()->create([
            'tarih' => now(), 'katilimcilar' => [], 'gundem' => ['Yıllık plan gözden geçirme'], 'kararlar' => [],
        ]);

        Livewire::test(KurulSayfasi::class)
            ->set('firmaId', $firma->id)
            ->call('toplantiSec', $toplanti->id)
            ->call('kararFormuAc', 0)
            ->set('yeniKararMetni', 'Plan Mart ayında güncellenecek')
            ->set('yeniKararSorumlu', 'İGU')
            ->set('yeniKararTermin', '2026-03-01')
            ->call('kararEkle');

        $toplanti->refresh();
        $this->assertCount(1, $toplanti->kararlar);
        $this->assertSame('Yıllık plan gözden geçirme', $toplanti->kararlar[0]['gundem_maddesi']);
        $this->assertSame('beklemede', $toplanti->kararlar[0]['durum']);
    }

    public function test_yeni_toplantiya_firma_yil_bazli_no_atanir(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();
        $yil = now()->year;

        Livewire::test(KurulSayfasi::class)->set('firmaId', $firma->id)->callAction('toplantiPlanla');
        Livewire::test(KurulSayfasi::class)->set('firmaId', $firma->id)->callAction('toplantiPlanla');

        $nolar = $firma->kurulToplantilari()->orderBy('id')->pluck('toplanti_no')->all();
        $this->assertSame(["{$yil}/1", "{$yil}/2"], $nolar);
    }

    public function test_toplanti_no_elle_duzeltilebilir(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();
        $toplanti = $firma->kurulToplantilari()->create(['toplanti_no' => '2026/1', 'tarih' => now(), 'katilimcilar' => [], 'gundem' => [], 'kararlar' => []]);

        Livewire::test(KurulSayfasi::class)
            ->set('firmaId', $firma->id)
            ->call('toplantiSec', $toplanti->id)
            ->set('toplantiNo', '2026/A-1')
            ->call('toplantiBilgileriniKaydet');

        $this->assertSame('2026/A-1', $toplanti->refresh()->toplanti_no);
    }

    public function test_karar_kayittan_sonra_duzenlenir(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();
        $toplanti = $firma->kurulToplantilari()->create([
            'tarih' => now(), 'katilimcilar' => [], 'gundem' => ['Gündem X'],
            'kararlar' => [['gundem_maddesi' => 'Gündem X', 'karar_metni' => 'Eski metin', 'sorumlu' => 'A', 'termin' => null, 'durum' => 'devam_ediyor']],
        ]);

        Livewire::test(KurulSayfasi::class)
            ->set('firmaId', $firma->id)
            ->call('toplantiSec', $toplanti->id)
            ->call('kararDuzenle', 0)
            ->assertSet('yeniKararMetni', 'Eski metin')
            ->set('yeniKararMetni', 'Yeni düzeltilmiş metin')
            ->set('yeniKararSorumlu', 'B')
            ->call('kararGuncelle');

        $karar = $toplanti->refresh()->kararlar[0];
        $this->assertSame('Yeni düzeltilmiş metin', $karar['karar_metni']);
        $this->assertSame('B', $karar['sorumlu']);
        $this->assertSame('Gündem X', $karar['gundem_maddesi']); // korunur
        $this->assertSame('devam_ediyor', $karar['durum']);      // korunur
    }

    public function test_tamamlanmis_toplantida_kararin_gundemi_degistirilir(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();
        $toplanti = $firma->kurulToplantilari()->create([
            'tarih' => now(), 'durum' => 'tamamlandi', 'katilimcilar' => [], 'gundem' => ['Gündem X', 'Gündem Y'],
            'kararlar' => [['gundem_maddesi' => 'Gündem X', 'karar_metni' => 'Metin', 'sorumlu' => null, 'termin' => null, 'durum' => 'beklemede']],
        ]);

        Livewire::test(KurulSayfasi::class)
            ->set('firmaId', $firma->id)
            ->call('toplantiSec', $toplanti->id)
            ->call('kararDuzenle', 0)
            ->assertSet('yeniKararGundem', 'Gündem X')
            ->set('yeniKararGundem', 'Gündem Y')
            ->call('kararGuncelle')
            ->assertSet('yeniKararGundem', null);

        $this->assertSame('Gündem Y', $toplanti->refresh()->kararlar[0]['gundem_maddesi']);
    }

    public function test_gundem_maddesi_duzenlenir_ve_bagli_kararlar_tasinir(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();
        $toplanti = $firma->kurulToplantilari()->create([
            'tarih' => now(), 'katilimcilar' => [], 'gundem' => ['Eski gündem', 'Diğer'],
            'kararlar' => [
                ['gundem_maddesi' => 'Eski gündem', 'karar_metni' => 'K1', 'durum' => 'beklemede'],
                ['gundem_maddesi' => 'Diğer', 'karar_metni' => 'K2', 'durum' => 'beklemede'],
            ],
        ]);

        Livewire::test(KurulSayfasi::class)
            ->set('firmaId', $firma->id)
            ->call('toplantiSec', $toplanti->id)
            ->call('gundemDuzenle', 0)
            ->assertSet('gundemDuzenMetni', 'Eski gündem')
            ->set('gundemDuzenMetni', 'Yeni gündem')
            ->call('gundemGuncelle')
            ->assertSet('duzenlenenGundemIndex', null)
            ->assertSee('Yeni gündem');

        $toplanti->refresh();
        $this->assertSame(['Yeni gündem', 'Diğer'], $toplanti->gundem);
        $this->assertSame('Yeni gündem', $toplanti->kararlar[0]['gundem_maddesi']);
        $this->assertSame('Diğer', $toplanti->kararlar[1]['gundem_maddesi']);
    }

    public function test_excel_uretilir_durum_sutunu_olmadan(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();
        $toplanti = KurulToplantisi::create([
            'firma_id' => $firma->id, 'toplanti_no' => '2026/3', 'tarih' => now(),
            'katilimcilar' => [['ad_soyad' => 'Test', 'gorev' => 'İGU', 'katildi' => true]],
            'gundem' => ['Madde 1'],
            'kararlar' => [['gundem_maddesi' => 'Madde 1', 'karar_metni' => 'Karar', 'sorumlu' => 'X', 'termin' => null, 'durum' => 'beklemede']],
        ]);

        $yanit = KurulToplantisiUretici::excel($toplanti);
        $this->assertInstanceOf(StreamedResponse::class, $yanit);
        ob_start();
        $yanit->sendContent();
        $this->assertStringStartsWith('PK', ob_get_clean());
    }

    public function test_pdf_kararlar_tablosunda_durum_sutunu_yok_toplanti_no_var(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();
        $toplanti = KurulToplantisi::create([
            'firma_id' => $firma->id, 'toplanti_no' => '2026/7', 'tarih' => now(),
            'katilimcilar' => [], 'gundem' => ['G1'],
            'kararlar' => [['gundem_maddesi' => 'G1', 'karar_metni' => 'K1', 'sorumlu' => 'S1', 'termin' => null, 'durum' => 'tamamlandi']],
        ]);

        $html = view('pdf.kurul-toplantisi', ['toplanti' => $toplanti, 'firma' => $firma])->render();

        $this->assertStringContainsString('Toplantı No', $html);
        $this->assertStringContainsString('2026/7', $html);
        $this->assertStringContainsString('<th>Karar Metni</th>', $html);
        // Kararlar tablosunda "Durum" başlığı/rozeti kaldırıldı (karar metni sütunu genişledi).
        $this->assertStringNotContainsString('<th>Durum</th>', $html);
        $this->assertStringNotContainsString('Tamamlandı', $html);
        $this->assertStringNotContainsString('class="durum"', $html);
    }

    public function test_karar_durumu_guncellenir(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();
        $toplanti = $firma->kurulToplantilari()->create([
            'tarih' => now(), 'katilimcilar' => [], 'gundem' => [],
            'kararlar' => [['gundem_maddesi' => 'X', 'karar_metni' => 'Y', 'sorumlu' => null, 'termin' => null, 'durum' => 'beklemede']],
        ]);

        Livewire::test(KurulSayfasi::class)
            ->set('firmaId', $firma->id)
            ->call('toplantiSec', $toplanti->id)
            ->call('kararDurumGuncelle', 0, 'tamamlandi');

        $toplanti->refresh();
        $this->assertSame('tamamlandi', $toplanti->kararlar[0]['durum']);
    }

    public function test_gemini_karar_onerisi_api_anahtari_yokken_pasif(): void
    {
        config(['services.gemini.key' => null]);

        $this->assertFalse(GeminiKararDanismani::aktifMi());
        $this->assertNull(GeminiKararDanismani::oner('Bir gündem maddesi'));
    }

    public function test_gemini_karar_onerisi_gecerli_yanit_doner(): void
    {
        config(['services.gemini.key' => 'test-key']);

        Http::fake([
            '*generativelanguage.googleapis.com*' => Http::response([
                'candidates' => [['content' => ['parts' => [['text' => 'Öneri: yıllık plan mart ayında güncellenecek.']]]]],
            ], 200),
        ]);

        $oneri = GeminiKararDanismani::oner('Yıllık plan gözden geçirme');

        $this->assertSame('Öneri: yıllık plan mart ayında güncellenecek.', $oneri);
    }

    public function test_pdf_uretilir(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();
        $toplanti = KurulToplantisi::create([
            'firma_id' => $firma->id,
            'tarih' => now(),
            'katilimcilar' => [['ad_soyad' => 'Test', 'gorev' => 'İGU', 'katildi' => true]],
            'gundem' => ['Madde 1'],
            'kararlar' => [['gundem_maddesi' => 'Madde 1', 'karar_metni' => 'Karar', 'sorumlu' => null, 'termin' => null, 'durum' => 'beklemede']],
        ]);

        $yanit = KurulToplantisiUretici::pdf($toplanti);

        $this->assertInstanceOf(StreamedResponse::class, $yanit);
        ob_start();
        $yanit->sendContent();
        $icerik = ob_get_clean();
        $this->assertStringStartsWith('%PDF', $icerik);
    }

    public function test_pdf_katilimcilar_tablosunda_katilim_yerine_imza_yeri_acilir(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();
        $toplanti = KurulToplantisi::create([
            'firma_id' => $firma->id,
            'tarih' => now(),
            'katilimcilar' => [
                ['ad_soyad' => 'Katılan Kişi', 'gorev' => 'İGU', 'katildi' => true],
                ['ad_soyad' => 'Katılmayan Kişi', 'gorev' => 'İşveren Vekili', 'katildi' => false],
            ],
            'gundem' => [],
            'kararlar' => [],
        ]);

        $html = view('pdf.kurul-toplantisi', ['toplanti' => $toplanti, 'firma' => $firma])->render();

        // İmza yeri en sonda, kararlardan sonra; kararlar yeni sayfadan başlar.
        $kararlar = strpos($html, 'Kararlar ve Takip</h2>');
        $imza = strpos($html, 'Katılımcı İmzaları');
        $this->assertNotFalse($imza);
        $this->assertGreaterThan($kararlar, $imza);
        $this->assertStringContainsString('page-break-before:always;margin-top:0">Kararlar ve Takip', $html);
        $this->assertGreaterThan(strpos($html, 'Gündem (Toplantı Konuları)'), $kararlar);
        $this->assertStringContainsString('İmza</th>', $html);
        $this->assertStringNotContainsString('Katıldı<', $html);

        // İmza tablosunda yalnız katılan; katılmayan ilk sayfada not olarak.
        $imzaBolumu = substr($html, $imza);
        $this->assertStringContainsString('Katılan Kişi', $imzaBolumu);
        $this->assertStringNotContainsString('Katılmayan Kişi', $imzaBolumu);
        $this->assertStringContainsString('Katılmayan: Katılmayan Kişi', $html);
    }

    public function test_pdf_gorevi_calisan_kaydindan_kurul_gorevi_atamadan_gelir(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();
        $firma->calisanlar()->create(['ad_soyad' => 'Ayşe Yılmaz', 'gorev' => 'Kalite Kontrol Teknisyeni', 'aktif' => true]);
        $firma->atamaYazilari()->create([
            'rol_anahtari' => 'calisan_temsilcisi',
            'uyeler' => [['ad_soyad' => 'AYŞE YILMAZ', 'gorev' => 'Teknisyen']],
        ]);
        $firma->atamaYazilari()->create([
            'rol_anahtari' => 'sondurme_ekibi',
            'uyeler' => [['ad_soyad' => 'Mehmet Kaya']],
        ]);

        $toplanti = KurulToplantisi::create([
            'firma_id' => $firma->id, 'tarih' => now(), 'gundem' => [], 'kararlar' => [],
            'katilimcilar' => [
                ['ad_soyad' => 'Ayşe Yılmaz', 'gorev' => 'eski görev', 'rol' => null, 'katildi' => true],
                ['ad_soyad' => 'Mehmet Kaya', 'gorev' => 'Usta', 'rol' => null, 'katildi' => true],
                ['ad_soyad' => 'Uzman Kişi', 'gorev' => 'A Sınıfı İGU', 'rol' => 'sekreter', 'katildi' => true],
            ],
        ]);

        [$ayse, $mehmet, $uzman] = \App\Support\KurulUyeleri::tutanakKatilimcilari($toplanti);

        $this->assertSame('Kalite Kontrol Teknisyeni', $ayse['is_gorevi']);       // çalışan kaydından
        $this->assertSame('Çalışan Temsilcisi (Baş Temsilci)', $ayse['kurul_gorevi']); // atamadan
        $this->assertSame('Usta', $mehmet['is_gorevi']);                           // kayıt yok → toplantıdaki
        $this->assertSame('Kurul Üyesi (Söndürme Ekibi)', $mehmet['kurul_gorevi']);
        $this->assertSame('İş Güvenliği Uzmanı (Sekreter)', $uzman['kurul_gorevi']);
        $this->assertSame('Uzman Kişi', $uzman['ad_soyad']);

        $html = view('pdf.kurul-toplantisi', ['toplanti' => $toplanti, 'firma' => $firma])->render();
        $this->assertStringContainsString('Kalite Kontrol Teknisyeni', $html);
        $this->assertStringContainsString('Uzman Kişi', $html); // kaşeli görevlinin adı da basılır (06.10.2026)
    }

    public function test_baskan_igu_hekim_adlari_bossa_firma_kaydindan_doldurulur(): void
    {
        $igu = \App\Models\IsgProfesyoneli::create(['user_id' => $this->uzman->id, 'tip' => 'igu', 'ad_soyad' => 'Mehmet Uzman', 'unvan' => 'A Sınıfı İş Güvenliği Uzmanı', 'sertifika_no' => '405044']);
        $hekim = \App\Models\IsgProfesyoneli::create(['user_id' => $this->uzman->id, 'tip' => 'isyeri_hekimi', 'ad_soyad' => 'Dr. Ayşe Hekim', 'sertifika_no' => 'H-77']);
        $firma = Firma::factory()->for($this->uzman)->create(['isveren_vekili' => 'Ali Müdür', 'igu_id' => $igu->id, 'isyeri_hekimi_id' => $hekim->id]);

        $toplanti = KurulToplantisi::create([
            'firma_id' => $firma->id, 'tarih' => now(), 'gundem' => [], 'kararlar' => [],
            'katilimcilar' => [
                ['ad_soyad' => '', 'gorev' => null, 'rol' => 'baskan', 'katildi' => true],
                ['ad_soyad' => '', 'gorev' => null, 'rol' => 'sekreter', 'katildi' => true],
                ['ad_soyad' => 'Dr. Ayşe Hekim', 'gorev' => 'İşyeri Hekimi', 'rol' => 'hekim', 'katildi' => true],
            ],
        ]);

        [$baskan, $sekreter, $hekimSatiri] = \App\Support\KurulUyeleri::tutanakKatilimcilari($toplanti);

        $this->assertSame('Ali Müdür', $baskan['ad_soyad']);
        $this->assertSame('Mehmet Uzman', $sekreter['ad_soyad']);
        $this->assertSame('A Sınıfı İş Güvenliği Uzmanı — Belge No: 405044', $sekreter['is_gorevi']);
        $this->assertStringContainsString('Belge No: H-77', $hekimSatiri['is_gorevi']);

        $html = view('pdf.kurul-toplantisi', ['toplanti' => $toplanti, 'firma' => $firma])->render();
        $this->assertStringContainsString('Ali Müdür', $html);
        $this->assertStringContainsString('Mehmet Uzman', $html);
        $this->assertStringContainsString('Dr. Ayşe Hekim', $html);
        // İmza föyü bölünmez kapta
        $this->assertStringContainsString('<div style="page-break-inside:avoid">'."\n".'<h2>Katılımcı İmzaları</h2>', $html);
    }

    public function test_word_ciktisi_pdf_ile_ayni_icerikte_uretilir(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create(['unvan' => 'Örnek & Ortak A.Ş.']);
        $toplanti = KurulToplantisi::create([
            'firma_id' => $firma->id, 'tarih' => '2026-10-06', 'toplanti_no' => '2026/4',
            'katilimcilar' => [
                ['ad_soyad' => 'Katılan Kişi', 'gorev' => 'Usta', 'rol' => null, 'katildi' => true],
                ['ad_soyad' => 'Uzman Kişi', 'gorev' => 'A Sınıfı İGU', 'rol' => 'sekreter', 'katildi' => true],
                ['ad_soyad' => 'Gelmeyen Kişi', 'gorev' => 'Formen', 'rol' => null, 'katildi' => false],
            ],
            'gundem' => ['Eğitim planı'],
            'kararlar' => [['gundem_maddesi' => 'Eğitim planı', 'karar_metni' => 'Yıllık eğitim planı onaylandı', 'sorumlu' => 'İGU', 'termin' => '2026-11-01']],
        ]);

        // Sayfadaki "Word İndir" düğmesi
        Livewire::test(KurulSayfasi::class)
            ->set('firmaId', $firma->id)
            ->call('toplantiSec', $toplanti->id)
            ->assertActionVisible('word')
            ->callAction('word')
            ->assertFileDownloaded('kurul-toplantisi-ornek-ortak-as-2026-4.docx');

        ob_start();
        KurulToplantisiUretici::word($toplanti)->sendContent();
        $docx = ob_get_clean();
        $this->assertStringStartsWith('PK', $docx);

        $yol = tempnam(sys_get_temp_dir(), 'kurul').'.docx';
        file_put_contents($yol, $docx);
        $zip = new \ZipArchive;
        $zip->open($yol);
        $xml = $zip->getFromName('word/document.xml');
        $medya = collect(range(0, $zip->numFiles - 1))->map(fn ($i) => $zip->getNameIndex($i))->filter(fn ($n) => str_starts_with($n, 'word/media/'));
        $zip->close();
        $metin = strip_tags($xml);

        $this->assertNotEmpty($medya, 'OSGB logosu gömülü olmalı');
        $this->assertStringContainsString('Örnek &amp; Ortak A.Ş.', $xml); // & doğru kaçışlı, belge bozulmaz
        $this->assertStringContainsString('Yıllık eğitim planı onaylandı', $metin);
        $this->assertStringContainsString('01.11.2026', $metin);
        $this->assertStringContainsString('<w:pageBreakBefore w:val="1"/>', $xml);              // kararlar yeni sayfada
        $this->assertGreaterThan(strpos($metin, 'Kararlar ve Takip'), strpos($metin, 'Katılımcı İmzaları'));
        $this->assertStringContainsString('Katılmayan: Gelmeyen Kişi', $metin);
        $this->assertStringContainsString('Uzman Kişi', $metin);                     // kaşeli görevlinin adı da basılır
    }

    public function test_pdf_basliginda_firma_logosu_yoksa_osgb_logosu_kullanilir(): void
    {
        \Illuminate\Support\Facades\Storage::fake('public');
        $firma = Firma::factory()->for($this->uzman)->create(['logo' => null]);
        $toplanti = KurulToplantisi::create(['firma_id' => $firma->id, 'tarih' => now(), 'katilimcilar' => [], 'gundem' => [], 'kararlar' => []]);

        $baslik = fn () => \Illuminate\Support\Str::betweenFirst(
            view('pdf.kurul-toplantisi', ['toplanti' => $toplanti->fresh(), 'firma' => $firma->fresh()])->render(),
            '<td class="firma">', '</td>'
        );

        $this->assertStringContainsString('yildiz-grup-osgb.png', $baslik());
        $this->assertStringNotContainsString($firma->unvan, $baslik());

        \Illuminate\Support\Facades\Storage::disk('public')->put('logolar/firma.png', 'x');
        $firma->update(['logo' => 'logolar/firma.png']);

        $this->assertStringContainsString('logolar', $baslik());
        $this->assertStringNotContainsString('yildiz-grup-osgb.png', $baslik());
    }

    public function test_toplanti_silinir(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();
        $toplanti = $firma->kurulToplantilari()->create(['tarih' => now(), 'katilimcilar' => [], 'gundem' => [], 'kararlar' => []]);

        Livewire::test(KurulSayfasi::class)
            ->set('firmaId', $firma->id)
            ->call('toplantiSil', $toplanti->id);

        $this->assertDatabaseMissing('kurul_toplantilari', ['id' => $toplanti->id]);
    }

    public function test_baska_uzmanin_firmasi_secilemez(): void
    {
        $baskaUzman = User::factory()->create();
        $baskaFirma = Firma::factory()->for($baskaUzman)->create();

        $firmalar = Livewire::test(KurulSayfasi::class)->instance()->firmalar();

        $this->assertArrayNotHasKey($baskaFirma->id, $firmalar);
    }
}
