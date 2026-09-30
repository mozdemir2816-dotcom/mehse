<x-filament-widgets::widget>
    <section class="mehse-hosgeldin">
        <div class="mehse-hosgeldin-sol">
            <p class="mehse-hosgeldin-tarih">{{ $tarih }}</p>
            <h2 class="mehse-hosgeldin-baslik">{{ $selam }}, {{ $ad }}</h2>
            <p class="mehse-hosgeldin-alt">
                @if ($firma > 0)
                    Portföyünüzde <strong>{{ $firma }}</strong> aktif firma var. Bugün neyle başlamak istersiniz?
                @else
                    Henüz firma eklemediniz — ilk firmanızı ekleyerek başlayın.
                @endif
            </p>

            <div class="mehse-hosgeldin-eylemler">
                @foreach ($eylemler as $i => $e)
                    <a href="{{ $e['url'] }}" class="mehse-hosgeldin-eylem @if ($i === 0) mehse-hosgeldin-eylem-birincil @endif">
                        <x-filament::icon :icon="$e['ikon']" class="mehse-hosgeldin-eylem-ikon" />
                        <span>{{ $e['ad'] }}</span>
                    </a>
                @endforeach
            </div>
        </div>

        @if ($firma > 0)
            <a href="{{ \App\Filament\Pages\KontrolMerkezi::getUrl() }}" class="mehse-hosgeldin-halka" title="Kontrol Merkezi'ne git"
               style="--yuzde: {{ max(0, min(100, $uyum)) }}">
                <span class="mehse-hosgeldin-halka-deger">%{{ $uyum }}</span>
                <span class="mehse-hosgeldin-halka-etiket">Evrak uyumu</span>
            </a>
        @endif
    </section>
</x-filament-widgets::widget>
