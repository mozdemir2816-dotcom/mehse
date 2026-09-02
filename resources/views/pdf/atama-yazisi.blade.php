<!doctype html>
<html lang="tr">
<head>
<meta charset="utf-8">
<style>
    * { font-family: DejaVu Sans, sans-serif; }
    body { margin: 0; color: #111; font-size: 11px; }
    .sayfa { padding: 30px 36px; }
    .sayfa + .sayfa { page-break-before: always; }
    .baslik { text-align: center; border-bottom: 3px double #111; padding-bottom: 10px; margin-bottom: 16px; }
    .baslik h1 { font-size: 16px; margin: 0 0 4px; text-transform: uppercase; }
    .kunye { width: 100%; border-collapse: collapse; font-size: 10px; margin-bottom: 14px; }
    .kunye td { border: 1px solid #999; padding: 5px 8px; }
    .kunye td:first-child { background: #f0f0f0; font-weight: bold; width: 24%; }
    .govde { font-size: 11.5px; line-height: 1.7; text-align: justify; margin-bottom: 18px; }
    .imza { margin-top: 60px; width: 100%; }
    .imza td { width: 50%; text-align: center; padding-top: 40px; border-top: 1px solid #111; font-size: 10px; }
    .imza img { max-height: 40px; display: block; margin: 0 auto -32px auto; }
    .yasal { margin-top: 14px; font-size: 8.5px; color: #666; }
</style>
</head>
<body>
@forelse (($kayit->uyeler ?? []) as $u)
    <div class="sayfa">

        <div class="baslik">
            <h1>{{ $rol['ad'] ?? $kayit->rolEtiketi() }} Görevlendirme Yazısı</h1>
            <div style="font-size:11px">{{ $firma?->unvan }}</div>
        </div>

        <table class="kunye">
            <tr>
                <td>Doküman No</td><td>{{ $kayit->dokuman_no }}</td>
                <td>Tarih</td><td>{{ $kayit->tarih?->format('d.m.Y') }}</td>
            </tr>
            <tr>
                <td>Ad Soyad</td><td>{{ $u['ad_soyad'] ?? '—' }}</td>
                <td>T.C. Kimlik No</td><td>{{ $u['tc'] ?? '—' }}</td>
            </tr>
            <tr>
                <td>Görev / Unvan</td><td>{{ $u['gorev'] ?? '—' }}</td>
                <td>İşveren / İşveren Vekili</td><td>{{ $kayit->isveren_vekili_adi ?: ($firma?->isveren_vekili ?: $firma?->isveren_ad) ?: '—' }}</td>
            </tr>
            @if ($kayit->gorev_baslangic)
                <tr>
                    <td>Görev Başlangıç</td><td>{{ $kayit->gorev_baslangic->format('d.m.Y') }}</td>
                    <td>Görev Bitiş</td><td>{{ $kayit->gorev_bitis?->format('d.m.Y') ?: 'Belirsiz süreli' }}</td>
                </tr>
            @endif
        </table>

        <div class="govde">
            {{ $firma?->unvan }} işyerinde görevli <strong>{{ $u['ad_soyad'] ?? '' }}</strong>,
            {{ $kayit->tarih?->format('d.m.Y') }} tarihinden itibaren
            <strong>{{ $rol['ad'] ?? $kayit->rolEtiketi() }}</strong> olarak görevlendirilmiştir.
            @if ($u['bas_uye'] ?? false)
                Adı geçen personel ekibin baş üyesi olarak görevlendirilmiştir.
            @endif
        </div>

        <table class="imza">
            <tr>
                <td>İşveren / İşveren Vekili<br>(İmza – Kaşe)</td>
                <td>
                    @if (! empty($u['kase_gorseli']))
                        <img src="{{ storage_path('app/public/'.$u['kase_gorseli']) }}">
                    @endif
                    {{ $u['ad_soyad'] ?? '' }}<br>(Görevlendirilen Personel – İmza)
                </td>
            </tr>
        </table>

        <p class="yasal">6331 Sayılı İş Sağlığı ve Güvenliği Kanunu ve ilgili yönetmelikler uyarınca düzenlenmiştir.</p>

    </div>
@empty
    <div class="sayfa"><p style="color:#888">Üye eklenmedi.</p></div>
@endforelse
</body>
</html>
