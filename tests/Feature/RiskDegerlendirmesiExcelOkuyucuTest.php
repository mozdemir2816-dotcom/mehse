<?php

namespace Tests\Feature;

use App\Support\RiskDegerlendirmesiExcelOkuyucu;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class RiskDegerlendirmesiExcelOkuyucuTest extends TestCase
{
    /** @param  array<int, array<int, mixed>>  $satirlar */
    private function xlsxOlustur(array $satirlar): string
    {
        $kitap = new Spreadsheet;
        $sayfa = $kitap->getActiveSheet();

        foreach ($satirlar as $i => $satir) {
            $sayfa->fromArray($satir, null, 'A'.($i + 1));
        }

        $yol = tempnam(sys_get_temp_dir(), 'xlsx').'.xlsx';
        (new Xlsx($kitap))->save($yol);

        return $yol;
    }

    public function test_ustte_lejant_olan_dosyada_gercek_baslik_satiri_bulunur(): void
    {
        // İlk satırlar bir "lejant" (gerçek başlık değil) -- kullanıcı dosyalarında yaygın.
        $yol = $this->xlsxOlustur([
            ['ZENIUM PROJESİ', '', ''],
            ['OLASILIK DEĞERİ', '', ''],
            ['0.2', 'Beklenmez', ''],
            [], // boş satır
            ['Alt Faaliyetler', 'Tehlikeli Durum Ya Da Davranış', 'Risk', 'Mevcut Durum', 'Olasılık', 'Şiddet', 'Önlem'],
            ['Kazı Çalışmaları', 'İksasız derin kazı', 'Göçük altında kalma', 'Şev açısı hesabı yok', 3, 5, 'Şev açısı hesaplanır.'],
            ['Kalıp İşleri', 'Kalıp montaj kontrolü yapılmaması', 'Kalıp çökmesi', 'Kontrol yapılmıyor', 2, 4, 'Kontrol formu doldurulur.'],
        ]);

        $sonuc = RiskDegerlendirmesiExcelOkuyucu::oku($yol);

        $this->assertSame(2, $sonuc['basarili']);
        $this->assertEmpty($sonuc['hatalar']);

        $ilk = $sonuc['adaylar'][0];
        $this->assertSame('Kazı Çalışmaları', $ilk['faaliyet']);
        $this->assertStringContainsString('İksasız derin kazı', $ilk['tehlike']);
        $this->assertSame(3.0, $ilk['olasilik']);
        $this->assertSame(5.0, $ilk['siddet']);
        $this->assertStringContainsString('Şev açısı hesaplanır', $ilk['oneri']);

        unlink($yol);
    }

    public function test_metin_sutunu_puan_sutunu_sanilmaz(): void
    {
        // "Şiddet" adında ama içeriği metin olan bir sütun -- sayisal olmadigi icin
        // puan alanina atanmamali.
        $yol = $this->xlsxOlustur([
            ['Bölüm', 'Tehlike', 'Şiddet Açıklaması'],
            ['Şantiye', 'Yüksekten düşme', 'Çok ciddi'],
            ['Şantiye', 'Elektrik çarpması', 'Ciddi'],
        ]);

        $sonuc = RiskDegerlendirmesiExcelOkuyucu::oku($yol);

        $this->assertSame(2, $sonuc['basarili']);
        $this->assertNull($sonuc['adaylar'][0]['siddet']);

        unlink($yol);
    }

    public function test_tehlike_sutunu_yoksa_hata_doner(): void
    {
        $yol = $this->xlsxOlustur([
            ['Bölüm', 'Açıklama'],
            ['Şantiye', 'bir şeyler'],
        ]);

        $sonuc = RiskDegerlendirmesiExcelOkuyucu::oku($yol);

        $this->assertSame(0, $sonuc['basarili']);
        $this->assertNotEmpty($sonuc['hatalar']);

        unlink($yol);
    }

    public function test_bos_satirlar_atlanir(): void
    {
        $yol = $this->xlsxOlustur([
            ['Bölüm', 'Tehlike'],
            ['Şantiye', 'Tehlike 1'],
            ['', ''],
            ['', ''],
            ['Şantiye', 'Tehlike 2'],
        ]);

        $sonuc = RiskDegerlendirmesiExcelOkuyucu::oku($yol);

        $this->assertSame(2, $sonuc['basarili']);

        unlink($yol);
    }

    public function test_iki_satir_baslik_ve_cok_sayfa_puanlar_dogru_alinir(): void
    {
        // "Beton dökümü" dosyasındaki gibi: künye sayfası + iş kalemi sayfaları,
        // ana başlık + O1/F1/S1 alt başlığı, "Risk Skoru / Risk Seviyesi" ara sütunlar.
        $kitap = new Spreadsheet;
        $kitap->getActiveSheet()->setTitle('Sayfa1')->fromArray(['Firmanın Adı: Örnek A.Ş.'], null, 'A1');

        foreach (['Beton', 'kazı'] as $ad) {
            $s = $kitap->createSheet()->setTitle($ad);
            $s->fromArray([
                'Sıra No', 'Faaliyet Alanı', 'Alt Faaliyet / Bölüm', 'Tehlike Kaynağı', 'Olası Risk & Sonuç',
                'MEVCUT DURUM', null, null, null, null, 'Alınması Gereken Tedbirler', 'Sorumlu Birim / Termin',
            ], null, 'A3');
            $s->fromArray([
                null, null, null, null, null,
                'Olasılık (O1)', 'Frekans (F1)', 'Şiddet (S1)', 'Risk Skoru (R1)', 'Risk Seviyesi 1', null, null,
            ], null, 'A4');
            $s->fromArray([1, $ad.' işleri', 'Süreç A', 'Tehlike '.$ad, 'Yaralanma', 3, 6, 7, 126, 'ÖNEMLİ RİSK', 'Önlem metni.', 'Kısım Şefi'], null, 'A5');
            $s->fromArray([2, $ad.' işleri', 'Süreç B', 'Tehlike 2 '.$ad, 'Ölüm', 3, 10, 15, 450, 'ÇOK YÜKSEK RİSK', 'Diğer önlem.', 'Kısım Şefi'], null, 'A6');
        }

        $yol = tempnam(sys_get_temp_dir(), 'xlsx').'.xlsx';
        (new Xlsx($kitap))->save($yol);

        $sonuc = RiskDegerlendirmesiExcelOkuyucu::oku($yol);
        unlink($yol);

        // 2 sayfa × 2 satır; künye sayfası okunmadı
        $this->assertSame(4, $sonuc['basarili']);

        $ilk = collect($sonuc['adaylar'])->firstWhere('tehlike', 'Tehlike Beton');
        $this->assertSame(3.0, $ilk['olasilik']);   // O1 alt başlıktan doğru sütuna bağlandı
        $this->assertSame(6.0, $ilk['frekans']);
        $this->assertSame(7.0, $ilk['siddet']);
        $this->assertSame('Süreç A', $ilk['faaliyet']);
        // "Risk Skoru" (126) ve "Risk Seviyesi" (ÖNEMLİ RİSK) risk alanına sızmadı
        $this->assertStringNotContainsString('126', (string) $ilk['risk']);
        $this->assertStringNotContainsString('RİSK', mb_strtoupper((string) $ilk['risk']));
    }
}
