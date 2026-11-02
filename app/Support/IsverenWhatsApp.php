<?php

namespace App\Support;

use App\Models\DofRaporu;
use App\Models\Firma;
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
        return trim((string) ($firma?->isveren_vekili ?: $firma?->isveren_ad)) ?: 'Yetkili';
    }

    /** "Mehmet ÖZDEMİR - A Sınıfı İSG Uzmanı" */
    public static function imza(?Firma $firma): string
    {
        $igu = $firma?->igu;
        $sahip = $firma?->user;
        $ad = $igu?->ad_soyad ?: $sahip?->name;
        $ham = (string) ($igu?->unvan ?: ($sahip?->unvan ? $sahip->unvanEtiketi() : ''));
        $sinif = preg_match('/\b([ABC])\s*SINIFI/u', mb_strtoupper(strtr($ham, ['ı' => 'I', 'i' => 'İ']), 'UTF-8'), $e) ? $e[1] : null;
        $unvan = $sinif ? $sinif.' Sınıfı İSG Uzmanı' : 'İSG Uzmanı';

        return trim(($ad ? $ad.' - ' : '').$unvan);
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
