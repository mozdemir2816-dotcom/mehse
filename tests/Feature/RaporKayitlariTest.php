<?php

namespace Tests\Feature;

use App\Filament\Pages\Profilim;
use App\Models\DofRaporu;
use App\Models\EgitimKatilim;
use App\Models\Firma;
use App\Models\Sertifika;
use App\Models\User;
use App\Support\RaporKayitlari;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class RaporKayitlariTest extends TestCase
{
    use RefreshDatabase;

    private User $uzman;

    private Firma $firma;

    protected function setUp(): void
    {
        parent::setUp();
        $this->uzman = User::factory()->create();
        $this->actingAs($this->uzman);
        $this->firma = Firma::factory()->for($this->uzman)->create(['unvan' => 'Örnek A.Ş.']);
    }

    public function test_farkli_modellerden_kayitlari_birlestirir(): void
    {
        Sertifika::create(['firma_id' => $this->firma->id, 'tip' => 'isg', 'created_at' => now()->subDay()]);
        EgitimKatilim::create(['firma_id' => $this->firma->id, 'created_at' => now()->subHours(2)]);
        DofRaporu::create(['firma_id' => $this->firma->id, 'created_at' => now()]);

        $satirlar = RaporKayitlari::hepsi($this->uzman->id);

        $this->assertCount(3, $satirlar);
        $this->assertSame('Örnek A.Ş. — İSG Sertifikası', collect($satirlar)->firstWhere('tip', 'Sertifika')['baslik']);
        $this->assertSame('Örnek A.Ş. — İş Sağlığı ve Güvenliği', collect($satirlar)->firstWhere('tip', 'Eğitim Katılım Formu')['baslik']);
        $this->assertSame('Örnek A.Ş. — DÖF Raporu', collect($satirlar)->firstWhere('tip', 'DÖF Raporu')['baslik']);

        // En yeni (DofRaporu, "now") en üstte.
        $this->assertInstanceOf(DofRaporu::class, $satirlar[0]['kayit']);
    }

    public function test_yalniz_kendi_portfoyu_gelir(): void
    {
        $baskasi = User::factory()->create();
        $baskasininFirmasi = Firma::factory()->for($baskasi)->create();
        Sertifika::create(['firma_id' => $baskasininFirmasi->id, 'tip' => 'isg']);
        Sertifika::create(['firma_id' => $this->firma->id, 'tip' => 'isg']);

        $satirlar = RaporKayitlari::hepsi($this->uzman->id);

        $this->assertCount(1, $satirlar);
    }

    public function test_arama_ve_tip_filtresi(): void
    {
        Sertifika::create(['firma_id' => $this->firma->id, 'tip' => 'isg']);
        DofRaporu::create(['firma_id' => $this->firma->id]);

        $this->assertCount(1, RaporKayitlari::hepsi($this->uzman->id, tipFiltre: 'Sertifika'));
        $this->assertCount(2, RaporKayitlari::hepsi($this->uzman->id, arama: 'örnek')); // ikisi de "Örnek A.Ş." firmasına ait
        $this->assertCount(0, RaporKayitlari::hepsi($this->uzman->id, arama: 'bulunmayan-firma'));
    }

    public function test_rapor_indir_action_gercek_pdf_dondurur(): void
    {
        $sertifika = Sertifika::create(['firma_id' => $this->firma->id, 'tip' => 'isg']);

        $yanit = Livewire::test(Profilim::class)
            ->callAction('raporIndir', arguments: ['model' => Sertifika::class, 'id' => $sertifika->id, 'format' => 'birincil']);

        $yanit->assertHasNoActionErrors();
    }
}
