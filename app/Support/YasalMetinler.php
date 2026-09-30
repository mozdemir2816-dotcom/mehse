<?php

namespace App\Support;

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
     * @return array<int, array{anahtar: string, baslik: string, revizyon: ?string, onay_at: string}>
     */
    public static function onayKaydi(): array
    {
        return collect(self::aktifler())
            ->map(fn (array $m, string $anahtar): array => [
                'anahtar' => $anahtar,
                'baslik' => $m['baslik'],
                'revizyon' => $m['revizyon'] ?? null,
                'onay_at' => now()->toIso8601String(),
            ])
            ->values()
            ->all();
    }
}
