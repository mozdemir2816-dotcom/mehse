<?php

namespace Tests\Feature;

use App\Filament\Pages\AiSahaAnalizi;
use App\Filament\Pages\DokumanYonetimi;
use App\Filament\Pages\ZiyaretModu;
use App\Models\ArsivDosya;
use App\Models\DofRaporu;
use App\Models\Firma;
use App\Models\SahaAnalizi;
use App\Models\SahaBulgusu;
use App\Models\User;
use App\Models\YillikPlan;
use App\Models\ZiyaretProgrami;
use App\Support\KullaniciAyarlari;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Telefon / saha düzeni (04.10.2026): Ziyaret Modu, tek dokunuşla arşiv
 * yükleme, alt menü, Saha Gözlem PDF paylaşım adresi.
 */
class ZiyaretModuTest extends TestCase
{
    use RefreshDatabase;

    private User $uzman;

    private Firma $firma;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        Carbon::setTestNow('2026-10-04 09:00:00');
        KullaniciAyarlari::onbellegiTemizle();
        $this->uzman = User::factory()->create(['name' => 'Mehmet Uzman']);
        $this->actingAs($this->uzman);
        $this->firma = Firma::factory()->for($this->uzman)->create(['unvan' => 'Ahmet Yapı', 'tehlike_sinifi' => 'cok_tehlikeli', 'calisan_sayisi' => 20, 'aktif' => true]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function ziyaretPlanla(Firma $firma, string $tarih = '2026-10-04'): void
    {
        $p = ZiyaretProgrami::firmaYilIcin($firma, 2026);
        $aylar = $p->ziyaretler;
        $aylar[9] = [['tarih' => $tarih, 'amac' => 'Aylık ziyaret', 'durum' => 'planlandi', 'sure_saat' => 2, 'notlar' => null]];
        $p->update(['ziyaretler' => $aylar]);
    }

    public function test_bugun_planli_tek_firma_kendiliginden_acilir_secim_oturuma_yazilir(): void
    {
        $this->ziyaretPlanla($this->firma);

        Livewire::test(ZiyaretModu::class)
            ->assertSet('firmaId', $this->firma->id)
            ->assertSee('Ahmet Yapı')
            ->assertSee('Aylık ziyaret')
            ->assertSee('Ziyareti Bitir');

        $this->assertSame($this->firma->id, session('ziyaret_firma'));
    }

    public function test_birden_cok_planli_firma_listelenir_ve_secilir(): void
    {
        $ikinci = Firma::factory()->for($this->uzman)->create(['unvan' => 'Beta Metal', 'aktif' => true]);
        $this->ziyaretPlanla($this->firma);
        $this->ziyaretPlanla($ikinci);
        session()->forget('ziyaret_firma');

        Livewire::test(ZiyaretModu::class)
            ->assertSet('firmaId', null)
            ->assertSee('Bugün planlı ziyaretler')
            ->assertSee('Beta Metal')
            ->call('firmaSec', $ikinci->id)
            ->assertSet('firmaId', $ikinci->id)
            ->assertSee('Bu ziyarette yapılacaklar');
    }

    public function test_bu_ayin_plan_maddeleri_gerceklesti_isaretlenir(): void
    {
        YillikPlan::create(['firma_id' => $this->firma->id, 'yil' => 2026, 'egitimler' => [], 'faaliyetler' => [
            ['faaliyet' => 'Yangın tüpü kontrolü', 'ana_konu' => 'ACİL DURUM', 'aylar' => [9 => 'planlandi']],
            ['faaliyet' => 'Mart işi', 'aylar' => [2 => 'planlandi']],
        ]]);

        $sayfa = Livewire::test(ZiyaretModu::class, ['firmaId' => $this->firma->id])
            ->assertSee('Yangın tüpü kontrolü')
            ->assertDontSee('Mart işi')
            ->assertSee('0/1')
            ->call('planMaddesiIsaretle', 'faaliyetler', 0)
            ->assertSee('1/1');

        $this->assertSame('tamamlandi', YillikPlan::sole()->faaliyetler[0]['aylar'][9]);
        $this->assertStringContainsString('Yıllık plan: Yangın tüpü kontrolü', $sayfa->instance()->ozetMetni());
    }

    public function test_eksik_arsiv_evragi_tek_dokunusla_cok_sayfa_yuklenir(): void
    {
        $sayfa = Livewire::test(ZiyaretModu::class, ['firmaId' => $this->firma->id])
            ->assertSee('İş Güvenliği Sözleşmesi')
            ->assertSee('📷 Çek');

        $sayfa->set('hizliYeni.igu_sozlesmesi', UploadedFile::fake()->image('s1.jpg', 300, 420))
            ->assertSee('1 sayfa')
            ->set('hizliYeni.igu_sozlesmesi', UploadedFile::fake()->image('s2.jpg', 300, 420))
            ->assertSee('2 sayfa');
        $this->assertDatabaseCount('arsiv_dosyalari', 0);

        $sayfa->call('hizliKaydet', 'igu_sozlesmesi');

        $d = ArsivDosya::sole();
        $this->assertSame('igu_sozlesmesi', $d->kategori);
        $this->assertSame('hizli', $d->kaynak);
        $this->assertSame('2026-10-04', $d->baslangic_tarihi->toDateString());
        $this->assertStringStartsWith('%PDF', Storage::disk('public')->get($d->dosya_yolu));
        $this->assertSame([], $sayfa->get('hizliSayfalar'));
        $this->assertSame('tamam', $sayfa->instance()->arsivDurumu['igu_sozlesmesi']['durum']);

        // Yıllık belge: yıl beklenen dönemden gelir; PDF tek dosya özgün adıyla.
        $sayfa->set('hizliYeni.yillik_calisma_plani', UploadedFile::fake()->create('plan-imzali.pdf', 40, 'application/pdf'))
            ->call('hizliKaydet', 'yillik_calisma_plani');
        $plan = ArsivDosya::where('kategori', 'yillik_calisma_plani')->sole();
        $this->assertSame(2026, $plan->yil);
        $this->assertSame('plan-imzali.pdf', $plan->dosya_adi);
        $this->assertSame('2026 Çalışma Planı', $plan->baslik);

        // Desteklenmeyen dosya tepsiye girmez; iptal geçici dosyayı siler.
        $sayfa->set('hizliYeni.risk_degerlendirmesi', UploadedFile::fake()->create('virus.exe', 5))
            ->assertSet('hizliSayfalar', [])
            ->set('hizliYeni.risk_degerlendirmesi', UploadedFile::fake()->image('r.jpg'))
            ->call('hizliIptal', 'risk_degerlendirmesi')
            ->assertSet('hizliSayfalar', []);
        $this->assertSame([], Storage::disk('public')->files('arsiv/gecici'));
    }

    public function test_arsiv_kontrol_listesinde_de_tek_dokunus_yukleme(): void
    {
        Livewire::test(DokumanYonetimi::class)->set('firmaId', $this->firma->id)
            ->assertSee('📷 Çek')
            ->assertSee('Formla yükle')
            ->set('hizliYeni.saha_gozlem', UploadedFile::fake()->image('rapor.jpg'))
            ->call('hizliKaydet', 'saha_gozlem')
            ->assertSee('1 / 8 güncel');

        $this->assertSame('saha_gozlem', ArsivDosya::sole()->kategori);
    }

    public function test_acik_bulgu_ve_dof_yerinde_fotografla_kapatilir(): void
    {
        $b = SahaBulgusu::create(['firma_id' => $this->firma->id, 'uygunsuzluk' => 'Merdiven korkuluğu eksik', 'olasilik' => 4, 'siddet' => 4, 'termin' => '2026-09-30', 'fotograflar' => ['saha-bulgu/once.jpg']]);
        $kapali = SahaBulgusu::create(['firma_id' => $this->firma->id, 'uygunsuzluk' => 'Eski kapalı', 'durum' => 'kapandi']);
        $dof = DofRaporu::create(['firma_id' => $this->firma->id, 'maddeler' => [
            ['tespit' => 'Yangın tüpü dolum tarihi geçmiş', 'durum' => 'acik', 'termin' => '2026-10-10', 'sorumlu' => 'Bakım'],
            ['tespit' => 'Zaten kapalı madde', 'durum' => 'tamamlandi'],
        ]]);

        $sayfa = Livewire::test(ZiyaretModu::class, ['firmaId' => $this->firma->id])
            ->assertSee('Açık uygunsuzluklar (2)')
            ->assertSee('Merdiven korkuluğu eksik')
            ->assertSee('Yangın tüpü dolum tarihi geçmiş')
            ->assertDontSee('Eski kapalı')
            ->assertDontSee('Zaten kapalı madde');

        $sayfa->call('kapanisAc', 'b'.$b->id)
            ->set('kapanisNot.b'.$b->id, 'Korkuluk takıldı')
            ->set('kapanisFoto.b'.$b->id, UploadedFile::fake()->image('sonra.jpg'))
            ->call('bulguKapat', $b->id);

        $b->refresh();
        $this->assertSame('kapandi', $b->durum);
        $this->assertSame('2026-10-04', $b->kapanis_tarihi->toDateString());
        $this->assertSame('Korkuluk takıldı', $b->kapanis_notu);
        $this->assertCount(2, $b->fotograflar);

        $sayfa->call('dofKapat', $dof->id, 0);
        $m = $dof->fresh()->maddeler[0];
        $this->assertSame('tamamlandi', $m['durum']);
        $this->assertSame('2026-10-04', $m['kapatma_tarihi']);
        $this->assertStringContainsString('yerinde kontrol', $m['kapatma_notu']);

        $sayfa->assertSee('Açık uygunsuzluk yok');
        $this->assertStringContainsString('Giderilen uygunsuzluk: 2', $sayfa->instance()->ozetMetni());
        $this->assertSame('kapandi', $kapali->fresh()->durum);
    }

    public function test_ziyareti_bitir_programa_isler_ve_ozet_hazirlar(): void
    {
        SahaAnalizi::create(['firma_id' => $this->firma->id, 'bulgular' => []]);   // bugün saha gözlemi

        $sayfa = Livewire::test(ZiyaretModu::class, ['firmaId' => $this->firma->id])
            ->call('ziyaretiBitir')
            ->assertSet('ozetAcik', true)
            ->assertSee('Ziyaret özeti')
            ->assertSee('WhatsApp / Paylaş');

        $girdi = ZiyaretProgrami::sole()->ziyaretler[9][0];
        $this->assertSame('2026-10-04', $girdi['tarih']);
        $this->assertSame('tamamlandi', $girdi['durum']);
        $this->assertSame('Saha ziyareti', $girdi['amac']);

        $ozet = $sayfa->instance()->ozetMetni();
        $this->assertStringContainsString('*Ahmet Yapı*', $ozet);
        $this->assertStringContainsString('04.10.2026', $ozet);
        $this->assertStringContainsString('Saha gözlem raporu: 1', $ozet);
        $this->assertStringContainsString('Eksik evrak: İş Güvenliği Sözleşmesi', $ozet);
        $this->assertStringNotContainsString('Mehmet Uzman', $ozet);   // görevli adı yok

        // Planlı ziyaret varsa aynı satır tamamlanır, yeni satır açılmaz.
        $this->ziyaretPlanla($this->firma);
        $sayfa->call('ziyaretiBitir');
        $ay = ZiyaretProgrami::sole()->ziyaretler[9];
        $this->assertCount(1, $ay);
        $this->assertSame('Aylık ziyaret', $ay[0]['amac']);
        $this->assertSame('tamamlandi', $ay[0]['durum']);
    }

    public function test_baskasinin_firmasi_ve_kayitlari_kullanilamaz(): void
    {
        $yabanci = Firma::factory()->for(User::factory())->create(['aktif' => true]);
        $b = SahaBulgusu::create(['firma_id' => $yabanci->id, 'uygunsuzluk' => 'Gizli']);

        Livewire::test(ZiyaretModu::class, ['firmaId' => $yabanci->id])
            ->assertSet('firmaId', null)
            ->call('firmaSec', $yabanci->id)
            ->assertSet('firmaId', null)
            ->call('bulguKapat', $b->id)
            ->call('hizliKaydet', 'igu_sozlesmesi');

        $this->assertSame('acik', $b->fresh()->durum);
        $this->assertDatabaseCount('arsiv_dosyalari', 0);
    }

    public function test_alt_menu_saha_duzeninde_ve_secili_firmayla(): void
    {
        session(['ziyaret_firma' => $this->firma->id]);
        $html = $this->get(ZiyaretModu::getUrl())->assertOk()->getContent();

        foreach (['Bugün', 'Saha Gözlem', 'Bulgu', 'Arşiv', 'Firmalar', 'fi-mobil-alt-nav-merkez'] as $metin) {
            $this->assertStringContainsString($metin, $html);
        }
        $this->assertStringContainsString('hizli-saha-bulgusu?firma='.$this->firma->id, $html);
        $this->assertStringContainsString('dokuman-yonetimi?firma='.$this->firma->id, $html);
    }

    public function test_saha_gozlem_pdf_adresi_yalniz_sahibine(): void
    {
        $rapor = SahaAnalizi::create(['firma_id' => $this->firma->id, 'bulgular' => [['tespit' => 'Test', 'oneriler' => [], 'risk_derecesi' => 3]]]);

        $yanit = $this->get(route('mehse.saha-gozlem.pdf', $rapor->id))->assertOk();
        ob_start();
        $yanit->baseResponse->sendContent();
        $this->assertStringStartsWith('%PDF', ob_get_clean());

        $this->actingAs(User::factory()->create())->get(route('mehse.saha-gozlem.pdf', $rapor->id))->assertNotFound();
        auth()->logout();
        $this->get(route('mehse.saha-gozlem.pdf', $rapor->id))->assertRedirect();

        // Kayıtlı raporda telefon paylaşım düğmesi görünür.
        $this->actingAs($this->uzman);
        Livewire::test(AiSahaAnalizi::class)->call('raporAc', $rapor->id)
            ->assertSee('Taslak PDF’i Paylaş')
            ->assertSee('mehse-sabit-alt');
    }
}
