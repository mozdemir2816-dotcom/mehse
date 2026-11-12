<?php

namespace Tests\Feature;

use App\Filament\Pages\PeriyodikKontrol as PeriyodikKontrolSayfasi;
use App\Models\Bildirim;
use App\Models\Firma;
use App\Models\IsEkipmani;
use App\Models\IsEkipmaniBakimi;
use App\Models\Talimat;
use App\Models\TalimatSablonu;
use App\Models\User;
use App\Support\BildirimTarayici;
use App\Support\EkipmanKatalogu;
use App\Support\EkipmanTalimatTaslagi;
use App\Support\EkipmanTebligPaketi;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

/** 12.11.2026: QR operatör ekranı, günlük kontrol → arıza kaydı, 30/15/7 uyarı, tebliğ paketi, YZ talimat taslağı. */
class AkilliEkipmanTest extends TestCase
{
    use RefreshDatabase;

    private User $uzman;

    private Firma $firma;

    protected function setUp(): void
    {
        parent::setUp();
        $this->uzman = User::factory()->create(['rol' => 'sahip']);
        $this->actingAs($this->uzman);
        $this->firma = Firma::factory()->for($this->uzman)->create(['nace_kodu' => '28.29.01']);
    }

    private function forklift(array $ek = []): IsEkipmani
    {
        return IsEkipmani::create($ek + [
            'firma_id' => $this->firma->id, 'kategori' => 'kaldirma_iletme', 'tip' => 'Forklift / Akülü & Dizel İstifleyici',
            'ekipman_adi' => 'Forklift 1', 'seri_no' => 'FL-01', 'aktif' => true,
            'bakim_sorumlusu' => 'Ali Usta', 'bakim_sorumlusu_eposta' => 'bakim@ornek.test',
        ]);
    }

    public function test_qr_anahtari_uretilir_ve_operator_ekrani_giris_istemez(): void
    {
        $e = $this->forklift();
        $this->assertSame(32, strlen($e->qr_anahtar));
        $this->assertStringStartsWith('data:image/svg+xml;base64,', $e->qrDataUri());

        auth()->logout();
        $this->get(route('ekipman.operator', $e->qr_anahtar))
            ->assertOk()
            ->assertSee('Forklift 1')
            ->assertSee('Forklift Kullanma Talimatı')                       // firmaya otomatik eklenen talimat
            ->assertSee(EkipmanKatalogu::kontrolFormu($e->tip)['sorular'][0]);

        $this->get('/ekipman/'.str_repeat('a', 32))->assertNotFound();
        $e->update(['aktif' => false]);
        $this->get(route('ekipman.operator', $e->qr_anahtar))->assertNotFound();
    }

    public function test_pin_tanimliysa_once_pin_sorulur(): void
    {
        $e = $this->forklift(['qr_pin' => Hash::make('4321')]);
        auth()->logout();

        $this->get(route('ekipman.operator', $e->qr_anahtar))->assertOk()->assertSee('Makine PIN kodu')->assertDontSee('Adınız soyadınız');
        $this->post(route('ekipman.operator.kontrol', $e->qr_anahtar), ['operator' => 'X', 'cevap' => ['U']])->assertForbidden();
        $this->post(route('ekipman.operator.pin', $e->qr_anahtar), ['pin' => '0000'])->assertSessionHasErrors('pin');
        $this->post(route('ekipman.operator.pin', $e->qr_anahtar), ['pin' => '4321'])->assertRedirect(route('ekipman.operator', $e->qr_anahtar));
        $this->get(route('ekipman.operator', $e->qr_anahtar))->assertSee('Adınız soyadınız');
    }

    public function test_uygun_degil_isaretlenince_ariza_kaydi_bildirim_ve_eposta(): void
    {
        config(['mail.default' => 'array']);
        $e = $this->forklift();
        $n = count(EkipmanKatalogu::kontrolFormu($e->tip)['sorular']);
        auth()->logout();

        // Eksik işaretleme kabul edilmez.
        $this->post(route('ekipman.operator.kontrol', $e->qr_anahtar), ['operator' => 'Veli', 'cevap' => ['U']])->assertSessionHasErrors('cevap');

        $cevap = array_fill(0, $n, 'U');
        $cevap[1] = 'UD';
        $this->post(route('ekipman.operator.kontrol', $e->qr_anahtar), ['operator' => 'Veli Operatör', 'cevap' => $cevap, 'not' => [1 => 'Park freni tutmuyor']])
            ->assertRedirect()->assertSessionHas('kaydedildi', 'uygunsuz');

        $kontrol = $e->gunlukKontroller()->sole();
        $this->assertFalse($kontrol->kullanima_uygun);
        $this->assertSame(1, $kontrol->uygunsuz_sayisi);

        $bakim = IsEkipmaniBakimi::sole();
        $this->assertSame('acik', $bakim->durum);
        $this->assertSame('Park freni tutmuyor', $bakim->uygunsuz_maddeler[0]['not']);
        $this->assertSame('Ali Usta', $bakim->sorumlu);

        $this->assertTrue(Bildirim::where('anahtar', 'f'.$this->firma->id.':ekipman_ariza:'.$bakim->id)->where('seviye', 'kritik')->exists());
        $mesajlar = app('mailer')->getSymfonyTransport()->messages();
        $this->assertCount(1, $mesajlar);
        $this->assertStringContainsString('Park freni tutmuyor', $mesajlar[0]->getOriginalMessage()->getTextBody());

        $this->get(route('ekipman.operator', $e->qr_anahtar))->assertSee('MAKİNEYİ KULLANMAYIN');   // kayıt sonrası (flash)
        $this->get(route('ekipman.operator', $e->qr_anahtar))->assertSee('Açık arıza kaydı var');   // sonraki açılışlarda

        // Panelden kapatınca bildirim çözülür.
        $this->actingAs($this->uzman);
        Livewire::test(PeriyodikKontrolSayfasi::class)->set('firmaId', $this->firma->id)
            ->callAction('bakimKapat', ['kapanis_notu' => 'Fren balatası değişti'], ['id' => $bakim->id])->assertHasNoActionErrors();
        $this->assertSame('kapali', $bakim->fresh()->durum);
        $this->assertNotNull(Bildirim::where('anahtar', 'f'.$this->firma->id.':ekipman_ariza:'.$bakim->id)->value('cozuldu_at'));
    }

    public function test_tum_maddeler_uygunsa_ariza_acilmaz(): void
    {
        $e = $this->forklift();
        $n = count(EkipmanKatalogu::kontrolFormu($e->tip)['sorular']);
        $this->post(route('ekipman.operator.kontrol', $e->qr_anahtar), ['operator' => 'Veli', 'cevap' => array_fill(0, $n, 'U')])
            ->assertSessionHas('kaydedildi', 'uygun');
        $this->assertSame(0, IsEkipmaniBakimi::count());
        $this->assertTrue($e->gunlukKontroller()->sole()->kullanima_uygun);
    }

    public function test_periyodik_kontrol_30_15_7_gun_renkleri_ve_bildirimleri(): void
    {
        $gun = fn (int $g) => $this->forklift(['ekipman_adi' => "F{$g}", 'son_muayene_tarihi' => now()->subMonths(3), 'sonraki_vize_tarihi' => now()->addDays($g)]);
        $this->assertSame('yesil', $gun(25)->uyariSeviyesi());
        $this->assertSame('sari', $gun(12)->uyariSeviyesi());
        $this->assertSame('kirmizi', $gun(5)->uyariSeviyesi());
        $this->assertSame('kirmizi', $gun(-3)->uyariSeviyesi());
        $this->assertNull($gun(60)->uyariSeviyesi());

        $seviyeler = BildirimTarayici::firmaBildirimleri($this->firma)
            ->filter(fn ($b) => str_contains($b['anahtar'], ':ekipman_vize:'))
            ->mapWithKeys(fn ($b) => [str($b['baslik'])->afterLast(': ')->toString() => $b['seviye']])->all();
        $this->assertSame(['F25' => 'bilgi', 'F12' => 'uyari', 'F5' => 'kritik', 'F-3' => 'kritik'], $seviyeler);
    }

    public function test_teblig_paketi_uc_pdf_ve_imzali_nusha_ile_teblig_edildi(): void
    {
        Storage::fake('local');
        $e = $this->forklift(['son_muayene_tarihi' => now()->subMonths(2), 'sonraki_vize_tarihi' => now()->addMonths(10)]);

        $belgeler = EkipmanTebligPaketi::belgeler($e);
        $this->assertCount(3, $belgeler);
        foreach ($belgeler as $pdf) {
            $this->assertStringStartsWith('%PDF', $pdf);
        }
        $this->assertSame('yok', $e->tebligDurumu());

        $sayfa = Livewire::test(PeriyodikKontrolSayfasi::class)->set('firmaId', $this->firma->id);
        $sayfa->call('tebligPaketiIndir', $e->id)->assertFileDownloaded('teblig-paketi-forklift-1.zip');
        $this->assertSame('bekliyor', $e->fresh()->tebligDurumu());
        $sayfa->assertSee('Çıktı alındı — imza bekliyor');

        $sayfa->callAction('tebligYukle', ['dosya' => UploadedFile::fake()->image('imzali.jpg'), 'teblig_tarihi' => now()->toDateString()], ['id' => $e->id])
            ->assertHasNoActionErrors()
            ->assertSee('İşverene tebliğ edildi');
        $e->refresh();
        $this->assertSame('edildi', $e->tebligDurumu());
        Storage::disk('local')->assertExists($e->imzali_belge_yolu);
        $sayfa->call('imzaliNushaIndir', $e->id)->assertFileDownloaded();
    }

    public function test_detay_ve_ayarlar_pin_ve_qr_yenileme(): void
    {
        $e = $this->forklift();
        $eskiQr = $e->qr_anahtar;

        Livewire::test(PeriyodikKontrolSayfasi::class)->set('firmaId', $this->firma->id)
            ->mountAction('ekipmanDetay', ['id' => $e->id])->assertMountedActionModalSee(['Operatör QR erişimi', 'Tebliğ paketini indir']);

        Livewire::test(PeriyodikKontrolSayfasi::class)->set('firmaId', $this->firma->id)
            ->callAction('ekipmanAyar', ['ekipman_adi' => 'Forklift A', 'qr_pin' => '2468', 'qr_yenile' => true, 'uretim_yili' => 2019], ['id' => $e->id])
            ->assertHasNoActionErrors();

        $e->refresh();
        $this->assertSame('Forklift A', $e->ekipman_adi);
        $this->assertSame(2019, $e->uretim_yili);
        $this->assertTrue($e->pinDogruMu('2468'));
        $this->assertNotSame($eskiQr, $e->qr_anahtar);
        $this->get('/ekipman/'.$eskiQr)->assertNotFound();
    }

    public function test_yz_talimat_taslagi_kutuphaneye_ve_firmaya_eklenir(): void
    {
        config(['services.gemini.key' => 'test-key', 'services.gemini.yedek_modeller' => []]);
        Http::fake(['*generativelanguage.googleapis.com*' => Http::response([
            'candidates' => [['content' => ['parts' => [['text' => json_encode([
                ['baslik' => 'AMAÇ VE KAPSAM', 'aciklama' => 'Bu talimatın amacı; kule dışı vinçte güvenli çalışma.', 'maddeler' => []],
                ['baslik' => 'ÇALIŞMA ÖNCESİ ALINACAK GÜVENLİK ÖNLEMLERİ', 'aciklama' => '', 'maddeler' => ['Halatlar kontrol edilmelidir.']],
            ])]]]]],
        ])]);

        $vinc = IsEkipmani::create(['firma_id' => $this->firma->id, 'kategori' => 'kaldirma_iletme', 'tip' => 'Portal Vinç', 'ekipman_adi' => 'Portal Vinç 10 t', 'aktif' => true]);
        $this->assertSame([], EkipmanKatalogu::talimatSablonlari('Portal Vinç'));
        $this->assertContains('Portal Vinç', EkipmanTalimatTaslagi::talimatsizTipler());

        Livewire::test(PeriyodikKontrolSayfasi::class)->set('firmaId', $this->firma->id)
            ->assertSee('Talimat yok — YZ taslağı hazırla')
            ->call('talimatTaslagiHazirla', $vinc->id)
            ->assertSee('Talimat: PORTAL VİNÇ GÜVENLİ ÇALIŞMA TALİMATI');

        $sablon = TalimatSablonu::sole();
        $this->assertSame('Portal Vinç', $sablon->ekipman_tipi);
        $this->assertTrue($sablon->yz_taslak);
        $this->assertTrue(Talimat::where('firma_id', $this->firma->id)->where('baslik', 'PORTAL VİNÇ GÜVENLİ ÇALIŞMA TALİMATI')->exists());
        $this->assertNotContains('Portal Vinç', EkipmanTalimatTaslagi::talimatsizTipler());

        // Aynı tipte yeni makine: talimat kütüphaneden, yapay zekâya sorulmadan gelir.
        Http::fake();
        $firma2 = Firma::factory()->for($this->uzman)->create();
        IsEkipmani::create(['firma_id' => $firma2->id, 'kategori' => 'kaldirma_iletme', 'tip' => 'Portal Vinç', 'ekipman_adi' => 'PV', 'aktif' => true]);
        $this->assertTrue(Talimat::where('firma_id', $firma2->id)->where('baslik', 'PORTAL VİNÇ GÜVENLİ ÇALIŞMA TALİMATI')->exists());
        Http::assertNothingSent();
    }
}
