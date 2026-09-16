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

    public function test_liste_sayfasi_acilir_ve_dort_varsayilanla_gelir(): void
    {
        Livewire::test(ListRiskProsedurs::class)
            ->assertOk()
            ->assertCountTableRecords(4);
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
        // diğer 3 yöntemin varsayılanı etkilenmedi.
        $this->assertSame(4, RiskProsedur::where('user_id', $this->uzman->id)->count());
    }

    public function test_silinen_yontem_icin_yeniden_yukleme_geri_getirip_gunceller(): void
    {
        RiskProsedur::varsayilanlariSeedEt($this->uzman->id);
        $matris = RiskProsedur::where('user_id', $this->uzman->id)->where('yontem', 'matris_5x5')->firstOrFail();
        $matrisId = $matris->id;

        Livewire::test(ListRiskProsedurs::class)->callTableAction('delete', $matris);
        $this->assertNotNull($matris->fresh()->deleted_at);

        $phpWord = new PhpWord();
        $section = $phpWord->addSection();
        $section->addText('YENİDEN YÜKLENDİ');
        $yol = tempnam(sys_get_temp_dir(), 'docx').'.docx';
        IOFactory::createWriter($phpWord, 'Word2007')->save($yol);

        // unique(user_id, yontem) çakışmasına düşmeden — soft-delete'i görüp geri getirmeli.
        Livewire::test(ListRiskProsedurs::class)
            ->callTableAction('yukle', data: [
                'yontem' => 'matris_5x5',
                'dosya' => UploadedFile::fake()->createWithContent('yeni.docx', file_get_contents($yol)),
            ])
            ->assertHasNoTableActionErrors();

        unlink($yol);

        $guncel = RiskProsedur::where('user_id', $this->uzman->id)->where('yontem', 'matris_5x5')->firstOrFail();
        $this->assertSame($matrisId, $guncel->id);
        $this->assertNull($guncel->deleted_at);
        $this->assertSame('YENİDEN YÜKLENDİ', $guncel->icerik[0]['metin']);
    }

    public function test_silinen_prosedur_yeniden_seed_edilmez(): void
    {
        RiskProsedur::varsayilanlariSeedEt($this->uzman->id);
        $matris = RiskProsedur::where('user_id', $this->uzman->id)->where('yontem', 'matris_5x5')->firstOrFail();

        Livewire::test(ListRiskProsedurs::class)
            ->callTableAction('delete', $matris);

        // Soft delete: satır fiziksel olarak durur (deleted_at dolar), aktif listede görünmez.
        $this->assertNotNull($matris->fresh()->deleted_at);
        $this->assertSame(3, RiskProsedur::where('user_id', $this->uzman->id)->count());

        // Asıl regresyon testi: seed tekrar çalıştırılsa (ör. sayfa yeniden açılsa)
        // bilerek silinen matris_5x5 "hiç var olmamış" sanılıp diriltilmemeli.
        RiskProsedur::varsayilanlariSeedEt($this->uzman->id);
        $this->assertSame(3, RiskProsedur::where('user_id', $this->uzman->id)->count());
    }

    public function test_baska_kullanicinin_prosedurlerini_gormez(): void
    {
        $baskasi = User::factory()->create();
        RiskProsedur::varsayilanlariSeedEt($baskasi->id);

        Livewire::test(ListRiskProsedurs::class)->assertCountTableRecords(4);

        $this->assertSame(4, RiskProsedur::where('user_id', $baskasi->id)->count());
        $this->assertSame(4, RiskProsedur::where('user_id', $this->uzman->id)->count());
    }
}
