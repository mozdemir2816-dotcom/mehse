<?php

namespace Tests\Feature;

use App\Filament\Pages\AcilDurumKrokisi as KrokiSayfasi;
use App\Filament\Pages\AcilDurumPlani as PlanSayfasi;
use App\Models\AcilDurumKrokisi;
use App\Models\AcilDurumPlani;
use App\Models\Firma;
use App\Models\TatbikatTutanagi;
use App\Models\User;
use App\Support\AcilDurumHazirlik;
use App\Support\AcilDurumKrokisiUretici;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Tests\TestCase;

class AcilDurumKrokisiTest extends TestCase
{
    use RefreshDatabase;

    private User $uzman;

    protected function setUp(): void
    {
        parent::setUp();
        $this->uzman = User::factory()->create();
        $this->actingAs($this->uzman);
    }

    /** 1x1 şeffaf PNG data URL. */
    private function png(): string
    {
        return 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAAASUVORK5CYII=';
    }

    private function veri(): array
    {
        return [
            'duvarlar' => [['x1' => 100, 'y1' => 100, 'x2' => 600, 'y2' => 100]],
            'semboller' => [
                ['tip' => 'sondurucu', 'x' => 120, 'y' => 340, 'r' => 45, 's' => 1.4, 'etiket' => 'YS-1'],
                ['tip' => 'yok_boyle_tip', 'x' => 1, 'y' => 1],                  // bilinmeyen tip atılır
                ['tip' => 'cikis', 'x' => 99999, 'y' => -99999, 's' => 50],      // sınırlanır
            ],
            'ogeler' => [
                'odalar' => [['x' => 10, 'y' => 10, 'w' => 300, 'h' => 200, 'etiket' => '<b>ÜRETİM</b>', 'renk' => 'kırmızı']],
                'yollar' => [['noktalar' => [[10, 10], [200, 10], [200, 300]]], ['noktalar' => [[5, 5]]]],   // tek noktalı yol atılır
                'metinler' => [['x' => 50, 'y' => 60, 'metin' => 'TOPLANMA ALANI', 'boyut' => 500, 'renk' => '#15803d']],
            ],
            'antet' => ['baslik' => 'ZEMİN KAT TAHLİYE', 'kat' => 'Zemin', 'revizyon' => '01', 'izgara' => false],
        ];
    }

    public function test_firma_icin_bos_kroki_olusur(): void
    {
        $kroki = AcilDurumKrokisi::firmaIcin(Firma::factory()->for($this->uzman)->create());

        $this->assertTrue($kroki->exists);
        $this->assertSame([], $kroki->duvarlar);
        $this->assertSame(['odalar' => [], 'yollar' => [], 'metinler' => []], $kroki->ogeler);
        $this->assertSame(2, $kroki->antet['surum']);
    }

    public function test_kaydet_veriyi_temizler_ve_png_saklar(): void
    {
        Storage::fake('public');
        $firma = Firma::factory()->for($this->uzman)->create();

        Livewire::test(KrokiSayfasi::class)
            ->set('firmaId', $firma->id)
            ->call('kaydet', $this->veri(), $this->png())
            ->assertNotified();

        $k = AcilDurumKrokisi::where('firma_id', $firma->id)->sole();
        $this->assertCount(1, $k->duvarlar);
        $this->assertCount(2, $k->semboller);
        $this->assertSame(['tip' => 'sondurucu', 'x' => 120, 'y' => 340, 'r' => 45, 's' => 1.4, 'etiket' => 'YS-1'], $k->semboller[0]);
        $this->assertEquals(4000, $k->semboller[1]['x']);
        $this->assertEquals(4, $k->semboller[1]['s']);
        $this->assertSame('ÜRETİM', $k->ogeler['odalar'][0]['etiket']);     // HTML temizlendi
        $this->assertSame('#e2e8f0', $k->ogeler['odalar'][0]['renk']);      // geçersiz renk → varsayılan
        $this->assertCount(1, $k->ogeler['yollar']);
        $this->assertSame(72, $k->ogeler['metinler'][0]['boyut']);
        $this->assertSame('ZEMİN KAT TAHLİYE', $k->antet['baslik']);
        $this->assertFalse($k->antet['izgara']);
        $this->assertNotNull($k->gorsel_yolu);
        Storage::disk('public')->assertExists($k->gorsel_yolu);

        // Yeniden kayıtta eski PNG silinir; PNG olmayan veri dosya olarak yazılmaz
        $eski = $k->gorsel_yolu;
        $this->travel(2)->seconds();
        Livewire::test(KrokiSayfasi::class)->set('firmaId', $firma->id)->call('kaydet', $this->veri(), $this->png());
        Storage::disk('public')->assertMissing($eski);
        $yeni = $k->fresh()->gorsel_yolu;
        $this->travel(2)->seconds();
        Livewire::test(KrokiSayfasi::class)->set('firmaId', $firma->id)->call('kaydet', $this->veri(), 'data:image/png;base64,'.base64_encode('<?php echo 1;'));
        $this->assertSame($yeni, $k->fresh()->gorsel_yolu);
        $this->assertCount(1, Storage::disk('public')->files('acil-durum-kroki'));
    }

    public function test_eski_surum_kroki_yeni_tuvale_olceklenir(): void
    {
        $kroki = AcilDurumKrokisi::firmaIcin(Firma::factory()->for($this->uzman)->create());
        $kroki->forceFill(['antet' => null, 'duvarlar' => [['x1' => 100, 'y1' => 100, 'x2' => 500, 'y2' => 100]], 'semboller' => [['tip' => 'cikis', 'x' => 1000, 'y' => 700, 'etiket' => null]]])->save();

        $v = $kroki->fresh()->editorVerisi();

        $this->assertEquals(140, $v['duvarlar'][0]['x1']);
        $this->assertEquals(1400, $v['semboller'][0]['x']);
        $this->assertEquals(980, $v['semboller'][0]['y']);
        $this->assertSame(2, $v['antet']['surum']);
    }

    public function test_sayfa_editoru_yukler_ve_baska_firma_secilemez(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create(['unvan' => 'Kroki Firması']);
        $baska = Firma::factory()->for(User::factory())->create();

        $this->get(KrokiSayfasi::getUrl(['firma' => $firma->id]))->assertOk()
            ->assertSee('krokiEditoru', false)->assertSee('Kaçış Yolu')->assertSee('Antet');

        $c = Livewire::test(KrokiSayfasi::class)->set('firmaId', $baska->id);
        $this->assertNull($c->instance()->firma);
        $c->call('kaydet', $this->veri());
        $this->assertSame(0, AcilDurumKrokisi::where('firma_id', $baska->id)->count());
    }

    public function test_pdf_png_varsa_onu_yoksa_svg_cizimi_basar(): void
    {
        Storage::fake('public');
        $firma = Firma::factory()->for($this->uzman)->create();
        $kroki = AcilDurumKrokisi::firmaIcin($firma);
        $kroki->update(['semboller' => [['tip' => 'cikis', 'x' => 10, 'y' => 10, 'etiket' => null]]]);

        foreach ([null, $this->png()] as $png) {
            if ($png) {
                Livewire::test(KrokiSayfasi::class)->set('firmaId', $firma->id)->call('kaydet', $this->veri(), $png);
            }
            $yanit = AcilDurumKrokisiUretici::pdf($kroki->fresh());
            $this->assertInstanceOf(StreamedResponse::class, $yanit);
            ob_start();
            $yanit->sendContent();
            $this->assertStringStartsWith('%PDF', ob_get_clean());
        }
    }

    public function test_lejant_sembol_tiplerine_gore_gruplar(): void
    {
        $kroki = AcilDurumKrokisi::firmaIcin(Firma::factory()->for($this->uzman)->create());
        $kroki->semboller = [
            ['tip' => 'cikis', 'x' => 1, 'y' => 1, 'etiket' => null],
            ['tip' => 'cikis', 'x' => 2, 'y' => 2, 'etiket' => null],
            ['tip' => 'sondurucu', 'x' => 3, 'y' => 3, 'etiket' => null],
        ];

        $lejant = collect($kroki->lejant())->keyBy('tip');

        $this->assertSame(2, $lejant['cikis']['adet']);
        $this->assertSame(1, $lejant['sondurucu']['adet']);
    }

    // ---------- Acil durum planı: uygulama + hazırlık kontrolü ----------

    public function test_plan_uygulama_bilgisi_kaydedilir_ve_hazirlik_hesaplanir(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create(['unvan' => 'Hazırlık AŞ']);

        Livewire::test(PlanSayfasi::class)
            ->set('firmaId', $firma->id)
            ->assertSee('Risk, tedbir ve uygulama')
            ->set('toplanmaYeri', 'Otopark')
            ->set('uygulama.onleyici_tedbirler', 'Yanıcılar ayrı depoda')
            ->set('uygulama.ekipman_kkd', '6 KKT söndürücü')
            ->set('uygulama.ozel_risk_alanlari', 'var')
            ->set('uygulama.enerji_kesme', 'var')
            ->set('uygulama.mudahale_yontemi', 'Alarm → ekip → tahliye → yoklama')
            ->call('iletisimEkle')
            ->set('uygulama.iletisim.1.ad', 'İtfaiye')
            ->set('uygulama.iletisim.1.telefon', '110')
            ->set('uygulama.onay_durumu', 'imzalandi')
            ->set('uygulama.kroki_asildi', true)
            ->set('uygulama.calisan_bilgilendirildi', true)
            ->set('ekipMetni.sondurme', 'Ali')->set('ekipMetni.kurtarma', 'Veli')->set('ekipMetni.koruma', 'Can')->set('ekipMetni.ilk_yardim', 'Ece')
            ->call('kaydet');

        $plan = AcilDurumPlani::where('firma_id', $firma->id)->sole();
        $this->assertSame('110', $plan->uygulama['iletisim'][1]['telefon']);

        $h = AcilDurumHazirlik::kontrol($plan);
        $eksik = collect($h['kontroller'])->where('tamam', false)->pluck('baslik')->all();
        // Kroki ve tatbikat henüz yok
        $this->assertSame(['Tahliye krokisi', 'Tatbikat (yılda en az bir)'], $eksik);
        $this->assertSame('aksiyon', $h['durum']);

        // Kroki çizilir + Tatbikat modülünde tamamlanmış kayıt → hazır
        AcilDurumKrokisi::firmaIcin($firma)->update(['semboller' => [['tip' => 'cikis', 'x' => 1, 'y' => 1]]]);
        TatbikatTutanagi::create(['firma_id' => $firma->id, 'tatbikat_tarihi' => now()->subMonths(2), 'durum' => 'tamamlandi']);
        $h = AcilDurumHazirlik::kontrol($plan->fresh());
        $this->assertSame(100, $h['yuzde']);
        $this->assertSame('hazir', $h['durum']);
        $this->assertStringContainsString('Tatbikat modülü', collect($h['kontroller'])->firstWhere('baslik', 'Tatbikat (yılda en az bir)')['aciklama']);

        // Gözden geçirme tarihi geçmişse "gözden geçirme"
        $plan->forceFill(['gecerlilik_tarihi' => now()->subDay()])->save();
        $this->assertSame('gozden_gecirme', AcilDurumHazirlik::kontrol($plan->fresh())['durum']);
    }

    public function test_plan_portfoyu_ve_excel(): void
    {
        $f1 = Firma::factory()->for($this->uzman)->create(['unvan' => 'Alfa']);
        Firma::factory()->for($this->uzman)->create(['unvan' => 'Beta']);
        Firma::factory()->for(User::factory())->create(['unvan' => 'Yabancı']);
        AcilDurumPlani::firmaIcin($f1)->update(['rapor_tarihi' => now()]);

        $p = AcilDurumHazirlik::portfoy($this->uzman->id);
        $this->assertCount(2, $p);
        $this->assertNotNull($p->firstWhere(fn ($r) => $r['firma']->id === $f1->id)['kontrol']);

        Livewire::test(PlanSayfasi::class)
            ->assertSee('İşyerlerinizin acil durum hazırlığı')
            ->assertDontSee('Yabancı')
            ->call('portfoyExcel')->assertFileDownloaded('acil-durum-hazirligi.xlsx');
    }
}
