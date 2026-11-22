<?php

namespace App\Support;

use App\Models\Firma;
use App\Models\SahaZiyaretKaniti;
use App\Models\User;
use Illuminate\Support\Carbon;

/**
 * Ziyaretlerde 4'lü Kanıt (QR Giriş/Çıkış + GPS + Gerçekleşen Dakika + Defter Nüshası)
 * ve "Bu Ay Eksik Saha Süresi (dk)" hesaplayıcısı (E2 — isgsuite #m=visits artısı).
 */
class SahaZiyaretKanitYonetici
{
    /**
     * Portföydeki aktif firmalar için belirtilen ayda (YYYY-MM) yasal dakika,
     * gerçekleşen kanıtlı dakika ve eksik kalan dakikayı hesaplar.
     *
     * @return array<int, array{firma_id: int, unvan: string, uzman: string, yasal_dk: int, gerceklesen_dk: int, eksik_dk: int, planli_var: bool, kanit_sayisi: int}>
     */
    public static function eksikSureTablosu(User $user, ?string $ay = null): array
    {
        $ay = $ay ?: now()->format('Y-m');
        $bas = Carbon::parse($ay.'-01')->startOfMonth();
        $son = $bas->copy()->endOfMonth();

        $firmalar = Firma::query()
            ->where('user_id', $user->id)
            ->where('aktif', true)
            ->with('igu:id,ad_soyad')
            ->orderBy('unvan')
            ->get();

        $kanitlar = SahaZiyaretKaniti::query()
            ->where('user_id', $user->id)
            ->whereBetween('ziyaret_tarihi', [$bas->toDateString(), $son->toDateString()])
            ->get()
            ->groupBy('firma_id');

        $takvimGruplari = ZiyaretTakvimi::gunlukGruplar($user->id);
        $planliFirmaIdler = collect($takvimGruplari)
            ->filter(fn ($_, string $tarih) => str_starts_with($tarih, $ay))
            ->flatten(1)
            ->pluck('firma.id')
            ->filter()
            ->unique()
            ->all();

        $satirlar = [];
        foreach ($firmalar as $f) {
            $yasalDk = YasalZiyaretSuresi::dakika($f);
            $fKanit = $kanitlar->get($f->id, collect());
            $gercekDk = (int) $fKanit->sum('sure_dk');
            $eksikDk = max(0, $yasalDk - $gercekDk);

            $satirlar[] = [
                'firma_id' => $f->id,
                'unvan' => $f->secimEtiketi(),
                'uzman' => $f->igu?->ad_soyad ?: $user->name,
                'yasal_dk' => $yasalDk,
                'gerceklesen_dk' => $gercekDk,
                'eksik_dk' => $eksikDk,
                'planli_var' => in_array($f->id, $planliFirmaIdler, true),
                'kanit_sayisi' => $fKanit->count(),
            ];
        }

        usort($satirlar, fn (array $a, array $b) => $b['eksik_dk'] <=> $a['eksik_dk']);

        return $satirlar;
    }

    public static function girisYap(
        User $user,
        Firma $firma,
        ?string $konu = null,
        ?float $enlem = null,
        ?float $boylam = null,
        ?string $qrKod = null,
        ?string $defterDosyasi = null,
    ): SahaZiyaretKaniti {
        $qrOk = filled($qrKod) && (
            FirmaRiskBildirimQrUretici::imzaGecerliMi($firma, trim($qrKod))
            || str_contains((string) $qrKod, FirmaRiskBildirimQrUretici::imzaKodu($firma))
        );

        return SahaZiyaretKaniti::create([
            'user_id' => $user->id,
            'firma_id' => $firma->id,
            'ziyaret_tarihi' => now()->toDateString(),
            'konu' => trim((string) $konu) ?: 'Periyodik Saha Gözetimi',
            'giris_at' => now(),
            'giris_enlem' => $enlem,
            'giris_boylam' => $boylam,
            'qr_dogrulandi' => $qrOk,
            'defter_dosyasi' => $defterDosyasi,
            'durum' => 'devam_ediyor',
        ]);
    }

    public static function cikisYap(
        SahaZiyaretKaniti $kanit,
        ?float $enlem = null,
        ?float $boylam = null,
        ?int $manuelDakika = null,
        bool $imzaOnay = true,
        ?string $defterDosyasi = null,
    ): SahaZiyaretKaniti {
        $cikis = now();
        $hesaplananDk = $kanit->giris_at
            ? max(1, (int) $kanit->giris_at->diffInMinutes($cikis))
            : 60;

        $kanit->forceFill([
            'cikis_at' => $cikis,
            'cikis_enlem' => $enlem ?? $kanit->cikis_enlem,
            'cikis_boylam' => $boylam ?? $kanit->cikis_boylam,
            'sure_dk' => $manuelDakika && $manuelDakika > 0 ? $manuelDakika : $hesaplananDk,
            'imza_onay' => $imzaOnay,
            'defter_dosyasi' => $defterDosyasi ?: $kanit->defter_dosyasi,
            'durum' => 'tamamlandi',
        ])->save();

        return $kanit->refresh();
    }
}
