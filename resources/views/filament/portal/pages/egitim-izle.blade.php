@php
    $a = $this->atamaKaydi();
    $aktif = $a?->paket->dersler->firstWhere('id', $aktifDersId);
    $izlenen = $this->izlenenDersIdler;
    $aktifIlerleme = $aktif ? $a->ilerlemeler->firstWhere('egitim_dersi_id', $aktif->id) : null;
    $kalanHak = $a?->kalanSinavHakki();
    // Peş peşe oynatma (08.11.2026): video bitince sıradaki video (aynı konunun 3.2'si ya da sonraki konu) kendiliğinden açılır.
    $sonraki = null;
    if ($aktif) {
        $dizi = $a->paket->dersler->values();
        $sonraki = $dizi->get($dizi->search(fn ($d) => $d->id === $aktif->id) + 1);
    }
    // Kalan zorunlu süre (08.11.2026): izlenmemiş derslerin zorunlu oranına kalan saniye toplamı.
    $kalanSn = 0;
    $suresizDers = 0;
    if ($a) {
        foreach ($a->paket->dersler as $d) {
            $il = $a->ilerlemeler->firstWhere('egitim_dersi_id', $d->id);
            if ($il?->izlendi) {
                continue;
            }
            if (! $d->sure_sn) {
                $suresizDers++;
                continue;
            }
            $kalanSn += max(0, (int) ceil($d->sure_sn * $a->paket->video_zorunlu_yuzde / 100) - (int) ($il?->izlenen_sn ?? 0));
        }
    }
    $sureYaz = fn (int $sn) => sprintf('%d:%02d:%02d', intdiv($sn, 3600), intdiv($sn % 3600, 60), $sn % 60);
@endphp

<x-filament-panels::page>
    @if (! $a)
        <div class="portal-card portal-card-pad">Eğitim bulunamadı.</div>
    @else
        <div style="margin-top:-.5rem">
            <a href="{{ \App\Filament\Portal\Pages\Egitimlerim::getUrl() }}" class="portal-muted" style="font-size:.85rem;display:inline-flex;align-items:center;gap:.3rem;text-decoration:none">
                &larr; Eğitimlerim
            </a>
            <h2 class="portal-heading" style="font-size:1.2rem;font-weight:700;margin:.4rem 0 0">{{ $a->paket->ad }}</h2>
        </div>

        {{-- ================= ÖN TEST (seviye tespiti) ================= --}}
        @if ($mod === 'on_test')
            <div class="portal-card portal-card-pad" style="max-width:760px">
                <div class="portal-heading" style="font-weight:700;margin-bottom:.3rem">Seviye Tespit Testi — {{ count($sinavSorulari) }} soru</div>
                <div class="portal-muted" style="font-size:.85rem;margin-bottom:1.1rem;line-height:1.5">
                    Eğitime başlamadan önce bilgi seviyenizi ölçmek için kısa bir test. Puanınız geçme / kalma için
                    kullanılmaz; yalnız kayıt altına alınır. Bilmediğiniz soruda tahmin edebilirsiniz.
                </div>

                @foreach ($sinavSorulari as $no => $s)
                    <div style="margin-bottom:1.2rem">
                        <div class="portal-heading" style="font-weight:600;font-size:.95rem">{{ $no + 1 }}. {{ $s['soru'] }}</div>
                        <div style="margin-top:.5rem;display:flex;flex-direction:column;gap:.15rem">
                            @foreach ($s['secenekler'] as $si => $secenek)
                                <label style="display:flex;gap:.6rem;align-items:flex-start;font-size:.9rem;cursor:pointer;padding:.5rem;border-radius:10px">
                                    <input type="radio" wire:model="cevaplar.{{ $s['id'] }}" value="{{ $si }}" style="margin-top:.2rem">
                                    <span>{{ chr(65 + $si) }}) {{ $secenek }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>
                @endforeach

                <x-filament::button wire:click="onTestiGonder" style="width:100%;justify-content:center">Testi Gönder ve Eğitime Başla</x-filament::button>
            </div>

        {{-- ================= İZLE ================= --}}
        @elseif ($mod === 'izle')
            @if ($a->yeniden_baslatma > 0 && $izlenen === [])
                <div class="portal-card portal-card-pad" style="margin-bottom:1rem;box-shadow:inset 0 0 0 1px var(--p-warning-border);background:var(--p-warning-bg);font-size:.88rem">
                    Sınav hakkınız dolduğu için eğitim yeniden başlatıldı. Dersleri baştan izleyip sınava tekrar girebilirsiniz.
                </div>
            @endif

            <style>
                /* Mobil (08.11.2026): video üstte sabit, ders listesi altta açılır-kapanır çekmece. */
                .portal-ders-ac { display: none; }
                @media (max-width: 900px) {
                    .portal-video-sabit { position: sticky; top: 0; z-index: 35; }
                    .portal-ders-listesi { position: static !important; max-height: none !important; }
                    .portal-ders-ac { display: inline-flex; }
                }
                #oynatici-cerceve:fullscreen { background: #000; display: flex; flex-direction: column; justify-content: center; }
            </style>

            <div class="portal-izle-grid">
                {{-- Video --}}
                <div>
                    @if ($aktif)
                        <div class="portal-card" style="overflow:hidden">
                            <div wire:ignore id="oynatici-cerceve" class="portal-video-sabit" style="background:#000">
                            <div id="oynatici-kutu" style="position:relative;padding-top:56.25%;background:#000">
                                <iframe id="oynatici"
                                    style="position:absolute;inset:0;width:100%;height:100%;border:0"
                                    src="{{ $aktif->gommeUrl() }}{{ $aktif->saglayici === 'youtube' ? '&enablejsapi=1&disablekb=1&playsinline=1&controls=0&fs=0&iv_load_policy=3' : '' }}"
                                    allow="accelerometer; autoplay; encrypted-media; gyroscope; picture-in-picture"
                                    allowfullscreen></iframe>

                                @if ($aktif->saglayici === 'youtube')
                                    {{-- YouTube'un kendi çubuğu gizli (controls=0): ileri sarma çubuğu yok. Video alanına tıklama yalnız oynat/duraklat. --}}
                                    <div id="tikla" style="position:absolute;inset:0;z-index:3;cursor:pointer"></div>
                                @endif

                                {{-- Aktif katılım penceresi (Md.12/5-c) --}}
                                <div id="yoklama" style="display:none;position:absolute;inset:0;background:rgb(0 0 0 / .82);color:#fff;padding:1.2rem;overflow:auto;z-index:5">
                                    <div style="max-width:560px;margin:0 auto">
                                        <div style="font-size:.75rem;letter-spacing:.06em;text-transform:uppercase;opacity:.75">Devam ediyor musunuz?</div>
                                        <div id="yoklama-soru" style="font-weight:700;font-size:1rem;margin:.5rem 0 .8rem;line-height:1.4"></div>
                                        <div id="yoklama-secenekler" style="display:flex;flex-direction:column;gap:.4rem"></div>
                                        <div style="font-size:.75rem;opacity:.7;margin-top:.8rem">Cevaplayınca video kaldığı yerden devam eder.</div>
                                    </div>
                                </div>

                                {{-- Sıradaki videoya geçiş (peş peşe oynatma) --}}
                                <div id="sonraki-kutu" style="display:none;position:absolute;inset:0;background:rgb(0 0 0 / .85);color:#fff;z-index:6;align-items:center;justify-content:center;text-align:center;padding:1.2rem">
                                    <div>
                                        <div style="font-size:.75rem;letter-spacing:.06em;text-transform:uppercase;opacity:.75">Sıradaki video</div>
                                        <div style="font-weight:700;font-size:1.05rem;margin:.5rem 0 1rem">{{ $sonraki?->numaraliBaslik() }}</div>
                                        <button type="button" id="sonraki-git" style="padding:.6rem 1.2rem;border-radius:.6rem;border:0;background:#2563eb;color:#fff;font-weight:600;cursor:pointer">▶ Şimdi başlat (<span id="sonraki-sayac">5</span>)</button>
                                    </div>
                                </div>

                                {{-- Uyarı şeridi (ileri sarma / sekme) --}}
                                <div id="izleme-uyari" style="display:none;position:absolute;left:0;right:0;bottom:0;background:rgb(180 83 9 / .92);color:#fff;font-size:.82rem;padding:.45rem .8rem;z-index:4"></div>
                            </div>
                            @if ($aktif->saglayici === 'youtube')
                                <div id="kontrol" style="height:44px;background:#111;display:flex;align-items:center;gap:.7rem;padding:0 .8rem;color:#fff;font-size:.8rem">
                                    <button type="button" id="oynat" style="background:none;border:0;color:#fff;font-size:1.15rem;cursor:pointer;width:1.6rem" aria-label="Oynat / duraklat">▶</button>
                                    <div style="flex:1;height:6px;background:rgb(255 255 255 / .25);border-radius:3px;pointer-events:none"><div id="ilerleme-dolu" style="height:100%;width:0;background:#60a5fa;border-radius:3px"></div></div>
                                    <span id="sure-yazi" style="font-variant-numeric:tabular-nums;white-space:nowrap">0:00 / 0:00</span>
                                    <button type="button" id="tamekran" style="background:none;border:0;color:#fff;font-size:1rem;cursor:pointer" aria-label="Tam ekran">⛶</button>
                                </div>
                            @endif
                            {{-- Kalan zorunlu süre: tüm derslerin zorunlu izleme oranına kalan süre; oynarken geri sayar. --}}
                            <div style="background:#1e293b;color:#e2e8f0;font-size:.78rem;padding:.35rem .8rem;display:flex;justify-content:space-between;gap:.5rem;flex-wrap:wrap">
                                <span>Kalan zorunlu süre: <strong id="kalan-sure" style="font-variant-numeric:tabular-nums">{{ $sureYaz($kalanSn) }}</strong></span>
                                @if ($suresizDers)<span style="opacity:.75">{{ $suresizDers }} videonun süresi ilk izlemede eklenecek</span>@endif
                            </div>
                            </div>
                            <div class="portal-card-pad">
                                <div class="portal-heading" style="font-weight:700">{{ $aktif->numaraliBaslik() }}</div>
                                @if ($aktif->aciklama)<div class="portal-muted" style="font-size:.85rem;margin-top:.35rem;line-height:1.5">{{ $aktif->aciklama }}</div>@endif

                                <div style="margin-top:.8rem;font-size:.85rem">
                                    @if (in_array($aktif->id, $izlenen))
                                        <span class="portal-badge portal-badge-success">✓ Bu ders izlendi</span>
                                    @elseif ($aktif->saglayici === 'youtube')
                                        <span wire:ignore id="izleme-durum" class="portal-muted">Videoyu başlatın — en az %{{ $a->paket->video_zorunlu_yuzde }} izlenmeli. Videoyu ileri sarma yoktur; sekme ya da uygulama değiştirirseniz video durur.</span>
                                    @else
                                        <x-filament::button size="sm" color="gray" wire:click="dersTamamla({{ $aktif->id }})">Videoyu izledim</x-filament::button>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endif
                </div>

                {{-- Ders listesi + sınav --}}
                <div class="portal-card portal-ders-listesi" style="padding:.7rem;position:sticky;top:1rem;max-height:calc(100dvh - 2rem);display:flex;flex-direction:column"
                    x-data="{ acik: window.matchMedia('(min-width: 901px)').matches }">
                    <div class="portal-label" style="font-size:.75rem;font-weight:700;text-transform:uppercase;letter-spacing:.05em;padding:.5rem .65rem;flex-shrink:0;display:flex;justify-content:space-between;align-items:center;gap:.5rem">
                        <span>Dersler ({{ count($izlenen) }}/{{ $a->paket->dersler->count() }} izlendi)</span>
                        <button type="button" class="portal-ders-ac" x-on:click="acik = ! acik" style="background:none;border:1px solid var(--p-border-light);border-radius:.5rem;padding:.25rem .6rem;cursor:pointer;font-size:.75rem;color:inherit" x-text="acik ? 'Listeyi gizle ▲' : 'Listeyi aç ▼'">Listeyi aç ▼</button>
                    </div>
                    <div x-show="acik" x-cloak style="display:flex;flex-direction:column;gap:.15rem;overflow-y:auto;min-height:0">
                        @php $oncekiModul = '__yok__'; @endphp
                        @foreach ($a->paket->dersler as $i => $d)
                            @php
                                $tamamlandi = in_array($d->id, $izlenen);
                                $numClass = $tamamlandi ? 'portal-lesson-done' : ($d->id === $aktifDersId ? 'portal-lesson-current' : '');
                            @endphp
                            @if ($d->modul !== $oncekiModul)
                                @php
                                    $oncekiModul = $d->modul;
                                    $grup = $a->paket->dersler->where('modul', $d->modul);
                                    $grupBiten = $grup->whereIn('id', $izlenen)->count();
                                @endphp
                                <div class="portal-label" style="font-size:.75rem;font-weight:700;padding:.55rem .65rem .2rem;display:flex;justify-content:space-between;gap:.5rem">
                                    <span>{{ $d->modul ? $d->modul.'. '.\App\Models\EgitimDersi::MODULLER[$d->modul] : 'Diğer dersler' }}</span>
                                    <span>{{ $grupBiten }}/{{ $grup->count() }} · %{{ (int) round($grupBiten / max(1, $grup->count()) * 100) }}</span>
                                </div>
                            @endif
                            <button type="button" wire:click="dersSec({{ $d->id }})"
                                class="portal-lesson-btn {{ $d->id === $aktifDersId ? 'portal-lesson-active' : '' }}">
                                <span class="portal-lesson-num {{ $numClass }}">{{ $tamamlandi ? '✓' : $i + 1 }}</span>
                                <span>{{ $d->numaraliBaslik() }}</span>
                            </button>
                        @endforeach
                    </div>

                    <div style="border-top:1px solid var(--p-border-light);margin-top:.6rem;padding:.85rem .65rem .3rem">
                        @if ($a->tumDerslerIzlendiMi())
                            <x-filament::button size="sm" wire:click="sinavaBasla" style="width:100%;justify-content:center">Final Sınavına Gir</x-filament::button>
                            <div class="portal-muted" style="font-size:.75rem;margin-top:.4rem;text-align:center">Kalan sınav hakkı: {{ $kalanHak }}</div>
                        @else
                            <div class="portal-muted" style="font-size:.8rem">Sınav için tüm dersleri izleyin ({{ $a->izlenenDersSayisi() }}/{{ $a->toplamDersSayisi() }}).</div>
                        @endif
                    </div>
                </div>
            </div>

            @if ($aktif && $aktif->saglayici === 'youtube')
                <script src="https://www.youtube.com/iframe_api"></script>
                <script>
                    (function () {
                        const dersId = {{ $aktif->id }};
                        const zorunlu = {{ (int) $a->paket->video_zorunlu_yuzde }};
                        const yoklamaSn = {{ (int) config('isg.uzaktan_egitim.yoklama_dk', 5) * 60 }};
                        const sorular = @json($this->yoklamaSorulari);
                        const bitti = {{ in_array($aktif->id, $izlenen) ? 'true' : 'false' }};
                        let izlenen = {{ (int) ($aktifIlerleme?->izlenen_sn ?? 0) }};
                        let enIleri = {{ (int) ($aktifIlerleme?->son_konum_sn ?? 0) }};
                        const sonrakiUrl = @json($sonraki ? \App\Filament\Portal\Pages\EgitimIzle::getUrl(['atama' => $a->id]).'?ders='.$sonraki->id.'&oto=1' : null);
                        const otomatik = new URLSearchParams(location.search).get('oto') === '1';
                        let artis = 0, yoklamaSayac = 0, yoklamaAcik = false, soruNo = 0, player = null, gecisBasladi = false;
                        let kalanSn = {{ (int) $kalanSn }};
                        const dersSure = {{ (int) ($aktif->sure_sn ?? 0) }};
                        function kalanYaz() {
                            const el = document.getElementById('kalan-sure'); if (!el) return;
                            const s = Math.max(0, kalanSn);
                            el.textContent = Math.floor(s / 3600) + ':' + String(Math.floor(s % 3600 / 60)).padStart(2, '0') + ':' + String(s % 60).padStart(2, '0');
                        }

                        const $ = (id) => document.getElementById(id);
                        function uyari(metin) {
                            const el = $('izleme-uyari'); if (!el) return;
                            el.textContent = metin; el.style.display = 'block';
                            clearTimeout(uyari.t); uyari.t = setTimeout(() => el.style.display = 'none', 4000);
                        }
                        function durumYaz() {
                            const el = $('izleme-durum'); if (!el || !player || !player.getDuration) return;
                            const d = player.getDuration(); if (!d) return;
                            const y = Math.min(100, Math.floor(izlenen / d * 100));
                            el.textContent = 'İzlenen süre: %' + y + (y >= zorunlu ? ' — tamamlandı ✓' : ' (en az %' + zorunlu + ')');
                        }
                        function rapor() {
                            if (!player || !player.getDuration || artis < 1) return Promise.resolve();
                            const n = artis; artis = 0;
                            return @this.call('dersIlerleme', dersId, Math.floor(enIleri), n, Math.floor(player.getDuration() || 0));
                        }
                        /* Video bitti: süre kaydedilir, 5 sn sonra sıradaki video açılır ve kendiliğinden başlar. */
                        function sonrakineGec() {
                            if (!sonrakiUrl) { rapor(); return; }
                            if (gecisBasladi) return;
                            gecisBasladi = true;
                            const git = () => { window.removeEventListener('pagehide', rapor); location.href = sonrakiUrl; };
                            const kayit = Promise.resolve(rapor()).catch(() => {});
                            $('sonraki-kutu').style.display = 'flex';
                            let kalan = 5;
                            const say = setInterval(() => {
                                if (--kalan <= 0) { clearInterval(say); kayit.then(git); }
                                if ($('sonraki-sayac')) $('sonraki-sayac').textContent = Math.max(0, kalan);
                            }, 1000);
                            $('sonraki-git').onclick = () => { clearInterval(say); kayit.then(git); };
                        }
                        function yoklamaAc() {
                            if (!sorular.length) { yoklamaSayac = 0; return; }
                            const s = sorular[soruNo++ % sorular.length];
                            yoklamaAcik = true; player.pauseVideo();
                            $('yoklama-soru').textContent = s.soru;
                            const kutu = $('yoklama-secenekler'); kutu.innerHTML = '';
                            s.secenekler.forEach((metin, i) => {
                                const b = document.createElement('button');
                                b.type = 'button';
                                b.textContent = String.fromCharCode(65 + i) + ') ' + metin;
                                b.style.cssText = 'text-align:left;padding:.55rem .75rem;border-radius:.5rem;border:1px solid rgb(255 255 255 / .35);background:rgb(255 255 255 / .08);color:#fff;cursor:pointer;font-size:.88rem';
                                b.onclick = () => {
                                    @this.call('yoklamaCevap', s.id, i);
                                    $('yoklama').style.display = 'none';
                                    yoklamaAcik = false; yoklamaSayac = 0; player.playVideo();
                                };
                                kutu.appendChild(b);
                            });
                            $('yoklama').style.display = 'block';
                        }
                        function dk(sn) { sn = Math.max(0, Math.floor(sn || 0)); return Math.floor(sn / 60) + ':' + String(sn % 60).padStart(2, '0'); }
                        /* İleri sarma denetimi: saniyenin dörtte biri, oynuyor/duraklıyor/yükleniyor fark etmez.
                           En ileri izlenen konumun 1,5 sn ötesine geçen her konum geri alınır. */
                        function kontrol() {
                            if (!player || !player.getCurrentTime || yoklamaAcik) return;
                            const t = player.getCurrentTime();
                            if (t > enIleri + 1.5) {
                                player.seekTo(enIleri, true);
                                uyari('İleri sarma yapılamaz — video izlediğiniz yerden devam ediyor.');
                                return;
                            }
                            if (player.getPlayerState() === YT.PlayerState.PLAYING) enIleri = Math.max(enIleri, t);
                            const d = player.getDuration() || 0;
                            if ($('ilerleme-dolu')) $('ilerleme-dolu').style.width = (d ? Math.min(100, t / d * 100) : 0) + '%';
                            if ($('sure-yazi')) $('sure-yazi').textContent = dk(t) + ' / ' + dk(d);
                        }
                        function tik() {
                            if (!player || !player.getPlayerState || yoklamaAcik) return;
                            if (player.getPlayerState() !== YT.PlayerState.PLAYING) return;
                            if (document.visibilityState !== 'visible') { player.pauseVideo(); return; }
                            const gereken = Math.ceil((dersSure || player.getDuration() || 0) * zorunlu / 100);
                            if (!bitti && dersSure && izlenen < gereken) { kalanSn--; kalanYaz(); }
                            izlenen++; artis++;
                            if (!bitti && ++yoklamaSayac >= yoklamaSn) yoklamaAc();
                            durumYaz();
                            if (artis >= 15) rapor();
                        }
                        function oynatDuraklat() {
                            if (!player || yoklamaAcik) return;
                            player.getPlayerState() === YT.PlayerState.PLAYING ? player.pauseVideo() : player.playVideo();
                        }
                        function baslat() {
                            if (!window.YT || !window.YT.Player) { return setTimeout(baslat, 300); }
                            player = new YT.Player('oynatici', {
                                events: {
                                    onReady: () => {
                                        if (enIleri > 5) player.seekTo(Math.max(0, enIleri - 2), true);
                                        durumYaz();
                                        if (otomatik) {
                                            player.playVideo();
                                            /* Tarayıcı sesli otomatik oynatmayı engellerse çalışan ▶ ile başlatır. */
                                            setTimeout(() => { if (player.getPlayerState() !== YT.PlayerState.PLAYING) uyari('Sıradaki video hazır — başlatmak için ▶ düğmesine basın.'); }, 2000);
                                        }
                                    },
                                    onStateChange: (e) => {
                                        if (e.data === YT.PlayerState.ENDED) sonrakineGec();
                                        else if (e.data === YT.PlayerState.PAUSED) rapor();
                                        if ($('oynat')) $('oynat').textContent = e.data === YT.PlayerState.PLAYING ? '⏸' : '▶';
                                    },
                                    onPlaybackRateChange: () => { if (player.getPlaybackRate() !== 1) { player.setPlaybackRate(1); uyari('Eğitim videoları normal hızda izlenir.'); } },
                                }
                            });
                            setInterval(tik, 1000);
                            setInterval(kontrol, 250);
                            if ($('tikla')) $('tikla').addEventListener('click', oynatDuraklat);
                            if ($('oynat')) $('oynat').addEventListener('click', oynatDuraklat);
                            if ($('tamekran')) $('tamekran').addEventListener('click', () => {
                                const k = $('oynatici-cerceve');
                                document.fullscreenElement ? document.exitFullscreen() : (k.requestFullscreen && k.requestFullscreen());
                            });
                            setInterval(() => { if (document.visibilityState === 'visible') @this.call('nabiz'); }, 60000);
                        }
                        document.addEventListener('visibilitychange', () => {
                            if (document.visibilityState !== 'visible' && player && player.pauseVideo) {
                                player.pauseVideo(); rapor();
                            } else if (player) {
                                uyari('Başka sekmeye / pencereye geçtiğiniz için video durduruldu. Devam etmek için oynatın.');
                            }
                        });
                        window.addEventListener('pagehide', rapor);
                        /* Başka uygulamaya / pencereye geçilince (sekme değişmeden) de video durur. */
                        window.addEventListener('blur', () => {
                            if (player && player.getPlayerState && player.getPlayerState() === YT.PlayerState.PLAYING) {
                                player.pauseVideo(); rapor();
                                uyari('Pencereden ayrıldığınız için video durduruldu. Devam etmek için oynatın.');
                            }
                        });

                        if (window.YT && window.YT.Player) baslat();
                        else window.onYouTubeIframeAPIReady = baslat;
                    })();
                </script>
            @endif

        {{-- ================= SINAV ================= --}}
        @elseif ($mod === 'sinav')
            <div class="portal-card portal-card-pad" style="max-width:760px">
                <div class="portal-heading" style="font-weight:700;margin-bottom:.3rem">Final Sınavı — {{ count($sinavSorulari) }} soru</div>
                <div class="portal-muted" style="font-size:.85rem;margin-bottom:1.1rem">
                    Geçmek için en az {{ $a->gecmePuani() }} puan gerekir. Bu sınavla birlikte kalan hakkınız: {{ $kalanHak }}.
                </div>

                @foreach ($sinavSorulari as $no => $s)
                    <div style="margin-bottom:1.4rem">
                        <div class="portal-heading" style="font-weight:600;font-size:.95rem">{{ $no + 1 }}. {{ $s['soru'] }}</div>
                        <div style="margin-top:.5rem;display:flex;flex-direction:column;gap:.15rem">
                            @foreach ($s['secenekler'] as $si => $secenek)
                                <label style="display:flex;gap:.6rem;align-items:flex-start;font-size:.9rem;cursor:pointer;padding:.55rem .5rem;border-radius:10px" onmouseover="this.style.background='var(--p-border-light)'" onmouseout="this.style.background=''">
                                    <input type="radio" wire:model="cevaplar.{{ $s['id'] }}" value="{{ $si }}" style="margin-top:.2rem">
                                    <span>{{ chr(65 + $si) }}) {{ $secenek }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>
                @endforeach

                <x-filament::button wire:click="sinaviGonder" wire:confirm="Sınavı bitir ve gönder?" style="width:100%;justify-content:center">Sınavı Gönder</x-filament::button>
            </div>

        {{-- ================= SONUÇ ================= --}}
        @elseif ($mod === 'sonuc' && $sonSonuc)
            @php $g = $sonSonuc->gecti; @endphp
            <div class="portal-card" style="padding:1.75rem 1.5rem;max-width:620px;text-align:center;box-shadow:inset 0 0 0 1px {{ $g ? 'var(--p-success-border)' : 'var(--p-danger-border)' }}">
                <div style="font-size:2.4rem">{{ $g ? '🎓' : '❌' }}</div>
                <div class="portal-heading" style="font-size:1.25rem;font-weight:700;margin-top:.4rem">
                    {{ $g ? 'Eğitimi başarıyla tamamladınız' : 'Sınavı geçemediniz' }}
                </div>
                <div class="portal-muted" style="margin-top:.4rem">
                    Puanınız: <strong class="portal-heading">{{ $sonSonuc->puan }}</strong> ({{ $sonSonuc->dogruSayisi() }}/{{ $sonSonuc->toplamSoru() }} doğru) ·
                    Geçme: {{ $a->gecmePuani() }}
                </div>
                @unless ($g)
                    <div class="portal-muted" style="margin-top:.4rem;font-size:.85rem">
                        @if ($a->yeniden_baslatma > 0 && $izlenen === [])
                            Üç sınav hakkınız doldu; yönetmelik gereği eğitim baştan başlatıldı.
                        @else
                            Kalan sınav hakkınız: {{ $kalanHak }}
                        @endif
                    </div>
                @endunless

                <div style="margin-top:1.3rem;display:flex;gap:.6rem;justify-content:center;flex-wrap:wrap">
                    @if ($g && $a->onayBekliyorMu())
                        <div class="portal-muted" style="font-size:.9rem;max-width:30rem">
                            Sınavı geçtiniz.
                            @if ($a->dorduncuKonuYuzYuzeMi() && $a->iguOnayiBekliyorMu())
                                İşyerinizin tehlike sınıfında 4. konu (işe ve işyerine özgü riskler) yüz yüze uygulamalı verilir.
                            @endif
                            Belgeniz, iş güvenliği uzmanı ve işyeri hekimi onayladıktan sonra buradan indirilebilir olacak.
                        </div>
                    @elseif ($g)
                        <x-filament::button wire:click="belgeIndir" icon="heroicon-o-arrow-down-tray">Temel Eğitim Belgesini İndir</x-filament::button>
                    @else
                        <x-filament::button color="gray" wire:click="tekrarDene">Dersleri Tekrar İzle / Yeniden Dene</x-filament::button>
                    @endif
                    <x-filament::button tag="a" color="gray" :href="\App\Filament\Portal\Pages\Egitimlerim::getUrl()">Eğitimlerim</x-filament::button>
                </div>
            </div>
        @endif
    @endif
</x-filament-panels::page>
