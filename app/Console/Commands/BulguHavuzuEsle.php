<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Support\BulguHavuzu;
use Illuminate\Console\Command;

/**
 * Saha kontrolleri 4. aşama: 3. aşamadan önce girilmiş DÖF ve Tespit-Öneri
 * maddelerini ortak saha bulgularına bağlar (BulguHavuzu::eskileriBagla).
 * Tekrar çalıştırılabilir. Canlıda shell olmadığı için aynı iş Saha Bulguları
 * sayfasındaki "Eski kayıtları bulgulara bağla" düğmesiyle de yapılır.
 */
class BulguHavuzuEsle extends Command
{
    protected $signature = 'bulgu:havuz-esle {--kuru : Yalnız bağlanacak madde sayısını göster}';

    protected $description = 'Eski DÖF ve Tespit-Öneri maddelerini ortak saha bulgularına bağlar';

    public function handle(): int
    {
        $toplam = 0;

        foreach (User::all() as $user) {
            $sayi = BulguHavuzu::baglanmamisMaddeSayisi($user->id);

            if ($sayi === 0) {
                continue;
            }

            if ($this->option('kuru')) {
                $this->line("{$user->name}: {$sayi} bağlanmamış madde");
                $toplam += $sayi;

                continue;
            }

            $acilan = BulguHavuzu::eskileriBagla($user->id);
            $this->line("{$user->name}: {$acilan} bulgu açıldı");
            $toplam += $acilan;
        }

        $this->info($this->option('kuru') ? "Toplam {$toplam} madde bağlanacak." : "Toplam {$toplam} bulgu açıldı.");

        return self::SUCCESS;
    }
}
