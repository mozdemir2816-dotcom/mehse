<!doctype html>
<html lang="tr">
<head>
<meta charset="utf-8">
<style>
    * { font-family: DejaVu Sans, sans-serif; }
    body { margin: 0; color: #111; font-size: 11px; }
    .sayfa { padding: 28px 34px; }
    .baslik { text-align: center; border-bottom: 3px double #111; padding-bottom: 10px; margin-bottom: 14px; }
    .baslik h1 { font-size: 16px; margin: 0 0 4px; }
    .kunye { width: 100%; border-collapse: collapse; font-size: 10px; margin-bottom: 14px; }
    .kunye td { border: 1px solid #999; padding: 5px 8px; }
    .kunye td:first-child { background: #f0f0f0; font-weight: bold; width: 22%; }
    h2 { font-size: 12.5px; border-bottom: 1px solid #7c3aed; padding-bottom: 3px; color: #7c3aed; margin: 16px 0 6px; }
    table.liste { width: 100%; border-collapse: collapse; font-size: 9.5px; margin-bottom: 6px; }
    table.liste th, table.liste td { border: 1px solid #999; padding: 4px 6px; text-align: left; vertical-align: top; }
    table.liste th { background: #f0f0f0; }
    .durum { color: #fff; padding: 1px 5px; border-radius: 3px; font-size: 8.5px; white-space: nowrap; }
    .imza { margin-top: 40px; width: 100%; }
    .imza td { width: 50%; text-align: center; padding-top: 40px; border-top: 1px solid #111; font-size: 10px; }
</style>
</head>
<body>
<div class="sayfa">

    <div class="baslik">
        <h1>İSG KURULU TOPLANTI TUTANAĞI</h1>
        <div style="font-size:11px">{{ $firma?->unvan }}</div>
    </div>

    <table class="kunye">
        <tr>
            <td>Tarih</td><td>{{ $toplanti->tarih?->format('d.m.Y') }}</td>
            <td>Saat</td><td>{{ $toplanti->saat ?: '—' }}</td>
        </tr>
        <tr>
            <td>Yer</td><td>{{ $toplanti->yer ?: '—' }}</td>
            <td>Toplantı Başkanı</td><td>{{ $toplanti->baskan ?: '—' }}</td>
        </tr>
    </table>

    <h2>KATILIMCILAR</h2>
    <table class="liste">
        <tr><th style="width:5%">#</th><th>Ad Soyad</th><th>Görev</th><th style="width:15%">Katılım</th></tr>
        @forelse (($toplanti->katilimcilar ?? []) as $i => $k)
            <tr>
                <td>{{ $i + 1 }}</td>
                <td>{{ $k['ad_soyad'] ?? '—' }}</td>
                <td>{{ $k['gorev'] ?? '—' }}</td>
                <td>{{ ($k['katildi'] ?? false) ? 'Katıldı' : 'Katılmadı' }}</td>
            </tr>
        @empty
            <tr><td colspan="4" style="color:#888">Katılımcı eklenmedi.</td></tr>
        @endforelse
    </table>

    <h2>GÜNDEM</h2>
    <table class="liste">
        <tr><th style="width:5%">#</th><th>Gündem Maddesi</th></tr>
        @forelse (($toplanti->gundem ?? []) as $i => $madde)
            <tr><td>{{ $i + 1 }}</td><td>{{ $madde }}</td></tr>
        @empty
            <tr><td colspan="2" style="color:#888">Gündem maddesi eklenmedi.</td></tr>
        @endforelse
    </table>

    <h2>ALINAN KARARLAR</h2>
    <table class="liste">
        <tr><th style="width:5%">#</th><th>İlgili Gündem</th><th>Karar Metni</th><th>Sorumlu</th><th>Termin</th><th>Durum</th></tr>
        @forelse (($toplanti->kararlar ?? []) as $i => $k)
            @php
                $renk = match ($k['durum'] ?? 'beklemede') {
                    'tamamlandi' => '#10b981',
                    'devam_ediyor' => '#f59e0b',
                    default => '#6b7280',
                };
                $durumEtiket = match ($k['durum'] ?? 'beklemede') {
                    'tamamlandi' => 'Tamamlandı',
                    'devam_ediyor' => 'Devam Ediyor',
                    default => 'Beklemede',
                };
            @endphp
            <tr>
                <td>{{ $i + 1 }}</td>
                <td>{{ $k['gundem_maddesi'] ?? '—' }}</td>
                <td>{{ $k['karar_metni'] ?? '—' }}</td>
                <td>{{ $k['sorumlu'] ?? '—' }}</td>
                <td>{{ $k['termin'] ?? '—' }}</td>
                <td><span class="durum" style="background:{{ $renk }}">{{ $durumEtiket }}</span></td>
            </tr>
        @empty
            <tr><td colspan="6" style="color:#888">Karar alınmadı.</td></tr>
        @endforelse
    </table>

    <table class="imza">
        <tr>
            <td>Toplantı Başkanı<br>(İmza)</td>
            <td>İş Güvenliği Uzmanı<br>(İmza – Kaşe)</td>
        </tr>
    </table>

</div>
</body>
</html>
