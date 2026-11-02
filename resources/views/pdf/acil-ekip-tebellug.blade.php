<!doctype html>
<html lang="tr">
<head>
<meta charset="utf-8">
<style>
    * { font-family: "DejaVu Sans", sans-serif; }
    @page { margin: 34px 40px; }
    body { margin: 0; color: #111; font-size: 10px; line-height: 1.45; }
    .sayfa { page-break-after: always; }
    .sayfa:last-child { page-break-after: auto; }
    h1 { font-size: 13px; text-align: center; margin: 0 0 3px; letter-spacing: .2px; }
    .alt { text-align: center; font-size: 8.5px; color: #444; margin-bottom: 12px; }
    table { width: 100%; border-collapse: collapse; }
    .kunye td { border: 1px solid #888; padding: 5px 7px; }
    .kunye td.e { background: #eee; font-weight: bold; width: 22%; }
    h2 { font-size: 10.5px; margin: 14px 0 5px; padding: 3px 6px; background: #0d9488; color: #fff; }
    ol { margin: 0; padding-left: 16px; }
    ol li { margin-bottom: 3px; }
    .metin { text-align: justify; margin-top: 10px; }
    .imza { margin-top: 18px; }
    .imza td { border: 1px solid #888; padding: 6px; width: 50%; height: 92px; vertical-align: top; text-align: center; }
    .imza .b { font-weight: bold; font-size: 9.5px; }
    .imza .k { font-size: 8.5px; color: #444; }
    .nokta { color: #777; }
</style>
</head>
<body>
@foreach ($uyeler as $u)
    @php
        $ekip = $u->ekip;
        $gorev = $ekip->ad.' '.($u->lider ? 'Ekip Lideri' : ($u->uyelik === 'yedek' ? 'Yedek Üye' : 'Asıl Üye'));
        $maddeler = [
            ...(config('isg.acil_durum.ekip_gorevleri.'.$ekip->tur) ?? config('isg.acil_durum.ekip_gorevleri.diger')),
            ...($u->lider ? config('isg.acil_durum.ekip_gorevleri.lider') : []),
            ...config('isg.acil_durum.ekip_gorevleri.ortak'),
        ];
        $tc = $u->calisan?->tc;
    @endphp
    <div class="sayfa">
        <h1>ACİL DURUM DESTEK ELEMANI GÖREVLENDİRME VE TEBELLÜĞ BELGESİ</h1>
        <div class="alt">6331 sayılı İş Sağlığı ve Güvenliği Kanunu Md.11-12 · İşyerlerinde Acil Durumlar Hakkında Yönetmelik Md.11{{ $ekip->tur === 'ilk_yardim' ? ' · İlkyardım Yönetmeliği' : '' }}</div>

        <table class="kunye">
            <tr><td class="e">İşyeri Unvanı</td><td colspan="3">{{ $firma->unvan }}</td></tr>
            <tr><td class="e">SGK Sicil No</td><td colspan="3">{{ $firma->sgk_sicil_no ?: '—' }}</td></tr>
            <tr><td class="e">Adı Soyadı</td><td style="width:30%">{{ $u->ad_soyad }}</td><td class="e" style="width:18%">T.C. Kimlik No</td><td>{!! $tc ?: '<span class="nokta">..............................</span>' !!}</td></tr>
            <tr><td class="e">Görevi / Bölümü</td><td>{{ collect([$u->gorev, $u->bolum])->filter()->implode(' / ') ?: '—' }}</td><td class="e">Vardiya</td><td>{{ $u->vardiya ?: '—' }}</td></tr>
            <tr><td class="e">Acil Durum Görevi</td><td colspan="3"><strong>{{ $gorev }}</strong></td></tr>
            <tr><td class="e">Eğitim Belgesi</td><td>{{ $u->belge_no ?: '—' }}</td><td class="e">Geçerlilik</td><td>{{ $u->belgeBitisTarihi()?->format('d.m.Y') ?? '—' }}</td></tr>
        </table>

        <div class="metin">
            İşyerlerinde Acil Durumlar Hakkında Yönetmelik'in 11. maddesi uyarınca, işyerimizde acil durumlarla mücadele
            amacıyla oluşturulan <strong>{{ $ekip->ad }}</strong>'nde <strong>{{ $u->lider ? 'ekip lideri' : ($u->uyelik === 'yedek' ? 'yedek üye' : 'asıl üye') }}</strong>
            olarak görevlendirilmiş bulunmaktasınız. Bu görev kapsamında aşağıdaki görev ve sorumlulukları yerine getirmeniz gerekmektedir.
        </div>

        <h2>Görev ve Sorumluluklar</h2>
        <ol>
            @foreach ($maddeler as $m)<li>{{ $m }}</li>@endforeach
        </ol>

        <div class="metin">
            Yukarıda belirtilen görev ve sorumluluklar tarafıma tebliğ edilmiş, okuyup anladım. Görevimle ilgili eğitimlere
            katılacağımı ve acil durumlarda bu görevleri yerine getireceğimi kabul ederim.
        </div>

        <table class="imza">
            <tr>
                <td>
                    <div class="b">TEBLİĞ EDEN</div>
                    <div class="k">İşveren / İşveren Vekili — Kaşe ve İmza</div>
                    <div style="margin-top:6px">{{ $isveren ?: '' }}</div>
                    <div class="k" style="margin-top:4px">Tarih: {{ $tarih }}</div>
                </td>
                <td>
                    <div class="b">TEBELLÜĞ EDEN</div>
                    <div class="k">Destek Elemanı — İmza</div>
                    <div style="margin-top:6px">{{ $u->ad_soyad }}</div>
                    <div class="k" style="margin-top:4px">Tarih: ....../....../..........</div>
                </td>
            </tr>
            <tr>
                <td colspan="2" style="height:58px">
                    <div class="b">İŞ GÜVENLİĞİ UZMANI</div>
                    <div class="k">{{ $uzman['ad'] ?? '' }}{{ ($uzman['sinif'] ?? null) ? ' — '.$uzman['sinif'].' Sınıfı İSG Uzmanı' : '' }} · İmza</div>
                </td>
            </tr>
        </table>
    </div>
@endforeach
</body>
</html>
