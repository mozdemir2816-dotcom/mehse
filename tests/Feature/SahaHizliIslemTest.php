<?php

namespace Tests\Feature;

use App\Filament\Pages\ZiyaretModu;
use App\Models\Firma;
use App\Models\IsgProfesyoneli;
use App\Models\IsIzinFormu;
use App\Models\OlayKaydi;
use App\Models\User;
use App\Support\IsIzinFormuUretici;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Saha hızlı işlemleri (iş izni kapatma, ramak kala) — 5. aşamadan beri
 * Ziyaret Modu içinde (Concerns\SahaHizliIslemleri).
 */
class SahaHizliIslemTest extends TestCase
{
    use RefreshDatabase;

    private User $uzman;

    private Firma $firma;

    protected function setUp(): void
    {
        parent::setUp();
        $this->uzman = User::factory()->create();
        $this->actingAs($this->uzman);
        $igu = IsgProfesyoneli::factory()->for($this->uzman)->create(['ad_soyad' => 'Ayşe Uzman']);
        $this->firma = Firma::factory()->for($this->uzman)->create(['igu_id' => $igu->id]);
    }

    private function izin(string $durum): IsIzinFormu
    {
        return IsIzinFormu::create(['firma_id' => $this->firma->id, 'izin_no' => 'IZN-'.$durum, 'calisma_alani' => 'Çatı', 'durum' => $durum]);
    }

    public function test_yalniz_onayli_ve_tamamlanmis_izinler_listelenir(): void
    {
        $this->izin('taslak');
        $this->izin('onay_bekliyor');
        $onay = $this->izin('onaylandi');
        $tamam = $this->izin('is_tamamlandi');
        $this->izin('kapatildi');

        $sayfa = Livewire::test(ZiyaretModu::class)->set('firmaId', $this->firma->id);

        $this->assertEqualsCanonicalizing([$onay->id, $tamam->id], $sayfa->instance()->aktifIzinler->pluck('id')->all());
    }

    public function test_izin_kamera_kanitiyla_sahada_kapatilir_ve_pdfe_girer(): void
    {
        Storage::fake('public');
        $izin = $this->izin('onaylandi');

        Livewire::test(ZiyaretModu::class)
            ->set('firmaId', $this->firma->id)
            ->set('ptwIzinId', $izin->id)
            ->set('ptwKapanisNotu', 'Saha temiz, yangın nöbeti tamamlandı.')
            ->set('ptwKapanisFoto', UploadedFile::fake()->image('kapanis.jpg'))
            ->call('izniKapat')
            ->assertHasNoErrors()
            ->assertSet('ptwIzinId', null);

        $izin->refresh();
        $this->assertSame('kapatildi', $izin->durum);
        $this->assertTrue((bool) $izin->saha_teslim_alindi);
        $this->assertSame('Ayşe Uzman', $izin->kapatan);
        $this->assertNotNull($izin->is_bitis_tarihi);
        Storage::disk('public')->assertExists($izin->kapanis_fotografi);

        ob_start();
        IsIzinFormuUretici::pdf($izin)->sendContent();
        $this->assertStringStartsWith('%PDF', ob_get_clean());
    }

    public function test_taslak_izin_kapatilamaz(): void
    {
        $taslak = $this->izin('taslak');

        Livewire::test(ZiyaretModu::class)
            ->set('firmaId', $this->firma->id)
            ->set('ptwIzinId', $taslak->id)
            ->call('izniKapat');

        $this->assertSame('taslak', $taslak->fresh()->durum);
    }

    public function test_ramak_kala_fotografla_olay_kayitlarina_duser(): void
    {
        Storage::fake('public');

        Livewire::test(ZiyaretModu::class)
            ->set('firmaId', $this->firma->id)
            ->set('ramakYer', 'Depo rampası')
            ->set('ramakSiniflandirma', 'is_makinesi')
            ->set('ramakOzet', 'Forklift geri manevrada yayaya çok yaklaştı.')
            ->set('ramakFotolar', [UploadedFile::fake()->image('r.jpg')])
            ->call('ramakKalaKaydet')
            ->assertHasNoErrors()
            ->assertSet('ramakOzet', null);

        $o = OlayKaydi::sole();
        $this->assertSame('ramak_kala', $o->olay_tipi);
        $this->assertSame('Depo rampası', $o->olay_yeri);
        $this->assertTrue($o->etkiVar('ramak_kala'));
        $this->assertSame('Ayşe Uzman', $o->rapor_hazirlayan);
        $this->assertCount(1, $o->fotograflar);
        Storage::disk('public')->assertExists($o->fotograflar[0]);
    }

    public function test_ramak_kala_kisa_ozet_ve_detay_reddedilir(): void
    {
        Livewire::test(ZiyaretModu::class)
            ->set('firmaId', $this->firma->id)
            ->set('ramakOzet', 'Kısa')
            ->set('ramakDetay', 'Az')
            ->call('ramakKalaKaydet')
            ->assertHasErrors(['ramakOzet', 'ramakDetay']);

        $this->assertSame(0, OlayKaydi::count());
    }

    public function test_ziyaret_modunda_gorunur_eski_adres_yonlenir(): void
    {
        $this->izin('onaylandi');

        Livewire::test(ZiyaretModu::class, ['firmaId' => $this->firma->id])
            ->assertSee('Hızlı işlemler')
            ->assertSee('İş iznini kapat (1)')
            ->call('hizliIslemAc', 'ramak')
            ->assertSee('Ramak kala kaydını oluştur');

        $this->get('/admin/saha-hizli-islem?firma='.$this->firma->id)->assertRedirect('/admin/ziyaret?firma='.$this->firma->id);
    }
}
