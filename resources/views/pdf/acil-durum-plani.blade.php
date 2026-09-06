@php
    $cerceveStil = match ($plan->kapak_cercevesi) {
        'altin' => 'border:3px double #b8860b;',
        'mavi_zarif' => 'border:2px solid #1e3a8a;',
        'minimalist' => 'border:1px solid #111;',
        'yesil_doga' => 'border:3px solid #15803d;',
        'kirmizi_resmi' => 'border:6px solid #b91c1c;',
        'golgeli' => 'border:2px solid #333;box-shadow:4px 4px 0 #ccc;',
        default => 'border:3px double #111;',
    };
@endphp
<!doctype html>
<html lang="tr">
<head>
<meta charset="utf-8">
<style>
    * { font-family: DejaVu Sans, sans-serif; }
    body { margin: 0; color: #111; font-size: 11px; }
    .kapak { {{ $cerceveStil }} margin: 28px; padding: 60px 40px; text-align: center; page-break-after: always; }
    .kapak h1 { font-size: 26px; margin: 40px 0 8px; letter-spacing: 1px; }
    .kapak .firma { font-size: 15px; font-weight: bold; margin-top: 40px; }
    .kunye { margin: 30px auto; width: 80%; border-collapse: collapse; font-size: 11px; }
    .kunye td { border: 1px solid #999; padding: 6px 10px; text-align: left; }
    .kunye td:first-child { background: #f0f0f0; font-weight: bold; width: 40%; }
    .sayfa { padding: 25px 30px; page-break-after: always; }
    h2 { font-size: 15px; border-bottom: 2px solid #b91c1c; padding-bottom: 4px; color: #b91c1c; }
    .adim { margin: 6px 0; padding-left: 6px; }
    .adim b { display: inline-block; width: 22px; }
    table.ekip { width: 100%; border-collapse: collapse; margin-top: 8px; }
    table.ekip th, table.ekip td { border: 1px solid #999; padding: 5px 8px; font-size: 10px; text-align: left; }
    table.ekip th { background: #f0f0f0; }
    .imza { margin-top: 50px; width: 100%; }
    .imza td { width: 33.33%; text-align: center; padding-top: 40px; border-top: 1px solid #111; font-size: 10px; }
    .imza img { max-height: 40px; display: block; margin: 0 auto -32px auto; }
    .not { background: #fff7ed; border: 1px solid #fdba74; padding: 10px; font-size: 10px; margin-top: 20px; }
</style>
</head>
<body>

{{-- KAPAK --}}
@php $uzman = $firma?->user; @endphp
<div class="kapak">
    <div style="font-size:12px;color:#666">{{ $firma?->unvan }}</div>
    <h1>ACİL DURUM EYLEM PLANI</h1>
    <div style="font-size:12px">6331 Sayılı İş Sağlığı ve Güvenliği Kanunu — İşyerlerinde Acil Durumlar Hakkında Yönetmelik</div>
    <div class="firma">{{ $firma?->unvan }}</div>
    <div style="font-size:11px;margin-top:6px">{{ $firma?->adres }}</div>
    <div style="font-size:11px;margin-top:6px">NACE Kodu: {{ $firma?->nace_kodu ?: '—' }} &nbsp;·&nbsp; SGK Sicil No: {{ $firma?->sgk_sicil_no ?: '—' }}</div>
    <div style="font-size:11px;margin-top:30px">
        Doküman No: {{ $plan->dokuman_no }}<br>
        @if ($plan->revizyon_no)
            Rev. Tarihi / No: {{ $plan->revizyon_no }}<br>
        @endif
        Hazırlanma Tarihi: {{ $plan->rapor_tarihi?->format('d.m.Y') }}<br>
        Geçerlilik Tarihi: {{ $plan->gecerlilik_tarihi?->format('d.m.Y') }}
    </div>
    <div style="font-size:11px;margin-top:20px;color:#444">
        Hazırlayan: {{ $uzman?->name ?: '—' }} @if ($uzman?->unvan) ({{ $uzman->unvanEtiketi() }}) @endif
    </div>
</div>

{{-- KÜNYE --}}
<div class="sayfa">
    <h2>1. İŞYERİ KÜNYESİ</h2>
    <table class="kunye">
        <tr><td>Ticari Unvan</td><td>{{ $firma?->unvan }}</td></tr>
        <tr><td>SGK Sicil No</td><td>{{ $firma?->sgk_sicil_no ?: '—' }}</td></tr>
        <tr><td>NACE Kodu</td><td>{{ trim(($firma?->nace_kodu ?: '').' '.($firma?->nace_aciklama ?: '')) ?: '—' }}</td></tr>
        <tr><td>Tehlike Sınıfı</td><td>{{ $firma?->tehlikeSinifiEtiketi() }}</td></tr>
        <tr><td>Çalışan Sayısı</td><td>{{ $firma?->calisan_sayisi ?: '—' }}</td></tr>
        <tr><td>Adres</td><td>{{ $firma?->adres ?: '—' }}</td></tr>
        <tr><td>Toplanma Yeri</td><td>{{ $plan->toplanma_yeri ?: '—' }}</td></tr>
    </table>

    <h2 style="margin-top:24px">2. İŞYERİ İÇİN BELİRLENEN ACİL DURUMLAR</h2>
    <ol style="margin:8px 0 0;padding-left:18px;font-size:11px">
        @forelse ($plan->konuAdlari() as $konuAdi)
            <li>{{ mb_strtoupper($konuAdi, 'UTF-8') }}</li>
        @empty
            <li style="list-style:none;color:#888">Henüz acil durum konusu seçilmedi.</li>
        @endforelse
    </ol>

    @if ($plan->disaridan_etkileyebilecek_isyerleri)
        <h2 style="margin-top:24px">3. İŞYERİNİ DIŞARIDAN ETKİLEYEBİLECEK İŞYERLERİ</h2>
        <p style="font-size:11px;white-space:pre-line">{{ $plan->disaridan_etkileyebilecek_isyerleri }}</p>
    @endif

    <h2 style="margin-top:24px">4. ACİL DURUM DESTEK EKİPLERİ</h2>
    <table class="ekip">
        <tr><th>Ekip</th><th>Görevliler</th></tr>
        @foreach (config('isg.acil_durum.ekipler') as $anahtar => $ad)
            <tr>
                <td>{{ $ad }}</td>
                <td>{{ implode(', ', $plan->ekipListesi()[$anahtar] ?? []) ?: '(atanmadı)' }}</td>
            </tr>
        @endforeach
    </table>
    <p style="font-size:10px;color:#666;margin-top:6px">
        Ekip üyeleri, 6331 SK ve İşyerlerinde Acil Durumlar Yönetmeliği uyarınca tehlike sınıfı ve çalışan
        sayısına göre belirlenir; söndürme ve kurtarma ekiplerinde en az bir kişi eğitimli olmalıdır.
    </p>

    <h2 style="margin-top:24px">5. ACİL DURUMLARDA İRTİBAT KURULACAK KURULUŞLAR VE TELEFONLARI</h2>
    <table class="ekip">
        <tr><th>Kuruluş</th><th>Telefon</th></tr>
        @foreach (config('isg.acil_durum.irtibat_telefonlari') as $kurulus => $telefon)
            <tr><td>{{ $kurulus }}</td><td>{{ $telefon }}</td></tr>
        @endforeach
    </table>
</div>

{{-- TAHLİYE PLANI --}}
<div class="sayfa">
    <h2>6. TAHLİYE PLANI</h2>
    @if ($plan->tahliye_plani_gorseli)
        <img src="{{ public_path('storage/'.$plan->tahliye_plani_gorseli) }}" style="max-width:100%;max-height:620px;margin-top:10px">
    @else
        <p style="font-size:11px">
            Bu sayfaya işyerinin her bölümü için hazırlanan tahliye planı (kaçış yolları, toplanma yeri,
            acil durum ekipmanlarının konumu ve varsa uyarı sistemlerinin yer aldığı kroki) eklenecektir.
            Sayfa başlığında yer alan "Tahliye Planı Görseli" ile yüklenebilir.
        </p>
    @endif
</div>

{{-- KONU SAYFALARI --}}
@php $afisler = config('isg.acil_durum.afisler'); @endphp
@foreach ($plan->konular ?? [] as $konu)
    @php
        $ad = collect(config('isg.acil_durum.konular'))->firstWhere('anahtar', $konu)['ad'] ?? $konu;
        $afis = $afisler[$konu] ?? null;
    @endphp
    <div class="sayfa">
        <h2>ACİL DURUM: {{ mb_strtoupper($ad, 'UTF-8') }}</h2>
        @if ($afis)
            <p style="font-size:10px;color:#555">{{ $afis['baslik'] }}</p>
            @foreach ($afis['adimlar'] as $i => $adim)
                <div class="adim"><b>{{ $i + 1 }}.</b> {{ $adim }}</div>
            @endforeach
        @else
            <p>Bu acil durum için işyerine özgü müdahale adımları, risk değerlendirmesi ve saha koşulları
            dikkate alınarak İSG uzmanı tarafından doldurulacaktır.</p>
        @endif
        <p style="font-size:10px;color:#666;margin-top:14px">
            Genel kural: Can güvenliği önceliklidir. Panik yapmadan en yakın çıkıştan tahliye edilir,
            toplanma bölgesinde sayım yapılır, gerekli hallerde 112 aranır.
        </p>
    </div>
@endforeach

{{-- ONAY --}}
<div class="sayfa" style="page-break-after:auto">
    <h2>ONAY</h2>
    <table class="imza">
        <tr>
            <td>
                @if ($uzman?->kase_gorseli)<img src="{{ storage_path('app/public/'.$uzman->kase_gorseli) }}">@endif
                @if ($uzman?->imza_gorseli)<img src="{{ storage_path('app/public/'.$uzman->imza_gorseli) }}">@endif
                {{ $uzman?->name ?: 'İş Güvenliği Uzmanı' }}
                @if ($uzman?->unvan) <br><span style="font-weight:normal">{{ $uzman->unvanEtiketi() }}</span> @endif
                <br>(İmza – Kaşe)
            </td>
            <td>
                @if ($firma?->isyeriHekimi?->kase_gorseli)<img src="{{ storage_path('app/public/'.$firma->isyeriHekimi->kase_gorseli) }}">@endif
                @if ($firma?->isyeriHekimi?->imza_gorseli)<img src="{{ storage_path('app/public/'.$firma->isyeriHekimi->imza_gorseli) }}">@endif
                {{ $firma?->isyeriHekimi?->ad_soyad ?: 'İşyeri Hekimi' }}
                <br>(İmza – Kaşe)
            </td>
            <td>{{ $firma?->isveren_ad ?: 'İşveren / İşveren Vekili' }}<br>(Ad – Soyad / İmza)</td>
        </tr>
    </table>
    <div class="not">
        {{ $hakkinda }}
    </div>
</div>

</body>
</html>
