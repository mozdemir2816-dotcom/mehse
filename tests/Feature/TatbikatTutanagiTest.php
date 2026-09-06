<?php

namespace Tests\Feature;

use App\Filament\Pages\TatbikatTutanagi as TatbikatSayfasi;
use App\Models\Calisan;
use App\Models\Firma;
use App\Models\TatbikatTutanagi;
use App\Models\User;
use App\Support\TatbikatTutanagiUretici;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Tests\TestCase;

class TatbikatTutanagiTest extends TestCase
{
    use RefreshDatabase;

    private User $uzman;

    protected function setUp(): void
    {
        parent::setUp();
        $this->uzman = User::factory()->create();
        $this->actingAs($this->uzman);
    }

    public function test_senaryo_secilince_metin_otomatik_dolar(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();
        $beklenenMetin = config('isg.tatbikat.senaryolar.yangin.metin');

        $component = Livewire::test(TatbikatSayfasi::class)
            ->set('firmaId', $firma->id)
            ->call('senaryoSec', 'yangin');

        $this->assertSame($beklenenMetin, $component->get('senaryoMetni'));
    }

    public function test_varsayilan_degerlendirme_sorulari_yuklenir(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();

        $component = Livewire::test(TatbikatSayfasi::class)->set('firmaId', $firma->id);

        $this->assertCount(count(config('isg.tatbikat.degerlendirme_sorulari')), $component->get('degerlendirmeler'));
        $this->assertNull($component->get('degerlendirmeler')[0]['cevap']);
    }

    public function test_degerlendirme_cevaplanir_ve_ozel_soru_eklenir(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();

        $component = Livewire::test(TatbikatSayfasi::class)
            ->set('firmaId', $firma->id)
            ->call('degerlendirmeCevapla', 0, 'evet')
            ->set('yeniOzelSoru', 'Jeneratör devreye girdi mi?')
            ->call('ozelSoruEkle');

        $degerlendirmeler = $component->get('degerlendirmeler');
        $this->assertSame('evet', $degerlendirmeler[0]['cevap']);
        $this->assertSame('Jeneratör devreye girdi mi?', end($degerlendirmeler)['soru']);
    }

    public function test_ekip_firma_calisanindan_hizli_eklenir(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();
        $calisan = Calisan::factory()->for($firma)->create(['ad_soyad' => 'Ahmet Yılmaz']);

        $component = Livewire::test(TatbikatSayfasi::class)
            ->set('firmaId', $firma->id)
            ->call('ekipHizliEkle', $calisan->id, 'Söndürme Ekibi');

        $ekipler = $component->get('ekipler');
        $this->assertSame('Ahmet Yılmaz', $ekipler[0]['ad_soyad']);
        $this->assertSame('Söndürme Ekibi', $ekipler[0]['ekip']);
    }

    public function test_eksiklik_ve_dof_onerisi_eklenir(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();

        $component = Livewire::test(TatbikatSayfasi::class)
            ->set('firmaId', $firma->id)
            ->set('yeniEksiklik', 'Alarm sesi depo alanında zayıf duyuldu')
            ->call('eksiklikEkle')
            ->set('yeniDofFaaliyet', 'Depo alanına ek siren montajı')
            ->set('yeniDofSorumlu', 'Bakım Sorumlusu')
            ->call('dofOnerisiEkle');

        $this->assertCount(1, $component->get('eksiklikler'));
        $this->assertCount(1, $component->get('dofOnerileri'));
        $this->assertSame('Depo alanına ek siren montajı', $component->get('dofOnerileri')[0]['faaliyet']);
    }

    public function test_pdf_aksiyonu_kayit_olusturur(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();

        Livewire::test(TatbikatSayfasi::class)
            ->set('firmaId', $firma->id)
            ->call('senaryoSec', 'deprem')
            ->set('tatbikatYeri', 'Üretim sahası')
            ->call('degerlendirmeCevapla', 0, 'evet')
            ->callAction('pdf');

        $t = TatbikatTutanagi::where('firma_id', $firma->id)->firstOrFail();
        $this->assertSame('deprem', $t->senaryo_anahtari);
        $this->assertSame('Üretim sahası', $t->tatbikat_yeri);
        $this->assertSame('evet', $t->degerlendirmeler[0]['cevap']);
    }

    public function test_fotograflar_yuklenir_kaydedilince_saklanir_ve_silinebilir(): void
    {
        Storage::fake('public');
        $firma = Firma::factory()->for($this->uzman)->create();

        $component = Livewire::test(TatbikatSayfasi::class)
            ->set('firmaId', $firma->id)
            ->set('yeniFotograflar', [
                UploadedFile::fake()->image('toplanma-1.jpg'),
                UploadedFile::fake()->image('toplanma-2.jpg'),
            ]);

        $this->assertCount(2, $component->get('yeniFotograflar'));

        $component->call('fotoSil', 0);
        $this->assertCount(1, $component->get('yeniFotograflar'));

        $component->callAction('pdf');

        $t = TatbikatTutanagi::where('firma_id', $firma->id)->firstOrFail();
        $this->assertCount(1, $t->fotograflar);
        Storage::disk('public')->assertExists($t->fotograflar[0]);
    }

    public function test_fotografli_tatbikat_pdfinde_kanit_sayfasi_olusur(): void
    {
        Storage::fake('public');
        $yol = UploadedFile::fake()->image('kanit.jpg')->store('tatbikat-foto', 'public');

        $firma = Firma::factory()->for($this->uzman)->create();
        $t = TatbikatTutanagi::create([
            'firma_id' => $firma->id,
            'senaryo_anahtari' => 'yangin',
            'fotograflar' => [$yol],
        ]);

        $html = view('pdf.tatbikat-tutanagi', ['tutanak' => $t, 'firma' => $firma])->render();

        $this->assertStringContainsString('FOTOĞRAF 1', $html);
        $this->assertStringContainsString($yol, $html);
    }

    public function test_firma_secilmeden_pdf_aksiyonu_gorunmez(): void
    {
        Livewire::test(TatbikatSayfasi::class)->assertActionHidden('pdf');

        $this->assertDatabaseCount('tatbikat_tutanaklari', 0);
    }

    public function test_pdf_uretilir(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();
        $t = TatbikatTutanagi::create([
            'firma_id' => $firma->id,
            'senaryo_anahtari' => 'yangin',
            'senaryo_metni' => 'Test senaryo metni',
            'degerlendirmeler' => [['soru' => 'Test soru', 'cevap' => 'evet']],
        ]);

        $yanit = TatbikatTutanagiUretici::pdf($t);

        $this->assertInstanceOf(StreamedResponse::class, $yanit);
        ob_start();
        $yanit->sendContent();
        $icerik = ob_get_clean();
        $this->assertStringStartsWith('%PDF', $icerik);
    }

    public function test_gecmis_tutanak_silinir(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();
        $t = TatbikatTutanagi::create(['firma_id' => $firma->id]);

        Livewire::test(TatbikatSayfasi::class)
            ->set('firmaId', $firma->id)
            ->call('gecmisSil', $t->id);

        $this->assertDatabaseMissing('tatbikat_tutanaklari', ['id' => $t->id]);
    }

    public function test_baska_uzmanin_firmasi_secilemez(): void
    {
        $baskaUzman = User::factory()->create();
        $baskaFirma = Firma::factory()->for($baskaUzman)->create();

        $firmalar = Livewire::test(TatbikatSayfasi::class)->instance()->firmalar();

        $this->assertArrayNotHasKey($baskaFirma->id, $firmalar);
    }
}
