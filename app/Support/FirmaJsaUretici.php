<?php

namespace App\Support;

use App\Models\FirmaJsa;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * config isg.raporlar.kaynaklar adaptörü: Raporlar / "Evrakları İndir" akışı
 * kayıtları tek argümanla (pdf_metod($kayit)) üretir; FirmaJsa ataması ise
 * JsaUretici'ye (şablon + firma) ayrıştırılmalı. Bu köprü onu yapar.
 */
class FirmaJsaUretici
{
    public static function pdf(FirmaJsa $atama): StreamedResponse
    {
        $atama->loadMissing(['jsaSablonu', 'firma']);

        return JsaUretici::pdf($atama->jsaSablonu, $atama->firma);
    }
}
