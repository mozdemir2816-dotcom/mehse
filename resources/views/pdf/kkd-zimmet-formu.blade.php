<!doctype html>
<html lang="tr">
<head>
<meta charset="utf-8">
<style>
    * { font-family: DejaVu Sans, sans-serif; }
    body { margin: 0; color: #111; font-size: 11px; }
    .sayfa { padding: 26px 32px; page-break-after: always; }
    .baslik { text-align: center; border-bottom: 3px double #111; padding-bottom: 10px; margin-bottom: 14px; }
    .baslik h1 { font-size: 15px; margin: 0 0 4px; }
    .kunye { width: 100%; border-collapse: collapse; font-size: 10px; margin-bottom: 14px; }
    .kunye td { border: 1px solid #999; padding: 5px 8px; }
    .kunye td:first-child { background: #f0f0f0; font-weight: bold; width: 24%; }
    table.kkd { width: 100%; border-collapse: collapse; font-size: 9.5px; margin-bottom: 14px; }
    table.kkd th, table.kkd td { border: 1px solid #999; padding: 4px 6px; text-align: left; }
    table.kkd th { background: #f0f0f0; }
    .imza { margin-top: 40px; width: 100%; }
    .imza td { width: 50%; text-align: center; padding-top: 40px; border-top: 1px solid #111; font-size: 10px; }
    .yasal { margin-top: 14px; font-size: 8.5px; color: #666; }
</style>
</head>
<body>

@forelse (($form->calisanlar ?: [null]) as $c)
    <div class="sayfa">
        <div class="baslik">
            <h1>KKD ZİMMET FORMU</h1>
            <div style="font-size:11px">{{ $firma?->unvan }}</div>
        </div>

        <table class="kunye">
            <tr>
                <td>Form No</td><td>{{ $form->form_no }}</td>
                <td>Teslim Tarihi</td><td>{{ $form->teslim_tarihi?->format('d.m.Y') }}</td>
            </tr>
            <tr>
                <td>Periyodik Kontrol</td><td>{{ $form->periyodik_kontrol_tarihi?->format('d.m.Y') ?: '—' }}</td>
                <td>Teslim Eden</td><td>{{ $form->teslim_eden ?: '—' }}</td>
            </tr>
            @if ($c)
                <tr>
                    <td>Çalışan</td><td>{{ $c['ad_soyad'] ?? '—' }}</td>
                    <td>Departman / Görev</td><td>{{ $c['departman'] ?? '—' }}</td>
                </tr>
            @endif
        </table>

        <table class="kkd">
            <tr><th style="width:5%">#</th><th>KKD</th><th style="width:30%">Standart</th></tr>
            @forelse (($form->kkdler ?? []) as $i => $k)
                <tr><td>{{ $i + 1 }}</td><td>{{ $k['ad'] ?? '' }}</td><td>{{ $k['standart'] ?? '—' }}</td></tr>
            @empty
                <tr><td colspan="3" style="color:#888">KKD seçilmedi.</td></tr>
            @endforelse
        </table>

        <table class="imza">
            <tr>
                <td>Teslim Eden<br>(İmza)</td>
                <td>Teslim Alan Çalışan<br>(İmza)</td>
            </tr>
        </table>

        <p class="yasal">
            6331 sayılı İş Sağlığı ve Güvenliği Kanunu ve Kişisel Koruyucu Donanımların İşyerlerinde
            Kullanılması Hakkında Yönetmelik uyarınca düzenlenmiştir. Teslim edilen donanımların
            doğru kullanım ve bakım eğitimi verilmiştir.
        </p>
    </div>
@empty
@endforelse

</body>
</html>
