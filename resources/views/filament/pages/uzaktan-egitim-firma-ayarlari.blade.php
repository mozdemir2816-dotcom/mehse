@php
    $girdi = 'margin-top:.3rem;width:100%;padding:.55rem .75rem;border-radius:.5rem;border:1px solid rgb(107 114 128 / .35);background:transparent;color:inherit';
    $ayar = $this->ayar;
@endphp

<x-filament-panels::page>
    @include('filament.pages.partials.acil-slate-stil')
    <p style="font-size:.85rem;color:rgb(107 114 128);margin-top:-.5rem;line-height:1.5">
        Her firma için bir kez ayarlanır: eğitimi verenler, belge görünümü ve eğitim içeriği onayı.
        <strong>İçerik onayı verilmeden o firmaya eğitim gönderilemez</strong>; eğitim içeriği değişirse onay yeniden istenir.
    </p>

    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(260px,1fr));gap:1rem">
        <div>
            <label class="mehse-etiket" for="ua-firma">Firma</label>
            <select id="ua-firma" wire:model.live="firmaId" style="{{ $girdi }}">
                <option value="">— Firma seçin —</option>
                @foreach ($this->firmalar as $id => $ad)<option value="{{ $id }}">{{ $ad }}</option>@endforeach
            </select>
        </div>
        <div>
            <label class="mehse-etiket" for="ua-paket">Eğitim paketi (içerik onayı için)</label>
            <select id="ua-paket" wire:model.live="paketId" style="{{ $girdi }}">
                @foreach ($this->paketler as $id => $ad)<option value="{{ $id }}">{{ $ad }}</option>@endforeach
            </select>
        </div>
    </div>

    @if ($this->firma)
        @if ($this->onayli)
            <div style="border-left:4px solid rgb(21 128 61);background:rgb(21 128 61 / .08);padding:.6rem .8rem;border-radius:.4rem;font-size:.85rem">
                <strong>{{ $this->firma->unvan }}</strong> gönderime hazır. İçerik onayı: {{ $ayar->icerik_onaylayan }} · {{ $ayar->icerik_onay_at->format('d.m.Y H:i') }}
            </div>
        @elseif ($ayar?->icerik_onay_at)
            <div style="border-left:4px solid #d97706;background:rgb(217 119 6 / .08);padding:.6rem .8rem;border-radius:.4rem;font-size:.85rem">
                Eğitim içeriği ya da paket değişti; <strong>onay yeniden gerekiyor</strong>. İçeriği inceleyip aşağıdan onaylayın.
            </div>
        @else
            <div style="border-left:4px solid #d97706;background:rgb(217 119 6 / .08);padding:.6rem .8rem;border-radius:.4rem;font-size:.85rem">
                Bu firma için içerik onayı henüz verilmedi; onaylanana kadar gönderim yapılamaz.
            </div>
        @endif

        <x-filament::section icon="heroicon-o-user-group" icon-color="primary">
            <x-slot name="heading">Eğitimi verenler</x-slot>
            <x-slot name="description">Belgede eğitici olarak yazılır. Sağlık konuları işyeri hekimi adına verilir (md. 10/2). Boş bırakılırsa firma kartındaki uzman ve hekim kullanılır.</x-slot>
            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:1rem">
                <div style="border:1px solid rgb(107 114 128 / .25);border-radius:.75rem;padding:.9rem">
                    <div style="font-weight:700">İş güvenliği uzmanı</div>
                    <label class="mehse-etiket" for="ua-igu-ad" style="margin-top:.5rem;display:block">Ad soyad</label>
                    <input id="ua-igu-ad" type="text" wire:model="iguAd" style="{{ $girdi }}">
                    <label class="mehse-etiket" for="ua-igu-unvan" style="margin-top:.5rem;display:block">Unvan</label>
                    <input id="ua-igu-unvan" type="text" wire:model="iguUnvan" style="{{ $girdi }}">
                </div>
                <div style="border:1px solid rgb(107 114 128 / .25);border-radius:.75rem;padding:.9rem">
                    <div style="font-weight:700">İşyeri hekimi</div>
                    <label class="mehse-etiket" for="ua-hekim-ad" style="margin-top:.5rem;display:block">Ad soyad</label>
                    <input id="ua-hekim-ad" type="text" wire:model="hekimAd" style="{{ $girdi }}">
                    <label class="mehse-etiket" for="ua-hekim-unvan" style="margin-top:.5rem;display:block">Unvan</label>
                    <input id="ua-hekim-unvan" type="text" wire:model="hekimUnvan" style="{{ $girdi }}">
                </div>
            </div>
        </x-filament::section>

        <x-filament::section icon="heroicon-o-document-check" icon-color="primary">
            <x-slot name="heading">Belge görünümü</x-slot>
            <x-slot name="description">Temel Eğitim Belgesi'nin (Ek-2) imza bölümü ve üst bilgisi.</x-slot>
            <label style="display:flex;gap:.6rem;align-items:center;font-size:.9rem;min-height:2.5rem">
                <input type="checkbox" wire:model="kaseBas"> Belgeye eğiticilerin kaşesini bas (uzman ve hekim kaşe görseli kayıtlıysa)
            </label>
            <div style="margin:.6rem 0 .3rem;font-size:.85rem;font-weight:600" id="ua-logo-etiket">Logo</div>
            <div role="radiogroup" aria-labelledby="ua-logo-etiket" style="display:flex;flex-wrap:wrap;gap:.5rem">
                @foreach (\App\Models\UzaktanEgitimFirmaAyari::LOGO_KONUMLARI as $k => $ad)
                    <label style="display:inline-flex;gap:.4rem;align-items:center;border:1px solid rgb(107 114 128 / .35);border-radius:.5rem;padding:.45rem .8rem;cursor:pointer;min-height:2.5rem;font-size:.85rem">
                        <input type="radio" wire:model="logoKonumu" value="{{ $k }}"> {{ $ad }}
                    </label>
                @endforeach
            </div>
            @if (! $this->firma->logo)
                <p style="font-size:.78rem;color:#b45309;margin:.4rem 0 0">Bu firmanın logosu kayıtlı değil; logo seçilse de belgede görünmez.</p>
            @endif
            <label style="display:flex;gap:.6rem;align-items:center;font-size:.9rem;min-height:2.5rem;margin-top:.6rem">
                <input type="checkbox" wire:model="calisanImzaAlani"> Belgede boş "Çalışan imzası" alanı olsun
            </label>
        </x-filament::section>

        <x-filament::section icon="heroicon-o-check-badge" icon-color="primary">
            <x-slot name="heading">İçerik onayı</x-slot>
            <x-slot name="description">Uzaktan eğitim içeriği eğitimi verenlerce incelenip onaylanmalıdır (Eğitim Yönetmeliği md. 10, 17/5). İçerik güncellenirse onay yeniden istenir.</x-slot>
            @if ($this->paket)
                <details open style="border:1px solid rgb(107 114 128 / .25);border-radius:.75rem;padding:.7rem .9rem">
                    <summary style="cursor:pointer;font-weight:700;font-size:.9rem">
                        {{ $this->paket->ad }} — {{ collect($this->icerik)->sum(fn ($k) => count($k['videolar'])) }} video, {{ count($this->icerik) }} konu
                        <span style="font-weight:400;color:rgb(107 114 128)">· içerik sürümü {{ \App\Models\UzaktanEgitimFirmaAyari::icerikSurumu($this->paket) }}</span>
                    </summary>
                    <ul style="margin:.6rem 0 0;padding-left:1.1rem;font-size:.85rem;line-height:1.6">
                        @foreach ($this->icerik as $k)
                            <li><strong>{{ $k['konu'] }}</strong> <span style="color:rgb(107 114 128)">· {{ count($k['videolar']) }} video · {{ $k['dk'] }} dk</span></li>
                        @endforeach
                    </ul>
                </details>
            @else
                <p style="font-size:.85rem;color:rgb(107 114 128)">Önce bir eğitim paketi seçin.</p>
            @endif
            <label style="display:flex;gap:.6rem;align-items:flex-start;font-size:.9rem;margin-top:.8rem;padding:.7rem;border:1px solid rgb(79 70 229 / .4);border-radius:.6rem;background:rgb(79 70 229 / .06)">
                <input type="checkbox" wire:model="icerikOnayi" style="margin-top:.25rem">
                <span>Uzaktan eğitim içeriğini (videolar, sorular, sınav) inceledim; işyerinin iş güvenliği uzmanı / işyeri hekimi olarak onaylıyorum.</span>
            </label>
        </x-filament::section>

        <div style="display:flex;justify-content:flex-end">
            <x-filament::button wire:click="kaydet" icon="heroicon-o-check">Ayarları kaydet</x-filament::button>
        </div>
    @else
        <p style="font-size:.85rem;color:rgb(107 114 128)">Ayarları görmek için firma seçin.</p>
    @endif
</x-filament-panels::page>
