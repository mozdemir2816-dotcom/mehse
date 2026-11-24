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

    table.imza { width: 100%; border-collapse: collapse; margin-top: 22px; font-size: 9.5px; page-break-inside: avoid; }
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
                    <div><b>Sayfa</b> {{ $loop->iteration }} / {{ $loop->count }}</div>
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
                <td class="e">Görevi / Bölümü</td><td>{{ collect([$c['gorev'] ?? null, $c['departman'] ?? null])->filter()->unique()->implode(' / ') }}</td>
            </tr>
            <tr>
                <td class="e">Periyodik Kontrol</td><td>{{ $form->periyodik_kontrol_tarihi?->format('d.m.Y') ?: '—' }}</td>
                <td class="e">T.C. Kimlik No</td><td>{{ $c['tc'] ?? '' }}</td>
            </tr>
        </table>

        <table class="mlz">
            <tr>
                <th style="width:24px">#</th>
                <th>MALZEMENİN TÜRÜ</th>
                <th style="width:95px">MARKA / MODEL</th>
                <th style="width:105px">CE / EN STANDARDI</th>
                <th style="width:95px">KULLANMA DÖNEMİ</th>
                <th style="width:48px">MİKTAR</th>
            </tr>
            @php $satirlar = array_values($form->kkdler ?? []); $satirSayisi = max(count($satirlar), 12); @endphp
            @for ($i = 0; $i < $satirSayisi; $i++)
                <tr>
                    <td class="no">{{ $i + 1 }}</td>
                    <td>{{ $satirlar[$i]['ad'] ?? '' }}</td>
                    <td>{{ $satirlar[$i]['marka_model'] ?? $satirlar[$i]['marka'] ?? '' }}</td>
                    <td>{{ $satirlar[$i]['standart'] ?? '' }}</td>
                    <td>{{ $satirlar[$i]['donem'] ?? '' }}</td>
                    <td class="mik">{{ $satirlar[$i]['miktar'] ?? '' }}</td>
                </tr>
            @endfor
        </table>

        <p class="taahhut">
            6331 sayılı İş Sağlığı ve Güvenliği Kanunu m.4 ve Kişisel Koruyucu Donanımların İşyerlerinde
            Kullanılması Hakkında Yönetmelik m.6–8 uyarınca; yukarıda belirtilen CE/EN standartlarına uygun
            kişisel koruyucu donanımların tarafıma ücretsiz olarak teslim edildiğini, kullanım yeri, süresi,
            bakım/saklama koşulları ve uygulamalı kullanım eğitimi hakkında bilgilendirildiğimi beyan ederim.
        </p>
        <p class="taahhut">
            6331 sayılı Kanun m.19 uyarınca; teslim aldığım kişisel koruyucu donanımları belirlenen iş ve
            alanlarda doğru şekilde kullanacağımı, koruyacağımı, bakım ve temizliğine özen göstereceğimi,
            hasar veya eksiklik durumunda işverene / amirime derhal bildireceğimi taahhüt ederim.
        </p>
        <p class="taahhut">
            İş sağlığı ve güvenliği kurallarına ve KKD kullanım talimatlarına aykırı davranılması halinde
            4857 sayılı Kanun 25. maddesi ve ilgili disiplin hükümleri çerçevesinde işlem yapılabileceğini
            bildiğimi beyan ederim.
        </p>
        <p class="taahhut">
            Teslim edilen kişisel koruyucu donanımlar iş durumuna, kullanım ömrüne ve yıpranmasına bağlı olarak
            işveren tarafından ücretsiz olarak yenilenecektir; çalışan mevcut donanımın durumunu zamanında
            bildirmekle yükümlüdür.
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
                    Görev: {{ $c['gorev'] ?? $c['departman'] ?? '' }}<br>
                    İmza:
                    @php $kisiImza = $c['imza'] ?? (count($form->calisanlar ?? []) === 1 ? ($canliImza ?? null) : null); @endphp
                    @if (! empty($kisiImza) && (str_starts_with((string) $kisiImza, 'data:image/') || is_file((string) $kisiImza)))
                        <img src="{{ $kisiImza }}" style="max-height:38px;vertical-align:middle">
                    @endif
                    <br>
                    Tarih: {{ $form->teslim_tarihi?->format('d.m.Y') ?? '' }}
                </td>
                <td class="alan">
                    Adı Soyadı: {{ $form->teslim_eden ?: ($firma?->isveren_vekili ?: ($firma?->isveren_ad ?: '')) }}<br>
                    Görev: İşveren / Yetkili<br>
                    İmza:<br>
                    Tarih: {{ $form->teslim_tarihi?->format('d.m.Y') ?? '' }}
                </td>
            </tr>
        </table>

    </div>
@endforeach

</body>
</html>
