<?php

namespace Tests\Feature;

use App\Filament\Pages\DofOlustur;
use App\Filament\Pages\OlayKayitlari;
use App\Models\Calisan;
use App\Models\Firma;
use App\Models\IsgProfesyoneli;
use App\Models\OlayKaydi;
use App\Models\User;
use App\Support\OlayKaydiUretici;
use App\Support\PortfoyKarne;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Tests\TestCase;

class OlayKayitlariTest extends TestCase
{
    use RefreshDatabase;

    private User $uzman;

    protected function setUp(): void
    {
        parent::setUp();
        $this->uzman = User::factory()->create();
        $this->actingAs($this->uzman);
    }

    public function test_potansiyel_skor_olasilik_carpi_siddet_ile_hesaplanir(): void
    {
        // orta (3.) × ciddi (4.) = 12 → Yüksek
        $this->assertSame(12, OlayKaydi::skorHesapla('orta', 'ciddi'));
        $this->assertNull(OlayKaydi::skorHesapla('orta', null));

        $o = OlayKaydi::create([
            'firma_id' => Firma::factory()->for($this->uzman)->create()->id,
            'olay_tipi' => 'ramak_kala',
            'olay_ozeti' => 'Yükten düşen parça',
            'olasilik' => 'yuksek',
            'siddet' => 'cok_ciddi',
        ]);

        $this->assertSame(20, $o->potansiyel_skor);
        $this->assertSame('Çok Yüksek', $o->potansiyelSeviye());
        $this->assertStringStartsWith('OLK-'.now()->year.'-', $o->belge_no);
    }

    public function test_bes_neden_zinciri_bos_adimlari_atlar(): void
    {
        $o = new OlayKaydi(['bes_neden' => ['Zemin ıslaktı', '  ', 'Temizlik programı yok', '', '']]);

        $this->assertSame(['Zemin ıslaktı', 'Temizlik programı yok'], $o->nedenZinciri());
    }

    public function test_balik_kilcigi_otomatik_kok_nedenlerden_doldurulur_ve_kaydedilir(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();

        Livewire::test(OlayKayitlari::class)
            ->set('firmaId', $firma->id)
            ->set('olayTipi', 'ramak_kala')
            ->set('olayOzeti', 'Yükseklikten malzeme düştü')
            ->set('besNeden', ['Bariyer yoktu', 'Kontrol yapılmadı', 'Saha denetimi eksik', '', ''])
            ->set('kokNedenKategorileri', ['ekipman_arizasi', 'yonetim_sistemi'])
            ->call('balikKilcigiOtomatik')
            ->callAction('kaydet_pdf');

        $o = OlayKaydi::where('firma_id', $firma->id)->firstOrFail();

        // ekipman_arizasi → makine, yonetim_sistemi → olcum (config balik_kilcigi.kok_neden_6m)
        $this->assertNotEmpty($o->balik_kilcigi['makine']);
        $this->assertContains('Yönetim Sistemi / Denetim Eksikliği', $o->balik_kilcigi['olcum']);
        $this->assertTrue($o->balikKilcigiDoluMu());
    }

    public function test_balik_kilcigi_pdf_uretilir(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();
        $o = OlayKaydi::create([
            'firma_id' => $firma->id,
            'olay_tipi' => 'ramak_kala',
            'olay_ozeti' => 'Forklift ile yaya çarpışması ramak kala',
            'olay_tarihi' => now(),
            'bes_neden' => ['Görüş engeli', 'Ayrılmış yaya yolu yok'],
            'balik_kilcigi' => [
                'insan' => ['Operatör hız yaptı'],
                'metot' => ['Yaya-araç ayrımı planlanmamış'],
                'olcum' => ['Trafik planı güncel değil'],
            ],
        ]);

        $yanit = OlayKaydiUretici::balikKilcigiPdf($o);
        ob_start();
        $yanit->sendContent();
        $this->assertStringStartsWith('%PDF', ob_get_clean());
    }

    public function test_hizli_calisan_secimi_etkilenen_alanlari_doldurur(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();
        $c = Calisan::factory()->for($firma)->create(['ad_soyad' => 'Veli Kaya', 'gorev' => 'Kaynakçı']);

        $component = Livewire::test(OlayKayitlari::class)
            ->set('firmaId', $firma->id)
            ->set('etkilenenHizliSecId', $c->id);

        $this->assertSame('Veli Kaya', $component->get('etkilenenAdSoyad'));
        $this->assertSame('Kaynakçı', $component->get('etkilenenGorev'));
    }

    public function test_olay_tipi_veya_ozet_olmadan_kaydedilemez(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();

        Livewire::test(OlayKayitlari::class)
            ->set('firmaId', $firma->id)
            ->set('olayOzeti', null)
            ->callAction('kaydet_pdf');

        $this->assertDatabaseCount('olay_kayitlari', 0);
    }

    public function test_kaydet_pdf_aksiyonu_kayit_olusturur_ve_kase_snapshotlanir(): void
    {
        $igu = IsgProfesyoneli::factory()->for($this->uzman)->create(['kase_gorseli' => 'kase/x.png']);
        $firma = Firma::factory()->for($this->uzman)->create(['igu_id' => $igu->id]);

        Livewire::test(OlayKayitlari::class)
            ->set('firmaId', $firma->id)
            ->set('olayTipi', 'ramak_kala')
            ->set('olayOzeti', 'Vinç yükü sallandı, çalışanın yanından geçti.')
            ->set('olasilik', 'orta')
            ->set('siddet', 'cok_ciddi')
            ->set('besNeden', ['Yük düzgün bağlanmamış', 'Sapan kontrolü yapılmıyor', '', '', ''])
            ->set('kokNedenKategorileri', ['talimat_eksikligi'])
            ->set('duzelticiFaaliyet', 'Kaldırma planı ve sapan kontrol formu oluşturulacak.')
            ->callAction('kaydet_pdf');

        $o = OlayKaydi::where('firma_id', $firma->id)->firstOrFail();
        $this->assertSame('ramak_kala', $o->olay_tipi);
        $this->assertSame(['talimat_eksikligi'], $o->kok_neden_kategorileri);
        $this->assertSame(15, $o->potansiyel_skor);
        $this->assertSame('kase/x.png', $o->rapor_hazirlayan_kase);
    }

    public function test_dofe_aktar_oturuma_madde_yazip_yonlendirir(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();
        $o = OlayKaydi::create([
            'firma_id' => $firma->id,
            'olay_tipi' => 'tehlikeli_durum',
            'olay_ozeti' => 'Korkuluk eksik.',
            'olasilik' => 'orta',
            'siddet' => 'ciddi',
            'kok_neden' => 'Montaj sonrası kontrol yapılmamış.',
            'duzeltici_faaliyet' => 'Korkuluk tamamlanacak.',
        ]);

        Livewire::test(OlayKayitlari::class)
            ->set('firmaId', $firma->id)
            ->call('dofeAktar', $o->id)
            ->assertRedirect(DofOlustur::getUrl());

        $aktarim = session('dof_aktarim');
        $this->assertSame($firma->id, $aktarim['firma_id']);
        $this->assertStringContainsString('Korkuluk tamamlanacak.', $aktarim['maddeler'][0]['oneri']);
        $this->assertSame('yuksek', $aktarim['maddeler'][0]['oncelik']);
    }

    public function test_gecmis_liste_tipe_gore_filtrelenir(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();
        OlayKaydi::create(['firma_id' => $firma->id, 'olay_tipi' => 'ramak_kala', 'olay_ozeti' => 'a', 'olay_tarihi' => now()]);
        OlayKaydi::create(['firma_id' => $firma->id, 'olay_tipi' => 'maddi_hasar', 'olay_ozeti' => 'b', 'olay_tarihi' => now()]);

        $component = Livewire::test(OlayKayitlari::class)
            ->set('firmaId', $firma->id)
            ->set('tipFiltre', 'maddi_hasar');

        $this->assertCount(1, $component->instance()->gecmisKayitlar);
    }

    public function test_defter_excel_uretilir(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();
        OlayKaydi::create(['firma_id' => $firma->id, 'olay_tipi' => 'ramak_kala', 'olay_ozeti' => 'a', 'olay_tarihi' => now()]);

        $yanit = OlayKaydiUretici::defterExcel($firma);

        $this->assertInstanceOf(StreamedResponse::class, $yanit);
        ob_start();
        $yanit->sendContent();
        $icerik = ob_get_clean();
        $this->assertStringContainsString('PK', substr($icerik, 0, 2));
    }

    public function test_pdf_uretilir(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();
        $o = OlayKaydi::create([
            'firma_id' => $firma->id,
            'olay_tipi' => 'is_kazasi',
            'olay_ozeti' => 'El sıkıştı.',
            'bes_neden' => ['Koruyucu yok', 'Bakım gecikmiş'],
        ]);

        $yanit = OlayKaydiUretici::pdf($o);

        ob_start();
        $yanit->sendContent();
        $icerik = ob_get_clean();
        $this->assertStringStartsWith('%PDF', $icerik);
    }

    public function test_kontrol_merkezi_kriteri_olay_kaydi_varken_karsilanir(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();

        $this->assertFalse(PortfoyKarne::firmaKriterKarsilarMi($firma, 'olay_ramak_kala_kaydi'));

        OlayKaydi::create(['firma_id' => $firma->id, 'olay_tipi' => 'ramak_kala', 'olay_ozeti' => 'a']);

        $this->assertTrue(PortfoyKarne::firmaKriterKarsilarMi($firma->fresh(), 'olay_ramak_kala_kaydi'));
    }
}
