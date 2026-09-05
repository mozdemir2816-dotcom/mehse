<?php

namespace Tests\Feature;

use App\Filament\Pages\Profilim;
use App\Models\Firma;
use App\Models\User;
use App\Models\ZiyaretProgrami;
use App\Support\ZiyaretTakvimi;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class FirmaZiyaretleriTest extends TestCase
{
    use RefreshDatabase;

    private User $uzman;

    protected function setUp(): void
    {
        parent::setUp();
        $this->uzman = User::factory()->create();
        $this->actingAs($this->uzman);
    }

    private function ziyaretEkle(Firma $firma, int $ayIndex, string $tarih, ?string $amac = null): void
    {
        $program = ZiyaretProgrami::firmaYilIcin($firma, (int) substr($tarih, 0, 4));
        $ziyaretler = $program->ziyaretler;
        $ziyaretler[$ayIndex] = ['tarih' => $tarih, 'amac' => $amac, 'durum' => 'tamamlandi', 'sure_saat' => 2, 'notlar' => null];
        $program->update(['ziyaretler' => $ziyaretler]);
    }

    public function test_tarihli_ay_satirlari_gune_gore_gruplanir(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create(['unvan' => 'A Firma']);
        $this->ziyaretEkle($firma, 0, '2026-01-15', 'Genel Saha Gözetimi');

        $gruplar = ZiyaretTakvimi::gunlukGruplar($this->uzman->id);

        $this->assertArrayHasKey('2026-01-15', $gruplar);
        $this->assertCount(1, $gruplar['2026-01-15']);
        $this->assertSame('A Firma', $gruplar['2026-01-15'][0]['firma']->unvan);
        $this->assertSame('Genel Saha Gözetimi', $gruplar['2026-01-15'][0]['amac']);
    }

    public function test_ayni_gunde_farkli_firmalar_birlikte_gorunur(): void
    {
        $a = Firma::factory()->for($this->uzman)->create(['unvan' => 'A Firma']);
        $b = Firma::factory()->for($this->uzman)->create(['unvan' => 'B Firma']);
        $this->ziyaretEkle($a, 0, '2026-03-10');
        $this->ziyaretEkle($b, 1, '2026-03-10');

        $gruplar = ZiyaretTakvimi::gunlukGruplar($this->uzman->id);

        $this->assertCount(2, $gruplar['2026-03-10']);
    }

    public function test_ozet_ziyaret_ve_firma_sayisini_dogru_hesaplar(): void
    {
        $a = Firma::factory()->for($this->uzman)->create();
        $b = Firma::factory()->for($this->uzman)->create();
        $this->ziyaretEkle($a, 0, '2026-01-05');
        $this->ziyaretEkle($a, 1, '2026-02-05');
        $this->ziyaretEkle($b, 0, '2026-01-06');

        $ozet = ZiyaretTakvimi::ozet($this->uzman->id);

        $this->assertSame(3, $ozet['ziyaret']);
        $this->assertSame(2, $ozet['firma']);
    }

    public function test_baskasinin_ziyaretleri_gorunmez(): void
    {
        $baskasi = User::factory()->create();
        $baskasininFirmasi = Firma::factory()->for($baskasi)->create();
        $this->ziyaretEkle($baskasininFirmasi, 0, '2026-01-05');

        $ozet = ZiyaretTakvimi::ozet($this->uzman->id);

        $this->assertSame(0, $ozet['ziyaret']);
    }

    public function test_tarih_secilince_o_gunun_ziyaretleri_dondurulur(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create(['unvan' => 'A Firma']);
        $this->ziyaretEkle($firma, 4, '2026-05-20', 'Eğitim');

        $liste = Livewire::test(Profilim::class)
            ->call('ziyaretTarihSec', '2026-05-20')
            ->instance()
            ->ziyaretlerGunluk();

        $this->assertCount(1, $liste);
        $this->assertSame('Eğitim', $liste[0]['amac']);
    }

    public function test_ay_degistir_gosterilen_ayi_ileri_geri_alir(): void
    {
        $test = Livewire::test(Profilim::class)->assertSet('ziyaretGosterilenAy', now()->format('Y-m'));

        $test->call('ziyaretAyDegistir', 1)
            ->assertSet('ziyaretGosterilenAy', now()->addMonth()->format('Y-m'))
            ->call('ziyaretAyDegistir', -1)
            ->assertSet('ziyaretGosterilenAy', now()->format('Y-m'));
    }
}
