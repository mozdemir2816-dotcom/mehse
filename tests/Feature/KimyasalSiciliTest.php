<?php

namespace Tests\Feature;

use App\Filament\Pages\KimyasalSicili;
use App\Models\Firma;
use App\Models\IsgAfis;
use App\Models\KimyasalKutuphane;
use App\Models\KimyasalUrun;
use App\Models\User;
use App\Support\KimyasalEnvanterUretici;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Testing\File;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Tests\TestCase;

class KimyasalSiciliTest extends TestCase
{
    use RefreshDatabase;

    private User $uzman;

    private Firma $firma;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        $this->uzman = User::factory()->create();
        $this->firma = Firma::factory()->for($this->uzman)->create(['unvan' => 'Kimya A.Ş.']);
        $this->actingAs($this->uzman);
    }

    public function test_kimyasal_eklenir_ve_gozden_gecirme_tarihi_otomatik_hesaplanir(): void
    {
        Livewire::test(KimyasalSicili::class)
            ->set('firmaId', $this->firma->id)
            ->callAction('kimyasalEkle', [
                'urun_adi' => 'Tiner',
                'cas_no' => '108-88-3',
                'fiziksel_hal' => 'sivi',
                'ghs' => ['ghs02', 'ghs07', 'ghs08'],
                'sds_tarihi' => '2026-01-10',
            ])
            ->assertHasNoActionErrors();

        $urun = KimyasalUrun::where('firma_id', $this->firma->id)->sole();
        $this->assertSame('Tiner', $urun->urun_adi);
        $this->assertSame(['ghs02', 'ghs07', 'ghs08'], $urun->ghs);
        $this->assertSame('2027-01-10', $urun->sonraki_gozden_gecirme->format('Y-m-d'));
        $this->assertFalse($urun->sdsVarMi());
    }

    public function test_sds_dosyasi_yuklenir_ve_indirilir(): void
    {
        Livewire::test(KimyasalSicili::class)
            ->set('firmaId', $this->firma->id)
            ->callAction('kimyasalEkle', [
                'urun_adi' => 'Aseton',
                'sds' => File::create('aseton-sds.pdf', 200, 'application/pdf'),
            ])
            ->assertHasNoActionErrors();

        $urun = KimyasalUrun::where('firma_id', $this->firma->id)->sole();
        $this->assertTrue($urun->sdsVarMi());
        Storage::disk('public')->assertExists($urun->sds_dosya_yolu);
    }

    public function test_ozet_sds_eksik_ve_gecikmisi_sayar(): void
    {
        $this->firma->kimyasalUrunler()->create(['urun_adi' => 'A', 'sds_dosya_yolu' => 'x.pdf', 'sonraki_gozden_gecirme' => now()->subWeek()]);
        $this->firma->kimyasalUrunler()->create(['urun_adi' => 'B', 'sonraki_gozden_gecirme' => now()->addYear()]);

        $ozet = Livewire::test(KimyasalSicili::class)->set('firmaId', $this->firma->id)->instance()->ozet();

        $this->assertSame(2, $ozet['toplam']);
        $this->assertSame(1, $ozet['sds_var']);
        $this->assertSame(1, $ozet['sds_yok']);
        $this->assertSame(1, $ozet['gecikmis']);
    }

    public function test_afis_genel_ve_firmaya_ozel_yuklenir(): void
    {
        $sayfa = Livewire::test(KimyasalSicili::class)->set('firmaId', $this->firma->id);

        $sayfa->callAction('afisEkle', [
            'baslik' => 'GHS Piktogram Levhası', 'kategori' => 'kimyasal', 'firma_id' => '',
            'dosya' => File::create('ghs.png', 100, 'image/png'),
        ])->assertHasNoActionErrors();

        $sayfa->callAction('afisEkle', [
            'baslik' => 'Depo Uyarı', 'kategori' => 'uyari_ikaz', 'firma_id' => $this->firma->id,
            'dosya' => File::create('depo.pdf', 100, 'application/pdf'),
        ])->assertHasNoActionErrors();

        $this->assertSame(2, IsgAfis::count());
        $this->assertNull(IsgAfis::where('baslik', 'GHS Piktogram Levhası')->sole()->firma_id);
        $this->assertSame($this->firma->id, IsgAfis::where('baslik', 'Depo Uyarı')->sole()->firma_id);
    }

    public function test_envanter_pdf_uretilir(): void
    {
        $this->firma->kimyasalUrunler()->create(['urun_adi' => 'Sülfürik Asit', 'cas_no' => '7664-93-9', 'ghs' => ['ghs05'], 'fiziksel_hal' => 'sivi']);

        $yanit = KimyasalEnvanterUretici::pdf($this->firma);
        $this->assertInstanceOf(StreamedResponse::class, $yanit);
        ob_start();
        $yanit->sendContent();
        $this->assertStringStartsWith('%PDF', ob_get_clean());
    }

    public function test_baska_uzmanin_firmasi_listede_yok(): void
    {
        $baska = User::factory()->create();
        $baskaFirma = Firma::factory()->for($baska)->create();

        $firmalar = Livewire::test(KimyasalSicili::class)->instance()->firmalar();
        $this->assertArrayNotHasKey($baskaFirma->id, $firmalar);
    }
    public function test_kutuphaneden_firmaya_kimyasal_eklenir_ve_mukerrer_atlanir(): void
    {
        $aseton = KimyasalKutuphane::create(['urun_adi' => 'Aseton', 'cas_no' => '67-64-1', 'ghs' => ['ghs02', 'ghs07']]);
        $etanol = KimyasalKutuphane::create(['urun_adi' => 'Etanol', 'cas_no' => '64-17-5']);
        KimyasalUrun::create(['firma_id' => $this->firma->id, 'urun_adi' => 'aseton']);

        Livewire::test(KimyasalSicili::class)
            ->set('firmaId', $this->firma->id)
            ->set('secilenKutuphane', [$aseton->id, $etanol->id])
            ->call('kutuphanedenEkle')
            ->assertSet('secilenKutuphane', []);

        $this->assertSame(['aseton', 'Etanol'], $this->firma->kimyasalUrunler()->orderBy('id')->pluck('urun_adi')->all());
        $this->assertSame('64-17-5', $this->firma->kimyasalUrunler()->where('urun_adi', 'Etanol')->value('cas_no'));
    }

    public function test_baska_uzmanin_kutuphane_kaydi_gorunmez_ve_silinemez(): void
    {
        $baska = User::factory()->create();
        $gizli = KimyasalKutuphane::create(['urun_adi' => 'Gizli', 'user_id' => $baska->id]);
        $hazir = KimyasalKutuphane::create(['urun_adi' => 'Hazır madde']);

        Livewire::test(KimyasalSicili::class)
            ->set('firmaId', $this->firma->id)
            ->set('secilenKutuphane', [$gizli->id])
            ->call('kutuphanedenEkle')
            ->call('kutuphaneKayitSil', $gizli->id)
            ->call('kutuphaneKayitSil', $hazir->id);

        $this->assertSame(0, $this->firma->kimyasalUrunler()->count());
        $this->assertDatabaseHas('kimyasal_kutuphanesi', ['id' => $gizli->id]);
        $this->assertDatabaseHas('kimyasal_kutuphanesi', ['id' => $hazir->id]);
    }

    public function test_kutuphaneye_kimyasal_eklenir_ve_urun_kutuphaneye_kaydedilir(): void
    {
        $urun = KimyasalUrun::create(['firma_id' => $this->firma->id, 'urun_adi' => 'Tiner', 'ghs' => ['ghs02']]);

        Livewire::test(KimyasalSicili::class)
            ->callAction('kutuphaneEkle', ['urun_adi' => 'Boya', 'ghs' => ['ghs07']])
            ->assertHasNoActionErrors()
            ->set('firmaId', $this->firma->id)
            ->call('kutuphaneyeKaydet', $urun->id);

        $this->assertSame(2, KimyasalKutuphane::where('user_id', $this->uzman->id)->count());
        $this->assertSame(['ghs02'], KimyasalKutuphane::where('urun_adi', 'Tiner')->value('ghs'));
    }

    public function test_hazir_kutuphane_seeder_idempotent(): void
    {
        $this->seed(\Database\Seeders\KimyasalKutuphanesiSeeder::class);
        $ilk = KimyasalKutuphane::whereNull('user_id')->count();
        $this->seed(\Database\Seeders\KimyasalKutuphanesiSeeder::class);

        $this->assertGreaterThan(20, $ilk);
        $this->assertSame($ilk, KimyasalKutuphane::whereNull('user_id')->count());
    }
}