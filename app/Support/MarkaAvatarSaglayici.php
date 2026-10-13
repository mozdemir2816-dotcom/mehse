<?php

namespace App\Support;

use Filament\AvatarProviders\Contracts\AvatarProvider;
use Filament\Facades\Filament;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;

/**
 * Kullanıcı avatarı: baş harfler, MEHSE lacivert → turkuaz zeminde (13.10.2026).
 *
 * Filament'in varsayılanı ui-avatars.com'dan siyah zeminli görsel çekiyordu;
 * bu sağlayıcı SVG'yi yerelde data URI olarak üretir — dış istek yok, her temada
 * marka renginde.
 */
class MarkaAvatarSaglayici implements AvatarProvider
{
    public function get(Model | Authenticatable $record): string
    {
        $harfler = collect(preg_split('/\s+/u', trim(Filament::getNameForDefaultAvatar($record))) ?: [])
            ->filter()
            ->map(fn (string $parca) => mb_substr($parca, 0, 1))
            ->take(2)
            ->join('');

        $harfler = htmlspecialchars(TurkceMetin::buyuk($harfler), ENT_XML1);

        $svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 64 64">'
            .'<defs><linearGradient id="g" x1="0" y1="0" x2="1" y2="1">'
            .'<stop offset="0" stop-color="#0A345A"/><stop offset="1" stop-color="#099291"/>'
            .'</linearGradient></defs>'
            .'<rect width="64" height="64" fill="url(#g)"/>'
            .'<text x="32" y="32" dy=".35em" text-anchor="middle" fill="#fff" '
            .'font-family="DM Sans, Arial, sans-serif" font-size="24" font-weight="700">'.$harfler.'</text>'
            .'</svg>';

        return 'data:image/svg+xml;base64,'.base64_encode($svg);
    }
}
