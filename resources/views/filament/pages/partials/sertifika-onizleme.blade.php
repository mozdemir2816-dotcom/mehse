@php
    $kat = $katilimci ?? ['ad_soyad' => 'ÖRNEK KATILIMCI', 'gorev' => '—'];
    $bolumler = [
        '1. GENEL KONULAR' => collect($icerik['genel_konular'] ?? [])->where('dahil', true),
        '2. SAĞLIK KONULARI' => collect($icerik['saglik_konulari'] ?? [])->where('dahil', true),
        '3. TEKNİK KONULAR' => collect($icerik['teknik_konular'] ?? [])->where('dahil', true),
        '4. İŞYERİNE ÖZGÜ RİSKLER' => collect($icerik['isyerine_ozgu']['maddeler'] ?? [])->where('dahil', true),
    ];
    $sayfa = 'background:#fff;color:#1E293B;border:1px solid #cbd5e1;box-shadow:0 2px 8px rgb(0 0 0/.12);aspect-ratio:210/297;padding:14px;position:relative;font-size:.68rem;line-height:1.35;overflow:hidden';
    $cerceve = 'position:absolute;inset:6px;border:3px double #C02626;pointer-events:none';
    $tarih = collect($tarihler ?? [])->filter()->map(fn ($t) => \Illuminate\Support\Carbon::parse($t)->format('d/m/Y'))->implode(' - ') ?: now()->format('d/m/Y');
@endphp
<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(260px,1fr));gap:1rem">
    <div style="{{ $sayfa }}">
        <div style="{{ $cerceve }}"></div>
        <div style="text-align:center;padding-top:10px;position:relative">
            <div style="font-weight:800;color:#C02626;font-size:.9rem;letter-spacing:.04em">YILDIZ GRUP</div>
            <div style="font-size:.68rem">Ortak Sağlık ve Güvenlik Birimleri</div>
            <div style="font-weight:800;font-size:.8rem;margin-top:8px;border-top:1px solid #1E293B;border-bottom:1px solid #1E293B;padding:3px 0">TEMEL EĞİTİM BELGESİ</div>
        </div>
        <p style="margin:10px 4px;position:relative">İşbu belge, aşağıda bilgileri yer alan katılımcının İş Sağlığı ve Güvenliği Eğitimlerinin Usul ve Esasları Hakkında Yönetmelik kapsamındaki eğitimi aldığını gösterir.</p>
        <div style="margin:0 4px;position:relative"><b>KATILIMCININ ADI :</b> {{ $kat['ad_soyad'] ?? '—' }}<br><b>GÖREVİ :</b> {{ $kat['gorev'] ?? '—' }}<br><b>İŞYERİ :</b> {{ $firmaUnvan ?? '' }}</div>
        <table style="width:calc(100% - 8px);margin:8px 4px;border-collapse:collapse;position:relative">
            @foreach ([['Belge Düzenlenme Tarihi', $tarih], ['Eğitimin Süresi', $sure ?: '—'], ['Eğitimin Türü', ($tur === 'tekrar' ? '☐ İlk Defa  ☒ Tekrar' : '☒ İlk Defa  ☐ Tekrar')], ['Eğitimin Şekli', ($sekil === 'uzaktan' ? '☒ Uzaktan  ☐ Yüz Yüze' : '☐ Uzaktan  ☒ Yüz Yüze')]] as [$a, $b])
                <tr><td style="border:1px solid #1E293B;padding:2px 4px;background:#F8FAFC;font-weight:700;width:40%">{{ $a }}</td><td style="border:1px solid #1E293B;padding:2px 4px">{{ $b }}</td></tr>
            @endforeach
        </table>
        <div style="position:absolute;left:14px;right:14px;bottom:14px;display:grid;grid-template-columns:repeat(3,1fr);gap:4px;text-align:center">
            @foreach ([['İş Güvenliği Uzmanı', 'Mehmet ÖZDEMİR'], ['İşyeri Hekimi', 'Kayıtlı hekim'], ['İşveren / Vekili', $firmaUnvan ?? '']] as [$r, $ad])
                <div style="border:1px solid #1E293B;padding:3px;height:62px"><b>{{ $r }}</b><br>{{ $ad }}</div>
            @endforeach
        </div>
    </div>
    <div style="{{ $sayfa }}">
        <div style="{{ $cerceve }}"></div>
        <div style="position:relative;padding-top:6px">
            <div style="display:flex;justify-content:space-between;font-weight:800;background:#1E293B;color:#fff;padding:3px 6px"><span>EĞİTİM KONULARI</span><span>SÜRE</span></div>
            @foreach ($bolumler as $baslik => $maddeler)
                <div style="display:flex;justify-content:space-between;font-weight:700;background:#F8FAFC;border-bottom:1px solid #cbd5e1;padding:2px 6px;margin-top:3px"><span>{{ $baslik }}</span><span>{{ $maddeler->sum('dakika') }} Dk.</span></div>
                @foreach ($maddeler as $m)
                    <div style="display:flex;justify-content:space-between;padding:0 6px"><span>{{ $m['madde'] }}</span><span>{{ $m['dakika'] }} Dk.</span></div>
                @endforeach
            @endforeach
        </div>
    </div>
</div>
<p style="font-size:.75rem;color:rgb(107 114 128);margin-top:.6rem">Bu yalnızca ekran önizlemesidir; gerçek çıktı Yıldız Grup Excel şablonundan üretilir ve kaşe/imzalar orada yer alır.</p>
