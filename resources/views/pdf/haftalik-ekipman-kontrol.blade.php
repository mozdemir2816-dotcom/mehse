<!doctype html>
<html lang="tr">
<head>
<meta charset="utf-8">
<style>
    * { font-family: DejaVu Sans, sans-serif; }
    body { margin: 0; color: #111; font-size: 9px; }
    .sayfa { padding: 18px 22px; }
    .sayfa:not(:first-child) { page-break-before: always; }
    .baslik { background: #57534e; color: #fff; text-align: center; padding: 8px; margin-bottom: 8px; }
    .baslik h1 { font-size: 13px; margin: 0 0 2px; }
    .baslik div { font-size: 9.5px; opacity: .9; }
    table.kunye { width: 100%; border-collapse: collapse; font-size: 8.5px; margin-bottom: 6px; }
    table.kunye td { border: 1px solid #999; padding: 4px 6px; }
    table.kunye td.etiket { font-weight: bold; width: 16%; background: #f5f5f4; }
    .aciklama { font-size: 7.5px; color: #444; margin-bottom: 6px; line-height: 1.4; }
    .aciklama strong { color: #111; }
    table.sorular { width: 100%; border-collapse: collapse; font-size: 7.8px; margin-bottom: 8px; }
    table.sorular th { background: #57534e; color: #fff; padding: 4px 3px; text-align: center; }
    table.sorular th.soru-basligi { text-align: left; padding-left: 6px; }
    table.sorular td { border: 1px solid #d6d3d1; padding: 4px 3px; text-align: center; }
    table.sorular td.no { width: 3%; }
    table.sorular td.soru { text-align: left; padding-left: 6px; width: 30%; }
    table.sorular td.gun { width: 9.5%; height: 16px; }
    .not-alani { font-size: 7.8px; margin-bottom: 4px; }
    .not-alani .satir { border-bottom: 1px solid #999; display: inline-block; width: 100%; height: 12px; }
    table.imza { width: 100%; border-collapse: collapse; font-size: 7.8px; margin-top: 6px; margin-bottom: 8px; }
    table.imza th { background: #78716c; color: #fff; padding: 4px 3px; text-align: center; }
    table.imza td { border: 1px solid #d6d3d1; padding: 4px 3px; text-align: center; height: 16px; }
    table.imza td.etiket { text-align: left; font-weight: bold; background: #f5f5f4; padding-left: 6px; }
    .onay { font-size: 8px; margin-top: 4px; }
    .footnote { font-size: 7px; color: #666; margin-top: 8px; border-top: 1px solid #ccc; padding-top: 4px; }
</style>
</head>
<body>
@foreach ($formlar as $i => $form)
    <div class="sayfa">
        <div class="baslik">
            <h1>RESİMLİ HAFTALIK EKİPMAN KONTROL FORMU</h1>
            <div>Makine Türü: {{ $form['ad'] }}</div>
        </div>

        <table class="kunye">
            <tr>
                <td class="etiket">Proje/Şantiye Adı</td><td>{{ $firmaUnvan ?: '' }}</td>
                <td class="etiket">Kontrol Edilen Hafta</td><td>.... / .... / ........  –  .... / .... / ........</td>
            </tr>
            <tr>
                <td class="etiket">Plaka/Şasi/Seri No</td><td>.................................</td>
                <td class="etiket">Operatör Adı Soyadı</td><td>....................................................</td>
            </tr>
        </table>

        <div class="aciklama">
            Numaralı detaylar soruların ana kontrol noktalarını gösterir. Görsel temsilîdir; konumlar modele göre değişir.<br>
            <strong>E</strong> = Evet / Uygun &nbsp;&bull;&nbsp; <strong>H</strong> = Hayır / Uygun Değil &nbsp;&bull;&nbsp; <strong>İ</strong> = İptal / Uygulanamaz (gerekçesini yazın).<br>
            E için tüm koşullar sağlanmalıdır. İşe başlamadan güvenle kontrol edin; güvenliği etkileyen H yanıtında kullanmayın ve bildirin.
        </div>

        <table class="sorular">
            <tr>
                <th class="no">No</th>
                <th class="soru-basligi">Operatöre Kontrol Soruları</th>
                <th>Pzt</th><th>Sal</th><th>Çar</th><th>Per</th><th>Cum</th><th>Cmt</th><th>Paz</th>
            </tr>
            @foreach ($form['sorular'] as $j => $soru)
                <tr>
                    <td class="no">{{ $j + 1 }}</td>
                    <td class="soru">{{ $soru }}</td>
                    <td class="gun"></td><td class="gun"></td><td class="gun"></td>
                    <td class="gun"></td><td class="gun"></td><td class="gun"></td><td class="gun"></td>
                </tr>
            @endforeach
        </table>

        <div class="not-alani">
            Uygunsuzluk / İptal — Gün, madde no, açıklama ve önlem: <span class="satir"></span>
            <span class="satir"></span>
            Giderilme / yeniden kullanıma izin — Tarih, saat, sorumlu: <span class="satir"></span>
        </div>

        <div class="onay">Her günün sonunda operatör ilgili günü imzalamalıdır.</div>
        <table class="imza">
            <tr>
                <th>Gün</th>
                <th>Pzt</th><th>Sal</th><th>Çar</th><th>Per</th><th>Cum</th><th>Cmt</th><th>Paz</th>
            </tr>
            <tr>
                <td class="etiket">Tarih</td>
                <td></td><td></td><td></td><td></td><td></td><td></td><td></td>
            </tr>
            <tr>
                <td class="etiket">Operatör İmzası</td>
                <td></td><td></td><td></td><td></td><td></td><td></td><td></td>
            </tr>
        </table>

        <div class="onay">
            <strong>Haftalık Genel Onay</strong> — Ad Soyad / Tarih / İmza<br>
            Sorumlu Amir/Şef: .................................................. &nbsp;&nbsp;&nbsp; Şantiye Şefi: ..................................................
        </div>

        <div class="footnote">
            Kapasite/sınırlar için gerçek makine levhası ve üretici talimatı esas alınır. Sökme ve bakım bu formun kapsamında değildir.
            Günlük kontrol periyodik kontrol yerine geçmez. &nbsp;&bull;&nbsp; Form {{ $i + 1 }} / {{ count($formlar) }}
        </div>
    </div>
@endforeach
</body>
</html>
