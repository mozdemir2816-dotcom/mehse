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

    public function test_isgsuite_alanlari_kaydedilir_ve_imza_sahipleri_firmadan_gelir(): void
    {
        $hekim = IsgProfesyoneli::factory()->for($this->uzman)->create(['ad_soyad' => 'Dr. Ayşe Demir']);
        $firma = Firma::factory()->for($this->uzman)->create([
            'isyeri_hekimi_id' => $hekim->id,
            'isveren_vekili' => 'Mehmet Yılmaz',
        ]);

        $component = Livewire::test(OlayKayitlari::class)->set('firmaId', $firma->id);

        $this->assertSame('Dr. Ayşe Demir', $component->get('isyeriHekimi'));
        $this->assertSame('Mehmet Yılmaz', $component->get('isverenVekili'));

        $component
            ->set('olayTipi', 'is_kazasi')
            ->set('durum', 'incelemede')
            ->set('bolum', 'Üretim')
            ->set('alan', 'Kaynakhane')
            ->set('yapilanIs', 'Profil kesimi')
            ->set('ekipman', 'Avuç taşlama')
            ->set('kimyasal', 'Kesme yağı')
            ->set('siniflandirma', 'el_aleti')
            ->set('olayOzeti', 'Taşlama sırasında disk kırıldı, operatörün eli kesildi.')
            ->set('olayDetayi', 'Koruyucu kapak sökülmüş taşlama ile kesim yapılırken disk parçalandı.')
            ->set('etkiler', ['yaralanma', 'tibbi_mudahale'])
            ->set('riskAnalizinde', 'kismen')
            ->set('acilDurumIliskisi', 'ilk_yardim')
            ->set('kazaTuru', 'kesilme')
            ->set('yaralanmaTuru', 'Sol el kesik')
            ->set('mudahaleDetayi', 'İlk yardım + hastaneye sevk')
            ->set('sistemselEksiklik', 'Ekipman ön kontrol formu yok')
            ->callAction('kaydet');

        $o = OlayKaydi::where('firma_id', $firma->id)->sole();
        $this->assertSame('incelemede', $o->durum);
        $this->assertSame('Kaynakhane', $o->alan);
        $this->assertSame('El aleti / elektrikli el aleti', $o->siniflandirmaEtiketi());
        $this->assertTrue($o->etkiVar('tibbi_mudahale'));
        $this->assertFalse($o->etkiVar('ekipman_hasari'));
        $this->assertSame('kesilme', $o->kaza_turu);
        $this->assertSame('Dr. Ayşe Demir', $o->isyeri_hekimi);
        $this->assertSame('Ekipman ön kontrol formu yok', $o->sistemsel_eksiklik);
    }

    public function test_kisa_ozet_ve_kisa_detay_reddedilir(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();

        Livewire::test(OlayKayitlari::class)
            ->set('firmaId', $firma->id)
            ->set('olayOzeti', 'Kısa özet')
            ->callAction('kaydet');
        $this->assertDatabaseCount('olay_kayitlari', 0);

        Livewire::test(OlayKayitlari::class)
            ->set('firmaId', $firma->id)
            ->set('olayOzeti', 'Yeterince uzun bir olay özeti yazıldı.')
            ->set('olayDetayi', 'Çok kısa')
            ->callAction('kaydet');
        $this->assertDatabaseCount('olay_kayitlari', 0);
    }

    public function test_duzenle_ayni_kaydi_gunceller_ve_fotograflari_korur(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();
        $o = OlayKaydi::create([
            'firma_id' => $firma->id,
            'olay_tipi' => 'ramak_kala',
            'olay_ozeti' => 'Raftan koli düştü, kimse yaralanmadı.',
            'olay_tarihi' => '2026-09-20',
            'bes_neden' => ['Raf aşırı yüklü'],
            'balik_kilcigi' => ['insan' => ['Dikkatsiz istifleme']],
            'fotograflar' => ['olay-kaydi-foto/eski.jpg'],
        ]);
        $belgeNo = $o->belge_no;

        $component = Livewire::test(OlayKayitlari::class)
            ->set('firmaId', $firma->id)
            ->call('duzenle', $o->id);

        $this->assertSame($o->id, $component->get('duzenlenenId'));
        $this->assertSame('Raftan koli düştü, kimse yaralanmadı.', $component->get('olayOzeti'));
        $this->assertSame('2026-09-20', $component->get('olayTarihi'));
        $this->assertSame(['Raf aşırı yüklü', '', '', '', ''], $component->get('besNeden'));
        $this->assertSame(['Dikkatsiz istifleme'], $component->get('balikKilcigi')['insan']);

        $component->set('durum', 'kapandi')->callAction('kaydet');

        $this->assertDatabaseCount('olay_kayitlari', 1);
        $o->refresh();
        $this->assertSame('kapandi', $o->durum);
        $this->assertSame($belgeNo, $o->belge_no);
        $this->assertSame(['olay-kaydi-foto/eski.jpg'], $o->fotograflar);

        // "Yeni Kayıt" düzenlemeyi bırakır; sonraki kayıt ayrı satır olur.
        $component->call('yeniKayit')
            ->set('olayOzeti', 'İkinci olay — merdivende kayma yaşandı.')
            ->callAction('kaydet');
        $this->assertDatabaseCount('olay_kayitlari', 2);
    }

    public function test_eksik_ve_otomatik_uyarilar_sgk_son_gunu_hafta_sonunu_atlar(): void
    {
        // 2026-10-01 Perşembe → +3 iş günü = 2026-10-06 Salı
        $o = new OlayKaydi([
            'olay_tipi' => 'is_kazasi',
            'olay_tarihi' => '2026-10-01',
            'risk_analizinde' => 'hayir',
        ]);

        $this->assertSame('06.10.2026', $o->sgkSonTarih()->format('d.m.Y'));
        $this->assertCount(5, $o->eksikUyarilari());
        $this->assertCount(2, $o->otomatikUyarilar());
        $this->assertStringContainsString('06.10.2026', $o->otomatikUyarilar()[0]);

        $o->fill(['sgk_bildirimi_yapildi' => true, 'kolluk_bildirimi_yapildi' => true, 'duzeltici_faaliyet' => 'x', 'kok_neden' => 'y', 'kok_neden_kategorileri' => ['egitim_eksikligi'], 'risk_analizinde' => 'evet']);
        $this->assertSame([], $o->eksikUyarilari());
        $this->assertSame([], $o->otomatikUyarilar());

        // Ramak kalada SGK/kolluk uyarısı yok.
        $this->assertSame([], (new OlayKaydi(['olay_tipi' => 'ramak_kala']))->otomatikUyarilar());
    }

    public function test_genel_degerlendirme_formdan_olusturulur(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();

        $component = Livewire::test(OlayKayitlari::class)
            ->set('firmaId', $firma->id)
            ->set('olayTipi', 'is_kazasi')
            ->set('olayTarihi', '2026-09-22')
            ->set('olayYeri', 'Depo')
            ->set('siniflandirma', 'malzeme_dusmesi')
            ->set('etkiler', ['yaralanma'])
            ->set('besNeden', ['Raf sabitlenmemiş', 'Montaj kontrolü yok', '', '', ''])
            ->call('genelDegerlendirmeOlustur');

        $metin = $component->get('genelDegerlendirme');
        $this->assertStringContainsString('22.09.2026', $metin);
        $this->assertStringContainsString('Malzeme / cisim düşmesi', $metin);
        $this->assertStringContainsString('Montaj kontrolü yok', $metin);
        $this->assertStringContainsString('SGK', $metin);
    }

    public function test_olaydan_aktarilan_dof_kaydedilince_olaya_baglanir(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();
        $o = OlayKaydi::create([
            'firma_id' => $firma->id,
            'olay_tipi' => 'tehlikeli_durum',
            'olay_ozeti' => 'Korkuluk eksik, düşme tehlikesi var.',
            'duzeltici_faaliyet' => 'Korkuluk tamamlanacak.',
        ]);

        Livewire::test(OlayKayitlari::class)
            ->set('firmaId', $firma->id)
            ->call('dofeAktar', $o->id);

        Livewire::test(DofOlustur::class)
            ->assertSet('kaynakOlayKaydiId', $o->id)
            ->callAction('pdf', ['imzali' => '1']);

        $this->assertNotNull($o->fresh()->dof_raporu_id);
    }

    public function test_dolu_is_kazasi_pdf_ve_genis_defter_excel_uretilir(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();
        $o = OlayKaydi::create([
            'firma_id' => $firma->id,
            'olay_tipi' => 'is_kazasi',
            'durum' => 'acik',
            'olay_tarihi' => now(),
            'olay_ozeti' => 'Taşlama diski kırıldı, operatör yaralandı.',
            'olay_detayi' => 'Koruyucu kapak sökülmüş taşlama ile kesim yapılırken disk parçalandı.',
            'bolum' => 'Üretim',
            'siniflandirma' => 'el_aleti',
            'etkiler' => ['yaralanma', 'is_goremezlik'],
            'kaza_turu' => 'kesilme',
            'risk_analizinde' => 'hayir',
            'acil_durum_iliskisi' => 'ilk_yardim',
            'genel_degerlendirme' => 'Değerlendirme metni.',
            'isyeri_hekimi' => 'Dr. X',
            'isveren_vekili' => 'Y',
        ]);

        ob_start();
        OlayKaydiUretici::pdf($o, imzali: false)->sendContent();
        $this->assertStringStartsWith('%PDF', ob_get_clean());

        $yanit = OlayKaydiUretici::defterExcel($firma);
        $tmp = tempnam(sys_get_temp_dir(), 'olt').'.xlsx';
        ob_start();
        $yanit->sendContent();
        file_put_contents($tmp, ob_get_clean());

        $sayfa = \PhpOffice\PhpSpreadsheet\IOFactory::load($tmp)->getActiveSheet();
        @unlink($tmp);
        $baslik = $sayfa->rangeToArray('A1:AG1')[0];
        $this->assertSame('Durum', $baslik[2]);
        $this->assertSame('İşveren Vekili', $baslik[32]);
        $this->assertSame('Açık', $sayfa->getCell('C2')->getValue());
        $this->assertSame('Üretim', $sayfa->getCell('G2')->getValue());
    }

    public function test_kontrol_merkezi_kriteri_olay_kaydi_varken_karsilanir(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();

        $this->assertFalse(PortfoyKarne::firmaKriterKarsilarMi($firma, 'olay_ramak_kala_kaydi'));

        OlayKaydi::create(['firma_id' => $firma->id, 'olay_tipi' => 'ramak_kala', 'olay_ozeti' => 'a']);

        $this->assertTrue(PortfoyKarne::firmaKriterKarsilarMi($firma->fresh(), 'olay_ramak_kala_kaydi'));
    }

    public function test_olay_dof_maddesi_eklenir_ve_ayni_dof_raporunda_birikir(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();
        $o = OlayKaydi::create([
            'firma_id' => $firma->id,
            'olay_tipi' => 'ramak_kala',
            'olay_ozeti' => 'Forklift yayaya çok yakın geçti, çarpma olmadı.',
            'olay_yeri' => 'Depo',
        ]);

        $component = Livewire::test(OlayKayitlari::class)
            ->set('firmaId', $firma->id)
            ->call('duzenle', $o->id)
            // Kısa tespit reddedilir
            ->set('dofTespit', 'Kısa')
            ->call('olayDofEkle');
        $this->assertNull($o->fresh()->dof_raporu_id);

        $component
            ->set('dofTespit', 'Yaya ve forklift yolları ayrılmamış')
            ->set('dofDuzeltici', 'Zemin çizgisi ve bariyer')
            ->set('dofOnleyici', 'Trafik planı hazırlanacak')
            ->set('dofSorumlu', 'Depo şefi')
            ->set('dofTermin', '2026-10-15')
            ->set('dofOncelik', 'yuksek')
            ->call('olayDofEkle')
            ->set('dofTespit', 'Forklift operatörü hız sınırına uymuyor')
            ->call('olayDofEkle');

        $dof = $o->fresh()->dofRaporu;
        $this->assertNotNull($dof);
        $this->assertCount(2, $dof->maddeler);
        $this->assertStringStartsWith('['.$o->belge_no.']', $dof->maddeler[0]['tespit']);
        $this->assertStringContainsString('Önleyici: Trafik planı hazırlanacak', $dof->maddeler[0]['oneri']);
        $this->assertSame('yuksek', $dof->maddeler[0]['oncelik']);
        $this->assertSame('acik', $dof->maddeler[1]['durum']);
        $this->assertCount(2, $component->instance()->olayDofMaddeleri);
        $this->assertSame(1, \App\Models\DofRaporu::count());

        ob_start();
        OlayKaydiUretici::pdf($o->fresh())->sendContent();
        $this->assertStringStartsWith('%PDF', ob_get_clean());
    }

    public function test_kaydedilmemis_olaya_dof_eklenemez(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();

        Livewire::test(OlayKayitlari::class)
            ->set('firmaId', $firma->id)
            ->set('dofTespit', 'Yaya ve forklift yolları ayrılmamış')
            ->call('olayDofEkle');

        $this->assertSame(0, \App\Models\DofRaporu::count());
    }

    public function test_kayit_kapatilir_ve_arama_filtreler(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();
        $a = OlayKaydi::create(['firma_id' => $firma->id, 'olay_tipi' => 'ramak_kala', 'olay_ozeti' => 'Merdivende kayma', 'bolum' => 'Üretim']);
        OlayKaydi::create(['firma_id' => $firma->id, 'olay_tipi' => 'ramak_kala', 'olay_ozeti' => 'Raftan koli düştü', 'bolum' => 'Depo']);

        $component = Livewire::test(OlayKayitlari::class)
            ->set('firmaId', $firma->id)
            ->call('kaydiKapat', $a->id);

        $this->assertSame('kapandi', $a->fresh()->durum);

        $component->set('arama', 'depo');
        $this->assertCount(1, $component->instance()->gecmisKayitlar);
        $component->set('arama', $a->belge_no);
        $this->assertSame($a->id, $component->instance()->gecmisKayitlar->first()->id);
    }

    public function test_rapor_basligi_olay_tipine_gore(): void
    {
        $this->assertSame('RAMAK KALA OLAYI RAPORU', (new OlayKaydi(['olay_tipi' => 'ramak_kala']))->raporBasligi());
        $this->assertSame('İŞ KAZASI RAPORU', (new OlayKaydi(['olay_tipi' => 'is_kazasi']))->raporBasligi());
        $this->assertSame('OLAY KAYIT VE İNCELEME FORMU', (new OlayKaydi(['olay_tipi' => 'maddi_hasar']))->raporBasligi());
    }
}
