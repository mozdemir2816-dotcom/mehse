<?php

namespace Tests\Feature;

use App\Filament\Pages\CezaTeblig as CezaSayfasi;
use App\Models\Calisan;
use App\Models\CezaTebligTutanagi;
use App\Models\Firma;
use App\Models\IpcTebligi;
use App\Models\IsgProfesyoneli;
use App\Models\User;
use App\Support\CezaTebligTutanagiUretici;
use App\Support\IpcTebligiUretici;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Tests\TestCase;

class CezaTebligTest extends TestCase
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
        $calisan = Calisan::factory()->for($firma)->create(['ad_soyad' => 'Ahmet Yılmaz', 'gorev' => 'Formen']);

        $component = Livewire::test(CezaSayfasi::class)
            ->set('firmaId', $firma->id)
            ->set('calisanHizliSecId', $calisan->id);

        $this->assertSame('Ahmet Yılmaz', $component->get('calisanAdSoyad'));
        $this->assertSame('Formen', $component->get('calisanGorev'));
    }

    public function test_katalogdan_ihlal_toggle_edilir(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();
        $ilkMadde = config('isg.ceza_teblig.ihlal_kategorileri.Kişisel Koruyucu Donanım.0.madde');
        $ilkDayanak = config('isg.ceza_teblig.ihlal_kategorileri.Kişisel Koruyucu Donanım.0.dayanak');

        $component = Livewire::test(CezaSayfasi::class)
            ->set('firmaId', $firma->id)
            ->call('ihlalToggle', $ilkMadde, $ilkDayanak);

        $this->assertCount(1, $component->get('ihlaller'));

        $component->call('ihlalToggle', $ilkMadde, $ilkDayanak);
        $this->assertCount(0, $component->get('ihlaller'));
    }

    public function test_serbest_ihlal_eklenir(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();

        $component = Livewire::test(CezaSayfasi::class)
            ->set('firmaId', $firma->id)
            ->set('serbestIhlalMetni', 'Vardiya değişiminde devir teslim yapılmadı.')
            ->call('serbestIhlalEkle');

        $this->assertCount(1, $component->get('ihlaller'));
        $this->assertSame('İşyeri İç Yönetmeliği', $component->get('ihlaller')[0]['dayanak']);
    }

    public function test_tanik_eklenir_ve_silinir(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();

        $component = Livewire::test(CezaSayfasi::class)
            ->set('firmaId', $firma->id)
            ->set('yeniTanikAd', 'Vardiya Amiri')
            ->call('tanikEkle');

        $this->assertCount(1, $component->get('taniklar'));

        $component->call('tanikSil', 0);
        $this->assertCount(0, $component->get('taniklar'));
    }

    public function test_pdf_aksiyonu_kayit_olusturur(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();

        Livewire::test(CezaSayfasi::class)
            ->set('firmaId', $firma->id)
            ->set('calisanAdSoyad', 'Ahmet Yılmaz')
            ->set('istihdamSekli', 'alt_isveren')
            ->set('yaptirim', 'yazili_ihtar')
            ->set('imzaDurumu', 'imtina_etti')
            ->callAction('pdf');

        $t = CezaTebligTutanagi::where('firma_id', $firma->id)->firstOrFail();
        $this->assertSame('Ahmet Yılmaz', $t->calisan_ad_soyad);
        $this->assertSame('alt_isveren', $t->istihdam_sekli);
        $this->assertSame('yazili_ihtar', $t->yaptirim);
        $this->assertSame('imtina_etti', $t->imza_durumu);
        $this->assertStringStartsWith('CT-'.now()->year.'-', $t->tutanak_no);
    }

    public function test_fotograflar_yuklenir_kaydedilince_saklanir_ve_silinebilir(): void
    {
        Storage::fake('public');
        $firma = Firma::factory()->for($this->uzman)->create();

        $component = Livewire::test(CezaSayfasi::class)
            ->set('firmaId', $firma->id)
            ->set('calisanAdSoyad', 'Ahmet Yılmaz')
            ->set('yaptirim', 'sozlu_uyari')
            ->set('yeniFotograflar', [
                UploadedFile::fake()->image('olay-1.jpg'),
                UploadedFile::fake()->image('olay-2.jpg'),
            ]);

        $this->assertCount(2, $component->get('yeniFotograflar'));

        $component->call('fotoSil', 0);
        $this->assertCount(1, $component->get('yeniFotograflar'));

        $component->callAction('pdf');

        $t = CezaTebligTutanagi::where('firma_id', $firma->id)->firstOrFail();
        $this->assertCount(1, $t->fotograflar);
        Storage::disk('public')->assertExists($t->fotograflar[0]);
    }

    public function test_fotografli_ceza_teblig_pdfinde_kanit_sayfasi_olusur(): void
    {
        Storage::fake('public');
        $yol = UploadedFile::fake()->image('kanit.jpg')->store('ceza-teblig-foto', 'public');

        $firma = Firma::factory()->for($this->uzman)->create();
        $t = CezaTebligTutanagi::create([
            'firma_id' => $firma->id,
            'calisan_ad_soyad' => 'Test Kişi',
            'yaptirim' => 'sozlu_uyari',
            'fotograflar' => [$yol],
        ]);

        $html = view('pdf.ceza-teblig-tutanagi', ['tutanak' => $t, 'firma' => $firma])->render();

        $this->assertStringContainsString('FOTOĞRAF 1', $html);
        $this->assertStringContainsString($yol, $html);
    }

    public function test_calisan_adi_olmadan_kaydedilemez(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();

        Livewire::test(CezaSayfasi::class)
            ->set('firmaId', $firma->id)
            ->callAction('pdf');

        $this->assertDatabaseCount('ceza_teblig_tutanaklari', 0);
    }

    public function test_pdf_uretilir(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();
        $t = CezaTebligTutanagi::create([
            'firma_id' => $firma->id,
            'calisan_ad_soyad' => 'Test Kişi',
            'istihdam_sekli' => 'kadrolu',
            'yaptirim' => 'sozlu_uyari',
            'ihlaller' => [['madde' => 'KKD kullanmama', 'dayanak' => '6331 s.K. m.19']],
            'taniklar' => [],
        ]);

        $yanit = CezaTebligTutanagiUretici::pdf($t);

        $this->assertInstanceOf(StreamedResponse::class, $yanit);
        ob_start();
        $yanit->sendContent();
        $icerik = ob_get_clean();
        $this->assertStringStartsWith('%PDF', $icerik);
    }

    public function test_gecmis_tutanak_silinir(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();
        $t = CezaTebligTutanagi::create(['firma_id' => $firma->id, 'calisan_ad_soyad' => 'Test']);

        Livewire::test(CezaSayfasi::class)
            ->set('firmaId', $firma->id)
            ->call('gecmisSil', $t->id);

        $this->assertDatabaseMissing('ceza_teblig_tutanaklari', ['id' => $t->id]);
    }

    public function test_baska_uzmanin_firmasi_secilemez(): void
    {
        $baskaUzman = User::factory()->create();
        $baskaFirma = Firma::factory()->for($baskaUzman)->create();

        $firmalar = Livewire::test(CezaSayfasi::class)->instance()->firmalar();

        $this->assertArrayNotHasKey($baskaFirma->id, $firmalar);
    }

    /*
    |--------------------------------------------------------------------------
    | İşverene İPC Tebliği (2. sekme)
    |--------------------------------------------------------------------------
    */

    public function test_ipc_katalogundan_ihlal_toggle_edilir(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();
        $ilkBaslik = config('isg.ceza_teblig.ipc_maddeleri.0.baslik');
        $ilkAciklama = config('isg.ceza_teblig.ipc_maddeleri.0.aciklama');

        $component = Livewire::test(CezaSayfasi::class)
            ->set('firmaId', $firma->id)
            ->set('aktifSekme', 'ipc')
            ->call('ihlalIpcToggle', $ilkBaslik, $ilkAciklama);

        $this->assertCount(1, $component->get('ihlallerIpc'));

        $component->call('ihlalIpcToggle', $ilkBaslik, $ilkAciklama);
        $this->assertCount(0, $component->get('ihlallerIpc'));
    }

    public function test_pesin_odeme_tutari_yuzde_25_indirimli_hesaplanir(): void
    {
        $component = Livewire::test(CezaSayfasi::class)
            ->set('aktifSekme', 'ipc')
            ->set('cezaTutari', 1000);

        $this->assertSame(750.0, $component->instance()->pesinOdemeTutari());
    }

    public function test_ipc_pdf_aksiyonu_kayit_olusturur_ve_kase_snapshotlanir(): void
    {
        $igu = IsgProfesyoneli::factory()->for($this->uzman)->create(['kase_gorseli' => 'isg-profesyonel-kase/x.png']);
        $firma = Firma::factory()->for($this->uzman)->create(['igu_id' => $igu->id]);

        Livewire::test(CezaSayfasi::class)
            ->set('firmaId', $firma->id)
            ->set('aktifSekme', 'ipc')
            ->set('tespitEdenKurum', 'Çalışma ve Sosyal Güvenlik Bakanlığı')
            ->set('mufettisAdi', 'X Y')
            ->call('ihlalIpcToggle', config('isg.ceza_teblig.ipc_maddeleri.0.baslik'), config('isg.ceza_teblig.ipc_maddeleri.0.aciklama'))
            ->set('cezaTutari', 2000)
            ->callAction('pdfIpc');

        $t = IpcTebligi::where('firma_id', $firma->id)->firstOrFail();
        $this->assertSame('Çalışma ve Sosyal Güvenlik Bakanlığı', $t->tespit_eden_kurum);
        $this->assertCount(1, $t->ihlaller);
        $this->assertSame(2000.0, (float) $t->ceza_tutari);
        $this->assertSame(1500.0, (float) $t->pesin_odeme_tutari);
        $this->assertSame('isg-profesyonel-kase/x.png', $t->hazirlayan_kase);
        $this->assertStringStartsWith('IPC-'.now()->year.'-', $t->belge_no);
    }

    public function test_ipc_firma_secilmeden_pdf_aksiyonu_gizli(): void
    {
        Livewire::test(CezaSayfasi::class)
            ->set('aktifSekme', 'ipc')
            ->assertActionHidden('pdfIpc');

        $this->assertDatabaseCount('ipc_tebligleri', 0);
    }

    public function test_ipc_pdf_uretilir(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();
        $t = IpcTebligi::create(['firma_id' => $firma->id, 'ceza_tutari' => 500]);

        $yanit = IpcTebligiUretici::pdf($t);

        $this->assertInstanceOf(StreamedResponse::class, $yanit);
        ob_start();
        $yanit->sendContent();
        $icerik = ob_get_clean();
        $this->assertStringStartsWith('%PDF', $icerik);
    }

    public function test_ipc_gecmis_teblig_silinir(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();
        $t = IpcTebligi::create(['firma_id' => $firma->id]);

        Livewire::test(CezaSayfasi::class)
            ->set('firmaId', $firma->id)
            ->call('gecmisIpcSil', $t->id);

        $this->assertDatabaseMissing('ipc_tebligleri', ['id' => $t->id]);
    }

    public function test_pdf_aksiyonu_sadece_kendi_sekmesinde_gorunur(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();

        Livewire::test(CezaSayfasi::class)
            ->set('firmaId', $firma->id)
            ->set('aktifSekme', 'tutanak')
            ->assertActionVisible('pdf')
            ->assertActionHidden('pdfIpc')
            ->set('aktifSekme', 'ipc')
            ->assertActionHidden('pdf')
            ->assertActionVisible('pdfIpc');
    }
}
