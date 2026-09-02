<?php

namespace Tests\Feature;

use App\Filament\Pages\SahaDenetimi as SahaSayfasi;
use App\Models\Calisan;
use App\Models\Firma;
use App\Models\IsgProfesyoneli;
use App\Models\SahaDenetimi;
use App\Models\User;
use App\Support\SahaDenetimiUretici;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Tests\TestCase;

class SahaDenetimiTest extends TestCase
{
    use RefreshDatabase;

    private User $uzman;

    protected function setUp(): void
    {
        parent::setUp();
        $this->uzman = User::factory()->create();
        $this->actingAs($this->uzman);
    }

    public function test_kontrol_listesi_41_madde_icerir(): void
    {
        $toplam = collect(config('isg.saha_denetimi.kategoriler'))->sum(fn ($k) => count($k['maddeler']));

        $this->assertSame(41, $toplam);
    }

    public function test_firma_secilince_denetci_otomatik_dolar(): void
    {
        $igu = IsgProfesyoneli::factory()->for($this->uzman)->create(['ad_soyad' => 'İGU Ayşe']);
        $firma = Firma::factory()->for($this->uzman)->create(['igu_id' => $igu->id, 'unvan' => 'Test A.Ş.']);

        $component = Livewire::test(SahaSayfasi::class)->set('firmaId', $firma->id);

        $this->assertSame('İGU Ayşe', $component->get('denetciAdi'));
    }

    public function test_firma_placeholder_ifadede_degistirilir(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create(['unvan' => 'ACME İnşaat A.Ş.']);

        $component = Livewire::test(SahaSayfasi::class)->set('firmaId', $firma->id);

        $component->assertSee('ACME İnşaat A.Ş. etiketiyle tanımlanmış mı?');
    }

    public function test_cevap_verilir(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();

        $component = Livewire::test(SahaSayfasi::class)
            ->set('firmaId', $firma->id)
            ->call('cevapVer', '1.1', 'uygun');

        $this->assertSame('uygun', $component->get('cevaplar')['1_1']['sonuc']);
    }

    public function test_uygun_degil_icin_aciklama_zorunlu(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();

        Livewire::test(SahaSayfasi::class)
            ->set('firmaId', $firma->id)
            ->call('cevapVer', '1.1', 'uygun_degil')
            ->callAction('pdf');

        $this->assertDatabaseCount('saha_denetimleri', 0);
    }

    public function test_ekip_firma_calisanindan_hizli_eklenir(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();
        $c = Calisan::factory()->for($firma)->create(['ad_soyad' => 'Ahmet Yılmaz']);

        $component = Livewire::test(SahaSayfasi::class)
            ->set('firmaId', $firma->id)
            ->call('ekipHizliEkle', $c->id);

        $this->assertSame('Ahmet Yılmaz', $component->get('ekipUyeleri')[0]['ad_soyad']);
    }

    public function test_pdf_aksiyonu_kayit_olusturur_uygunluk_hesaplar_ve_kase_snapshotlanir(): void
    {
        Storage::fake('public');
        $igu = IsgProfesyoneli::factory()->for($this->uzman)->create(['kase_gorseli' => 'isg-profesyonel-kase/x.png']);
        $firma = Firma::factory()->for($this->uzman)->create(['igu_id' => $igu->id]);

        Livewire::test(SahaSayfasi::class)
            ->set('firmaId', $firma->id)
            ->call('cevapVer', '1.1', 'uygun')
            ->call('cevapVer', '1.2', 'uygun')
            ->call('cevapVer', '1.3', 'uygun_degil')
            ->set('cevaplar.1_3.aciklama', 'Eğitim eksik')
            ->set('fotoYuklemeleri.1_3', UploadedFile::fake()->image('kanit.jpg'))
            ->call('cevapVer', '1.4', 'uygulanamaz')
            ->callAction('pdf');

        $d = SahaDenetimi::where('firma_id', $firma->id)->firstOrFail();
        $this->assertSame(1, $d->revizyon);
        $this->assertSame('isg-profesyonel-kase/x.png', $d->denetci_kase);
        $this->assertTrue($d->kritik_uygunsuzluk_var);
        $this->assertEqualsWithDelta(66.67, $d->uygunluk_yuzdesi, 0.01);

        $madde13 = collect($d->cevaplar)->firstWhere('kod', '1.3');
        $this->assertSame('Eğitim eksik', $madde13['aciklama']);
        $this->assertNotNull($madde13['foto_yolu']);
        Storage::disk('public')->assertExists($madde13['foto_yolu']);
    }

    public function test_pdf_uretilir(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();
        $d = SahaDenetimi::create([
            'firma_id' => $firma->id,
            'revizyon' => 1,
            'cevaplar' => [
                ['kategori_ad' => 'Yangın', 'kod' => '1.1', 'ifade' => 'Test?', 'kritik' => true, 'sonuc' => 'uygun', 'aciklama' => null, 'foto_yolu' => null],
            ],
        ]);

        $yanit = SahaDenetimiUretici::pdf($d);

        $this->assertInstanceOf(StreamedResponse::class, $yanit);
        ob_start();
        $yanit->sendContent();
        $icerik = ob_get_clean();
        $this->assertStringStartsWith('%PDF', $icerik);
    }

    public function test_gecmis_kayit_silinir(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();
        $d = SahaDenetimi::create(['firma_id' => $firma->id, 'revizyon' => 1]);

        Livewire::test(SahaSayfasi::class)
            ->set('firmaId', $firma->id)
            ->call('gecmisSil', $d->id);

        $this->assertDatabaseMissing('saha_denetimleri', ['id' => $d->id]);
    }

    public function test_baska_uzmanin_firmasi_secilemez(): void
    {
        $baskaUzman = User::factory()->create();
        $baskaFirma = Firma::factory()->for($baskaUzman)->create();

        $firmalar = Livewire::test(SahaSayfasi::class)->instance()->firmalar();

        $this->assertArrayNotHasKey($baskaFirma->id, $firmalar);
    }
}
