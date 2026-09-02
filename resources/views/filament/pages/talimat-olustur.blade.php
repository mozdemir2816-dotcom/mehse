@php
    $mavi = 'rgb(59 130 246)';
    $mor = 'rgb(139 92 246)';
    $kutu = 'border:1px solid rgb(107 114 128 / .3);border-radius:.75rem;padding:1rem';
@endphp

<x-filament-panels::page>
    <p style="font-size:.85rem;color:rgb(107 114 128);margin-top:-.5rem">
        Hazır şablonlardan veya kendi arşivinizden yüklediğiniz talimatlardan seçin ya
        da yapay zeka ile saniyeler içinde profesyonel İSG talimatı oluşturun.
    </p>

    {{-- 1. FİRMA --}}
    <x-filament::section icon="heroicon-o-document-duplicate" icon-color="info">
        <x-slot name="heading">1. Firma</x-slot>
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:1rem">
            <div>
                <label style="font-weight:600;font-size:.82rem">Firma Seçin <span style="color:#ef4444">*</span></label>
                <select wire:model.live="firmaId"
                    style="margin-top:.3rem;width:100%;padding:.55rem .75rem;border-radius:.5rem;border:1px solid rgb(107 114 128 / .35);background:transparent">
                    <option value="">— Firma seçin —</option>
                    @foreach ($this->firmalar as $id => $ad)
                        <option value="{{ $id }}">{{ $ad }}</option>
                    @endforeach
                </select>
            </div>
        </div>
    </x-filament::section>

    {{-- 2. ŞABLON KÜTÜPHANESİ (hazır + kendi arşivim) --}}
    <x-filament::section icon="heroicon-o-book-open" icon-color="info">
        <x-slot name="heading">2. Şablon Kütüphanesi ({{ count($this->sablonlar) }})</x-slot>
        <x-slot name="description">Sağ üstteki "Şablon İndir" ile Excel şablonunu alıp kendi arşivinizdeki talimatları toplu yükleyebilirsiniz</x-slot>

        <div style="display:grid;grid-template-columns:1fr 2fr;gap:.75rem;margin-bottom:1rem">
            <select wire:model.live="kategoriFiltre"
                style="padding:.5rem .7rem;border-radius:.5rem;border:1px solid rgb(107 114 128 / .35);background:transparent;font-size:.82rem">
                <option value="">Tüm kategoriler</option>
                @foreach ($this->kategoriler as $anahtar => $ad)
                    <option value="{{ $anahtar }}">{{ $ad }}</option>
                @endforeach
            </select>
            <input type="text" wire:model.live.debounce.400ms="arama" placeholder="Talimat ara..."
                style="padding:.5rem .7rem;border-radius:.5rem;border:1px solid rgb(107 114 128 / .35);background:transparent;font-size:.82rem">
        </div>

        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(260px,1fr));gap:.6rem;max-height:420px;overflow-y:auto;padding-right:.3rem">
            @forelse ($this->sablonlar as $s)
                @php $secili = $secilenKaynak === $s['kaynak'] && (string) $secilenAnahtar === (string) $s['anahtar']; @endphp
                <div role="button" tabindex="0" wire:click="sablonSec('{{ $s['kaynak'] }}', '{{ $s['anahtar'] }}')"
                    style="text-align:left;padding:.6rem .7rem;border-radius:.5rem;cursor:pointer;position:relative;
                        border:1px solid {{ $secili ? $mavi : 'rgb(107 114 128 / .3)' }};
                        background:{{ $secili ? 'rgb(59 130 246 / .08)' : 'transparent' }}">
                    <div style="display:flex;justify-content:space-between;align-items:start;gap:.4rem">
                        <div style="font-size:.7rem;color:rgb(107 114 128);text-transform:uppercase">{{ $this->kategoriler[$s['kategori']] ?? ($s['kategori'] ?: 'Genel') }}</div>
                        @if ($s['kaynak'] === 'ozel')
                            <span style="display:flex;align-items:center;gap:.3rem;flex-shrink:0">
                                <span style="font-size:.65rem;background:{{ $mor }};color:#fff;padding:1px 6px;border-radius:3px">Arşivim</span>
                                <span role="button" wire:click.stop="kendiSablonSil({{ $s['anahtar'] }})" title="Şablonu sil"
                                    style="color:#ef4444;cursor:pointer;font-size:.8rem">✕</span>
                            </span>
                        @endif
                    </div>
                    <div style="font-weight:700;font-size:.85rem;margin:.15rem 0">{{ $s['baslik'] }}</div>
                    <div style="font-size:.75rem;color:rgb(107 114 128)">{{ $s['aciklama'] }}</div>
                </div>
            @empty
                <p style="font-size:.82rem;color:rgb(107 114 128)">Sonuç bulunamadı.</p>
            @endforelse
        </div>
    </x-filament::section>

    {{-- 3. TALİMAT DÜZENLE --}}
    @if ($baslik)
        <x-filament::section icon="heroicon-o-pencil-square" icon-color="info">
            <x-slot name="heading">3. Talimatı Düzenle</x-slot>

            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:1rem;margin-bottom:.75rem">
                <div>
                    <label style="font-weight:600;font-size:.8rem">Başlık</label>
                    <input type="text" wire:model="baslik"
                        style="margin-top:.2rem;width:100%;padding:.45rem .6rem;border-radius:.4rem;border:1px solid rgb(107 114 128 / .3);background:transparent;font-size:.82rem">
                </div>
                <div>
                    <label style="font-weight:600;font-size:.8rem">Kategori</label>
                    <select wire:model="kategori"
                        style="margin-top:.2rem;width:100%;padding:.45rem .6rem;border-radius:.4rem;border:1px solid rgb(107 114 128 / .3);background:transparent;font-size:.82rem">
                        <option value="">—</option>
                        @foreach ($this->kategoriler as $anahtar => $ad)
                            <option value="{{ $anahtar }}">{{ $ad }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div style="margin-bottom:.75rem">
                <div style="font-weight:600;font-size:.8rem;margin-bottom:.3rem">Gerekli KKD'ler</div>
                <div style="display:flex;flex-wrap:wrap;gap:.3rem">
                    @foreach ($kkdler as $kkd)
                        <span style="display:inline-block;background:{{ $mavi }};color:#fff;padding:2px 8px;border-radius:4px;font-size:.72rem">{{ $kkd }}</span>
                    @endforeach
                </div>
            </div>

            <div style="margin-bottom:.75rem">
                <x-filament::button color="info" icon="heroicon-o-sparkles" wire:click="aiIleUret">
                    @if ($this->aiAktif) AI ile Üret @else AI ile Üret (devre dışı) @endif
                </x-filament::button>
            </div>

            @if ($maddeler)
                <ol style="margin:0 0 .75rem 1.2rem;padding:0;font-size:.82rem;display:flex;flex-direction:column;gap:.3rem">
                    @foreach ($maddeler as $i => $m)
                        <li style="display:flex;justify-content:space-between;gap:.5rem">
                            <span>{{ $m }}</span>
                            <button type="button" wire:click="maddeSil({{ $i }})" style="color:#ef4444;cursor:pointer;background:none;border:none;flex-shrink:0">✕</button>
                        </li>
                    @endforeach
                </ol>
            @endif

            <div style="display:grid;grid-template-columns:1fr auto;gap:.5rem">
                <input type="text" wire:model="yeniMadde" placeholder="Madde ekle..."
                    style="padding:.45rem .6rem;border-radius:.4rem;border:1px solid rgb(107 114 128 / .3);background:transparent;font-size:.82rem">
                <x-filament::button size="sm" wire:click="maddeEkle">+ Ekle</x-filament::button>
            </div>
        </x-filament::section>
    @endif

    @if ($this->firma)
        {{-- 4. KAYITLI TALİMATLARIM --}}
        @if ($this->kayitliTalimatlar->isNotEmpty())
            <x-filament::section icon="heroicon-o-clock" icon-color="gray">
                <x-slot name="heading">Kayıtlı Talimatlarım (bu firma)</x-slot>
                <table style="width:100%;border-collapse:collapse;font-size:.82rem">
                    @foreach ($this->kayitliTalimatlar as $t)
                        <tr>
                            <td style="padding:.3rem .5rem">{{ $t->baslik }} — {{ $t->kategoriEtiketi() }}</td>
                            <td style="padding:.3rem .5rem;text-align:right;white-space:nowrap">
                                <x-filament::button size="xs" color="gray" wire:click="kayitliPdf({{ $t->id }})">PDF</x-filament::button>
                                <x-filament::button size="xs" color="danger" wire:click="kayitliSil({{ $t->id }})">Sil</x-filament::button>
                            </td>
                        </tr>
                    @endforeach
                </table>
            </x-filament::section>
        @endif
    @else
        <p style="margin-top:1rem;font-size:.85rem;color:#f59e0b">PDF olarak kaydetmek için bir firma seçin.</p>
    @endif
</x-filament-panels::page>
