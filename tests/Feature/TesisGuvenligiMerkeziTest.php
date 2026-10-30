<?php

namespace Tests\Feature;

use App\Filament\Pages\KkdTakip;
use App\Filament\Pages\OrtamOlcumleri;
use App\Filament\Pages\PeriyodikKontrol;
use App\Filament\Pages\TabelaTasarimi;
use App\Filament\Pages\TaseronYonetimi;
use App\Filament\Pages\ZiyaretciYonetimi;
use App\Filament\Pages\KkdTakip as KkdSayfasi;
use App\Models\Calisan;
use App\Models\Firma;
use App\Models\KkdMatrisi;
use App\Models\KkdZimmet;
use App\Models\User;
use App\Support\KkdDegisimSuresi;
use App\Support\KkdGorevOnerisi;
use Livewire\Livewire;
use App\Support\SayfaAileleri;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Tesis Güvenliği, Ekipman & Dış Paydaş Merkezi: tek menü girişi ve 3 ana sekme. */
class TesisGuvenligiMerkeziTest extends TestCase
{
    use RefreshDatabase;

    public function test_kkd_yalniz_kkd_sayfalarini_tasir_diger_sayfalar_kendi_girislerinde(): void
    {
        $this->actingAs(User::factory()->create(['rol' => 'sahip']));

        $menu = collect(Filament::getNavigation());
        $this->assertNull($menu->first(fn ($g) => $g->getLabel() === 'Saha Dışı Kayıtlar'));
        $etiketler = $menu->flatMap(fn ($g) => $g->getItems())->map(fn ($i) => $i->getLabel());
        foreach (['KKD', 'Ekipman & Ölçüm', 'Dış Paydaş'] as $giris) {
            $this->assertCount(1, $etiketler->filter(fn ($e) => $e === $giris), $giris);
        }

        foreach ([OrtamOlcumleri::class, ZiyaretciYonetimi::class, TabelaTasarimi::class] as $gizli) {
            $this->assertFalse($gizli::shouldRegisterNavigation());
        }

        // KKD ailesi yalnız KKD sayfaları; periyodik kontrol/ölçüm/taşeron/ziyaretçi/tabela ona karışmaz.
        $kkd = SayfaAileleri::sekmeler([KkdTakip::class]);
        $this->assertSame(['Zimmet & Stok', 'KKD Formu', 'Seçim Matrisi'], array_column($kkd, 'etiket'));

        $this->assertSame(['Periyodik Kontrol', 'Ortam Ölçümleri'], array_column(SayfaAileleri::sekmeler([OrtamOlcumleri::class]), 'etiket'));
        $this->assertSame(['Taşeron / Alt İşveren', 'Tesis Giriş / Ziyaretçi Kartı'], array_column(SayfaAileleri::sekmeler([ZiyaretciYonetimi::class]), 'etiket'));

        // Tabela güvenlik levhasıdır: Acil Durum & Yangın merkezinin sekmesi.
        $g = SayfaAileleri::sekmeGruplari([TabelaTasarimi::class]);
        $this->assertSame('acil', $g['aile']);
        $this->assertSame('Güvenlik Levhaları (Tabela)', collect($g['ust'])->firstWhere('aktif', true)['etiket']);

        foreach ([KkdTakip::class, PeriyodikKontrol::class, OrtamOlcumleri::class, TaseronYonetimi::class, ZiyaretciYonetimi::class, TabelaTasarimi::class] as $sayfa) {
            $this->get($sayfa::getUrl())->assertSuccessful();
        }
        $this->get(OrtamOlcumleri::getUrl())->assertSee('Periyodik Kontrol')->assertSee('Ortam Ölçümleri');
    }

    public function test_degisim_suresi_baret_ve_kemer_bes_yil_kartus_alti_ay(): void
    {
        $this->assertSame(60, KkdDegisimSuresi::sure('Endüstriyel Emniyet Bareti')['ay']);
        $this->assertSame('5 yıl', KkdDegisimSuresi::sure('Emniyet Kemeri')['etiket']);
        $this->assertSame('5 yıl', KkdDegisimSuresi::sure('Düşme Durdurma Sistemi (EN 361)')['etiket']);
        $this->assertSame(6, KkdDegisimSuresi::sure('Filtreli Maske Kartuşu')['ay']);
        $this->assertNull(KkdDegisimSuresi::sure('İş Ayakkabısı'));

        $this->assertSame('2031-10-30', KkdDegisimSuresi::sonrakiDegisim('Baret', '2026-10-30'));
        $this->assertSame('2027-04-30', KkdDegisimSuresi::sonrakiDegisim('Maske kartuşu', '2026-10-30'));
        $this->assertNull(KkdDegisimSuresi::sonrakiDegisim('Eldiven', '2026-10-30'));
        $this->assertNull(KkdDegisimSuresi::sonrakiDegisim('Baret', null));
    }

    public function test_gorev_bazli_kkd_onerisi_matristen_gelir(): void
    {
        $uzman = User::factory()->create(['rol' => 'sahip']);
        $firma = Firma::factory()->for($uzman)->create();
        KkdMatrisi::firmaIcin($firma)->update(['satirlar' => [
            ['is_kalemi' => 'Kaynak İşleri', 'grup' => 'Atölye', 'baret' => 'zorunlu', 'eldiven' => 'zorunlu', 'ayakkabi' => 'zorunlu', 'maske' => 'duruma_gore', 'gozluk' => 'gerekmez'],
            ['is_kalemi' => 'Depo / Forklift Operasyonu', 'grup' => 'Depo', 'yelek' => 'zorunlu'],
        ]]);

        $o = KkdGorevOnerisi::oner($firma, 'Kaynakçı');

        $this->assertSame('Kaynak İşleri', $o['is_kalemi']);
        $this->assertSame(['baret', 'eldiven', 'ayakkabi'], array_keys($o['kalemler']));
        $this->assertSame('İş Ayakkabısı', KkdGorevOnerisi::turAdi('İş Ayakkabısı (EN ISO 20345 S3)'));
        $this->assertSame([], KkdGorevOnerisi::oner($firma, 'Muhasebeci')['kalemler']);
        $this->assertSame([], KkdGorevOnerisi::oner($firma, null)['kalemler']);
    }

    public function test_zimmet_formunda_onerilen_kkd_secilince_tur_ve_degisim_tarihi_dolar(): void
    {
        $uzman = User::factory()->create(['rol' => 'sahip']);
        $this->actingAs($uzman);
        $firma = Firma::factory()->for($uzman)->create();
        KkdMatrisi::firmaIcin($firma)->update(['satirlar' => [
            ['is_kalemi' => 'Kaynak İşleri', 'grup' => 'Atölye', 'baret' => 'zorunlu', 'eldiven' => 'zorunlu'],
        ]]);
        $c = Calisan::factory()->for($firma)->create(['ad_soyad' => 'Veli Kaya', 'gorev' => 'Kaynakçı']);

        Livewire::test(KkdSayfasi::class)
            ->set('firmaId', $firma->id)
            ->mountAction('yeniZimmet')
            ->fillForm(['calisan_id' => $c->id, 'teslim_tarihi' => '2026-10-30'])
            ->fillForm(['onerilen_kkd' => 'baret'])
            ->assertSchemaStateSet(['tur' => 'Baret', 'raf_omru' => '5 yıl', 'yenileme_tarihi' => '2031-10-30']);
    }
}
