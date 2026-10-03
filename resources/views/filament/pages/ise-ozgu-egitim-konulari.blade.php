@php
    use App\Support\IseOzguEgitimKutuphanesi as K;
    $girdi = 'padding:.45rem .7rem;border-radius:.5rem;border:1px solid rgb(107 114 128 / .35);background:transparent;font-size:.85rem';
    $th = 'text-align:left;padding:.5rem .6rem;font-size:.7rem;letter-spacing:.05em;text-transform:uppercase;color:rgb(107 114 128);border-bottom:1px solid rgb(107 114 128 / .2)';
    $td = 'padding:.5rem .6rem;border-bottom:1px solid rgb(107 114 128 / .12);vertical-align:top';
    $kalemAd = collect(config('isg.kkd_matris.is_kalemleri', []))->flatten(1)->filter(fn ($k) => filled($k['anahtar'] ?? null))->pluck('ad', 'anahtar');
@endphp

<x-filament-panels::page>
    <div style="font-size:.85rem;color:rgb(107 114 128);margin-top:-.75rem">
        Yıllık eğitim planının 4. bölümü (<strong>İşe ve İşyerine Özgü Riskler</strong>) bu kütüphaneden dolar: plan oluşturulurken firmada iş kalemi seçiliyse
        iş kalemine, değilse NACE koduna uyan konular <strong>otomatik</strong> gelir. Plandaki konular Eğitim Katılım formunun işyerine özgü bölümüne aktarılır.
    </div>

    <div style="display:flex;flex-wrap:wrap;gap:.5rem;align-items:flex-end;justify-content:space-between">
        <div style="display:flex;flex-wrap:wrap;gap:.5rem;align-items:flex-end">
            <div><label style="display:block;font-size:.72rem;font-weight:600">Ara</label>
                <input type="search" wire:model.live.debounce.400ms="arama" placeholder="Konu, hedef, NACE…" style="{{ $girdi }};min-width:240px"></div>
            <div><label style="display:block;font-size:.72rem;font-weight:600">Kaynak</label>
                <select wire:model.live="kaynakFiltre" style="{{ $girdi }}">
                    <option value="">Tümü</option>
                    <option value="kendi">Kendi kayıtlarım / düzenlediklerim</option>
                    <option value="kalem">İş kalemine bağlı</option>
                    <option value="nace">NACE'ye bağlı (sistem)</option>
                </select></div>
            <div><label style="display:block;font-size:.72rem;font-weight:600">Firmaya önerilenleri göster</label>
                <select wire:model.live="firmaId" style="{{ $girdi }};min-width:240px">
                    <option value="">— Tüm konular —</option>
                    @foreach ($this->firmalar as $id => $ad)<option value="{{ $id }}">{{ $ad }}</option>@endforeach
                </select></div>
        </div>
        <x-filament::button icon="heroicon-o-plus" wire:click="mountAction('yeniKonu')">Yeni Konu</x-filament::button>
    </div>

    @if ($this->firma)
        <div style="border:1px solid rgb(13 148 136 / .4);background:rgb(13 148 136 / .06);border-radius:.6rem;padding:.55rem .8rem;font-size:.8rem">
            <strong>{{ $this->firma->unvan }}</strong> — NACE {{ $this->firma->nace_kodu ?: '—' }}
            @if (filled($this->firma->is_kalemleri)) · İş kalemleri: {{ collect($this->firma->is_kalemleri)->map(fn ($a) => $kalemAd[$a] ?? $a)->implode(', ') }} (iş kalemine göre seçilir)@else · iş kalemi seçili değil (NACE'ye göre seçilir)@endif
            · {{ $this->konular->count() }} konu önerilir.
        </div>
    @endif

    <x-filament::section>
        <x-slot name="heading">Konular ({{ $this->konular->count() }})</x-slot>
        <div style="overflow-x:auto">
            <table style="width:100%;border-collapse:collapse;font-size:.8rem">
                <thead><tr><th style="{{ $th }}">Eğitimin adı / hedefi</th><th style="{{ $th }}">Eşleşme</th><th style="{{ $th }}">Eğitici</th><th style="{{ $th }}">Kaynak</th><th style="{{ $th }}"></th></tr></thead>
                <tbody>
                    @forelse ($this->konular as $a => $k)
                        <tr wire:key="k-{{ $a }}">
                            <td style="{{ $td }}"><div style="font-weight:700">{{ $k['ad'] }}</div><div style="color:rgb(107 114 128);font-size:.75rem">{{ $k['hedef'] }}</div></td>
                            <td style="{{ $td }};font-size:.74rem">
                                @if ($k['is_kalemleri'])<div>İş kalemi: {{ collect($k['is_kalemleri'])->map(fn ($x) => $kalemAd[$x] ?? $x)->implode(', ') }}</div>@endif
                                @if ($k['nace'])<div>NACE: {{ K::naceGoster($k['nace']) }}</div>@endif
                                @if (! $k['is_kalemleri'] && ! $k['nace'])<span style="color:rgb(217 119 6)">Eşleşme yok — yalnız elle seçilir</span>@endif
                            </td>
                            <td style="{{ $td }};font-size:.74rem">{{ $k['egitici'] }}</td>
                            <td style="{{ $td }};font-size:.74rem">{{ $k['kaynak'] }}@if ($k['duzenlendi'])<div style="color:rgb(13 148 136)">düzenlendi</div>@endif</td>
                            <td style="{{ $td }};white-space:nowrap">
                                <x-filament::button size="xs" color="gray" wire:click="mountAction('konuDuzenle', { anahtar: '{{ $a }}' })">Düzenle</x-filament::button>
                                @if ($k['duzenlendi'])
                                    <x-filament::button size="xs" color="gray" wire:click="sistemeDondur('{{ $a }}')">Orijinaline dön</x-filament::button>
                                @endif
                                <x-filament::button size="xs" color="danger" wire:click="kaldir('{{ $a }}')" wire:confirm="{{ $k['sistem'] ? 'Sistem konusu kütüphanenizden gizlensin mi? (Geri getirilebilir)' : 'Konu silinsin mi?' }}">{{ $k['sistem'] ? 'Gizle' : 'Sil' }}</x-filament::button>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" style="{{ $td }};color:rgb(107 114 128)">Konu yok. @if ($this->firma)Bu firmaya uyan konu bulunamadı — "Yeni Konu" ile firmanın NACE kodu veya iş kalemiyle kaydedin.@endif</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-filament::section>

    @if ($this->gizlenenler->isNotEmpty())
        <x-filament::section heading="Gizlenen sistem konuları" collapsible collapsed>
            @foreach ($this->gizlenenler as $a => $k)
                <div style="display:flex;justify-content:space-between;align-items:center;padding:.3rem 0;border-bottom:1px solid rgb(107 114 128 / .12);font-size:.8rem">
                    <span>{{ $k['ad'] }} <span style="color:rgb(107 114 128)">· {{ $k['kaynak'] }}</span></span>
                    <x-filament::button size="xs" color="gray" wire:click="sistemeDondur('{{ $a }}')">Geri getir</x-filament::button>
                </div>
            @endforeach
        </x-filament::section>
    @endif
</x-filament-panels::page>
