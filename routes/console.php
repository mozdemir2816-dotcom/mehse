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
