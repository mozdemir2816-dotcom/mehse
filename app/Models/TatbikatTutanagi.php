<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Tatbikat Tutanağı — isgpratik 61-65.jpg; isgsuite "Tatbikat Yönetimi" ile
 * planlama durumu eklendi (planlandı → yapıldı / takip gerekiyor).
 */
class TatbikatTutanagi extends Model
{
    use HasFactory;

    /** Yönetmelik gereği "yapılmış tatbikat" sayılan durumlar. */
    public const YAPILMIS = ['yapildi', 'takip'];

    protected $table = 'tatbikat_tutanaklari';

    protected $guarded = ['id'];

    protected $attributes = ['durum' => 'yapildi'];

    protected $casts = [
        'tatbikat_tarihi' => 'date',
        'belge_tarihi' => 'date',
        'haberli_tatbikat' => 'boolean',
        'yillik_plan_dahilinde' => 'boolean',
        'isyeri_hekimi_imzasi' => 'boolean',
        'katilimci_sayisi' => 'integer',
        'tahliye_dk' => 'integer',
        'hedef_tahliye_dk' => 'integer',
        'ekipler' => 'array',
        'degerlendirmeler' => 'array',
        'eksiklikler' => 'array',
        'dof_onerileri' => 'array',
        'katilimcilar' => 'array',
        'fotograflar' => 'array',
    ];

    public function firma(): BelongsTo
    {
        return $this->belongsTo(Firma::class);
    }

    public function senaryoEtiketi(): string
    {
        return config('isg.tatbikat.senaryolar.'.$this->senaryo_anahtari.'.ad', 'Genel Tatbikat');
    }

    public function durumEtiketi(): string
    {
        return config('isg.tatbikat.durumlar.'.$this->durum, $this->durum ?? '—');
    }

    public function yapildiMi(): bool
    {
        return in_array($this->durum, self::YAPILMIS, true);
    }

    /** Planlanan tarih geçtiği hâlde hâlâ "planlandı" duran kayıt. */
    public function gecikmisMi(): bool
    {
        return $this->durum === 'planlandi' && $this->tatbikat_tarihi?->lt(today());
    }

    /** Elle girilen sayı yoksa katılımcı listesi sayılır. */
    public function katilimciSayisi(): int
    {
        return $this->katilimci_sayisi ?? count($this->katilimcilar ?? []);
    }

    /**
     * Senaryo şablonundaki {firma}, {adres}, {yer}, {toplanma} yer tutucularını doldurur (02.11.2026).
     * Bilinmeyen değerler genel ifadeyle değiştirilir; uzman metni sonra düzenleyebilir.
     */
    public static function senaryoMetniDoldur(string $sablon, ?Firma $firma, ?string $yer = null, ?string $toplanma = null): string
    {
        $adres = trim((string) $firma?->adres);

        $metin = strtr($sablon, [
            '{firma}' => trim((string) $firma?->unvan) ?: 'işyeri',
            '{adres}' => $adres !== '' ? $adres : 'adres belirtilmedi',
            '{yer}' => trim((string) $yer) ?: 'ilgili',
            '{toplanma}' => trim((string) $toplanma) ?: 'belirlenen',
        ]);

        // Adres yoksa "(adres belirtilmedi)" parantezini tamamen kaldır.
        return str_replace(' (adres belirtilmedi)', '', $metin);
    }

    /** Başlama → bitiş arası toplam tatbikat süresi (dakika); saatler eksik / hatalıysa null. */
    public function toplamSureDk(): ?int
    {
        if (! preg_match('/^(\d{1,2}):(\d{2})/', (string) $this->baslama_saati, $b)
            || ! preg_match('/^(\d{1,2}):(\d{2})/', (string) $this->bitis_saati, $s)) {
            return null;
        }

        $fark = ((int) $s[1] * 60 + (int) $s[2]) - ((int) $b[1] * 60 + (int) $b[2]);

        return $fark > 0 ? $fark : null;
    }

    /** Hedef tahliye süresi: kayıttaki değer, yoksa senaryonun önerilen değeri. */
    public function hedefTahliyeDk(): ?int
    {
        return $this->hedef_tahliye_dk ?: (config('isg.tatbikat.senaryolar.'.$this->senaryo_anahtari.'.hedef_tahliye_dk') ?: null);
    }

    /** Tahliye hedef süre içinde mi? (iki değerden biri yoksa null) */
    public function hedefTuttuMu(): ?bool
    {
        $hedef = $this->hedefTahliyeDk();

        $sure = $this->gerceklesenTahliyeDk();

        return $hedef && $sure ? $sure <= $hedef : null;
    }

    /** Tahliye süresi: kronometreyle girilen değer, yoksa başlama–bitiş farkı. */
    public function gerceklesenTahliyeDk(): ?int
    {
        return $this->tahliye_dk ?: $this->toplamSureDk();
    }

    /**
     * 02.11.2026'dan önceki 2 satırlık jenerik senaryo metinleri (md5). Bu metinle kaydedilmiş eski
     * tutanaklarda rapora kütüphanedeki ayrıntılı senaryo basılır.
     */
    private const ESKI_KISA_METINLER = [
        '1e971580aa5ced74a6f1236de4c406ba', '41912bdfe55ea7f51d8e5dad42ba0cab', 'b511a8e70710d3af4e5153e370b86d8f',
        '5106f533449f650074609f579ff87617', '3f93e3e97648ced20b9283cb62620ea6', 'dc0b90e3e2244665c10089ed73e8f00b',
        'e007440db20b2393792f9629df627a5e', 'e23f7676a539cc6e43660afa0a7b14df', '75c1998fe6193f16a7a553d2451e8ab3',
        '8178f576cbbbe3abc7afb8d86f16ea21',
    ];

    /** Rapordaki senaryo: uzmanın metni; boş veya eski jenerik metinse kütüphanedeki ayrıntılı senaryo. */
    public function raporSenaryoMetni(): ?string
    {
        $metin = trim((string) $this->senaryo_metni);
        $sablon = config('isg.tatbikat.senaryolar.'.$this->senaryo_anahtari.'.metin');

        if ($sablon && ($metin === '' || in_array(md5($metin), self::ESKI_KISA_METINLER, true))) {
            return static::senaryoMetniDoldur($sablon, $this->firma, $this->tatbikat_yeri, $this->toplanma_alani ?: $this->firma?->acilDurumPlani?->toplanma_yeri);
        }

        return $metin !== '' ? $metin : null;
    }

    /** Yangın türü tatbikatlarda söndürme ekibi görev almak zorundadır. */
    public function yanginTuruMu(): bool
    {
        return in_array($this->senaryo_anahtari, ['yangin', 'elektrik_yangini'], true);
    }

    public function sondurmeEkibiVarMi(): bool
    {
        return collect($this->ekipler ?? [])->contains(fn ($e) => str_contains(mb_strtolower((string) ($e['ekip'] ?? '')), 'söndürme'));
    }

    /** Firmanın Acil Durum Ekipleri'ndeki söndürme ekibi üyeleri (ad soyad). */
    public static function sondurmeEkibiUyeleri(?Firma $firma): array
    {
        return $firma ? (\App\Support\AcilEkipDurumu::planListesi($firma)['sondurme'] ?? []) : [];
    }

    /**
     * Rapordaki ekipler: yangın türünde söndürme ekibi tabloda yoksa firmanın tanımlı söndürme
     * ekibi üyeleri eklenir.
     *
     * @return array<int, array{ad_soyad: string, ekip: ?string}>
     */
    public function raporEkipleri(): array
    {
        $ekipler = array_values($this->ekipler ?? []);

        if ($this->yanginTuruMu() && ! $this->sondurmeEkibiVarMi()) {
            foreach (static::sondurmeEkibiUyeleri($this->firma) as $ad) {
                $ekipler[] = ['ad_soyad' => $ad, 'ekip' => 'Söndürme Ekibi'];
            }
        }

        return $ekipler;
    }

    /** Yalnız diskte gerçekten bulunan fotoğrafların tam yolları. */
    public function raporFotograflari(): array
    {
        return collect($this->fotograflar ?? [])->filter(fn ($f) => filled($f))
            ->map(fn ($f) => \Illuminate\Support\Facades\Storage::disk('public')->path($f))
            ->filter(fn ($yol) => is_file($yol) && @getimagesize($yol) !== false)
            ->values()->all();
    }

    /** İmza bloğu adları: tutanaktaki ad, yoksa firmada tanımlı yetkili / atanmış İGU / işyeri hekimi. */
    public function isverenAdi(): ?string
    {
        return $this->isveren_vekili ?: ($this->firma?->isveren_ad ?: $this->firma?->isveren_vekili) ?: null;
    }

    public function iguAdi(): ?string
    {
        return $this->is_guvenligi_uzmani ?: ($this->firma?->igu?->ad_soyad ?: $this->firma?->user?->name) ?: null;
    }

    public function hekimAdi(): ?string
    {
        return $this->firma?->isyeriHekimi?->ad_soyad ?: null;
    }

    /** Eksiklik veya DÖF önerisi olan yapılmış tatbikat takip ister. */
    public function takipGerektiriyorMu(): bool
    {
        return filled($this->eksiklikler) || filled($this->dof_onerileri);
    }
}
