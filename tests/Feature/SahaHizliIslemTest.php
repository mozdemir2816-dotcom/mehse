<?php

namespace Tests\Feature;

use App\Filament\Pages\SahaHizliIslem;
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

        $sayfa = Livewire::test(SahaHizliIslem::class)->set('firmaId', $this->firma->id);

        $this->assertEqualsCanonicalizing([$onay->id, $tamam->id], $sayfa->instance()->aktifIzinler->pluck('id')->all());
    }

    public function test_izin_kamera_kanitiyla_sahada_kapatilir_ve_pdfe_girer(): void
    {
        Storage::fake('public');
        $izin = $this->izin('onaylandi');

        Livewire::test(SahaHizliIslem::class)
            ->set('firmaId', $this->firma->id)
            ->set('izinId', $izin->id)
            ->set('kapanisNotu', 'Saha temiz, yangın nöbeti tamamlandı.')
            ->set('kapanisFoto', UploadedFile::fake()->image('kapanis.jpg'))
            ->call('izniKapat')
            ->assertHasNoErrors()
            ->assertSet('izinId', null);

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

        Livewire::test(SahaHizliIslem::class)
            ->set('firmaId', $this->firma->id)
            ->set('izinId', $taslak->id)
            ->call('izniKapat');

        $this->assertSame('taslak', $taslak->fresh()->durum);
    }

    public function test_ramak_kala_fotografla_olay_kayitlarina_duser(): void
    {
        Storage::fake('public');

        Livewire::test(SahaHizliIslem::class)
            ->set('sekme', 'ramak')
            ->set('firmaId', $this->firma->id)
            ->set('yer', 'Depo rampası')
            ->set('siniflandirma', 'is_makinesi')
            ->set('ozet', 'Forklift geri manevrada yayaya çok yaklaştı.')
            ->set('ramakFotolar', [UploadedFile::fake()->image('r.jpg')])
            ->call('ramakKalaKaydet')
            ->assertHasNoErrors()
            ->assertSet('ozet', null);

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
        Livewire::test(SahaHizliIslem::class)
            ->set('firmaId', $this->firma->id)
            ->set('ozet', 'Kısa')
            ->set('detay', 'Az')
            ->call('ramakKalaKaydet')
            ->assertHasErrors(['ozet', 'detay']);

        $this->assertSame(0, OlayKaydi::count());
    }

    public function test_sekme_adresten_acilir_ve_denetim_kisayollari_gorunur(): void
    {
        $this->get(SahaHizliIslem::getUrl(['sekme' => 'denetim', 'firma' => $this->firma->id]))
            ->assertOk()
            ->assertSee('Hızlı Saha Bulgusu')
            ->assertSee('AI Saha Analizi');
    }
}
