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
    .kunye td:first-child { background: #f0f0f0; font-weight: bold; width: 22%; }
    h2 { font-size: 12px; margin: 14px 0 6px; padding: 4px 8px; color: #fff; border-radius: 3px; }
    .turler span { display: inline-block; background: #f59e0b; color: #fff; padding: 2px 8px; border-radius: 3px; font-size: 9.5px; margin: 0 4px 4px 0; }
    table.liste { width: 100%; border-collapse: collapse; font-size: 9.5px; margin-bottom: 10px; }
    table.liste td { border: 1px solid #999; padding: 4px 8px; }
    .imza { margin-top: 30px; width: 100%; }
    .imza td { width: 50%; text-align: center; padding-top: 40px; border-top: 1px solid #111; font-size: 10px; }
    .yasal { margin-top: 14px; font-size: 8.5px; color: #666; }
</style>
</head>
<body>
<div class="sayfa">

    <div class="baslik">
        <h1>İŞ İZİN FORMU (PERMIT TO WORK)</h1>
        <div style="font-size:11px">{{ $firma?->unvan }} — İzin No: {{ $form->izin_no }}</div>
    </div>

    <table class="kunye">
        <tr><td>Çalışma Alanı / Lokasyon</td><td colspan="3">{{ $form->calisma_alani ?: '—' }}</td></tr>
        <tr><td>Yapılacak İşin Detayı</td><td colspan="3">{{ $form->is_detayi ?: '—' }}</td></tr>
        <tr>
            <td>Başlangıç</td><td>{{ $form->baslangic?->format('d.m.Y H:i') ?: '—' }}</td>
            <td>Bitiş</td><td>{{ $form->bitis?->format('d.m.Y H:i') ?: '—' }}</td>
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

    <table class="imza">
        <tr>
            <td>{{ $form->onay1_baslik ?: 'Formen / Mühendis / Şef' }}<br>{{ $form->onay1_ad ?: '—' }}<br>(İmza)</td>
            <td>{{ $form->onay2_baslik ?: 'İSG Uzmanı / Amir / Müdür' }}<br>{{ $form->onay2_ad ?: '—' }}<br>(İmza)</td>
        </tr>
    </table>

    <p class="yasal">
        İş İzin Formu (Permit to Work); sıcak iş, yüksekte çalışma, kapalı alan ve elektrik gibi yüksek
        riskli faaliyetlerde güvenlik önlemlerini, gerekli KKD donanımlarını ve yetkili onaylarını
        kayıt altına alan iş güvenliği dokümanıdır.
    </p>

</div>
</body>
</html>
