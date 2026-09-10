{{-- Yangın Güvenliği Genel Durum Değerlendirme Raporu — Binaların Yangından
     Korunması Hakkında Yönetmelik. A4 dikey. --}}
<!doctype html>
<html lang="tr">
<head>
<meta charset="utf-8">
<style>
    * { font-family: "DejaVu Sans", sans-serif; }
    @page { margin: 28px 32px; }
    body { margin: 0; color: #111; font-size: 9.5px; line-height: 1.4; }
    .baslik { text-align: center; border-bottom: 3px double #b91c1c; padding-bottom: 8px; margin-bottom: 12px; }
    .baslik h1 { font-size: 14px; margin: 0 0 3px; color: #b91c1c; }
    .baslik .firma { font-size: 10px; }
    h2 { font-size: 10.5px; margin: 13px 0 5px; color: #b91c1c; border-bottom: 1px solid #ddd; padding-bottom: 2px; }
    table.k { width: 100%; border-collapse: collapse; font-size: 9px; margin-bottom: 4px; }
    table.k td { border: 1px solid #999; padding: 4px 7px; }
    table.k td.e { background: #f0f0f0; font-weight: bold; width: 30%; }
    .sinif { display: inline-block; border-radius: 4px; padding: 4px 12px; font-weight: bold; color: #fff; font-size: 11px; }
    .s-dusuk { background: #16a34a; } .s-orta { background: #d97706; } .s-yuksek { background: #dc2626; }
    ul.liste { margin: 4px 0 6px; padding-left: 16px; font-size: 9px; }
    ul.liste li { margin-bottom: 1px; }
    table.tespit { width: 100%; border-collapse: collapse; font-size: 9px; }
    table.tespit th, table.tespit td { border: 1px solid #999; padding: 3px 6px; text-align: left; }
    table.tespit th { background: #b91c1c; color: #fff; }
    .o-yuksek { color: #b91c1c; font-weight: bold; }
    .o-orta { color: #b45309; font-weight: bold; }
    .yasal { margin-top: 12px; font-size: 8px; color: #666; }
    .imza { margin-top: 30px; width: 100%; border-collapse: collapse; }
    .imza td { width: 33%; text-align: center; font-size: 9px; padding-top: 40px; border-top: 1px solid #111; }
</style>
</head>
<body>

<div class="baslik">
    <h1>YANGIN GÜVENLİĞİ GENEL DURUM DEĞERLENDİRME RAPORU</h1>
    <div class="firma">{{ $firma?->unvan }}</div>
</div>

<h2>1. BİNA VE KULLANIM BİLGİLERİ</h2>
<table class="k">
    <tr><td class="e">İnceleme Yeri</td><td>{{ $kayit->inceleme_yeri ?: '—' }}</td></tr>
    <tr><td class="e">Yapı Durumu</td><td>{{ $kayit->yapi_durumu ?: '—' }}</td></tr>
    <tr><td class="e">İşletme / Kullanım Türü</td><td>{{ $kayit->kullanimTuruEtiketi() }}</td></tr>
    <tr><td class="e">Bina Taban / Yerleşim Alanı</td><td>{{ $kayit->taban_alani_m2 ? number_format((float) $kayit->taban_alani_m2, 2, ',', '.').' m²' : '—' }}</td></tr>
    <tr><td class="e">Zemin Üstü Kat Sayısı</td><td>{{ $kayit->kat_sayisi ?? '—' }}</td></tr>
    <tr><td class="e">Azami Toplam Kullanıcı Yükü</td><td>{{ $kayit->kullanici_yuku ? number_format($kayit->kullanici_yuku, 0, ',', '.').' kişi' : '—' }}</td></tr>
    @if ($kayit->kullanim_aciklamasi)
        <tr><td class="e">Kullanım Açıklaması</td><td>{{ $kayit->kullanim_aciklamasi }}</td></tr>
    @endif
    @php
        $ozel = collect($kayit->ozel_kullanimlar ?? [])->filter()->keys()
            ->map(fn ($k) => ['kapali_otopark' => 'Kapalı otopark', 'bodrum' => 'Bodrum kat', 'konaklama' => 'Konaklama/yataklı bölüm', 'toplanti' => 'Toplantı/eğlence alanı', 'kazan_dairesi' => 'Kazan dairesi'][$k] ?? $k);
    @endphp
    @if ($ozel->isNotEmpty())
        <tr><td class="e">Özel Kullanım</td><td>{{ $ozel->implode(' · ') }}</td></tr>
    @endif
</table>

<div style="margin:8px 0 4px">
    <strong>BELİRLENEN BİNA YANGIN TEHLİKE SINIFI:</strong>
    <span class="sinif s-{{ $kayit->belirlenen_tehlike_sinifi }}">{{ $kayit->tehlikeSinifiEtiketi() }}</span>
    @if ($kayit->sinif_elle) <span style="font-size:8px;color:#666">(elle belirlendi)</span> @endif
</div>

@if ($kayit->bolumler)
    <h2>2. İŞLETMEDE BULUNAN BÖLÜMLER</h2>
    <ul class="liste">
        @foreach ($kayit->bolumler as $b) <li>{{ $b }}</li> @endforeach
    </ul>
@endif

@if ($kayit->riskler)
    <h2>3. FAALİYET / DEPOLAMA RİSKLERİ</h2>
    <ul class="liste">
        @foreach ($kayit->riskler as $r) <li>{{ $r }}</li> @endforeach
    </ul>
@endif

<h2>4. TESPİT VE ÖNERİLER</h2>
<table class="tespit">
    <tr><th style="width:22px">#</th><th>Tespit / Öneri</th><th style="width:60px">Öncelik</th></tr>
    @forelse ($kayit->tespitler ?? [] as $i => $t)
        <tr>
            <td>{{ $i + 1 }}</td>
            <td>{{ $t['madde'] ?? '' }}</td>
            <td class="o-{{ $t['oncelik'] ?? 'orta' }}">{{ ($t['oncelik'] ?? 'orta') === 'yuksek' ? 'Yüksek' : 'Orta' }}</td>
        </tr>
    @empty
        <tr><td colspan="3" style="color:#888">Tespit girilmedi.</td></tr>
    @endforelse
</table>

@if ($kayit->genel_not)
    <p style="margin-top:8px"><strong>Genel Değerlendirme:</strong> {{ $kayit->genel_not }}</p>
@endif

<p class="yasal">
    Bu rapor, Binaların Yangından Korunması Hakkında Yönetmelik ve 6331 Sayılı İş Sağlığı ve
    Güvenliği Kanunu kapsamında ön değerlendirme amaçlıdır; resmî yangın güvenlik raporu / proje
    onayı yerine geçmez. Yüksek öncelikli tespitler risk değerlendirmesi ve yıllık çalışma planına
    işlenmelidir. Rapor tarihi: {{ $kayit->degerlendirme_tarihi?->format('d.m.Y') ?? now()->format('d.m.Y') }}
</p>

<table class="imza">
    <tr>
        <td>İş Güvenliği Uzmanı</td>
        <td>İşyeri Hekimi</td>
        <td>İşveren / İşveren Vekili</td>
    </tr>
</table>

</body>
</html>
