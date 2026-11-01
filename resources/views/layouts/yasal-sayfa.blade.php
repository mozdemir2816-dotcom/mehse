{{-- Herkese açık yasal sayfalar (/kvkk, /cerez-politikasi). --}}
@extends('layouts.site')

@section('govde')
<main id="icerik" class="yasal-sayfa">
    <div class="kap yasal-kap">
        <h1>@yield('baslik')</h1>
        <div class="yasal-metin">
            @yield('icerik')
        </div>
    </div>
</main>
@endsection
