<?php

namespace App\Support;

use App\Models\Firma;
use App\Models\IseOzguEgitimKonusu;
use Illuminate\Support\Collection;

/**
 * İşe özgü eğitim konuları kütüphanesi — yıllık eğitim planının 4. bölümü
 * ("İşe ve İşyerine Özgü Riskler") ve oradan Eğitim Katılım formunun
 * işyerine özgü bölümü bu kütüphaneden beslenir.
 *
 * Kaynaklar:
 *  - sistem / VİZYON şablonu: config/yillik_plan_sablonu.php 4. bölüm (NACE 41–43)
 *  - sistem / iş kalemi: config isg.kkd_matris.is_kalemleri[].egitim_konusu
 *  - sistem / NACE grubu: config/risk_nace.php gruplar[].basliklar (inşaat hariç —
 *    o grup VİZYON şablonuyla karşılanır)
 *  - kullanıcı: ise_ozgu_egitim_konulari (kendi konuları + sistem konusunun
 *    düzenlenmiş / gizlenmiş hali, `sistem_anahtari` ile)
 *
 * Otomatik seçim (firmaIcin): firmada iş kalemi seçiliyse iş kalemiyle
 * eşleşen konular (yapılan işe özgü), değilse NACE ön ekiyle eşleşenler.
 * Kullanıcının kendi konuları her iki durumda da eşleşirse eklenir.
 */
class IseOzguEgitimKutuphanesi
{
    public const VARSAYILAN_EGITICI = 'İŞ GÜVENLİĞİ UZMANI';

    /** @return Collection<string, array<string, mixed>> anahtar => konu */
    public static function sistemKonulari(): Collection
    {
        $konular = collect();

        foreach (config('yillik_plan_sablonu.egitimler', []) as $i => $e) {
            if (($e['kategori'] ?? null) === 'ise_ozgu') {
                $konular->put('vizyon_'.$i, [
                    'ad' => $e['konu'], 'hedef' => $e['hedef'] ?? null, 'egitici' => $e['egitici'] ?? self::VARSAYILAN_EGITICI,
                    'nace' => ['41', '42', '43'], 'is_kalemleri' => [], 'kaynak' => 'VİZYON şablonu (inşaat)',
                ]);
            }
        }

        foreach (config('isg.kkd_matris.is_kalemleri', []) as $grup => $kalemler) {
            foreach ($kalemler as $k) {
                if (filled($k['anahtar'] ?? null) && filled($k['egitim_konusu'] ?? null)) {
                    $konular->put('kalem_'.$k['anahtar'], [
                        'ad' => $k['ad'], 'hedef' => $k['egitim_konusu'], 'egitici' => self::VARSAYILAN_EGITICI,
                        'nace' => [], 'is_kalemleri' => [$k['anahtar']], 'kaynak' => 'İş kalemi ('.$grup.')',
                    ]);
                }
            }
        }

        foreach (config('risk_nace.gruplar', []) as $anahtar => $g) {
            if ($anahtar === 'insaat') {
                continue;
            }
            foreach ($g['basliklar'] as $i => $baslik) {
                $konular->put('nace_'.$anahtar.'_'.$i, [
                    'ad' => $baslik,
                    'hedef' => $baslik.' kaynaklı tehlikeleri ve riskleri tanımak, alınacak önlemleri ve güvenli çalışma yöntemlerini uygulamak.',
                    'egitici' => self::VARSAYILAN_EGITICI,
                    'nace' => $g['bolumler'], 'is_kalemleri' => [], 'kaynak' => 'NACE grubu ('.$g['ad'].')',
                ]);
            }
        }

        return $konular->map(fn ($k, $a) => [...$k, 'anahtar' => $a, 'sistem' => true, 'duzenlendi' => false, 'id' => null]);
    }

    /** Kullanıcının göreceği tüm konular (sistem + kendi; düzenlenen / gizlenen uygulanmış). */
    public static function tumu(?int $userId): Collection
    {
        $kayitlar = $userId ? IseOzguEgitimKonusu::query()->where('user_id', $userId)->get() : collect();
        $gecersizKilan = $kayitlar->whereNotNull('sistem_anahtari')->keyBy('sistem_anahtari');

        $sistem = static::sistemKonulari()
            ->reject(fn ($k, $a) => $gecersizKilan[$a]?->gizli ?? false)
            ->map(function ($k, $a) use ($gecersizKilan) {
                $d = $gecersizKilan[$a] ?? null;

                return $d ? [...static::kayittanKonu($d), 'anahtar' => $a, 'sistem' => true, 'duzenlendi' => true, 'kaynak' => $k['kaynak']] : $k;
            });

        $kendi = $kayitlar->toBase()->whereNull('sistem_anahtari')->where('gizli', false)
            ->mapWithKeys(fn (IseOzguEgitimKonusu $d) => ['ozel_'.$d->id => [...static::kayittanKonu($d), 'anahtar' => 'ozel_'.$d->id, 'sistem' => false, 'duzenlendi' => false, 'kaynak' => filled($d->is_kolu) ? 'İş kolu: '.$d->is_kolu : 'Kendi kaydım']]);

        return $kendi->merge($sistem);
    }

    /** Kullanıcının bu iş koluna kaydettiği konular (kütüphane; firmadan bağımsız). @return Collection<int, array<string, mixed>> */
    public static function isKoluKonulari(?int $userId, string $isKolu): Collection
    {
        if (! $userId || trim($isKolu) === '') {
            return collect();
        }

        // Büyük/küçük harf (Türkçe İ/ı dahil) karşılaştırması PHP'de yapılır: veritabanı LOWER'ı Türkçe harflerde güvenilir değil.
        $hedef = mb_strtolower(trim($isKolu));

        return IseOzguEgitimKonusu::query()->where('user_id', $userId)->whereNull('sistem_anahtari')->where('gizli', false)
            ->whereNotNull('is_kolu')->orderBy('id')->get()
            ->filter(fn (IseOzguEgitimKonusu $d) => mb_strtolower(trim((string) $d->is_kolu)) === $hedef)
            ->map(fn (IseOzguEgitimKonusu $d) => static::kayittanKonu($d))->values();
    }

    /**
     * İş koluna göre kaydedilen Eğitim Katılım formunun işe özgü konularını kütüphaneye o iş koluyla yazar
     * (aynı iş kolunda aynı metin tekrar eklenmez). Sistem konularının (iş kalemi / şablon) aynısı olan konular
     * zaten kütüphanede olduğundan eklenmez. @return int eklenen konu sayısı
     */
    public static function isKolundanKaydet(\App\Models\EgitimKatilim $kayit): int
    {
        $isKolu = trim((string) $kayit->is_kolu);
        $userId = $kayit->firma?->user_id;

        if ($isKolu === '' || ! $userId) {
            return 0;
        }

        $sistemMetinleri = static::sistemKonulari()
            ->flatMap(fn ($k) => [mb_strtolower(trim((string) $k['ad'])), mb_strtolower(trim((string) $k['hedef']))])->filter()->flip();
        // Sektör şablonunun kendi hazır işe özgü konuları da her formda yeniden kurulur → kütüphaneye yazılmaz.
        $sablon = collect(EgitimIcerikOlusturucu::olustur('genel', $kayit->sektor_anahtari, $kayit->firma->tehlike_sinifi ?? 'az_tehlikeli', $kayit->egitim_turu ?: 'ilk')['isyerine_ozgu']['maddeler'] ?? [])
            ->map(fn ($m) => mb_strtolower(trim((string) ($m['madde'] ?? ''))))->filter()->flip();
        $sistemMetinleri = $sistemMetinleri->union($sablon);
        $mevcut = static::isKoluKonulari($userId, $isKolu)->map(fn ($k) => mb_strtolower(trim($k['ad'])))->flip();
        $eklenen = 0;

        foreach (collect($kayit->konu_secimleri['isyerine_ozgu']['maddeler'] ?? []) as $m) {
            $metin = trim((string) ($m['madde'] ?? ''));
            $anahtar = mb_strtolower($metin);

            if ($metin === '' || ! ($m['dahil'] ?? true) || $sistemMetinleri->has($anahtar) || $mevcut->has($anahtar)) {
                continue;
            }

            IseOzguEgitimKonusu::create([
                'user_id' => $userId,
                'ad' => $metin,
                'egitici' => self::VARSAYILAN_EGITICI,
                'is_kalemleri' => array_values($kayit->is_kolu_kalemleri ?? []) ?: null,
                'is_kolu' => $isKolu,
            ]);
            $mevcut->put($anahtar, true);
            $eklenen++;
        }

        return $eklenen;
    }

    /** Sistem konusu gizlenmiş mi (geri getirme listesi için). */
    public static function gizlenenler(?int $userId): Collection
    {
        $gizli = IseOzguEgitimKonusu::query()->where('user_id', $userId)->whereNotNull('sistem_anahtari')->where('gizli', true)->pluck('sistem_anahtari');

        return static::sistemKonulari()->only($gizli->all());
    }

    private static function kayittanKonu(IseOzguEgitimKonusu $d): array
    {
        return [
            'id' => $d->id,
            'ad' => $d->ad,
            'hedef' => $d->hedef,
            'egitici' => $d->egitici ?: self::VARSAYILAN_EGITICI,
            'nace' => collect(explode(',', trim((string) $d->nace_onekleri, ',')))->filter()->values()->all(),
            'is_kalemleri' => array_values($d->is_kalemleri ?? []),
            'is_kolu' => filled($d->is_kolu) ? $d->is_kolu : null,
        ];
    }

    /** "41, 43.21" → ",41,4321," */
    public static function naceHazirla(?string $girdi): ?string
    {
        $o = collect(preg_split('/[\s,;]+/', (string) $girdi, -1, PREG_SPLIT_NO_EMPTY))
            ->map(fn ($p) => preg_replace('/\D/', '', $p))->filter(fn ($p) => strlen($p) >= 2 && strlen($p) <= 6)->unique()->values();

        return $o->isEmpty() ? null : ','.$o->implode(',').',';
    }

    public static function naceGoster(array $onekler): string
    {
        return collect($onekler)->map(fn ($p) => implode('.', str_split($p, 2)))->implode(', ');
    }

    public static function naceEslesirMi(array $onekler, ?string $nace): bool
    {
        $r = preg_replace('/\D/', '', (string) $nace);

        return $r !== '' && collect($onekler)->contains(fn ($p) => str_starts_with($r, $p));
    }

    /**
     * Firmaya otomatik önerilen konular: iş kalemi seçiliyse iş kalemiyle
     * eşleşenler, değilse NACE ile eşleşenler (kendi konuları dahil).
     */
    public static function firmaIcin(Firma $firma): Collection
    {
        // İş koluna (çalışan grubuna) bağlı konular yalnız o iş koluyla düzenlenen formlara gelir; firma geneli plan önerisine girmez.
        $tumu = static::tumu($firma->user_id)->reject(fn ($k) => filled($k['is_kolu'] ?? null));
        $kalemler = array_values($firma->is_kalemleri ?? []);

        $kalemEslesen = $kalemler ? $tumu->filter(fn ($k) => array_intersect($k['is_kalemleri'], $kalemler) !== []) : collect();

        return $kalemEslesen->isNotEmpty()
            ? $kalemEslesen
            : $tumu->filter(fn ($k) => $k['is_kalemleri'] === [] && static::naceEslesirMi($k['nace'], $firma->nace_kodu));
    }

    /** Yıllık eğitim planı satırı (4. bölüm; şablondaki gibi 2. hafta, tüm aylar P). */
    public static function planSatiri(array $konu, int $kilitAy = 0): array
    {
        $satir = [
            'konu' => $konu['ad'],
            'kategori' => 'ise_ozgu',
            'hedef' => $konu['hedef'],
            'egitici' => $konu['egitici'] ?: self::VARSAYILAN_EGITICI,
            'hafta' => 2,
            'varsayilan_aylar' => range(0, 11),
            'kutuphane_anahtari' => $konu['anahtar'],
        ];

        return \App\Models\YillikPlan::maddeAylarIle([$satir], $kilitAy)[0];
    }
}
