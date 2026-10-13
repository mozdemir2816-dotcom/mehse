<?php

namespace App\Console\Commands;

use App\Models\PushAbonelik;
use App\Models\User;
use App\Support\TelefonBildirimi;
use Illuminate\Console\Command;

/**
 * Günlük telefon bildirimi — routes/console.php her sabah 08:30'da (İstanbul)
 * çalıştırır. Canlıda zamanlayıcı yoksa cPanel > Cron Jobs'a bu komut doğrudan
 * günde bir kez eklenebilir: php artisan mehse:telefon-bildirimi
 */
class TelefonBildirimiGonder extends Command
{
    protected $signature = 'mehse:telefon-bildirimi';

    protected $description = 'Telefon bildirimi açık kullanıcılara yeni kritik/uyarı bildirimlerinin özetini gönderir';

    public function handle(): int
    {
        $kullanicilar = User::whereIn('id', PushAbonelik::query()->select('user_id'))->get();

        foreach ($kullanicilar as $kullanici) {
            $sonuc = TelefonBildirimi::gunlukOzet($kullanici);
            $this->line("{$kullanici->email}: {$sonuc['uyari']} yeni uyarı, {$sonuc['gonderildi']}/{$sonuc['abonelik']} cihaza gönderildi");
        }

        return self::SUCCESS;
    }
}
