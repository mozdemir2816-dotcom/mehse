<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Yangın Güvenliği Genel Durum Değerlendirmesi — firma başına bir kayıt. Bina
 * kullanım türü + bölümler + faaliyet/depolama risklerinden bina yangın tehlike
 * sınıfı (düşük/orta/yüksek) belirlenir; tespitler kütüphaneden seçilir.
 */
class YanginGuvenligiDegerlendirmesi extends Model
{
    use HasFactory;

    protected $table = 'yangin_guvenligi_degerlendirmeleri';

    protected $guarded = ['id'];

    protected $casts = [
        'ozel_kullanimlar' => 'array',
        'bolumler' => 'array',
        'riskler' => 'array',
        'tespitler' => 'array',
        'kat_sayisi' => 'integer',
        'kullanici_yuku' => 'integer',
        'taban_alani_m2' => 'decimal:2',
        'sinif_elle' => 'boolean',
        'degerlendirme_tarihi' => 'date',
    ];

    protected static function booted(): void
    {
        static::saving(function (YanginGuvenligiDegerlendirmesi $d): void {
            if (! $d->sinif_elle) {
                $d->belirlenen_tehlike_sinifi = $d->hesaplaTehlikeSinifi();
            }
        });
    }

    public function firma(): BelongsTo
    {
        return $this->belongsTo(Firma::class);
    }

    public static function firmaIcin(Firma $firma): self
    {
        $kayit = static::firstOrNew(['firma_id' => $firma->id]);

        if (! $kayit->exists) {
            $kayit->save();
        }

        return $kayit;
    }

    /**
     * Seçili risklerin config'teki 'sinif' değerlerinin en yükseği + kullanım
     * türü / bölüm etkisi → bina yangın tehlike sınıfı.
     */
    public function hesaplaTehlikeSinifi(): string
    {
        $agirlik = ['dusuk' => 1, 'orta' => 2, 'yuksek' => 3];
        $katalog = config('isg.yangin_guvenligi.risk_kutuphanesi', []);

        $enYuksek = 1;

        foreach ($this->riskler ?? [] as $risk) {
            $puan = $agirlik[$katalog[$risk] ?? 'dusuk'] ?? 1;
            $enYuksek = max($enYuksek, $puan);
        }

        // Depolama / endüstriyel yapı ve yüksek kullanıcı yükü en az "orta"ya çeker.
        if (in_array($this->kullanim_turu, ['endustriyel', 'depolama'], true) && $enYuksek < 2) {
            $enYuksek = 2;
        }

        if (($this->kullanici_yuku ?? 0) >= 1000 && $enYuksek < 2) {
            $enYuksek = 2;
        }

        return array_flip($agirlik)[$enYuksek] ?? 'dusuk';
    }

    /** Toplam kapalı (yapı inşaat) alanı: taban alanı × zemin üstü kat sayısı (en az 1). */
    public function toplamAlan(): ?float
    {
        $taban = (float) ($this->taban_alani_m2 ?? 0);

        return $taban > 0 ? round($taban * max(1, (int) ($this->kat_sayisi ?? 1)), 2) : null;
    }

    /**
     * Kullanıcı yükü (BYKHY Md.32 + Ek-5/A): brüt alan ÷ kullanım katsayısı; kişi sayısı belli olan
     * yerlerde hesaplanandan az olmamak üzere belirtilen kişi sayısı. Esas alınan = en büyüğü
     * (hesaplanan, girilen, firmanın çalışan sayısı). 02.11.2026: elle girilen "1" raporu bozuyordu.
     *
     * @return array{katsayi: ?float, hesaplanan: ?int, girilen: ?int, calisan: ?int, esas: ?int}
     */
    public function kullaniciYukuHesabi(): array
    {
        $katsayi = config('isg.yangin_guvenligi.kullanici_yuku_katsayilari.'.$this->kullanim_turu);
        $alan = $this->toplamAlan();
        $hesaplanan = $katsayi && $alan ? (int) ceil($alan / $katsayi) : null;
        $girilen = $this->kullanici_yuku ?: null;
        $calisan = (int) ($this->firma?->calisan_sayisi ?? 0) ?: null;
        $esas = max($hesaplanan ?? 0, $girilen ?? 0, $calisan ?? 0) ?: null;

        return ['katsayi' => $katsayi ? (float) $katsayi : null, 'hesaplanan' => $hesaplanan, 'girilen' => $girilen, 'calisan' => $calisan, 'esas' => $esas];
    }

    /**
     * Taşınabilir söndürücü (BYKHY Md.99): düşük tehlikede her 500 m², orta / yüksek tehlikede her 250 m²
     * yapı inşaat alanı için 1 adet 6 kg KKT; ulaşma mesafesi en fazla 25 m; depo / otopark / tesisat
     * dairelerinde ayrıca tekerlekli tip. Alan girilmemişse null.
     *
     * @return array{alan: float, birim: int, adet: int, tekerlekli: array<int, string>}|null
     */
    public function yscHesabi(): ?array
    {
        $alan = $this->toplamAlan();
        if (! $alan) {
            return null;
        }

        $birim = (int) config('isg.yangin_guvenligi.ysc_m2_basina.'.($this->belirlenen_tehlike_sinifi ?: $this->hesaplaTehlikeSinifi()), 250);
        $tekerlekli = array_values(array_intersect(config('isg.yangin_guvenligi.tekerlekli_ysc_bolumleri', []), $this->bolumler ?? []));
        if (($this->ozel_kullanimlar['kapali_otopark'] ?? false) && ! in_array('Kapalı otopark', $tekerlekli, true)) {
            $tekerlekli[] = 'Kapalı otopark';
        }
        if (($this->ozel_kullanimlar['kazan_dairesi'] ?? false) && ! in_array('Kazan dairesi', $tekerlekli, true)) {
            $tekerlekli[] = 'Kazan dairesi';
        }

        return ['alan' => $alan, 'birim' => $birim, 'adet' => max(1, (int) ceil($alan / $birim)), 'tekerlekli' => $tekerlekli];
    }

    /** Tespit listesindeki söndürücü maddesinin firmaya göre hesaplanmış metni (alan yoksa null). */
    public function yscTespitMetni(): ?string
    {
        $h = $this->yscHesabi();
        if (! $h) {
            return null;
        }

        $sayi = fn ($n) => number_format($n, fmod($n, 1) ? 2 : 0, ',', '.');
        $kat = max(1, (int) ($this->kat_sayisi ?? 1));
        $alanAciklama = $kat > 1 ? ' ('.$sayi((float) $this->taban_alani_m2).' m² × '.$kat.' kat)' : '';

        return 'Toplam '.$sayi($h['alan']).' m² kapalı alan'.$alanAciklama.' için en az '.$h['adet'].' adet 6 kg ABC kuru kimyevi tozlu (KKT) '
            .'yangın söndürme cihazı, her '.$h['birim'].' m²\'ye 1 adet düşecek ve ulaşma mesafesi en fazla 25 m olacak şekilde '
            .'dengeli yerleştirilmeli (BYKHY Md.99/2, 99/4). Elektrik panoları ve trafo yakınında ayrıca karbondioksitli (CO₂) söndürücü bulundurulmalı.'
            .($h['tekerlekli'] ? ' '.implode(', ', $h['tekerlekli']).' için ayrıca tekerlekli tip söndürücü gerekir (Md.99/3).' : '');
    }

    /** Rapordaki tespitler: söndürücü maddesi (eski "200 m²" metni dahil) firmaya göre hesaplanmış metinle değişir. */
    public function raporTespitleri(): array
    {
        $metin = $this->yscTespitMetni();

        return collect($this->tespitler ?? [])->map(fn ($t) => $metin && str_starts_with((string) ($t['madde'] ?? ''), 'Yangın söndürme cihazları (YSC)')
            ? [...$t, 'madde' => $metin] : $t)->all();
    }

    public function tehlikeSinifiEtiketi(): string
    {
        return config('isg.yangin_guvenligi.tehlike_siniflari.'.$this->belirlenen_tehlike_sinifi, '—');
    }

    public function kullanimTuruEtiketi(): string
    {
        return config('isg.yangin_guvenligi.kullanim_turleri.'.$this->kullanim_turu, $this->kullanim_turu ?? '—');
    }

    public function doluMu(): bool
    {
        return filled($this->kullanim_turu) || filled($this->riskler) || filled($this->bolumler);
    }
}
