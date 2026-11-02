<!doctype html>
<html lang="tr">
<head>
<meta charset="utf-8">
<style>
    * { font-family: DejaVu Sans, sans-serif; }
    body { margin: 0; color: #111; font-size: 10px; }
    .sayfa { padding: 22px 30px; }
    .baslik { text-align: center; border-bottom: 3px double #b91c1c; padding-bottom: 8px; margin-bottom: 10px; }
    .baslik h1 { font-size: 14px; margin: 0 0 3px; color: #7f1d1d; letter-spacing: .3px; }
    .baslik .firma { font-size: 11px; font-weight: bold; }
    .baslik .dayanak { font-size: 8px; color: #555; margin-top: 3px; }
    table.kunye { width: 100%; border-collapse: collapse; font-size: 9.5px; margin-bottom: 6px; }
    table.kunye td { border: 1px solid #999; padding: 3px 7px; vertical-align: top; }
    table.kunye td.e { background: #f3f4f6; font-weight: bold; width: 19%; }
    h2 { font-size: 10.5px; margin: 10px 0 4px; color: #fff; background: #b91c1c; padding: 3px 7px; }
    p.metin { font-size: 9.5px; text-align: justify; margin: 0 0 6px; line-height: 1.45; }
    table.liste { width: 100%; border-collapse: collapse; font-size: 9px; margin-bottom: 6px; }
    table.liste th, table.liste td { border: 1px solid #999; padding: 3px 6px; text-align: left; vertical-align: top; }
    table.liste th { background: #f3f4f6; }
    table.krono { width: 100%; border-collapse: collapse; font-size: 9.5px; margin-bottom: 6px; }
    table.krono td { border: 1px solid #999; padding: 5px 4px; text-align: center; width: 16.66%; }
    table.krono .k { font-size: 7.5px; color: #555; text-transform: uppercase; }
    table.krono .d { font-size: 12px; font-weight: bold; margin-top: 2px; }
    .iyi { color: #15803d; } .kotu { color: #b91c1c; }
    .bos { color: #888; }
    table.imza { margin-top: 18px; width: 100%; border-collapse: collapse; page-break-inside: avoid; }
    table.imza td { text-align: center; vertical-align: top; font-size: 9px; padding: 0 6px; }
    table.imza .alan { height: 52px; border-bottom: 1px solid #111; }
    table.imza .rol { font-weight: bold; margin-top: 3px; }
    .foto-sayfa { page-break-before: always; }
    table.foto { width: 100%; border-collapse: collapse; }
    table.foto td { width: 50%; padding: 4px; text-align: center; vertical-align: top; border: 1px solid #ddd; }
    table.foto img { max-width: 250px; max-height: 300px; }
    table.foto .alt { font-size: 8px; color: #555; margin-top: 2px; }
</style>
</head>
<body>
@php
    $t = $tutanak;
    $toplam = $t->toplamSureDk();
    $hedef = $t->hedefTahliyeDk();
    $tuttu = $t->hedefTuttuMu();
    $tahliye = $t->gerceklesenTahliyeDk();
    $ekipler = $t->raporEkipleri();
    $fotolar = $t->raporFotograflari();
    $sec = config('isg.tatbikat.degerlendirme_secenekleri');
    $imzalar = array_values(array_filter([
        ['rol' => 'İşveren / İşveren Vekili', 'ad' => $t->isverenAdi(), 'not' => 'İmza – Kaşe'],
        ['rol' => 'İş Güvenliği Uzmanı', 'ad' => $t->iguAdi(), 'not' => 'İmza'],
        $t->isyeri_hekimi_imzasi ? ['rol' => 'İşyeri Hekimi', 'ad' => $t->hekimAdi(), 'not' => 'İmza'] : null,
        ['rol' => 'Tatbikat Koordinatörü', 'ad' => $t->tatbikat_koordinatoru, 'not' => 'İmza'],
    ]));
@endphp
<div class="sayfa">

    <div class="baslik">
        <h1>ACİL DURUM TATBİKAT TUTANAĞI</h1>
        <div class="firma">{{ $firma?->unvan }}</div>
        <div style="font-size:10px;margin-top:2px">{{ mb_strtoupper($t->senaryoEtiketi(), 'UTF-8') }}</div>
        <div class="dayanak">İşyerlerinde Acil Durumlar Hakkında Yönetmelik Md. 13 (Tatbikat) uyarınca düzenlenmiştir.</div>
    </div>

    <h2>1. İŞYERİ KÜNYESİ</h2>
    <table class="kunye">
        <tr><td class="e">İşyeri Unvanı</td><td colspan="3">{{ $firma?->unvan ?: '—' }}</td></tr>
        <tr><td class="e">Adres</td><td colspan="3">{{ $firma?->adres ?: '—' }}</td></tr>
        <tr>
            <td class="e">SGK Sicil No</td><td>{{ $firma?->sgk_sicil_no ?: '—' }}</td>
            <td class="e">Tehlike Sınıfı</td><td>{{ $firma?->tehlike_sinifi ? $firma->tehlikeSinifiEtiketi() : '—' }}</td>
        </tr>
        <tr>
            <td class="e">Çalışan Sayısı</td><td>{{ $firma?->calisan_sayisi ?: '—' }}</td>
            <td class="e">Belge Tarihi</td><td>{{ $t->belge_tarihi?->format('d.m.Y') ?: '—' }}</td>
        </tr>
    </table>

    <h2>2. TATBİKAT BİLGİLERİ</h2>
    <table class="kunye">
        <tr>
            <td class="e">Tatbikat Türü</td><td>{{ $t->senaryoEtiketi() }}</td>
            <td class="e">Tatbikat Tarihi</td><td>{{ $t->tatbikat_tarihi?->format('d.m.Y') ?: '—' }}</td>
        </tr>
        <tr>
            <td class="e">Tatbikat Yeri</td><td>{{ $t->tatbikat_yeri ?: '—' }}</td>
            <td class="e">Toplanma Alanı</td><td>{{ $t->toplanma_alani ?: '—' }}</td>
        </tr>
        <tr>
            <td class="e">Niteliği</td><td>{{ $t->haberli_tatbikat ? 'Haberli' : 'Habersiz' }}, {{ $t->yillik_plan_dahilinde ? 'yıllık plan dahilinde' : 'plan dışı' }}</td>
            <td class="e">Katılımcı Sayısı</td><td>{{ $t->katilimciSayisi() ?: '—' }} kişi</td>
        </tr>
        <tr><td class="e">Tatbikat Koordinatörü</td><td colspan="3">{{ $t->tatbikat_koordinatoru ?: '—' }}</td></tr>
    </table>

    <h2>3. KRONOMETRE VE SÜRELER</h2>
    <table class="krono">
        <tr>
            <td><div class="k">Başlama</div><div class="d">{{ $t->baslama_saati ?: '—' }}</div></td>
            <td><div class="k">Bitiş</div><div class="d">{{ $t->bitis_saati ?: '—' }}</div></td>
            <td><div class="k">Toplam Süre</div><div class="d">{{ $toplam ? $toplam.' dk' : '—' }}</div></td>
            <td><div class="k">Hedef Tahliye</div><div class="d">{{ $hedef ? $hedef.' dk' : '—' }}</div></td>
            <td><div class="k">Gerçekleşen Tahliye</div><div class="d">{{ $tahliye ? $tahliye.' dk' : '—' }}</div>@if (! $t->tahliye_dk && $tahliye)<div class="k" style="text-transform:none">başlama–bitiş farkı</div>@endif</td>
            <td><div class="k">Sonuç</div>
                <div class="d {{ $tuttu === null ? '' : ($tuttu ? 'iyi' : 'kotu') }}" style="font-size:9.5px">
                    {{ $tuttu === null ? '—' : ($tuttu ? 'Hedef içinde' : ($tahliye - $hedef).' dk aşıldı') }}
                </div>
            </td>
        </tr>
    </table>

    <h2>4. TATBİKAT SENARYOSU</h2>
    @foreach (preg_split('/\R+/u', trim((string) $t->raporSenaryoMetni())) ?: [] as $paragraf)
        <p class="metin">{{ $paragraf !== '' ? $paragraf : '—' }}</p>
    @endforeach

    <h2>5. GÖREV ALAN ACİL DURUM EKİPLERİ</h2>
    <table class="liste">
        <tr><th style="width:5%">#</th><th>Ad Soyad</th><th style="width:35%">Ekip</th></tr>
        @forelse ($ekipler as $i => $e)
            <tr><td>{{ $i + 1 }}</td><td>{{ $e['ad_soyad'] ?? '' }}</td><td>{{ $e['ekip'] ?? '—' }}</td></tr>
        @empty
            <tr><td colspan="3" class="bos">Ekip üyesi eklenmedi.</td></tr>
        @endforelse
        @if ($t->yanginTuruMu() && ! collect($ekipler)->contains(fn ($e) => str_contains(mb_strtolower((string) ($e['ekip'] ?? '')), 'söndürme')))
            <tr><td colspan="3" class="kotu">Uyarı: Yangın tatbikatında Söndürme Ekibi görev almalıdır — firmada söndürme ekibi tanımlı değil (Acil Durum Ekipleri).</td></tr>
        @endif
    </table>

    <h2>6. DEĞERLENDİRME KONTROL LİSTESİ</h2>
    <table class="liste">
        <tr><th style="width:5%">#</th><th>Kontrol Sorusu</th><th style="width:14%">Sonuç</th></tr>
        @forelse (($t->degerlendirmeler ?? []) as $i => $d)
            @php $c = $d['cevap'] ?? null; @endphp
            <tr>
                <td>{{ $i + 1 }}</td><td>{{ $d['soru'] ?? '' }}</td>
                <td class="{{ $c === 'evet' ? 'iyi' : ($c === 'hayir' ? 'kotu' : '') }}" style="font-weight:bold">{{ $sec[$c] ?? '—' }}</td>
            </tr>
        @empty
            <tr><td colspan="3" class="bos">Değerlendirme yapılmadı.</td></tr>
        @endforelse
    </table>

    <h2>7. GÖZLEMLER VE GENEL DEĞERLENDİRME</h2>
    <p class="metin">{{ $t->gozlem ?: '—' }}</p>

    <h2>8. TESPİT EDİLEN EKSİKLİKLER VE DÜZELTİCİ / ÖNLEYİCİ FAALİYETLER (DÖF)</h2>
    <table class="liste">
        <tr><th style="width:5%">#</th><th>Tespit Edilen Eksiklik</th></tr>
        @forelse (($t->eksiklikler ?? []) as $i => $e)
            <tr><td>{{ $i + 1 }}</td><td>{{ $e }}</td></tr>
        @empty
            <tr><td colspan="2" class="bos">Eksiklik tespit edilmedi.</td></tr>
        @endforelse
    </table>
    <table class="liste">
        <tr><th style="width:5%">#</th><th>Yapılacak Düzeltici / Önleyici Faaliyet</th><th style="width:22%">Sorumlu</th><th style="width:13%">Termin</th><th style="width:10%">Durum</th></tr>
        @forelse (($t->dof_onerileri ?? []) as $i => $d)
            <tr>
                <td>{{ $i + 1 }}</td><td>{{ $d['faaliyet'] ?? '' }}</td><td>{{ $d['sorumlu'] ?: '—' }}</td>
                <td>{{ filled($d['tarih'] ?? null) ? \Illuminate\Support\Carbon::parse($d['tarih'])->format('d.m.Y') : '—' }}</td><td></td>
            </tr>
        @empty
            <tr><td colspan="5" class="bos">Düzeltici / önleyici faaliyet önerisi eklenmedi.</td></tr>
        @endforelse
    </table>

    <h2>9. KATILIMCILAR</h2>
    <table class="liste">
        <tr><th style="width:5%">#</th><th>Ad Soyad</th><th style="width:18%">T.C. No</th><th style="width:24%">Görevi</th><th style="width:16%">İmza</th></tr>
        @forelse (($t->katilimcilar ?? []) as $i => $k)
            <tr><td>{{ $i + 1 }}</td><td>{{ $k['ad_soyad'] ?? '' }}</td><td>{{ $k['tc'] ?? '—' }}</td><td>{{ $k['gorev'] ?? '—' }}</td><td></td></tr>
        @empty
            <tr><td colspan="5" class="bos">Katılımcı listesi eklenmedi{{ $t->katilimci_sayisi ? ' (toplam '.$t->katilimci_sayisi.' kişi katıldı)' : '' }}.</td></tr>
        @endforelse
    </table>

    <table class="imza">
        <tr>
            @foreach ($imzalar as $im)
                <td style="width:{{ round(100 / count($imzalar), 2) }}%">
                    <div class="alan"></div>
                    <div class="rol">{{ $im['rol'] }}</div>
                    <div>{{ $im['ad'] ?: '.............................' }}</div>
                    <div style="color:#666;font-size:8px">({{ $im['not'] }})</div>
                </td>
            @endforeach
        </tr>
    </table>

</div>

@if ($fotolar !== [])
    <div class="sayfa foto-sayfa">
        <div class="baslik">
            <h1>TATBİKAT FOTOĞRAFLARI</h1>
            <div class="firma">{{ $firma?->unvan }}</div>
            <div style="font-size:10px;margin-top:2px">{{ $t->senaryoEtiketi() }} — {{ $t->tatbikat_tarihi?->format('d.m.Y') }}</div>
        </div>
        <table class="foto">
            @foreach (array_chunk($fotolar, 2) as $s => $cift)
                <tr>
                    @foreach ($cift as $j => $yol)
                        <td>
                            <img src="{{ $yol }}">
                            <div class="alt">Fotoğraf {{ $s * 2 + $j + 1 }}</div>
                        </td>
                    @endforeach
                    @if (count($cift) === 1)<td></td>@endif
                </tr>
            @endforeach
        </table>
    </div>
@endif

</body>
</html>
