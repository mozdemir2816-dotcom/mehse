<?php

namespace Tests\Feature;

use App\Filament\Pages\RiskSihirbazi;
use App\Models\Firma;
use App\Models\RiskDegerlendirmesi;
use App\Models\Tehlike;
use App\Models\TehlikeKategorisi;
use App\Models\User;
use App\Support\RiskMaddeMukerrer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Risk maddelerinde mükerrer tespiti / temizliği ve sihirbazdaki "Mükerrerleri sil" düğmesi (28.10.2026).
 */
class RiskMaddeMukerrerTest extends TestCase
{
    use RefreshDatabase;

    private User $uzman;

    protected function setUp(): void
    {
        parent::setUp();
        $this->uzman = User::factory()->create();
        $this->actingAs($this->uzman);
    }

    private function madde(array $ek = []): array
    {
        return $ek + ['bolum' => 'Saha', 'faaliyet' => 'Kalıp', 'tehlike' => 'Yüksekten düşme', 'risk' => 'Yaralanma'];
    }

    public function test_bosluk_ve_harf_farki_yok_sayilir_en_dolu_satir_kalir(): void
    {
        $maddeler = [
            'a' => $this->madde(),
            'b' => $this->madde(['tehlike' => '  yüksekten   DÜŞME ', 'olasilik' => 3, 'siddet' => 5, 'oneri' => 'Korkuluk']),
            'c' => $this->madde(['faaliyet' => 'Beton dökümü']),
        ];

        $this->assertSame(['a'], RiskMaddeMukerrer::tekrarlar($maddeler));
        $this->assertSame(['b', 'c'], array_keys(RiskMaddeMukerrer::temizle($maddeler)));
    }

    public function test_yalniz_tehlike_ayni_ise_mukerrer_sayilmaz(): void
    {
        $maddeler = [$this->madde(), $this->madde(['faaliyet' => 'Beton dökümü']), $this->madde(['bolum' => 'Şantiye'])];

        $this->assertSame([], RiskMaddeMukerrer::tekrarlar($maddeler));
    }

    public function test_kayitli_degerlendirmede_silinir_sira_yeniden_numaralanir_hastane_korunur(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();
        $rd = RiskDegerlendirmesi::create(['firma_id' => $firma->id, 'yontem' => 'matris_5x5']);

        foreach ([1 => $this->madde(), 2 => $this->madde(), 3 => $this->madde(['faaliyet' => 'Başka'])] as $sira => $m) {
            $rd->maddeler()->create($m + ['sira' => $sira]);
        }

        $this->assertSame(1, RiskMaddeMukerrer::degerlendirmeTemizle($rd, true));
        $this->assertCount(3, $rd->maddeler()->get(), 'Deneme koşusu silmemeli.');

        $this->assertSame(1, RiskMaddeMukerrer::degerlendirmeTemizle($rd));
        $this->assertSame([1, 2], $rd->maddeler()->orderBy('sira')->pluck('sira')->all());

        $hastane = RiskDegerlendirmesi::create(['firma_id' => $firma->id, 'yontem' => 'fine_kinney', 'cikti_sablonu' => 'hastane']);
        $hastane->maddeler()->create($this->madde() + ['sira' => 1]);
        $hastane->maddeler()->create($this->madde() + ['sira' => 2]);

        $this->assertSame(0, RiskMaddeMukerrer::degerlendirmeTemizle($hastane));
        $this->assertCount(2, $hastane->maddeler()->get());
    }

    public function test_kutuphane_klasoru_kapaliyken_eklenen_sayisini_gosterir(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();
        $kategori = TehlikeKategorisi::create(['anahtar' => 'ekipman_test_forklift', 'ad' => 'Ekipman – Test forklift', 'sira' => 1, 'yontem' => 'matris_5x5', 'nace_gruplari' => ['ortak']]);
        $ilk = Tehlike::create(['tehlike_kategorisi_id' => $kategori->id, 'kod' => 'TST-001', 'tehlike' => 'Devrilme']);
        Tehlike::create(['tehlike_kategorisi_id' => $kategori->id, 'kod' => 'TST-002', 'tehlike' => 'Çarpma']);

        $test = Livewire::test(RiskSihirbazi::class)
            ->set('firmaId', $firma->id)
            ->set('yontem', 'matris_5x5')
            ->set('yontemSecim', 'manuel')
            ->set('adim', 3)
            ->assertSee('Ekipman – Test forklift')
            ->assertDontSee('eklendi');

        $test->call('tehlikeEkle', $ilk->id)->assertSee('1 / 2 eklendi');
        $test->call('kategoriTumunuEkle', $kategori->id)->assertSee('2 / 2 eklendi')->assertSee('Tümü eklendi');
    }

    public function test_sihirbazda_mukerrerleri_sil_dugmesi_listeden_cikarir(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();

        $test = Livewire::test(RiskSihirbazi::class)
            ->set('firmaId', $firma->id)
            ->set('yontem', 'matris_5x5')
            ->set('secilenler', [$this->madde(['olasilik' => 3, 'siddet' => 4]), $this->madde(['olasilik' => 3, 'siddet' => 4]), $this->madde(['faaliyet' => 'Başka', 'olasilik' => 2, 'siddet' => 2])]);

        $this->assertSame(1, $test->instance()->mukerrerSayisi());

        $test->call('mukerrerleriSil');

        $this->assertCount(2, $test->get('secilenler'));
        $this->assertSame(0, $test->instance()->mukerrerSayisi());
    }
}
