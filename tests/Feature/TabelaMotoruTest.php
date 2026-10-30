<?php

namespace Tests\Feature;

use App\Filament\Pages\TabelaTasarimi as TabelaSayfasi;
use App\Models\Firma;
use App\Models\RiskDegerlendirmesi;
use App\Models\SahaBulgusu;
use App\Models\TabelaTasarimi;
use App\Models\User;
use App\Support\TabelaOnerisi;
use App\Support\TabelaPdfUretici;
use App\Support\TabelaPiktogramlari;
use App\Support\TabelaSvgUretici;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/** Tabela motoru: gerçek vektör SVG, PDF şablonu ve risk / saha bulgularından levha önerisi. */
class TabelaMotoruTest extends TestCase
{
    use RefreshDatabase;

    private User $uzman;

    private Firma $firma;

    protected function setUp(): void
    {
        parent::setUp();
        $this->uzman = User::factory()->create(['rol' => 'sahip']);
        $this->actingAs($this->uzman);
        $this->firma = Firma::factory()->for($this->uzman)->create(['unvan' => 'Ahmet Yapı']);
    }

    public function test_piktogram_json_editordeki_kutuphaneyle_ayni_anahtarlari_tasir(): void
    {
        $editor = file_get_contents(resource_path('views/tabela/editor.blade.php'));
        $bas = strpos($editor, 'const REGULATION_PICTOGRAMS = {');
        preg_match_all('/^\s{6}"([a-z0-9_]+)": \{/m', substr($editor, $bas, 60000), $m);

        $this->assertEqualsCanonicalizing($m[1], array_keys(TabelaPiktogramlari::tum()), 'resources/tabela/piktogramlar.json editörle eşit değil — yeniden çıkarın');
        $this->assertNotEmpty(TabelaPiktogramlari::ic('warn_electric')['ic']);
        $this->assertNull(TabelaPiktogramlari::ic('olmayan'));
    }

    public function test_svg_tum_yerlesimlerde_gecerli_xml_ve_foreignobject_icermez(): void
    {
        foreach (['top', 'bottom', 'left', 'right', 'none'] as $konum) {
            foreach (['none', 'warning_triangle', 'mandatory_circle', 'prohibition_circle'] as $cerceve) {
                $svg = TabelaSvgUretici::svg(['picPosition' => $konum, 'picWrapper' => $cerceve, 'picCount' => 3, 'showEn' => true, 'enTitle' => 'High voltage', 'enDesc' => 'No entry.']);

                $d = new \DOMDocument;
                $this->assertTrue(@$d->loadXML($svg), "$konum/$cerceve geçerli XML değil");
                $this->assertStringNotContainsString('foreignObject', $svg);
                $this->assertStringContainsString('YÜKSEK GERİLİM', $svg);
                $this->assertStringContainsString('TEHLİKE', $svg);
            }
        }
    }

    public function test_svg_ingilizce_metni_turkce_buyuk_harfe_cevirmez_ve_girdiyi_temizler(): void
    {
        $svg = TabelaSvgUretici::svg(['signalEn' => 'high', 'showEn' => true, 'enTitle' => 'high light', 'mainTitle' => 'girilmez <script>alert(1)</script>', 'slotCustomImages' => ['javascript:alert(1)', null, null, null]]);

        $this->assertStringContainsString('/ HIGH', $svg);
        $this->assertStringContainsString('HIGH LIGHT', $svg);
        $this->assertStringContainsString('GİRİLMEZ', $svg);
        $this->assertStringNotContainsString('<script', $svg);
        $this->assertStringNotContainsString('javascript:', $svg);
    }

    public function test_svg_ikonu_transform_grubuyla_yerlesir_ic_ice_svg_kullanmaz(): void
    {
        $svg = TabelaSvgUretici::svg(['slots' => ['warn_electric', null, null, null], 'picCount' => 1]);

        $this->assertStringContainsString('<g transform="translate(', $svg);
        // kök dışında iç içe <svg> olmamalı (php-svg-lib x/y/viewBox'ı yok sayar)
        $this->assertSame(1, substr_count($svg, '<svg '));
    }

    public function test_pdf_ve_svg_indirilir(): void
    {
        $t = TabelaTasarimi::create(['firma_id' => $this->firma->id, 'baslik' => 'Yüksek Gerilim', 'tasarim' => TabelaTasarimi::varsayilanTasarim()]);

        ob_start();
        TabelaPdfUretici::pdf($t)->sendContent();
        $this->assertStringStartsWith('%PDF', ob_get_clean());

        $this->assertSame('tabela-yuksek-gerilim.svg', TabelaPdfUretici::svgDosyaAdi($t));
    }

    public function test_anahtar_sozcukler_dogru_levhaya_eslenir(): void
    {
        $this->assertSame('elektrik', TabelaOnerisi::kuralBul('Elektrik panosunda açık kablo'));
        $this->assertSame('yuksekte', TabelaOnerisi::kuralBul('Yüksekte çalışmada düşme riski'));
        $this->assertSame('forklift', TabelaOnerisi::kuralBul('FORKLİFT ile yaya çarpışması'));
        $this->assertSame('kimyasal', TabelaOnerisi::kuralBul('Solvent ve boya buharı'));
        $this->assertSame('kkd', TabelaOnerisi::kuralBul('Baret kullanılmıyor'));
        $this->assertNull(TabelaOnerisi::kuralBul('Ofis düzeni'));

        // her kuralın piktogramları kütüphanede var
        foreach (TabelaOnerisi::KURALLAR as $anahtar => $kural) {
            foreach ($kural['slots'] as $slot) {
                $this->assertTrue(TabelaPiktogramlari::var($slot), "$anahtar: $slot piktogramı yok");
            }
        }
    }

    public function test_oneri_risk_maddeleri_ve_saha_bulgularindan_gelir(): void
    {
        $rd = RiskDegerlendirmesi::create(['firma_id' => $this->firma->id, 'yontem' => 'fine_kinney', 'rapor_tarihi' => now(), 'proje_adi' => 'Şantiye']);
        $rd->maddeler()->create(['tehlike' => 'Yüksekte çalışmada düşme', 'olasilik' => 6, 'frekans' => 6, 'siddet' => 40]);
        $rd->maddeler()->create(['tehlike' => 'Panoda açık elektrik kablosu', 'olasilik' => 1, 'frekans' => 1, 'siddet' => 1]);
        $rd->maddeler()->create(['tehlike' => 'Ofis düzeni', 'olasilik' => 1, 'frekans' => 1, 'siddet' => 1]);
        SahaBulgusu::create(['firma_id' => $this->firma->id, 'uygunsuzluk' => 'Forklift trafiğinde yaya ayrımı yok', 'oncelik' => 'kritik']);
        SahaBulgusu::create(['firma_id' => $this->firma->id, 'uygunsuzluk' => 'Kapanmış elektrik bulgusu', 'durum' => 'kapandi']);

        $oneriler = collect(TabelaOnerisi::firmaIcin($this->firma))->keyBy('anahtar');

        $this->assertSame(['yuksekte', 'forklift', 'elektrik'], $oneriler->keys()->all());   // yüksek ciddiyet önce
        $this->assertSame('yuksek', $oneriler['yuksekte']['seviye']);
        $this->assertSame('TEHLİKE', $oneriler['yuksekte']['tasarim']['signalTr']);
        $this->assertSame('#D02C2F', $oneriler['yuksekte']['tasarim']['headerBg']);
        $this->assertSame('dusuk', $oneriler['elektrik']['seviye']);
        $this->assertSame('DİKKAT', $oneriler['elektrik']['tasarim']['signalTr']);
        $this->assertSame('warn_falling', $oneriler['yuksekte']['tasarim']['slots'][0]);
        $this->assertStringContainsString('Saha bulgusu', $oneriler['forklift']['kaynaklar'][0]);

        // üretilen tasarım doğrulayıcıdan geçer ve SVG'ye dönüşür
        $this->assertSame($oneriler['yuksekte']['tasarim']['mainTitle'], TabelaTasarimi::temizle($oneriler['yuksekte']['tasarim'])['mainTitle']);
        $this->assertStringContainsString('TEHLİKESİ', TabelaSvgUretici::svg($oneriler['yuksekte']['tasarim']));
    }

    public function test_hastane_harf_duzeyi_seviyeye_cevrilir_ve_kaynaklar_en_ciddiden_baslar(): void
    {
        $rd = RiskDegerlendirmesi::create(['firma_id' => $this->firma->id, 'yontem' => 'fine_kinney', 'rapor_tarihi' => now(), 'proje_adi' => 'Hastane', 'cikti_sablonu' => 'hastane']);
        // Puanlar hastane bantlarına göre: ≥200 B, ≥70 C, ≥20 D, altı E.
        $rd->maddeler()->create(['tehlike' => 'Elektrik panosu önünde engel', 'olasilik' => 1, 'frekans' => 1, 'siddet' => 1]);
        $rd->maddeler()->create(['tehlike' => 'Halojen free olmayan elektrik kabloları', 'olasilik' => 6, 'frekans' => 6, 'siddet' => 7]);
        $rd->maddeler()->create(['tehlike' => 'Oksijen tüpü basınçlı kap devrilmesi', 'olasilik' => 5, 'frekans' => 3, 'siddet' => 5]);
        $rd->maddeler()->create(['tehlike' => 'Lazer ışını maruziyeti', 'olasilik' => 2, 'frekans' => 2, 'siddet' => 6]);

        $oneriler = collect(TabelaOnerisi::firmaIcin($this->firma))->keyBy('anahtar');

        $this->assertSame('yuksek', $oneriler['elektrik']['seviye']);                      // B → yüksek (E'den etkilenmez)
        $this->assertStringContainsString('(B)', $oneriler['elektrik']['kaynaklar'][0]);   // en ciddi kaynak başta
        $this->assertSame('orta', $oneriler['patlayici']['seviye']);                       // C → orta
        $this->assertSame('dusuk', $oneriler['lazer']['seviye']);                          // D → düşük
        $this->assertLessThan(array_search('lazer', $oneriler->keys()->all()), array_search('elektrik', $oneriler->keys()->all()));
    }

    public function test_anahtar_sozcuk_sozcuk_ortasinda_eslesmez_sigara_yangina_gider(): void
    {
        $this->assertNull(TabelaOnerisi::kuralBul('Mobbing, stres ve depresyon'));          // "pres" ≠ pres makinesi
        $this->assertNull(TabelaOnerisi::kuralBul('Otopark kapasitesinin yetersizliği'));   // "asit" ≠ asit
        $this->assertNull(TabelaOnerisi::kuralBul('Kas-iskelet sistemi rahatsızlıkları'));  // "iskele" ≠ iskele
        $this->assertNull(TabelaOnerisi::kuralBul('Etanol-izopropanol kullanımı'));         // "pano" ≠ pano
        $this->assertSame('yangin', TabelaOnerisi::kuralBul('Kapalı alanlarda sigara içilmesi'));
        $this->assertSame('kapali_alan', TabelaOnerisi::kuralBul('Su tankı içinde çalışma'));
        $this->assertSame('yuksekte', TabelaOnerisi::kuralBul('İskele kurulumu'));
        $this->assertSame('kimyasal', TabelaOnerisi::kuralBul('Sitotoksik ilaçların dökülmesi'));
    }

    public function test_oneri_firmanin_tum_degerlendirmelerinden_gelir_yalniz_sonuncusundan_degil(): void
    {
        $eski = RiskDegerlendirmesi::create(['firma_id' => $this->firma->id, 'yontem' => 'fine_kinney', 'rapor_tarihi' => '2026-01-10', 'proje_adi' => 'Ameliyathane']);
        $eski->maddeler()->create(['tehlike' => 'İyonize radyasyona maruziyet', 'olasilik' => 1, 'frekans' => 1, 'siddet' => 1]);
        $yeni = RiskDegerlendirmesi::create(['firma_id' => $this->firma->id, 'yontem' => 'fine_kinney', 'rapor_tarihi' => '2026-06-10', 'proje_adi' => 'Asansör']);
        $yeni->maddeler()->create(['tehlike' => 'Asansör kabininde sıkışma — makine bakımı', 'olasilik' => 1, 'frekans' => 1, 'siddet' => 1]);
        $yeni->maddeler()->create(['tehlike' => 'Kapatılmış elektrik maddesi', 'durum' => 'kapali', 'olasilik' => 1, 'frekans' => 1, 'siddet' => 1]);

        $anahtarlar = collect(TabelaOnerisi::firmaIcin($this->firma))->pluck('anahtar')->all();

        $this->assertContains('radyasyon', $anahtarlar);   // eski değerlendirmeden
        $this->assertContains('makine', $anahtarlar);      // yeni değerlendirmeden
        $this->assertNotContains('elektrik', $anahtarlar); // kapalı madde sayılmaz
    }

    public function test_yeni_kurallar_radyasyon_manyetik_lazer_ve_soguk_ortami_taniir(): void
    {
        $this->assertSame('radyasyon', TabelaOnerisi::kuralBul('İyonize radyasyon kaynağı ve kimyasal kontrast madde'));
        $this->assertSame('radyasyon', TabelaOnerisi::kuralBul('Röntgen ve bilgisayarlı tomografi odası'));
        $this->assertSame('manyetik', TabelaOnerisi::kuralBul('MR cihazı güçlü manyetik alan'));
        $this->assertSame('lazer', TabelaOnerisi::kuralBul('Lazer cihazı ile tedavi'));
        $this->assertSame('soguk', TabelaOnerisi::kuralBul('Soğuk oda ve dondurucu depoda çalışma'));
        $this->assertContains('radyoaktif', TabelaOnerisi::KURALLAR['radyasyon']['kelimeler']);
        $this->assertSame('warn_radioactive', TabelaOnerisi::tasarim('radyasyon', 'dusuk')['slots'][0]);
    }

    public function test_olcum_siniri_asan_parametre_levha_onerir_uygun_olan_onermez(): void
    {
        \App\Models\OrtamOlcumu::create(['firma_id' => $this->firma->id, 'olcumler' => [
            ['parametre' => 'Gürültü Maruziyeti (LEX,8h)', 'birim' => 'dB(A)', 'sonuc' => 'asim', 'olculen_deger' => '91', 'sinir_deger' => '85'],
            ['parametre' => 'Solunabilir Toz', 'birim' => 'mg/m³', 'sonuc' => 'uygun'],
        ]]);

        $oneriler = collect(TabelaOnerisi::firmaIcin($this->firma))->keyBy('anahtar');

        $this->assertSame(['gurultu'], $oneriler->keys()->all());
        $this->assertSame('yuksek', $oneriler['gurultu']['seviye']);
        $this->assertStringContainsString('Ölçüm:', $oneriler['gurultu']['kaynaklar'][0]);
    }

    public function test_sayfa_onerileri_gosterir_oneriyi_editore_tasir_ve_indirir(): void
    {
        $rd = RiskDegerlendirmesi::create(['firma_id' => $this->firma->id, 'yontem' => 'fine_kinney', 'rapor_tarihi' => now(), 'proje_adi' => 'Şantiye']);
        $rd->maddeler()->create(['tehlike' => 'Kimyasal madde teması', 'olasilik' => 6, 'frekans' => 6, 'siddet' => 40]);
        $t = TabelaTasarimi::create(['firma_id' => $this->firma->id, 'baslik' => 'Kayıtlı Levha', 'tasarim' => TabelaTasarimi::varsayilanTasarim()]);

        Livewire::withQueryParams(['firma' => $this->firma->id])->test(TabelaSayfasi::class)
            ->assertSee('Önerilen Levhalar')
            ->assertSee('Kimyasal')
            ->call('svgIndir', $t->id)->assertFileDownloaded('tabela-kayitli-levha.svg')
            ->call('pdfIndir', $t->id)->assertFileDownloaded('tabela-kayitli-levha.pdf');

        $sayfa = Livewire::withQueryParams(['firma' => $this->firma->id, 'oneri' => 'kimyasal'])->test(TabelaSayfasi::class);
        $this->assertSame('kimyasal', $sayfa->get('oneri'));
        $this->assertSame('KİMYASAL MADDE TEHLİKESİ', $sayfa->instance()->editorVerisi()['mainTitle']);

        $gecersiz = Livewire::withQueryParams(['firma' => $this->firma->id, 'oneri' => 'olmayan'])->test(TabelaSayfasi::class);
        $this->assertNull($gecersiz->get('oneri'));
    }
}
