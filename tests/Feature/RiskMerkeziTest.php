<?php

namespace Tests\Feature;

use App\Filament\Pages\RiskMerkezi;
use App\Models\Firma;
use App\Models\RiskDegerlendirmesi;
use App\Models\User;
use App\Support\RiskMerkeziVerisi;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class RiskMerkeziTest extends TestCase
{
    use RefreshDatabase;

    private User $uzman;

    private Firma $firma;

    protected function setUp(): void
    {
        parent::setUp();
        $this->uzman = User::factory()->create();
        $this->actingAs($this->uzman);
        $this->firma = Firma::factory()->for($this->uzman)->create([
            'unvan' => 'Ahmet İnşaat', 'nace_kodu' => '41.00.01', 'tehlike_sinifi' => 'cok_tehlikeli', 'sgk_sicil_no' => '123', 'adres' => 'Bursa',
        ]);
    }

    private function rd(): RiskDegerlendirmesi
    {
        $rd = RiskDegerlendirmesi::create(['firma_id' => $this->firma->id, 'yontem' => 'matris_5x5', 'rapor_tarihi' => now()]);
        $rd->maddeler()->create(['sira' => 1, 'bolum' => 'Şantiye', 'tehlike' => 'Yüksekten düşme', 'olasilik' => 5, 'siddet' => 5, 'oneri' => 'Korkuluk', 'sorumlu' => 'Şef', 'termin' => now()->subDays(3)->format('d.m.Y')]);
        $rd->maddeler()->create(['sira' => 2, 'bolum' => 'Şantiye', 'tehlike' => 'Elektrik', 'olasilik' => 4, 'siddet' => 4, 'oneri' => 'Kaçak akım rölesi', 'termin' => now()->addDays(10)->toDateString()]);
        $rd->maddeler()->create(['sira' => 3, 'bolum' => 'Depo', 'tehlike' => 'Takılma', 'olasilik' => 1, 'siddet' => 2, 'durum' => 'kapali']);
        $rd->maddeler()->create(['sira' => 4, 'tehlike' => 'Puansız tehlike']);

        return $rd;
    }

    public function test_ozet_gostergeler_ve_isi_haritasi(): void
    {
        $o = RiskMerkeziVerisi::ozet($this->rd());

        $this->assertSame(4, $o['toplam']);
        $this->assertSame(3, $o['acik']);
        $this->assertSame(1, $o['cok_yuksek_acik']);   // 25
        $this->assertSame(1, $o['yuksek_acik']);       // 16
        $this->assertSame(2, $o['aksiyon_acik']);
        $this->assertSame(1, $o['geciken']);           // d.m.Y termin geçmiş
        $this->assertSame(1, $o['puansiz']);
        $this->assertSame(1, $o['matris'][5][5]);
        $this->assertSame(['Şantiye' => 2, 'Depo' => 1, 'Bölüm belirtilmemiş' => 1], $o['bolumler']);
        $this->assertSame('Yüksekten düşme', $o['oncelikli']->first()->tehlike);
    }

    public function test_termin_tarihi_ayrisir(): void
    {
        $this->assertSame('2026-09-05', RiskMerkeziVerisi::terminTarihi('05.09.2026')->toDateString());
        $this->assertSame('2026-09-05', RiskMerkeziVerisi::terminTarihi('2026-09-05')->toDateString());
        $this->assertNull(RiskMerkeziVerisi::terminTarihi('Sürekli'));
        $this->assertNull(RiskMerkeziVerisi::terminTarihi(null));
    }

    public function test_nace_grubu_ve_rapor_kontrolleri(): void
    {
        $this->assertSame('insaat', RiskMerkeziVerisi::naceGrubu($this->firma)['anahtar']);
        $this->assertNull(RiskMerkeziVerisi::naceGrubu(Firma::factory()->for($this->uzman)->create(['nace_kodu' => null])));

        $rd = $this->rd();
        $k = collect(RiskMerkeziVerisi::raporKontrolleri($this->firma, $rd))->keyBy('baslik');
        $this->assertTrue($k['İşyeri kapsamı ve NACE kimliği']['tamam']);
        $this->assertFalse($k['Risk değerlendirme ekibi']['tamam']);
        $this->assertFalse($k['Risklerin seçilen yöntemle puanlanması']['tamam']);   // puansız madde var
        $this->assertNull($k['Kimyasallar ve SDS']['tamam']);                        // kayıt yok → bilgi

        $yol = RiskMerkeziVerisi::yolHaritasi($k->values()->all(), RiskMerkeziVerisi::ozet($rd));
        $this->assertCount(10, $yol);
        $this->assertTrue($yol[0][2]);
    }

    public function test_sayfa_sekmeler_aksiyon_durumu_ve_excel(): void
    {
        $rd = $this->rd();
        $yabanci = RiskDegerlendirmesi::create(['firma_id' => Firma::factory()->for(User::factory())->create()->id, 'yontem' => 'matris_5x5']);
        $yabanciMadde = $yabanci->maddeler()->create(['tehlike' => 'X', 'oneri' => 'Y']);

        $s = Livewire::test(RiskMerkezi::class)
            ->assertSee('Öncelikli riskler')
            ->assertSee('Yüksekten düşme')
            ->set('sekme', 'aksiyon')
            ->assertSee('Korkuluk');

        $this->assertCount(2, $s->instance()->aksiyonlar);
        $s->set('aksiyonFiltre', 'geciken');
        $this->assertCount(1, $s->instance()->aksiyonlar);

        $madde = $rd->maddeler()->where('sira', 1)->first();
        $s->call('maddeDurumu', $madde->id, 'kapali');
        $this->assertSame('kapali', $madde->fresh()->durum);
        $s->call('maddeDurumu', $yabanciMadde->id, 'kapali');
        $this->assertSame('acik', $yabanciMadde->fresh()->durum);

        $s->set('sekme', 'nace')->assertSee('Yüksekte çalışma ve düşmeye karşı koruma')->assertSee('Uygulama yol haritası');
        $s->set('sekme', 'raporlar')
            ->call('riskExcel')->assertFileDownloaded('risk-degerlendirmesi-ahmet-insaat.xlsx')
            ->call('aksiyonExcel')->assertFileDownloaded('risk-onlem-takibi-ahmet-insaat.xlsx');
    }

    public function test_degerlendirmesiz_firma_yenileme_uyarisi(): void
    {
        $y = RiskMerkeziVerisi::yenileme($this->firma, null);
        $this->assertSame('yok', $y['durum']);
        $this->assertStringContainsString('2 yılda bir', $y['metin']);

        Livewire::test(RiskMerkezi::class)->assertSee('Henüz risk değerlendirmesi yok');
    }
}
