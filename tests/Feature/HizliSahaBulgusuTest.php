<?php

namespace Tests\Feature;

use App\Filament\Pages\AiSahaAnalizi;
use App\Filament\Pages\DofOlustur;
use App\Filament\Pages\HizliSahaBulgusu;
use App\Models\Firma;
use App\Models\SahaBulgusu;
use App\Models\User;
use App\Support\SahaBulgusuUretici;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class HizliSahaBulgusuTest extends TestCase
{
    use RefreshDatabase;

    private User $uzman;

    private Firma $firma;

    protected function setUp(): void
    {
        parent::setUp();
        $this->uzman = User::factory()->create();
        $this->actingAs($this->uzman);
        $this->firma = Firma::factory()->for($this->uzman)->create();
    }

    public function test_manuel_bulgu_fotograf_ve_konumla_kaydedilir(): void
    {
        Storage::fake('public');

        $sayfa = Livewire::test(HizliSahaBulgusu::class)
            ->set('firmaId', $this->firma->id)
            ->set('bolum', 'Pres hattı')
            ->set('gozlemKonumu', 'Pres 3 önü')
            ->call('konumAyarla', 40.65512345678, 29.27654321)
            ->set('kategori', 'Makine koruyucuları')
            ->set('tehlike', 'Koruyucu sökülmüş')
            ->set('uygunsuzluk', 'Pres 3 iki el kumanda koruyucusu sökülmüş, çalışan elle besleme yapıyor.')
            ->set('olasilik', 4)
            ->set('siddet', 5)
            ->set('aksiyon', 'Koruyucu takılacak, sensör devreye alınacak.')
            ->set('sorumlu', 'Bakım şefi')
            ->set('termin', now()->addDays(3)->toDateString())
            ->set('yeniFotograflar', [UploadedFile::fake()->image('pres.jpg'), UploadedFile::fake()->image('pres2.jpg')])
            ->call('kaydet')
            ->assertHasNoErrors();

        $b = SahaBulgusu::sole();
        $this->assertSame(20, $b->skor());
        $this->assertSame('Çok Yüksek', $b->seviyeEtiketi());
        $this->assertSame(40.6551235, $b->enlem);
        $this->assertCount(2, $b->fotograflar);
        Storage::disk('public')->assertExists($b->fotograflar[0]);
        $this->assertStringStartsWith('SB-', $b->bulgu_no);
        $this->assertStringContainsString('google.com/maps?q=40.6551235', $b->konumLinki());

        // Firma ve bölüm sonraki bulgu için korunur, diğer alanlar temizlenir
        $sayfa->assertSet('bolum', 'Pres hattı')->assertSet('uygunsuzluk', null)->assertSet('olasilik', 3);
        $this->assertSame(['Pres hattı'], $sayfa->instance()->bolumOnerileri);
    }

    public function test_zorunlu_alanlar_ve_foto_siniri(): void
    {
        Livewire::test(HizliSahaBulgusu::class)
            ->set('uygunsuzluk', '')
            ->call('kaydet')
            ->assertHasErrors(['firmaId', 'uygunsuzluk']);

        Storage::fake('public');
        Livewire::test(HizliSahaBulgusu::class)
            ->set('firmaId', $this->firma->id)
            ->set('uygunsuzluk', 'Zemin yağlı, kayma riski.')
            ->set('yeniFotograflar', array_map(fn ($i) => UploadedFile::fake()->image("f{$i}.jpg"), range(1, 6)))
            ->call('kaydet');

        $this->assertSame(0, SahaBulgusu::count());
    }

    public function test_ai_ile_doldur_formu_taslakla_doldurur(): void
    {
        Storage::fake('public');
        config(['services.gemini.key' => 'test-key']);
        Http::fake([
            '*generativelanguage.googleapis.com*' => Http::response([
                'candidates' => [['content' => ['parts' => [['text' => json_encode([[
                    'foto_index' => 1,
                    'bina_bolge' => 'Merdiven boşluğu',
                    'kategori' => 'Merdivenler',
                    'tespit' => 'Merdiven önünde malzeme yığılmış.',
                    'oneriler' => ['Malzeme kaldırılmalı.', 'Geçiş yolu işaretlenmeli.'],
                    'yasal_gerekce' => '6331 m.4',
                    'risk_derecesi' => 2,
                ]])]]]]],
            ], 200),
        ]);

        $sayfa = Livewire::test(HizliSahaBulgusu::class)
            ->set('firmaId', $this->firma->id)
            ->set('yeniFotograflar', [UploadedFile::fake()->image('m.jpg')])
            ->call('aiIleDoldur')
            ->assertSet('uygunsuzluk', 'Merdiven önünde malzeme yığılmış.')
            ->assertSet('kategori', 'Merdivenler')
            ->assertSet('siddet', 4)
            ->assertSet('kaynak', 'ai')
            ->assertSet('yeniFotograflar', []);

        $this->assertCount(1, $sayfa->get('kaydedilenFotolar'));

        $sayfa->call('kaydet')->assertHasNoErrors();
        $b = SahaBulgusu::sole();
        $this->assertSame('ai', $b->kaynak);
        $this->assertCount(1, $b->fotograflar);
    }

    public function test_kapat_yeniden_ac_dofe_aktar_ve_ozet(): void
    {
        $a = SahaBulgusu::create(['firma_id' => $this->firma->id, 'uygunsuzluk' => 'Kablo açıkta', 'olasilik' => 4, 'siddet' => 4, 'termin' => now()->subDay(), 'aksiyon' => 'Kanal içine alınacak', 'fotograflar' => ['saha-bulgu-foto/a.jpg']]);
        SahaBulgusu::create(['firma_id' => $this->firma->id, 'uygunsuzluk' => 'Etiket eksik', 'olasilik' => 2, 'siddet' => 2]);
        SahaBulgusu::create(['firma_id' => Firma::factory()->for(User::factory())->create()->id, 'uygunsuzluk' => 'X']);

        $sayfa = Livewire::test(HizliSahaBulgusu::class);
        $this->assertSame(['acik' => 2, 'kritik' => 1, 'gecikmis' => 1, 'kapandi' => 0], $sayfa->instance()->ozet);

        $sayfa->callAction('kapat', ['kapanis_tarihi' => now()->toDateString(), 'kapanis_notu' => 'Kablo kanala alındı'], ['id' => $a->id])
            ->assertHasNoActionErrors();
        $this->assertSame('kapandi', $a->fresh()->durum);
        $this->assertCount(1, $sayfa->instance()->bulgular);

        $sayfa->call('yenidenAc', $a->id);
        $this->assertSame('acik', $a->fresh()->durum);

        $sayfa->call('dofeAktar', $a->id)->assertRedirect(DofOlustur::getUrl());
        $aktarim = session('dof_aktarim');
        $this->assertSame('kritik', $aktarim['maddeler'][0]['oncelik']);
        $this->assertSame('Kanal içine alınacak', $aktarim['maddeler'][0]['oneri']);
        $this->assertSame('saha-bulgu-foto/a.jpg', $aktarim['maddeler'][0]['foto_yolu']);
    }

    public function test_tutanak_pdf_ve_excel(): void
    {
        $b = SahaBulgusu::create(['firma_id' => $this->firma->id, 'bolum' => 'Depo', 'uygunsuzluk' => 'Raf devrilme riski', 'olasilik' => 3, 'siddet' => 4, 'enlem' => 40.1, 'boylam' => 29.1]);

        ob_start();
        SahaBulgusuUretici::pdf($b)->sendContent();
        $this->assertStringStartsWith('%PDF', ob_get_clean());

        $tmp = tempnam(sys_get_temp_dir(), 'sbt').'.xlsx';
        ob_start();
        SahaBulgusuUretici::excel(SahaBulgusu::with('firma')->get())->sendContent();
        file_put_contents($tmp, ob_get_clean());
        $s = \PhpOffice\PhpSpreadsheet\IOFactory::load($tmp)->getActiveSheet();
        @unlink($tmp);
        $this->assertSame(12, $s->getCell('L2')->getValue());
        $this->assertSame('Yüksek', $s->getCell('M2')->getValue());
    }

    public function test_ai_saha_analizi_odak_kategorileri_baglama_eklenir(): void
    {
        $sayfa = Livewire::test(AiSahaAnalizi::class)
            ->set('baglamNotu', 'İnşaat sahası')
            ->set('odakKategoriler', ['İskeleler', 'Yüksekte çalışma', 'Uydurma kategori']);

        $baglam = $sayfa->instance()->aiBaglami();
        $this->assertStringContainsString('İnşaat sahası', $baglam);
        $this->assertStringContainsString('İskeleler, Yüksekte çalışma', $baglam);
        $this->assertStringNotContainsString('Uydurma', $baglam);
        $this->assertStringContainsString('diğer tehlikeleri de raporla', $baglam);

        $sayfa->set('tumunuTara', false);
        $this->assertStringContainsString('Yalnız bu kategorilerdeki', $sayfa->instance()->aiBaglami());

        $sayfa->set('odakKategoriler', []);
        $this->assertSame('İnşaat sahası', $sayfa->instance()->aiBaglami());
    }

    // ---------- 2. aşama: Saha Bulguları (tek giriş noktası) ----------

    public function test_sayfa_adi_saha_bulgulari_ve_adres_ayni(): void
    {
        $this->assertSame('Saha Bulguları', HizliSahaBulgusu::getNavigationLabel());
        $this->assertStringEndsWith('/hizli-saha-bulgusu', HizliSahaBulgusu::getUrl());
    }

    public function test_elle_oncelik_ve_yasal_gerekce_kaydedilir(): void
    {
        Livewire::test(HizliSahaBulgusu::class)
            ->set('firmaId', $this->firma->id)
            ->set('uygunsuzluk', 'Yangın tüpünün dolum tarihi geçmiş.')
            ->set('olasilik', 2)->set('siddet', 2)          // skor 4 → otomatik "düşük"
            ->set('oncelik', 'yuksek')
            ->set('yasalGerekce', 'Binaların Yangından Korunması Hk. Yön.')
            ->call('kaydet')
            ->assertHasNoErrors();

        $b = SahaBulgusu::sole();
        $this->assertSame('yuksek', $b->oncelikAnahtari());
        $this->assertSame('Binaların Yangından Korunması Hk. Yön.', $b->yasal_gerekce);
    }

    public function test_durum_degisir_ve_acik_listede_devam_edenler_gorunur(): void
    {
        $b = SahaBulgusu::create(['firma_id' => $this->firma->id, 'uygunsuzluk' => 'Kablo açıkta']);

        $sayfa = Livewire::test(HizliSahaBulgusu::class)->call('durumDegistir', $b->id, 'devam_ediyor');
        $this->assertSame('devam_ediyor', $b->fresh()->durum);
        $this->assertCount(1, $sayfa->instance()->bulgular);           // varsayılan filtre: açık + devam eden
        $this->assertSame(1, $sayfa->instance()->ozet['acik']);

        $sayfa->call('durumDegistir', $b->id, 'ertelendi');
        $this->assertCount(0, $sayfa->instance()->bulgular);
        $sayfa->set('listeDurum', 'ertelendi');
        $this->assertCount(1, $sayfa->instance()->bulgular);

        $sayfa->call('durumDegistir', $b->id, 'kapandi')->assertStatus(422);
    }

    public function test_secilen_bulgular_tek_dof_olarak_aktarilir(): void
    {
        $a = SahaBulgusu::create(['firma_id' => $this->firma->id, 'bolum' => 'Depo', 'uygunsuzluk' => 'Raf sabit değil', 'olasilik' => 3, 'siddet' => 4]);
        $b = SahaBulgusu::create(['firma_id' => $this->firma->id, 'uygunsuzluk' => 'Etiket eksik', 'oncelik' => 'dusuk']);
        $baska = SahaBulgusu::create(['firma_id' => Firma::factory()->for($this->uzman)->create()->id, 'uygunsuzluk' => 'Başka işyeri']);

        // Farklı işyerleri tek DÖF'te birleşmez
        Livewire::test(HizliSahaBulgusu::class)->set('secili', [$a->id, $baska->id])->call('dofeAktar')->assertNoRedirect();

        Livewire::test(HizliSahaBulgusu::class)->set('secili', [$a->id, $b->id])->call('dofeAktar')->assertRedirect(DofOlustur::getUrl());
        $maddeler = session('dof_aktarim')['maddeler'];

        $this->assertCount(2, $maddeler);
        $this->assertSame('[Depo] Raf sabit değil', $maddeler[0]['tespit']);
        $this->assertSame('yuksek', $maddeler[0]['oncelik']);
        $this->assertSame('dusuk', $maddeler[1]['oncelik']);
        $this->assertSame($a->id, $maddeler[0]['bulgu_id']);
    }

    public function test_word_tablosundan_her_satir_bulgu_olur(): void
    {
        Storage::fake('public');
        Storage::fake('local');

        $word = new \PhpOffice\PhpWord\PhpWord;
        $s = $word->addSection();
        $bilgi = $s->addTable();
        $bilgi->addRow();
        $bilgi->addCell()->addText('Alan / Bölge');
        $bilgi->addCell()->addText('Bina cephesi');
        $t = $s->addTable();
        $t->addRow();
        foreach (['#', 'Tespit', 'Öncelik', 'Öneri / Düzeltici Faaliyet', 'Sorumlu', 'Termin', 'Durum', 'Foto'] as $h) {
            $t->addCell()->addText($h);
        }
        $foto = UploadedFile::fake()->image('f.jpg', 60, 40);   // değişkende kalsın: geçici dosyası silinmesin
        foreach ([['1', 'Korkuluk yok', 'Kritik', 'Korkuluk kurulmalı', 'İşveren', '15.10.2026', 'Açık'], ['2', 'Moloz dağınık', 'Orta', 'Toplanmalı', 'İşveren', '07.11.2026', 'Açık']] as $r) {
            $t->addRow();
            foreach ($r as $d) {
                $t->addCell()->addText($d);
            }
            $t->addCell()->addImage($foto->getPathname(), ['width' => 40]);
        }
        $yol = tempnam(sys_get_temp_dir(), 'blg').'.docx';
        \PhpOffice\PhpWord\IOFactory::createWriter($word, 'Word2007')->save($yol);

        Livewire::test(HizliSahaBulgusu::class)
            ->set('firmaId', $this->firma->id)
            ->callAction('dosyadanAktar', data: [
                'dosya' => UploadedFile::fake()->createWithContent('DOF_rapor.docx', file_get_contents($yol)),
            ])
            ->assertHasNoActionErrors();

        $bulgular = SahaBulgusu::orderBy('id')->get();
        $this->assertCount(2, $bulgular);
        $this->assertSame('Korkuluk yok', $bulgular[0]->uygunsuzluk);
        $this->assertSame('kritik', $bulgular[0]->oncelikAnahtari());
        $this->assertSame('2026-10-15', $bulgular[0]->termin->toDateString());
        $this->assertSame('Bina cephesi', $bulgular[0]->bolum);
        $this->assertSame('dosya', $bulgular[0]->kaynak);
        $this->assertCount(1, $bulgular[0]->fotograflar);
        Storage::disk('public')->assertExists($bulgular[0]->fotograflar[0]);
    }

    public function test_ai_kalan_tespitler_ayri_bulgu_olarak_eklenir(): void
    {
        Livewire::test(HizliSahaBulgusu::class)
            ->set('firmaId', $this->firma->id)
            ->set('bolum', 'Şantiye')
            ->set('kaydedilenFotolar', ['saha-bulgu-foto/x.jpg'])
            ->set('aiEkTespitler', [
                ['tespit' => 'Baret kullanılmıyor', 'oneriler' => ['Baret zorunlu'], 'risk_derecesi' => 2, 'kategori' => 'KKD'],
                ['tespit' => 'İskele ayağı boşta', 'oneriler' => ['Taban plakası'], 'risk_derecesi' => 1],
            ])
            ->call('kalanTespitleriEkle')
            ->assertSet('aiEkTespitler', []);

        $bulgular = SahaBulgusu::orderBy('id')->get();
        $this->assertCount(2, $bulgular);
        $this->assertSame('yuksek', $bulgular[0]->oncelikAnahtari());
        $this->assertSame('kritik', $bulgular[1]->oncelikAnahtari());
        $this->assertSame('Şantiye', $bulgular[0]->bolum);
        $this->assertSame('ai', $bulgular[0]->kaynak);
        $this->assertSame(['saha-bulgu-foto/x.jpg'], $bulgular[0]->fotograflar);
    }
}
