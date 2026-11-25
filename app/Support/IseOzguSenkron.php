<?php

namespace App\Support;

use App\Models\EgitimKatilim;
use App\Models\Firma;
use App\Models\User;
use App\Models\YillikPlan;
use Illuminate\Support\Collection;

/**
 * Yıllık Eğitim Planı'nın 4. bölümü ("İşe ve İşyerine Özgü Riskler") ile Eğitim Katılım formunun işyerine özgü
 * bölümünü tek doğruluk kaynağı olarak tutar:
 *  - formdanPlana: formda kaydedilen işe özgü konular, o firmanın o yılki planının 4. bölümüne yazılır
 *    (plan henüz yoksa otomatik açılır; iş kolu/ekip formları hedef_kitle etiketiyle eklenir).
 *  - firmaGrubuOku / firmaGrubuKaydet: NACE'si gerçek işiyle tutmayan firmalar için elle "Gerçek İş Grubu" seçimi.
 *  - dorduncuBolumuGrubaEsitle: aynı NACE / aynı Gerçek İş Grubundaki tüm firmaların 4. bölümünü tek tıkla eşitler.
 *  - naceOrtakSatirlar: kütüphaneden eşleşme çıkmayan yeni planlarda aynı NACE'deki diğer firmaların 4. bölümünü getirir.
 */
class IseOzguSenkron
{
    public static function formdanPlana(EgitimKatilim $kayit): bool
    {
        $icerik = $kayit->konu_secimleri ?? [];
        $firma = $kayit->firma;

        if ($kayit->baslik_anahtari !== 'genel' || ($icerik['tip'] ?? 'genel') !== 'genel' || ! $firma || ! $kayit->belge_tarihi) {
            return false;
        }

        // Yalnız online (uzaktan) oturumda Tehlikeli/Çok Tehlikeli 4. bölüm hariçtir — planın 4. bölümünü ezme.
        if ($kayit->egitim_sekli === 'uzaktan' && YillikEgitimPlanlayici::zorunluYuzYuzeMi(['kategori' => 'ise_ozgu'], $firma)) {
            return false;
        }

        $konular = collect($icerik['isyerine_ozgu']['maddeler'] ?? [])
            ->filter(fn ($m) => ($m['dahil'] ?? true) && filled(trim((string) ($m['madde'] ?? ''))))
            ->map(fn ($m) => trim((string) $m['madde']))
            ->unique(fn (string $k) => mb_strtolower($k))
            ->values();

        // Formda hiç işe özgü konu yoksa planı boşaltma — kullanıcı büyük olasılıkla bu bölümü doldurmadı.
        if ($konular->isEmpty()) {
            return false;
        }

        $yil = (int) $kayit->belge_tarihi->year;
        $plan = YillikPlan::firmaYilIcin($firma, $yil);

        $isKolu = filled($kayit->is_kolu) ? trim((string) $kayit->is_kolu) : null;
        $yeniGelenAnahtarlar = $konular->map(fn (string $k) => mb_strtolower($k))->flip();

        // Çalışan grubu / iş kolu formu (ör. Kalıp Ekibi / Sıva Ekibi): plan firma geneli olduğundan bu grubun
        // konuları plana EKLENİR, diğer grupların konuları korunur.
        if ($isKolu !== null) {
            $konular = collect($plan->egitimler ?? [])->where('kategori', 'ise_ozgu')->pluck('konu')
                ->map(fn ($k) => trim((string) $k))->filter()->merge($konular)
                ->unique(fn (string $k) => mb_strtolower($k))->values();
        }

        $kilit = $firma->planKilitAyIndeksi($yil);
        $mevcutlar = collect($plan->egitimler ?? [])->where('kategori', 'ise_ozgu')
            ->keyBy(fn ($e) => mb_strtolower(trim((string) ($e['konu'] ?? ''))));
        $tumu = IseOzguEgitimKutuphanesi::tumu($firma->user_id);
        $kutuphane = $tumu->mapWithKeys(fn ($k, $a) => [mb_strtolower(trim($k['ad'])) => $a]);

        $satirlar = $konular->map(function (string $konu) use ($mevcutlar, $kutuphane, $tumu, $kilit, $isKolu, $yeniGelenAnahtarlar): array {
            $anahtar = mb_strtolower($konu);
            $buGruptanMi = $isKolu !== null && $yeniGelenAnahtarlar->has($anahtar);

            if ($mevcutlar->has($anahtar)) {
                $satir = $mevcutlar[$anahtar];
                if ($buGruptanMi && blank($satir['hedef_kitle'] ?? null)) {
                    $satir['hedef_kitle'] = $isKolu;
                }

                return $satir;
            }

            if ($kutuphane->has($anahtar)) {
                $satir = IseOzguEgitimKutuphanesi::planSatiri($tumu[$kutuphane[$anahtar]], $kilit);
                if ($buGruptanMi) {
                    $satir['hedef_kitle'] = $isKolu;
                }

                return $satir;
            }

            return YillikPlan::maddeAylarIle([[
                'konu' => $konu,
                'kategori' => 'ise_ozgu',
                'hedef' => $buGruptanMi ? $isKolu.' çalışanlarına özgü saha riskleri ve korunma önlemleri.' : '',
                'hedef_kitle' => $buGruptanMi ? $isKolu : null,
                'egitici' => IseOzguEgitimKutuphanesi::VARSAYILAN_EGITICI,
                'hafta' => 2,
                'varsayilan_aylar' => range(0, 11),
            ]], $kilit)[0];
        })->all();

        $aktifMod = YillikEgitimPlanlayici::moduTespitEt($plan->egitimler ?? [], $firma);
        $yeni = YillikPlanSablonu::iseOzguYerlestir($plan->egitimler ?? [], $satirlar);
        $yeni = YillikEgitimPlanlayici::satirlaraUygula($yeni, $firma, $aktifMod);

        if ($yeni === ($plan->egitimler ?? [])) {
            return false;
        }

        $plan->update(['egitimler' => $yeni]);

        return true;
    }

    /** NACE'si gerçek işiyle tutmayan firma için elle kaydedilmiş eğitim sektörü (yoksa null). */
    public static function firmaGrubuOku(?Firma $firma): ?string
    {
        if (! $firma || ! $firma->user_id) {
            return null;
        }

        $user = User::find($firma->user_id);
        $gruplar = (array) (($user?->ayarlar ?? [])['firma_egitim_gruplari'] ?? []);
        $deger = $gruplar[(string) $firma->id] ?? $gruplar[$firma->id] ?? null;

        return filled($deger) ? (string) $deger : null;
    }

    /** Firma için elle gerçek iş grubu (sektör anahtarı) kaydeder; null verilirse NACE otomatiğine döner. */
    public static function firmaGrubuKaydet(Firma $firma, ?string $sektorAnahtari): void
    {
        $user = User::find($firma->user_id);
        if (! $user) {
            return;
        }

        $gruplar = (array) (($user->ayarlar ?? [])['firma_egitim_gruplari'] ?? []);
        if (filled($sektorAnahtari)) {
            $gruplar[(string) $firma->id] = (string) $sektorAnahtari;
        } else {
            unset($gruplar[(string) $firma->id], $gruplar[$firma->id]);
        }

        KullaniciAyarlari::kaydet($user, ['firma_egitim_gruplari' => $gruplar]);
    }

    /**
     * Bu firmaya NACE/sektör şablonu dışında firmaya özel konular mı tanımlandı?
     * true ise: bu firmanın 4. bölümü yalnız bu firmada kalır; aynı NACE'deki diğer firmalara
     * (ör. diğer inşaat şablonlarına) taşınmaz ve grup eşitlemede de ezilmez.
     */
    public static function firmaOzelMi(?Firma $firma): bool
    {
        if (! $firma || ! $firma->user_id) {
            return false;
        }

        $user = User::find($firma->user_id);
        $ozeller = (array) (($user?->ayarlar ?? [])['firma_egitim_ozel'] ?? []);

        return (bool) ($ozeller[(string) $firma->id] ?? $ozeller[$firma->id] ?? false);
    }

    /** Firmayı "yalnız bu firmaya özel 4. bölüm" olarak işaretler veya genel gruba geri açar. */
    public static function firmaOzelKaydet(Firma $firma, bool $ozel): void
    {
        $user = User::find($firma->user_id);
        if (! $user) {
            return;
        }

        $ozeller = (array) (($user->ayarlar ?? [])['firma_egitim_ozel'] ?? []);
        if ($ozel) {
            $ozeller[(string) $firma->id] = true;
        } else {
            unset($ozeller[(string) $firma->id], $ozeller[$firma->id]);
        }

        KullaniciAyarlari::kaydet($user, ['firma_egitim_ozel' => $ozeller]);
    }

    /** Firmanın etkin eğitim grubu: önce elle seçilen gerçek iş grubu, yoksa NACE'den türetilen sektör. */
    public static function etkinGrup(Firma $firma): ?string
    {
        return self::firmaGrubuOku($firma) ?: EgitimIcerikOlusturucu::naceSektoru($firma->nace_kodu);
    }

    /**
     * Aynı etkin eğitim grubunda (elle seçili aynı gerçek iş grubu veya aynı NACE sınıfı/sektörü)
     * bulunan diğer aktif firmaları döndürür. Firmaya özel yalıtılmış (`firmaOzelMi`) firmalar hariç tutulur.
     *
     * @return Collection<int, Firma>
     */
    public static function ayniGrubunFirmalari(Firma $kaynak): Collection
    {
        if (self::firmaOzelMi($kaynak)) {
            return collect();
        }

        $grup = self::etkinGrup($kaynak);
        $kaynakNace4 = substr(preg_replace('/\D/', '', (string) $kaynak->nace_kodu), 0, 4);

        return Firma::query()
            ->where('user_id', $kaynak->user_id)
            ->where('aktif', true)
            ->where('id', '!=', $kaynak->id)
            ->orderBy('unvan')
            ->get()
            ->filter(function (Firma $f) use ($grup, $kaynakNace4): bool {
                if (self::firmaOzelMi($f)) {
                    return false;
                }
                $hedefGrup = self::etkinGrup($f);
                if ($grup !== null && $hedefGrup === $grup) {
                    return true;
                }
                if ($grup === null && strlen($kaynakNace4) >= 4) {
                    return str_starts_with(preg_replace('/\D/', '', (string) $f->nace_kodu), $kaynakNace4);
                }

                return false;
            })
            ->values();
    }

    /**
     * Kaynak firmanın 4. bölüm ("İşe ve İşyerine Özgü Riskler") satırlarını, aynı iş grubundaki
     * (veya belirtilen) diğer firmaların ilgili yıl Yıllık Eğitim Planı'na eşitler.
     * Hedef firmalardaki tamamlanmış (G) aylar ve hedef firmanın tehlike sınıfına göre saatleri korunur.
     *
     * @param  array<int, int>|null  $hedefFirmaIdler
     */
    public static function dorduncuBolumuGrubaEsitle(Firma $kaynak, int $yil, ?array $hedefFirmaIdler = null): int
    {
        $kaynakPlan = YillikPlan::firmaYilIcin($kaynak, $yil);
        $kaynakSatirlar = collect($kaynakPlan->egitimler ?? [])->where('kategori', 'ise_ozgu')->values()->all();

        if ($kaynakSatirlar === []) {
            return 0;
        }

        $hedefler = $hedefFirmaIdler !== null
            ? Firma::query()->where('user_id', $kaynak->user_id)->whereIn('id', $hedefFirmaIdler)->where('id', '!=', $kaynak->id)->get()
            : self::ayniGrubunFirmalari($kaynak);

        $guncellenen = 0;
        foreach ($hedefler as $hedef) {
            $plan = YillikPlan::firmaYilIcin($hedef, $yil);
            $kilit = $hedef->planKilitAyIndeksi($yil);

            // Hedef firmanın mevcut 4. bölüm tamamlandı (G) işaretlerini konu adına göre koru
            $mevcutG = [];
            foreach (collect($plan->egitimler ?? [])->where('kategori', 'ise_ozgu') as $m) {
                $anahtar = EgitimKonulari::normalize((string) ($m['konu'] ?? ''));
                foreach (($m['aylar'] ?? []) as $ay => $durum) {
                    if ($durum === 'tamamlandi') {
                        $mevcutG[$anahtar][$ay] = true;
                    }
                }
            }

            $yeniOzgu = array_map(function (array $satir) use ($mevcutG, $kilit): array {
                $anahtar = EgitimKonulari::normalize((string) ($satir['konu'] ?? ''));
                $aylar = [];
                for ($ay = 0; $ay < 12; $ay++) {
                    $d = $satir['aylar'][$ay] ?? 'planlandi';
                    $aylar[$ay] = isset($mevcutG[$anahtar][$ay])
                        ? 'tamamlandi'
                        : ($ay < $kilit ? 'bos' : ($d === 'tamamlandi' ? 'planlandi' : $d));
                }

                return [
                    ...collect($satir)->only(['konu', 'kategori', 'hedef', 'hedef_kitle', 'egitici', 'hafta', 'kutuphane_anahtari'])->all(),
                    'kategori' => 'ise_ozgu',
                    'aylar' => $aylar,
                ];
            }, $kaynakSatirlar);

            $aktifMod = YillikEgitimPlanlayici::moduTespitEt($plan->egitimler ?? [], $hedef);
            $tum = YillikPlanSablonu::iseOzguYerlestir($plan->egitimler ?? [], $yeniOzgu);
            $tum = YillikEgitimPlanlayici::satirlaraUygula($tum, $hedef, $aktifMod);

            $plan->update(['egitimler' => $tum]);
            $guncellenen++;
        }

        return $guncellenen;
    }

    /**
     * Kullanıcının NACE'si aynı diğer firmalarının en son planındaki 4. bölüm satırları (ay işaretleri sıfırlanır).
     * Önce tam kod, sonra sınıf, sonra bölüm; ilk dolu eşleşme kullanılır.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function naceOrtakSatirlar(Firma $firma, int $yil, int $kilitAy): array
    {
        $rakam = preg_replace('/\D/', '', (string) $firma->nace_kodu);

        if (strlen($rakam) < 2) {
            return [];
        }

        $onekler = array_unique(array_filter([
            strlen($rakam) >= 6 ? substr($rakam, 0, 2).'.'.substr($rakam, 2, 2).'.'.substr($rakam, 4, 2) : null,
            strlen($rakam) >= 4 ? substr($rakam, 0, 2).'.'.substr($rakam, 2, 2) : null,
            substr($rakam, 0, 2),
        ]));

        $hedefGrup = self::etkinGrup($firma);

        foreach ($onekler as $onek) {
            $planlar = YillikPlan::query()
                ->with('firma')
                ->where('firma_id', '!=', $firma->id)
                ->whereHas('firma', fn ($q) => $q->withoutGlobalScopes()->where('user_id', $firma->user_id)->where('nace_kodu', 'like', $onek.'%'))
                ->orderByDesc('yil')->orderByDesc('id')
                ->get()
                // NACE'si aynı olsa bile firmaya özel yalıtılmış veya farklı gerçek iş grubuna alınmış
                // firmaların konuları başka firmaların (ör. inşaat) şablonunu bozmaz.
                ->filter(function (YillikPlan $p) use ($hedefGrup): bool {
                    if (! $p->firma || self::firmaOzelMi($p->firma)) {
                        return false;
                    }
                    $kaynakElleGrup = self::firmaGrubuOku($p->firma);
                    if ($kaynakElleGrup !== null && $kaynakElleGrup !== $hedefGrup) {
                        return false;
                    }

                    return true;
                });

            /** @var Collection<int, array<string, mixed>> $satirlar */
            $satirlar = $planlar
                ->map(fn (YillikPlan $p) => collect($p->egitimler ?? [])
                    ->where('kategori', 'ise_ozgu')
                    ->reject(fn (array $e) => ! empty($e['firma_ozel']))
                    ->values())
                ->first(fn (Collection $s) => $s->isNotEmpty());

            if ($satirlar) {
                return YillikPlan::maddeAylarIle(
                    $satirlar->map(fn (array $e) => collect($e)->only(['konu', 'kategori', 'hedef', 'hedef_kitle', 'egitici', 'hafta', 'varsayilan_aylar', 'kutuphane_anahtari'])
                        ->put('varsayilan_aylar', $e['varsayilan_aylar'] ?? range(0, 11))->all())->all(),
                    $kilitAy,
                );
            }
        }

        return [];
    }
}
