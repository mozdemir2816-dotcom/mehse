<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use WeakMap;

/**
 * Kullanıcı ayarları (users.ayarlar JSON) — Ayarlar sayfasının tek kaynağı.
 *
 * Kayıtlı değer yoksa varsayılan döner; varsayılanlar bu özellik gelmeden önce
 * kodda/config'te sabit olan değerlerle birebir aynı, yani ayar kaydetmemiş
 * kullanıcı için hiçbir davranış değişmez.
 *
 * Eşikler "görüntüleyen kullanıcıya" göre çözülür (Auth), çünkü model
 * metotları (EgitimKaydi::durum() vb.) kullanıcı parametresi almıyor; Kontrol
 * Merkezi kriter muafiyeti ise verinin sahibine göre (PortfoyKarne $userId).
 */
class KullaniciAyarlari
{
    public const TEMALAR = [
        'klasik' => 'Klasik',
        'material' => 'Google Material',
        'windows11' => 'Windows 11',
        'saha' => 'Saha',
    ];

    public const YOGUNLUKLAR = [
        'rahat' => 'Rahat',
        'kompakt' => 'Kompakt',
    ];

    public const YAZI_BOYUTLARI = [
        'kucuk' => 'Küçük',
        'normal' => 'Normal',
        'buyuk' => 'Büyük',
    ];

    /**
     * Uyarı eşikleri: anahtar => [etiket, açıklama, varsayılan gün].
     * Varsayılan null ise config'ten okunur (mevcut config anahtarı korunur).
     */
    public const ESIKLER = [
        'egitim' => ['Eğitim geçerliliği', 'Çalışan eğitiminin geçerliliği bitmeden kaç gün önce "yakında" sayılsın', 60],
        'ekipman' => ['Periyodik kontrol (ekipman)', 'İş ekipmanının sonraki kontrol tarihine kaç gün kala "yaklaşan" sayılsın', null],
        'onayli_defter' => ['Onaylı defter nüshası', 'Onaylı defter nüshasının yenileme tarihine kaç gün kala uyarı çıksın', null],
        'kimyasal' => ['Kimyasal / GBF gözden geçirme', 'Kimyasal ürünün gözden geçirme tarihine kaç gün kala "yaklaşan" sayılsın', 60],
        'kontrol_vade' => ['Kontrol Merkezi vadeleri', 'Firma checklist maddesinin vade tarihine kaç gün kala "yakın" gösterilsin', 30],
    ];

    private const ESIK_CONFIG = [
        'ekipman' => ['isg.periyodik_kontrol.vize_yaklasan_gun', 30],
        'onayli_defter' => ['isg.onayli_defter.yaklasan_gun', 15],
    ];

    /** @var WeakMap<User, array<string, mixed>>|null model nesnesi başına önbellek (id'ye bağlı değil — testlerde id'ler yeniden kullanılıyor) */
    private static ?WeakMap $onbellek = null;

    /** @return array<string, mixed> */
    public static function varsayilanlar(): array
    {
        $esikler = [];
        foreach (array_keys(self::ESIKLER) as $anahtar) {
            $esikler[$anahtar] = self::varsayilanEsik($anahtar);
        }

        return [
            'tema' => 'klasik',
            'yogunluk' => 'rahat',
            'yazi_boyutu' => 'normal',
            'esikler' => $esikler,
            'kontrol_haric' => [],
        ];
    }

    /** @return array<string, mixed> kayıtlı değerler varsayılanlarla birleştirilmiş */
    public static function hepsi(?User $user = null): array
    {
        $user ??= self::aktifKullanici();

        if (! $user) {
            return self::varsayilanlar();
        }

        self::$onbellek ??= new WeakMap;

        return self::$onbellek[$user] ??= array_replace_recursive(
            self::varsayilanlar(),
            array_filter((array) ($user->ayarlar ?? []), fn ($v) => $v !== null),
        );
    }

    public static function tema(?User $user = null): string
    {
        $tema = (string) self::hepsi($user)['tema'];

        return array_key_exists($tema, self::TEMALAR) ? $tema : 'klasik';
    }

    public static function esik(string $anahtar, ?User $user = null): int
    {
        $gun = (int) (self::hepsi($user)['esikler'][$anahtar] ?? self::varsayilanEsik($anahtar));

        return max(1, min(365, $gun));
    }

    /** @return array<int, string> Kontrol Merkezi'nde takip dışı bırakılan kriter anahtarları */
    public static function kontrolHaric(?int $userId): array
    {
        if (! $userId) {
            return [];
        }

        $aktif = self::aktifKullanici();
        $user = $aktif?->id === $userId ? $aktif : User::query()->find($userId);

        return $user ? array_values((array) self::hepsi($user)['kontrol_haric']) : [];
    }

    /** @param  array<string, mixed>  $degerler */
    public static function kaydet(User $user, array $degerler): void
    {
        $user->forceFill([
            'ayarlar' => array_replace((array) ($user->ayarlar ?? []), $degerler),
        ])->save();

        self::$onbellek?->offsetUnset($user);
    }

    public static function onbellegiTemizle(): void
    {
        self::$onbellek = null;
    }

    private static function varsayilanEsik(string $anahtar): int
    {
        if (isset(self::ESIK_CONFIG[$anahtar])) {
            [$config, $yedek] = self::ESIK_CONFIG[$anahtar];

            return (int) config($config, $yedek);
        }

        return (int) (self::ESIKLER[$anahtar][2] ?? 30);
    }

    private static function aktifKullanici(): ?User
    {
        $user = Auth::user();

        return $user instanceof User ? $user : null;
    }
}
