<?php

namespace App\Support;

use App\Models\Firma;
use App\Models\IsKazasiRaporu;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * İş Kazası ve Ramak Kala İstatistikleri Raporu (12 Otomatik Kırılım Tablosu + 7 KPI)
 * — isgpratik "Kaza İstatistikleri" tek sayfa özet PDF karşılığı.
 */
class IsKazasiIstatistikUretici
{
    private const GUNLER = [
        1 => 'Pazartesi',
        2 => 'Salı',
        3 => 'Çarşamba',
        4 => 'Perşembe',
        5 => 'Cuma',
        6 => 'Cumartesi',
        7 => 'Pazar',
    ];

    private const AYLAR = [
        1 => 'Ocak', 2 => 'Şubat', 3 => 'Mart', 4 => 'Nisan',
        5 => 'Mayıs', 6 => 'Haziran', 7 => 'Temmuz', 8 => 'Ağustos',
        9 => 'Eylül', 10 => 'Ekim', 11 => 'Kasım', 12 => 'Aralık',
    ];

    /**
     * Seçili firma (veya uzmanın tüm firmaları) için 7 KPI ve 12 kırılım tablosu hesaplar.
     *
     * @return array{
     *     kpi: array<string, int>,
     *     kirilimlar: array<string, array{baslik: string, satirlar: array<string, int>}>
     * }
     */
    public static function hesapla(?Firma $firma, ?int $userId = null): array
    {
        $sorgu = IsKazasiRaporu::query()->with('firma');

        if ($firma) {
            $sorgu->where('firma_id', $firma->id);
        } elseif ($userId) {
            $sorgu->whereHas('firma', fn ($q) => $q->where('user_id', $userId));
        }

        /** @var Collection<int, IsKazasiRaporu> $raporlar */
        $raporlar = $sorgu->get();

        $toplam = $raporlar->count();
        $kayipGun = (int) $raporlar->sum('kayip_gun_sayisi');
        $sgkBildirilen = $raporlar->where('sgk_bildirimi_yapildi', true)->count();
        $ramakKala = $raporlar->filter(fn (IsKazasiRaporu $r) => in_array($r->agirlik_derecesi, ['ramak_kala', 'yaralanmasiz', 'ilk_yardim'], true) || (int) $r->kayip_gun_sayisi === 0)->count();
        $agirKaza = $raporlar->filter(fn (IsKazasiRaporu $r) => in_array($r->agirlik_derecesi, ['agir', 'uzuv_kaybi', 'olum'], true) || (int) $r->kayip_gun_sayisi >= 15)->count();
        $acikDof = $raporlar->sum(fn (IsKazasiRaporu $r) => collect($r->dof_maddeleri ?? [])->where('durum', '!=', 'tamamlandi')->count());

        $kazaTurleri = config('isg.is_kazasi.kaza_turleri', []);
        $agirliklar = config('isg.is_kazasi.agirlik_dereceleri', []);
        $vucutBolgeleri = config('isg.is_kazasi.vucut_bolgeleri', []);
        $yaralanmaTurleri = config('isg.is_kazasi.yaralanma_turleri', []);
        $kokNedenler = config('isg.is_kazasi.kok_neden_kategorileri', []);

        // 1. Kaza Saati Dilimi
        $saatDilimi = ['08:00 - 12:00 (Sabah)' => 0, '12:00 - 16:00 (Öğleden Sonra)' => 0, '16:00 - 24:00 (Akşam Vardiyası)' => 0, '00:00 - 08:00 (Gece Vardiyası)' => 0, 'Belirtilmemiş' => 0];
        foreach ($raporlar as $r) {
            if (blank($r->kaza_saati)) {
                $saatDilimi['Belirtilmemiş']++;

                continue;
            }
            $saat = (int) substr((string) $r->kaza_saati, 0, 2);
            match (true) {
                $saat >= 8 && $saat < 12 => $saatDilimi['08:00 - 12:00 (Sabah)']++,
                $saat >= 12 && $saat < 16 => $saatDilimi['12:00 - 16:00 (Öğleden Sonra)']++,
                $saat >= 16 && $saat <= 23 => $saatDilimi['16:00 - 24:00 (Akşam Vardiyası)']++,
                default => $saatDilimi['00:00 - 08:00 (Gece Vardiyası)']++,
            };
        }

        // 2. Haftanın Günü
        $gunDagilimi = [];
        foreach ($raporlar as $r) {
            $g = $r->kaza_tarihi ? (self::GUNLER[Carbon::parse($r->kaza_tarihi)->dayOfWeekIso] ?? 'Belirtilmemiş') : 'Belirtilmemiş';
            $gunDagilimi[$g] = ($gunDagilimi[$g] ?? 0) + 1;
        }

        // 3. Aylara Göre
        $ayDagilimi = [];
        foreach ($raporlar as $r) {
            $a = $r->kaza_tarihi ? (self::AYLAR[Carbon::parse($r->kaza_tarihi)->month] ?? 'Belirtilmemiş') : 'Belirtilmemiş';
            $ayDagilimi[$a] = ($ayDagilimi[$a] ?? 0) + 1;
        }

        // 4. Kaza Sonucu / Ağırlık Derecesi
        $agirlikDagilimi = $raporlar->countBy(fn (IsKazasiRaporu $r) => $agirliklar[$r->agirlik_derecesi] ?? ($r->agirlik_derecesi ?: 'Belirtilmemiş'))->all();

        // 5. Kaza Türü
        $turDagilimi = $raporlar->countBy(fn (IsKazasiRaporu $r) => $kazaTurleri[$r->kaza_turu] ?? ($r->kaza_turu ?: 'Belirtilmemiş'))->all();

        // 6. Yaralanan Vücut Bölgesi & 7. Yaralanma Türü
        $bolgeDagilimi = [];
        $yTurDagilimi = [];
        foreach ($raporlar as $r) {
            foreach ($r->yaralanmalar ?? [] as $y) {
                if (! empty($y['bolge'])) {
                    $etiket = $vucutBolgeleri[$y['bolge']] ?? $y['bolge'];
                    $bolgeDagilimi[$etiket] = ($bolgeDagilimi[$etiket] ?? 0) + 1;
                }
                if (! empty($y['tur'])) {
                    $etiket = $yaralanmaTurleri[$y['tur']] ?? $y['tur'];
                    $yTurDagilimi[$etiket] = ($yTurDagilimi[$etiket] ?? 0) + 1;
                }
            }
        }

        // 8. Görev / Meslek
        $gorevDagilimi = $raporlar->countBy(fn (IsKazasiRaporu $r) => filled($r->kazazede_gorev) ? trim($r->kazazede_gorev) : 'Belirtilmemiş')->all();

        // 9. Kaza Yeri / Bölüm
        $yerDagilimi = $raporlar->countBy(fn (IsKazasiRaporu $r) => filled($r->kaza_yeri) ? trim($r->kaza_yeri) : 'Belirtilmemiş')->all();

        // 10. Kıdem Durumu
        $kidemDagilimi = $raporlar->countBy(fn (IsKazasiRaporu $r) => filled($r->kazazede_kidem) ? trim($r->kazazede_kidem) : 'Belirtilmemiş')->all();

        // 11. Kök Neden Kategorileri
        $kokDagilimi = [];
        foreach ($raporlar as $r) {
            foreach ($r->kok_neden_kategorileri ?? [] as $k) {
                $etiket = $kokNedenler[$k] ?? $k;
                $kokDagilimi[$etiket] = ($kokDagilimi[$etiket] ?? 0) + 1;
            }
        }

        // 12. Firmalara Göre Dağılım
        $firmaDagilimi = $raporlar->countBy(fn (IsKazasiRaporu $r) => $r->firma?->unvan ?? 'Belirtilmemiş')->all();

        return [
            'kpi' => [
                'toplam' => $toplam,
                'kayip_gun' => $kayipGun,
                'ramak_kala' => $ramakKala,
                'agir_kaza' => $agirKaza,
                'sgk_bildirilen' => $sgkBildirilen,
                'acik_dof' => (int) $acikDof,
            ],
            'kirilimlar' => [
                'saat' => ['baslik' => '1. Kaza Saati Dilimi', 'satirlar' => array_filter($saatDilimi)],
                'gun' => ['baslik' => '2. Haftanın Günü', 'satirlar' => $gunDagilimi],
                'ay' => ['baslik' => '3. Aylara Göre Dağılım', 'satirlar' => $ayDagilimi],
                'agirlik' => ['baslik' => '4. Kaza Sonucu / Ağırlık Derecesi', 'satirlar' => $agirlikDagilimi],
                'tur' => ['baslik' => '5. Kaza Türü', 'satirlar' => $turDagilimi],
                'bolge' => ['baslik' => '6. Yaralanan Vücut Bölgesi', 'satirlar' => $bolgeDagilimi],
                'yaralanma_turu' => ['baslik' => '7. Yaralanma Türü', 'satirlar' => $yTurDagilimi],
                'gorev' => ['baslik' => '8. Görev / Meslek', 'satirlar' => $gorevDagilimi],
                'yer' => ['baslik' => '9. Kaza Yeri / Bölüm', 'satirlar' => $yerDagilimi],
                'kidem' => ['baslik' => '10. Kıdem Durumu', 'satirlar' => $kidemDagilimi],
                'kok_neden' => ['baslik' => '11. Kök Neden Kategorileri', 'satirlar' => $kokDagilimi],
                'firma' => ['baslik' => '12. Firmalara Göre Dağılım', 'satirlar' => $firmaDagilimi],
            ],
        ];
    }

    public static function pdf(?Firma $firma, ?int $userId = null): StreamedResponse
    {
        $veri = static::hesapla($firma, $userId);

        $pdf = Pdf::loadView('pdf.is-kazasi-istatistikleri', [
            'firma' => $firma,
            'kpi' => $veri['kpi'],
            'kirilimlar' => $veri['kirilimlar'],
            'tarih' => now()->format('d.m.Y'),
        ])->setPaper('a4');

        $ad = 'is-kazasi-istatistikleri-'.Str::slug($firma?->unvan ?? 'portfoy').'.pdf';

        return response()->streamDownload(fn () => print ($pdf->output()), $ad);
    }
}
