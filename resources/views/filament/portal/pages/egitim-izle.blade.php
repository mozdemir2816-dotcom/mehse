@php
    $a = $this->atamaKaydi();
    $aktif = $a?->paket->dersler->firstWhere('id', $aktifDersId);
    $izlenen = $this->izlenenDersIdler;
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

        {{-- ================= İZLE ================= --}}
        @if ($mod === 'izle')
            <div class="portal-izle-grid">
                {{-- Video --}}
                <div>
                    @if ($aktif)
                        <div class="portal-card" style="overflow:hidden">
                            <div style="position:relative;padding-top:56.25%;background:#000">
                                <iframe id="oynatici"
                                    style="position:absolute;inset:0;width:100%;height:100%;border:0"
                                    src="{{ $aktif->gommeUrl() }}{{ $aktif->saglayici === 'youtube' ? '&enablejsapi=1' : '' }}"
                                    allow="accelerometer; autoplay; encrypted-media; gyroscope; picture-in-picture"
                                    allowfullscreen></iframe>
                            </div>
                            <div class="portal-card-pad">
                                <div class="portal-heading" style="font-weight:700">{{ $aktif->baslik }}</div>
                                @if ($aktif->aciklama)<div class="portal-muted" style="font-size:.85rem;margin-top:.35rem;line-height:1.5">{{ $aktif->aciklama }}</div>@endif

                                <div style="margin-top:.8rem;font-size:.85rem">
                                    @if (in_array($aktif->id, $izlenen))
                                        <span class="portal-badge portal-badge-success">✓ Bu ders izlendi</span>
                                    @elseif ($aktif->saglayici === 'youtube')
                                        <span id="izleme-durum" class="portal-muted">İzleniyor… (video en az %{{ $a->paket->video_zorunlu_yuzde }} izlenmeli)</span>
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
                        @else
                            <div class="portal-muted" style="font-size:.8rem">Sınav için tüm dersleri izleyin ({{ $a->izlenenDersSayisi() }}/{{ $a->toplamDersSayisi() }}).</div>
                        @endif
                    </div>
                </div>
            </div>

            @if ($aktif && $aktif->saglayici === 'youtube' && ! in_array($aktif->id, $izlenen))
                <script src="https://www.youtube.com/iframe_api"></script>
                <script>
                    (function () {
                        const dersId = {{ $aktif->id }};
                        const zorunlu = {{ $a->paket->video_zorunlu_yuzde }};
                        let enYuksek = 0, gonderildi = -1, player = null;

                        function baslat() {
                            if (!window.YT || !window.YT.Player) { return setTimeout(baslat, 300); }
                            player = new YT.Player('oynatici', {
                                events: {
                                    onStateChange: (e) => { if (e.data === YT.PlayerState.ENDED) rapor(100); },
                                }
                            });
                            setInterval(tik, 4000);
                        }
                        function tik() {
                            if (!player || !player.getDuration) return;
                            const d = player.getDuration(), t = player.getCurrentTime();
                            if (!d) return;
                            const y = Math.min(100, Math.round(t / d * 100));
                            if (y > enYuksek) enYuksek = y;
                            const el = document.getElementById('izleme-durum');
                            if (el) el.textContent = 'İzlendi: %' + enYuksek + (enYuksek >= zorunlu ? ' — tamamlandı ✓' : '');
                            if (enYuksek >= zorunlu && gonderildi < zorunlu) rapor(enYuksek);
                            else if (enYuksek - gonderildi >= 20) rapor(enYuksek);
                        }
                        function rapor(y) { gonderildi = y; @this.call('dersIlerleme', dersId, y); }

                        if (window.YT && window.YT.Player) baslat();
                        else window.onYouTubeIframeAPIReady = baslat;
                    })();
                </script>
            @endif

        {{-- ================= SINAV ================= --}}
        @elseif ($mod === 'sinav')
            <div class="portal-card portal-card-pad" style="max-width:760px">
                <div class="portal-heading" style="font-weight:700;margin-bottom:.3rem">Final Sınavı — {{ count($sinavSorulari) }} soru</div>
                <div class="portal-muted" style="font-size:.85rem;margin-bottom:1.1rem">Geçmek için en az %{{ $a->paket->gecme_puani }} gerekir.</div>

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
                    Puanınız: <strong class="portal-heading">%{{ $sonSonuc->puan }}</strong> ({{ $sonSonuc->dogruSayisi() }}/{{ $sonSonuc->toplamSoru() }} doğru) ·
                    Geçme: %{{ $a->paket->gecme_puani }}
                </div>

                <div style="margin-top:1.3rem;display:flex;gap:.6rem;justify-content:center;flex-wrap:wrap">
                    @if ($g)
                        <x-filament::button wire:click="belgeIndir" icon="heroicon-o-arrow-down-tray">Katılım Belgesini İndir</x-filament::button>
                    @else
                        <x-filament::button color="gray" wire:click="tekrarDene">Dersleri Tekrar İzle / Yeniden Dene</x-filament::button>
                    @endif
                    <x-filament::button tag="a" color="gray" :href="\App\Filament\Portal\Pages\Egitimlerim::getUrl()">Eğitimlerim</x-filament::button>
                </div>
            </div>
        @endif
    @endif
</x-filament-panels::page>
