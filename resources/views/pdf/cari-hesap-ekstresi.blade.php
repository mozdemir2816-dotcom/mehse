<!doctype html>
<html lang="tr">
<head>
<meta charset="utf-8">
<title>Cari Hesap Ekstresi — {{ $firma->unvan }}</title>
<style>
    @page { margin: 24px 28px; }
    * { font-family: 'DejaVu Sans', sans-serif; }
    body { margin: 0; color: #111827; font-size: 9px; line-height: 1.4; }
    .ust { border: 1.5px solid #0f766e; background: #f0fdfa; border-radius: 5px; padding: 10px 14px; margin-bottom: 10px; }
    .baslik { font-size: 13px; font-weight: bold; color: #115e59; }
    .alt { font-size: 8.2px; color: #374151; margin-top: 2px; }
    table.ozet { width: 100%; border-collapse: separate; border-spacing: 6px 0; margin: 0 -6px 10px -6px; }
    table.ozet td { border: 1px solid #cbd5e1; border-radius: 4px; padding: 6px 8px; background: #f8fafc; width: 33.33%; }
    .o-lbl { font-size: 7.2px; color: #64748b; letter-spacing: .2px; }
    .o-val { font-size: 11px; font-weight: bold; margin-top: 2px; }
    table.liste { width: 100%; border-collapse: collapse; font-size: 8.5px; }
    table.liste th { background: #f1f5f9; border: 1px solid #cbd5e1; padding: 5px 6px; text-align: left; }
    table.liste td { border: 1px solid #e2e8f0; padding: 4.5px 6px; }
    .sag { text-align: right; }
    .alt-not { margin-top: 14px; border-top: 1px solid #e5e7eb; padding-top: 6px; font-size: 7.5px; color: #6b7280; text-align: center; }
</style>
</head>
<body>
    <div class="ust">
        <div class="baslik">MÜŞTERİ CARİ HESAP EKSTRESİ — İSG HİZMETLERİ</div>
        <div class="alt">
            <strong>Firma:</strong> {{ $firma->unvan }}
            &nbsp;|&nbsp; <strong>Hizmet Veren:</strong> {{ $user->name }}
            &nbsp;|&nbsp; <strong>Ekstre Tarihi:</strong> {{ $tarih }}
        </div>
        @if ($ozet['aylik_ucret'] > 0)
            <div class="alt">
                <strong>Aylık Sözleşme Bedeli:</strong> {{ number_format($ozet['aylik_ucret'], 2, ',', '.') }} TL + %{{ $ozet['kdv'] }} KDV
            </div>
        @endif
    </div>

    <table class="ozet">
        <tr>
            <td>
                <div class="o-lbl">Toplam Tahakkuk (Borç)</div>
                <div class="o-val">{{ number_format($ozet['toplam_borc'], 2, ',', '.') }} TL</div>
            </td>
            <td>
                <div class="o-lbl">Toplam Tahsilat (Ödenen)</div>
                <div class="o-val" style="color:#15803d">{{ number_format($ozet['toplam_tahsilat'], 2, ',', '.') }} TL</div>
            </td>
            <td>
                <div class="o-lbl">Güncel Kalan Bakiye</div>
                <div class="o-val" style="color:{{ $ozet['bakiye'] > 0 ? '#b91c1c' : '#15803d' }}">
                    {{ number_format($ozet['bakiye'], 2, ',', '.') }} TL
                </div>
            </td>
        </tr>
    </table>

    <table class="liste">
        <thead>
            <tr>
                <th style="width:14%">Tarih</th>
                <th style="width:41%">Açıklama</th>
                <th style="width:15%" class="sag">Borç (TL)</th>
                <th style="width:15%" class="sag">Tahsilat (TL)</th>
                <th style="width:15%" class="sag">Bakiye (TL)</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($satirlar as $s)
                <tr>
                    <td>{{ $s['tarih'] }}</td>
                    <td>{{ $s['aciklama'] }}</td>
                    <td class="sag">{{ $s['borc'] > 0 ? number_format($s['borc'], 2, ',', '.') : '—' }}</td>
                    <td class="sag" style="color:#15803d">{{ $s['tahsilat'] > 0 ? number_format($s['tahsilat'], 2, ',', '.') : '—' }}</td>
                    <td class="sag" style="font-weight:bold;color:{{ $s['bakiye'] > 0 ? '#b91c1c' : '#15803d' }}">{{ number_format($s['bakiye'], 2, ',', '.') }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" style="text-align:center;color:#6b7280;padding:14px">Bu firma için henüz cari hareket kaydı bulunmamaktadır.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="alt-not">
        Bu cari hesap ekstresi bilgilendirme amaçlı olarak <strong>mehse.com</strong> İSG yönetim panelinden oluşturulmuştur ({{ $tarih }}).
    </div>
</body>
</html>
