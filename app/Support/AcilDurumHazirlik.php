<?php

namespace App\Support;

use App\Models\AcilDurumKrokisi;
use App\Models\AcilDurumPlani;
use App\Models\Firma;
use App\Models\TatbikatTutanagi;
use App\Models\User;
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
            'ozel_risk_alanlari' => 'degerlendirilmedi', 'ozel_risk_notu' => '',
            'enerji_kesme' => 'degerlendirilmedi', 'enerji_kesme_notu' => '',
            'mudahale_yontemi' => '', 'ozel_destek' => '',
            'ziyaretci_dahil' => false, 'gecici_calisan_dahil' => false, 'coklu_isveren' => false,
            'iletisim' => [['ad' => '112 Acil Çağrı Merkezi', 'telefon' => '112', 'aciklama' => 'Ulusal acil çağrı']],
            'onay_durumu' => 'yok', 'son_tatbikat' => null, 'sonraki_tatbikat' => null, 'tutanak_ref' => '',
            'kroki_asildi' => false, 'calisan_bilgilendirildi' => false, 'ek_not' => '',
            ...array_fill_keys(array_keys(static::BELGE_ALANLARI), ''),
            'risk_puanlari' => [], 'cevresel_riskler' => [],
        ];
    }

    /**
     * Plan belgesindeki (FOM düzeni) işyerine özgü kısa bilgiler — alan => azami uzunluk.
     * Boş kalanlar belgede elle doldurulacak noktalı satır olarak basılır.
     */
    public const BELGE_ALANLARI = [
        'koordinator_ad' => 120, 'koordinator_tel' => 40, 'koordinator_vekil' => 120, 'koordinator_vekil_tel' => 40,
        'isveren_tel' => 40, 'isveren_vekili_tel' => 40,
        'alarm_yontemi' => 600, 'yedek_toplanma' => 300, 'refakatci' => 300, 'tatbikat_sorumlusu' => 120,
        'bina_bilgisi' => 600, 'vardiyalar' => 200, 'kayit_yedekleme' => 600,
        'hastane_ad' => 200, 'hastane_tel' => 40, 'kroki_kat' => 200,
    ];

    /** Kullanıcının bir kez doldurup tüm firmalarına önerilen alanlar (aynı bölgedeki işyerleri). */
    public const SABLON_ALANLARI = ['risk_puanlari', 'cevresel_riskler'];

    /** @return array{risk_puanlari: array, cevresel_riskler: array} users.ayarlar.acil_durum_sablonu */
    public static function kullaniciSablonu(?User $user): array
    {
        $kayitli = (array) (((array) ($user?->ayarlar ?? []))['acil_durum_sablonu'] ?? []);

        return collect(static::SABLON_ALANLARI)->mapWithKeys(fn ($a) => [$a => (array) ($kayitli[$a] ?? [])])->all();
    }

    /** Planda hiç girilmemiş risk puanı / çevresel risk varsa kullanıcının şablonundan doldurur. */
    public static function sablonlaTamamla(array $u, ?User $user): array
    {
        foreach (static::kullaniciSablonu($user) as $alan => $deger) {
            if (static::bosMu($u[$alan] ?? []) && ! static::bosMu($deger)) {
                $u[$alan] = $deger;
            }
        }

        return $u;
    }

    public static function sablonaKaydet(User $user, array $u): void
    {
        KullaniciAyarlari::kaydet($user, [
            'acil_durum_sablonu' => array_intersect_key(static::temizle($u), array_flip(static::SABLON_ALANLARI)),
        ]);
    }

    public static function bosMu(mixed $deger): bool
    {
        return collect((array) $deger)->flatten()->filter(fn ($v) => filled($v))->isEmpty();
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
            'ozel_risk_notu' => $metin($u['ozel_risk_notu'] ?? ''),
            'enerji_kesme_notu' => $metin($u['enerji_kesme_notu'] ?? ''),
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
            ...collect(static::BELGE_ALANLARI)->map(fn ($uz, $k) => $metin($u[$k] ?? '', $uz))->all(),
            'risk_puanlari' => collect(config('isg.acil_durum.risk_listesi'))->keys()
                ->mapWithKeys(fn ($r) => [$r => collect(config('isg.acil_durum.risk_kolonlari'))->keys()
                    ->mapWithKeys(fn ($k) => [$k => in_array((int) ($u['risk_puanlari'][$r][$k] ?? 0), [1, 2, 3, 4, 5], true) ? (int) $u['risk_puanlari'][$r][$k] : null])
                    ->filter()->all()])
                ->filter()->all(),
            'cevresel_riskler' => collect(config('isg.acil_durum.cevresel_riskler'))->keys()
                ->mapWithKeys(fn ($i) => [$i => [
                    'durum' => in_array($u['cevresel_riskler'][$i]['durum'] ?? '', ['var', 'yok'], true) ? $u['cevresel_riskler'][$i]['durum'] : '',
                    'not' => $metin($u['cevresel_riskler'][$i]['not'] ?? '', 300),
                ]])
                ->filter(fn ($r) => $r['durum'] !== '' || $r['not'] !== '')->all(),
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

        // Tahliye planı: yüklenen görsel (içeriği denetlenemez) ya da editör
        // krokisi — editör krokisinde Md.12(1)(f) zorunlu unsurları aranır.
        $kroki = AcilDurumKrokisi::query()->where('firma_id', $plan->firma_id)->first();
        $krokiCizili = $kroki && (count($kroki->semboller ?? []) > 0 || count($kroki->duvarlar ?? []) > 0);
        $krokiEksik = $krokiCizili ? $kroki->eksikZorunluUnsurlar() : [];
        $krokiVar = filled($plan->tahliye_plani_gorseli) || ($krokiCizili && $krokiEksik === []);
        $krokiAciklama = match (true) {
            filled($plan->tahliye_plani_gorseli) => 'Tahliye planı görseli yüklendi',
            ! $krokiCizili => 'Kroki çizilmemiş',
            $krokiEksik !== [] => 'Krokide eksik: '.implode(', ', $krokiEksik),
            default => 'Kroki mevcut, zorunlu unsurlar işaretli',
        };

        $iletisim = collect($u['iletisim']);

        // Özel risk / enerji kesme: "Var" ise yerleri yazılmalı (Md.12/1-f-6,7)
        $yerYazili = fn (string $durum, string $not) => $durum !== 'degerlendirilmedi' && ($durum !== 'var' || filled($not));
        $isveren = $firma?->isveren_ad ?: $firma?->isveren_vekili;
        $kunyeEksik = array_keys(array_filter(['adres' => blank($firma?->adres), 'işveren / vekili adı' => blank($isveren)]));

        $k = [
            ['Künye: unvan, adres ve işveren adı', $kunyeEksik === [],
                $kunyeEksik ? 'Firma kaydında eksik: '.implode(', ', $kunyeEksik) : 'Tamam'],
            ['Künye: plan ve gözden geçirme tarihi', $plan->rapor_tarihi && $plan->gecerlilik_tarihi?->gte($bugun),
                $plan->gecerlilik_tarihi ? 'Gözden geçirme '.$plan->gecerlilik_tarihi->format('d.m.Y').($plan->gecerlilik_tarihi->lt($bugun) ? ' — süresi geçti' : '') : 'Plan tarihi girilmemiş'],
            ['Senaryolar (acil durum konuları)', count($plan->konular ?? []) > 0, count($plan->konular ?? []).' senaryo seçili'],
            ['Önleyici ve sınırlandırıcı tedbirler', filled($u['onleyici_tedbirler']), filled($u['onleyici_tedbirler']) ? 'Yazıldı' : 'Tedbirler yazılmamış'],
            ['Acil durum ekipmanı ve KKD listesi', filled($u['ekipman_kkd']), filled($u['ekipman_kkd']) ? 'Yazıldı' : 'Liste yok'],
            ['Özel risk alanları / enerji kesme noktaları', $yerYazili($u['ozel_risk_alanlari'], $u['ozel_risk_notu']) && $yerYazili($u['enerji_kesme'], $u['enerji_kesme_notu']),
                'Özel risk: '.static::UC_DURUM[$u['ozel_risk_alanlari']].($u['ozel_risk_alanlari'] === 'var' && blank($u['ozel_risk_notu']) ? ' (yeri yazılmamış)' : '')
                .' · Enerji / vana: '.static::UC_DURUM[$u['enerji_kesme']].($u['enerji_kesme'] === 'var' && blank($u['enerji_kesme_notu']) ? ' (yeri yazılmamış)' : '')],
            ['Destek ekipleri (söndürme, kurtarma, koruma, ilk yardım)', $bosEkip === [], $bosEkip === [] ? 'Tüm ekiplerde kişi var' : 'Boş ekip: '.implode(', ', $bosEkip)],
            ['Müdahale, haberleşme ve tahliye yöntemi', filled($u['mudahale_yontemi']), filled($u['mudahale_yontemi']) ? 'Yazıldı' : 'Yöntem yazılmamış'],
            ['Toplanma alanı', filled($plan->toplanma_yeri), $plan->toplanma_yeri ?: 'Toplanma yeri girilmemiş'],
            ['Acil iletişim listesi', $iletisim->count() >= 2, $iletisim->count().' irtibat (112 dışında işyerine uygun yerel irtibat ekleyin)'],
            ['Tahliye krokisi', $krokiVar, $krokiAciklama],
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
