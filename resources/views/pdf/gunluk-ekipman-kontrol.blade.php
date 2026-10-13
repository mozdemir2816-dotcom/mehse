<!doctype html>
<html lang="tr">
<head>
<meta charset="utf-8">
<style>
    * { font-family: DejaVu Sans, sans-serif; }
    body { margin: 0; color: #111; font-size: 9px; }
    .sayfa { padding: 22px 26px; }
    .yeni-sayfa { page-break-before: always; }
    table.ust { width: 100%; border-collapse: collapse; margin-bottom: 8px; }
    table.ust td.logo { width: 22%; border: 1px solid #0A345A; text-align: center; vertical-align: middle; padding: 4px; background: #fff; }
    table.ust td.logo img { max-height: 46px; max-width: 140px; }
    .baslik { background: #0A345A; color: #fff; text-align: center; padding: 8px; }
    .baslik h1 { font-size: 13.5px; margin: 0 0 2px; letter-spacing: .3px; }
    .baslik div { font-size: 9.5px; opacity: .92; }
    table.kunye { width: 100%; border-collapse: collapse; font-size: 8.5px; margin-bottom: 6px; }
    table.kunye td { border: 1px solid #999; padding: 5px 6px; }
    table.kunye td.etiket { font-weight: bold; width: 15%; background: #f1f5f9; }
    .aciklama { font-size: 7.6px; color: #444; margin-bottom: 6px; line-height: 1.45; }
    .aciklama strong { color: #111; }
    table.sorular { width: 100%; border-collapse: collapse; font-size: 8.2px; margin-bottom: 8px; }
    table.sorular th { background: #0A345A; color: #fff; padding: 4px 3px; text-align: center; font-size: 7.6px; }
    table.sorular td { border: 1px solid #cbd5e1; padding: 5px 4px; vertical-align: middle; }
    table.sorular td.no { width: 4%; text-align: center; }
    table.sorular td.soru { width: 52%; }
    table.sorular td.kutu { width: 7.5%; text-align: center; font-size: 11px; }
    table.sorular td.aciklama-hucre { width: 21.5%; }
    table.sorular tr:nth-child(even) td { background: #f8fafc; }
    .karar { border: 1.5px solid #0A345A; padding: 6px 8px; margin: 6px 0 8px; font-size: 9px; }
    .karar strong { font-size: 9.5px; }
    .not-alani { font-size: 8px; margin-bottom: 6px; }
    .not-alani .satir { border-bottom: 1px solid #999; display: block; height: 15px; }
    table.imza { width: 100%; border-collapse: collapse; font-size: 8px; margin-top: 4px; }
    table.imza th { background: #e2e8f0; padding: 4px; border: 1px solid #94a3b8; }
    table.imza td { border: 1px solid #94a3b8; padding: 4px 6px; height: 26px; vertical-align: top; }
    .footnote { font-size: 7px; color: #666; margin-top: 8px; border-top: 1px solid #ccc; padding-top: 4px; }
</style>
</head>
<body>
@forelse ($sayfalar as $i => $s)
    @php $e = $s['ekipman']; @endphp
    <div class="sayfa{{ $i > 0 ? ' yeni-sayfa' : '' }}">
        {{-- Sol üstte firma logosu (yüklüyse); yoksa başlık tam genişlik. --}}
        <table class="ust">
            <tr>
                @if ($logo)
                    <td class="logo"><img src="{{ $logo }}"></td>
                @endif
                <td class="baslik">
                    <h1>GÜNLÜK EKİPMAN KONTROL FORMU</h1>
                    <div>{{ $s['form']['ad'] }} — kullanım öncesi kontrol</div>
                </td>
            </tr>
        </table>

        <table class="kunye">
            <tr>
                <td class="etiket">Firma</td><td colspan="3">{{ $firmaUnvan }}</td>
            </tr>
            <tr>
                <td class="etiket">Ekipman</td><td>{{ $e->ekipman_adi }}</td>
                <td class="etiket">Seri No</td><td>{{ $e->seri_no ?: '.................................' }}</td>
            </tr>
            <tr>
                <td class="etiket">Marka / Model</td><td>{{ $e->marka_model ?: '.................................' }}</td>
                <td class="etiket">Bölüm / Konum</td><td>{{ $e->konum ?: '.................................' }}</td>
            </tr>
            <tr>
                <td class="etiket">Tarih</td><td>.... / .... / ........</td>
                <td class="etiket">Vardiya / Saat</td><td>................ / ........</td>
            </tr>
            <tr>
                <td class="etiket">Operatör</td><td colspan="3">.................................................................................</td>
            </tr>
        </table>

        <div class="aciklama">
            Ekipmanı çalıştırmadan / işe başlamadan önce kontrol edin; enerji gerektirmeyen kontrolleri ekipman durmuşken yapın. Her madde için bir kutuyu işaretleyin (✓).
            <strong>Uygun Değil</strong> işaretlenen ve güvenliği etkileyen bir maddede ekipmanı <strong>kullanmayın</strong>, amirinize bildirin ve açıklama yazın.
            <strong>Uygulanamaz</strong> seçilirse gerekçesini açıklamaya yazın.
        </div>

        <table class="sorular">
            <tr>
                <th>No</th>
                <th style="text-align:left;padding-left:6px">Kontrol Maddesi</th>
                <th>Uygun</th>
                <th>Uygun<br>Değil</th>
                <th>Uygula-<br>namaz</th>
                <th>Açıklama</th>
            </tr>
            @foreach ($s['form']['sorular'] as $j => $soru)
                <tr>
                    <td class="no">{{ $j + 1 }}</td>
                    <td class="soru">{{ $soru }}</td>
                    <td class="kutu">☐</td>
                    <td class="kutu">☐</td>
                    <td class="kutu">☐</td>
                    <td class="aciklama-hucre"></td>
                </tr>
            @endforeach
        </table>

        <div class="karar">
            <strong>Ekipman kullanıma uygun mu?</strong>
            &nbsp;&nbsp;&nbsp; ☐ Evet, kullanılabilir
            &nbsp;&nbsp;&nbsp; ☐ Hayır — kullanım durduruldu, amire bildirildi (saat: ............)
        </div>

        <div class="not-alani">
            Uygunsuzluk ve alınan önlem (madde no, açıklama):
            <span class="satir"></span>
            <span class="satir"></span>
            Giderildi / yeniden kullanıma izin — tarih, saat, sorumlu:
            <span class="satir"></span>
        </div>

        <table class="imza">
            <tr><th style="width:50%">Kontrol Eden Operatör</th><th>Onaylayan Amir / Sorumlu</th></tr>
            <tr><td>Ad Soyad:<br><br>İmza:</td><td>Ad Soyad:<br><br>İmza:</td></tr>
        </table>

        <div class="footnote">
            Kapasite ve sınırlar için ekipman etiketi ve üretici talimatı esas alınır. Bakım/onarım bu formun kapsamında değildir;
            enerji kesilip kilitlenmeden müdahale edilmez. Günlük kontrol, periyodik kontrol ve bakımın yerine geçmez.
            &nbsp;&bull;&nbsp; Sayfa {{ $i + 1 }} / {{ count($sayfalar) }}
        </div>
    </div>
@empty
    <div class="sayfa">Kontrol formu tanımlı ekipman yok.</div>
@endforelse
</body>
</html>
