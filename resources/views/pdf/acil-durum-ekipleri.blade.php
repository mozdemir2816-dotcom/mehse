<!doctype html>
<html lang="tr">
<head>
<meta charset="utf-8">
<style>
    * { font-family: "DejaVu Sans", sans-serif; }
    @page { margin: 24px 28px; }
    body { margin: 0; color: #111; font-size: 9px; line-height: 1.4; }
    h1 { font-size: 14px; text-align: center; margin: 0 0 4px; }
    .alt { text-align: center; font-size: 8.5px; color: #444; margin-bottom: 10px; }
    table { width: 100%; border-collapse: collapse; }
    .kunye td { border: 1px solid #999; padding: 3px 6px; }
    .kunye td.e { background: #eee; font-weight: bold; width: 14%; }
    .ekip { margin-top: 10px; page-break-inside: avoid; }
    .ekip-baslik { background: #0d9488; color: #fff; padding: 4px 6px; font-weight: bold; font-size: 9.5px; }
    .ekip-baslik span { float: right; font-weight: normal; }
    .uyeler th, .uyeler td { border: 1px solid #888; padding: 3px 5px; }
    .uyeler th { background: #eee; text-align: left; }
    .bos { color: #b91c1c; font-style: italic; }
    .imza { margin-top: 22px; }
    .imza td { border: 1px solid #888; padding: 6px; width: 33%; height: 52px; vertical-align: top; text-align: center; }
</style>
</head>
<body>
    <h1>ACİL DURUM EKİPLERİ / DESTEK ELEMANLARI GÖREVLENDİRME ÇİZELGESİ</h1>
    <div class="alt">İşyerlerinde Acil Durumlar Hakkında Yönetmelik Md.11 — İlkyardım Yönetmeliği</div>

    <table class="kunye">
        <tr><td class="e">İşyeri</td><td>{{ $firma->unvan }}</td><td class="e">SGK Sicil</td><td>{{ $firma->sgk_sicil_no ?: '—' }}</td></tr>
        <tr><td class="e">Tehlike sınıfı</td><td>{{ $firma->tehlikeSinifiEtiketi() }}</td><td class="e">Çalışan</td><td>{{ $ozet['calisan'] ?: '—' }}</td></tr>
        <tr><td class="e">Adres</td><td>{{ $firma->adres ?: '—' }}</td><td class="e">Tarih</td><td>{{ now()->format('d.m.Y') }}</td></tr>
    </table>

    @foreach ($ekipler as $e)
        @php $d = $ozet['durumlar'][$e->id]; @endphp
        <div class="ekip">
            <div class="ekip-baslik">{{ $e->ad }} <span>Asıl {{ $d['asil'] }} · Yedek {{ $d['yedek'] }} · Asgari {{ $d['min'] }} · Lider: {{ $d['lider'] ?: '—' }}</span></div>
            <table class="uyeler">
                <tr><th style="width:4%">#</th><th style="width:20%">Ad Soyad</th><th style="width:8%">Üyelik</th><th style="width:15%">Görev / Bölüm</th><th style="width:10%">Telefon</th><th style="width:10%">Vardiya</th><th style="width:13%">Belge No</th><th style="width:10%">Geçerlilik</th><th style="width:10%">İmza</th></tr>
                @forelse ($e->uyeler as $i => $u)
                    <tr>
                        <td>{{ $i + 1 }}</td>
                        <td>{{ $u->ad_soyad }}@if ($u->lider) <b>(Lider)</b>@endif</td>
                        <td>{{ $u->uyelikEtiketi() }}</td>
                        <td>{{ collect([$u->gorev, $u->bolum])->filter()->implode(' / ') }}</td>
                        <td>{{ $u->telefon }}</td>
                        <td>{{ $u->vardiya }}</td>
                        <td>{{ $u->belge_no }}</td>
                        <td>{{ $u->belgeBitisTarihi()?->format('d.m.Y') }}</td>
                        <td></td>
                    </tr>
                @empty
                    <tr><td colspan="9" class="bos">Bu ekibe henüz üye atanmamış.</td></tr>
                @endforelse
            </table>
        </div>
    @endforeach

    <table class="imza">
        <tr>
            <td><b>Hazırlayan (İSG Uzmanı)</b><br>Kaşe / İmza</td>
            <td><b>İşyeri Hekimi</b><br>Kaşe / İmza</td>
            <td><b>İşveren / İşveren Vekili</b><br>Kaşe / İmza</td>
        </tr>
    </table>
</body>
</html>
