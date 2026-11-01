{{-- App\Support\SozlesmePdf — A4, DejaVu Sans (Türkçe karakter). Madde metni yasal.sozlesme'den. --}}
@php
    $musteriAdres = trim(implode(' ', array_filter([$user->fatura_adresi, collect([$user->fatura_ilce, $user->fatura_il])->filter()->implode(' / ')])));
    $vd = trim((string) $user->vergi_dairesi);
    if ($vd !== '' && ! str_contains(mb_strtolower($vd), 'vergi dairesi')) {
        $vd .= ' Vergi Dairesi';
    }
@endphp
<!doctype html>
<html lang="tr">
<head>
<meta charset="utf-8">
<title>Hizmet ve Kullanım Sözleşmesi</title>
<style>
    @page { margin: 28px 34px 40px; }
    * { font-family: DejaVu Sans, sans-serif; }
    body { margin: 0; color: #0f172a; font-size: 9.5px; line-height: 1.55; }
    .ust { border-bottom: 3px solid #0284c7; padding-bottom: 8px; margin-bottom: 14px; }
    .ust table { width: 100%; border-collapse: collapse; }
    .marka { font-size: 17px; font-weight: bold; color: #0f172a; }
    .marka span { color: #0284c7; }
    .marka-alt { font-size: 8.5px; color: #475569; }
    .ust-sag { text-align: right; font-size: 8.5px; color: #475569; }
    h1 { font-size: 14px; text-align: center; margin: 4px 0 12px; text-transform: uppercase; letter-spacing: .5px; }
    .taraflar { width: 100%; border-collapse: collapse; margin-bottom: 14px; }
    .taraflar th { background: #0f172a; color: #fff; font-size: 9.5px; padding: 5px 8px; text-align: left; width: 50%; }
    .taraflar td { border: 1px solid #cbd5e1; padding: 6px 8px; vertical-align: top; font-size: 9px; }
    .taraflar .k { color: #64748b; font-size: 8px; }
    .metin h3 { font-size: 10.5px; margin: 10px 0 3px; color: #0f172a; }
    .metin p { margin: 0 0 5px; text-align: justify; }
    .metin mark { background: #fef08a; }
    .kase { margin-top: 18px; border: 2px solid #047857; border-radius: 6px; padding: 8px 12px; width: 62%; }
    .kase-baslik { color: #047857; font-weight: bold; font-size: 11px; letter-spacing: .5px; }
    .kase table { border-collapse: collapse; margin-top: 4px; }
    .kase td { padding: 1px 8px 1px 0; font-size: 8.5px; }
    .kase-yok { border-color: #94a3b8; }
    .kase-yok .kase-baslik { color: #475569; }
    .uyari { color: #b45309; font-size: 8.5px; margin-top: 3px; }
    .alt { position: fixed; bottom: -26px; left: 0; right: 0; font-size: 7.5px; color: #94a3b8; text-align: center; }
</style>
</head>
<body>
<div class="alt">mehse.com · Hizmet ve Kullanım Sözleşmesi · {{ $metin['revizyon'] ?? '' }} · Oluşturma: {{ $olusturma->format('d.m.Y H:i') }}</div>

<div class="ust">
    <table>
        <tr>
            <td>
                <div class="marka">Mehse <span>İSG</span></div>
                <div class="marka-alt">İş Sağlığı ve Güvenliği Yönetim Platformu · mehse.com</div>
            </td>
            <td class="ust-sag">
                {{ $metin['revizyon'] ?? '' }}<br>
                {{ $saglayici['eposta'] }} · {{ $saglayici['telefon'] }}
            </td>
        </tr>
    </table>
</div>

<h1>Hizmet ve Kullanım Sözleşmesi</h1>

<table class="taraflar">
    <tr><th>SAĞLAYICI</th><th>MÜŞTERİ</th></tr>
    <tr>
        <td>
            <div class="k">Unvan</div>{{ $saglayici['unvan'] }}<br>
            <div class="k">Vergi dairesi / no</div>{{ $saglayici['vergi_dairesi'] }} / {{ $saglayici['vergi_no'] ?: '—' }}<br>
            <div class="k">Adres</div>{{ $saglayici['adres'] }}<br>
            <div class="k">E-posta / telefon</div>{{ $saglayici['eposta'] }} · {{ $saglayici['telefon'] }}
        </td>
        <td>
            <div class="k">Unvan / ad soyad</div>{{ $user->fatura_unvan ?: $user->name }}<br>
            <div class="k">Vergi dairesi / VKN-TCKN</div>{{ $vd !== '' ? $vd.' / ' : '' }}{{ $user->vergi_no ?: '—' }}<br>
            <div class="k">Adres</div>{{ $musteriAdres ?: '—' }}<br>
            <div class="k">E-posta / telefon</div>{{ $user->email }}{{ $user->telefon ? ' · '.$user->telefon : '' }}
        </td>
    </tr>
</table>

<div class="metin">
    @include($metin['gorunum'])
</div>

@if ($onay)
    <div class="kase">
        <div class="kase-baslik">✓ ELEKTRONİK OLARAK ONAYLANMIŞTIR</div>
        <table>
            <tr><td>Onaylayan</td><td><strong>{{ $user->name }}</strong> ({{ $user->email }})</td></tr>
            <tr><td>Onay tarihi</td><td>{{ $onayAt?->format('d.m.Y H:i') ?? '—' }} (Türkiye saati)</td></tr>
            <tr><td>Onaylanan sürüm</td><td>{{ $onay['revizyon'] ?? '—' }}</td></tr>
            <tr><td>IP adresi</td><td>{{ $onay['ip'] ?? '—' }}</td></tr>
        </table>
        @unless ($guncel)
            <div class="uyari">Bu belgedeki metin güncel sürümdür ({{ $metin['revizyon'] ?? '' }}); onay önceki sürüme verilmiştir. Güncel sürümü panelde Güvenlik sayfasından onaylayabilirsiniz.</div>
        @endunless
    </div>
@else
    <div class="kase kase-yok">
        <div class="kase-baslik">ELEKTRONİK ONAY KAYDI BULUNMUYOR</div>
        <div style="font-size:8.5px">Bu hesap için kayıtlı bir sözleşme onayı yok. Panelde Güvenlik sayfasından onaylayabilirsiniz.</div>
    </div>
@endif
</body>
</html>
