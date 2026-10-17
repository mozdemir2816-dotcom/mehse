<?php

namespace Tests\Feature;

use App\Filament\Pages\RiskSihirbazi;
use App\Models\User;
use App\Support\EkipmanRiskKutuphanesi;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Livewire\Livewire;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class HazopSihirbaziTest extends TestCase
{
    use RefreshDatabase;

    private function hazopXlsx(): string
    {
        $kitap = new Spreadsheet;
        $s = $kitap->getActiveSheet()->setTitle('HAZOP');
        $s->fromArray(['No', 'Düğüm', 'Parametre', 'Kılavuz kelime', 'Sapma', 'Olası nedenler', 'Olası sonuçlar', 'Doğrulanacak korumalar', 'Öneri / aksiyon', 'Öncelik', 'Sorumlu rol', 'Hedef tarih', 'Kapanış kanıtı', 'Durum'], null, 'A6');
        $s->fromArray([
            [1, 'N1 — Silo dolumu', 'Basınç', 'FAZLA', 'Silo basıncı tasarım sınırını aşar', 'Filtre tıkanması', 'Gövde hasarı', 'Basınç alarmı', 'Tahliye kapasitesini doğrulayın.', 'P1', 'Üretim', 'Atanacak', '', 'Açık'],
        ], null, 'A7');
        $yol = sys_get_temp_dir().DIRECTORY_SEPARATOR.uniqid().'-Siva_HAZOP.xlsx';
        (new Xlsx($kitap))->save($yol);

        return $yol;
    }

    public function test_hazop_excel_yuklenince_yontem_hazop_olur_ve_puan_uretilmez(): void
    {
        $this->actingAs(User::factory()->create());

        Livewire::test(RiskSihirbazi::class)
            ->set('yontem', 'matris_5x5')
            ->set('excelDosya', UploadedFile::fake()->createWithContent('Siva_HAZOP.xlsx', file_get_contents($this->hazopXlsx())))
            ->call('excelIceAktar')
            ->assertSet('yontem', 'hazop')
            ->assertCount('excelAdaylar', 1)
            ->tap(function ($bilesen): void {
                $aday = $bilesen->get('excelAdaylar')[0];

                $this->assertNull($aday['olasilik']);
                $this->assertNull($aday['siddet']);
                $this->assertSame('Basınç', $aday['hazop']['parametre']);
                $this->assertSame('N1 — Silo dolumu', $aday['hazop']['dugum']);
            });
    }

    public function test_hazop_dosyasi_hazop_olarak_algilanir(): void
    {
        $this->assertTrue(EkipmanRiskKutuphanesi::hazopMi($this->hazopXlsx()));
    }
}
