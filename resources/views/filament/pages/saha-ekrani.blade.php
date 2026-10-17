<x-filament-panels::page>
    @if ($kartlar === [])
        <div class="mehse-saha-bos">
            <p>Saha Ekranı'nda henüz kısayol yok.</p>
            <a href="{{ \App\Filament\Pages\Ayarlar::getUrl() }}" class="fi-btn fi-color-primary fi-size-md">Ayarlar'da kısayol seç</a>
        </div>
    @else
        <nav class="mehse-saha-izgara" aria-label="Saha kısayolları">
            @foreach ($kartlar as $kart)
                <a href="{{ $kart['url'] }}" class="mehse-saha-kart" data-kisayol="{{ $kart['anahtar'] }}">
                    <span class="mehse-saha-kart-ikon">
                        <x-filament::icon :icon="$kart['ikon']" />
                    </span>
                    <span class="mehse-saha-kart-etiket">{{ $kart['etiket'] }}</span>
                </a>
            @endforeach
        </nav>

        <p class="mehse-saha-not">
            Kartları ve ana ekranı değiştirmek için <a href="{{ \App\Filament\Pages\Ayarlar::getUrl() }}">Ayarlar</a>'a gidin.
        </p>
    @endif
</x-filament-panels::page>
