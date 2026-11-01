<?php

namespace Tests\Feature;

use Tests\TestCase;

class SeoTest extends TestCase
{
    public function test_sitemap_gecerli_xml_ana_yasal_ve_sektor_sayfalari(): void
    {
        $yanit = $this->get('/sitemap.xml')->assertOk()
            ->assertHeader('Content-Type', 'application/xml; charset=utf-8');

        $xml = simplexml_load_string($yanit->getContent());
        $this->assertNotFalse($xml);
        $this->assertStringStartsWith('<?xml version="1.0" encoding="UTF-8"?>', $yanit->getContent());

        $adresler = [];
        foreach ($xml->url as $u) {
            $adresler[(string) $u->loc] = (string) $u->priority;
            $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2}$/', (string) $u->lastmod);
        }
        $this->assertSame([
            'https://mehse.com/' => '1.0',
            'https://mehse.com/kvkk' => '0.5',
            'https://mehse.com/cerez-politikasi' => '0.5',
            'https://mehse.com/sektor/otomotiv-yan-sanayi' => '0.8',
            'https://mehse.com/sektor/metal-talasli-imalat' => '0.8',
            'https://mehse.com/sektor/lojistik-ve-depolama' => '0.8',
        ], $adresler);
    }

    public function test_robots_txt_paneli_kapatir_sitemap_gosterir(): void
    {
        $robots = file_get_contents(public_path('robots.txt'));
        $this->assertStringContainsString('Disallow: /admin', $robots);
        $this->assertStringContainsString('Sitemap: https://mehse.com/sitemap.xml', $robots);

        $this->get('/')->assertSee('<meta name="robots" content="index, follow">', false);
    }
}
