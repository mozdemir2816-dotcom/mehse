<?php

namespace App\Http\Controllers;

use App\Models\DofRaporu;
use App\Models\RiskDegerlendirmesi;
use App\Models\SahaDenetimi;
use App\Models\TespitOneriDefteri;
use App\Support\DofRaporuUretici;
use App\Support\HastaneRiskPdfUretici;
use App\Support\RiskDegerlendirmesiUretici;
use App\Support\IsverenWhatsApp;
use App\Support\SahaDenetimiUretici;
use App\Support\TespitOneriDefteriUretici;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

/**
 * İşverene WhatsApp ile gönderilen rapor PDF'i (02.11.2026). Oturum gerektirmez; yalnız
 * IsverenWhatsApp::pdfBaglantisi() ile üretilmiş, süresi dolmamış imzalı bağlantıyla açılır
 * ("signed" ara katmanı). Bağlantı tek bir raporu gösterir.
 */
class PaylasimPdfController extends Controller
{
    public function __invoke(string $tur, int $id): Response
    {
        abort_unless(isset(IsverenWhatsApp::TURLER[$tur]), 404);

        return match ($tur) {
            'dof' => DofRaporuUretici::pdf(DofRaporu::findOrFail($id)),
            'saha-denetimi' => SahaDenetimiUretici::pdf(SahaDenetimi::where('durum', 'tamamlandi')->findOrFail($id)),
            'tespit-defteri' => TespitOneriDefteriUretici::pdf(TespitOneriDefteri::findOrFail($id)),
            'risk' => $this->risk(RiskDegerlendirmesi::findOrFail($id)),
        };
    }

    /** Büyük raporda arka planda hazırlanmış (önbellekteki) imzalı PDF varsa o verilir, yoksa anında üretilir. */
    private function risk(RiskDegerlendirmesi $rd): Response
    {
        if ($rd->hastaneMi()) {
            return HastaneRiskPdfUretici::pdf($rd);
        }

        if ($rd->pdfHazirMi(true)) {
            return response()->download(Storage::disk('local')->path($rd->pdfDosyasi(true)),
                'risk-degerlendirmesi-'.Str::slug($rd->firma_unvan ?: 'firma').'.pdf');
        }

        @set_time_limit(180);

        return RiskDegerlendirmesiUretici::pdf($rd);
    }
}
