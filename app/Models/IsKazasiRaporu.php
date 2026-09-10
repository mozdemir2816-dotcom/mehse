<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * İş Kazası İnceleme ve Kök Neden Analiz Raporu — 6331 s.K. + isgpratik 6 adımlı
 * sihirbaz: Genel Bilgiler → 5 Neden → Balık Kılçığı (6M) → DÖF → Foto & Notlar
 * → Önizleme. `bes_neden` sabit 5 soruya karşılık gelen yanıt dizisidir.
 */
class IsKazasiRaporu extends Model
{
    protected $table = 'is_kazasi_raporlari';

    protected $guarded = ['id'];

    protected $casts = [
        'kaza_tarihi' => 'date',
        'sgk_bildirim_tarihi' => 'date',
        'kayip_gun_sayisi' => 'integer',
        'kok_neden_kategorileri' => 'array',
        'bes_neden' => 'array',
        'balik_kilcigi' => 'array',
        'dof_maddeleri' => 'array',
        'taniklar' => 'array',
        'fotograflar' => 'array',
        'sgk_bildirimi_yapildi' => 'boolean',
        'isyeri_hekimi_dahil' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::saving(function (IsKazasiRaporu $r): void {
            $r->belge_no ??= 'IKR-'.now()->format('Y').'-'.str_pad((string) (static::count() + 1), 3, '0', STR_PAD_LEFT);
        });
    }

    public function firma(): BelongsTo
    {
        return $this->belongsTo(Firma::class);
    }

    public function calisan(): BelongsTo
    {
        return $this->belongsTo(Calisan::class);
    }

    public function kazaTuruEtiketi(): string
    {
        return config('isg.is_kazasi.kaza_turleri.'.$this->kaza_turu, $this->kaza_turu ?? '—');
    }

    public function agirlikDerecesiEtiketi(): string
    {
        return config('isg.is_kazasi.agirlik_dereceleri.'.$this->agirlik_derecesi, $this->agirlik_derecesi ?? '—');
    }

    public function durumEtiketi(): string
    {
        return config('isg.is_kazasi.rapor_durumlari.'.$this->durum, $this->durum ?? 'Taslak');
    }

    /** @return array<int, string> */
    public function kokNedenEtiketleri(): array
    {
        return collect($this->kok_neden_kategorileri ?? [])
            ->map(fn ($k) => config('isg.is_kazasi.kok_neden_kategorileri.'.$k, $k))
            ->all();
    }

    /**
     * 5 Neden — sabit soru ile yanıtı eşleştirir (boş yanıt atlanmaz; PDF'de
     * tümü gösterilir).
     *
     * @return array<int, array{no: int, soru: string, yanit: string}>
     */
    public function besNedenSatirlari(): array
    {
        $sorular = config('isg.is_kazasi.bes_neden_sorulari', []);
        $yanitlar = $this->bes_neden ?? [];

        return collect($sorular)
            ->map(fn (string $soru, int $i) => [
                'no' => $i + 1,
                'soru' => $soru,
                'yanit' => trim((string) ($yanitlar[$i] ?? '')),
            ])
            ->all();
    }

    /**
     * Balık kılçığı 6M kategorileri — her kategori için boş olmayan neden listesi.
     *
     * @return array<int, array{anahtar: string, etiket: string, aciklama: string, nedenler: array<int, string>}>
     */
    public function balikKilcigiKategorileri(): array
    {
        $veri = $this->balik_kilcigi ?? [];

        return collect(config('isg.balik_kilcigi.kategoriler', []))
            ->map(fn (array $tanim, string $anahtar): array => [
                'anahtar' => $anahtar,
                'etiket' => $tanim['ad'],
                'aciklama' => $tanim['aciklama'],
                'nedenler' => array_values(array_filter(
                    array_map('trim', (array) ($veri[$anahtar] ?? [])),
                    fn ($n) => $n !== '',
                )),
            ])
            ->values()
            ->all();
    }

    public function balikKilcigiBulguSayisi(): int
    {
        return collect($this->balikKilcigiKategorileri())->sum(fn (array $k) => count($k['nedenler']));
    }

    /** @return array<int, array<string, mixed>> Doldurulmuş (açıklaması olan) DÖF maddeleri. */
    public function dofSatirlari(): array
    {
        return collect($this->dof_maddeleri ?? [])
            ->filter(fn (array $d) => filled(trim((string) ($d['aciklama'] ?? ''))))
            ->values()
            ->all();
    }

    public function dofTipEtiketi(?string $tip): string
    {
        return config('isg.is_kazasi.dof_onlem_tipleri.'.$tip, $tip ?? '—');
    }

    public function dofDurumEtiketi(?string $durum): string
    {
        return config('isg.is_kazasi.dof_durumlari.'.$durum, $durum ?? '—');
    }

    /** Zorunlu alan eksikleri (Önizleme adımı). @return array<int, string> */
    public function eksikAlanlar(): array
    {
        $eksik = [];

        if (blank($this->kaza_tarihi)) {
            $eksik[] = 'Kaza Tarihi';
        }
        if (blank($this->kazazede_ad_soyad)) {
            $eksik[] = 'Kazazede Adı';
        }
        if (blank($this->kaza_yeri)) {
            $eksik[] = 'Kaza Yeri';
        }
        if (blank($this->kaza_tanimi)) {
            $eksik[] = 'Kaza Özeti';
        }

        return $eksik;
    }
}
