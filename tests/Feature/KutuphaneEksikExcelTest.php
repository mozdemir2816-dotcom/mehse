<?php

namespace Tests\Feature;

use App\Filament\Resources\Tehlikes\Pages\ListTehlikes;
use App\Models\Tehlike;
use App\Models\TehlikeKategorisi;
use App\Models\User;
use App\Support\KutuphaneEksikExcel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

/** 12.11.2026: kütüphane eksiklerini Excel ile indirip (Gemini sohbetinde doldurulmuş) geri yükleme. */
class KutuphaneEksikExcelTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(User::factory()->create(['rol' => 'sahip']));
    }

    private function madde(array $alanlar = [], ?string $yontem = null): Tehlike
    {
        $k = TehlikeKategorisi::firstOrCreate(['anahtar' => 'kat_'.($yontem ?? 'fk')], ['ad' => 'Atölye', 'yontem' => $yontem, 'sira' => 1]);

        return Tehlike::create(['tehlike_kategorisi_id' => $k->id, 'kod' => 'T-'.uniqid(), 'tehlike' => 'Taşlama taşının kırılması'] + $alanlar);
    }

    public function test_eksikler_indirilir_doldurulan_excel_yalniz_bos_alanlari_yazar(): void
    {
        Http::fake();
        $tam = ['risk' => 'Göz yaralanması', 'mevcut_onlem' => 'Koruyucu var', 'oneri' => 'Siper takılmalı', 'mevzuat' => 'İş Ekipmanları Yön.',
            'sorumlu' => 'Ustabaşı', 'olasilik' => 3, 'frekans' => 6, 'siddet' => 15, 'son_olasilik' => 1, 'son_frekans' => 6, 'son_siddet' => 15, 'termin' => '1 ay'];
        $bos = $this->madde(['oneri' => 'Uzmanın önerisi', 'mevcut_onlem' => null]);
        $this->madde(['tehlike' => 'Tam madde'] + $tam);                       // eksiği yok
        $yalnizOnlem = $this->madde(['tehlike' => 'Önlemi boş'] + array_merge($tam, ['mevcut_onlem' => null]));
        $hazop = $this->madde(['tehlike' => 'HAZOP senaryosu'], 'hazop');

        // Mevcut önlem eksik sayılmaz, HAZOP kapsam dışı.
        $this->assertSame([$bos->id], KutuphaneEksikExcel::eksikSorgu()->pluck('id')->all());
        $this->assertStringContainsString('0,5 / 1 / 2 / 3 / 6 / 10', KutuphaneEksikExcel::istem(1));

        Livewire::test(ListTehlikes::class)
            ->callAction('eksikExcelIndir', ['boyut' => 100, 'parca' => 1])
            ->assertFileDownloaded('kutuphane-eksik-parca-1-1.xlsx');

        // Gemini'nin doldurduğu tablo: puanlar ölçek dışı / virgüllü, dolu öneri değiştirilmeye çalışılmış.
        $kitap = new Spreadsheet;
        $kitap->getActiveSheet()->fromArray([
            KutuphaneEksikExcel::BASLIKLAR,
            [$bos->id, 'Atölye', 'Taşlama taşının kırılması', 'Kesik, göz yaralanması', 'Gemini önerisi', 'İş Ekipmanları Yönetmeliği', 'Bakım Sorumlusu', '2,8', 6, 16, '0,5', 6, 15],
            [999999, 'Yok', 'Silinmiş', 'x', 'x', 'x', 'x', 1, 1, 1, 1, 1, 1],
        ]);
        $yol = tempnam(sys_get_temp_dir(), 'kx').'.xlsx';
        (new Xlsx($kitap))->save($yol);

        Livewire::test(ListTehlikes::class)
            ->callAction('eksikExcelYukle', ['dosya' => UploadedFile::fake()->createWithContent('dolu.xlsx', file_get_contents($yol))])
            ->assertHasNoActionErrors()
            ->assertNotified('1 kütüphane maddesi güncellendi (10 alan)');

        $bos->refresh();
        $this->assertSame('Kesik, göz yaralanması', $bos->risk);
        $this->assertSame('Uzmanın önerisi', $bos->oneri);              // dolu alan korunur
        $this->assertSame('İş Ekipmanları Yönetmeliği', $bos->mevzuat);
        $this->assertSame('Bakım Sorumlusu', $bos->sorumlu);
        $this->assertSame(3.0, (float) $bos->olasilik);                  // 2,8 → ölçekte 3
        $this->assertSame(15.0, (float) $bos->siddet);                   // 16 → 15
        $this->assertSame(0.5, (float) $bos->son_olasilik);
        $this->assertNotNull($bos->termin);                              // risk düzeyinden hesaplandı
        $this->assertNull($bos->mevcut_onlem);                           // mevcut önleme dokunulmaz
        $this->assertNull($yalnizOnlem->fresh()->mevcut_onlem);
        $this->assertNull($hazop->fresh()->oneri);
        $this->assertSame(0, KutuphaneEksikExcel::eksikSorgu()->count());
        Http::assertNothingSent();
    }
}
