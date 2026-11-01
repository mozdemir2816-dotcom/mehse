<?php

namespace Tests\Feature;

use App\Filament\Auth\OsgbBasvuru;
use App\Mail\YeniTeklifTalebi;
use App\Models\Basvuru;
use App\Models\TeklifTalebi;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use Tests\TestCase;

class YoneticiBildirimiTest extends TestCase
{
    use RefreshDatabase;

    private function teklifFormu(): array
    {
        return ['firma' => 'Örnek Metal Ltd.', 'kisi' => 'Ali Veli', 'telefon' => '0506 111 22 33', 'calisan' => 45, 'sinif' => 'Tehlikeli', 'kvkk' => '1'];
    }

    private function osgbGonder(): void
    {
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        Livewire::test(OsgbBasvuru::class)
            ->fillForm([
                'osgb_adi' => 'Örnek OSGB Ltd.', 'yetki_no' => 'OSGB-1234', 'vergi_no' => '1234567890',
                'iletisim_eposta' => 'iletisim@ornekosgb.com', 'ad_soyad' => 'Ayşe Başvuran', 'eposta' => 'ayse@ornekosgb.com',
                'telefon' => '0532 000 00 00', 'yasal_onay_sozlesme' => true, 'yasal_onay_kvkk' => true,
            ])
            ->call('gonder')
            ->assertHasNoFormErrors();
    }

    public function test_teklif_talebi_telegrama_ve_epostaya_gider(): void
    {
        config(['services.telegram.bot_token' => 'TEST-TOKEN', 'services.telegram.chat_id' => '42', 'isg.teklif_bildirim_eposta' => 'sahip@example.test']);
        Http::fake(['api.telegram.org/*' => Http::response(['ok' => true])]);
        Mail::fake();

        $this->post(route('tanitim.teklif'), $this->teklifFormu())->assertSessionHas('teklif_gonderildi');

        Http::assertSent(fn (Request $r) => $r->url() === 'https://api.telegram.org/botTEST-TOKEN/sendMessage'
            && $r['chat_id'] === '42'
            && str_contains($r['text'], '🚨 YENİ İSG TEKLİF TALEBİ!')
            && str_contains($r['text'], '• Firma: Örnek Metal Ltd.')
            && str_contains($r['text'], '• Yetkili: Ali Veli')
            && str_contains($r['text'], '• Çalışan: 45')
            && str_contains($r['text'], '• Tehlike Sınıfı: Tehlikeli')
            && str_contains($r['text'], '/admin/teklif-talepleri'));
        Mail::assertSent(YeniTeklifTalebi::class, fn ($m) => $m->hasTo('sahip@example.test'));
    }

    public function test_telegram_ayari_yoksa_istek_atilmaz(): void
    {
        config(['services.telegram.bot_token' => null, 'services.telegram.chat_id' => null]);
        Http::fake();
        Mail::fake();

        $this->post(route('tanitim.teklif'), $this->teklifFormu())->assertSessionHas('teklif_gonderildi');

        Http::assertNothingSent();
        $this->assertSame(1, TeklifTalebi::count());
    }

    public function test_telegram_ve_eposta_patlasa_da_form_tamamlanir(): void
    {
        config(['services.telegram.bot_token' => 'X', 'services.telegram.chat_id' => '1', 'isg.teklif_bildirim_eposta' => 'sahip@example.test']);
        Http::fake(['api.telegram.org/*' => Http::response(['ok' => false], 500)]);
        Mail::shouldReceive('to')->andThrow(new \RuntimeException('SMTP kapalı'));

        $this->post(route('tanitim.teklif'), $this->teklifFormu())
            ->assertRedirect(url('/').'#teklif')
            ->assertSessionHas('teklif_gonderildi');

        $this->assertSame(1, TeklifTalebi::count());
    }

    public function test_osgb_basvurusu_telegrama_ve_epostaya_gider(): void
    {
        config(['services.telegram.bot_token' => 'T', 'services.telegram.chat_id' => '7', 'isg.teklif_bildirim_eposta' => 'sahip@example.test', 'mail.default' => 'array']);
        Http::fake(['api.telegram.org/*' => Http::response(['ok' => true])]);

        $this->osgbGonder();

        $this->assertSame(1, Basvuru::count());
        Http::assertSent(fn (Request $r) => str_contains($r['text'], '🏢 YENİ OSGB KURUMSAL BAŞVURUSU!')
            && str_contains($r['text'], '• OSGB: Örnek OSGB Ltd.')
            && str_contains($r['text'], '• Yetkili: Ayşe Başvuran')
            && str_contains($r['text'], '• Telefon: 0532 000 00 00')
            && str_contains($r['text'], '• E-posta: ayse@ornekosgb.com')
            && str_contains($r['text'], '/admin/basvurular'));

        $mesajlar = app('mailer')->getSymfonyTransport()->messages();
        $this->assertCount(1, $mesajlar);
        $eposta = $mesajlar->first()->getOriginalMessage();
        $this->assertSame('sahip@example.test', $eposta->getTo()[0]->getAddress());
        $this->assertSame('Yeni OSGB başvurusu — Örnek OSGB Ltd.', $eposta->getSubject());
        $this->assertStringContainsString('• OSGB: Örnek OSGB Ltd.', $eposta->getTextBody());
    }

    public function test_osgb_bildirimi_patlasa_da_basvuru_kaydedilir(): void
    {
        config(['services.telegram.bot_token' => 'T', 'services.telegram.chat_id' => '7', 'isg.teklif_bildirim_eposta' => 'sahip@example.test']);
        Http::fake(fn () => throw new \Illuminate\Http\Client\ConnectionException('zaman aşımı'));
        Mail::shouldReceive('raw')->andThrow(new \RuntimeException('SMTP kapalı'));

        $this->osgbGonder();

        $this->assertSame(1, Basvuru::count());
    }
}
