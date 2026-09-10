<?php

namespace Tests\Feature;

use App\Filament\Pages\KkdSecimMatrisi as KkdSecimMatrisiSayfasi;
use App\Models\Firma;
use App\Models\KkdMatrisi;
use App\Models\User;
use App\Support\KkdMatrisiUretici;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class KkdSecimMatrisiTest extends TestCase
{
    use RefreshDatabase;

    private User $uzman;

    private Firma $firma;

    protected function setUp(): void
    {
        parent::setUp();
        $this->uzman = User::factory()->create();
        $this->actingAs($this->uzman);
        $this->firma = Firma::factory()->for($this->uzman)->create();
    }

    public function test_katalogdan_is_kalemi_varsayilan_kkd_ile_eklenir(): void
    {
        Livewire::test(KkdSecimMatrisiSayfasi::class)
            ->set('firmaId', $this->firma->id)
            ->call('katalogdanEkle', 'İnşaat / Şantiye', 'Yüksekte Çalışma / İskele')
            ->call('kaydet');

        $matris = KkdMatrisi::where('firma_id', $this->firma->id)->sole();
        $this->assertCount(1, $matris->satirlar);
        $this->assertSame('Yüksekte Çalışma / İskele', $matris->satirlar[0]['is_kalemi']);
        $this->assertStringContainsString('EN 361', $matris->satirlar[0]['kemer']);
    }

    public function test_serbest_satir_eklenir_ve_bos_is_kalemi_saving_ile_temizlenir(): void
    {
        Livewire::test(KkdSecimMatrisiSayfasi::class)
            ->set('firmaId', $this->firma->id)
            ->set('yeniIsKalemi', 'Vinç ile yük kaldırma')
            ->call('serbestEkle')
            ->call('kaydet');

        $matris = KkdMatrisi::where('firma_id', $this->firma->id)->sole();
        $this->assertSame('Vinç ile yük kaldırma', $matris->satirlar[0]['is_kalemi']);

        // Boş iş kalemi satırı kaydedilmez.
        $matris->update(['satirlar' => [
            ['is_kalemi' => 'Geçerli', 'baret' => '✔'],
            ['is_kalemi' => '', 'baret' => '✔'],
        ]]);
        $this->assertCount(1, $matris->refresh()->satirlar);
    }

    public function test_pdf_uretilir(): void
    {
        $matris = KkdMatrisi::firmaIcin($this->firma);
        $matris->update(['satirlar' => [
            ['is_kalemi' => 'Kaynak İşleri', 'grup' => 'İmalat', 'gozluk' => 'Kaynak maskesi', 'eldiven' => 'EN 407', 'ayakkabi' => '✔ S3'],
        ]]);

        $yanit = KkdMatrisiUretici::pdf($matris);
        ob_start();
        $yanit->sendContent();
        $this->assertStringStartsWith('%PDF', ob_get_clean());
    }

    public function test_baska_uzmanin_firmasi_secilemez(): void
    {
        $baska = User::factory()->create();
        $baskaFirma = Firma::factory()->for($baska)->create();

        $firmalar = Livewire::test(KkdSecimMatrisiSayfasi::class)->instance()->firmalar();
        $this->assertArrayNotHasKey($baskaFirma->id, $firmalar);
    }
}
