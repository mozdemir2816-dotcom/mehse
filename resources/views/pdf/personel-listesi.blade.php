{{-- Firma personel listesi — A4 yatay. TC maskeli değil (resmi liste); paylaşırken dikkat. --}}
<!doctype html>
<html lang="tr">
<head>
<meta charset="utf-8">
<style>
    * { font-family: "DejaVu Sans", sans-serif; }
    @page { margin: 24px 28px; }
    body { margin: 0; color: #111; font-size: 8.5px; line-height: 1.35; }
    .ust { width: 100%; margin-bottom: 8px; }
    .ust td { vertical-align: top; }
    h1 { font-size: 15px; margin: 0; }
    .tarih { text-align: right; color: #555; font-size: 8px; }
    table { border-collapse: collapse; width: 100%; }
    .kunye td { border: 1px solid #c9d3dc; padding: 3px 6px; }
    .kunye td.e { background: #f3f6f8; color: #0f4c5c; font-weight: bold; width: 13%; }
    .ozet { margin: 10px 0 8px; font-size: 8.5px; }
    .ozet b { color: #0f4c5c; }
    .liste th { background: #0f4c5c; color: #fff; padding: 4px 4px; border: 1px solid #0f4c5c; font-size: 8px; }
    .liste td { border: 1px solid #c9d3dc; padding: 3px 4px; }
    .liste tr:nth-child(even) td { background: #f7f9fa; }
    .c { text-align: center; }
    .bos { text-align: center; color: #777; padding: 10px; }
    .pasif { color: #888; }
</style>
</head>
<body>

<table class="ust">
    <tr>
        <td><h1>{{ $firma->unvan }} — Personel Listesi</h1></td>
        <td class="tarih">Rapor tarihi<br>{{ now()->format('d.m.Y H:i') }}</td>
    </tr>
</table>

<table class="kunye">
    <tr>
        <td class="e">Firma Unvanı</td><td>{{ $firma->unvan }}</td>
        <td class="e">SGK Sicil No</td><td>{{ $firma->sgk_sicil_no ?: '—' }}</td>
    </tr>
    <tr>
        <td class="e">NACE Kodu</td><td>{{ $firma->nace_kodu ?: '—' }}</td>
        <td class="e">Tehlike Sınıfı</td><td>{{ $firma->tehlike_sinifi ? $firma->tehlikeSinifiEtiketi() : '—' }}</td>
    </tr>
    <tr>
        <td class="e">Adres</td><td>{{ $firma->adres ?: '—' }}</td>
        <td class="e">Telefon</td><td>{{ $firma->telefon ?: '—' }}</td>
    </tr>
</table>

<div class="ozet">
    <b>Personel Özeti</b> ({{ $gorunum }}{{ $sube ? ' · Şube: '.$sube : '' }}) &nbsp;
    Toplam: {{ $ozet['toplam'] }} &nbsp;|&nbsp; Kadın: {{ $ozet['kadin'] }} &nbsp;|&nbsp;
    Erkek: {{ $ozet['erkek'] }} &nbsp;|&nbsp; Cinsiyet belirtilmemiş: {{ $ozet['belirtilmemis'] }} &nbsp;|&nbsp;
    Engelli: {{ $ozet['engelli'] }}
</div>

<table class="liste">
    <thead>
        <tr>
            <th style="width:3%">#</th>
            <th style="width:16%">Ad Soyad</th>
            <th style="width:9%">TC Kimlik No</th>
            <th style="width:12%">Görev</th>
            <th style="width:10%">Departman</th>
            <th style="width:9%">Şube</th>
            <th style="width:6%">Cinsiyet</th>
            <th style="width:7%">İşe Giriş</th>
            <th style="width:7%">İşten Çıkış</th>
            <th style="width:6%">Ağır-Teh.</th>
            <th style="width:9%">Özel Durum</th>
            <th style="width:6%">Durum</th>
        </tr>
    </thead>
    <tbody>
        @forelse ($calisanlar as $i => $c)
            <tr @class(['pasif' => ! $c->aktif])>
                <td class="c">{{ $i + 1 }}</td>
                <td>{{ $c->ad_soyad }}</td>
                <td class="c">{{ $c->tc ?: '—' }}</td>
                <td>{{ $c->gorev ?: '—' }}</td>
                <td>{{ $c->departman ?: '—' }}</td>
                <td>{{ $c->sube ?: '—' }}</td>
                <td class="c">{{ $c->cinsiyetEtiketi() ?? '—' }}</td>
                <td class="c">{{ $c->ise_giris?->format('d.m.Y') ?? '—' }}</td>
                <td class="c">{{ $c->isten_cikis?->format('d.m.Y') ?? '—' }}</td>
                <td class="c">{{ $c->agir_tehlikeli_iste ? 'Evet' : '—' }}</td>
                <td>{{ $c->ozel_durum ?: '—' }}</td>
                <td class="c">{{ $c->aktif ? 'Aktif' : 'Pasif' }}</td>
            </tr>
        @empty
            <tr><td colspan="12" class="bos">Kayıt bulunamadı.</td></tr>
        @endforelse
    </tbody>
</table>

</body>
</html>
