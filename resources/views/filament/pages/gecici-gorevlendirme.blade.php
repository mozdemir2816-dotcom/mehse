@php
    $girdi = 'width:100%;padding:.45rem .65rem;border-radius:.45rem;border:1px solid rgb(107 114 128 / .35);background:transparent;font-size:.84rem';
    $sablonlar = \App\Support\GeciciGorevlendirmeUretici::SABLONLAR;
@endphp

<x-filament-panels::page>
    <p class="mehse-bolum-aciklama">
        Şantiye, dış saha, şubeler arası veya 4857 m.7 geçici iş ilişkisi kapsamında çalışan görevlendirmeleri için
        yasal İSG taahhütnameli resmi görev emri PDF'i üretin (seçilen her çalışan için ayrı imzalı A4 sayfa oluşturulur).
    </p>

    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:.6rem;margin-bottom:1rem">
        @foreach ($sablonlar as $kod => $s)
            @php $aktif = $sablon === $kod; @endphp
            <button type="button" wire:click="$set('sablon', '{{ $kod }}')"
                style="text-align:left;padding:.75rem .85rem;border-radius:.65rem;cursor:pointer;
                    border:1.5px solid {{ $aktif ? '#2563eb' : 'rgb(107 114 128 / .28)' }};
                    background:{{ $aktif ? 'rgb(37 99 235 / .08)' : 'transparent' }}">
                <div style="font-size:.84rem;font-weight:700;color:{{ $aktif ? '#1d4ed8' : 'inherit' }}">{{ $s['ad'] }}</div>
                <div style="font-size:.73rem;color:rgb(107 114 128);margin-top:.2rem">{{ $s['dayanak'] }}</div>
                <div style="font-size:.74rem;margin-top:.3rem;opacity:.85">{{ $s['aciklama'] }}</div>
            </button>
        @endforeach
    </div>

    <x-filament::section icon="heroicon-o-building-office-2" icon-color="primary">
        <x-slot name="heading">1. Firma ve Görevlendirme Bilgileri</x-slot>

        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(230px,1fr));gap:.8rem">
            <div>
                <label class="mehse-etiket">Firma <span class="mehse-hata">*</span></label>
                <select wire:model.live="firmaId" style="{{ $girdi }};margin-top:.25rem">
                    <option value="">— Firma seçin —</option>
                    @foreach ($this->firmalar as $id => $ad)
                        <option value="{{ $id }}">{{ $ad }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="mehse-etiket">Görev Yeri / Şantiye / Şube <span class="mehse-hata">*</span></label>
                <input type="text" wire:model="gorevYeri" placeholder="Örn: Bursa Nilüfer Konut Şantiyesi / Ankara Şube" style="{{ $girdi }};margin-top:.25rem">
            </div>
            <div>
                <label class="mehse-etiket">Başlangıç Tarihi <span class="mehse-hata">*</span></label>
                <input type="date" wire:model="baslangicTarihi" style="{{ $girdi }};margin-top:.25rem">
            </div>
            <div>
                <label class="mehse-etiket">Bitiş Tarihi (Boşsa: İşin bitimine kadar)</label>
                <input type="date" wire:model="bitisTarihi" style="{{ $girdi }};margin-top:.25rem">
            </div>
            <div style="grid-column:1/-1">
                <label class="mehse-etiket">Görev Konusu / Yapılacak İş <span class="mehse-hata">*</span></label>
                <input type="text" wire:model="gorevKonusu" placeholder="Örn: Dış cephe elektrik tesisatı montaj ve devreye alma çalışmaları" style="{{ $girdi }};margin-top:.25rem">
            </div>
            <div>
                <label class="mehse-etiket">Harcırah / Konaklama / Yemek Notu</label>
                <input type="text" wire:model="harcirahNotu" placeholder="Örn: Konaklama ve yemek işveren tarafından karşılanır" style="{{ $girdi }};margin-top:.25rem">
            </div>
            <div>
                <label class="mehse-etiket">Araç / Plaka Bilgisi (Opsiyonel)</label>
                <input type="text" wire:model="aracPlaka" placeholder="Örn: 16 ABC 123" style="{{ $girdi }};margin-top:.25rem">
            </div>
            <div style="display:flex;flex-direction:column;justify-content:flex-end;gap:.35rem">
                <label style="display:inline-flex;align-items:center;gap:.45rem;font-size:.82rem;cursor:pointer">
                    <input type="checkbox" wire:model="tcMaskele">
                    <span>KVKK: T.C. Kimlik No Maskele (`123*****89`)</span>
                </label>
                <label style="display:inline-flex;align-items:center;gap:.45rem;font-size:.82rem;cursor:pointer">
                    <input type="checkbox" wire:model="arsiveKaydet">
                    <span>Üretilen PDF'i Firma Arşivine Otomatik Kaydet</span>
                </label>
            </div>
        </div>
    </x-filament::section>

    <x-filament::section icon="heroicon-o-users" icon-color="primary" style="margin-top:1rem">
        <x-slot name="heading">2. Görevlendirilecek Çalışanlar ({{ count($this->seciliCalisanListesi()) }} kişi seçili)</x-slot>

        @if ($this->firma && $this->calisanlar->isNotEmpty())
            <div style="display:flex;gap:.5rem;margin-bottom:.6rem;flex-wrap:wrap">
                <x-filament::button size="xs" color="gray" wire:click="tumCalisanlariSec(true)">Tümünü Seç ({{ $this->calisanlar->count() }})</x-filament::button>
                <x-filament::button size="xs" color="gray" wire:click="tumCalisanlariSec(false)">Seçimi Temizle</x-filament::button>
            </div>
            <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:.4rem;max-height:220px;overflow-y:auto;padding:.5rem;border:1px solid rgb(107 114 128 / .25);border-radius:.5rem">
                @foreach ($this->calisanlar as $c)
                    <label style="display:flex;align-items:center;gap:.45rem;font-size:.8rem;cursor:pointer;padding:.2rem">
                        <input type="checkbox" value="{{ $c->id }}" wire:model.live="secilenCalisanIdler">
                        <span><strong>{{ $c->ad_soyad }}</strong> @if($c->gorev)<span style="color:rgb(107 114 128)">({{ $c->gorev }})</span>@endif</span>
                    </label>
                @endforeach
            </div>
        @elseif ($this->firma)
            <p style="font-size:.82rem;color:rgb(107 114 128);margin:0 0 .6rem">Bu firmada kayıtlı aktif çalışan yok — aşağıdan elle ekleyebilirsiniz.</p>
        @else
            <p style="font-size:.82rem;color:rgb(107 114 128);margin:0 0 .6rem">Aktif çalışan listesini görmek için yukarıdan firma seçin.</p>
        @endif

        <div style="margin-top:.85rem;padding-top:.75rem;border-top:1px dashed rgb(107 114 128 / .25)">
            <div style="font-size:.8rem;font-weight:700;margin-bottom:.4rem">Listede Olmayan Çalışanı Elle Ekle</div>
            <div style="display:grid;grid-template-columns:2fr 1.2fr 1.5fr auto;gap:.45rem;align-items:end">
                <input type="text" wire:model="yeniAdSoyad" placeholder="Ad Soyad *" style="{{ $girdi }}">
                <input type="text" wire:model="yeniTc" placeholder="T.C. Kimlik No" style="{{ $girdi }}">
                <input type="text" wire:model="yeniGorev" placeholder="Görevi / Unvanı" style="{{ $girdi }}">
                <x-filament::button size="sm" color="gray" wire:click="manuelCalisanEkle">+ Ekle</x-filament::button>
            </div>

            @if ($manuelCalisanlar)
                <div style="display:flex;flex-wrap:wrap;gap:.4rem;margin-top:.6rem">
                    @foreach ($manuelCalisanlar as $idx => $mc)
                        <span style="display:inline-flex;align-items:center;gap:.35rem;padding:.25rem .6rem;border-radius:999px;border:1px solid rgb(107 114 128 / .3);font-size:.78rem">
                            {{ $mc['ad_soyad'] }}@if($mc['gorev']) ({{ $mc['gorev'] }})@endif
                            <button type="button" wire:click="manuelCalisanSil({{ $idx }})" style="border:none;background:none;color:#dc2626;cursor:pointer;font-weight:700">✕</button>
                        </span>
                    @endforeach
                </div>
            @endif
        </div>

        @if (count($this->seciliCalisanListesi()) === 1)
            <div style="margin-top:.85rem;padding-top:.75rem;border-top:1px dashed rgb(107 114 128 / .25)">
                @include('filament.pages.partials.canli-imza', [
                    'hedefModel' => 'canliImza',
                    'etiket' => 'Çalışan Tebellüğ Canlı İmzası (Tek çalışan için PDF imza kutusuna basılır)',
                ])
            </div>
        @endif

        <div style="margin-top:1rem">
            <x-filament::button color="primary" icon="heroicon-o-document-arrow-down" wire:click="pdfIndir">
                Geçici Görevlendirme & İSG Taahhütnamesi PDF İndir
            </x-filament::button>
        </div>
    </x-filament::section>
</x-filament-panels::page>
