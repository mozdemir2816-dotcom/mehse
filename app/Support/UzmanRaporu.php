<?php

namespace App\Support;

use App\Filament\Pages\EgitimYenilemeTakibi;
use App\Filament\Pages\Mevzuat;
use App\Models\Firma;
use App\Models\KkdZimmet;
use App\Models\OlayKaydi;
use App\Models\RiskDegerlendirmesi;
use Illuminate\Support\Collection;
use Throwable;

/**
 * Uzman Rapor Merkezi (isgsuite "Uzman Rapor Merkezi"): seçili işyerinin
 * sağlık dışı İSG görünümü — göstergeler, işyeri uygunluk özeti (yasal
 * dayanaklarıyla) ve kalem bazında öncelikli aksiyonlar. Klinik sağlık
 * kayıtları bu rapora ve uzman rolüne dahil edilmez. Hesaplar
 * IsyeriDurumu / EgitimTakibi ile aynı kaynaktan gelir.
 */
class UzmanRaporu
{
    /** Sağlık kategorisi uzman raporuna girmez. */
    private const HARIC_KATEGORILER = ['Sağlık'];

    /** @return array{ad: string, url: string, resmi: bool} */
    public static function dayanak(string $kategori): array
    {
        $d = config('isg.uzman_raporu.dayanaklar.'.$kategori) ?? config('isg.uzman_raporu.dayanaklar.Süreç');
        $mevzuatUrl = static::guvenli(fn () => Mevzuat::getUrl(), '#');

        return ['ad' => $d['ad'], 'url' => $d['url'] ?? $mevzuatUrl, 'resmi' => filled($d['url'] ?? null)];
    }

    public static function acikRiskSayisi(Firma $firma): int
    {
        $rd = RiskDegerlendirmesi::query()->where('firma_id', $firma->id)->latest('id')->first();

        $yuksek = config('isg.uzman_raporu.acik_risk_duzeyleri', []);

        // Önlem sonrası (kalan) düzeyi hâlâ yüksek olan; kalan puanı yoksa ilk düzeyi yüksek olan maddeler.
        return $rd ? $rd->maddeler()
            ->where(fn ($q) => $q->whereIn('son_duzey', $yuksek)->orWhere(fn ($q) => $q->whereNull('son_duzey')->whereIn('duzey', $yuksek)))
            ->count() : 0;
    }

    /**
     * Kalem bazında öncelikli aksiyonlar: termini geçmiş / 30 gün içinde
     * olan yükümlülükler, temel eğitimi olmayan çalışanlar ve eksik süreçler.
     *
     * @return Collection<int, array{baslik: string, alt: string, durum: string, tarih: ?\Illuminate\Support\Carbon, kategori: string, url: ?string, modul: string, dayanak: array}>
     */
    public static function aksiyonlar(Firma $firma): Collection
    {
        $liste = collect();

        foreach (IsyeriDurumu::takvim($firma) as $t) {
            if (in_array($t['kategori'], static::HARIC_KATEGORILER, true) || ! in_array($t['durum'], ['gecikmis', 'cok_yakin', 'yaklasiyor'], true)) {
                continue;
            }
            $liste->push([
                'baslik' => $t['kategori'].' — '.$t['kayit'],
                'alt' => trim($firma->unvan.($t['alt'] ? ' · '.$t['alt'] : '').' · Termin '.$t['tarih']->format('d.m.Y').' ('.IsyeriDurumu::kalanMetni($t['tarih']).')'),
                'durum' => $t['durum'],
                'tarih' => $t['tarih'],
                'kategori' => $t['kategori'],
                'url' => $t['url'],
                'modul' => $t['kategori'],
                'dayanak' => static::dayanak($t['kategori']),
            ]);
        }

        $egitimUrl = static::guvenli(fn () => EgitimYenilemeTakibi::getUrl(['firma' => $firma->id]), null);
        foreach (EgitimTakibi::satirlar($firma)->where('durum', 'kayit_yok') as $s) {
            $liste->push([
                'baslik' => 'Eğitim kaydı eksik — '.$s['calisan']->ad_soyad,
                'alt' => $firma->unvan.' · Aktif çalışan için tamamlanmış ve katılımı doğrulanmış temel eğitim bulunamadı.'
                    .($s['ilk_son_tarih'] ? ' İşe girişten 3 ay: '.$s['ilk_son_tarih']->format('d.m.Y').'.' : ''),
                'durum' => $s['ilk_kalan_gun'] !== null && $s['ilk_kalan_gun'] < 0 ? 'gecikmis' : 'eksik',
                'tarih' => $s['ilk_son_tarih'],
                'kategori' => 'Eğitim',
                'url' => $egitimUrl,
                'modul' => 'Eğitimler',
                'dayanak' => static::dayanak('Eğitim'),
            ]);
        }

        foreach (IsyeriDurumu::surecler($firma) as $s) {
            if ($s['durum'] !== 'eksik' || $s['surec'] === 'Sağlık gözetimi' || str_contains($s['surec'], 'eğitim')) {
                continue;
            }
            $liste->push([
                'baslik' => $s['surec'].' — eksik',
                'alt' => $firma->unvan.' · '.$s['sonuc'],
                'durum' => 'eksik',
                'tarih' => null,
                'kategori' => 'Süreç',
                'url' => $s['url'],
                'modul' => $s['surec'],
                'dayanak' => static::dayanak(static::surecKategorisi($s['surec'])),
            ]);
        }

        $sira = ['gecikmis' => 0, 'cok_yakin' => 1, 'yaklasiyor' => 2, 'eksik' => 3];

        return $liste->sortBy(fn ($a) => [$sira[$a['durum']] ?? 4, $a['tarih']?->timestamp ?? PHP_INT_MAX])->values();
    }

    /** @return array<string, mixed> */
    public static function rapor(Firma $firma): array
    {
        $surecler = collect(IsyeriDurumu::surecler($firma))->reject(fn ($s) => $s['surec'] === 'Sağlık gözetimi')->values();
        $takvim = IsyeriDurumu::takvim($firma)->reject(fn ($t) => in_array($t['kategori'], static::HARIC_KATEGORILER, true));
        $aksiyonlar = static::aksiyonlar($firma);
        $basarisiz = $surecler->whereIn('durum', ['eksik', 'gecikmis']);

        $dayanaklar = $basarisiz->map(fn ($s) => static::dayanak(static::surecKategorisi($s['surec'])))
            ->merge($aksiyonlar->pluck('dayanak'))
            ->unique('ad')->values();

        return [
            'gostergeler' => [
                'isyeri' => 1,
                'aktif_calisan' => $firma->calisanlar()->where('aktif', true)->count(),
                'acik_risk' => static::acikRiskSayisi($firma),
                'acik_olay' => OlayKaydi::query()->where('firma_id', $firma->id)->where(fn ($q) => $q->whereNull('durum')->orWhere('durum', 'acik'))->count(),
                'kkd_zimmet' => KkdZimmet::query()->where('firma_id', $firma->id)->where('durum', 'teslim_edildi')->count(),
                'yaklasan_geciken' => $takvim->whereIn('durum', ['gecikmis', 'cok_yakin', 'yaklasiyor'])->count(),
            ],
            'uygunluk' => [
                'durum' => $basarisiz->isEmpty() ? 'Uygun' : 'İşlem gerekli',
                'basarisiz' => $basarisiz->count(),
                'yaklasan_geciken' => $takvim->whereIn('durum', ['gecikmis', 'cok_yakin', 'yaklasiyor'])->count(),
                'dayanaklar' => $dayanaklar->all(),
            ],
            'aksiyonlar' => $aksiyonlar,
        ];
    }

    public static function txt(Firma $firma): string
    {
        $r = static::rapor($firma);
        $g = $r['gostergeler'];
        $u = $r['uygunluk'];

        $satirlar = [
            'UZMAN RAPOR MERKEZİ',
            $firma->unvan.' · NACE '.($firma->nace_kodu ?: '—').' · '.$firma->tehlikeSinifiEtiketi(),
            'Rapor tarihi: '.now()->format('d.m.Y H:i'),
            'Kapsam: sağlık dışı İSG görünümü — klinik sağlık kayıtları dahil değildir.',
            '',
            "Aktif çalışan: {$g['aktif_calisan']} · Açık risk: {$g['acik_risk']} · Açık olay: {$g['acik_olay']} · KKD zimmet: {$g['kkd_zimmet']} · Yaklaşan/geciken: {$g['yaklasan_geciken']}",
            "Uygunluk: {$u['durum']} — {$u['basarisiz']} başarısız süreç, {$u['yaklasan_geciken']} yaklaşan/geciken kayıt",
            '',
            '== YASAL DAYANAKLAR ==',
        ];
        foreach ($u['dayanaklar'] as $d) {
            $satirlar[] = '- '.$d['ad'].($d['resmi'] ? ' — '.$d['url'] : '');
        }
        $satirlar[] = '';
        $satirlar[] = '== ÖNCELİKLİ AKSİYONLAR ('.$r['aksiyonlar']->count().') ==';
        foreach ($r['aksiyonlar'] as $a) {
            $satirlar[] = '- '.$a['baslik'].' | '.$a['alt'].' | Dayanak: '.$a['dayanak']['ad'];
        }

        return implode("\r\n", $satirlar)."\r\n";
    }

    private static function surecKategorisi(string $surec): string
    {
        return match (true) {
            str_contains($surec, 'Risk') => 'Risk',
            str_contains($surec, 'Acil durum planı') => 'Acil durum',
            str_contains($surec, 'tatbikat') => 'Tatbikat',
            str_contains($surec, 'KKD') => 'KKD',
            str_contains($surec, 'Periyodik') => 'Periyodik kontrol',
            str_contains($surec, 'eğitim') => 'Eğitim',
            str_contains($surec, 'SDS') => 'SDS',
            str_contains($surec, 'Kurul') => 'İSG Kurulu',
            str_contains($surec, 'kaza') => 'Olay / SGK bildirimi',
            str_contains($surec, 'Düzeltici') => 'DÖF',
            str_contains($surec, 'Ortam') => 'Ortam ölçümü',
            default => 'Süreç',
        };
    }

    private static function guvenli(callable $f, mixed $varsayilan): mixed
    {
        try {
            return $f();
        } catch (Throwable) {
            return $varsayilan;
        }
    }
}
