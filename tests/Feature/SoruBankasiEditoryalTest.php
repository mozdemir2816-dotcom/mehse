<?php

namespace Tests\Feature;

use App\Filament\Pages\SoruBankasi;
use App\Models\NaceKodu;
use App\Models\SoruBankasiSorusu;
use App\Models\User;
use App\Support\SoruBankasiIceAktarici;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class SoruBankasiEditoryalTest extends TestCase
{
    use RefreshDatabase;

    private User $uzman;

    protected function setUp(): void
    {
        parent::setUp();
        $this->uzman = User::factory()->create();
        $this->actingAs($this->uzman);
    }

    private function soru(array $ek = []): SoruBankasiSorusu
    {
        return SoruBankasiSorusu::create([
            'user_id' => $this->uzman->id, 'konu' => 'genel_isg', 'zorluk' => 'orta', 'soru' => 'Soru?',
            'secenekler' => ['A', 'B', 'C', 'D'], 'dogru_index' => 0, 'durum' => 'taslak',
            'aciklama' => 'Gerekçe', 'kaynaklar' => [['ad' => '6331 sK', 'url' => null, 'madde' => 'Md.4', 'tarih' => null]], ...$ek,
        ]);
    }

    public function test_form_kod_nace_coklu_kaynak_ve_inceleme_notu_kaydeder(): void
    {
        Livewire::test(SoruBankasi::class)
            ->set('yeniKod', 'NACE-43.21-001')
            ->set('yeniSoru', 'Elektrik tesisatında çalışmadan önce ne yapılır?')
            ->set('yeniSecenekler', ['Enerji kesilip kilitlenir', 'Eldivenle devam edilir', 'Hızlı çalışılır', 'Hiçbiri'])
            ->set('yeniNace', '43.21, 41')
            ->set('yeniAciklama', 'Kilitleme-etiketleme önce gelir.')
            ->set('yeniKaynaklar', [
                ['ad' => 'Elektrik İç Tesisleri Yön.', 'url' => 'https://www.mevzuat.gov.tr/', 'madde' => 'Md.5', 'tarih' => '2026-10-03'],
                ['ad' => 'İş Ekipmanları Yön.', 'url' => '', 'madde' => '', 'tarih' => ''],
            ])
            ->set('yeniIncelemeNotu', 'Kontrol edildi')
            ->call('soruEkle');

        $s = SoruBankasiSorusu::firstOrFail();
        $this->assertSame('NACE-43.21-001', $s->soru_kodu);
        $this->assertSame(',4321,41,', $s->nace_onekleri);
        $this->assertSame(['43.21', '41'], $s->naceKapsami());
        $this->assertCount(2, $s->kaynakListesi());
        $this->assertSame('Elektrik İç Tesisleri Yön.', $s->kaynak);
        $this->assertSame('Kontrol edildi', $s->inceleme_notu);
        $this->assertSame('taslak', $s->durum);
    }

    public function test_ayni_secenekler_ve_kaynaksiz_soru_reddedilir(): void
    {
        Livewire::test(SoruBankasi::class)
            ->set('yeniSoru', 'S?')->set('yeniSecenekler', ['A', 'a', 'B', 'C'])->set('yeniKaynak', 'K')->call('soruEkle')
            ->set('yeniSecenekler', ['A', 'B', 'C', 'D'])->set('yeniKaynak', null)->call('soruEkle');

        $this->assertDatabaseCount('soru_bankasi_sorulari', 0);
    }

    public function test_inceleme_yayim_sarti_ve_yeni_surum(): void
    {
        $eksik = $this->soru(['aciklama' => null]);
        $s = $this->soru(['soru_kodu' => 'ORTAK-001']);

        $lw = Livewire::test(SoruBankasi::class)
            ->call('onayla', $eksik->id)
            ->call('incelemeyeGonder', $s->id);
        $this->assertSame('taslak', $eksik->fresh()->durum);   // gerekçesiz yayımlanmaz
        $this->assertSame('incelemede', $s->fresh()->durum);

        $lw->call('onayla', $s->id);
        $this->assertSame('onaylandi', $s->fresh()->durum);

        $lw->call('yeniSurum', $s->id)->assertSet('yeniKod', 'ORTAK-001');
        $v2 = SoruBankasiSorusu::where('onceki_surum_id', $s->id)->firstOrFail();
        $this->assertSame(2, $v2->surum);
        $this->assertSame('taslak', $v2->durum);
        $this->assertSame('onaylandi', $s->fresh()->durum);   // yeni sürüm yayımlanana kadar eski kullanımda

        $lw->set('yeniSoru', 'Güncel soru?')->call('soruEkle');   // form v2'yi günceller
        $this->assertSame('Güncel soru?', $v2->fresh()->soru);

        $lw->call('onayla', $v2->id);
        $this->assertSame('onaylandi', $v2->fresh()->durum);
        $this->assertSame('arsiv', $s->fresh()->durum);
        $this->assertSame('ORTAK-001 v2', $v2->fresh()->kodEtiketi());
    }

    public function test_kapsam_nace_on_ekiyle_eslesir(): void
    {
        $this->soru(['durum' => 'onaylandi']);                                                // ortak
        $this->soru(['durum' => 'onaylandi', 'nace_onekleri' => ',4321,']);                   // 43.21.xx
        $this->soru(['durum' => 'onaylandi', 'nace_onekleri' => ',10,']);                     // gıda
        $this->soru(['durum' => 'onaylandi', 'sektor_anahtari' => 'insaat']);

        $say = fn (?string $sektor, ?string $nace) => SoruBankasiSorusu::query()->onayli()->kapsamaUygun($sektor, $nace)->count();

        $this->assertSame(2, $say(null, '43.21.01'));
        $this->assertSame(3, $say('insaat', '43.21.01'));
        $this->assertSame(1, $say(null, '43.22.01'));
        $this->assertSame(2, $say(null, '10.11.01'));
        $this->assertSame(1, $say(null, null));
    }

    public function test_json_toplu_yukleme_gecerlileri_taslak_ekler(): void
    {
        $json = json_encode([
            ...SoruBankasiIceAktarici::sablon(),
            ['soru' => 'Kaynaksız?', 'secenekler' => ['A', 'B', 'C', 'D'], 'dogru' => 'B', 'gerekce' => 'x', 'kaynaklar' => []],
            ['soru' => 'Üç şık?', 'secenekler' => ['A', 'B', 'C'], 'dogru' => 'A', 'gerekce' => 'x', 'kaynaklar' => [['ad' => 'K']]],
        ]);

        $sonuc = Livewire::test(SoruBankasi::class)->instance()->jsonIceAktar($json);

        $this->assertCount(1, $sonuc['kayitlar']);
        $this->assertCount(2, $sonuc['hatalar']);
        $this->assertStringContainsString('2. kayıt', $sonuc['hatalar'][0]);
        $s = SoruBankasiSorusu::firstOrFail();
        $this->assertSame('json', $s->uretim_kaynagi);
        $this->assertSame('taslak', $s->durum);
        $this->assertSame(',41,4321,', $s->nace_onekleri);
        $this->assertSame('insaat', $s->sektor_anahtari);

        $this->assertSame(['Dosya geçerli bir JSON değil: Syntax error'], SoruBankasiIceAktarici::cozumle('{bozuk')['hatalar']);
    }

    public function test_nace_kapsama_tablosu_ve_sayfa(): void
    {
        NaceKodu::create(['kod' => '43.21.01', 'tanim' => 'Elektrik tesisatı', 'tehlike_sinifi' => 'cok_tehlikeli']);
        NaceKodu::create(['kod' => '10.11.01', 'tanim' => 'Et işleme', 'tehlike_sinifi' => 'tehlikeli']);
        foreach (range(1, 5) as $i) {
            $this->soru(['durum' => 'onaylandi', 'soru' => "Ortak {$i}"]);
        }
        foreach (range(1, 15) as $i) {
            $this->soru(['durum' => 'onaylandi', 'nace_onekleri' => ',4321,', 'soru' => "Elektrik {$i}"]);
        }
        $this->soru(['nace_onekleri' => ',10,']);   // taslak

        $lw = Livewire::test(SoruBankasi::class)->assertOk()->assertSee('NACE için soru kapsaması');
        $nk = $lw->instance()->naceKapsama;

        $this->assertSame(1, $nk['yeterli']);
        $satir = collect($nk['satirlar'])->keyBy('kod');
        $this->assertSame('yeterli', $satir['43.21.01']['durum']);
        $this->assertSame('eksik', $satir['10.11.01']['durum']);
        $this->assertSame(1, $satir['10.11.01']['bekleyen']);

        $lw->set('naceArama', 'et işleme');
        $this->assertCount(1, $lw->instance()->naceKapsama['satirlar']);

        $lw->call('jsonSablonu')->assertFileDownloaded('soru-bankasi-sablonu.json');
    }
}
