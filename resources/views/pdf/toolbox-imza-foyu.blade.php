<!DOCTYPE html>
<html lang="tr"><head><meta charset="utf-8">
<style>
    body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #0f172a; }
    h1 { font-size: 16px; margin: 0 0 4px; }
    .alt { color: #475569; margin-bottom: 10px; }
    ol { margin: 6px 0 12px 18px; padding: 0; }
    li { margin-bottom: 3px; }
    table { width: 100%; border-collapse: collapse; }
    th, td { border: 1px solid #94a3b8; padding: 6px; text-align: left; }
    th { background: #f1f5f9; font-size: 10px; }
    td { height: 22px; }
</style></head>
<body>
    <h1>İŞ BAŞI (TOOLBOX) KONUŞMASI — KATILIM İMZA LİSTESİ</h1>
    <div class="alt">Konu: <strong>{{ $kart['baslik'] }}</strong> · Tarih: ____/____/________ · Yer: ______________________ · Konuşmayı yapan: ______________________</div>
    <strong>Konuşma özeti</strong>
    <ol>@foreach ($kart['maddeler'] as $m)<li>{{ $m }}</li>@endforeach</ol>
    <table>
        <tr><th style="width:28px">#</th><th>Ad Soyad</th><th style="width:110px">T.C. Kimlik No</th><th style="width:100px">Görev</th><th style="width:100px">İmza</th></tr>
        @for ($i = 1; $i <= 15; $i++)<tr><td>{{ $i }}</td><td></td><td></td><td></td><td></td></tr>@endfor
    </table>
</body></html>
