<!doctype html>
<html lang="tr">
<head>
<meta charset="utf-8">
{{-- Uzaktan eğitim portal giriş kartları (08.11.2026) — kesilip çalışanlara dağıtılır. --}}
<style>
    * { font-family: "DejaVu Sans", sans-serif; }
    @page { margin: 18px 22px; }
    body { margin: 0; color: #111; font-size: 10px; }
    h1 { font-size: 13px; margin: 0 0 2px; }
    .alt { font-size: 8.5px; color: #555; margin-bottom: 8px; }
    table.kartlar { width: 100%; border-collapse: separate; border-spacing: 6px; }
    td.kart { width: 50%; border: 1px dashed #777; padding: 9px 11px; vertical-align: top; }
    .ad { font-weight: bold; font-size: 11.5px; }
    .gorev { font-size: 8.5px; color: #555; }
    .satir { margin-top: 5px; }
    .deger { font-family: "DejaVu Sans Mono", monospace; font-weight: bold; font-size: 11px; }
    .not { font-size: 7.5px; color: #555; margin-top: 6px; line-height: 1.4; }
</style>
</head>
<body>
<h1>Uzaktan İSG Eğitimi — Portal Giriş Bilgileri</h1>
<div class="alt">{{ $firma->unvan }} · {{ now()->format('d.m.Y H:i') }} · {{ count($satirlar) }} çalışan · Kartları kesip ilgili çalışana elden verin.</div>

<table class="kartlar">
    @foreach (array_chunk($satirlar, 2) as $cift)
        <tr>
            @foreach ($cift as $r)
                <td class="kart">
                    <div class="ad">{{ $r['ad_soyad'] }}</div>
                    @if ($r['gorev'])<div class="gorev">{{ $r['gorev'] }}</div>@endif
                    <div class="satir">Portal: <span class="deger">{{ preg_replace('~^https?://~', '', $portal) }}</span></div>
                    <div class="satir">Kullanıcı kodu: <span class="deger">{{ $r['kod'] }}</span></div>
                    <div class="satir">Geçici şifre: <span class="deger">{{ $r['sifre'] }}</span></div>
                    <div class="not">İlk girişte şifrenizi değiştirin. Videolar ileri sarılamaz; sekme ya da uygulama değiştirince video durur.
                        Tüm dersleri izleyip final sınavından en az {{ (int) config('isg.uzaktan_egitim.gecme_puani_alt_sinir', 60) }} puan alın.</div>
                </td>
            @endforeach
            @if (count($cift) === 1)<td></td>@endif
        </tr>
    @endforeach
</table>
</body>
</html>
