@php
    $a = $this->atamaKaydi();
    $kutu = 'border:1px solid rgb(107 114 128 / .25);border-radius:.75rem';
    $aktif = $a?->paket->dersler->firstWhere('id', $aktifDersId);
    $izlenen = $this->izlenenDersIdler;
@endphp

<x-filament-panels::page>
    @if (! $a)
        <div>Eğitim bulunamadı.</div>
    @else
        <div style="margin-top:-.5rem">
            <a href="{{ \App\Filament\Portal\Pages\Egitimlerim::getUrl() }}" style="font-size:.82rem;color:rgb(107 114 128)">&larr; Eğitimlerim</a>
            <h2 style="font-size:1.15rem;font-weight:700;margin:.3rem 0 0">{{ $a->paket->ad }}</h2>
        </div>

        {{-- ================= İZLE ================= --}}
        @if ($mod === 'izle')
            <div style="display:grid;grid-template-columns:minmax(0,1fr) 260px;gap:1.2rem;align-items:start" class="izle-grid">
                {{-- Video --}}
                <div>
                    @if ($aktif)
                        <div style="{{ $kutu }};overflow:hidden">
                            <div style="position:relative;padding-top:56.25%;background:#000">
                                <iframe id="oynatici"
                                    style="position:absolute;inset:0;width:100%;height:100%;border:0"
                                    src="{{ $aktif->gommeUrl() }}{{ $aktif->saglayici === 'youtube' ? '&enablejsapi=1' : '' }}"
                                    allow="accelerometer; autoplay; encrypted-media; gyroscope; picture-in-picture"
                                    allowfullscreen></iframe>
                            </div>
                            <div style="padding:.9rem 1.1rem">
                                <div style="font-weight:700">{{ $aktif->baslik }}</div>
                                @if ($aktif->aciklama)<div style="font-size:.85rem;color:rgb(107 114 128);margin-top:.3rem">{{ $aktif->aciklama }}</div>@endif

                                <div style="margin-top:.7rem;font-size:.8rem">
                                    @if (in_array($aktif->id, $izlenen))
                                        <span style="color:rgb(21 128 61);font-weight:600">✓ Bu ders izlendi</span>
                                    @elseif ($aktif->saglayici === 'youtube')
                                        <span id="izleme-durum" style="color:rgb(107 114 128)">İzleniyor… (video en az %{{ $a->paket->video_zorunlu_yuzde }} izlenmeli)</span>
                                    @else
                                        <x-filament::button size="xs" color="gray" wire:click="dersTamamla({{ $aktif->id }})">Videoyu izledim</x-filament::button>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endif
                </div>

                {{-- Ders listesi + sınav --}}
                <div style="{{ $kutu }};padding:.6rem">
                    <div style="font-size:.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:rgb(107 114 128);padding:.4rem .6rem">Dersler</div>
                    @foreach ($a->paket->dersler as $i => $d)
                        <button type="button" wire:click="dersSec({{ $d->id }})"
                            style="display:flex;width:100%;text-align:left;gap:.5rem;align-items:flex-start;padding:.55rem .6rem;border:0;border-radius:.5rem;cursor:pointer;font-size:.85rem;background:{{ $d->id === $aktifDersId ? 'rgb(20 184 166 / .12)' : 'transparent' }}">
                            <span style="flex-shrink:0">{{ in_array($d->id, $izlenen) ? '✓' : ($i + 1) }}</span>
                            <span>{{ $d->baslik }}</span>
                        </button>
                    @endforeach

                    <div style="border-top:1px solid rgb(107 114 128 / .2);margin-top:.5rem;padding:.7rem .6rem">
                        @if ($a->tumDerslerIzlendiMi())
                            <x-filament::button size="sm" wire:click="sinavaBasla" style="width:100%">Final Sınavına Gir</x-filament::button>
                        @else
                            <div style="font-size:.78rem;color:rgb(107 114 128)">Sınav için tüm dersleri izleyin ({{ $a->izlenenDersSayisi() }}/{{ $a->toplamDersSayisi() }}).</div>
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
            <div style="{{ $kutu }};padding:1.2rem 1.4rem;max-width:760px">
                <div style="font-weight:700;margin-bottom:.3rem">Final Sınavı — {{ count($sinavSorulari) }} soru</div>
                <div style="font-size:.82rem;color:rgb(107 114 128);margin-bottom:1rem">Geçmek için en az %{{ $a->paket->gecme_puani }} gerekir.</div>

                @foreach ($sinavSorulari as $no => $s)
                    <div style="margin-bottom:1.3rem">
                        <div style="font-weight:600;font-size:.92rem">{{ $no + 1 }}. {{ $s['soru'] }}</div>
                        <div style="margin-top:.4rem;display:flex;flex-direction:column;gap:.3rem">
                            @foreach ($s['secenekler'] as $si => $secenek)
                                <label style="display:flex;gap:.5rem;align-items:flex-start;font-size:.88rem;cursor:pointer">
                                    <input type="radio" wire:model="cevaplar.{{ $s['id'] }}" value="{{ $si }}">
                                    <span>{{ chr(65 + $si) }}) {{ $secenek }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>
                @endforeach

                <x-filament::button wire:click="sinaviGonder" wire:confirm="Sınavı bitir ve gönder?">Sınavı Gönder</x-filament::button>
            </div>

        {{-- ================= SONUÇ ================= --}}
        @elseif ($mod === 'sonuc' && $sonSonuc)
            @php $g = $sonSonuc->gecti; @endphp
            <div style="{{ $kutu }};padding:1.5rem;max-width:620px;text-align:center;border-color:{{ $g ? 'rgb(21 128 61)' : 'rgb(185 28 28)' }}">
                <div style="font-size:2.2rem">{{ $g ? '🎓' : '❌' }}</div>
                <div style="font-size:1.2rem;font-weight:700;margin-top:.3rem">
                    {{ $g ? 'Eğitimi başarıyla tamamladınız' : 'Sınavı geçemediniz' }}
                </div>
                <div style="color:rgb(107 114 128);margin-top:.3rem">
                    Puanınız: <strong>%{{ $sonSonuc->puan }}</strong> ({{ $sonSonuc->dogruSayisi() }}/{{ $sonSonuc->toplamSoru() }} doğru) ·
                    Geçme: %{{ $a->paket->gecme_puani }}
                </div>

                <div style="margin-top:1.1rem;display:flex;gap:.6rem;justify-content:center;flex-wrap:wrap">
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

    <style>
        @media (max-width: 820px) { .izle-grid { grid-template-columns: 1fr !important; } }
    </style>
</x-filament-panels::page>
