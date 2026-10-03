<?php

namespace App\Support;

use App\Filament\Pages\AcilDurumPlani as AcilDurumPlaniSayfasi;
use App\Filament\Pages\DokumanYonetimi;
use App\Filament\Pages\KimyasalSicili;
use App\Filament\Pages\PkdSicili;
use App\Filament\Pages\SaglikGozetimi as SaglikGozetimiSayfasi;
use App\Filament\Pages\YillikPlan\YillikCalismaPlani;
use App\Filament\Resources\Firmas\FirmaResource;
use App\Filament\Resources\RiskDegerlendirmesis\RiskDegerlendirmesiResource;
use App\Models\AcilDurumPlani;
use App\Models\ArsivDosya;
use App\Models\Bildirim;
use App\Models\Firma;
use App\Models\KimyasalUrun;
use App\Models\PkdKaydi;
use App\Models\RiskDegerlendirmesi;
use App\Models\SaglikGozetimi;
use App\Models\User;
use App\Models\YillikPlan;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Throwable;

/**
 * Bildirim Merkezi taraması (isgsuite "Süreleri Kontrol Et"): görevlendirme /
 * sözleşme bitişi, İSG-KATİP no eksikliği, atanmamış profesyonel, doküman
 * geçerliliği (risk değerlendirmesi, acil durum planı), sağlık muayenesi,
 * geciken yıllık plan, SDS / PKD gözden geçirme terminleri ve Ana Sayfa'daki
 * tarihli takipler (periyodik kontrol, KKD, ortam ölçümü, DÖF, eğitim).
 *
 * Her uyarı `anahtar` ile tekildir; tarama yeniden yazar, artık geçerli
 * olmayan açık bildirimleri "çözüldü" diye kapatır. Sağlık bildirimleri
 * yalnız sayı verir — çalışan adı / klinik bilgi içermez.
 */
class BildirimTarayici
{
    /** Taramadan sonra bu kadar dakika geçmeden sayfa açılışında yeniden taranmaz. */
    public const OTOMATIK_TARAMA_DK = 60;

    /** @return array{yeni: int, acik: int, cozulen: int} */
    public static function tara(User $kullanici, ?int $firmaId = null): array
    {
        $firmalar = Firma::query()
            ->where('user_id', $kullanici->id)
            ->where('aktif', true)
            ->when($firmaId, fn ($q) => $q->whereKey($firmaId))
            ->get();

        $bulunan = $firmalar->flatMap(fn (Firma $f) => static::firmaBildirimleri($f))->keyBy('anahtar');

        $yeni = 0;
        foreach ($bulunan as $anahtar => $b) {
            $kayit = Bildirim::firstOrNew(['user_id' => $kullanici->id, 'anahtar' => $anahtar]);
            $yeniMi = ! $kayit->exists || $kayit->cozuldu_at !== null;
            $kayit->fill([...$b, 'cozuldu_at' => null]);
            if ($yeniMi) {
                $kayit->okundu_at = null;
                $yeni++;
            }
            $kayit->save();
        }

        $cozulen = Bildirim::query()
            ->where('user_id', $kullanici->id)
            ->acik()
            ->when($firmaId, fn ($q) => $q->where('firma_id', $firmaId))
            ->whereNotIn('anahtar', $bulunan->keys())
            ->update(['cozuldu_at' => now()]);

        return ['yeni' => $yeni, 'acik' => $bulunan->count(), 'cozulen' => $cozulen];
    }

    /** Son taramadan bu yana OTOMATIK_TARAMA_DK geçtiyse tarar. */
    public static function gerekirseTara(User $kullanici): void
    {
        $son = Bildirim::query()->where('user_id', $kullanici->id)->max('updated_at');

        if (! $son || Carbon::parse($son)->lt(now()->subMinutes(static::OTOMATIK_TARAMA_DK))) {
            static::tara($kullanici);
        }
    }

    /** @return Collection<int, array<string, mixed>> */
    public static function firmaBildirimleri(Firma $firma): Collection
    {
        $liste = collect();
        $bugun = Carbon::today();
        $esik = 30;
        $ekle = function (string $anahtar, string $seviye, string $baslik, string $aciklama, ?Carbon $tarih = null, ?string $url = null) use ($liste, $firma): void {
            $liste->push([
                'anahtar' => 'f'.$firma->id.':'.$anahtar,
                'firma_id' => $firma->id,
                'seviye' => $seviye,
                'baslik' => $baslik,
                'aciklama' => $aciklama,
                'tarih' => $tarih?->toDateString(),
                'url' => $url,
            ]);
        };
        $firmaUrl = static::url(fn () => FirmaResource::getUrl('edit', ['record' => $firma]));

        // Görevlendirme / sözleşme bitişi
        if ($firma->sozlesme_bitis) {
            $bitis = Carbon::parse($firma->sozlesme_bitis);
            if ($bitis->lt($bugun)) {
                $ekle('sozlesme:gecti', 'kritik', 'Görevlendirme / sözleşme süresi doldu', 'Sözleşme '.$bitis->format('d.m.Y').' tarihinde bitti; İSG-KATİP görevlendirmesini ve sözleşmeyi yenileyin.', $bitis, $firmaUrl);
            } elseif ($bitis->lte($bugun->copy()->addDays($esik))) {
                $ekle('sozlesme:yakin', 'uyari', 'Görevlendirme / sözleşme bitişi yaklaşıyor', 'Sözleşme '.$bitis->format('d.m.Y').' tarihinde bitiyor ('.$bugun->diffInDays($bitis).' gün).', $bitis, $firmaUrl);
            }
        } else {
            $ekle('sozlesme:yok', 'bilgi', 'Sözleşme bitiş tarihi girilmemiş', 'Süre takibi için firma kaydına sözleşme bitiş tarihini girin.', null, $firmaUrl);
        }

        // İSG-KATİP no ve atanmamış profesyonel
        if (blank($firma->katip_no)) {
            $ekle('katip', 'uyari', 'İSG-KATİP işyeri no eksik', 'Firma kaydında İSG-KATİP işyeri numarası yok.', null, $firmaUrl);
        }
        if (! $firma->igu_id) {
            $ekle('igu', 'kritik', 'İş güvenliği uzmanı atanmamış', '6331 sayılı Kanun Md.6 — işyerine iş güvenliği uzmanı görevlendirilmelidir.', null, $firmaUrl);
        }
        if (! $firma->isyeri_hekimi_id) {
            $ekle('hekim', 'kritik', 'İşyeri hekimi atanmamış', '6331 sayılı Kanun Md.6 — işyerine işyeri hekimi görevlendirilmelidir.', null, $firmaUrl);
        }

        // Doküman geçerliliği
        $rd = RiskDegerlendirmesi::query()->where('firma_id', $firma->id)->whereNotNull('gecerlilik_tarihi')->latest('gecerlilik_tarihi')->first();
        $rdUrl = static::url(fn () => RiskDegerlendirmesiResource::getUrl('index'));
        if ($rd && $rd->gecerlilik_tarihi->lt($bugun)) {
            $ekle('risk:gecti', 'kritik', 'Risk değerlendirmesinin geçerliliği doldu', 'Son risk değerlendirmesi '.$rd->gecerlilik_tarihi->format('d.m.Y').' tarihinde geçerliliğini yitirdi; yenileyin (6331 Md.10).', $rd->gecerlilik_tarihi, $rdUrl);
        } elseif ($rd && $rd->gecerlilik_tarihi->lte($bugun->copy()->addDays($esik))) {
            $ekle('risk:yakin', 'uyari', 'Risk değerlendirmesi yenileme tarihi yaklaşıyor', 'Geçerlilik '.$rd->gecerlilik_tarihi->format('d.m.Y').' tarihinde bitiyor.', $rd->gecerlilik_tarihi, $rdUrl);
        }

        $adp = AcilDurumPlani::query()->where('firma_id', $firma->id)->first();
        $adpUrl = static::url(fn () => AcilDurumPlaniSayfasi::getUrl(['firma' => $firma->id]));
        if ($adp?->gecerlilik_tarihi && $adp->gecerlilik_tarihi->lt($bugun)) {
            $ekle('adp:gecti', 'kritik', 'Acil durum planının geçerliliği doldu', 'Plan '.$adp->gecerlilik_tarihi->format('d.m.Y').' tarihinde geçerliliğini yitirdi; yenileyin.', $adp->gecerlilik_tarihi, $adpUrl);
        } elseif ($adp?->gecerlilik_tarihi && $adp->gecerlilik_tarihi->lte($bugun->copy()->addDays($esik))) {
            $ekle('adp:yakin', 'uyari', 'Acil durum planı yenileme tarihi yaklaşıyor', 'Geçerlilik '.$adp->gecerlilik_tarihi->format('d.m.Y').' tarihinde bitiyor.', $adp->gecerlilik_tarihi, $adpUrl);
        }

        // Sağlık muayenesi — yalnız sayı (klinik bilgi / ad yok)
        $sg = collect(SaglikGozetimi::query()->where('firma_id', $firma->id)->value('satirlar') ?? [])
            ->pluck('sonraki_tarih')->filter()->map(fn ($t) => static::tarih($t))->filter();
        $sgUrl = static::url(fn () => SaglikGozetimiSayfasi::getUrl(['firma' => $firma->id]));
        $sgGecmis = $sg->filter(fn (Carbon $t) => $t->lt($bugun));
        $sgYakin = $sg->filter(fn (Carbon $t) => $t->gte($bugun) && $t->lte($bugun->copy()->addDays($esik)));
        if ($sgGecmis->isNotEmpty()) {
            $ekle('saglik:gecti', 'kritik', 'Periyodik sağlık muayenesi gecikmiş', $sgGecmis->count().' tetkikin tarihi geçti (ayrıntı işyeri hekimi kaydında).', $sgGecmis->min(), $sgUrl);
        }
        if ($sgYakin->isNotEmpty()) {
            $ekle('saglik:yakin', 'uyari', 'Periyodik sağlık muayenesi yaklaşıyor', $sgYakin->count().' tetkikin tarihi '.$esik.' gün içinde.', $sgYakin->min(), $sgUrl);
        }

        // Geciken yıllık plan: bu yılın geçmiş aylarında planlanıp gerçekleşmeyen maddeler
        $plan = YillikPlan::query()->where('firma_id', $firma->id)->where('yil', $bugun->year)->first();
        if ($plan) {
            $gecikenler = collect(range(0, $bugun->month - 2))
                ->filter(fn ($ay) => $ay >= 0)
                ->sum(fn (int $ay) => collect($plan->ayinYapilacaklari($ay))->where('gerceklesti', false)->count());
            if ($gecikenler > 0) {
                $ekle('yillik_plan', 'uyari', 'Geciken yıllık plan maddeleri', $gecikenler.' madde geçmiş aylarda planlandı ama "Gerçekleşti" işaretlenmedi.', null, static::url(fn () => YillikCalismaPlani::getUrl(['firma' => $firma->id])));
            }
        }

        // SDS / PKD gözden geçirme
        $sds = KimyasalUrun::query()->where('firma_id', $firma->id)->where('aktif', true)->whereNotNull('sonraki_gozden_gecirme')
            ->whereDate('sonraki_gozden_gecirme', '<=', $bugun->copy()->addDays($esik))->get(['urun_adi', 'sonraki_gozden_gecirme']);
        if ($sds->isNotEmpty()) {
            $ilk = $sds->min('sonraki_gozden_gecirme');
            $ekle('sds', Carbon::parse($ilk)->lt($bugun) ? 'kritik' : 'uyari', 'SDS gözden geçirme termini', $sds->count().' kimyasalın güvenlik bilgi formu gözden geçirilmeli: '.Str::limit($sds->pluck('urun_adi')->implode(', '), 120).'.', Carbon::parse($ilk), static::url(fn () => KimyasalSicili::getUrl(['firma' => $firma->id])));
        }
        $pkd = PkdKaydi::query()->where('firma_id', $firma->id)->whereNotNull('sonraki_gozden_gecirme')
            ->whereDate('sonraki_gozden_gecirme', '<=', $bugun->copy()->addDays($esik))->min('sonraki_gozden_gecirme');
        if ($pkd) {
            $ekle('pkd', Carbon::parse($pkd)->lt($bugun) ? 'kritik' : 'uyari', 'Patlamadan korunma dokümanı gözden geçirme termini', 'PKD '.Carbon::parse($pkd)->format('d.m.Y').' tarihinde gözden geçirilmeli.', Carbon::parse($pkd), static::url(fn () => PkdSicili::getUrl(['firma' => $firma->id])));
        }

        // Arşiv: geçerlilik sonu dolan / yaklaşan "kayıt" türü belgeler…
        $dokumanlar = ArsivDosya::query()->where('firma_id', $firma->id)->tarihTakipli()->whereNotNull('gecerlilik_sonu')
            ->whereDate('gecerlilik_sonu', '<=', $bugun->copy()->addDays($esik))->orderBy('gecerlilik_sonu')->get();
        // …ve kural kategorilerinde (periyodik / yıllık) yenilemesi geciken / yaklaşan belge.
        // Yalnız arşivde o kategoride kaydı olan firmalar — arşivi hiç kullanmayan
        // firmaya her kategori için "eksik" bildirimi yağdırılmaz (Arşiv sayfası gösterir).
        $arsivKayitlari = ArsivDosya::query()->where('firma_id', $firma->id)->get()->groupBy(fn (ArsivDosya $d) => ArsivKurali::kategori($d->kategori)['anahtar']);
        foreach ($arsivKayitlari as $kategori => $kayitlar) {
            if (! ArsivKurali::takipliMi($kategori)) {
                continue;
            }
            $durum = ArsivKurali::durum($firma, $kategori, $kayitlar, $bugun, KullaniciAyarlari::arsivHaric($firma->user));
            if (in_array($durum['durum'], ['gecikmis', 'yaklasan'], true)) {
                $ekle('arsiv:'.$kategori.':'.$durum['durum'], $durum['durum'] === 'gecikmis' ? 'kritik' : 'uyari', $durum['baslik'].($durum['durum'] === 'gecikmis' ? ' gecikti' : ' yaklaşıyor'), $durum['mesaj'], $durum['son_tarih'] ?? $bugun, static::url(fn () => DokumanYonetimi::getUrl(['firma' => $firma->id, 'kategori' => $kategori])));
            }
        }
        $dokUrl = static::url(fn () => DokumanYonetimi::getUrl(['firma' => $firma->id]));
        $dolan = $dokumanlar->filter(fn (ArsivDosya $d) => $d->gecerlilik_sonu->lt($bugun));
        $yakin = $dokumanlar->filter(fn (ArsivDosya $d) => $d->gecerlilik_sonu->gte($bugun));
        if ($dolan->isNotEmpty()) {
            $ekle('dokuman:gecti', 'kritik', 'Doküman geçerliliği doldu', $dolan->count().' doküman: '.Str::limit($dolan->map(fn ($d) => $d->etiket())->implode(', '), 120).'.', $dolan->first()->gecerlilik_sonu, $dokUrl);
        }
        if ($yakin->isNotEmpty()) {
            $ekle('dokuman:yakin', 'uyari', 'Doküman geçerliliği yaklaşıyor', $yakin->count().' doküman: '.Str::limit($yakin->map(fn ($d) => $d->etiket())->implode(', '), 120).'.', $yakin->first()->gecerlilik_sonu, $dokUrl);
        }

        // Ana Sayfa'daki tarihli takipler (periyodik kontrol, KKD, ortam, DÖF, eğitim)
        foreach (GorevDurumu::tarihliGorevler($firma) as $g) {
            $ekle(
                'gorev:'.Str::slug($g['baslik']).':'.$g['durum'],
                $g['durum'] === 'gecikmis' ? 'kritik' : 'uyari',
                $g['baslik'].' — '.GorevDurumu::DURUMLAR[$g['durum']],
                $g['aciklama'],
                $g['termin'],
                $g['url'],
            );
        }

        return $liste;
    }

    private static function url(callable $uret): ?string
    {
        try {
            return $uret();
        } catch (Throwable) {
            return null;
        }
    }

    private static function tarih(mixed $deger): ?Carbon
    {
        try {
            return Carbon::parse($deger);
        } catch (Throwable) {
            return null;
        }
    }
}
