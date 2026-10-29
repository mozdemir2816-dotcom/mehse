<?php

namespace Tests\Feature;

use App\Filament\Pages\KazaIstatistikleri;
use App\Filament\Pages\OlayKayitlari;
use App\Filament\Pages\SaglikGozetimi;
use App\Models\User;
use App\Support\SayfaAileleri;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Sağlık & Olay Yönetim Merkezi: tek menü girişi ve 3 ana sekme. */
class SaglikOlayMerkeziTest extends TestCase
{
    use RefreshDatabase;

    public function test_menude_tek_giris_ve_uc_ana_sekme(): void
    {
        $this->actingAs(User::factory()->create(['rol' => 'sahip']));

        $grup = collect(Filament::getNavigation())->first(fn ($g) => $g->getLabel() === 'Sağlık & Olaylar');
        $this->assertNotNull($grup);
        $this->assertCount(1, $grup->getItems());
        $this->assertFalse(OlayKayitlari::shouldRegisterNavigation());

        $g = SayfaAileleri::sekmeGruplari([KazaIstatistikleri::class]);
        $this->assertSame('saglik_olay', $g['aile']);
        $this->assertSame(
            ['Sağlık Gözetimi & EK-2 Muayene Formu', 'Olay / Kaza / Ramak Kala & Kök Neden Analizi', 'Kaza İstatistikleri & Sıklık/Ağırlık Oranları'],
            array_column($g['ust'], 'etiket'),
        );
        $this->assertSame('Kaza İstatistikleri & Sıklık/Ağırlık Oranları', collect($g['ust'])->firstWhere('aktif', true)['etiket']);

        $olay = SayfaAileleri::sekmeGruplari([OlayKayitlari::class]);
        $this->assertCount(2, $olay['alt']);

        $this->get(SaglikGozetimi::getUrl())->assertSuccessful()->assertSee('Sağlık Gözetimi &amp; EK-2 Muayene Formu', false);
    }

    public function test_sgk_sayaci_uc_is_gununu_hafta_sonu_atlayarak_hesaplar(): void
    {
        // Cuma günü kaza → son gün Çarşamba (hafta sonu sayılmaz)
        $s = \App\Support\SgkBildirimSayaci::hesapla('2026-10-02', false, \Illuminate\Support\Carbon::parse('2026-10-05 09:00'));
        $this->assertSame('2026-10-07', $s['son']->toDateString());
        $this->assertSame('surede', $s['durum']);

        $acil = \App\Support\SgkBildirimSayaci::hesapla('2026-10-02', false, \Illuminate\Support\Carbon::parse('2026-10-07 10:00'));
        $this->assertSame('acil', $acil['durum']);
        $this->assertStringContainsString('Kalan', \App\Support\SgkBildirimSayaci::metin($acil)[0]);

        $this->assertSame('gecti', \App\Support\SgkBildirimSayaci::hesapla('2026-10-02', false, \Illuminate\Support\Carbon::parse('2026-10-09'))['durum']);
        $this->assertSame('yapildi', \App\Support\SgkBildirimSayaci::hesapla('2026-10-02', true, \Illuminate\Support\Carbon::parse('2026-10-09'))['durum']);
        $this->assertNull(\App\Support\SgkBildirimSayaci::hesapla(null));
    }

    public function test_olay_kaydinda_is_kazasi_sgk_sayaci_ve_saglik_sayfasinda_kvkk_rozeti(): void
    {
        $uzman = User::factory()->create(['rol' => 'sahip']);
        $this->actingAs($uzman);

        $firma = \App\Models\Firma::factory()->for($uzman)->create();

        \Livewire\Livewire::test(OlayKayitlari::class)
            ->set('firmaId', $firma->id)
            ->set('olayTipi', 'is_kazasi')
            ->set('olayTarihi', now()->toDateString())
            ->assertSee('SGK Bildirim Süresi');

        \Livewire\Livewire::test(SaglikGozetimi::class)->assertSee('KVKK Korumalı Sağlık Verisi');
        \Livewire\Livewire::test(\App\Filament\Pages\MuayeneFormu::class)->assertSee('KVKK Korumalı Sağlık Verisi');
    }
}
