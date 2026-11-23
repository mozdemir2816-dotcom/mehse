<?php

namespace App\Support;

use App\Models\Firma;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * OSGB & Bireysel İSG Uzmanı Cari Hesap, Aylık Hizmet Bedeli ve Tahsilat Takibi.
 * Veriler users.ayarlar JSON alanında ('cari_hesap') saklanır — ek migration gerektirmez.
 */
class CariHesapYonetici
{
    /** @return array<string, mixed> */
    public static function hamVeri(User $user): array
    {
        $ayar = (array) ($user->ayarlar ?? []);

        return is_array($ayar['cari_hesap'] ?? null) ? $ayar['cari_hesap'] : ['firmalar' => []];
    }

    /** @param  array<string, mixed>  $veri */
    private static function kaydet(User $user, array $veri): void
    {
        \Illuminate\Support\Facades\DB::transaction(function () use ($user, $veri): void {
            $kilitli = User::query()->lockForUpdate()->find($user->id);
            if (! $kilitli) {
                return;
            }
            $mevcutAyarlar = (array) ($kilitli->ayarlar ?? []);
            $mevcutAyarlar['cari_hesap'] = $veri;
            $kilitli->forceFill(['ayarlar' => $mevcutAyarlar])->save();
            $user->ayarlar = $mevcutAyarlar;
        });
    }

    public static function ucretKaydet(User $user, int $firmaId, float $aylikUcret, int $kdv = 20): void
    {
        $veri = self::hamVeri($user);
        $anahtar = (string) $firmaId;
        $mevcut = $veri['firmalar'][$anahtar] ?? ['aylik_ucret' => 0.0, 'kdv' => 20, 'hareketler' => []];

        $mevcut['aylik_ucret'] = max(0.0, round($aylikUcret, 2));
        $mevcut['kdv'] = max(0, min(100, $kdv));
        $veri['firmalar'][$anahtar] = $mevcut;

        self::kaydet($user, $veri);
    }

    public static function hareketEkle(
        User $user,
        int $firmaId,
        string $tur,
        float $tutar,
        string $tarih,
        string $aciklama,
        ?string $donem = null,
    ): void {
        if (! in_array($tur, ['borc', 'tahsilat'], true) || $tutar <= 0) {
            return;
        }

        $veri = self::hamVeri($user);
        $anahtar = (string) $firmaId;
        $mevcut = $veri['firmalar'][$anahtar] ?? ['aylik_ucret' => 0.0, 'kdv' => 20, 'hareketler' => []];

        $mevcut['hareketler'][] = [
            'id' => (string) Str::uuid(),
            'tarih' => Carbon::parse($tarih)->toDateString(),
            'tur' => $tur,
            'tutar' => round($tutar, 2),
            'aciklama' => trim($aciklama) ?: ($tur === 'tahsilat' ? 'Tahsilat' : 'İSG Hizmet Bedeli'),
            'donem' => $donem,
        ];

        usort($mevcut['hareketler'], fn (array $a, array $b) => strcmp($b['tarih'], $a['tarih']));
        $veri['firmalar'][$anahtar] = $mevcut;

        self::kaydet($user, $veri);
    }

    public static function hareketSil(User $user, int $firmaId, string $hareketId): void
    {
        $veri = self::hamVeri($user);
        $anahtar = (string) $firmaId;
        if (! isset($veri['firmalar'][$anahtar]['hareketler'])) {
            return;
        }

        $veri['firmalar'][$anahtar]['hareketler'] = array_values(array_filter(
            $veri['firmalar'][$anahtar]['hareketler'],
            fn (array $h) => ($h['id'] ?? '') !== $hareketId,
        ));

        self::kaydet($user, $veri);
    }

    /** Aylık ücreti tanımlı tüm aktif firmalara bu ayın KDV dahil hizmet bedelini (mükerrersiz) borç işler. */
    public static function buAyTahakkukOlustur(User $user, ?string $donem = null): int
    {
        $donem ??= now()->format('Y-m');
        $donemEtiket = Carbon::parse($donem.'-01')->translatedFormat('F Y');
        $veri = self::hamVeri($user);
        $eklenen = 0;

        $aktifFirmalar = Firma::query()
            ->where('user_id', $user->id)
            ->where('aktif', true)
            ->pluck('id');

        foreach ($aktifFirmalar as $fId) {
            $anahtar = (string) $fId;
            $kayit = $veri['firmalar'][$anahtar] ?? null;
            $ucret = (float) ($kayit['aylik_ucret'] ?? 0);
            if ($ucret <= 0) {
                continue;
            }

            $zatenVar = collect($kayit['hareketler'] ?? [])
                ->contains(fn (array $h) => ($h['tur'] ?? '') === 'borc' && ($h['donem'] ?? null) === $donem);

            if ($zatenVar) {
                continue;
            }

            $kdv = (int) ($kayit['kdv'] ?? 20);
            $kdvDahil = round($ucret * (1 + $kdv / 100), 2);

            $veri['firmalar'][$anahtar]['hareketler'][] = [
                'id' => (string) Str::uuid(),
                'tarih' => now()->toDateString(),
                'tur' => 'borc',
                'tutar' => $kdvDahil,
                'aciklama' => $donemEtiket.' İSG Hizmet Bedeli (%'.$kdv.' KDV dahil)',
                'donem' => $donem,
            ];
            usort($veri['firmalar'][$anahtar]['hareketler'], fn (array $a, array $b) => strcmp($b['tarih'], $a['tarih']));
            $eklenen++;
        }

        if ($eklenen > 0) {
            self::kaydet($user, $veri);
        }

        return $eklenen;
    }

    /** @return array{aylik_ucret: float, kdv: int, toplam_borc: float, toplam_tahsilat: float, bakiye: float, hareketler: array<int, array<string, mixed>>} */
    public static function firmaOzeti(User $user, Firma $firma): array
    {
        $veri = self::hamVeri($user);
        $kayit = $veri['firmalar'][(string) $firma->id] ?? ['aylik_ucret' => 0.0, 'kdv' => 20, 'hareketler' => []];
        $hareketler = array_values((array) ($kayit['hareketler'] ?? []));

        $borc = 0.0;
        $tahsilat = 0.0;
        foreach ($hareketler as $h) {
            if (($h['tur'] ?? '') === 'borc') {
                $borc += (float) ($h['tutar'] ?? 0);
            } elseif (($h['tur'] ?? '') === 'tahsilat') {
                $tahsilat += (float) ($h['tutar'] ?? 0);
            }
        }

        return [
            'aylik_ucret' => (float) ($kayit['aylik_ucret'] ?? 0),
            'kdv' => (int) ($kayit['kdv'] ?? 20),
            'toplam_borc' => round($borc, 2),
            'toplam_tahsilat' => round($tahsilat, 2),
            'bakiye' => round($borc - $tahsilat, 2),
            'hareketler' => $hareketler,
        ];
    }

    /** @return array{aylik_ciro: float, toplam_alacak: float, bu_ay_tahsilat: float, borclu_firma_sayisi: int, satirlar: array<int, array<string, mixed>>} */
    public static function portfoyOzeti(User $user): array
    {
        $firmalar = Firma::query()
            ->where('user_id', $user->id)
            ->where('aktif', true)
            ->orderBy('unvan')
            ->get();

        $buAy = now()->format('Y-m');
        $aylikCiro = 0.0;
        $toplamAlacak = 0.0;
        $buAyTahsilat = 0.0;
        $borcluSayisi = 0;
        $satirlar = [];

        foreach ($firmalar as $f) {
            $o = self::firmaOzeti($user, $f);
            $kdvDahilAylik = round($o['aylik_ucret'] * (1 + $o['kdv'] / 100), 2);
            $aylikCiro += $kdvDahilAylik;

            if ($o['bakiye'] > 0.01) {
                $toplamAlacak += $o['bakiye'];
                $borcluSayisi++;
            }

            foreach ($o['hareketler'] as $h) {
                if (($h['tur'] ?? '') === 'tahsilat' && str_starts_with((string) ($h['tarih'] ?? ''), $buAy)) {
                    $buAyTahsilat += (float) ($h['tutar'] ?? 0);
                }
            }

            $satirlar[] = [
                'firma_id' => $f->id,
                'unvan' => $f->secimEtiketi(),
                'tehlike' => $f->tehlikeSinifiEtiketi(),
                'calisan' => (int) $f->calisan_sayisi,
                'aylik_ucret' => $o['aylik_ucret'],
                'kdv' => $o['kdv'],
                'kdv_dahil_aylik' => $kdvDahilAylik,
                'toplam_borc' => $o['toplam_borc'],
                'toplam_tahsilat' => $o['toplam_tahsilat'],
                'bakiye' => $o['bakiye'],
                'hareket_sayisi' => count($o['hareketler']),
            ];
        }

        return [
            'aylik_ciro' => round($aylikCiro, 2),
            'toplam_alacak' => round($toplamAlacak, 2),
            'bu_ay_tahsilat' => round($buAyTahsilat, 2),
            'borclu_firma_sayisi' => $borcluSayisi,
            'satirlar' => $satirlar,
        ];
    }

    public static function ekstrePdf(User $user, Firma $firma): StreamedResponse
    {
        $ozet = self::firmaOzeti($user, $firma);
        // Ekstrede kronolojik (eskiden yeniye) yürüyen bakiye hesapla
        $kronolojik = array_reverse($ozet['hareketler']);
        $yuruyen = 0.0;
        $satirlar = [];
        foreach ($kronolojik as $h) {
            $borc = ($h['tur'] ?? '') === 'borc' ? (float) $h['tutar'] : 0.0;
            $alacak = ($h['tur'] ?? '') === 'tahsilat' ? (float) $h['tutar'] : 0.0;
            $yuruyen = round($yuruyen + $borc - $alacak, 2);
            $satirlar[] = [
                'tarih' => Carbon::parse($h['tarih'])->format('d.m.Y'),
                'aciklama' => $h['aciklama'] ?? '',
                'borc' => $borc,
                'tahsilat' => $alacak,
                'bakiye' => $yuruyen,
            ];
        }

        $pdf = Pdf::loadView('pdf.cari-hesap-ekstresi', [
            'user' => $user,
            'firma' => $firma,
            'ozet' => $ozet,
            'satirlar' => $satirlar,
            'tarih' => now()->format('d.m.Y'),
        ])->setPaper('a4');

        $ad = 'cari-hesap-ekstresi-'.Str::slug($firma->unvan).'.pdf';

        return response()->streamDownload(fn () => print ($pdf->output()), $ad);
    }
}
