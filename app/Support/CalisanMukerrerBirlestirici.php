<?php

namespace App\Support;

use App\Models\AtamaYazisi;
use App\Models\Calisan;
use App\Models\Firma;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Bir firmadaki mükerrer çalışan kayıtlarını birleştirir (aynı kişi iki kez açılmış: ör. T.C.'siz eski kayıt +
 * Excel'den gelen T.C.'li kayıt).
 *
 * Kurallar:
 *  - Aynı firmada, ad soyadı (büyük/küçük harf, Türkçe İ/I ve boşluk farkı yok sayılarak) aynı olanlar aday gruptur.
 *  - Gruptaki kayıtların T.C.'leri farklıysa (ikiden fazla farklı dolu T.C.) AYRI KİŞİLER olabilir → birleştirilmez, bildirilir.
 *  - En ESKİ kayıt tutulur (eğitim, muayene, KKD… geçmişi çoğunlukla onda); diğerlerine bağlı TÜM kayıtlar
 *    (calisan_id taşıyan bütün tablolar + atama yazılarındaki üye bağlantıları) ona taşınır, sonra mükerrerler silinir.
 *  - Alanlar: her alan için EN YENİ dolu değer alınır (son yükleme/kayıt kullanıcının en güncel verisi); hiçbiri doluysa
 *    tutulan kaydın değeri kalır. "Aktif": gruptan biri aktifse aktif. LMS şifresi tutulan kayıttan korunur.
 *  - Aynı anahtarlı kayıtlar çakışırsa (aynı eğitim türü, aynı paket, aynı ekip) tek kayıt kalır: eğitim kaydında tarihi
 *    daha yeni olan, eğitim atamasında tamamlanmış olan, diğerlerinde tutulan kaydınki.
 */
class CalisanMukerrerBirlestirici
{
    /** Birleştirmede "en yeni dolu değer" kuralıyla taşınan alanlar. */
    private const ALANLAR = ['tc', 'gorev', 'departman', 'sube', 'cinsiyet', 'ozel_durum', 'ise_giris', 'isten_cikis', 'dogum_tarihi', 'kan_grubu', 'telefon', 'eposta', 'agir_tehlikeli_iste', 'notlar'];

    /** Aynı çalışana ait tek olması gereken kayıtlar: tablo => ek anahtar sütunlar (calisan_id ile birlikte). */
    private const TEKIL = [
        'egitim_kayitlari' => ['tur'],
        'egitim_atamalari' => ['egitim_paketi_id'],
        'acil_ekip_uyeleri' => ['acil_ekip_id'],
    ];

    /**
     * @return array{birlesecek: array<int, array{tutulan: Calisan, kayitlar: Collection<int, Calisan>}>, atlanan: array<int, array{ad: string, neden: string}>}
     */
    public static function gruplar(Firma $firma): array
    {
        $birlesecek = [];
        $atlanan = [];

        $firma->calisanlar()->orderBy('id')->get()
            ->groupBy(fn (Calisan $c) => EgitimTakibi::adAnahtari($c->ad_soyad))
            ->filter(fn (Collection $g, string $ad) => $ad !== '' && $g->count() > 1)
            ->each(function (Collection $g) use (&$birlesecek, &$atlanan): void {
                $tcler = $g->map(fn (Calisan $c) => EgitimTakibi::tcAnahtari($c->tc))->filter()->unique();

                if ($tcler->count() > 1) {
                    $atlanan[] = ['ad' => $g->first()->ad_soyad, 'neden' => 'Farklı T.C.\'ler ('.$tcler->implode(', ').') — ayrı kişiler olabilir'];

                    return;
                }

                $birlesecek[] = ['tutulan' => $g->first(), 'kayitlar' => $g->values()];
            });

        return ['birlesecek' => $birlesecek, 'atlanan' => $atlanan];
    }

    /**
     * Önizleme (veritabanına yazmaz).
     *
     * @return array{grup: int, silinecek: int, tasinacak: int, cakisan: int, satirlar: array<int, array<string, mixed>>, atlanan: array<int, array{ad: string, neden: string}>}
     */
    public static function onizle(Firma $firma): array
    {
        $g = self::gruplar($firma);
        $satirlar = [];
        $tasinacak = 0;
        $cakisan = 0;

        foreach ($g['birlesecek'] as $grup) {
            $tutulan = $grup['tutulan'];
            $digerleri = $grup['kayitlar']->slice(1)->values();
            $veri = self::birlesmisVeri($grup['kayitlar']);
            $degisen = [];

            foreach ($veri as $alan => $yeni) {
                $eski = $tutulan->{$alan};
                $eskiMetin = $eski instanceof \DateTimeInterface ? $eski->format('d.m.Y') : (is_bool($eski) ? ($eski ? 'Evet' : 'Hayır') : (string) $eski);
                $yeniMetin = $yeni instanceof \DateTimeInterface ? $yeni->format('d.m.Y') : (is_bool($yeni) ? ($yeni ? 'Evet' : 'Hayır') : (string) $yeni);

                if ($eskiMetin !== $yeniMetin && $yeniMetin !== '') {
                    $degisen[$alan] = [$eskiMetin, $yeniMetin];
                }
            }

            [$t, $c] = self::baglantiSayilari($tutulan->id, $digerleri->pluck('id')->all());
            $tasinacak += $t;
            $cakisan += $c;

            $satirlar[] = [
                'ad' => $tutulan->ad_soyad,
                'tutulan' => $tutulan->id,
                'silinecek' => $digerleri->pluck('id')->all(),
                'degisen' => $degisen,
                'tasinacak' => $t,
                'cakisan' => $c,
            ];
        }

        return [
            'grup' => count($satirlar),
            'silinecek' => array_sum(array_map(fn ($s) => count($s['silinecek']), $satirlar)),
            'tasinacak' => $tasinacak,
            'cakisan' => $cakisan,
            'satirlar' => $satirlar,
            'atlanan' => $g['atlanan'],
        ];
    }

    /**
     * Birleştirir.
     *
     * @return array{grup: int, silinen: int, tasinan: int, cakisan: int}
     */
    public static function birlestir(Firma $firma): array
    {
        $sonuc = ['grup' => 0, 'silinen' => 0, 'tasinan' => 0, 'cakisan' => 0];

        foreach (self::gruplar($firma)['birlesecek'] as $grup) {
            DB::transaction(function () use ($grup, $firma, &$sonuc): void {
                $tutulan = $grup['tutulan'];
                $digerleri = $grup['kayitlar']->slice(1)->values();
                $veri = self::birlesmisVeri($grup['kayitlar']);

                foreach ($digerleri as $mukerrer) {
                    [$tasinan, $cakisan] = self::baglantilariTasi($mukerrer->id, $tutulan->id);
                    self::atamaYazilariniGuncelle($firma, $mukerrer->id, $tutulan->id);
                    $sonuc['tasinan'] += $tasinan;
                    $sonuc['cakisan'] += $cakisan;
                }

                // Önce mükerrerler silinir (ör. e-posta/T.C. çakışması olmasın), sonra tutulan kayıt güncellenir.
                Calisan::query()->whereIn('id', $digerleri->pluck('id'))->delete();
                $tutulan->update($veri);

                $sonuc['grup']++;
                $sonuc['silinen'] += $digerleri->count();
            });
        }

        return $sonuc;
    }

    /**
     * En yeni dolu değer kuralıyla birleşmiş alanlar (+ aktif: biri aktifse aktif).
     *
     * @param  Collection<int, Calisan>  $kayitlar  eskiden yeniye
     * @return array<string, mixed>
     */
    private static function birlesmisVeri(Collection $kayitlar): array
    {
        $yenidenEskiye = $kayitlar->sortByDesc('id')->values();
        $tutulan = $kayitlar->first();
        $veri = [];

        foreach (self::ALANLAR as $alan) {
            if ($alan === 'agir_tehlikeli_iste') {   // evet/hayır: biri evet ise evet (false "boş" değil, yenisi eskisini ezmesin)
                $veri[$alan] = $kayitlar->contains(fn (Calisan $c) => (bool) $c->{$alan});

                continue;
            }

            $deger = $yenidenEskiye->map(fn (Calisan $c) => $c->{$alan})->first(fn ($d) => $d !== null && $d !== '');
            $veri[$alan] = $deger ?? $tutulan->{$alan};
        }

        $veri['aktif'] = $kayitlar->contains(fn (Calisan $c) => (bool) $c->aktif);

        return $veri;
    }

    /** @return array<int, string> calisan_id sütunu taşıyan tablolar (calisanlar hariç) */
    private static function tablolar(): array
    {
        static $liste = null;

        return $liste ??= collect(Schema::getTables())
            ->pluck('name')
            ->filter(fn (string $t) => $t !== 'calisanlar' && Schema::hasColumn($t, 'calisan_id'))
            ->values()
            ->all();
    }

    /** @return array{0: int, 1: int} [taşınacak, çakışıp tek kalacak] */
    private static function baglantiSayilari(int $tutulan, array $mukerrerIdler): array
    {
        $tasinacak = 0;
        $cakisan = 0;

        foreach (self::tablolar() as $tablo) {
            foreach (DB::table($tablo)->whereIn('calisan_id', $mukerrerIdler)->get() as $satir) {
                self::cakisanVar($tablo, $tutulan, $satir) ? $cakisan++ : $tasinacak++;
            }
        }

        return [$tasinacak, $cakisan];
    }

    /** Tutulan kayıtta aynı anahtarlı satır var mı (tablo TEKIL listesindeyse)? */
    private static function cakisanVar(string $tablo, int $tutulan, object $satir): ?object
    {
        $anahtar = self::TEKIL[$tablo] ?? null;

        if (! $anahtar) {
            return null;
        }

        $q = DB::table($tablo)->where('calisan_id', $tutulan);
        foreach ($anahtar as $sutun) {
            $q->where($sutun, $satir->{$sutun});
        }

        return $q->first();
    }

    /** @return array{0: int, 1: int} [taşınan, çakışıp elenen] */
    private static function baglantilariTasi(int $mukerrer, int $tutulan): array
    {
        $tasinan = 0;
        $cakisan = 0;

        foreach (self::tablolar() as $tablo) {
            $idVar = Schema::hasColumn($tablo, 'id');

            foreach (DB::table($tablo)->where('calisan_id', $mukerrer)->get() as $satir) {
                $var = $idVar ? self::cakisanVar($tablo, $tutulan, $satir) : null;

                if ($var) {
                    // Çakışma: tek kayıt kalır — hangisi kalacağı tabloya göre.
                    $yeniKazanir = match ($tablo) {
                        'egitim_kayitlari' => ($satir->tarih ?? '') > ($var->tarih ?? ''),
                        'egitim_atamalari' => ($satir->durum ?? '') === 'tamamlandi' && ($var->durum ?? '') !== 'tamamlandi',
                        default => false,
                    };

                    if ($yeniKazanir) {
                        DB::table($tablo)->where('id', $var->id)->delete();
                        DB::table($tablo)->where('id', $satir->id)->update(['calisan_id' => $tutulan]);
                        $tasinan++;
                    } else {
                        DB::table($tablo)->where('id', $satir->id)->delete();
                    }

                    $cakisan++;

                    continue;
                }

                if ($idVar) {
                    DB::table($tablo)->where('id', $satir->id)->update(['calisan_id' => $tutulan]);
                } else {
                    DB::table($tablo)->where('calisan_id', $mukerrer)->update(['calisan_id' => $tutulan]);
                }

                $tasinan++;
            }
        }

        return [$tasinan, $cakisan];
    }

    /** Atama yazılarındaki üye listesinde (JSON) mükerrer kaydı gösteren calisan_id değerlerini tutulana çevirir. */
    private static function atamaYazilariniGuncelle(Firma $firma, int $mukerrer, int $tutulan): void
    {
        AtamaYazisi::query()->where('firma_id', $firma->id)->get()->each(function (AtamaYazisi $a) use ($mukerrer, $tutulan): void {
            $degisti = false;
            $uyeler = collect($a->uyeler)->map(function ($u) use ($mukerrer, $tutulan, &$degisti) {
                if (is_array($u) && (int) ($u['calisan_id'] ?? 0) === $mukerrer) {
                    $u['calisan_id'] = $tutulan;
                    $degisti = true;
                }

                return $u;
            })->all();

            if ($degisti) {
                // Model olayları (PDF önbelleği temizleme) tetiklenmesin diye sessiz kayıt.
                $a->uyeler = $uyeler;
                $a->saveQuietly();
            }
        });
    }
}
