<!doctype html>
<html lang="tr">
<head>
<meta charset="utf-8">
{{--
    İSG Kurulu Toplantı Tutanağı — isgsuite.tr tutanak düzeni (üst künye bloğu,
    kurul üyeleri & imza sütunu, gündem, kararlar). Karar "durum"u ve toplantı
    iş akışı durumu resmî tutanağa basılmaz (KurulToplantisiTest).
    Üretici: App\Support\KurulToplantisiUretici::pdf()
--}}
<style>
    @page { margin: 26px 30px 40px; }
    * { font-family: DejaVu Sans, sans-serif; }
    body { margin: 0; color: #1e293b; font-size: 10px; }
    .ust { width: 100%; border-collapse: collapse; border: 1px solid #94a3b8; margin-bottom: 10px; }
    .ust td { vertical-align: middle; padding: 8px 10px; }
    .ust .firma { width: 26%; font-size: 12px; font-weight: bold; color: #1e3a5f; border-right: 1px solid #cbd5e1; }
    .ust .baslik { text-align: center; background: #eff6ff; font-size: 15px; font-weight: bold; color: #1e3a5f; line-height: 1.3; border-right: 1px solid #cbd5e1; }
    .ust .bilgi { width: 27%; font-size: 9px; padding: 4px 8px; }
    .ust .bilgi table { width: 100%; border-collapse: collapse; }
    .ust .bilgi td { padding: 2px 0; border: none; }
    .ust .bilgi td.e { font-weight: bold; color: #475569; width: 48%; }
    table.kunye { width: 100%; border-collapse: collapse; margin-bottom: 12px; font-size: 9.5px; }
    table.kunye td { border: 1px solid #cbd5e1; padding: 5px 7px; }
    table.kunye td.e { background: #f1f5f9; font-weight: bold; color: #475569; width: 16%; }
    h2 { font-size: 12px; color: #1e3a5f; margin: 14px 0 6px; padding-bottom: 3px; border-bottom: 2px solid #2563eb; }
    table.liste { width: 100%; border-collapse: collapse; font-size: 9px; }
    table.liste th { background: #dbeafe; color: #1e3a5f; text-align: left; padding: 5px 6px; border: 1px solid #cbd5e1; }
    table.liste td { padding: 5px 6px; border: 1px solid #cbd5e1; vertical-align: top; }
    .ortala { text-align: center; }
    .soluk { color: #94a3b8; }
    .altbilgi { position: fixed; bottom: -24px; left: 0; right: 0; font-size: 8px; color: #64748b; border-top: 1px solid #cbd5e1; padding-top: 4px; }
</style>
</head>
<body>
@php
    $rolAdi = fn (?string $rol) => $rol ? config("isg.kurul_toplantisi.roller.{$rol}.ad", $rol) : '—';
    $katilimcilar = $toplanti->katilimcilar ?? [];
    $adres = collect([$firma?->adres, $firma?->ilce, $firma?->il])->filter()->implode(', ');
@endphp

<div class="altbilgi">
    İSG Kurulu Toplantı Tutanağı · {{ $firma?->unvan }} · Belge No: {{ $toplanti->belge_no ?: '—' }} · Rev: {{ $toplanti->revizyon_no ?: '00' }}
</div>

<table class="ust">
    <tr>
        <td class="firma">{{ $firma?->unvan }}</td>
        <td class="baslik">İŞ SAĞLIĞI VE GÜVENLİĞİ KURULU<br>TOPLANTI TUTANAĞI</td>
        <td class="bilgi">
            <table>
                <tr><td class="e">Belge No</td><td>{{ $toplanti->belge_no ?: '—' }}</td></tr>
                <tr><td class="e">Toplantı No</td><td>{{ $toplanti->toplanti_no ?: '—' }}</td></tr>
                <tr><td class="e">Revizyon</td><td>{{ $toplanti->revizyon_no ?: '00' }}</td></tr>
                <tr><td class="e">Oluşturma</td><td>{{ now()->format('d.m.Y') }}</td></tr>
            </table>
        </td>
    </tr>
</table>

<table class="kunye">
    <tr>
        <td class="e">İşyeri</td><td>{{ $firma?->unvan }}</td>
        <td class="e">Adres</td><td>{{ $adres ?: '—' }}</td>
    </tr>
    <tr>
        <td class="e">Toplantı tarihi</td><td>{{ $toplanti->tarih?->format('d.m.Y') ?: '—' }}</td>
        <td class="e">Saat</td><td>{{ $toplanti->saatAraligi() ?: '—' }}</td>
    </tr>
    <tr>
        <td class="e">Toplantı yeri</td><td>{{ $toplanti->yer ?: '—' }}</td>
        <td class="e">Toplantı türü</td><td>{{ $toplanti->turEtiketi() }}</td>
    </tr>
    <tr>
        <td class="e">Toplantı başkanı</td><td>{{ $toplanti->baskan ?: '—' }}</td>
        <td class="e">Sonraki toplantı</td><td>{{ $toplanti->sonraki_toplanti?->format('d.m.Y') ?: '—' }}</td>
    </tr>
    <tr>
        <td class="e">Katılım</td>
        <td colspan="3">
            @php $katilan = collect($katilimcilar)->where('katildi', true)->count(); @endphp
            Katılan: {{ $katilan }} / {{ count($katilimcilar) }} kişi{{ count($katilimcilar) > $katilan ? ' · Katılmayan: '.(count($katilimcilar) - $katilan) : '' }}
        </td>
    </tr>
</table>

{{-- Katılım yerine İmza sütunu (kullanıcı kararı, KurulToplantisiTest): katılanın
     hücresi ıslak imza için boş, katılmayanda "Katılmadı". --}}
<h2>Kurul Üyeleri ve Katılımcılar</h2>
<table class="liste">
    <tr>
        <th style="width:4%">#</th>
        <th style="width:23%">Ad Soyad</th>
        <th style="width:21%">Görevi / Unvanı</th>
        <th style="width:26%">Kurul Rolü</th>
        <th>İmza</th>
    </tr>
    @forelse ($katilimcilar as $i => $k)
        <tr>
            <td class="ortala">{{ $i + 1 }}</td>
            <td>{{ $k['ad_soyad'] ?? '—' }}</td>
            <td>{{ $k['gorev'] ?? '—' }}</td>
            <td>{{ $rolAdi($k['rol'] ?? null) }}</td>
            <td style="height:26px" class="ortala">{{ ($k['katildi'] ?? false) ? '' : 'Katılmadı' }}</td>
        </tr>
    @empty
        <tr><td colspan="5" class="soluk">Katılımcı eklenmedi.</td></tr>
    @endforelse
</table>

<h2>Gündem</h2>
<table class="liste">
    @forelse (($toplanti->gundem ?? []) as $i => $madde)
        <tr><td style="width:4%" class="ortala">{{ $i + 1 }}</td><td>{{ $madde }}</td></tr>
    @empty
        <tr><td class="soluk">Gündem maddesi eklenmedi.</td></tr>
    @endforelse
</table>

<h2>Kararlar ve Takip</h2>
<table class="liste">
    <tr>
        <th style="width:4%">#</th>
        <th style="width:24%">İlgili Gündem</th>
        <th>Karar Metni</th>
        <th style="width:14%">Sorumlu</th>
        <th style="width:11%">Termin</th>
    </tr>
    @forelse (($toplanti->kararlar ?? []) as $i => $k)
        <tr>
            <td class="ortala">{{ $i + 1 }}</td>
            <td>{{ $k['gundem_maddesi'] ?? '—' }}</td>
            <td>{{ $k['karar_metni'] ?? '—' }}</td>
            <td>{{ ($k['sorumlu'] ?? null) ?: '—' }}</td>
            <td style="white-space:nowrap">{{ filled($k['termin'] ?? null) ? \Illuminate\Support\Carbon::parse($k['termin'])->format('d.m.Y') : '—' }}</td>
        </tr>
    @empty
        <tr><td colspan="5" class="soluk">Karar alınmadı.</td></tr>
    @endforelse
</table>

@if (filled($toplanti->notlar))
    <h2>Notlar</h2>
    <div style="font-size:9.5px;white-space:pre-line">{{ $toplanti->notlar }}</div>
@endif

</body>
</html>
