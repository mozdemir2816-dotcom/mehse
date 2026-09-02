<!doctype html>
<html lang="tr">
<head>
<meta charset="utf-8">
<style>
    * { font-family: DejaVu Sans, sans-serif; }
    body { margin: 0; color: #111; font-size: 11px; }
    .sayfa { padding: 30px 36px; }
    .baslik { text-align: center; border-bottom: 3px double #111; padding-bottom: 10px; margin-bottom: 16px; }
    .baslik h1 { font-size: 16px; margin: 0 0 4px; text-transform: uppercase; }
    .kunye { width: 100%; border-collapse: collapse; font-size: 10px; margin-bottom: 14px; }
    .kunye td { border: 1px solid #999; padding: 5px 8px; }
    .kunye td:first-child { background: #f0f0f0; font-weight: bold; width: 24%; }
    .govde { font-size: 11px; line-height: 1.6; text-align: justify; margin-bottom: 16px; }
    table.uyeler { width: 100%; border-collapse: collapse; margin-bottom: 16px; font-size: 10px; }
    table.uyeler th, table.uyeler td { border: 1px solid #999; padding: 5px 8px; text-align: left; }
    table.uyeler th { background: #f0f0f0; }
    .imza { margin-top: 60px; width: 100%; }
    .imza td { width: 50%; text-align: center; padding-top: 40px; border-top: 1px solid #111; font-size: 10px; }
    .yasal { margin-top: 14px; font-size: 8.5px; color: #666; }
</style>
</head>
<body>
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
            <td>İşveren / İşveren Vekili</td><td colspan="3">{{ $kayit->isveren_vekili_adi ?: ($firma?->isveren_vekili ?: $firma?->isveren_ad) ?: '—' }}</td>
        </tr>
        @if ($kayit->gorev_baslangic)
            <tr>
                <td>Görev Başlangıç</td><td>{{ $kayit->gorev_baslangic->format('d.m.Y') }}</td>
                <td>Görev Bitiş</td><td>{{ $kayit->gorev_bitis?->format('d.m.Y') ?: 'Belirsiz süreli' }}</td>
            </tr>
        @endif
    </table>

    <div class="govde">
        {{ $firma?->unvan }} işyerinde, aşağıda bilgileri yer alan personel/personeller
        <strong>{{ $rol['ad'] ?? $kayit->rolEtiketi() }}</strong> olarak görevlendirilmiştir.
        {{ $rol['aciklama'] ?? '' }}
    </div>

    <table class="uyeler">
        <tr>
            <th style="width:5%">#</th><th>Ad Soyad</th><th style="width:16%">T.C. Kimlik No</th>
            <th style="width:20%">Görev / Unvan</th>
            @if ($kayit->ekipMi())<th style="width:10%">Baş Üye</th>@endif
            @if ($kayit->ekipMi())<th style="width:14%">Kaşe / İmza</th>@endif
        </tr>
        @forelse (($kayit->uyeler ?? []) as $i => $u)
            <tr>
                <td>{{ $i + 1 }}</td>
                <td>{{ $u['ad_soyad'] ?? '—' }}</td>
                <td>{{ $u['tc'] ?? '—' }}</td>
                <td>{{ $u['gorev'] ?? '—' }}</td>
                @if ($kayit->ekipMi())<td>{{ ($u['bas_uye'] ?? false) ? 'Evet' : '—' }}</td>@endif
                @if ($kayit->ekipMi())
                    <td>
                        @if (! empty($u['kase_gorseli']))
                            <img src="{{ storage_path('app/public/'.$u['kase_gorseli']) }}" style="max-height:36px;max-width:100%">
                        @else
                            —
                        @endif
                    </td>
                @endif
            </tr>
        @empty
            <tr><td colspan="{{ $kayit->ekipMi() ? 6 : 4 }}" style="color:#888">Üye eklenmedi.</td></tr>
        @endforelse
    </table>

    <table class="imza">
        <tr>
            <td>İşveren / İşveren Vekili<br>(İmza – Kaşe)</td>
            <td>Görevlendirilen Personel<br>(İmza)</td>
        </tr>
    </table>

    <p class="yasal">6331 Sayılı İş Sağlığı ve Güvenliği Kanunu ve ilgili yönetmelikler uyarınca düzenlenmiştir.</p>

</div>
</body>
</html>
