<?php

namespace Tests\Feature;

use App\Filament\Pages\TalimatOlustur as TalimatSayfasi;
use App\Models\Firma;
use App\Models\Talimat;
use App\Models\User;
use App\Support\GeminiTalimatUretici;
use App\Support\TalimatKutuphanesi;
use App\Support\TalimatUretici;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Talimatlar kullanıcının inşaat talimatlarının yapısında (10.10.2026): künye
 * (doküman no / yayın / revizyon), bölümlü veya düz içerik, taahhüt + tebliğ.
 */
class TalimatYapisiTest extends TestCase
{
    use RefreshDatabase;

    private User $uzman;

    private Firma $firma;

    protected function setUp(): void
    {
        parent::setUp();
        $this->uzman = User::factory()->create(['name' => 'Mehmet Özdemir']);
        $this->actingAs($this->uzman);
        $this->firma = Firma::factory()->for($this->uzman)->create(['unvan' => 'Vizyon Yapı A.Ş.']);
    }

    private function sablonIndeksi(string $baslik): int
    {
        return collect(TalimatKutuphanesi::hazirSablonlar())->search(fn ($s) => $s['baslik'] === $baslik);
    }

    public function test_insaat_talimatlari_kutuphanede_birebir_yer_alir(): void
    {
        $insaat = config('talimat_insaat');
        $this->assertCount(12, $insaat);

        // Genel şablonlar önde: eski 'hazir' indeksleri değişmez
        $this->assertSame(config('isg.talimat.sablonlar.0.baslik'), TalimatKutuphanesi::hazirSablonlar()[0]['baslik']);

        $kalip = collect($insaat)->firstWhere('baslik', 'KALIP İŞLERİ GÜVENLİ ÇALIŞMA TALİMATI');
        $this->assertSame('İn_klp_tlmt', $kalip['dokuman_no']);
        $this->assertCount(16, $kalip['bolumler']);
        $this->assertSame('AMAÇ', $kalip['bolumler'][0]['baslik']);
        $this->assertSame('Aynı anda en fazla iki aks sökülmelidir.', $kalip['bolumler'][7]['maddeler'][1]);

        $boya = collect($insaat)->firstWhere('baslik', 'BOYA İŞLERİNDE GÜVENLİ ÇALIŞMA TALİMATI');
        $this->assertCount(24, $boya['maddeler']);
        // Kaynakta tek paragrafa yapışmış 15-16. maddeler ayrıldı
        $this->assertSame('Boşalan boya ve solvent tenekelerini çalışma mahallinde bulundurmamalıdır.', $boya['maddeler'][15]);
        $this->assertStringContainsString('imza ediyorum', $boya['taahhut']);
    }

    public function test_bolumlu_sablon_secilip_kaydedilir_ve_pdf_duzeni_dogrudur(): void
    {
        $sayfa = Livewire::test(TalimatSayfasi::class)
            ->set('firmaId', $this->firma->id)
            ->call('sablonSec', 'hazir', $this->sablonIndeksi('KALIP İŞLERİ GÜVENLİ ÇALIŞMA TALİMATI'))
            ->assertSet('bolumlu', true)
            ->assertSet('dokumanNo', 'İn_klp_tlmt')
            ->assertSet('taahhut', Talimat::TAAHHUT_BOLUMLU)
            ->set('revizyonNo', '01')
            ->set('yayinTarihi', '2026-03-18')
            ->set('bolumler.0.maddeler', "• Yeni madde bir\n2) Yeni madde iki\n\n");

        $sayfa->callAction('pdf', ['imzali' => '1'])->assertFileDownloaded('talimat-kalip-isleri-guvenli-calisma-talimati.pdf');

        $t = Talimat::where('firma_id', $this->firma->id)->sole();
        $this->assertTrue($t->bolumluMu());
        $this->assertSame([], $t->maddeler);
        $this->assertSame(['Yeni madde bir', 'Yeni madde iki'], $t->bolumler[0]['maddeler']);   // işaret/numara temizlendi
        $this->assertNull($t->taahhut);   // varsayılan saklanmaz
        $this->assertSame('İn_klp_tlmt', $t->dokumanNoGoster());

        $html = view('pdf.talimat', [
            'talimat' => $t, 'firma' => $this->firma, 'logo' => null,
            'tebligEden' => TalimatUretici::tebligEden($this->firma), 'imzali' => true,
        ])->render();
        $this->assertStringContainsString('Doküman No: İn_klp_tlmt', $html);
        $this->assertStringContainsString('Yayınlanma Tarihi: 18.03.2026', $html);
        $this->assertStringContainsString('Revizyon No: 01', $html);
        $this->assertStringContainsString('Vizyon Yapı A.Ş.', $html);   // logo yoksa firma unvanı
        $this->assertStringContainsString('16. ÇALIŞAN YÜKÜMLÜLÜKLERİ', $html);
        $this->assertStringContainsString('17. TAAHHÜT', $html);
        $this->assertStringContainsString('TEBELLÜĞ EDEN', $html);
        $this->assertStringContainsString('Mehmet Özdemir', $html);

        $sayfa->callAction('word', ['imzali' => '0'])->assertFileDownloaded('talimat-kalip-isleri-guvenli-calisma-talimati.docx');
    }

    public function test_duz_talimat_numarali_basilir_ve_otomatik_kunye_alir(): void
    {
        $t = Talimat::create([
            'firma_id' => $this->firma->id, 'baslik' => 'Boya Talimatı',
            'maddeler' => ['Birinci kural.', "Alt maddeli kural;\na. bir,\nb. iki"],
        ]);

        $this->assertSame('TLM-'.$this->firma->id.'-'.str_pad((string) $t->id, 3, '0', STR_PAD_LEFT), $t->dokumanNoGoster());
        $this->assertSame(now()->format('d.m.Y'), $t->yayinTarihiGoster());
        $this->assertSame(Talimat::TAAHHUT_DUZ, $t->taahhutMetni());

        $html = view('pdf.talimat', [
            'talimat' => $t, 'firma' => $this->firma, 'logo' => null,
            'tebligEden' => TalimatUretici::tebligEden($this->firma), 'imzali' => false,
        ])->render();
        $this->assertStringContainsString('2. Alt maddeli kural;<br />', $html);
        $this->assertStringContainsString('Her işçi kendi emniyetini almakla yükümlüdür.', $html);
        $this->assertStringContainsString('TEBLİĞ EDEN', $html);
        $this->assertStringNotContainsString('Mehmet Özdemir', $html);   // imzasız (matbu): ad basılmaz
    }

    public function test_duz_talimat_bolumlu_yapiya_ve_geri_cevrilir(): void
    {
        $sayfa = Livewire::test(TalimatSayfasi::class)
            ->set('firmaId', $this->firma->id)
            ->call('yeniTalimat')
            ->set('baslik', 'Forklift Kullanma Talimatı')
            ->set('kkdler', ['Baret'])
            ->set('maddeler', ['Kural 1', 'Kural 2'])
            ->call('bolumluYap')
            ->assertSet('bolumlu', true)
            ->assertSet('taahhut', Talimat::TAAHHUT_BOLUMLU);

        $bolumler = collect($sayfa->get('bolumler'))->keyBy('baslik');
        $this->assertStringContainsString('Forklift Kullanma sırasında', $bolumler['AMAÇ']['aciklama']);
        $this->assertSame("Kural 1\nKural 2", $bolumler['GENEL KURALLAR']['maddeler']);
        $this->assertSame('Baret', $bolumler['KİŞİSEL KORUYUCU DONANIMLAR (KKD)']['maddeler']);

        // Yeni bölüm sondaki Yasaklar bloğunun önüne girer
        $sayfa->call('bolumEkle');
        $this->assertSame('', $sayfa->get('bolumler')[4]['baslik']);
        $this->assertSame('YASAKLAR', $sayfa->get('bolumler')[5]['baslik']);

        $sayfa->call('duzYap')->assertSet('bolumlu', false)->assertSet('taahhut', Talimat::TAAHHUT_DUZ);
        $this->assertSame(['Kural 1', 'Kural 2', 'Baret'], array_slice($sayfa->get('maddeler'), 0, 3));
    }

    public function test_kayitli_talimat_duzenlenip_ayni_kayit_guncellenir(): void
    {
        $t = Talimat::create([
            'firma_id' => $this->firma->id, 'baslik' => 'Kalıp Talimatı', 'dokuman_no' => 'İn_klp_tlmt',
            'bolumler' => [['baslik' => 'AMAÇ', 'aciklama' => 'Amaç metni', 'maddeler' => []]],
        ]);

        Livewire::test(TalimatSayfasi::class)
            ->set('firmaId', $this->firma->id)
            ->call('kayitliDuzenle', $t->id)
            ->assertSet('bolumlu', true)
            ->assertSet('bolumler.0.aciklama', 'Amaç metni')
            ->set('revizyonNo', '02')
            ->set('revizyonTarihi', '2026-10-10')
            ->callAction('sadeceKaydet');

        $this->assertSame(1, Talimat::where('firma_id', $this->firma->id)->count());
        $t->refresh();
        $this->assertSame('02', $t->revizyon_no);
        $this->assertSame('10.10.2026', $t->revizyon_tarihi->format('d.m.Y'));
    }

    public function test_is_kalemi_hazirlayici_kutuphane_alanlarini_kopyalar(): void
    {
        $alanlar = TalimatKutuphanesi::talimatAlanlari(collect(config('talimat_insaat'))->firstWhere('dokuman_no', 'İn_dmr_tlmt'));

        $this->assertSame('İn_dmr_tlmt', $alanlar['dokuman_no']);
        $this->assertCount(16, $alanlar['bolumler']);
        $this->assertNotEmpty($alanlar['kkdler']);
    }

    public function test_ai_bolumlu_uretim_bolumleri_doldurur(): void
    {
        config(['services.gemini.key' => 'test-key', 'services.gemini.yedek_modeller' => []]);
        Http::fake(['*generativelanguage.googleapis.com*' => Http::response([
            'candidates' => [['content' => ['parts' => [['text' => json_encode([
                ['baslik' => 'AMAÇ', 'aciklama' => 'Bu talimatın amacı; test.', 'maddeler' => []],
                ['baslik' => 'GENEL KURALLAR', 'aciklama' => '', 'maddeler' => ['Kural A', '']],
                ['baslik' => 'TAAHHÜT', 'aciklama' => 'atılmalı', 'maddeler' => []],
            ])]]]]],
        ])]);

        $this->assertCount(2, GeminiTalimatUretici::uretBolumlu('Kalıp', 'İnşaat', []));

        Livewire::test(TalimatSayfasi::class)
            ->set('firmaId', $this->firma->id)
            ->call('yeniTalimat')
            ->set('baslik', 'Kalıp Talimatı')
            ->call('bolumluYap')
            ->call('aiIleUret')
            ->assertSet('bolumler.1.maddeler', 'Kural A')
            ->assertCount('bolumler', 2);
    }
}
