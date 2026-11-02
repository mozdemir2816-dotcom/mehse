<!doctype html>
<html lang="tr">
<head>
<meta charset="utf-8">
<style>
    /* İSG Tespit ve Öneri Defteri (A4 dikey) — 02.11.2026: resmî künye tablosu (unvan, SGK sicil, NACE /
       tehlike sınıfı, düzenleme tarihi), dengeli imza kutuları (İGU adı-unvanı-belge no / işveren),
       boş öneriye mevzuata uygun varsayılan, dayanak italik-gri. Başlık "onaylı nüsha" iddiası taşımaz:
       bu PDF onaylı deftere işlenmek üzere hazırlanır. */
    @page { margin: 26px 32px 30px 32px; }
    * { font-family: DejaVu Sans, sans-serif; }
    body { margin: 0; color: #111; font-size: 10.5px; }

    .baslik { text-align: center; border-bottom: 3px double #111; padding-bottom: 8px; margin: 0 0 10px; }
    .baslik h1 { font-size: 15px; margin: 0 0 3px; letter-spacing: .3px; }
    .baslik .alt { font-size: 8.5px; color: #555; }

    table.kunye { width: 100%; border-collapse: collapse; table-layout: fixed; font-size: 9.5px; margin: 0 0 12px; }
    table.kunye td { border: 1px solid #777; padding: 5px 8px; vertical-align: top; }
    table.kunye td.e { background: #eef0f3; font-weight: bold; width: 22%; }

    .madde { border: 1px solid #999; border-radius: 3px; padding: 7px 10px; margin-bottom: 7px; page-break-inside: avoid; }
    .oncelik { display: inline-block; color: #fff; padding: 1px 7px; border-radius: 3px; font-size: 8.5px; font-weight: bold; margin-bottom: 4px; }
    .tespit { font-weight: bold; font-size: 10.5px; margin-bottom: 3px; }
    .oneri { font-size: 10px; margin-bottom: 2px; }
    .oneri .e { font-weight: bold; }
    .oneri.varsayilan { color: #444; }
    .dayanak { font-size: 8.5px; color: #6b7280; font-style: italic; margin-top: 2px; }
    .satirli { white-space: pre-line; }
    .on-bilgi { background: #2563eb; }

    table.imza { width: 100%; border-collapse: collapse; table-layout: fixed; margin-top: 30px; page-break-inside: avoid; }
    table.imza td.kutu { width: 45%; text-align: center; vertical-align: top; font-size: 10px; line-height: 1.5; }
    table.imza td.ara { width: 10%; }
    table.imza .cizgi { height: 42px; border-bottom: 1px solid #111; margin-bottom: 5px; }
    table.imza .ad { font-weight: bold; font-size: 11px; }
    table.imza .kucuk { color: #555; font-size: 8.5px; }

    .foto-sayfa { page-break-before: always; }
    .foto-sayfa .baslik2 { font-size: 10.5px; font-weight: bold; margin: 0 0 6px; }
    .foto-sayfa img { max-width: 100%; max-height: 860px; }
</style>
</head>
<body>
@php
    $uzman = \App\Support\FirmaImza::uzman($firma);
    $isveren = \App\Support\FirmaImza::isveren($firma);
    $maddeler = array_values($defter->maddeler ?? []);
    $oncelikler = [
        'kritik' => ['Kritik Öncelik', '#991b1b'],
        'yuksek' => ['Yüksek Öncelik', '#ef4444'],
        'orta' => ['Orta Öncelik', '#d97706'],
        'dusuk' => ['Düşük Öncelik', '#6b7280'],
    ];
    $varsayilanOneri = 'Mevzuata uygun koruyucu tedbirler ivedilikle alınmalı ve uygunsuzluk giderilmelidir.';
    $fotoYolu = fn (array $m) => \Illuminate\Support\Facades\Storage::disk('public')->path((string) ($m['foto_yolu'] ?? ''));
    $fotoVar = fn (array $m) => filled($m['foto_yolu'] ?? null) && is_file($fotoYolu($m));
    $nace = trim(($firma?->nace_kodu ?? '').($firma?->nace_aciklama ? ' — '.$firma->nace_aciklama : ''));
@endphp

<div class="baslik">
    <h1>İŞ SAĞLIĞI VE GÜVENLİĞİ TESPİT VE ÖNERİ DEFTERİ</h1>
    <div class="alt">6331 sayılı İş Sağlığı ve Güvenliği Kanunu ve İSG Hizmetleri Yönetmeliği uyarınca onaylı deftere işlenmek üzere hazırlanmıştır.</div>
</div>

<table class="kunye">
    <tr><td class="e">İşyeri Unvanı</td><td colspan="3"><strong>{{ $firma?->unvan ?: '—' }}</strong></td></tr>
    <tr><td class="e">Adres</td><td colspan="3">{{ $firma?->adres ?: '—' }}</td></tr>
    <tr>
        <td class="e">SGK Sicil No</td><td>{{ $firma?->sgk_sicil_no ?: '—' }}</td>
        <td class="e">Düzenleme Tarihi</td><td>{{ now()->format('d.m.Y') }}</td>
    </tr>
    <tr>
        <td class="e">NACE Kodu / Tehlike Sınıfı</td>
        <td colspan="3">{{ $nace ?: '—' }} / <strong>{{ $firma?->tehlike_sinifi ? $firma->tehlikeSinifiEtiketi() : '—' }}</strong></td>
    </tr>
    @if ($defter->genel_not)
        <tr><td class="e">Genel Not</td><td colspan="3">{{ $defter->genel_not }}</td></tr>
    @endif
</table>

@forelse ($maddeler as $i => $m)
    @php
        [$etiket, $renk] = $oncelikler[$m['oncelik'] ?? 'orta'] ?? $oncelikler['orta'];
        $oneri = trim((string) ($m['oneri'] ?? ''));
    @endphp
    <div class="madde">
        @if (($m['tur'] ?? null) === 'bilgilendirme')
            <span class="oncelik on-bilgi">Ön Bilgilendirme{{ ! empty($m['tarih']) ? ' — '.$m['tarih'] : '' }}</span>
            <div class="tespit satirli">{{ $i + 1 }}. {{ $m['tespit'] ?? '' }}</div>
            <div class="oneri satirli"><span class="e">Firmanın alması gereken önlemler:</span>
{{ $oneri !== '' ? $oneri : $varsayilanOneri }}</div>
        @else
            <span class="oncelik" style="background:{{ $renk }}">{{ $etiket }}</span>
            <div class="tespit satirli">{{ $i + 1 }}. {{ $m['tespit'] ?? '' }}</div>
            <div class="oneri satirli {{ $oneri === '' ? 'varsayilan' : '' }}"><span class="e">Öneri:</span> {{ $oneri !== '' ? $oneri : $varsayilanOneri }}</div>
        @endif
        @if (filled($m['dayanak'] ?? null))
            <div class="dayanak">Dayanak: {{ $m['dayanak'] }}</div>
        @endif
    </div>
@empty
    <p style="color:#888">Madde eklenmedi.</p>
@endforelse

<table class="imza">
    <tr>
        <td class="kutu">
            <div class="cizgi"></div>
            <div class="ad">{{ $uzman['ad'] ?: '.............................' }}</div>
            <div>{{ $uzman['unvan'] }}</div>
            @if ($uzman['belge_no'])<div>Belge No: {{ $uzman['belge_no'] }}</div>@endif
            <div class="kucuk">(İmza – Kaşe)</div>
        </td>
        <td class="ara"></td>
        <td class="kutu">
            <div class="cizgi"></div>
            <div class="ad">{{ $isveren ?: '.............................' }}</div>
            <div>İşveren / İşveren Vekili</div>
            <div class="kucuk">(İmza)</div>
        </td>
    </tr>
</table>

{{-- Fotoğraf kanıtı sayfaları: yalnız diskte gerçekten bulunan fotoğraflar --}}
@php $fotoNo = 0; @endphp
@foreach ($maddeler as $i => $m)
    @if ($fotoVar($m))
        @php $fotoNo++; @endphp
        <div class="foto-sayfa">
            <div class="baslik">
                <h1>İŞ SAĞLIĞI VE GÜVENLİĞİ TESPİT VE ÖNERİ DEFTERİ</h1>
                <div class="alt">{{ $firma?->unvan }}</div>
            </div>
            <div class="baslik2">FOTOĞRAF KANITI {{ $fotoNo }} · Madde {{ $i + 1 }} — {{ $m['tespit'] ?? '' }}</div>
            <img src="{{ $fotoYolu($m) }}">
        </div>
    @endif
@endforeach

</body>
</html>
