<!doctype html>
<html lang="tr">
<head>
<meta charset="utf-8">
{{-- Firmaya tanımlı çalışma talimatları listesi. Üretici: App\Support\TalimatListesiUretici::pdf() --}}
<style>
    @page { margin: 26px 30px 40px; }
    * { font-family: DejaVu Sans, sans-serif; }
    body { margin: 0; color: #1e293b; font-size: 10px; }
    .ust { width: 100%; border-collapse: collapse; border: 1px solid #94a3b8; margin-bottom: 10px; }
    .ust td { vertical-align: middle; padding: 8px 10px; }
    .ust .firma { width: 26%; text-align: center; border-right: 1px solid #cbd5e1; }
    .ust .firma img { max-width: 170px; max-height: 62px; }
    .ust .baslik { text-align: center; background: #eff6ff; font-size: 15px; font-weight: bold; color: #1e3a5f; line-height: 1.3; border-right: 1px solid #cbd5e1; }
    .ust .bilgi { width: 24%; font-size: 9px; padding: 4px 8px; }
    .ust .bilgi td { padding: 2px 0; border: none; }
    h2 { font-size: 12px; color: #1e3a5f; margin: 14px 0 6px; padding-bottom: 3px; border-bottom: 2px solid #2563eb; }
    table.liste { width: 100%; border-collapse: collapse; font-size: 9px; }
    table.liste th { background: #dbeafe; color: #1e3a5f; text-align: left; padding: 5px 6px; border: 1px solid #cbd5e1; }
    table.liste td { padding: 5px 6px; border: 1px solid #cbd5e1; vertical-align: top; }
    .ortala { text-align: center; }
    .soluk { color: #94a3b8; }
    .talimat { page-break-inside: avoid; margin-bottom: 10px; }
    .talimat h3 { font-size: 10.5px; margin: 0 0 3px; color: #1e3a5f; }
    .talimat .alt { font-size: 8.5px; color: #64748b; margin-bottom: 3px; }
    .talimat ol { margin: 0; padding-left: 16px; font-size: 9px; line-height: 1.5; }
    table.imza { width: 100%; margin-top: 30px; page-break-inside: avoid; }
    table.imza td { width: 50%; text-align: center; padding-top: 34px; font-size: 9px; color: #475569; }
    .altbilgi { position: fixed; bottom: -24px; left: 0; right: 0; font-size: 8px; color: #64748b; border-top: 1px solid #cbd5e1; padding-top: 4px; }
</style>
</head>
<body>

<div class="altbilgi">Çalışma Talimatları Listesi · {{ $firma->unvan }} · {{ now()->format('d.m.Y') }}</div>

<table class="ust">
    <tr>
        <td class="firma">@if ($logo)<img src="{{ $logo }}">@endif</td>
        <td class="baslik">ÇALIŞMA TALİMATLARI LİSTESİ</td>
        <td class="bilgi">
            <table>
                <tr><td><strong>Tarih:</strong> {{ now()->format('d.m.Y') }}</td></tr>
                <tr><td><strong>Talimat sayısı:</strong> {{ $talimatlar->count() }}</td></tr>
            </table>
        </td>
    </tr>
</table>

<table class="liste" style="margin-bottom:4px">
    <tr><td style="width:16%;background:#f1f5f9;font-weight:bold;color:#475569">İşyeri</td><td>{{ $firma->unvan }}</td></tr>
    <tr><td style="background:#f1f5f9;font-weight:bold;color:#475569">Adres</td><td>{{ collect([$firma->adres, $firma->ilce, $firma->il])->filter()->implode(', ') ?: '—' }}</td></tr>
</table>

<h2>Firmaya Tanımlı Talimatlar</h2>
<table class="liste">
    <tr>
        <th style="width:4%">#</th>
        <th>Talimat Adı</th>
        <th style="width:17%">Kategori</th>
        <th style="width:26%">Gerekli KKD</th>
        <th style="width:13%">İçerik</th>
        <th style="width:10%">Hazırlanma</th>
    </tr>
    @forelse ($talimatlar as $i => $t)
        <tr>
            <td class="ortala">{{ $i + 1 }}</td>
            <td>{{ $t->baslik }}</td>
            <td>{{ $t->kategoriEtiketi() ?: '—' }}</td>
            <td>{{ implode(', ', $t->kkdler ?? []) ?: '—' }}</td>
            <td>{{ \App\Support\TalimatListesiUretici::icerikEtiketi($t) }}</td>
            <td style="white-space:nowrap">{{ $t->created_at?->format('d.m.Y') }}</td>
        </tr>
    @empty
        <tr><td colspan="6" class="soluk">Bu firmaya henüz talimat tanımlanmadı.</td></tr>
    @endforelse
</table>

<table class="imza">
    <tr>
        <td>Hazırlayan<br>İş Güvenliği Uzmanı<br>(İmza – Kaşe)</td>
        <td>Onaylayan<br>İşveren / İşveren Vekili<br>(İmza – Kaşe)</td>
    </tr>
</table>

@if ($maddelerDahil && $talimatlar->contains(fn ($t) => $t->maddeler))
    <h2 style="page-break-before:always;margin-top:0">Talimat İçerikleri</h2>
    @foreach ($talimatlar as $i => $t)
        @if ($t->maddeler)
            <div class="talimat">
                <h3>{{ $i + 1 }}. {{ $t->baslik }}</h3>
                <div class="alt">{{ $t->kategoriEtiketi() }}@if ($t->kkdler) · KKD: {{ implode(', ', $t->kkdler) }}@endif</div>
                <ol>
                    @foreach ($t->maddeler as $madde)
                        <li>{{ $madde }}</li>
                    @endforeach
                </ol>
            </div>
        @endif
    @endforeach
@endif

</body>
</html>
