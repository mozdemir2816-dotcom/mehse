<?php

namespace App\Support;

use App\Models\Firma;
use App\Models\IsgKatipSozlesmesi;
use App\Models\IsgProfesyoneli;
use App\Models\SahaBulgusu;
use App\Models\SahaDenetimi;
use App\Models\SahaZiyaretKaniti;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * OSGB Kadro Panosu, Belge Sınıfı Uyuşmazlığı, Tam Süreli Kilit,
 * İhmal Edilen Firmalar ve Uzman Aylık Karne (0–100) hesapları (F4, F5, F6).
 */
class OsgbKadroAnalizi
{
    /** Tam zamanlı bir İSG profesyonelinin aylık yasal kapasitesi (180 saat = 10.800 dk). */
    public const TAM_ZAMANLI_DK = 10800;

    /**
     * Bir İSG uzmanının unvan/sertifika metninden belge sınıfını (A/B/C) çözer.
     */
    public static function uzmanSinifi(?IsgProfesyoneli $p): ?string
    {
        if (! $p) {
            return null;
        }

        $metin = mb_strtoupper(trim(($p->unvan ?? '').' '.($p->sertifika_no ?? '')), 'UTF-8');

        if (preg_match('/\bA\s*SINIFI\b|\(A\)|\bA\s+IGU\b|\bA\s+İGU\b/u', $metin)) {
            return 'A';
        }
        if (preg_match('/\bB\s*SINIFI\b|\(B\)|\bB\s+IGU\b|\bB\s+İGU\b/u', $metin)) {
            return 'B';
        }
        if (preg_match('/\bC\s*SINIFI\b|\(C\)|\bC\s+IGU\b|\bC\s+İGU\b/u', $metin)) {
            return 'C';
        }

        return null;
    }

    /**
     * Tehlike sınıfı için İGU belge sınıfı uygun mu?
     * Az Tehlikeli: A, B, C | Tehlikeli: A, B | Çok Tehlikeli: A.
     */
    public static function sinifUygunMu(?string $belgeSinifi, ?string $tehlikeSinifi): bool
    {
        if (! $belgeSinifi || ! $tehlikeSinifi) {
            return true;
        }

        return match ($tehlikeSinifi) {
            'cok_tehlikeli' => $belgeSinifi === 'A',
            'tehlikeli' => in_array($belgeSinifi, ['A', 'B'], true),
            default => in_array($belgeSinifi, ['A', 'B', 'C'], true),
        };
    }

    /**
     * Belge sınıfı uyuşmazlığı tespit edilen aktif firmalar.
     *
     * @return array<int, array{firma_id: int, unvan: string, tehlike_sinifi: string, igu_ad: string, belge_sinifi: string, gereken: string}>
     */
    public static function sinifUyusmazliklari(int $userId): array
    {
        $firmalar = Firma::query()
            ->where('user_id', $userId)
            ->where('aktif', true)
            ->where('igu_hizmeti_yok', false)
            ->whereNotNull('igu_id')
            ->with('igu')
            ->get();

        $uyarilar = [];
        foreach ($firmalar as $f) {
            $sinif = static::uzmanSinifi($f->igu);
            if ($sinif && ! static::sinifUygunMu($sinif, $f->tehlike_sinifi)) {
                $uyarilar[] = [
                    'firma_id' => $f->id,
                    'unvan' => $f->secimEtiketi(),
                    'tehlike_sinifi' => (string) config('isg.tehlike_siniflari.'.$f->tehlike_sinifi, $f->tehlike_sinifi),
                    'igu_ad' => (string) $f->igu->ad_soyad,
                    'belge_sinifi' => $sinif,
                    'gereken' => $f->tehlike_sinifi === 'cok_tehlikeli' ? 'A Sınıfı' : 'A veya B Sınıfı',
                ];
            }
        }

        return $uyarilar;
    }

    /**
     * Bir İSG profesyoneli başka bir aktif firmada "Tam Süreli" kilitli mi? (F5)
     */
    public static function tamSureliCakisanFirma(?int $profesyonelId, string $tip, ?int $haricFirmaId = null): ?Firma
    {
        if (! $profesyonelId) {
            return null;
        }

        [$idKolon, $bayrakKolon] = match ($tip) {
            'igu' => ['igu_id', 'tam_sureli_igu'],
            'isyeri_hekimi' => ['isyeri_hekimi_id', 'tam_sureli_hekim'],
            default => [null, null],
        };

        if (! $idKolon) {
            return null;
        }

        return Firma::query()
            ->where('aktif', true)
            ->where($idKolon, $profesyonelId)
            ->where($bayrakKolon, true)
            ->when($haricFirmaId, fn ($q) => $q->whereKeyNot($haricFirmaId))
            ->first();
    }

    /**
     * OSGB FTE Kadro Panosu ve Profesyonel Doluluk Bantları (F4).
     *
     * @return array{
     *   toplam_igu_dk: int,
     *   toplam_hekim_dk: int,
     *   toplam_dsp_dk: int,
     *   gereken_igu_fte: float,
     *   gereken_hekim_fte: float,
     *   bantlar: array{asan: int, sinirda: int, normal: int, bosta: int},
     *   personeller: array<int, array<string, mixed>>
     * }
     */
    public static function kadroPanosu(int $userId): array
    {
        $firmalar = Firma::query()
            ->where('user_id', $userId)
            ->where('aktif', true)
            ->get();

        $profesyoneller = IsgProfesyoneli::query()
            ->where('user_id', $userId)
            ->where('aktif', true)
            ->orderBy('tip')
            ->orderBy('ad_soyad')
            ->get();

        $toplamIguDk = 0;
        $toplamHekimDk = 0;
        $toplamDspDk = 0;

        $kisiDk = [];
        $kisiFirmaSayi = [];
        $kisiTamSureli = [];

        foreach ($firmalar as $f) {
            $n = max(0, (int) $f->calisan_sayisi);
            $iguDk = $f->igu_hizmeti_yok ? 0 : ((int) ($f->katip_aylik_dk ?: 0) ?: static::yasalDakika('igu', $f->tehlike_sinifi, $n));
            $hekimDk = $f->hekim_hizmeti_yok ? 0 : static::yasalDakika('isyeri_hekimi', $f->tehlike_sinifi, $n);
            $dspDk = $f->dsp_hizmeti_yok ? 0 : static::yasalDakika('dsp', $f->tehlike_sinifi, $n);

            $toplamIguDk += $iguDk;
            $toplamHekimDk += $hekimDk;
            $toplamDspDk += $dspDk;

            if ($f->igu_id && ! $f->igu_hizmeti_yok) {
                $kisiDk[$f->igu_id] = ($kisiDk[$f->igu_id] ?? 0) + ($f->tam_sureli_igu ? self::TAM_ZAMANLI_DK : $iguDk);
                $kisiFirmaSayi[$f->igu_id] = ($kisiFirmaSayi[$f->igu_id] ?? 0) + 1;
                if ($f->tam_sureli_igu) {
                    $kisiTamSureli[$f->igu_id] = $f->secimEtiketi();
                }
            }
            if ($f->isyeri_hekimi_id && ! $f->hekim_hizmeti_yok) {
                $kisiDk[$f->isyeri_hekimi_id] = ($kisiDk[$f->isyeri_hekimi_id] ?? 0) + ($f->tam_sureli_hekim ? self::TAM_ZAMANLI_DK : $hekimDk);
                $kisiFirmaSayi[$f->isyeri_hekimi_id] = ($kisiFirmaSayi[$f->isyeri_hekimi_id] ?? 0) + 1;
                if ($f->tam_sureli_hekim) {
                    $kisiTamSureli[$f->isyeri_hekimi_id] = $f->secimEtiketi();
                }
            }
            if ($f->dsp_id && ! $f->dsp_hizmeti_yok) {
                $kisiDk[$f->dsp_id] = ($kisiDk[$f->dsp_id] ?? 0) + $dspDk;
                $kisiFirmaSayi[$f->dsp_id] = ($kisiFirmaSayi[$f->dsp_id] ?? 0) + 1;
            }
        }

        // Aktif İSG-KATİP "Tam Süreli" sözleşmelerini de tespit et (varsa not olarak göster)
        $katipTamSureli = IsgKatipSozlesmesi::query()
            ->where('user_id', $userId)
            ->aktif()
            ->where('calisma_suresi', 'like', '%Tam%')
            ->pluck('kisi_ad')
            ->map(fn ($ad) => mb_strtolower(trim((string) $ad), 'UTF-8'))
            ->filter()
            ->flip()
            ->all();

        $bantlar = ['asan' => 0, 'sinirda' => 0, 'normal' => 0, 'bosta' => 0];
        $satirlar = [];

        foreach ($profesyoneller as $p) {
            $dk = (int) ($kisiDk[$p->id] ?? 0);
            $yuzde = (int) round(($dk / self::TAM_ZAMANLI_DK) * 100);
            $tamSureliFirma = $kisiTamSureli[$p->id] ?? (isset($katipTamSureli[mb_strtolower(trim((string) $p->ad_soyad), 'UTF-8')]) ? 'İSG-KATİP Tam Süreli' : null);

            [$bant, $bantEtiket, $bantRenk] = match (true) {
                $yuzde > 100 => ['asan', 'Kapasiteyi Aşan (>%100)', '#dc2626'],
                $yuzde >= 80 => ['sinirda', 'Sınırda (%80–100)', '#d97706'],
                $yuzde >= 30 => ['normal', 'Normal (%30–79)', '#059669'],
                default => ['bosta', 'Boşta (<%30)', '#2563eb'],
            };
            $bantlar[$bant]++;

            $satirlar[] = [
                'id' => $p->id,
                'ad_soyad' => $p->ad_soyad,
                'tip' => $p->tipEtiketi(),
                'sinif' => static::uzmanSinifi($p),
                'firma_sayisi' => (int) ($kisiFirmaSayi[$p->id] ?? 0),
                'toplam_dk' => $dk,
                'toplam_saat' => round($dk / 60, 1),
                'doluluk_yuzde' => $yuzde,
                'bant' => $bant,
                'bant_etiket' => $bantEtiket,
                'bant_renk' => $bantRenk,
                'tam_sureli_firma' => $tamSureliFirma,
            ];
        }

        usort($satirlar, fn ($a, $b) => $b['doluluk_yuzde'] <=> $a['doluluk_yuzde']);

        return [
            'toplam_igu_dk' => $toplamIguDk,
            'toplam_hekim_dk' => $toplamHekimDk,
            'toplam_dsp_dk' => $toplamDspDk,
            'gereken_igu_fte' => round($toplamIguDk / self::TAM_ZAMANLI_DK, 2),
            'gereken_hekim_fte' => round($toplamHekimDk / self::TAM_ZAMANLI_DK, 2),
            'bantlar' => $bantlar,
            'personeller' => $satirlar,
        ];
    }

    /**
     * Son saha ziyaretinden bu yana en uzun süredir gidilmeyen aktif firmalar (F4).
     *
     * @return array<int, array{firma_id: int, unvan: string, tehlike_sinifi: string, igu_ad: string, son_ziyaret: ?string, gecen_gun: ?int, kritik: bool}>
     */
    public static function ihmalEdilenFirmalar(int $userId, int $limit = 15): array
    {
        $firmalar = Firma::query()
            ->where('user_id', $userId)
            ->where('aktif', true)
            ->with('igu:id,ad_soyad')
            ->get();

        if ($firmalar->isEmpty()) {
            return [];
        }

        $idler = $firmalar->pluck('id')->all();

        $denetimSon = SahaDenetimi::query()->whereIn('firma_id', $idler)
            ->selectRaw('firma_id, MAX(denetim_tarihi) as son')
            ->groupBy('firma_id')->pluck('son', 'firma_id');

        $kanitSon = SahaZiyaretKaniti::query()->whereIn('firma_id', $idler)
            ->selectRaw('firma_id, MAX(ziyaret_tarihi) as son')
            ->groupBy('firma_id')->pluck('son', 'firma_id');

        $bulguSon = SahaBulgusu::query()->whereIn('firma_id', $idler)
            ->selectRaw('firma_id, MAX(created_at) as son')
            ->groupBy('firma_id')->pluck('son', 'firma_id');

        $bugun = Carbon::today();
        $satirlar = [];

        foreach ($firmalar as $f) {
            $tarihler = collect([
                $denetimSon[$f->id] ?? null,
                $kanitSon[$f->id] ?? null,
                $bulguSon[$f->id] ?? null,
            ])->filter()->map(fn ($t) => Carbon::parse($t)->startOfDay());

            /** @var ?Carbon $son */
            $son = $tarihler->sortDesc()->first();
            $gun = $son ? (int) $son->diffInDays($bugun) : null;
            $esik = match ($f->tehlike_sinifi) {
                'cok_tehlikeli' => 15,
                'tehlikeli' => 30,
                default => 45,
            };

            $satirlar[] = [
                'firma_id' => $f->id,
                'unvan' => $f->secimEtiketi(),
                'tehlike_sinifi' => (string) config('isg.tehlike_siniflari.'.$f->tehlike_sinifi, $f->tehlike_sinifi),
                'igu_ad' => (string) ($f->igu?->ad_soyad ?? 'Atanmadı'),
                'son_ziyaret' => $son?->format('d.m.Y'),
                'gecen_gun' => $gun,
                'kritik' => $gun === null || $gun > $esik,
            ];
        }

        usort($satirlar, function (array $a, array $b): int {
            $ga = $a['gecen_gun'] ?? PHP_INT_MAX;
            $gb = $b['gecen_gun'] ?? PHP_INT_MAX;

            return $gb <=> $ga;
        });

        return array_slice($satirlar, 0, $limit);
    }

    /**
     * Aylık Uzman Değerlendirme Karnesi (0–100 + erken uyarı sinyalleri — F6).
     * Ağırlıklar: Ziyaret uyumu %40, DÖF kapanış oranı %35, Belge/sınıf uyumu %25.
     *
     * @return array<int, array{id: int, ad_soyad: string, tip: string, firma_sayisi: int, ziyaret_puan: int, dof_puan: int, uyum_puan: int, toplam_puan: int, renk: string, uyarilar: array<int, string>}>
     */
    public static function uzmanKarneleri(int $userId): array
    {
        $profesyoneller = IsgProfesyoneli::query()
            ->where('user_id', $userId)
            ->where('aktif', true)
            ->orderBy('ad_soyad')
            ->get();

        $firmalar = Firma::query()
            ->where('user_id', $userId)
            ->where('aktif', true)
            ->get();

        $buAyBas = now()->startOfMonth();
        $ziyaretFirmaIdler = SahaZiyaretKaniti::query()
            ->whereIn('firma_id', $firmalar->pluck('id'))
            ->where('ziyaret_tarihi', '>=', $buAyBas)
            ->pluck('firma_id')
            ->merge(
                SahaDenetimi::query()
                    ->whereIn('firma_id', $firmalar->pluck('id'))
                    ->where('denetim_tarihi', '>=', $buAyBas)
                    ->pluck('firma_id')
            )
            ->unique()
            ->flip()
            ->all();

        $bulgular = SahaBulgusu::query()
            ->whereIn('firma_id', $firmalar->pluck('id'))
            ->get(['firma_id', 'durum', 'termin', 'oncelik']);

        $sonuclar = [];

        foreach ($profesyoneller as $p) {
            $kolon = $p->firmaAlani();
            $pFirmalar = $kolon ? $firmalar->where($kolon, $p->id)->values() : collect();
            $sayi = $pFirmalar->count();
            if ($sayi === 0) {
                continue;
            }

            $pIdler = $pFirmalar->pluck('id')->all();
            $ziyaretEdilen = $pFirmalar->filter(fn (Firma $f) => isset($ziyaretFirmaIdler[$f->id]))->count();
            $ziyaretPuan = (int) round(($ziyaretEdilen / $sayi) * 100);

            $pBulgular = $bulgular->whereIn('firma_id', $pIdler);
            $toplamBulgu = $pBulgular->count();
            $kapaliBulgu = $pBulgular->where('durum', 'kapandi')->count();
            $gecikenBulgu = $pBulgular->where('durum', '!=', 'kapandi')
                ->filter(fn (SahaBulgusu $b) => $b->termin && $b->termin->isPast())
                ->count();
            $dofPuan = $toplamBulgu > 0 ? (int) round(($kapaliBulgu / $toplamBulgu) * 100) : 100;

            $uygunsuzSinif = 0;
            if ($p->tip === 'igu') {
                $sinif = static::uzmanSinifi($p);
                foreach ($pFirmalar as $f) {
                    if ($sinif && ! static::sinifUygunMu($sinif, $f->tehlike_sinifi)) {
                        $uygunsuzSinif++;
                    }
                }
            }
            $tamUyumlu = $pFirmalar->filter(fn (Firma $f) => PortfoyKarne::firmaTamUyumluMu($f))->count();
            $uyumPuan = max(0, (int) round(($tamUyumlu / $sayi) * 100) - ($uygunsuzSinif * 15));

            $toplam = (int) round(($ziyaretPuan * 0.40) + ($dofPuan * 0.35) + ($uyumPuan * 0.25));

            $uyarilar = [];
            if ($ziyaretPuan < 50) {
                $uyarilar[] = 'Bu ay ziyaret açığı (%'.$ziyaretPuan.')';
            }
            if ($gecikenBulgu > 0) {
                $uyarilar[] = $gecikenBulgu.' gecikmiş açık DÖF/bulgu';
            }
            if ($uygunsuzSinif > 0) {
                $uyarilar[] = $uygunsuzSinif.' firmada belge sınıfı uyuşmazlığı';
            }

            $renk = $toplam >= 80 ? '#059669' : ($toplam >= 55 ? '#d97706' : '#dc2626');

            $sonuclar[] = [
                'id' => $p->id,
                'ad_soyad' => $p->ad_soyad,
                'tip' => $p->tipEtiketi(),
                'firma_sayisi' => $sayi,
                'ziyaret_puan' => $ziyaretPuan,
                'dof_puan' => $dofPuan,
                'uyum_puan' => $uyumPuan,
                'toplam_puan' => $toplam,
                'renk' => $renk,
                'uyarilar' => $uyarilar,
            ];
        }

        usort($sonuclar, fn ($a, $b) => $b['toplam_puan'] <=> $a['toplam_puan']);

        return $sonuclar;
    }

    public static function yasalDakika(string $tip, ?string $tehlikeSinifi, int $calisan): int
    {
        if ($calisan <= 0) {
            return 0;
        }

        return match ($tip) {
            'igu' => $calisan * match ($tehlikeSinifi) {
                'cok_tehlikeli' => 40,
                'tehlikeli' => 20,
                default => 10,
            },
            'isyeri_hekimi' => $calisan * match ($tehlikeSinifi) {
                'cok_tehlikeli' => 15,
                'tehlikeli' => 10,
                default => 5,
            },
            'dsp' => ($tehlikeSinifi === 'cok_tehlikeli' && $calisan >= 10)
                ? $calisan * ($calisan < 50 ? 10 : ($calisan < 250 ? 15 : 20))
                : 0,
            default => 0,
        };
    }
}
