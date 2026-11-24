<!doctype html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: DejaVu Sans, sans-serif; margin: 24px; color: #0f172a; }
        .cerceve { border: 6px double #1e3a8a; padding: 28px 34px; text-align: center; }
        h1 { font-size: 24px; color: #1e3a8a; margin: 0 0 6px; letter-spacing: 1px; }
        .alt { font-size: 12px; color: #475569; margin-bottom: 18px; }
        .isim { font-size: 22px; font-weight: bold; margin: 12px 0; color: #0f172a; }
        .detay { font-size: 12px; line-height: 1.6; max-width: 620px; margin: 0 auto 20px; }
        table { width: 100%; margin-top: 22px; border-collapse: collapse; }
        td { width: 33.3%; text-align: center; vertical-align: middle; font-size: 11px; }
    </style>
</head>
<body>
<div class="cerceve">
    <h1>KATILIM VE BAŞARI SERTİFİKASI</h1>
    <div class="alt">6331 Sayılı İş Sağlığı ve Güvenliği Kanunu · Çalışanların İSG Eğitimlerinin Usul ve Esasları Hakkında Yönetmelik (Ek-2)</div>

    <div class="isim">{{ \App\Support\TurkceMetin::buyuk($sonuc['ad_soyad'] ?? '') }}</div>
    @if (! empty($sonuc['tc']) || ! empty($sonuc['gorev']))
        <div style="font-size:10.5px;color:#334155;margin:-6px 0 12px">
            @if (! empty($sonuc['tc']))T.C. Kimlik No: <strong>{{ $sonuc['tc'] }}</strong>@endif
            @if (! empty($sonuc['tc']) && ! empty($sonuc['gorev'])) &nbsp;·&nbsp; @endif
            @if (! empty($sonuc['gorev']))Görevi: <strong>{{ $sonuc['gorev'] }}</strong>@endif
        </div>
    @endif

    @php
        $gecerlilikYil = match ($oturum->firma?->tehlike_sinifi) {
            'cok_tehlikeli' => 1,
            'tehlikeli' => 2,
            default => 3,
        };
        $gecerlilik = $oturum->baslangic_at
            ? $oturum->baslangic_at->copy()->addYears($gecerlilikYil)->format('d.m.Y')
            : now()->addYears($gecerlilikYil)->format('d.m.Y');
    @endphp

    <div class="detay">
        <strong>{{ $oturum->firma?->unvan }}</strong> işyerinde
        <strong>{{ $oturum->baslangic_at?->format('d.m.Y') ?: ($sonuc['tarih'] ?? now()->format('d.m.Y')) }}</strong> tarihinde düzenlenen
        <strong>{{ $oturum->baslik }}</strong> ({{ $oturum->kategoriEtiketi() }} — {{ $oturum->sure_saat }} Ders Saati)
        eğitim programına katılmış ve değerlendirme sınavından <strong>{{ $sonuc['puan'] }} / 100</strong> puan alarak
        bu eğitim belgesini almaya hak kazanmıştır.
        <br><span style="font-size:10px;color:#475569">Geçerlilik Tarihi: <strong>{{ $gecerlilik }}</strong> · Belge Kodu: <strong>{{ $sonuc['kod'] }}</strong></span>
    </div>

    <table>
        <tr>
            <td>
                <strong>Eğitici / İSG Uzmanı</strong><br>
                {{ $oturum->firma?->igu?->ad_soyad ?: ($oturum->user?->name ?? '—') }}<br>
                @if ($oturum->firma?->igu?->sertifika_no)<span style="font-size:9px;color:#475569">Belge No: {{ $oturum->firma->igu->sertifika_no }}</span><br>@endif
                <span style="font-size:9px;color:#475569">(İmza / Kaşe)</span>
            </td>
            <td>
                <img src="{{ $qrUri }}" style="width:86px;height:86px" alt="QR"><br>
                <span style="font-size:8.5px;color:#475569">Düzenleme: {{ $sonuc['tarih'] }}</span>
            </td>
            <td>
                <strong>İşveren / İşveren Vekili</strong><br>
                {{ $oturum->firma?->isveren_vekili ?: ($oturum->firma?->isveren_ad ?: '........................') }}<br>
                <span style="font-size:9px;color:#475569">(İmza / Kaşe)</span>
            </td>
        </tr>
    </table>
</div>
</body>
</html>
