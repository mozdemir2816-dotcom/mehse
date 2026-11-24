<?php

namespace Tests\Feature;

use App\Filament\Pages\IseDonusBelgesi;
use App\Filament\Pages\IsKazasiRaporu;
use App\Filament\Pages\KazaIstatistikleri;
use App\Filament\Pages\MeslekHastaligiBildirimi;
use App\Filament\Pages\MuayeneFormu;
use App\Filament\Pages\OlayKayitlari;
use App\Filament\Pages\OlayKomuta360;
use App\Filament\Pages\SaglikGozetimi as SaglikGozetimiSayfasi;
use App\Models\Calisan;
use App\Models\Firma;
use App\Models\OlayKaydi;
use App\Models\RevirKaydi;
use App\Models\SaglikGozetimi;
use App\Models\User;
use App\Support\KomutaFirma;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class Grup5IcerikVeMantikTest extends TestCase
{
    use RefreshDatabase;

    private User $uzman;

    private Firma $firma;

    protected function setUp(): void
    {
        parent::setUp();
        $this->uzman = User::factory()->create(['rol' => 'sahip']);
        $this->actingAs($this->uzman);
        $this->firma = Firma::factory()->for($this->uzman)->create(['unvan' => 'Grup5 Üretim A.Ş.']);
    }

    public function test_ek2_kaydedilince_saglik_gozetimi_satiri_otomatik_senkronlanir(): void
    {
        $calisan = Calisan::factory()->for($this->firma)->create(['ad_soyad' => 'Zeynep Arslan', 'gorev' => 'Operatör']);

        Livewire::test(MuayeneFormu::class)
            ->set('firmaId', $this->firma->id)
            ->set('calisanHizliSecId', $calisan->id)
            ->set('muayeneTuru', 'periyodik')
            ->set('muayeneTarihi', '2026-05-10')
            ->set('sonucKanaati', 'sartli_uygun')
            ->set('sartAciklamasi', 'Yüksekte çalışamaz')
            ->set('onerilenKontrolTarihi', '2027-05-10')
            ->callAction('kaydet');

        $gozetim = SaglikGozetimi::where('firma_id', $this->firma->id)->sole();
        $this->assertCount(1, $gozetim->satirlar);
        $this->assertSame($calisan->id, $gozetim->satirlar[0]['calisan_id']);
        $this->assertSame('periyodik', $gozetim->satirlar[0]['tetkik_turu']);
        $this->assertSame('sartli', $gozetim->satirlar[0]['sonuc']);
        $this->assertSame('2026-05-10', $gozetim->satirlar[0]['tarih']);
        $this->assertSame('2027-05-10', $gozetim->satirlar[0]['sonraki_tarih']);

        // Aynı çalışan için yeni periyodik EK-2 aynı satırı günceller (mükerrer oluşturmaz)
        Livewire::test(MuayeneFormu::class)
            ->set('firmaId', $this->firma->id)
            ->set('calisanHizliSecId', $calisan->id)
            ->set('muayeneTuru', 'periyodik')
            ->set('muayeneTarihi', '2026-06-01')
            ->set('sonucKanaati', 'uygun')
            ->set('onerilenKontrolTarihi', '2027-06-01')
            ->callAction('kaydet');

        $gozetim->refresh();
        $this->assertCount(1, $gozetim->satirlar);
        $this->assertSame('uygun', $gozetim->satirlar[0]['sonuc']);
        $this->assertSame('2026-06-01', $gozetim->satirlar[0]['tarih']);
    }

    public function test_kvkk_tibbi_veri_gizliligi_igu_uyesinde_taniyi_maskeler_hekimde_gosterir(): void
    {
        $iguUye = User::factory()->create(['rol' => 'uye', 'unvan' => 'a_sinifi_igu']);
        $iguUye->sayfaYetkileri()->create(['sayfa_anahtari' => 'saglik-gozetimi']);
        $hekimUye = User::factory()->create(['rol' => 'uye', 'unvan' => 'isyeri_hekimi']);
        $this->assertFalse($iguUye->tibbiVeriGorebilirMi());
        $this->assertTrue($hekimUye->tibbiVeriGorebilirMi());
        $this->assertTrue($this->uzman->tibbiVeriGorebilirMi());

        $iguFirma = Firma::factory()->for($iguUye)->create();
        RevirKaydi::create([
            'firma_id' => $iguFirma->id,
            'calisan_ad_soyad' => 'Ali Kaya',
            'tarih' => now(),
            'basvuru_nedeni' => 'Gizli Medikal Şikayet',
            'bulgu_tani' => 'Hipertansiyon Atağı',
            'sonuc' => 'ayakta',
        ]);

        $this->actingAs($iguUye);
        Livewire::test(SaglikGozetimiSayfasi::class)
            ->set('firmaId', $iguFirma->id)
            ->set('sekme', 'revir')
            ->assertSee('[Gizli — 6331 m.15/5]')
            ->assertDontSee('Hipertansiyon Atağı');
    }

    public function test_olay_kaydindan_is_kazasi_raporuna_aktarim_ve_komuta_firma(): void
    {
        KomutaFirma::sec($this->firma->id);

        foreach ([SaglikGozetimiSayfasi::class, MuayeneFormu::class, IseDonusBelgesi::class, MeslekHastaligiBildirimi::class, KazaIstatistikleri::class, OlayKomuta360::class] as $sayfa) {
            Livewire::test($sayfa)->assertSet('firmaId', $this->firma->id);
        }

        $calisan = Calisan::factory()->for($this->firma)->create(['ad_soyad' => 'Murat Demir', 'gorev' => 'Kaynakçı']);
        $olay = OlayKaydi::create([
            'firma_id' => $this->firma->id,
            'calisan_id' => $calisan->id,
            'olay_tipi' => 'is_kazasi',
            'etkilenen_ad_soyad' => 'Murat Demir',
            'etkilenen_gorev' => 'Kaynakçı',
            'olay_tarihi' => '2026-04-15',
            'olay_saati' => '10:30',
            'bolum' => 'Kaynak Atölyesi',
            'olay_ozeti' => 'Taşlama diski kırıldı',
            'bes_neden' => ['Koruyucu kapak sökülmüş', '', '', '', 'Denetim eksikliği'],
        ]);

        Livewire::test(OlayKayitlari::class)
            ->assertSet('firmaId', $this->firma->id)
            ->call('isKazasiRaporunaAktar', $olay->id)
            ->assertRedirect(IsKazasiRaporu::getUrl(['firma' => $this->firma->id]));

        Livewire::test(IsKazasiRaporu::class)
            ->assertSet('firmaId', $this->firma->id)
            ->assertSet('kazazedeHizliSecId', $calisan->id)
            ->assertSet('kazazedeAdSoyad', 'Murat Demir')
            ->assertSet('kazaTarihi', '2026-04-15')
            ->assertSet('kazaYeri', 'Kaynak Atölyesi')
            ->assertSet('kazaTanimi', 'Taşlama diski kırıldı');
    }
}
