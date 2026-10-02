<?php

namespace Tests\Feature;

use App\Filament\Pages\TaseronYonetimi;
use App\Models\Firma;
use App\Models\IsIzinFormu;
use App\Models\Taseron;
use App\Models\TaseronCalisani;
use App\Models\User;
use App\Support\TaseronUretici;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class TaseronYonetimiTest extends TestCase
{
    use RefreshDatabase;

    private User $uzman;

    private Firma $firma;

    protected function setUp(): void
    {
        parent::setUp();
        $this->uzman = User::factory()->create();
        $this->actingAs($this->uzman);
        $this->firma = Firma::factory()->for($this->uzman)->create(['tehlike_sinifi' => 'cok_tehlikeli']);
    }

    private function taseron(array $ek = []): Taseron
    {
        return Taseron::create([
            'firma_id' => $this->firma->id, 'tur' => 'alt_isveren', 'unvan' => 'Yıldız Elektrik',
            'sozlesme_bitis' => now()->addYear(), ...$ek,
        ]);
    }

    public function test_yeni_alt_isveren_ilk_calisanlarla_kaydedilir_ve_ana_personele_eklenmez(): void
    {
        Livewire::test(TaseronYonetimi::class)
            ->callAction('yeniTaseron', [
                'firma_id' => $this->firma->id,
                'tur' => 'alt_isveren',
                'unvan' => 'Yıldız Elektrik Ltd.',
                'faaliyet' => 'Elektrik tesisatı',
                'sozlesme_no' => 'SZ-1',
                'sozlesme_baslangic' => '2026-01-01',
                'sozlesme_bitis' => '2027-01-01',
                'ilk_calisanlar' => "Ali Veli - Elektrikçi\nAyşe Kaya",
            ])
            ->assertHasNoActionErrors()
            ->assertSet('seciliId', Taseron::sole()->id);

        $t = Taseron::sole();
        $this->assertSame('Elektrik tesisatı', $t->faaliyet);
        $this->assertSame(['Ali Veli', 'Ayşe Kaya'], $t->calisanlar->pluck('ad_soyad')->all());
        $this->assertSame('Elektrikçi', $t->calisanlar->first()->gorev);
        $this->assertSame(0, $this->firma->calisanlar()->count());
    }

    public function test_baskasinin_firmasina_taseron_eklenemez(): void
    {
        $baska = Firma::factory()->for(User::factory())->create();
        // Sahip tüm firmaları görür — izolasyon kısıtlı (sahip olmayan) kullanıcıyla test edilir.
        $kisitli = User::factory()->kisitli()->create();
        $kisitli->sayfaYetkileri()->create(['sayfa_anahtari' => 'taseron-yonetimi']);
        $this->actingAs($kisitli);

        Livewire::test(TaseronYonetimi::class)
            ->callAction('yeniTaseron', ['firma_id' => $baska->id, 'tur' => 'taseron', 'unvan' => 'X']);

        $this->assertSame(0, Taseron::count());
    }

    public function test_eksikler_sozlesme_belge_ve_calisan_kontrolu(): void
    {
        $t = $this->taseron(['sozlesme_bitis' => now()->subDay()]);

        $eksik = $t->eksikler();
        $this->assertStringContainsString('bitmiş', $eksik[0]);
        $this->assertContains('Belge kaydı bulunmuyor', $eksik);
        $this->assertContains('Aktif taşeron çalışanı bulunmuyor', $eksik);

        // Çok tehlikeli: eğitim 1 yıl, sağlık 1 yıl
        $t->calisanlar()->create(['ad_soyad' => 'A', 'isg_egitim_tarihi' => now()->subMonths(6), 'saglik_raporu_tarihi' => now()->subMonths(13)]);
        $t->belgeler()->create(['tur' => 'sozlesme']);
        $t->update(['sozlesme_bitis' => now()->addYear()]);

        $eksik = $t->fresh()->eksikler();
        $this->assertCount(2, $eksik);
        $this->assertStringContainsString('Eksik zorunlu belge', $eksik[0]);
        $this->assertStringContainsString('SGK işyeri tescil', $eksik[0]);
        $this->assertSame('1 çalışanın sağlık raporu yok / süresi dolmuş', $eksik[1]);

        foreach (['sgk_tescil', 'isg_hizmet', 'risk_degerlendirmesi', 'egitim_kaydi'] as $tur) {
            $t->belgeler()->create(['tur' => $tur]);
        }
        $t->calisanlar()->first()->update(['saglik_raporu_tarihi' => now()->subMonth()]);
        $this->assertSame([], $t->fresh()->eksikler());

        // Süresi dolan belge ve pasif kayıt
        $t->belgeler()->create(['tur' => 'sigorta', 'gecerlilik_sonu' => now()->subDay()]);
        $this->assertStringContainsString('Süresi dolmuş belge', $t->fresh()->eksikler()[0]);
        $t->update(['aktif' => false]);
        $this->assertSame([], $t->fresh()->eksikler());
    }

    public function test_taseron_turunde_risk_degerlendirmesi_zorunlu_degil(): void
    {
        $t = $this->taseron(['tur' => 'taseron']);
        $t->belgeler()->create(['tur' => 'sozlesme']);

        $this->assertSame(
            ['İSG hizmet sözleşmesi / İGU–hekim görevlendirmesi', 'İSG eğitim kayıtları'],
            $t->fresh()->eksikZorunluBelgeler(),
        );
    }

    public function test_calisan_ekleme_kimligi_maskeler_ve_pasife_alir(): void
    {
        $t = $this->taseron();

        $sayfa = Livewire::test(TaseronYonetimi::class)
            ->call('yonet', $t->id)
            ->set('calisanAd', 'Mehmet Demir')
            ->set('calisanGorev', 'Usta')
            ->set('calisanTc', '12345678901')
            ->set('calisanEgitim', now()->subMonth()->toDateString())
            ->call('calisanEkle');

        $c = TaseronCalisani::sole();
        $this->assertSame('123******01', $c->tc_maskeli);
        $this->assertTrue($c->egitimGecerliMi());
        $this->assertFalse($c->saglikGecerliMi());

        $sayfa->call('calisanAktiflik', $c->id);
        $this->assertFalse($c->fresh()->aktif);

        $sayfa->callAction('calisanDuzenle', ['ad_soyad' => 'Mehmet Demir', 'tc_maskeli' => '98765432109', 'saglik_raporu_tarihi' => now()->toDateString()], ['id' => $c->id])
            ->assertHasNoActionErrors();
        $this->assertSame('987******09', $c->fresh()->tc_maskeli);
    }

    public function test_belge_yukle_indir_ve_sil(): void
    {
        Storage::fake('public');
        $t = $this->taseron();

        $sayfa = Livewire::test(TaseronYonetimi::class)
            ->call('yonet', $t->id)
            ->callAction('belgeEkle', [
                'tur' => 'sozlesme',
                'baslik' => 'Alt işverenlik sözleşmesi',
                'gecerlilik_sonu' => now()->addYear()->toDateString(),
                'dosya' => UploadedFile::fake()->create('sozlesme.pdf', 50, 'application/pdf'),
            ])
            ->assertHasNoActionErrors();

        $b = $t->belgeler()->sole();
        $this->assertSame('gecerli', $b->durum());
        Storage::disk('public')->assertExists($b->dosya_yolu);

        $sayfa->call('belgeSil', $b->id);
        Storage::disk('public')->assertMissing($b->dosya_yolu);
        $this->assertSame(0, $t->belgeler()->count());
    }

    public function test_is_izni_baglanir_ve_kaldirilir(): void
    {
        $t = $this->taseron();
        $izin = IsIzinFormu::create(['firma_id' => $this->firma->id, 'calisma_alani' => 'Çatı', 'baslangic' => now()]);
        $baskaFirmaIzni = IsIzinFormu::create(['firma_id' => Firma::factory()->for($this->uzman)->create()->id, 'calisma_alani' => 'X']);

        $sayfa = Livewire::test(TaseronYonetimi::class)->call('yonet', $t->id);
        $this->assertArrayHasKey($izin->id, $sayfa->instance()->baglanabilirIzinler);
        $this->assertArrayNotHasKey($baskaFirmaIzni->id, $sayfa->instance()->baglanabilirIzinler);

        $sayfa->set('baglanacakIzinId', $baskaFirmaIzni->id)->call('izinBagla');
        $this->assertSame(0, $t->isIzinleri()->count());

        $sayfa->set('baglanacakIzinId', $izin->id)->call('izinBagla');
        $this->assertSame([$izin->id], $t->isIzinleri()->pluck('is_izin_formlari.id')->all());
        $this->assertSame('Çatı', $izin->fresh()->calisma_alani);

        $sayfa->call('izinBagKaldir', $izin->id);
        $this->assertSame(0, $t->isIzinleri()->count());
    }

    public function test_pasife_al_listeden_gizler_ve_sil_alt_kayitlari_temizler(): void
    {
        $t = $this->taseron();
        $t->calisanlar()->create(['ad_soyad' => 'A']);
        $t->belgeler()->create(['tur' => 'sozlesme']);

        $sayfa = Livewire::test(TaseronYonetimi::class)->call('yonet', $t->id)->call('aktiflikDegistir');
        $this->assertFalse($t->fresh()->aktif);
        $this->assertCount(0, $sayfa->instance()->taseronlar);
        $sayfa->set('pasifleriGoster', true);
        $this->assertCount(1, $sayfa->instance()->taseronlar);

        $sayfa->call('taseronSil');
        $this->assertSame(0, Taseron::count());
        $this->assertSame(0, TaseronCalisani::count());
        $this->assertDatabaseCount('taseron_belgeleri', 0);
    }

    public function test_uygunluk_pdf_ve_excel_uretilir(): void
    {
        $t = $this->taseron();
        $t->calisanlar()->create(['ad_soyad' => 'Ali', 'isg_egitim_tarihi' => now()]);
        $t->belgeler()->create(['tur' => 'sozlesme', 'gecerlilik_sonu' => now()->addMonth()]);

        ob_start();
        TaseronUretici::uygunlukPdf($t)->sendContent();
        $this->assertStringStartsWith('%PDF', ob_get_clean());

        $tmp = tempnam(sys_get_temp_dir(), 'tsx').'.xlsx';
        ob_start();
        TaseronUretici::listeExcel(Taseron::with(['firma', 'calisanlar', 'belgeler'])->get())->sendContent();
        file_put_contents($tmp, ob_get_clean());
        $sayfa = \PhpOffice\PhpSpreadsheet\IOFactory::load($tmp)->getActiveSheet();
        @unlink($tmp);

        $this->assertSame('Yıldız Elektrik', $sayfa->getCell('C2')->getValue());
        $this->assertSame('Eksik var', $sayfa->getCell('N2')->getValue());
    }
}
