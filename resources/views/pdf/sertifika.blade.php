<!doctype html>
<html lang="tr">
<head>
<meta charset="utf-8">
<style>
    * { font-family: DejaVu Sans, sans-serif; }
    body { margin: 0; color: #111; font-size: 10.5px; }
    .sayfa { padding: 26px 32px; page-break-after: always; }
    .sayfa:last-child { page-break-after: avoid; }
    .baslik { text-align: center; border-bottom: 3px double #111; padding-bottom: 10px; margin-bottom: 14px; }
    .baslik img { max-height: 46px; float: left; }
    .baslik h1 { font-size: 16px; margin: 0; }
    .bilgi { width: 100%; border-collapse: collapse; font-size: 10px; margin-bottom: 12px; }
    .bilgi td { padding: 3px 6px; vertical-align: top; width: 25%; }
    .bilgi td.etiket { font-weight: bold; width: 20%; }
    .metin { font-size: 10px; text-align: justify; margin-bottom: 10px; }
    h3 { font-size: 10.5px; margin: 10px 0 4px; }
    table.konular { width: 100%; border-collapse: collapse; }
    table.konular > tr > td { width: 50%; vertical-align: top; padding: 0 6px 0 0; }
    .blok { margin-bottom: 8px; }
    .blok-baslik { font-weight: bold; font-size: 9.5px; margin-bottom: 2px; }
    .blok p { margin: 0 0 2px; font-size: 9.5px; }
    table.imza { width: 100%; border-collapse: collapse; margin-top: 24px; }
    table.imza td { width: 33.33%; vertical-align: top; font-size: 9.5px; padding-right: 10px; }
    table.imza img { max-height: 40px; display: block; margin: 4px 0; }
    .not { margin-top: 14px; font-size: 8.5px; color: #666; }
    .tarih-sag { text-align: right; font-size: 8.5px; font-weight: bold; margin-top: 4px; }
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
    <div class="sayfa">

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
            <div class="blok">
                @foreach ($ozelMaddeler as $i => $m)
                    <p>{{ $harf($i) }}) {{ $m['madde'] }} ({{ $m['dakika'] }} dk)</p>
                @endforeach
            </div>
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
@empty
    <div class="sayfa">
        <p style="text-align:center;color:#888;margin-top:40%">Katılımcı eklenmedi.</p>
    </div>
@endforelse

</body>
</html>
