<!doctype html>
<html lang="tr">
<head>
<meta charset="utf-8">
<style>
    * { font-family: DejaVu Sans, sans-serif; }
    @page { margin: 28px 32px; }
    body { margin: 0; color: #111827; font-size: 9.5px; line-height: 1.45; }
    .baslik { border-bottom: 2.5px solid #0f4c81; padding-bottom: 8px; margin-bottom: 12px; }
    .rozet { display: inline-block; border: 1.2px solid #0f4c81; color: #0f4c81; font-weight: bold; font-size: 9px; padding: 1px 7px; margin-bottom: 4px; }
    h1 { font-size: 14px; margin: 2px 0; color: #0f172a; }
    .alt { font-size: 8px; color: #475569; margin: 0; }
    .kutu { border: 1px solid #cbd5e1; border-radius: 4px; margin-bottom: 10px; }
    .kutu-baslik { background: #0f4c81; color: #fff; font-weight: bold; font-size: 9.5px; padding: 4px 8px; }
    table.bilgi { width: 100%; border-collapse: collapse; }
    table.bilgi td { border: 1px solid #cbd5e1; padding: 5px 7px; vertical-align: top; }
    table.bilgi td.etk { width: 24%; background: #f8fafc; font-weight: bold; color: #334155; font-size: 8.5px; }
    .kanaat-kutu { border: 1.5px solid #0f4c81; background: #f0f9ff; padding: 10px 12px; margin: 10px 0; }
    .kanaat-madde { margin: 4px 0; font-size: 9.5px; }
    .kvkk { border: 1px solid #cbd5e1; background: #f8fafc; padding: 7px 9px; font-size: 8px; color: #334155; margin-top: 8px; }
    table.imza { width: 100%; border-collapse: collapse; margin-top: 16px; }
    table.imza td { width: 50%; border: 1px solid #94a3b8; padding: 8px 10px; vertical-align: top; height: 110px; }
    .imza-baslik { font-weight: bold; font-size: 9px; color: #0f172a; border-bottom: 1px solid #e2e8f0; padding-bottom: 3px; margin-bottom: 5px; }
    .kase-alan { height: 52px; text-align: center; }
    .kase-alan img { max-height: 50px; max-width: 140px; }
</style>
</head>
<body>
@php
    $en = ($dil ?? 'tr') === 'tr_en';
    $e = \App\Models\MuayeneFormu::ek2Birlestir($form->ek2);
    $tr = fn ($t) => filled($t) ? \Illuminate\Support\Carbon::parse($t)->format('d.m.Y') : '—';
    $isaret = fn (bool $secili) => $secili ? '☒' : '☐';
    $gorev = $e['kanaat']['gorev'] ?: ($e['yaptigi_is'] ?: ($form->calisan_gorevi ?: '—'));
    $turAd = match ($form->muayene_turu) {
        'ise_giris' => $en ? 'İşe Giriş Muayenesi / Pre-Employment Exam' : 'İşe Giriş Muayenesi',
        'periyodik' => $en ? 'Periyodik Muayene / Periodic Exam' : 'Periyodik Muayene',
        default => $en ? 'İşe Dönüş / Ek Muayene / Return-to-Work Exam' : 'İşe Dönüş / Ek Muayene',
    };
    $periyotYil = match ($firma?->tehlike_sinifi) {
        'cok_tehlikeli' => 1,
        'tehlikeli' => 3,
        default => 5,
    };
    $sonrakiTarih = $form->muayene_tarihi
        ? \Illuminate\Support\Carbon::parse($form->muayene_tarihi)->addYears($periyotYil)->format('d.m.Y')
        : '—';
    $kanaat = $e['kanaat'] ?? [];
    $elverisli = (bool) ($kanaat['elverisli'] ?? ($form->sonuc === 'uygun'));
    $sartli = (bool) ($kanaat['sartli'] ?? ($form->sonuc === 'kosullu'));
    $gece = (bool) ($kanaat['gece'] ?? false);
    $yuksekte = (bool) ($kanaat['yuksekte'] ?? false);
    $kaseYol = $firma?->isyeriHekimi?->kase_gorseli;
    $kaseDosya = $kaseYol
        ? (is_file(storage_path('app/public/'.$kaseYol))
            ? storage_path('app/public/'.$kaseYol)
            : (\Illuminate\Support\Facades\Storage::disk('public')->exists($kaseYol) ? \Illuminate\Support\Facades\Storage::disk('public')->path($kaseYol) : null))
        : null;
@endphp

<div class="baslik">
    <span class="rozet">EK-2 {{ $en ? 'HEKİM KANAAT BELGESİ / PHYSICIAN FITNESS CERTIFICATE' : 'İŞYERİ HEKİMİ KANAAT BELGESİ (İŞVEREN NÜSHASI)' }}</span>
    <h1>{{ $en ? 'İŞE UYGUNLUK / HEKİM KANAAT RAPORU — MEDICAL FITNESS OPINION' : 'ÇALIŞAN İŞE UYGUNLUK / HEKİM KANAAT BELGESİ' }}</h1>
    <p class="alt">
        6331 Sayılı İSG Kanunu m.15 &amp; İşyeri Hekimi ve DSP Yönetmeliği · Belge No: <strong>{{ $form->belge_no ?: '—' }}</strong>
        &nbsp;·&nbsp; {{ $en ? 'Muayene Tarihi / Exam Date' : 'Muayene Tarihi' }}: <strong>{{ $tr($form->muayene_tarihi) }}</strong>
    </p>
</div>

<div class="kutu">
    <div class="kutu-baslik">1. {{ $en ? 'İŞYERİ VE ÇALIŞAN BİLGİLERİ / WORKPLACE & EMPLOYEE INFORMATION' : 'İŞYERİ VE ÇALIŞAN BİLGİLERİ' }}</div>
    <table class="bilgi">
        <tr>
            <td class="etk">{{ $en ? 'İşyeri Unvanı / Company' : 'İşyeri Unvanı' }}</td>
            <td colspan="3"><strong>{{ $firma?->unvan ?: '—' }}</strong></td>
        </tr>
        <tr>
            <td class="etk">{{ $en ? 'SGK Sicil No / SSI No' : 'SGK Sicil No' }}</td>
            <td>{{ $firma?->sgk_sicil_no ?: '—' }}</td>
            <td class="etk">{{ $en ? 'Tehlike Sınıfı / Hazard Class' : 'Tehlike Sınıfı' }}</td>
            <td>{{ $firma?->tehlikeSinifiEtiketi() ?: '—' }}</td>
        </tr>
        <tr>
            <td class="etk">{{ $en ? 'Çalışan Adı Soyadı / Full Name' : 'Çalışan Adı Soyadı' }}</td>
            <td><strong>{{ $form->calisan_ad_soyad ?: '—' }}</strong></td>
            <td class="etk">{{ $en ? 'T.C. Kimlik No / ID No' : 'T.C. Kimlik No' }}</td>
            <td>{{ $form->calisan_tc ?: '—' }}</td>
        </tr>
        <tr>
            <td class="etk">{{ $en ? 'Yaptığı İş / Job Title' : 'Yaptığı İş / Görevi' }}</td>
            <td>{{ $gorev }}</td>
            <td class="etk">{{ $en ? 'Muayene Türü / Exam Type' : 'Muayene Türü' }}</td>
            <td>{{ $turAd }}</td>
        </tr>
        <tr>
            <td class="etk">{{ $en ? 'Muayene Tarihi / Date' : 'Muayene Tarihi' }}</td>
            <td>{{ $tr($form->muayene_tarihi) }}</td>
            <td class="etk">{{ $en ? 'Sonraki Kontrol / Next Exam' : 'Sonraki Periyodik Muayene' }}</td>
            <td>{{ $sonrakiTarih }} ({{ $periyotYil }} {{ $en ? 'yıl / yr' : 'yıl' }})</td>
        </tr>
    </table>
</div>

<div class="kanaat-kutu">
    <div style="font-weight:bold;font-size:10px;color:#0f4c81;margin-bottom:6px">
        2. {{ $en ? 'İŞYERİ HEKİMİ KANAATİ VE SONUÇ / PHYSICIAN OPINION & CONCLUSION' : 'İŞYERİ HEKİMİ KANAATİ VE SONUÇ' }}
    </div>
    <div class="kanaat-madde">
        {{ $isaret($elverisli) }} <strong>{{ $gorev }}</strong> {{ $en ? 'işinde çalışmaya ELVERİŞLİDİR. / FIT to work in the stated job.' : 'işinde çalışmaya ELVERİŞLİDİR.' }}
    </div>
    <div class="kanaat-madde">
        {{ $isaret($sartli) }} <strong>{{ $gorev }}</strong> {{ $en ? 'işinde KOŞULLU OLARAK çalışmaya elverişlidir. / FIT WITH CONDITIONS.' : 'işinde KOŞULLU OLARAK çalışmaya elverişlidir.' }}
        @if (! empty($kanaat['sart_aciklama']))
            <div style="margin:3px 0 0 18px;color:#b45309"><strong>{{ $en ? 'Koşul / Condition' : 'Koşul' }}:</strong> {{ $kanaat['sart_aciklama'] }}</div>
        @endif
    </div>
    <div class="kanaat-madde" style="margin-top:8px">
        {{ $isaret($gece) }} {{ $en ? 'Gece ve vardiyalı çalışabilir / Fit for night & shift work' : 'Gece ve vardiyalı çalışabilir' }}
        &nbsp;&nbsp;&nbsp;&nbsp;
        {{ $isaret($yuksekte) }} {{ $en ? 'Yüksekte çalışabilir / Fit for working at height' : 'Yüksekte çalışabilir' }}
    </div>
    @if (filled($form->notlar))
        <div style="margin-top:7px;padding-top:5px;border-top:1px dashed #94a3b8;font-size:8.5px">
            <strong>{{ $en ? 'Hekim Notu (İdari) / Administrative Note' : 'Hekim İdari Notu' }}:</strong> {{ $form->notlar }}
        </div>
    @endif
</div>

<div class="kvkk">
    <strong>6331 s. İSG Kanunu m.15/5 &amp; 6698 s. KVKK Gizlilik Şerhi{{ $en ? ' / Medical Confidentiality Notice' : '' }}:</strong>
    Çalışanın tıbbi anamnez, fizik muayene ve laboratuvar bulguları özel nitelikli kişisel sağlık verisi olup İşyeri Hekimi tarafından gizli sağlık dosyasında muhafaza edilir. Bu belge yalnızca işverene sunulacak işe uygunluk kanaatini içerir.
</div>

<table class="imza">
    <tr>
        <td>
            <div class="imza-baslik">{{ $en ? 'İŞYERİ HEKİMİ / OCCUPATIONAL PHYSICIAN' : 'İŞYERİ HEKİMİ' }}</div>
            <div>{{ $form->hekim_adi ?: ($firma?->isyeriHekimi?->ad_soyad ?: '—') }}</div>
            @if ($firma?->isyeriHekimi?->sertifika_no)
                <div style="font-size:7.5px;color:#475569">Belge No: {{ $firma->isyeriHekimi->sertifika_no }}</div>
            @endif
            <div class="kase-alan">
                @if (($imzali ?? true) && $kaseDosya)
                    <img src="{{ $kaseDosya }}" alt="Hekim Kaşe">
                @endif
            </div>
            <div style="text-align:center;font-size:7.5px;color:#475569">Kaşe / İmza</div>
        </td>
        <td>
            <div class="imza-baslik">{{ $en ? 'İŞVEREN / İŞVEREN VEKİLİ TEBELLÜĞ / EMPLOYER ACKNOWLEDGEMENT' : 'İŞVEREN / İŞVEREN VEKİLİ TEBELLÜĞ' }}</div>
            <div>{{ $firma?->isveren_vekili ?: ($firma?->isveren_ad ?: '—') }}</div>
            <div class="kase-alan"></div>
            <div style="text-align:center;font-size:7.5px;color:#475569">{{ $en ? 'Tarih — İmza / Date — Signature' : 'Teslim Alma Tarihi — İmza' }}</div>
        </td>
    </tr>
</table>
</body>
</html>
