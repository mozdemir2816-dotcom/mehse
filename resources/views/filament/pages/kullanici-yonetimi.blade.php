<x-filament-panels::page>
    <p style="font-size:.85rem;color:rgb(128 116 148)">
        Kayıt olan kullanıcılar varsayılan olarak hiçbir sayfa görmez. Aşağıdan her kullanıcı
        için erişebileceği sayfaları ve firmaları seçip kaydedin.
    </p>

    <div style="display:flex;flex-direction:column;gap:.75rem">
        @forelse ($this->kullanicilar as $kullanici)
            @php $acik = $this->acikKullaniciId === $kullanici->id; @endphp
            <div style="border:1px solid rgb(128 116 148 / .3);border-radius:.75rem;overflow:hidden">
                <div style="display:flex;align-items:center;justify-content:space-between;gap:.75rem;padding:.85rem 1rem;flex-wrap:wrap">
                    <div style="cursor:pointer;flex:1;min-width:220px" wire:click="ac({{ $kullanici->id }})">
                        <div style="font-weight:700">{{ $kullanici->name }}</div>
                        <div style="font-size:.8rem;color:rgb(128 116 148)">
                            {{ $kullanici->email }} · {{ $kullanici->telefon ?: '—' }} · {{ $kullanici->rol }}
                        </div>
                    </div>
                    <div style="display:flex;align-items:center;gap:.6rem">
                        <label style="display:flex;align-items:center;gap:.35rem;font-size:.8rem;cursor:pointer">
                            <input type="checkbox" wire:click="aktifDegistir({{ $kullanici->id }})" @checked($kullanici->aktif)>
                            Aktif
                        </label>
                        <x-filament::button size="sm" color="gray" wire:click="ac({{ $kullanici->id }})">
                            {{ $acik ? 'Kapat' : 'Yetkileri Düzenle' }}
                        </x-filament::button>
                    </div>
                </div>

                @if ($acik)
                    <div style="padding:1rem;border-top:1px solid rgb(128 116 148 / .2);display:grid;gap:1.25rem;grid-template-columns:repeat(auto-fit,minmax(260px,1fr))">
                        <div>
                            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:.5rem">
                                <div style="font-weight:600;font-size:.85rem">Sayfalar</div>
                                <button type="button" wire:click="tumSayfalariSec" style="font-size:.75rem;color:rgb(139 92 246);background:none;border:none;cursor:pointer;text-decoration:underline">Tümünü Seç</button>
                            </div>
                            <div style="display:flex;flex-direction:column;gap:.3rem;max-height:320px;overflow:auto">
                                @foreach ($this->sayfaKatalogu as $anahtar => $ad)
                                    <label style="display:flex;align-items:center;gap:.4rem;font-size:.8rem">
                                        <input type="checkbox" wire:model="sayfaSecim.{{ $anahtar }}">
                                        {{ $ad }}
                                    </label>
                                @endforeach
                            </div>
                        </div>
                        <div>
                            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:.5rem">
                                <div style="font-weight:600;font-size:.85rem">Firmalar</div>
                                <button type="button" wire:click="tumFirmalariSec" style="font-size:.75rem;color:rgb(139 92 246);background:none;border:none;cursor:pointer;text-decoration:underline">Tümünü Seç</button>
                            </div>
                            <div style="display:flex;flex-direction:column;gap:.3rem;max-height:320px;overflow:auto">
                                @foreach ($this->firmalar as $firma)
                                    <label style="display:flex;align-items:center;gap:.4rem;font-size:.8rem">
                                        <input type="checkbox" wire:model="firmaSecim.{{ $firma->id }}">
                                        {{ $firma->unvan }}
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    </div>
                    <div style="padding:0 1rem 1rem">
                        <x-filament::button wire:click="kaydet">Kaydet</x-filament::button>
                    </div>
                @endif
            </div>
        @empty
            <p style="font-size:.85rem;color:rgb(128 116 148)">Henüz kayıt olan başka kullanıcı yok.</p>
        @endforelse
    </div>
</x-filament-panels::page>
