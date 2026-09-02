@php
    $turuncu = 'rgb(217 119 6)';
    $kutu = 'border:1px solid rgb(107 114 128 / .3);border-radius:.75rem;padding:1rem';
    $girdi = 'margin-top:.3rem;width:100%;padding:.5rem .75rem;border-radius:.5rem;border:1px solid rgb(107 114 128 / .35);background:transparent';
@endphp

<x-filament-panels::page>
    <p style="font-size:.85rem;color:rgb(107 114 128);margin-top:-.5rem">
        Firma seçimi gerektirmeyen, bağımsız İSG hesaplayıcıları. Girdiğiniz değerler kaydedilmez.
    </p>

    {{-- KAZA SIKLIK / AĞIRLIK HIZI --}}
    <x-filament::section icon="heroicon-o-calculator" icon-color="warning">
        <x-slot name="heading">Kaza Sıklık Hızı ve Ağırlık Hızı Hesaplayıcı</x-slot>
        <x-slot name="description">Sıklık Hızı = (Kaza Sayısı × 1.000.000) / Toplam Çalışma Saati &nbsp;·&nbsp; Ağırlık Hızı = (Kayıp Gün Sayısı × 1.000) / Toplam Çalışma Saati</x-slot>

        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:1rem">
            <div>
                <label style="font-weight:600;font-size:.82rem">Kaza Sayısı</label>
                <input type="number" min="0" wire:model.live="kazaSayisi" style="{{ $girdi }}">
            </div>
            <div>
                <label style="font-weight:600;font-size:.82rem">Toplam Kayıp Gün Sayısı</label>
                <input type="number" min="0" wire:model.live="kayipGunSayisi" style="{{ $girdi }}">
            </div>
            <div>
                <label style="font-weight:600;font-size:.82rem">Toplam Çalışma Saati (adam × saat)</label>
                <input type="number" min="0" step="0.01" wire:model.live="toplamCalismaSaati" style="{{ $girdi }}">
            </div>
        </div>

        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:1rem;margin-top:1rem">
            <div style="{{ $kutu }};text-align:center">
                <div style="font-size:.78rem;color:rgb(107 114 128)">Kaza Sıklık Hızı</div>
                <div style="font-size:1.5rem;font-weight:800;color:{{ $turuncu }}">{{ $this->sikikHizi() ?? '—' }}</div>
            </div>
            <div style="{{ $kutu }};text-align:center">
                <div style="font-size:.78rem;color:rgb(107 114 128)">Kaza Ağırlık Hızı</div>
                <div style="font-size:1.5rem;font-weight:800;color:{{ $turuncu }}">{{ $this->agirlikHizi() ?? '—' }}</div>
            </div>
        </div>

        <div style="margin-top:.75rem">
            <x-filament::button size="xs" color="gray" wire:click="kazaHesaplayiciSifirla">Sıfırla</x-filament::button>
        </div>
    </x-filament::section>

    {{-- GÜRÜLTÜ MARUZİYET DÜZEYİ --}}
    <x-filament::section icon="heroicon-o-speaker-wave" icon-color="warning">
        <x-slot name="heading">Gürültü Maruziyet Düzeyi (Lex,8h) Hesaplayıcı</x-slot>
        <x-slot name="description">Farklı gürültü seviyelerinde geçirilen süreleri girin; günlük 8 saatlik normalize maruziyet düzeyi hesaplanır.</x-slot>

        <div style="display:flex;flex-direction:column;gap:.6rem">
            @foreach ($gurultuOlcumleri as $i => $olcum)
                <div style="display:grid;grid-template-columns:1fr 1fr auto;gap:.75rem;align-items:end">
                    <div>
                        <label style="font-weight:600;font-size:.82rem">Ölçülen Düzey (dB(A))</label>
                        <input type="number" step="0.1" wire:model.live="gurultuOlcumleri.{{ $i }}.db" style="{{ $girdi }}">
                    </div>
                    <div>
                        <label style="font-weight:600;font-size:.82rem">Maruziyet Süresi (saat)</label>
                        <input type="number" step="0.1" min="0" max="24" wire:model.live="gurultuOlcumleri.{{ $i }}.saat" style="{{ $girdi }}">
                    </div>
                    <div>
                        @if (count($gurultuOlcumleri) > 1)
                            <x-filament::icon-button icon="heroicon-o-trash" color="danger" wire:click="gurultuOlcumSil({{ $i }})"/>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>

        <div style="margin-top:.6rem">
            <x-filament::button size="xs" color="gray" icon="heroicon-o-plus" wire:click="gurultuOlcumEkle">Ölçüm Ekle</x-filament::button>
        </div>

        @php $seviye = $this->gurultuSeviyesi(); $lex = $this->lex8h(); @endphp
        <div style="{{ $kutu }};margin-top:1rem;text-align:center;{{ $seviye ? 'background:rgb('.($seviye['renk'] === 'danger' ? '220 38 38' : '217 119 6').' / .08)' : '' }}">
            <div style="font-size:.78rem;color:rgb(107 114 128)">Günlük Maruziyet Düzeyi — Lex,8h</div>
            <div style="font-size:1.5rem;font-weight:800;color:{{ $turuncu }}">{{ $lex !== null ? $lex.' dB(A)' : '—' }}</div>
            @if ($lex !== null)
                <div style="margin-top:.4rem">
                    @if ($seviye)
                        <x-filament::badge color="{{ $seviye['renk'] }}">{{ $seviye['etiket'] }}</x-filament::badge>
                        <div style="font-size:.78rem;color:rgb(107 114 128);margin-top:.4rem">{{ $seviye['aciklama'] }}</div>
                    @else
                        <x-filament::badge color="success">{{ config('isg.araclar.gurultu_guvenli_mesaj') }}</x-filament::badge>
                    @endif
                </div>
            @endif
        </div>

        <div style="margin-top:.75rem">
            <x-filament::button size="xs" color="gray" wire:click="gurultuHesaplayiciSifirla">Sıfırla</x-filament::button>
        </div>
    </x-filament::section>
</x-filament-panels::page>
