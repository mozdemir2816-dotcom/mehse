<?php

namespace Tests\Feature;

use App\Filament\Pages\SahaDenetimi as SahaSayfasi;
use App\Models\Calisan;
use App\Models\Firma;
use App\Models\IsgProfesyoneli;
use App\Models\SahaDenetimi;
use App\Models\SahaDenetimiOzelMadde;
use App\Models\User;
use App\Support\EkipmanKatalogu;
use App\Support\HaftalikEkipmanKontrolUretici;
use App\Support\SahaDenetimiUretici;
use App\Support\SahaFineKinneyKutuphanesi;
use App\Support\SahaSektorDefteri;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Tests\TestCase;

class SahaDenetimiTest extends TestCase
{
    use RefreshDatabase;

    private User $uzman;

    protected function setUp(): void
    {
        parent::setUp();
        $this->uzman = User::factory()->create();
        $this->actingAs($this->uzman);
    }

    public function test_kontrol_listesi_41_madde_icerir(): void
    {
        $toplam = collect(config('isg.saha_denetimi.kategoriler'))->sum(fn ($k) => count($k['maddeler']));

        $this->assertSame(41, $toplam);
    }

    public function test_master_defter_40_sektor_ve_her_sektorde_40_madde_icerir(): void
    {
        $sektorler = SahaSektorDefteri::sektorler();

        $this->assertCount(40, $sektorler);
        $this->assertArrayHasKey('insaat', $sektorler);
        $this->assertArrayHasKey('kagit_ve_matbaa', $sektorler);

        foreach (array_keys($sektorler) as $anahtar) {
            $maddeler = SahaSektorDefteri::maddeler($anahtar);
            $this->assertCount(40, $maddeler, $anahtar);
            $this->assertSame('SD1', $maddeler[0]['kod']);
            $this->assertCount(40, array_unique(array_column($maddeler, 'kod')), $anahtar);
            $this->assertNotContains('', array_column($maddeler, 'dayanak'), $anahtar);
            $this->assertNotContains('', array_column($maddeler, 'oneri'), $anahtar);
        }

        $this->assertSame([], SahaSektorDefteri::maddeler('yok'));
    }

    public function test_sektore_ozgu_kontrol_secilince_40_madde_eklenir_taslakla_pdfe_tasinir(): void
    {
        Storage::fake('public');
        $firma = Firma::factory()->for($this->uzman)->create();

        $sayfa = Livewire::test(SahaSayfasi::class)->set('firmaId', $firma->id);
        $this->assertArrayNotHasKey('sektor_defteri', $sayfa->instance()->kategoriler());

        $sayfa->set('defterSektoru', 'insaat');
        $kategori = $sayfa->instance()->kategoriler()['sektor_defteri'];
        $this->assertSame('Sektöre Özgü Kontrol — İnşaat', $kategori['ad']);
        $this->assertCount(40, $kategori['maddeler']);
        // Sabit 41 madde yerinde, özel olmayan kategoriler bozulmadı
        $this->assertArrayHasKey('yangin', $sayfa->instance()->kategoriler());

        $sayfa->assertSee('Dayanak: Risk Değerlendirmesi Yönetmeliği')
            ->call('cevapVer', 'SD1', 'uygun_degil')
            ->call('oneriyiAciklamayaEkle', 'SD1')
            ->call('oneriyiAciklamayaEkle', 'SD1') // ikinci çağrı tekrar eklemez
            ->call('cevapVer', 'SD2', 'uygun');

        $aciklama = $sayfa->get('cevaplar')['SD1']['aciklama'];
        $this->assertSame(1, substr_count($aciklama, 'Öneri: Risk değerlendirmesini düşme ve malzeme çarpması dahil güncelleyin'));

        // Taslak: sektör seçimi ve cevaplar geri yüklenir
        $sayfa->call('taslakKaydet');
        $yeni = Livewire::test(SahaSayfasi::class)->set('firmaId', $firma->id);
        $yeni->assertSet('defterSektoru', 'insaat');
        $this->assertSame('uygun_degil', $yeni->get('cevaplar')['SD1']['sonuc']);
        $this->assertSame('uygun', $yeni->get('cevaplar')['SD2']['sonuc']);

        // Tamamla → kayıtta dayanak/öneri anlık görüntüsü, PDF'te görünür
        $yeni->callAction('pdf');
        $d = SahaDenetimi::where('firma_id', $firma->id)->where('durum', 'tamamlandi')->firstOrFail();
        $sd1 = collect($d->cevaplar)->firstWhere('kod', 'SD1');
        $this->assertSame('Risk Değerlendirmesi Yönetmeliği', $sd1['dayanak']);
        $this->assertSame('insaat', $sd1['defter_sektoru']);
        $this->assertCount(40, collect($d->cevaplar)->where('kategori_ad', 'Sektöre Özgü Kontrol — İnşaat'));

        $html = view('pdf.saha-denetimi', ['denetim' => $d, 'firma' => $firma])->render();
        $this->assertStringContainsString('Dayanak: Risk Değerlendirmesi Yönetmeliği', $html);
        $this->assertStringContainsString('SEKTÖRE ÖZGÜ KONTROL', $html);
    }

    public function test_fine_kinney_kutuphanesi_40_sektor_ve_her_sektorde_20_madde_icerir(): void
    {
        $sektorler = SahaFineKinneyKutuphanesi::sektorler();
        $olcek = config('isg.risk_fine_kinney');

        $this->assertCount(40, $sektorler);
        $this->assertSame('İnşaat ve Yapı İşleri', $sektorler['insaat']);
        $this->assertSame('Havalimanı Yer Hizmetleri (Apron)', $sektorler['havalimani_apron']);

        foreach (array_keys($sektorler) as $anahtar) {
            $maddeler = SahaFineKinneyKutuphanesi::maddeler($anahtar);
            $this->assertCount(20, $maddeler, $anahtar);
            $this->assertSame('FK1', $maddeler[0]['kod']);
            $this->assertNotContains('', array_column($maddeler, 'oneri'), $anahtar);

            // Varsayılan puanlar sistem ölçeğinde (şiddette 100 yok)
            foreach ($maddeler as $m) {
                $this->assertArrayHasKey((string) $m['fk']['olasilik'], $olcek['olasilik'], $anahtar);
                $this->assertArrayHasKey((string) $m['fk']['frekans'], $olcek['frekans'], $anahtar);
                $this->assertArrayHasKey((string) $m['fk']['siddet'], $olcek['siddet'], $anahtar);
            }
        }

        $this->assertSame([], SahaFineKinneyKutuphanesi::maddeler('yok'));
    }

    public function test_fine_kinney_sektoru_nace_kodundan_secilir(): void
    {
        $this->assertSame('insaat', SahaFineKinneyKutuphanesi::naceSektoru('41.00.01'));
        $this->assertSame('plastik', SahaFineKinneyKutuphanesi::naceSektoru('22.12.01'));
        $this->assertSame('ambalaj', SahaFineKinneyKutuphanesi::naceSektoru('17.21.01'));
        $this->assertSame('tersane', SahaFineKinneyKutuphanesi::naceSektoru('30.11'));
        $this->assertSame('saglik', SahaFineKinneyKutuphanesi::naceSektoru('86.10.05'));
        $this->assertSame('tarim', SahaFineKinneyKutuphanesi::naceSektoru('02.10 01'));
        $this->assertNull(SahaFineKinneyKutuphanesi::naceSektoru(null));
        $this->assertNull(SahaFineKinneyKutuphanesi::naceSektoru('99.00'));

        $firma = Firma::factory()->for($this->uzman)->create(['nace_kodu' => '29.32.21']);
        $sayfa = Livewire::test(SahaSayfasi::class)->set('firmaId', $firma->id)->assertSet('fkSektoru', 'otomotiv');
        $this->assertCount(20, $sayfa->instance()->kategoriler()['fine_kinney']['maddeler']);

        $sayfa->set('fkSektoru', '');
        $sayfa->assertSet('fkSektoru', null);
        $this->assertArrayNotHasKey('fine_kinney', $sayfa->instance()->kategoriler());
    }

    public function test_fine_kinney_maddesi_uygunsuzda_varsayilan_puan_gelir_taslakta_korunur_pdfe_yazilir(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create(['nace_kodu' => '41.00.01']);

        // FK1 (inşaat): yüksekte çalışma — kaynakta O6 F6 Ş40 (tek ölüm) → sistemde Ş30
        $sayfa = Livewire::test(SahaSayfasi::class)
            ->set('firmaId', $firma->id)
            ->assertSee('Yüksekte Çalışma')
            ->call('cevapVer', 'FK1', 'uygun_degil');

        $c = $sayfa->get('cevaplar')['FK1'];
        $this->assertSame(['6', '6', '30'], [$c['fk_olasilik'], $c['fk_frekans'], $c['fk_siddet']]);
        $this->assertSame(1080.0, $sayfa->instance()->fkPuani('FK1')['puan']);

        // Kullanıcı puanı düşürür → risk seviyesi puandan türetilir (6×3×7=126 → orta)
        $sayfa->set('cevaplar.FK1.fk_frekans', '3')
            ->set('cevaplar.FK1.fk_siddet', '7')
            ->set('cevaplar.FK1.aciklama', 'Korkuluk eksik')
            ->call('taslakKaydet');

        $yeni = Livewire::test(SahaSayfasi::class)->set('firmaId', $firma->id)->assertSet('fkSektoru', 'insaat');
        $c = $yeni->get('cevaplar')['FK1'];
        $this->assertSame(['6', '3', '7'], [$c['fk_olasilik'], $c['fk_frekans'], $c['fk_siddet']]);
        $this->assertNull($c['risk']); // türetilmiş risk taslakta sabitlenmez

        $yeni->callAction('pdf');
        $d = SahaDenetimi::where('firma_id', $firma->id)->where('durum', 'tamamlandi')->firstOrFail();
        $fk1 = collect($d->cevaplar)->firstWhere('kod', 'FK1');
        $this->assertSame(126.0, (float) $fk1['fk_puan']);
        $this->assertSame('Önemli Risk', $fk1['fk_duzey']);
        $this->assertSame('orta', $fk1['risk']);
        $this->assertSame('insaat', $fk1['fk_sektoru']);
        $this->assertNull(collect($d->cevaplar)->firstWhere('kod', 'FK2')['fk_puan']);

        $html = view('pdf.saha-denetimi', ['denetim' => $d, 'firma' => $firma])->render();
        $this->assertStringContainsString('O 6 × F 3 × Ş 7 = <strong>126</strong> (Önemli Risk)', $html);
        $this->assertStringContainsString('FİNE-KİNNEY SAHA GÖZETİMİ', $html);
    }

    public function test_uygunsuz_maddede_risk_sorumlu_termin_kaydedilir_taslakta_korunur_pdfte_gorunur(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();

        $sayfa = Livewire::test(SahaSayfasi::class)
            ->set('firmaId', $firma->id)
            ->call('cevapVer', '1.1', 'uygun_degil')
            ->set('cevaplar.1_1.aciklama', 'Tüpün etiketi yok')
            ->set('cevaplar.1_1.risk', 'yuksek')
            ->set('cevaplar.1_1.sorumlu', 'Bakım şefi')
            ->set('cevaplar.1_1.termin', '2026-11-05')
            // Uygun maddede girilen risk/sorumlu/termin kayda taşınmaz
            ->call('cevapVer', '1.2', 'uygun')
            ->set('cevaplar.1_2.risk', 'orta')
            ->set('cevaplar.1_2.sorumlu', 'Gereksiz')
            ->call('taslakKaydet');

        $taslak = SahaDenetimi::where('firma_id', $firma->id)->where('durum', 'taslak')->firstOrFail();
        $m11 = collect($taslak->cevaplar)->firstWhere('kod', '1.1');
        $this->assertSame(['yuksek', 'Bakım şefi', '2026-11-05'], [$m11['risk'], $m11['sorumlu'], $m11['termin']]);
        $m12 = collect($taslak->cevaplar)->firstWhere('kod', '1.2');
        $this->assertSame([null, null, null], [$m12['risk'], $m12['sorumlu'], $m12['termin']]);

        // Taslaktan yüklenince alanlar geri gelir
        $yeni = Livewire::test(SahaSayfasi::class)->set('firmaId', $firma->id);
        $this->assertSame('yuksek', $yeni->get('cevaplar')['1_1']['risk']);
        $this->assertSame('Bakım şefi', $yeni->get('cevaplar')['1_1']['sorumlu']);
        $this->assertSame('2026-11-05', $yeni->get('cevaplar')['1_1']['termin']);

        $yeni->callAction('pdf');
        $d = SahaDenetimi::where('firma_id', $firma->id)->where('durum', 'tamamlandi')->firstOrFail();
        $html = view('pdf.saha-denetimi', ['denetim' => $d, 'firma' => $firma])->render();
        $this->assertStringContainsString('Risk: Yüksek', $html);
        $this->assertStringContainsString('Sorumlu: Bakım şefi', $html);
        $this->assertStringContainsString('Termin: 05.11.2026', $html);
        $this->assertStringNotContainsString('Gereksiz', $html);

        // Eski kayıtlarda (risk/sorumlu/termin anahtarı yok) PDF hatasız çıkar
        $eski = SahaDenetimi::create(['firma_id' => $firma->id, 'revizyon' => 9, 'cevaplar' => [
            ['kategori_ad' => 'Yangın', 'kod' => '1.1', 'ifade' => 'Eski madde', 'kritik' => false, 'sonuc' => 'uygun_degil', 'aciklama' => 'x', 'foto_yolu' => null],
        ]]);
        $this->assertStringContainsString('Eski madde', view('pdf.saha-denetimi', ['denetim' => $eski, 'firma' => $firma])->render());
    }

    public function test_sektore_ozgu_kontrol_degisince_eski_cevaplar_listeden_duser(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();

        $sayfa = Livewire::test(SahaSayfasi::class)->set('firmaId', $firma->id)->set('defterSektoru', 'insaat');
        $this->assertSame('Sektöre Özgü Kontrol — İnşaat', $sayfa->instance()->kategoriler()['sektor_defteri']['ad']);

        $sayfa->set('defterSektoru', 'tekstil');
        $this->assertSame('Sektöre Özgü Kontrol — Tekstil', $sayfa->instance()->kategoriler()['sektor_defteri']['ad']);

        $sayfa->set('defterSektoru', '');
        $sayfa->assertSet('defterSektoru', null);
        $this->assertArrayNotHasKey('sektor_defteri', $sayfa->instance()->kategoriler());
    }

    public function test_firma_secilince_denetci_otomatik_dolar(): void
    {
        $igu = IsgProfesyoneli::factory()->for($this->uzman)->create(['ad_soyad' => 'İGU Ayşe']);
        $firma = Firma::factory()->for($this->uzman)->create(['igu_id' => $igu->id, 'unvan' => 'Test A.Ş.']);

        $component = Livewire::test(SahaSayfasi::class)->set('firmaId', $firma->id);

        $this->assertSame('İGU Ayşe', $component->get('denetciAdi'));
    }

    public function test_firma_placeholder_ifadede_degistirilir(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create(['unvan' => 'ACME İnşaat A.Ş.']);

        $component = Livewire::test(SahaSayfasi::class)->set('firmaId', $firma->id);

        $component->assertSee('ACME İnşaat A.Ş. etiketiyle tanımlanmış mı?');
    }

    public function test_cevap_verilir(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();

        $component = Livewire::test(SahaSayfasi::class)
            ->set('firmaId', $firma->id)
            ->call('cevapVer', '1.1', 'uygun');

        $this->assertSame('uygun', $component->get('cevaplar')['1_1']['sonuc']);
    }

    public function test_uygun_degil_icin_aciklama_zorunlu(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();

        Livewire::test(SahaSayfasi::class)
            ->set('firmaId', $firma->id)
            ->call('cevapVer', '1.1', 'uygun_degil')
            ->callAction('pdf');

        $this->assertDatabaseCount('saha_denetimleri', 0);
    }

    public function test_ekip_firma_calisanindan_hizli_eklenir(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();
        $c = Calisan::factory()->for($firma)->create(['ad_soyad' => 'Ahmet Yılmaz']);

        $component = Livewire::test(SahaSayfasi::class)
            ->set('firmaId', $firma->id)
            ->call('ekipHizliEkle', $c->id);

        $this->assertSame('Ahmet Yılmaz', $component->get('ekipUyeleri')[0]['ad_soyad']);
    }

    public function test_pdf_aksiyonu_kayit_olusturur_uygunluk_hesaplar_ve_kase_snapshotlanir(): void
    {
        Storage::fake('public');
        $igu = IsgProfesyoneli::factory()->for($this->uzman)->create(['kase_gorseli' => 'isg-profesyonel-kase/x.png']);
        $firma = Firma::factory()->for($this->uzman)->create(['igu_id' => $igu->id]);

        Livewire::test(SahaSayfasi::class)
            ->set('firmaId', $firma->id)
            ->call('cevapVer', '1.1', 'uygun')
            ->call('cevapVer', '1.2', 'uygun')
            ->call('cevapVer', '1.3', 'uygun_degil')
            ->set('cevaplar.1_3.aciklama', 'Eğitim eksik')
            ->set('fotoYuklemeleri.1_3', UploadedFile::fake()->image('kanit.jpg'))
            ->call('cevapVer', '1.4', 'uygulanamaz')
            ->callAction('pdf');

        $d = SahaDenetimi::where('firma_id', $firma->id)->firstOrFail();
        $this->assertSame(1, $d->revizyon);
        $this->assertSame('isg-profesyonel-kase/x.png', $d->denetci_kase);
        $this->assertTrue($d->kritik_uygunsuzluk_var);
        $this->assertEqualsWithDelta(66.67, $d->uygunluk_yuzdesi, 0.01);

        $madde13 = collect($d->cevaplar)->firstWhere('kod', '1.3');
        $this->assertSame('Eğitim eksik', $madde13['aciklama']);
        $this->assertNotNull($madde13['foto_yolu']);
        Storage::disk('public')->assertExists($madde13['foto_yolu']);
    }

    public function test_pdf_uretilir(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();
        $d = SahaDenetimi::create([
            'firma_id' => $firma->id,
            'revizyon' => 1,
            'cevaplar' => [
                ['kategori_ad' => 'Yangın', 'kod' => '1.1', 'ifade' => 'Test?', 'kritik' => true, 'sonuc' => 'uygun', 'aciklama' => null, 'foto_yolu' => null],
            ],
        ]);

        $yanit = SahaDenetimiUretici::pdf($d);

        $this->assertInstanceOf(StreamedResponse::class, $yanit);
        ob_start();
        $yanit->sendContent();
        $icerik = ob_get_clean();
        $this->assertStringStartsWith('%PDF', $icerik);
    }

    public function test_pdf_raporunda_uygulanamaz_maddeler_listelenmez(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();
        $d = SahaDenetimi::create([
            'firma_id' => $firma->id,
            'revizyon' => 1,
            'cevaplar' => [
                ['kategori_ad' => 'Yangın', 'kod' => '1.1', 'ifade' => 'Görünmesi gereken madde', 'kritik' => true, 'sonuc' => 'uygun', 'aciklama' => null, 'foto_yolu' => null],
                ['kategori_ad' => 'Yangın', 'kod' => '1.4', 'ifade' => 'Gizlenmesi gereken uygulanamaz madde', 'kritik' => false, 'sonuc' => 'uygulanamaz', 'aciklama' => null, 'foto_yolu' => null],
            ],
        ]);

        $html = view('pdf.saha-denetimi', ['denetim' => $d, 'firma' => $firma])->render();

        $this->assertStringContainsString('Görünmesi gereken madde', $html);
        $this->assertStringNotContainsString('Gizlenmesi gereken uygulanamaz madde', $html);
    }

    public function test_ozel_madde_eklenir_ve_secili_sektorde_kontrol_listesine_dahil_olur(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();

        $component = Livewire::test(SahaSayfasi::class)
            ->set('firmaId', $firma->id)
            ->set('yeniOzelSektorAnahtari', 'insaat')
            ->set('yeniOzelKategoriAdi', 'Kazı Kontrolü')
            ->set('yeniOzelIfade', 'Kazı şevi/iksa sistemi güvenli mi?')
            ->set('yeniOzelKritik', true)
            ->call('ozelMaddeEkle');

        $this->assertDatabaseHas('saha_denetimi_ozel_maddeleri', [
            'user_id' => $this->uzman->id,
            'sektor_anahtari' => 'insaat',
            'kategori_ad' => 'Kazı Kontrolü',
        ]);

        // Sektör henüz seçilmediği için kontrol listesinde (kategoriler) görünmemeli
        // (özel madde yönetim tablosunda hâlâ görünür — o listede sektör filtresi yok).
        $iceriyorMu = fn ($kategoriler) => collect($kategoriler)->contains(fn ($k) => $k['ad'] === 'Kazı Kontrolü');
        $this->assertFalse($iceriyorMu($component->get('kategoriler')));

        // Sektör "İnşaat" seçilince kontrol listesine dahil olmalı.
        $component->set('sektorAnahtari', 'insaat');
        $this->assertTrue($iceriyorMu($component->get('kategoriler')));
    }

    public function test_sektorsuz_ozel_madde_her_sektorde_gorunur(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();

        SahaDenetimiOzelMadde::create([
            'user_id' => $this->uzman->id,
            'sektor_anahtari' => null,
            'kategori_ad' => 'Genel Ek Kontrol',
            'ifade' => 'Herkes için görünen madde',
        ]);

        Livewire::test(SahaSayfasi::class)
            ->set('firmaId', $firma->id)
            ->set('sektorAnahtari', 'maden')
            ->assertSee('Herkes için görünen madde');
    }

    public function test_ozel_madde_mevcut_kategoriye_eklenince_birlesir(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();

        $component = Livewire::test(SahaSayfasi::class)
            ->set('firmaId', $firma->id)
            ->set('yeniOzelKategoriAdi', 'Yüksekte Çalışma')
            ->set('yeniOzelIfade', 'Kat kenarları korkuluklu mu?')
            ->call('ozelMaddeEkle');

        $kategoriler = $component->get('kategoriler');
        $bulunduMu = collect($kategoriler)->contains(fn ($k) => $k['ad'] === 'Yüksekte Çalışma'
            && collect($k['maddeler'])->contains(fn ($m) => $m['ifade'] === 'Kat kenarları korkuluklu mu?'));

        $this->assertTrue($bulunduMu);
        // Aynı kategori tekrar oluşturulmamalı (tek "Yüksekte Çalışma" girdisi kalmalı).
        $this->assertCount(1, collect($kategoriler)->where('ad', 'Yüksekte Çalışma'));
    }

    public function test_ozel_madde_silinir(): void
    {
        $madde = SahaDenetimiOzelMadde::create([
            'user_id' => $this->uzman->id,
            'kategori_ad' => 'Test Başlık',
            'ifade' => 'Test madde',
        ]);

        Livewire::test(SahaSayfasi::class)->call('ozelMaddeSil', $madde->id);

        $this->assertDatabaseMissing('saha_denetimi_ozel_maddeleri', ['id' => $madde->id]);
    }

    public function test_gecmis_kayit_silinir(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();
        $d = SahaDenetimi::create(['firma_id' => $firma->id, 'revizyon' => 1]);

        Livewire::test(SahaSayfasi::class)
            ->set('firmaId', $firma->id)
            ->call('gecmisSil', $d->id);

        $this->assertDatabaseMissing('saha_denetimleri', ['id' => $d->id]);
    }

    public function test_taslak_kaydedilir_ve_gecmise_karismaz(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();

        Livewire::test(SahaSayfasi::class)
            ->set('firmaId', $firma->id)
            ->set('santiyeAdi', 'Yarım Kalan Şantiye')
            ->call('cevapVer', '1.1', 'uygun')
            ->call('taslakKaydet');

        $this->assertDatabaseCount('saha_denetimleri', 1);
        $taslak = SahaDenetimi::where('firma_id', $firma->id)->firstOrFail();
        $this->assertSame('taslak', $taslak->durum);
        $this->assertSame('Yarım Kalan Şantiye', $taslak->santiye_adi);

        // Taslak, "geçmiş kayıtlar" (tamamlanmış) listesine dahil edilmemeli.
        $sayfa = Livewire::test(SahaSayfasi::class)->set('firmaId', $firma->id);
        $this->assertCount(0, $sayfa->instance()->gecmisKayitlar());
    }

    public function test_ikinci_taslak_kaydi_oncekini_gunceller_yeni_satir_acmaz(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();

        $component = Livewire::test(SahaSayfasi::class)
            ->set('firmaId', $firma->id)
            ->set('santiyeAdi', 'İlk Hal')
            ->call('taslakKaydet');

        $component->set('santiyeAdi', 'Güncellenmiş Hal')->call('taslakKaydet');

        $this->assertDatabaseCount('saha_denetimleri', 1);
        $this->assertSame('Güncellenmiş Hal', SahaDenetimi::where('firma_id', $firma->id)->firstOrFail()->santiye_adi);
    }

    public function test_firma_secilince_taslak_otomatik_yuklenir(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();
        SahaDenetimi::create([
            'firma_id' => $firma->id, 'durum' => 'taslak', 'revizyon' => 0,
            'santiye_adi' => 'Kaydedilmiş Şantiye', 'ekip_uyeleri' => [], 'cevaplar' => [
                ['kategori_ad' => 'x', 'kod' => '1.1', 'ifade' => 'y', 'kritik' => false, 'sonuc' => 'uygun', 'aciklama' => null, 'foto_yolu' => null],
            ],
        ]);

        $component = Livewire::test(SahaSayfasi::class)->set('firmaId', $firma->id);

        $this->assertTrue($component->get('taslakYuklendi'));
        $this->assertSame('Kaydedilmiş Şantiye', $component->get('santiyeAdi'));
        $this->assertSame('uygun', $component->get('cevaplar')['1_1']['sonuc']);
    }

    public function test_taslak_temizle_kaydi_siler(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();
        $taslak = SahaDenetimi::create(['firma_id' => $firma->id, 'durum' => 'taslak', 'revizyon' => 0]);

        Livewire::test(SahaSayfasi::class)
            ->set('firmaId', $firma->id)
            ->call('taslakTemizle');

        $this->assertDatabaseMissing('saha_denetimleri', ['id' => $taslak->id]);
    }

    public function test_denetimi_tamamlayinca_bekleyen_taslak_silinir(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();
        SahaDenetimi::create(['firma_id' => $firma->id, 'durum' => 'taslak', 'revizyon' => 0]);

        Livewire::test(SahaSayfasi::class)
            ->set('firmaId', $firma->id)
            ->call('cevapVer', '1.1', 'uygun')
            ->callAction('pdf');

        $this->assertDatabaseCount('saha_denetimleri', 1);
        $this->assertSame('tamamlandi', SahaDenetimi::where('firma_id', $firma->id)->firstOrFail()->durum);
    }

    public function test_baska_uzmanin_firmasi_secilemez(): void
    {
        $baskaUzman = User::factory()->create();
        $baskaFirma = Firma::factory()->for($baskaUzman)->create();

        $firmalar = Livewire::test(SahaSayfasi::class)->instance()->firmalar();

        $this->assertArrayNotHasKey($baskaFirma->id, $firmalar);
    }

    public function test_uygun_maddeye_de_fotograf_eklenebilir(): void
    {
        Storage::fake('public');
        $firma = Firma::factory()->for($this->uzman)->create();

        Livewire::test(SahaSayfasi::class)
            ->set('firmaId', $firma->id)
            ->call('cevapVer', '1.1', 'uygun')
            ->set('fotoYuklemeleri.1_1', UploadedFile::fake()->image('uygun-kanit.jpg'))
            ->callAction('pdf');

        $d = SahaDenetimi::where('firma_id', $firma->id)->firstOrFail();
        $madde11 = collect($d->cevaplar)->firstWhere('kod', '1.1');

        $this->assertSame('uygun', $madde11['sonuc']);
        $this->assertNotNull($madde11['foto_yolu']);
        Storage::disk('public')->assertExists($madde11['foto_yolu']);
    }

    public function test_bir_maddeye_birden_fazla_fotograf_eklenir_ve_kanit_sayfalari_basilir(): void
    {
        Storage::fake('public');
        $firma = Firma::factory()->for($this->uzman)->create();

        Livewire::test(SahaSayfasi::class)
            ->set('firmaId', $firma->id)
            ->call('cevapVer', '5.3', 'uygun_degil')
            ->set('cevaplar.5_3.aciklama', 'Priz kırık')
            ->set('fotoYuklemeleri.5_3', [UploadedFile::fake()->image('a.jpg'), UploadedFile::fake()->image('b.jpg')])
            ->callAction('pdf');

        $d = SahaDenetimi::where('firma_id', $firma->id)->firstOrFail();
        $madde = collect($d->cevaplar)->firstWhere('kod', '5.3');

        $this->assertCount(2, $madde['fotolar']);
        $this->assertSame($madde['fotolar'][0], $madde['foto_yolu']);

        $kanitlar = $d->kanitlar();
        $this->assertCount(2, $kanitlar);
        $this->assertSame(2, $kanitlar[1]['sira']);

        $html = view('pdf.saha-denetimi', ['denetim' => $d, 'firma' => $firma])->render();
        $this->assertStringContainsString('Kanıt 2 · Madde 5.3', $html);
        $this->assertStringContainsString('Kanıt kimliği: '.$kanitlar[0]['kimlik'], $html);
    }

    public function test_eski_tekil_foto_yolu_kanit_olarak_okunur(): void
    {
        $d = new SahaDenetimi(['cevaplar' => [
            ['kod' => '1.1', 'foto_yolu' => 'saha-denetimi-foto/eski.jpg'],
            ['kod' => '1.2', 'foto_yolu' => null],
        ]]);

        $this->assertSame([['sira' => 1, 'kod' => '1.1', 'yol' => 'saha-denetimi-foto/eski.jpg', 'kimlik' => 'eski']], $d->kanitlar());
    }

    public function test_kayitli_fotograflardan_yalniz_biri_silinir(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();

        $component = Livewire::test(SahaSayfasi::class)
            ->set('firmaId', $firma->id)
            ->set('cevaplar.1_1.fotolar', ['saha-denetimi-foto/a.jpg', 'saha-denetimi-foto/b.jpg'])
            ->call('kayitliFotoSil', '1.1', 0);

        $this->assertSame(['saha-denetimi-foto/b.jpg'], $component->get('cevaplar')['1_1']['fotolar']);
        $this->assertSame('saha-denetimi-foto/b.jpg', $component->get('cevaplar')['1_1']['foto_yolu']);
    }

    public function test_foto_kaldir_yuklemeyi_ve_taslak_yolunu_temizler(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();

        $component = Livewire::test(SahaSayfasi::class)
            ->set('firmaId', $firma->id)
            ->set('cevaplar.1_1.foto_yolu', 'saha-denetimi-foto/eski.jpg')
            ->set('fotoYuklemeleri.1_1', UploadedFile::fake()->image('yeni.jpg'))
            ->call('fotoKaldir', '1.1');

        $this->assertArrayNotHasKey('1_1', $component->get('fotoYuklemeleri'));
        $this->assertNull($component->get('cevaplar')['1_1']['foto_yolu']);
    }

    public function test_pdf_madde_tablosunda_satir_ici_foto_gosterilir(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();
        $d = SahaDenetimi::create([
            'firma_id' => $firma->id,
            'revizyon' => 1,
            'cevaplar' => [
                ['kategori_ad' => 'Yangın', 'kod' => '1.1', 'ifade' => 'Test?', 'kritik' => false, 'sonuc' => 'uygun', 'aciklama' => null, 'foto_yolu' => 'saha-denetimi-foto/kanit.jpg'],
            ],
        ]);

        $html = view('pdf.saha-denetimi', ['denetim' => $d, 'firma' => $firma])->render();

        $this->assertStringContainsString('class="satir-foto"', $html);
        $this->assertStringContainsString('saha-denetimi-foto/kanit.jpg', $html);
    }

    public function test_ise_ozgu_kontrol_listesi_jsa_sayfasina_yonlendirir(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();

        Livewire::test(SahaSayfasi::class)
            ->set('firmaId', $firma->id)
            ->assertActionExists('isEOzguKontrolListesi');
    }

    public function test_ekipman_kontrol_formu_secilen_tipler_icin_pdf_uretir(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create(['unvan' => 'ABC İnşaat']);

        Livewire::test(SahaSayfasi::class)
            ->set('firmaId', $firma->id)
            ->callAction('ekipmanKontrolFormlari', data: ['tipIndeksleri' => [0, 15]])
            ->assertHasNoActionErrors();
    }

    public function test_haftalik_ekipman_kontrol_uretici_dogru_tipleri_pdfe_yazar(): void
    {
        $yanit = HaftalikEkipmanKontrolUretici::pdf([0, 15], 'ABC İnşaat');

        $this->assertInstanceOf(StreamedResponse::class, $yanit);
        ob_start();
        $yanit->sendContent();
        $icerik = ob_get_clean();
        $this->assertStringStartsWith('%PDF', $icerik);
    }

    public function test_haftalik_ekipman_kontrol_konfigurasyonu_kullanici_formlari_ve_taslaklari_icerir(): void
    {
        $tipler = collect(EkipmanKatalogu::kontrolFormlari());

        // 41 kullanıcı referansı (düz, 12 soru) + kullanıcının kendi ekipman formları (form_no'lu:
        // gruplu U/UD/Y/X ya da kabul kriterli ✓/X/-) + kullanıcının 14.10.2026'da onayladığı 3 AI formu
        // (Matkap, Kesme Tezgâhı, Gaz Tüpü — artık taslak değil, düz 12 soru).
        $kullaniciFormlari = $tipler->filter(fn ($t) => filled($t['form_no'] ?? null));
        $this->assertCount(60, $tipler->filter(fn ($t) => blank($t['form_no'] ?? null))); // 44 + 16 yeni (Ekipman Değerlendirme Aracı, 19.10.2026)
        $this->assertSame([], $tipler->where('taslak', true)->pluck('ad')->all());
        $this->assertCount(17, $kullaniciFormlari); // 18 talimat; ekstrüzyonda eski+yeni talimat, tek (yeni) form; +4 havalandırma/sac (14.10.2026)
        $this->assertSame($tipler->count(), $tipler->pluck('ad')->unique()->count(), 'form adları tekil olmalı');

        foreach ($tipler as $t) {
            $this->assertNotEmpty($t['ad']);
            if (filled($t['form_no'] ?? null)) {
                $this->assertGreaterThanOrEqual(8, count($t['sorular']), $t['ad']);
                $this->assertNotEmpty($t['kriterler'], $t['ad']);
            } else {
                $this->assertCount(12, $t['sorular'], $t['ad']);
            }
        }
    }

    public function test_gruplu_kullanici_formu_haftalik_pdfe_de_yazilir(): void
    {
        $cnc = collect(EkipmanKatalogu::kontrolFormlari())->search(fn ($f) => $f['ad'] === 'CNC Tezgâhı');

        $yanit = HaftalikEkipmanKontrolUretici::pdf([$cnc], 'ABC Makina');

        ob_start();
        $yanit->sendContent();
        $this->assertStringStartsWith('%PDF', ob_get_clean());
    }
}
