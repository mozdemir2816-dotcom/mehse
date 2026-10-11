@php
    $cerceveStil = match ($plan->kapak_cercevesi) {
        'altin' => 'border:3px double #b8860b;',
        'mavi_zarif' => 'border:2px solid #1e3a8a;',
        'minimalist' => 'border:1px solid #111;',
        'yesil_doga' => 'border:3px solid #15803d;',
        'kirmizi_resmi' => 'border:6px solid #b91c1c;',
        'golgeli' => 'border:2px solid #333;box-shadow:4px 4px 0 #ccc;',
        default => 'border:3px double #111;',
    };

    // Hazırlayan (Md.12/1-b: adı, soyadı, unvanı): firmaya atanmış İGU, yoksa
    // hesap sahibi — RiskDegerlendirmesiUretici::hazirlayan() ile aynı öncelik.
    $hesap = $firma?->user;
    $igu = $firma?->igu;
    $hazirlayan = (object) [
        'ad' => $igu?->ad_soyad ?: $hesap?->name,
        'unvan' => $igu ? $igu->unvan : ($hesap?->unvan ? $hesap->unvanEtiketi() : null),
        'kase' => $igu?->kase_gorseli ?: $hesap?->kase_gorseli,
        'imza' => $igu?->imza_gorseli ?: $hesap?->imza_gorseli,
    ];
    $isveren = $firma?->isveren_ad;
    $isverenVekili = $firma?->isveren_vekili;
    $hekim = $firma?->isyeriHekimi;
    $u ??= array_replace(\App\Support\AcilDurumHazirlik::varsayilan(), $plan->uygulama ?? []);
    $ekipler = $plan->ekipDetaylari();
    $tahliyeGorsel = $plan->tahliyeGorselYolu();
    $durumYazi = fn (string $d, string $not) => match ($d) {
        'var' => $not !== '' ? $not : 'Var (yeri belirtilmemiş)',
        'uygulanamaz' => 'İşyerinde bulunmuyor / uygulanamaz.',
        default => 'Henüz değerlendirilmedi.',
    };
    $yenilemeYil = config('isg.acil_durum.gecerlilik_yili.'.($firma?->tehlike_sinifi ?? 'az_tehlikeli'), 6);
    $tarih = fn ($t) => $t ? \Illuminate\Support\Carbon::parse($t)->format('d.m.Y') : '—';
    $buyuk = fn (string $s) => mb_strtoupper(str_replace('i', 'İ', $s), 'UTF-8');
@endphp
<!doctype html>
<html lang="tr">
<head>
<meta charset="utf-8">
<style>
    @page { margin: 20px 20px 42px 20px; }
    * { font-family: DejaVu Sans, sans-serif; }
    body { margin: 0; color: #111; font-size: 11px; }
    .kapak { {{ $cerceveStil }} margin: 8px; padding: 60px 40px; text-align: center; page-break-after: always; }
    .kapak h1 { font-size: 26px; margin: 40px 0 8px; letter-spacing: 1px; }
    .kapak .firma { font-size: 15px; font-weight: bold; margin-top: 40px; }
    .kunye { margin: 6px 0; width: 100%; border-collapse: collapse; font-size: 11px; }
    .kunye td { border: 1px solid #999; padding: 5px 10px; text-align: left; }
    .kunye td:first-child { background: #f0f0f0; font-weight: bold; width: 34%; }
    .sayfa { padding: 10px 14px; page-break-after: always; }
    h2 { font-size: 14px; border-bottom: 2px solid #b91c1c; padding-bottom: 4px; color: #b91c1c; margin: 18px 0 6px; }
    h3 { font-size: 11.5px; margin: 10px 0 3px; }
    .metin { font-size: 10.5px; white-space: pre-line; margin: 2px 0 6px; }
    .bos { color: #999; font-style: italic; }
    .adim { margin: 6px 0; padding-left: 6px; }
    .adim b { display: inline-block; width: 22px; }
    table.ekip { width: 100%; border-collapse: collapse; margin-top: 4px; }
    table.ekip th, table.ekip td { border: 1px solid #999; padding: 4px 6px; font-size: 9.5px; text-align: left; vertical-align: top; }
    table.ekip th { background: #f0f0f0; }
    .imza { margin-top: 50px; width: 100%; }
    .imza td { width: 33.33%; text-align: center; padding-top: 40px; border-top: 1px solid #111; font-size: 10px; vertical-align: top; }
    .imza img { max-height: 40px; display: block; margin: 0 auto -32px auto; }
    .not { background: #fff7ed; border: 1px solid #fdba74; padding: 8px 10px; font-size: 9.5px; margin-top: 12px; }
    .dayanak { font-size: 8.5px; color: #777; }
</style>
</head>
<body>

{{-- KAPAK --}}
<div class="kapak">
    <div style="font-size:12px;color:#666">{{ $firma?->unvan }}</div>
    <h1>ACİL DURUM EYLEM PLANI</h1>
    <div style="font-size:12px">6331 Sayılı İş Sağlığı ve Güvenliği Kanunu — İşyerlerinde Acil Durumlar Hakkında Yönetmelik</div>
    <div class="firma">{{ $firma?->unvan }}</div>
    <div style="font-size:11px;margin-top:6px">{{ $firma?->adres }}</div>
    <div style="font-size:11px;margin-top:6px">NACE Kodu: {{ $firma?->nace_kodu ?: '—' }} &nbsp;·&nbsp; SGK Sicil No: {{ $firma?->sgk_sicil_no ?: '—' }}</div>
    <div style="font-size:11px;margin-top:30px">
        Doküman No: {{ $plan->dokuman_no }}<br>
        @if ($plan->revizyon_no)
            Rev. Tarihi / No: {{ $plan->revizyon_no }}<br>
        @endif
        Hazırlanma Tarihi: {{ $plan->rapor_tarihi?->format('d.m.Y') }}<br>
        Geçerlilik Tarihi: {{ $plan->gecerlilik_tarihi?->format('d.m.Y') }}
    </div>
    <div style="font-size:11px;margin-top:20px;color:#444">
        Hazırlayan: {{ $hazirlayan->ad ?: '—' }}@if ($hazirlayan->unvan) — {{ $hazirlayan->unvan }}@endif
    </div>
</div>

{{-- 1-4: KÜNYE, ACİL DURUMLAR, TEDBİRLER, MÜDAHALE --}}
<div class="sayfa">
    <h2 style="margin-top:0">1. İŞYERİ KÜNYESİ</h2>
    <table class="kunye">
        <tr><td>Ticari Unvan</td><td>{{ $firma?->unvan }}</td></tr>
        <tr><td>Adres</td><td>{{ $firma?->adres ?: '—' }}</td></tr>
        <tr><td>İşveren</td><td>{{ $isveren ?: '—' }}</td></tr>
        @if ($isverenVekili)
            <tr><td>İşveren Vekili</td><td>{{ $isverenVekili }}</td></tr>
        @endif
        <tr><td>SGK Sicil No</td><td>{{ $firma?->sgk_sicil_no ?: '—' }}</td></tr>
        <tr><td>NACE Kodu</td><td>{{ trim(($firma?->nace_kodu ?: '').' '.($firma?->nace_aciklama ?: '')) ?: '—' }}</td></tr>
        <tr><td>Tehlike Sınıfı</td><td>{{ $firma?->tehlikeSinifiEtiketi() }}</td></tr>
        <tr><td>Çalışan Sayısı</td><td>{{ $firma?->calisan_sayisi ?: '—' }}</td></tr>
        <tr><td>Hazırlayan</td><td>{{ $hazirlayan->ad ?: '—' }}@if ($hazirlayan->unvan) — {{ $hazirlayan->unvan }}@endif</td></tr>
        <tr><td>Hazırlanma / Geçerlilik</td><td>{{ $plan->rapor_tarihi?->format('d.m.Y') ?: '—' }} / {{ $plan->gecerlilik_tarihi?->format('d.m.Y') ?: '—' }}</td></tr>
        <tr><td>Toplanma Yeri</td><td>{{ $plan->toplanma_yeri ?: '—' }}</td></tr>
    </table>
    <div class="dayanak">Acil Durumlar Yön. Md.12(1)(a)-(c)</div>

    <h2>2. İŞYERİ İÇİN BELİRLENEN ACİL DURUMLAR</h2>
    <p class="metin">Acil durumlar; risk değerlendirmesi sonuçları ile yangın ve patlama; tehlikeli kimyasal, biyolojik, radyoaktif maddelerden kaynaklanan yayılım, zehirlenme ve salgın hastalık; doğal afet ve sabotaj ihtimalleri dikkate alınarak belirlenmiştir (Md.8).</p>
    <ol style="margin:4px 0 0;padding-left:18px;font-size:11px">
        @forelse ($plan->konuAdlari() as $konuAdi)
            <li>{{ $buyuk($konuAdi) }}</li>
        @empty
            <li style="list-style:none;color:#888">Henüz acil durum konusu seçilmedi.</li>
        @endforelse
    </ol>

    <h3>İşyerini dışarıdan etkileyebilecek işyerleri ve çevre (Md.10/7)</h3>
    @if ($plan->disaridan_etkileyebilecek_isyerleri)
        <p class="metin">{{ $plan->disaridan_etkileyebilecek_isyerleri }}</p>
    @else
        <p class="metin bos">Belirtilmedi.</p>
    @endif

    <h2>3. ÖNLEYİCİ VE SINIRLANDIRICI TEDBİRLER</h2>
    @if ($u['onleyici_tedbirler'] !== '')
        <p class="metin">{{ $u['onleyici_tedbirler'] }}</p>
    @else
        <p class="metin bos">Tedbirler yazılmadı.</p>
    @endif
    @if ($u['olcum_notu'] !== '')
        <h3>Ölçüm ve değerlendirmeler (Md.9/2)</h3>
        <p class="metin">{{ $u['olcum_notu'] }}</p>
    @endif
    <h3>Acil durum ekipmanı ve kişisel koruyucu donanım (Md.5/1-ğ)</h3>
    @if ($u['ekipman_kkd'] !== '')
        <p class="metin">{{ $u['ekipman_kkd'] }}</p>
    @else
        <p class="metin bos">Liste yazılmadı.</p>
    @endif
    <h3>Kimyasal yayılım, parlama / patlama gibi özel riskli bölümler (Md.12/1-f-6)</h3>
    <p class="metin">{{ $durumYazi($u['ozel_risk_alanlari'], $u['ozel_risk_notu']) }}</p>
    <h3>Elektrik ve gaz akışının kesim noktaları, vanalar (Md.12/1-f-7)</h3>
    <p class="metin">{{ $durumYazi($u['enerji_kesme'], $u['enerji_kesme_notu']) }}</p>
    <div class="dayanak">Acil Durumlar Yön. Md.9, Md.12(1)(d)</div>

    <h2>4. ACİL DURUM MÜDAHALE VE TAHLİYE YÖNTEMLERİ</h2>
    @if ($u['mudahale_yontemi'] !== '')
        <p class="metin">{{ $u['mudahale_yontemi'] }}</p>
    @else
        <p class="metin bos">Uyarı verme, haberleşme, ilk müdahale ve tahliye sırası yazılmadı.</p>
    @endif
    <p class="metin">Tahliye sonrası toplanma yerinde koruma ekibince sayım yapılır; işyerinde kalmış olabilecek kişiler belirlenerek ilgili ekiplere ve 112'ye bildirilir (Md.10/2). Eşya almak için geri dönülmez, asansör kullanılmaz.</p>
    <h3>Yaşlı, engelli, gebe çalışanlar ve kreşteki çocuklar için refakat (Md.10/4)</h3>
    @if ($u['ozel_destek'] !== '')
        <p class="metin">{{ $u['ozel_destek'] }}</p>
    @else
        <p class="metin bos">Refakat düzenlemesi yazılmadı.</p>
    @endif
    @php
        $kapsam = array_filter([
            $u['ziyaretci_dahil'] ? 'müşteri ve ziyaretçiler' : null,
            $u['gecici_calisan_dahil'] ? 'alt işveren ve geçici iş ilişkisiyle çalışanlar' : null,
        ]);
    @endphp
    <p class="metin">Müdahale ve tahliye yöntemleri çalışanlar dışında işyerinde bulunabilecek diğer kişiler de gözetilerek belirlenmiştir (Md.10/6){{ $kapsam ? '; plan kapsamına ayrıca '.implode(' ile ', $kapsam).' dahildir' : '' }}.{{ $u['coklu_isveren'] ? ' Çalışma alanı birden fazla işverence paylaşıldığından plan diğer işverenlerle koordineli uygulanır (Md.17).' : '' }}</p>
    <p class="metin">Senaryo bazında müdahale adımları "Acil Durum" sayfalarında verilmiştir. Yöntemler Binaların Yangından Korunması Hakkında Yönetmelik hükümleri dikkate alınarak belirlenmiştir (Md.10/5).</p>
</div>

{{-- 5-6: EKİPLER VE İRTİBAT --}}
<div class="sayfa">
    <h2 style="margin-top:0">5. ACİL DURUM EKİPLERİ VE DESTEK ELEMANLARI</h2>
    @foreach (config('isg.acil_durum.ekipler') as $anahtar => $ekipAdi)
        <h3>{{ $ekipAdi }}</h3>
        <table class="ekip">
            <tr><th style="width:24%">Adı Soyadı</th><th style="width:20%">Unvanı / Görevi</th><th style="width:20%">Sorumluluk Alanı</th><th style="width:16%">Telefon</th><th>Rol / Vardiya</th></tr>
            @forelse ($ekipler[$anahtar] ?? [] as $uye)
                <tr>
                    <td>{{ $uye['ad'] }}</td>
                    <td>{{ $uye['gorev'] }}</td>
                    <td>{{ $uye['bolum'] }}</td>
                    <td>{{ $uye['telefon'] }}</td>
                    <td>{{ implode(' · ', array_filter([$uye['lider'] ? 'Ekip başı' : null, $uye['yedek'] ? 'Yedek' : 'Asıl', $uye['vardiya']])) }}</td>
                </tr>
            @empty
                <tr><td colspan="5" class="bos">(atanmadı)</td></tr>
            @endforelse
        </table>
    @endforeach
    <div class="not">
        Söndürme, kurtarma ve koruma ekiplerinin her biri için tehlike sınıfına göre çok tehlikeli işyerlerinde her 30,
        tehlikeli işyerlerinde her 40, az tehlikeli işyerlerinde her 50 çalışana kadar uygun donanıma sahip ve özel eğitimli
        en az birer destek elemanı görevlendirilir; 10'dan az çalışanı olan işyerlerinde bu üç ekip için en az bir kişi
        yeterlidir. İlk yardım ekibi İlkyardım Yönetmeliği esaslarına göre oluşturulur. Ekipler arası koordinasyon için koruma
        ekibinden sorumlu görevlendirilir; destek elemanı ve yedekler belirlenirken vardiya düzeni dikkate alınır. Bu liste
        çalışanların görüş seviyesinde görünür şekilde asılır; ayrılma ve yer değişikliğinde yeniden görevlendirme yapılır (Md.11).
    </div>

    <h2>6. ACİL DURUMLARDA İRTİBAT KURULACAK KURULUŞLAR VE TELEFONLARI</h2>
    <table class="ekip">
        <tr><th>Kurum / Kişi</th><th style="width:18%">Telefon</th><th style="width:30%">Açıklama</th></tr>
        @foreach (config('isg.acil_durum.irtibat_telefonlari') as $kurulus => $telefon)
            <tr><td>{{ $kurulus }}</td><td>{{ $telefon }}</td><td>Ulusal acil hat</td></tr>
        @endforeach
        @foreach (collect($u['iletisim'])->reject(fn ($i) => trim((string) ($i['telefon'] ?? '')) === '112') as $i)
            <tr><td>{{ $i['ad'] ?? '' }}</td><td>{{ $i['telefon'] ?? '' }}</td><td>{{ $i['aciklama'] ?? '' }}</td></tr>
        @endforeach
    </table>
    <div class="dayanak">Acil Durumlar Yön. Md.12(1)(f)(4)-(5). Yerel irtibatlar (en yakın hastane, OSB itfaiyesi vb.) plan ekranındaki "Acil iletişim listesi"nden gelir.</div>
</div>

{{-- 7: TAHLİYE PLANI --}}
<div class="sayfa">
    <h2 style="margin-top:0">7. TAHLİYE PLANI</h2>
    @if ($tahliyeGorsel)
        <img src="{{ $tahliyeGorsel }}" style="max-width:100%;max-height:600px;margin-top:6px">
    @else
        <p class="metin bos">
            Bu sayfaya işyerinin her bölümü için hazırlanan tahliye planı eklenecektir: acil durum ve yangın söndürme
            ekipmanlarının yerleri, ilk yardım malzemelerinin yerleri, kaçış yolları, toplanma yerleri ve varsa uyarı
            sistemleri, özel riskli bölümler ile elektrik / gaz kesim noktaları (Md.12/1-f). Kroki editöründe "Kaydet"
            ile kaydedilen kroki veya "Tahliye Planı Görseli" ile yüklenen görsel buraya otomatik basılır.
        </p>
    @endif
    <p class="metin" style="margin-top:8px">Tahliye planı işyeri bina ve eklentilerinin giriş ve çıkışları ile katlarda, çalışanların görüş seviyesine uygun yükseklikte ve görünür şekilde asılır (Md.12/3).</p>
</div>

{{-- KONU SAYFALARI --}}
@php $afisler = config('isg.acil_durum.afisler'); @endphp
@foreach ($plan->konular ?? [] as $konu)
    @php
        $ad = collect(config('isg.acil_durum.konular'))->firstWhere('anahtar', $konu)['ad'] ?? $konu;
        $afis = $afisler[$konu] ?? null;
    @endphp
    <div class="sayfa">
        <h2 style="margin-top:0">ACİL DURUM: {{ $buyuk($ad) }}</h2>
        @if ($afis)
            <p style="font-size:10px;color:#555">{{ $afis['baslik'] }}</p>
            @foreach ($afis['adimlar'] as $i => $adim)
                <div class="adim"><b>{{ $i + 1 }}.</b> {{ $adim }}</div>
            @endforeach
        @else
            <p>Bu acil durum için işyerine özgü müdahale adımları, risk değerlendirmesi ve saha koşulları
            dikkate alınarak İSG uzmanı tarafından doldurulacaktır.</p>
        @endif
        <p style="font-size:10px;color:#666;margin-top:14px">
            Genel kural: Can güvenliği önceliklidir. Panik yapmadan en yakın çıkıştan tahliye edilir,
            toplanma bölgesinde sayım yapılır, gerekli hallerde 112 aranır.
        </p>
    </div>
@endforeach

{{-- 8: TATBİKAT, BİLGİLENDİRME, YENİLEME + ONAY --}}
<div class="sayfa" style="page-break-after:auto">
    <h2 style="margin-top:0">8. TATBİKAT, BİLGİLENDİRME VE PLANIN YENİLENMESİ</h2>
    <table class="kunye">
        <tr><td>Son tatbikat</td><td>{{ $tarih($u['son_tatbikat']) }}{{ $u['tutanak_ref'] !== '' ? ' — Kayıt: '.$u['tutanak_ref'] : '' }}</td></tr>
        <tr><td>Planlanan sonraki tatbikat</td><td>{{ $tarih($u['sonraki_tatbikat']) }}</td></tr>
        <tr><td>Planın en geç yenileneceği tarih</td><td>{{ $plan->gecerlilik_tarihi?->format('d.m.Y') ?: '—' }} ({{ $firma?->tehlikeSinifiEtiketi() }}: {{ $yenilemeYil }} yıl)</td></tr>
    </table>
    <p class="metin">Tatbikat en geç yılda bir yapılır; tatbikatın tarihi, görülen eksiklikler ve yapılacak düzenlemeler tatbikat formuna kaydedilir ve plan bu sonuçlara göre gözden geçirilir (Md.13). İşyerinde veya yakın çevresinde acil durumları etkileyebilecek değişikliklerde plan süre beklenmeden tamamen veya kısmen yenilenir (Md.14).</p>
    <p class="metin">Tüm çalışanlar acil durum planı ile söndürme, kurtarma, koruma ve ilk yardım ekiplerindeki destek elemanları hakkında bilgilendirilir; işe yeni başlayan ve geçici çalışanlara ayrıca bilgilendirme yapılır. Destek elemanları görevlerine özgü olarak eğitilir ve eğitimler belgelendirilir (Md.15). Alt işveren ve geçici çalışanlar, müşteri ve ziyaretçiler de acil durumlar, tahliye planı, kaçış yolları, toplanma yerleri ve acil durum ekipleri hakkında bilgilendirilir (Md.5/1-g).</p>

    <h2>ONAY</h2>
    <p class="metin">Bu plan işveren onayına sunulmuştur. Sayfaları numaralandırılmış olup hazırlayan tarafından her sayfası paraflanır, son sayfası imzalanır; plan acil durum ekiplerinin kolayca ulaşabileceği yerde saklanır (Md.12/2).</p>
    <table class="imza">
        <tr>
            <td>
                @if (($imzali ?? true) && $hazirlayan->kase)<img src="{{ storage_path('app/public/'.$hazirlayan->kase) }}">@endif
                @if (($imzali ?? true) && $hazirlayan->imza)<img src="{{ storage_path('app/public/'.$hazirlayan->imza) }}">@endif
                <b>Hazırlayan</b><br>{{ $hazirlayan->ad }}
                @if ($hazirlayan->unvan) <br><span style="font-weight:normal">{{ $hazirlayan->unvan }}</span> @endif
                <br>(İmza – Kaşe)
            </td>
            <td>
                @if (($imzali ?? true) && $hekim?->kase_gorseli)<img src="{{ storage_path('app/public/'.$hekim->kase_gorseli) }}">@endif
                @if (($imzali ?? true) && $hekim?->imza_gorseli)<img src="{{ storage_path('app/public/'.$hekim->imza_gorseli) }}">@endif
                <b>İşyeri Hekimi</b>
                <br>(İmza – Kaşe)
            </td>
            <td>
                <b>Onaylayan — İşveren / İşveren Vekili</b>
                @if ($isveren || $isverenVekili) <br>{{ $isveren ?: $isverenVekili }} @endif
                <br>(İmza – Kaşe – Tarih)
            </td>
        </tr>
    </table>
    <div class="not">
        {{ $hakkinda }}
    </div>
</div>

</body>
</html>
