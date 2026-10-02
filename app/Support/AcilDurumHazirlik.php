<?php

namespace App\Support;

use App\Models\AcilDurumKrokisi;
use App\Models\AcilDurumPlani;
use App\Models\Firma;
use App\Models\TatbikatTutanagi;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Acil durum planı hazırlık kontrolü (isgsuite "mevzuat odaklı plan kontrol
 * merkezi"): planın künye, risk / tedbir ve uygulama başlıklarının
 * belgelenip belgelenmediğini işaretler. Hukuki uygunluk beyanı değildir;
 * saha doğrulaması, işveren onayı, ekip eğitimleri ve tatbikat kayıtları
 * ayrıca tamamlanmalıdır.
 */
class AcilDurumHazirlik
{
    public const UC_DURUM = ['degerlendirilmedi' => 'Değerlendirilmedi', 'uygulanamaz' => 'Uygulanamaz', 'var' => 'Var'];

    public const ONAY = ['yok' => 'Onay kaydı yok', 'taslak' => 'Taslak / onaya sunuldu', 'imzalandi' => 'İşveren onayladı / imzalandı'];

    /** Uygulama bilgisinin varsayılanı (isgsuite plan sihirbazı 2-3. adım). */
    public static function varsayilan(): array
    {
        return [
            'onleyici_tedbirler' => '', 'olcum_notu' => '', 'ekipman_kkd' => '',
            'ozel_risk_alanlari' => 'degerlendirilmedi', 'enerji_kesme' => 'degerlendirilmedi',
            'mudahale_yontemi' => '', 'ozel_destek' => '',
            'ziyaretci_dahil' => false, 'gecici_calisan_dahil' => false, 'coklu_isveren' => false,
            'iletisim' => [['ad' => '112 Acil Çağrı Merkezi', 'telefon' => '112', 'aciklama' => 'Ulusal acil çağrı']],
            'onay_durumu' => 'yok', 'son_tatbikat' => null, 'sonraki_tatbikat' => null, 'tutanak_ref' => '',
            'kroki_asildi' => false, 'calisan_bilgilendirildi' => false, 'ek_not' => '',
        ];
    }

    /** Tarayıcıdan / formdan gelen uygulama bilgisini sınırlar. */
    public static function temizle(array $u): array
    {
        $v = static::varsayilan();
        $metin = fn ($x, $uz = 2000) => mb_substr(trim((string) ($x ?? '')), 0, $uz);
        $tarih = fn ($x) => filled($x) && strtotime((string) $x) ? Carbon::parse($x)->toDateString() : null;

        return [
            'onleyici_tedbirler' => $metin($u['onleyici_tedbirler'] ?? ''),
            'olcum_notu' => $metin($u['olcum_notu'] ?? ''),
            'ekipman_kkd' => $metin($u['ekipman_kkd'] ?? ''),
            'ozel_risk_alanlari' => array_key_exists($u['ozel_risk_alanlari'] ?? '', static::UC_DURUM) ? $u['ozel_risk_alanlari'] : $v['ozel_risk_alanlari'],
            'enerji_kesme' => array_key_exists($u['enerji_kesme'] ?? '', static::UC_DURUM) ? $u['enerji_kesme'] : $v['enerji_kesme'],
            'mudahale_yontemi' => $metin($u['mudahale_yontemi'] ?? ''),
            'ozel_destek' => $metin($u['ozel_destek'] ?? ''),
            'ziyaretci_dahil' => (bool) ($u['ziyaretci_dahil'] ?? false),
            'gecici_calisan_dahil' => (bool) ($u['gecici_calisan_dahil'] ?? false),
            'coklu_isveren' => (bool) ($u['coklu_isveren'] ?? false),
            'iletisim' => collect($u['iletisim'] ?? [])->filter(fn ($i) => is_array($i) && (filled($i['ad'] ?? null) || filled($i['telefon'] ?? null)))
                ->take(30)->map(fn ($i) => ['ad' => $metin($i['ad'] ?? '', 80), 'telefon' => $metin($i['telefon'] ?? '', 30), 'aciklama' => $metin($i['aciklama'] ?? '', 120)])
                ->values()->all(),
            'onay_durumu' => array_key_exists($u['onay_durumu'] ?? '', static::ONAY) ? $u['onay_durumu'] : 'yok',
            'son_tatbikat' => $tarih($u['son_tatbikat'] ?? null),
            'sonraki_tatbikat' => $tarih($u['sonraki_tatbikat'] ?? null),
            'tutanak_ref' => $metin($u['tutanak_ref'] ?? '', 60),
            'kroki_asildi' => (bool) ($u['kroki_asildi'] ?? false),
            'calisan_bilgilendirildi' => (bool) ($u['calisan_bilgilendirildi'] ?? false),
            'ek_not' => $metin($u['ek_not'] ?? ''),
        ];
    }

    /**
     * Hazırlık kontrolleri.
     *
     * @return array{kontroller: array<int, array{baslik: string, tamam: bool, aciklama: string}>, yuzde: int, durum: string}
     */
    public static function kontrol(AcilDurumPlani $plan): array
    {
        $u = array_replace(static::varsayilan(), $plan->uygulama ?? []);
        $firma = $plan->firma;
        $bugun = Carbon::today();

        $ekipler = $plan->ekipListesi();
        $bosEkip = collect($ekipler)->filter(fn ($l) => $l === [])->keys()->map(fn ($k) => config('isg.acil_durum.ekipler.'.$k))->all();

        // Tatbikat: Tatbikat modülündeki "yapıldı" kaydı öncelikli, yoksa elle girilen tarih
        $sonTatbikat = TatbikatTutanagi::query()->where('firma_id', $plan->firma_id)
            ->where(fn ($q) => $q->whereNull('durum')->orWhere('durum', 'tamamlandi'))
            ->whereNotNull('tatbikat_tarihi')->max('tatbikat_tarihi');
        $sonTatbikat = $sonTatbikat ? Carbon::parse($sonTatbikat) : (filled($u['son_tatbikat']) ? Carbon::parse($u['son_tatbikat']) : null);
        $tatbikatKaynak = $sonTatbikat && TatbikatTutanagi::query()->where('firma_id', $plan->firma_id)->exists() ? 'Tatbikat modülü' : 'elle girilen tarih';

        $kroki = AcilDurumKrokisi::query()->where('firma_id', $plan->firma_id)->first();
        $krokiVar = ($kroki && (count($kroki->semboller ?? []) > 0 || count($kroki->duvarlar ?? []) > 0)) || filled($plan->tahliye_plani_gorseli);

        $iletisim = collect($u['iletisim']);

        $k = [
            ['Künye: plan ve gözden geçirme tarihi', $plan->rapor_tarihi && $plan->gecerlilik_tarihi?->gte($bugun),
                $plan->gecerlilik_tarihi ? 'Gözden geçirme '.$plan->gecerlilik_tarihi->format('d.m.Y').($plan->gecerlilik_tarihi->lt($bugun) ? ' — süresi geçti' : '') : 'Plan tarihi girilmemiş'],
            ['Senaryolar (acil durum konuları)', count($plan->konular ?? []) > 0, count($plan->konular ?? []).' senaryo seçili'],
            ['Önleyici ve sınırlandırıcı tedbirler', filled($u['onleyici_tedbirler']), filled($u['onleyici_tedbirler']) ? 'Yazıldı' : 'Tedbirler yazılmamış'],
            ['Acil durum ekipmanı ve KKD listesi', filled($u['ekipman_kkd']), filled($u['ekipman_kkd']) ? 'Yazıldı' : 'Liste yok'],
            ['Özel risk alanları / enerji kesme noktaları', $u['ozel_risk_alanlari'] !== 'degerlendirilmedi' && $u['enerji_kesme'] !== 'degerlendirilmedi',
                'Özel risk: '.static::UC_DURUM[$u['ozel_risk_alanlari']].' · Enerji / vana: '.static::UC_DURUM[$u['enerji_kesme']]],
            ['Destek ekipleri (söndürme, kurtarma, koruma, ilk yardım)', $bosEkip === [], $bosEkip === [] ? 'Tüm ekiplerde kişi var' : 'Boş ekip: '.implode(', ', $bosEkip)],
            ['Müdahale, haberleşme ve tahliye yöntemi', filled($u['mudahale_yontemi']), filled($u['mudahale_yontemi']) ? 'Yazıldı' : 'Yöntem yazılmamış'],
            ['Toplanma alanı', filled($plan->toplanma_yeri), $plan->toplanma_yeri ?: 'Toplanma yeri girilmemiş'],
            ['Acil iletişim listesi', $iletisim->count() >= 2, $iletisim->count().' irtibat (112 dışında işyerine uygun yerel irtibat ekleyin)'],
            ['Tahliye krokisi', $krokiVar, $krokiVar ? 'Kroki / tahliye planı mevcut' : 'Kroki çizilmemiş'],
            ['Krokiler görünür yerlere asıldı', (bool) $u['kroki_asildi'], $u['kroki_asildi'] ? 'Saha doğrulandı' : 'Doğrulanmadı'],
            ['Tatbikat (yılda en az bir)', $sonTatbikat !== null && $sonTatbikat->gte($bugun->copy()->subYear()),
                $sonTatbikat ? 'Son tatbikat '.$sonTatbikat->format('d.m.Y').' ('.$tatbikatKaynak.')' : 'Tatbikat kaydı yok'],
            ['Çalışan bilgilendirmesi', (bool) $u['calisan_bilgilendirildi'], $u['calisan_bilgilendirildi'] ? 'Tamamlandı (yeni ve geçici çalışanlar dahil)' : 'Tamamlanmadı'],
            ['Yayın / işveren onayı', $u['onay_durumu'] === 'imzalandi', static::ONAY[$u['onay_durumu']]],
        ];

        $kontroller = collect($k)->map(fn ($r) => ['baslik' => $r[0], 'tamam' => (bool) $r[1], 'aciklama' => $r[2]])->all();
        $tamam = collect($kontroller)->where('tamam', true)->count();
        $gozden = $plan->gecerlilik_tarihi?->lt($bugun) ?? false;

        return [
            'kontroller' => $kontroller,
            'yuzde' => (int) round($tamam * 100 / count($kontroller)),
            'durum' => match (true) {
                $gozden => 'gozden_gecirme',
                $tamam === count($kontroller) => 'hazir',
                default => 'aksiyon',
            },
        ];
    }

    public const DURUM_ETIKET = ['hazir' => 'Hazır', 'aksiyon' => 'Aksiyon gerekli', 'gozden_gecirme' => 'Gözden geçirme'];

    /**
     * Plan portföyü (isgsuite "İşyerlerinizin acil durum hazırlığı").
     *
     * @return Collection<int, array{firma: Firma, plan: ?AcilDurumPlani, kontrol: ?array}>
     */
    public static function portfoy(int $userId): Collection
    {
        $planlar = AcilDurumPlani::query()->whereHas('firma', fn ($q) => $q->where('user_id', $userId))->with('firma')->get()->keyBy('firma_id');

        return Firma::query()->where('user_id', $userId)->where('aktif', true)->orderBy('unvan')->get()
            ->map(fn (Firma $f) => [
                'firma' => $f,
                'plan' => $planlar->get($f->id),
                'kontrol' => $planlar->get($f->id)?->rapor_tarihi ? static::kontrol($planlar->get($f->id)) : null,
            ]);
    }
}
