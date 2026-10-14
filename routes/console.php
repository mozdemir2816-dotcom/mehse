<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Canlı sunucuda proc_open kapalı (14.10.2026): Schedule::command() görevi ayrı süreç olarak
// başlatamıyor, bu yüzden görevler Schedule::call() + Artisan::call() ile aynı süreçte çalışır.
// cPanel cron her dakika "/usr/local/bin/php artisan schedule:run" çalıştırır.

// Firmalar Çöp Kutusu: süresi (Firma::COP_SURESI_AY) dolanları kalıcı sil.
// Sunucuda cron yoksa Firmalar listesi açılırken de temizlenir (ListFirmas::mount).
Schedule::call(fn () => Artisan::call('model:prune', ['--model' => [\App\Models\Firma::class]]))
    ->name('model:prune')->daily();

// Telefon bildirimi: her sabah yeni kritik/uyarıların özeti (App\Support\TelefonBildirimi).
Schedule::call(fn () => Artisan::call('mehse:telefon-bildirimi'))
    ->name('mehse:telefon-bildirimi')->dailyAt('08:30')->timezone('Europe/Istanbul');

// Büyük risk değerlendirmesi PDF'leri (RiskDegerlendirmesi::PDF_ARKA_PLAN_ESIGI üstü) arka planda.
Schedule::call(fn () => Artisan::call('risk:pdf-uret'))
    ->name('risk:pdf-uret')->everyMinute()->withoutOverlapping(30);

// Öneri (düzeltici faaliyet) metni boş risk maddeleri Gemini ile doldurulur; boş kalmayınca anında biter.
Schedule::call(fn () => Artisan::call('risk:oneri-tamamla'))
    ->name('risk:oneri-tamamla')->everyMinute()->withoutOverlapping(10);
