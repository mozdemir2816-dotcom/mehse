<?php

namespace App\Support;

use App\Models\AtamaYazisi;
use App\Models\EgitimKatilim;
use App\Models\Firma;
use App\Models\KurulUyesi;
use Illuminate\Support\Collection;

/**
 * İSG Kurulu üyeleri atandığında / değiştiğinde (kullanıcı 03.11.2026):
 *  - İSG Kurulu ATAMA YAZISI otomatik hazırlanır (Atama Yazıları > İSG Kurulu kaydı; kurul üyeleri + kurul görevleri),
 *  - kurul üyelerine İSG Kurulu EĞİTİMİ verilmesi gerektiği (eğitim kaydı olmayan üyeler) gösterilir ve eğitim
 *    katılım formuna üyeler hazır aktarılır; ana sayfa görevlerine de düşer.
 */
class KurulAtamaEgitim
{
    /** Kurul üyeliği rolü → atama yazısındaki kurul görevi (config isg.atama.kurul_gorevleri anahtarı). */
    public const ROL_GOREV = [
        'baskan' => 'baskan', 'sekreter' => 'igu', 'hekim' => 'isyeri_hekimi', 'ik' => 'insan_kaynaklari',
        'calisan_temsilcisi' => 'calisan_temsilcisi', 'sivil_savunma' => 'sivil_savunma', 'formen' => 'usta_formen', 'diger' => 'diger',
    ];

    public const EGITIM_BASLIGI = 'isg_kurulu';

    /** @return Collection<int, KurulUyesi> */
    public static function uyeler(Firma $firma): Collection
    {
        return $firma->kurulUyeleri()->where('aktif', true)->with('calisan:id,tc')->orderBy('id')->get();
    }

    /** Atama yazısı üye satırları (AtamaYazisi.uyeler biçimi). @return array<int, array<string, mixed>> */
    public static function atamaUyeleri(Firma $firma): array
    {
        $igu = $firma->igu;
        $hekim = $firma->isyeriHekimi;

        return self::uyeler($firma)->map(function (KurulUyesi $u) use ($igu, $hekim): array {
            $satir = [
                'calisan_id' => $u->calisan_id,
                'ad_soyad' => $u->ad_soyad,
                'tc' => $u->calisan?->tc,
                'gorev' => config('isg.atama.kurul_gorevleri.'.(self::ROL_GOREV[$u->rol] ?? 'diger')) ?: $u->rolEtiketi(),
                'bas_uye' => $u->rol === 'baskan',
            ];

            // Firmaya atanmış İGU / işyeri hekimi kurul üyesiyse kaşe-imza görselleri yazıya da basılır (Atama Yazıları ile aynı).
            foreach ([[$igu, 'sekreter'], [$hekim, 'hekim']] as [$p, $rol]) {
                if ($p && $u->rol === $rol && EgitimTakibi::adAnahtari($p->ad_soyad) === EgitimTakibi::adAnahtari($u->ad_soyad)) {
                    $satir['kase_gorseli'] = $p->kase_gorseli;
                    $satir['imza_gorseli'] = $p->imza_gorseli;
                }
            }

            return $satir;
        })->all();
    }

    public static function sonAtama(Firma $firma): ?AtamaYazisi
    {
        return $firma->atamaYazilari()->where('rol_anahtari', 'isg_kurulu')->latest('tarih')->latest('id')->first();
    }

    /**
     * Güncel kurul üyeleri son atama yazısıyla aynı mı?
     *
     * @return array{var: bool, guncel: bool, son: ?AtamaYazisi, eklenen: array<int, string>, cikan: array<int, string>}
     */
    public static function atamaDurumu(Firma $firma): array
    {
        $son = self::sonAtama($firma);
        $anahtar = fn (?string $ad) => EgitimTakibi::adAnahtari($ad);
        $simdi = collect(self::atamaUyeleri($firma))->mapWithKeys(fn ($u) => [$anahtar($u['ad_soyad']) => $u['ad_soyad']]);
        $eski = collect($son?->uyeler ?? [])->mapWithKeys(fn ($u) => [$anahtar($u['ad_soyad'] ?? null) => $u['ad_soyad'] ?? '']);

        $eklenen = $simdi->diffKeys($eski)->values()->all();
        $cikan = $eski->diffKeys($simdi)->values()->all();

        return [
            'var' => $son !== null,
            'guncel' => $son !== null && ! $eklenen && ! $cikan,
            'son' => $son,
            'eklenen' => $eklenen,
            'cikan' => $cikan,
        ];
    }

    /**
     * İSG Kurulu atama yazısını güncel üyelerle hazırlar: aynı gün kaydı varsa güncellenir, yoksa yeni kayıt açılır
     * (her üye değişikliğinde kopya birikmez). Üye yoksa null.
     */
    public static function atamaYazisiHazirla(Firma $firma): ?AtamaYazisi
    {
        $uyeler = self::atamaUyeleri($firma);

        if (! $uyeler) {
            return null;
        }

        $baskan = collect($uyeler)->firstWhere('bas_uye', true);
        $bugun = $firma->atamaYazilari()->where('rol_anahtari', 'isg_kurulu')->whereDate('tarih', today())->latest('id')->first();
        $alanlar = ['uyeler' => $uyeler, 'isveren_vekili_adi' => $baskan['ad_soyad'] ?? ($firma->isveren_vekili ?: $firma->isveren_ad)];

        if ($bugun) {
            $bugun->update($alanlar);

            return $bugun;
        }

        return AtamaYazisi::create(['firma_id' => $firma->id, 'rol_anahtari' => 'isg_kurulu', 'tarih' => today(), ...$alanlar]);
    }

    /**
     * Kurul eğitimi: İSG Kurulu eğitimi kaydı (katılımı onaylı) bulunmayan aktif üyeler.
     *
     * @return array{toplam: int, alan: int, eksik: Collection<int, KurulUyesi>}
     */
    public static function egitimDurumu(Firma $firma): array
    {
        $uyeler = self::uyeler($firma);
        $alanlar = EgitimKatilim::query()->where('firma_id', $firma->id)->where('baslik_anahtari', self::EGITIM_BASLIGI)->get()
            ->flatMap(fn (EgitimKatilim $k) => collect($k->belgeAlacakKatilimcilar())->pluck('ad_soyad'))
            ->map(fn ($ad) => EgitimTakibi::adAnahtari($ad))->filter()->flip();

        $eksik = $uyeler->reject(fn (KurulUyesi $u) => $alanlar->has(EgitimTakibi::adAnahtari($u->ad_soyad)))->values();

        return ['toplam' => $uyeler->count(), 'alan' => $uyeler->count() - $eksik->count(), 'eksik' => $eksik];
    }

    /** Eğitimi olmayan kurul üyeleri için Eğitim Katılım'da planlı kayıt açar / günceller (bkz. EgitimPlanlayici). */
    public static function egitimPlanla(Firma $firma): ?EgitimKatilim
    {
        $eksik = self::egitimDurumu($firma)['eksik']->map(fn (KurulUyesi $u) => ['ad_soyad' => $u->ad_soyad, 'tc' => $u->calisan?->tc, 'gorev' => $u->rolEtiketi()])->values()->all();

        return EgitimPlanlayici::planla($firma, self::EGITIM_BASLIGI, $eksik, 'kurul');
    }

    public static function planliEgitim(Firma $firma): ?EgitimKatilim
    {
        return EgitimPlanlayici::acikKayit($firma, self::EGITIM_BASLIGI, 'kurul');
    }

    /** Eğitim Katılım formuna aktarılacak oturum verisi (eğitimi olmayan üyeler); yoksa null. */
    public static function egitimAktarimi(Firma $firma): ?array
    {
        $eksik = self::egitimDurumu($firma)['eksik'];

        if ($eksik->isEmpty()) {
            return null;
        }

        return [
            'firma_id' => $firma->id,
            'baslik_anahtari' => self::EGITIM_BASLIGI,
            'katilimcilar' => $eksik->map(fn (KurulUyesi $u) => ['ad_soyad' => $u->ad_soyad, 'tc' => $u->calisan?->tc, 'gorev' => $u->rolEtiketi()])->values()->all(),
        ];
    }
}
