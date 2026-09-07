@php
    $sari = 'rgb(245 158 11)';
    $kutu = 'border:1px solid rgb(107 114 128 / .3);border-radius:.75rem;padding:1rem';
    $oncelikRenk = fn ($o) => match ($o) { 'yuksek' => '#ef4444', 'dusuk' => '#6b7280', default => '#f59e0b' };
    $oncelikEtiket = fn ($o) => match ($o) { 'yuksek' => 'Yüksek', 'dusuk' => 'Düşük', default => 'Orta' };
@endphp

<x-filament-panels::page>
    <p style="font-size:.85rem;color:rgb(107 114 128);margin-top:-.5rem">
        Onaylı deftere yazılacak tespit ve önerileri hazır katalogdan seçin veya
        serbest yazın; firma için kaydedip PDF çıktısını alın.
    </p>

    @include('filament.pages.partials.eksik-firmalar', ['kriterAnahtari' => 'tespit_oneri'])

    {{-- 1. FİRMA --}}
    <x-filament::section icon="heroicon-o-book-open" icon-color="warning">
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

    @if ($this->firma)
        {{-- 2. HAZIR ÖNERİ KATALOĞU --}}
        <x-filament::section icon="heroicon-o-clipboard-document-list" icon-color="warning">
            <x-slot name="heading">2. Hazır Öneri Kataloğu</x-slot>

            <div style="display:grid;grid-template-columns:1fr 2fr;gap:.75rem;margin-bottom:1rem">
                <select wire:model.live="konuFiltre"
                    style="padding:.5rem .7rem;border-radius:.5rem;border:1px solid rgb(107 114 128 / .35);background:transparent;font-size:.82rem">
                    <option value="">Tüm konular</option>
                    @foreach ($this->konular as $k)
                        <option value="{{ $k }}">{{ $k }}</option>
                    @endforeach
                </select>
                <input type="text" wire:model.live.debounce.400ms="arama" placeholder="Tespit içinde ara..."
                    style="padding:.5rem .7rem;border-radius:.5rem;border:1px solid rgb(107 114 128 / .35);background:transparent;font-size:.82rem">
            </div>

            <div style="display:flex;flex-direction:column;gap:1rem;max-height:420px;overflow-y:auto;padding-right:.3rem">
                @forelse ($this->katalog as $kategori => $maddeler)
                    <div>
                        <div style="font-weight:700;font-size:.82rem;color:{{ $sari }};margin-bottom:.4rem">{{ $kategori }} ({{ count($maddeler) }})</div>
                        <div style="display:flex;flex-direction:column;gap:.5rem">
                            @foreach ($maddeler as $m)
                                <div style="{{ $kutu }}">
                                    <div style="display:flex;justify-content:space-between;gap:.5rem;align-items:start">
                                        <div>
                                            <span style="display:inline-block;color:#fff;padding:1px 6px;border-radius:3px;font-size:.7rem;background:{{ $oncelikRenk($m['oncelik']) }}">{{ $oncelikEtiket($m['oncelik']) }} Öncelik</span>
                                            <div style="font-size:.82rem;font-weight:600;margin-top:.3rem">{{ $m['tespit'] }}</div>
                                            <div style="font-size:.78rem;color:rgb(107 114 128);margin-top:.2rem">{{ $m['oneri'] }}</div>
                                            <div style="font-size:.7rem;color:rgb(107 114 128);margin-top:.2rem">Dayanak: {{ $m['dayanak'] }}</div>
                                        </div>
                                        <x-filament::button size="xs" color="warning" wire:click="katalogdanEkle('{{ addslashes($m['tespit']) }}')">+ Ekle</x-filament::button>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @empty
                    <p style="font-size:.82rem;color:rgb(107 114 128)">Sonuç bulunamadı.</p>
                @endforelse
            </div>
        </x-filament::section>

        {{-- 3. SERBEST TESPİT/ÖNERİ --}}
        <x-filament::section icon="heroicon-o-pencil-square" icon-color="warning">
            <x-slot name="heading">3. Diğer (Kendiniz Yazın)</x-slot>

            <textarea wire:model="serbestTespit" rows="2" placeholder="Tespitinizi yazın (örn: İşyerinde acil çıkış kapısının kilitli olduğu tespit edilmiştir.)"
                style="width:100%;padding:.5rem .7rem;border-radius:.4rem;border:1px solid rgb(107 114 128 / .3);background:transparent;font-size:.82rem;font-family:inherit;margin-bottom:.5rem"></textarea>

            @if ($this->aiAktif)
                <x-filament::button size="xs" color="warning" wire:click="aiOnerisiAl">✨ Yapay Zekadan Öneri Al</x-filament::button>
            @endif

            <textarea wire:model="serbestOneri" rows="2" placeholder="Önerinizi yazın (örn: Acil çıkış kapısı her zaman açılabilir durumda bulundurulmalıdır.)"
                style="width:100%;padding:.5rem .7rem;border-radius:.4rem;border:1px solid rgb(107 114 128 / .3);background:transparent;font-size:.82rem;font-family:inherit;margin-top:.5rem;margin-bottom:.5rem"></textarea>

            <div style="display:flex;align-items:center;gap:.6rem;margin-bottom:.5rem">
                <input type="file" wire:model="yeniFoto" accept="image/*" style="font-size:.8rem">
                @if ($yeniFoto)
                    <img src="{{ $yeniFoto->temporaryUrl() }}" style="width:44px;height:44px;object-fit:cover;border-radius:.35rem;border:1px solid rgb(107 114 128 / .3)">
                    <button type="button" wire:click="$set('yeniFoto', null)" style="color:#ef4444;cursor:pointer;background:none;border:none;font-size:.8rem">Kaldır</button>
                @endif
            </div>
            <p style="font-size:.72rem;color:rgb(107 114 128);margin-bottom:.5rem">
                İsteğe bağlı fotoğraf kanıtı — PDF'te ilgili maddenin ardından ayrı bir sayfa olarak eklenir.
            </p>

            <x-filament::button size="sm" wire:click="serbestEkle">+ Listeye Ekle</x-filament::button>
        </x-filament::section>

        {{-- 4. DEFTER KAYDI --}}
        <x-filament::section icon="heroicon-o-archive-box" icon-color="warning">
            <x-slot name="heading">
                4. Defter Kaydı
                <span style="font-weight:400;font-size:.8rem;color:rgb(107 114 128)">({{ count($this->defter->maddeler ?? []) }} madde)</span>
            </x-slot>

            <div style="margin-bottom:.75rem">
                <label style="font-weight:600;font-size:.82rem">Genel Not (Opsiyonel)</label>
                <div style="display:flex;gap:.5rem;margin-top:.3rem">
                    <input type="text" wire:model="genelNot" placeholder="Örn: 12.06.2026 tarihli saha ziyareti"
                        style="flex:1;padding:.5rem .7rem;border-radius:.4rem;border:1px solid rgb(107 114 128 / .3);background:transparent;font-size:.82rem">
                    <x-filament::button size="sm" color="gray" wire:click="genelNotuKaydet">Kaydet</x-filament::button>
                </div>
            </div>

            @if ($this->defter->maddeler ?? [])
                <table style="width:100%;border-collapse:collapse;font-size:.8rem">
                    <tr>
                        <th style="text-align:left;padding:.3rem .5rem;border-bottom:1px solid rgb(107 114 128 / .3)">Tespit</th>
                        <th style="text-align:left;padding:.3rem .5rem;border-bottom:1px solid rgb(107 114 128 / .3)">Öneri</th>
                        <th style="border-bottom:1px solid rgb(107 114 128 / .3)"></th>
                    </tr>
                    @foreach ($this->defter->maddeler as $i => $m)
                        <tr>
                            <td style="padding:.3rem .5rem">
                                @if (! empty($m['foto_yolu'])) <span title="Fotoğraf kanıtı ekli">📷</span> @endif
                                {{ $m['tespit'] }}
                            </td>
                            <td style="padding:.3rem .5rem">{{ $m['oneri'] }}</td>
                            <td style="padding:.3rem .5rem;text-align:right">
                                <button type="button" wire:click="maddeSil({{ $i }})" style="color:#ef4444;cursor:pointer;background:none;border:none">✕</button>
                            </td>
                        </tr>
                    @endforeach
                </table>
            @else
                <p style="font-size:.82rem;color:rgb(107 114 128)">Henüz madde eklenmedi.</p>
            @endif
        </x-filament::section>
    @else
        <p style="margin-top:1rem;font-size:.85rem;color:#f59e0b">Devam etmek için bir firma seçin.</p>
    @endif
</x-filament-panels::page>
