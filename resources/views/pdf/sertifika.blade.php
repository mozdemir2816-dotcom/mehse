<!doctype html>
<html lang="tr">
<head>
<meta charset="utf-8">
<style>
    * { font-family: DejaVu Sans, sans-serif; }
    body { margin: 0; color: #111; font-size: 8px; }
    .sayfa { position: relative; page-break-after: always; page-break-inside: avoid; }
    .sayfa:last-child { page-break-after: avoid; }
    .ic { padding: 14px 20px; }
    .cerceve-sade .ic { padding: 16px 22px; }
    .cerceve-mavi_kose { border: 3px double #1e5f8c; margin: 10px; }
    .cerceve-mavi_kose .kose { position: absolute; width: 14px; height: 14px; background: #1e5f8c; }
    .cerceve-mavi_kose .kose-sol-ust { top: 6px; left: 6px; }
    .cerceve-mavi_kose .kose-sag-ust { top: 6px; right: 6px; }
    .cerceve-mavi_kose .kose-sol-alt { bottom: 6px; left: 6px; }
    .cerceve-mavi_kose .kose-sag-alt { bottom: 6px; right: 6px; }
    .cerceve-altin_susleme { border: 3px double #b8860b; border-radius: 14px; margin: 10px; }
    .cerceve-gri_cizgi { border: 3px double #444; margin: 10px; }
    .baslik { text-align: center; border-bottom: 2px solid #111; padding-bottom: 4px; margin-bottom: 6px; }
    .baslik img { max-height: 32px; float: left; }
    .baslik h1 { font-size: 12px; margin: 0; }
    .bilgi { width: 100%; border-collapse: collapse; font-size: 8px; margin-bottom: 5px; }
    .bilgi td { padding: 1px 5px; vertical-align: top; width: 25%; line-height: 1.25; }
    .bilgi td.etiket { font-weight: bold; width: 20%; }
    .metin { font-size: 8px; text-align: justify; margin-bottom: 5px; line-height: 1.3; }
    h3 { font-size: 8.5px; margin: 4px 0 2px; }
    table.konular { width: 100%; border-collapse: collapse; }
    table.konular > tr > td { width: 50%; vertical-align: top; padding: 0 5px 0 0; }
    .blok { margin-bottom: 3px; }
    .blok-baslik { font-weight: bold; font-size: 7.5px; margin-bottom: 1px; }
    .blok p { margin: 0 0 1px; font-size: 7px; line-height: 1.2; }
    table.imza { width: 100%; border-collapse: collapse; margin-top: 8px; }
    table.imza td { width: 33.33%; vertical-align: top; font-size: 7.5px; padding-right: 8px; line-height: 1.25; }
    table.imza img { max-height: 28px; display: block; margin: 2px 0; }
    .not { margin-top: 4px; font-size: 6.5px; color: #666; }
    .tarih-sag { text-align: right; font-size: 6.5px; font-weight: bold; margin-top: 1px; }
</style>
</head>
<body>

@php
    // Türk alfabesi sırasına göre madde harflendirme (a, b, c, ç, d, ...).
    $turkceAlfabe = ['a','b','c','ç','d','e','f','g','ğ','h','ı','i','j','k','l','m','n','o','ö','p','r','s','ş','t','u','ü','v','y','z'];
    $harf = fn (int $i) => $turkceAlfabe[$i] ?? (string) ($i + 1);
    $sure = fn (array $maddeler) => \App\Support\EgitimIcerikOlusturucu::bolumSuresi($maddeler);
    $goster = fn (array $maddeler) => collect($maddeler)->where('dahil', true)->values();
@endphp

@forelse (($sertifika->katilimcilar ?? []) as $k)
    <div class="sayfa cerceve-{{ $sertifika->cerceve }}">
        @if ($sertifika->cerceve === 'mavi_kose')
            <div class="kose kose-sol-ust"></div>
            <div class="kose kose-sag-ust"></div>
            <div class="kose kose-sol-alt"></div>
            <div class="kose kose-sag-alt"></div>
        @endif
        <div class="ic">

        <div class="baslik">
            @if ($firma?->logo)
                <img src="{{ storage_path('app/public/'.$firma->logo) }}">
            @endif
            <h1>{{ \App\Support\TurkceMetin::buyuk($sertifika->tipBasligi()) }}</h1>
        </div>

        <table class="bilgi">
            <tr>
                <td class="etiket">Katılımcının Adı Soyadı</td><td>: <strong>{{ $k['ad_soyad'] ?? '—' }}</strong></td>
                <td class="etiket">Katılımcının T.C. No</td><td>: {{ $k['tc'] ?? '' }}</td>
            </tr>
            <tr>
                <td class="etiket">Katılımcının Görev Ünvanı</td><td>: {{ $k['gorev'] ?? '—' }}</td>
                <td class="etiket">Eğitim Tarihi</td><td>: {{ collect($sertifika->egitim_tarihleri)->filter()->map(fn ($t) => \Illuminate\Support\Carbon::parse($t)->format('d.m.Y'))->implode('-') ?: '—' }}</td>
            </tr>
            <tr>
                <td class="etiket">Eğitim Türü / Şekli</td><td>: {{ $sertifika->turEtiketi() }} | {{ $sertifika->sekilEtiketi() }}</td>
                <td class="etiket">Geçerlilik Tarihi</td><td>: {{ $sertifika->gecerlilik_tarihi?->format('d.m.Y') ?: '—' }}</td>
            </tr>
            <tr>
                <td class="etiket">Firma</td><td>: {{ $firma?->unvan }}</td>
                <td class="etiket">Eğitim Süresi</td><td>: {{ $sertifika->sure_metni ?: '—' }}</td>
            </tr>
        </table>

        <p class="metin">
            Yukarıda adı geçen katılımcı, "Çalışanların İş Sağlığı ve Güvenliği Eğitimleri Usul ve Esasları
            hakkında" yönetmelik kapsamında verilen "{{ $sertifika->tipEtiketi() }}" eğitimlerini başarıyla
            tamamlayarak bu eğitim belgesini almaya hak kazanmıştır.
        </p>

        <h3>Eğitimin Konuları :</h3>

        @if ($sertifika->cokluEgiticiMi() && ($sertifika->konu_icerigi['tip'] ?? null) === 'genel')
            @php
                $genel = $goster($sertifika->konu_icerigi['genel_konular'] ?? []);
                $saglik = $goster($sertifika->konu_icerigi['saglik_konulari'] ?? []);
                $teknik = $goster($sertifika->konu_icerigi['teknik_konular'] ?? []);
                $ozgu = $sertifika->konu_icerigi['isyerine_ozgu'] ?? null;
                $ozguMaddeler = $ozgu ? $goster($ozgu['maddeler']) : collect();
            @endphp
            <table class="konular">
                <tr>
                    <td>
                        <div class="blok">
                            @php $s = $sure($sertifika->konu_icerigi['genel_konular'] ?? []); @endphp
                            <div class="blok-baslik">1. Genel Konular (Fiili Ders: {{ $s['fiili'] }}dk / Din: {{ $s['dinlenme'] }}dk)</div>
                            @foreach ($genel as $i => $m)
                                <p>{{ $harf($i) }}) {{ $m['madde'] }} ({{ $m['dakika'] }} dk)</p>
                            @endforeach
                        </div>
                        <div class="blok">
                            @php $s = $sure($sertifika->konu_icerigi['saglik_konulari'] ?? []); @endphp
                            <div class="blok-baslik">2. Sağlık Konular (Fiili Ders: {{ $s['fiili'] }}dk / Din: {{ $s['dinlenme'] }}dk)</div>
                            @foreach ($saglik as $i => $m)
                                <p>{{ $harf($i) }}) {{ $m['madde'] }} ({{ $m['dakika'] }} dk)</p>
                            @endforeach
                        </div>
                    </td>
                    <td>
                        <div class="blok">
                            @php $s = $sure($sertifika->konu_icerigi['teknik_konular'] ?? []); @endphp
                            <div class="blok-baslik">3. Teknik Konular (Fiili Ders: {{ $s['fiili'] }}dk / Din: {{ $s['dinlenme'] }}dk)</div>
                            @foreach ($teknik as $i => $m)
                                <p>{{ $harf($i) }}) {{ $m['madde'] }} ({{ $m['dakika'] }} dk)</p>
                            @endforeach
                        </div>
                        @if ($ozgu)
                            <div class="blok">
                                @php $s = $sure($ozgu['maddeler']); @endphp
                                <div class="blok-baslik">4. İşyerine Özgü Riskler (Fiili Ders: {{ $s['fiili'] }}dk / Din: {{ $s['dinlenme'] }}dk)</div>
                                @foreach ($ozguMaddeler as $i => $m)
                                    <p>{{ $harf($i) }}) {{ \App\Support\TurkceMetin::buyuk($m['madde']) }} ({{ $m['dakika'] }} dk)</p>
                                @endforeach
                            </div>
                        @endif
                    </td>
                </tr>
            </table>
        @else
            @php $ozelMaddeler = $goster($sertifika->konu_icerigi['maddeler'] ?? []); @endphp
            <table class="konular">
                <tr>
                    <td>
                        <div class="blok">
                            @foreach ($ozelMaddeler->slice(0, (int) ceil($ozelMaddeler->count() / 2)) as $i => $m)
                                <p>{{ $harf($i) }}) {{ $m['madde'] }} ({{ $m['dakika'] }} dk)</p>
                            @endforeach
                        </div>
                    </td>
                    <td>
                        <div class="blok">
                            @foreach ($ozelMaddeler->slice((int) ceil($ozelMaddeler->count() / 2)) as $i => $m)
                                <p>{{ $harf($i + (int) ceil($ozelMaddeler->count() / 2)) }}) {{ $m['madde'] }} ({{ $m['dakika'] }} dk)</p>
                            @endforeach
                        </div>
                    </td>
                </tr>
            </table>
        @endif

        <table class="imza">
            <tr>
                @if ($sertifika->egitici_igu_dahil)
                    <td>
                        İş Güvenliği Uzmanı<br>
                        Eğitici Adı Soyadı : {{ $sertifika->egitici_igu_adi ?: '—' }}<br>
                        @if ($sertifika->egitici_igu_kase)
                            <img src="{{ storage_path('app/public/'.$sertifika->egitici_igu_kase) }}">
                        @endif
                        İmza :
                    </td>
                @endif
                @if ($sertifika->cokluEgiticiMi() && $sertifika->egitici_hekim_dahil)
                    <td>
                        İşyeri Hekimi<br>
                        Eğitici Adı Soyadı : {{ $sertifika->egitici_hekim_adi ?: '—' }}<br>
                        @if ($sertifika->egitici_hekim_kase)
                            <img src="{{ storage_path('app/public/'.$sertifika->egitici_hekim_kase) }}">
                        @endif
                        İmza :
                    </td>
                @endif
                <td>
                    İşveren / İşveren Vekilinin<br>
                    Adı Soyadı :<br><br>
                    İmza :
                </td>
            </tr>
        </table>

        <p class="not">(1 ders saati: 45 dk ders + 15 dk Dinlenme)</p>
        <p class="tarih-sag">Düzenleme Tarihi : {{ now()->format('d.m.Y') }}</p>

        </div>
    </div>
@empty
    <div class="sayfa">
        <div class="ic">
            <p style="text-align:center;color:#888;margin-top:40%">Katılımcı eklenmedi.</p>
        </div>
    </div>
@endforelse

</body>
</html>
