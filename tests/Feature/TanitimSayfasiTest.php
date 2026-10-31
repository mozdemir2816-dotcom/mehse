<?php

namespace Tests\Feature;

use App\Filament\Resources\TeklifTalepleris\Pages\ListTeklifTalepleris;
use App\Mail\YeniTeklifTalebi;
use App\Models\TeklifTalebi;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use Tests\TestCase;

/** Herkese açık ana sayfa (tanıtım) + teklif formu + panelde Teklif Talepleri. */
class TanitimSayfasiTest extends TestCase
{
    use RefreshDatabase;

    private function form(array $ek = []): array
    {
        return $ek + [
            'firma' => 'Örnek Metal San. Ltd.',
            'kisi' => 'Ali Veli',
            'telefon' => '0532 111 22 33',
            'calisan' => 45,
            'sinif' => 'Tehlikeli',
            'kvkk' => '1',
        ];
    }

    public function test_ana_sayfa_giris_olmadan_acilir_ve_iletisim_bilgileri_dogru(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('Evrakla Değil, ', false)
            ->assertSee('"@context":"https://schema.org"', false)
            ->assertSee('"@type":"FAQPage"', false)
            ->assertSee('İSG-KATİP sözleşmesi ne kadar sürede tamamlanır?')
            ->assertSee('id="yazilim"', false)
            ->assertSee('/admin/register', false)
            ->assertSee('/admin/osgb-basvuru', false)
            ->assertSee('/admin/login', false)
            ->assertSee('Üye Girişi')
            ->assertSee('mozdemir2816@gmail.com')
            ->assertSee('0506 475 48 55')
            ->assertSee('tel:+905064754855', false)
            ->assertSee('wa.me/905064754855', false)
            ->assertSee('css/tanitim.css', false);
    }

    public function test_eski_tanitim_adresi_ana_sayfaya_yonlenir(): void
    {
        $this->get('/tanitim')->assertRedirect(url('/'))->assertStatus(301);
    }

    public function test_giris_ekraninda_tanitim_baglantisi_var(): void
    {
        $this->get('/admin/login')->assertOk()->assertSee('Ana Sayfaya Dön');
    }

    public function test_teklif_formu_talebi_kaydeder(): void
    {
        $this->post(route('tanitim.teklif'), $this->form())
            ->assertRedirect(url('/').'#teklif')
            ->assertSessionHas('teklif_gonderildi');

        $t = TeklifTalebi::sole();
        $this->assertSame('Örnek Metal San. Ltd.', $t->firma_adi);
        $this->assertSame(45, $t->calisan_sayisi);
        $this->assertSame('yeni', $t->durum);

        $this->followingRedirects()->post(route('tanitim.teklif'), $this->form())->assertSee('Talebiniz alındı');
    }

    /**
     * Uçtan uca: ana sayfadaki GERÇEK formu HTML'den okur (action, method, @csrf, alan adları)
     * ve yalnız oradaki alan adlarıyla gönderir. Blade'de bir name="..." ya da action
     * değişirse bu test kırılır — doğrudan rotaya POST eden testler bunu yakalayamaz.
     */
    public function test_sayfadaki_form_rotaya_baglidir_ve_gonderilince_veritabanina_kaydeder(): void
    {
        $html = $this->get('/')->assertOk()->getContent();

        $dom = new \DOMDocument;
        libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="UTF-8">'.$html);
        libxml_clear_errors();
        $xp = new \DOMXPath($dom);

        $form = $xp->query('//form[@aria-label="Teklif formu"]')->item(0);
        $this->assertNotNull($form, 'Ana sayfada teklif formu bulunamadı.');
        $this->assertSame('post', strtolower($form->getAttribute('method')));
        $this->assertSame(route('tanitim.teklif'), $form->getAttribute('action'));

        $alanlar = [];
        foreach ($xp->query('.//input[@name] | .//select[@name] | .//textarea[@name]', $form) as $el) {
            $alanlar[$el->getAttribute('name')] = $el->getAttribute('value');
        }

        $this->assertNotEmpty($alanlar['_token'] ?? null, '@csrf token alanı formda yok.');
        foreach (['firma', 'kisi', 'telefon', 'calisan', 'sinif', 'kvkk', 'web'] as $ad) {
            $this->assertArrayHasKey($ad, $alanlar, "Formda name=\"{$ad}\" alanı yok.");
        }

        $secenekler = [];
        foreach ($xp->query('.//select[@name="sinif"]/option[not(@disabled)]', $form) as $opt) {
            $secenekler[] = trim($opt->textContent);
        }
        $this->assertSame(TeklifTalebi::TEHLIKE_SINIFLARI, $secenekler);

        // Yalnız formda bulunan alanlarla, bir ziyaretçinin dolduracağı gibi gönder.
        $gonderilen = array_merge($alanlar, [
            'firma' => 'Uçtan Uca Makine A.Ş.',
            'kisi' => 'Ayşe Yılmaz',
            'telefon' => '0506 123 45 67',
            'calisan' => '120',
            'sinif' => $secenekler[2],
            'kvkk' => $alanlar['kvkk'],
            'web' => '',
        ]);

        $this->post($form->getAttribute('action'), $gonderilen)
            ->assertSessionHasNoErrors()
            ->assertRedirect(url('/').'#teklif');

        $this->assertDatabaseCount('teklif_talepleri', 1);
        $this->assertDatabaseHas('teklif_talepleri', [
            'firma_adi' => 'Uçtan Uca Makine A.Ş.',
            'kisi' => 'Ayşe Yılmaz',
            'telefon' => '0506 123 45 67',
            'calisan_sayisi' => 120,
            'tehlike_sinifi' => 'Çok Tehlikeli',
            'durum' => 'yeni',
        ]);

        // Yönlendirme sonrası ziyaretçi teşekkür mesajını görür.
        $this->get('/')->assertSee('Talebiniz alındı');
    }

    public function test_teklif_talebi_sahibe_eposta_bildirimi_gonderir(): void
    {
        Mail::fake();
        config(['isg.teklif_bildirim_eposta' => 'sahip@example.test']);

        $this->post(route('tanitim.teklif'), $this->form())->assertSessionHas('teklif_gonderildi');

        Mail::assertSent(YeniTeklifTalebi::class, fn (YeniTeklifTalebi $m) => $m->hasTo('sahip@example.test')
            && $m->talep->firma_adi === 'Örnek Metal San. Ltd.'
            && str_contains($m->render(), '45'));
    }

    public function test_alici_tanimli_degilse_eposta_gitmez_ama_talep_kaydedilir(): void
    {
        Mail::fake();
        config(['isg.teklif_bildirim_eposta' => null]);

        $this->post(route('tanitim.teklif'), $this->form())->assertSessionHas('teklif_gonderildi');

        Mail::assertNothingSent();
        $this->assertSame(1, TeklifTalebi::count());
    }

    public function test_eposta_gonderimi_patlasa_da_talep_kaydedilir_ve_ziyaretci_hata_gormez(): void
    {
        config(['isg.teklif_bildirim_eposta' => 'sahip@example.test']);
        Mail::shouldReceive('to')->andThrow(new \RuntimeException('SMTP kapalı'));

        $this->post(route('tanitim.teklif'), $this->form())
            ->assertRedirect(url('/').'#teklif')
            ->assertSessionHas('teklif_gonderildi');

        $this->assertSame(1, TeklifTalebi::count());
    }

    public function test_eksik_veya_gecersiz_form_kaydedilmez(): void
    {
        $this->from('/')->post(route('tanitim.teklif'), $this->form(['telefon' => 'abc', 'sinif' => 'Başka', 'kvkk' => null]))
            ->assertSessionHasErrors(['telefon', 'sinif', 'kvkk']);

        $this->post(route('tanitim.teklif'), $this->form(['calisan' => 0]))->assertSessionHasErrors('calisan');
        $this->assertSame(0, TeklifTalebi::count());
    }

    public function test_bot_tuzagi_dolu_ise_kaydedilmez(): void
    {
        $this->post(route('tanitim.teklif'), $this->form(['web' => 'http://spam.example']))->assertSessionHas('teklif_gonderildi');

        $this->assertSame(0, TeklifTalebi::count());
    }

    public function test_istek_siniri_asilinca_engellenir(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->post(route('tanitim.teklif'), $this->form());
        }

        $this->post(route('tanitim.teklif'), $this->form())->assertStatus(429);
        $this->assertSame(5, TeklifTalebi::count());
    }

    public function test_panelde_yalniz_sahip_gorur_ve_durum_guncelleyebilir(): void
    {
        $talep = TeklifTalebi::create(['firma_adi' => 'X', 'kisi' => 'Y', 'telefon' => '0532 000 00 00', 'calisan_sayisi' => 10, 'tehlike_sinifi' => 'Az Tehlikeli']);

        $this->actingAs(User::factory()->create(['rol' => 'uzman']));
        $this->get('/admin/teklif-talepleri')->assertForbidden();

        $this->actingAs(User::factory()->create(['rol' => 'sahip']));
        Livewire::test(ListTeklifTalepleris::class)->assertCanSeeTableRecords([$talep]);

        Livewire::test(ListTeklifTalepleris::class)
            ->callTableAction('edit', $talep, ['durum' => 'arandi', 'not' => 'Pazartesi dönülecek'])
            ->assertHasNoTableActionErrors();

        $this->assertSame('arandi', $talep->fresh()->durum);
    }

    public function test_durum_tablodan_dogrudan_secilerek_degisir(): void
    {
        $talep = TeklifTalebi::create(['firma_adi' => 'X', 'kisi' => 'Y', 'telefon' => '0532 000 00 00', 'calisan_sayisi' => 10, 'tehlike_sinifi' => 'Az Tehlikeli']);
        $this->actingAs(User::factory()->create(['rol' => 'sahip']));

        Livewire::test(ListTeklifTalepleris::class)
            ->call('updateTableColumnState', 'durum', (string) $talep->getKey(), 'teklif_verildi');
        $this->assertSame('teklif_verildi', $talep->fresh()->durum);

        // Listede olmayan değer kaydedilmez.
        Livewire::test(ListTeklifTalepleris::class)
            ->call('updateTableColumnState', 'durum', (string) $talep->getKey(), 'uydurma');
        $this->assertSame('teklif_verildi', $talep->fresh()->durum);
    }

    /** @return array<string, array{string, ?string}> */
    public static function telefonlar(): array
    {
        return [
            'boşluklu 0 ile' => ['0532 111 22 33', '905321112233'],
            '+90 ile' => ['+90 (532) 111-22-33', '905321112233'],
            '0090 ile' => ['0090 532 111 22 33', '905321112233'],
            'başında 0 yok' => ['5321112233', '905321112233'],
            'sabit hat' => ['0224 111 22 33', null],
            'eksik' => ['0532 111', null],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('telefonlar')]
    public function test_whatsapp_numarasi_turk_cep_bicimine_cevrilir(string $telefon, ?string $beklenen): void
    {
        $talep = new TeklifTalebi(['telefon' => $telefon, 'kisi' => 'Ali Veli', 'firma_adi' => 'Örnek A.Ş.']);

        $this->assertSame($beklenen, $talep->whatsappNumarasi());
        $this->assertSame($beklenen === null, $talep->whatsappUrl() === null);
    }

    public function test_whatsapp_dugmesi_hazir_sablonla_musteriye_acilir(): void
    {
        $cep = TeklifTalebi::create(['firma_adi' => 'Örnek Metal', 'kisi' => 'Ali Veli', 'telefon' => '0532 111 22 33', 'calisan_sayisi' => 10, 'tehlike_sinifi' => 'Tehlikeli']);
        $sabit = TeklifTalebi::create(['firma_adi' => 'Sabit Hat Ltd.', 'kisi' => 'Z', 'telefon' => '0224 111 22 33', 'calisan_sayisi' => 5, 'tehlike_sinifi' => 'Az Tehlikeli']);
        $this->actingAs(User::factory()->create(['rol' => 'sahip']));

        $url = $cep->whatsappUrl();
        $this->assertStringStartsWith('https://wa.me/905321112233?text=', $url);
        $mesaj = rawurldecode(substr($url, strpos($url, '=') + 1));
        $this->assertStringContainsString('Merhaba Ali Veli', $mesaj);
        $this->assertStringContainsString('Örnek Metal için', $mesaj);

        Livewire::test(ListTeklifTalepleris::class)
            ->assertTableActionVisible('whatsapp', $cep)
            ->assertTableActionHasUrl('whatsapp', $url, $cep)
            ->assertTableActionShouldOpenUrlInNewTab('whatsapp', $cep)
            ->assertTableActionHidden('whatsapp', $sabit);
    }

    public function test_liste_ustunde_durum_sayaclari_ve_15li_sayfalama(): void
    {
        foreach (['yeni', 'yeni', 'yeni', 'arandi', 'teklif_verildi', 'teklif_verildi', 'kapandi'] as $i => $durum) {
            TeklifTalebi::create(['firma_adi' => "F{$i}", 'kisi' => 'K', 'telefon' => '0532 000 00 0'.$i, 'calisan_sayisi' => 1, 'tehlike_sinifi' => 'Az Tehlikeli', 'durum' => $durum]);
        }
        $this->actingAs(User::factory()->create(['rol' => 'sahip']));

        $sayac = Livewire::test(\App\Filament\Resources\TeklifTalepleris\Widgets\TeklifSayaclari::class);
        $stats = collect((fn () => $this->getStats())->call($sayac->instance()))
            ->mapWithKeys(fn ($s) => [(string) $s->getLabel() => $s]);

        $this->assertSame(['Toplam', 'Yeni', 'Arandı', 'Teklif verildi'], $stats->keys()->all());
        $this->assertSame([7, 3, 1, 2], $stats->map(fn ($s) => $s->getValue())->values()->all());
        $this->assertStringContainsString('filters', urldecode($stats['Yeni']->getUrl()));
        $this->assertStringContainsString('[value]=yeni', urldecode($stats['Yeni']->getUrl()));

        // Karttaki bağlantı listeyi gerçekten o duruma süzer.
        Livewire::withQueryParams(['filters' => ['durum' => ['value' => 'yeni']]])
            ->test(ListTeklifTalepleris::class)
            ->assertCanSeeTableRecords(TeklifTalebi::where('durum', 'yeni')->get())
            ->assertCanNotSeeTableRecords(TeklifTalebi::where('durum', '!=', 'yeni')->get());

        for ($i = 0; $i < 13; $i++) {
            TeklifTalebi::create(['firma_adi' => "Ek{$i}", 'kisi' => 'K', 'telefon' => '0532 111 11 11', 'calisan_sayisi' => 1, 'tehlike_sinifi' => 'Az Tehlikeli']);
        }
        Livewire::withQueryParams([])->test(ListTeklifTalepleris::class)->assertCountTableRecords(20)
            ->assertSet('tableRecordsPerPage', 15);

        // Kartlar sayfaya yerleşir (Filament widget'ı sayfa açıldıktan sonra yükler).
        $this->get('/admin/teklif-talepleri')->assertOk()->assertSeeLivewire(\App\Filament\Resources\TeklifTalepleris\Widgets\TeklifSayaclari::class);
    }

    public function test_yeni_teklif_rozeti_menude_ve_sekmede_gorunur(): void
    {
        TeklifTalebi::create(['firma_adi' => 'A', 'kisi' => 'A', 'telefon' => '0532 000 00 01', 'calisan_sayisi' => 1, 'tehlike_sinifi' => 'Az Tehlikeli']);
        TeklifTalebi::create(['firma_adi' => 'B', 'kisi' => 'B', 'telefon' => '0532 000 00 02', 'calisan_sayisi' => 1, 'tehlike_sinifi' => 'Az Tehlikeli']);
        TeklifTalebi::create(['firma_adi' => 'C', 'kisi' => 'C', 'telefon' => '0532 000 00 03', 'calisan_sayisi' => 1, 'tehlike_sinifi' => 'Az Tehlikeli', 'durum' => 'arandi']);

        $this->actingAs(User::factory()->create(['rol' => 'sahip']));
        $this->assertSame('2', \App\Filament\Pages\Pazarlama::getNavigationBadge());

        $sekme = collect(\App\Support\SayfaAileleri::sekmeler([\App\Filament\Pages\Pazarlama::class]))->firstWhere('etiket', 'Teklif Talepleri');
        $this->assertSame('2', $sekme['rozet']);

        $this->get('/admin/pazarlama')->assertOk()->assertSee('Yeni teklif talebi');

        // Talepleri göremeyen kullanıcıya rozet sızmaz.
        $this->actingAs(User::factory()->create(['rol' => 'uzman']));
        $this->assertNull(\App\Filament\Pages\Pazarlama::getNavigationBadge());
    }
}
