<?php

namespace Tests\Feature;

use App\Filament\Pages\EgitimKatilim;
use App\Filament\Pages\EgitimSorulari;
use App\Filament\Pages\IsbasiEgitim;
use App\Filament\Pages\SertifikaOlustur;
use App\Filament\Pages\UzaktanEgitimAtama;
use App\Filament\Pages\UzaktanEgitimFirmaAyarlari;
use App\Filament\Pages\UzaktanEgitimSorulari;
use App\Models\Calisan;
use App\Models\EgitimAtamasi;
use App\Models\EgitimPaketi;
use App\Models\Firma;
use App\Models\SoruBankasiSorusu;
use App\Models\User;
use App\Support\KomutaFirma;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

class Grup4IcerikVeMantikTest extends TestCase
{
    use RefreshDatabase;

    private User $uzman;

    private Firma $firma;

    protected function setUp(): void
    {
        parent::setUp();
        $this->uzman = User::factory()->create();
        $this->actingAs($this->uzman);
        $this->firma = Firma::factory()->for($this->uzman)->create([
            'unvan' => 'Grup4 Metal A.Ş.',
            'tehlike_sinifi' => 'cok_tehlikeli',
        ]);
    }

    public function test_giris_bilgileri_varsayilan_indirmede_mevcut_sifre_bozulmaz(): void
    {
        $calisan = Calisan::factory()->for($this->firma)->create([
            'eposta' => 'ali@grup4.test',
            'sifre' => 'ESKI123',
            'sifre_belirlendi_at' => null,
        ]);
        $eskiHash = $calisan->fresh()->sifre;

        $paket = EgitimPaketi::create(['user_id' => $this->uzman->id, 'ad' => 'Temel Paket', 'aktif' => true]);
        EgitimAtamasi::create([
            'calisan_id' => $calisan->id,
            'egitim_paketi_id' => $paket->id,
            'atayan_user_id' => $this->uzman->id,
            'durum' => 'atandi',
            'atandi_at' => now(),
        ]);

        // Varsayılan (sifreSifirla = false): mevcut geçici şifre bozulmaz
        $sayfa = Livewire::test(UzaktanEgitimAtama::class)->set('firmaId', $this->firma->id);
        $yanit = $sayfa->instance()->girisBilgileriIndir('excel', false);
        $this->assertNotNull($yanit);
        $this->assertSame($eskiHash, $calisan->fresh()->sifre);

        // Açık sıfırlama (sifreSifirla = true): yeni geçici şifre üretilir
        $sayfa->instance()->girisBilgileriIndir('excel', true);
        $this->assertNotSame($eskiHash, $calisan->fresh()->sifre);
        $this->assertFalse(Hash::check('ESKI123', $calisan->fresh()->sifre));
    }

    public function test_isbasi_en_az_2_saat_zorunlu_ve_temel_egitim_asgari_sure_uyarir(): void
    {
        // İşbaşı eğitimi < 2 saat kaydedilmez (Yön. m.7)
        Livewire::test(IsbasiEgitim::class)
            ->set('firmaId', $this->firma->id)
            ->set('calisanAdSoyad', 'Hasan Yılmaz')
            ->set('sureSaat', 1)
            ->callAction('kaydet');

        $this->assertDatabaseCount('isbasi_egitim_tutanaklari', 0);

        Livewire::test(IsbasiEgitim::class)
            ->set('firmaId', $this->firma->id)
            ->set('calisanAdSoyad', 'Hasan Yılmaz')
            ->set('sureSaat', 2)
            ->callAction('kaydet');

        $this->assertDatabaseCount('isbasi_egitim_tutanaklari', 1);

        // Çok tehlikeli işyerinde temel eğitim < 16 saat için uyarı üretir ama kayda izin verir (Seçenek A)
        $ek = Livewire::test(EgitimKatilim::class)
            ->set('firmaId', $this->firma->id)
            ->set('baslikAnahtari', 'genel')
            ->set('dersSaati', 8);

        $this->assertSame(16, $ek->instance()->asgariDersSaati);
        $this->assertStringContainsString('16 saat', (string) $ek->instance()->sureYasalUyarisi);
    }

    public function test_soru_havuzu_koprusu_ve_egitim_komuta_firma_paylasimi(): void
    {
        KomutaFirma::sec($this->firma->id);

        // Eğitim sayfaları KomutaFirma'yı paylaşır
        Livewire::test(IsbasiEgitim::class)->assertSet('firmaId', $this->firma->id);
        Livewire::test(SertifikaOlustur::class)->assertSet('firmaId', $this->firma->id);
        Livewire::test(UzaktanEgitimFirmaAyarlari::class)->assertSet('firmaId', $this->firma->id);

        // EgitimSorulari -> SoruBankasi aktarımı (tekrar edenleri atlar)
        Livewire::test(EgitimSorulari::class)
            ->assertSet('firmaId', $this->firma->id)
            ->set('sorular', [
                ['soru' => 'Bareti ne zaman takmalıyız?', 'secenekler' => ['Her zaman sahada', 'Sadece kışın', 'İsteğe bağlı', 'Hiç'], 'dogru_index' => 0],
            ])
            ->call('bankayaKaydet')
            ->call('bankayaKaydet');

        $this->assertDatabaseCount('soru_bankasi_sorulari', 1);

        // Onaylı soru -> UzaktanEgitimSorulari paketine çekme
        SoruBankasiSorusu::first()->update(['durum' => 'onaylandi']);
        $paket = EgitimPaketi::create(['user_id' => $this->uzman->id, 'ad' => 'LMS Paket', 'aktif' => true]);

        Livewire::test(UzaktanEgitimSorulari::class, ['paketId' => $paket->id])
            ->callTableAction('bankadanCek', data: ['konu' => null, 'kullanim' => 'ortak', 'adet' => 5]);

        $this->assertSame(1, $paket->sorular()->count());
        $this->assertSame('Bareti ne zaman takmalıyız?', $paket->sorular()->first()->soru);
    }
}
