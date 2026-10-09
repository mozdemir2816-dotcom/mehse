<?php

namespace Tests\Feature;

use App\Filament\Pages\KurulToplantisi as KurulSayfasi;
use App\Models\Firma;
use App\Models\KurulToplantisi;
use App\Models\User;
use App\Support\KurulCagriUretici;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/** İSG Kurulu Toplantıya Çağrı Formu — Yönetmelik Md.9 (48 saat önce bildirim). */
class KurulCagriTest extends TestCase
{
    use RefreshDatabase;

    private User $uzman;

    protected function setUp(): void
    {
        parent::setUp();
        $this->uzman = User::factory()->create();
        $this->actingAs($this->uzman);
    }

    private function toplanti(Firma $firma, array $ek = []): KurulToplantisi
    {
        return $firma->kurulToplantilari()->create([
            'tarih' => '2026-10-20', 'saat' => '14:00', 'yer' => 'Toplantı Salonu', 'toplanti_no' => '2026/5', 'tur' => 'olagan',
            'katilimcilar' => [
                ['ad_soyad' => 'Başkan Kişi', 'gorev' => 'İşveren Vekili', 'rol' => 'baskan', 'katildi' => true],
                ['ad_soyad' => 'Uzman Kişi', 'gorev' => 'İGU', 'rol' => 'sekreter', 'katildi' => true],
                ['ad_soyad' => 'Temsilci Kişi', 'gorev' => 'Usta', 'rol' => 'calisan_temsilcisi', 'katildi' => false],
            ],
            'gundem' => ['Risk değerlendirmesi sonuçları', 'Tatbikat planı'],
            'kararlar' => [],
            ...$ek,
        ]);
    }

    public function test_olagan_toplantida_48_saat_kurali_denetlenir(): void
    {
        $t = $this->toplanti(Firma::factory()->for($this->uzman)->create());

        $this->assertTrue(KurulCagriUretici::sureYeterli($t, '2026-10-18'));
        $this->assertFalse(KurulCagriUretici::sureYeterli($t, '2026-10-19'));
        $this->assertFalse(KurulCagriUretici::sureYeterli($t, '2026-10-20'));

        $t->tur = 'olaganustu';   // olağanüstüde süre aciliyete göre — engel yok
        $this->assertTrue(KurulCagriUretici::sureYeterli($t, '2026-10-20'));
    }

    public function test_cagri_tum_uyeleri_gundemi_ve_onceki_acik_kararlari_icerir(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();
        $firma->kurulToplantilari()->create([
            'tarih' => '2026-09-20', 'katilimcilar' => [], 'gundem' => [],
            'kararlar' => [
                ['gundem_maddesi' => 'X', 'karar_metni' => 'Yangın tüpleri yenilenecek', 'sorumlu' => 'Bakım', 'termin' => '2026-10-01', 'durum' => 'devam_ediyor'],
                ['gundem_maddesi' => 'X', 'karar_metni' => 'Biten karar', 'durum' => 'tamamlandi'],
            ],
        ]);
        $t = $this->toplanti($firma);

        $v = KurulCagriUretici::veri($t, ['cagri_tarihi' => '2026-10-15']);

        $this->assertCount(3, $v['davetliler']);                 // katılmadı işaretli üye de davet edilir
        $this->assertSame('Başkan Kişi', $v['baskan']);
        $this->assertSame('Uzman Kişi', $v['sekreter']);
        $this->assertSame('15.10.2026', $v['cagri_tarihi']);
        $this->assertTrue($v['sure_yeterli']);
        $this->assertCount(1, $v['onceki_kararlar']);
        $this->assertSame('Devam ediyor', $v['onceki_kararlar'][0]['durum']);
        $this->assertStringContainsString('2026/5 sayılı olağan toplantısı', $v['metin']);

        $this->assertSame([], KurulCagriUretici::veri($t, ['onceki_kararlar' => false])['onceki_kararlar']);
    }

    public function test_pdf_ve_word_sayfadan_indirilir(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create(['unvan' => 'Örnek & Ortak A.Ş.']);
        $t = $this->toplanti($firma, ['tur' => 'olaganustu']);

        $sayfa = Livewire::test(KurulSayfasi::class)
            ->set('firmaId', $firma->id)
            ->call('toplantiSec', $t->id)
            ->assertActionVisible('cagri');

        $sayfa->callAction('cagri', ['cagri_tarihi' => '2026-10-19', 'bicim' => 'pdf', 'olaganustu_nedeni' => 'Ağır iş kazası'])
            ->assertHasNoActionErrors()
            ->assertFileDownloaded('kurul-cagri-ornek-ortak-as-2026-5.pdf');

        $sayfa->callAction('cagri', ['cagri_tarihi' => '2026-10-19', 'bicim' => 'word'])
            ->assertFileDownloaded('kurul-cagri-ornek-ortak-as-2026-5.docx');

        $v = KurulCagriUretici::veri($t, ['olaganustu_nedeni' => 'Ağır iş kazası']);
        $html = view('pdf.kurul-cagri', $v + ['bilgi' => KurulCagriUretici::belgeBilgisi($v), 'kunye' => KurulCagriUretici::kunye($v)])->render();
        $this->assertStringContainsString('TOPLANTIYA ÇAĞRI FORMU', $html);
        $this->assertStringContainsString('Ağır iş kazası', $html);
        $this->assertStringContainsString('Temsilci Kişi', $html);
        $this->assertStringContainsString('Tebliğ – Tebellüğ', $html);
        $this->assertStringContainsString('olağanüstü toplantıdır', $html);

        ob_start();
        KurulCagriUretici::word($t, ['olaganustu_nedeni' => 'Ağır iş kazası'])->sendContent();
        $docx = ob_get_clean();
        $yol = tempnam(sys_get_temp_dir(), 'cagri').'.docx';
        file_put_contents($yol, $docx);
        $zip = new \ZipArchive;
        $zip->open($yol);
        $xml = $zip->getFromName('word/document.xml');
        $zip->close();
        @unlink($yol);

        $this->assertStringContainsString('Örnek &amp; Ortak A.Ş.', $xml);
        $this->assertStringContainsString('Tatbikat planı', strip_tags($xml));
        $this->assertStringContainsString('Tebellüğ', strip_tags($xml));
    }
}
