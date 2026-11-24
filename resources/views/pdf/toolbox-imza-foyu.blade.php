<!DOCTYPE html>
<html lang="tr"><head><meta charset="utf-8">
<style>
    @page { margin: 14mm 14mm; }
    body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #0f172a; margin: 0; }
    h1 { font-size: 14.5px; margin: 0 0 4px; text-align: center; border-bottom: 2px solid #0f172a; padding-bottom: 5px; }
    table.kunye { width: 100%; border-collapse: collapse; margin: 8px 0 10px; font-size: 9.5px; }
    table.kunye td { border: 1px solid #94a3b8; padding: 4px 6px; }
    table.kunye td.e { background: #f1f5f9; font-weight: bold; width: 18%; }
    ol { margin: 4px 0 10px 18px; padding: 0; font-size: 9.5px; }
    li { margin-bottom: 2px; }
    table.liste { width: 100%; border-collapse: collapse; }
    table.liste thead { display: table-header-group; }
    table.liste tr { page-break-inside: avoid; }
    table.liste th, table.liste td { border: 1px solid #94a3b8; padding: 5px 6px; text-align: left; }
    table.liste th { background: #f1f5f9; font-size: 9.5px; }
    table.liste td { height: 20px; }
    table.imza { width: 100%; border-collapse: collapse; margin-top: 12px; page-break-inside: avoid; font-size: 9px; }
    table.imza td { width: 50%; border: 1px solid #94a3b8; padding: 6px 8px 30px; vertical-align: top; }
    .not { margin-top: 6px; font-size: 8.5px; color: #475569; }
</style></head>
<body>
    <h1>İŞ BAŞI (TOOLBOX) KONUŞMASI — KATILIM VE BİLGİLENDİRME İMZA FÖYÜ</h1>
    <table class="kunye">
        <tr>
            <td class="e">İşyeri Unvanı</td><td>{{ $firma?->unvan ?? '................................................................' }}</td>
            <td class="e">Tarih / Saat / Süre</td><td>____/____/________ · ____:____ (____ dk)</td>
        </tr>
        <tr>
            <td class="e">Konu Başlığı</td><td><strong>{{ $kart['baslik'] }}</strong></td>
            <td class="e">Bölüm / Saha</td><td>................................................</td>
        </tr>
    </table>
    <strong>Konuşma Özeti (6331 SK m.16–17 Çalışan Bilgilendirmesi):</strong>
    <ol>@foreach ($kart['maddeler'] as $m)<li>{{ $m }}</li>@endforeach</ol>
    <table class="liste">
        <thead>
            <tr><th style="width:26px">#</th><th>Ad Soyad</th><th style="width:105px">T.C. Kimlik No</th><th style="width:105px">Görev / Bölüm</th><th style="width:95px">İmza</th></tr>
        </thead>
        <tbody>
            @for ($i = 1; $i <= 15; $i++)<tr><td>{{ $i }}</td><td></td><td></td><td></td><td></td></tr>@endfor
        </tbody>
    </table>
    <p class="not">Yukarıda özeti yer alan işbaşı güvenlik bilgilendirmesini dinlediğimi, anladığımı ve çalışma süresince kurallara uyacağımı beyan ederim.</p>
    <table class="imza">
        <tr>
            <td><strong>Konuşmayı Yapan (Ad Soyad / Unvan / İmza):</strong></td>
            <td><strong>İşveren Vekili / Saha Amiri (Ad Soyad / İmza):</strong></td>
        </tr>
    </table>
</body></html>
