<?php

namespace Tests\Feature;

use App\Filament\Resources\RiskProsedurs\Pages\ListRiskProsedurs;
use App\Models\RiskProsedur;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Livewire\Livewire;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;
use Tests\TestCase;

class RiskProsedurResourceTest extends TestCase
{
    use RefreshDatabase;

    private User $uzman;

    protected function setUp(): void
    {
        parent::setUp();
        $this->uzman = User::factory()->create();
        $this->actingAs($this->uzman);
    }

    public function test_liste_sayfasi_acilir_ve_iki_varsayilanla_gelir(): void
    {
        Livewire::test(ListRiskProsedurs::class)
            ->assertOk()
            ->assertCountTableRecords(2);
    }

    public function test_yukle_action_secilen_yontemin_prosedurunu_degistirir(): void
    {
        $phpWord = new PhpWord();
        $section = $phpWord->addSection();
        $section->addText('YENİ BAŞLIK');
        $section->addText('Kullanıcının kendi yüklediği özel prosedür metni.');

        $yol = tempnam(sys_get_temp_dir(), 'docx').'.docx';
        IOFactory::createWriter($phpWord, 'Word2007')->save($yol);

        Livewire::test(ListRiskProsedurs::class)
            ->callTableAction('yukle', data: [
                'yontem' => 'matris_5x5',
                'dosya' => UploadedFile::fake()->createWithContent('prosedur.docx', file_get_contents($yol)),
            ])
            ->assertHasNoTableActionErrors();

        unlink($yol);

        $prosedur = RiskProsedur::where('user_id', $this->uzman->id)->where('yontem', 'matris_5x5')->firstOrFail();
        $this->assertSame('prosedur.docx', $prosedur->dosya_adi);
        $this->assertSame('YENİ BAŞLIK', $prosedur->icerik[0]['metin']);
        // fine_kinney'in varsayılanı etkilenmedi.
        $this->assertSame(2, RiskProsedur::where('user_id', $this->uzman->id)->count());
    }

    public function test_silinen_prosedur_yeniden_seed_edilmez(): void
    {
        RiskProsedur::varsayilanlariSeedEt($this->uzman->id);
        $matris = RiskProsedur::where('user_id', $this->uzman->id)->where('yontem', 'matris_5x5')->firstOrFail();

        Livewire::test(ListRiskProsedurs::class)
            ->callTableAction('delete', $matris);

        $this->assertDatabaseMissing('risk_prosedurleri', ['id' => $matris->id]);
        $this->assertSame(1, RiskProsedur::where('user_id', $this->uzman->id)->count());
    }

    public function test_baska_kullanicinin_prosedurlerini_gormez(): void
    {
        $baskasi = User::factory()->create();
        RiskProsedur::varsayilanlariSeedEt($baskasi->id);

        Livewire::test(ListRiskProsedurs::class)->assertCountTableRecords(2);

        $this->assertSame(2, RiskProsedur::where('user_id', $baskasi->id)->count());
        $this->assertSame(2, RiskProsedur::where('user_id', $this->uzman->id)->count());
    }
}
