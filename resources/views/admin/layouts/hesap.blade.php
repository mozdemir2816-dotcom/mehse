{{-- Panel dışı sade hesap ekranları (süre doldu, paketler) — CheckTrialPeriod süresi dolan
     kullanıcıyı panelin menüsüne sokmadığı için bunlar Filament düzeni olmadan çizilir. --}}
{{-- $user ve $waNo rota tarafından verilir (AdminPanelProvider::authenticatedRoutes). --}}
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>@yield('baslik') | Mehse İSG</title>
    <link rel="stylesheet" href="{{ asset('css/hesap.css') }}">
</head>
<body>
<header class="h-ust">
    <div class="h-kap h-ust-ic">
        <a href="{{ url('/admin') }}" class="h-logo"><span class="h-logo-kutu"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 3l8 3v6c0 4.5-3.2 8.2-8 9-4.8-.8-8-4.5-8-9V6l8-3z"/><path d="M8.5 12l2.5 2.5 4.5-5"/></svg></span>Mehse İSG</a>
        <div class="h-ust-sag">
            <span class="h-kisi">{{ $user?->name }}</span>
            <form method="POST" action="{{ route('filament.admin.auth.logout') }}">
                @csrf
                <button type="submit" class="h-dugme h-dugme-cerceve">Güvenli Çıkış</button>
            </form>
        </div>
    </div>
</header>
<main class="h-kap h-icerik">
    @yield('icerik')
</main>
</body>
</html>
