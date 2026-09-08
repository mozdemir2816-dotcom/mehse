{{--
    Kişisel Koruyucu Donanım Teslim Tutanağı — kullanıcının gerçek şablonu
    (Desktop\yeni isg dosya evrakları\KKD\İSG_KKD_1.xlsx) birebir esas alınmıştır:
    başlık, malzeme tablosu sütunları (Türü / Standardı / Kullanma Dönemi / Miktar),
    4 maddelik taahhüt metni ve Teslim Alan / Teslim Veren imza bloğu aynen korunur.
    Her çalışan için ayrı sayfa; seçilen KKD seti hepsinde aynıdır.
--}}
<!doctype html>
<html lang="tr">
<head>
<meta charset="utf-8">
<style>
    * { font-family: "DejaVu Sans", sans-serif; }
    @page { margin: 26px 32px; }
    body { margin: 0; color: #111; font-size: 10.5px; line-height: 1.45; }
    .sayfa { page-break-after: always; }
    .sayfa:last-child { page-break-after: auto; }

    .ust { width: 100%; border-collapse: collapse; margin-bottom: 12px; }
    .ust td { border: 1px solid #111; padding: 6px 8px; vertical-align: middle; }
    .ust .baslik { text-align: center; font-size: 13px; font-weight: bold; letter-spacing: .3px; }
    .ust .dk { font-size: 8px; width: 104px; line-height: 1.4; }
    .ust .dk b { display: inline-block; min-width: 40px; }

    .kunye { width: 100%; border-collapse: collapse; font-size: 9.5px; margin-bottom: 12px; }
    .kunye td { border: 1px solid #999; padding: 4px 7px; }
    .kunye td.e { background: #eee; font-weight: bold; width: 20%; }

    table.mlz { width: 100%; border-collapse: collapse; font-size: 9.5px; margin-bottom: 14px; }
    table.mlz th, table.mlz td { border: 1px solid #666; padding: 4px 6px; }
    table.mlz th { background: #eee; text-align: center; }
    table.mlz td.no { text-align: center; }
    table.mlz td.mik { text-align: center; }

    .taahhut { margin: 0 0 9px; text-align: justify; }
    .teslim-aldim { font-weight: bold; margin: 14px 0 0; text-align: center; letter-spacing: .3px; }

    table.imza { width: 100%; border-collapse: collapse; margin-top: 22px; font-size: 9.5px; }
    table.imza td { border: 1px solid #666; padding: 6px 9px; width: 50%; vertical-align: top; }
    table.imza .rol { text-align: center; font-weight: bold; background: #eee; letter-spacing: .3px; }
    table.imza .alan { line-height: 2.1; }
</style>
</head>
<body>

@foreach (($form->calisanlar ?: [null]) as $c)
    <div class="sayfa">

        <table class="ust">
            <tr>
                <td class="baslik">KİŞİSEL KORUYUCU DONANIM TESLİM TUTANAĞI</td>
                <td class="dk">
                    <div><b>Form No</b> {{ $form->form_no }}</div>
                    <div><b>Tarih</b> {{ $form->teslim_tarihi?->format('d.m.Y') }}</div>
                    <div><b>Sayfa</b> 1 / 1</div>
                </td>
            </tr>
        </table>

        <table class="kunye">
            <tr>
                <td class="e">İşyeri</td><td>{{ $firma?->unvan ?: '—' }}</td>
                <td class="e">Teslim Tarihi</td><td>{{ $form->teslim_tarihi?->format('d.m.Y') ?: '—' }}</td>
            </tr>
            <tr>
                <td class="e">Adı Soyadı</td><td>{{ $c['ad_soyad'] ?? '' }}</td>
                <td class="e">Görevi</td><td>{{ $c['departman'] ?? '' }}</td>
            </tr>
            <tr>
                <td class="e">Periyodik Kontrol</td><td>{{ $form->periyodik_kontrol_tarihi?->format('d.m.Y') ?: '—' }}</td>
                <td class="e">T.C. Kimlik No</td><td>{{ $c['tc'] ?? '' }}</td>
            </tr>
        </table>

        <table class="mlz">
            <tr>
                <th style="width:26px">#</th>
                <th>MALZEMENİN TÜRÜ</th>
                <th style="width:130px">STANDARDI</th>
                <th style="width:112px">KULLANMA DÖNEMİ</th>
                <th style="width:58px">MİKTAR</th>
            </tr>
            @php $satirlar = array_values($form->kkdler ?? []); $satirSayisi = max(count($satirlar), 12); @endphp
            @for ($i = 0; $i < $satirSayisi; $i++)
                <tr>
                    <td class="no">{{ $i + 1 }}</td>
                    <td>{{ $satirlar[$i]['ad'] ?? '' }}</td>
                    <td>{{ $satirlar[$i]['standart'] ?? '' }}</td>
                    <td></td>
                    <td class="mik"></td>
                </tr>
            @endfor
        </table>

        <p class="taahhut">
            Yukarıda belirtilen kişisel koruyucu malzemelerin nerede ve ne zaman kullanacağımı ve
            kullanmadığım takdirde karşılaşabileceğim tehlikeler konusunda bilgilendirildiğimi,
        </p>
        <p class="taahhut">
            Belirtilen malzemeleri kullanmadığım takdirde, İSG birimi tarafından yapılacak olan sözlü /
            yazılı uyarıdan sonraki ihlalde 4857 sayılı Kanun 25. maddesi uyarınca görevime son
            verileceğini bildiğimi beyan ve taahhüt ederim.
        </p>
        <p class="taahhut">
            Belirtilen malzemelerin kullanılmamasından dolayı meydana gelecek ceza ve kazalar ile ilgili
            her türlü sorumluluğu kabul ederim.
        </p>
        <p class="taahhut">
            Süresiz olarak verilmiş iş güvenliği malzemeleri iş durumuna göre belirli aralıkta ve
            yıpranmalarına bakılarak yenisi verilecektir. Bunun için personel mevcut olan kişisel
            koruyucu donanımın durumunu beyan etmekle sorumludur.
        </p>

        <p class="teslim-aldim">TÜM AÇIKLAMALARI OKUDUM, ANLADIM VE İŞ GÜVENLİĞİ MALZEMELERİMİ TESLİM ALDIM.</p>

        <table class="imza">
            <tr>
                <td class="rol">TESLİM ALAN</td>
                <td class="rol">TESLİM VEREN</td>
            </tr>
            <tr>
                <td class="alan">
                    Adı Soyadı: {{ $c['ad_soyad'] ?? '' }}<br>
                    Görev: {{ $c['departman'] ?? '' }}<br>
                    İmza:<br>
                    Tarih:
                </td>
                <td class="alan">
                    Adı Soyadı: {{ $form->teslim_eden ?: '' }}<br>
                    Görev:<br>
                    İmza:<br>
                    Tarih:
                </td>
            </tr>
        </table>

    </div>
@endforeach

</body>
</html>
