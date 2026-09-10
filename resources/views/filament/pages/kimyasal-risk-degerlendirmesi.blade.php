@php
    $kutu = 'border:1px solid rgb(107 114 128 / .3);border-radius:.75rem;padding:1rem';
    $girdi = 'width:100%;padding:.35rem .5rem;border-radius:.4rem;border:1px solid rgb(107 114 128 / .35);background:transparent;font-size:.78rem';
    $yaklasimRenk = [1 => '#16a34a', 2 => '#ca8a04', 3 => '#ea580c', 4 => '#dc2626'];
@endphp

<x-filament-panels::page>
    <p style="font-size:.85rem;color:rgb(107 114 128);margin-top:-.5rem">
        Kimyasal envanterindeki her ürün için tehlike grubu, kullanım miktarı ve uçuculuk/tozlaşma
        girin; kontrol yaklaşımı (1: genel havalandırma → 4: uzman görüşü) COSHH Essentials
        yöntemiyle otomatik hesaplanır. Kimyasal Sicili'ndeki ürünler listeden çekilir.
    </p>

    <x-filament::section icon="heroicon-o-beaker" icon-color="primary">
        <x-slot name="heading">Firma</x-slot>
        <div style="display:flex;gap:1rem;flex-wrap:wrap;align-items:end">
            <select wire:model.live="firmaId" style="{{ $girdi }};max-width:380px">
                <option value="">— Firma seçin —</option>
                @foreach ($this->firmalar as $id => $ad)
                    <option value="{{ $id }}">{{ $ad }}</option>
                @endforeach
            </select>
            @if ($this->firma)
                <div style="width:170px">
                    <label style="font-size:.75rem;font-weight:600">Değerlendirme Tarihi</label>
                    <input type="date" wire:model.blur="degerlendirmeTarihi" style="{{ $girdi }}">
                </div>
            @endif
        </div>
    </x-filament::section>

    @if ($this->firma)
        <x-filament::section icon="heroicon-o-plus-circle" icon-color="gray">
            <x-slot name="heading">Kimyasal Ekle</x-slot>

            <div style="{{ $kutu }};display:grid;grid-template-columns:1fr auto;gap:.5rem;align-items:end;margin-bottom:.5rem">
                <div>
                    <label style="font-size:.78rem;font-weight:600">Envanterden (Kimyasal Sicili)</label>
                    <select wire:model="yeniUrunId" style="{{ $girdi }}">
                        <option value="">— {{ $this->envanter->count() }} ürün —</option>
                        @foreach ($this->envanter as $u)
                            <option value="{{ $u->id }}">{{ $u->urun_adi }}</option>
                        @endforeach
                    </select>
                </div>
                <x-filament::button size="sm" wire:click="envanterdenEkle">Ekle</x-filament::button>
            </div>
            <div style="{{ $kutu }};display:grid;grid-template-columns:1fr auto;gap:.5rem;align-items:end">
                <div>
                    <label style="font-size:.78rem;font-weight:600">Serbest kimyasal adı</label>
                    <input type="text" wire:model="yeniSerbest" style="{{ $girdi }}" placeholder="örn. Tiner / Selülozik">
                </div>
                <x-filament::button size="sm" color="gray" wire:click="serbestEkle">Ekle</x-filament::button>
            </div>
        </x-filament::section>

        <x-filament::section icon="heroicon-o-table-cells" icon-color="primary">
            <x-slot name="heading">Değerlendirme ({{ count($satirlar) }} kimyasal)</x-slot>
            <x-slot name="description">Kaydetmek için sağ üstteki “Kaydet”. Kontrol yaklaşımı seçimlere göre otomatik güncellenir.</x-slot>

            @if (count($satirlar) === 0)
                <p style="color:rgb(107 114 128);font-size:.85rem">Yukarıdan kimyasal ekleyin.</p>
            @else
                @foreach ($satirlar as $i => $s)
                    @php $y = $this->yaklasimOnizle($i); @endphp
                    <div style="{{ $kutu }};margin-bottom:.6rem">
                        <div style="display:flex;justify-content:space-between;align-items:center;gap:.5rem;margin-bottom:.5rem">
                            <input type="text" wire:model.blur="satirlar.{{ $i }}.kimyasal_adi" style="{{ $girdi }};font-weight:700;max-width:320px">
                            <div style="display:flex;align-items:center;gap:.6rem">
                                <span style="font-size:.72rem;color:rgb(107 114 128)">Kontrol Yaklaşımı</span>
                                <span style="background:{{ $yaklasimRenk[$y] }};color:#fff;font-weight:700;border-radius:.35rem;padding:.15rem .55rem;font-size:.9rem">{{ $y }}</span>
                                <button type="button" wire:click="satirSil({{ $i }})" style="color:#ef4444;background:none;border:none;cursor:pointer;font-size:1rem">✕</button>
                            </div>
                        </div>
                        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:.5rem">
                            <div>
                                <label style="font-size:.7rem;font-weight:600">Tehlike Grubu</label>
                                <select wire:model.blur="satirlar.{{ $i }}.tehlike_grubu" style="{{ $girdi }}">
                                    @foreach ($this->gruplar as $g => $ad)
                                        <option value="{{ $g }}">{{ $ad }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label style="font-size:.7rem;font-weight:600">Kullanım Miktarı</label>
                                <select wire:model.blur="satirlar.{{ $i }}.miktar" style="{{ $girdi }}">
                                    @foreach ($this->miktarlar as $m => $ad)
                                        <option value="{{ $m }}">{{ $ad }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label style="font-size:.7rem;font-weight:600">Uçuculuk / Tozlaşma</label>
                                <select wire:model.blur="satirlar.{{ $i }}.ucuculuk" style="{{ $girdi }}">
                                    @foreach ($this->ucuculukSecenekleri as $u => $ad)
                                        <option value="{{ $u }}">{{ $ad }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label style="font-size:.7rem;font-weight:600">Kullanım Alanı / Bölüm</label>
                                <input type="text" wire:model.blur="satirlar.{{ $i }}.kullanim_alani" style="{{ $girdi }}">
                            </div>
                        </div>
                        <div style="display:flex;gap:1.2rem;flex-wrap:wrap;margin:.5rem 0">
                            <label style="font-size:.75rem;display:flex;align-items:center;gap:.3rem"><input type="checkbox" wire:model.blur="satirlar.{{ $i }}.cmr"> CMR (kanserojen/mutajen/üreme toksik)</label>
                            <label style="font-size:.75rem;display:flex;align-items:center;gap:.3rem"><input type="checkbox" wire:model.blur="satirlar.{{ $i }}.deri_goz_yolu"> Deri/göz teması yolu</label>
                        </div>
                        <div style="display:grid;grid-template-columns:2fr 1fr;gap:.5rem">
                            <div>
                                <label style="font-size:.7rem;font-weight:600">Alınan / Önerilen Önlemler</label>
                                <input type="text" wire:model.blur="satirlar.{{ $i }}.alinan_onlemler" style="{{ $girdi }}" placeholder="ikame, LEV, kapalı transfer, KKD, eğitim…">
                            </div>
                            <div>
                                <label style="font-size:.7rem;font-weight:600">Artık Risk</label>
                                <select wire:model.blur="satirlar.{{ $i }}.artik_risk" style="{{ $girdi }}">
                                    <option value="dusuk">Düşük</option>
                                    <option value="orta">Orta</option>
                                    <option value="yuksek">Yüksek</option>
                                </select>
                            </div>
                        </div>
                    </div>
                @endforeach
            @endif

            <div style="margin-top:.5rem">
                <label style="font-size:.8rem;font-weight:600">Genel Not</label>
                <input type="text" wire:model.blur="genelNot" style="{{ $girdi }}" placeholder="örn. GBF'ler güncel; ölçüm gereken maddeler ortam ölçümüne yönlendirildi">
            </div>
        </x-filament::section>
    @endif
</x-filament-panels::page>
