<!doctype html>
<html lang="tr"><head><meta charset="utf-8">@include('pdf.ekipman._stil')
<style> .metin { font-size: 9.6px; line-height: 1.65; text-align: justify; margin: 10px 0; } table.imza td { height: 80px; } </style>
</head>
<body>
@include('pdf.ekipman._antet', ['baslik' => 'İSG EKİPMAN DOKÜMANLARI VE PERİYODİK KONTROL TEBLİĞ TUTANAĞI'])
<table class="kunye">
    <tr><td class="e">Ekipman tipi</td><td>{{ $e->tip ?: '—' }}</td><td class="e">Kapasite</td><td>{{ $e->kapasite ?: '—' }}</td></tr>
    <tr><td class="e">Son periyodik kontrol</td><td>{{ $e->son_muayene_tarihi?->format('d.m.Y') ?? '—' }}{{ $e->muayene_yapan ? ' — '.$e->muayene_yapan : '' }}</td>
        <td class="e">Periyot</td><td>{{ \App\Support\EkipmanKatalogu::periyodikMi($e->kategori) ? $e->muayene_periyodu_ay.' ay' : 'Periyodik muayeneye tabi değil' }}</td></tr>
    <tr><td class="e">Yasal standart</td><td colspan="3">{{ $e->yasal_standart ?: '—' }}</td></tr>
</table>

<p class="metin">İşyerinde kullanılmakta olan yukarıda künyesi belirtilen iş ekipmanına ait
    <strong>Çalışma Talimatı</strong>{{ $talimat ? ' ('.$talimat->baslik.')' : '' }} ve
    <strong>Günlük Kontrol Formu</strong>{{ $form ? ' ('.$form['ad'].')' : '' }} hazırlanmış olup;
    @if ($e->sonraki_vize_tarihi)
        bir sonraki yasal periyodik kontrol tarihinin <strong>{{ $e->sonraki_vize_tarihi->format('d.m.Y') }}</strong> olduğu,
    @elseif (\App\Support\EkipmanKatalogu::periyodikMi($e->kategori))
        periyodik kontrol tarihinin henüz belirlenmediği ve kontrolün yetkili kişiye yaptırılması gerektiği,
    @else
        ekipmanın periyodik muayeneye tabi olmadığı, kullanım öncesi günlük kontrolle takip edileceği,
    @endif
    6331 sayılı İş Sağlığı ve Güvenliği Kanunu ve İş Ekipmanlarının Kullanımında Sağlık ve Güvenlik Şartları
    Yönetmeliği kapsamında işverene / işveren vekiline tebliğ edilmiştir.</p>

<p class="metin">Günlük kontrolde uygunsuzluk tespit edilen ekipmanın giderilene kadar kullanılmaması, periyodik
    kontrolün süresi içinde yetkili kişilere yaptırılması ve sonuç raporunun İSG birimine iletilmesi işverenin
    sorumluluğundadır.</p>

<table class="kunye"><tr><td class="e">Teslim edilen belgeler</td><td>1) Çalışma ve güvenlik talimatı &nbsp; 2) Aylık operatör günlük kontrol çizelgesi &nbsp; 3) Bu tutanak</td></tr></table>

<table class="imza">
    <tr><th>TEBLİĞ EDEN<br>İş Güvenliği Uzmanı</th><th>TEBELLÜĞ EDEN<br>İşveren / İşveren Vekili</th></tr>
    <tr>
        <td>{{ $tebligEden->ad }}<br>Tarih: {{ now()->format('d.m.Y') }}<br>
            @if ($tebligEden->imza_gorseli && is_file($p = storage_path('app/public/'.$tebligEden->imza_gorseli)))<img src="{{ $p }}">@endif<br>İmza:</td>
        <td>{{ $firma->isveren_vekili ?: $firma->isveren_ad }}<br>Tarih: ....../....../..........<br><br>Kaşe / İmza:</td>
    </tr>
</table>
</body></html>
