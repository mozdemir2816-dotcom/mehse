@php $kutu = 'border:1px solid rgb(107 114 128 / .25);border-radius:.75rem;padding:1.1rem 1.25rem'; @endphp

<x-filament-panels::page>
    <p style="font-size:.9rem;color:rgb(107 114 128);margin-top:-.5rem">
        Aşağıdaki eğitimleri sırayla izleyip final sınavına girin. Tüm dersleri izlemeden sınav açılmaz.
        Sınavı geçince katılım belgenizi indirebilirsiniz.
    </p>

    @if ($this->atamalar->isEmpty())
        <div style="{{ $kutu }};text-align:center;color:rgb(107 114 128);padding:2.5rem 1rem">
            Size atanmış bir uzaktan eğitim bulunmuyor.
        </div>
    @else
        <div style="display:flex;flex-direction:column;gap:.9rem">
            @foreach ($this->atamalar as $a)
                @php
                    $yuzde = $a->ilerlemeYuzdesi();
                    $renk = match ($a->durum) {
                        'tamamlandi' => 'rgb(21 128 61)',
                        'basarisiz' => 'rgb(185 28 28)',
                        'devam' => 'rgb(180 83 9)',
                        default => 'rgb(107 114 128)',
                    };
                @endphp
                <div style="{{ $kutu }}">
                    <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:1rem;flex-wrap:wrap">
                        <div>
                            <div style="font-weight:700;font-size:1.05rem">{{ $a->paket->ad }}</div>
                            <div style="font-size:.82rem;color:rgb(107 114 128);margin-top:.2rem">
                                {{ $a->paket->dersler->count() }} ders · ~{{ $a->paket->toplamSureDk() }} dk
                                @if ($a->son_tarih) · Son tarih: {{ $a->son_tarih->format('d.m.Y') }} @endif
                            </div>
                        </div>
                        <span style="font-family:monospace;font-size:.72rem;text-transform:uppercase;letter-spacing:.04em;color:{{ $renk }};border:1px solid {{ $renk }};border-radius:999px;padding:.15rem .6rem">
                            {{ $a->durumEtiketi() }}
                        </span>
                    </div>

                    <div style="margin-top:.8rem;height:8px;border-radius:99px;background:rgb(107 114 128 / .15);overflow:hidden">
                        <div style="height:100%;width:{{ $yuzde }}%;background:{{ $renk }}"></div>
                    </div>
                    <div style="font-size:.75rem;color:rgb(107 114 128);margin-top:.25rem">{{ $a->izlenenDersSayisi() }}/{{ $a->toplamDersSayisi() }} ders izlendi
                        @if ($a->sonSinav()) · Son sınav: %{{ $a->sonSinav()->puan }} @endif
                    </div>

                    <div style="margin-top:.9rem;display:flex;gap:.5rem;flex-wrap:wrap">
                        <x-filament::button tag="a" :href="\App\Filament\Portal\Pages\EgitimIzle::getUrl(['atama' => $a->id])" size="sm">
                            {{ $a->durum === 'tamamlandi' ? 'Görüntüle' : ($a->izlenenDersSayisi() > 0 ? 'Devam Et' : 'Başla') }}
                        </x-filament::button>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</x-filament-panels::page>
