<?php

namespace Tests\Feature;

use App\Filament\Pages\KontrolMerkezi;
use App\Filament\Resources\Calisans\Pages\ListCalisans;
use App\Models\Calisan;
use App\Models\EgitimKaydi;
use App\Models\Firma;
use App\Models\User;
use App\Support\UzmanNotlari;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PaketSekizOzelliklerTest extends TestCase
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
            'unvan' => 'Örnek Lojistik A.Ş.',
            'aktif' => true,
        ]);
    }

    public function test_ust_bar_hizli_notlarim_ekle_tamamla_ve_sil_calisir(): void
    {
        $this->postJson(route('mehse.notlar.ekle'), [
            'metin' => 'Yarın iskele kontrolü yapılacak',
            'etiket' => 'acil',
            'firma_id' => $this->firma->id,
        ])->assertOk()->assertJsonPath('notlar.0.etiket', 'acil');

        $liste = UzmanNotlari::liste($this->uzman->fresh());
        $this->assertCount(1, $liste);
        $this->assertSame('Örnek Lojistik A.Ş.', $liste[0]['firma_adi']);

        $id = $liste[0]['id'];
        $this->postJson(route('mehse.notlar.tamamla', ['id' => $id]))
            ->assertOk()
            ->assertJsonPath('notlar.0.tamam', true);

        $this->deleteJson(route('mehse.notlar.sil', ['id' => $id]))
            ->assertOk()
            ->assertJsonCount(0, 'notlar');
    }

    public function test_yuzen_isg_mevzuat_asistani_soru_yanitlar_ve_panelde_gorunur(): void
    {
        $yanit = $this->postJson(route('mehse.mevzuat.sor'), [
            'soru' => 'İş kazası ve meslek hastalığı SGK bildirim süresi kaç gündür?',
        ])->assertOk()->json('yanit');

        $this->assertStringContainsString('3 İŞ GÜNÜ', (string) $yanit);

        $this->get(KontrolMerkezi::getUrl())
            ->assertOk()
            ->assertSee('Hızlı Notlarım')
            ->assertSee('İSG Mevzuat Asistanı');
    }

    public function test_calisanlar_tablosu_isg_eksik_filtreleri_calisir(): void
    {
        $egitimli = Calisan::factory()->for($this->firma)->create([
            'ad_soyad' => 'Eğitimli Personel',
            'tc' => '11122233344',
            'aktif' => true,
        ]);
        EgitimKaydi::create([
            'calisan_id' => $egitimli->id,
            'tur' => 'is_sagligi_guvenligi_egitimi',
            'tarih' => now()->toDateString(),
            'gecerlilik' => now()->addYear()->toDateString(),
        ]);

        $eksik = Calisan::factory()->for($this->firma)->create([
            'ad_soyad' => 'Eksik Personel',
            'tc' => null,
            'aktif' => true,
        ]);

        Livewire::test(ListCalisans::class)
            ->filterTable('egitim_durumu', false)
            ->assertCanSeeTableRecords([$eksik])
            ->assertCanNotSeeTableRecords([$egitimli])
            ->resetTableFilters()
            ->filterTable('tc_durumu', false)
            ->assertCanSeeTableRecords([$eksik])
            ->assertCanNotSeeTableRecords([$egitimli]);
    }
}
