<?php

namespace Tests\Feature;

use App\Filament\Pages\AiSahaAnalizi as SahaSayfasi;
use App\Models\DofRaporu;
use App\Models\Firma;
use App\Models\IsgProfesyoneli;
use App\Models\SahaAnalizi;
use App\Models\User;
use App\Support\GeminiSahaAnalizi;
use App\Support\SahaAnaliziUretici;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Tests\TestCase;

class AiSahaAnaliziTest extends TestCase
{
    use RefreshDatabase;

    private User $uzman;

    protected function setUp(): void
    {
        parent::setUp();
        $this->uzman = User::factory()->create(['name' => 'Mehmet Uzman']);
        $this->actingAs($this->uzman);
    }

    /** Sayfanın bulgu alan setiyle uyumlu, onaylı bir madde. */
    private function bulgu(array $deger = []): array
    {
        return array_merge([
            'foto_yolu' => null, 'bina_bolge' => null, 'kategori' => null,
            'tespit' => 'Döşeme kenarında korkuluk yok.', 'oneriler_metni' => "Korkuluk kurulmalı.\nTopuk levhası takılmalı.",
            'yasal_gerekce' => 'Yapı İşlerinde İSG Yönetmeliği', 'olasilik' => '6', 'frekans' => '3', 'siddet' => '40',
            'risk_derecesi' => 1, 'durum' => 'onaylandi', 'kaynak' => 'manuel', 'dof_acilacak' => false,
            'dof_sorumlu_tipi' => 'kendim', 'dof_sorumlu' => null, 'dof_termin' => null, 'dof_hedef_skor' => null,
        ], $deger);
    }

    private function geminiYaniti(mixed $veri): void
    {
        config(['services.gemini.key' => 'test-key']);
        Http::fake([
            '*generativelanguage.googleapis.com*' => Http::response([
                'candidates' => [['content' => ['parts' => [['text' => json_encode($veri)]]]]],
            ], 200),
        ]);
    }

    public function test_sayfa_firma_secilmeden_acilir(): void
    {
        Livewire::test(SahaSayfasi::class)
            ->assertSee('Uygunsuzluk Ekle')
            ->assertSee('Rapor Fotoğrafları')
            ->assertSee('Raporu kaydetmek ve PDF almak için');
    }

    public function test_firma_secilince_igu_bilgileri_otomatik_dolar(): void
    {
        $igu = IsgProfesyoneli::factory()->for($this->uzman)->create(['ad_soyad' => 'İGU Ayşe', 'sertifika_no' => 'A-99']);
        $firma = Firma::factory()->for($this->uzman)->create(['igu_id' => $igu->id, 'isveren_ad' => 'Ali Patron']);

        $component = Livewire::test(SahaSayfasi::class)->set('firmaId', $firma->id);

        $this->assertSame('İGU Ayşe', $component->get('gozetimYapan'));
        $this->assertSame('A-99', $component->get('gozetimYapanSertifikaNo'));
        $this->assertSame('Ali Patron', $component->get('isverenVekiliAdi'));
    }

    public function test_yuklenen_fotograf_havuza_alinir_ve_secilir(): void
    {
        Storage::fake('public');

        $component = Livewire::test(SahaSayfasi::class)
            ->set('yeniFotograflar', [UploadedFile::fake()->image('a.jpg'), UploadedFile::fake()->image('b.jpg')]);

        $this->assertCount(2, $component->get('fotograflar'));
        $this->assertFalse($component->get('fotograflar')[0]['analiz_edildi']);
        $this->assertCount(2, $component->get('seciliFotograflar'));
        $this->assertSame([], $component->get('yeniFotograflar'));

        $yol = $component->get('fotograflar')[0]['yol'];
        $component->call('fotoSeciminiDegistir', $yol);
        $this->assertNotContains($yol, $component->get('seciliFotograflar'));
    }

    public function test_ai_api_anahtari_yokken_istek_atilmaz(): void
    {
        config(['services.gemini.key' => null]);
        Storage::fake('public');
        Http::fake();

        Livewire::test(SahaSayfasi::class)
            ->set('yeniFotograflar', [UploadedFile::fake()->image('saha.jpg')])
            ->call('fotograflariAnalizEt')
            ->set('aciklamaMetni', 'Korkuluk yok')
            ->call('aciklamadanEkle');

        Http::assertNothingSent();
        $this->assertFalse(GeminiSahaAnalizi::aktifMi());
    }

    public function test_fotograf_analizi_fine_kinney_ile_bulgu_ekler_ve_taslak_kaydeder(): void
    {
        Storage::fake('public');
        $this->geminiYaniti([[
            'foto_index' => 1, 'bina_bolge' => 'Depo', 'kategori' => 'Düzen/Temizlik',
            'tespit' => 'Hortumlar zeminde dağınık bırakılmış.', 'oneriler' => ['Hortumlar toplanmalı.', 'Kablo köprüsü kullanılmalı.'],
            'yasal_gerekce' => '6331 sayılı Kanun m.4', 'olasilik' => 5, 'frekans' => 3, 'siddet' => 7, 'risk_derecesi' => 3,
        ]]);
        $firma = Firma::factory()->for($this->uzman)->create();

        $component = Livewire::test(SahaSayfasi::class)
            ->set('firmaId', $firma->id)
            ->set('yeniFotograflar', [UploadedFile::fake()->image('saha.jpg')])
            ->call('fotograflariAnalizEt');

        $b = $component->get('bulgular')[0];
        $this->assertSame('Depo', $b['bina_bolge']);
        $this->assertSame('6', $b['olasilik']);   // 5 → ölçekteki en yakın değer
        $this->assertSame('bekliyor', $b['durum']);
        $this->assertSame('foto', $b['kaynak']);
        $this->assertSame(3, $b['risk_derecesi']);   // 6×3×7 = 126 → Önemli Risk
        $this->assertStringContainsString('Hortumlar toplanmalı.', $b['oneriler_metni']);
        $this->assertTrue($component->get('fotograflar')[0]['analiz_edildi']);
        $this->assertSame([], $component->get('seciliFotograflar'));

        $rapor = SahaAnalizi::where('firma_id', $firma->id)->sole();
        $this->assertSame(SahaAnalizi::TASLAK, $rapor->durum);
        $this->assertEquals(126, $rapor->bulgular[0]['skor']);
        $this->assertSame($rapor->id, $component->get('kayitId'));
    }

    public function test_aciklamadan_madde_olusturulur(): void
    {
        $this->geminiYaniti([
            'tespit' => 'Döşeme kenarlarında korkuluk bulunmamaktadır.', 'oneriler' => ['Korkuluk kurulmalı.'],
            'yasal_gerekce' => 'Yapı İşlerinde İSG Yönetmeliği', 'olasilik' => 6, 'frekans' => 6, 'siddet' => 40,
        ]);

        $component = Livewire::test(SahaSayfasi::class)
            ->set('eklemeYontemi', 'aciklama')
            ->set('aciklamaMetni', 'kat kenarında korkuluk yok')
            ->call('aciklamadanEkle');

        $b = $component->get('bulgular')[0];
        $this->assertSame('aciklama', $b['kaynak']);
        $this->assertSame('40', $b['siddet']);
        $this->assertSame(1, $b['risk_derecesi']);   // 1440
        $this->assertNull($component->get('aciklamaMetni'));
    }

    public function test_manuel_madde_tespitsiz_onaylanamaz(): void
    {
        $component = Livewire::test(SahaSayfasi::class)
            ->call('manuelEkle')
            ->call('bulguOnayla', 0);

        $this->assertSame('bekliyor', $component->get('bulgular')[0]['durum']);

        $component->set('bulgular.0.tespit', 'Merdiven boşluğu açık.')->call('bulguOnayla', 0);
        $this->assertSame('onaylandi', $component->get('bulgular')[0]['durum']);

        $component->call('bulguOnayiniKaldir', 0);
        $this->assertSame('bekliyor', $component->get('bulgular')[0]['durum']);
    }

    public function test_iyilestir_maddeyi_yeniden_yazar_dof_isaretini_korur(): void
    {
        $this->geminiYaniti([
            'tespit' => 'İyileştirilmiş tespit metni.', 'oneriler' => ['Yeni önlem.'],
            'yasal_gerekce' => 'Yeni dayanak', 'olasilik' => 3, 'frekans' => 2, 'siddet' => 7,
        ]);

        $component = Livewire::test(SahaSayfasi::class)
            ->set('bulgular', [$this->bulgu(['dof_acilacak' => true, 'bina_bolge' => 'B Blok'])])
            ->call('iyilestirAc', 0)
            ->set('iyilestirNotu', 'daha kısa yaz')
            ->call('bulguIyilestir');

        $b = $component->get('bulgular')[0];
        $this->assertSame('İyileştirilmiş tespit metni.', $b['tespit']);
        $this->assertSame('bekliyor', $b['durum']);   // yeniden onay ister
        $this->assertTrue($b['dof_acilacak']);
        $this->assertSame('B Blok', $b['bina_bolge']);
        $this->assertNull($component->get('iyilestirIndex'));
        Http::assertSent(fn ($r) => str_contains(json_encode($r->data(), JSON_UNESCAPED_UNICODE), 'daha kısa yaz'));
    }

    public function test_onay_bekleyen_madde_varken_tamamlanamaz(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();

        $component = Livewire::test(SahaSayfasi::class)
            ->set('firmaId', $firma->id)
            ->set('bulgular', [$this->bulgu(), $this->bulgu(['durum' => 'bekliyor'])])
            ->call('tamamlaPaneliAc');

        $this->assertFalse($component->get('tamamlaPaneli'));

        $component->call('raporuTamamla');
        $this->assertSame(SahaAnalizi::TASLAK, $component->get('durum'));
        $this->assertDatabaseCount('dof_raporlari', 0);
    }

    public function test_tamamlaninca_isaretli_maddeler_icin_dof_acilir_ve_kilitlenir(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();

        $component = Livewire::test(SahaSayfasi::class)
            ->set('firmaId', $firma->id)
            ->set('bulgular', [
                $this->bulgu(['dof_acilacak' => true, 'bina_bolge' => 'Şantiye']),
                $this->bulgu(['dof_acilacak' => true, 'dof_sorumlu_tipi' => 'dis']),
                $this->bulgu(['tespit' => 'DÖF açılmayacak madde.']),
            ])
            ->call('tamamlaPaneliAc');

        $this->assertTrue($component->get('tamamlaPaneli'));

        // Termin yok → tamamlanmaz
        $component->call('raporuTamamla');
        $this->assertSame(SahaAnalizi::TASLAK, $component->get('durum'));

        // Toplu termin; dış kişide ad hâlâ eksik
        $component->set('topluTermin', '2026-10-20')->call('hepsineUygula');
        $this->assertSame('kendim', $component->get('bulgular')[1]['dof_sorumlu_tipi']);   // toplu "kendim" ezdi

        $component->set('bulgular.1.dof_sorumlu_tipi', 'dis')->call('raporuTamamla');
        $this->assertSame(SahaAnalizi::TASLAK, $component->get('durum'));

        $component->set('bulgular.1.dof_sorumlu', 'Şantiye Şefi Veli')
            ->set('bulgular.0.dof_hedef_skor', '25')
            ->call('raporuTamamla');

        $this->assertSame(SahaAnalizi::TAMAMLANDI, $component->get('durum'));

        $rapor = SahaAnalizi::where('firma_id', $firma->id)->sole();
        $this->assertSame(SahaAnalizi::TAMAMLANDI, $rapor->durum);
        $this->assertNotNull($rapor->tamamlanma_tarihi);

        $dof = DofRaporu::findOrFail($rapor->dof_raporu_id);
        $this->assertCount(2, $dof->maddeler);
        $this->assertStringContainsString('[Şantiye] Döşeme kenarında', $dof->maddeler[0]['tespit']);
        $this->assertStringContainsString($rapor->belge_no, $dof->maddeler[0]['tespit']);
        $this->assertSame('Mehmet Uzman', $dof->maddeler[0]['sorumlu']);
        $this->assertSame('Şantiye Şefi Veli', $dof->maddeler[1]['sorumlu']);
        $this->assertSame('2026-10-20', $dof->maddeler[0]['termin']);
        $this->assertSame('kritik', $dof->maddeler[0]['oncelik']);   // 720
        $this->assertEquals(25, $dof->maddeler[0]['hedef_skor']);
        $this->assertEquals(720, $dof->maddeler[0]['mevcut_skor']);
        $this->assertStringContainsString('Yasal dayanak: Yapı İşlerinde', $dof->maddeler[0]['oneri']);

        // Kilitli: değişiklik yapılamaz
        $component->call('bulguSil', 0)->call('manuelEkle')->call('taslagiIptal');
        $this->assertCount(3, $component->get('bulgular'));
        $this->assertDatabaseHas('saha_analizleri', ['id' => $rapor->id]);
    }

    public function test_dof_isaretsiz_rapor_dofsuz_tamamlanir(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();

        Livewire::test(SahaSayfasi::class)
            ->set('firmaId', $firma->id)
            ->set('bulgular', [$this->bulgu()])
            ->call('raporuTamamla')
            ->assertSet('durum', SahaAnalizi::TAMAMLANDI);

        $this->assertNull(SahaAnalizi::sole()->dof_raporu_id);
        $this->assertDatabaseCount('dof_raporlari', 0);
    }

    public function test_taslak_kaydedilir_acilir_ve_iptal_edilir(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();

        $id = Livewire::test(SahaSayfasi::class)
            ->set('firmaId', $firma->id)
            ->set('alanBolge', 'B Blok')
            ->set('bulgular', [$this->bulgu(['durum' => 'bekliyor', 'foto_yolu' => 'saha-analiz-foto/x.jpg'])])
            ->set('mevzuatReferanslari', [['ad' => '6331 sayılı Kanun', 'url' => 'https://www.mevzuat.gov.tr/x']])
            ->call('taslagiKaydet')
            ->get('kayitId');

        $acilan = Livewire::test(SahaSayfasi::class)->call('raporAc', $id);
        $this->assertSame($firma->id, $acilan->get('firmaId'));
        $this->assertSame('B Blok', $acilan->get('alanBolge'));
        $this->assertSame('bekliyor', $acilan->get('bulgular')[0]['durum']);
        $this->assertSame("Korkuluk kurulmalı.\nTopuk levhası takılmalı.", $acilan->get('bulgular')[0]['oneriler_metni']);
        $this->assertCount(1, $acilan->get('mevzuatReferanslari'));

        $acilan->call('taslagiIptal');
        $this->assertDatabaseMissing('saha_analizleri', ['id' => $id]);
        $this->assertNull($acilan->get('kayitId'));
        $this->assertSame([], $acilan->get('bulgular'));
    }

    public function test_eski_kayit_acilinca_maddeler_onayli_ve_foto_havuzu_kurulur(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();
        $eski = SahaAnalizi::create([
            'firma_id' => $firma->id,
            'durum' => SahaAnalizi::TAMAMLANDI,
            'bulgular' => [['foto_yolu' => 'saha-analiz-foto/e.jpg', 'tespit' => 'Eski', 'oneriler' => ['Ö'], 'risk_derecesi' => 2]],
        ]);

        $c = Livewire::test(SahaSayfasi::class)->call('raporAc', $eski->id);

        $this->assertSame('onaylandi', $c->get('bulgular')[0]['durum']);
        $this->assertSame([['yol' => 'saha-analiz-foto/e.jpg', 'analiz_edildi' => true]], $c->get('fotograflar'));
        $this->assertTrue($c->instance()->kilitliMi());
    }

    public function test_baska_uzmanin_raporu_acilamaz(): void
    {
        $baskaFirma = Firma::factory()->for(User::factory()->create())->create();
        $rapor = SahaAnalizi::create(['firma_id' => $baskaFirma->id, 'bulgular' => [['tespit' => 'Gizli']]]);

        Livewire::test(SahaSayfasi::class)->call('raporAc', $rapor->id)->assertSet('kayitId', null)->assertSet('bulgular', []);
    }

    public function test_mevzuat_referanslari(): void
    {
        $component = Livewire::test(SahaSayfasi::class)
            ->set('yeniRefAd', 'Yapı İşleri Yönetmeliği')
            ->set('yeniRefUrl', 'gecersiz-adres')
            ->call('referansEkle');
        $this->assertSame([], $component->get('mevzuatReferanslari'));

        $component->set('yeniRefUrl', 'https://www.mevzuat.gov.tr/ornek')->call('referansEkle');
        $this->assertSame([['ad' => 'Yapı İşleri Yönetmeliği', 'url' => 'https://www.mevzuat.gov.tr/ornek']], $component->get('mevzuatReferanslari'));

        $component->call('hazirReferansEkle', 'KKD')->call('hazirReferansEkle', 'KKD');
        $this->assertCount(2, $component->get('mevzuatReferanslari'));
        $this->assertNotNull($component->get('mevzuatReferanslari')[1]['url']);

        $component->set('bulgular', [$this->bulgu(['yasal_gerekce' => 'yapı işleri yönetmeliği']), $this->bulgu(['yasal_gerekce' => '6331 m.4'])])
            ->call('bulgulardanReferansTopla');
        $this->assertSame(['Yapı İşleri Yönetmeliği', config('isg.uzman_raporu.dayanaklar.KKD.ad'), '6331 m.4'], array_column($component->get('mevzuatReferanslari'), 'ad'));

        $component->call('referansSil', 0);
        $this->assertCount(2, $component->get('mevzuatReferanslari'));
    }

    public function test_pdf_aksiyonu_taslagi_kaydeder_ve_kase_snapshotlanir(): void
    {
        $igu = IsgProfesyoneli::factory()->for($this->uzman)->create(['kase_gorseli' => 'isg-profesyonel-kase/x.png']);
        $firma = Firma::factory()->for($this->uzman)->create(['igu_id' => $igu->id]);

        Livewire::test(SahaSayfasi::class)
            ->set('firmaId', $firma->id)
            ->set('bulgular', [$this->bulgu(['bina_bolge' => 'Depo']), $this->bulgu(['durum' => 'bekliyor'])])
            ->callAction('pdf', ['bicim' => 'gozlem', 'imzali' => '1']);

        $rapor = SahaAnalizi::where('firma_id', $firma->id)->sole();
        $this->assertCount(2, $rapor->bulgular);
        $this->assertSame(SahaAnalizi::TASLAK, $rapor->durum);
        $this->assertSame(['Korkuluk kurulmalı.', 'Topuk levhası takılmalı.'], $rapor->bulgular[0]['oneriler']);
        $this->assertSame('isg-profesyonel-kase/x.png', $rapor->gozetim_yapan_kase);
        $this->assertStringStartsWith('SAHA-'.now()->year.'-', $rapor->belge_no);
    }

    public function test_pdf_iki_bicimde_uretilir(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();
        $rapor = SahaAnalizi::create([
            'firma_id' => $firma->id,
            'bulgular' => [
                ['bina_bolge' => 'Depo', 'tespit' => 'Test', 'oneriler' => ['Öneri'], 'yasal_gerekce' => 'm.4', 'olasilik' => '6', 'frekans' => '6', 'siddet' => '40', 'skor' => 1440, 'risk_derecesi' => 1],
                ['tespit' => 'Eski biçim madde', 'oneriler' => [], 'risk_derecesi' => 3],
            ],
            'mevzuat_referanslari' => [['ad' => '6331', 'url' => 'https://www.mevzuat.gov.tr/x'], ['ad' => 'Bağlantısız', 'url' => null]],
        ]);

        foreach (['gozlem', 'gozetim'] as $bicim) {
            foreach ([true, false] as $imzali) {
                $yanit = SahaAnaliziUretici::pdf($rapor, $bicim, $imzali);
                $this->assertInstanceOf(StreamedResponse::class, $yanit);
                ob_start();
                $yanit->sendContent();
                $this->assertStringStartsWith('%PDF', ob_get_clean());
            }
        }

        $this->assertNotNull(SahaAnaliziUretici::referanslar($rapor)[0]['qr']);
        $this->assertNull(SahaAnaliziUretici::referanslar($rapor)[1]['qr']);
    }

    public function test_fine_kinney_yardimcilari(): void
    {
        $this->assertEquals(1440, SahaAnalizi::fineKinneySkoru(['olasilik' => '6', 'frekans' => '6', 'siddet' => '40']));
        $this->assertNull(SahaAnalizi::fineKinneySkoru(['olasilik' => '6', 'frekans' => null, 'siddet' => '40']));
        $this->assertSame('Çok Yüksek Risk', SahaAnalizi::fineKinneyBandi(1440)['ad']);
        $this->assertSame(4, SahaAnalizi::skordanDerece(18));
        $this->assertSame(3, SahaAnalizi::skordanDerece(null));
        $this->assertSame('0.5', SahaAnalizi::olcegeYuvarla('olasilik', 0.4));
        $this->assertSame('Kuvvetle muhtemel', SahaAnalizi::olcekEtiketi('olasilik', 6));
    }

    public function test_gecmis_kayit_silinir(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();
        $rapor = SahaAnalizi::create(['firma_id' => $firma->id, 'bulgular' => []]);

        Livewire::test(SahaSayfasi::class)
            ->set('firmaId', $firma->id)
            ->call('gecmisSil', $rapor->id);

        $this->assertDatabaseMissing('saha_analizleri', ['id' => $rapor->id]);
    }

    public function test_baska_uzmanin_firmasi_secilemez(): void
    {
        $baskaUzman = User::factory()->create();
        $baskaFirma = Firma::factory()->for($baskaUzman)->create();

        $firmalar = Livewire::test(SahaSayfasi::class)->instance()->firmalar();

        $this->assertArrayNotHasKey($baskaFirma->id, $firmalar);
    }
}
