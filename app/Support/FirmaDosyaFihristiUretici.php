<?php

namespace App\Support;

use App\Models\Firma;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Firma İSG Dosyası Fihristi — TEDBİR ON "Firma Çalışma Merkezi" referansı.
 * PortfoyKarne::firmaChecklistDetay()'ın döndürdüğü kriter listesini, sol
 * menüde kullanılan aynı 12 kategoriye göre gruplayıp numaralı bir içindekiler
 * (dosya fihristi) sayfası basar — hangi belgenin dosyada olması gerektiğini,
 * hazır olup olmadığını ve varsa vade tarihini tek sayfada gösterir.
 *
 * Not: isgpratik'teki "Belge / İmza / Firmada" 3 ayrı durumu mehse'de genel
 * geçer biçimde hesaplanamıyor (çoğu belge türü için imza/teslim ayrı bir alan
 * olarak tutulmuyor) — bu yüzden burada checklistDetay'ın zaten ürettiği
 * tek durum (tamamlandı / yakın / eksik) kullanılıyor.
 */
class FirmaDosyaFihristiUretici
{
    /** Sol menüdeki navigasyon grup sırasıyla aynı — fihriste de bu sıra kullanılır. */
    private const KATEGORI_SIRASI = [
        'Yönetim',
        'Risk Değerlendirmesi',
        'Acil Durum & Yangın',
        'Eğitimler',
        'Çalışan & Kurul',
        'Sağlık Gözetimi',
        'Saha Kontrolleri',
        'Periyodik Kontrol & Ölçüm',
        'KKD',
        'İş Kazaları & Olaylar',
        'Planlama & Arşiv',
        'Diğer Belge & Yazışma',
    ];

    public static function pdf(Firma $firma): StreamedResponse
    {
        $gruplu = collect(PortfoyKarne::firmaChecklistDetay($firma))
            ->groupBy('kategori')
            ->sortBy(fn ($_, $kategori) => array_search($kategori, self::KATEGORI_SIRASI, true) ?: 99);

        $pdf = Pdf::loadView('pdf.firma-dosya-fihristi', [
            'firma' => $firma,
            'gruplu' => $gruplu,
        ])->setPaper('a4');

        $ad = 'dosya-fihristi-'.Str::slug($firma->unvan).'.pdf';

        return response()->streamDownload(fn () => print ($pdf->output()), $ad);
    }
}
