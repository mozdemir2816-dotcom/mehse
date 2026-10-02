<!doctype html>
<html lang="tr">
<head>
<meta charset="utf-8">
<style>
    * { font-family: "DejaVu Sans", sans-serif; }
    @page { margin: 40px 34px 50px; }
    body { margin: 0; color: #1f2937; font-size: 9px; }
    h1 { font-size: 26px; color: #1e3a8a; text-align: center; margin: 4px 0; }
    h2 { font-size: 14px; color: #1e3a8a; margin: 16px 0 6px; }
    .marka { text-align: center; color: #0f766e; font-weight: bold; font-size: 11px; letter-spacing: 1px; margin-top: 150px; }
    .firma { text-align: center; font-size: 17px; font-weight: bold; margin-bottom: 30px; }
    table { width: 100%; border-collapse: collapse; }
    th { background: #1e3a8a; color: #fff; text-align: left; padding: 4px 5px; font-size: 8.5px; }
    td { border: 1px solid #d1d5db; padding: 4px 5px; vertical-align: top; }
    .kv th { background: #f3f4f6; color: #374151; width: 18%; border: 1px solid #d1d5db; }
    .kpi td { text-align: center; font-size: 16px; font-weight: bold; color: #0f766e; }
    .kpi th { text-align: center; background: #ecfdf5; color: #374151; }
    .not { font-size: 8px; color: #4b5563; margin-top: 10px; line-height: 1.5; }
    .sayfa-sonu { page-break-after: always; }
    .d-tamamlandi { color: #15803d; font-weight: bold; } .d-eksik, .d-gecikmis { color: #b91c1c; font-weight: bold; }
    .d-yaklasan { color: #b45309; font-weight: bold; } .d-bilgi { color: #1d4ed8; font-weight: bold; }
</style>
</head>
<body>
@php
    use App\Support\IsyeriDurumu;
    $osgb = $uzman?->name;
@endphp

{{-- Kapak --}}
<div class="marka">MEHSE · İŞ SAĞLIĞI VE GÜVENLİĞİ</div>
<h1>TAM FİRMA DOSYASI</h1>
<div class="firma">{{ $firma->unvan }}</div>
<table>
    <tr><th>Rapor tarihi</th><td>{{ now()->format('d.m.Y') }}</td><th>Genel durum</th><td>{{ $ozet['genel'] }}</td></tr>
    <tr><th>Tamamlanma</th><td>%{{ $ozet['yuzde'] }}</td><th>Rapor kapsamı</th><td>Operasyonel görünüm</td></tr>
</table>
<p class="not">Bu rapor mevcut uygulama kayıtlarının rapor tarihi itibarıyla salt okunur bir fotoğrafıdır. Sağlık bilgileri kişi, tanı ve hekim notu içermeyen toplu göstergeler olarak sunulmuştur.</p>
<div class="sayfa-sonu"></div>

<h2>Yönetici Özeti</h2>
<table class="kpi">
    <tr><th>Tamamlanan</th><th>Eksik</th><th>Gecikmiş</th><th>Yaklaşan</th></tr>
    <tr><td>{{ $ozet['tamamlandi'] }}</td><td>{{ $ozet['eksik'] }}</td><td>{{ $ozet['gecikmis'] }}</td><td>{{ $ozet['yaklasan'] }}</td></tr>
</table>

<h2>Firma Kimliği</h2>
<table class="kv">
    <tr><th>Firma</th><td>{{ $firma->unvan }}</td><th>Vergi no</th><td>{{ $firma->vergi_no ?: '—' }}</td></tr>
    <tr><th>SGK sicil no</th><td>{{ $firma->sgk_sicil_no ?: '—' }}</td><th>NACE kodu</th><td>{{ trim($firma->nace_kodu.' '.$firma->nace_aciklama) ?: '—' }}</td></tr>
    <tr><th>Tehlike sınıfı</th><td>{{ $firma->tehlikeSinifiEtiketi() }}</td><th>Yetkili</th><td>{{ $firma->isveren_vekili ?: $firma->isveren_ad ?: '—' }}</td></tr>
    <tr><th>Telefon</th><td>{{ $firma->telefon ?: '—' }}</td><th>Durum</th><td>{{ $firma->aktif ? 'Aktif' : 'Pasif' }}</td></tr>
    <tr><th>Adres</th><td>{{ trim($firma->adres.' '.$firma->ilce.' '.$firma->il) ?: '—' }}</td><th>İSG-KATİP no</th><td>{{ $firma->katip_no ?: '—' }}</td></tr>
    <tr><th>Hizmet sözleşmesi</th><td>{{ $firma->sozlesme_baslangic ? \Illuminate\Support\Carbon::parse($firma->sozlesme_baslangic)->format('d.m.Y') : '—' }} – {{ $firma->sozlesme_bitis ? \Illuminate\Support\Carbon::parse($firma->sozlesme_bitis)->format('d.m.Y') : '—' }}</td><th>Hazırlayan</th><td>{{ $osgb ?: '—' }}</td></tr>
</table>

<h2>İşgücü ve Görevlendirmeler</h2>
<table>
    <tr><th>Aktif çalışan</th><th>Şube</th><th>Görevlendirme</th><th>Evrak uyumu</th><th>Açık DÖF</th><th>Gecikmiş muayene</th><th>Eğitim kaydı</th></tr>
    <tr>
        <td>{{ $gostergeler['personel'] }}</td><td>{{ $gostergeler['sube'] }}</td><td>{{ $gostergeler['gorevlendirme'] }}</td>
        <td>%{{ $gostergeler['evrak_uyum'] }}</td><td>{{ $gostergeler['acik_dof'] }}</td><td>{{ $gostergeler['gecikmis_muayene'] }}</td><td>{{ $gostergeler['egitim_kaydi'] }}</td>
    </tr>
</table>
<table style="margin-top:6px">
    <tr><th>Profesyonel</th><th>Rol</th><th>Sertifika no</th><th>Otomatik aylık süre</th></tr>
    @forelse ($gorevlendirmeler as $r)
        <tr><td>{{ $r['ad'] }}</td><td>{{ $r['rol'] }}</td><td>{{ $r['sertifika'] ?: '—' }}</td><td>{{ $r['aylik_dk'] }} dk</td></tr>
    @empty
        <tr><td colspan="4">Aktif görevlendirme yok.</td></tr>
    @endforelse
</table>

<h2>İSG Süreçleri ve Terminler</h2>
<table>
    <tr><th style="width:20%">Süreç</th><th style="width:10%">Durum</th><th>Gerçek veri sonucu</th><th style="width:20%">Sorumlu</th></tr>
    @foreach ($surecler as $s)
        <tr><td>{{ $s['surec'] }}</td><td class="d-{{ $s['durum'] }}">{{ IsyeriDurumu::DURUMLAR[$s['durum']] }}</td><td>{{ $s['sonuc'] }}</td><td>{{ $s['sorumlu'] }}</td></tr>
    @endforeach
</table>

<h2>Termin Takvimi</h2>
<table>
    <tr><th>Kaynak</th><th>Başlık</th><th>Termin</th><th>Kalan</th><th>Sorumlu</th></tr>
    @forelse ($takvim as $t)
        <tr><td>{{ $t['kategori'] }}</td><td>{{ $t['kayit'] }}@if ($t['alt']) — {{ $t['alt'] }}@endif</td><td>{{ $t['tarih']->format('d.m.Y') }}</td><td>{{ $t['kalan'] }}</td><td>{{ $t['sorumlu'] }}</td></tr>
    @empty
        <tr><td colspan="5">Açık termin yok.</td></tr>
    @endforelse
</table>

<p class="not">Gizlilik: Sağlık verileri kişi, tanı, tetkik ve hekim notu içermeden toplu olarak gösterilir. Bu çıktı resmî bir uygunluk onayı değildir; mevcut kayıtların özetidir.</p>

</body>
</html>
