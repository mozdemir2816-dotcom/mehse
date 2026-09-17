<x-filament-panels::page>
    <p class="portal-muted" style="font-size:.88rem;margin-top:-.5rem;line-height:1.5">
        Aşağıdaki eğitimleri sırayla izleyip final sınavına girin. Tüm dersleri izlemeden sınav açılmaz.
        Sınavı geçince katılım belgenizi indirebilirsiniz.
    </p>

    @if ($this->atamalar->isEmpty())
        <div class="portal-card" style="text-align:center;padding:3rem 1.25rem">
            <div style="font-size:2rem;line-height:1">🎓</div>
            <div class="portal-muted" style="margin-top:.6rem">Size atanmış bir uzaktan eğitim bulunmuyor.</div>
        </div>
    @else
        <div style="display:flex;flex-direction:column;gap:.85rem">
            @foreach ($this->atamalar as $a)
                @php
                    $yuzde = $a->ilerlemeYuzdesi();
                    [$badgeClass, $barColor] = match ($a->durum) {
                        'tamamlandi' => ['portal-badge-success', 'var(--p-success-text)'],
                        'basarisiz' => ['portal-badge-danger', 'var(--p-danger-text)'],
                        'devam' => ['portal-badge-warning', 'var(--p-warning-text)'],
                        default => ['portal-badge-neutral', 'var(--p-text-label)'],
                    };
                @endphp
                <div class="portal-card portal-card-pad">
                    <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:1rem;flex-wrap:wrap">
                        <div>
                            <div class="portal-heading" style="font-weight:700;font-size:1.05rem">{{ $a->paket->ad }}</div>
                            <div class="portal-muted" style="font-size:.82rem;margin-top:.25rem">
                                {{ $a->paket->dersler->count() }} ders · ~{{ $a->paket->toplamSureDk() }} dk
                                @if ($a->son_tarih) · Son tarih: {{ $a->son_tarih->format('d.m.Y') }} @endif
                            </div>
                        </div>
                        <span class="portal-badge {{ $badgeClass }}">{{ $a->durumEtiketi() }}</span>
                    </div>

                    <div class="portal-progress-track" style="margin-top:.9rem">
                        <div class="portal-progress-fill" style="width:{{ $yuzde }}%;background:{{ $barColor }}"></div>
                    </div>
                    <div class="portal-label" style="font-size:.76rem;margin-top:.3rem">
                        {{ $a->izlenenDersSayisi() }}/{{ $a->toplamDersSayisi() }} ders izlendi
                        @if ($a->sonSinav()) · Son sınav: %{{ $a->sonSinav()->puan }} @endif
                    </div>

                    <div style="margin-top:1rem">
                        <x-filament::button
                            tag="a"
                            :href="\App\Filament\Portal\Pages\EgitimIzle::getUrl(['atama' => $a->id])"
                            style="width:100%;justify-content:center"
                        >
                            {{ $a->durum === 'tamamlandi' ? 'Görüntüle' : ($a->izlenenDersSayisi() > 0 ? 'Devam Et' : 'Başla') }}
                        </x-filament::button>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</x-filament-panels::page>
