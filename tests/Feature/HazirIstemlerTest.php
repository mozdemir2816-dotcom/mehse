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
use Illuminate\Support\Facades\Storage;
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
        $this->assertStringContainsString('30 satırın TAMAMINI tek yanıtta', $istem);   // varsayılan: tek seferde
        $this->assertStringNotContainsString('PARÇA KURALI', $istem);
        $parcali = HazirIstemler::istem('risk_analizi', $firma, ['adet' => 30, 'parca' => 1]);
        $this->assertStringContainsString('EN FAZLA 25 satır', $parcali);
        $this->assertStringContainsString('"BİTTİ"', implode(' ', HazirIstemler::adimlar('risk_analizi', true)));
        $this->assertStringNotContainsString('BİTTİ', implode(' ', HazirIstemler::adimlar('risk_analizi')));
        $this->assertStringContainsString('Mevcut Önlem sütununu BOŞ', $istem);
        $this->assertStringContainsString('Termin sütununu BOŞ', $istem);          // termin sistemce düzeyden verilir
        $this->assertStringContainsString('EN FAZLA beşte biri', $istem);          // en kötü senaryo şişmesine karşı (13.11.2026)
        $this->assertStringNotContainsString('mevcut önlem yokmuş gibi', $istem);

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
            ['Hidrolik Pres Kullanma Talimatı', 'İş Makineleri', 'Presin güvenli kullanımı.', 'İş eldiveni, Koruyucu gözlük', 'Çalışmadan önce ışık perdesini dene • Çift el kumandasını bantlama • Kalıp değişiminde enerjiyi kilitle'],
            ['Şerit Testere Talimatı', 'İş Makineleri', 'Eski biçim', '', 'Siperi indir | Eldiven takma'],
        ]), $user->id);

        $this->assertSame(2, $sonuc['basarili'], implode(' ', $sonuc['hatalar']));
        $this->assertStringNotContainsString(' | " (boşluk', $istem);   // Gemini tablosunda dik çizgi sütun böler
        $this->assertStringContainsString('" • "', $istem);
        $this->assertSame(['Siperi indir', 'Eldiven takma'], TalimatSablonu::query()->where('baslik', 'Şerit Testere Talimatı')->firstOrFail()->maddeler);
        $t = TalimatSablonu::query()->where('baslik', 'Hidrolik Pres Kullanma Talimatı')->firstOrFail();
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

    public function test_saha_gozlem_tablosu_bulgulara_cevrilir(): void
    {
        $istem = HazirIstemler::istem('saha_gozlem', null, ['adet' => 10]);
        $this->assertStringContainsString('eklediğim saha fotoğraflarını', $istem);
        $b = $this->basliklar($istem, 'Fotoğraf No');

        $satir = ['1', 'Depo', 'sol alt, zemindeki kablo', 'Elektrik Güvenliği', 'Yürüme yolunda yalıtımı hasarlı uzatma kablosu', 'Kablo değiştirilmeli • Kablo kanalı kullanılmalı', 'Elektrik İç Tesisleri Yönetmeliği', '3', '6', '15'];
        $sonuc = \App\Support\SahaBulguExcelOkuyucu::oku($this->excel($b, [$satir, $b, ['', '', '', '', 'kısa']]));

        $this->assertCount(1, $sonuc['bulgular']);   // tekrar eden başlık atlanır
        $this->assertSame(1, $sonuc['atlanan']);       // 5 karakterden kısa tespit
        $x = $sonuc['bulgular'][0];
        $this->assertSame('Depo', $x['bina_bolge']);
        $this->assertSame('sol alt, zemindeki kablo', $x['fotograftaki_konum']);
        $this->assertSame(['Kablo değiştirilmeli', 'Kablo kanalı kullanılmalı'], $x['oneriler']);
        $this->assertSame('3', (string) $x['olasilik']);
        $this->assertSame('15', (string) $x['siddet']);
        $this->assertContains($x['risk_derecesi'], [1, 2, 3, 4]);   // 3×6×15 = 270 → skordan
    }

    public function test_dof_tablosu_dof_aktarimindan_gecer(): void
    {
        $istem = HazirIstemler::istem('dof', Firma::factory()->create(), ['ek' => '1) Raf sabitlenmemiş']);
        $this->assertStringContainsString('Kritik → '.now()->addDays(7)->format('d.m.Y'), $istem);
        $b = $this->basliklar($istem, 'Fotoğraf No');

        $sonuc = \App\Support\DofTabloOkuyucu::oku($this->excel($b, [
            ['2', 'Depo rafları duvara sabitlenmemiş', 'Raflar duvara ankrajla sabitlenmeli.', 'Kritik', 'Bakım Sorumlusu', now()->addDays(7)->format('d.m.Y'), 'İşyeri Bina ve Eklentileri Yönetmeliği'],
        ]));

        $this->assertCount(1, $sonuc['maddeler']);
        $m = $sonuc['maddeler'][0];
        $this->assertSame('kritik', $m['oncelik']);
        $this->assertSame(now()->addDays(7)->toDateString(), $m['termin']);
        $this->assertSame('Bakım Sorumlusu', $m['sorumlu']);
        $this->assertStringContainsString('ankraj', $m['oneri']);
        $this->assertSame(2, $m['foto_no']);
    }

    /** 14.11.2026: fotoğraflar dosya adındaki numaraya, numara yoksa sıraya göre eşlenir. */
    public function test_fotograflar_dosya_adi_numarasina_ya_da_siraya_gore_eslenir(): void
    {
        $oku = \App\Support\SahaBulguExcelOkuyucu::class;

        $this->assertSame([2 => 'a', 1 => 'b'], $oku::fotoEsle(['a', 'b'], ['a' => '2.jpg', 'b' => 'foto 1.png']));
        // Telefon adları numara sayılmaz → sıra
        $this->assertSame([1 => 'a', 2 => 'b'], $oku::fotoEsle(['a', 'b'], ['a' => 'IMG_20261114_1234.jpg', 'b' => 'IMG_20261114_1240.jpg']));
        // Aynı numara iki kez → sıra
        $this->assertSame([1 => 'a', 2 => 'b'], $oku::fotoEsle(['a', 'b'], ['a' => '1.jpg', 'b' => '1 (2).jpg']));
        $this->assertSame([], $oku::fotoEsle([]));
        $this->assertSame(3, $oku::fotoNo('Fotoğraf 3, 5'));
        $this->assertNull($oku::fotoNo(''));
    }

    /** 14.11.2026: Excel'de maddenin satırına yapıştırılan fotoğraf o maddeye bağlanır. */
    public function test_saha_excel_satirina_yapistirilan_fotograf_maddeye_baglanir(): void
    {
        Storage::fake('public');
        $k = new Spreadsheet;
        $k->getActiveSheet()->fromArray([
            ['Fotoğraf No', 'Tespit', 'Öneriler'],
            ['', 'Yürüme yolunda hasarlı uzatma kablosu', 'Kablo değiştirilmeli'],
            ['', 'Yangın tüpünün önü kapatılmış', 'Önü boşaltılmalı'],
        ], null, 'A1');
        $resim = tempnam(sys_get_temp_dir(), 'r').'.png';
        imagepng(imagecreatetruecolor(20, 20), $resim);
        $cizim = new \PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
        $cizim->setPath($resim);
        $cizim->setCoordinates('D3');
        $cizim->setWorksheet($k->getActiveSheet());
        $yol = tempnam(sys_get_temp_dir(), 'sg').'.xlsx';
        (new Xlsx($k))->save($yol);

        $b = \App\Support\SahaBulguExcelOkuyucu::oku($yol)['bulgular'];

        $this->assertNull($b[0]['foto_yolu']);
        $this->assertNotNull($b[1]['foto_yolu']);
        $this->assertStringStartsWith('saha-analiz-foto/', $b[1]['foto_yolu']);
        Storage::disk('public')->assertExists($b[1]['foto_yolu']);
    }

    public function test_ayni_ekipman_icin_benzer_klasor_bulunur_yukleme_durur_istem_mevcutlari_bildirir(): void
    {
        $k = \App\Models\TehlikeKategorisi::create(['ad' => 'Ekipman – Manitou / teleskopik yükleyici', 'anahtar' => 'ekipman_manitou', 'sira' => 1]);
        Tehlike::create(['tehlike_kategorisi_id' => $k->id, 'tehlike' => 'Bom uzatılınca boyuna devrilme']);
        \App\Models\TehlikeKategorisi::create(['ad' => 'Kaynak ve Kesme', 'anahtar' => 'kaynak', 'sira' => 2]);

        // "manito" → Manitou klasörü; ilgisiz ad eşleşmez.
        $this->assertSame($k->id, \App\Models\TehlikeKategorisi::benzerleri('manito')->first()['kategori']->id);
        $this->assertTrue(\App\Models\TehlikeKategorisi::benzerleri('Matbaa ofset baskı')->isEmpty());

        // Yükleme: benzer klasör varken yeni klasör açılmaz, satırlar yüklenmez.
        $b = ['Sıra', 'Kategori', 'Kod', 'Bölüm', 'Faaliyet', 'Tehlike', 'Risk', 'Mevcut Önlem', 'Mevzuat'];
        $dosya = $this->excel($b, [['1', 'manito', '', 'Saha', 'Kaldırma', 'Stabilizatörün yumuşak zemine basması', 'Devrilme', '', '']]);
        $sonuc = TehlikeExcelIceAktarici::iceAktar($dosya);
        $this->assertSame(0, $sonuc['basarili']);
        $this->assertArrayHasKey('manito', $sonuc['benzerKlasorler']);
        $this->assertStringContainsString('Manitou', $sonuc['benzerKlasorler']['manito'][0]);
        $this->assertSame(0, \App\Models\TehlikeKategorisi::where('anahtar', 'manito')->count());

        // Kullanıcı "yine de yeni klasör aç" derse açılır.
        $this->assertSame(1, TehlikeExcelIceAktarici::iceAktar($dosya, true)['basarili']);
        $this->assertSame(1, \App\Models\TehlikeKategorisi::where('anahtar', 'manito')->count());

        // Mevcut klasör adıyla istem: içindeki maddeler "tekrar yazma" diye eklenir.
        $istem = HazirIstemler::istem('tehlike_kutuphanesi', null, ['kategori' => 'Ekipman – Manitou / teleskopik yükleyici', 'adet' => 10]);
        $this->assertStringContainsString('ZATEN OLAN MADDELER', $istem);
        $this->assertStringContainsString('- Bom uzatılınca boyuna devrilme', $istem);
        $this->assertStringNotContainsString('ZATEN OLAN', HazirIstemler::istem('tehlike_kutuphanesi', null, ['kategori' => 'Matbaa']));

        // Sayfa: benzer klasör listelenir, "Bunu kullan" adı mevcut klasörün adıyla değiştirir.
        $this->actingAs(User::factory()->create(['rol' => 'sahip']));
        Livewire::test(Sayfa::class)
            ->set('data.tur', 'tehlike_kutuphanesi')
            ->set('data.kategori', 'Manitu')
            ->assertSee('Kütüphanede benzer klasör var')
            ->call('klasorSec', $k->id)
            ->assertSet('data.kategori', 'Ekipman – Manitou / teleskopik yükleyici')
            ->assertSee('Bu klasör kütüphanede var');
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
