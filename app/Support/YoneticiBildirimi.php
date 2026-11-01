<?php

namespace App\Support;

use App\Mail\YeniTeklifTalebi;
use App\Models\Basvuru;
use App\Models\TeklifTalebi;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * Siteden yeni teklif talebi / OSGB başvurusu gelince yöneticiye anlık bildirim:
 * Telegram (config services.telegram — boşsa atlanır) + e-posta (config isg.teklif_bildirim_eposta).
 * Hiçbir kanal hatası form gönderimini kesmez; hata log'a yazılır.
 */
class YoneticiBildirimi
{
    public function yeniTeklifBildir(TeklifTalebi $teklif): void
    {
        $this->telegram(implode("\n", [
            '🚨 YENİ İSG TEKLİF TALEBİ!',
            '• Firma: '.$teklif->firma_adi,
            '• Yetkili: '.$teklif->kisi,
            '• Telefon: '.$teklif->telefon,
            '• Çalışan: '.$teklif->calisan_sayisi,
            '• Tehlike Sınıfı: '.$teklif->tehlike_sinifi,
            ...($teklif->not ? ['• '.$teklif->not] : []),
            '🔗 Panele Git: '.url('/admin/teklif-talepleri'),
        ]));

        $this->eposta(fn (string $alici) => Mail::to($alici)->send(new YeniTeklifTalebi($teklif)));
    }

    public function yeniOsgbBasvuruBildir(Basvuru $basvuru): void
    {
        $metin = implode("\n", [
            '🏢 YENİ OSGB KURUMSAL BAŞVURUSU!',
            '• OSGB: '.$basvuru->osgb_adi,
            '• Yetkili: '.$basvuru->ad_soyad,
            '• Telefon: '.($basvuru->telefon ?: '—'),
            '• E-posta: '.$basvuru->eposta,
            '🔗 Onaylamak İçin: '.url('/admin/basvurular'),
        ]);

        $this->telegram($metin);
        $this->eposta(fn (string $alici) => Mail::raw($metin, fn ($m) => $m->to($alici)
            ->subject('Yeni OSGB başvurusu — '.$basvuru->osgb_adi)));
    }

    private function telegram(string $metin): void
    {
        $token = config('services.telegram.bot_token');
        $sohbet = config('services.telegram.chat_id');

        if (blank($token) || blank($sohbet)) {
            return;
        }

        try {
            Http::timeout(4)->asForm()->post("https://api.telegram.org/bot{$token}/sendMessage", [
                'chat_id' => $sohbet,
                'text' => $metin,
                'disable_web_page_preview' => 'true',
            ])->throw();
        } catch (Throwable $e) {
            // Hata mesajı bot token'ını içeren adresi taşıyabilir — log'a yazılmaz.
            Log::error('Yönetici Telegram bildirimi gönderilemedi', ['hata' => class_basename($e)]);
        }
    }

    /** @param  callable(string): void  $gonder */
    private function eposta(callable $gonder): void
    {
        $alici = config('isg.teklif_bildirim_eposta');

        if (blank($alici)) {
            return;
        }

        try {
            $gonder($alici);
        } catch (Throwable $e) {
            Log::error('Yönetici e-posta bildirimi gönderilemedi', ['hata' => $e->getMessage()]);
        }
    }
}
