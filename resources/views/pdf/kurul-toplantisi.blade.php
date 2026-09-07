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
    table.kararlar td.sorumlu, table.kararlar td.termin { white-space: nowrap; }
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
            <td>Toplantı No</td><td>{{ $toplanti->toplanti_no ?: '—' }}</td>
            <td>Tarih</td><td>{{ $toplanti->tarih?->format('d.m.Y') }}</td>
        </tr>
        <tr>
            <td>Saat</td><td>{{ $toplanti->saat ?: '—' }}</td>
            <td>Yer</td><td>{{ $toplanti->yer ?: '—' }}</td>
        </tr>
        <tr>
            <td>Toplantı Başkanı</td><td colspan="3">{{ $toplanti->baskan ?: '—' }}</td>
        </tr>
    </table>

    <h2>KATILIMCILAR</h2>
    <table class="liste">
        <tr><th style="width:5%">#</th><th>Ad Soyad</th><th>Görev</th><th style="width:22%">İmza</th></tr>
        @forelse (($toplanti->katilimcilar ?? []) as $i => $k)
            <tr>
                <td>{{ $i + 1 }}</td>
                <td>{{ $k['ad_soyad'] ?? '—' }}</td>
                <td>{{ $k['gorev'] ?? '—' }}</td>
                <td>{{ ($k['katildi'] ?? false) ? '' : 'Katılmadı' }}</td>
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
    <table class="liste kararlar">
        <tr>
            <th style="width:4%">#</th>
            <th style="width:24%">İlgili Gündem</th>
            <th>Karar Metni</th>
            <th style="width:13%">Sorumlu</th>
            <th style="width:11%">Termin</th>
        </tr>
        @forelse (($toplanti->kararlar ?? []) as $i => $k)
            <tr>
                <td>{{ $i + 1 }}</td>
                <td>{{ $k['gundem_maddesi'] ?? '—' }}</td>
                <td>{{ $k['karar_metni'] ?? '—' }}</td>
                <td class="sorumlu">{{ $k['sorumlu'] ?: '—' }}</td>
                <td class="termin">{{ $k['termin'] ?: '—' }}</td>
            </tr>
        @empty
            <tr><td colspan="5" style="color:#888">Karar alınmadı.</td></tr>
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
