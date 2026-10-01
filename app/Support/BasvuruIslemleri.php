<?php

namespace App\Support;

use App\Models\Basvuru;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * OSGB başvurusunun sahip tarafından incelenmesi (App\Filament\Pages\Basvurular).
 * Onay: başvuran adına hesap açılır, deneme süresi başlar, geçici şifre
 * yalnız bir kez döndürülür (e-posta gönderilmez — sahip kişiye iletir).
 */
class BasvuruIslemleri
{
    /**
     * @return array{user: User, sifre: string}
     *
     * @throws ValidationException e-posta başka bir hesapta kayıtlıysa
     */
    public static function onayla(Basvuru $basvuru, User $inceleyen): array
    {
        if ($basvuru->durum !== 'beklemede') {
            throw ValidationException::withMessages(['basvuru' => 'Bu başvuru zaten incelenmiş.']);
        }

        if (User::query()->where('email', $basvuru->eposta)->exists()) {
            throw ValidationException::withMessages(['basvuru' => "{$basvuru->eposta} adresiyle zaten bir hesap var."]);
        }

        $sifre = Str::password(12, symbols: false);

        $user = DB::transaction(function () use ($basvuru, $inceleyen, $sifre): User {
            $user = User::create([
                'name' => $basvuru->ad_soyad,
                'email' => $basvuru->eposta,
                'telefon' => $basvuru->telefon,
                'password' => $sifre,
                'rol' => 'uzman',
                'aktif' => true,
                'abonelik_plani' => 'deneme',
                'abonelik_baslangic' => today(),
                'abonelik_bitis' => today()->addDays((int) config('isg.kayit.osgb_deneme_gun', 90)),
                // Başvuruda onaylanan yasal metinler hesaba taşınır (Güvenlik sayfası).
                'yasal_onaylar' => $basvuru->onaylar ?: null,
            ]);

            $basvuru->update([
                'durum' => 'onaylandi',
                'user_id' => $user->id,
                'inceleyen_id' => $inceleyen->id,
                'incelendi_at' => now(),
            ]);

            return $user;
        });

        return ['user' => $user, 'sifre' => $sifre];
    }

    public static function reddet(Basvuru $basvuru, User $inceleyen, ?string $neden): void
    {
        $basvuru->update([
            'durum' => 'reddedildi',
            'red_nedeni' => $neden,
            'inceleyen_id' => $inceleyen->id,
            'incelendi_at' => now(),
        ]);
    }
}
