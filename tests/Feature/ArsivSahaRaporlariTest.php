<?php

namespace Tests\Feature;

use App\Filament\Pages\DokumanYonetimi;
use App\Models\Firma;
use App\Models\SahaAnalizi;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

/** Arşiv → Saha Gözlem Raporları: firmanın sistemdeki tüm raporları, tek PDF ve toplu ZIP (13.11.2026). */
class ArsivSahaRaporlariTest extends TestCase
{
    use RefreshDatabase;

    private User $uzman;

    private Firma $firma;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        $this->uzman = User::factory()->create();
        $this->actingAs($this->uzman);
        $this->firma = Firma::factory()->for($this->uzman)->create(['unvan' => 'Ahmet Yapı']);
    }

    private function rapor(string $tarih, string $durum, int $adet = 1): SahaAnalizi
    {
        return SahaAnalizi::create([
            'firma_id' => $this->firma->id,
            'durum' => $durum,
            'rapor_tarihi' => $tarih,
            'bulgular' => array_fill(0, $adet, ['tespit' => 'Korkuluk yok', 'oneriler' => ['Korkuluk takılmalı'], 'olasilik' => 6, 'frekans' => 6, 'siddet' => 15]),
        ]);
    }

    public function test_tum_raporlar_tarih_sirasiyla_listelenir(): void
    {
        $eski = $this->rapor('2026-11-04', SahaAnalizi::TAMAMLANDI, 4);
        $yeni = $this->rapor('2026-11-13', SahaAnalizi::TASLAK, 2);

        $sayfa = Livewire::test(DokumanYonetimi::class)->set('firmaId', $this->firma->id)->call('kategoriAc', 'saha_gozlem');

        $this->assertSame([$yeni->id, $eski->id], $sayfa->instance()->sahaRaporlari->pluck('id')->all());
        $sayfa->assertSee('Sistemde Hazırlanan Raporlar (2)')->assertSee('13.11.2026')->assertSee('04.11.2026')
            ->assertSee('4 bulgu')->assertSee('Taslak')->assertSee('Tümünü İndir (ZIP)');
    }

    public function test_baska_kategoride_ve_firmasiz_liste_bos(): void
    {
        $this->rapor('2026-11-13', SahaAnalizi::TAMAMLANDI);

        $this->assertTrue(Livewire::test(DokumanYonetimi::class)->set('firmaId', $this->firma->id)->call('kategoriAc', 'tatbikat')->instance()->sahaRaporlari->isEmpty());
        Livewire::test(DokumanYonetimi::class)->call('kategoriAc', 'saha_gozlem')->assertDontSee('Sistemde Hazırlanan Raporlar');
    }

    public function test_tek_pdf_ve_toplu_zip_indirilir(): void
    {
        $a = $this->rapor('2026-11-04', SahaAnalizi::TAMAMLANDI);
        $this->rapor('2026-11-13', SahaAnalizi::TASLAK);

        $sayfa = Livewire::test(DokumanYonetimi::class)->set('firmaId', $this->firma->id)->call('kategoriAc', 'saha_gozlem');
        $sayfa->call('sahaRaporPdf', $a->id)->assertFileDownloaded();

        $sonuc = $sayfa->instance()->sahaRaporlariZip();
        ob_start();
        $sonuc->sendContent();
        $icerik = ob_get_clean();
        $tmp = tempnam(sys_get_temp_dir(), 'z');
        file_put_contents($tmp, $icerik);
        $zip = new \ZipArchive;
        $this->assertTrue($zip->open($tmp));
        $adlar = collect(range(0, $zip->numFiles - 1))->map(fn ($i) => $zip->getNameIndex($i))->all();
        $this->assertCount(2, $adlar);
        $this->assertStringStartsWith('2026-11-04_saha-', $adlar[0]);
        $this->assertStringEndsWith('_taslak.pdf', $adlar[1]);
        $this->assertStringStartsWith('%PDF', $zip->getFromIndex(0));
        $zip->close();
        @unlink($tmp);
    }

    public function test_baska_uzmanin_raporu_indirilemez(): void
    {
        $baska = Firma::factory()->for(User::factory()->create())->create();
        $gizli = SahaAnalizi::create(['firma_id' => $baska->id, 'bulgular' => [['tespit' => 'Gizli']]]);

        // URL ile yabancı firma kimliği verilse bile (görünüm çizilmeden) yöntemler boş döner.
        $sayfa = new DokumanYonetimi;
        $sayfa->firmaId = $baska->id;
        $sayfa->kategoriAnahtari = 'saha_gozlem';
        $this->assertTrue($sayfa->sahaRaporlari()->isEmpty());
        $this->assertNull($sayfa->sahaRaporPdf($gizli->id));
        $this->assertNull($sayfa->sahaRaporlariZip());

        $kendi = Livewire::test(DokumanYonetimi::class)->set('firmaId', $this->firma->id)->call('kategoriAc', 'saha_gozlem');
        $this->assertNull($kendi->instance()->sahaRaporPdf($gizli->id));
    }
}
