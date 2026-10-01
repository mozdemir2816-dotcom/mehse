<?php

namespace App\Support;

use App\Models\User;
use Filament\Forms\Components\Checkbox;
use Illuminate\Support\HtmlString;

/**
 * Kayıt/başvuru formlarındaki yasal onaylar (sözleşme, KVKK) — config
 * isg.kayit.yasal_metinler. Bir metin yalnız `gorunum` Blade dosyası varsa
 * etkindir: kullanıcı metni verene kadar kutu hiç gösterilmez. Metnin kendisi
 * "görüntüle" bağlantısıyla modalda açılır (filament.components.yasal-modallar).
 */
class YasalMetinler
{
    /** @return array<string, array{baslik: string, baglanti: string, gorunum: string, revizyon: ?string}> */
    public static function aktifler(): array
    {
        return collect(config('isg.kayit.yasal_metinler', []))
            ->filter(fn (array $m): bool => filled($m['gorunum'] ?? null) && view()->exists($m['gorunum']))
            ->all();
    }

    /** @return array<int, Checkbox> her etkin metin için zorunlu onay kutusu */
    public static function onayAlanlari(): array
    {
        return collect(self::aktifler())
            ->map(fn (array $m, string $anahtar): Checkbox => Checkbox::make("yasal_onay_{$anahtar}")
                ->label(new HtmlString(
                    '<a href="#" class="mehse-yasal-baglanti" x-on:click.prevent.stop="$dispatch(\'open-modal\', { id: \'yasal-'.e($anahtar).'\' })">'
                    .e($m['baglanti']).'</a> '.e($m['onay_sonrasi'] ?? 'okudum ve kabul ediyorum')
                ))
                ->accepted()
                ->validationMessages(['accepted' => e($m['baslik']).' onaylanmalıdır.'])
                ->dehydrated(false))
            ->values()
            ->all();
    }

    /**
     * Onay izi — formdaki kutular dehydrate edilmediği için kayıt anında etkin
     * metinlerin tamamı onaylanmış sayılır (accepted() doğrulaması geçmeden
     * kayıt yapılamaz).
     *
     * @return array<int, array{anahtar: string, baslik: string, revizyon: ?string, onay_at: string, ip: ?string}>
     */
    public static function onayKaydi(): array
    {
        return collect(self::aktifler())
            ->map(fn (array $m, string $anahtar): array => self::onaySatiri($anahtar, $m))
            ->values()
            ->all();
    }

    /*
    | Kullanıcı bazlı onay (Güvenlik sayfası) — users.yasal_onaylar. Metnin
    | revizyonu değişince eski onay "güncel değil" sayılır, yeniden onay istenir.
    | Eski onaylar silinmez (iz), yeni satır eklenir.
    */

    /** @return array{anahtar: string, baslik: string, revizyon: ?string, onay_at: string, ip: ?string}|null kullanıcının bu metin için son onayı */
    public static function sonOnay(User $user, string $anahtar): ?array
    {
        return collect($user->yasal_onaylar ?? [])->where('anahtar', $anahtar)->last();
    }

    public static function guncelOnayliMi(User $user, string $anahtar): bool
    {
        $son = self::sonOnay($user, $anahtar);

        return $son !== null && ($son['revizyon'] ?? null) === (self::aktifler()[$anahtar]['revizyon'] ?? null);
    }

    public static function onayla(User $user, string $anahtar): bool
    {
        $metin = self::aktifler()[$anahtar] ?? null;

        if (! $metin || self::guncelOnayliMi($user, $anahtar)) {
            return false;
        }

        $user->yasal_onaylar = [...($user->yasal_onaylar ?? []), self::onaySatiri($anahtar, $metin)];
        $user->save();

        return true;
    }

    /** @param  array<string, mixed>  $metin */
    private static function onaySatiri(string $anahtar, array $metin): array
    {
        return [
            'anahtar' => $anahtar,
            'baslik' => $metin['baslik'],
            'revizyon' => $metin['revizyon'] ?? null,
            'onay_at' => now()->toIso8601String(),
            'ip' => request()?->ip(),
        ];
    }
}
