<?php

namespace Tests\Feature;

use App\Filament\Pages\IsIzinFormu as IzinSayfasi;
use App\Models\Firma;
use App\Models\IsIzinFormu;
use App\Models\IsIzinSablonu;
use App\Models\User;
use App\Support\IsIzinFormuUretici;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Tests\TestCase;

class IsIzinFormuTest extends TestCase
{
    use RefreshDatabase;

    private User $uzman;

    protected function setUp(): void
    {
        parent::setUp();
        $this->uzman = User::factory()->create();
        $this->actingAs($this->uzman);
    }

    public function test_izin_turu_secilince_ilgili_onlemler_gorunur(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();

        $component = Livewire::test(IzinSayfasi::class)
            ->set('firmaId', $firma->id)
            ->call('izinTuruToggle', 'kapali_alan');

        $onlemler = $component->get('onlemler');
        $this->assertContains('Gaz ölçümü yapıldı ve uygun', $onlemler);
        $this->assertNotContains('Enerji kesildi ve kilitlendi (LOTO)', $onlemler);
        $this->assertContains('Çalışma alanı sınırlandırıldı / Uyarı levhaları asıldı', $onlemler); // genel madde
    }

    public function test_izin_turu_kaldirilinca_uygun_olmayan_secili_onlem_temizlenir(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();

        $component = Livewire::test(IzinSayfasi::class)
            ->set('firmaId', $firma->id)
            ->call('izinTuruToggle', 'elektrik')
            ->call('onlemToggle', 'Topraklama kontrolü yapıldı')
            ->call('izinTuruToggle', 'elektrik'); // tur kaldırıldı

        $this->assertNotContains('Topraklama kontrolü yapıldı', $component->get('secilenOnlemler'));
    }

    public function test_kkd_secimi_toggle_edilir(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();

        $component = Livewire::test(IzinSayfasi::class)
            ->set('firmaId', $firma->id)
            ->call('kkdToggle', 'Baret');

        $this->assertContains('Baret', $component->get('secilenKkdler'));

        $component->call('kkdToggle', 'Baret');
        $this->assertNotContains('Baret', $component->get('secilenKkdler'));
    }

    public function test_pdf_aksiyonu_kayit_olusturur(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();

        Livewire::test(IzinSayfasi::class)
            ->set('firmaId', $firma->id)
            ->set('calismaAlani', 'Kazan Dairesi')
            ->call('izinTuruToggle', 'sicak_is')
            ->call('onlemToggle', 'Yangın söndürme tüpü hazır')
            ->call('kkdToggle', 'Kaynak Maskesi')
            ->set('onay1Baslik', 'Formen')
            ->set('onay1Ad', 'Ali Veli')
            ->callAction('pdf');

        $form = IsIzinFormu::where('firma_id', $firma->id)->firstOrFail();
        $this->assertSame('Kazan Dairesi', $form->calisma_alani);
        $this->assertContains('sicak_is', $form->izin_turleri);
        $this->assertContains('Yangın söndürme tüpü hazır', $form->guvenlik_onlemleri);
        $this->assertContains('Kaynak Maskesi', $form->gerekli_kkdler);
        $this->assertStringStartsWith('PTW-'.now()->year.'-', $form->izin_no);
    }

    public function test_pdf_uretilir(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();
        $form = IsIzinFormu::create([
            'firma_id' => $firma->id,
            'calisma_alani' => 'Test Alan',
            'izin_turleri' => ['yukseklik'],
            'guvenlik_onlemleri' => ['Yaşam hattı / Emniyet kemeri kontrol edildi'],
            'gerekli_kkdler' => ['Emniyet Kemeri'],
        ]);

        $yanit = IsIzinFormuUretici::pdf($form);

        $this->assertInstanceOf(StreamedResponse::class, $yanit);
        ob_start();
        $yanit->sendContent();
        $icerik = ob_get_clean();
        $this->assertStringStartsWith('%PDF', $icerik);
    }

    public function test_gecmis_form_silinir(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();
        $form = IsIzinFormu::create(['firma_id' => $firma->id]);

        Livewire::test(IzinSayfasi::class)
            ->set('firmaId', $firma->id)
            ->call('gecmisSil', $form->id);

        $this->assertDatabaseMissing('is_izin_formlari', ['id' => $form->id]);
    }

    public function test_baska_uzmanin_firmasi_secilemez(): void
    {
        $baskaUzman = User::factory()->create();
        $baskaFirma = Firma::factory()->for($baskaUzman)->create();

        $firmalar = Livewire::test(IzinSayfasi::class)->instance()->firmalar();

        $this->assertArrayNotHasKey($baskaFirma->id, $firmalar);
    }

    /*
    |--------------------------------------------------------------------------
    | İzin kütüphanesi
    |--------------------------------------------------------------------------
    */

    public function test_kutuphaneden_hazir_izin_secilince_form_on_dolar(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();

        // "Kapalı / Dar Alan Giriş İzni" hazır katalogda 3. sırada (index 2).
        $component = Livewire::test(IzinSayfasi::class)
            ->set('firmaId', $firma->id)
            ->set('sablonSecim', 'hazir:2');

        $this->assertContains('kapali_alan', $component->get('izinTurleri'));
        $this->assertContains('Gaz ölçümü yapıldı ve uygun', $component->get('secilenOnlemler'));
        $this->assertContains('Giriş-çıkış kayıt çizelgesi tutulacak', $component->get('secilenOnlemler')); // ek önlem
        $this->assertContains('Gaz Maskesi', $component->get('secilenKkdler'));
        $this->assertSame(4, $component->get('gecerlilikSaat'));
        $this->assertNotEmpty($component->get('uyarilar'));
    }

    public function test_form_kutuphaneye_sablon_olarak_eklenir_ve_geri_uygulanir(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();

        Livewire::test(IzinSayfasi::class)
            ->set('firmaId', $firma->id)
            ->call('izinTuruToggle', 'sicak_is')
            ->call('onlemToggle', 'Kaynak sonrası özel kontrol yapılacak')
            ->call('kkdToggle', 'Yüz Siperi')
            ->set('gecerlilikSaat', 6)
            ->callAction('kutuphayeEkle', ['ad' => 'Fabrika Sıcak İş']);

        $sablon = IsIzinSablonu::where('user_id', $this->uzman->id)->firstOrFail();
        $this->assertSame('Fabrika Sıcak İş', $sablon->ad);
        $this->assertContains('sicak_is', $sablon->turler);
        $this->assertContains('Kaynak sonrası özel kontrol yapılacak', $sablon->ek_onlemler);

        $component = Livewire::test(IzinSayfasi::class)
            ->set('firmaId', $firma->id)
            ->set('sablonSecim', 'ozel:'.$sablon->id);

        $this->assertContains('sicak_is', $component->get('izinTurleri'));
        $this->assertContains('Kaynak sonrası özel kontrol yapılacak', $component->get('secilenOnlemler'));
        $this->assertContains('Yüz Siperi', $component->get('secilenKkdler'));
        $this->assertSame(6, $component->get('gecerlilikSaat'));
    }

    public function test_gecerlilik_saati_bitis_bosken_baslangictan_turetilir(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();

        $form = IsIzinFormu::create([
            'firma_id' => $firma->id,
            'baslangic' => '2026-09-10 08:00',
            'gecerlilik_saat' => 8,
        ]);

        $this->assertSame('2026-09-10 16:00', $form->fresh()->bitis->format('Y-m-d H:i'));
    }

    /*
    |--------------------------------------------------------------------------
    | Onay / kapanış yaşam döngüsü
    |--------------------------------------------------------------------------
    */

    public function test_izin_onaya_gonderilir_iki_onayla_onaylanir(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();
        $form = IsIzinFormu::create(['firma_id' => $firma->id, 'izin_turleri' => ['sicak_is']]);

        $component = Livewire::test(IzinSayfasi::class)
            ->set('firmaId', $firma->id)
            ->call('onayaGonder', $form->id);

        $this->assertSame('onay_bekliyor', $form->fresh()->durum);

        $component->call('onayla', $form->id, 1);
        $this->assertSame('onay_bekliyor', $form->fresh()->durum); // tek onay yetmez

        $component->call('onayla', $form->id, 2);
        $form->refresh();
        $this->assertSame('onaylandi', $form->durum);
        $this->assertNotNull($form->onay1_tarih);
        $this->assertNotNull($form->onay2_tarih);
        $this->assertTrue($form->tamOnayliMi());
    }

    public function test_onaycinin_reddi_izni_reddedildi_yapar(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();
        $form = IsIzinFormu::create(['firma_id' => $firma->id]);

        Livewire::test(IzinSayfasi::class)
            ->set('firmaId', $firma->id)
            ->call('onayaGonder', $form->id)
            ->call('islemBaslat', $form->id, 'reddet')
            ->set('islemNotu', 'Gaz ölçümü yapılmamış.')
            ->call('islemiUygula');

        $form->refresh();
        $this->assertSame('reddedildi', $form->durum);
        $this->assertSame('Gaz ölçümü yapılmamış.', $form->red_gerekcesi);
    }

    public function test_onayli_izin_is_tamamlanip_saha_teslimiyle_kapatilir(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();
        $form = IsIzinFormu::create(['firma_id' => $firma->id, 'durum' => 'onaylandi']);

        $component = Livewire::test(IzinSayfasi::class)
            ->set('firmaId', $firma->id)
            ->call('isiTamamla', $form->id);

        $form->refresh();
        $this->assertSame('is_tamamlandi', $form->durum);
        $this->assertNotNull($form->is_bitis_tarihi);

        $component->call('islemBaslat', $form->id, 'kapat')
            ->set('islemNotu', 'Alan temizlendi, ekipman toplandı.')
            ->set('islemSahaTeslim', true)
            ->call('islemiUygula');

        $form->refresh();
        $this->assertSame('kapatildi', $form->durum);
        $this->assertTrue($form->saha_teslim_alindi);
    }

    public function test_taslak_izin_onaylanamaz(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();
        $form = IsIzinFormu::create(['firma_id' => $firma->id]); // durum: taslak

        Livewire::test(IzinSayfasi::class)
            ->set('firmaId', $firma->id)
            ->call('onayla', $form->id, 1);

        $this->assertSame('taslak', $form->fresh()->durum);
        $this->assertNull($form->fresh()->onay1_durum);
    }
}
