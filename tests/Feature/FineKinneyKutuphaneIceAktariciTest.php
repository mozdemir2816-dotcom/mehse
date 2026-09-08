<?php

namespace Tests\Feature;

use App\Filament\Resources\Tehlikes\Pages\ListTehlikes;
use App\Models\Tehlike;
use App\Models\TehlikeKategorisi;
use App\Models\User;
use App\Support\FineKinneyKutuphaneIceAktarici;
use App\Support\RiskKutuphanesi;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Livewire\Livewire;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

/**
 * Sektörel Fine-Kinney risk analizi Excel'ini Risk Kütüphanesine aktarma —
 * "Faaliyet Alanı" kategori olur, O/F/Ş taşınır.
 */
class FineKinneyKutuphaneIceAktariciTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(User::factory()->create());
    }

    private function ornekExcel(): string
    {
        $kitap = new Spreadsheet;
        $s = $kitap->getActiveSheet();
        // Gerçek dosyadaki gibi: üstte lejant satırları, sonra başlık
        $s->fromArray(['İNŞAAT İSG RİSK VERİTABANI'], null, 'A1');
        $s->fromArray([
            'Sıra No', 'Faaliyet Alanı (Ana Kategori)', 'Alt Faaliyet / Bölüm', 'Tehlike Kaynağı',
            'Olası Risk & Sonuç', 'Riskten Etkilenenler', 'Olasılık', 'Frekans', 'Şiddet',
            'Alınması Gereken Önleyici ve Düzeltici Tedbirler', 'Yasal Mevzuat / Standart',
        ], null, 'A3');
        $s->fromArray([1, 'Kazı İşleri', 'Elle kazı', 'Göçük', 'Ezilme, ölüm', 'Çalışanlar', 3, 6, 15, 'İksa/şev hesabı yapılır.', 'Yapı İşleri Yön. Ek-4'], null, 'A4');
        $s->fromArray([2, 'Kazı İşleri', 'Makineyle kazı', 'Devrilme', 'Yaralanma', 'Operatör', 3, 3, 7, 'Manevracı bulundurulur.', 'İş Ekipmanları Yön.'], null, 'A5');
        $s->fromArray([3, 'Elektrik İşleri', 'Pano montajı', 'Elektrik çarpması', 'Ölüm', 'Çalışanlar', 6, 6, 15, 'Enerji kesilir, LOTO uygulanır.', 'Elektrik Tesisatı Yön.'], null, 'A6');

        $yol = tempnam(sys_get_temp_dir(), 'fk').'.xlsx';
        (new Xlsx($kitap))->save($yol);

        return $yol;
    }

    public function test_faaliyet_alani_kategori_olur_ve_ofs_yazilir(): void
    {
        $sonuc = FineKinneyKutuphaneIceAktarici::iceAktar($this->ornekExcel());

        $this->assertSame(3, $sonuc['basarili']);
        $this->assertSame(2, $sonuc['yeniKategori']); // Kazı İşleri + Elektrik İşleri

        $kazi = TehlikeKategorisi::where('ad', 'Kazı İşleri')->sole();
        $this->assertSame(2, $kazi->tehlikeler()->count());

        $gocuk = Tehlike::where('tehlike', 'Göçük')->sole();
        $this->assertSame('Elle kazı', $gocuk->faaliyet);
        $this->assertEqualsWithDelta(3.0, (float) $gocuk->olasilik, 0.01);
        $this->assertEqualsWithDelta(6.0, (float) $gocuk->frekans, 0.01);
        $this->assertEqualsWithDelta(15.0, (float) $gocuk->siddet, 0.01);
        $this->assertStringContainsString('İksa', $gocuk->mevcut_onlem);
    }

    public function test_ikinci_yukleme_ayni_maddeyi_gunceller_kopya_olusmaz(): void
    {
        FineKinneyKutuphaneIceAktarici::iceAktar($this->ornekExcel());
        FineKinneyKutuphaneIceAktarici::iceAktar($this->ornekExcel());

        $this->assertSame(3, Tehlike::count());
        $this->assertSame(2, TehlikeKategorisi::count());
    }

    public function test_maddeye_cevir_kutuphanedeki_ofs_degerini_tasir(): void
    {
        FineKinneyKutuphaneIceAktarici::iceAktar($this->ornekExcel());
        $t = Tehlike::where('tehlike', 'Elektrik çarpması')->sole();

        $madde = RiskKutuphanesi::maddeyeCevir($t);

        $this->assertSame(6.0, $madde['olasilik']);
        $this->assertSame(6.0, $madde['frekans']);
        $this->assertSame(15.0, $madde['siddet']);
        $this->assertSame($t->id, $madde['tehlike_id']);
    }

    public function test_list_sayfasindan_fine_kinney_yukleme_aksiyonu(): void
    {
        $dosya = UploadedFile::fake()->createWithContent('insaat-fk.xlsx', file_get_contents($this->ornekExcel()));

        Livewire::test(ListTehlikes::class)
            ->callAction('fineKinneyYukle', ['dosya' => $dosya]);

        $this->assertGreaterThan(0, Tehlike::whereNotNull('olasilik')->count());
    }
}
