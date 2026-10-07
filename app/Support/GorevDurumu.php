<?php

namespace App\Support;

use App\Filament\Pages\AcilDurumPlani;
use App\Filament\Pages\CalisanTemsilcisiSecimi;
use App\Filament\Pages\DofOlustur;
use App\Filament\Pages\EgitimKatilim;
use App\Filament\Pages\EgitimYenilemeTakibi;
use App\Filament\Pages\IsbasiEgitim;
use App\Filament\Pages\IseDonusBelgesi;
use App\Filament\Pages\IsIzinFormu;
use App\Filament\Pages\KkdTakip;
use App\Filament\Pages\KurulToplantisi;
use App\Filament\Pages\MeslekHastaligiBildirimi;
use App\Filament\Pages\OlayKayitlari;
use App\Filament\Pages\OnayliDefterNushalari;
use App\Filament\Pages\OrtamOlcumleri;
use App\Filament\Pages\PeriyodikKontrol;
use App\Filament\Pages\SaglikGozetimi;
use App\Filament\Pages\SahaDenetimi;
use App\Filament\Pages\TatbikatTutanagi;
use App\Filament\Pages\TespitOneriDefteri;
use App\Filament\Pages\YillikPlan\YillikCalismaPlani;
use App\Filament\Pages\YillikPlan\YillikDegerlendirmeRaporu;
use App\Filament\Pages\YillikPlan\YillikEgitimPlani;
use App\Filament\Resources\Firmas\FirmaResource;
use App\Filament\Resources\RiskDegerlendirmesis\RiskDegerlendirmesiResource;
use App\Models\DofRaporu;
use App\Models\Firma;
use App\Models\IsEkipmani;
use App\Models\KkdZimmet;
use App\Models\OrtamOlcumu;
use App\Models\SaglikGozetimi as SaglikGozetimiModel;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Throwable;

/**
 * Ana Sayfa — görev durumu (isgsuite "Ana sayfa — görev durumum"): uzmanın
 * tüm işyerlerindeki yapılan, yapılmayan, yaklaşan ve süresi geçen
 * faaliyetleri tek listede toplar. Kayıt üretmez; her görev ilgili modüle
 * yönlendirir. Kaynaklar: Kontrol Merkezi kriterleri (vade tarihiyle),
 * periyodik kontrol vizeleri, KKD yenilemeleri, ortam ölçümleri, sağlık
 * gözetimi, temel eğitim (Yenileme Takibi) ve açık DÖF maddeleri.
 */
class GorevDurumu
{
    public const DURUMLAR = [
        'gecikmis' => 'Günü geçen',
        'yaklasan' => 'Yaklaşan',
        'yapilmayan' => 'Yapılmayan',
        'yapilan' => 'Yapılan',
    ];

    /** Kontrol Merkezi kriteri → ilgili sayfa ve yasal dayanak. */
    private const KRITER_SAYFALARI = [
        'risk_degerlendirmesi' => [RiskDegerlendirmesiResource::class, '6331 sayılı Kanun Md.10 — risk değerlendirmesi'],
        'yillik_calisma_plani' => [YillikCalismaPlani::class, 'İSG Hizmetleri Yönetmeliği — yıllık çalışma planı'],
        'acil_durum_plani' => [AcilDurumPlani::class, '6331 sayılı Kanun Md.11 — acil durum planları'],
        'acil_durum_destek' => [AcilDurumPlani::class, 'İşyerlerinde Acil Durumlar Hakkında Yönetmelik — destek elemanları'],
        'acil_durum_tatbikat' => [TatbikatTutanagi::class, 'İşyerlerinde Acil Durumlar Hakkında Yönetmelik — tatbikat'],
        'isg_kurulu' => [KurulToplantisi::class, '6331 sayılı Kanun Md.22 — iş sağlığı ve güvenliği kurulu'],
        'yillik_egitim_plani' => [YillikEgitimPlani::class, 'İSG Eğitimleri Yönetmeliği — eğitim planı'],
        'yillik_degerlendirme' => [YillikDegerlendirmeRaporu::class, 'İSG Hizmetleri Yönetmeliği — yıllık değerlendirme raporu'],
        'calisan_temsilcisi' => [CalisanTemsilcisiSecimi::class, '6331 sayılı Kanun Md.20 — çalışan temsilcisi'],
        'igu_atamasi' => [null, '6331 sayılı Kanun Md.6 — iş güvenliği uzmanı görevlendirmesi'],
        'hekim_atamasi' => [null, '6331 sayılı Kanun Md.6 — işyeri hekimi görevlendirmesi'],
        'tespit_oneri' => [TespitOneriDefteri::class, 'Onaylı defter — tespit ve öneriler'],
        'egitim_katilim_formu' => [EgitimKatilim::class, '6331 sayılı Kanun Md.17 — çalışanların eğitimi'],
        'ise_donus_muayenesi' => [IseDonusBelgesi::class, 'İş kazası sonrası işe dönüş muayenesi'],
        'kaza_sonrasi_isbasi_egitimi' => [IsbasiEgitim::class, 'İSG Eğitimleri Yönetmeliği Md.11 — iş kazası sonrası bilgilendirme eğitimi'],
        'periyodik_kontrol_raporu' => [PeriyodikKontrol::class, 'İş Ekipmanlarının Kullanımında Sağlık ve Güvenlik Şartları Yönetmeliği'],
        'calisma_izin_formu' => [IsIzinFormu::class, 'Tehlikeli işlerde çalışma izni'],
        'saha_denetim_formu' => [SahaDenetimi::class, 'İşyeri gözetimi ve saha denetimi'],
        'is_kazasi_bildirimi' => [OlayKayitlari::class, '6331 sayılı Kanun Md.14 — iş kazası kayıt ve bildirimi'],
        'olay_ramak_kala_kaydi' => [OlayKayitlari::class, '6331 sayılı Kanun Md.14 — ramak kala olayları'],
        'meslek_hastaligi_bildirimi' => [MeslekHastaligiBildirimi::class, '6331 sayılı Kanun Md.14 — meslek hastalığı bildirimi'],
        'saglik_raporu' => [SaglikGozetimi::class, '6331 sayılı Kanun Md.15 — sağlık gözetimi'],
        'onayli_defter_nushalari' => [OnayliDefterNushalari::class, 'Onaylı defter nüshaları'],
    ];

    public static function yaklasanGun(): int
    {
        return (int) config('isg.ana_sayfa.yaklasan_gun', 14);
    }

    /**
     * @return Collection<int, array{firma_id: int, firma: string, baslik: string, aciklama: string, dayanak: ?string, termin: ?Carbon, durum: string, modul: string, url: ?string}>
     */
    public static function gorevler(User $kullanici): Collection
    {
        $firmalar = Firma::query()->where('user_id', $kullanici->id)->where('aktif', true)->orderBy('unvan')->get();

        $gorevler = collect();
        foreach ($firmalar as $firma) {
            $gorevler = $gorevler
                ->merge(static::kriterGorevleri($firma))
                ->merge(static::tarihliGorevler($firma));
        }

        $sira = array_flip(array_keys(static::DURUMLAR));

        return $gorevler
            ->sortBy(fn (array $g) => [$sira[$g['durum']], $g['termin']?->timestamp ?? PHP_INT_MAX, $g['firma']])
            ->values();
    }

    /** @param  Collection<int, array<string, mixed>>  $gorevler */
    public static function ozet(Collection $gorevler): array
    {
        $say = $gorevler->countBy('durum');
        $toplam = $gorevler->count();

        return [
            'gecikmis' => $say['gecikmis'] ?? 0,
            'yaklasan' => $say['yaklasan'] ?? 0,
            'yapilmayan' => $say['yapilmayan'] ?? 0,
            'yapilan' => $say['yapilan'] ?? 0,
            'tamamlanma' => $toplam ? (int) round(($say['yapilan'] ?? 0) * 100 / $toplam) : 0,
        ];
    }

    /**
     * Aylık görevlendirme süresi: İSG Hizmetleri Yönetmeliği'ne göre her aktif
     * işyeri için çalışan × (10/20/40 dk) toplamı, normal aylık kapasiteye
     * (varsayılan 195 saat = haftalık 45 saat) oranı. Belge sınıfına uymayan
     * işyerleri ayrıca listelenir (C: az tehlikeli, B: + tehlikeli, A: tümü).
     *
     * @return array{kullanilan_dk: int, kapasite_dk: int, kalan_dk: int, yuzde: int, isyeri: int, sinif_uygunsuz: array<int, string>}
     */
    public static function sureOzeti(User $kullanici): array
    {
        $firmalar = Firma::query()->where('user_id', $kullanici->id)->where('aktif', true)->get(['id', 'unvan', 'calisan_sayisi', 'tehlike_sinifi', 'katip_aylik_dk']);

        $kullanilan = (int) $firmalar->sum(fn (Firma $f) => $f->iguAylikDk());
        $kapasite = (int) config('isg.ana_sayfa.aylik_kapasite_saat', 195) * 60;

        $izinli = config('isg.ana_sayfa.sinif_tehlike.'.$kullanici->unvan);

        return [
            'kullanilan_dk' => $kullanilan,
            'kapasite_dk' => $kapasite,
            'kalan_dk' => max(0, $kapasite - $kullanilan),
            'yuzde' => $kapasite ? (int) min(999, round($kullanilan * 100 / $kapasite)) : 0,
            'isyeri' => $firmalar->count(),
            'sinif_uygunsuz' => $izinli === null ? [] : $firmalar
                ->filter(fn (Firma $f) => $f->tehlike_sinifi && ! in_array($f->tehlike_sinifi, $izinli, true))
                ->pluck('unvan')->values()->all(),
        ];
    }

    /** Durum raporu (düz metin) — OSGB'ye / işverene iletilebilir. */
    public static function durumRaporu(User $kullanici): string
    {
        $gorevler = static::gorevler($kullanici);
        $o = static::ozet($gorevler);
        $sure = static::sureOzeti($kullanici);

        $satirlar = [
            'ANA SAYFA — GÖREV DURUMU RAPORU',
            $kullanici->name.' · '.config('isg.uzman_unvanlari.'.$kullanici->unvan, '').' · '.$sure['isyeri'].' işyeri',
            'Rapor tarihi: '.now()->format('d.m.Y H:i'),
            '',
            sprintf('Günü geçen: %d · Yaklaşan (%d gün): %d · Yapılmayan: %d · Yapılan: %d · Tamamlanma: %%%d',
                $o['gecikmis'], static::yaklasanGun(), $o['yaklasan'], $o['yapilmayan'], $o['yapilan'], $o['tamamlanma']),
            sprintf('Aylık görevlendirme süresi: %s kullanılan / %s kalan', static::saatDk($sure['kullanilan_dk']), static::saatDk($sure['kalan_dk'])),
        ];

        foreach (static::DURUMLAR as $durum => $ad) {
            $grup = $gorevler->where('durum', $durum);
            $satirlar[] = '';
            $satirlar[] = '== '.mb_strtoupper($ad).' ('.$grup->count().') ==';
            foreach ($grup as $g) {
                $satirlar[] = '- ['.$g['firma'].'] '.$g['baslik'].($g['termin'] ? ' · termin '.$g['termin']->format('d.m.Y') : '').' — '.$g['aciklama'];
            }
        }

        return implode("\r\n", $satirlar)."\r\n";
    }

    public static function saatDk(int $dk): string
    {
        return intdiv($dk, 60).' saat '.($dk % 60).' dk';
    }

    /** @return array<int, array<string, mixed>> */
    private static function kriterGorevleri(Firma $firma): array
    {
        $yaklasan = static::yaklasanGun();

        return collect(PortfoyKarne::firmaChecklistDetay($firma))
            // Firmayı kapsamayan (muaf) kriter görev değildir; "yapılan" sayılırsa
            // tamamlanma oranı Evrak uyumundan yüksek görünür.
            ->filter(fn (array $k) => $k['hazir'] && ! $k['takip'] && ! $k['muaf'])
            ->map(function (array $k) use ($firma, $yaklasan): array {
                [$sayfa, $dayanak] = static::KRITER_SAYFALARI[$k['anahtar']] ?? [null, null];
                $vade = $k['vade_tarihi'];
                $kalan = $vade ? (int) now()->startOfDay()->diffInDays($vade->copy()->startOfDay(), false) : null;

                $durum = match (true) {
                    $k['tamam'] => 'yapilan',
                    $kalan !== null && $kalan < 0 => 'gecikmis',
                    $kalan !== null && $kalan <= $yaklasan => 'yaklasan',
                    default => 'yapilmayan',
                };

                return static::gorev($firma, $k['ad'], match ($durum) {
                    'yapilan' => 'Kayıt mevcut.',
                    'gecikmis' => 'Vade tarihi geçti, kayıt yok.',
                    'yaklasan' => 'Vade yaklaşıyor, kayıt yok.',
                    default => 'Bu işyeri için kayıt bulunamadı.',
                }, $dayanak, $vade, $durum, $k['kategori'], static::sayfaUrl($sayfa, $firma));
            })
            ->values()->all();
    }

    /** Tarihli takipler: tek tek kayıt yerine işyeri başına toplu görev. */
    public static function tarihliGorevler(Firma $firma): array
    {
        $gorevler = [];
        $bugun = Carbon::today();
        $sinir = $bugun->copy()->addDays(static::yaklasanGun());

        $ekle = function (string $baslik, Collection $tarihler, string $birim, ?string $dayanak, string $modul, ?string $url) use (&$gorevler, $firma, $bugun, $sinir): void {
            $gecmis = $tarihler->filter(fn (Carbon $t) => $t->lt($bugun));
            $yakin = $tarihler->filter(fn (Carbon $t) => $t->gte($bugun) && $t->lte($sinir));

            if ($gecmis->isNotEmpty()) {
                $gorevler[] = static::gorev($firma, $baslik, $gecmis->count().' '.$birim.' süresi geçmiş.', $dayanak, $gecmis->min(), 'gecikmis', $modul, $url);
            }
            if ($yakin->isNotEmpty()) {
                $gorevler[] = static::gorev($firma, $baslik, $yakin->count().' '.$birim.' '.static::yaklasanGun().' gün içinde doluyor.', $dayanak, $yakin->min(), 'yaklasan', $modul, $url);
            }
        };

        $ekle('Periyodik kontrol', IsEkipmani::query()->where('firma_id', $firma->id)->where('aktif', true)->whereNotNull('sonraki_vize_tarihi')
            ->pluck('sonraki_vize_tarihi')->map(fn ($t) => Carbon::parse($t)),
            'ekipmanın vizesi', 'İş Ekipmanları Yönetmeliği — periyodik kontrol', 'Periyodik Kontrol & Ölçüm', static::sayfaUrl(PeriyodikKontrol::class, $firma));

        $ekle('KKD yenileme', KkdZimmet::query()->where('firma_id', $firma->id)->where('durum', 'teslim_edildi')->get()
            ->map(fn (KkdZimmet $z) => $z->vade())->filter()->values(),
            'KKD zimmetinin', 'KKD Kullanımı Hakkında Yönetmelik', 'KKD', static::sayfaUrl(KkdTakip::class, $firma));

        $ekle('Ortam ölçümü', collect(OrtamOlcumu::query()->where('firma_id', $firma->id)->value('olcumler') ?? [])
            ->pluck('sonraki_olcum_tarihi')->filter()->map(fn ($t) => Carbon::parse($t)),
            'ölçümün', 'İşyeri ortam ölçümleri', 'Periyodik Kontrol & Ölçüm', static::sayfaUrl(OrtamOlcumleri::class, $firma));

        $ekle('Sağlık gözetimi', collect(SaglikGozetimiModel::query()->where('firma_id', $firma->id)->value('satirlar') ?? [])
            ->pluck('sonraki_tarih')->filter()->map(fn ($t) => Carbon::parse($t)),
            'tetkikin', '6331 sayılı Kanun Md.15 — sağlık gözetimi', 'Sağlık Gözetimi', static::sayfaUrl(SaglikGozetimi::class, $firma));

        $ekle('DÖF', DofRaporu::query()->where('firma_id', $firma->id)->get()
            ->flatMap(fn (DofRaporu $r) => collect($r->maddeler ?? []))
            ->filter(fn (array $m) => ($m['durum'] ?? null) !== 'tamamlandi' && filled($m['termin'] ?? null))
            ->map(fn (array $m) => static::tarih($m['termin']))->filter()->values(),
            'açık DÖF maddesinin terminin', 'Düzeltici / önleyici faaliyet takibi', 'Risk ve DÖF', static::sayfaUrl(DofOlustur::class, $firma));

        // Temel eğitim — Yenileme Takibi ile aynı hesap
        $egitim = collect(EgitimTakibi::satirlar($firma));
        $eksik = $egitim->where('durum', 'kayit_yok');
        $dolmus = $egitim->where('durum', 'dolmus');
        $yakin = $egitim->where('durum', 'yaklasan')->filter(fn (array $s) => $s['yenileme']?->lte($sinir));
        $egitimUrl = static::sayfaUrl(EgitimYenilemeTakibi::class, $firma);
        $dayanak = 'İSG Eğitimleri Yönetmeliği (RG 02.04.2026)';

        if ($dolmus->isNotEmpty()) {
            $gorevler[] = static::gorev($firma, 'Temel eğitim yenileme', $dolmus->count().' çalışanın temel eğitim süresi dolmuş.', $dayanak, $dolmus->min('yenileme'), 'gecikmis', 'Eğitimler', $egitimUrl);
        }
        if ($eksik->isNotEmpty()) {
            $gecikenIlk = $eksik->filter(fn (array $s) => $s['ilk_son_tarih']?->lt($bugun));
            $gorevler[] = static::gorev(
                $firma,
                'Eğitim kaydı eksik',
                $eksik->count().' aktif çalışan için doğrulanmış temel eğitim bulunamadı'.($gecikenIlk->isNotEmpty() ? ' ('.$gecikenIlk->count().' kişide işe girişten 3 ay geçti)' : '').'.',
                $dayanak,
                $eksik->pluck('ilk_son_tarih')->filter()->min(),
                $gecikenIlk->isNotEmpty() ? 'gecikmis' : 'yapilmayan',
                'Eğitimler',
                $egitimUrl,
            );
        }
        if ($yakin->isNotEmpty()) {
            $gorevler[] = static::gorev($firma, 'Temel eğitim yenileme', $yakin->count().' çalışanın eğitim yenilemesi yaklaşıyor.', $dayanak, $yakin->min('yenileme'), 'yaklasan', 'Eğitimler', $egitimUrl);
        }

        return $gorevler;
    }

    private static function gorev(Firma $firma, string $baslik, string $aciklama, ?string $dayanak, ?Carbon $termin, string $durum, string $modul, ?string $url): array
    {
        return [
            'firma_id' => $firma->id,
            'firma' => $firma->unvan,
            'baslik' => $baslik,
            'aciklama' => $aciklama,
            'dayanak' => $dayanak,
            'termin' => $termin,
            'kalan_gun' => $termin ? (int) now()->startOfDay()->diffInDays($termin->copy()->startOfDay(), false) : null,
            'durum' => $durum,
            'modul' => $modul,
            'url' => $url,
        ];
    }

    private static function sayfaUrl(?string $sayfa, Firma $firma): ?string
    {
        try {
            if ($sayfa === null) {
                return FirmaResource::getUrl('edit', ['record' => $firma]);
            }

            return is_subclass_of($sayfa, \Filament\Resources\Resource::class)
                ? $sayfa::getUrl('index')
                : $sayfa::getUrl(['firma' => $firma->id]);
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
