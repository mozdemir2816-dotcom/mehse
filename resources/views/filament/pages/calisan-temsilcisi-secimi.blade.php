@php
    $mavi = 'rgb(37 99 235)';
    $kutu = 'border:1px solid rgb(107 114 128 / .3);border-radius:.75rem;padding:1rem';
    $grad = 'linear-gradient(135deg, rgb(37 99 235), rgb(29 78 216))';
@endphp

<x-filament-panels::page>
    <p style="font-size:.85rem;color:rgb(107 114 128);margin-top:-.5rem">
        6331 sayılı Kanun md.20 uyarınca çalışan temsilcisi <strong>seçim</strong> süreci: duyuru → aday başvuruları →
        kesin aday listesi → oy pusulası → seçim sonucu atama tutanağı. Doğrudan atama için "Atama Yazıları"
        sayfasını kullanabilirsiniz.
    </p>

    <div style="{{ $kutu }};background:{{ $grad }};color:#fff;border:none;display:flex;align-items:center;gap:.6rem">
        <x-filament::icon icon="heroicon-o-user-group" style="width:1.4rem;height:1.4rem"/>
        <span style="font-size:1.05rem;font-weight:800">Çalışan Temsilcisi Seçimi</span>
    </div>

    <x-filament::section icon="heroicon-o-building-office" icon-color="primary">
        <x-slot name="heading">1. Firma Seçin</x-slot>

        <select wire:model.live="firmaId"
            style="width:100%;max-width:28rem;padding:.55rem .75rem;border-radius:.5rem;border:1px solid rgb(107 114 128 / .35);background:transparent">
            <option value="">— Firma seçin —</option>
            @foreach ($this->firmalar as $id => $ad)
                <option value="{{ $id }}">{{ $ad }}</option>
            @endforeach
        </select>
    </x-filament::section>

    @if ($this->firma)
        <x-filament::section icon="heroicon-o-megaphone" icon-color="primary">
            <x-slot name="heading">2. Seçim Bilgileri</x-slot>
            <x-slot name="description">Duyuru, aday listesi ve oy pusulasında kullanılır.</x-slot>

            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:1rem">
                <div>
                    <label style="font-weight:600;font-size:.82rem">İşyeri Çalışan Sayısı</label>
                    <input type="number" min="0" wire:model="isyeriCalisanSayisi"
                        style="margin-top:.3rem;width:100%;padding:.5rem .75rem;border-radius:.5rem;border:1px solid rgb(107 114 128 / .35);background:transparent">
                </div>
                <div>
                    <label style="font-weight:600;font-size:.82rem">Zorunlu Temsilci Sayısı</label>
                    <input type="number" min="1" wire:model="zorunluTemsilciSayisi"
                        style="margin-top:.3rem;width:100%;padding:.5rem .75rem;border-radius:.5rem;border:1px solid rgb(107 114 128 / .35);background:transparent">
                    <p style="font-size:.72rem;color:rgb(107 114 128);margin-top:.2rem">6331 md.20 kademesine göre önerilir, gerekirse değiştirin.</p>
                </div>
                <div>
                    <label style="font-weight:600;font-size:.82rem">Aday Başvuru Son Tarihi</label>
                    <input type="date" wire:model="adayBasvuruSonTarihi"
                        style="margin-top:.3rem;width:100%;padding:.5rem .75rem;border-radius:.5rem;border:1px solid rgb(107 114 128 / .35);background:transparent">
                </div>
                <div>
                    <label style="font-weight:600;font-size:.82rem">Seçim Tarihi</label>
                    <input type="date" wire:model="secimTarihi"
                        style="margin-top:.3rem;width:100%;padding:.5rem .75rem;border-radius:.5rem;border:1px solid rgb(107 114 128 / .35);background:transparent">
                </div>
                <div>
                    <label style="font-weight:600;font-size:.82rem">Seçim Saati</label>
                    <input type="time" wire:model="secimSaati"
                        style="margin-top:.3rem;width:100%;padding:.5rem .75rem;border-radius:.5rem;border:1px solid rgb(107 114 128 / .35);background:transparent">
                </div>
                <div>
                    <label style="font-weight:600;font-size:.82rem">Seçim Yeri</label>
                    <input type="text" wire:model="secimYeri" placeholder="Örn: Toplantı Salonu"
                        style="margin-top:.3rem;width:100%;padding:.5rem .75rem;border-radius:.5rem;border:1px solid rgb(107 114 128 / .35);background:transparent">
                </div>
                <div>
                    <label style="font-weight:600;font-size:.82rem">Görevlendirme Tarihi</label>
                    <input type="date" wire:model="gorevlendirmeTarihi"
                        style="margin-top:.3rem;width:100%;padding:.5rem .75rem;border-radius:.5rem;border:1px solid rgb(107 114 128 / .35);background:transparent">
                    <p style="font-size:.72rem;color:rgb(107 114 128);margin-top:.2rem">Boş bırakılırsa seçim tarihi kullanılır (Atama Tutanağı için).</p>
                </div>
            </div>

            <div style="margin-top:1rem">
                <x-filament::button size="sm" color="gray" wire:click="kaydetVeBildir">Kaydet</x-filament::button>
            </div>
        </x-filament::section>

        <x-filament::section icon="heroicon-o-list-bullet" icon-color="primary">
            <x-slot name="heading">3. Adaylar</x-slot>
            <x-slot name="description">Kesin Aday Listesi ve Oy Pusulası bu listeden üretilir. Seçim sonucunda kazananı işaretleyip "Atama Tutanağı"nı indirin.</x-slot>

            <div style="display:flex;flex-direction:column;gap:.5rem">
                @forelse ($adaylar as $i => $aday)
                    <div style="display:flex;align-items:center;gap:.5rem;padding:.5rem;border-radius:.5rem;
                        border:1px solid {{ $secilenAdayIndex === $i ? $mavi : 'rgb(107 114 128 / .3)' }};
                        background:{{ $secilenAdayIndex === $i ? 'rgb(37 99 235 / .08)' : 'transparent' }}">
                        <input type="text" wire:model="adaylar.{{ $i }}.ad_soyad" placeholder="Ad Soyad"
                            style="flex:2;padding:.4rem .6rem;border-radius:.4rem;border:1px solid rgb(107 114 128 / .35);background:transparent">
                        <input type="text" wire:model="adaylar.{{ $i }}.unvan" placeholder="Unvan / Görev"
                            style="flex:1;padding:.4rem .6rem;border-radius:.4rem;border:1px solid rgb(107 114 128 / .35);background:transparent">
                        <button type="button" wire:click="kazananSec({{ $i }})" title="Seçim kazananı"
                            style="background:none;border:none;cursor:pointer;font-size:1.1rem;color:{{ $secilenAdayIndex === $i ? '#16a34a' : 'rgb(107 114 128 / .4)' }}">
                            {{ $secilenAdayIndex === $i ? '✓' : '○' }}
                        </button>
                        <button type="button" wire:click="adaySil({{ $i }})" title="Sil"
                            style="background:none;border:none;cursor:pointer;color:#ef4444">✕</button>
                    </div>
                @empty
                    <p style="font-size:.82rem;color:rgb(107 114 128)">Henüz aday eklenmedi.</p>
                @endforelse
            </div>

            <div style="margin-top:.75rem">
                <x-filament::button size="sm" color="gray" icon="heroicon-o-plus" wire:click="adayEkle">Aday Ekle</x-filament::button>
            </div>
        </x-filament::section>
    @else
        <p style="margin-top:1rem;font-size:.85rem;color:#f59e0b">Devam etmek için bir firma seçin.</p>
    @endif

    <div style="{{ $kutu }};background:rgb(59 130 246 / .06);border-color:rgb(59 130 246 / .25)">
        <div style="display:flex;align-items:center;gap:.5rem;font-weight:700;color:{{ $mavi }};margin-bottom:.5rem">
            <x-filament::icon icon="heroicon-o-information-circle" style="width:1.15rem;height:1.15rem"/>
            Bilgi
        </div>
        <p style="margin:0;font-size:.78rem;color:rgb(75 85 99);line-height:1.6">
            Aday başvuru süresi 6331 sayılı Kanun ve Çalışan Temsilcisinin Nitelikleri ve Seçilme Usul ve Esaslarına
            İlişkin Tebliğ uyarınca yedi günden az olamaz. Zorunlu temsilci sayısı çalışan sayısına göre kademeli
            olarak belirlenir (2–50: 1, 51–100: 2, 101–500: 3, 501–1000: 4, 1001–2000: 5, 2001+: 6) — sistem bu
            kademeye göre öneri sunar, siz gerekirse değiştirebilirsiniz.
        </p>
    </div>
</x-filament-panels::page>
