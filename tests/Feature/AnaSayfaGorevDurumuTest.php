<?php

namespace Tests\Feature;

use App\Filament\Widgets\GorevDurumuWidget;
use App\Models\Calisan;
use App\Models\DofRaporu;
use App\Models\Firma;
use App\Models\IsEkipmani;
use App\Models\User;
use App\Support\GorevDurumu;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AnaSayfaGorevDurumuTest extends TestCase
{
    use RefreshDatabase;

    private User $uzman;

    private Firma $firma;

    protected function setUp(): void
    {
        parent::setUp();
        $this->uzman = User::factory()->create(['unvan' => 'c_sinifi']);
        $this->actingAs($this->uzman);
        $this->firma = Firma::factory()->for($this->uzman)->create(['unvan' => 'Alfa Yapı', 'tehlike_sinifi' => 'cok_tehlikeli', 'calisan_sayisi' => 10]);
    }

    public function test_panel_adi_ana_sayfa(): void
    {
        $this->get('/admin')->assertOk()->assertSee('Ana Sayfa');
        $this->assertSame('Ana Sayfa', \App\Filament\Pages\AnaSayfa::getNavigationLabel());
    }

    public function test_gorevler_dort_duruma_ayrilir(): void
    {
        // Kontrol Merkezi kriteri: vadesi geçmiş → günü geçen, vadesi yakın → yaklaşan
        $this->firma->checklistVadeleri()->create(['kriter_anahtari' => 'acil_durum_plani', 'vade_tarihi' => now()->subDays(5)]);
        $this->firma->checklistVadeleri()->create(['kriter_anahtari' => 'acil_durum_tatbikat', 'vade_tarihi' => now()->addDays(7)]);

        // Periyodik kontrol: vizesi dolmuş ekipman
        IsEkipmani::create([
            'firma_id' => $this->firma->id, 'kategori' => 'kaldirma', 'ekipman_adi' => 'Vinç',
            'muayene_periyodu_ay' => 12, 'son_muayene_tarihi' => now()->subMonths(13), 'sonraki_vize_tarihi' => now()->subMonth(),
        ]);

        // Açık DÖF maddesi termini yaklaşıyor
        DofRaporu::create(['firma_id' => $this->firma->id, 'maddeler' => [
            ['tespit' => 'Korkuluk eksik', 'termin' => now()->addDays(3)->toDateString(), 'durum' => 'acik'],
            ['tespit' => 'Kapandı', 'termin' => now()->subDays(3)->toDateString(), 'durum' => 'tamamlandi'],
        ]]);

        // Eğitim kaydı olmayan çalışan, işe girişi 4 ay önce → 3 ay geçti
        Calisan::factory()->for($this->firma)->create(['ad_soyad' => 'Mehmet', 'aktif' => true, 'ise_giris' => now()->subMonths(4)]);

        $gorevler = GorevDurumu::gorevler($this->uzman);
        $bul = fn (string $baslik, string $durum) => $gorevler->first(fn ($g) => $g['baslik'] === $baslik && $g['durum'] === $durum);

        $this->assertNotNull($bul('Acil Durum Planı Belgesi (ADP)', 'gecikmis'));
        $this->assertNotNull($bul('Acil Durum Tatbikat Tutanağı', 'yaklasan'));
        $this->assertNotNull($bul('Periyodik kontrol', 'gecikmis'));
        $dof = $bul('DÖF', 'yaklasan');
        $this->assertNotNull($dof);
        $this->assertStringContainsString('1 açık DÖF', $dof['aciklama']);
        $egitim = $bul('Eğitim kaydı eksik', 'gecikmis');
        $this->assertNotNull($egitim);
        $this->assertStringContainsString('3 ay geçti', $egitim['aciklama']);
        $this->assertNotNull($bul('Yıllık Çalışma Planı', 'yapilmayan'));
        $this->assertNotNull($bul('Periyodik Kontrol Raporu', 'yapilan') ?? $bul('Periyodik Kontrol Raporu', 'yapilmayan'));

        // Günü geçenler önce sıralanır
        $this->assertSame('gecikmis', $gorevler->first()['durum']);

        $o = GorevDurumu::ozet($gorevler);
        $this->assertSame($gorevler->count(), $o['gecikmis'] + $o['yaklasan'] + $o['yapilmayan'] + $o['yapilan']);
    }

    public function test_aylik_sure_ve_belge_sinifi_uyarisi(): void
    {
        Firma::factory()->for($this->uzman)->create(['tehlike_sinifi' => 'az_tehlikeli', 'calisan_sayisi' => 30]);
        Firma::factory()->for($this->uzman)->create(['tehlike_sinifi' => 'tehlikeli', 'calisan_sayisi' => 500, 'aktif' => false]);   // pasif sayılmaz

        $s = GorevDurumu::sureOzeti($this->uzman);

        $this->assertSame(10 * 40 + 30 * 10, $s['kullanilan_dk']);
        $this->assertSame(195 * 60 - 700, $s['kalan_dk']);
        $this->assertSame(['Alfa Yapı'], $s['sinif_uygunsuz']);   // C sınıfı çok tehlikeli işyerinde görev alamaz
    }

    public function test_widget_arama_ve_durum_raporu(): void
    {
        $diger = Firma::factory()->for($this->uzman)->create(['unvan' => 'Beta Gıda', 'tehlike_sinifi' => 'az_tehlikeli']);
        Firma::factory()->for(User::factory())->create(['unvan' => 'Başkasının Firması']);

        $w = Livewire::test(GorevDurumuWidget::class)
            ->assertSee('Aylık görevlendirme süresi')
            ->assertSee('Alfa Yapı')
            ->assertDontSee('Başkasının Firması');

        $w->set('arama', 'beta');
        $this->assertTrue($w->instance()->gorevler->isNotEmpty());
        $this->assertTrue($w->instance()->gorevler->every(fn ($g) => $g['firma_id'] === $diger->id));

        $w->call('durumRaporu')->assertFileDownloaded('gorev-durumu-'.now()->format('Y-m-d').'.txt');
        $this->assertStringContainsString('GÖREV DURUMU RAPORU', GorevDurumu::durumRaporu($this->uzman));
    }
}
