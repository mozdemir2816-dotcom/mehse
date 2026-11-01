<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\YasalMetinler;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SozlesmePdfTest extends TestCase
{
    use RefreshDatabase;

    private function uzman(array $ek = []): User
    {
        return User::factory()->create(['rol' => 'uzman', 'name' => 'Ayşe Yılmaz'] + $ek);
    }

    public function test_misafir_indiremez(): void
    {
        $this->get(route('filament.admin.sozlesme.pdf'))->assertRedirect();
    }

    public function test_pdf_indirilir_ve_fatura_bilgisi_ile_onay_kasesi_icerir(): void
    {
        $user = $this->uzman([
            'fatura_tipi' => 'kurumsal', 'fatura_unvan' => 'Örnek OSGB Ltd. Şti.', 'vergi_dairesi' => 'Uludağ',
            'vergi_no' => '4532187690', 'fatura_adresi' => 'FSM Blv. No: 1', 'fatura_il' => 'Bursa', 'fatura_ilce' => 'Nilüfer',
        ]);
        $this->actingAs($user);
        YasalMetinler::onayla($user, 'sozlesme');

        $yanit = $this->get(route('filament.admin.sozlesme.pdf'))->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');
        $this->assertStringContainsString('mehse-hizmet-sozlesmesi-ornek-osgb-ltd-sti.pdf', $yanit->headers->get('Content-Disposition'));
        $this->assertStringStartsWith('%PDF', $yanit->getContent());

        $html = view('pdf.hizmet_sozlesmesi', [
            'user' => $user->fresh(), 'metin' => YasalMetinler::aktifler()['sozlesme'],
            'onay' => YasalMetinler::sonOnay($user->fresh(), 'sozlesme'), 'onayAt' => now(), 'guncel' => true,
            'saglayici' => config('isg.kayit.saglayici'), 'olusturma' => now(),
        ])->render();

        $this->assertStringContainsString('Uludağ Vergi Dairesi / 4532187690', $html);
        $this->assertStringContainsString('Müşteri: Örnek OSGB Ltd. Şti. (Uludağ Vergi Dairesi, VKN/TCKN: 4532187690)', $html);
        $this->assertStringContainsString('ELEKTRONİK OLARAK ONAYLANMIŞTIR', $html);
        $this->assertStringNotContainsString('mehse-yasal-bos', $html);
    }

    public function test_onay_kaydi_yoksa_kase_bunu_soyler(): void
    {
        $user = $this->uzman(['yasal_onaylar' => null]);
        $this->actingAs($user);
        $this->get(route('filament.admin.sozlesme.pdf'))->assertOk();

        $html = view('pdf.hizmet_sozlesmesi', [
            'user' => $user, 'metin' => YasalMetinler::aktifler()['sozlesme'], 'onay' => null, 'onayAt' => null,
            'guncel' => false, 'saglayici' => config('isg.kayit.saglayici'), 'olusturma' => now(),
        ])->render();
        $this->assertStringContainsString('ELEKTRONİK ONAY KAYDI BULUNMUYOR', $html);
    }

    public function test_suresi_dolan_da_indirir_ve_dugmeler_gorunur(): void
    {
        $user = $this->uzman(['abonelik_plani' => 'deneme', 'abonelik_bitis' => today()->subDay()]);
        $this->actingAs($user)->get(route('filament.admin.sozlesme.pdf'))->assertOk();

        $aktif = $this->uzman();
        $this->actingAs($aktif)->get('/admin/fatura-profil')->assertOk()
            ->assertSee('Onaylı Sözleşmeyi PDF İndir')->assertSee(route('filament.admin.sozlesme.pdf'), false);
        $this->actingAs($aktif)->get('/admin/guvenlik')->assertOk()
            ->assertSee('Onaylı Sözleşmeyi PDF İndir');
        auth()->logout();
        $this->get('/admin/register')->assertOk()->assertDontSee('Onaylı Sözleşmeyi PDF İndir');
    }
}
