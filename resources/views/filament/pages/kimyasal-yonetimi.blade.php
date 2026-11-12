@php
    $inp = 'margin-top:.3rem;width:100%;max-width:420px;padding:.55rem .75rem;border-radius:.5rem;border:1px solid rgb(107 114 128 / .35);background:transparent';
    $kart = 'border:1px solid rgb(107 114 128 / .3);border-radius:.9rem;padding:1.1rem 1.2rem;display:flex;flex-direction:column;gap:.7rem';
    $s = $this->sds;
    $r = $this->risk;
    $p = $this->pkd;
    $sayi = fn ($deger, string $ad, string $renk = 'inherit') => '<div><div style="font-size:1.25rem;font-weight:700;color:'.($deger > 0 ? $renk : 'inherit').'">'.e($deger).'</div><div class="mehse-soluk">'.e($ad).'</div></div>';
@endphp

<x-filament-panels::page>
    <p class="mehse-bolum-aciklama">
        Kimyasal güvenliği ve patlamadan korunma tek merkezde: Güvenlik Bilgi Formları (SDS / GBF), kimyasal risk
        değerlendirmesi, kimyasal afişleri ve Patlamadan Korunma Dokümanları. İşyeri seçerseniz sayılar ve açılan
        sayfalar o işyerine göre gelir.
    </p>

    <div>
        <label class="mehse-etiket">İşyeri</label><br>
        <select wire:model.live="firmaId" style="{{ $inp }}">
            <option value="">Tüm işyerleri</option>
            @foreach ($this->firmalar as $id => $ad)
                <option value="{{ $id }}">{{ $ad }}</option>
            @endforeach
        </select>
    </div>

    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:1rem">
        {{-- SDS / GBF --}}
        <div style="{{ $kart }}">
            <div style="display:flex;gap:.6rem;align-items:center">
                <x-filament::icon icon="heroicon-o-document-text" style="width:1.6rem;height:1.6rem;color:rgb(20 184 166)" />
                <div>
                    <div style="font-weight:700">SDS / GBF Sicili</div>
                    <div class="mehse-soluk">Kimyasal envanteri, güvenlik bilgi formları, GHS etiketleri</div>
                </div>
            </div>
            <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:.5rem">
                {!! $sayi($s['toplam'], 'Ürün') !!}
                {!! $sayi($s['sds_yok'], 'SDS eksik', '#dc2626') !!}
                {!! $sayi($s['etiketli'], 'GHS işaretli') !!}
                {!! $sayi($s['gecikmis'], 'Gecikmiş', '#dc2626') !!}
            </div>
            <x-filament::button tag="a" :href="$this->adres(\App\Filament\Pages\KimyasalSicili::class)" icon="heroicon-o-arrow-right" icon-position="after">SDS / GBF Sicilini Aç</x-filament::button>
        </div>

        {{-- RİSK --}}
        <div style="{{ $kart }}">
            <div style="display:flex;gap:.6rem;align-items:center">
                <x-filament::icon icon="heroicon-o-shield-exclamation" style="width:1.6rem;height:1.6rem;color:rgb(124 58 237)" />
                <div>
                    <div style="font-weight:700">Kimyasal Risk Değerlendirmesi</div>
                    <div class="mehse-soluk">Kontrol bantlama (COSHH) — envanterden otomatik satır</div>
                </div>
            </div>
            <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:.5rem">
                {!! $sayi($r['firma'], 'Firma') !!}
                {!! $sayi($r['satir'], 'Kimyasal') !!}
                {!! $sayi($r['yuksek'], 'Yaklaşım 3-4', '#b45309') !!}
                {!! $sayi($r['cmr'], 'CMR', '#dc2626') !!}
            </div>
            <x-filament::button tag="a" :href="$this->adres(\App\Filament\Pages\KimyasalRiskDegerlendirmesi::class)" icon="heroicon-o-arrow-right" icon-position="after" color="gray">Risk Değerlendirmesini Aç</x-filament::button>
        </div>

        {{-- AFİŞ --}}
        <div style="{{ $kart }}">
            <div style="display:flex;gap:.6rem;align-items:center">
                <x-filament::icon icon="heroicon-o-photo" style="width:1.6rem;height:1.6rem;color:rgb(180 83 9)" />
                <div>
                    <div style="font-weight:700">Kimyasal Afişleri</div>
                    <div class="mehse-soluk">GHS levhaları, kimyasal güvenliği posterleri</div>
                </div>
            </div>
            <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:.5rem">
                {!! $sayi($this->afis, 'Afiş') !!}
            </div>
            <x-filament::button tag="a" :href="$this->adres(\App\Filament\Pages\KimyasalSicili::class, 'afisler')" icon="heroicon-o-arrow-right" icon-position="after" color="gray">Afiş Kütüphanesini Aç</x-filament::button>
        </div>

        {{-- PKD --}}
        <div style="{{ $kart }}">
            <div style="display:flex;gap:.6rem;align-items:center">
                <x-filament::icon icon="heroicon-o-fire" style="width:1.6rem;height:1.6rem;color:rgb(194 65 12)" />
                <div>
                    <div style="font-weight:700">PKD Sicili</div>
                    <div class="mehse-soluk">Patlamadan korunma dokümanları, zone ve önlem takibi</div>
                </div>
            </div>
            <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:.5rem">
                {!! $sayi($p['toplam'], 'PKD') !!}
                {!! $sayi($p['dosyasiz'], 'Dosya eksik', '#dc2626') !!}
                {!! $sayi($p['takip'], 'Takip', '#b45309') !!}
            </div>
            <x-filament::button tag="a" :href="$this->adres(\App\Filament\Pages\PkdSicili::class)" icon="heroicon-o-arrow-right" icon-position="after" color="warning">PKD Sicilini Aç</x-filament::button>
        </div>
    </div>
</x-filament-panels::page>
