<?php

namespace Tests\Feature;

use App\Filament\Pages\RiskSihirbazi;
use App\Models\Firma;
use App\Models\RiskDegerlendirmesi;
use App\Models\Tehlike;
use App\Models\TehlikeKategorisi;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/** Risk Sihirbazı "Hızlı Kurulum" yöntemi: tek tıkla genel riskler + çoklu ekipman seçimi. */
class RiskHizliKurulumTest extends TestCase
{
    use RefreshDatabase;

    private User $uzman;

    private Firma $firma;

    protected function setUp(): void
    {
        parent::setUp();
        $this->uzman = User::factory()->create(['rol' => 'sahip']);
        $this->actingAs($this->uzman);
        $this->firma = Firma::factory()->for($this->uzman)->create(['tehlike_sinifi' => 'tehlikeli']);

        $kategori = function (string $anahtar, string $ad, int $adet): void {
            $k = TehlikeKategorisi::create(['anahtar' => $anahtar, 'ad' => $ad, 'sira' => 1]);
            for ($i = 1; $i <= $adet; $i++) {
                Tehlike::create([
                    'tehlike_kategorisi_id' => $k->id, 'kod' => strtoupper($anahtar).'-'.$i,
                    'tehlike' => "$ad tehlikesi $i", 'risk' => 'Yaralanma', 'oneri' => 'Önlem al',
                    'olasilik' => 3, 'frekans' => 6, 'siddet' => 7,
                ]);
            }
        };
        $kategori('genel_isyeri', 'Genel İşyeri', 3);
        $kategori('atolye_acil_durumlar', 'Atölye – Acil durumlar', 2);
        $kategori('ekipman_forklift', 'Ekipman – Forklift', 4);
        $kategori('ekipman_kompresor_ve_hava_tanki', 'Ekipman – Kompresör ve hava tankı', 2);
    }

    private function sihirbaz(string $yontem = 'fine_kinney')
    {
        return Livewire::test(RiskSihirbazi::class)
            ->set('firmaId', $this->firma->id)
            ->set('yontem', $yontem)
            ->call('ileri')
            ->call('yontemSec', 'hizli')
            ->assertSet('yontemSecim', 'hizli')
            ->call('ileri')
            ->assertSet('adim', 3);
    }

    public function test_genel_riskler_tek_tikla_ve_tekrarsiz_eklenir(): void
    {
        $s = $this->sihirbaz()
            ->assertSee('Hızlı Kurulum')
            ->assertSee('5 Ortak Genel Riski Analize Ekle')
            ->assertSee('Fine-Kinney')
            ->call('genelRiskleriAktar');

        $this->assertCount(5, $s->get('secilenler'));
        $s->assertSee('5 genel risk eklendi');

        $s->call('genelRiskleriAktar');
        $this->assertCount(5, $s->get('secilenler'));
    }

    public function test_secilen_ekipmanlarin_riskleri_toplu_eklenir_olmayanlar_pasif(): void
    {
        $s = $this->sihirbaz()
            ->assertSee('Forklift')
            ->assertSee('kütüphanede yok — manuel ekleyin') // ör. eksantrik pres bu testte tanımsız
            ->call('ekipmanRiskleriAktar');
        $this->assertCount(0, $s->get('secilenler')); // seçim yokken uyarı

        $s->call('hizliEkipmanToggle', 'forklift')
            ->call('hizliEkipmanToggle', 'kompresor')
            ->call('hizliEkipmanToggle', 'eksantrik_pres') // kütüphanede yok → yok sayılır
            ->assertSet('hizliEkipmanSecim', ['forklift', 'kompresor', 'eksantrik_pres'])
            ->call('ekipmanRiskleriAktar')
            ->assertSet('hizliEkipmanSecim', []);

        $maddeler = collect($s->get('secilenler'));
        $this->assertCount(6, $maddeler);
        $this->assertTrue($maddeler->every(fn ($m) => filled($m['frekans'])));
        $s->assertSee('4 madde eklendi');
    }

    public function test_5x5_yonteminde_frekanssiz_eklenir_ve_kaydedilir(): void
    {
        $s = $this->sihirbaz('matris_5x5')
            ->call('genelRiskleriAktar')
            ->call('hizliEkipmanToggle', 'forklift')
            ->call('ekipmanRiskleriAktar');

        $maddeler = collect($s->get('secilenler'));
        $this->assertCount(9, $maddeler);
        $this->assertTrue($maddeler->every(fn ($m) => blank($m['frekans'] ?? null) && $m['olasilik'] >= 1 && $m['olasilik'] <= 5));

        $s->call('ileri')->call('ileri')->call('ileri')
            ->assertSet('adim', 6)
            ->assertSee('Tehlikeli · 4 yıl geçerli', false)
            ->assertSee('Risk Değerlendirme Ekibi (imza bloğu)')
            ->assertSee('İş Güvenliği Uzmanı')
            ->call('kaydet');

        $rd = RiskDegerlendirmesi::first();
        $this->assertSame(9, $rd->maddeler()->count());
        $this->assertSame(now()->addYears(4)->toDateString(), $rd->gecerlilik_tarihi->toDateString());
    }

    public function test_pres_seederlari_kutulari_aktiflestirir_ve_tekrar_calisinca_cogaltmaz(): void
    {
        $this->seed(\Database\Seeders\EksantrikPresRiskSeeder::class);
        $this->seed(\Database\Seeders\HidrolikVeAbkantPresSeeder::class);
        $this->seed(\Database\Seeders\HidrolikVeAbkantPresSeeder::class); // tekrar: güncelle, çoğaltma

        $this->assertSame(18, Tehlike::where('kod', 'like', 'EKP-%')->orWhere('kod', 'like', 'HDP-%')->orWhere('kod', 'like', 'ABK-%')->count());

        $s = $this->sihirbaz();
        $gruplar = $s->instance()->hizliGruplar['ekipmanlar'];
        $this->assertSame(8, $gruplar['eksantrik_pres']['adet']);
        $this->assertSame(5, $gruplar['hidrolik_pres']['adet']);   // bu testte eski atölye presi klasörü yok
        $this->assertSame(5, $gruplar['abkant_pres']['adet']);

        $s->call('hizliEkipmanToggle', 'eksantrik_pres')->call('hizliEkipmanToggle', 'abkant_pres')->call('ekipmanRiskleriAktar');
        $maddeler = collect($s->get('secilenler'));
        $this->assertCount(13, $maddeler);
        $isik = $maddeler->first(fn ($m) => str_starts_with($m['tehlike'], 'Işık bariyeri'));
        $this->assertSame(1440.0, $isik['olasilik'] * $isik['frekans'] * $isik['siddet']);

        // 5x5 yönteminde kullanıcının verdiği matris değerleri aynen gelir
        $x = $this->sihirbaz('matris_5x5')->call('hizliEkipmanToggle', 'abkant_pres')->call('ekipmanRiskleriAktar');
        $lazer = collect($x->get('secilenler'))->first(fn ($m) => str_starts_with($m['tehlike'], 'Lazer parmak'));
        $this->assertSame([4, 5], [(int) $lazer['olasilik'], (int) $lazer['siddet']]);
    }
}
