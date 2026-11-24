<?php

namespace Tests\Feature;

use App\Filament\Pages\DofOlustur;
use App\Filament\Pages\IsIzinFormu as IsIzinSayfasi;
use App\Filament\Pages\OnayliDefterNushalari;
use App\Filament\Pages\TespitOneriDefteri as DefterSayfasi;
use App\Filament\Pages\ZiyaretModu;
use App\Models\DofRaporu;
use App\Models\Firma;
use App\Models\IsgProfesyoneli;
use App\Models\IsIzinFormu;
use App\Models\SahaBulgusu;
use App\Models\TespitOneriDefteri;
use App\Models\User;
use App\Support\KomutaFirma;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class Grup3IcerikVeMantikTest extends TestCase
{
    use RefreshDatabase;

    private User $uzman;

    private Firma $firma;

    protected function setUp(): void
    {
        parent::setUp();
        $this->uzman = User::factory()->create();
        $this->actingAs($this->uzman);
        $this->firma = Firma::factory()->for($this->uzman)->create(['unvan' => 'Grup3 İnşaat A.Ş.']);
    }

    public function test_yuksek_kritik_bulgu_ve_dof_kanitsiz_kapatilamaz_orta_serbesttir(): void
    {
        $kritikBulgu = SahaBulgusu::create([
            'firma_id' => $this->firma->id,
            'uygunsuzluk' => 'Yüksekte yaşam hattı yok',
            'olasilik' => 5,
            'siddet' => 5,
            'oncelik' => 'kritik',
            'durum' => 'acik',
        ]);
        $ortaBulgu = SahaBulgusu::create([
            'firma_id' => $this->firma->id,
            'uygunsuzluk' => 'Depo düzeni dağınık',
            'olasilik' => 2,
            'siddet' => 2,
            'oncelik' => 'orta',
            'durum' => 'acik',
        ]);
        $dof = DofRaporu::create([
            'firma_id' => $this->firma->id,
            'maddeler' => [
                ['tespit' => 'Açık pano', 'oncelik' => 'yuksek', 'durum' => 'acik'],
            ],
        ]);

        // ZiyaretModu: kanıtsız (fotoğraf ve not yok) yüksek/kritik kapanmaz
        Livewire::test(ZiyaretModu::class, ['firmaId' => $this->firma->id])
            ->call('bulguKapat', $kritikBulgu->id)
            ->call('dofKapat', $dof->id, 0)
            ->call('bulguKapat', $ortaBulgu->id);

        $this->assertSame('acik', $kritikBulgu->fresh()->durum);
        $this->assertSame('acik', $dof->fresh()->maddeler[0]['durum']);
        $this->assertSame('kapandi', $ortaBulgu->fresh()->durum);

        // Kapanış notu girilince yüksek/kritik kapanır
        Livewire::test(ZiyaretModu::class, ['firmaId' => $this->firma->id])
            ->set('kapanisNot.b'.$kritikBulgu->id, 'Yaşam hattı çekildi ve yerinde kontrol edildi.')
            ->call('bulguKapat', $kritikBulgu->id)
            ->set('kapanisNot.d'.$dof->id.'_0', 'Pano kilitlendi ve paspas konuldu.')
            ->call('dofKapat', $dof->id, 0);

        $this->assertSame('kapandi', $kritikBulgu->fresh()->durum);
        $this->assertSame('tamamlandi', $dof->fresh()->maddeler[0]['durum']);

        // DofOlustur: yüksek madde not/foto olmadan tamamlandi olmaz
        $sayfa = Livewire::test(DofOlustur::class)
            ->set('firmaId', $this->firma->id)
            ->set('yeniTespit', 'Korkuluk eksik')
            ->set('yeniOncelik', 'yuksek')
            ->call('maddeEkle')
            ->call('durumGuncelle', 0, 'tamamlandi');

        $this->assertSame('acik', $sayfa->get('maddeler')[0]['durum']);
        $sayfa->call('durumGuncelle', 0, 'tamamlandi', 'Korkuluk monte edildi');
        $this->assertSame('tamamlandi', $sayfa->get('maddeler')[0]['durum']);
    }

    public function test_ptw_gaz_loto_kaydedilir_ve_ayni_onayci_sod_engellenir(): void
    {
        KomutaFirma::sec($this->firma->id);

        // KomutaFirma otomatik yüklenir; aynı kişi 1. ve 2. onaycı olursa kayıt engellenir
        Livewire::test(IsIzinSayfasi::class)
            ->assertSet('firmaId', $this->firma->id)
            ->call('izinTuruToggle', 'kapali_alan')
            ->set('calismaAlani', 'Tank İçi')
            ->set('onay1Ad', 'Ahmet Yılmaz')
            ->set('onay2Ad', 'ahmet yılmaz')
            ->callAction('pdf');

        $this->assertDatabaseCount('is_izin_formlari', 0);

        // Farklı onaycılar + gaz/LOTO ölçümü ile kaydedilir ve PDF'te görünür
        Livewire::test(IsIzinSayfasi::class)
            ->set('firmaId', $this->firma->id)
            ->call('izinTuruToggle', 'kapali_alan')
            ->call('izinTuruToggle', 'elektrik')
            ->set('calismaAlani', 'Tank İçi')
            ->set('gazO2', '20.9')
            ->set('gazLel', '0')
            ->set('lotoKilitNo', 'LOTO-42')
            ->set('onay1Ad', 'Ahmet Yılmaz')
            ->set('onay2Ad', 'Mehmet Demir')
            ->callAction('pdf');

        $form = IsIzinFormu::where('firma_id', $this->firma->id)->sole();
        $this->assertSame('20.9', $form->olcumVerileri()['gaz_o2']);
        $this->assertSame('LOTO-42', $form->olcumVerileri()['loto_kilit_no']);

        $html = view('pdf.is-izin-formu', ['form' => $form, 'firma' => $this->firma])->render();
        $this->assertStringContainsString('O₂: 20.9%', $html);
        $this->assertStringContainsString('LOTO-42', $html);

        // Onaylı defter nüshaları da KomutaFirma'yı hatırlar
        Livewire::test(OnayliDefterNushalari::class)
            ->assertSet('firmaId', $this->firma->id);
    }

    public function test_tespit_oneri_defteri_hayati_tehlike_hekim_imzasi_ve_nusha_kilidi(): void
    {
        $hekim = IsgProfesyoneli::factory()->for($this->uzman)->create([
            'tip' => 'hekim',
            'ad_soyad' => 'Dr. Selin Kaya',
            'unvan' => 'İşyeri Hekimi',
            'sertifika_no' => 'HK-9988',
        ]);
        $this->firma->update(['isyeri_hekimi_id' => $hekim->id]);

        Livewire::test(DefterSayfasi::class)
            ->set('firmaId', $this->firma->id)
            ->set('serbestTespit', 'İskele bağlantısı kopuk, yıkılma riski var.')
            ->set('serbestOneri', 'Çalışma derhal durdurulmalıdır.')
            ->set('serbestHayatiTehlike', true)
            ->call('serbestEkle')
            ->callAction('nushaKilitle', [
                'seri_sayfa_no' => 'Seri B · Sayfa 5-6',
                'onay_makami' => '12. Noter',
            ])
            ->call('maddeSil', 0); // Kilitli madde silinemez

        $defter = TespitOneriDefteri::where('firma_id', $this->firma->id)->sole();
        $this->assertCount(1, $defter->maddeler);
        $this->assertTrue($defter->maddeler[0]['hayati_tehlike']);
        $this->assertTrue($defter->maddeler[0]['kilitli']);

        $html = view('pdf.tespit-oneri-defteri', ['defter' => $defter, 'firma' => $this->firma->fresh()])->render();
        $this->assertStringContainsString('HAYATİ TEHLİKE — 6331 m.8/2', $html);
        $this->assertStringContainsString('Dr. Selin Kaya', $html);
        $this->assertStringContainsString('Seri B · Sayfa 5-6', $html);
        $this->assertStringContainsString('12. Noter', $html);
    }
}
