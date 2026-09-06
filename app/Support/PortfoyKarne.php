<?php

namespace App\Support;

use App\Models\Calisan;
use App\Models\Firma;
use App\Models\YillikPlan;
use Illuminate\Support\Carbon;

/**
 * Kontrol Merkezi "Firma / Çalışan Asistanı" hesapları — isgpratik 135-136.jpg.
 * Portföy genelinde 12 yasal kriterin firma bazlı tamamlanma oranı ve çalışan
 * eksikleri. Modülü olmayan kriterler 0/N döner (`hazir=false`).
 */
class PortfoyKarne
{
    /** @return array<string, int|float> portföy özeti */
    public static function ozet(int $userId): array
    {
        $firmalar = Firma::query()->where('user_id', $userId)->where('aktif', true)->get();
        $toplam = $firmalar->count();

        $riskOlan = $firmalar->filter(fn (Firma $f) => $f->riskDegerlendirmeleri()->exists())->count();
        $tamUyumlu = $firmalar->filter(fn (Firma $f) => static::firmaTamUyumluMu($f))->count();

        $kriterler = static::kriterler($userId);
        $hazirKriterler = array_filter($kriterler, fn ($k) => $k['hazir']);
        $uyumYuzde = $hazirKriterler
            ? round(array_sum(array_column($hazirKriterler, 'yuzde')) / count($hazirKriterler))
            : 0;

        return [
            'firma' => $toplam,
            // Firmaya kayıtlı Çalışan (isim) sayısı değil, firmanın bildirdiği
            // toplam çalışan sayısı esas alınır (çalışan kaydı eksik olsa da doğru sayı görünsün).
            'calisan' => (int) $firmalar->sum('calisan_sayisi'),
            'risk_olan' => $riskOlan,
            'evrak_eksigi' => $toplam - $riskOlan,
            'tam_uyumlu' => $tamUyumlu,
            'uyum_yuzde' => $uyumYuzde,
        ];
    }

    /**
     * 12 kriterin portföydeki tamamlanma oranı.
     *
     * @return array<int, array{anahtar:string, ad:string, ikon:string, tamam:int, toplam:int, yuzde:int, hazir:bool}>
     */
    public static function kriterler(int $userId): array
    {
        $firmalar = Firma::query()->where('user_id', $userId)->where('aktif', true)->get();
        $toplam = $firmalar->count();

        return array_map(function (array $kriter) use ($firmalar, $toplam): array {
            $kapsam = ($kriter['kosul'] ?? null) === 'elli_calisan'
                ? $firmalar->filter(fn (Firma $f) => (int) $f->calisan_sayisi >= 50)
                : $firmalar;

            $kapsamSayi = $kapsam->count();

            $tamam = ($kriter['hazir'] && $kapsamSayi > 0)
                ? $kapsam->filter(fn (Firma $f) => static::firmaKriterKarsilarMi($f, $kriter['anahtar']))->count()
                : 0;

            return [
                'anahtar' => $kriter['anahtar'],
                'ad' => $kriter['ad'],
                'ikon' => $kriter['ikon'],
                'tamam' => $tamam,
                'toplam' => $kapsamSayi ?: $toplam,
                'yuzde' => $kapsamSayi > 0 ? (int) round($tamam / $kapsamSayi * 100) : 0,
                'hazir' => (bool) $kriter['hazir'],
            ];
        }, config('isg.kontrol_merkezi.kriterler', []));
    }

    public static function firmaKriterKarsilarMi(Firma $firma, string $anahtar): bool
    {
        return static::gercekModulVarMi($firma, $anahtar) === true;
    }

    /**
     * Kriter anahtarına karşılık gelen gerçek mehse modülünü kontrol eder — hem
     * `firmaKriterKarsilarMi()` hem `firmaKriterMatrisi()` bunu kullanır, böylece
     * "gerçek modül" tanımı tek yerde kalır. Modülü henüz kurulmamış (veya kasıtlı
     * olarak hep manuel kalan — ör. 'diger_evrak') kriterler için null döner;
     * çağıran o zaman manuel Evrak Kaydı'na bakar.
     */
    private static function gercekModulVarMi(Firma $firma, string $anahtar): ?bool
    {
        return match ($anahtar) {
            'risk_degerlendirmesi' => $firma->riskDegerlendirmeleri()->exists(),
            // AcilDurumPlani::firmaIcin() ilk ziyarette otomatik bir taslak kaydı açar
            // (rapor_tarihi atanır) — bu yüzden salt "kayıt var" yeterli kanıt değil,
            // konular (acil durum sayfaları) gerçekten seçilmiş olmalı.
            'acil_durum_plani' => filled($firma->acilDurumPlani?->konular),
            'egitim_katilim_formu' => $firma->egitimKatilimlari()->exists(),
            'acil_durum_tatbikat' => $firma->tatbikatTutanaklari()->exists(),
            'isg_kurulu' => $firma->kurulToplantilari()->exists(),
            'calisma_izin_formu' => $firma->isIzinFormlari()->exists(),
            'saha_denetim_formu' => $firma->sahaDenetimleri()->exists(),
            'is_kazasi_bildirimi' => $firma->isKazasiRaporlari()->exists(),
            'saglik_raporu' => $firma->muayeneFormlari()->exists(),
            // Doğrudan atama (Atama Yazıları) veya seçim süreci sonucu (Çalışan
            // Temsilcisi Seçimi) — hangisi kullanılmışsa geçerli sayılır.
            'calisan_temsilcisi' => $firma->atamaYazilari()->where('rol_anahtari', 'calisan_temsilcisi')->exists()
                || $firma->calisanTemsilcisiSecimi?->secilen_aday_index !== null,
            'igu_atamasi' => $firma->igu_id !== null,
            'hekim_atamasi' => $firma->isyeri_hekimi_id !== null,
            'tespit_oneri' => filled($firma->tespitOneriDefteri?->maddeler),
            'yillik_calisma_plani' => static::yillikPlanAyMatrisiDoluMu($firma, 'faaliyetler'),
            'yillik_egitim_plani' => static::yillikPlanAyMatrisiDoluMu($firma, 'egitimler'),
            'yillik_degerlendirme' => static::yillikPlanDegerlendirmeDoluMu($firma),
            default => null,
        };
    }

    /**
     * Yıllık Çalışma/Eğitim Planı'nda ($alan: 'faaliyetler'/'egitimler') en az bir
     * ay 'bos' dışında işaretlenmiş mi (herhangi bir yıl) — kayıt sayfa ilk
     * açıldığında boş şablonla otomatik oluştuğu için salt "kayıt var" yeterli
     * kanıt değil, gerçekten işaretlenmiş olması aranır.
     */
    private static function yillikPlanAyMatrisiDoluMu(Firma $firma, string $alan): bool
    {
        return $firma->yillikPlanlar->contains(
            fn (YillikPlan $p) => collect($p->{$alan} ?? [])
                ->contains(fn (array $madde) => collect($madde['aylar'] ?? [])->contains(fn ($durum) => $durum !== 'bos'))
        );
    }

    /** Yıllık Değerlendirme Raporu'nda en az bir maddeye tarih girilmiş mi (herhangi bir yıl). */
    private static function yillikPlanDegerlendirmeDoluMu(Firma $firma): bool
    {
        return $firma->yillikPlanlar->contains(
            fn (YillikPlan $p) => collect($p->degerlendirmeler ?? [])->contains(fn (array $d) => filled($d['tarih'] ?? null))
        );
    }

    /**
     * Firma Takip'in sütun listesi: config'teki sabit kriterler.
     *
     * @return array<int, array{anahtar:string, ad:string, ikon?:string, hazir:bool, kosul?:string}>
     */
    public static function firmaTakipKriterleri(int $userId): array
    {
        return config('isg.kontrol_merkezi.kriterler', []);
    }

    /**
     * Profilim "OSGB / Firma Takip" — firma × kriter matrisi (isgpratik 141-142).
     *
     * @return array<int, array{firma: Firma, hucreler: array<string, bool>, oran: int}>
     */
    public static function firmaKriterMatrisi(int $userId): array
    {
        $kriterler = static::firmaTakipKriterleri($userId);

        return Firma::query()
            ->where('user_id', $userId)
            ->where('aktif', true)
            ->orderBy('unvan')
            ->get()
            ->map(function (Firma $firma) use ($kriterler): array {
                $hucreler = [];
                $karsilanan = 0;
                $hazirSayi = 0;

                foreach ($kriterler as $k) {
                    // 'kosul' => 'elli_calisan' kriterler (şu an yalnız isg_kurulu) 50 altı
                    // çalışanlı firmalarda muaf — kriterler()/ozet() bu firmaları zaten
                    // kapsam dışı bırakıyordu (bkz. 'kapsam' filtresi), burada da aynı
                    // muafiyet uygulanmazsa küçük firmalar haksız yere düşük "Oran" alır.
                    $muaf = ($k['kosul'] ?? null) === 'elli_calisan' && (int) $firma->calisan_sayisi < 50;

                    $var = $muaf || static::gercekModulVarMi($firma, $k['anahtar']) === true;

                    $hucreler[$k['anahtar']] = $var;

                    if ($k['hazir'] && ! $muaf) {
                        $hazirSayi++;
                        $karsilanan += $var ? 1 : 0;
                    }
                }

                return [
                    'firma' => $firma,
                    'hucreler' => $hucreler,
                    'oran' => $hazirSayi > 0 ? (int) round($karsilanan / $hazirSayi * 100) : 0,
                ];
            })
            ->all();
    }

    /**
     * Genel Bakış — Çalışan Dağılımı (firma başına aktif çalışan). isgpratik 6.jpg.
     *
     * @return array<string, int>
     */
    /**
     * Portföy genelinde yasal ilkyardımcı ihtiyacı — İlkyardım Yönetmeliği md.19
     * (çok tehlikeli: her 10 çalışana 1, tehlikeli: 15'e 1, az tehlikeli: 20'ye 1).
     * Firma.calisan_sayisi (bildirilen toplam) esas alınır — Calisan (isim) kaydı
     * eksik olsa da doğru sayı çıksın diye (bkz. ozet()'teki aynı gerekçe).
     */
    public static function ilkyardimciIhtiyaci(int $userId): int
    {
        $oran = ['cok_tehlikeli' => 10, 'tehlikeli' => 15, 'az_tehlikeli' => 20];

        return Firma::query()
            ->where('user_id', $userId)
            ->where('aktif', true)
            ->get()
            ->sum(fn (Firma $f) => (int) ceil(max(0, (int) $f->calisan_sayisi) / ($oran[$f->tehlike_sinifi] ?? 20)));
    }

    /**
     * "Performans Profili" — 5 eksenli portföy sağlık özeti (isgpratik profil
     * ekranı). Yalnız GERÇEK modülü olan (hazır=true) kriterlerin ortalaması
     * alınır — henüz kurulmamış (hazır=false) kriterler eksende 0'a çekip
     * yanıltıcı olmasın diye dahil edilmez.
     *
     * @return array<string, int> eksen adı => yüzde (0-100)
     */
    public static function performansEksenleri(int $userId): array
    {
        $kriterler = collect(static::kriterler($userId))->keyBy('anahtar');

        $ortalama = fn (array $anahtarlar): int => (int) round(
            collect($anahtarlar)
                ->map(fn (string $a) => $kriterler[$a]['yuzde'] ?? null)
                ->filter(fn (?int $v) => $v !== null)
                ->avg() ?? 0
        );

        $toplamFirma = Firma::query()->where('user_id', $userId)->where('aktif', true)->count();
        $calisansizFirma = Firma::query()
            ->where('user_id', $userId)->where('aktif', true)
            ->whereDoesntHave('calisanlar', fn ($q) => $q->where('aktif', true))
            ->count();
        $calisanKapsami = $toplamFirma > 0 ? (int) round((($toplamFirma - $calisansizFirma) / $toplamFirma) * 100) : 0;

        return [
            'Evrak Uyumu' => $ortalama(['yillik_calisma_plani', 'yillik_egitim_plani', 'yillik_degerlendirme', 'tespit_oneri']),
            'Çalışan Kapsamı' => $calisanKapsami,
            'Eğitim Durumu' => $ortalama(['egitim_katilim_formu']),
            'Risk Yönetimi' => $ortalama(['risk_degerlendirmesi', 'saha_denetim_formu']),
            'Acil Durum' => $ortalama(['acil_durum_tatbikat']),
        ];
    }

    /**
     * Bir kriteri henüz karşılamayan aktif firmalar — form/belge sayfalarında
     * "Eksik Olan Firmalar" hızlı erişim listesi için (kullanıcı tek tek firma
     * açıp kontrol etmek zorunda kalmasın).
     *
     * @return \Illuminate\Support\Collection<int, Firma>
     */
    public static function eksikFirmalar(int $userId, string $kriterAnahtari): \Illuminate\Support\Collection
    {
        $kriter = collect(config('isg.kontrol_merkezi.kriterler'))->firstWhere('anahtar', $kriterAnahtari);

        $firmalar = Firma::query()->where('user_id', $userId)->where('aktif', true)->orderBy('unvan')->get();

        if (($kriter['kosul'] ?? null) === 'elli_calisan') {
            $firmalar = $firmalar->filter(fn (Firma $f) => (int) $f->calisan_sayisi >= 50)->values();
        }

        return $firmalar->reject(fn (Firma $f) => static::firmaKriterKarsilarMi($f, $kriterAnahtari))->values();
    }

    public static function calisanDagilimi(int $userId): array
    {
        return Firma::query()
            ->where('user_id', $userId)
            ->where('aktif', true)
            ->withCount(['calisanlar as aktif_calisan' => fn ($q) => $q->where('aktif', true)])
            ->orderByDesc('aktif_calisan')
            ->orderBy('unvan')
            ->get()
            ->mapWithKeys(fn (Firma $f) => [$f->unvan => (int) $f->aktif_calisan])
            ->all();
    }

    /**
     * Genel Bakış — günlük aktivite (son N gün). isgpratik 6.jpg: trend + heatmap.
     * Risk değerlendirmesi / madde / şablon / firma / çalışan eklemeleri sayılır.
     *
     * @return array<string, int>  'Y-m-d' => adet   (bugüne kadar, sıralı)
     */
    public static function aktiviteGunluk(int $userId, int $gun = 90): array
    {
        $baslangic = now()->subDays($gun - 1)->startOfDay();

        $tarihler = [];
        for ($i = 0; $i < $gun; $i++) {
            $tarihler[$baslangic->copy()->addDays($i)->toDateString()] = 0;
        }

        $ekle = function (\Illuminate\Support\Collection $tarihKolonu) use (&$tarihler): void {
            foreach ($tarihKolonu as $tarih) {
                $g = \Illuminate\Support\Carbon::parse($tarih)->toDateString();
                if (array_key_exists($g, $tarihler)) {
                    $tarihler[$g]++;
                }
            }
        };

        $ekle(\App\Models\RiskDegerlendirmesi::query()
            ->whereHas('firma', fn ($q) => $q->where('user_id', $userId))
            ->where('created_at', '>=', $baslangic)->pluck('created_at'));

        $ekle(\App\Models\RiskMaddesi::query()
            ->whereHas('riskDegerlendirmesi.firma', fn ($q) => $q->where('user_id', $userId))
            ->where('created_at', '>=', $baslangic)->pluck('created_at'));

        $ekle(\App\Models\RiskSablonu::query()->where('user_id', $userId)
            ->where('created_at', '>=', $baslangic)->pluck('created_at'));

        $ekle(Firma::query()->where('user_id', $userId)
            ->where('created_at', '>=', $baslangic)->pluck('created_at'));

        $ekle(Calisan::query()->whereHas('firma', fn ($q) => $q->where('user_id', $userId))
            ->where('created_at', '>=', $baslangic)->pluck('created_at'));

        return $tarihler;
    }

    /** Profilim başlık kartları + Genel Bakış (isgpratik 5.jpg). */
    public static function profilOzeti(int $userId): array
    {
        $firmalar = Firma::query()->where('user_id', $userId)->where('aktif', true)->get();

        $tehlikeDagilimi = collect(config('isg.tehlike_siniflari'))
            ->mapWithKeys(fn ($ad, $anahtar) => [$ad => $firmalar->where('tehlike_sinifi', $anahtar)->count()])
            ->all();

        $onemliEsik = (int) config('isg.onemli_risk_esigi', 140);
        $onemliRisk = \App\Models\RiskMaddesi::query()
            ->whereHas('riskDegerlendirmesi.firma', fn ($q) => $q->where('user_id', $userId))
            ->where('puan', '>', $onemliEsik)
            ->count();

        $calisansizFirma = Firma::query()
            ->where('user_id', $userId)
            ->where('aktif', true)
            ->whereDoesntHave('calisanlar', fn ($q) => $q->where('aktif', true))
            ->count();

        $ozet = static::ozet($userId);

        return [
            'firma' => $firmalar->count(),
            // Firmaya kayıtlı Çalışan (isim) sayısı değil, firmanın bildirdiği
            // toplam çalışan sayısı esas alınır.
            'calisan' => (int) $firmalar->sum('calisan_sayisi'),
            'risk_degerlendirmesi' => \App\Models\RiskDegerlendirmesi::query()
                ->whereHas('firma', fn ($q) => $q->where('user_id', $userId))->count(),
            'risk_sablonu' => \App\Models\RiskSablonu::query()->where('user_id', $userId)->count(),
            'onemli_risk' => $onemliRisk,
            'calisansiz_firma' => $calisansizFirma,
            'evrak_eksigi' => $ozet['evrak_eksigi'],
            'tehlike_dagilimi' => $tehlikeDagilimi,
            'uyum_yuzde' => $ozet['uyum_yuzde'],
        ];
    }

    private static function firmaTamUyumluMu(Firma $firma): bool
    {
        foreach (config('isg.kontrol_merkezi.kriterler', []) as $kriter) {
            if (! $kriter['hazir']) {
                continue;
            }

            // 'kosul' => 'elli_calisan' kriterler (şu an yalnız isg_kurulu) 50 altı
            // çalışanlı firmalarda muaf — aksi halde küçük firmalar bu şartı hiç
            // karşılayamayacağı için asla "tam uyumlu" sayılmaz.
            $muaf = ($kriter['kosul'] ?? null) === 'elli_calisan' && (int) $firma->calisan_sayisi < 50;
            if ($muaf) {
                continue;
            }

            if (! static::firmaKriterKarsilarMi($firma, $kriter['anahtar'])) {
                return false;
            }
        }

        return true;
    }

    /** Tek firma için çalışan eksikleri (Çalışan Asistanı sekmesi). */
    public static function calisanKarne(Firma $firma): array
    {
        $calisanlar = $firma->calisanlar()->where('aktif', true)->get();

        $genc = $calisanlar->filter(function (Calisan $c): bool {
            return $c->dogum_tarihi
                && Carbon::parse($c->dogum_tarihi)->age < 18;
        });

        return [
            'toplam' => $calisanlar->count(),
            'genc' => $genc->values(),
            'agir_tehlikeli' => $calisanlar->where('agir_tehlikeli_iste', true)->count(),
            // Muayene / eğitim / MYK takibi ilgili modüller kurulunca eklenecek
            'moduller_bekliyor' => ['Periyodik muayene', 'İSG eğitimi', 'MYK belgesi'],
        ];
    }
}
