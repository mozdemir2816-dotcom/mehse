<?php

namespace Tests\Feature;

use App\Filament\Pages\KontrolKarti as KontrolKartiSayfasi;
use App\Models\Firma;
use App\Models\KontrolKarti;
use App\Models\KontrolKartiSablonu;
use App\Models\User;
use App\Support\FotoYonu;
use App\Support\HazirIstemler;
use App\Support\KontrolKartiIceAktarici;
use App\Support\KontrolKartiUretici;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

/** Kontrol Kartı (14.11.2026): fotoğraf üzerinde numaralı işaretli kontrol listesi + Gemini istemi. */
class KontrolKartiTest extends TestCase
{
    use RefreshDatabase;

    private User $uzman;

    private Firma $firma;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        $this->uzman = User::factory()->create();
        $this->actingAs($this->uzman);
        $this->firma = Firma::factory()->for($this->uzman)->create();
    }

    private function gemininCevabi(array $ust, array $maddeler): string
    {
        return "İşte kontrol kartı:\n```json\n".json_encode($ust + ['maddeler' => $maddeler], JSON_UNESCAPED_UNICODE)."\n```\nBaşka sorunuz var mı?";
    }

    public function test_sayfa_tup_sablonuyla_acilir_ve_kart_kaydedilip_pdf_alinir(): void
    {
        $sayfa = Livewire::test(KontrolKartiSayfasi::class)
            ->assertOk()
            ->set('firmaId', $this->firma->id)
            ->assertSee('Gaz dedektörü')
            ->set('foto', UploadedFile::fake()->image('tup.jpg', 800, 600))
            ->call('durumSec', 10, 'uygunsuz')
            ->call('durumSec', 7, 'uygunsuz')
            ->call('isaretKoy', 10, 140, 35.5)   // sınır dışı x → 100
            ->call('kalanlariUygunYap')
            ->assertSee('Fine-Kinney', false);

        $m = $sayfa->get('maddeler');
        $this->assertCount(12, $m);
        $this->assertEquals(['x' => 100, 'y' => 35.5], $m[10]['isaret']);
        $this->assertSame('uygun', $m[0]['durum']);
        $this->assertNotNull($sayfa->get('fotoYolu'));

        $sayfa->callAction('pdf')->assertHasNoActionErrors();

        $k = KontrolKarti::sole();
        $this->assertStringStartsWith('KK-'.now()->format('Y').'-', $k->belge_no);
        $this->assertSame(['uygun' => 10, 'uygunsuz' => 2, 'kapsam_disi' => 0, 'bos' => 0, 'toplam' => 12], KontrolKarti::sayim($k->maddeler));
        Storage::disk('public')->assertExists($k->foto_yolu);

        // PDF gerçekten üretilir (fotoğraf + işaret + risk tablosu)
        $pdf = KontrolKartiUretici::pdf($k);
        ob_start();
        $pdf->sendContent();
        $this->assertStringStartsWith('%PDF', ob_get_clean());

        // "Kaydet" aynı kartı kullanır; sonraki değişiklikler de aynı karta yazılır (KaydetSecenegi)
        $sayfa->callAction('sadeceKaydet');
        $sayfa->call('durumSec', 0, 'kapsam_disi')->callAction('sadeceKaydet');
        $this->assertSame(1, KontrolKarti::count());
        $this->assertSame('kapsam_disi', $k->fresh()->maddeler[0]['durum']);

        // Kayıtlı kart açılıp değiştirilince aynı kart güncellenir
        $sayfa->call('gecmisAc', $k->id)->call('durumSec', 1, 'kapsam_disi')->callAction('sadeceKaydet');
        $this->assertSame(1, KontrolKarti::count());
        $this->assertSame('kapsam_disi', $k->fresh()->maddeler[1]['durum']);
    }

    public function test_hic_madde_isaretlenmeden_kaydedilmez(): void
    {
        Livewire::test(KontrolKartiSayfasi::class)
            ->set('firmaId', $this->firma->id)
            ->callAction('sadeceKaydet');

        $this->assertSame(0, KontrolKarti::count());
    }

    public function test_baska_firmanin_karti_acilamaz(): void
    {
        $baska = Firma::factory()->for(User::factory()->create())->create();
        $k = KontrolKarti::create(['firma_id' => $baska->id, 'sablon' => 'basincli_tup', 'maddeler' => KontrolKarti::bosMaddeler('basincli_tup')]);

        Livewire::test(KontrolKartiSayfasi::class)
            ->set('firmaId', $baska->id)
            ->call('gecmisAc', $k->id)
            ->assertSet('kayitNo', null);
    }

    public function test_gemini_cevabi_var_olan_sablonla_birlestirilir(): void
    {
        $cevap = $this->gemininCevabi(['alan_adi' => 'Basınçlı Tüp Depolama Alanı', 'sablon' => 'basincli_tup'], [
            ['kod' => 'dedektor', 'baslik' => 'Gaz dedektörü', 'durum' => 'Uygunsuz', 'isaret' => ['x' => 92, 'y' => 18],
                'tespit' => 'Dedektör yok.', 'olasilik' => 1.2, 'frekans' => 6, 'siddet' => 40, 'yeni_olasilik' => 0.2, 'yeni_frekans' => 6, 'yeni_siddet' => 40, 'hedef_gun' => 30],
            ['kod' => 'sabitleme', 'baslik' => 'Devrilmeye karşı sabitleme', 'durum' => 'uygun', 'isaret' => ['x' => 55, 'y' => 48]],
            ['kod' => 'aydinlatma', 'baslik' => 'Aydınlatma', 'kontrol' => 'Alan aydınlık mı?', 'durum' => 'uygun', 'isaret' => null],
        ]);

        $s = KontrolKartiIceAktarici::aktar($cevap, $this->uzman->id);

        $this->assertSame('basincli_tup', $s['sablon']);
        $this->assertFalse($s['yeni_sablon']);
        $this->assertCount(13, $s['maddeler']);   // 12 şablon + 1 fazladan
        $dedektor = collect($s['maddeler'])->firstWhere('kod', 'dedektor');
        $this->assertSame('uygunsuz', $dedektor['durum']);
        $this->assertSame('1', $dedektor['olasilik']);        // ölçeğe yuvarlandı
        $this->assertSame('Dedektör yok.', $dedektor['tespit']);
        $this->assertStringContainsString('60079', $dedektor['standart']);   // boşsa şablondan
        $this->assertNull(collect($s['maddeler'])->firstWhere('kod', 'erisim')['durum']);   // Gemini vermedi → bakılmadı
        $this->assertSame('Aydınlatma', $s['maddeler'][12]['baslik']);
        $this->assertSame(0, KontrolKartiSablonu::count());
    }

    public function test_sistemde_olmayan_alan_yeni_sablon_olur_ve_tekrar_olusmaz(): void
    {
        $maddeler = [
            ['baslik' => 'Emniyet ventili', 'kontrol' => 'Emniyet ventili mühürlü mü?', 'durum' => 'uygunsuz', 'isaret' => ['x' => 30, 'y' => 20],
                'tespit' => 'Ventil mühürsüz.', 'oneri' => 'Mühürlü ventil takılmalı.', 'olasilik' => 3, 'frekans' => 6, 'siddet' => 30, 'yeni_olasilik' => 0.5, 'yeni_frekans' => 6, 'yeni_siddet' => 30],
            ['baslik' => 'Kayış muhafazası', 'kontrol' => 'Kayış kasnak muhafazalı mı?', 'durum' => 'uygun', 'isaret' => ['x' => 70, 'y' => 60]],
        ];

        $s = KontrolKartiIceAktarici::aktar($this->gemininCevabi(['alan_adi' => 'Kompresör Odası', 'sablon' => ''], $maddeler), $this->uzman->id);

        $this->assertTrue($s['yeni_sablon']);
        $sablon = KontrolKartiSablonu::sole();
        $this->assertSame('Kompresör Odası', $sablon->ad);
        $this->assertSame($sablon->anahtar, $s['sablon']);
        $this->assertSame(['3', '6', '30'], $sablon->maddeler[0]['once']);
        $this->assertArrayHasKey($sablon->anahtar, KontrolKarti::sablonSecenekleri());
        $this->assertSame('uygunsuz', $s['maddeler'][0]['durum']);

        // Aynı alan tekrar (adı biraz farklı) → mevcut şablon kullanılır
        $tekrar = KontrolKartiIceAktarici::aktar($this->gemininCevabi(['alan_adi' => 'Kompresör odası'], $maddeler), $this->uzman->id);
        $this->assertFalse($tekrar['yeni_sablon']);
        $this->assertSame(1, KontrolKartiSablonu::count());
    }

    public function test_sayfada_gemini_cevabi_fotografla_yuklenir(): void
    {
        $cevap = $this->gemininCevabi(['alan_adi' => 'Basınçlı Tüp Depolama Alanı', 'sablon' => 'basincli_tup'], [
            ['kod' => 'dedektor', 'baslik' => 'Gaz dedektörü', 'durum' => 'uygunsuz', 'isaret' => ['x' => 92, 'y' => 18]],
        ]);

        $sayfa = Livewire::test(KontrolKartiSayfasi::class)
            ->set('firmaId', $this->firma->id)
            ->callAction('geminidenYukle', data: ['cevap' => $cevap, 'foto' => UploadedFile::fake()->image('tup.jpg')])
            ->assertHasNoActionErrors();

        $this->assertNotNull($sayfa->get('fotoYolu'));
        $this->assertEquals(['x' => 92, 'y' => 18], collect($sayfa->get('maddeler'))->firstWhere('kod', 'dedektor')['isaret']);

        // Bozuk cevap: kart değişmez
        $sayfa->callAction('geminidenYukle', data: ['cevap' => 'Üzgünüm, yardımcı olamam.']);
        $this->assertSame('uygunsuz', collect($sayfa->get('maddeler'))->firstWhere('kod', 'dedektor')['durum']);
    }

    public function test_hazir_istem_sablon_listesini_ya_da_yeni_alan_gorevini_icerir(): void
    {
        $istem = HazirIstemler::istem('kontrol_karti', null, ['sablon' => 'basincli_tup']);
        $this->assertStringContainsString('kod=dedektor | Gaz dedektörü', $istem);
        $this->assertStringContainsString('"sablon": "basincli_tup"', $istem);
        $this->assertStringContainsString('0.5 (Zayıf olasılık', $istem);

        $yeni = HazirIstemler::istem('kontrol_karti', null, ['sablon' => '', 'adet' => 10]);
        $this->assertStringContainsString('10 maddelik bir kontrol listesi', $yeni);

        // İstemdeki örnek JSON içe aktarıcıdan geçer
        preg_match('/(\{"alan_adi".+\})\s*$/su', $yeni, $m);
        $ornek = str_replace('…', 'Örnek', $m[1]);
        $s = KontrolKartiIceAktarici::aktar($ornek, $this->uzman->id);
        $this->assertSame('uygunsuz', $s['maddeler'][0]['durum']);
    }

    public function test_kurulum_komutu_tablo_varsa_bir_sey_yapmaz(): void
    {
        $this->artisan('kurulum:kontrol-karti')->expectsOutputToContain('zaten var')->assertSuccessful();
    }

    public function test_buyuk_fotograf_kucultulur(): void
    {
        $yol = UploadedFile::fake()->image('buyuk.jpg', 3200, 2400)->store('kontrol-karti-foto', 'public');

        FotoYonu::duzelt($yol);

        [$g, $y] = getimagesize(Storage::disk('public')->path($yol));
        $this->assertSame([1600, 1200], [$g, $y]);
    }
}
