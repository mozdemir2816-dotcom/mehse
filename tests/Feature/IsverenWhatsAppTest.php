<?php

namespace Tests\Feature;

use App\Filament\Pages\DofOlustur;
use App\Filament\Pages\HizliSahaBulgusu;
use App\Filament\Pages\SahaDenetimi as SahaSayfasi;
use App\Filament\Pages\TespitOneriDefteri as DefterSayfasi;
use App\Filament\Pages\ZiyaretModu;
use App\Models\AcilDurumPlani;
use App\Models\DofRaporu;
use App\Models\Firma;
use App\Models\IsgProfesyoneli;
use App\Models\SahaBulgusu;
use App\Models\SahaDenetimi;
use App\Models\TespitOneriDefteri;
use App\Models\User;
use App\Support\IsverenWhatsApp;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * 02.11.2026: Saha Denetimi / DÖF / Tespit-Öneri Defteri hızlandırma + işverene WhatsApp ile rapor
 * (imzalı 30 günlük PDF bağlantısı) + Çoklu DÖF PDF künye / imza / tablo varsayılanları.
 */
class IsverenWhatsAppTest extends TestCase
{
    use RefreshDatabase;

    private User $uzman;

    private Firma $firma;

    protected function setUp(): void
    {
        parent::setUp();
        $this->uzman = User::factory()->create(['name' => 'Mehmet ÖZDEMİR', 'unvan' => 'a_sinifi']);
        $this->actingAs($this->uzman);
        $igu = IsgProfesyoneli::create(['user_id' => $this->uzman->id, 'tip' => 'igu', 'ad_soyad' => 'Mehmet ÖZDEMİR', 'unvan' => 'A SINIFI İŞ GÜVENLİĞİ UZMANI', 'sertifika_no' => '405044']);
        $this->firma = Firma::factory()->for($this->uzman)->create([
            'unvan' => 'FOM MAKİNA', 'isveren_ad' => 'Yakup MERAL', 'isveren_vekili' => null,
            'telefon' => '0224 111 22 33', 'igu_id' => $igu->id,
        ]);
    }

    private function cepTelefonuGir(): void
    {
        AcilDurumPlani::create(['firma_id' => $this->firma->id, 'uygulama' => ['isveren_tel' => '0532 111 22 33']]);
        $this->firma->unsetRelation('acilDurumPlani');
    }

    public function test_telefon_sabit_hatti_atlar_cep_numarasini_bulur(): void
    {
        $this->assertNull(IsverenWhatsApp::telefon($this->firma));   // yalnız sabit hat → yok

        $this->cepTelefonuGir();
        $this->assertSame('905321112233', IsverenWhatsApp::telefon($this->firma->fresh()));
        $this->assertSame('Mehmet ÖZDEMİR - A Sınıfı İSG Uzmanı', IsverenWhatsApp::imza($this->firma));
    }

    public function test_dof_mesaji_imzali_baglanti_ve_pdf_varsayilanlari(): void
    {
        $this->cepTelefonuGir();
        $r = DofRaporu::create([
            'firma_id' => $this->firma->id, 'rapor_tarihi' => '2026-11-02', 'gozetim_yapan_kase' => null,
            'sorumlu_kisi' => 'İşveren / İşveren Vekili',
            'maddeler' => [
                ['tespit' => 'Korkuluk eksik', 'oncelik' => 'kritik', 'oneri' => 'Korkuluk takılmalı', 'durum' => 'acik'],
                ['tespit' => 'Pano kapağı açık', 'oncelik' => 'yuksek', 'oneri' => 'Kapak kapatılmalı', 'durum' => 'acik', 'sorumlu' => 'Bakım'],
            ],
        ]);

        $mesaj = IsverenWhatsApp::dofMesaji($r);
        $this->assertStringContainsString('Sayın Yakup MERAL (FOM MAKİNA),', $mesaj);
        $this->assertStringContainsString('02.11.2026 tarihli ve '.$r->belge_no.' numaralı', $mesaj);
        $this->assertStringContainsString('- Tespit Edilen Madde: 2 adet', $mesaj);
        $this->assertStringContainsString('Mehmet ÖZDEMİR - A Sınıfı İSG Uzmanı', $mesaj);

        // İmzalı bağlantı: geçerliyse PDF, değiştirilmişse / imzasızsa 403
        preg_match('#https?://\S+/paylasim/dof/'.$r->id.'\S+#', $mesaj, $e);
        $this->assertNotEmpty($e);
        auth()->logout();
        $this->get($e[0])->assertOk();
        $this->get(str_replace('/dof/'.$r->id, '/dof/'.($r->id + 1), $e[0]))->assertForbidden();
        $this->get('/paylasim/dof/'.$r->id)->assertForbidden();
        $this->actingAs($this->uzman);

        // PDF künye / imza / tablo varsayılanları
        $html = view('pdf.dof-raporu', ['imzali' => true, 'rapor' => $r->fresh(), 'firma' => $this->firma])->render();
        foreach (['Mehmet ÖZDEMİR (A Sınıfı İGU)', 'Yakup MERAL', 'A Sınıfı İş Güvenliği Uzmanı', 'Belge No: 405044',
            'Şantiye / İşletme Geneli', 'Derhal', '05.11.2026', 'İşveren / Saha Sorumlusu', 'Görsel yok'] as $parca) {
            $this->assertStringContainsString($parca, $html, $parca);
        }

        // Sayfa: getWhatsappShareUrl + düğme
        $sayfa = Livewire::test(DofOlustur::class)->set('firmaId', $this->firma->id);
        $url = $sayfa->instance()->getWhatsappShareUrl($r->id);
        $this->assertStringStartsWith('https://api.whatsapp.com/send?phone=905321112233&text=', $url);
        $sayfa->call('gecmisWhatsapp', $r->id)->assertNotified('DÖF raporu WhatsApp\'ta hazırlandı');
    }

    public function test_telefon_yoksa_uyari_verilir(): void
    {
        $r = DofRaporu::create(['firma_id' => $this->firma->id, 'maddeler' => [['tespit' => 'X', 'oncelik' => 'orta']]]);

        Livewire::test(DofOlustur::class)->set('firmaId', $this->firma->id)
            ->call('gecmisWhatsapp', $r->id)
            ->assertNotified('Firma yetkili telefonu tanımlı değil');
    }

    public function test_saha_denetimi_tumunu_uygun_isaretle_secimleri_korur_ve_whatsapp(): void
    {
        $this->cepTelefonuGir();
        $sayfa = Livewire::test(SahaSayfasi::class)->set('firmaId', $this->firma->id)
            ->assertSee('Mehmet ÖZDEMİR')->assertSee('Tümünü Uygun İşaretle')
            ->call('cevapVer', '1.1', 'uygun_degil')
            ->call('cevapVer', '1.2', 'uygulanamaz');
        $a1 = $sayfa->instance()->anahtar('1.1');
        $sayfa->set('cevaplar.'.$a1.'.aciklama', 'Korkuluk eksik')->call('tumunuUygunIsaretle');

        $c = collect($sayfa->get('cevaplar'));
        $this->assertSame('uygun_degil', $c[$a1]['sonuc']);
        $this->assertSame('uygulanamaz', $c[$sayfa->instance()->anahtar('1.2')]['sonuc']);
        $gorunen = collect($sayfa->instance()->kategoriler())->flatMap(fn ($k) => collect($k['maddeler'])->pluck('kod'));
        $this->assertGreaterThan(30, $gorunen->count());
        $this->assertSame(0, $gorunen->filter(fn ($kod) => blank($c[$sayfa->instance()->anahtar($kod)]['sonuc'] ?? null))->count());

        $sayfa->callAction('whatsapp')->assertNotified('Denetim raporu WhatsApp\'ta hazırlandı');
        $d = SahaDenetimi::where('firma_id', $this->firma->id)->where('durum', 'tamamlandi')->firstOrFail();
        $this->assertStringContainsString('- Tespit edilen uygunsuzluk: 1 adet', IsverenWhatsApp::sahaDenetimiMesaji($d));
        $this->assertSame(1, SahaBulgusu::where('firma_id', $this->firma->id)->count());   // ortak havuz
    }

    public function test_defter_bugunku_bulgulari_resmi_dille_bir_kez_ceker(): void
    {
        $this->cepTelefonuGir();
        SahaBulgusu::create(['firma_id' => $this->firma->id, 'uygunsuzluk' => 'Korkuluk eksik', 'aksiyon' => 'Korkuluk takılmalıdır']);
        $eski = SahaBulgusu::create(['firma_id' => $this->firma->id, 'uygunsuzluk' => 'Eski bulgu']);
        $eski->forceFill(['created_at' => now()->subDays(3)])->saveQuietly();

        $sayfa = Livewire::test(DefterSayfasi::class)->set('firmaId', $this->firma->id)
            ->callAction('bugununBulgulari')->assertNotified('1 bugünkü bulgu deftere eklendi');

        $maddeler = TespitOneriDefteri::where('firma_id', $this->firma->id)->firstOrFail()->maddeler;
        $this->assertCount(1, $maddeler);
        $this->assertSame('Yapılan saha incelemesinde; Korkuluk eksik durumu tespit edilmiştir.', $maddeler[0]['tespit']);
        $this->assertSame('Korkuluk takılmalıdır.', $maddeler[0]['oneri']);

        $sayfa->callAction('bugununBulgulari')->assertNotified('Bugün deftere eklenecek yeni saha bulgusu yok');
        $sayfa->callAction('whatsapp')->assertNotified('Defter sayfası WhatsApp\'ta hazırlandı');
    }

    public function test_acik_bulguya_whatsapp_hatirlatmasi_ve_ziyaret_ozeti_imzasi(): void
    {
        $this->cepTelefonuGir();
        $b = SahaBulgusu::create(['firma_id' => $this->firma->id, 'uygunsuzluk' => 'Yangın tüpü dolum tarihi geçmiş', 'termin' => now()->subDays(2)]);

        $this->assertStringContainsString('termin 2 gün geçti', IsverenWhatsApp::bulguHatirlatmasi($b));
        Livewire::test(HizliSahaBulgusu::class)->call('whatsappHatirlat', $b->id)->assertNotified('Hatırlatma WhatsApp\'ta hazırlandı');

        $z = Livewire::test(ZiyaretModu::class)->set('firmaId', $this->firma->id);
        $this->assertSame('905321112233', $z->instance()->isverenWhatsappNo());
        $this->assertStringNotContainsString('ÖZDEMİR', $z->instance()->ozetMetni());   // genel özet isimsiz (04.10.2026 kararı)
        $this->assertStringEndsWith('Mehmet ÖZDEMİR - A Sınıfı İSG Uzmanı', $z->instance()->isverenImzasi());   // yalnız işverene gönderimde
    }

    /** 02.11.2026: Ziyaret Modu tamamlandı durumu, DÖF manuel giriş daraltma, defter önizleme varsayılan önerisi. */
    public function test_ziyaret_tamamlandi_durumu_dof_manuel_giris_ve_defter_onizleme(): void
    {
        $z = Livewire::test(ZiyaretModu::class)->set('firmaId', $this->firma->id)
            ->assertDontSee('Ziyaret Raporunu WhatsApp ile Gönder');
        $this->assertFalse($z->instance()->ziyaretTamamlandiMi());

        $z->call('ziyaretiBitir')->assertSee('Bugünkü ziyaret tamamlandı')->assertSee('Ziyaret Raporunu WhatsApp ile Gönder')->assertSee('Ziyaret Raporu (PDF İndir)');
        $this->assertTrue($z->instance()->ziyaretTamamlandiMi());
        ob_start();
        $z->instance()->ozetPdf()->sendContent();
        $this->assertStringStartsWith('%PDF', ob_get_clean());

        Livewire::test(DofOlustur::class)->set('firmaId', $this->firma->id)->assertSee('Manuel Madde Girişi')->assertSee('Opsiyonel');

        $d = TespitOneriDefteri::firmaIcin($this->firma);
        $d->update(['maddeler' => [['tespit' => 'Korkuluk eksik', 'oneri' => '', 'oncelik' => 'orta']]]);
        Livewire::test(DefterSayfasi::class)->set('firmaId', $this->firma->id)->assertSee(TespitOneriDefteri::VARSAYILAN_ONERI);
        $this->assertStringContainsString(TespitOneriDefteri::VARSAYILAN_ONERI, view('pdf.tespit-oneri-defteri', ['defter' => $d->fresh(), 'firma' => $this->firma])->render());
    }

    /** 02.11.2026: risk raporu WhatsApp (imzalı bağlantı), PDF kapak / ekip / onay noktalı yer tutucu, termin. */
    public function test_risk_raporu_whatsapp_ve_pdf_ekip_yer_tutuculari(): void
    {
        $this->cepTelefonuGir();
        $this->firma->update(['tehlike_sinifi' => 'cok_tehlikeli']);
        $rd = \App\Models\RiskDegerlendirmesi::create(['firma_id' => $this->firma->id, 'yontem' => 'fine_kinney', 'rapor_tarihi' => '2026-11-02']);
        $rd->maddeler()->create(['sira' => 1, 'tehlike' => 'Ezilme', 'olasilik' => 6, 'frekans' => 6, 'siddet' => 40, 'durum' => 'acik']);
        $rd->refresh();

        $mesaj = IsverenWhatsApp::riskMesaji($rd);
        $this->assertStringContainsString('Sayın Yakup MERAL (FOM MAKİNA),', $mesaj);
        $this->assertStringContainsString('02.11.2026 tarihli ve '.$rd->belge_no.' numaralı 6331 sayılı Kanun', $mesaj);
        $this->assertStringContainsString('- İncelenen Risk Sayısı: 1', $mesaj);
        $this->assertStringContainsString('- Geçerlilik: 02.11.2028 tarihine kadar (Çok Tehlikeli - 2 Yıl)', $mesaj);
        $this->assertStringEndsWith('Mehmet ÖZDEMİR - A Sınıfı İSG Uzmanı', $mesaj);

        preg_match('#https?://\S+/paylasim/risk/'.$rd->id.'\S+#', $mesaj, $e);
        $this->assertNotEmpty($e);
        auth()->logout();
        $this->get($e[0])->assertOk();
        $this->get('/paylasim/risk/'.$rd->id)->assertForbidden();
        $this->actingAs($this->uzman);

        Livewire::test(\App\Filament\Resources\RiskDegerlendirmesis\Pages\EditRiskDegerlendirmesi::class, ['record' => $rd->getRouteKey()])
            ->callAction('whatsapp')->assertNotified("Risk değerlendirmesi WhatsApp'ta hazırlandı");

        $veri = \App\Support\RiskDegerlendirmesiUretici::veri($rd->fresh());
        $dikey = view('pdf.risk-degerlendirmesi', $veri + ['bolum' => 'dikey'])->render();
        $yatay = view('pdf.risk-degerlendirmesi', $veri + ['bolum' => 'yatay'])->render();
        $this->assertStringContainsString('FOM MAKİNA', $dikey);   // boş proje adı → unvan
        $this->assertStringContainsString('........................................', $dikey);   // kapak: temsilci / destek
        $this->assertStringContainsString('........................................', $yatay);   // ekip tablosu + onay
        $this->assertStringContainsString('<td>Derhal</td>', $yatay);   // ÇY → Derhal
    }
}
