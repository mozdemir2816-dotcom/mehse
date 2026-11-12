<!doctype html>
<html lang="tr">
<head>
@include('ekipman-operator._stil')
<title>{{ $ekipman->ekipman_adi }} — PIN</title>
</head>
<body>
<header class="bas">
    <div class="firma">{{ $ekipman->firma?->unvan }}</div>
    <h1>{{ $ekipman->ekipman_adi }}</h1>
</header>
<main class="sar">
    <form method="post" action="{{ route('ekipman.operator.pin', $ekipman->qr_anahtar) }}" class="kart">
        @csrf
        <label class="etiket" for="pin">Makine PIN kodu</label>
        <p class="soluk" style="margin:0 0 10px">PIN'i bakım sorumlusundan ya da İSG uzmanından öğrenebilirsiniz.</p>
        <input id="pin" name="pin" type="password" inputmode="numeric" autocomplete="one-time-code" maxlength="12" autofocus required>
        @error('pin')<div class="hata" role="alert">{{ $message }}</div>@enderror
        <button class="dugme" type="submit" style="width:100%;margin-top:14px">Devam</button>
    </form>
</main>
</body>
</html>
