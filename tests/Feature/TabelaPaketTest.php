<?php

namespace Tests\Feature;

use App\Filament\Pages\TabelaTasarimi as TabelaSayfasi;
use App\Models\Firma;
use App\Models\RiskDegerlendirmesi;
use App\Models\TabelaTasarimi;
use App\Models\User;
use App\Support\GeminiTabelaCevirmen;
use App\Support\IsverenWhatsApp;
use App\Support\TabelaPdfUretici;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;
use ZipArchive;

/** 02.11.2026: önerilen levhaların toplu ZIP paketi, AI çeviri ve levhanın WhatsApp ile paylaşımı. */
class TabelaPaketTest extends TestCase
{
    use RefreshDatabase;

    private User $uzman;

    private Firma $firma;

    protected function setUp(): void
    {
        parent::setUp();
        $this->uzman = User::factory()->create();
        $this->actingAs($this->uzman);
        $this->firma = Firma::factory()->for($this->uzman)->create(['unvan' => 'Ahmet Yapı', 'telefon' => null]);
    }

    public function test_onerilen_levhalar_a3_zip_olarak_paketlenir(): void
    {
        $rd = RiskDegerlendirmesi::create(['firma_id' => $this->firma->id, 'yontem' => 'fine_kinney', 'rapor_tarihi' => now(), 'proje_adi' => 'Şantiye']);
        $rd->maddeler()->create(['tehlike' => 'Yüksekte çalışmada düşme', 'olasilik' => 6, 'frekans' => 6, 'siddet' => 40]);
        $rd->maddeler()->create(['tehlike' => 'Panoda açık elektrik kablosu', 'olasilik' => 1, 'frekans' => 1, 'siddet' => 1]);

        $yanit = TabelaPdfUretici::oneriPaketi($this->firma, 'a3');
        ob_start();
        $yanit->sendContent();
        $icerik = ob_get_clean();
        $yol = tempnam(sys_get_temp_dir(), 'zt');
        file_put_contents($yol, $icerik);

        $zip = new ZipArchive;
        $this->assertTrue($zip->open($yol) === true);
        $adlar = collect(range(0, $zip->numFiles - 1))->map(fn ($i) => $zip->getNameIndex($i));
        $this->assertSame(2, $adlar->filter(fn ($a) => str_ends_with($a, '-a3.pdf'))->count());
        $this->assertSame(2, $adlar->filter(fn ($a) => str_ends_with($a, '.svg'))->count());
        $this->assertContains('OKUBENI.txt', $adlar->all());
        $pdf = $zip->getFromName($adlar->first(fn ($a) => str_ends_with($a, '.pdf')));
        $this->assertStringStartsWith('%PDF', $pdf);
        $zip->close();
        @unlink($yol);

        Livewire::test(TabelaSayfasi::class, ['firmaId' => $this->firma->id])->set('firmaId', $this->firma->id)
            ->call('oneriPaketi', 'a4')->assertFileDownloaded('tabela-paketi-ahmet-yapi-a4.zip');
    }

    public function test_oneri_yoksa_uyari_verir(): void
    {
        $this->assertNull(TabelaPdfUretici::oneriPaketi($this->firma));
    }

    public function test_ai_ceviri_baslik_ve_aciklamayi_ingilizceye_cevirir_sinyali_sabit_esler(): void
    {
        config(['services.gemini.key' => 'test-anahtar', 'services.gemini.yedek_modeller' => []]);
        Http::fake(['generativelanguage.googleapis.com/*' => Http::response(['candidates' => [['content' => ['parts' => [['text' => json_encode([
            'baslik' => 'High Voltage - 380 V', 'aciklama' => 'AUTHORIZED PERSONNEL ONLY. Lock out and tag out before servicing.',
        ])]]]]]])]);

        $s = GeminiTabelaCevirmen::cevir('YÜKSEK GERİLİM - 380 VOLT', 'Yetkisiz müdahale yasaktır. Bakım öncesi enerjiyi kesip kilitleyin (LOTO).', 'TEHLİKE');

        $this->assertNull($s['hata']);
        $this->assertSame('HIGH VOLTAGE - 380 V', $s['baslik']);
        $this->assertStringContainsString('AUTHORIZED PERSONNEL ONLY', $s['aciklama']);
        $this->assertSame('DANGER', $s['sinyal']);
        $this->assertSame('CAUTION', GeminiTabelaCevirmen::sinyal('Dikkat'));
        $this->assertNotNull(GeminiTabelaCevirmen::cevir('', '')['hata']);

        // Sayfa yöntemi aynı sonucu iframe'e döndürür.
        $sonuc = Livewire::test(TabelaSayfasi::class)->set('firmaId', $this->firma->id)->instance()->cevir('UYARI', 'Baret takınız', 'UYARI');
        $this->assertSame('WARNING', $sonuc['sinyal']);
    }

    public function test_editorde_ai_cevir_dugmesi_ve_mesaj_dinleyicisi_var(): void
    {
        $html = view('tabela.editor', ['veri' => ['baslik' => '', ...TabelaTasarimi::varsayilanTasarim()]])->render();

        $this->assertStringContainsString('🤖 AI ile Çevir', $html);
        $this->assertStringContainsString('tip: "cevir"', $html);
        $this->assertStringContainsString('m.tip !== "ceviri"', $html);
    }

    public function test_whatsapp_paylasimi_telefon_yoksa_alici_secilir_imzali_pdf_acilir(): void
    {
        $t = TabelaTasarimi::create(['firma_id' => $this->firma->id, 'baslik' => 'Yüksek Gerilim', 'tasarim' => TabelaTasarimi::varsayilanTasarim()]);

        $mesaj = IsverenWhatsApp::tabelaMesaji($t);
        $this->assertStringStartsWith('Sayın Yetkili,', $mesaj);
        $this->assertStringContainsString('Ahmet Yapı işyeri için hazırlanan "Yüksek Gerilim" İSG Güvenlik Levhası baskı dosyası (127 × 180 mm, PDF) hazırdır:', $mesaj);
        $this->assertStringContainsString('Bilgilerinize sunarız.', $mesaj);

        $this->assertNull(IsverenWhatsApp::url($this->firma, $mesaj));
        $this->assertStringStartsWith('https://api.whatsapp.com/send?text=', IsverenWhatsApp::url($this->firma, $mesaj, kisiSecilir: true));

        Livewire::test(TabelaSayfasi::class)->set('firmaId', $this->firma->id)->set('tasarimId', $t->id)
            ->call('whatsapp')
            ->assertNotified("Levha baskı dosyası WhatsApp'ta hazırlandı");

        preg_match('#https?://\S+/paylasim/tabela/'.$t->id.'\S+#', $mesaj, $e);
        auth()->logout();
        $this->get($e[0])->assertOk()->assertDownload('tabela-yuksek-gerilim.pdf');
    }
}
