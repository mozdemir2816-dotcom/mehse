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
        $beklenenMetin = TatbikatTutanagi::senaryoMetniDoldur(config('isg.tatbikat.senaryolar.yangin.metin'), $firma);

        $component = Livewire::test(TatbikatSayfasi::class)
            ->set('firmaId', $firma->id)
            ->call('senaryoSec', 'yangin');

        $this->assertSame($beklenenMetin, $component->get('senaryoMetni'));
    }

    public function test_on_hazir_senaryolar_firma_adres_yer_ve_toplanma_ile_dolar(): void
    {
        $this->assertCount(10, config('isg.tatbikat.senaryolar'));
        foreach (config('isg.tatbikat.senaryolar') as $anahtar => $s) {
            $this->assertStringContainsString('{firma}', $s['metin'], $anahtar);
            $this->assertGreaterThan(0, $s['hedef_tahliye_dk'], $anahtar);
        }

        $firma = Firma::factory()->for($this->uzman)->create(['unvan' => 'ACME Metal A.Ş.', 'adres' => 'Nilüfer / Bursa']);

        $sayfa = Livewire::test(TatbikatSayfasi::class)
            ->set('firmaId', $firma->id)
            ->call('senaryoSec', 'deprem')
            ->assertSet('hedefTahliyeDk', 5);

        $metin = $sayfa->get('senaryoMetni');
        $this->assertStringContainsString('ACME Metal A.Ş. (Nilüfer / Bursa) işyerinde', $metin);
        $this->assertStringNotContainsString('{', $metin);

        // Yer / toplanma sonradan yazılınca elle değiştirilmemiş metin yenilenir.
        $sayfa->set('tatbikatYeri', 'CNC atölyesi')->set('toplanmaAlani', 'Ana kapı önü otopark');
        $this->assertStringContainsString('CNC atölyesi bölümünde', $sayfa->get('senaryoMetni'));
        $this->assertStringContainsString('Ana kapı önü otopark toplanma alanına', $sayfa->get('senaryoMetni'));

        // Uzman metni elle düzenlediyse dokunulmaz.
        $sayfa->set('senaryoMetni', 'Uzmanın kendi metni')->set('tatbikatYeri', 'Depo');
        $this->assertSame('Uzmanın kendi metni', $sayfa->get('senaryoMetni'));

        // Adres yoksa parantez kalmaz.
        $adressiz = new Firma(['unvan' => 'X Ltd.', 'adres' => null]);
        $this->assertStringStartsWith('X Ltd. işyerinde', TatbikatTutanagi::senaryoMetniDoldur('{firma} ({adres}) işyerinde', $adressiz));
    }

    public function test_sureler_hedef_kiyasi_pdf_ve_word_ciktisi(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create(['unvan' => 'ACME Metal A.Ş.', 'adres' => 'Nilüfer / Bursa']);

        Livewire::test(TatbikatSayfasi::class)
            ->set('firmaId', $firma->id)
            ->call('senaryoSec', 'yangin')
            ->set('baslamaSaati', '10:00')
            ->set('bitisSaati', '10:35')
            ->set('tahliyeDk', 7)
            ->set('isGuvenligiUzmani', 'Mehmet Öz')
            ->set('isyeriHekimiImzasi', true)
            ->set('dofOnerileri', [['faaliyet' => 'Acil çıkış kapısı panik bar ile değiştirilecek', 'sorumlu' => 'Bakım', 'tarih' => '2026-12-01']])
            ->callAction('kaydet');

        $t = TatbikatTutanagi::where('firma_id', $firma->id)->firstOrFail();
        $this->assertSame(5, $t->hedef_tahliye_dk);
        $this->assertSame(35, $t->toplamSureDk());
        $this->assertFalse($t->hedefTuttuMu());

        $html = view('pdf.tatbikat-tutanagi', ['tutanak' => $t, 'firma' => $firma])->render();
        foreach (['ACİL DURUM TATBİKAT TUTANAĞI', 'Md. 13', 'Nilüfer / Bursa', '35 dk', '2 dk aşıldı', 'İşyeri Hekimi', 'Mehmet Öz', '01.12.2026', 'Acil çıkış kapısı panik bar'] as $parca) {
            $this->assertStringContainsString($parca, $html);
        }

        $yanit = TatbikatTutanagiUretici::word($t);
        ob_start();
        $yanit->sendContent();
        $docx = ob_get_clean();
        $this->assertStringStartsWith('PK', $docx);

        $gecici = tempnam(sys_get_temp_dir(), 'tt').'.docx';
        file_put_contents($gecici, $docx);
        $zip = new \ZipArchive;
        $zip->open($gecici);
        $xml = $zip->getFromName('word/document.xml');
        $zip->close();
        @unlink($gecici);
        foreach (['ACME Metal A.Ş.', 'KRONOMETRE', '2 dk aşıldı', 'Mehmet Öz', 'Acil çıkış kapısı panik bar'] as $parca) {
            $this->assertStringContainsString($parca, $xml);
        }
    }

    public function test_firma_sayfasinda_tatbikatlar_sekmesi_listeler_ve_duzenleme_linki_formu_doldurur(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();
        $t = TatbikatTutanagi::create(['firma_id' => $firma->id, 'senaryo_anahtari' => 'deprem', 'tatbikat_tarihi' => '2026-10-15', 'tahliye_dk' => 4]);

        Livewire::test(\App\Filament\Resources\Firmas\RelationManagers\TatbikatlarRelationManager::class, [
            'ownerRecord' => $firma,
            'pageClass' => \App\Filament\Resources\Firmas\Pages\EditFirma::class,
        ])
            ->assertCanSeeTableRecords([$t])
            ->assertSee('Deprem Tatbikatı')
            ->assertSee('4 dk / hedef 5 dk');

        $this->get(TatbikatSayfasi::getUrl(['firma' => $firma->id, 'duzenle' => $t->id]))->assertOk();

        Livewire::withQueryParams(['firma' => $firma->id, 'duzenle' => $t->id])
            ->test(TatbikatSayfasi::class)
            ->assertSet('duzenlenenId', $t->id)
            ->assertSet('senaryoAnahtari', 'deprem')
            ->assertSet('tahliyeDk', 4);
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

        $this->assertStringContainsString('Fotoğraf 1', $html);
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

    public function test_planlanan_tatbikat_kaydedilir_ve_kontrol_merkezinde_sayilmaz(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();

        Livewire::test(TatbikatSayfasi::class)
            ->set('firmaId', $firma->id)
            ->call('senaryoSec', 'deprem')
            ->set('durum', 'planlandi')
            ->set('tatbikatTarihi', now()->addMonth()->toDateString())
            ->set('toplanmaAlani', 'Otopark')
            ->set('tatbikatKoordinatoru', 'Ali Usta')
            ->callAction('kaydet');

        $t = TatbikatTutanagi::sole();
        $this->assertSame('planlandi', $t->durum);
        $this->assertSame('Otopark', $t->toplanma_alani);
        $this->assertFalse(\App\Support\PortfoyKarne::firmaKriterKarsilarMi($firma, 'acil_durum_tatbikat'));

        $t->update(['durum' => 'yapildi']);
        $this->assertTrue(\App\Support\PortfoyKarne::firmaKriterKarsilarMi($firma->fresh(), 'acil_durum_tatbikat'));
    }

    public function test_planlanani_yapildi_olarak_doldurmak_ayni_kaydi_gunceller(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();
        $t = TatbikatTutanagi::create([
            'firma_id' => $firma->id, 'senaryo_anahtari' => 'yangin', 'durum' => 'planlandi',
            'tatbikat_tarihi' => now()->subDay(), 'fotograflar' => ['tatbikat-foto/eski.jpg'],
        ]);
        $this->assertTrue($t->gecikmisMi());

        $sayfa = Livewire::test(TatbikatSayfasi::class)
            ->call('duzenle', $t->id, 'yapildi')
            ->assertSet('firmaId', $firma->id)
            ->assertSet('durum', 'yapildi')
            ->set('katilimciSayisi', 42)
            ->set('tahliyeDk', 4)
            ->callAction('kaydet');

        $this->assertSame(1, TatbikatTutanagi::count());
        $t->refresh();
        $this->assertSame('yapildi', $t->durum);
        $this->assertSame(42, $t->katilimciSayisi());
        $this->assertSame(['tatbikat-foto/eski.jpg'], $t->fotograflar);
        $this->assertFalse($t->gecikmisMi());

        $sayfa->call('yeniKayit')->assertSet('duzenlenenId', null)->assertSet('firmaId', $firma->id);
    }

    public function test_eksiklik_varsa_yapildi_takip_gerekiyor_olur(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();

        Livewire::test(TatbikatSayfasi::class)
            ->set('firmaId', $firma->id)
            ->set('durum', 'yapildi')
            ->set('yeniEksiklik', 'Alarm B blokta duyulmadı')
            ->call('eksiklikEkle')
            ->callAction('kaydet');

        $this->assertSame('takip', TatbikatTutanagi::sole()->durum);
    }

    public function test_kayit_listesi_ozet_filtre_ve_arama(): void
    {
        $a = Firma::factory()->for($this->uzman)->create(['unvan' => 'Alfa']);
        $b = Firma::factory()->for($this->uzman)->create(['unvan' => 'Beta']);
        TatbikatTutanagi::create(['firma_id' => $a->id, 'senaryo_anahtari' => 'yangin', 'durum' => 'yapildi', 'tatbikat_koordinatoru' => 'Veli']);
        TatbikatTutanagi::create(['firma_id' => $b->id, 'senaryo_anahtari' => 'deprem', 'durum' => 'planlandi', 'tatbikat_tarihi' => now()->addWeek()]);
        TatbikatTutanagi::create(['firma_id' => $b->id, 'senaryo_anahtari' => 'deprem', 'durum' => 'takip']);
        TatbikatTutanagi::create(['firma_id' => Firma::factory()->for(User::factory())->create()->id, 'senaryo_anahtari' => 'yangin']);

        $sayfa = Livewire::test(TatbikatSayfasi::class);
        $this->assertSame(['toplam' => 3, 'planlandi' => 1, 'yapildi' => 1, 'takip' => 1, 'gecikmis' => 0], $sayfa->instance()->ozet);

        $sayfa->set('listeDurum', 'planlandi');
        $this->assertSame([$b->id], $sayfa->instance()->kayitlar->pluck('firma_id')->all());

        $sayfa->set('listeDurum', null)->set('listeArama', 'veli');
        $this->assertCount(1, $sayfa->instance()->kayitlar);

        $sayfa->set('listeArama', null)->set('firmaId', $b->id);
        $this->assertSame(2, $sayfa->instance()->ozet['toplam']);
    }

    public function test_tutarlilik_gecikmede_dof_sondurme_ekibi_sure_ve_yetkili_adlari(): void
    {
        Storage::fake('public');
        $hekim = \App\Models\IsgProfesyoneli::create(['user_id' => $this->uzman->id, 'tip' => 'isyeri_hekimi', 'ad_soyad' => 'Dr. Zeynep Ak']);
        $firma = Firma::factory()->for($this->uzman)->create(['isveren_ad' => 'Yasin ŞENER', 'isyeri_hekimi_id' => $hekim->id]);
        \App\Support\AcilEkipDurumu::temelEkipleriOlustur($firma);
        \App\Models\AcilEkip::where('firma_id', $firma->id)->where('tur', 'sondurme')->first()->uyeler()->create(['ad_soyad' => 'Söndüren Kişi', 'lider' => true]);

        $sayfa = Livewire::test(TatbikatSayfasi::class)
            ->set('firmaId', $firma->id)
            ->assertSet('isverenVekili', 'Yasin ŞENER')
            ->set('isGuvenligiUzmani', 'Mehmet ÖZDEMİR')
            ->set('tatbikatKoordinatoru', 'Ayşe Kaya')
            ->set('tatbikatTarihi', '2026-11-02')
            ->call('senaryoSec', 'yangin')
            ->set('baslamaSaati', '10:00')
            ->set('bitisSaati', '10:08')
            ->set('isyeriHekimiImzasi', true)
            ->callAction('kaydet');

        $t = TatbikatTutanagi::where('firma_id', $firma->id)->firstOrFail();
        $this->assertSame(8, $t->tahliye_dk);                         // başlama–bitiş farkı
        $soru = collect($t->degerlendirmeler)->first(fn ($d) => str_contains($d['soru'], 'Tahliye planlanan sürede'));
        $this->assertSame('hayir', $soru['cevap']);                  // hedef 5 dk aşıldı
        $this->assertStringStartsWith('Tahliye süresinde gecikme yaşandı (gerçekleşen 8 dk / hedef 5 dk)', $t->eksiklikler[0]);
        $this->assertSame('Ayşe Kaya', $t->dof_onerileri[0]['sorumlu']);
        $this->assertSame('2026-12-02', $t->dof_onerileri[0]['tarih']);
        $this->assertSame('takip', $t->durum);
        $this->assertContains(['ad_soyad' => 'Söndüren Kişi', 'ekip' => 'Söndürme Ekibi'], $t->ekipler);

        // İkinci kayıtta tekrar eklenmez.
        $sayfa->callAction('kaydet');
        $t->refresh();
        $this->assertCount(1, $t->eksiklikler);
        $this->assertCount(1, $t->dof_onerileri);
        $this->assertCount(1, collect($t->ekipler)->where('ekip', 'Söndürme Ekibi'));

        $html = view('pdf.tatbikat-tutanagi', ['tutanak' => $t, 'firma' => $firma])->render();
        foreach (['Yasin ŞENER', 'Mehmet ÖZDEMİR', 'Dr. Zeynep Ak', '8 dk', '3 dk aşıldı', 'Söndüren Kişi', 'ihbar butonuna'] as $parca) {
            $this->assertStringContainsString($parca, $html);
        }
        $this->assertStringNotContainsString('TATBİKAT FOTOĞRAFLARI', $html);
    }

    public function test_sorunsuz_tahliyede_evet_eski_kisa_metin_ve_bozuk_foto(): void
    {
        Storage::fake('public');
        $firma = Firma::factory()->for($this->uzman)->create(['unvan' => 'Kısa Metin Ltd.']);

        Livewire::test(TatbikatSayfasi::class)
            ->set('firmaId', $firma->id)
            ->call('senaryoSec', 'deprem')
            ->set('tahliyeDk', 4)
            ->callAction('kaydet');
        $t = TatbikatTutanagi::where('firma_id', $firma->id)->firstOrFail();
        $this->assertSame('evet', collect($t->degerlendirmeler)->first(fn ($d) => str_contains($d['soru'], 'Tahliye planlanan sürede'))['cevap']);
        $this->assertEmpty($t->eksiklikler);
        $this->assertSame('yapildi', $t->durum);

        // Eski jenerik 2 satırlık metinle kaydedilmiş tutanak + diskte olmayan / bozuk fotoğraf.
        Storage::disk('public')->put('tatbikat-foto/bozuk.jpg', 'resim degil');
        $eski = TatbikatTutanagi::create([
            'firma_id' => $firma->id, 'senaryo_anahtari' => 'yangin', 'fotograflar' => ['tatbikat-foto/yok.jpg', 'tatbikat-foto/bozuk.jpg'],
            'senaryo_metni' => 'Tatbikat kapsamında işyerinde yangın alarmı verilerek çalışanların en yakın ve güvenli çıkış yollarını kullanarak belirlenen toplanma noktasına tahliyesi test edilmiştir.',
        ]);
        $html = view('pdf.tatbikat-tutanagi', ['tutanak' => $eski, 'firma' => $firma])->render();
        $this->assertStringContainsString('Kısa Metin Ltd. işyerinde', $html);
        $this->assertStringContainsString('Söndürme Ekibi görev almalıdır', $html);
        $this->assertStringNotContainsString('Fotoğraf 1', $html);
    }
}
