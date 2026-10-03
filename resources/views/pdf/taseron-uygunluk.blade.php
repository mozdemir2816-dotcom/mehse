<!doctype html>
<html lang="tr">
<head>
<meta charset="utf-8">
<style>
    * { font-family: DejaVu Sans, sans-serif; }
    body { margin: 0; color: #111; font-size: 10.5px; }
    .sayfa { padding: 26px 32px; }
    .baslik { text-align: center; border-bottom: 3px double #111; padding-bottom: 10px; margin-bottom: 12px; }
    .baslik h1 { font-size: 15px; margin: 0 0 4px; }
    h2 { font-size: 11.5px; margin: 14px 0 5px; color: #7c3aed; border-bottom: 1px solid #7c3aed; padding-bottom: 3px; }
    table { width: 100%; border-collapse: collapse; font-size: 9.5px; margin-bottom: 8px; }
    th, td { border: 1px solid #999; padding: 4px 6px; text-align: left; vertical-align: top; }
    th { background: #f0f0f0; }
    td.k { background: #f0f0f0; font-weight: bold; width: 20%; }
    .eksik { background: #fff7d6; border: 1px solid #e8c95a; padding: 6px 9px; font-size: 9.5px; margin-bottom: 10px; }
    .uygun { background: #e7f6ec; border: 1px solid #8fd1a6; padding: 6px 9px; font-size: 9.5px; margin-bottom: 10px; }
    .kirmizi { color: #b91c1c; }
    .imza { margin-top: 26px; }
    .imza td { border: none; width: 50%; text-align: center; padding-top: 36px; }
    .imza span { display: block; border-top: 1px solid #111; margin: 0 30px; padding-top: 4px; }
</style>
</head>
<body>
@php
    $tarih = fn ($d) => $d?->format('d.m.Y') ?? '—';
    $eksikler = $t->eksikler();
@endphp
<div class="sayfa">
    <div class="baslik">
        <h1>{{ $t->tur === 'taseron' ? 'TAŞERON' : 'ALT İŞVEREN' }} UYGUNLUK RAPORU</h1>
        <div>{{ $t->unvan }}</div>
        <div style="font-size:9.5px;color:#555;margin-top:2px">İşyeri: {{ $firma?->unvan }} · Düzenleme: {{ now()->format('d.m.Y') }}</div>
    </div>

    @if (! $t->aktif)
        <div class="eksik"><strong>Bu firma pasif durumdadır.</strong></div>
    @elseif ($eksikler)
        <div class="eksik">
            <strong>EKSİKLER:</strong>
            @foreach ($eksikler as $e)<br>• {{ $e }}@endforeach
        </div>
    @else
        <div class="uygun"><strong>Uygun:</strong> Sözleşme, zorunlu belgeler ve çalışan eğitim / sağlık kayıtları geçerli.</div>
    @endif

    <h2>1. FİRMA VE SÖZLEŞME BİLGİLERİ</h2>
    <table>
        <tr><td class="k">Ünvan</td><td>{{ $t->unvan }}</td><td class="k">Tür</td><td>{{ $t->turEtiketi() }}</td></tr>
        <tr><td class="k">Faaliyet</td><td>{{ $t->faaliyet ?: '—' }}</td><td class="k">Tehlike Sınıfı</td><td>{{ config('isg.tehlike_siniflari.'.$t->etkinTehlikeSinifi(), '—') }}</td></tr>
        <tr><td class="k">Vergi No</td><td>{{ $t->vergi_no ?: '—' }}</td><td class="k">SGK Sicil No</td><td>{{ $t->sgk_sicil_no ?: '—' }}</td></tr>
        <tr><td class="k">Sözleşme No</td><td>{{ $t->sozlesme_no ?: '—' }}</td><td class="k">Sözleşme Süresi</td><td>{{ $tarih($t->sozlesme_baslangic) }} – {{ $tarih($t->sozlesme_bitis) }}</td></tr>
        <tr><td class="k">Yetkili</td><td>{{ $t->yetkili ?: '—' }}</td><td class="k">Telefon / E-posta</td><td>{{ collect([$t->telefon, $t->eposta])->filter()->implode(' · ') ?: '—' }}</td></tr>
        <tr><td class="k">İSG Uzmanı</td><td>{{ $t->isg_uzmani ?: '—' }}</td><td class="k">İşyeri Hekimi</td><td>{{ $t->isyeri_hekimi ?: '—' }}</td></tr>
    </table>

    <h2>2. ÇALIŞANLAR</h2>
    <table>
        <tr><th style="width:4%">#</th><th>Ad Soyad</th><th>Görev</th><th>Kimlik No</th><th>İşe Giriş</th><th>İSG Eğitimi (geçerlilik)</th><th>Sağlık Raporu (geçerlilik)</th><th>Durum</th></tr>
        @forelse ($t->calisanlar as $i => $c)
            <tr>
                <td>{{ $i + 1 }}</td>
                <td>{{ $c->ad_soyad }}</td>
                <td>{{ $c->gorev ?: '—' }}</td>
                <td>{{ $c->tc_maskeli ?: '—' }}</td>
                <td>{{ $tarih($c->ise_giris) }}</td>
                <td class="{{ $c->aktif && ! $c->egitimGecerliMi() ? 'kirmizi' : '' }}">{{ $tarih($c->isg_egitim_tarihi) }} ({{ $tarih($c->egitimBitis()) }})</td>
                <td class="{{ $c->aktif && ! $c->saglikGecerliMi() ? 'kirmizi' : '' }}">{{ $tarih($c->saglik_raporu_tarihi) }} ({{ $tarih($c->saglikBitis()) }})</td>
                <td>{{ $c->aktif ? 'Aktif' : 'Pasif' }}</td>
            </tr>
        @empty
            <tr><td colspan="8" style="color:#888">Çalışan kaydı yok.</td></tr>
        @endforelse
    </table>

    <h2>3. BELGELER</h2>
    <table>
        <tr><th>Belge Türü</th><th>Başlık</th><th>Geçerlilik Sonu</th><th>Durum</th><th>Not</th></tr>
        @forelse ($t->belgeler as $b)
            <tr>
                <td>{{ $b->turEtiketi() }}</td>
                <td>{{ $b->baslik ?: '—' }}</td>
                <td>{{ $b->gecerlilik_sonu ? $b->gecerlilik_sonu->format('d.m.Y') : 'Süresiz' }}</td>
                <td class="{{ $b->durum() === 'dolmus' ? 'kirmizi' : '' }}">{{ ['suresiz' => 'Süresiz', 'gecerli' => 'Geçerli', 'yaklasan' => 'Süresi yaklaşıyor', 'dolmus' => 'Süresi dolmuş'][$b->durum()] }}</td>
                <td>{{ $b->notu ?: '' }}</td>
            </tr>
        @empty
            <tr><td colspan="5" style="color:#888">Belge kaydı yok.</td></tr>
        @endforelse
    </table>
    @if ($zorunluEksik = $t->eksikZorunluBelgeler())
        <p style="font-size:9.5px" class="kirmizi"><strong>Eksik zorunlu belgeler:</strong> {{ implode(', ', $zorunluEksik) }}</p>
    @endif

    <h2>4. BAĞLI İŞ İZİNLERİ</h2>
    <table>
        <tr><th>İzin No</th><th>Çalışma Alanı</th><th>Başlangıç</th><th>Bitiş</th></tr>
        @forelse ($t->isIzinleri as $iz)
            <tr>
                <td>{{ $iz->izin_no ?: '#'.$iz->id }}</td>
                <td>{{ $iz->calisma_alani ?: '—' }}</td>
                <td>{{ $iz->baslangic?->format('d.m.Y H:i') ?? '—' }}</td>
                <td>{{ $iz->bitis?->format('d.m.Y H:i') ?? '—' }}</td>
            </tr>
        @empty
            <tr><td colspan="4" style="color:#888">Bağlı iş izni yok.</td></tr>
        @endforelse
    </table>

    <table class="imza">
        <tr>
            <td><span>&nbsp;<br>İş Güvenliği Uzmanı</span></td>
            <td><span>{{ $t->yetkili ?: 'Taşeron Yetkilisi' }}<br>{{ $t->turEtiketi() }} Yetkilisi</span></td>
        </tr>
    </table>
</div>
</body>
</html>
