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

    /*
    |--------------------------------------------------------------------------
    | Çoklu / bileşik senaryo (02.11.2026)
    |--------------------------------------------------------------------------
    | senaryo_anahtari tek anahtar ("yangin") ya da virgülle ayrılmış birden çok
    | anahtar ("deprem,yangin,genel_tahliye") tutar; yeni sütun gerekmez.
    */

    /**
     * Geçerli senaryo anahtarlarını olayların tetikleme sırasına göre döndürür.
     *
     * @return array<int, string>
     */
    public static function siraliSenaryolar(array $anahtarlar): array
    {
        $sira = array_flip(array_keys(config('isg.tatbikat.bilesik', [])));
        $gecerli = array_values(array_unique(array_filter($anahtarlar, fn ($a) => is_string($a) && config('isg.tatbikat.senaryolar.'.$a))));
        usort($gecerli, fn ($a, $b) => ($sira[$a] ?? 99) <=> ($sira[$b] ?? 99));

        return $gecerli;
    }

    /** @return array<int, string> */
    public function bilesenler(): array
    {
        return static::siraliSenaryolar(explode(',', (string) $this->senaryo_anahtari));
    }

    public function bilesikMi(): bool
    {
        return count($this->bilesenler()) > 1;
    }

    public function senaryoEtiketi(): string
    {
        return static::senaryoBasligi($this->bilesenler());
    }

    /**
     * Tek senaryoda kütüphanedeki ad; birden çoğunda "Bileşik Acil Durum Tatbikatı (Deprem ve İkincil Yangın)".
     * Tahliye ve ilk yardım olay değil aşamadır: başka olay varsa başlıkta yer almaz.
     */
    public static function senaryoBasligi(array $anahtarlar): string
    {
        $anahtarlar = static::siraliSenaryolar($anahtarlar);

        if (count($anahtarlar) <= 1) {
            return config('isg.tatbikat.senaryolar.'.($anahtarlar[0] ?? '').'.ad', 'Genel Tatbikat');
        }

        $kisa = fn ($a) => static::basHarfler(config('isg.tatbikat.bilesik.'.$a.'.kisa', $a));
        $asamalar = ['genel_tahliye', 'ilkyardim_is_kazasi'];
        $olaylar = array_values(array_diff($anahtarlar, $asamalar));

        if ($olaylar === []) {
            $ic = implode(' ve ', array_map($kisa, $anahtarlar));
        } elseif (count($olaylar) === 1) {
            $ic = $kisa($olaylar[0]).' ve '.implode(' ve ', array_map($kisa, array_values(array_intersect($anahtarlar, $asamalar))));
        } else {
            $ic = $kisa($olaylar[0]).' ve İkincil '.implode(', ', array_map($kisa, array_slice($olaylar, 1)));
        }

        return 'Bileşik Acil Durum Tatbikatı ('.$ic.')';
    }

    /** "Gaz kaçağı" → "Gaz Kaçağı" (Türkçe i/ı korunur). */
    private static function basHarfler(string $metin): string
    {
        return implode(' ', array_map(fn ($k) => static::buyukHarf(mb_substr($k, 0, 1)).mb_substr($k, 1), explode(' ', $metin)));
    }

    /** Türkçe büyük harf: i → İ, ı → I (mb_strtoupper "Tatbikatı"nı "TATBIKATI" yapıyordu). */
    public static function buyukHarf(string $metin): string
    {
        return mb_strtoupper(strtr($metin, ['i' => 'İ', 'ı' => 'I']), 'UTF-8');
    }

    /** Senaryo akışı "Deprem → Yangın → Tahliye" (bileşik tatbikatta künyede gösterilir). */
    public function senaryoAkisi(): string
    {
        return implode(' → ', array_map(fn ($a) => config('isg.tatbikat.bilesik.'.$a.'.kisa', $a), $this->bilesenler()));
    }

    /**
     * Seçilen senaryoların şablon metni: tek senaryoda kütüphane metni; birden çoğunda olayların
     * tetikleme sırasına göre numaralı aşamalarla bileşik metin. Yer tutucular senaryoMetniDoldur'da dolar.
     */
    public static function senaryoSablonu(array $anahtarlar): ?string
    {
        $anahtarlar = static::siraliSenaryolar($anahtarlar);

        if (count($anahtarlar) <= 1) {
            return config('isg.tatbikat.senaryolar.'.($anahtarlar[0] ?? '').'.metin');
        }

        $b = config('isg.tatbikat.bilesik');
        $satirlar = ['{firma} ({adres}) işyerinde, birbirini tetikleyen olaylardan oluşan bileşik acil durum senaryosu kurgulanmıştır. Senaryo akışı: '
            .implode(' → ', array_map(fn ($a) => $b[$a]['kisa'], $anahtarlar)).'.'];

        $onceki = null;
        foreach ($anahtarlar as $i => $a) {
            $tetik = $onceki === null ? 'İlk olarak ' : $b[$onceki]['kisa'].' sonrası ';
            $satirlar[] = ($i + 1).'. '.$b[$a]['baslik'].': '.str_replace('{tetik}', $tetik, $b[$a]['asama']);
            if ($a !== 'genel_tahliye') {
                $onceki = $a;
            }
        }

        // Tahliye seçilmemiş olsa da bileşik tatbikat toplanma ve sayımla kapanır.
        if (! in_array('genel_tahliye', $anahtarlar, true)) {
            $satirlar[] = (count($anahtarlar) + 1).'. '.$b['genel_tahliye']['baslik'].': '.$b['genel_tahliye']['asama'];
        }

        return implode("\n", $satirlar);
    }

    /** Seçilen senaryoların kritik kontrol soruları (genel sorulara eklenir). */
    public static function senaryoSorulari(array $anahtarlar): array
    {
        return collect(static::siraliSenaryolar($anahtarlar))
            ->flatMap(fn ($a) => config('isg.tatbikat.bilesik.'.$a.'.sorular', []))
            ->unique()->values()->all();
    }

    /**
     * Seçilen senaryoların gerektirdiği acil durum ekip türleri (sondurme, kurtarma, koruma, ilk_yardim …).
     *
     * @return array<int, string>
     */
    public static function gerekenEkipTurleri(array $anahtarlar): array
    {
        $sira = array_keys(config('isg.acil_durum.ekip_turleri', []));
        $turler = collect(static::siraliSenaryolar($anahtarlar))
            ->flatMap(fn ($a) => config('isg.tatbikat.bilesik.'.$a.'.ekipler', []))->unique()->all();

        return array_values(array_intersect($sira, $turler));
    }

    public static function ekipEtiketi(string $tur): string
    {
        return config('isg.acil_durum.ekip_turleri.'.$tur.'.ad', $tur);
    }

    /** Ekip tablosunda bu türden bir satır var mı ("Söndürme Ekibi", "söndürme" …). */
    public static function ekipVarMi(array $ekipler, string $tur): bool
    {
        $kok = mb_strtolower(str_replace(' Ekibi', '', static::ekipEtiketi($tur)));

        return collect($ekipler)->contains(fn ($e) => str_contains(mb_strtolower((string) ($e['ekip'] ?? '')), $kok));
    }

    /**
     * Firmanın Acil Durum Ekipleri'ndeki üyeler, ekip türüne göre (ad soyad).
     *
     * @return array<string, array<int, string>>
     */
    public static function firmaEkipUyeleri(?Firma $firma): array
    {
        if (! $firma) {
            return [];
        }

        $liste = [];
        foreach (\App\Support\AcilEkipDurumu::ekipler($firma) as $ekip) {
            $liste[$ekip->tur] = array_values(array_unique([...($liste[$ekip->tur] ?? []), ...$ekip->uyeler->pluck('ad_soyad')->filter()->all()]));
        }

        return $liste;
    }

    /**
     * Ekip tablosunda olmayan gerekli ekipleri firmanın tanımlı üyeleriyle tamamlar.
     *
     * @return array{ekipler: array<int, array{ad_soyad: string, ekip: ?string}>, eklenen: array<int, string>, eksik: array<int, string>}
     */
    public static function ekipleriTamamla(array $ekipler, array $anahtarlar, ?Firma $firma): array
    {
        $ekipler = array_values($ekipler);
        $uyeler = null;
        $eklenen = $eksik = [];

        foreach (static::gerekenEkipTurleri($anahtarlar) as $tur) {
            if (static::ekipVarMi($ekipler, $tur)) {
                continue;
            }
            $uyeler ??= static::firmaEkipUyeleri($firma);
            if (($uyeler[$tur] ?? []) === []) {
                $eksik[] = static::ekipEtiketi($tur);

                continue;
            }
            foreach ($uyeler[$tur] as $ad) {
                $ekipler[] = ['ad_soyad' => $ad, 'ekip' => static::ekipEtiketi($tur)];
            }
            $eklenen[] = static::ekipEtiketi($tur);
        }

        return ['ekipler' => $ekipler, 'eklenen' => $eklenen, 'eksik' => $eksik];
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
        return $this->hedef_tahliye_dk ?: static::onerilenHedefDk($this->bilesenler());
    }

    /** Seçili senaryoların önerilen hedef tahliye sürelerinin en büyüğü. */
    public static function onerilenHedefDk(array $anahtarlar): ?int
    {
        return collect($anahtarlar)->map(fn ($a) => (int) config('isg.tatbikat.senaryolar.'.$a.'.hedef_tahliye_dk'))->max() ?: null;
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
        $sablon = static::senaryoSablonu($this->bilesenler());

        if ($sablon && ($metin === '' || in_array(md5($metin), self::ESKI_KISA_METINLER, true))) {
            return static::senaryoMetniDoldur($sablon, $this->firma, $this->tatbikat_yeri, $this->toplanma_alani ?: $this->firma?->acilDurumPlani?->toplanma_yeri);
        }

        return $metin !== '' ? $metin : null;
    }

    /** Yangın türü (yangın / elektrik yangını) bileşen içeriyor mu — söndürme ekibi zorunlu. */
    public function yanginTuruMu(): bool
    {
        return array_intersect($this->bilesenler(), ['yangin', 'elektrik_yangini']) !== [];
    }

    /**
     * Rapordaki ekipler: seçili senaryoların gerektirdiği ekipler tabloda yoksa firmanın Acil Durum
     * Ekipleri'ndeki üyeler eklenir.
     *
     * @return array<int, array{ad_soyad: string, ekip: ?string}>
     */
    public function raporEkipleri(): array
    {
        return static::ekipleriTamamla($this->ekipler ?? [], $this->bilesenler(), $this->firma)['ekipler'];
    }

    /** Tutanakta görev almamış ve firmada da tanımlı olmayan gerekli ekipler (uyarı için). */
    public function eksikEkipler(): array
    {
        return static::ekipleriTamamla($this->ekipler ?? [], $this->bilesenler(), $this->firma)['eksik'];
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
