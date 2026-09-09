<!doctype html>
<html lang="tr">
<head>
<meta charset="utf-8">
<style>
    * { font-family: DejaVu Sans, sans-serif; }
    body { margin: 0; color: #111; font-size: 11px; }
    .sayfa { padding: 28px 34px; }
    .baslik { text-align: center; border-bottom: 3px double #111; padding-bottom: 10px; margin-bottom: 14px; }
    .baslik h1 { font-size: 16px; margin: 0 0 4px; }
    .kunye { width: 100%; border-collapse: collapse; font-size: 10px; margin-bottom: 12px; }
    .kunye td { border: 1px solid #999; padding: 5px 8px; }
    .kunye td.k { background: #f0f0f0; font-weight: bold; width: 22%; }
    h2 { font-size: 12px; margin: 14px 0 6px; padding: 4px 8px; color: #fff; border-radius: 3px; }
    .turler span { display: inline-block; background: #f59e0b; color: #fff; padding: 2px 8px; border-radius: 3px; font-size: 9.5px; margin: 0 4px 4px 0; }
    table.liste { width: 100%; border-collapse: collapse; font-size: 9.5px; margin-bottom: 10px; }
    table.liste td { border: 1px solid #999; padding: 4px 8px; }
    .uyari { border: 1px solid #dc2626; background: #fdeaea; color: #991b1b; font-size: 9.5px; padding: 6px 8px; border-radius: 3px; margin-bottom: 4px; }
    .onay { width: 100%; border-collapse: collapse; font-size: 9.5px; margin-top: 8px; }
    .onay td, .onay th { border: 1px solid #999; padding: 5px 8px; text-align: left; }
    .onay th { background: #f0f0f0; }
    .imza { margin-top: 26px; width: 100%; }
    .imza td { width: 50%; text-align: center; padding-top: 38px; border-top: 1px solid #111; font-size: 10px; }
    .yasal { margin-top: 14px; font-size: 8.5px; color: #666; }
    .damga { display: inline-block; padding: 3px 10px; border: 2px solid #111; border-radius: 4px; font-weight: bold; font-size: 11px; }
</style>
</head>
<body>
<div class="sayfa">

    <div class="baslik">
        <h1>İŞ İZİN FORMU (PERMIT TO WORK)</h1>
        <div style="font-size:11px">{{ $firma?->unvan }} — İzin No: {{ $form->izin_no }}</div>
        <div style="margin-top:6px">
            <span class="damga">DURUM — {{ $form->durumEtiketi() }}</span>
        </div>
    </div>

    <table class="kunye">
        <tr>
            <td class="k">Geçerlilik</td><td>{{ $form->gecerlilik_saat ? $form->gecerlilik_saat.' saat' : '—' }}</td>
            <td class="k">Kütüphane Şablonu</td><td>{{ $form->sablon_kaynak ?: '—' }}</td>
        </tr>
        <tr><td class="k">Çalışma Alanı / Lokasyon</td><td colspan="3">{{ $form->calisma_alani ?: '—' }}</td></tr>
        <tr><td class="k">Yapılacak İşin Detayı</td><td colspan="3">{{ $form->is_detayi ?: '—' }}</td></tr>
        <tr>
            <td class="k">Başlangıç</td><td>{{ $form->baslangic?->format('d.m.Y H:i') ?: '—' }}</td>
            <td class="k">Bitiş</td><td>{{ $form->bitis?->format('d.m.Y H:i') ?: '—' }}</td>
        </tr>
    </table>

    <h2 style="background:#f59e0b">İZİN TÜRÜ</h2>
    <div class="turler">
        @forelse ($form->izinTurEtiketleri() as $t)
            <span>{{ $t }}</span>
        @empty
            <span style="background:#6b7280">Belirtilmedi</span>
        @endforelse
    </div>

    <h2 style="background:#10b981">GÜVENLİK ÖNLEMLERİ KONTROL LİSTESİ</h2>
    <table class="liste">
        @forelse (($form->guvenlik_onlemleri ?? []) as $onlem)
            <tr><td>☑ {{ $onlem }}</td></tr>
        @empty
            <tr><td style="color:#888">İşaretlenen önlem yok.</td></tr>
        @endforelse
    </table>

    <h2 style="background:#8b5cf6">GEREKLİ KİŞİSEL KORUYUCU DONANIMLAR</h2>
    <div class="turler">
        @forelse (($form->gerekli_kkdler ?? []) as $kkd)
            <span style="background:#8b5cf6">{{ $kkd }}</span>
        @empty
            <span style="background:#6b7280">Belirtilmedi</span>
        @endforelse
    </div>

    @if ($form->uyarilar || $form->ozel_kosullar)
        <h2 style="background:#dc2626">UYARILAR VE ÖZEL KOŞULLAR</h2>
        @foreach (($form->uyarilar ?? []) as $u)
            <div class="uyari">⚠ {{ $u }}</div>
        @endforeach
        @if ($form->ozel_kosullar)
            <p style="font-size:9.5px;margin:6px 0 0">{{ $form->ozel_kosullar }}</p>
        @endif
    @endif

    <h2 style="background:#374151">ONAY DURUMU</h2>
    <table class="onay">
        <tr><th style="width:28%">Onaycı</th><th style="width:26%">Ad Soyad</th><th style="width:22%">Durum</th><th style="width:24%">Tarih</th></tr>
        <tr>
            <td>{{ $form->onay1_baslik ?: 'Saha Sorumlusu / Formen' }}</td>
            <td>{{ $form->onay1_ad ?: '—' }}</td>
            <td>{{ $form->onay1Etiketi() }}</td>
            <td>{{ $form->onay1_tarih?->format('d.m.Y H:i') ?: '—' }}</td>
        </tr>
        <tr>
            <td>{{ $form->onay2_baslik ?: 'İSG Uzmanı / İşveren Vekili' }}</td>
            <td>{{ $form->onay2_ad ?: '—' }}</td>
            <td>{{ $form->onay2Etiketi() }}</td>
            <td>{{ $form->onay2_tarih?->format('d.m.Y H:i') ?: '—' }}</td>
        </tr>
        @if ($form->red_gerekcesi)
            <tr><td colspan="4" style="color:#991b1b">Ret Gerekçesi: {{ $form->red_gerekcesi }}</td></tr>
        @endif
    </table>

    @if (in_array($form->durum, ['is_tamamlandi', 'kapatildi']))
        <h2 style="background:#6b7280">İŞ TAMAMLAMA / SAHA TESLİM</h2>
        <table class="onay">
            <tr>
                <td class="k" style="background:#f0f0f0;font-weight:bold;width:28%">İş Bitiş Tarihi</td>
                <td>{{ $form->is_bitis_tarihi?->format('d.m.Y H:i') ?: '—' }}</td>
                <td class="k" style="background:#f0f0f0;font-weight:bold;width:28%">Saha Teslim Alındı</td>
                <td>{{ $form->saha_teslim_alindi ? 'Evet' : 'Hayır' }}</td>
            </tr>
            @if ($form->kapanis_notu)
                <tr><td colspan="4">Kapanış Notu: {{ $form->kapanis_notu }}</td></tr>
            @endif
            @if ($form->kapatan)
                <tr><td colspan="4">Kapatan: {{ $form->kapatan }}</td></tr>
            @endif
        </table>
    @endif

    <table class="imza">
        <tr>
            <td>{{ $form->onay1_baslik ?: 'Saha Sorumlusu / Formen' }}<br>{{ $form->onay1_ad ?: '—' }}<br>(İmza)</td>
            <td>{{ $form->onay2_baslik ?: 'İSG Uzmanı / İşveren Vekili' }}<br>{{ $form->onay2_ad ?: '—' }}<br>(İmza)</td>
        </tr>
    </table>

    <p class="yasal">
        İş İzin Formu (Permit to Work); sıcak iş, yüksekte çalışma, kapalı alan, elektrik, kazı ve
        kaldırma gibi yüksek riskli faaliyetlerde güvenlik önlemlerini, gerekli KKD donanımlarını,
        yetkili onaylarını ve iş tamamlama/saha teslim kaydını içeren iş güvenliği dokümanıdır.
    </p>

</div>
</body>
</html>
