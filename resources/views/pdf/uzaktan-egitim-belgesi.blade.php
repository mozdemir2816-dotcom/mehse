<!doctype html>
<html lang="tr">
<head>
<meta charset="utf-8">
{{-- Ek-2 TEMEL EĞİTİM BELGESİ — Çalışanların İSG Eğitimlerinin Usul ve Esasları
     Hakkında Yönetmelik (RG 02.04.2026) örneğiyle birebir: sol ön yüz, sağ arka yüz. --}}
<style>
    * { font-family: "DejaVu Sans", sans-serif; }
    @page { margin: 22px 26px; }
    body { margin: 0; color: #111; font-size: 9.5px; }
    .ek { text-align: right; font-weight: bold; font-size: 11px; }
    .baslik { text-align: center; font-weight: bold; font-size: 13px; margin: 2px 0 6px; }
    table.sayfa { width: 100%; border-collapse: collapse; }
    table.sayfa > tbody > tr > td { vertical-align: top; border: 1px solid #000; padding: 0; }
    .on { width: 52%; padding: 8px 12px !important; line-height: 1.55; }
    .arka { width: 48%; }
    .yuz { font-size: 9px; padding: 2px 4px; border-bottom: 1px solid #000; }
    table.konu { width: 100%; border-collapse: collapse; }
    table.konu td { border-bottom: 1px solid #000; padding: 1.5px 4px; font-size: 8.3px; }
    table.konu td.sure { width: 44px; border-left: 1px solid #000; text-align: center; }
    table.konu tr.ana td { font-weight: bold; }
    .kutu { font-size: 11px; }
    .dolu { font-weight: bold; }
    .not { font-size: 7.5px; margin-top: 4px; }
    .alan { margin-top: 2px; }
</style>
</head>
<body>
@php
    $tehlike = $firma?->tehlike_sinifi;
    $dorduncuYuzYuze = $atama->dorduncuKonuYuzYuzeMi();
    $uzaktanBasliklar = $dorduncuYuzYuze ? '1, 2, 3' : '1, 2, 3, 4';
    $kutu = fn (bool $isaretli) => $isaretli ? '<span class="kutu dolu">☒</span>' : '<span class="kutu">☐</span>';
    $dk = $paket?->toplamSureDk() ?? 0;
    $dersSaati = (int) config('isg.uzaktan_egitim.ders_saati_dk', 45);
    $egitimci = $atama->atayan;
    $egitimciUnvan = $egitimci ? config('isg.uzman_unvanlari.'.$egitimci->unvan, '') : '';
    $tarih = $atama->tamamlandi_at?->format('d.m.Y') ?? '../../....';
@endphp

<div class="ek">Ek-2</div>
<div class="baslik">TEMEL EĞİTİM BELGESİ</div>

<table class="sayfa">
    <tr>
        <td class="on">
            <div style="font-size:9px">(ÖN YÜZ)</div>
            <div style="font-weight:bold;font-size:11px;margin:8px 0 12px">TEMEL EĞİTİM BELGESİ</div>

            <div>İşbu belge,</div>
            <div style="margin:8px 0"><strong>{{ $calisan?->ad_soyad }}</strong>@if ($calisan?->gorev) ({{ $calisan->gorev }})@endif adına</div>

            <div style="text-indent:24px;text-align:justify">
                Çalışanların İş Sağlığı ve Güvenliği Eğitimlerinin Usul ve Esasları Hakkında Yönetmelik kapsamında
                <strong>{{ $egitimci?->name ?? '—' }}</strong>@if ($egitimciUnvan) ({{ $egitimciUnvan }})@endif tarafından
                <strong>{{ $tarih }}</strong> tarihinde gerçekleştirilen temel eğitim sonunda düzenlenmiştir.
            </div>

            <div style="margin-top:16px">
                <div class="alan">Belge düzenlenme tarihi: <strong>{{ now()->format('d.m.Y') }}</strong></div>
                <div class="alan">Eğitimin süresi: <strong>{{ intdiv($dk, 60) }} saat {{ $dk % 60 }} dk</strong> (≈ {{ round($dk / max(1, $dersSaati), 1) }} ders saati, uzaktan)</div>
                <div class="alan">
                    Eğitimin türü: İlk defa verilen temel eğitim {!! $kutu($atama->egitim_turu === 'ilk_defa') !!}
                    <div style="padding-left:62px">Tekrar verilen temel eğitim {!! $kutu($atama->egitim_turu === 'yenileme') !!}</div>
                </div>
                <div class="alan">
                    Eğitimin şekli: Uzaktan {!! $kutu(true) !!} (Başlık {{ $uzaktanBasliklar }})
                    <div style="padding-left:62px">Yüz yüze {!! $kutu(false) !!} (Başlık {{ $dorduncuYuzYuze ? '4 — ayrıca yüz yüze verilir' : '……' }})</div>
                </div>
                <div class="alan">Eğiticilerin adı soyadı ve ünvanı: <strong>{{ $egitimci?->name }}</strong>@if ($egitimciUnvan) — {{ $egitimciUnvan }}@endif</div>
                <div class="alan">Eğiticilerin imzası:</div>
            </div>

            <div style="margin-top:22px">
                <div class="alan">Çalışanın işyerinin ünvanı: <strong>{{ $firma?->unvan }}</strong></div>
                <div class="alan">İşverenin/işveren vekilinin adı soyadı: <strong></strong></div>
                <div class="alan">İşveren/işveren vekilinin imzası:</div>
            </div>

            <div style="margin-top:22px;font-size:7.5px;color:#444">
                Uzaktan eğitim kaydı: {{ $paket?->ad }} · sınav puanı {{ $sinav?->puan ?? '—' }}
                @if ($atama->on_test_puani !== null) · ön test {{ $atama->on_test_puani }} @endif
                · belge no UE-{{ $atama->id }}
            </div>
        </td>

        <td class="arka">
            <div class="yuz">(ARKA YÜZ)</div>
            <table class="konu">
                <tr class="ana"><td>EĞİTİM KONULARI</td><td class="sure">SÜRE</td></tr>
                @foreach (config('isg.uzaktan_egitim.ek1_konulari') as $baslik => $maddeler)
                    <tr class="ana"><td>{{ $baslik }}</td><td class="sure"></td></tr>
                    @foreach ($maddeler as $m)
                        <tr><td>{{ $m }}</td><td class="sure"></td></tr>
                    @endforeach
                @endforeach
                <tr class="ana"><td>{{ config('isg.uzaktan_egitim.ek1_dorduncu_baslik') }}</td><td class="sure"></td></tr>
                <tr><td>{{ config('isg.uzaktan_egitim.ek1_dorduncu_a') }} {!! $kutu(false) !!}@if ($dorduncuYuzYuze) <em>(yüz yüze verilir)</em>@endif</td><td class="sure"></td></tr>
                <tr><td>{{ config('isg.uzaktan_egitim.ek1_dorduncu_b') }} {!! $kutu($tehlike === 'az_tehlikeli') !!}</td><td class="sure"></td></tr>
            </table>
        </td>
    </tr>
</table>
<div class="not">* İşveren, Ek-2'de yer alan temel eğitimin dördüncü konu başlığını tehlike sınıfına uygun şekilde a) veya b) seçeneğini işaretler ve varsa ilave konuları da ekler.</div>
</body>
</html>
