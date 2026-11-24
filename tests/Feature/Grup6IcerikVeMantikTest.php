<?php

namespace Tests\Feature;

use App\Filament\Pages\AcilDurumPlani;
use App\Filament\Pages\AtamaYazilari;
use App\Filament\Pages\CalisanTemsilcisiSecimi as SecimSayfasi;
use App\Filament\Pages\CezaTeblig;
use App\Filament\Pages\DofOlustur;
use App\Filament\Pages\GeciciGorevlendirme;
use App\Filament\Pages\KurulToplantisi as KurulSayfasi;
use App\Filament\Pages\TatbikatTutanagi;
use App\Filament\Pages\YanginGuvenligi;
use App\Models\ArsivDosya;
use App\Models\CalisanTemsilcisiSecimi;
use App\Models\Firma;
use App\Models\KurulToplantisi;
use App\Models\User;
use App\Support\KomutaFirma;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class Grup6IcerikVeMantikTest extends TestCase
{
    use RefreshDatabase;

    private User $uzman;

    private Firma $firma;

    protected function setUp(): void
    {
        parent::setUp();
        $this->uzman = User::factory()->create(['rol' => 'sahip']);
        $this->actingAs($this->uzman);
        $this->firma = Firma::factory()->for($this->uzman)->create(['unvan' => 'Grup6 Sanayi A.Ş.', 'calisan_sayisi' => 120]);
    }

    public function test_coklu_calisan_temsilcisi_ve_bas_temsilci_secilir_ve_tutanaga_yansir(): void
    {
        Livewire::test(SecimSayfasi::class)
            ->set('firmaId', $this->firma->id)
            ->set('zorunluTemsilciSayisi', 3)
            ->set('adaylar', [
                ['ad_soyad' => 'Ahmet Yılmaz', 'unvan' => 'Usta', 'secildi' => false, 'bas_temsilci' => false],
                ['ad_soyad' => 'Fatma Demir', 'unvan' => 'Teknisyen', 'secildi' => false, 'bas_temsilci' => false],
                ['ad_soyad' => 'Ali Kaya', 'unvan' => 'Operatör', 'secildi' => false, 'bas_temsilci' => false],
            ])
            ->call('kazananSec', 0)
            ->call('kazananSec', 1)
            ->call('basTemsilciSec', 1)
            ->call('kaydetVeBildir');

        $secim = CalisanTemsilcisiSecimi::where('firma_id', $this->firma->id)->sole();
        $this->assertCount(2, $secim->secilenAdaylar());
        $this->assertSame('Fatma Demir', $secim->basTemsilci()['ad_soyad']);
        $this->assertSame('Fatma Demir', $secim->secilenAday()['ad_soyad']);

        $html = view('pdf.calisan-temsilcisi-tutanak', ['secim' => $secim, 'firma' => $this->firma])->render();
        $this->assertStringContainsString('Fatma Demir', $html);
        $this->assertStringContainsString('Ahmet Yılmaz', $html);
        $this->assertStringContainsString('Baş Temsilci — 6331 m.20/2', $html);
    }

    public function test_yangin_tespitleri_dofe_aktarilir_ve_gecici_gorevlendirme_arsive_kaydedilir(): void
    {
        Storage::fake('public');

        // Yangın → DÖF aktarımı
        Livewire::test(YanginGuvenligi::class)
            ->set('firmaId', $this->firma->id)
            ->call('serbestTespitEkle', 'Yangın dolabı hortumu yırtık')
            ->call('dofeAktar')
            ->assertRedirect(DofOlustur::getUrl(['firma' => $this->firma->id]));

        $this->assertSame('Yangın Güvenliği Değerlendirmesi', session('dof_aktarim.kaynak'));
        $this->assertStringContainsString('Yangın dolabı hortumu yırtık', session('dof_aktarim.maddeler.0.tespit'));

        // Geçici Görevlendirme → OSGB Arşivi otomatik kayıt
        Livewire::test(GeciciGorevlendirme::class)
            ->set('firmaId', $this->firma->id)
            ->set('gorevYeri', 'Gebze Şantiyesi')
            ->set('gorevKonusu', 'Pano montajı')
            ->set('yeniAdSoyad', 'Mehmet Öz')
            ->call('manuelCalisanEkle')
            ->call('pdfIndir');

        $arsiv = ArsivDosya::where('firma_id', $this->firma->id)->sole();
        $this->assertStringContainsString('Gebze Şantiyesi', $arsiv->baslik);
        Storage::disk('public')->assertExists($arsiv->dosya_yolu);
    }

    public function test_tamamlanmis_kurul_toplantisi_kilitlenir_ve_revizyona_acilir(): void
    {
        KomutaFirma::sec($this->firma->id);

        foreach ([AcilDurumPlani::class, TatbikatTutanagi::class, AtamaYazilari::class, CezaTeblig::class, KurulSayfasi::class] as $sayfa) {
            Livewire::test($sayfa)->assertSet('firmaId', $this->firma->id);
        }

        $toplanti = KurulToplantisi::create([
            'firma_id' => $this->firma->id,
            'toplanti_no' => '2026/01',
            'tarih' => now()->toDateString(),
            'durum' => 'tamamlandi',
            'gundem' => ['Eski gündem maddesi'],
            'kararlar' => [['id' => 'k1', 'gundem_maddesi' => 'Eski gündem maddesi', 'karar_metni' => 'Kask kontrolü', 'durum' => 'beklemede']],
            'katilimcilar' => [['ad_soyad' => 'Ali', 'gorev' => 'İGU', 'rol' => 'sekreter', 'katildi' => true]],
        ]);

        // Kilitli toplantıda gündem/karar ekleme, silme ve toplantı silme engellenir
        $sayfa = Livewire::test(KurulSayfasi::class, ['firmaId' => $this->firma->id, 'toplantiId' => $toplanti->id])
            ->set('yeniGundemMaddesi', 'Kilitli iken eklenmemeli')
            ->call('gundemEkle')
            ->call('gundemSil', 0)
            ->call('kararSil', 0)
            ->call('toplantiSil', $toplanti->id);

        $toplanti->refresh();
        $this->assertCount(1, $toplanti->gundem);
        $this->assertCount(1, $toplanti->kararlar);

        // Ama karar takip durumu (beklemede -> tamamlandi) güncellenebilir
        $sayfa->call('kararDurumGuncelle', 0, 'tamamlandi');
        $this->assertSame('tamamlandi', $toplanti->fresh()->kararlar[0]['durum']);

        // Revizyona açılınca tekrar düzenlenebilir
        $sayfa->call('revizyonaAc', $toplanti->id)
            ->set('yeniGundemMaddesi', 'Yeni gündem')
            ->call('gundemEkle');

        $this->assertSame('taslak', $toplanti->fresh()->durum);
        $this->assertCount(2, $toplanti->fresh()->gundem);
    }
}
