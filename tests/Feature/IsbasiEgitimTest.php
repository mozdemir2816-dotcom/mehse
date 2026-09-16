<?php

namespace Tests\Feature;

use App\Filament\Pages\IsbasiEgitim as IsbasiSayfasi;
use App\Models\Calisan;
use App\Models\EgitimSunumu;
use App\Models\Firma;
use App\Models\IsbasiEgitimTutanagi;
use App\Models\User;
use App\Support\IsbasiEgitimTutanagiUretici;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Tests\TestCase;

class IsbasiEgitimTest extends TestCase
{
    use RefreshDatabase;

    private User $uzman;

    protected function setUp(): void
    {
        parent::setUp();
        $this->uzman = User::factory()->create();
        $this->actingAs($this->uzman);
    }

    public function test_firma_calisanindan_hizli_secim_alanlari_doldurur(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();
        $calisan = Calisan::factory()->for($firma)->create(['ad_soyad' => 'Ahmet Yılmaz', 'tc' => '12345678901']);

        $component = Livewire::test(IsbasiSayfasi::class)
            ->set('firmaId', $firma->id)
            ->set('calisanHizliSecId', $calisan->id);

        $this->assertSame('Ahmet Yılmaz', $component->get('calisanAdSoyad'));
        $this->assertSame('12345678901', $component->get('calisanTc'));
    }

    public function test_konu_toggle_ve_tumunu_sec(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();
        $toplam = collect(config('isg.isbasi_egitim.konu_kategorileri'))->flatten()->count();

        $component = Livewire::test(IsbasiSayfasi::class)
            ->set('firmaId', $firma->id)
            ->call('tumKonular', true);

        $this->assertCount($toplam, $component->get('secilenKonular'));

        $component->call('tumKonular', false);
        $this->assertCount(0, $component->get('secilenKonular'));
    }

    public function test_pdf_aksiyonu_kayit_olusturur(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();
        $ilkMadde = collect(config('isg.isbasi_egitim.konu_kategorileri'))->flatten()->first();

        Livewire::test(IsbasiSayfasi::class)
            ->set('firmaId', $firma->id)
            ->set('calisanAdSoyad', 'Ahmet Yılmaz')
            ->call('konuToggle', $ilkMadde)
            ->callAction('pdf');

        $t = IsbasiEgitimTutanagi::where('firma_id', $firma->id)->firstOrFail();
        $this->assertSame('Ahmet Yılmaz', $t->calisan_ad_soyad);
        $this->assertContains($ilkMadde, $t->konular);
    }

    public function test_calisan_adi_olmadan_kaydedilemez(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();

        Livewire::test(IsbasiSayfasi::class)
            ->set('firmaId', $firma->id)
            ->callAction('pdf');

        $this->assertDatabaseCount('isbasi_egitim_tutanaklari', 0);
    }

    public function test_tc_gizli_isaretlenince_pdf_gorunumu_maskelenir(): void
    {
        $t = IsbasiEgitimTutanagi::create([
            'firma_id' => Firma::factory()->for($this->uzman)->create()->id,
            'calisan_ad_soyad' => 'Test Kişi',
            'calisan_tc' => '12345678901',
            'tc_gizli' => true,
        ]);

        $this->assertSame('123'.str_repeat('*', 8), $t->tcGorunur());
    }

    public function test_pdf_uretilir(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();
        $t = IsbasiEgitimTutanagi::create([
            'firma_id' => $firma->id,
            'calisan_ad_soyad' => 'Test Kişi',
            'konular' => ['İşyeri ve organizasyonun tanıtımı'],
        ]);

        $yanit = IsbasiEgitimTutanagiUretici::pdf($t);

        $this->assertInstanceOf(StreamedResponse::class, $yanit);
        ob_start();
        $yanit->sendContent();
        $icerik = ob_get_clean();
        $this->assertStringStartsWith('%PDF', $icerik);
    }

    public function test_katilim_formu_calisan_adi_gerektirmeden_uretilir(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();
        Calisan::factory()->count(3)->for($firma)->create();

        // Tekil tutanağın aksine, toplu katılım formu tek bir "Çalışan Adı"
        // alanına bağlı değildir — yalnız firma seçilmesi yeterlidir.
        Livewire::test(IsbasiSayfasi::class)
            ->set('firmaId', $firma->id)
            ->assertActionExists('katilimFormu')
            ->callAction('katilimFormu');

        $this->assertDatabaseCount('isbasi_egitim_tutanaklari', 0);
    }

    public function test_bos_katilim_formu_personel_tanimlanmadan_indirilir(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();

        Livewire::test(IsbasiSayfasi::class)
            ->set('firmaId', $firma->id)
            ->assertActionExists('bosKatilimFormu')
            ->callAction('bosKatilimFormu');

        // Boş imza satırlarıyla; hiçbir kayıt oluşturmaz.
        $this->assertDatabaseCount('isbasi_egitim_tutanaklari', 0);
        $this->assertSame(0, $firma->calisanlar()->count());
    }

    public function test_bos_katilim_formu_firma_secmeden_indirilir(): void
    {
        $ilkMadde = collect(config('isg.isbasi_egitim.konu_kategorileri'))->flatten()->first();

        Livewire::test(IsbasiSayfasi::class)
            ->assertActionExists('bosKatilimFormu')
            ->call('konuToggle', $ilkMadde)
            ->callAction('bosKatilimFormu');

        $this->assertDatabaseCount('isbasi_egitim_tutanaklari', 0);
    }

    public function test_katilim_formu_uretici_firmasiz_calisir(): void
    {
        $yanit = IsbasiEgitimTutanagiUretici::katilimFormuPdf(null, [
            'egitim_tarihi' => now()->toDateString(),
            'sure_saat' => 2,
            'konular' => [],
            'katilimcilar' => [],
        ]);

        $this->assertInstanceOf(StreamedResponse::class, $yanit);
        ob_start();
        $yanit->sendContent();
        $this->assertStringStartsWith('%PDF', ob_get_clean());
        $this->assertStringContainsString('bos', $yanit->headers->get('content-disposition'));
    }

    public function test_katilim_formu_pdf_firma_calisanlarini_en_az_10_satir_listeler(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();
        Calisan::factory()->for($firma)->create(['ad_soyad' => 'Zeynep Kaya', 'tc' => '11122233344', 'gorev' => 'Operatör']);

        $yanit = IsbasiEgitimTutanagiUretici::katilimFormuPdf($firma, [
            'egitim_tarihi' => now()->toDateString(),
            'sure_saat' => 2,
            'egitim_yeri' => 'Üretim Sahası',
            'egitimi_veren' => 'Ali Veli',
            'egitim_yontemi' => 'Uygulamalı',
            'belge_tarihi' => now()->toDateString(),
            'igu_imzasi' => true,
            'isyeri_hekimi_imzasi' => false,
            'konular' => [],
            'katilimcilar' => [['ad_soyad' => 'Zeynep Kaya', 'tc' => '11122233344', 'gorev' => 'Operatör']],
        ]);

        $this->assertInstanceOf(StreamedResponse::class, $yanit);
        ob_start();
        $yanit->sendContent();
        $icerik = ob_get_clean();
        $this->assertStringStartsWith('%PDF', $icerik);

        $html = view('pdf.isbasi-egitim-katilim-formu', [
            'firma' => $firma,
            'veri' => [
                'egitim_tarihi' => '06.09.2026',
                'katilimcilar' => [['ad_soyad' => 'Zeynep Kaya', 'tc' => '11122233344', 'gorev' => 'Operatör']],
            ],
        ])->render();

        $this->assertStringContainsString('Zeynep Kaya', $html);
        // Katılımcı sayısı 10'dan az olsa da katılım tablosunda en az 10 satır
        // olur: kunye tablosunun 3 satırı + katılım tablosunun 1 başlık +
        // 10 veri satırı + alt imza tablosunun 1 satırı = toplam 15 <tr>.
        $this->assertSame(15, substr_count($html, '<tr>'));
    }

    public function test_gecmis_tutanak_silinir(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();
        $t = IsbasiEgitimTutanagi::create(['firma_id' => $firma->id, 'calisan_ad_soyad' => 'Test']);

        Livewire::test(IsbasiSayfasi::class)
            ->set('firmaId', $firma->id)
            ->call('gecmisSil', $t->id);

        $this->assertDatabaseMissing('isbasi_egitim_tutanaklari', ['id' => $t->id]);
    }

    public function test_baska_uzmanin_firmasi_secilemez(): void
    {
        $baskaUzman = User::factory()->create();
        $baskaFirma = Firma::factory()->for($baskaUzman)->create();

        $firmalar = Livewire::test(IsbasiSayfasi::class)->instance()->firmalar();

        $this->assertArrayNotHasKey($baskaFirma->id, $firmalar);
    }

    public function test_egitim_sunumu_yuklenir_ve_kutuphanede_gorunur(): void
    {
        Storage::fake('public');

        Livewire::test(IsbasiSayfasi::class)
            ->callAction('sunumYukle', data: [
                'gorev' => 'Kaynakçı',
                'baslik' => 'Kaynak İşleri Güvenliği',
                'dosya' => UploadedFile::fake()->createWithContent('kaynak.pdf', str_repeat('a', 1024)),
            ])
            ->assertHasNoActionErrors();

        $sunum = EgitimSunumu::where('user_id', $this->uzman->id)->firstOrFail();
        $this->assertSame('Kaynakçı', $sunum->gorev);
        $this->assertSame('kaynak.pdf', $sunum->dosya_adi);
        $this->assertTrue(Storage::disk('public')->exists($sunum->dosya_yolu));
        $this->assertGreaterThan(0, $sunum->boyut);
    }

    public function test_calisan_gorevine_uygun_sunum_onerilir(): void
    {
        EgitimSunumu::create([
            'user_id' => $this->uzman->id, 'gorev' => 'Kaynakçı', 'baslik' => 'Kaynak Güvenliği',
        ]);
        EgitimSunumu::create([
            'user_id' => $this->uzman->id, 'gorev' => 'Forklift Operatörü', 'baslik' => 'Forklift Güvenliği',
        ]);

        $component = Livewire::test(IsbasiSayfasi::class)->set('calisanGorev', 'kaynakçı');

        $eslesen = $component->get('eslesenSunumlar');
        $this->assertCount(1, $eslesen);
        $this->assertSame('Kaynakçı', $eslesen->first()->gorev);
    }

    public function test_gorev_bos_ise_hicbir_sunum_onerilmez(): void
    {
        EgitimSunumu::create(['user_id' => $this->uzman->id, 'gorev' => 'Kaynakçı', 'baslik' => 'Kaynak Güvenliği']);

        $component = Livewire::test(IsbasiSayfasi::class);

        $this->assertCount(0, $component->get('eslesenSunumlar'));
    }

    public function test_calisan_hizli_secilince_gorevi_de_dolar_ve_eslesen_sunum_bulunur(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();
        $calisan = Calisan::factory()->for($firma)->create(['gorev' => 'Forklift Operatörü']);
        EgitimSunumu::create(['user_id' => $this->uzman->id, 'gorev' => 'Forklift Operatörü', 'baslik' => 'Forklift Güvenliği']);

        $component = Livewire::test(IsbasiSayfasi::class)
            ->set('firmaId', $firma->id)
            ->set('calisanHizliSecId', $calisan->id);

        $this->assertSame('Forklift Operatörü', $component->get('calisanGorev'));
        $this->assertCount(1, $component->get('eslesenSunumlar'));
    }

    public function test_baska_uzmanin_sunumu_gorunmez(): void
    {
        $baskaUzman = User::factory()->create();
        EgitimSunumu::create(['user_id' => $baskaUzman->id, 'gorev' => 'Kaynakçı', 'baslik' => 'Başkasının Sunumu']);

        $sunumlar = Livewire::test(IsbasiSayfasi::class)->get('sunumlar');

        $this->assertCount(0, $sunumlar);
    }

    public function test_sunum_indirilir(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('egitim-sunumlari/'.$this->uzman->id.'/s.pdf', 'icerik');

        $sunum = EgitimSunumu::create([
            'user_id' => $this->uzman->id, 'gorev' => 'Kaynakçı', 'baslik' => 'Test',
            'dosya_adi' => 's.pdf', 'dosya_yolu' => 'egitim-sunumlari/'.$this->uzman->id.'/s.pdf', 'boyut' => 7,
        ]);

        $yanit = Livewire::test(IsbasiSayfasi::class)->instance()->sunumIndir($sunum->id);

        $this->assertInstanceOf(StreamedResponse::class, $yanit);
    }

    public function test_sunum_silinince_dosya_da_kaldirilir(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('egitim-sunumlari/'.$this->uzman->id.'/s.pdf', 'icerik');

        $sunum = EgitimSunumu::create([
            'user_id' => $this->uzman->id, 'gorev' => 'Kaynakçı', 'baslik' => 'Test',
            'dosya_adi' => 's.pdf', 'dosya_yolu' => 'egitim-sunumlari/'.$this->uzman->id.'/s.pdf', 'boyut' => 7,
        ]);

        Livewire::test(IsbasiSayfasi::class)->call('sunumSil', $sunum->id);

        $this->assertDatabaseMissing('egitim_sunumlari', ['id' => $sunum->id]);
        Storage::disk('public')->assertMissing('egitim-sunumlari/'.$this->uzman->id.'/s.pdf');
    }
}
