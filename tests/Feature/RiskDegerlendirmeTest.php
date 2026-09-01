<?php

namespace Tests\Feature;

use App\Filament\Resources\RiskDegerlendirmesis\Pages\CreateRiskDegerlendirmesi;
use App\Filament\Resources\RiskDegerlendirmesis\Pages\EditRiskDegerlendirmesi;
use App\Filament\Resources\RiskDegerlendirmesis\Pages\ListRiskDegerlendirmesis;
use App\Filament\Resources\Tehlikes\Pages\ListTehlikes;
use App\Models\Firma;
use App\Models\RiskDegerlendirmesi;
use App\Models\RiskMaddesi;
use App\Models\User;
use App\Support\RiskDegerlendirmesiUretici;
use App\Support\RiskSkorlama;
use Database\Seeders\TehlikeKutuphanesiSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Tests\TestCase;

class RiskDegerlendirmeTest extends TestCase
{
    use RefreshDatabase;

    private User $uzman;

    protected function setUp(): void
    {
        parent::setUp();
        $this->uzman = User::factory()->create();
        $this->actingAs($this->uzman);
    }

    public function test_5x5_ve_fine_kinney_puan_duzeyi(): void
    {
        $m5 = RiskSkorlama::hesapla('matris_5x5', 4, 4);
        $this->assertSame(16, $m5['puan']);
        $this->assertSame('Yüksek Risk', $m5['duzey']);

        $fk = RiskSkorlama::hesapla('fine_kinney', 6, 6, 15);
        $this->assertSame(540.0, $fk['puan']);
        $this->assertSame('Tolerans Gösterilemez Risk', $fk['duzey']);

        $bos = RiskSkorlama::hesapla('matris_5x5', null, 3);
        $this->assertSame(0, $bos['puan']);
    }

    public function test_belge_no_ve_gecerlilik_otomatik(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create(['tehlike_sinifi' => 'cok_tehlikeli']);

        $rd = RiskDegerlendirmesi::create([
            'firma_id' => $firma->id,
            'yontem' => 'matris_5x5',
            'rapor_tarihi' => '2026-01-01',
        ]);

        $this->assertStringStartsWith('RD-'.now()->year.'-', $rd->belge_no);
        $this->assertSame('2028-01-01', $rd->gecerlilik_tarihi->toDateString()); // +2 yıl (çok tehlikeli)
        $this->assertSame($firma->unvan, $rd->firma_unvan);
    }

    public function test_risk_maddesi_saving_puan_hesaplar(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();
        $rd = RiskDegerlendirmesi::create(['firma_id' => $firma->id, 'yontem' => 'matris_5x5', 'rapor_tarihi' => now()]);

        $m = $rd->maddeler()->create([
            'tehlike' => 'Korkuluksuz kenar', 'olasilik' => 3, 'siddet' => 5,
            'son_olasilik' => 1, 'son_siddet' => 5,
        ]);

        $this->assertSame(15.0, $m->puan);
        $this->assertSame('Yüksek Risk', $m->duzey);
        $this->assertSame(5.0, $m->son_puan);
        $this->assertSame('Katlanılabilir Risk', $m->son_duzey);
    }

    public function test_sayfalar_acilir_ve_kutuphane_seed(): void
    {
        $this->seed(TehlikeKutuphanesiSeeder::class);
        $firma = Firma::factory()->for($this->uzman)->create();
        $rd = RiskDegerlendirmesi::create(['firma_id' => $firma->id, 'yontem' => 'matris_5x5', 'rapor_tarihi' => now()]);

        Livewire::test(ListRiskDegerlendirmesis::class)->assertOk();
        Livewire::test(CreateRiskDegerlendirmesi::class)->assertOk();
        Livewire::test(EditRiskDegerlendirmesi::class, ['record' => $rd->getRouteKey()])->assertOk();
        Livewire::test(ListTehlikes::class)->assertOk()->assertCountTableRecords(13);
    }

    public function test_pdf_matris_5x5_uretilir(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create(['tehlike_sinifi' => 'tehlikeli']);
        $rd = RiskDegerlendirmesi::create([
            'firma_id' => $firma->id, 'yontem' => 'matris_5x5', 'rapor_tarihi' => now(),
            'ekip' => [['ad' => 'Ali Veli', 'unvan' => 'İşveren']],
        ]);
        $rd->maddeler()->create(['bolum' => 'Şantiye', 'tehlike' => 'Korkuluksuz kenar', 'olasilik' => 3, 'siddet' => 5]);

        $yanit = RiskDegerlendirmesiUretici::pdf($rd);

        $this->assertInstanceOf(StreamedResponse::class, $yanit);
        ob_start();
        $yanit->sendContent();
        $icerik = ob_get_clean();
        $this->assertStringStartsWith('%PDF', $icerik);
    }

    public function test_pdf_fine_kinney_uretilir(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();
        $rd = RiskDegerlendirmesi::create(['firma_id' => $firma->id, 'yontem' => 'fine_kinney', 'rapor_tarihi' => now()]);
        $rd->maddeler()->create(['tehlike' => 'Gürültü', 'olasilik' => 6, 'frekans' => 6, 'siddet' => 15]);

        $yanit = RiskDegerlendirmesiUretici::pdf($rd);

        ob_start();
        $yanit->sendContent();
        $icerik = ob_get_clean();
        $this->assertStringStartsWith('%PDF', $icerik);
    }

    public function test_edit_sayfasinda_pdf_aksiyonu_calisir(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();
        $rd = RiskDegerlendirmesi::create(['firma_id' => $firma->id, 'yontem' => 'matris_5x5', 'rapor_tarihi' => now()]);
        $rd->maddeler()->create(['tehlike' => 'Test tehlike', 'olasilik' => 2, 'siddet' => 2]);

        Livewire::test(EditRiskDegerlendirmesi::class, ['record' => $rd->getRouteKey()])
            ->assertOk()
            ->callAction('pdf');
    }

    public function test_uzman_baskasinin_risk_degerlendirmesini_gormez(): void
    {
        $benim = RiskDegerlendirmesi::create([
            'firma_id' => Firma::factory()->for($this->uzman)->create()->id,
            'yontem' => 'matris_5x5', 'rapor_tarihi' => now(),
        ]);
        RiskDegerlendirmesi::create([
            'firma_id' => Firma::factory()->create()->id,
            'yontem' => 'matris_5x5', 'rapor_tarihi' => now(),
        ]);

        Livewire::test(ListRiskDegerlendirmesis::class)
            ->assertCanSeeTableRecords([$benim])
            ->assertCountTableRecords(1);
    }
}
