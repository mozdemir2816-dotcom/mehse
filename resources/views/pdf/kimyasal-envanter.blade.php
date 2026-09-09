<!doctype html>
<html lang="tr">
<head>
<meta charset="utf-8">
<style>
    * { font-family: "DejaVu Sans", sans-serif; }
    @page { margin: 22px 26px; }
    body { margin: 0; color: #111; font-size: 8.5px; line-height: 1.35; }
    h1 { font-size: 13px; text-align: center; margin: 0 0 2px; }
    .alt { text-align: center; color: #555; font-size: 8px; margin-bottom: 10px; }
    .kunye { width: 100%; border-collapse: collapse; margin-bottom: 10px; font-size: 8px; }
    .kunye td { border: 1px solid #999; padding: 3px 6px; }
    .kunye td.e { background: #eee; font-weight: bold; width: 13%; }
    table.env { width: 100%; border-collapse: collapse; }
    table.env th, table.env td { border: 1px solid #777; padding: 3px 4px; vertical-align: top; }
    table.env th { background: #eee; text-align: left; font-size: 7.5px; }
    .gec { color: #b91c1c; font-weight: bold; }
    .yak { color: #b45309; font-weight: bold; }
    .yasal { margin-top: 10px; font-size: 7.5px; color: #555; }
    .imza { margin-top: 24px; width: 100%; border-collapse: collapse; font-size: 8px; }
    .imza td { border-top: 1px solid #111; padding-top: 4px; text-align: center; width: 50%; }
</style>
</head>
<body>

<h1>TEHLİKELİ KİMYASAL MADDE ENVANTER LİSTESİ</h1>
<div class="alt">Kimyasal Maddelerle Çalışmalarda Sağlık ve Güvenlik Önlemleri Hakkında Yönetmelik uyarınca</div>

<table class="kunye">
    <tr>
        <td class="e">İşyeri</td><td>{{ $firma?->unvan ?: '—' }}</td>
        <td class="e">SGK Sicil No</td><td>{{ $firma?->sgk_sicil_no ?: '—' }}</td>
        <td class="e">Liste Tarihi</td><td>{{ now()->format('d.m.Y') }}</td>
    </tr>
</table>

<table class="env">
    <thead>
        <tr>
            <th style="width:16px">#</th>
            <th>Ürün Adı</th>
            <th style="width:70px">CAS No</th>
            <th style="width:90px">Tedarikçi</th>
            <th style="width:44px">Fiz. Hal</th>
            <th>GHS / CLP Tehlike Sınıfları</th>
            <th style="width:74px">Kullanım Alanı</th>
            <th style="width:50px">Miktar</th>
            <th style="width:38px">SDS</th>
            <th style="width:52px">SDS Tarihi</th>
            <th style="width:58px">Gözden Geçirme</th>
        </tr>
    </thead>
    <tbody>
        @forelse ($urunler as $i => $u)
            @php $d = $u->gozdenGecirmeDurumu(); @endphp
            <tr>
                <td>{{ $i + 1 }}</td>
                <td>{{ $u->urun_adi }}</td>
                <td>{{ $u->cas_no ?: '—' }}</td>
                <td>{{ $u->tedarikci ?: '—' }}</td>
                <td>{{ config('isg.kimyasal.fiziksel_hal.'.$u->fiziksel_hal, '—') }}</td>
                <td>{{ implode(', ', $u->ghsEtiketleri()) ?: '—' }}</td>
                <td>{{ $u->kullanim_alani ?: '—' }}</td>
                <td>{{ $u->miktar ?: '—' }}</td>
                <td>{{ $u->sdsVarMi() ? 'Var' : 'YOK' }}</td>
                <td>{{ $u->sds_tarihi?->format('d.m.Y') ?: '—' }}</td>
                <td class="{{ $d === 'gecikmis' ? 'gec' : ($d === 'yaklasan' ? 'yak' : '') }}">
                    {{ $u->sonraki_gozden_gecirme?->format('d.m.Y') ?: '—' }}
                </td>
            </tr>
        @empty
            <tr><td colspan="11" style="text-align:center;color:#888">Kayıtlı kimyasal ürün yok.</td></tr>
        @endforelse
    </tbody>
</table>

<p class="yasal">
    Her tehlikeli kimyasal için güncel Malzeme Güvenlik Bilgi Formu (SDS/GBF) işyerinde bulundurulmalı,
    çalışanların erişimine açık olmalı ve tedarikçi tarafından revize edildikçe güncellenmelidir. GHS/CLP
    etiketleri ambalaj üzerinde ve depolama alanında görünür olmalıdır.
</p>

<table class="imza">
    <tr>
        <td>İş Güvenliği Uzmanı</td>
        <td>İşveren / İşveren Vekili</td>
    </tr>
</table>

</body>
</html>
