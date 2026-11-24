<?php

namespace Tests\Feature;

use App\Filament\Pages\AcilDurumKrokisi as AcilDurumKrokisiPage;
use App\Filament\Pages\MuayeneFormu as MuayeneFormuPage;
use App\Filament\Pages\SahaDenetimi as SahaDenetimiPage;
use App\Models\AcilDurumKrokisi;
use App\Models\EgitimKatilim;
use App\Models\Firma;
use App\Models\MuayeneFormu;
use App\Models\RiskDegerlendirmesi;
use App\Models\SahaDenetimi;
use App\Models\Sertifika;
use App\Models\User;
use App\Support\AcilDurumKrokisiUretici;
use App\Support\EgitimIcerikOlusturucu;
use App\Support\EgitimKatilimUretici;
use App\Support\MuayeneFormuUretici;
use App\Support\RiskDegerlendirmesiOfisUretici;
use App\Support\SahaDenetimiUretici;
use App\Support\SertifikaUretici;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * İSG Pratik evrak karşılaştırması eklentileri testleri:
 *  - EK-2 KVKK Hekim Kanaat PDF + Çoklu EK-2 Toplu PDF + TR/EN
 *  - Eğitim Katılım TR/EN PDF
 *  - Sertifika 7 çerçeve + Arkalı Önlü (2 sayfa) PDF
 *  - Acil Durum Krokisi A4/A3/A0 Vektör SVG + otomatik ISO 7010 sembol sayacı
 *  - Saha Denetimi Excel (.xlsx) çıktısı
 *  - Risk Değerlendirmesi Word her sayfa alt paraflama şeridi (Yön. m.11/2)
 */
class IsgPratikEvrakEklentileriTest extends TestCase
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
            'unvan' => 'Örnek Sanayi A.Ş.',
            'tehlike_sinifi' => 'cok_tehlikeli',
        ]);
    }

    public function test_ek2_hekim_kanaat_kvkk_pdf_coklu_ek2_ve_tr_en_calisir(): void
    {
        $m1 = MuayeneFormu::create([
            'firma_id' => $this->firma->id,
            'belge_no' => 'EK2-2026-001',
            'muayene_turu' => 'ise_giris',
            'muayene_tarihi' => '2026-03-10',
            'calisan_ad_soyad' => 'Ali Yılmaz',
            'calisan_tc' => '12345678901',
            'calisan_gorevi' => 'Kaynak Operatörü',
            'sonuc' => 'uygun',
            'ek2' => ['anamnez' => ['kronik' => 'Gizli Tansiyon Geçmişi'], 'kanaat' => ['elverisli' => true, 'gece' => false, 'yuksekte' => true]],
        ]);
        $m2 = MuayeneFormu::create([
            'firma_id' => $this->firma->id,
            'belge_no' => 'EK2-2026-002',
            'muayene_turu' => 'periyodik',
            'muayene_tarihi' => '2026-03-11',
            'calisan_ad_soyad' => 'Veli Kaya',
            'calisan_tc' => '10987654321',
            'calisan_gorevi' => 'Torna Ustası',
            'sonuc' => 'kosullu',
        ]);

        // Hekim Kanaat HTML'inde tıbbi anamnez sızmamalı, KVKK şerhi ve EN başlık görünmeli
        $kanaatHtml = view('pdf.hekim-kanaat', ['form' => $m1, 'firma' => $this->firma, 'imzali' => false, 'dil' => 'tr_en'])->render();
        $this->assertStringContainsString('MEDICAL FITNESS OPINION', $kanaatHtml);
        $this->assertStringContainsString('6331 s. İSG Kanunu m.15/5', $kanaatHtml);
        $this->assertStringNotContainsString('Gizli Tansiyon Geçmişi', $kanaatHtml);

        $this->assertSame(200, MuayeneFormuUretici::kanaatPdf($m1, false, 'tr_en')->getStatusCode());
        $this->assertSame(200, MuayeneFormuUretici::pdf($m1, false, null, 'tr_en')->getStatusCode());
        $this->assertSame(200, MuayeneFormuUretici::topluPdf(collect([$m1, $m2]), false, 'tr')->getStatusCode());

        // Sayfadan Çoklu EK-2 indirme
        Livewire::test(MuayeneFormuPage::class)
            ->set('firmaId', $this->firma->id)
            ->callAction('cokluEk2Pdf', ['kayit_idler' => [$m1->id, $m2->id], 'imzali' => '0', 'dil' => 'tr'])
            ->assertFileDownloaded('coklu-ek2-muayene-ornek-sanayi-as-2-kisi.pdf');
    }

    public function test_egitim_katilim_cift_dilli_tr_en_pdf_uretilir(): void
    {
        $icerik = EgitimIcerikOlusturucu::olustur('genel', 'insaat', 'cok_tehlikeli', 'ilk');
        $kayit = EgitimKatilim::create([
            'firma_id' => $this->firma->id,
            'belge_no' => 'EGT-2026-001',
            'baslik_anahtari' => 'genel',
            'sektor_anahtari' => 'insaat',
            'egitim_turu' => 'ilk',
            'egitim_sekli' => 'yuz_yuze',
            'belge_tarihi' => '2026-04-10',
            'konu_secimleri' => $icerik,
            'katilimcilar' => [['ad_soyad' => 'John Doe', 'tc' => '99999999999', 'gorev' => 'Engineer']],
        ]);

        $html = view('pdf.egitim-katilim', [
            'kayit' => $kayit,
            'firma' => $this->firma,
            'icerik' => $icerik,
            'imzali' => false,
            'dil' => 'tr_en',
        ])->render();

        $this->assertStringContainsString('TRAINING ATTENDANCE FORM', $html);
        $this->assertStringContainsString('Full Name', $html);
        $this->assertSame(200, EgitimKatilimUretici::pdf($kayit, false, 'tr_en')->getStatusCode());
    }

    public function test_sertifika_yedi_cerceve_ve_arkali_onlu_pdf_uretilir(): void
    {
        $cerceveler = config('isg.sertifika.cerceveler');
        $this->assertCount(7, $cerceveler);
        $this->assertArrayHasKey('lacivert_kurumsal', $cerceveler);
        $this->assertArrayHasKey('zumrut_klasik', $cerceveler);
        $this->assertArrayHasKey('bordo_resmi', $cerceveler);

        $icerik = EgitimIcerikOlusturucu::olustur('genel', 'insaat', 'cok_tehlikeli', 'ilk');
        $s = Sertifika::create([
            'firma_id' => $this->firma->id,
            'belge_no' => 'SRT-2026-001',
            'tip' => 'isg',
            'tur' => 'ilk_defa',
            'sekil' => 'yuz_yuze',
            'cerceve' => 'lacivert_kurumsal',
            'egitim_tarihleri' => ['2026-04-10'],
            'sure_metni' => '16 saat',
            'konu_icerigi' => $icerik,
            'katilimcilar' => [['ad_soyad' => 'Ayşe Demir', 'tc' => '12345678901', 'gorev' => 'Mühendis']],
        ]);

        $html = view('pdf.sertifika', [
            'sertifika' => $s,
            'firma' => $this->firma,
            'imzali' => false,
            'tcMaskele' => false,
            'arkaliOnlu' => true,
        ])->render();

        $this->assertStringContainsString('cerceve-lacivert_kurumsal', $html);
        $this->assertStringContainsString('EĞİTİM MÜFREDATI VE KONU / SÜRE LİSTESİ (ARKA YÜZ)', $html);
        $this->assertSame(200, SertifikaUretici::pdf($s, false, false, true)->getStatusCode());
    }

    public function test_acil_durum_krokisi_vektor_svg_ve_otomatik_lejant_sayaci_uretilir(): void
    {
        $kroki = AcilDurumKrokisi::create([
            'firma_id' => $this->firma->id,
            'duvarlar' => [['x1' => 10, 'y1' => 10, 'x2' => 500, 'y2' => 10]],
            'semboller' => [
                ['tip' => 'yangin_tupu', 'x' => 100, 'y' => 100],
                ['tip' => 'yangin_tupu', 'x' => 250, 'y' => 100],
                ['tip' => 'acil_cikis', 'x' => 450, 'y' => 100],
            ],
            'hazirlanma_tarihi' => '2026-05-01',
        ]);

        $svg = AcilDurumKrokisiUretici::svgIcerigi($kroki, 'A0');
        $this->assertStringContainsString('width="1189mm" height="841mm"', $svg);
        $this->assertStringContainsString('ACİL ÇAĞRI: 112', $svg);
        $this->assertStringContainsString('OTOMATİK ISO 7010 SEMBOL LEJANTI', $svg);
        $this->assertStringContainsString('2 adet', $svg);

        Livewire::test(AcilDurumKrokisiPage::class)
            ->set('firmaId', $this->firma->id)
            ->callAction('svg', ['boyut' => 'A0'])
            ->assertFileDownloaded('acil-durum-krokisi-ornek-sanayi-as-a0.svg');
    }

    public function test_saha_denetimi_excel_raporu_ve_risk_word_paraflama_seridi_uretilir(): void
    {
        $denetim = SahaDenetimi::create([
            'firma_id' => $this->firma->id,
            'revizyon' => 1,
            'denetim_tarihi' => '2026-05-05',
            'durum' => 'tamamlandi',
            'uygunluk_yuzdesi' => 50.0,
            'kritik_uygunsuzluk_var' => true,
            'cevaplar' => [
                ['kategori' => 'Elektrik', 'madde' => 'Pano önünde yalıtkan paspas var mı?', 'sonuc' => 'uygun_degil', 'kritik' => true, 'aciklama' => 'Paspas eksik', 'fotolar' => []],
                ['kategori' => 'Yangın', 'madde' => 'Yangın tüpleri periyodik kontrollü mü?', 'sonuc' => 'uygun', 'kritik' => false, 'aciklama' => '', 'fotolar' => []],
            ],
        ]);

        $kitap = SahaDenetimiUretici::excelKitabi($denetim);
        $sheet = $kitap->getActiveSheet();
        $this->assertStringContainsString('SAHA DENETİM VE DEĞERLENDİRME RAPORU', (string) $sheet->getCell('A1')->getValue());
        $this->assertSame('Uygun Değil', $sheet->getCell('D5')->getValue());
        $this->assertSame('EVET', $sheet->getCell('E5')->getValue());
        $this->assertSame('Uygun', $sheet->getCell('D6')->getValue());
        $this->assertSame(200, SahaDenetimiUretici::excel($denetim)->getStatusCode());

        // Risk Değerlendirmesi Word alt bilgisinde ekip paraflama şeridi
        $rd = RiskDegerlendirmesi::create([
            'firma_id' => $this->firma->id,
            'belge_no' => 'RD-2026-001',
            'yontem' => 'fine_kinney',
            'rapor_tarihi' => '2026-05-05',
        ]);
        $rd->maddeler()->create(['sira' => 1, 'tehlike' => 'Yüksekte çalışma', 'olasilik' => 3, 'frekans' => 6, 'siddet' => 7]);

        $word = RiskDegerlendirmesiOfisUretici::wordBelgesi($rd, false);
        $footerText = '';
        foreach ($word->getSections() as $sec) {
            foreach ($sec->getFooters() as $f) {
                foreach ($f->getElements() as $el) {
                    if (method_exists($el, 'getText')) {
                        $t = $el->getText();
                        $footerText .= is_string($t) ? $t : '';
                    }
                }
            }
        }
        $this->assertStringContainsString('Paraf (Yön. m.11/2)', $footerText);
        $this->assertStringContainsString('İşveren/Vekili', $footerText);
    }
}
