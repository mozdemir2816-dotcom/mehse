<?php

namespace Tests\Feature;

use App\Filament\Pages\TopluMakinePaketi;
use App\Models\EkipmanAfisi;
use App\Models\Firma;
use App\Models\IsEkipmani;
use App\Models\OzelEkipmanTipi;
use App\Models\TalimatSablonu;
use App\Models\Tehlike;
use App\Models\User;
use App\Support\EkipmanKatalogu;
use App\Support\OzelEkipmanTipleri;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/** 21.11.2026: Toplu Makine Paketi — istem, Gemini JSON'unu kurma, config'e ekleme, onay. */
class TopluMakinePaketiTest extends TestCase
{
    use RefreshDatabase;

    private User $uzman;

    protected function setUp(): void
    {
        parent::setUp();
        OzelEkipmanTipleri::onbellegiTemizle();
        $this->uzman = User::factory()->create(['rol' => 'sahip']);
        $this->actingAs($this->uzman);
    }

    private function cevap(string $ad = 'Karot Makinesi'): string
    {
        $veri = [[
            'makine' => $ad, 'dosya_adi' => 'karot-makinesi', 'kategori' => 'uretim_atolye', 'periyot_ay' => 12,
            'standart' => 'TS EN 12348', 'deney' => 'Periyodik muayene yok — kullanım öncesi kontrol formu',
            'kontrol_sorulari' => ['Karot ucu sağlam mı?', 'Su soğutma hattı çalışıyor mu?'],
            'talimat' => [
                ['baslik' => 'AMAÇ VE KAPSAM', 'aciklama' => 'Karot makinesinin güvenli kullanımı.', 'maddeler' => []],
                ['baslik' => 'YASAKLAR', 'aciklama' => '', 'maddeler' => ['Islak elle fişe dokunulmamalıdır.']],
            ],
            'riskler' => [
                ['faaliyet' => 'Delme', 'tehlike' => 'Ucun sıkışması', 'risk' => 'Bilek burkulması', 'oneri' => 'Kavramalı makine kullanın', 'sorumlu' => 'İşveren',
                    'olasilik' => 2.5, 'frekans' => 5, 'siddet' => 20, 'son_olasilik' => 1, 'son_frekans' => 6, 'son_siddet' => 15],
            ],
            'afis' => ['yapin' => ['Ucu sıkın', 'Suyu açın'], 'yapmayin' => ['Tek elle tutmayın'], 'kkd' => ['Baret', 'Koruyucu gözlük', 'Pelerin']],
            'resim_sahnesi' => 'bir çalışan duvara karot ile delik açıyor',
        ]];

        return "İşte paket:\n```json\n".json_encode($veri, JSON_UNESCAPED_UNICODE)."\n```";
    }

    public function test_istem_yalniz_yeni_makineler_icin_ve_dorderli_parca(): void
    {
        $ayrim = OzelEkipmanTipleri::ayir("Forklift\nKarot Makinesi\nkarot makinesi\nA\nB\nC\nD");

        $this->assertSame('Forklift', $ayrim['mevcut'][0]['ad']);
        $this->assertSame(['Karot Makinesi', 'A', 'B', 'C', 'D'], $ayrim['yeni']);
        $istemler = OzelEkipmanTipleri::istemler($ayrim['yeni']);
        $this->assertCount(2, $istemler);
        $this->assertStringContainsString('dosya_adi: karot-makinesi', $istemler[0]);
        $this->assertStringContainsString('parça 2/2', $istemler[1]);
    }

    public function test_json_kurulur_ve_tip_her_listede_gorunur(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();
        IsEkipmani::create(['firma_id' => $firma->id, 'kategori' => 'uretim_atolye', 'tip' => 'Karot Makinesi', 'ekipman_adi' => 'Karot 1', 'aktif' => true]);

        $sonuc = OzelEkipmanTipleri::kur($this->cevap());

        $this->assertSame(['Karot Makinesi'], $sonuc['kurulan']);
        $tip = OzelEkipmanTipi::where('ad', 'Karot Makinesi')->first();
        $this->assertTrue($tip->onayBekliyorMu());

        // Config'e eklendi: seçim listesi, günlük form, katalog, afiş.
        $this->assertContains('Karot Makinesi', collect(config('isg.periyodik_kontrol.tipler.uretim_atolye'))->pluck('ad'));
        $this->assertSame(['Karot ucu sağlam mı?', 'Su soğutma hattı çalışıyor mu?'], EkipmanKatalogu::kontrolFormu('Karot Makinesi')['sorular']);
        $this->assertSame('Karot Makinesi', config('ekipman_afis.tipler.karot-makinesi.ad'));

        // Talimat kütüphanede taslak, firmaya eklendi; risk FK ölçeğine yuvarlandı; afiş yazısı ve geçerli KKD.
        $this->assertTrue((bool) TalimatSablonu::where('ekipman_tipi', 'Karot Makinesi')->value('yz_taslak'));
        $this->assertSame(1, $firma->talimatlar()->count());
        $r = Tehlike::where('bolum', 'Karot Makinesi')->first();
        $this->assertEquals([3.0, 6.0, 15.0], [(float) $r->olasilik, (float) $r->frekans, (float) $r->siddet]);
        $this->assertSame(['Baret', 'Koruyucu gözlük'], EkipmanAfisi::where('anahtar', 'karot-makinesi')->value('kkd'));

        // Yeniden kurmak mükerrer risk klasörü açmaz.
        OzelEkipmanTipleri::kur($this->cevap());
        $this->assertSame(1, Tehlike::where('bolum', 'Karot Makinesi')->count());
        $this->assertSame(1, OzelEkipmanTipi::count());
    }

    public function test_config_te_olan_tip_ve_bozuk_json_kurulmaz(): void
    {
        $this->assertSame(['Forklift / Akülü & Dizel İstifleyici: sistemde zaten var, atlandı'], OzelEkipmanTipleri::kur($this->cevap('Forklift / Akülü & Dizel İstifleyici'))['hatalar']);
        $this->assertNotEmpty(OzelEkipmanTipleri::kur('Gemini cevap vermedi')['hatalar']);
        $this->assertSame(0, OzelEkipmanTipi::count());
    }

    public function test_sayfa_akisi_ve_onay(): void
    {
        Livewire::test(TopluMakinePaketi::class)
            ->set('makineler', "Karot Makinesi\nForklift")
            ->call('istemleriHazirla')
            ->assertSee('İstem 1')
            ->assertSee('Paketi zaten olan')
            ->set('cevap', $this->cevap())
            ->call('kur')
            ->assertSee('Karot Makinesi')
            ->assertSee('karot-makinesi.png')
            ->assertSee('Paketi onayla');

        $tip = OzelEkipmanTipi::first();
        Livewire::test(TopluMakinePaketi::class)->call('onayla', $tip->id)->assertSee('Onaylı');
        $this->assertFalse((bool) TalimatSablonu::where('ekipman_tipi', 'Karot Makinesi')->value('yz_taslak'));
        $this->assertNotNull(EkipmanAfisi::where('anahtar', 'karot-makinesi')->value('onaylandi_at'));

        $this->actingAs(User::factory()->create(['rol' => 'uzman']));
        $this->get(TopluMakinePaketi::getUrl())->assertForbidden();
    }
}
