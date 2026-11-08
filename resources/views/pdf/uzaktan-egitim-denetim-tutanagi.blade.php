<!doctype html>
<html lang="tr">
<head>
<meta charset="utf-8">
{{-- Firma bazlı toplu İSG uzaktan eğitim katılım ve kayıt (log) tutanağı — müfettiş denetimi için (08.11.2026).
     Çalışanların İSG Eğitimlerinin Usul ve Esasları Hakkında Yönetmelik (RG 02.04.2026) Md.12/4 kayıtları. --}}
<style>
    * { font-family: "DejaVu Sans", sans-serif; }
    @page { margin: 18px 20px; }
    body { margin: 0; color: #111; font-size: 7.6px; }
    h1 { font-size: 12px; text-align: center; margin: 0 0 3px; }
    .ust { text-align: center; font-size: 8px; margin-bottom: 6px; }
    table.bilgi { width: 100%; border-collapse: collapse; margin-bottom: 6px; }
    table.bilgi td { border: 1px solid #000; padding: 2px 4px; font-size: 8px; }
    table.liste { width: 100%; border-collapse: collapse; }
    table.liste th, table.liste td { border: 1px solid #000; padding: 2px 3px; vertical-align: top; }
    table.liste th { background: #e8e5f2; font-size: 7.2px; }
    .k { color: #444; font-size: 6.8px; }
    .imza { width: 100%; margin-top: 14px; border-collapse: collapse; }
    .imza td { width: 33%; text-align: center; font-size: 8px; padding-top: 4px; vertical-align: top; }
</style>
</head>
<body>
@php
    $sinif = $firma->tehlike_sinifi;
    $sinifAd = method_exists($firma, 'tehlikeSinifiEtiketi') ? $firma->tehlikeSinifiEtiketi() : $sinif;
    $sure = \App\Models\EgitimPaketi::gerekenUzaktanSure((string) $sinif, 'ilk_defa');
@endphp

<h1>UZAKTAN İSG EĞİTİMİ KATILIM VE KAYIT (LOG) TUTANAĞI</h1>
<div class="ust">Çalışanların İş Sağlığı ve Güvenliği Eğitimlerinin Usul ve Esasları Hakkında Yönetmelik (RG 02.04.2026) — Md.12 uzaktan eğitim kayıtları</div>

<table class="bilgi">
    <tr>
        <td><strong>İşyeri:</strong> {{ $firma->unvan }}</td>
        <td><strong>SGK sicil no:</strong> {{ $firma->sgk_sicil_no ?? '—' }}</td>
        <td><strong>Tehlike sınıfı:</strong> {{ $sinifAd }}</td>
        <td><strong>Düzenlenme:</strong> {{ now()->format('d.m.Y H:i') }}</td>
    </tr>
    <tr>
        <td colspan="2"><strong>İş güvenliği uzmanı:</strong> {{ $firma->igu?->ad_soyad ?? $uzman?->name }} @if ($firma->igu?->sertifika_no) · Belge no: {{ $firma->igu->sertifika_no }} @endif</td>
        <td colspan="2"><strong>İşyeri hekimi:</strong> {{ $firma->isyeriHekimi?->ad_soyad ?? '—' }} @if ($firma->isyeriHekimi?->sertifika_no) · Diploma / belge no: {{ $firma->isyeriHekimi->sertifika_no }} @endif</td>
    </tr>
    <tr>
        <td colspan="4" class="k">
            İlk defa temel eğitim asgari {{ $sure['saat'] }} ders saati; uzaktan verilmesi gereken kısım ≈ {{ $sure['gereken_dk'] }} dk
            @if ($sure['dorduncu_yuz_yuze']) (4. konu — işe ve işyerine özgü riskler — yüz yüze verilir)@endif.
            Kayıtlar: oturum giriş–çıkış saatleri, IP / cihaz, fiilen oynatılan saniye (ileri sarma sayılmaz), aktif katılım cevapları, ön test ve final sınavı.
        </td>
    </tr>
</table>

<table class="liste">
    <thead>
        <tr>
            <th>#</th><th>Ad Soyad / T.C.</th><th>Eğitim</th><th>Oturumlar (giriş – çıkış) · IP / cihaz</th>
            <th>Fiili izleme (dk)<br>Genel / Sağlık / Teknik</th><th>Aktif katılım</th><th>Ön test</th><th>Final (deneme)</th>
            <th>Onaylar</th><th>Durum</th>
        </tr>
    </thead>
    <tbody>
    @foreach ($atamalar as $i => $a)
        @php
            $konu = $a->ilerlemeler->groupBy(fn ($il) => (int) ($il->ders?->modul ?? 0))->map(fn ($g) => (int) round($g->sum('izlenen_sn') / 60));
            $son = $a->sinavSonuclari->sortByDesc('id')->first();
        @endphp
        <tr>
            <td>{{ $i + 1 }}</td>
            <td><strong>{{ $a->calisan->ad_soyad }}</strong><br><span class="k">{{ $a->calisan->tc ?: '—' }} · {{ $a->calisan->gorev }}</span></td>
            <td>{{ \Illuminate\Support\Str::limit($a->paket->ad, 34) }}<br><span class="k">{{ $a->turEtiketi() }} · atandı {{ $a->atandi_at?->format('d.m.Y') }}</span></td>
            <td>
                @forelse ($a->girisler as $g)
                    <div>{{ $g->giris_at->format('d.m.Y H:i') }} – {{ $g->cikis_at?->format('H:i') ?? '?' }}@if ($g->izleme_sn) · {{ round($g->izleme_sn / 60) }} dk @endif <span class="k">{{ trim($g->ip.' '.\Illuminate\Support\Str::limit((string) $g->cihaz, 28)) }}</span></div>
                @empty
                    <span class="k">Giriş yok</span>
                @endforelse
            </td>
            <td style="white-space:nowrap">{{ (int) round($a->ilerlemeler->sum('izlenen_sn') / 60) }} dk<br><span class="k">{{ $konu[1] ?? 0 }} / {{ $konu[2] ?? 0 }} / {{ $konu[3] ?? 0 }}</span></td>
            <td>{{ (int) $a->girisler->sum('yoklama_sayisi') }} cevap</td>
            <td>{{ $a->on_test_puani ?? '—' }}<br><span class="k">{{ $a->on_test_at?->format('d.m.Y') }}</span></td>
            <td>{{ $son?->puan ?? '—' }}@if ($son) ({{ $a->sinavSonuclari->count() }})<br><span class="k">{{ $son->tamamlandi_at?->format('d.m.Y H:i') }}</span>@endif</td>
            <td>
                İGU: {{ $a->igu_onay_at?->format('d.m.Y') ?? '—' }}<br>
                İH: {{ $a->ih_onay_at?->format('d.m.Y') ?? '—' }}@if ($a->ih_ad_soyad)<br><span class="k">{{ $a->ih_ad_soyad }}</span>@endif
            </td>
            <td>{{ $a->durumEtiketi() }}@if ($a->tamamlandi_at)<br><span class="k">{{ $a->tamamlandi_at->format('d.m.Y') }} · belge UE-{{ $a->id }}</span>@endif</td>
        </tr>
    @endforeach
    </tbody>
</table>

<table class="imza">
    <tr>
        <td>İş Güvenliği Uzmanı<br><br>{{ $firma->igu?->ad_soyad ?? $uzman?->name }}<br>İmza</td>
        <td>İşyeri Hekimi<br><br>{{ $firma->isyeriHekimi?->ad_soyad ?? '' }}<br>İmza</td>
        <td>İşveren / İşveren Vekili<br><br><br>İmza</td>
    </tr>
</table>
</body>
</html>
