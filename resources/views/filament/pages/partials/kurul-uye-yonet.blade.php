{{--
    "Üye Yönet" modalı içeriği (KurulToplantisi::getHeaderActions → uyeYonet).
    Sol: zorunlu roller (firma kaydından öneri) + aday personel; sağ: mevcut üyeler.
    Butonlar sayfanın Livewire metotlarını çağırır, modal açık kalır.
--}}
@php
    $roller = \App\Support\KurulUyeleri::roller();
    $uyeler = $this->uyeler;
    $girdi = 'width:100%;padding:.45rem .6rem;border-radius:.45rem;border:1px solid var(--border);background:var(--panel-bg, #fff);font-size:.82rem';
@endphp

<div class="grid gap-5 lg:grid-cols-[3fr_2fr]">
    <div class="flex flex-col gap-4">
        {{-- Zorunlu üyeler --}}
        <div>
            <div class="mb-2 flex items-center justify-between">
                <div>
                    <div class="text-sm font-bold">Zorunlu kurul üyeleri</div>
                    <div class="text-xs" style="color:var(--text-secondary)">Yönetmelik Md.6 — işyeri görevlendirmelerinden önerilir.</div>
                </div>
            </div>

            <div class="flex flex-col gap-2">
                @foreach ($roller as $rol => $tanim)
                    @continue(! ($tanim['zorunlu'] ?? false))
                    @php
                        $mevcut = $uyeler->where('rol', $rol);
                        $oneri = $this->oneriler[$rol] ?? null;
                    @endphp

                    <div class="flex flex-wrap items-center gap-2" style="border:1px solid {{ $mevcut->isNotEmpty() ? 'var(--success-border)' : 'var(--border)' }};background:{{ $mevcut->isNotEmpty() ? 'var(--success-bg)' : 'transparent' }};border-radius:10px;padding:.55rem .75rem">
                        <div class="flex-1" style="min-width:12rem">
                            <div class="text-xs font-bold" style="color:var(--text-secondary)">{{ $tanim['ad'] }}</div>
                            @if ($mevcut->isNotEmpty())
                                <div class="text-sm font-semibold">✓ {{ $mevcut->pluck('ad_soyad')->implode(', ') }}</div>
                            @elseif ($oneri)
                                <div class="text-sm">{{ $oneri['ad_soyad'] }} <span class="text-xs" style="color:var(--text-label)">· {{ $oneri['gorev'] }}</span></div>
                            @elseif ($tanim['kaynak'])
                                <div class="text-xs" style="color:rgb(217 119 6)">⚠ Bu işyerine {{ mb_strtolower(explode(' (', $tanim['ad'])[0]) }} atanmamış — firma kaydından atayın ya da aşağıdan seçin.</div>
                            @else
                                <div class="text-xs" style="color:var(--text-label)">Aşağıdaki personel listesinden "{{ $tanim['ad'] }}" rolüyle ekleyin.</div>
                            @endif
                        </div>

                        @if ($mevcut->isEmpty() && $oneri)
                            <x-filament::badge color="warning">Zorunlu</x-filament::badge>
                            <x-filament::button size="xs" icon="heroicon-o-plus" wire:click="oneriyiEkle('{{ $rol }}')">Seç</x-filament::button>
                        @elseif ($mevcut->isEmpty())
                            <x-filament::badge color="warning">Eksik</x-filament::badge>
                            <x-filament::button size="xs" color="gray" wire:click="$set('uyeRol', '{{ $rol }}')">Rolü seç</x-filament::button>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Aday personel --}}
        <div>
            <div class="mb-2">
                <div class="text-sm font-bold">Aday personel <span class="text-xs font-normal" style="color:var(--text-label)">({{ $this->adayCalisanlar->count() }} uygun aday)</span></div>
                <div class="text-xs" style="color:var(--text-secondary)">Rolü seçin, sonra personelin yanındaki "Ekle"ye basın.</div>
            </div>

            <div class="mb-2 grid gap-2 sm:grid-cols-2">
                <input type="search" wire:model.live.debounce.300ms="uyeArama" placeholder="Ad, görev veya departman ara" style="{{ $girdi }}">
                <select wire:model.live="uyeRol" style="{{ $girdi }}">
                    @foreach ($roller as $rol => $tanim)
                        <option value="{{ $rol }}">{{ $tanim['ad'] }}{{ ($tanim['zorunlu'] ?? false) ? ' *' : '' }}</option>
                    @endforeach
                </select>
            </div>

            <div class="flex flex-col gap-1" style="max-height:11rem;overflow-y:auto">
                @forelse ($this->adayCalisanlar as $c)
                    <div class="flex items-center gap-2" style="border:1px solid var(--border-light);border-radius:10px;padding:.4rem .6rem">
                        <span class="grid h-7 w-7 shrink-0 place-items-center rounded-full text-xs font-bold" style="background:var(--info-bg);color:var(--primary)">
                            {{ mb_strtoupper(str_replace('i', 'İ', mb_substr($c->ad_soyad, 0, 1))) }}
                        </span>
                        <div class="flex-1 leading-tight">
                            <div class="text-sm font-semibold">{{ $c->ad_soyad }}</div>
                            <div class="text-xs" style="color:var(--text-label)">{{ collect([$c->gorev, $c->departman])->filter()->implode(' · ') ?: '—' }}</div>
                        </div>
                        <x-filament::button size="xs" color="gray" icon="heroicon-o-plus" wire:click="calisaniEkle({{ $c->id }})">Ekle</x-filament::button>
                    </div>
                @empty
                    <p class="py-3 text-center text-xs" style="color:var(--text-label)">Uygun aktif personel yok.</p>
                @endforelse
            </div>

            <div class="mt-3 grid gap-2 sm:grid-cols-[1fr_1fr_auto]">
                <input type="text" wire:model="elleUyeAd" placeholder="Listede yoksa: ad soyad" style="{{ $girdi }}">
                <input type="text" wire:model="elleUyeGorev" placeholder="Görevi / unvanı" style="{{ $girdi }}">
                <x-filament::button size="sm" color="gray" wire:click="elleUyeEkle">Elle ekle</x-filament::button>
            </div>
        </div>
    </div>

    {{-- Mevcut üyeler --}}
    <div style="border:1px dashed var(--border);border-radius:12px;padding:.9rem">
        <div class="mb-2 text-sm font-bold">Kurul üyeleri ({{ $uyeler->count() }})</div>

        @forelse ($uyeler as $u)
            <div class="flex items-center gap-2 py-2" style="border-top:{{ $loop->first ? 'none' : '1px solid var(--border-light)' }}">
                <div class="flex-1 leading-tight">
                    <div class="text-sm font-semibold">{{ $u->ad_soyad }}</div>
                    <div class="text-xs" style="color:var(--text-label)">{{ $u->rolEtiketi() }}{{ $u->gorev ? ' · '.$u->gorev : '' }}</div>
                </div>
                <x-filament::icon-button icon="heroicon-o-x-mark" color="danger" size="sm" label="Çıkar" wire:click="uyeKaldir({{ $u->id }})" />
            </div>
        @empty
            <div class="py-6 text-center text-xs" style="color:var(--text-label)">
                Henüz üye seçilmedi.<br>Soldan zorunlu üyeleri ve personeli ekleyin.
            </div>
        @endforelse
    </div>
</div>
