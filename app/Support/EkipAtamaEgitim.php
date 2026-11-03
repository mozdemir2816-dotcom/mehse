<?php

namespace App\Support;

use App\Models\AcilEkip;
use App\Models\AcilEkipUyesi;
use App\Models\AtamaYazisi;
use App\Models\Calisan;
use App\Models\EgitimKatilim;
use App\Models\Firma;
use Illuminate\Support\Collection;

/**
 * Acil durum ekipleri ve çalışan temsilcisi: üye atanınca / değişince atama yazısı otomatik hazırlanır, eğitim
 * hatırlatılır (kullanıcı 03.11.2026 — İSG Kurulu ile aynı mantık, bkz. KurulAtamaEgitim).
 *
 *  Acil ekipleri : AcilEkip (söndürme/kurtarma/koruma/ilk yardım) üyeleri → atama yazısı rolleri + ekip eğitimi başlıkları.
 *                  Üyenin eğitim belgesi (belge_no/tarihi, süresi dolmamış) ya da ilgili eğitim katılım kaydı varsa eğitimi alınmış sayılır.
 *  Temsilci      : seçim sonucundaki kazanan + kurul temsilci üyeleri + son 2 yılın atama yazılarındaki temsilciler.
 */
class EkipAtamaEgitim
{
    /** Acil ekip türü => atama yazısı rolü ve Eğitim Katılım başlığı. */
    public const ACIL = [
        'sondurme' => ['rol' => 'sondurme_ekibi', 'baslik' => 'sondurme_ekibi'],
        'kurtarma' => ['rol' => 'kurtarma_ekibi', 'baslik' => 'kurtarma_ekibi'],
        'koruma' => ['rol' => 'koruma_ekibi', 'baslik' => 'koruma_ekibi'],
        'ilk_yardim' => ['rol' => 'ilkyardim_ekibi', 'baslik' => 'ilkyardim_ekibi'],
    ];

    public const TEMSILCI_ROL = 'calisan_temsilcisi';

    public const TEMSILCI_BASLIK = 'calisan_temsilcisi';

    // ------------------------------------------------------------------ ortak

    /** Eğitim Katılım kayıtlarında (belge alacak) geçen kişi adı anahtarları. @return Collection<string, int> */
    private static function egitimAlanlar(Firma $firma, string $baslik): Collection
    {
        return EgitimKatilim::query()->where('firma_id', $firma->id)->where('baslik_anahtari', $baslik)->get()
            ->flatMap(fn (EgitimKatilim $k) => collect($k->belgeAlacakKatilimcilar())->pluck('ad_soyad'))
            ->map(fn ($ad) => EgitimTakibi::adAnahtari($ad))->filter()->flip();
    }

    /** @param  array<int, array<string, mixed>>  $uyeler */
    private static function atamaFarki(Firma $firma, string $rol, array $uyeler): array
    {
        $son = $firma->atamaYazilari()->where('rol_anahtari', $rol)->latest('tarih')->latest('id')->first();
        $anahtar = fn (?string $ad) => EgitimTakibi::adAnahtari($ad);
        $simdi = collect($uyeler)->mapWithKeys(fn ($u) => [$anahtar($u['ad_soyad']) => $u['ad_soyad']]);
        $eski = collect($son?->uyeler ?? [])->mapWithKeys(fn ($u) => [$anahtar($u['ad_soyad'] ?? null) => $u['ad_soyad'] ?? '']);
        $eklenen = $simdi->diffKeys($eski)->values()->all();
        $cikan = $eski->diffKeys($simdi)->values()->all();

        return ['var' => $son !== null, 'guncel' => $son !== null && ! $eklenen && ! $cikan, 'son' => $son, 'eklenen' => $eklenen, 'cikan' => $cikan];
    }

    // ------------------------------------------------------------- acil ekipler

    /** @return Collection<int, AcilEkip> üyesi olan acil ekipleri (tür başına ilk ekip) */
    public static function acilEkipler(Firma $firma): Collection
    {
        return AcilEkip::query()->where('firma_id', $firma->id)->whereIn('tur', array_keys(self::ACIL))
            ->with(['uyeler.calisan:id,tc'])->orderBy('id')->get()
            ->unique('tur')->filter(fn (AcilEkip $e) => $e->uyeler->isNotEmpty())->values();
    }

    /** Üyenin ekipteki rolü (ör. Söndürme Ekibi — Ekip Lideri): eğitim katılımcısının görevi olarak yazılır. */
    public static function acilRol(AcilEkip $ekip, AcilEkipUyesi $u): string
    {
        return $ekip->ad.' — '.($u->lider ? 'Ekip Lideri' : ($u->uyelik === 'yedek' ? 'Yedek Üye' : 'Üye'));
    }

    /** @return array<int, array<string, mixed>> atama yazısı üye satırları */
    public static function acilUyeleri(AcilEkip $ekip): array
    {
        return $ekip->uyeler->sortBy([['lider', 'desc'], ['id', 'asc']])->map(fn (AcilEkipUyesi $u) => [
            'calisan_id' => $u->calisan_id,
            'ad_soyad' => $u->ad_soyad,
            'tc' => $u->calisan?->tc,
            'gorev' => $u->gorev,
            'bas_uye' => (bool) $u->lider,
        ])->values()->all();
    }

    /** @return array{var: bool, guncel: bool, son: ?AtamaYazisi, eklenen: array<int, string>, cikan: array<int, string>} */
    public static function acilAtamaDurumu(AcilEkip $ekip): array
    {
        return self::atamaFarki($ekip->firma ?? Firma::find($ekip->firma_id), self::ACIL[$ekip->tur]['rol'], self::acilUyeleri($ekip));
    }

    /** Ekibin atama yazısını güncel üyelerle hazırlar (aynı gün kaydı güncellenir, kopya birikmez). */
    public static function acilAtamaHazirla(AcilEkip $ekip): ?AtamaYazisi
    {
        $ekip->loadMissing('uyeler.calisan:id,tc', 'firma');

        return $ekip->uyeler->isEmpty() ? null : AcilEkipAtamaSenkron::ekiptenKayit($ekip);
    }

    /** Tüm acil ekiplerin atama yazılarını hazırlar. @return int hazırlanan ekip sayısı */
    public static function acilHepsiniHazirla(Firma $firma): int
    {
        return self::acilEkipler($firma)->filter(fn (AcilEkip $e) => self::acilAtamaHazirla($e) !== null)->count();
    }

    /**
     * Eğitimi bulunmayan ekip üyeleri: geçerli eğitim belgesi yok VE ilgili eğitim katılım kaydında adı geçmiyor.
     *
     * @return array{toplam: int, alan: int, eksik: Collection<int, AcilEkipUyesi>}
     */
    public static function acilEgitimDurumu(AcilEkip $ekip): array
    {
        $alanlar = self::egitimAlanlar($ekip->firma ?? Firma::find($ekip->firma_id), self::ACIL[$ekip->tur]['baslik']);
        $eksik = $ekip->uyeler->filter(fn (AcilEkipUyesi $u) => in_array($u->belgeDurumu(), ['yok', 'dolmus'], true)
            && ! $alanlar->has(EgitimTakibi::adAnahtari($u->ad_soyad)))->values();

        return ['toplam' => $ekip->uyeler->count(), 'alan' => $ekip->uyeler->count() - $eksik->count(), 'eksik' => $eksik];
    }

    /** Tüm acil ekiplerin eğitimi olmayan üyelerini Eğitim Katılım'da planlar; üyesi kalmayan ekiplerin planlı kaydı temizlenir. @return int planlı kayıt sayısı */
    public static function acilHepsiniPlanla(Firma $firma): int
    {
        $ekipler = self::acilEkipler($firma)->keyBy('tur');
        $sayi = 0;

        foreach (self::ACIL as $tur => $tanim) {
            $eksik = isset($ekipler[$tur])
                ? self::acilEgitimDurumu($ekipler[$tur])['eksik']->map(fn (AcilEkipUyesi $u) => ['ad_soyad' => $u->ad_soyad, 'tc' => $u->calisan?->tc, 'gorev' => self::acilRol($ekipler[$tur], $u)])->values()->all()
                : [];

            $sayi += EgitimPlanlayici::planla($firma, $tanim['baslik'], $eksik, 'acil_'.$tur) ? 1 : 0;
        }

        return $sayi;
    }

    public static function acilPlanliEgitim(Firma $firma, string $tur): ?EgitimKatilim
    {
        return EgitimPlanlayici::acikKayit($firma, self::ACIL[$tur]['baslik'], 'acil_'.$tur);
    }

    public static function acilEgitimAktarimi(AcilEkip $ekip): ?array
    {
        $eksik = self::acilEgitimDurumu($ekip)['eksik'];

        return $eksik->isEmpty() ? null : [
            'firma_id' => $ekip->firma_id,
            'baslik_anahtari' => self::ACIL[$ekip->tur]['baslik'],
            'katilimcilar' => $eksik->map(fn (AcilEkipUyesi $u) => ['ad_soyad' => $u->ad_soyad, 'tc' => $u->calisan?->tc, 'gorev' => self::acilRol($ekip, $u)])->values()->all(),
        ];
    }

    // -------------------------------------------------------- çalışan temsilcisi

    /**
     * Güncel çalışan temsilcileri: seçim sonucundaki kazanan, kurul temsilci üyeleri ve son 2 yılın atama yazılarındaki kişiler.
     *
     * @return Collection<int, array{ad_soyad: string, unvan: ?string, tc: ?string}>
     */
    public static function temsilciler(Firma $firma): Collection
    {
        $liste = collect();

        if ($aday = $firma->calisanTemsilcisiSecimi?->secilenAday()) {
            $liste->push(['ad_soyad' => $aday['ad_soyad'], 'unvan' => $aday['unvan'] ?? null]);
        }

        foreach ($firma->kurulUyeleri()->where('aktif', true)->where('rol', 'calisan_temsilcisi')->get() as $u) {
            $liste->push(['ad_soyad' => $u->ad_soyad, 'unvan' => $u->gorev]);
        }

        foreach ($firma->atamaYazilari()->where('rol_anahtari', self::TEMSILCI_ROL)->where('tarih', '>=', now()->subYears(2))->get() as $y) {
            foreach ((array) $y->uyeler as $u) {
                $liste->push(['ad_soyad' => (string) ($u['ad_soyad'] ?? ''), 'unvan' => $u['gorev'] ?? null]);
            }
        }

        $calisanlar = $firma->calisanlar()->get(['ad_soyad', 'tc']);

        return $liste->filter(fn ($t) => trim($t['ad_soyad']) !== '')
            ->unique(fn ($t) => EgitimTakibi::adAnahtari($t['ad_soyad']))
            ->map(fn ($t) => [...$t, 'tc' => $calisanlar->first(fn ($c) => EgitimTakibi::adAnahtari($c->ad_soyad) === EgitimTakibi::adAnahtari($t['ad_soyad']))?->tc])
            ->values();
    }

    /** Temsilcinin (son 2 yıl) atama yazısı var mı? */
    public static function temsilciAtamaVar(Firma $firma, string $adSoyad): bool
    {
        $anahtar = EgitimTakibi::adAnahtari($adSoyad);

        return $firma->atamaYazilari()->where('rol_anahtari', self::TEMSILCI_ROL)->where('tarih', '>=', now()->subYears(2))->get()
            ->contains(fn (AtamaYazisi $y) => collect((array) $y->uyeler)->contains(fn ($u) => EgitimTakibi::adAnahtari($u['ad_soyad'] ?? null) === $anahtar));
    }

    /**
     * Seçim sonucundaki kazanan için atama yazısını hazırlar (aynı kişinin son 2 yıl içinde yazısı varsa yenisi açılmaz).
     *
     * @return AtamaYazisi|null yeni açılan yazı
     */
    public static function temsilciAtamaHazirla(Firma $firma): ?AtamaYazisi
    {
        $aday = $firma->calisanTemsilcisiSecimi?->secilenAday();

        if (! $aday || self::temsilciAtamaVar($firma, $aday['ad_soyad'])) {
            return null;
        }

        $tc = $firma->calisanlar()->get(['ad_soyad', 'tc'])->first(fn ($c) => EgitimTakibi::adAnahtari($c->ad_soyad) === EgitimTakibi::adAnahtari($aday['ad_soyad']))?->tc;

        return AtamaYazisi::create([
            'firma_id' => $firma->id,
            'rol_anahtari' => self::TEMSILCI_ROL,
            'tarih' => today(),
            'isveren_vekili_adi' => $firma->isveren_vekili ?: $firma->isveren_ad,
            'gorev_baslangic' => $firma->calisanTemsilcisiSecimi->gorevlendirme_tarihi ?? today(),
            'uyeler' => [['ad_soyad' => $aday['ad_soyad'], 'tc' => $tc, 'gorev' => $aday['unvan'] ?? null, 'bas_uye' => true]],
        ]);
    }

    /** @return array{toplam: int, alan: int, eksik: Collection<int, array{ad_soyad: string, unvan: ?string, tc: ?string}>} */
    public static function temsilciEgitimDurumu(Firma $firma): array
    {
        $temsilciler = self::temsilciler($firma);
        $alanlar = self::egitimAlanlar($firma, self::TEMSILCI_BASLIK);
        $eksik = $temsilciler->reject(fn ($t) => $alanlar->has(EgitimTakibi::adAnahtari($t['ad_soyad'])))->values();

        return ['toplam' => $temsilciler->count(), 'alan' => $temsilciler->count() - $eksik->count(), 'eksik' => $eksik];
    }

    /**
     * Eğitim Katılım'da bu başlık bir grup eğitimiyse (İSG Kurulu, çalışan temsilcisi, acil durum ekipleri, destek elemanları)
     * o grubun TÜM güncel üyeleri; grup eğitimi değilse null.
     *
     * @return array<int, array{ad_soyad: string, tc: ?string, gorev: ?string, calisan_id: ?int}>|null
     */
    public static function egitimGrubuUyeleri(Firma $firma, string $baslik): ?array
    {
        if ($baslik === KurulAtamaEgitim::EGITIM_BASLIGI) {
            return KurulAtamaEgitim::uyeler($firma)->map(fn ($u) => ['ad_soyad' => $u->ad_soyad, 'tc' => $u->calisan?->tc, 'gorev' => $u->rolEtiketi(), 'calisan_id' => $u->calisan_id])->values()->all();
        }

        if ($baslik === self::TEMSILCI_BASLIK) {
            return self::temsilciler($firma)->map(fn ($t) => ['ad_soyad' => $t['ad_soyad'], 'tc' => $t['tc'], 'gorev' => 'Çalışan Temsilcisi', 'calisan_id' => null])->values()->all();
        }

        $turler = $baslik === 'destek_elemanlari'
            ? array_keys(self::ACIL)
            : collect(self::ACIL)->filter(fn ($t) => $t['baslik'] === $baslik)->keys()->all();

        if (! $turler) {
            return null;
        }

        return self::acilEkipler($firma)->whereIn('tur', $turler)
            // Görev = ekipteki rolü (ör. "Söndürme Ekibi — Ekip Lideri"); birden çok ekipteyse roller birleşir.
            ->flatMap(fn (AcilEkip $e) => $e->uyeler->map(fn (AcilEkipUyesi $u) => [
                'ad_soyad' => $u->ad_soyad, 'tc' => $u->calisan?->tc, 'calisan_id' => $u->calisan_id,
                'gorev' => self::acilRol($e, $u),
            ]))
            ->groupBy(fn ($u) => EgitimTakibi::adAnahtari($u['ad_soyad']))
            ->map(fn ($g) => [...$g->first(), 'gorev' => $g->pluck('gorev')->implode(', ')])
            ->values()->all();
    }

    /** Eğitimi olmayan çalışan temsilcileri için Eğitim Katılım'da planlı kayıt açar / günceller. */
    public static function temsilciEgitimPlanla(Firma $firma): ?EgitimKatilim
    {
        $eksik = self::temsilciEgitimDurumu($firma)['eksik']->map(fn ($t) => ['ad_soyad' => $t['ad_soyad'], 'tc' => $t['tc'], 'gorev' => 'Çalışan Temsilcisi'])->all();

        return EgitimPlanlayici::planla($firma, self::TEMSILCI_BASLIK, $eksik, 'temsilci');
    }

    public static function temsilciPlanliEgitim(Firma $firma): ?EgitimKatilim
    {
        return EgitimPlanlayici::acikKayit($firma, self::TEMSILCI_BASLIK, 'temsilci');
    }

    public static function temsilciEgitimAktarimi(Firma $firma): ?array
    {
        $eksik = self::temsilciEgitimDurumu($firma)['eksik'];

        return $eksik->isEmpty() ? null : [
            'firma_id' => $firma->id,
            'baslik_anahtari' => self::TEMSILCI_BASLIK,
            'katilimcilar' => $eksik->map(fn ($t) => ['ad_soyad' => $t['ad_soyad'], 'tc' => $t['tc'], 'gorev' => 'Çalışan Temsilcisi'])->all(),
        ];
    }
}
