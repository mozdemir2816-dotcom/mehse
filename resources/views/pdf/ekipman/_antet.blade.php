{{-- Ortak antet: logo | başlık + firma künyesi | ekipman QR'ı; altında ekipman / doküman künyesi. --}}
<table class="antet">
    <tr>
        @if ($logo)<td class="logo"><img src="{{ $logo }}"></td>@endif
        <td class="baslik">
            <h1>{{ $baslik }}</h1>
            <div>{{ $firma->unvan }}</div>
            <div>{{ collect([$firma->sgk_sicil_no ? 'SGK Sicil: '.$firma->sgk_sicil_no : null, trim(($firma->adres ?? '').' '.($firma->ilce ?? '').($firma->il ? ' / '.$firma->il : ''))])->filter()->implode(' · ') }}</div>
        </td>
        <td class="qr"><img src="{{ $qr }}"><div>Talimat ve günlük kontrol için okutun</div></td>
    </tr>
</table>
<table class="kunye">
    <tr>
        <td class="e">Ekipman</td><td>{{ $e->ekipman_adi }}</td>
        <td class="e">Doküman kodu</td><td>{{ $dokumanKodu }}</td>
    </tr>
    <tr>
        <td class="e">Marka / model</td><td>{{ $e->marka_model ?: '—' }}{{ $e->uretim_yili ? ' ('.$e->uretim_yili.')' : '' }}</td>
        <td class="e">Revizyon tarihi</td><td>{{ $revizyonTarihi }}</td>
    </tr>
    <tr>
        <td class="e">Seri no / plaka</td><td>{{ $e->seri_no ?: '—' }}</td>
        <td class="e">Konum</td><td>{{ $e->konum ?: '—' }}</td>
    </tr>
</table>
