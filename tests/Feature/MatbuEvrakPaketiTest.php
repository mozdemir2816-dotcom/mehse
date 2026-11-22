<?php

namespace Tests\Feature;

use App\Filament\Pages\MatbuEvrakPaketi as Sayfa;
use App\Models\Firma;
use App\Models\IsEkipmani;
use App\Models\KimyasalUrun;
use App\Models\User;
use App\Support\KimyasalOzetleri;
use App\Support\MatbuEvrakPaketi;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use setasign\Fpdi\Fpdi;
use setasign\Fpdi\PdfParser\StreamReader;
use Tests\TestCase;

/** 22.11.2026: firma matbu evrak paketi — kimyasal MSDS özeti (Gemini elle), kimyasal afişi, birleşik PDF. */
class MatbuEvrakPaketiTest extends TestCase
{
    use RefreshDatabase;

    private User $uzman;

    private Firma $firma;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        $this->uzman = User::factory()->create(['rol' => 'sahip']);
        $this->actingAs($this->uzman);
        $this->firma = Firma::factory()->for($this->uzman)->create();
    }

    private function kimyasal(string $ad, ?string $cas = null, ?Firma $firma = null): KimyasalUrun
    {
        return ($firma ?? $this->firma)->kimyasalUrunler()->create(['urun_adi' => $ad, 'cas_no' => $cas, 'aktif' => true]);
    }

    private function cevap(KimyasalUrun $k, array $ek = []): string
    {
        return "Hazırladım:\n```json\n".json_encode([$ek + [
            'id' => $k->id,
            'urun_adi' => $k->urun_adi,
            'sinyal_kelime' => 'TEHLİKE',
            'ghs' => ['GHS02', 'ghs07', 'ghs99'],
            'tehlikeler' => ['H225 Kolay alevlenir sıvı ve buhar', 'H319 Ciddi göz tahrişine yol açar'],
            'onlemler' => ['P210 Isı ve kıvılcımdan uzak tutun'],
            'kkd' => ['Koruyucu gözlük', 'gaz maskesi', 'uçan pelerin'],
            'ilk_yardim' => ['soluma' => 'Temiz havaya çıkarın.', 'cilt' => 'Bol suyla yıkayın.', 'goz' => '15 dakika suyla yıkayın.', 'yutma' => 'Kusturmayın, 114 arayın.'],
            'yangin' => 'Köpük veya CO2 kullanın.',
        ]], JSON_UNESCAPED_UNICODE)."\n```";
    }

    private function sayfaSayisi(string $pdf): int
    {
        return (new Fpdi)->setSourceFile(StreamReader::createByString($pdf));
    }

    public function test_istem_ozetsiz_kimyasallari_id_ile_listeler_ve_parcalara_boler(): void
    {
        $urunler = collect(range(1, 10))->map(fn ($i) => $this->kimyasal("Ürün {$i}"));
        $istemler = KimyasalOzetleri::istemler($urunler);

        $this->assertCount(2, $istemler);
        $this->assertStringContainsString('id '.$urunler[0]->id.': Ürün 1', $istemler[0]);
        $this->assertStringContainsString('(parça 2/2)', $istemler[1]);
        $this->assertStringContainsString('Kimyasal koruyucu eldiven', $istemler[0]);
    }

    public function test_cevap_temizlenip_yalniz_bu_firmanin_urunune_yazilir(): void
    {
        $aseton = $this->kimyasal('Aseton', '67-64-1');
        $baskaFirma = Firma::factory()->for($this->uzman)->create();
        $yabanci = $this->kimyasal('Tiner', null, $baskaFirma);

        $sonuc = KimyasalOzetleri::iceAktar($this->firma, $this->cevap($aseton).$this->cevap($yabanci));

        // İlk JSON bloğundan son köşeli paranteze kadar tek dizi okunamaz: iki ayrı cevap birlikte yapıştırılırsa hata verir.
        $this->assertSame([], $sonuc['kaydedilen']);

        $sonuc = KimyasalOzetleri::iceAktar($this->firma, $this->cevap($yabanci, ['id' => $yabanci->id, 'urun_adi' => 'Tiner']));
        $this->assertSame([], $sonuc['kaydedilen']);
        $this->assertCount(1, $sonuc['hatalar']);
        $this->assertNull($yabanci->fresh()->ozet);

        $sonuc = KimyasalOzetleri::iceAktar($this->firma, $this->cevap($aseton));
        $this->assertSame(['Aseton'], $sonuc['kaydedilen']);

        $aseton->refresh();
        $this->assertSame('Tehlike', $aseton->ozet['sinyal_kelime']);
        $this->assertSame(['ghs02', 'ghs07'], $aseton->ozet['ghs']);
        $this->assertSame(['Koruyucu gözlük', 'Solunum maskesi (filtreli)'], $aseton->ozet['kkd']);
        $this->assertSame(['ghs02', 'ghs07'], $aseton->ghs, 'GHS işaretsizse özetten envantere geçer');
        $this->assertTrue($aseton->ozetOnayBekliyorMu());

        KimyasalOzetleri::onayla($aseton);
        $this->assertFalse($aseton->fresh()->ozetOnayBekliyorMu());
    }

    public function test_baska_firmadaki_ozet_cas_ile_kopyalanir(): void
    {
        $baskaFirma = Firma::factory()->for($this->uzman)->create();
        $kaynak = $this->kimyasal('Aseton (teknik)', '67-64-1', $baskaFirma);
        KimyasalOzetleri::iceAktar($baskaFirma, $this->cevap($kaynak));

        $hedef = $this->kimyasal('ASETON', '67-64-1');
        $digerUzman = Firma::factory()->for(User::factory()->create())->create();
        $baskasininki = $this->kimyasal('Aseton', '67-64-1', $digerUzman);

        $this->assertSame(['ASETON'], KimyasalOzetleri::hazirOzetleriUygula($this->firma));
        $this->assertTrue($hedef->fresh()->ozetVarMi());
        $this->assertSame([], KimyasalOzetleri::hazirOzetleriUygula($digerUzman), 'başka uzmanın özeti kullanılmaz');
        $this->assertFalse($baskasininki->fresh()->ozetVarMi());
    }

    public function test_birlesik_pdf_kapak_ekipman_ve_kimyasal_belgelerini_icerir_ve_gonderildi_isaretler(): void
    {
        $e = IsEkipmani::create(['firma_id' => $this->firma->id, 'ekipman_adi' => 'CNC Tezgâhı', 'tip' => 'CNC Tezgâhı', 'kategori' => 'diger_ekipman', 'aktif' => true]);
        $pasif = IsEkipmani::create(['firma_id' => $this->firma->id, 'ekipman_adi' => 'Eski pres', 'tip' => 'Hidrolik Pres', 'kategori' => 'diger_ekipman', 'aktif' => false]);
        $aseton = $this->kimyasal('Aseton');
        KimyasalOzetleri::iceAktar($this->firma, $this->cevap($aseton));
        $ozetsiz = $this->kimyasal('Bor yağı');

        $sonuc = MatbuEvrakPaketi::olustur($this->firma);

        $this->assertNotNull($sonuc);
        $this->assertSame(1, $sonuc['ekipman']);
        $this->assertSame(1, $sonuc['kimyasal']);
        $this->assertSame($sonuc['sayfa'], $this->sayfaSayisi($sonuc['pdf']));
        // kapak + talimat + aylık çizelge + kimyasal afişi + MSDS özeti
        $this->assertGreaterThanOrEqual(5, $sonuc['sayfa']);

        $this->assertNotNull($e->fresh()->teblig_cikti_at);
        $this->assertNull($pasif->fresh()->teblig_cikti_at);
        $this->assertNotNull($aseton->fresh()->evrak_cikti_at);
        $this->assertNull($ozetsiz->fresh()->evrak_cikti_at);

        $this->assertNull(MatbuEvrakPaketi::olustur($this->firma, [], []));
    }

    public function test_tek_kimyasal_afisi_ve_ozeti_tek_sayfa(): void
    {
        $k = $this->kimyasal('Çok uzun adlı endüstriyel yağ çözücü ve metal temizleyici sprey');
        KimyasalOzetleri::iceAktar($this->firma, $this->cevap($k, [
            'ghs' => ['ghs02', 'ghs05', 'ghs06', 'ghs07', 'ghs08', 'ghs09'],
            'tehlikeler' => array_fill(0, 7, str_repeat('Uzun tehlike ifadesi ', 4)),
            'onlemler' => array_fill(0, 7, str_repeat('Uzun önlem ifadesi ', 4)),
            'dokulme' => str_repeat('Döküntüde yapılacaklar ', 12),
        ]));
        $k->refresh();

        $this->assertCount(5, $k->ozet['tehlikeler']);
        $this->assertSame(1, $this->sayfaSayisi(KimyasalOzetleri::afisPdf($k)));
        $this->assertSame(1, $this->sayfaSayisi(KimyasalOzetleri::ozetPdf($k)));
    }

    public function test_sayfa_yenileri_gosterir_ozet_kaydeder_ve_indirir(): void
    {
        IsEkipmani::create(['firma_id' => $this->firma->id, 'ekipman_adi' => 'CNC Tezgâhı', 'tip' => 'CNC Tezgâhı', 'kategori' => 'diger_ekipman', 'aktif' => true]);
        $aseton = $this->kimyasal('Aseton');

        $sayfa = Livewire::test(Sayfa::class, ['firmaId' => $this->firma->id])
            ->assertOk()
            ->assertSee('Evrakı gönderilmemiş')
            ->assertSee('Eksik MSDS özetlerini hazırlayın')
            ->set('cevap', $this->cevap($aseton))
            ->call('ozetleriKaydet')
            ->assertSee('YZ taslağı — onay bekliyor')
            ->call('onayla', $aseton->id)
            ->assertDontSee('Eksik MSDS özetlerini hazırlayın');

        $sayfa->call('yenileriIndir')->assertFileDownloaded();
        $sayfa->call('yenileriIndir')->assertNotified('Gönderilmemiş evrak yok');
    }

    public function test_baska_uzmanin_firmasi_acilmaz(): void
    {
        $yabanci = Firma::factory()->for(User::factory()->create())->create();
        $k = $this->kimyasal('Aseton', null, $yabanci);

        Livewire::test(Sayfa::class, ['firmaId' => $yabanci->id])
            ->assertDontSee('Aseton')
            ->call('kimyasalAfisi', $k->id)
            ->assertStatus(404);
    }
}
