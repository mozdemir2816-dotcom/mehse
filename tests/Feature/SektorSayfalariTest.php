<?php

namespace Tests\Feature;

use App\Models\TeklifTalebi;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class SektorSayfalariTest extends TestCase
{
    use RefreshDatabase;

    public function test_uc_sektor_sayfasi_acilir(): void
    {
        foreach (['otomotiv-yan-sanayi' => 'Pres güvenliği', 'metal-talasli-imalat' => 'CNC tezgah güvenliği', 'lojistik-ve-depolama' => 'Raf statiği'] as $slug => $risk) {
            $sektor = config("sektorler.{$slug}");
            $this->get(route('sektor.show', $slug))->assertOk()
                ->assertSee('<title> '.e($sektor['seo_baslik']).' </title>', false)
                ->assertSee($sektor['baslik'].' İş Güvenliği Hizmeti')
                ->assertSee('Öne Çıkan Kritik Riskler ve Denetim Başlıkları')
                ->assertSee('Mehse İSG ile Çalışmanın Avantajları')
                ->assertSee($risk)
                ->assertSee('NOSAB')
                ->assertSee('tehlike-orta', false)
                ->assertSee(url('/').'?sektor='.$slug.'#teklif', false)
                ->assertSee('<meta name="robots" content="index, follow">', false);
        }
    }

    public function test_olmayan_sektor_404(): void
    {
        $this->get('/sektor/olmayan-sektor')->assertNotFound();
    }

    public function test_yasal_sayfalar_ortak_duzenle_calismaya_devam_eder(): void
    {
        $this->get('/kvkk')->assertOk()->assertSee('<title> KVKK', false);
        $this->get('/cerez-politikasi')->assertOk();
    }

    public function test_ana_sayfa_sektoru_forma_ekler_ve_sinifi_secer(): void
    {
        $this->get('/?sektor=otomotiv-yan-sanayi')->assertOk()
            ->assertSee('<input type="hidden" name="sektor" value="otomotiv-yan-sanayi">', false)
            ->assertSee('Sektör: Otomotiv Yan Sanayi')
            ->assertSee('<option selected>Tehlikeli</option>', false)
            ->assertSee(route('sektor.show', 'metal-talasli-imalat'), false);

        $this->get('/?sektor=uydurma')->assertOk()->assertDontSee('name="sektor"', false);
        $this->get('/?sektor=otomotiv-yan-sanayi.riskler')->assertOk()->assertDontSee('name="sektor"', false);
        $this->get('/')->assertOk()->assertDontSee('<option selected>', false);
    }

    public function test_sektorlu_teklif_nota_yazilir(): void
    {
        Mail::fake();

        $this->post(route('tanitim.teklif'), [
            'firma' => 'Örnek Oto', 'kisi' => 'Ali', 'telefon' => '0506 111 22 33', 'calisan' => 120,
            'sinif' => 'Tehlikeli', 'kvkk' => '1', 'sektor' => 'otomotiv-yan-sanayi',
        ])->assertSessionHas('teklif_gonderildi');

        $this->assertSame('Sektör sayfası: Otomotiv Yan Sanayi', TeklifTalebi::first()->not);

        $this->post(route('tanitim.teklif'), [
            'firma' => 'X', 'kisi' => 'Y', 'telefon' => '0506 111 22 33', 'calisan' => 5,
            'sinif' => 'Tehlikeli', 'kvkk' => '1', 'sektor' => 'uydurma',
        ])->assertSessionHasErrors('sektor');
    }

    public function test_sitemap_sektorleri_08_ile_icerir(): void
    {
        $xml = simplexml_load_string($this->get('/sitemap.xml')->assertOk()->getContent());
        $adresler = [];
        foreach ($xml->url as $u) {
            $adresler[(string) $u->loc] = (string) $u->priority;
        }
        foreach (array_keys(config('sektorler')) as $slug) {
            $this->assertSame('0.8', $adresler['https://mehse.com/sektor/'.$slug] ?? null);
        }
    }

    public function test_sss_json_ld_gecerli_ve_akordiyonla_ayni(): void
    {
        foreach (config('sektorler') as $slug => $sektor) {
            $this->assertCount(3, $sektor['sss']);
            $html = $this->get(route('sektor.show', $slug))->assertOk()->getContent();

            preg_match_all('#<script type="application/ld\+json">(.*?)</script>#s', $html, $bloklar);
            $this->assertCount(1, $bloklar[1], $slug);
            $this->assertLessThan(strpos($html, '</head>'), strpos($html, 'application/ld+json'), 'JSON-LD head içinde olmalı');

            $ld = json_decode($bloklar[1][0], true, flags: JSON_THROW_ON_ERROR);
            $this->assertSame('https://schema.org', $ld['@context']);
            $this->assertSame('FAQPage', $ld['@type']);
            $this->assertCount(3, $ld['mainEntity']);

            foreach ($sektor['sss'] as $i => [$soru, $cevap]) {
                $this->assertSame(['@type' => 'Question', 'name' => $soru, 'acceptedAnswer' => ['@type' => 'Answer', 'text' => $cevap]], $ld['mainEntity'][$i]);
                $this->assertStringContainsString('<summary>'.e($soru).'<span class="sss-isaret"', $html);
                $this->assertStringContainsString('<p>'.e($cevap).'</p>', $html);
            }
        }
    }
}
