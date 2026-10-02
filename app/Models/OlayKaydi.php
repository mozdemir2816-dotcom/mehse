<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Olay Kaydı — İSG olay defterinin tek bir satırı. İş Kazası Raporu'ndan farkı:
 * yaralanma olmasa da (ramak kala, tehlikeli durum/davranış, maddi hasar, çevre)
 * her olayı kapsar; sınıflandırma + potansiyel risk skoru + 5 Neden (5N) kök
 * neden zinciri tutar ve tek tıkla DÖF'e aktarılabilir.
 */
class OlayKaydi extends Model
{
    protected $table = 'olay_kayitlari';

    protected $guarded = ['id'];

    protected $casts = [
        'olay_tarihi' => 'date',
        'bildirim_tarihi' => 'date',
        'sgk_bildirim_tarihi' => 'date',
        'kayip_gun_sayisi' => 'integer',
        'potansiyel_skor' => 'integer',
        'bes_neden' => 'array',
        'balik_kilcigi' => 'array',
        'etkilenen_kategorileri' => 'array',
        'kok_neden_kategorileri' => 'array',
        'taniklar' => 'array',
        'fotograflar' => 'array',
        'etkiler' => 'array',
        'sgk_bildirimi_yapildi' => 'boolean',
        'kolluk_bildirimi_yapildi' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::saving(function (OlayKaydi $o): void {
            $o->belge_no ??= 'OLK-'.now()->format('Y').'-'.str_pad((string) (static::count() + 1), 3, '0', STR_PAD_LEFT);
            $o->potansiyel_skor = static::skorHesapla($o->olasilik, $o->siddet);
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

    public function dofRaporu(): BelongsTo
    {
        return $this->belongsTo(DofRaporu::class);
    }

    /**
     * Potansiyel risk skoru = olasılık sırası (1-5) × şiddet sırası (1-5) → 1-25.
     * (Kayıp/ramak kala olayında "gerçekleşen" değil "olabilecek en kötü sonuç"
     * değerlendirilir — bu yüzden İş Kazası Raporu'ndaki ağırlık derecesinden ayrı.)
     */
    public static function skorHesapla(?string $olasilik, ?string $siddet): ?int
    {
        $o = array_search($olasilik, array_keys(config('isg.olay.olasiliklar', [])), true);
        $s = array_search($siddet, array_keys(config('isg.olay.siddetler', [])), true);

        return ($o !== false && $s !== false) ? ($o + 1) * ($s + 1) : null;
    }

    /** PDF başlığı — olay tipine göre. */
    public function raporBasligi(): string
    {
        return match ($this->olay_tipi) {
            'is_kazasi' => 'İŞ KAZASI RAPORU',
            'ramak_kala' => 'RAMAK KALA OLAYI RAPORU',
            'meslek_hastaligi_supheli' => 'MESLEK HASTALIĞI ŞÜPHESİ RAPORU',
            default => 'OLAY KAYIT VE İNCELEME FORMU',
        };
    }

    public function tipEtiketi(): string
    {
        return config('isg.olay.tipler.'.$this->olay_tipi, $this->olay_tipi ?? '—');
    }

    public function sonucEtiketi(): string
    {
        return config('isg.olay.sonuc_turleri.'.$this->sonuc_turu, $this->sonuc_turu ?? '—');
    }

    public function olasilikEtiketi(): string
    {
        return config('isg.olay.olasiliklar.'.$this->olasilik, $this->olasilik ?? '—');
    }

    public function siddetEtiketi(): string
    {
        return config('isg.olay.siddetler.'.$this->siddet, $this->siddet ?? '—');
    }

    public function potansiyelSeviye(): string
    {
        return match (true) {
            $this->potansiyel_skor === null => '—',
            $this->potansiyel_skor <= 4 => 'Düşük',
            $this->potansiyel_skor <= 9 => 'Orta',
            $this->potansiyel_skor <= 15 => 'Yüksek',
            default => 'Çok Yüksek',
        };
    }

    /** İş kazası / meslek hastalığı şüphesi — SGK/kolluk bildirim alanları görünür. */
    public function isKazasiMi(): bool
    {
        return in_array($this->olay_tipi, ['is_kazasi', 'meslek_hastaligi_supheli'], true);
    }

    public function durumEtiketi(): string
    {
        return config('isg.olay.durumlar.'.($this->durum ?: 'acik'), $this->durum ?? 'Açık');
    }

    public function siniflandirmaEtiketi(): string
    {
        return config('isg.olay.siniflandirmalar.'.$this->siniflandirma, $this->siniflandirma ?? '—');
    }

    public function kazaTuruEtiketi(): string
    {
        return config('isg.is_kazasi.kaza_turleri.'.$this->kaza_turu, $this->kaza_turu ?? '—');
    }

    public function riskAnalizindeEtiketi(): string
    {
        return config('isg.olay.risk_analizi_durumlari.'.$this->risk_analizinde, $this->risk_analizinde ?? '—');
    }

    public function acilDurumEtiketi(): string
    {
        return config('isg.olay.acil_durum_iliskileri.'.$this->acil_durum_iliskisi, $this->acil_durum_iliskisi ?? '—');
    }

    /** Olay etkileri kutucuğu işaretli mi (yaralanma, tıbbi müdahale, …). */
    public function etkiVar(string $anahtar): bool
    {
        return in_array($anahtar, $this->etkiler ?? [], true);
    }

    /**
     * SGK bildirimi için son gün — olaydan sonraki 3 iş günü (5510 s.K. m.13).
     * Hafta sonu atlanır; resmi tatiller hesaba katılmaz.
     */
    public function sgkSonTarih(): ?Carbon
    {
        return $this->olay_tarihi?->copy()->addWeekdays(3);
    }

    /**
     * Raporda eksik kalan alanlar — PDF'in başında sarı uyarı kutusu olarak basılır.
     *
     * @return array<int, string>
     */
    public function eksikUyarilari(): array
    {
        $eksik = [];

        if (! $this->dof_raporu_id && blank($this->duzeltici_faaliyet)) {
            $eksik[] = 'Olay için düzeltici / önleyici faaliyet (DÖF) tanımlanmamış.';
        }
        if (! $this->nedenZinciri() && blank($this->kok_neden)) {
            $eksik[] = 'Kök neden analizi yapılmamış.';
        } elseif (blank($this->kok_neden)) {
            $eksik[] = 'Kök neden metni eksik.';
        }
        if (blank($this->kok_neden_kategorileri)) {
            $eksik[] = 'Kök neden kategorisi eksik.';
        }
        if ($this->isKazasiMi() && ! $this->sgk_bildirimi_yapildi) {
            $eksik[] = 'İş kazasında SGK bildirimi işaretlenmemiş.';
        }
        if ($this->risk_analizinde === 'hayir') {
            $eksik[] = 'Olaya yol açan tehlike risk analizinde yok — risk değerlendirmesi güncellenmeli.';
        }

        return $eksik;
    }

    /**
     * Yasal süre uyarıları (SGK'ya 3 iş günü, kolluğa derhal bildirim).
     *
     * @return array<int, string>
     */
    public function otomatikUyarilar(): array
    {
        if (! $this->isKazasiMi()) {
            return [];
        }

        $uyarilar = [];

        if (! $this->sgk_bildirimi_yapildi) {
            $son = $this->sgkSonTarih();
            $uyarilar[] = 'SGK\'ya bildirim yapılmadı! Olay tarihinden sonraki 3 iş günü içinde bildirim yapılmalıdır'
                .($son ? ' (son gün: '.$son->format('d.m.Y').').' : '.');
        }
        if ($this->olay_tipi === 'is_kazasi' && ! $this->kolluk_bildirimi_yapildi) {
            $uyarilar[] = 'Kolluk kuvvetlerine bildirim yapılmadı! İş kazası derhal kolluğa bildirilmelidir.';
        }

        return $uyarilar;
    }

    /**
     * Genel değerlendirme taslağı — kayıttaki verilerden kalıp metin üretir
     * (kullanıcı formda düzenler). Boş alanlar cümleye girmez.
     */
    public function otomatikDegerlendirme(): string
    {
        $c = [];

        $c[] = trim(sprintf(
            '%s tarihinde%s %s%s meydana gelen olay "%s" olarak kayda alınmıştır.',
            $this->olay_tarihi?->format('d.m.Y') ?? '—',
            $this->olay_saati ? ' saat '.$this->olay_saati.' sularında' : '',
            collect([$this->olay_yeri, $this->bolum, $this->alan])->filter()->implode(' / ') ?: 'işyerinde',
            filled($this->yapilan_is) ? ', '.$this->yapilan_is.' işi sırasında' : '',
            $this->tipEtiketi(),
        ));

        if (filled($this->siniflandirma)) {
            $c[] = 'Olaya yol açan tehlike kaynağı: '.$this->siniflandirmaEtiketi().'.';
        }
        if ($etki = $this->etkiEtiketleri()) {
            $c[] = 'Olay sonuçları: '.implode(', ', $etki).'.';
        }
        if ($this->potansiyel_skor !== null) {
            $c[] = "Potansiyel risk skoru {$this->potansiyel_skor} ({$this->potansiyelSeviye()} risk) olarak değerlendirilmiştir.";
        }
        if ($zincir = $this->nedenZinciri()) {
            $c[] = 'Yapılan 5 Neden analizinde kök nedenin "'.($this->kok_neden ?: end($zincir)).'" olduğu belirlenmiştir.';
        }
        if (filled($this->sistemsel_eksiklik)) {
            $c[] = 'Tespit edilen sistemsel eksiklik: '.$this->sistemsel_eksiklik.'.';
        }
        if ($this->risk_analizinde === 'hayir' || $this->risk_analizinde === 'kismen') {
            $c[] = 'Olaya ilişkin tehlike risk değerlendirmesinde yeterince yer almadığından risk değerlendirmesi güncellenmelidir.';
        }
        if (filled($this->duzeltici_faaliyet)) {
            $c[] = 'Benzer olayların tekrarını önlemek için şu faaliyetler planlanmıştır: '.$this->duzeltici_faaliyet;
        }
        if ($this->isKazasiMi()) {
            $c[] = $this->sgk_bildirimi_yapildi
                ? 'Olay SGK\'ya'.($this->sgk_bildirim_tarihi ? ' '.$this->sgk_bildirim_tarihi->format('d.m.Y').' tarihinde' : '').' bildirilmiştir.'
                : 'Olayın yasal süre içinde SGK\'ya bildirilmesi gerekmektedir.';
        }

        return implode(' ', $c);
    }

    /** @return array<int, string> */
    public function etkiEtiketleri(): array
    {
        return collect($this->etkiler ?? [])
            ->map(fn ($k) => config('isg.olay.etkiler.'.$k.'.ad', $k))
            ->all();
    }

    /** @return array<int, string> */
    public function etkilenenEtiketleri(): array
    {
        return collect($this->etkilenen_kategorileri ?? [])
            ->map(fn ($k) => config('isg.olay.etkilenen_kategorileri.'.$k, $k))
            ->all();
    }

    /** @return array<int, string> */
    public function kokNedenEtiketleri(): array
    {
        return collect($this->kok_neden_kategorileri ?? [])
            ->map(fn ($k) => config('isg.is_kazasi.kok_neden_kategorileri.'.$k, $k))
            ->all();
    }

    /**
     * 5 Neden zinciri — boş adımlar atılır ("Neden 1: … → Neden 2: …" formatına
     * PDF/Excel tarafında dönüştürülür).
     *
     * @return array<int, string>
     */
    public function nedenZinciri(): array
    {
        return array_values(array_filter(
            array_map('trim', $this->bes_neden ?? []),
            fn ($n) => $n !== '',
        ));
    }

    /**
     * Balık kılçığı 6M kategorileri — her kategori için (boş olmayan) neden
     * listesi. Etiketler config'ten; sıra config sırasıyla.
     *
     * @return array<int, array{anahtar: string, etiket: string, nedenler: array<int, string>}>
     */
    public function balikKilcigiKategorileri(): array
    {
        $veri = $this->balik_kilcigi ?? [];

        return collect(config('isg.balik_kilcigi.kategoriler', []))
            ->map(fn (array $tanim, string $anahtar): array => [
                'anahtar' => $anahtar,
                'etiket' => $tanim['ad'],
                'nedenler' => array_values(array_filter(
                    array_map('trim', (array) ($veri[$anahtar] ?? [])),
                    fn ($n) => $n !== '',
                )),
            ])
            ->values()
            ->all();
    }

    public function balikKilcigiDoluMu(): bool
    {
        return collect($this->balikKilcigiKategorileri())->contains(fn (array $k) => filled($k['nedenler']));
    }
}
