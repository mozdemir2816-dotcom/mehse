<?php

namespace App\Support;

use App\Models\Calisan;
use App\Models\EgitimAtamasi;
use App\Models\EgitimPaketi;
use App\Models\EgitimTopluBildirimi;
use App\Models\Firma;
use App\Models\UzaktanEgitimFirmaAyari;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * Yıl başı toplu online eğitim (23.11.2026, kullanıcı kararları):
 *  - Temel İSG eğitimi kaydı olan ve yenilemesi o yıl içinde dolan (ya da zaten dolmuş) personel →
 *    4 saatlik "Temel İSG Yenileme Eğitimi" paketi (egitim_turu = yenileme).
 *  - Hiç temel eğitim kaydı olmayan personel → 8 saatlik "Temel İSG Eğitimi" paketi (ilk_defa).
 *  - Yenilemesi o yıldan sonra olanlar ve o paketi zaten almış / sürdürenler atlanır.
 *  - Atama sonrası portal kullanıcı kodu + geçici şifre üretilir; firma yetkilisine giriş kartları
 *    PDF'i e-postayla gider, ayrıca WhatsApp mesajı hazırlanır. Gönderim düğmeyle, önizlemeden sonra.
 */
class YilBasiTopluEgitim
{
    /**
     * Firma için önizleme: kim 4 saat, kim 8 saat alacak, kim atlanıyor.
     *
     * @return array{firma: Firma, yenileme: Collection<int, array{calisan: Calisan, son_egitim: ?Carbon, yenileme: ?Carbon}>, ilk_defa: Collection<int, array{calisan: Calisan}>, gecerli: int, zaten_atanmis: int, eksik: array<int, string>, bildirim: ?EgitimTopluBildirimi}
     */
    public static function firmaOnizleme(Firma $firma, int $yil, ?EgitimPaketi $temel, ?EgitimPaketi $yenilemePaketi): array
    {
        $yilSonu = Carbon::create($yil, 12, 31)->endOfDay();
        $yilBasi = Carbon::create($yil, 1, 1)->startOfDay();
        $yenilemeYili = (int) (config('isg.egitim_yenileme_yili.'.$firma->tehlike_sinifi)
            ?? config('isg.uzaktan_egitim.yenileme_yili.'.$firma->tehlike_sinifi, 0));

        $satirlar = EgitimTakibi::satirlar($firma);
        $paketIdleri = array_filter([$temel?->id, $yenilemePaketi?->id]);
        // Devam eden (herhangi bir paketteki bitmemiş) eğitimi olan ya da bu iki paketi bu yıl bitirmiş
        // çalışana tekrar gönderilmez — yarım kalan eğitimin ilerlemesi silinmesin.
        $donemAtamalari = EgitimAtamasi::query()
            ->whereIn('calisan_id', $satirlar->map(fn ($s) => $s['calisan']->id)->all() ?: [0])
            ->where(fn ($q) => $q->where('durum', '!=', 'tamamlandi')
                ->orWhere(fn ($q2) => $q2->whereIn('egitim_paketi_id', $paketIdleri ?: [0])->where('tamamlandi_at', '>=', $yilBasi)))
            ->pluck('calisan_id')
            ->flip();

        $yenileme = collect();
        $ilk = collect();
        $gecerli = 0;
        $zatenAtanmis = 0;

        foreach ($satirlar as $s) {
            /** @var Calisan $c */
            $c = $s['calisan'];

            if ($donemAtamalari->has($c->id)) {
                $zatenAtanmis++;

                continue;
            }

            // Eğitim kaydı / yüz yüze katılım yoksa tamamlanmış online temel eğitim de kayıt sayılır.
            $son = $s['son_egitim'];
            $online = $c->egitimAtamalari->max('tamamlandi_at');
            if ($online && (! $son || $online->gt($son))) {
                $son = $online->copy()->startOfDay();
            }

            if (! $son) {
                $ilk->push(['calisan' => $c]);

                continue;
            }

            $yenilemeTarihi = $yenilemeYili ? $son->copy()->addYears($yenilemeYili) : null;

            if ($yenilemeTarihi && $yenilemeTarihi->lte($yilSonu)) {
                $yenileme->push(['calisan' => $c, 'son_egitim' => $son, 'yenileme' => $yenilemeTarihi]);
            } else {
                $gecerli++;
            }
        }

        $eksik = [];
        if ($yenileme->isNotEmpty() && ! $yenilemePaketi) {
            $eksik[] = 'Yenileme paketi seçilmedi';
        } elseif ($yenileme->isNotEmpty() && ! UzaktanEgitimFirmaAyari::gonderimeHazirMi($firma, $yenilemePaketi)) {
            $eksik[] = 'Yenileme paketi için içerik onayı yok (Firma Ayarları)';
        }
        if ($ilk->isNotEmpty() && ! $temel) {
            $eksik[] = 'Temel (8 saat) paket seçilmedi';
        } elseif ($ilk->isNotEmpty() && ! UzaktanEgitimFirmaAyari::gonderimeHazirMi($firma, $temel)) {
            $eksik[] = 'Temel (8 saat) paket için içerik onayı yok (Firma Ayarları)';
        }

        return [
            'firma' => $firma,
            'yenileme' => $yenileme,
            'ilk_defa' => $ilk,
            'gecerli' => $gecerli,
            'zaten_atanmis' => $zatenAtanmis,
            'eksik' => $eksik,
            'bildirim' => EgitimTopluBildirimi::query()->where('firma_id', $firma->id)->where('yil', $yil)->first(),
        ];
    }

    /**
     * Firma için atamaları yapar, giriş bilgilerini üretir, firma yetkilisine e-posta yollar.
     *
     * @return array{yenileme: int, ilk_defa: int, giris: array<int, array{ad_soyad: string, gorev: ?string, kod: ?string, sifre: string, telefon: ?string, egitim: string}>, eposta: ?string, eposta_hatasi: ?string, bildirim: EgitimTopluBildirimi}
     */
    public static function gonder(Firma $firma, int $yil, ?EgitimPaketi $temel, ?EgitimPaketi $yenilemePaketi, int $userId, bool $epostaGonder = true): array
    {
        $o = static::firmaOnizleme($firma, $yil, $temel, $yenilemePaketi);

        if ($o['eksik']) {
            throw new \RuntimeException(implode(' · ', $o['eksik']));
        }

        $sonTarih = Carbon::create($yil, 1, 1)->addMonths((int) config('isg.uzaktan_egitim.temel_egitim_son_ay', 3))->subDay()->toDateString();
        $sayi = ['yenileme' => 0, 'ilk_defa' => 0];

        if ($o['yenileme']->isNotEmpty()) {
            $sayi['yenileme'] = UzaktanEgitimAtayici::ata($yenilemePaketi, $o['yenileme']->pluck('calisan'), 'yenileme', $sonTarih, $userId, $yil)['atanan'];
        }
        if ($o['ilk_defa']->isNotEmpty()) {
            $sayi['ilk_defa'] = UzaktanEgitimAtayici::ata($temel, $o['ilk_defa']->pluck('calisan'), 'ilk_defa', $sonTarih, $userId, $yil)['atanan'];
        }

        $giris = static::girisBilgileri($firma, $yil, $temel, $yenilemePaketi);

        $bildirim = EgitimTopluBildirimi::query()->firstOrNew(['firma_id' => $firma->id, 'yil' => $yil]);
        $bildirim->fill([
            'user_id' => $userId,
            'yenileme_sayisi' => (int) $bildirim->yenileme_sayisi + $sayi['yenileme'],
            'ilk_defa_sayisi' => (int) $bildirim->ilk_defa_sayisi + $sayi['ilk_defa'],
        ])->save();

        $hata = null;
        if ($epostaGonder && $giris) {
            $hata = static::epostaGonder($firma, $yil, $giris, $bildirim);
        }

        return ['yenileme' => $sayi['yenileme'], 'ilk_defa' => $sayi['ilk_defa'], 'giris' => $giris,
            'eposta' => $bildirim->eposta, 'eposta_hatasi' => $hata, 'bildirim' => $bildirim->refresh()];
    }

    /**
     * Bu dönemin bitmemiş atamaları için giriş bilgileri. Kendi şifresini belirlememiş çalışana yeni
     * geçici şifre verilir (eskisi şifreli saklandığından tekrar gösterilemez).
     *
     * @return array<int, array{ad_soyad: string, gorev: ?string, kod: ?string, sifre: string, telefon: ?string, egitim: string}>
     */
    public static function girisBilgileri(Firma $firma, int $yil, ?EgitimPaketi $temel, ?EgitimPaketi $yenilemePaketi): array
    {
        $atamalar = EgitimAtamasi::query()
            ->whereIn('egitim_paketi_id', array_filter([$temel?->id, $yenilemePaketi?->id]) ?: [0])
            ->where('donem_yili', $yil)
            ->where('durum', '!=', 'tamamlandi')
            ->whereHas('calisan', fn ($q) => $q->where('firma_id', $firma->id))
            ->with(['calisan', 'paket'])
            ->get()
            ->sortBy(fn (EgitimAtamasi $a) => $a->calisan?->ad_soyad);

        $satirlar = [];
        foreach ($atamalar as $a) {
            $c = $a->calisan;
            $sifre = '(kendi belirlediğiniz şifre)';
            if ($c->sifre_belirlendi_at === null) {
                $sifre = UzaktanEgitimAtayici::geciciSifre();
                $c->forceFill(['sifre' => $sifre])->save();
            }

            $satirlar[] = [
                'ad_soyad' => $c->ad_soyad,
                'gorev' => $c->gorev,
                'kod' => $c->eposta,
                'sifre' => $sifre,
                'telefon' => $c->telefon,
                'egitim' => $a->paket?->egitim_turu === 'yenileme' ? 'Temel İSG Yenileme Eğitimi (4 saat)' : 'Temel İSG Eğitimi (8 saat)',
            ];
        }

        return $satirlar;
    }

    public static function girisKartlariPdf(Firma $firma, array $giris): string
    {
        return \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.uzaktan-egitim-giris-kartlari', [
            'firma' => $firma, 'satirlar' => $giris, 'portal' => url('/egitim'),
        ])->setPaper('a4')->output();
    }

    /** Firma yetkilisine bildirim metni (e-posta gövdesi ve WhatsApp mesajı). */
    public static function bildirimMetni(Firma $firma, int $yil, array $giris): string
    {
        $yenileme = collect($giris)->filter(fn ($g) => str_contains($g['egitim'], 'Yenileme'))->count();
        $ilk = count($giris) - $yenileme;
        $sonTarih = Carbon::create($yil, 1, 1)->addMonths((int) config('isg.uzaktan_egitim.temel_egitim_son_ay', 3))->subDay()->format('d.m.Y');
        $muhatap = $firma->isveren_vekili ?: $firma->isveren_ad;

        return 'Sayın '.($muhatap ?: 'Yetkili').",\n\n"
            ."{$firma->unvan} çalışanlarının {$yil} yılı online İş Sağlığı ve Güvenliği eğitimleri tanımlanmıştır.\n\n"
            .($yenileme ? "• {$yenileme} çalışan: Temel İSG Yenileme Eğitimi (4 saat online + 4 saat yüz yüze)\n" : '')
            .($ilk ? "• {$ilk} çalışan: Temel İSG Eğitimi (8 saat online)\n" : '')
            ."\nEğitim portalı: ".url('/egitim')."\n"
            ."Çalışanların kullanıcı kodu ve geçici şifreleri ekteki giriş kartlarındadır; lütfen kartları ilgili çalışanlara iletiniz.\n"
            ."Eğitimlerin en geç {$sonTarih} tarihine kadar tamamlanması rica olunur. Videolar ileri sarılamaz; sınavdan en az "
            .(int) config('isg.uzaktan_egitim.gecme_puani_alt_sinir', 60)." puan alınmalıdır.\n\nİyi çalışmalar dileriz.";
    }

    /** WhatsApp bağlantısı — firma telefonuna (şifreler mesajda YOK; kartlar PDF ile iletilir). */
    public static function whatsappLinki(Firma $firma, int $yil, array $giris): string
    {
        $no = preg_replace('/\D/', '', (string) $firma->telefon);
        $no = match (true) {
            strlen($no) === 11 && str_starts_with($no, '0') => '9'.$no,
            strlen($no) === 10 => '90'.$no,
            default => $no,
        };

        return 'https://wa.me/'.$no.'?text='.rawurlencode(static::bildirimMetni($firma, $yil, $giris));
    }

    /** @return ?string hata metni (başarılıysa null) */
    public static function epostaGonder(Firma $firma, int $yil, array $giris, EgitimTopluBildirimi $bildirim): ?string
    {
        $alici = trim((string) $firma->eposta);

        if ($alici === '' || ! filter_var($alici, FILTER_VALIDATE_EMAIL)) {
            $bildirim->forceFill(['eposta' => $alici ?: null, 'eposta_hatasi' => 'Firmada geçerli e-posta adresi yok'])->save();

            return 'Firmada geçerli e-posta adresi yok';
        }

        try {
            $pdf = static::girisKartlariPdf($firma, $giris);
            Mail::raw(static::bildirimMetni($firma, $yil, $giris), fn ($m) => $m->to($alici)
                ->subject("{$yil} Yılı Online İSG Eğitimi — Çalışan Giriş Bilgileri ({$firma->unvan})")
                ->attachData($pdf, 'online-isg-egitimi-giris-kartlari-'.$yil.'.pdf', ['mime' => 'application/pdf']));
        } catch (Throwable $e) {
            Log::error('Yıl başı toplu eğitim e-postası gönderilemedi', ['firma' => $firma->id, 'hata' => $e->getMessage()]);
            $bildirim->forceFill(['eposta' => $alici, 'eposta_hatasi' => $e->getMessage()])->save();

            return $e->getMessage();
        }

        $bildirim->forceFill(['eposta' => $alici, 'eposta_gonderildi_at' => now(), 'eposta_hatasi' => null])->save();

        return null;
    }
}
