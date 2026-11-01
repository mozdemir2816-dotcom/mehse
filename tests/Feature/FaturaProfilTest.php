<?php

namespace Tests\Feature;

use App\Filament\Pages\FaturaProfil;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class FaturaProfilTest extends TestCase
{
    use RefreshDatabase;

    private function uzman(array $ek = []): User
    {
        return User::factory()->create(['rol' => 'uzman'] + $ek);
    }

    public function test_yetkisiz_uzman_da_sayfayi_acar_ve_menude_gorur(): void
    {
        $user = $this->uzman();

        $this->actingAs($user)->get('/admin/fatura-profil')->assertOk()
            ->assertSee('Fatura Bilgileri')
            ->assertSee('Fatura bilgileriniz eksik.');

        $this->actingAs($user)->get('/admin')->assertOk()->assertSee('Fatura &amp; Profil', false);
    }

    public function test_kurumsal_fatura_kaydedilir(): void
    {
        $user = $this->uzman();
        $this->actingAs($user);

        Livewire::test(FaturaProfil::class)
            ->assertSet('data.fatura_tipi', 'bireysel')
            ->set('data.fatura_tipi', 'kurumsal')
            ->set('data.fatura_unvan', 'Örnek İnşaat Ltd. Şti.')
            ->set('data.vergi_dairesi', 'Uludağ Vergi Dairesi')
            ->set('data.vergi_no', '4532187690')
            ->set('data.fatura_adresi', 'Fatih Sultan Mehmet Blv. No: 1')
            ->set('data.fatura_il', 'Bursa')
            ->set('data.fatura_ilce', 'Nilüfer')
            ->call('kaydet')
            ->assertHasNoFormErrors();

        $user->refresh();
        $this->assertSame('kurumsal', $user->fatura_tipi);
        $this->assertSame('4532187690', $user->vergi_no);
        $this->assertSame('Uludağ Vergi Dairesi', $user->vergi_dairesi);
        $this->assertTrue($user->faturaBilgisiTamMi());
    }

    public function test_bireyselde_tc_11_hane_ister_vergi_dairesi_tutulmaz(): void
    {
        $user = $this->uzman(['fatura_tipi' => 'kurumsal', 'vergi_dairesi' => 'Eski V.D.']);
        $this->actingAs($user);

        $sayfa = Livewire::test(FaturaProfil::class)
            ->set('data.fatura_tipi', 'bireysel')
            ->set('data.fatura_unvan', 'Ayşe Yılmaz')
            ->set('data.vergi_no', '4532187690') // 10 hane: bireyselde yetmez
            ->set('data.fatura_adresi', 'Adres')
            ->set('data.fatura_il', 'Bursa')
            ->set('data.fatura_ilce', 'Osmangazi')
            ->call('kaydet')
            ->assertHasFormErrors(['vergi_no' => 'regex']);

        $sayfa->set('data.vergi_no', '12345678901')->call('kaydet')->assertHasNoFormErrors();

        $user->refresh();
        $this->assertSame('bireysel', $user->fatura_tipi);
        $this->assertNull($user->vergi_dairesi);
        $this->assertTrue($user->faturaBilgisiTamMi());
    }

    public function test_kurumsalda_zorunlu_alanlar(): void
    {
        $this->actingAs($this->uzman());

        Livewire::test(FaturaProfil::class)
            ->set('data.fatura_tipi', 'kurumsal')
            ->set('data.vergi_no', '12AB')
            ->call('kaydet')
            ->assertHasFormErrors(['fatura_unvan', 'vergi_dairesi', 'vergi_no', 'fatura_adresi', 'fatura_il', 'fatura_ilce']);
    }

    public function test_suresi_dolan_kullanici_fatura_sayfasini_acabilir(): void
    {
        $user = $this->uzman(['abonelik_plani' => 'deneme', 'abonelik_bitis' => today()->subDay()]);

        $this->actingAs($user)->get('/admin/fatura-profil')->assertOk();
        $this->actingAs($user)->get('/admin')->assertRedirect(route('filament.admin.deneme-bitti'));
    }
    public function test_kaydedilen_fatura_bilgisi_sozlesmedeki_musteri_fikrasina_yansir(): void
    {
        $user = $this->uzman(['email' => 'osgb@ornek.com']);
        $this->actingAs($user);

        // Fatura bilgisi yokken genel tanım
        $this->get('/admin/guvenlik')->assertOk()
            ->assertSee('Müşteri: Başvuru veya kayıt formunda kimlik', false)
            ->assertDontSee('mehse-yasal-bos', false);

        Livewire::test(FaturaProfil::class)
            ->set('data.fatura_tipi', 'kurumsal')
            ->set('data.fatura_unvan', 'Örnek OSGB Ltd. Şti.')
            ->set('data.vergi_dairesi', 'Uludağ Vergi Dairesi')
            ->set('data.vergi_no', '4532187690')
            ->set('data.fatura_adresi', 'FSM Blv. No: 1')
            ->set('data.fatura_il', 'Bursa')
            ->set('data.fatura_ilce', 'Nilüfer')
            ->call('kaydet')
            ->assertHasNoFormErrors();

        $this->get('/admin/guvenlik')->assertOk()
            ->assertSee('Müşteri: Örnek OSGB Ltd. Şti. (Uludağ Vergi Dairesi, VKN/TCKN: 4532187690). Adres: FSM Blv. No: 1 Nilüfer / Bursa. Bildirim e-postası: osgb@ornek.com.', false)
            ->assertSee('Müşteri unvanı veya adı: Örnek OSGB Ltd. Şti.', false)
            ->assertDontSee('Müşteri: Başvuru veya kayıt formunda kimlik', false)
            ->assertDontSee('mehse-yasal-bos', false)
            ->assertDontSee('Vergi Dairesi Vergi Dairesi', false);
    }

    public function test_vergi_dairesi_kisa_yazilinca_ek_alir_bireyselde_yalniz_tc(): void
    {
        $this->actingAs($this->uzman(['fatura_tipi' => 'kurumsal', 'fatura_unvan' => 'A Ltd.', 'vergi_dairesi' => 'Uludağ', 'vergi_no' => '4532187690']));
        $this->get('/admin/guvenlik')->assertSee('Müşteri: A Ltd. (Uludağ Vergi Dairesi, VKN/TCKN: 4532187690). Bildirim', false);

        $this->actingAs($this->uzman(['fatura_tipi' => 'bireysel', 'fatura_unvan' => 'Ayşe Yılmaz', 'vergi_no' => '12345678901']));
        $this->get('/admin/guvenlik')->assertSee('Müşteri: Ayşe Yılmaz (VKN/TCKN: 12345678901). Bildirim', false);
    }

    public function test_kayit_ekraninda_misafir_genel_tanimi_gorur(): void
    {
        $this->get('/admin/register')->assertOk()
            ->assertSee('Müşteri: Başvuru veya kayıt formunda kimlik', false);
    }
}
