<!doctype html>
<html lang="tr">
<head>
<meta charset="utf-8">
<style>
    * { font-family: DejaVu Sans, sans-serif; }
    body { margin: 0; color: #111; }
    .sayfa { padding: 30px 40px; page-break-after: always; position: relative; height: 100%; box-sizing: border-box; }
    .sayfa:last-child { page-break-after: avoid; }
    .cerceve-klasik_siyah { border: 3px double #111; padding: 24px; height: calc(100% - 48px); box-sizing: border-box; }
    .cerceve-mor { border: 4px solid rgb(139 92 246); padding: 24px; height: calc(100% - 48px); box-sizing: border-box; }
    .cerceve-sade { padding: 10px; }
    .logo { max-height: 60px; }
    .ust { width: 100%; margin-bottom: 10px; }
    .ust td { vertical-align: middle; width: 33%; }
    .baslik { text-align: center; }
    .baslik h1 { font-size: 22px; margin: 0 0 4px; letter-spacing: 1px; }
    .baslik h2 { font-size: 13px; margin: 0; font-weight: normal; color: #444; }
    .govde { text-align: center; margin-top: 26px; font-size: 12px; }
    .govde .kisi { font-size: 20px; font-weight: bold; margin: 10px 0; }
    .govde .tc { font-size: 11px; color: #555; margin-bottom: 14px; }
    .bilgi { width: 70%; margin: 18px auto 0; border-collapse: collapse; font-size: 10.5px; }
    .bilgi td { padding: 4px 8px; }
    .bilgi td:first-child { font-weight: bold; width: 45%; text-align: right; color: #444; }
    .bilgi td:last-child { text-align: left; }
    .konular { margin-top: 16px; font-size: 9.5px; color: #444; text-align: left; columns: 2; column-gap: 24px; }
    .konular div { break-inside: avoid; margin-bottom: 2px; }
    .imza { margin-top: 34px; width: 100%; }
    .imza td { width: 50%; text-align: center; vertical-align: bottom; font-size: 10px; }
    .imza img { max-height: 44px; display: block; margin: 0 auto 4px; }
    .imza .cizgi { border-top: 1px solid #111; padding-top: 4px; display: inline-block; min-width: 60%; }
    .belge-no { position: absolute; bottom: 8px; right: 16px; font-size: 8px; color: #999; }
</style>
</head>
<body>

@forelse (($sertifika->katilimcilar ?? []) as $k)
    <div class="sayfa">
        <div class="cerceve-{{ $sertifika->cerceve }}">
            <table class="ust">
                <tr>
                    <td style="text-align:left">
                        @if (in_array($sertifika->logo_konumu, ['sol', 'iki_taraf']) && $firma?->logo)
                            <img class="logo" src="{{ storage_path('app/public/'.$firma->logo) }}">
                        @endif
                    </td>
                    <td></td>
                    <td style="text-align:right">
                        @if (in_array($sertifika->logo_konumu, ['sag', 'iki_taraf']) && $firma?->logo)
                            <img class="logo" src="{{ storage_path('app/public/'.$firma->logo) }}">
                        @endif
                    </td>
                </tr>
            </table>

            <div class="baslik">
                <h1>SERTİFİKA</h1>
                <h2>{{ $sertifika->tipBasligi() }}</h2>
            </div>

            <div class="govde">
                Bu sertifika,
                <div class="kisi">{{ $k['ad_soyad'] ?? '—' }}</div>
                @if (! empty($k['tc']))
                    <div class="tc">T.C. Kimlik No: {{ $k['tc'] }}</div>
                @endif
                adlı katılımcının <strong>{{ $firma?->unvan }}</strong> işyerinde düzenlenen
                <strong>{{ $sertifika->tipEtiketi() }}</strong> eğitimini başarıyla tamamladığını belgeler.
            </div>

            <table class="bilgi">
                @if ($sertifika->egitim_tarihleri)
                    <tr><td>Eğitim Tarihleri</td><td>{{ collect($sertifika->egitim_tarihleri)->filter()->map(fn ($t) => \Illuminate\Support\Carbon::parse($t)->format('d.m.Y'))->implode(' · ') ?: '—' }}</td></tr>
                @endif
                @if ($sertifika->sure_metni)
                    <tr><td>Eğitim Süresi</td><td>{{ $sertifika->sure_metni }}</td></tr>
                @endif
                <tr><td>Geçerlilik Tarihi</td><td>{{ $sertifika->gecerlilik_tarihi?->format('d.m.Y') ?: '—' }}</td></tr>
                <tr><td>Belge No</td><td>{{ $sertifika->belge_no }}</td></tr>
            </table>

            @if ($sertifika->cokluEgiticiMi() && ($sertifika->konu_icerigi['tip'] ?? null) === 'genel')
                <div class="konular">
                    @foreach (($sertifika->konu_icerigi['genel_konular'] ?? []) as $m)
                        <div>· {{ $m['madde'] }}</div>
                    @endforeach
                    @foreach (($sertifika->konu_icerigi['saglik_konulari'] ?? []) as $m)
                        <div>· {{ $m['madde'] }}</div>
                    @endforeach
                    @foreach (($sertifika->konu_icerigi['teknik_konular'] ?? []) as $m)
                        <div>· {{ $m['madde'] }}</div>
                    @endforeach
                    @foreach (($sertifika->konu_icerigi['isyerine_ozgu']['maddeler'] ?? []) as $madde)
                        <div>· {{ $madde }}</div>
                    @endforeach
                </div>
            @else
                <div class="konular">
                    @foreach (($sertifika->konu_icerigi['maddeler'] ?? []) as $madde)
                        <div>· {{ $madde }}</div>
                    @endforeach
                </div>
            @endif

            <table class="imza">
                <tr>
                    @if ($sertifika->egitici_igu_dahil)
                        <td>
                            @if ($sertifika->egitici_igu_kase)
                                <img src="{{ storage_path('app/public/'.$sertifika->egitici_igu_kase) }}">
                            @endif
                            <span class="cizgi">{{ $sertifika->egitici_igu_adi ?: 'İş Güvenliği Uzmanı' }}</span>
                        </td>
                    @endif
                    @if ($sertifika->cokluEgiticiMi() && $sertifika->egitici_hekim_dahil)
                        <td>
                            @if ($sertifika->egitici_hekim_kase)
                                <img src="{{ storage_path('app/public/'.$sertifika->egitici_hekim_kase) }}">
                            @endif
                            <span class="cizgi">{{ $sertifika->egitici_hekim_adi ?: 'İşyeri Hekimi' }}</span>
                        </td>
                    @endif
                </tr>
            </table>

            <div class="belge-no">{{ $sertifika->belge_no }}</div>
        </div>
    </div>
@empty
    <div class="sayfa">
        <div class="cerceve-{{ $sertifika->cerceve }}">
            <p style="text-align:center;color:#888;margin-top:40%">Katılımcı eklenmedi.</p>
        </div>
    </div>
@endforelse

</body>
</html>
