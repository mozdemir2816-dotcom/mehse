<!doctype html>
<html lang="tr">
<head>
<meta charset="utf-8">
<style>
    * { font-family: "DejaVu Sans", sans-serif; }
    @page { margin: 22px 28px; }
    body { margin: 0; color: #111827; font-size: 9.4px; line-height: 1.38; }
    .sayfa { page-break-after: always; }
    .sayfa:last-child { page-break-after: auto; }
    .baslik { text-align: center; border-bottom: 2px solid #0f172a; padding-bottom: 6px; margin-bottom: 8px; }
    .baslik h1 { font-size: 13px; margin: 0 0 2px; }
    .baslik .alt { font-size: 9px; color: #475569; }
    table.kunye { width: 100%; border-collapse: collapse; margin-bottom: 8px; font-size: 9px; }
    table.kunye td { border: 1px solid #94a3b8; padding: 3.5px 6px; }
    table.kunye td.e { background: #f1f5f9; font-weight: bold; width: 18%; }
    .soru-kutu { margin-bottom: 6px; padding-bottom: 4px; border-bottom: 1px dashed #cbd5e1; }
    .soru-metin { font-weight: bold; margin-bottom: 2px; }
    .siklar { margin-left: 10px; font-size: 8.9px; }
    .sik { margin-bottom: 1px; }
    table.imza { width: 100%; border-collapse: collapse; margin-top: 8px; font-size: 9px; }
    table.imza td { border: 1px solid #94a3b8; padding: 5px 8px; width: 50%; vertical-align: top; }
    table.cevap { width: 100%; border-collapse: collapse; margin-top: 12px; font-size: 10px; }
    table.cevap th, table.cevap td { border: 1px solid #94a3b8; padding: 6px; text-align: center; }
    table.cevap th { background: #0f172a; color: #fff; }
</style>
</head>
<body>

@foreach ($katilimcilar as $k)
    <div class="sayfa">
        <div class="baslik">
            <h1>İŞ SAĞLIĞI VE GÜVENLİĞİ EĞİTİMİ DEĞERLENDİRME SINAVI ({{ \App\Support\TurkceMetin::buyuk($sinavTuru) }})</h1>
            <div class="alt">{{ $firma?->unvan ?: '..........................................................' }} — {{ $egitimBasligi }}</div>
        </div>

        <table class="kunye">
            <tr>
                <td class="e">Adı Soyadı</td><td style="width:36%">{{ $k['ad_soyad'] ?? '' }}</td>
                <td class="e">T.C. Kimlik No</td><td>{{ $k['tc'] ?? '' }}</td>
            </tr>
            <tr>
                <td class="e">Görevi / Bölümü</td><td>{{ $k['gorev'] ?? '' }}</td>
                <td class="e">Sınav Tarihi / Puan</td><td>{{ $tarih }} &nbsp;&nbsp;|&nbsp;&nbsp; <strong>Puan: ........ / 100</strong></td>
            </tr>
        </table>

        @foreach ($sorular as $i => $s)
            <div class="soru-kutu">
                <div class="soru-metin">{{ $i + 1 }}. {{ $s['soru'] }} (10 Puan)</div>
                <div class="siklar">
                    @foreach ($s['siklar'] as $harf => $metin)
                        <div class="sik">☐ <strong>{{ $harf }})</strong> {{ $metin }}</div>
                    @endforeach
                </div>
            </div>
        @endforeach

        <table class="imza">
            <tr>
                <td>
                    <strong>Katılımcı (Çalışan) İmzası:</strong><br><br>
                    {{ $k['ad_soyad'] ?? '........................................' }}
                </td>
                <td>
                    <strong>Değerlendiren Eğitici / İSG Uzmanı:</strong><br><br>
                    {{ $firma?->igu?->ad_soyad ?: '........................................' }}
                </td>
            </tr>
        </table>
    </div>
@endforeach

{{-- CEVAP ANAHTARI SAYFASI --}}
<div class="sayfa">
    <div class="baslik">
        <h1>İSG EĞİTİMİ DEĞERLENDİRME SINAVI — CEVAP ANAHTARI</h1>
        <div class="alt">{{ $firma?->unvan ?: 'Tüm Firmalar' }} · {{ $egitimBasligi }} · Tarih: {{ $tarih }}</div>
    </div>

    <p style="font-size:9.5px">
        Her soru <strong>10 puan</strong> değerindedir (Toplam 100 puan). Çalışanların İş Sağlığı ve Güvenliği Eğitimlerinin Usul ve Esasları Hakkında Yönetmelik (RG 02.04.2026 / 33212, m.16) uyarınca yapılan ölçme ve değerlendirme sonucunda başarılı sayılma taban puanı <strong>60</strong>'tır.
    </p>

    <table class="cevap">
        <tr>
            @foreach ($sorular as $i => $s)
                <th>Soru {{ $i + 1 }}</th>
            @endforeach
        </tr>
        <tr>
            @foreach ($sorular as $s)
                <td style="font-weight:bold;font-size:12px">{{ $s['dogru'] }}</td>
            @endforeach
        </tr>
    </table>
</div>

</body>
</html>
