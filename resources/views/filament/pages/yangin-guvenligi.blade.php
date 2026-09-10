@php
    $lbl = 'font-weight:600;font-size:.82rem;display:block;margin-bottom:.25rem';
    $inp = 'width:100%;padding:.45rem .6rem;border-radius:.45rem;border:1px solid rgb(107 114 128 / .35);background:transparent;font-size:.82rem';
    $sinifRenk = ['dusuk' => '#16a34a', 'orta' => '#d97706', 'yuksek' => '#dc2626'];
    $ozelSorular = ['kapali_otopark' => 'Kapalı otopark var mı?', 'bodrum' => 'Bodrum kat var mı?', 'konaklama' => 'Konaklama / yataklı bölüm var mı?', 'toplanti' => 'Toplantı / eğlence alanı var mı?', 'kazan_dairesi' => 'Kazan dairesi var mı?'];
@endphp

<x-filament-panels::page>
    <p style="font-size:.85rem;color:rgb(107 114 128);margin-top:-.5rem">
        Bina kullanımı, kat/kullanıcı yükü, bölümler ve faaliyet/depolama risklerini girin;
        bina yangın tehlike sınıfı otomatik belirlenir (Binaların Yangından Korunması Hakkında
        Yönetmelik). Kütüphaneden tespit seçip "Değerlendirme Raporu" alın.
    </p>

    <x-filament::section icon="heroicon-o-fire" icon-color="danger">
        <x-slot name="heading">Firma</x-slot>
        <select wire:model.live="firmaId" style="{{ $inp }};max-width:420px">
            <option value="">— Firma seçin —</option>
            @foreach ($this->firmalar as $id => $ad)
                <option value="{{ $id }}">{{ $ad }}</option>
            @endforeach
        </select>
    </x-filament::section>

    @if ($this->firma)
        {{-- Tehlike sınıfı önizleme --}}
        <div style="border:2px solid {{ $sinifRenk[$this->anlikSinif] }};border-radius:.75rem;padding:1rem;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:.75rem">
            <div>
                <div style="font-size:.75rem;color:rgb(107 114 128);font-weight:700">BİNA YANGIN TEHLİKE SINIFI</div>
                <div style="font-size:1.5rem;font-weight:800;color:{{ $sinifRenk[$this->anlikSinif] }}">{{ $this->siniflar[$this->anlikSinif] }}</div>
            </div>
            <label style="display:flex;align-items:center;gap:.4rem;font-size:.8rem">
                <input type="checkbox" wire:model.live="sinifElle"> Sınıfı elle belirle
                @if ($sinifElle)
                    <select wire:model.live="elleSinif" style="{{ $inp }};max-width:160px;margin-left:.5rem">
                        <option value="">— seçin —</option>
                        @foreach ($this->siniflar as $a => $ad) <option value="{{ $a }}">{{ $ad }}</option> @endforeach
                    </select>
                @endif
            </label>
        </div>

        {{-- 1. BİNA / KULLANIM --}}
        <x-filament::section icon="heroicon-o-building-office-2" icon-color="danger">
            <x-slot name="heading">1. Bina Kullanımı ve Yangın Tehlike Sınıfı Ön Belirlemesi</x-slot>

            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:1rem">
                <div><label style="{{ $lbl }}">İnceleme Yeri / Bölüm</label><input type="text" wire:model="incelemeYeri" style="{{ $inp }}"></div>
                <div>
                    <label style="{{ $lbl }}">Yapı Durumu</label>
                    <select wire:model="yapiDurumu" style="{{ $inp }}">
                        <option value="">— seçin —</option>
                        @foreach ($this->yapiDurumlari as $y) <option value="{{ $y }}">{{ $y }}</option> @endforeach
                    </select>
                </div>
                <div>
                    <label style="{{ $lbl }}">İşletme / Kullanım Türü</label>
                    <select wire:model.live="kullanimTuru" style="{{ $inp }}">
                        <option value="">— seçin —</option>
                        @foreach ($this->kullanimTurleri as $a => $ad) <option value="{{ $a }}">{{ $ad }}</option> @endforeach
                    </select>
                </div>
                <div><label style="{{ $lbl }}">Bina Taban / Yerleşim Alanı (m²)</label><input type="number" min="0" step="0.01" wire:model="tabanAlaniM2" style="{{ $inp }}"></div>
                <div><label style="{{ $lbl }}">Zemin Üstü Kat Sayısı</label><input type="number" min="0" wire:model="katSayisi" style="{{ $inp }}"></div>
                <div><label style="{{ $lbl }}">Azami Toplam Kullanıcı Yükü</label><input type="number" min="0" wire:model.live.debounce.500ms="kullaniciYuku" style="{{ $inp }}"></div>
            </div>

            <div style="margin-top:1rem">
                <label style="{{ $lbl }}">Kullanımın Kısa Açıklaması</label>
                <textarea wire:model="kullanimAciklamasi" rows="2" style="{{ $inp }}"></textarea>
            </div>

            <div style="margin-top:1rem">
                <label style="{{ $lbl }}">Özel Kullanım Bilgileri</label>
                <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(240px,1fr));gap:.35rem">
                    @foreach ($ozelSorular as $anahtar => $soru)
                        <label style="display:flex;align-items:center;gap:.35rem;font-size:.8rem;cursor:pointer">
                            <input type="checkbox" wire:model="ozelKullanimlar.{{ $anahtar }}"> {{ $soru }}
                        </label>
                    @endforeach
                </div>
            </div>
        </x-filament::section>

        {{-- 2. BÖLÜMLER --}}
        <x-filament::section icon="heroicon-o-squares-2x2" icon-color="warning">
            <x-slot name="heading">2. İşletmede Bulunan Bölümler</x-slot>
            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(240px,1fr));gap:.35rem">
                @foreach ($this->bolumKutuphanesi as $b)
                    <label style="display:flex;align-items:center;gap:.35rem;font-size:.8rem;cursor:pointer">
                        <input type="checkbox" wire:model="bolumler" value="{{ $b }}"> {{ $b }}
                    </label>
                @endforeach
            </div>
        </x-filament::section>

        {{-- 3. RİSKLER --}}
        <x-filament::section icon="heroicon-o-exclamation-triangle" icon-color="danger">
            <x-slot name="heading">3. Ek Faaliyet / Depolama Riskleri</x-slot>
            <x-slot name="description">Seçilen risklerin en yükseği bina yangın tehlike sınıfını belirler.</x-slot>
            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(300px,1fr));gap:.35rem">
                @foreach ($this->riskKutuphanesi as $risk => $sinif)
                    <label style="display:flex;align-items:center;gap:.35rem;font-size:.8rem;cursor:pointer">
                        <input type="checkbox" wire:model.live="riskler" value="{{ $risk }}"> {{ $risk }}
                        <span style="font-size:.66rem;color:{{ $sinifRenk[$sinif] }};font-weight:700">[{{ $this->siniflar[$sinif] }}]</span>
                    </label>
                @endforeach
            </div>
        </x-filament::section>

        {{-- 4. TESPİTLER --}}
        <x-filament::section icon="heroicon-o-clipboard-document-check" icon-color="primary">
            <x-slot name="heading">4. Tespit ve Öneriler ({{ count($tespitler) }})</x-slot>

            <div style="display:flex;flex-wrap:wrap;gap:.35rem;margin-bottom:.75rem">
                @foreach ($this->tespitKutuphanesi as $i => $t)
                    <x-filament::button size="xs" color="gray" icon="heroicon-o-plus" wire:click="tespitEkle({{ $i }})">
                        {{ \Illuminate\Support\Str::limit($t['madde'], 48) }}
                    </x-filament::button>
                @endforeach
            </div>

            <div x-data="{ metin: '' }" style="display:flex;gap:.5rem;margin-bottom:.75rem">
                <input type="text" x-model="metin" placeholder="Serbest tespit ekle…" style="{{ $inp }}">
                <x-filament::button size="sm" x-on:click="$wire.serbestTespitEkle(metin); metin=''">Ekle</x-filament::button>
            </div>

            @forelse ($tespitler as $i => $t)
                <div style="display:flex;gap:.5rem;align-items:start;margin-bottom:.3rem;font-size:.82rem">
                    <span style="font-weight:700;color:rgb(107 114 128)">{{ $i + 1 }}.</span>
                    <span style="flex:1">{{ $t['madde'] }}</span>
                    <span style="font-size:.68rem;color:{{ $t['oncelik'] === 'yuksek' ? '#dc2626' : '#d97706' }};font-weight:700">{{ $t['oncelik'] === 'yuksek' ? 'Yüksek' : 'Orta' }}</span>
                    <button type="button" wire:click="tespitSil({{ $i }})" style="color:#ef4444;border:none;background:none;cursor:pointer">✕</button>
                </div>
            @empty
                <p style="font-size:.82rem;color:rgb(107 114 128)">Kütüphaneden tespit ekleyin.</p>
            @endforelse

            <div style="margin-top:1rem">
                <label style="{{ $lbl }}">Genel Not / Değerlendirme</label>
                <textarea wire:model="genelNot" rows="2" style="{{ $inp }}"></textarea>
            </div>
        </x-filament::section>
    @endif
</x-filament-panels::page>
