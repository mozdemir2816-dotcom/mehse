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
    .ust .firma { width: 26%; text-align: center; border-right: 1px solid #cbd5e1; }
    .ust .firma img { max-width: 170px; max-height: 62px; }
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
    $katilimcilar = $toplanti->katilimcilar ?? [];
    $kisiler = \App\Support\KurulUyeleri::tutanakKatilimcilari($toplanti);
    $katilanlar = array_values(array_filter($kisiler, fn ($k) => $k['katildi']));
    $katilmayanlar = array_values(array_filter($kisiler, fn ($k) => ! $k['katildi']));
    $adres = collect([$firma?->adres, $firma?->ilce, $firma?->il])->filter()->implode(', ');

    // Başlığın sol kutusu: firma logosu, yoksa OSGB logosu (kullanıcı kararı 06.10.2026 — unvan yazılmaz).
    $logo = \App\Support\KurulToplantisiUretici::logoYolu($firma);
@endphp

<div class="altbilgi">
    İSG Kurulu Toplantı Tutanağı · {{ $firma?->unvan }} · Belge No: {{ $toplanti->belge_no ?: '—' }} · Rev: {{ $toplanti->revizyon_no ?: '00' }}
</div>

<table class="ust">
    <tr>
        <td class="firma">@if ($logo)<img src="{{ $logo }}">@endif</td>
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
        <td class="e">Toplantı başkanı</td><td>İşveren / İşveren Vekili</td>
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

{{-- 1. SAYFA: katılanlar + gündem (konu başlıkları). Görevi = çalışan kaydı
     (işe giriş bildirgesi), Kuruldaki Görevi = kurul rolü / Atama Yazıları
     (KurulUyeleri::tutanakKatilimcilari). Kaşeli görevlilerin adı basılmaz. --}}
<h2>Toplantıya Katılanlar</h2>
<table class="liste">
    <tr>
        <th style="width:4%">#</th>
        <th style="width:26%">Ad Soyad</th>
        <th style="width:30%">Görevi (İşe Giriş Bildirgesi)</th>
        <th>Kuruldaki Görevi</th>
    </tr>
    @forelse ($katilanlar as $i => $k)
        <tr>
            <td class="ortala">{{ $i + 1 }}</td>
            <td>{{ $k['ad_basilir'] ? $k['ad_soyad'] : '' }}</td>
            <td>{{ $k['is_gorevi'] }}</td>
            <td>{{ $k['kurul_gorevi'] }}</td>
        </tr>
    @empty
        <tr><td colspan="4" class="soluk">Katılımcı eklenmedi.</td></tr>
    @endforelse
</table>
@if ($katilmayanlar)
    <div style="font-size:8.5px;color:#64748b;margin-top:4px">
        Katılmayan: {{ collect($katilmayanlar)->map(fn ($k) => ($k['ad_basilir'] ? $k['ad_soyad'].' — ' : '').$k['kurul_gorevi'])->implode('; ') }}
    </div>
@endif

<h2>Gündem (Toplantı Konuları)</h2>
<table class="liste">
    @forelse (($toplanti->gundem ?? []) as $i => $madde)
        <tr><td style="width:4%" class="ortala">{{ $i + 1 }}</td><td>{{ $madde }}</td></tr>
    @empty
        <tr><td class="soluk">Gündem maddesi eklenmedi.</td></tr>
    @endforelse
</table>

{{-- 2. SAYFADAN İTİBAREN: kararlar --}}
<h2 style="page-break-before:always;margin-top:0">Kararlar ve Takip</h2>
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

{{-- SON: katılanların ıslak imza yeri (kullanıcı kararı 06.10.2026). Tablo
     bölünmesin diye satırlar page-break-inside:avoid. --}}
<h2>Katılımcı İmzaları</h2>
<div style="font-size:8.5px;color:#475569;margin-bottom:4px">
    Yukarıdaki kararlar toplantıya katılan kurul üyelerince alınmış ve imza altına alınmıştır.
</div>
<table class="liste imza-tablo">
    <tr>
        <th style="width:4%">#</th>
        <th style="width:28%">Ad Soyad</th>
        <th style="width:34%">Kuruldaki Görevi</th>
        <th>İmza</th>
    </tr>
    @forelse ($katilanlar as $i => $k)
        <tr style="page-break-inside:avoid">
            <td class="ortala" style="height:34px;vertical-align:middle">{{ $i + 1 }}</td>
            <td style="vertical-align:middle">{{ $k['ad_basilir'] ? $k['ad_soyad'] : '' }}</td>
            <td style="vertical-align:middle">{{ $k['kurul_gorevi'] }}</td>
            <td></td>
        </tr>
    @empty
        <tr><td colspan="4" class="soluk">Katılımcı eklenmedi.</td></tr>
    @endforelse
</table>

</body>
</html>
