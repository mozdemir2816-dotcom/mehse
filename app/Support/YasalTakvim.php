<?php

namespace App\Support;

use App\Filament\Pages\AcilDurumPlani as AcilDurumPlaniSayfasi;
use App\Filament\Pages\EgitimYenilemeTakibi;
use App\Filament\Pages\IsgKatipSozlesmeleri;
use App\Filament\Pages\KkdTakip;
use App\Filament\Pages\KurulToplantisi as KurulToplantisiSayfasi;
use App\Filament\Pages\OrtamOlcumleri;
use App\Filament\Pages\PeriyodikKontrol;
use App\Filament\Pages\SaglikGozetimi;
use App\Filament\Pages\TatbikatTutanagi as TatbikatSayfasi;
use App\Filament\Resources\RiskDegerlendirmesis\RiskDegerlendirmesiResource;
use App\Models\Firma;
use App\Models\IsgKatipSozlesmesi;
use App\Models\KkdZimmet;
use App\Models\RiskDegerlendirmesi;
use App\Models\TatbikatTutanagi;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Throwable;

/**
 * Portföy yasal takvimi (13.11.2026): tüm aktif firmaların süreli yükümlülükleri tek listede —
 * süresi geçmiş olanlar ve önümüzdeki N gün içinde dolanlar. Kontrol Merkezi > Günlük Akış kullanır.
 *
 * Kaynaklar: risk değerlendirmesi ve acil durum planı geçerliliği, yıllık tatbikat, İSG kurulu
 * (50+ çalışan), temel eğitim yenilemeleri, periyodik kontrol, sağlık gözetimi, ortam ölçümü,
 * KKD yenileme, İSG-KATİP sözleşme bitişi.
 */
class YasalTakvim
{
    /**
     * @return Collection<int, array{firma_id: int, firma: string, tur: string, baslik: string, aciklama: string, tarih: ?Carbon, kalan_gun: ?int, durum: string, url: ?string}>
     */
    public static function kalemler(int $userId, int $gun = 90): Collection
    {
        $bugun = Carbon::today();
        $sinir = $bugun->copy()->addDays($gun);
        $kalemler = collect();

        $firmalar = Firma::query()->where('user_id', $userId)->where('aktif', true)->orderBy('unvan')->get();

        foreach ($firmalar as $firma) {
            foreach (static::firmaKalemleri($firma) as $k) {
                // Tarihsiz kalem = hiç yapılmamış zorunlu iş → gecikmiş sayılır.
                if ($k['tarih'] === null || $k['tarih']->lte($sinir)) {
                    $kalan = $k['tarih'] ? (int) $bugun->diffInDays($k['tarih']->copy()->startOfDay(), false) : null;
                    $kalemler->push([
                        'firma_id' => $firma->id,
                        'firma' => (string) $firma->unvan,
                        ...$k,
                        'kalan_gun' => $kalan,
                        'durum' => $kalan === null || $kalan < 0 ? 'gecikti' : 'yaklasiyor',
                    ]);
                }
            }
        }

        return $kalemler->sortBy(fn (array $k) => [$k['tarih']?->timestamp ?? 0, $k['firma']])->values();
    }

    /**
     * Firmanın sıradaki yasal tarihleri (pencere uygulanmadan).
     *
     * @return array<int, array{tur: string, baslik: string, aciklama: string, tarih: ?Carbon, url: ?string}>
     */
    public static function firmaKalemleri(Firma $firma): array
    {
        $k = [];
        $ekle = function (string $tur, string $baslik, string $aciklama, ?Carbon $tarih, ?string $url) use (&$k): void {
            $k[] = compact('tur', 'baslik', 'aciklama', 'tarih', 'url');
        };

        // Risk değerlendirmesi (yürürlükteki esas alınır)
        $rd = RiskDegerlendirmesi::gecerliOlan($firma->id);
        $rdUrl = static::url(fn () => RiskDegerlendirmesiResource::getUrl('index'));
        if (! $rd) {
            $ekle('risk', 'Risk değerlendirmesi yok', '6331 sayılı Kanun md.10 — risk değerlendirmesi yapılmalı.', null, $rdUrl);
        } elseif ($rd->gecerlilik_tarihi) {
            $ekle('risk', 'Risk değerlendirmesi yenileme', $rd->belge_no.' geçerlilik sonu (tehlike sınıfına göre 2 / 4 / 6 yıl).', $rd->gecerlilik_tarihi->copy(), $rdUrl);
        }

        // Acil durum planı
        $adp = $firma->acilDurumPlani;
        if ($adp && filled($adp->konular) && $adp->gecerlilik_tarihi) {
            $ekle('acil', 'Acil durum planı yenileme', 'Acil Durumlar Yönetmeliği md.12 — plan geçerlilik sonu.', Carbon::parse($adp->gecerlilik_tarihi), static::url(fn () => AcilDurumPlaniSayfasi::getUrl(['firma' => $firma->id])));
        }

        // Yıllık tatbikat: son yapılan tatbikat + 1 yıl; hiç yoksa sözleşme başlangıcı + 1 yıl
        $sonTatbikat = $firma->tatbikatTutanaklari()->whereIn('durum', TatbikatTutanagi::YAPILMIS)->max('tatbikat_tarihi');
        $tatbikatVade = $sonTatbikat
            ? Carbon::parse($sonTatbikat)->addYear()
            : ($firma->sozlesme_baslangic ? Carbon::parse($firma->sozlesme_baslangic)->addYear() : null);
        if ($sonTatbikat || $tatbikatVade) {
            $ekle('acil', 'Acil durum tatbikatı', $sonTatbikat
                ? 'Son tatbikat '.Carbon::parse($sonTatbikat)->format('d.m.Y').' — yılda en az bir kez (Acil Durumlar Yönetmeliği md.13).'
                : 'Henüz tatbikat kaydı yok — yılda en az bir kez (Acil Durumlar Yönetmeliği md.13).', $tatbikatVade, static::url(fn () => TatbikatSayfasi::getUrl(['firma' => $firma->id])));
        }

        // İSG Kurulu (50 ve üzeri çalışan)
        if ((int) $firma->calisan_sayisi >= 50) {
            $son = $firma->kurulToplantilari()->latest('tarih')->first();
            $kurulUrl = static::url(fn () => KurulToplantisiSayfasi::getUrl(['firma' => $firma->id]));
            $ekle('kurul', 'İSG Kurulu toplantısı', 'İSG Kurulları Yönetmeliği — '.KurulUyeleri::periyotEtiketi($firma).' toplanır.',
                $son ? ($son->sonraki_toplanti?->copy() ?? KurulUyeleri::sonrakiToplanti($firma, $son->tarih)) : null, $kurulUrl);
        }

        // Temel eğitim yenilemeleri — firma başına en erken tarih + kişi sayısı
        try {
            $egitim = collect(EgitimTakibi::satirlar($firma))->filter(fn (array $s) => $s['yenileme'] !== null && in_array($s['durum'], ['dolmus', 'yaklasan'], true));
        } catch (Throwable) {
            $egitim = collect();
        }
        if ($egitim->isNotEmpty()) {
            $ekle('egitim', 'Temel eğitim yenileme', $egitim->count().' çalışanın temel İSG eğitimi yenilenmeli (İSG Eğitimleri Yönetmeliği).', $egitim->min('yenileme')->copy(), static::url(fn () => EgitimYenilemeTakibi::getUrl(['firma' => $firma->id])));
        }

        // Toplu tarih listeleri (en erken tarih + adet)
        $toplu = function (string $tur, string $baslik, string $birim, Collection $tarihler, ?string $url) use ($ekle): void {
            $tarihler = $tarihler->filter()->map(fn ($t) => Carbon::parse($t))->sort()->values();
            if ($tarihler->isEmpty()) {
                return;
            }
            $ilk = $tarihler->first();
            $ayni = $tarihler->filter(fn (Carbon $t) => $t->lte($ilk->copy()->addDays(30)))->count();
            $ekle($tur, $baslik, $ayni.' '.$birim.($tarihler->count() > $ayni ? ' (toplam '.$tarihler->count().')' : ''), $ilk, $url);
        };

        $toplu('ekipman', 'Periyodik kontrol', 'ekipmanın vizesi', $firma->isEkipmanlari()->where('aktif', true)->pluck('sonraki_vize_tarihi'),
            static::url(fn () => PeriyodikKontrol::getUrl(['firma' => $firma->id])));
        $toplu('saglik', 'Sağlık gözetimi', 'tetkik / muayene', collect($firma->saglikGozetimi?->satirlar ?? [])->pluck('sonraki_tarih'),
            static::url(fn () => SaglikGozetimi::getUrl(['firma' => $firma->id])));
        $toplu('olcum', 'Ortam ölçümü', 'ölçüm', collect($firma->ortamOlcumu?->olcumler ?? [])->pluck('sonraki_olcum_tarihi'),
            static::url(fn () => OrtamOlcumleri::getUrl(['firma' => $firma->id])));
        $toplu('kkd', 'KKD yenileme', 'KKD zimmeti', $firma->kkdZimmetleri()->where('durum', 'teslim_edildi')->get()->map(fn (KkdZimmet $z) => $z->vade()),
            static::url(fn () => KkdTakip::getUrl(['firma' => $firma->id])));

        // İSG-KATİP sözleşmesi
        $sozlesme = IsgKatipSozlesmesi::query()->where('firma_id', $firma->id)->aktif()->whereNotNull('bitis_tarihi')->orderBy('bitis_tarihi')->first();
        if ($sozlesme) {
            $ekle('sozlesme', 'İSG-KATİP sözleşme bitişi', trim(($sozlesme->sertifika_tipi ?: '').' sözleşmesi sona eriyor — yenileme / uzatma.'), Carbon::parse($sozlesme->bitis_tarihi),
                static::url(fn () => IsgKatipSozlesmeleri::getUrl()));
        }

        return $k;
    }

    private static function url(callable $uret): ?string
    {
        try {
            return $uret();
        } catch (Throwable) {
            return null;
        }
    }
}
