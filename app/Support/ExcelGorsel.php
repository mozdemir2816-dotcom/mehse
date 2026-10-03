<?php

namespace App\Support;

/**
 * Excel'e (PhpSpreadsheet Drawing) gömülecek görselin yolu. PhpSpreadsheet
 * medya dosyasını UZANTIYLA adlandırır; içerik başka türdeyse (ör. canlıdaki
 * İGU kaşesi: adı .jpeg, içeriği PNG) xlsx'te "jpeg = image/png" gibi
 * tutarsız bir tür kaydı oluşur — masaüstü Excel tolere ediyor ama telefon
 * görüntüleyicileri / Google E-Tablolar görseli göstermeyebilir. Uyuşmazlıkta
 * görsel doğru uzantılı geçici bir kopyaya alınır (istek sonunda silinir).
 */
final class ExcelGorsel
{
    private const UZANTILAR = [
        'image/png' => 'png',
        'image/jpeg' => 'jpeg',
        'image/gif' => 'gif',
        'image/bmp' => 'bmp',
    ];

    /** @var array<int, string> */
    private static array $geciciler = [];

    /** Gömülebilir yol; görsel okunamıyorsa ya da desteklenmeyen türdeyse null. */
    public static function yol(?string $tamYol): ?string
    {
        if (! $tamYol || ! is_file($tamYol)) {
            return null;
        }

        $uzanti = self::UZANTILAR[@getimagesize($tamYol)['mime'] ?? ''] ?? null;

        if (! $uzanti) {
            return null;
        }

        $mevcut = strtolower(pathinfo($tamYol, PATHINFO_EXTENSION));

        if ($mevcut === $uzanti || ($uzanti === 'jpeg' && $mevcut === 'jpg')) {
            return $tamYol;
        }

        $kopya = tempnam(sys_get_temp_dir(), 'xg').'.'.$uzanti;
        copy($tamYol, $kopya);

        if (! self::$geciciler) {
            register_shutdown_function(static function (): void {
                foreach (self::$geciciler as $g) {
                    @unlink($g);
                }
            });
        }

        self::$geciciler[] = $kopya;

        return $kopya;
    }
}
