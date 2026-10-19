<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Muayene Formu (EK-2) — İşyeri Hekimi ve Diğer Sağlık Personelinin Görev,
 * Yetki, Sorumluluk ve Eğitimleri Hakkında Yönetmelik EK-2.
 */
class MuayeneFormu extends Model
{
    protected $table = 'muayene_formlari';

    protected $guarded = ['id'];

    protected $casts = [
        'calisan_dogum_tarihi' => 'date',
        'ise_giris_tarihi' => 'date',
        'muayene_tarihi' => 'date',
        'onerilen_kontrol_tarihi' => 'date',
        'sistemik_muayene' => 'array',
        'tetkikler' => 'array',
        'ek2' => 'array',
    ];

    /** EK-2 3.3 Özgeçmiş soruları (referans şablondaki metinler, sırası değişmez). */
    public const OZGECMIS_SORULARI = [
        1 => 'Kronik bir hastalığınız var mı? (Şeker, hipertansiyon, kalp, astım, epilepsi, böbrek vb.)',
        2 => 'Bağışıklık sisteminizi baskılayan bir hastalık veya durum var mı?',
        3 => 'Son 2 yıl içinde hastanede yattınız mı?',
        4 => 'Son 2 yıl içinde ameliyat geçirdiniz mi?',
        5 => 'Daha önce iş kazası geçirdiniz mi?',
        6 => 'Meslek hastalıkları şüphesi ile tetkik veya muayeneye tabi tutuldunuz mu?',
        7 => 'Herhangi bir maluliyet / engellilik raporu aldınız mı?',
        8 => 'Şu anda herhangi bir tedavi görüyor musunuz veya düzenli ilaç kullanıyor musunuz?',
        9 => 'Baş dönmesi, bayılma, denge kaybı, yükseklik korkusu veya gece körlüğü var mı?',
        10 => 'Bel-boyun fıtığı, eklem ağrısı, nefes darlığı, çarpıntı veya işitme kaybı yakınmanız var mı?',
    ];

    /** "Evet" ise açıklama alanının ipucu (referans şablon). */
    public const OZGECMIS_IPUCLARI = [
        3 => 'Tanı:', 4 => 'Neden:', 5 => 'Ne oldu:', 6 => 'Sonuç:', 7 => 'Nedir ve Oranı (%):', 8 => 'İlaç / Tedavi:',
    ];

    /** EK-2 4. Fizik muayene: anahtar => [etiket, alt açıklama, "Normal doldur" metni]. */
    public const FIZIK_SISTEMLERI = [
        'duyu' => ['a) Duyu Organları', '(Göz, KBB, Deri)', 'Görme keskinliği, renk görme, orofarenks, dış kulak yolu/timpanik membran ve cilt muayenesi doğal.'],
        'kvs' => ['b) Kardiyovasküler Sistem', '', 'S1+, S2+ ritmik, ek ses ve üfürüm yok. Periferik nabızlar bilateral açık.'],
        'solunum' => ['c) Solunum Sistemi', '', 'Her iki hemitoraks solunuma eşit katılıyor. Solunum sesleri doğal, ral ve ronküs yok.'],
        'sindirim' => ['d) Sindirim Sistemi', '', 'Batın rahat, defans ve rebound yok, hepatosplenomegali saptanmadı.'],
        'urogenital' => ['e) Ürogenital Sistem', '', 'Kostovertebral açı hassasiyeti (KVAH) bilateral negatif, haricen doğal.'],
        'kas_iskelet' => ['f) Kas - İskelet Sistemi', '', 'Eklem hareket açıklıkları (EHA) tam, kas gücü 5/5, deformite yok.'],
        'norolojik' => ['g) Nörolojik Muayene', '', 'Bilinç açık, koopere, oryante. Kraniyal sinir muayenesi ve serebellar testler doğal, patolojik refleks yok.'],
        'psikiyatrik' => ['h) Psikiyatrik Muayene', '', 'Duygudurum ve duygulanım doğal, gerçeği değerlendirme ve uyum tam.'],
        'diger' => ['ı) Diğer Bulgular', '', 'Ek patolojik bulgu saptanmadı.'],
    ];

    /** EK-2 5. Laboratuvar bulguları ve diğer tetkikler: anahtar => [etiket, alt açıklama]. */
    public const LAB_GRUPLARI = [
        'biyolojik' => ['a) Biyolojik Analizler', '(Kan: Hemogram, AKŞ, ALT, AST, Kreatinin vb. / İdrar)'],
        'radyolojik' => ['b) Radyolojik Analizler', '(PA Akciğer Grafisi / ILO Pnömokonyoz Değerlendirmesi)'],
        'fizyolojik' => ['c) Fizyolojik Analizler', '(Odyometri - İşitme Testi / SFT - Solunum Fonksiyon Testi)'],
        'psikolojik' => ['d) Psikolojik Testler', ''],
        'diger' => ['e) Diğer Tetkikler', '(EKG, Göz Muayenesi, Portör / Seroloji, Ağır Metal vb.)'],
    ];

    public const KAN_GRUPLARI = ['A Rh(+)', 'A Rh(-)', 'B Rh(+)', 'B Rh(-)', 'AB Rh(+)', 'AB Rh(-)', '0 Rh(+)', '0 Rh(-)'];

    public const EGITIM_DURUMLARI = ['İlköğretim', 'Lise / Dengi', 'Ön Lisans', 'Lisans', 'Lisansüstü'];

    /** @return array<string, mixed> Boş EK-2 ek alanları (form ve PDF için tek kaynak). */
    public static function ek2Sablon(): array
    {
        return [
            'isyeri_tel' => null, 'isyeri_eposta' => null,
            'kan_grubu' => null, 'dogum_yeri' => null, 'cinsiyet' => null, 'egitim_durumu' => null,
            'medeni_durum' => null, 'cocuk_sayisi' => null, 'calisan_tel' => null, 'meslegi' => null,
            'ev_adresi' => null, 'calistigi_bolum' => null, 'yaptigi_is' => null,
            'onceki' => [
                ['isyeri' => null, 'is' => null, 'baslama' => null, 'ayrilma' => null],
                ['isyeri' => null, 'is' => null, 'baslama' => null, 'ayrilma' => null],
            ],
            'beyan_tarih' => null,
            'asi' => [
                'tetanoz' => ['durum' => null, 'not' => null],
                'hepatit' => ['durum' => null, 'not' => null],
                'diger' => ['durum' => null, 'not' => null],
            ],
            'soygecmis' => ['anne' => null, 'baba' => null, 'kardes' => null, 'cocuk' => null],
            'oz' => collect(self::OZGECMIS_SORULARI)->map(fn () => ['cevap' => null, 'aciklama' => null])->all(),
            'sigara' => ['durum' => 'Hayır', 'detay' => null],
            'alkol' => ['durum' => 'Hayır', 'detay' => null],
            'vital' => ['boy' => null, 'kilo' => null, 'kan_basinci' => null, 'nabiz' => null],
            'fizik' => collect(self::FIZIK_SISTEMLERI)->map(fn () => null)->all(),
            'lab' => collect(self::LAB_GRUPLARI)->map(fn () => ['tarih' => null, 'sonuc' => null])->all(),
            'kanaat' => ['gorev' => null, 'gece' => true, 'yuksekte' => false],
            'hekim' => ['diploma' => null, 'belge_no' => null],
        ];
    }

    /** Kayıtlı ek2 verisini şablonla birleştirir (eski kayıtta veya eksik anahtarda varsayılanlar korunur). */
    public static function ek2Birlestir(?array $kayitli): array
    {
        $sablon = self::ek2Sablon();

        foreach ($kayitli ?? [] as $anahtar => $deger) {
            if (! array_key_exists($anahtar, $sablon)) {
                continue;
            }

            // "onceki" gibi listeler olduğu gibi gelir; sözlükler anahtar anahtar birleşir.
            $sablon[$anahtar] = is_array($sablon[$anahtar]) && is_array($deger) && ! array_is_list($sablon[$anahtar])
                ? array_replace_recursive($sablon[$anahtar], $deger)
                : $deger;
        }

        return $sablon;
    }

    /**
     * Boy (cm) ve kilo (kg)'dan BKİ (1 ondalık) + sınıf; veri yoksa null.
     *
     * @return array{deger: float, sinif: string}|null
     */
    public static function bki(mixed $boyCm, mixed $kiloKg): ?array
    {
        $boy = (float) str_replace(',', '.', (string) $boyCm);
        $kilo = (float) str_replace(',', '.', (string) $kiloKg);

        if ($boy <= 50 || $kilo <= 20) {
            return null;
        }

        $deger = round($kilo / (($boy / 100) ** 2), 1);
        $sinif = match (true) {
            $deger < 18.5 => 'Zayıf',
            $deger < 25 => 'Normal',
            $deger < 30 => 'Fazla Kilolu',
            default => 'Obez (BKİ ≥ 30)',
        };

        return ['deger' => $deger, 'sinif' => $sinif];
    }

    protected static function booted(): void
    {
        static::saving(function (MuayeneFormu $m): void {
            $m->belge_no ??= 'MF-'.now()->format('Y').'-'.str_pad((string) (static::count() + 1), 3, '0', STR_PAD_LEFT);
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

    public function muayeneTuruEtiketi(): string
    {
        return config('isg.muayene.muayene_turleri.'.$this->muayene_turu, $this->muayene_turu ?? '—');
    }

    public function sonucKanaatiEtiketi(): string
    {
        return config('isg.muayene.sonuc_kanaatleri.'.$this->sonuc_kanaati, $this->sonuc_kanaati ?? '—');
    }
}
