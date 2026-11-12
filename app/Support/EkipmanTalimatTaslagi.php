<?php

namespace App\Support;

use App\Models\Firma;
use App\Models\IsEkipmani;
use App\Models\Talimat;
use App\Models\TalimatSablonu;
use App\Models\User;

/**
 * Kütüphanede talimatı olmayan iş ekipmanı için yapay zekâ talimat taslağı (12.11.2026, kullanıcı kararı:
 * "yapay zekâ taslak hazırlasın"). Taslak, kütüphaneye (talimat_sablonlari, ekipman_tipi ile) "YZ taslağı"
 * işaretiyle girer; o tipteki makinesi olan firmalara talimat olarak eklenir. Uzman Talimat Oluştur'da
 * düzenleyip kaydeder; kütüphane taslağı "Taslağı onayla" ile işaretten çıkar.
 */
class EkipmanTalimatTaslagi
{
    /** Makine / operatörlü ekipman sayılmayan periyodik kontrol kategorileri (ölçüm, tesisat). */
    public const KAPSAM_DISI_KATEGORILER = ['is_hijyeni', 'elektrik_topraklama', 'tesisat_yangin'];

    /**
     * Seçim listesinde olup ne katalogda ne kütüphanede talimatı olan tipler.
     *
     * @return array<int, string>
     */
    public static function talimatsizTipler(): array
    {
        $tipler = [];
        foreach (config('isg.periyodik_kontrol.tipler', []) as $kategori => $liste) {
            if (in_array($kategori, self::KAPSAM_DISI_KATEGORILER, true)) {
                continue;
            }
            foreach ($liste as $t) {
                if (EkipmanKatalogu::talimatSablonlari($t['ad']) === []) {
                    $tipler[] = $t['ad'];
                }
            }
        }

        return array_values(array_unique($tipler));
    }

    /** Talimatın kaydedileceği kütüphane tipi: katalogdaki tanınan ad, yoksa yazıldığı gibi. */
    public static function kutuphaneTipi(string $tip): string
    {
        return EkipmanKatalogu::tipBul($tip) ?? trim($tip);
    }

    public static function baslik(string $tip): string
    {
        return static::buyukHarf(static::kutuphaneTipi($tip)).' GÜVENLİ ÇALIŞMA TALİMATI';
    }

    /** Yapay zekâ ile taslak üretir, kütüphaneye yazar, o tipteki makinesi olan firmalara ekler. Yanıt yoksa null. */
    public static function olustur(string $tip): ?TalimatSablonu
    {
        $tip = static::kutuphaneTipi($tip);
        if ($tip === '') {
            return null;
        }

        if ($mevcut = TalimatSablonu::query()->where('ekipman_tipi', $tip)->first()) {
            static::firmalaraEkle($tip);

            return $mevcut;
        }

        [$kategoriAdi, $standart] = static::kunye($tip);
        $form = EkipmanKatalogu::kontrolFormu($tip);

        $bolumler = GeminiTalimatUretici::uretEkipman($tip, $kategoriAdi, $standart, $form['sorular'] ?? []);
        if ($bolumler === []) {
            return null;
        }

        $sablon = TalimatSablonu::create([
            'user_id' => static::kutuphaneSahibi(),
            'baslik' => static::baslik($tip),
            'kategori' => 'is_makineleri',
            'ekipman_tipi' => $tip,
            'yz_taslak' => true,
            'dokuman_no' => 'YZ-TLM-'.str_pad((string) (TalimatSablonu::query()->whereNotNull('ekipman_tipi')->count() + 1), 3, '0', STR_PAD_LEFT),
            'aciklama' => 'Yapay zekâ taslağı — uzman tarafından gözden geçirilmeli',
            'kkdler' => [],
            'maddeler' => [],
            'bolumler' => $bolumler,
            'taahhut' => Talimat::TAAHHUT_BOLUMLU,
        ]);

        static::firmalaraEkle($tip);

        return $sablon;
    }

    /** Bu tipte aktif makinesi olan her firmaya eksik talimatı ekler (firmada varsa eklemez). */
    public static function firmalaraEkle(string $tip): int
    {
        $sayi = 0;
        $firmaIdleri = IsEkipmani::query()->where('aktif', true)->get(['firma_id', 'tip'])
            ->filter(fn (IsEkipmani $e) => static::kutuphaneTipi((string) $e->tip) === $tip)
            ->pluck('firma_id')->unique();

        foreach (Firma::query()->whereIn('id', $firmaIdleri)->get() as $firma) {
            $sayi += count(EkipmanKatalogu::talimatlariEkle($firma));
        }

        return $sayi;
    }

    /** @return array{0: string, 1: ?string} kategori adı, yasal standart */
    private static function kunye(string $tip): array
    {
        foreach (config('isg.periyodik_kontrol.tipler', []) as $kategori => $liste) {
            foreach ($liste as $t) {
                if ($t['ad'] === $tip) {
                    return [config('isg.periyodik_kontrol.kategoriler.'.$kategori.'.ad', 'İş ekipmanı'), $t['standart'] ?? null];
                }
            }
        }

        return ['İş ekipmanı', null];
    }

    /** Kütüphane ortak; kayıt sahip hesaba yazılır (yoksa oturumdaki kullanıcı). */
    private static function kutuphaneSahibi(): ?int
    {
        return User::query()->where('rol', 'sahip')->orderBy('id')->value('id') ?? auth()->id();
    }

    private static function buyukHarf(string $metin): string
    {
        return mb_strtoupper(strtr($metin, ['i' => 'İ', 'ı' => 'I']), 'UTF-8');
    }
}
