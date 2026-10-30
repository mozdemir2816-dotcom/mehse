<?php

namespace App\Models;

use App\Support\EgitimKonulari;
use App\Support\YillikPlanSablonu;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Yıllık Planlar — isgpratik 86-90.jpg. Firma + yıl başına bir kayıt, 3
 * sekme: Çalışma Planı / Eğitim Planı (ikisi de ay durum matrisli) /
 * Değerlendirme Raporu (satır bazlı serbest metin, ay matrisi yok).
 */
class YillikPlan extends Model
{
    use HasFactory;

    protected $table = 'yillik_planlar';

    protected $guarded = ['id'];

    protected $casts = [
        'yil' => 'integer',
        'baslangic_ayi' => 'integer',
        'faaliyetler' => 'array',
        'egitimler' => 'array',
        'degerlendirmeler' => 'array',
    ];

    public function firma(): BelongsTo
    {
        return $this->belongsTo(Firma::class);
    }

    public static function firmaYilIcin(Firma $firma, int $yil): self
    {
        $plan = static::firstOrNew(['firma_id' => $firma->id, 'yil' => $yil]);

        if (! $plan->exists) {
            // Tek şablon (config/yillik_plan_sablonu.php); 4. bölüm inşaat firmalarında dolu.
            $icerik = YillikPlanSablonu::icerik($firma, $yil);

            $plan->faaliyetler = $icerik['faaliyetler'];
            $plan->egitimler = $icerik['egitimler'];
            // Kullanıcının İSG Yıllık Değerlendirme Raporu şablonu (36 satır) — kayıtlardan dolu gelir.
            $plan->degerlendirmeler = \App\Support\YillikDegerlendirmeVerisi::planiDoldur($firma, $yil, [])['satirlar'];
            $plan->save();
            // Plan açılmadan önce girilmiş eğitimler de "tamamlandı" işlenir.
            EgitimKonulari::kayitlariPlanaIsle($plan);
        } elseif (YillikPlanSablonu::eskiYapidaMi($plan)) {
            // Eski genel varsayılanlarla açılmış plan — kullanıcı kararıyla (01.10.2026)
            // çalışma + eğitim satırları bir kez yeni şablona çevrilir. Değerlendirme
            // sekmesine dokunulmaz.
            $icerik = YillikPlanSablonu::icerik($firma, $yil);
            $plan->update(['faaliyetler' => $icerik['faaliyetler'], 'egitimler' => $icerik['egitimler']]);
            EgitimKonulari::kayitlariPlanaIsle($plan);
        } elseif (EgitimKonulari::cevrilmeliMi($plan->egitimler ?? [])) {
            // Temel konular ortak ana listeye (kodlu) bir kez çevrilir (25.10.2026); ay durumları korunur
            // ve o yıl girilmiş eğitim kayıtları plana işlenir.
            $plan->update(['egitimler' => EgitimKonulari::planSatirlariniCevir($plan->egitimler ?? [])]);
            EgitimKonulari::kayitlariPlanaIsle($plan);
        }

        // 50 çalışan altı işyerinde İSG Kurulu zorunlu değil (30.10.2026): planlı ama gerçekleşmemiş kurul ayları boşaltılır,
        // gerçekleşmiş (tamamlandı) aylara dokunulmaz.
        if ($plan->exists && ($firma->calisan_sayisi ?? 0) < 50) {
            $faaliyetler = $plan->faaliyetler ?? [];
            $degisti = false;

            foreach ($faaliyetler as $i => $f) {
                if (($f['ana_konu'] ?? null) !== 'İSG KURULU' || ! in_array('planlandi', $f['aylar'] ?? [], true)) {
                    continue;
                }

                $faaliyetler[$i]['aylar'] = array_map(fn ($d) => $d === 'planlandi' ? 'bos' : $d, $f['aylar']);
                $degisti = true;
            }

            if ($degisti) {
                $plan->update(['faaliyetler' => $faaliyetler]);
            }
        }

        return $plan;
    }

    /**
     * Varsayılan maddelere 12 aylık durum dizisi ekler. `varsayilan_aylar`
     * (0-11) verilenler otomatik "Planlandı" işaretlenir — ancak atanmış uzman
     * öncesindeki ($kilitAy) aylar boş bırakılır: kullanıcı veri girmeden plan
     * dolu gelsin, sonra gerekirse düzeltsin.
     *
     * @param  array<int, array<string, mixed>>  $maddeler
     * @return array<int, array<string, mixed>>
     */
    public static function maddeAylarIle(array $maddeler, int $kilitAy = 0): array
    {
        return collect($maddeler)
            ->map(function (array $m) use ($kilitAy): array {
                $aylar = array_fill(0, 12, 'bos');

                foreach ($m['varsayilan_aylar'] ?? [] as $ay) {
                    if ($ay >= $kilitAy && $ay <= 11) {
                        $aylar[$ay] = 'planlandi';
                    }
                }

                return [...$m, 'aylar' => $aylar];
            })
            ->all();
    }

    /**
     * Excel'deki P/G sütunlarının ekran karşılığı: ay durumu tek değerde tutulur
     * (bos / planlandi / tamamlandi = P+G). P hücresi planı açar/kapatır, G hücresi
     * "Gerçekleşti"yi açar/kapatır (plansız yapılan iş de G ile işaretlenebilir).
     */
    public static function hucreDurumu(string $mevcut, string $hucre): string
    {
        if ($hucre === 'G') {
            return $mevcut === 'tamamlandi' ? 'planlandi' : 'tamamlandi';
        }

        return $mevcut === 'bos' ? 'planlandi' : 'bos';
    }

    /**
     * Gecikme sınırı: bu aydan ÖNCEKİ (0–11) aylarda P'de kalmış hücre gecikmiştir.
     * Geçmiş yıl: 12 (hepsi), gelecek yıl: 0 (hiçbiri), bu yıl: içinde bulunulan ay.
     */
    public function gecikmeAyIndeksi(): int
    {
        return match (true) {
            $this->yil < (int) now()->format('Y') => 12,
            $this->yil > (int) now()->format('Y') => 0,
            default => (int) now()->format('n') - 1,
        };
    }

    /**
     * Çalışma + eğitim planının P/G özeti. Vadesi gelmiş hücreler (gecikme sınırından önceki
     * aylar) ile G olan hücreler "planlanan" sayılır; oran = gerçekleşen / planlanan.
     *
     * @return array{planlanan: int, gerceklesen: int, geciken: int, oran: int|null}
     */
    public function gerceklesmeOzeti(): array
    {
        $sinir = $this->gecikmeAyIndeksi();
        $planlanan = $gerceklesen = $geciken = 0;

        foreach (['faaliyetler', 'egitimler'] as $alan) {
            foreach (($this->{$alan} ?? []) as $satir) {
                foreach (($satir['aylar'] ?? []) as $ay => $durum) {
                    if ($durum === 'tamamlandi') {
                        $planlanan++;
                        $gerceklesen++;
                    } elseif ($durum === 'planlandi' && $ay < $sinir) {
                        $planlanan++;
                        $geciken++;
                    }
                }
            }
        }

        return [
            'planlanan' => $planlanan,
            'gerceklesen' => $gerceklesen,
            'geciken' => $geciken,
            'oran' => $planlanan > 0 ? (int) round(100 * $gerceklesen / $planlanan) : null,
        ];
    }

    /** Satırın genel durumu — Excel "Durum" sütunu ve ekran aynı metni kullanır. */
    public static function faaliyetDurumu(array $aylar): string
    {
        $degerler = collect($aylar);

        return match (true) {
            $degerler->contains('tamamlandi') && ! $degerler->contains('planlandi') => 'Gerçekleşti',
            $degerler->contains('tamamlandi') => 'Devam Ediyor',
            $degerler->contains('planlandi') => 'Planlandı',
            default => '—',
        };
    }

    /**
     * Bir ayın (0–11) ziyaret yapılacaklar listesi: çalışma + eğitim planında o
     * ay P (veya G) olan satırlar. Ziyaret Programı ve ana sayfa widget'ı kullanır.
     *
     * @return array<int, array{alan: string, index: int, baslik: string, grup: string, sorumlu: ?string, gerceklesti: bool}>
     */
    public function ayinYapilacaklari(int $ay): array
    {
        $kategoriler = config('isg.yillik_plan.egitim_kategorileri', []);
        $liste = [];

        foreach (['faaliyetler', 'egitimler'] as $alan) {
            foreach (($this->{$alan} ?? []) as $i => $m) {
                $durum = $m['aylar'][$ay] ?? 'bos';
                if ($durum === 'bos') {
                    continue;
                }

                $liste[] = [
                    'alan' => $alan,
                    'index' => $i,
                    'baslik' => (string) ($alan === 'faaliyetler' ? ($m['faaliyet'] ?? '') : ($m['konu'] ?? '')),
                    'grup' => $alan === 'faaliyetler'
                        ? (string) ($m['ana_konu'] ?? 'ÇALIŞMA PLANI')
                        : 'EĞİTİM — '.($kategoriler[$m['kategori'] ?? ''] ?? 'Diğer'),
                    'sorumlu' => $m['sorumlu'] ?? $m['egitici'] ?? null,
                    'gerceklesti' => $durum === 'tamamlandi',
                ];
            }
        }

        return $liste;
    }

    /** Yapılacaklar listesinden "Gerçekleşti" işaretini açar/kapatır (planlı satır P olarak kalır). */
    public function gerceklestiDegistir(string $alan, int $index, int $ay): void
    {
        if (! in_array($alan, ['faaliyetler', 'egitimler'], true) || $ay < 0 || $ay > 11) {
            return;
        }

        $satirlar = $this->{$alan} ?? [];
        if (! isset($satirlar[$index])) {
            return;
        }

        $satirlar[$index]['aylar'][$ay] = static::hucreDurumu($satirlar[$index]['aylar'][$ay] ?? 'bos', 'G');
        $this->update([$alan => $satirlar]);
    }
}
