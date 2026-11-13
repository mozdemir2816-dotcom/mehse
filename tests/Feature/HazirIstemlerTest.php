<?php

namespace Tests\Feature;

use App\Filament\Pages\HazirIstemler as Sayfa;
use App\Models\Firma;
use App\Models\TalimatSablonu;
use App\Models\Tehlike;
use App\Models\User;
use App\Support\HazirIstemler;
use App\Support\RiskDegerlendirmesiExcelOkuyucu;
use App\Support\SoruBankasiIceAktarici;
use App\Support\TalimatSablonuExcelIceAktarici;
use App\Support\TehlikeExcelIceAktarici;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

/**
 * 13.11.2026: Hazır İstemler — istemin istediği çıktı biçimi, sistemin mevcut içe
 * aktarıcılarınca gerçekten okunuyor mu? (Gemini'nin cevabı istemdeki başlıklarla taklit edilir.)
 */
class HazirIstemlerTest extends TestCase
{
    use RefreshDatabase;

    /** İstemdeki "a | b | c" başlık satırını bulur. */
    private function basliklar(string $istem, string $ilkSutun): array
    {
        foreach (explode("\n", $istem) as $satir) {
            if (str_starts_with(trim($satir), $ilkSutun.' | ')) {
                return array_map('trim', explode(' | ', trim($satir)));
            }
        }
        $this->fail("Başlık satırı bulunamadı: {$ilkSutun}");
    }

    private function excel(array $basliklar, array $satirlar): string
    {
        $k = new Spreadsheet;
        $k->getActiveSheet()->fromArray([$basliklar, ...$satirlar], null, 'A1');
        $yol = tempnam(sys_get_temp_dir(), 'hi').'.xlsx';
        (new Xlsx($k))->save($yol);

        return $yol;
    }

    public function test_risk_analizi_istemi_firma_bilgisini_icerir_ve_tablosu_okunur(): void
    {
        $firma = Firma::factory()->create(['unvan' => 'Deneme Tekstil A.Ş.', 'nace_kodu' => '14.13', 'nace_aciklama' => 'Dış giyim imalatı', 'calisan_sayisi' => 85]);
        $istem = HazirIstemler::istem('risk_analizi', $firma, ['yontem' => 'fine_kinney', 'adet' => 30, 'ek' => 'Bölümler: kesimhane, dikimhane']);

        $this->assertStringContainsString('Deneme Tekstil A.Ş.', $istem);
        $this->assertStringContainsString('14.13 — Dış giyim imalatı', $istem);
        $this->assertStringContainsString('Çalışan sayısı: 85', $istem);
        $this->assertStringContainsString('kesimhane, dikimhane', $istem);
        $this->assertStringContainsString('TAM OLARAK 30 maddelik', $istem);
        $this->assertStringContainsString('EN FAZLA 25 satır', $istem);
        $this->assertStringContainsString('Mevcut Önlem sütununu BOŞ', $istem);

        $b = $this->basliklar($istem, 'Sıra');
        $satir = ['1', 'Kesimhane', 'Kumaş kesimi', 'Korumasız şerit bıçak', 'El kesilmesi, uzuv kaybı', '', '3', '6', '15', 'Bıçak koruyucusu takılmalı, çelik örgü eldiven verilmeli.', 'Bölüm Şefi', '1 Ay', 'İş Ekipmanları Yönetmeliği', '1', '6', '15'];
        // İkinci parça başlığıyla birlikte altına yapıştırılmış (Gemini her parçada başlığı tekrarlar).
        $sonuc = RiskDegerlendirmesiExcelOkuyucu::oku($this->excel($b, [$satir, $b]));

        $this->assertSame(1, $sonuc['basarili'], implode(' ', $sonuc['hatalar']));
        $a = $sonuc['adaylar'][0];
        $this->assertSame('Korumasız şerit bıçak', $a['tehlike']);
        $this->assertSame('Kesimhane', $a['bolum']);
        $this->assertSame('Kumaş kesimi', $a['faaliyet']);
        $this->assertSame('Bölüm Şefi', $a['sorumlu']);
        $this->assertStringContainsString('Bıçak koruyucusu', $a['oneri']);
        $this->assertStringContainsString('İş Ekipmanları', $a['mevzuat']);
        $this->assertEquals(3, $a['olasilik']);
        $this->assertEquals(6, $a['frekans']);
        $this->assertEquals(15, $a['siddet']);
        $this->assertEquals(1, $a['son_olasilik']);
        $this->assertEquals(15, $a['son_siddet']);
    }

    public function test_5x5_istemi_frekans_sutunu_istemez(): void
    {
        $istem = HazirIstemler::istem('risk_analizi', null, ['yontem' => 'matris_5x5', 'ek' => 'Küçük bir büro']);
        $b = $this->basliklar($istem, 'Sıra');
        $this->assertNotContains('Frekans', $b);
        $this->assertContains('Son Şiddet', $b);
        $this->assertStringContainsString('1–5 arası', $istem);
    }

    public function test_tehlike_kutuphanesi_tablosu_ice_aktarilir_mevcut_onlem_bos_kalir(): void
    {
        $istem = HazirIstemler::istem('tehlike_kutuphanesi', null, ['kategori' => 'Matbaa – Ofset baskı', 'adet' => 10]);
        $this->assertStringContainsString('aynen şunu yaz: Matbaa – Ofset baskı', $istem);

        $b = $this->basliklar($istem, 'Sıra');
        $sonuc = TehlikeExcelIceAktarici::iceAktar($this->excel($b, [
            ['1', 'Matbaa – Ofset baskı', '', 'Baskı salonu', 'Silindir temizliği', 'Dönen silindirler arasına el kaptırma', 'Ezilme, parmak kopması', '', 'İş Ekipmanları Yönetmeliği'],
            $b,
        ]));

        $this->assertSame(1, $sonuc['basarili'], implode(' ', $sonuc['hatalar']));
        $this->assertSame([], $sonuc['hatalar']);
        $t = Tehlike::query()->where('tehlike', 'Dönen silindirler arasına el kaptırma')->firstOrFail();
        $this->assertSame('Ezilme, parmak kopması', $t->risk);
        $this->assertNull($t->mevcut_onlem);
    }

    public function test_talimat_tablosu_dik_cizgiyle_ayrilan_maddelerle_ice_aktarilir(): void
    {
        $istem = HazirIstemler::istem('talimat', null, ['adet' => 1, 'ek' => 'Hidrolik pres']);
        $b = $this->basliklar($istem, 'Başlık');
        $user = User::factory()->create();

        $sonuc = TalimatSablonuExcelIceAktarici::iceAktar($this->excel($b, [
            ['Hidrolik Pres Kullanma Talimatı', 'İş Makineleri', 'Presin güvenli kullanımı.', 'İş eldiveni, Koruyucu gözlük', 'Çalışmadan önce ışık perdesini dene | Çift el kumandasını bantlama | Kalıp değişiminde enerjiyi kilitle'],
        ]), $user->id);

        $this->assertSame(1, $sonuc['basarili'], implode(' ', $sonuc['hatalar']));
        $t = TalimatSablonu::query()->latest('id')->firstOrFail();
        $this->assertCount(3, $t->maddeler);
        $this->assertSame('Çift el kumandasını bantlama', $t->maddeler[1]);
        $this->assertSame(['İş eldiveni', 'Koruyucu gözlük'], $t->kkdler);
        $this->assertSame('is_makineleri', $t->kategori);
    }

    public function test_soru_bankasi_istemindeki_ornek_kayit_ice_aktaricidan_gecer(): void
    {
        $firma = Firma::factory()->create(['nace_kodu' => '10.71']);
        $istem = HazirIstemler::istem('soru_bankasi', $firma, ['konu' => 'yangin', 'adet' => 5]);
        $this->assertStringContainsString('aynen "yangin"', $istem);

        preg_match('/^\{"konu".*\}\r?$/m', $istem, $m);
        $this->assertNotEmpty($m, 'örnek JSON satırı yok');
        $ornek = json_decode(trim($m[0]), true);
        $this->assertSame('yangin', $ornek['konu']);
        $this->assertIsArray($ornek);
        $this->assertSame(['10.71'], $ornek['kapsam']['nace']);

        $ornek = array_replace($ornek, ['soru' => 'Yangın söndürücü nereye tutulur?', 'secenekler' => ['Alevin tabanına', 'Alevin üstüne', 'Dumana', 'Tavana'], 'gerekce' => 'Yanan madde tabandadır.']);
        $sonuc = SoruBankasiIceAktarici::cozumle(json_encode([$ornek]));
        $this->assertSame([], $sonuc['hatalar']);
        $this->assertCount(1, $sonuc['kayitlar']);
    }

    public function test_sayfa_yalniz_sahibe_acik_ve_istem_secime_gore_degisir(): void
    {
        $this->actingAs(User::factory()->create(['rol' => 'uzman']));
        $this->assertFalse(Sayfa::canAccess());

        $sahip = User::factory()->create(['rol' => 'sahip']);
        $this->actingAs($sahip);
        $this->assertTrue(Sayfa::canAccess());
        $firma = Firma::factory()->create(['user_id' => $sahip->id, 'unvan' => 'Örnek Gıda Ltd.']);

        Livewire::test(Sayfa::class)
            ->assertSee('Firma seçmediniz')
            ->set('data.firma_id', $firma->id)
            ->assertSee('Örnek Gıda Ltd.')
            ->assertDontSee('Firma seçmediniz')
            ->set('data.tur', 'talimat')
            ->assertSee('Talimat Oluştur');
    }
}
