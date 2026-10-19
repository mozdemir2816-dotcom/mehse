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

        $this->get(KrokiSayfasi::getUrl(['firma' => $firma->id, 'editor' => 'klasik']))->assertOk()
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

    public function test_makine_sembolleri_kaydedilir_ve_pdfde_taban_izi_olarak_cizilir(): void
    {
        $kroki = AcilDurumKrokisi::firmaIcin(Firma::factory()->for($this->uzman)->create());
        $temiz = AcilDurumKrokisi::temizle(['semboller' => [
            ['tip' => 'cnc', 'x' => 100, 'y' => 100, 'etiket' => 'CNC-1'],
            ['tip' => 'vinc', 'x' => 300, 'y' => 100],
        ]]);
        $this->assertSame(['cnc', 'vinc'], collect($temiz['semboller'])->pluck('tip')->all());

        $kroki->forceFill($temiz)->save();
        $html = view('pdf.acil-durum-krokisi', ['kroki' => $kroki->fresh(), 'firma' => $kroki->firma])->render();
        $this->assertStringContainsString('VİNÇ', $html);
        $this->assertStringContainsString('CNC-1', $html);
        $this->assertSame('Makine & Ekipman', config('isg.kroki.kategoriler.makine'));
    }

    public function test_kroki_yonetmelik_unsur_kontrolu(): void
    {
        $kroki = AcilDurumKrokisi::firmaIcin(Firma::factory()->for($this->uzman)->create());
        $kroki->semboller = [['tip' => 'sondurucu', 'x' => 1, 'y' => 1], ['tip' => 'cikis', 'x' => 2, 'y' => 2]];
        $kroki->ogeler = ['odalar' => [], 'yollar' => [[[0, 0], [10, 0]]], 'metinler' => []];

        $k = $kroki->yonetmelikKontrolu();
        $this->assertTrue($k['sondurme']['var']);
        $this->assertTrue($k['kacis']['var']);          // çizilmiş kaçış yolu kaçış yönü sayılır
        $this->assertFalse($k['uyari']['zorunlu']);      // uyarı sistemi "bulunması halinde"

        $this->assertSame([
            'İlk yardım malzemesi (Md.12/1-f-2)', 'Toplanma yeri (Md.12/1-f-3)', 'Elektrik / gaz kesme noktası (Md.12/1-f-7)',
        ], $kroki->eksikZorunluUnsurlar());

        $kroki->save();
        $this->get(KrokiSayfasi::getUrl(['firma' => $kroki->firma_id]))->assertOk()
            ->assertSee('3 zorunlu unsur eksik');
    }

    // ---------- 3B (izometrik) editör ----------

    /** @return array{S: array, G: array, I: array} */
    private function veri3b(): array
    {
        return [
            'S' => [
                ['t' => 'z', 'x' => 1, 'y' => 1, 'w' => 8, 'h' => 6, 'c' => '#dfe9f6', 'l' => '<b>Atölye</b>', 'p' => ''],
                ['t' => 'b', 'x' => 2, 'y' => 2, 'w' => 3, 'h' => 2, 'hz' => 1.8, 'c' => '#7b8fa6', 'l' => 'CNC "X"'],
                ['t' => 's', 'k' => 'E001', 'x' => 1, 'y' => 1, 'l' => ''],
                ['t' => 's', 'k' => 'E007', 'x' => 9, 'y' => 9, 'l' => ''],
                ['t' => 's', 'k' => 'E003', 'x' => 3, 'y' => 1, 'l' => ''],
                ['t' => 's', 'k' => 'F001', 'x' => 4, 'y' => 1, 'l' => ''],
                ['t' => 'r', 'open' => false, 'p' => [[1, 1], [5, 1], [5, 9]]],
                ['t' => 'a', 'x' => 2, 'y' => 8, 'd' => 1],
                ['t' => 'x', 'x' => 1, 'y' => 1],                                // bilinmeyen tür atılır
                'metin',                                                          // dizi olmayan öğe atılır
                ['t' => 'b', 'x' => 999999, 'y' => -999999, 'w' => 1, 'h' => 1],  // sayı sınırlanır
            ],
            'G' => ['w' => 9999, 'h' => 1, 'cell' => 'abc'],
            'I' => ['firma' => 'Örnek <i>AŞ</i>', 'tarih' => '2026-10-20', 'notes' => [['t' => 'Not', 'b' => "Satır 1\nSatır 2"]], 'at' => str_repeat('a', 5000)],
        ];
    }

    public function test_3b_kaydet_veriyi_temizler_ve_png_saklar(): void
    {
        Storage::fake('public');
        $firma = Firma::factory()->for($this->uzman)->create();

        Livewire::test(KrokiSayfasi::class)
            ->set('firmaId', $firma->id)
            ->call('kaydet3b', $this->veri3b(), $this->png())
            ->assertNotified('3B kroki kaydedildi');

        $k = AcilDurumKrokisi::where('firma_id', $firma->id)->sole();
        $b = $k->kroki3b;

        $this->assertCount(9, $b['S']);                                   // x türü + düz metin atıldı
        $this->assertSame('Atölye', $b['S'][0]['l']);                      // HTML temizlendi
        $this->assertSame('CNC X', $b['S'][1]['l']);                       // tırnak temizlendi
        $this->assertEquals(100000, $b['S'][8]['x']);                      // sayı sınırlandı
        $this->assertSame([[1, 1], [5, 1], [5, 9]], $b['S'][6]['p']);      // rota noktaları korunur
        $this->assertSame(['w' => 120, 'h' => 4, 'cell' => 0.5], $b['G']); // ızgara sınırlandı
        $this->assertSame('Örnek AŞ', $b['I']['firma']);
        $this->assertSame("Satır 1\nSatır 2", $b['I']['notes'][0]['b']);
        $this->assertSame(600, mb_strlen($b['I']['at']));                  // uzun metin kısaltıldı
        $this->assertTrue($k->kroki3bVar());
        $this->assertNotNull($k->gorsel_yolu);
        Storage::disk('public')->assertExists($k->gorsel_yolu);
        // klasik editör alanlarına dokunulmaz
        $this->assertSame([], $k->duvarlar);
        $this->assertSame([], $k->semboller);

        // Yeniden kayıtta eski PNG silinir; PNG olmayan veri dosya olarak yazılmaz
        $eski = $k->gorsel_yolu;
        $this->travel(2)->seconds();
        Livewire::test(KrokiSayfasi::class)->set('firmaId', $firma->id)->call('kaydet3b', $this->veri3b(), $this->png());
        Storage::disk('public')->assertMissing($eski);
        $yeni = $k->fresh()->gorsel_yolu;
        Livewire::test(KrokiSayfasi::class)->set('firmaId', $firma->id)->call('kaydet3b', $this->veri3b(), 'data:image/png;base64,'.base64_encode('<?php echo 1;'));
        $this->assertSame($yeni, $k->fresh()->gorsel_yolu);
    }

    public function test_3b_asiri_buyuk_veri_reddedilir_ve_baska_firmaya_yazilamaz(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();
        $baska = Firma::factory()->for(User::factory())->create();

        $dev = ['S' => array_fill(0, 3000, ['t' => 'b', 'x' => 1, 'y' => 1, 'l' => str_repeat('m', 600), 'n' => str_repeat('m', 600)]), 'G' => [], 'I' => []];
        Livewire::test(KrokiSayfasi::class)->set('firmaId', $firma->id)->call('kaydet3b', $dev)->assertNotified('Kroki verisi geçersiz veya çok büyük');
        $this->assertNull(AcilDurumKrokisi::where('firma_id', $firma->id)->sole()->kroki3b);

        Livewire::test(KrokiSayfasi::class)->set('firmaId', $baska->id)->call('kaydet3b', $this->veri3b());
        $this->assertSame(0, AcilDurumKrokisi::where('firma_id', $baska->id)->count());
    }

    public function test_sayfa_varsayilan_3b_editoru_iframe_olarak_yukler_klasik_cizimli_firmada_klasik_acilir(): void
    {
        $yeni = Firma::factory()->for($this->uzman)->create(['unvan' => 'Yeni Kroki AŞ']);
        $this->get(KrokiSayfasi::getUrl(['firma' => $yeni->id]))->assertOk()
            ->assertSee('3B Editör')->assertSee('Klasik Editör (2B)')
            ->assertSee('srcdoc=', false)->assertSee('Tahliye rotası')
            ->assertDontSee('krokiEditoru', false)->assertDontSee('İSGCEO');

        // Klasik editörde çizimi olan firma klasik açılır; 3B'ye geçilebilir
        $klasik = Firma::factory()->for($this->uzman)->create();
        AcilDurumKrokisi::firmaIcin($klasik)->update(['duvarlar' => [['x1' => 1, 'y1' => 1, 'x2' => 9, 'y2' => 1]]]);
        $this->get(KrokiSayfasi::getUrl(['firma' => $klasik->id]))->assertOk()->assertSee('krokiEditoru', false);

        $c = Livewire::test(KrokiSayfasi::class)->set('firmaId', $klasik->id);
        $c->assertSet('editor', 'klasik')->call('editorSec', '3b')->assertSet('editor', '3b')->assertSee('srcdoc=', false);
        $c->set('firmaId', $yeni->id)->assertSet('editor', '3b');

        // 3B verisi iframe'e kayıtlı çizim + firma bilgisiyle gider
        $c->call('kaydet3b', $this->veri3b());
        $veri = $c->instance()->kroki3bVerisi();
        $this->assertSame('Yeni Kroki AŞ', $veri['bilgi']['firma']);
        $this->assertCount(9, $veri['veri']['S']);
    }

    public function test_3b_yonetmelik_kontrolu_ve_hazirlik_plan_kontrolu(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create(['adres' => 'Merkez', 'isveren_ad' => 'Ali Veli']);
        $kroki = AcilDurumKrokisi::firmaIcin($firma);
        $plan = AcilDurumPlani::firmaIcin($firma);

        // Eksik 3B çizim: yalnız çıkış + tüp
        $kroki->update(['kroki3b' => ['S' => [['t' => 's', 'k' => 'E001'], ['t' => 's', 'k' => 'F001']], 'G' => [], 'I' => []]]);
        $kroki = $kroki->fresh();
        $this->assertSame(['İlk yardım malzemesi (Md.12/1-f-2)', 'Kaçış yolu / yönü (Md.12/1-f-3) — rota veya en az 3 yön oku', 'Toplanma yeri (Md.12/1-f-3)'], $kroki->eksikZorunluUnsurlar());
        $k = collect(AcilDurumHazirlik::kontrol($plan->fresh())['kontroller'])->keyBy('baslik');
        $this->assertFalse($k['Tahliye krokisi']['tamam']);
        $this->assertStringContainsString('Krokide eksik', $k['Tahliye krokisi']['aciklama']);

        // Tam 3B çizim: zorunlu unsurlar tamam, plan "Tahliye krokisi" tamam
        $kroki->update(['kroki3b' => $this->veri3b()]);
        $this->assertSame([], $kroki->fresh()->eksikZorunluUnsurlar());
        $k = collect(AcilDurumHazirlik::kontrol($plan->fresh())['kontroller'])->keyBy('baslik');
        $this->assertTrue($k['Tahliye krokisi']['tamam']);

        // Ok ile kaçış yönü (rota yok, 3 ok)
        $oklu = $this->veri3b();
        $oklu['S'] = array_values(array_filter($oklu['S'], fn ($o) => is_array($o) && ($o['t'] ?? null) !== 'r'));
        $oklu['S'][] = ['t' => 'a', 'x' => 1, 'y' => 1, 'd' => 0];
        $oklu['S'][] = ['t' => 'a', 'x' => 2, 'y' => 1, 'd' => 0];
        $kroki->update(['kroki3b' => $oklu]);
        $this->assertTrue($kroki->fresh()->yonetmelikKontrolu3b()['kacis']['var']);

        // Sayfa 3B kontrolünü gösterir
        $this->get(KrokiSayfasi::getUrl(['firma' => $firma->id, 'editor' => '3b']))->assertOk()->assertSee('kayıtlı 3B krokiye göre');
    }

    public function test_plan_tahliye_sayfasi_gorsel_yoksa_kayitli_kroki_goruntusunu_kullanir(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();
        $plan = AcilDurumPlani::firmaIcin($firma);
        $this->assertNull($plan->tahliyeGorselYolu());

        $goreli = 'acil-durum-kroki/test-'.$firma->id.'-'.uniqid().'.png';
        $tam = storage_path('app/public/'.$goreli);
        @mkdir(dirname($tam), 0777, true);
        file_put_contents($tam, base64_decode(substr($this->png(), 22)));

        try {
            AcilDurumKrokisi::firmaIcin($firma)->update(['gorsel_yolu' => $goreli]);
            $this->assertSame($tam, $plan->fresh()->tahliyeGorselYolu());
        } finally {
            @unlink($tam);
        }
    }

    public function test_hazirlik_kunye_ve_ozel_risk_yeri_yonetmelige_gore_kontrol_edilir(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create(['adres' => null, 'isveren_ad' => null]);
        $plan = AcilDurumPlani::firmaIcin($firma);
        $plan->forceFill(['uygulama' => ['ozel_risk_alanlari' => 'var', 'enerji_kesme' => 'uygulanamaz']])->save();

        $k = collect(AcilDurumHazirlik::kontrol($plan->fresh())['kontroller'])->keyBy('baslik');
        $this->assertFalse($k['Künye: unvan, adres ve işveren adı']['tamam']);
        $this->assertStringContainsString('işveren / vekili adı', $k['Künye: unvan, adres ve işveren adı']['aciklama']);
        $this->assertFalse($k['Özel risk alanları / enerji kesme noktaları']['tamam']);
        $this->assertStringContainsString('yeri yazılmamış', $k['Özel risk alanları / enerji kesme noktaları']['aciklama']);

        $firma->update(['adres' => 'Merkez', 'isveren_vekili' => 'Ayşe Demir']);
        $plan->forceFill(['uygulama' => [...$plan->uygulama, 'ozel_risk_notu' => 'LPG tüp alanı']])->save();
        $k = collect(AcilDurumHazirlik::kontrol($plan->fresh())['kontroller'])->keyBy('baslik');
        $this->assertTrue($k['Künye: unvan, adres ve işveren adı']['tamam']);
        $this->assertTrue($k['Özel risk alanları / enerji kesme noktaları']['tamam']);
    }

    // ---------- Acil durum planı: uygulama + hazırlık kontrolü ----------

    public function test_plan_uygulama_bilgisi_kaydedilir_ve_hazirlik_hesaplanir(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create(['unvan' => 'Hazırlık AŞ', 'adres' => 'Merkez Mh.', 'isveren_ad' => 'Hasan Kaya']);

        Livewire::test(PlanSayfasi::class)
            ->set('firmaId', $firma->id)
            ->assertSee('Risk, tedbir ve uygulama')
            ->set('toplanmaYeri', 'Otopark')
            ->set('uygulama.onleyici_tedbirler', 'Yanıcılar ayrı depoda')
            ->set('uygulama.ekipman_kkd', '6 KKT söndürücü')
            ->set('uygulama.ozel_risk_alanlari', 'var')
            ->set('uygulama.ozel_risk_notu', 'Boya deposu')
            ->set('uygulama.enerji_kesme', 'var')
            ->set('uygulama.enerji_kesme_notu', 'Ana pano girişte')
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

        // Yalnız çıkış işaretli kroki Md.12(1)(f) için yetmez
        AcilDurumKrokisi::firmaIcin($firma)->update(['semboller' => [['tip' => 'cikis', 'x' => 1, 'y' => 1]]]);
        $kroki = collect(AcilDurumHazirlik::kontrol($plan->fresh())['kontroller'])->firstWhere('baslik', 'Tahliye krokisi');
        $this->assertFalse($kroki['tamam']);
        $this->assertStringContainsString('Toplanma yeri', $kroki['aciklama']);

        // Zorunlu unsurlar işaretli kroki + Tatbikat modülünde tamamlanmış kayıt → hazır
        AcilDurumKrokisi::firmaIcin($firma)->update(['semboller' => collect(['cikis', 'cikis_sag', 'toplanma', 'sondurucu', 'ilkyardim', 'elektrik_pano'])
            ->map(fn ($t, $i) => ['tip' => $t, 'x' => $i * 10, 'y' => 1])->all()]);
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
