<?php

namespace App\Support;

use App\Models\DofRaporu;
use App\Models\Firma;
use App\Models\RiskDegerlendirmesi;
use App\Models\SahaBulgusu;
use App\Models\SahaDenetimi;
use App\Models\TespitOneriDefteri;
use Illuminate\Support\Facades\URL;

/**
 * İşverene WhatsApp ile rapor / hatırlatma (02.11.2026).
 *
 * - Telefon: Acil Durum Planı'ndaki işveren vekili / işveren telefonu, sonra firma telefonu — ilk geçerli
 *   Türk cep numarası (sabit hat WhatsApp'a gitmez). Yoksa null → arayüz uyarı verir.
 * - PDF: oturum gerektirmeyen, 30 gün geçerli imzalı bağlantı (routes/web.php "paylasim.pdf");
 *   yalnız o raporu açar, süresi dolunca 403 verir.
 * - Mesajlar uzmanın adı ve unvanıyla imzalanır.
 */
class IsverenWhatsApp
{
    public const BAGLANTI_GUN = 30;

    /** Paylaşılabilir PDF türleri → model sınıfı. */
    public const TURLER = [
        'dof' => DofRaporu::class,
        'saha-denetimi' => SahaDenetimi::class,
        'tespit-defteri' => TespitOneriDefteri::class,
        'risk' => RiskDegerlendirmesi::class,
    ];

    public static function telefon(?Firma $firma): ?string
    {
        if (! $firma) {
            return null;
        }

        $u = $firma->acilDurumPlani?->uygulama ?? [];

        foreach ([$u['isveren_vekili_tel'] ?? null, $u['isveren_tel'] ?? null, $firma->telefon] as $aday) {
            if ($no = WhatsApp::numara($aday)) {
                return $no;
            }
        }

        return null;
    }

    public static function isverenAdi(?Firma $firma): string
    {
        return FirmaImza::isveren($firma) ?? 'Yetkili';
    }

    /** "Mehmet ÖZDEMİR - A Sınıfı İSG Uzmanı" */
    public static function imza(?Firma $firma): string
    {
        $u = FirmaImza::uzman($firma);
        $unvan = $u['sinif'] ? $u['sinif'].' Sınıfı İSG Uzmanı' : 'İSG Uzmanı';

        return trim(($u['ad'] ? $u['ad'].' - ' : '').$unvan);
    }

    public static function pdfBaglantisi(string $tur, int $id): string
    {
        return URL::temporarySignedRoute('paylasim.pdf', now()->addDays(self::BAGLANTI_GUN), ['tur' => $tur, 'id' => $id]);
    }

    /** WhatsApp bağlantısı; işverenin cep telefonu yoksa null. */
    public static function url(?Firma $firma, string $mesaj): ?string
    {
        $no = self::telefon($firma);

        return $no ? 'https://api.whatsapp.com/send?phone='.$no.'&text='.rawurlencode($mesaj) : null;
    }

    /*
    |--------------------------------------------------------------------------
    | Mesaj şablonları
    |--------------------------------------------------------------------------
    */

    public static function dofMesaji(DofRaporu $r): string
    {
        $r->loadMissing('firma');

        return implode("\n", [
            'Sayın '.self::isverenAdi($r->firma).' ('.$r->firma?->unvan.'),',
            ($r->rapor_tarihi ?? now())->format('d.m.Y').' tarihli ve '.$r->belge_no.' numaralı İSG Düzeltici Önleyici Faaliyet (DÖF) Raporunuz hazırlanmıştır.',
            '- Tespit Edilen Madde: '.count($r->maddeler ?? []).' adet',
            '- Durum: İnceleme ve aksiyon bekleniyor.',
            'Rapor dosyasını ve tespit detaylarını incelemek için: '.self::pdfBaglantisi('dof', $r->id),
            '(Bağlantı '.self::BAGLANTI_GUN.' gün geçerlidir.)',
            'Bilgilerinize sunar, iyi çalışmalar dileriz.',
            self::imza($r->firma),
        ]);
    }

    public static function sahaDenetimiMesaji(SahaDenetimi $d): string
    {
        $d->loadMissing('firma');
        $uygunsuz = collect($d->cevaplar ?? [])->where('sonuc', 'uygun_degil')->count();

        return implode("\n", array_filter([
            'Sayın '.self::isverenAdi($d->firma).' ('.$d->firma?->unvan.'),',
            ($d->denetim_tarihi ?? now())->format('d.m.Y').' tarihli saha denetimi tamamlanmıştır.',
            '- Tespit edilen uygunsuzluk: '.$uygunsuz.' adet'.($d->kritik_uygunsuzluk_var ? ' (kritik uygunsuzluk var)' : ''),
            $d->uygunluk_yuzdesi !== null ? '- Uygunluk oranı: %'.rtrim(rtrim(number_format((float) $d->uygunluk_yuzdesi, 1, ',', ''), '0'), ',') : null,
            'Denetim raporu: '.self::pdfBaglantisi('saha-denetimi', $d->id),
            '(Bağlantı '.self::BAGLANTI_GUN.' gün geçerlidir.)',
            'Bilgilerinize sunar, iyi çalışmalar dileriz.',
            self::imza($d->firma),
        ]));
    }

    public static function defterMesaji(TespitOneriDefteri $t): string
    {
        $t->loadMissing('firma');

        return implode("\n", [
            'Sayın '.self::isverenAdi($t->firma).' ('.$t->firma?->unvan.'),',
            'İş sağlığı ve güvenliği tespit ve öneri defterine '.count($t->maddeler ?? []).' madde işlenmiştir (6331 s. Kanun Md. 8; İSG Hizmetleri Yönetmeliği).',
            'Defter sayfasını incelemek ve gereğini yapmak için: '.self::pdfBaglantisi('tespit-defteri', $t->id),
            '(Bağlantı '.self::BAGLANTI_GUN.' gün geçerlidir.)',
            'Bilgilerinize sunar, iyi çalışmalar dileriz.',
            self::imza($t->firma),
        ]);
    }

    /** İSG Risk Değerlendirmesi raporu (02.11.2026). */
    public static function riskMesaji(RiskDegerlendirmesi $rd): string
    {
        $rd->loadMissing('firma');
        $f = $rd->firma;
        $yil = $f?->tehlike_sinifi ? $f->riskGecerlilikYili() : null;
        $sinif = $f?->tehlike_sinifi ? $f->tehlikeSinifiEtiketi() : null;

        return implode("\n", array_filter([
            'Sayın '.self::isverenAdi($f).' ('.($rd->firma_unvan ?: $f?->unvan).'),',
            ($rd->rapor_tarihi ?? now())->format('d.m.Y').' tarihli ve '.$rd->belge_no.' numaralı 6331 sayılı Kanun kapsamındaki İş Sağlığı ve Güvenliği Risk Değerlendirmesi Raporunuz hazırlanmıştır.',
            '- İncelenen Risk Sayısı: '.$rd->maddeler()->count(),
            $rd->gecerlilik_tarihi ? '- Geçerlilik: '.$rd->gecerlilik_tarihi->format('d.m.Y').' tarihine kadar'.($sinif ? ' ('.$sinif.($yil ? ' - '.$yil.' Yıl' : '').')' : '') : null,
            'Raporu incelemek ve imzalamak için bağlantı: '.self::pdfBaglantisi('risk', $rd->id),
            '(Bağlantı '.self::BAGLANTI_GUN.' gün geçerlidir.)',
            'Bilgilerinize sunarız.',
            self::imza($f),
        ]));
    }

    /** Açık bulgu / DÖF maddesi için hatırlatma. */
    public static function bulguHatirlatmasi(SahaBulgusu $b): string
    {
        $b->loadMissing('firma');
        $termin = $b->termin
            ? $b->termin->format('d.m.Y').($b->terminGectiMi() ? ' (termin '.$b->termin->diffInDays(today()).' gün geçti)' : '')
            : 'belirtilmedi';

        return implode("\n", array_filter([
            'Sayın '.self::isverenAdi($b->firma).' ('.$b->firma?->unvan.'),',
            'Açık düzeltici / önleyici faaliyet hatırlatması ('.$b->bulgu_no.'):',
            '- Tespit: '.\Illuminate\Support\Str::limit((string) $b->uygunsuzluk, 220),
            filled($b->aksiyon ?? null) ? '- Yapılması gereken: '.\Illuminate\Support\Str::limit((string) $b->aksiyon, 220) : null,
            '- Sorumlu: '.($b->sorumlu ?: 'İşveren / Saha Sorumlusu'),
            '- Termin: '.$termin,
            'Tamamlandığında fotoğrafla bilgi vermenizi rica ederiz.',
            self::imza($b->firma),
        ]));
    }
}
