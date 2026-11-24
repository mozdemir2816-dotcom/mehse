<!doctype html>
<html lang="tr">
<head>
<meta charset="utf-8">
<style>
    * { font-family: DejaVu Sans, sans-serif; }
    body { margin: 0; color: #111; font-size: 11px; }
    .sayfa { padding: 28px 34px; }
    .baslik { text-align: center; border-bottom: 3px double #111; padding-bottom: 10px; margin-bottom: 14px; }
    .baslik h1 { font-size: 15px; margin: 0 0 4px; }
    .kunye { width: 100%; border-collapse: collapse; font-size: 10px; margin-bottom: 12px; }
    .kunye td { border: 1px solid #999; padding: 5px 8px; }
    .kunye td:nth-child(odd) { background: #f0f0f0; font-weight: bold; width: 22%; }
    h2 { font-size: 12px; margin: 14px 0 6px; color: #ef4444; border-bottom: 1px solid #ef4444; padding-bottom: 3px; }
    table.liste { width: 100%; border-collapse: collapse; font-size: 9.5px; margin-bottom: 10px; }
    table.liste th, table.liste td { border: 1px solid #999; padding: 4px 6px; text-align: left; }
    table.liste th { background: #f0f0f0; }
    .yaptirim { display: inline-block; background: #ef4444; color: #fff; padding: 3px 10px; border-radius: 4px; font-size: 10.5px; font-weight: bold; }
    .imza { margin-top: 22px; width: 100%; border-collapse: collapse; page-break-inside: avoid; }
    .imza td { width: 33.33%; text-align: center; padding: 6px; font-size: 9.5px; vertical-align: bottom; }
    .imza .alan { height: 42px; border-bottom: 1px solid #111; margin: 0 10px 4px; }
    .yasal { margin-top: 14px; font-size: 8.5px; color: #666; }
    .foto-sayfa { page-break-before: always; }
    .foto-sayfa .baslik2 { font-size: 11px; font-weight: bold; margin-bottom: 6px; }
    .foto-sayfa img { max-width: 100%; max-height: 880px; }
</style>
</head>
<body>
<div class="sayfa">

    <div class="baslik">
        <h1>İSG CEZA VE TEBLİĞ TUTANAĞI</h1>
        <div style="font-size:11px">{{ $firma?->unvan }} — Tutanak No: {{ $tutanak->tutanak_no }}</div>
    </div>

    <table class="kunye">
        <tr>
            <td>Çalışan</td><td>{{ $tutanak->calisan_ad_soyad ?: '—' }}</td>
            <td>T.C. Kimlik No</td><td>{{ $tutanak->calisan_tc ? \App\Support\GeciciGorevlendirmeUretici::tcMaskele($tutanak->calisan_tc, true) : '—' }}</td>
        </tr>
        <tr>
            <td>Görevi / Bölümü</td><td>{{ $tutanak->calisan_gorev ?: '—' }} / {{ $tutanak->calisan_bolum ?: '—' }}</td>
            <td>İstihdam Şekli</td><td>{{ $tutanak->istihdamSekliEtiketi() }}</td>
        </tr>
        <tr>
            <td>İşe Giriş Tarihi</td><td>{{ $tutanak->ise_giris_tarihi?->format('d.m.Y') ?: '—' }}</td>
            <td>Tutanak Tarihi</td><td>{{ $tutanak->tutanak_tarihi?->format('d.m.Y') ?: '—' }}</td>
        </tr>
    </table>

    <h2>OLAY BİLGİLERİ</h2>
    <table class="kunye">
        <tr>
            <td>Olay Tarihi / Saati</td><td>{{ $tutanak->olay_tarihi?->format('d.m.Y') ?: '—' }} {{ $tutanak->olay_saati }}</td>
            <td>Olay Yeri</td><td>{{ $tutanak->olay_yeri ?: '—' }}</td>
        </tr>
        <tr><td>Açıklama</td><td colspan="3">{{ $tutanak->olay_aciklamasi ?: '—' }}</td></tr>
    </table>

    <h2>İHLAL EDİLEN KURALLAR</h2>
    <table class="liste">
        <tr><th style="width:5%">#</th><th>Madde</th><th style="width:35%">Yasal Dayanak</th></tr>
        @forelse (($tutanak->ihlaller ?? []) as $i => $ih)
            <tr><td>{{ $i + 1 }}</td><td>{{ $ih['madde'] ?? '' }}</td><td>{{ $ih['dayanak'] ?? '—' }}</td></tr>
        @empty
            <tr><td colspan="3" style="color:#888">İhlal maddesi eklenmedi.</td></tr>
        @endforelse
    </table>

    <h2>TANIKLAR</h2>
    <table class="liste">
        <tr><th style="width:5%">#</th><th>Ad Soyad</th><th>Görevi</th><th style="width:22%">İmza</th></tr>
        @forelse (($tutanak->taniklar ?? []) as $i => $tk)
            <tr><td>{{ $i + 1 }}</td><td>{{ $tk['ad_soyad'] ?? '' }}</td><td>{{ $tk['gorev'] ?? '—' }}</td><td>&nbsp;</td></tr>
        @empty
            <tr><td colspan="4" style="color:#888">Tanık eklenmedi.</td></tr>
        @endforelse
    </table>

    <h2>UYGULANAN YAPTIRIM</h2>
    <span class="yaptirim">{{ $tutanak->yaptirimEtiketi() }}</span>

    <h2>TEBLİĞ / TEBELLÜĞ VE SAVUNMA HAKKI (4857 s.K. md.19)</h2>
    <table class="kunye">
        <tr>
            <td>Tebliğ Tarihi</td><td>{{ $tutanak->teblig_tarihi?->format('d.m.Y') ?: '—' }}</td>
            <td>Çalışanın İmza Durumu</td>
            <td>{{ $tutanak->imza_durumu === 'imtina_etti' ? 'İmzadan İmtina Etti' : 'İmzaladı / Teslim Aldı' }}</td>
        </tr>
        <tr>
            <td colspan="4" style="font-size:8px;color:#333">
                İşbu tutanağın bir nüshasını elden teslim aldım. 4857 sayılı İş Kanunu md.19 uyarınca konu hakkındaki
                yazılı savunmamı tebliğ tarihinden itibaren makul süre içinde işverene sunma hakkım tarafıma bildirilmiştir.
            </td>
        </tr>
    </table>

    <table class="imza">
        <tr>
            <td><div class="alan"></div>Tespit Eden (İSG Uzmanı)<br>(İmza – Kaşe)</td>
            <td><div class="alan"></div>Tebliğ Eden (İşveren / Vekili)<br>(İmza – Kaşe)</td>
            <td>
                <div class="alan">
                    @if (! empty($canliImza) && $tutanak->imza_durumu !== 'imtina_etti' && (str_starts_with((string) $canliImza, 'data:image/') || is_file((string) $canliImza)))
                        <img src="{{ $canliImza }}" style="max-height:40px;display:block;margin:0 auto">
                    @endif
                </div>
                Tebellüğ Eden (Çalışan)<br>{{ $tutanak->imza_durumu === 'imtina_etti' ? '(İmzadan İmtina Etti)' : '(İmza)' }}
            </td>
        </tr>
    </table>

    @if ($tutanak->imza_durumu === 'imtina_etti')
        <table class="kunye" style="margin-top:10px;page-break-inside:avoid">
            <tr>
                <td colspan="2" style="background:#fef2f2;color:#991b1b;font-weight:bold;width:100%">
                    İMZADAN İMTİNA ŞERHİ — Tutanak çalışana okunarak tebliğ edilmiş, imzalamaktan imtina etmesi üzerine aşağıda imzası bulunan iki tanık huzurunda kayıt altına alınmıştır.
                </td>
            </tr>
            <tr>
                <td style="width:50%;height:38px;background:#fff;font-weight:normal;vertical-align:top"><strong>1. Tanık Ad Soyad / İmza:</strong></td>
                <td style="width:50%;height:38px;background:#fff;font-weight:normal;vertical-align:top"><strong>2. Tanık Ad Soyad / İmza:</strong></td>
            </tr>
        </table>
    @endif

    <p class="yasal">
        6331 sayılı İş Sağlığı ve Güvenliği Kanunu m.19, 4857 sayılı İş Kanunu m.19, m.25/II ve m.38 uyarınca düzenlenmiştir.
        Ücret kesme cezası ancak iş/toplu iş sözleşmesinde açıkça gösterilen sebeplerle, bir ayda en fazla iki gündelik tutarında uygulanabilir ve kesinti bir ay içinde ÇSGB hesabına yatırılır (4857 m.38).
        @if (! empty($canliImza) && $tutanak->imza_durumu !== 'imtina_etti')
            (Elektronik ortamda alınan canlı imza 5070 SK anlamında nitelikli e-imza olmayıp basit elektronik onay kaydıdır.)
        @endif
    </p>

</div>

@foreach (($tutanak->fotograflar ?? []) as $i => $foto)
    @php
        $fotoYol = null;
        if ($foto) {
            $gercek = storage_path('app/public/'.$foto);
            if (is_file($gercek)) {
                $fotoYol = $gercek;
            } elseif (\Illuminate\Support\Facades\Storage::disk('public')->exists($foto)) {
                $fotoYol = \Illuminate\Support\Facades\Storage::disk('public')->path($foto);
            }
        }
    @endphp
    @if ($fotoYol)
        <div class="sayfa foto-sayfa">
            <div class="baslik">
                <h1>İSG CEZA VE TEBLİĞ TUTANAĞI</h1>
                <div style="font-size:11px">{{ $firma?->unvan }} — Tutanak No: {{ $tutanak->tutanak_no }}</div>
            </div>
            <div class="baslik2">FOTOĞRAF {{ $i + 1 }}</div>
            <img src="{{ $fotoYol }}">
        </div>
    @endif
@endforeach

</body>
</html>
