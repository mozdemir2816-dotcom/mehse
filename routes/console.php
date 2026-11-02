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
// KVKK saklama süreleri: teklif talepleri 1 yıl, reddedilen başvurular 1 yıl (yasal/kvkk.blade.php).
// Sunucuda cron yoksa Firmalar listesi açılırken de temizlenir (ListFirmas::mount).
Schedule::call(fn () => Artisan::call('model:prune', ['--model' => [\App\Models\Firma::class, \App\Models\TeklifTalebi::class, \App\Models\Basvuru::class]]))
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

// "Eksik Puan/Önlemleri AI ile Tamamla" kuyruğu: dakikada bir istekte 25 madde (02.11.2026).
Schedule::call(fn () => Artisan::call('risk:puan-tamamla'))
    ->name('risk:puan-tamamla')->everyMinute()->withoutOverlapping(10);

// Kütüphane onarımı: eksik alanlı kütüphane maddeleri 15 dakikada bir parti (40 madde; günlük sınırlı, kota dolunca
// ertesi güne kadar durur), analiz kuyrukları boşken (02.11.2026).
Schedule::call(fn () => Artisan::call('risk:repair-library-all-fields', ['--zamanlanmis' => true, '--limit' => 40]))
    ->name('risk:repair-library-all-fields')->everyFifteenMinutes()->withoutOverlapping(30);

// Günlük veritabanı yedeği (Hizmet Sözleşmesi md.5): storage/app/private/yedekler, 30 gün saklanır.
Schedule::call(fn () => Artisan::call('mehse:yedekle'))
    ->name('mehse:yedekle')->dailyAt('03:00')->timezone('Europe/Istanbul');
