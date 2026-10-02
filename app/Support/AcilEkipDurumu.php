<?php

namespace App\Support;

use App\Models\AcilEkip;
use App\Models\AcilEkipUyesi;
use App\Models\Firma;
use Illuminate\Support\Collection;

/**
 * Acil Durum Ekipleri / Destek Elemanları (isgsuite acil_ekipler): ekip
 * yeterliliği (asıl üye ≥ asgari, lider, geçerli eğitim belgesi),
 * göstergeler ve kontrol önerileri.
 *
 * Durum: kritik = hiç asıl üye yok; eksik = asgariden az / lider yok /
 * süresi dolmuş belge; tam = hepsi uygun.
 */
class AcilEkipDurumu
{
    /** Ekip yönetiminin ilk kurulumda açtığı temel ekipler. */
    public const TEMEL = ['sondurme', 'kurtarma', 'koruma', 'ilk_yardim', 'tahliye', 'haberlesme'];

    public static function calisanSayisi(Firma $firma): int
    {
        return $firma->calisanlar()->where('aktif', true)->count() ?: (int) $firma->calisan_sayisi;
    }

    /** @return Collection<int, AcilEkip> */
    public static function ekipler(Firma $firma): Collection
    {
        $sira = array_flip(array_keys(config('isg.acil_durum.ekip_turleri')));

        return AcilEkip::query()->where('firma_id', $firma->id)
            ->with(['uyeler' => fn ($q) => $q->orderByDesc('lider')->orderBy('uyelik')->orderBy('ad_soyad')])
            ->get()
            ->each(fn (AcilEkip $e) => $e->setRelation('firma', $firma))
            ->sortBy(fn (AcilEkip $e) => [$sira[$e->tur] ?? 99, $e->id])
            ->values();
    }

    /** @return array{asil: int, yedek: int, min: int, lider: ?string, durum: string, uyarilar: array<int, string>, dolmus: int, yaklasan: int} */
    public static function ekipDurumu(AcilEkip $ekip, int $calisan): array
    {
        $uyeler = $ekip->uyeler;
        $asil = $uyeler->where('uyelik', '!=', 'yedek')->count();
        $min = $ekip->minimum($calisan);
        $lider = $uyeler->firstWhere('lider', true);
        $dolmus = $uyeler->filter(fn (AcilEkipUyesi $u) => $u->belgeDurumu() === 'dolmus')->count();
        $yaklasan = $uyeler->filter(fn (AcilEkipUyesi $u) => $u->belgeDurumu() === 'yaklasan')->count();
        $belgesiz = $uyeler->filter(fn (AcilEkipUyesi $u) => $u->belgeDurumu() === 'yok')->count();

        $uyarilar = [];
        if ($uyeler->isEmpty()) {
            $uyarilar[] = 'Bu ekibe henüz üye atanmamış — kontrol edilmesi önerilir.';
        } else {
            if ($asil < $min) {
                $uyarilar[] = "Asıl üye {$asil}, asgari {$min}".($ekip->yasalOranMetni() ? ' ('.$ekip->yasalOranMetni().')' : '').' — '.($min - $asil).' kişi daha görevlendirilmeli.';
            }
            if (! $lider) {
                $uyarilar[] = 'Ekip lideri (sorumlusu) belirlenmemiş.';
            }
            if ($dolmus) {
                $uyarilar[] = "{$dolmus} üyenin eğitim belgesinin süresi dolmuş — eğitim yenilenmeli.";
            }
            if ($yaklasan) {
                $uyarilar[] = "{$yaklasan} üyenin belgesi 30 gün içinde doluyor.";
            }
            if ($belgesiz) {
                $uyarilar[] = "{$belgesiz} üyenin eğitim belgesi girilmemiş.";
            }
            if ($uyeler->where('uyelik', 'yedek')->isEmpty()) {
                $uyarilar[] = 'Yedek üye yok — izin / vardiya durumunda ekip eksik kalabilir.';
            }
        }

        $durum = match (true) {
            $asil === 0 => 'kritik',
            $asil < $min || ! $lider || $dolmus > 0 => 'eksik',
            default => 'tam',
        };

        return ['asil' => $asil, 'yedek' => $uyeler->count() - $asil, 'min' => $min, 'lider' => $lider?->ad_soyad, 'durum' => $durum, 'uyarilar' => $uyarilar, 'dolmus' => $dolmus, 'yaklasan' => $yaklasan];
    }

    /** @return array<string, mixed> */
    public static function ozet(Firma $firma, ?Collection $ekipler = null): array
    {
        $ekipler ??= static::ekipler($firma);
        $calisan = static::calisanSayisi($firma);
        $durumlar = $ekipler->mapWithKeys(fn (AcilEkip $e) => [$e->id => static::ekipDurumu($e, $calisan)]);
        $uyeler = $ekipler->flatMap->uyeler;

        $oneriler = [];
        foreach (['sondurme', 'kurtarma', 'koruma', 'ilk_yardim'] as $tur) {
            if (! $ekipler->contains('tur', $tur)) {
                $oneriler[] = config('isg.acil_durum.ekip_turleri.'.$tur.'.ad').': ekip tanımlı değil ('.($tur === 'ilk_yardim' ? 'İlkyardım Yön.' : 'Acil Durumlar Yön. Md.11').').';
            }
        }
        foreach ($ekipler as $e) {
            foreach ($durumlar[$e->id]['uyarilar'] as $u) {
                $oneriler[] = $e->ad.': '.$u;
            }
        }

        return [
            'calisan' => $calisan,
            'ekip' => $ekipler->count(),
            'uye' => $uyeler->count(),
            'lider' => $uyeler->where('lider', true)->count(),
            'tam' => $durumlar->where('durum', 'tam')->count(),
            'kritik' => $durumlar->where('durum', 'kritik')->count(),
            'belge_dolan' => $durumlar->sum('dolmus'),
            'yaklasan' => $durumlar->sum('yaklasan'),
            'durumlar' => $durumlar->all(),
            'oneriler' => $oneriler,
        ];
    }

    /** Eksik temel ekipleri oluşturur; oluşturulan sayısını döndürür. */
    public static function temelEkipleriOlustur(Firma $firma): int
    {
        $mevcut = AcilEkip::query()->where('firma_id', $firma->id)->pluck('tur')->all();
        $n = 0;

        foreach (static::TEMEL as $tur) {
            if (! in_array($tur, $mevcut, true)) {
                AcilEkip::create(['firma_id' => $firma->id, 'tur' => $tur, 'ad' => config('isg.acil_durum.ekip_turleri.'.$tur.'.ad')]);
                $n++;
            }
        }

        return $n;
    }

    /**
     * Acil Durum Planı ekip anahtarına göre üye adları (yalnız üyesi olan
     * ekipler) — plan PDF'i ve hazırlık kontrolü bunu kullanır.
     *
     * @return array<string, array<int, string>>
     */
    public static function planListesi(Firma $firma): array
    {
        $liste = [];

        foreach (static::ekipler($firma) as $e) {
            $anahtar = $e->turBilgisi()['plan'] ?? null;
            if ($anahtar && $e->uyeler->isNotEmpty()) {
                $liste[$anahtar] = array_values(array_unique([...($liste[$anahtar] ?? []), ...$e->uyeler->pluck('ad_soyad')->all()]));
            }
        }

        return $liste;
    }
}
