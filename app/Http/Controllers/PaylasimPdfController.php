<?php

namespace App\Http\Controllers;

use App\Models\DofRaporu;
use App\Models\SahaDenetimi;
use App\Models\TespitOneriDefteri;
use App\Support\DofRaporuUretici;
use App\Support\IsverenWhatsApp;
use App\Support\SahaDenetimiUretici;
use App\Support\TespitOneriDefteriUretici;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * İşverene WhatsApp ile gönderilen rapor PDF'i (02.11.2026). Oturum gerektirmez; yalnız
 * IsverenWhatsApp::pdfBaglantisi() ile üretilmiş, süresi dolmamış imzalı bağlantıyla açılır
 * ("signed" ara katmanı). Bağlantı tek bir raporu gösterir.
 */
class PaylasimPdfController extends Controller
{
    public function __invoke(string $tur, int $id): StreamedResponse
    {
        abort_unless(isset(IsverenWhatsApp::TURLER[$tur]), 404);

        return match ($tur) {
            'dof' => DofRaporuUretici::pdf(DofRaporu::findOrFail($id)),
            'saha-denetimi' => SahaDenetimiUretici::pdf(SahaDenetimi::where('durum', 'tamamlandi')->findOrFail($id)),
            'tespit-defteri' => TespitOneriDefteriUretici::pdf(TespitOneriDefteri::findOrFail($id)),
        };
    }
}
