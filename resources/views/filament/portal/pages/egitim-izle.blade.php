@php
    $a = $this->atamaKaydi();
    $aktif = $a?->paket->dersler->firstWhere('id', $aktifDersId);
    $izlenen = $this->izlenenDersIdler;
    $aktifIlerleme = $aktif ? $a->ilerlemeler->firstWhere('egitim_dersi_id', $aktif->id) : null;
    $kalanHak = $a?->kalanSinavHakki();
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

            <div class="portal-izle-grid">
                {{-- Video --}}
                <div>
                    @if ($aktif)
                        <div class="portal-card" style="overflow:hidden">
                            <div wire:ignore style="position:relative;padding-top:56.25%;background:#000">
                                <iframe id="oynatici"
                                    style="position:absolute;inset:0;width:100%;height:100%;border:0"
                                    src="{{ $aktif->gommeUrl() }}{{ $aktif->saglayici === 'youtube' ? '&enablejsapi=1&disablekb=1&playsinline=1' : '' }}"
                                    allow="accelerometer; autoplay; encrypted-media; gyroscope; picture-in-picture"
                                    allowfullscreen></iframe>

                                {{-- Aktif katılım penceresi (Md.12/5-c) --}}
                                <div id="yoklama" style="display:none;position:absolute;inset:0;background:rgb(0 0 0 / .82);color:#fff;padding:1.2rem;overflow:auto;z-index:5">
                                    <div style="max-width:560px;margin:0 auto">
                                        <div style="font-size:.75rem;letter-spacing:.06em;text-transform:uppercase;opacity:.75">Devam ediyor musunuz?</div>
                                        <div id="yoklama-soru" style="font-weight:700;font-size:1rem;margin:.5rem 0 .8rem;line-height:1.4"></div>
                                        <div id="yoklama-secenekler" style="display:flex;flex-direction:column;gap:.4rem"></div>
                                        <div style="font-size:.75rem;opacity:.7;margin-top:.8rem">Cevaplayınca video kaldığı yerden devam eder.</div>
                                    </div>
                                </div>

                                {{-- Uyarı şeridi (ileri sarma / sekme) --}}
                                <div id="izleme-uyari" style="display:none;position:absolute;left:0;right:0;bottom:0;background:rgb(180 83 9 / .92);color:#fff;font-size:.82rem;padding:.45rem .8rem;z-index:4"></div>
                            </div>
                            <div class="portal-card-pad">
                                <div class="portal-heading" style="font-weight:700">{{ $aktif->baslik }}</div>
                                @if ($aktif->aciklama)<div class="portal-muted" style="font-size:.85rem;margin-top:.35rem;line-height:1.5">{{ $aktif->aciklama }}</div>@endif

                                <div style="margin-top:.8rem;font-size:.85rem">
                                    @if (in_array($aktif->id, $izlenen))
                                        <span class="portal-badge portal-badge-success">✓ Bu ders izlendi</span>
                                    @elseif ($aktif->saglayici === 'youtube')
                                        <span wire:ignore id="izleme-durum" class="portal-muted">Videoyu başlatın — en az %{{ $a->paket->video_zorunlu_yuzde }} izlenmeli. İleri sarılamaz; sekme değiştirirseniz video durur.</span>
                                    @else
                                        <x-filament::button size="sm" color="gray" wire:click="dersTamamla({{ $aktif->id }})">Videoyu izledim</x-filament::button>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endif
                </div>

                {{-- Ders listesi + sınav --}}
                <div class="portal-card" style="padding:.7rem;position:sticky;top:1rem;max-height:calc(100dvh - 2rem);display:flex;flex-direction:column">
                    <div class="portal-label" style="font-size:.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.05em;padding:.5rem .65rem;flex-shrink:0">Dersler ({{ $a->paket->dersler->count() }})</div>
                    <div style="display:flex;flex-direction:column;gap:.15rem;overflow-y:auto;min-height:0">
                        @foreach ($a->paket->dersler as $i => $d)
                            @php
                                $tamamlandi = in_array($d->id, $izlenen);
                                $numClass = $tamamlandi ? 'portal-lesson-done' : ($d->id === $aktifDersId ? 'portal-lesson-current' : '');
                            @endphp
                            <button type="button" wire:click="dersSec({{ $d->id }})"
                                class="portal-lesson-btn {{ $d->id === $aktifDersId ? 'portal-lesson-active' : '' }}">
                                <span class="portal-lesson-num {{ $numClass }}">{{ $tamamlandi ? '✓' : $i + 1 }}</span>
                                <span>{{ $d->baslik }}</span>
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
                        let artis = 0, yoklamaSayac = 0, yoklamaAcik = false, soruNo = 0, player = null;

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
                            if (!player || !player.getDuration || artis < 1) return;
                            const n = artis; artis = 0;
                            @this.call('dersIlerleme', dersId, Math.floor(enIleri), n, Math.floor(player.getDuration() || 0));
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
                        function tik() {
                            if (!player || !player.getPlayerState || yoklamaAcik) return;
                            if (player.getPlayerState() !== YT.PlayerState.PLAYING) return;
                            if (document.visibilityState !== 'visible') { player.pauseVideo(); return; }
                            const t = player.getCurrentTime();
                            if (t > enIleri + 3) {
                                player.seekTo(enIleri, true);
                                uyari('İleri sarma yapılamaz — video izlediğiniz yerden devam ediyor.');
                                return;
                            }
                            enIleri = Math.max(enIleri, t);
                            izlenen++; artis++;
                            if (!bitti && ++yoklamaSayac >= yoklamaSn) yoklamaAc();
                            durumYaz();
                            if (artis >= 15) rapor();
                        }
                        function baslat() {
                            if (!window.YT || !window.YT.Player) { return setTimeout(baslat, 300); }
                            player = new YT.Player('oynatici', {
                                events: {
                                    onReady: () => { if (enIleri > 5) player.seekTo(Math.max(0, enIleri - 2), true); durumYaz(); },
                                    onStateChange: (e) => { if (e.data === YT.PlayerState.ENDED || e.data === YT.PlayerState.PAUSED) rapor(); },
                                    onPlaybackRateChange: () => { if (player.getPlaybackRate() !== 1) { player.setPlaybackRate(1); uyari('Eğitim videoları normal hızda izlenir.'); } },
                                }
                            });
                            setInterval(tik, 1000);
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
                    @if ($g)
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
