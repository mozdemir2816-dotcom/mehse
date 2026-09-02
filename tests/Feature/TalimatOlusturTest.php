<?php

namespace Tests\Feature;

use App\Filament\Pages\TalimatOlustur as TalimatSayfasi;
use App\Models\Firma;
use App\Models\Talimat;
use App\Models\TalimatSablonu;
use App\Models\User;
use App\Support\GeminiTalimatUretici;
use App\Support\TalimatSablonuExcelIceAktarici;
use App\Support\TalimatUretici;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Tests\TestCase;

class TalimatOlusturTest extends TestCase
{
    use RefreshDatabase;

    private User $uzman;

    protected function setUp(): void
    {
        parent::setUp();
        $this->uzman = User::factory()->create();
        $this->actingAs($this->uzman);
    }

    public function test_sablon_secilince_alanlar_dolar(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();

        $component = Livewire::test(TalimatSayfasi::class)
            ->set('firmaId', $firma->id)
            ->call('sablonSec', 'hazir', 0);

        $ilkSablon = config('isg.talimat.sablonlar.0');
        $this->assertSame($ilkSablon['baslik'], $component->get('baslik'));
        $this->assertSame($ilkSablon['kkdler'], $component->get('kkdler'));
    }

    public function test_kategori_filtresi_sablonlari_daraltir(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();

        $component = Livewire::test(TalimatSayfasi::class)
            ->set('firmaId', $firma->id)
            ->set('kategoriFiltre', 'mutfak_yemekhane');

        $sablonlar = $component->get('sablonlar');
        $this->assertNotEmpty($sablonlar);
        foreach ($sablonlar as $s) {
            $this->assertSame('mutfak_yemekhane', $s['kategori']);
        }
    }

    public function test_ai_api_anahtari_yokken_pasif_ve_istek_atilmaz(): void
    {
        config(['services.gemini.key' => null]);
        Http::fake();

        $this->assertFalse(GeminiTalimatUretici::aktifMi());
        $this->assertSame([], GeminiTalimatUretici::uret('Forklift Kullanma Talimatı', 'İş Makineleri', ['Baret']));
        Http::assertNothingSent();
    }

    public function test_ai_gecerli_yanit_maddelere_donusturulur(): void
    {
        config(['services.gemini.key' => 'test-key']);

        Http::fake([
            '*generativelanguage.googleapis.com*' => Http::response([
                'candidates' => [['content' => ['parts' => [['text' => json_encode([
                    'Forklift sürücü belgesi olmayan kullanamaz.',
                    'Kullanım öncesi fren ve korna kontrolü yapılır.',
                ])]]]]],
            ], 200),
        ]);

        $maddeler = GeminiTalimatUretici::uret('Forklift Kullanma Talimatı', 'İş Makineleri', ['Baret']);

        $this->assertCount(2, $maddeler);
    }

    public function test_manuel_madde_eklenir_ve_silinir(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();

        $component = Livewire::test(TalimatSayfasi::class)
            ->set('firmaId', $firma->id)
            ->call('sablonSec', 'hazir', 0)
            ->set('yeniMadde', 'Sahaya sadece yetkili operatör girer.')
            ->call('maddeEkle');

        $this->assertCount(1, $component->get('maddeler'));

        $component->call('maddeSil', 0);
        $this->assertCount(0, $component->get('maddeler'));
    }

    public function test_pdf_aksiyonu_kayit_olusturur(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();

        Livewire::test(TalimatSayfasi::class)
            ->set('firmaId', $firma->id)
            ->call('sablonSec', 'hazir', 0)
            ->set('yeniMadde', 'Test maddesi')
            ->call('maddeEkle')
            ->callAction('pdf');

        $talimat = Talimat::where('firma_id', $firma->id)->firstOrFail();
        $ilkSablon = config('isg.talimat.sablonlar.0');
        $this->assertSame($ilkSablon['baslik'], $talimat->baslik);
        $this->assertCount(1, $talimat->maddeler);
    }

    public function test_baslik_olmadan_kaydedilemez(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();

        Livewire::test(TalimatSayfasi::class)
            ->set('firmaId', $firma->id)
            ->callAction('pdf');

        $this->assertDatabaseCount('talimatlar', 0);
    }

    public function test_pdf_uretilir(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();
        $talimat = Talimat::create([
            'firma_id' => $firma->id,
            'baslik' => 'Forklift Kullanma Talimatı',
            'kategori' => 'is_makineleri',
            'aciklama' => 'Test açıklama',
            'kkdler' => ['Baret'],
            'maddeler' => ['Madde 1'],
        ]);

        $yanit = TalimatUretici::pdf($talimat);

        $this->assertInstanceOf(StreamedResponse::class, $yanit);
        ob_start();
        $yanit->sendContent();
        $icerik = ob_get_clean();
        $this->assertStringStartsWith('%PDF', $icerik);
    }

    public function test_kayitli_talimat_silinir(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();
        $talimat = Talimat::create(['firma_id' => $firma->id, 'baslik' => 'Test']);

        Livewire::test(TalimatSayfasi::class)
            ->set('firmaId', $firma->id)
            ->call('kayitliSil', $talimat->id);

        $this->assertDatabaseMissing('talimatlar', ['id' => $talimat->id]);
    }

    public function test_baska_uzmanin_firmasi_secilemez(): void
    {
        $baskaUzman = User::factory()->create();
        $baskaFirma = Firma::factory()->for($baskaUzman)->create();

        $firmalar = Livewire::test(TalimatSayfasi::class)->instance()->firmalar();

        $this->assertArrayNotHasKey($baskaFirma->id, $firmalar);
    }

    public function test_kendi_arsivindeki_sablon_kutuphanede_gorunur_ve_secilebilir(): void
    {
        $ozel = TalimatSablonu::create([
            'user_id' => $this->uzman->id,
            'baslik' => 'Kompresör Kullanma Talimatı',
            'kategori' => 'is_makineleri',
            'aciklama' => 'Kendi arşivimden',
            'kkdler' => ['Kulak tıkacı'],
            'maddeler' => ['Basınç göstergesi kontrol edilir.'],
        ]);

        $component = Livewire::test(TalimatSayfasi::class);
        $sablonlar = collect($component->get('sablonlar'));

        $this->assertTrue($sablonlar->contains(fn ($s) => $s['kaynak'] === 'ozel' && $s['anahtar'] === $ozel->id));

        $component->call('sablonSec', 'ozel', $ozel->id);
        $this->assertSame('Kompresör Kullanma Talimatı', $component->get('baslik'));
        $this->assertSame(['Kulak tıkacı'], $component->get('kkdler'));
    }

    public function test_baska_uzmanin_arsiv_sablonu_gorunmez(): void
    {
        $baskaUzman = User::factory()->create();
        TalimatSablonu::create(['user_id' => $baskaUzman->id, 'baslik' => 'Başkasının Şablonu']);

        $sablonlar = collect(Livewire::test(TalimatSayfasi::class)->get('sablonlar'));

        $this->assertFalse($sablonlar->contains(fn ($s) => $s['baslik'] === 'Başkasının Şablonu'));
    }

    public function test_kendi_sablonu_silinir(): void
    {
        $ozel = TalimatSablonu::create(['user_id' => $this->uzman->id, 'baslik' => 'Silinecek Şablon']);

        Livewire::test(TalimatSayfasi::class)->call('kendiSablonSil', $ozel->id);

        $this->assertDatabaseMissing('talimat_sablonlari', ['id' => $ozel->id]);
    }

    public function test_excelden_arsiv_sablonlari_toplu_yuklenir(): void
    {
        $kitap = new Spreadsheet();
        $sayfa = $kitap->getActiveSheet();
        $sayfa->fromArray(TalimatSablonuExcelIceAktarici::SABLON_BASLIKLARI, null, 'A1');
        $sayfa->fromArray(['Kompresör Kullanma Talimatı', 'İş Makineleri', 'Açıklama', 'Kulak tıkacı, Koruyucu gözlük', "Madde bir.\nMadde iki."], null, 'A2');
        $sayfa->fromArray(['', 'İş Makineleri', 'Başlıksız satır atlanmalı', '', ''], null, 'A3');
        $yol = tempnam(sys_get_temp_dir(), 'xlsx').'.xlsx';
        (new Xlsx($kitap))->save($yol);

        $sonuc = TalimatSablonuExcelIceAktarici::iceAktar($yol, $this->uzman->id);
        unlink($yol);

        $this->assertSame(1, $sonuc['basarili']);
        $this->assertNotEmpty($sonuc['hatalar']);
        $bu = TalimatSablonu::where('baslik', 'Kompresör Kullanma Talimatı')->firstOrFail();
        $this->assertSame('is_makineleri', $bu->kategori);
        $this->assertSame(['Kulak tıkacı', 'Koruyucu gözlük'], $bu->kkdler);
        $this->assertSame(['Madde bir.', 'Madde iki.'], $bu->maddeler);
    }

    public function test_excel_sablonu_indirilebilir(): void
    {
        $yanit = TalimatSablonuExcelIceAktarici::sablonIndir();

        $this->assertInstanceOf(StreamedResponse::class, $yanit);
    }
}
