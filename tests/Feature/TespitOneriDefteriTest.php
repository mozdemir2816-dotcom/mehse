<?php

namespace Tests\Feature;

use App\Filament\Pages\TespitOneriDefteri as DefterSayfasi;
use App\Models\Firma;
use App\Models\SahaBulgusu;
use App\Models\TespitOneriDefteri;
use App\Models\User;
use App\Support\GeminiOneriDanismani;
use App\Support\TespitOneriDefteriUretici;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Tests\TestCase;

class TespitOneriDefteriTest extends TestCase
{
    use RefreshDatabase;

    private User $uzman;

    protected function setUp(): void
    {
        parent::setUp();
        $this->uzman = User::factory()->create();
        $this->actingAs($this->uzman);
    }

    public function test_firma_secilince_defter_otomatik_olusur(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();

        Livewire::test(DefterSayfasi::class)->set('firmaId', $firma->id);

        $this->assertDatabaseHas('tespit_oneri_defterleri', ['firma_id' => $firma->id]);
    }

    public function test_katalogdan_madde_eklenir(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();
        $ilkTespit = config('isg.tespit_oneri.katalog.Yönetim Sistemi ve Dokümantasyon.0.tespit');

        Livewire::test(DefterSayfasi::class)
            ->set('firmaId', $firma->id)
            ->call('katalogdanEkle', $ilkTespit);

        $defter = TespitOneriDefteri::where('firma_id', $firma->id)->firstOrFail();
        $this->assertCount(1, $defter->maddeler);
        $this->assertSame($ilkTespit, $defter->maddeler[0]['tespit']);
    }

    public function test_konu_ve_arama_filtresi_katalogu_daraltir(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();

        $component = Livewire::test(DefterSayfasi::class)
            ->set('firmaId', $firma->id)
            ->set('konuFiltre', 'Kişisel Koruyucu Donanım');

        $this->assertArrayHasKey('Kişisel Koruyucu Donanım', $component->get('katalog'));
        $this->assertArrayNotHasKey('Acil Durum', $component->get('katalog'));
    }

    public function test_serbest_tespit_oneri_eklenir(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();

        Livewire::test(DefterSayfasi::class)
            ->set('firmaId', $firma->id)
            ->set('serbestTespit', 'Acil çıkış kapısı kilitliydi.')
            ->set('serbestOneri', 'Kapı her zaman açılabilir olmalı.')
            ->call('serbestEkle');

        $defter = TespitOneriDefteri::where('firma_id', $firma->id)->firstOrFail();
        $this->assertCount(1, $defter->maddeler);
        $this->assertSame('Acil çıkış kapısı kilitliydi.', $defter->maddeler[0]['tespit']);
    }

    public function test_ai_onerisi_gecerli_yanit_doner(): void
    {
        config(['services.gemini.key' => 'test-key']);

        Http::fake([
            '*generativelanguage.googleapis.com*' => Http::response([
                'candidates' => [['content' => ['parts' => [['text' => 'Kapı her zaman açılabilir olmalıdır.']]]]],
            ], 200),
        ]);

        $this->assertSame('Kapı her zaman açılabilir olmalıdır.', GeminiOneriDanismani::oner('Kapı kilitliydi.'));
    }

    public function test_ai_api_anahtari_yokken_pasif(): void
    {
        config(['services.gemini.key' => null]);

        $this->assertFalse(GeminiOneriDanismani::aktifMi());
        $this->assertNull(GeminiOneriDanismani::oner('Bir tespit'));
    }

    public function test_serbest_madde_fotografi_yuklenip_saklanir(): void
    {
        Storage::fake('public');
        $firma = Firma::factory()->for($this->uzman)->create();

        $component = Livewire::test(DefterSayfasi::class)
            ->set('firmaId', $firma->id)
            ->set('serbestTespit', 'Kaygan zemin tespit edildi.')
            ->set('serbestOneri', 'Kaymaz yüzey uygulanmalı.')
            ->set('yeniFoto', UploadedFile::fake()->image('kanit.jpg'))
            ->call('serbestEkle');

        $defter = TespitOneriDefteri::where('firma_id', $firma->id)->firstOrFail();
        $this->assertCount(1, $defter->maddeler);
        $this->assertNotNull($defter->maddeler[0]['foto_yolu']);
        Storage::disk('public')->assertExists($defter->maddeler[0]['foto_yolu']);
        $this->assertNull($component->get('yeniFoto'));
    }

    public function test_fotografli_madde_pdfinde_kanit_sayfasi_olusur(): void
    {
        Storage::fake('public');
        $yol = UploadedFile::fake()->image('kanit.jpg')->store('tespit-oneri-foto', 'public');

        $yol2 = UploadedFile::fake()->image('kanit2.jpg')->store('tespit-oneri-foto', 'public');

        $firma = Firma::factory()->for($this->uzman)->create();
        $defter = TespitOneriDefteri::firmaIcin($firma);
        $defter->update(['maddeler' => [
            ['tespit' => 'Fotosuz madde', 'oneri' => '—', 'dayanak' => null, 'oncelik' => 'orta', 'foto_yolu' => null],
            ['tespit' => 'Kaygan zemin', 'oneri' => 'Kaymaz yüzey', 'dayanak' => null, 'oncelik' => 'orta', 'foto_yolu' => $yol],
            ['tespit' => 'Açık pano', 'oneri' => 'Kapat', 'dayanak' => null, 'oncelik' => 'yuksek', 'foto_yolu' => $yol2],
        ]]);

        $html = view('pdf.tespit-oneri-defteri', ['defter' => $defter, 'firma' => $firma])->render();

        // Fotoğraflar madde indeksinden bağımsız 1, 2 diye sıralanır (madde 2 ve 3'ün fotosu).
        $this->assertStringContainsString('FOTOĞRAF KANITI 1 · Madde 2', $html);
        $this->assertStringContainsString('FOTOĞRAF KANITI 2 · Madde 3', $html);
        $this->assertStringContainsString($yol, $html);
        $this->assertStringContainsString($yol2, $html);
    }

    public function test_madde_silinir(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();
        $defter = TespitOneriDefteri::firmaIcin($firma);
        $defter->update(['maddeler' => [['tespit' => 'X', 'oneri' => 'Y', 'dayanak' => null, 'oncelik' => 'orta']]]);

        Livewire::test(DefterSayfasi::class)
            ->set('firmaId', $firma->id)
            ->call('maddeSil', 0);

        $this->assertCount(0, $defter->fresh()->maddeler);
    }

    public function test_pdf_uretilir(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();
        $defter = TespitOneriDefteri::firmaIcin($firma);
        $defter->update(['maddeler' => [['tespit' => 'X', 'oneri' => 'Y', 'dayanak' => '6331 s.K.', 'oncelik' => 'yuksek']]]);

        $yanit = TespitOneriDefteriUretici::pdf($defter);

        $this->assertInstanceOf(StreamedResponse::class, $yanit);
        ob_start();
        $yanit->sendContent();
        $icerik = ob_get_clean();
        $this->assertStringStartsWith('%PDF', $icerik);
    }

    public function test_baska_uzmanin_firmasi_secilemez(): void
    {
        $baskaUzman = User::factory()->create();
        $baskaFirma = Firma::factory()->for($baskaUzman)->create();

        $firmalar = Livewire::test(DefterSayfasi::class)->instance()->firmalar();

        $this->assertArrayNotHasKey($baskaFirma->id, $firmalar);
    }

    public function test_hazirlanan_word_tablosu_deftere_eklenir(): void
    {
        Storage::fake('local');
        $firma = Firma::factory()->for($this->uzman)->create();
        $defter = TespitOneriDefteri::firmaIcin($firma);
        $defter->update(['maddeler' => [['tespit' => 'Eski madde', 'oneri' => 'Eski öneri', 'dayanak' => null, 'oncelik' => 'orta', 'foto_yolu' => null]]]);

        $word = new PhpWord;
        $t = $word->addSection()->addTable();
        $t->addRow();
        foreach (['Sıra', 'Tespit', 'Öneri', 'Öncelik', 'Dayanak'] as $b) {
            $t->addCell()->addText($b);
        }
        foreach ([['1', 'Yangın tüpü dolum tarihi geçmiş', 'Tüp dolumu yaptırılmalı', 'Yüksek', 'Yangın Yönetmeliği'], ['2', 'Pano kapağı açık', 'Kapak kilitlenmeli', '', '']] as $satir) {
            $t->addRow();
            foreach ($satir as $h) {
                $t->addCell()->addText($h);
            }
        }
        $yol = tempnam(sys_get_temp_dir(), 'tod').'.docx';
        IOFactory::createWriter($word, 'Word2007')->save($yol);

        Livewire::test(DefterSayfasi::class)
            ->set('firmaId', $firma->id)
            ->callAction('wordAktar', data: ['dosya' => UploadedFile::fake()->createWithContent('defter.docx', file_get_contents($yol))])
            ->assertHasNoActionErrors();

        $maddeler = $defter->fresh()->maddeler;
        $this->assertCount(3, $maddeler);
        $this->assertSame('Eski madde', $maddeler[0]['tespit']);
        $this->assertSame('Yangın tüpü dolum tarihi geçmiş', $maddeler[1]['tespit']);
        $this->assertSame('Tüp dolumu yaptırılmalı', $maddeler[1]['oneri']);
        $this->assertSame('yuksek', $maddeler[1]['oncelik']);
        $this->assertSame('Yangın Yönetmeliği', $maddeler[1]['dayanak']);
        $this->assertSame('Kapak kilitlenmeli', $maddeler[2]['oneri']);
        $this->assertNull($maddeler[2]['dayanak']);
    }

    public function test_tablosuz_bilgilendirme_yazisi_on_bilgilendirme_maddesi_olur(): void
    {
        Storage::fake('local');
        $firma = Firma::factory()->for($this->uzman)->create();
        $defter = TespitOneriDefteri::firmaIcin($firma);

        $word = new PhpWord;
        $word->addTitleStyle(1, ['bold' => true]);
        $b = $word->addSection();
        $b->addText('İskele ve Elektrik Hatları Güvenliği');
        $b->addText('İŞ GÜVENLİĞİ BİLGİLENDİRMESİ');
        $b->addText('İşyeri: Örnek Firma   Tarih: 15.10.2026');
        $b->addText('İskelenin yakınında elektrik hatları bulunduğu bildirilmiştir.');
        $b->addTitle('1 Elektrik hatları yakınında alınacak önlemler', 1);
        $b->addText('Dağıtım şirketine yazılı başvuru yapılmalıdır.');
        $b->addText('Hattın enerjisi kesilmelidir.');
        $b->addText('Bilgilerinize sunarım.');
        $b->addText('Uzman Adı');
        $b->addText('Dayanak: Yapı İşlerinde İSG Yönetmeliği.');
        $yol = tempnam(sys_get_temp_dir(), 'tod').'.docx';
        IOFactory::createWriter($word, 'Word2007')->save($yol);

        Livewire::test(DefterSayfasi::class)
            ->set('firmaId', $firma->id)
            ->callAction('wordAktar', data: ['dosya' => UploadedFile::fake()->createWithContent('yazi.docx', file_get_contents($yol))])
            ->assertHasNoActionErrors();

        $m = $defter->fresh()->maddeler[0];
        $this->assertSame('bilgilendirme', $m['tur']);
        $this->assertSame('15.10.2026', $m['tarih']);
        $this->assertSame("İskele ve Elektrik Hatları Güvenliği\nİskelenin yakınında elektrik hatları bulunduğu bildirilmiştir.", $m['tespit']);
        $this->assertSame("1 Elektrik hatları yakınında alınacak önlemler\n• Dağıtım şirketine yazılı başvuru yapılmalıdır.\n• Hattın enerjisi kesilmelidir.", $m['oneri']);
        $this->assertSame('Yapı İşlerinde İSG Yönetmeliği.', $m['dayanak']);
        $this->assertSame(0, SahaBulgusu::count()); // saha bulgusu açılmaz

        $html = view('pdf.tespit-oneri-defteri', ['defter' => $defter->fresh(), 'firma' => $firma])->render();
        $this->assertStringContainsString('Ön Bilgilendirme — 15.10.2026', $html);
        $this->assertStringContainsString('Firmanın alması gereken önlemler', $html);
    }
}
