<!doctype html>
<html lang="tr">
<head>
@include('ekipman-operator._stil')
<title>{{ $ekipman->ekipman_adi }} — Günlük Kontrol</title>
</head>
<body>
@php
    $sorular = $form['sorular'] ?? [];
    $bugunYapildi = $sonKontrol && $sonKontrol->kontrol_zamani->isToday();
    $renk = $ekipman->uyariSeviyesi();
@endphp
<header class="bas">
    <div class="firma">{{ $ekipman->firma?->unvan }}</div>
    <h1>{{ $ekipman->ekipman_adi }}</h1>
    <div class="kunye">
        <span>Tip: <b>{{ $ekipman->tip ?: '—' }}</b></span>
        @if ($ekipman->seri_no)<span>Seri no: <b>{{ $ekipman->seri_no }}</b></span>@endif
        @if ($ekipman->marka_model)<span>Marka/model: <b>{{ $ekipman->marka_model }}</b></span>@endif
        @if ($ekipman->konum)<span>Konum: <b>{{ $ekipman->konum }}</b></span>@endif
        @if ($ekipman->sonraki_vize_tarihi)<span>Periyodik kontrol: <b>{{ $ekipman->sonraki_vize_tarihi->format('d.m.Y') }}</b></span>@endif
    </div>
</header>

<main class="sar">
    @if (session('kaydedildi') === 'uygun')
        <div class="serit ok" role="status"><span aria-hidden="true">✓</span><span>Kontrol kaydedildi. Makine kullanıma uygun.</span></div>
    @elseif (session('kaydedildi') === 'uygunsuz')
        <div class="serit red" role="alert"><span aria-hidden="true">✕</span><span>Kontrol kaydedildi — uygunsuzluk var.
            <small>MAKİNEYİ KULLANMAYIN. Arıza kaydı açıldı; İSG uzmanı ve bakım sorumlusu bilgilendirildi.</small></span></div>
    @elseif ($acikAriza)
        <div class="serit red" role="alert"><span aria-hidden="true">⚠</span><span>Açık arıza kaydı var — makineyi kullanmayın.
            <small>{{ $acikAriza->acilis_zamani->format('d.m.Y H:i') }} · {{ $acikAriza->baslik }}</small></span></div>
    @elseif ($renk === 'kirmizi' && $ekipman->kalanGun() < 0)
        <div class="serit red" role="alert"><span aria-hidden="true">⚠</span><span>Periyodik kontrol süresi dolmuş.
            <small>Yetkili kişi kontrol edene kadar makineyi kullanmayın; İSG uzmanına haber verin.</small></span></div>
    @elseif ($bugunYapildi)
        <div class="serit ok" role="status"><span aria-hidden="true">✓</span><span>Bugün kontrol edildi.
            <small>{{ $sonKontrol->kontrol_zamani->format('H:i') }} · {{ $sonKontrol->operator_adi }}</small></span></div>
    @endif

    <nav class="sekme" aria-label="Bölümler">
        <a href="#kontrol">Günlük kontrol</a>
        <a href="#talimat">Talimat</a>
    </nav>

    <section id="kontrol" aria-labelledby="kontrol-baslik">
        <h2 id="kontrol-baslik">Günlük kontrol @if ($form)<span class="soluk">— {{ count($sorular) }} madde</span>@endif</h2>

        @if (! $form)
            <div class="kart">Bu makine için kütüphanede günlük kontrol formu tanımlı değil. İSG uzmanınıza bildirin.</div>
        @else
            @if ($errors->any())
                <div class="hata" role="alert">{{ $errors->first() }}</div>
            @endif
            <form method="post" action="{{ route('ekipman.operator.kontrol', $ekipman->qr_anahtar) }}" id="kontrol-formu">
                @csrf
                <div class="kart">
                    <label class="etiket" for="operator">Adınız soyadınız</label>
                    <input id="operator" name="operator" type="text" autocomplete="name" maxlength="120" required
                        value="{{ old('operator', session('ekipman_operator')) }}">
                </div>

                @foreach ($sorular as $i => $soru)
                    <fieldset class="kart" style="margin-inline:0">
                        <legend class="soru" style="padding:0;float:left;width:100%"><span class="no">{{ $i + 1 }}.</span>{{ $soru }}</legend>
                        <div class="secim" style="clear:both">
                            @foreach (['U' => ['✓', 'Uygun'], 'UD' => ['✕', 'Uygun değil'], 'Y' => ['–', 'Uygulanamaz']] as $deger => [$isaret, $ad])
                                <input type="radio" id="c{{ $i }}{{ $deger }}" name="cevap[{{ $i }}]" value="{{ $deger }}" required @checked(old('cevap.'.$i) === $deger)>
                                <label for="c{{ $i }}{{ $deger }}"><span class="i" aria-hidden="true">{{ $isaret }}</span>{{ $ad }}</label>
                            @endforeach
                        </div>
                        <div class="not-alani">
                            <label class="etiket" for="n{{ $i }}">Ne sorun var?</label>
                            <input id="n{{ $i }}" name="not[{{ $i }}]" type="text" maxlength="500" value="{{ old('not.'.$i) }}" placeholder="Örn. sol fren zayıf">
                        </div>
                    </fieldset>
                @endforeach

                <div class="kart">
                    <label class="etiket" for="genel_not">Not (isteğe bağlı)</label>
                    <textarea id="genel_not" name="genel_not" rows="2" maxlength="1000">{{ old('genel_not') }}</textarea>
                </div>
                <p class="soluk">Bir madde bile "Uygun değil" ise makineyi kullanmayın; kayıt otomatik olarak bakım sorumlusuna ve İSG uzmanına iletilir.</p>
            </form>
        @endif
    </section>

    <section id="talimat" class="talimat" aria-labelledby="talimat-baslik">
        <h2 id="talimat-baslik">Güvenli çalışma talimatı</h2>
        @if (! $talimat)
            <div class="kart">Bu makine için firmanızda talimat bulunmuyor. İSG uzmanınıza bildirin.</div>
        @else
            <div class="kart">
                <strong>{{ $talimat->baslik }}</strong>
                @if ($talimat->dokuman_no)<div class="soluk">{{ $talimat->dokuman_no }}</div>@endif
                @if ($talimat->kkdler)
                    <h3>Kullanılacak KKD</h3>
                    <ul>@foreach ($talimat->kkdler as $k)<li>{{ $k }}</li>@endforeach</ul>
                @endif
                @foreach ($talimat->bolumler ?? [] as $b)
                    <h3>{{ $b['baslik'] }}</h3>
                    @if (filled($b['aciklama'] ?? null))<p>{{ $b['aciklama'] }}</p>@endif
                    @if (! empty($b['maddeler']))<ul>@foreach ($b['maddeler'] as $m)<li>{{ $m }}</li>@endforeach</ul>@endif
                @endforeach
                @if (! empty($talimat->maddeler))
                    <ol>@foreach ($talimat->maddeler as $m)<li>{{ $m }}</li>@endforeach</ol>
                @endif
                <a class="dugme ikincil" style="margin-top:16px;width:100%" href="{{ route('ekipman.operator.talimat', $ekipman->qr_anahtar) }}">Talimatı PDF olarak aç</a>
            </div>
        @endif
    </section>
</main>

@if ($form)
    <div class="alt">
        <div class="ic">
            <span class="sayac" id="sayac" aria-live="polite">0 / {{ count($sorular) }}</span>
            <button class="dugme" type="submit" form="kontrol-formu">Kontrolü kaydet</button>
        </div>
    </div>
    <script>
        (() => {
            const f = document.getElementById('kontrol-formu'), s = document.getElementById('sayac'), n = {{ count($sorular) }};
            const say = () => { s.textContent = f.querySelectorAll('input[type=radio]:checked').length + ' / ' + n; };
            f.addEventListener('change', say); say();
            // İşaretlenmemiş ilk maddeye götür (tarayıcı uyarısı sabit alt çubuğun altında kalmasın).
            f.addEventListener('invalid', (e) => { e.target.closest('.kart')?.scrollIntoView({ block: 'center' }); }, true);
        })();
    </script>
@endif
</body>
</html>
