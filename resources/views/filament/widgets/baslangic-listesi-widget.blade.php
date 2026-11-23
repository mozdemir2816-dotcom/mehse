<x-filament-widgets::widget>
    {{-- O12 (23.11.2026): ilk kullanım başlangıç listesi. Sıra anlamlı (her adım öncekinin verisini kullanır) — bu yüzden numaralı. --}}
    <section class="mehse-baslangic mehse-mobilde-gizle" aria-labelledby="mehse-baslangic-baslik">
        <header class="mehse-baslangic-ust">
            <div class="mehse-baslangic-baslik-blok">
                <h2 id="mehse-baslangic-baslik" class="mehse-baslangic-baslik">Başlangıç listesi</h2>
                <p class="mehse-baslangic-alt">Programı kullanmaya hazır olmak için 6 adım. Adımlar kayıtlarınızdan otomatik işaretlenir.</p>
            </div>
            <div class="mehse-baslangic-ilerleme">
                <span class="mehse-baslangic-sayac"><strong>{{ $biten }}</strong>/{{ $toplam }} tamam</span>
                <span class="mehse-kokpit-cubuk mehse-baslangic-cubuk" role="progressbar" aria-valuemin="0" aria-valuemax="{{ $toplam }}" aria-valuenow="{{ $biten }}"
                      aria-label="{{ $toplam }} adımdan {{ $biten }} tanesi tamamlandı">
                    <span style="width: {{ $toplam ? round($biten / $toplam * 100) : 0 }}%"></span>
                </span>
            </div>
        </header>

        <ol class="mehse-baslangic-adimlar">
            @foreach ($adimlar as $i => $a)
                <li @class(['mehse-baslangic-adim', 'tamam' => $a['tamam'], 'siradaki' => $i === $siradaki])>
                    <span class="mehse-baslangic-isaret" aria-hidden="true">
                        @if ($a['tamam'])
                            <x-filament::icon icon="heroicon-m-check" />
                        @else
                            {{ $i + 1 }}
                        @endif
                    </span>
                    <span class="mehse-baslangic-metin">
                        <span class="mehse-baslangic-adim-baslik">{{ $a['baslik'] }}<span class="sr-only">{{ $a['tamam'] ? ' — tamamlandı' : '' }}</span></span>
                        <span class="mehse-baslangic-adim-aciklama">{{ $a['aciklama'] }}</span>
                    </span>
                    @unless ($a['tamam'])
                        @if ($i === $siradaki)
                            <x-filament::button tag="a" :href="$a['url']" size="sm" class="mehse-baslangic-eylem">{{ $a['eylem'] }}</x-filament::button>
                        @else
                            <a href="{{ $a['url'] }}" class="mehse-baslangic-baglanti">{{ $a['eylem'] }} →</a>
                        @endif
                    @endunless
                </li>
            @endforeach
        </ol>

        <footer class="mehse-baslangic-alt-satir">
            <button type="button" wire:click="gizle" class="mehse-baslangic-gizle">
                Bu listeyi gizle
            </button>
            <span>Liste, tüm adımlar tamamlanınca kendiliğinden kaybolur.</span>
        </footer>
    </section>
</x-filament-widgets::widget>
