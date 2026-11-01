<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

/** Herkese açık sektör sayfaları (/sektor/{slug}) — içerik config/sektorler.php. */
class SektorController extends Controller
{
    public function show(string $slug): View
    {
        $sektor = config("sektorler.{$slug}");
        abort_unless(is_array($sektor), 404);

        return view('pages.sektor_detay', [
            'slug' => $slug,
            'sektor' => $sektor,
            'teklifUrl' => url('/').'?sektor='.$slug.'#teklif',
        ]);
    }
}
