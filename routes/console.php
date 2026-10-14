<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Firmalar Çöp Kutusu: süresi (Firma::COP_SURESI_AY) dolanları kalıcı sil.
// Sunucuda cron yoksa Firmalar listesi açılırken de temizlenir (ListFirmas::mount).
Schedule::command('model:prune', ['--model' => [\App\Models\Firma::class]])->daily();

// Telefon bildirimi: her sabah yeni kritik/uyarıların özeti (App\Support\TelefonBildirimi).
Schedule::command('mehse:telefon-bildirimi')->dailyAt('08:30')->timezone('Europe/Istanbul');

// Büyük risk değerlendirmesi PDF'leri (RiskDegerlendirmesi::PDF_ARKA_PLAN_ESIGI üstü) arka planda.
// cPanel cron her dakika "artisan schedule:run" çalıştırmalı (PHP CLI ile; CGI komut adını iletmez).
Schedule::command('risk:pdf-uret')->everyMinute()->withoutOverlapping(30);
