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
}
