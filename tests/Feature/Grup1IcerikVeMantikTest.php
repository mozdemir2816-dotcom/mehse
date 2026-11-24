<?php

namespace Tests\Feature;

use App\Filament\Pages\KontrolMerkezi;
use App\Filament\Pages\TaseronYonetimi;
use App\Filament\Resources\Calisans\Schemas\CalisanForm;
use App\Models\Firma;
use App\Models\Taseron;
use App\Models\TaseronBasvurusu;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class Grup1IcerikVeMantikTest extends TestCase
{
    use RefreshDatabase;

    public function test_taseron_basvurusu_alti_ayda_sona_erer_ve_onaylaninca_alti_ay_verilir(): void
    {
        $u = User::factory()->create(['rol' => 'sahip', 'aktif' => true]);
        $this->actingAs($u);
        $f = Firma::create(['user_id' => $u->id, 'unvan' => 'Ana İşyeri A.Ş.', 'tehlike_sinifi' => 'tehlikeli', 'aktif' => true]);

        // 7 ay önce oluşturulmuş link süresi dolmuş olmalı
        $eski = TaseronBasvurusu::create(['firma_id' => $f->id, 'durum' => 'link_acik']);
        $eski->forceFill(['created_at' => now()->subMonths(7)])->save();
        $this->assertTrue($eski->fresh()->linkSuresiDolduMu());

        $this->post(route('taseron.basvuru.kaydet', $eski->token), [
            'unvan' => 'Eski Taşeron',
            'faaliyet' => 'Montaj',
            'yetkili_ad' => 'Ali',
            'telefon' => '05551112233',
        ])->assertSessionHasErrors('link');

        // Yeni başvuru onaylanınca 6 aylık geçerlilik atanır
        $yeni = TaseronBasvurusu::create([
            'firma_id' => $f->id,
            'unvan' => 'Güncel Taşeron Ltd.',
            'faaliyet' => 'Elektrik',
            'yetkili_ad' => 'Veli',
            'telefon' => '05552223344',
            'durum' => 'bekliyor',
        ]);

        Livewire::test(TaseronYonetimi::class)
            ->call('basvuruOnayla', $yeni->id);

        $t = Taseron::where('unvan', 'Güncel Taşeron Ltd.')->firstOrFail();
        $this->assertSame(now()->toDateString(), $t->sozlesme_baslangic->toDateString());
        $this->assertSame(now()->addMonthsNoOverflow(6)->toDateString(), $t->sozlesme_bitis->toDateString());

        // Süresi dolmuş taşeron tek tıkla 6 ay yenilenebilir
        $t->update(['sozlesme_bitis' => now()->subDays(5)->toDateString()]);
        $this->assertTrue($t->fresh()->onaySuresiDolduMu());

        Livewire::test(TaseronYonetimi::class)
            ->call('altiAyOnayla', $t->id);

        $this->assertFalse($t->fresh()->onaySuresiDolduMu());
        $this->assertSame(now()->addMonthsNoOverflow(6)->toDateString(), $t->fresh()->sozlesme_bitis->toDateString());
    }

    public function test_kontrol_merkezi_secilen_firmaya_gore_ozet_ve_kriterleri_filtreler(): void
    {
        $u = User::factory()->create(['rol' => 'sahip', 'aktif' => true]);
        $this->actingAs($u);
        $f1 = Firma::create(['user_id' => $u->id, 'unvan' => 'Alfa A.Ş.', 'tehlike_sinifi' => 'tehlikeli', 'aktif' => true]);
        $f2 = Firma::create(['user_id' => $u->id, 'unvan' => 'Beta A.Ş.', 'tehlike_sinifi' => 'az_tehlikeli', 'aktif' => true]);

        $sayfa = Livewire::test(KontrolMerkezi::class);
        $this->assertSame(2, $sayfa->get('ozet')['firma']);

        $sayfa->set('firmaId', $f1->id);
        $this->assertSame(1, $sayfa->get('ozet')['firma']);
    }

    public function test_tc_kimlik_no_dogrulama_algoritmasi_calisir(): void
    {
        $this->assertTrue(CalisanForm::tcGecerliMi('10000000146'));
        $this->assertFalse(CalisanForm::tcGecerliMi('11111111111'));
        $this->assertFalse(CalisanForm::tcGecerliMi('01234567890'));
        $this->assertFalse(CalisanForm::tcGecerliMi('12345'));
    }
}
