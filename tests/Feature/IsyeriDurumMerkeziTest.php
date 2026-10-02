<?php

namespace Tests\Feature;

use App\Filament\Pages\IsyeriDurumMerkezi;
use App\Models\Calisan;
use App\Models\DofRaporu;
use App\Models\Firma;
use App\Models\IsEkipmani;
use App\Models\OlayKaydi;
use App\Models\SaglikGozetimi;
use App\Models\User;
use App\Support\IsyeriDurumu;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class IsyeriDurumMerkeziTest extends TestCase
{
    use RefreshDatabase;

    private User $uzman;

    private Firma $firma;

    protected function setUp(): void
    {
        parent::setUp();
        $this->uzman = User::factory()->create();
        $this->actingAs($this->uzman);
        $this->firma = Firma::factory()->for($this->uzman)->create([
            'unvan' => 'Alfa İnşaat', 'tehlike_sinifi' => 'cok_tehlikeli', 'calisan_sayisi' => 12,
            'igu_id' => null, 'isyeri_hekimi_id' => null, 'sozlesme_bitis' => now()->addDays(5)->toDateString(),
        ]);
    }

    private function surec(string $ad): array
    {
        return collect(IsyeriDurumu::surecler($this->firma->fresh()))->firstWhere('surec', $ad);
    }

    public function test_surecler_gercek_kayitlardan_hesaplanir(): void
    {
        Calisan::factory()->for($this->firma)->create(['aktif' => true]);
        IsEkipmani::create(['firma_id' => $this->firma->id, 'kategori' => 'kaldirma', 'ekipman_adi' => 'Vinç', 'muayene_periyodu_ay' => 12,
            'son_muayene_tarihi' => now()->subMonths(13), 'sonraki_vize_tarihi' => now()->subMonth()]);
        DofRaporu::create(['firma_id' => $this->firma->id, 'maddeler' => [['tespit' => 'Korkuluk', 'termin' => now()->addDays(10)->toDateString(), 'durum' => 'acik']]]);
        SaglikGozetimi::create(['firma_id' => $this->firma->id, 'satirlar' => [['calisan' => 'Gizli Ad', 'tetkik' => 'Odyometri', 'sonraki_tarih' => now()->subDay()->toDateString()]]]);
        OlayKaydi::create(['firma_id' => $this->firma->id, 'olay_tipi' => 'is_kazasi', 'olay_tarihi' => now()->subDay(), 'sgk_bildirimi_yapildi' => false]);

        $this->assertSame('eksik', $this->surec('İSG profesyoneli görevlendirmeleri')['durum']);
        $this->assertStringContainsString('Atanmamış', $this->surec('İSG profesyoneli görevlendirmeleri')['sonuc']);
        $this->assertSame('tamamlandi', $this->surec('Çalışan kayıtları')['durum']);
        $this->assertStringContainsString('12 çalışan bildirilmiş', $this->surec('Çalışan kayıtları')['sonuc']);
        $this->assertSame('eksik', $this->surec('Risk değerlendirmesi')['durum']);
        $this->assertStringContainsString('2 yılda bir', $this->surec('Risk değerlendirmesi yenileme süresi')['sonuc']);
        $this->assertSame('gecikmis', $this->surec('Periyodik kontroller')['durum']);
        $this->assertSame('yaklasan', $this->surec('Düzeltici ve önleyici faaliyetler')['durum']);
        $this->assertSame('gecikmis', $this->surec('Sağlık gözetimi')['durum']);
        $this->assertStringNotContainsString('Gizli Ad', $this->surec('Sağlık gözetimi')['sonuc']);
        $this->assertSame('yaklasan', $this->surec('İş kazası ve ramak kala kayıtları')['durum']);
        $this->assertSame('bilgi', $this->surec('İSG Kurulu toplantıları')['durum']);   // 50 altı

        $o = IsyeriDurumu::ozet(IsyeriDurumu::surecler($this->firma->fresh()));
        $this->assertSame('Kritik eksikler var', $o['genel']);
        $this->assertGreaterThan(0, $o['gecikmis']);
    }

    public function test_yukumluluk_takvimi_sirali_ve_saglik_adsiz(): void
    {
        IsEkipmani::create(['firma_id' => $this->firma->id, 'kategori' => 'kaldirma', 'ekipman_adi' => 'Forklift', 'muayene_periyodu_ay' => 12,
            'son_muayene_tarihi' => now()->subMonths(13), 'sonraki_vize_tarihi' => now()->subMonth()]);
        SaglikGozetimi::create(['firma_id' => $this->firma->id, 'satirlar' => [
            ['calisan' => 'Gizli Ad', 'sonraki_tarih' => now()->addDays(20)->toDateString()],
            ['calisan' => 'Diğer Ad', 'sonraki_tarih' => now()->addDays(20)->toDateString()],
        ]]);
        OlayKaydi::create(['firma_id' => $this->firma->id, 'olay_tipi' => 'is_kazasi', 'belge_no' => 'IK-1', 'olay_tarihi' => now()->subDays(10), 'sgk_bildirimi_yapildi' => true]);

        $takvim = IsyeriDurumu::takvim($this->firma);

        $this->assertSame('gecikmis', $takvim->first()['durum']);
        $this->assertSame('Forklift', $takvim->first()['kayit']);
        $this->assertNotNull($takvim->first(fn ($t) => $t['kategori'] === 'Sözleşme' && $t['durum'] === 'cok_yakin'));
        $saglik = $takvim->firstWhere('kategori', 'Sağlık');
        $this->assertSame('2 kişi', $saglik['alt']);
        $this->assertSame('tamamlandi', $takvim->firstWhere('kategori', 'Olay / SGK bildirimi')['durum']);
        $this->assertFalse($takvim->contains(fn ($t) => str_contains(json_encode($t), 'Gizli Ad')));
    }

    public function test_sayfa_filtre_sayfalama_ve_disa_aktarim(): void
    {
        $diger = Firma::factory()->for($this->uzman)->create(['unvan' => 'Beta']);
        Firma::factory()->for(User::factory())->create(['unvan' => 'Yabancı Firma']);
        foreach (range(1, 12) as $i) {
            IsEkipmani::create(['firma_id' => $this->firma->id, 'kategori' => 'kaldirma', 'ekipman_adi' => "Ekipman {$i}", 'muayene_periyodu_ay' => 12,
                'son_muayene_tarihi' => now()->subMonths(6), 'sonraki_vize_tarihi' => now()->addMonths(6)]);
        }

        $sayfa = Livewire::test(IsyeriDurumMerkezi::class, [])
            ->set('firmaId', $this->firma->id)
            ->assertSee('Genel uyum durumu')
            ->assertSee('Eksikler, tamamlanan süreçler ve sorumlular')
            ->assertDontSee('Yabancı Firma');

        $sayfa->set('sayfaBoyutu', 10);
        $this->assertCount(10, $sayfa->instance()->takvimSayfasi());
        $sayfa->call('sayfaDegistir', 1);
        $this->assertSame(2, $sayfa->get('takvimSayfa'));

        $sayfa->set('takvimKategori', 'Periyodik kontrol');
        $this->assertSame(1, $sayfa->get('takvimSayfa'));
        $this->assertSame(12, $sayfa->instance()->filtreliTakvim->count());

        $sayfa->call('takvimSifirla')->assertSet('takvimKategori', '');

        $sayfa->call('tamPdf')->assertFileDownloaded('firma-dosyasi-alfa-insaat.pdf');
        $sayfa->call('detayliExcel')->assertFileDownloaded('firma-dosyasi-alfa-insaat.xlsx');

        // Başkasının firması seçilemez
        $sayfa->set('firmaId', Firma::where('unvan', 'Yabancı Firma')->value('id'));
        $this->assertNull($sayfa->instance()->firma);
    }

    public function test_firma_parametresiyle_acilir(): void
    {
        $this->get(IsyeriDurumMerkezi::getUrl(['firma' => $this->firma->id]))->assertOk()->assertSee('Alfa İnşaat');
    }
}
