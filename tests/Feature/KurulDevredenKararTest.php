<?php

namespace Tests\Feature;

use App\Filament\Pages\KurulToplantisi as KurulSayfasi;
use App\Models\Firma;
use App\Models\KurulToplantisi;
use App\Models\User;
use App\Support\BildirimTarayici;
use App\Support\KurulToplantisiUretici;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Kapanmamış kurul kararları sonraki toplantıya devreder (isg-kurul-takip.html "Gündem" mantığı):
 * her toplantıda durum + görüşme notu girilir, "Tamamlandı" kararlar devretmez.
 */
class KurulDevredenKararTest extends TestCase
{
    use RefreshDatabase;

    private User $uzman;

    private Firma $firma;

    protected function setUp(): void
    {
        parent::setUp();
        $this->uzman = User::factory()->create();
        $this->actingAs($this->uzman);
        $this->firma = Firma::factory()->for($this->uzman)->create();
    }

    private function toplanti(string $tarih, string $no, array $kararlar = []): KurulToplantisi
    {
        return KurulToplantisi::create([
            'firma_id' => $this->firma->id, 'toplanti_no' => $no, 'tarih' => $tarih,
            'katilimcilar' => [], 'gundem' => ['G'], 'kararlar' => $kararlar,
        ]);
    }

    private function karar(string $metin, string $durum = 'beklemede', ?string $termin = null, ?string $kapanis = null): array
    {
        return ['gundem_maddesi' => 'G', 'karar_metni' => $metin, 'sorumlu' => 'İGU', 'termin' => $termin, 'durum' => $durum, 'kapanis_tarihi' => $kapanis];
    }

    public function test_kapanmamis_kararlar_sonraki_toplantiya_devreder_kapananlar_devretmez(): void
    {
        $ilk = $this->toplanti('2026-01-10', '2026/1', [
            $this->karar('Açık karar', 'devam_ediyor', '2026-02-01'),
            $this->karar('Kapanan karar', 'tamamlandi', null, '2026-02-01'),
            $this->karar('Ertelenen karar', 'ertelendi'),
        ]);
        $ikinci = $this->toplanti('2026-03-10', '2026/2');

        // Her karara tekil kimlik yazıldı
        $this->assertTrue(collect($ilk->fresh()->kararlar)->every(fn ($k) => filled($k['id'])));

        $devreden = $ikinci->devredenKararlar();
        $this->assertSame(['Açık karar', 'Ertelenen karar'], array_column($devreden, 'karar'));
        $this->assertTrue($devreden[0]['gecikti']);   // termin 01.02 < toplantı 10.03
        $this->assertSame('devam_ediyor', $devreden[0]['durum']);

        // 01.02'den önceki bir toplantıda "kapanan" karar henüz açıktı
        $arada = $this->toplanti('2026-01-20', '2026/1-b');
        $this->assertContains('Kapanan karar', array_column($arada->devredenKararlar(), 'karar'));

        // İlk toplantının kendisine ve tarihsiz toplantıya devir yok
        $this->assertSame([], $ilk->devredenKararlar());
    }

    public function test_devreden_karar_durumu_ve_notu_kaydedilir_tamamlaninca_devretmez(): void
    {
        $ilk = $this->toplanti('2026-01-10', '2026/1', [$this->karar('Pano etiketlenecek')]);
        $ikinci = $this->toplanti('2026-03-10', '2026/2');
        $ucuncu = $this->toplanti('2026-06-10', '2026/3');

        $d = $ikinci->devredenKararlar()[0];
        $this->assertTrue($ikinci->devredenGuncelle($d['kaynak_id'], $d['id'], 'devam_ediyor', null));
        $this->assertTrue($ikinci->devredenGuncelle($d['kaynak_id'], $d['id'], null, 'Malzeme sipariş edildi'));

        $d = $ikinci->devredenKararlar()[0];
        $this->assertSame('devam_ediyor', $d['durum']);
        $this->assertSame('Malzeme sipariş edildi', $d['not']);   // durum korunarak not eklendi
        $this->assertCount(1, $ilk->fresh()->kararlar[0]['gorusmeler']);

        // Üçüncü toplantıda hâlâ açık, "devam ediyor" olarak devreder
        $d3 = $ucuncu->devredenKararlar()[0];
        $this->assertSame('devam_ediyor', $d3['onceki_durum']);

        // Üçüncü toplantıda tamamlandı → dördüncüye devretmez, ikinci toplantının geçmişi bozulmaz
        $this->assertTrue($ucuncu->devredenGuncelle($d3['kaynak_id'], $d3['id'], 'tamamlandi', 'Yapıldı'));
        $dorduncu = $this->toplanti('2026-09-10', '2026/4');
        $this->assertSame([], $dorduncu->devredenKararlar());
        $this->assertCount(1, $ikinci->devredenKararlar());
        $this->assertSame('2026-06-10', $ilk->fresh()->kararlar[0]['kapanis_tarihi']);

        // Geçersiz durum / bilinmeyen karar reddedilir
        $this->assertFalse($ucuncu->devredenGuncelle($d3['kaynak_id'], $d3['id'], 'bilinmeyen', null));
        $this->assertFalse($ucuncu->devredenGuncelle($d3['kaynak_id'], 'yok', 'tamamlandi', null));
    }

    public function test_sayfada_devreden_bolumu_gorunur_ve_durum_notu_guncellenir(): void
    {
        $ilk = $this->toplanti('2026-01-10', '2026/1', [$this->karar('Yangın tüpleri kontrol edilecek')]);
        $ikinci = $this->toplanti('2026-03-10', '2026/2');

        $sayfa = Livewire::test(KurulSayfasi::class)
            ->set('firmaId', $this->firma->id)
            ->call('toplantiSec', $ikinci->id)
            ->assertSee('Önceki toplantılardan devreden kararlar')
            ->assertSee('Yangın tüpleri kontrol edilecek');

        $kararId = $ilk->fresh()->kararlar[0]['id'];
        $sayfa->call('devredenDurum', $ilk->id, $kararId, 'ertelendi')
            ->call('devredenNotu', $ilk->id, $kararId, '  Bütçe onayı bekleniyor ');

        $d = $ikinci->fresh()->devredenKararlar()[0];
        $this->assertSame('ertelendi', $d['durum']);
        $this->assertSame('Bütçe onayı bekleniyor', $d['not']);

        // Devreden olmayan ilk toplantıda bölüm görünmez
        Livewire::test(KurulSayfasi::class)
            ->set('firmaId', $this->firma->id)
            ->call('toplantiSec', $ilk->id)
            ->assertDontSee('Önceki toplantılardan devreden kararlar');
    }

    public function test_karar_durumu_kapanis_tarihi_ve_ertelendi(): void
    {
        $t = $this->toplanti('2026-01-10', '2026/1', [$this->karar('X')]);

        $sayfa = Livewire::test(KurulSayfasi::class)->set('firmaId', $this->firma->id)->call('toplantiSec', $t->id);
        $sayfa->call('kararDurumGuncelle', 0, 'tamamlandi');
        $this->assertSame(now()->toDateString(), $t->fresh()->kararlar[0]['kapanis_tarihi']);

        $sayfa->call('kararDurumGuncelle', 0, 'ertelendi');
        $this->assertNull($t->fresh()->kararlar[0]['kapanis_tarihi']);
        $this->assertSame('ertelendi', $t->fresh()->kararlar[0]['durum']);

        $sayfa->call('kararDurumGuncelle', 0, 'gecersiz');
        $this->assertSame('ertelendi', $t->fresh()->kararlar[0]['durum']);
    }

    public function test_tutanak_ciktilarinda_devreden_bolumu_var(): void
    {
        $this->toplanti('2026-01-10', '2026/1', [$this->karar('Devreden iş', 'devam_ediyor', '2026-02-01')]);
        $ikinci = $this->toplanti('2026-03-10', '2026/2', [$this->karar('Yeni karar')]);
        $d = $ikinci->devredenKararlar()[0];
        $ikinci->devredenGuncelle($d['kaynak_id'], $d['id'], 'devam_ediyor', 'Teklif alındı');

        $html = view('pdf.kurul-toplantisi', ['toplanti' => $ikinci->fresh(), 'firma' => $this->firma])->render();
        $this->assertStringContainsString('Önceki Toplantılardan Devreden Kararlar', $html);
        $this->assertStringContainsString('Devreden iş', $html);
        $this->assertStringContainsString('Devam ediyor — Teklif alındı', $html);
        $this->assertStringContainsString('Bu Toplantıda Alınan Kararlar', $html);

        // İlk toplantıda bölüm yok, eski başlık korunur
        $ilkHtml = view('pdf.kurul-toplantisi', ['toplanti' => KurulToplantisi::where('toplanti_no', '2026/1')->first(), 'firma' => $this->firma])->render();
        $this->assertStringNotContainsString('Devreden Kararlar', $ilkHtml);
        $this->assertStringContainsString('Kararlar ve Takip', $ilkHtml);

        // PDF / Excel / Word üretimi hatasız
        foreach (['pdf' => '%PDF', 'excel' => 'PK', 'word' => 'PK'] as $tur => $baslangic) {
            ob_start();
            KurulToplantisiUretici::$tur($ikinci->fresh())->sendContent();
            $this->assertStringStartsWith($baslangic, ob_get_clean(), $tur);
        }

        // Word içeriğinde devreden bölüm
        ob_start();
        KurulToplantisiUretici::word($ikinci->fresh())->sendContent();
        $yol = tempnam(sys_get_temp_dir(), 'kurul').'.docx';
        file_put_contents($yol, ob_get_clean());
        $zip = new \ZipArchive;
        $zip->open($yol);
        $xml = $zip->getFromName('word/document.xml');
        $zip->close();
        @unlink($yol);
        $this->assertStringContainsString('Önceki Toplantılardan Devreden Kararlar', $xml);
        $this->assertStringContainsString('Teklif alındı', $xml);
    }

    public function test_termini_gecen_acik_kurul_karari_bildirim_uretir(): void
    {
        $this->toplanti('2026-01-10', '2026/1', [
            $this->karar('Süresi geçti', 'beklemede', '2026-02-01'),
            $this->karar('Kapandı', 'tamamlandi', '2026-02-01', '2026-02-02'),
        ]);

        $liste = BildirimTarayici::firmaBildirimleri($this->firma);
        $bildirim = $liste->firstWhere('anahtar', 'f'.$this->firma->id.':kurul_karar_gecikme');

        $this->assertNotNull($bildirim);
        $this->assertStringStartsWith('1 karar', $bildirim['aciklama']);
        $this->assertStringContainsString('Süresi geçti', $bildirim['aciklama']);
        $this->assertStringNotContainsString('Kapandı', $bildirim['aciklama']);

        // Tamamlanınca uyarı kalkar
        $t = KurulToplantisi::first();
        $kararlar = $t->kararlar;
        $kararlar[0]['durum'] = 'tamamlandi';
        $t->update(['kararlar' => $kararlar]);
        $this->assertNull(BildirimTarayici::firmaBildirimleri($this->firma)->firstWhere('anahtar', 'f'.$this->firma->id.':kurul_karar_gecikme'));
    }
}
