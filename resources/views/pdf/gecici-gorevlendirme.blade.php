<!doctype html>
<html lang="tr">
<head>
<meta charset="utf-8">
<title>Geçici Görevlendirme — {{ $firma->unvan }}</title>
<style>
    @page { margin: 26px 32px; }
    * { font-family: 'DejaVu Sans', sans-serif; }
    body { margin: 0; color: #111827; font-size: 9.5px; line-height: 1.45; }
    .sayfa { page-break-after: always; }
    .sayfa:last-child { page-break-after: avoid; }
    .ust { border: 1.5px solid #1e3a8a; background: #eff6ff; padding: 10px 14px; border-radius: 5px; margin-bottom: 12px; }
    .ust-baslik { font-size: 13px; font-weight: bold; color: #1e3a8a; text-align: center; }
    .ust-alt { font-size: 8.5px; color: #374151; text-align: center; margin-top: 3px; }
    table.kunye { width: 100%; border-collapse: collapse; margin-bottom: 10px; font-size: 9px; }
    table.kunye td { border: 1px solid #cbd5e1; padding: 5px 8px; vertical-align: top; }
    table.kunye td.e { width: 22%; background: #f8fafc; font-weight: bold; color: #1f2937; }
    .bolum { font-size: 9.5px; font-weight: bold; color: #1e3a8a; background: #f1f5f9; border-left: 3px solid #1d4ed8; padding: 4px 8px; margin: 10px 0 6px; }
    .metin { text-align: justify; margin: 0 0 8px; font-size: 9.3px; }
    ul.taahhut { margin: 4px 0 10px; padding-left: 16px; }
    ul.taahhut li { margin-bottom: 4px; text-align: justify; font-size: 9px; }
    .not-kutu { border: 1px solid #fcd34d; background: #fffbeb; border-radius: 4px; padding: 6px 10px; font-size: 8.5px; color: #92400e; margin-bottom: 10px; }
    table.imza { width: 100%; border-collapse: collapse; table-layout: fixed; margin-top: 18px; page-break-inside: avoid; }
    table.imza td { width: 50%; border: 1px solid #cbd5e1; padding: 8px 10px; vertical-align: top; text-align: center; font-size: 8.8px; }
    .imza-alan { height: 54px; margin: 6px 0; text-align: center; }
    .imza-alan img { max-height: 50px; max-width: 160px; }
    .alt-bilgi { margin-top: 14px; border-top: 1px solid #e5e7eb; padding-top: 5px; font-size: 7.5px; color: #6b7280; text-align: center; }
</style>
</head>
<body>
@foreach ($calisanlar as $c)
    <div class="sayfa">
        <div class="ust">
            <div class="ust-baslik">{{ \App\Support\TurkceMetin::buyuk($sablon['ad']) }}</div>
            <div class="ust-alt">{{ $sablon['dayanak'] }} &nbsp;|&nbsp; Belge No: {{ $belgeNo }} &nbsp;|&nbsp; Tarih: {{ $tarih }}</div>
        </div>

        <div class="bolum">1. İŞVEREN VE ÇALIŞAN BİLGİLERİ</div>
        <table class="kunye">
            <tr>
                <td class="e">İşveren / Firma Ünvanı</td>
                <td colspan="3"><strong>{{ $firma->unvan }}</strong></td>
            </tr>
            <tr>
                <td class="e">Tehlike Sınıfı</td>
                <td>{{ $firma->tehlikeSinifiEtiketi() }}</td>
                <td class="e">SGK Sicil No</td>
                <td>{{ $firma->sgk_sicil_no ?: '—' }}</td>
            </tr>
            <tr>
                <td class="e">Çalışanın Adı Soyadı</td>
                <td><strong>{{ $c['ad_soyad'] }}</strong></td>
                <td class="e">T.C. Kimlik No</td>
                <td>{{ \App\Support\GeciciGorevlendirmeUretici::tcMaskele($c['tc'] ?? null, $tcMaskele) }}</td>
            </tr>
            <tr>
                <td class="e">Görevi / Unvanı</td>
                <td>{{ $c['gorev'] ?: '—' }}</td>
                <td class="e">Atanan İSG Uzmanı</td>
                <td>{{ $firma->igu?->ad_soyad ?: '—' }}</td>
            </tr>
        </table>

        <div class="bolum">2. GEÇİCİ GÖREVLENDİRME DETAYLARI</div>
        <table class="kunye">
            <tr>
                <td class="e">Görev Yeri / Lokasyon</td>
                <td colspan="3"><strong>{{ $gorevYeri }}</strong></td>
            </tr>
            <tr>
                <td class="e">Başlangıç Tarihi</td>
                <td>{{ $baslangic }}</td>
                <td class="e">Bitiş Tarihi</td>
                <td>{{ $bitis }}</td>
            </tr>
            <tr>
                <td class="e">Görev Konusu / Kapsamı</td>
                <td colspan="3">{{ $gorevKonusu }}</td>
            </tr>
            @if (filled($harcirahNotu) || filled($aracPlaka))
                <tr>
                    <td class="e">Harcırah / Konaklama</td>
                    <td>{{ $harcirahNotu ?: '—' }}</td>
                    <td class="e">Araç / Plaka</td>
                    <td>{{ $aracPlaka ?: '—' }}</td>
                </tr>
            @endif
        </table>

        <div class="bolum">3. GÖREVLENDİRME METNİ VE İSG TAAHHÜTNAMESİ</div>
        <p class="metin">{{ $sablon['metin'] }}</p>
        <ul class="taahhut">
            @foreach ($sablon['taahhut'] as $madde)
                <li>{{ $madde }}</li>
            @endforeach
        </ul>

        @if ($sablonAnahtar === 'gecici_is_iliskisi')
            <div class="not-kutu">
                <strong>4857 Sayılı İş Kanunu m.7 Yazılı Rıza Beyanı:</strong> Yukarıda belirtilen işyerinde ve tarih aralığında geçici iş ilişkisi kapsamında görevlendirilmeyi kendi özgür irademle kabul ediyor, işbu belgeyi okuyarak imzalıyorum.
            </div>
        @endif

        <table class="imza">
            <tr>
                <td>
                    <div><strong>TEBLİĞ EDEN (İŞVEREN / YETKİLİ)</strong></div>
                    <div style="font-size:8px;color:#4b5563">{{ $firma->isveren_ad ?: $firma->unvan }}</div>
                    <div class="imza-alan"></div>
                    <div style="font-size:8px;color:#6b7280">İmza – Kaşe · {{ $tarih }}</div>
                </td>
                <td>
                    <div><strong>TEBELLÜĞ EDEN (ÇALIŞAN)</strong></div>
                    <div style="font-size:8px;color:#4b5563">{{ $c['ad_soyad'] }}</div>
                    <div class="imza-alan">
                        @if (count($calisanlar) === 1 && filled($canliImza) && str_starts_with($canliImza, 'data:image/'))
                            <img src="{{ $canliImza }}" alt="Çalışan İmzası">
                        @endif
                    </div>
                    <div style="font-size:8px;color:#6b7280">Okudum, anladım, bir nüshasını teslim aldım · İmza</div>
                </td>
            </tr>
        </table>

        <div class="alt-bilgi">
            Bu belge iki nüsha olarak düzenlenmiş olup bir nüshası çalışanın özlük dosyasında saklanır. · <strong>mehse.com</strong>
        </div>
    </div>
@endforeach
</body>
</html>
