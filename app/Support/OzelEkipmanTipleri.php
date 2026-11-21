<?php

namespace App\Support;

use App\Models\EkipmanAfisi;
use App\Models\IsEkipmani;
use App\Models\OzelEkipmanTipi;
use App\Models\TalimatSablonu;
use App\Models\Tehlike;
use App\Models\TehlikeKategorisi;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;

/**
 * Toplu Makine Paketi (21.11.2026) — yeni makine için kontrol formu, çalışma talimatı, risk
 * maddeleri ve afiş yazısı Gemini SOHBETİNDE (API kotası harcanmadan) tek JSON olarak üretilir,
 * bu sınıf JSON'u sisteme kurar. Hepsi "YZ taslağı"dır; uzman "Paketi onayla" ile kesinleştirir.
 *
 * Kurulan tip, açılışta config'e eklenir (configeEkle): isg.periyodik_kontrol.tipler (seçim listesi
 * ve periyot), isg.haftalik_ekipman_kontrol.tipler (günlük form), ekipman_katalogu.tipler (bağlar)
 * ve ekipman_afis.tipler (afiş). Böylece mevcut ekranlar yeni tipi config'teki gibi tanır.
 */
class OzelEkipmanTipleri
{
    public const ONBELLEK = 'ozel_ekipman_tipleri.v1';

    public const PARCA = 4;

    public const AFIS_GRUBU = 'Yeni makineler';

    /** Açılışta (AppServiceProvider) — tablo yoksa ya da DB'ye ulaşılamazsa sessizce atlanır. */
    public static function configeEkle(): void
    {
        try {
            $tipler = Cache::rememberForever(self::ONBELLEK, fn () => OzelEkipmanTipi::query()->orderBy('id')->get()
                ->map(fn ($t) => $t->only(['ad', 'anahtar', 'kategori', 'periyot_ay', 'standart', 'deney', 'kontrol_sorulari']))->all());
        } catch (Throwable) {
            return;
        }

        foreach ($tipler as $t) {
            if (array_key_exists($t['ad'], config('ekipman_katalogu.tipler', []))) {
                continue;   // config'te zaten olan tip ezilmez
            }
            config()->push('isg.periyodik_kontrol.tipler.'.$t['kategori'], [
                'ad' => $t['ad'], 'periyot_ay' => (int) $t['periyot_ay'],
                'standart' => $t['standart'] ?: 'Üretici talimatı', 'deney' => $t['deney'] ?: 'Kullanım öncesi kontrol formu',
            ]);
            if (filled($t['kontrol_sorulari'])) {
                config()->push('isg.haftalik_ekipman_kontrol.tipler', ['ad' => $t['ad'], 'sorular' => array_values($t['kontrol_sorulari'])]);
            }
            config(['ekipman_katalogu.tipler.'.$t['ad'] => ['risk_kategorileri' => [], 'kontrol_formu' => filled($t['kontrol_sorulari']) ? $t['ad'] : null]]);
            if (! array_key_exists($t['anahtar'], config('ekipman_afis.tipler', []))) {
                config(['ekipman_afis.tipler.'.$t['anahtar'] => ['ad' => $t['ad'], 'grup' => self::AFIS_GRUBU]]);
            }
        }
    }

    public static function onbellegiTemizle(): void
    {
        Cache::forget(self::ONBELLEK);
    }

    /**
     * Yazılan adları ayırır: sistemde paketi zaten olan (bilinen tip) ve yeni olanlar.
     *
     * @return array{yeni: array<int, string>, mevcut: array<int, array{ad: string, tip: string}>}
     */
    public static function ayir(string $metin): array
    {
        $sonuc = ['yeni' => [], 'mevcut' => []];
        $gorulen = [];
        foreach (preg_split('/[\r\n;]+/u', $metin) as $ad) {
            $ad = trim(preg_replace('/\s+/u', ' ', $ad), " \t-•*");
            $n = EkipmanKatalogu::norm($ad);
            if ($ad === '' || isset($gorulen[$n])) {
                continue;
            }
            $gorulen[$n] = true;
            // Kontrol formu adı ("Forklift") katalog tipinden ("Forklift / Akülü …") kısa olabilir; önce formlara bakılır.
            $formAdlari ??= collect(EkipmanKatalogu::kontrolFormlari())->pluck('ad')->keyBy(fn ($f) => EkipmanKatalogu::norm($f));
            if ($form = $formAdlari->get($n)) {
                $sonuc['mevcut'][] = ['ad' => $ad, 'tip' => $form];

                continue;
            }
            $tip = EkipmanKatalogu::tipBul($ad);
            if ($tip && EkipmanKatalogu::kontrolFormu($tip)) {
                $sonuc['mevcut'][] = ['ad' => $ad, 'tip' => $tip];
            } else {
                $sonuc['yeni'][] = mb_substr($ad, 0, 120);
            }
        }

        return $sonuc;
    }

    /**
     * Firmalara eklenmiş ama sistemde kontrol formu / paketi olmayan makineler (öneri listesi).
     *
     * @return array<int, string>
     */
    public static function paketsizFirmaEkipmanlari(): array
    {
        return IsEkipmani::query()->where('aktif', true)->get(['tip', 'ekipman_adi', 'kategori'])
            ->reject(fn ($e) => in_array($e->kategori, EkipmanTalimatTaslagi::KAPSAM_DISI_KATEGORILER, true))
            ->map(fn ($e) => trim((string) ($e->tip ?: $e->ekipman_adi)))
            ->filter(fn ($ad) => $ad !== '' && ! EkipmanKatalogu::kontrolFormu($ad))
            ->unique(fn ($ad) => EkipmanKatalogu::norm($ad))
            ->values()->all();
    }

    public static function anahtar(string $ad): string
    {
        return Str::limit(Str::slug($ad), 70, '');
    }

    /**
     * Gemini sohbetine yapıştırılacak istemler — her biri en fazla PARCA makine.
     *
     * @param  array<int, string>  $adlar
     * @return array<int, string>
     */
    public static function istemler(array $adlar): array
    {
        return collect($adlar)->chunk(self::PARCA)->values()
            ->map(fn ($parca, $i) => static::istem($parca->values()->all(), $i + 1, (int) ceil(count($adlar) / self::PARCA)))
            ->all();
    }

    /** @param  array<int, string>  $adlar */
    public static function istem(array $adlar, int $no = 1, int $toplam = 1): string
    {
        $kategoriler = collect(config('isg.periyodik_kontrol.kategoriler', []))
            ->except(EkipmanTalimatTaslagi::KAPSAM_DISI_KATEGORILER)
            ->map(fn ($k, $anahtar) => "{$anahtar} = {$k['ad']}")->implode('; ');
        $kkd = implode(', ', array_keys(config('ekipman_afis.kkd', [])));
        $liste = collect($adlar)->map(fn ($ad) => "- {$ad}  (dosya_adi: ".static::anahtar($ad).')')->implode("\n");
        $parca = $toplam > 1 ? " (parça {$no}/{$toplam})" : '';

        return <<<ISTEM
        Sen Türkiye'de 6331 sayılı İSG Kanunu ve İş Ekipmanlarının Kullanımında Sağlık ve Güvenlik Şartları Yönetmeliği'ne göre çalışan deneyimli bir A sınıfı iş güvenliği uzmanısın. Aşağıdaki makinelerin her biri için İSG paketi hazırla{$parca}.

        MAKİNELER:
        {$liste}

        Her makine için şunları üret:
        1) kategori: YALNIZ şu anahtarlardan biri — {$kategoriler}
        2) periyot_ay: kaldırma/basınçlı ekipmanda yasal periyodik kontrol süresi (ay); periyodik muayenesi yoksa 12.
        3) standart: ilgili TS EN / TS EN ISO standart adları (emin olmadığın numarayı yazma). deney: periyodik kontrolde ne yapılır, kısa (muayenesi yoksa "Periyodik muayene yok — kullanım öncesi kontrol formu").
        4) kontrol_sorulari: operatörün her vardiya öncesi dolduracağı 10-14 soru; her biri "...mı?" diye biten, bu makineye özgü, somut.
        5) talimat: bölümlü güvenli çalışma talimatı. Bölümler bu sırayla ve başlıkları BÜYÜK HARF: AMAÇ VE KAPSAM (yalnız aciklama, maddeler boş), KİŞİSEL KORUYUCU DONANIMLAR (KKD), ÇALIŞMA ÖNCESİ ALINACAK GÜVENLİK ÖNLEMLERİ, ÇALIŞMA SIRASINDA UYULACAK KURALLAR, ÇALIŞMA SONRASI VE BAKIM, YASAKLAR, ACİL DURUM VE ARIZA BİLDİRİMİ. Her bölümde 4-8 madde, "...malıdır/meli" kipinde. TAAHHÜT bölümü ekleme.
        6) riskler: 8-12 Fine-Kinney risk maddesi. olasilik şu değerlerden: 0.2, 0.5, 1, 3, 6, 10; frekans: 0.5, 1, 2, 3, 6, 10; siddet: 1, 3, 7, 15, 40, 100. son_* = öneri uygulandıktan sonraki beklenen değerler. Gerçekçi puanla; her maddeyi 400 üstüne çıkarma. Mevcut önlem YAZMA.
        7) afis: yapin tam 6 madde, yapmayin tam 5 madde (her biri en fazla 60 karakter, kısa emir cümlesi); kkd YALNIZ şu listeden: {$kkd}.
        8) resim_sahnesi: bu makinenin DOĞRU ve GÜVENLİ kullanıldığı tek cümlelik çizim sahnesi (yazı/marka içermeyen).

        Kurallar: Türkçe karakterleri doğru kullan. Marka adı, uydurma yönetmelik madde numarası yazma. makine ve dosya_adi alanlarını yukarıda verdiğim gibi AYNEN yaz.

        ÇIKTI: yalnız tek bir JSON dizisi ver (başka açıklama yazma), biçim:
        [{"makine":"...","dosya_adi":"...","kategori":"...","periyot_ay":12,"standart":"...","deney":"...",
          "kontrol_sorulari":["...mı?"],
          "talimat":[{"baslik":"AMAÇ VE KAPSAM","aciklama":"...","maddeler":[]},{"baslik":"...","aciklama":"","maddeler":["..."]}],
          "riskler":[{"faaliyet":"...","tehlike":"...","risk":"...","oneri":"...","sorumlu":"...","olasilik":3,"frekans":6,"siddet":15,"son_olasilik":1,"son_frekans":6,"son_siddet":15}],
          "afis":{"yapin":["..."],"yapmayin":["..."],"kkd":["Baret"]},
          "resim_sahnesi":"..."}]
        ISTEM;
    }

    /** Kurulmuş tipin afiş resmi için Gemini istemi (Ekipman Afişleri'yle aynı stil). */
    public static function resimIstemi(OzelEkipmanTipi $t): string
    {
        $kkd = implode(', ', EkipmanAfisi::query()->where('anahtar', $t->anahtar)->value('kkd') ?? []);

        return "Bir iş güvenliği afişinin ortasında kullanılacak, İÇİNDE HİÇ YAZI OLMAYAN bir illüstrasyon çiz. Konu: {$t->ad}. "
            .'Sahne: '.($t->resim_sahnesi ?: "{$t->ad} güvenli şekilde kullanılıyor").'. '
            .($kkd ? "Çalışan(lar) şu koruyucu donanımları kullanıyor: {$kkd}. " : '')
            .'Görüntü doğru ve güvenli çalışma şeklini göstersin (yanlış davranış gösterme). Stil (serinin tüm görselleri aynı olacak): temiz, modern, düz vektör illüstrasyon; '
            .'net konturlar, hafif gölgeler; renkler güvenlik sarısı, antrasit gri, lacivert ve az miktarda turuncu; açık gri-beyaz sade arka plan, arkada çok soluk bir çalışma ortamı silueti. '
            .'Makine genel bir model olsun ve tamamı görünsün, kenarlarda boşluk kalsın. YASAK: harf, kelime, rakam, plaka, etiket yazısı, marka, logo, filigran, imza. '
            .'Gerçek bir firmaya ait renk düzeni de kullanma. Oran: 4:3 yatay, yüksek çözünürlük.';
    }

    /**
     * Gemini'nin JSON cevabını kurar.
     *
     * @return array{kurulan: array<int, string>, hatalar: array<int, string>}
     */
    public static function kur(string $metin): array
    {
        $veri = static::jsonAyikla($metin);
        if ($veri === null) {
            return ['kurulan' => [], 'hatalar' => ['Cevapta JSON dizisi bulunamadı. Gemini\'nin verdiği metnin tamamını (köşeli parantezle başlayan) yapıştırın.']];
        }

        $sonuc = ['kurulan' => [], 'hatalar' => []];
        foreach ($veri as $i => $m) {
            $ad = Str::limit(trim((string) ($m['makine'] ?? '')), 150, '');
            if (! is_array($m) || $ad === '') {
                $sonuc['hatalar'][] = ($i + 1).'. kayıtta makine adı yok';

                continue;
            }
            if (array_key_exists($ad, config('ekipman_katalogu.tipler', [])) && ! OzelEkipmanTipi::query()->where('ad', $ad)->exists()) {
                $sonuc['hatalar'][] = "{$ad}: sistemde zaten var, atlandı";

                continue;
            }
            try {
                DB::transaction(fn () => static::makineKur($ad, $m));
                $sonuc['kurulan'][] = $ad;
            } catch (Throwable $e) {
                report($e);
                $sonuc['hatalar'][] = "{$ad}: kurulamadı ({$e->getMessage()})";
            }
        }

        static::onbellegiTemizle();
        static::configeEkle();
        foreach ($sonuc['kurulan'] as $ad) {
            EkipmanTalimatTaslagi::firmalaraEkle($ad);
        }

        return $sonuc;
    }

    /** @return array<int, array<string, mixed>>|null */
    public static function jsonAyikla(string $metin): ?array
    {
        $metin = trim(preg_replace('/^```(?:json)?|```$/mi', '', trim($metin)));
        $bas = strpos($metin, '[');
        $son = strrpos($metin, ']');
        if ($bas === false || $son === false || $son <= $bas) {
            return null;
        }
        $veri = json_decode(substr($metin, $bas, $son - $bas + 1), true);

        return is_array($veri) && array_is_list($veri) ? $veri : null;
    }

    /** @param  array<string, mixed>  $m */
    private static function makineKur(string $ad, array $m): void
    {
        $kategoriler = array_keys(config('isg.periyodik_kontrol.kategoriler', []));
        $kategori = in_array($m['kategori'] ?? null, $kategoriler, true) ? $m['kategori'] : 'diger_ekipman';
        $metinler = fn ($l, int $max) => array_slice(array_values(array_filter(array_map(fn ($x) => trim((string) $x), (array) $l))), 0, $max);
        $anahtar = static::anahtar((string) ($m['dosya_adi'] ?? '')) ?: static::anahtar($ad);

        $tip = OzelEkipmanTipi::query()->firstOrNew(['ad' => $ad]);
        $tip->fill([
            'anahtar' => $tip->anahtar ?? $anahtar,
            'kategori' => $kategori,
            'periyot_ay' => in_array((int) ($m['periyot_ay'] ?? 12), [3, 6, 12, 24, 36, 60], true) ? (int) $m['periyot_ay'] : 12,
            'standart' => Str::limit(trim((string) ($m['standart'] ?? '')), 190, ''),
            'deney' => Str::limit(trim((string) ($m['deney'] ?? '')), 190, ''),
            'kontrol_sorulari' => $metinler($m['kontrol_sorulari'] ?? [], 20),
            'resim_sahnesi' => trim((string) ($m['resim_sahnesi'] ?? '')) ?: null,
            'yz_taslak' => true,
            'onaylandi_at' => null,
            'onaylayan_id' => null,
        ])->save();

        // Talimat — tipte varsa dokunulmaz.
        $bolumler = collect((array) ($m['talimat'] ?? []))
            ->filter(fn ($b) => is_array($b) && filled($b['baslik'] ?? null))
            ->map(fn ($b) => ['baslik' => trim((string) $b['baslik']), 'aciklama' => trim((string) ($b['aciklama'] ?? '')), 'maddeler' => $metinler($b['maddeler'] ?? [], 12)])
            ->values()->all();
        if ($bolumler) {
            EkipmanTalimatTaslagi::kaydet($ad, $bolumler);
        }

        static::riskleriKur($tip, (array) ($m['riskler'] ?? []));

        $afis = (array) ($m['afis'] ?? []);
        $kkdListe = array_keys(config('ekipman_afis.kkd', []));
        $kkdNorm = array_combine(array_map([EkipmanKatalogu::class, 'norm'], $kkdListe), $kkdListe);
        EkipmanAfisi::query()->updateOrCreate(['anahtar' => $tip->anahtar], [
            'ad' => $ad,
            'grup' => self::AFIS_GRUBU,
            'yapin' => $metinler($afis['yapin'] ?? [], 8) ?: null,
            'yapmayin' => $metinler($afis['yapmayin'] ?? [], 6) ?: null,
            'kkd' => array_values(array_unique(array_filter(array_map(fn ($k) => $kkdNorm[EkipmanKatalogu::norm((string) $k)] ?? null, (array) ($afis['kkd'] ?? []))))),
            'yz_taslak' => true,
            'onaylandi_at' => null,
        ]);
    }

    /** Risk klasörü (tehlike_kategorileri, ekipman_tipleri bağıyla) — tipte bağlı klasör varsa maddeler eklenmez. */
    private static function riskleriKur(OzelEkipmanTipi $tip, array $riskler): void
    {
        if (EkipmanKatalogu::kutuphaneBaglari()[$tip->ad] ?? false) {
            return;
        }
        $riskler = array_values(array_filter($riskler, fn ($r) => is_array($r) && filled($r['tehlike'] ?? null)));
        if ($riskler === []) {
            return;
        }

        $kategori = TehlikeKategorisi::create([
            'ad' => 'Ekipman – '.$tip->ad,
            'anahtar' => 'ekipman_'.Str::limit(str_replace('-', '_', $tip->anahtar), 60, '').'_'.Str::lower(Str::random(4)),
            'ekipman_tipleri' => [$tip->ad],
            'yontem' => 'fine_kinney',
            'nace_gruplari' => ['ortak'],
            'sira' => (int) TehlikeKategorisi::query()->max('sira') + 1,
        ]);

        $onek = Str::upper(Str::substr(Str::ascii(str_replace(['-', ' '], '', $tip->anahtar)), 0, 3)) ?: 'EKP';
        foreach (array_slice($riskler, 0, 20) as $i => $r) {
            Tehlike::create([
                'tehlike_kategorisi_id' => $kategori->id,
                'kod' => $onek.'-YZ-'.str_pad((string) ($i + 1), 3, '0', STR_PAD_LEFT),
                'bolum' => $tip->ad,
                'faaliyet' => Str::limit(trim((string) ($r['faaliyet'] ?? '')), 190, ''),
                'tehlike' => trim((string) $r['tehlike']),
                'risk' => trim((string) ($r['risk'] ?? '')),
                'oneri' => trim((string) ($r['oneri'] ?? '')),
                'sorumlu' => Str::limit(trim((string) ($r['sorumlu'] ?? 'İşveren / İşveren Vekili')), 190, ''),
                'olasilik' => static::yuvarla($r['olasilik'] ?? 1, GeminiEkipmanRiskUretici::FK_OLASILIK),
                'frekans' => static::yuvarla($r['frekans'] ?? 1, GeminiEkipmanRiskUretici::FK_FREKANS),
                'siddet' => static::yuvarla($r['siddet'] ?? 7, GeminiEkipmanRiskUretici::FK_SIDDET),
                'son_olasilik' => static::yuvarla($r['son_olasilik'] ?? 0.5, GeminiEkipmanRiskUretici::FK_OLASILIK),
                'son_frekans' => static::yuvarla($r['son_frekans'] ?? ($r['frekans'] ?? 1), GeminiEkipmanRiskUretici::FK_FREKANS),
                'son_siddet' => static::yuvarla($r['son_siddet'] ?? ($r['siddet'] ?? 7), GeminiEkipmanRiskUretici::FK_SIDDET),
            ]);
        }
    }

    /** @param  array<int, float|int>  $olcek */
    public static function yuvarla(mixed $deger, array $olcek): float
    {
        $d = is_numeric($deger) ? (float) $deger : $olcek[0];

        return (float) collect($olcek)->sortBy(fn ($o) => abs($o - $d))->first();
    }

    /**
     * Kurulmuş tiplerin durumu (sayfa listesi).
     *
     * @return array<int, array<string, mixed>>
     */
    public static function durumlar(): array
    {
        $afisler = EkipmanAfisi::query()->get()->keyBy('anahtar');
        $baglar = EkipmanKatalogu::kutuphaneBaglari();

        return OzelEkipmanTipi::query()->latest('id')->get()->map(function (OzelEkipmanTipi $t) use ($afisler, $baglar) {
            $afis = $afisler->get($t->anahtar);
            $talimat = TalimatSablonu::query()->where('ekipman_tipi', $t->ad)->first();

            return [
                'tip' => $t,
                'form' => count($t->kontrol_sorulari ?? []),
                'talimat' => $talimat,
                'risk' => Tehlike::query()->whereIn('tehlike_kategorisi_id', TehlikeKategorisi::query()->whereIn('anahtar', $baglar[$t->ad] ?? [])->pluck('id'))->count(),
                'afis_yazi' => (bool) $afis?->yaziVarMi(),
                'afis_resim' => (bool) $afis?->gorselVarMi(),
                'kategori' => config('isg.periyodik_kontrol.kategoriler.'.$t->kategori.'.ad', $t->kategori),
            ];
        })->all();
    }

    /** "Paketi onayla": tip + talimat taslağı + afiş yazısı uzman onaylı olur. */
    public static function onayla(OzelEkipmanTipi $t, ?int $kullaniciId): void
    {
        $t->forceFill(['onaylandi_at' => now(), 'onaylayan_id' => $kullaniciId])->save();
        TalimatSablonu::query()->where('ekipman_tipi', $t->ad)->update(['yz_taslak' => false]);
        EkipmanAfisi::query()->where('anahtar', $t->anahtar)->whereNotNull('yapin')
            ->update(['onaylandi_at' => now(), 'onaylayan_id' => $kullaniciId]);
    }
}
