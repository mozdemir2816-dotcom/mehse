@php
    $inp = 'margin-top:.3rem;width:100%;padding:.5rem .75rem;border-radius:.5rem;border:1px solid rgb(107 114 128 / .35);background:transparent';
    $lbl = 'font-weight:600;font-size:.82rem';
    $durumRenk = ['taslak' => '#f59e0b', 'onaylandi' => '#10b981', 'arsiv' => '#6b7280'];
@endphp

<x-filament-panels::page>
    <p style="font-size:.85rem;color:rgb(107 114 128);margin-top:-.5rem">
        Sektör + konu + zorluk etiketli, kaynağı belirtilen İSG sınav sorusu havuzu.
        AI ile üretilen veya elle eklenen sorular <strong>taslak</strong> gelir; inceleyip
        onayladıktan sonra Eğitim Soruları ve Uzaktan Eğitim sınavları yalnızca onaylı
        sorulardan beslenir.
    </p>

    {{-- ÖZET --}}
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(130px,1fr));gap:.6rem">
        @foreach (['toplam' => 'Toplam', 'onayli' => 'Onaylı', 'taslak' => 'Taslak', 'arsiv' => 'Arşiv'] as $k => $ad)
            <div style="border:1px solid rgb(107 114 128 / .25);border-radius:.6rem;padding:.6rem .8rem">
                <div style="font-size:.72rem;color:rgb(107 114 128)">{{ $ad }}</div>
                <div style="font-size:1.35rem;font-weight:700">{{ $this->ozet[$k] }}</div>
            </div>
        @endforeach
    </div>

    {{-- FİLTRELER --}}
    <x-filament::section icon="heroicon-o-funnel">
        <x-slot name="heading">Filtrele</x-slot>
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:.75rem">
            <div>
                <label style="{{ $lbl }}">Durum</label>
                <select wire:model.live="durumFiltre" style="{{ $inp }}">
                    <option value="">Tümü</option>
                    @foreach ($this->durumlar as $a => $ad)
                        <option value="{{ $a }}">{{ $ad }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label style="{{ $lbl }}">Sektör</label>
                <select wire:model.live="sektorFiltre" style="{{ $inp }}">
                    <option value="">Tümü</option>
                    @foreach ($this->sektorler as $a => $ad)
                        <option value="{{ $a }}">{{ $ad }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label style="{{ $lbl }}">Konu</label>
                <select wire:model.live="konuFiltre" style="{{ $inp }}">
                    <option value="">Tümü</option>
                    @foreach ($this->konular as $a => $ad)
                        <option value="{{ $a }}">{{ $ad }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label style="{{ $lbl }}">Zorluk</label>
                <select wire:model.live="zorlukFiltre" style="{{ $inp }}">
                    <option value="">Tümü</option>
                    @foreach ($this->zorluklar as $a => $ad)
                        <option value="{{ $a }}">{{ $ad }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label style="{{ $lbl }}">Ara</label>
                <input type="text" wire:model.live.debounce.400ms="arama" placeholder="Soru metninde ara..." style="{{ $inp }}">
            </div>
        </div>

        @if ($durumFiltre === 'taslak' && $this->sorular->isNotEmpty())
            <div style="margin-top:.75rem">
                <x-filament::button size="sm" color="success" wire:click="tumTaslaklariOnayla"
                    wire:confirm="Filtredeki tüm taslak sorular onaylanacak. Emin misiniz?">
                    Filtredeki Taslakları Toplu Onayla
                </x-filament::button>
            </div>
        @endif
    </x-filament::section>

    {{-- YENİ SORU (MANUEL) --}}
    <x-filament::section icon="heroicon-o-plus-circle" icon-color="primary" collapsible collapsed>
        <x-slot name="heading">Elle Soru Ekle</x-slot>

        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:.6rem;margin-bottom:.75rem">
            <div>
                <label style="{{ $lbl }}">Sektör</label>
                <select wire:model="yeniSektor" style="{{ $inp }}">
                    @foreach ($this->sektorler as $a => $ad)
                        <option value="{{ $a }}">{{ $ad }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label style="{{ $lbl }}">Konu</label>
                <select wire:model="yeniKonu" style="{{ $inp }}">
                    @foreach ($this->konular as $a => $ad)
                        <option value="{{ $a }}">{{ $ad }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label style="{{ $lbl }}">Zorluk</label>
                <select wire:model="yeniZorluk" style="{{ $inp }}">
                    @foreach ($this->zorluklar as $a => $ad)
                        <option value="{{ $a }}">{{ $ad }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <label style="{{ $lbl }}">Soru Metni</label>
        <textarea wire:model="yeniSoru" rows="2" style="{{ $inp }};font-family:inherit;font-size:.85rem"></textarea>

        <div style="margin-top:.6rem;display:flex;flex-direction:column;gap:.35rem">
            @foreach (range(0, 3) as $i)
                <label style="display:flex;align-items:center;gap:.5rem;font-size:.82rem">
                    <input type="radio" wire:model="yeniDogruIndex" value="{{ $i }}" title="Doğru şık">
                    <input type="text" wire:model="yeniSecenekler.{{ $i }}" placeholder="{{ chr(65 + $i) }} şıkkı"
                        style="flex:1;padding:.4rem .6rem;border-radius:.4rem;border:1px solid rgb(107 114 128 / .3);background:transparent">
                </label>
            @endforeach
        </div>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:.6rem;margin-top:.6rem">
            <div>
                <label style="{{ $lbl }}">Kaynak (yasal dayanak / referans)</label>
                <input type="text" wire:model="yeniKaynak" placeholder="Örn: 6331 sK m.19" style="{{ $inp }}" list="kaynak-listesi">
                <datalist id="kaynak-listesi">
                    @foreach (config('isg.soru_bankasi.ornek_kaynaklar') as $ok)
                        <option value="{{ $ok }}">
                    @endforeach
                </datalist>
            </div>
            <div>
                <label style="{{ $lbl }}">Açıklama (doğru cevabın gerekçesi)</label>
                <input type="text" wire:model="yeniAciklama" style="{{ $inp }}">
            </div>
        </div>

        <div style="margin-top:.75rem">
            <x-filament::button size="sm" wire:click="soruEkle">Bankaya Taslak Olarak Ekle</x-filament::button>
        </div>
    </x-filament::section>

    {{-- SORU LİSTESİ --}}
    <x-filament::section icon="heroicon-o-rectangle-stack">
        <x-slot name="heading">Sorular ({{ $this->sorular->count() }})</x-slot>

        @if ($this->sorular->isEmpty())
            <p style="font-size:.83rem;color:rgb(107 114 128)">Bu filtrede soru yok.</p>
        @else
            <div style="display:flex;flex-direction:column;gap:.6rem">
                @foreach ($this->sorular as $s)
                    <div style="border:1px solid rgb(107 114 128 / .22);border-radius:.6rem;padding:.7rem .85rem">
                        <div style="display:flex;gap:.4rem;flex-wrap:wrap;align-items:center;margin-bottom:.4rem">
                            <span style="font-size:.68rem;color:#fff;background:{{ $durumRenk[$s->durum] ?? '#6b7280' }};border-radius:.4rem;padding:.05rem .45rem">{{ $s->durumEtiketi() }}</span>
                            <span style="font-size:.7rem;color:rgb(107 114 128)">{{ $s->sektorEtiketi() }} · {{ $s->konuEtiketi() }} · {{ $s->zorlukEtiketi() }}</span>
                            @if ($s->uretim_kaynagi === 'ai')<span style="font-size:.65rem;color:#8b5cf6">✨ AI</span>@endif
                            @if ($s->user_id === null)<span style="font-size:.65rem;color:rgb(107 114 128)">sistem havuzu</span>@endif
                        </div>

                        <div style="font-weight:600;font-size:.86rem;margin-bottom:.35rem">{{ $s->soru }}</div>
                        <ol type="A" style="margin:0 0 .3rem 1.1rem;padding:0;font-size:.8rem">
                            @foreach ($s->secenekler as $si => $sec)
                                <li style="{{ $si === $s->dogru_index ? 'color:#10b981;font-weight:600' : '' }}">
                                    {{ $sec }}{{ $si === $s->dogru_index ? ' ✓' : '' }}
                                </li>
                            @endforeach
                        </ol>
                        @if ($s->aciklama)
                            <div style="font-size:.75rem;color:rgb(107 114 128)"><strong>Gerekçe:</strong> {{ $s->aciklama }}</div>
                        @endif
                        @if ($s->kaynak)
                            <div style="font-size:.75rem;color:rgb(107 114 128)"><strong>Kaynak:</strong> {{ $s->kaynak }}</div>
                        @endif
                        @if ($s->onaylayan)
                            <div style="font-size:.7rem;color:rgb(107 114 128)">Onaylayan: {{ $s->onaylayan }} · {{ $s->onay_tarihi?->format('d.m.Y') }}</div>
                        @endif

                        <div style="margin-top:.5rem;display:flex;gap:.35rem;flex-wrap:wrap">
                            @if ($s->durum !== 'onaylandi')
                                <x-filament::button size="xs" color="success" wire:click="onayla({{ $s->id }})">Onayla</x-filament::button>
                            @endif
                            @if ($s->durum === 'onaylandi')
                                <x-filament::button size="xs" color="gray" wire:click="taslagaAl({{ $s->id }})">Taslağa Al</x-filament::button>
                            @endif
                            @if ($s->durum !== 'arsiv')
                                <x-filament::button size="xs" color="gray" wire:click="arsivle({{ $s->id }})">Arşivle</x-filament::button>
                            @endif
                            @if ($s->user_id !== null)
                                <x-filament::button size="xs" color="danger" wire:click="sil({{ $s->id }})" wire:confirm="Soru silinsin mi?">Sil</x-filament::button>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </x-filament::section>
</x-filament-panels::page>
