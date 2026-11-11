{{-- Risk Sihirbazı: "Kütüphanede Olmayan Ekipman / Proses Ekle (AI)" — düğme + pencere.
     Mantık: RiskSihirbazi::aiEkipmanUret / aiEkipmanAdayToggle / aiEkipmanEkle, App\Support\GeminiEkipmanRiskUretici. --}}
<x-filament::button color="primary" icon="heroicon-o-sparkles" style="background:linear-gradient(90deg, rgb(139 92 246), rgb(217 70 239));border:none"
    x-on:click="$dispatch('open-modal', { id: 'ai-ekipman-risk' })">
    Kütüphanede Olmayan Ekipman / Proses Ekle (AI)
</x-filament::button>

<x-filament::modal id="ai-ekipman-risk" width="3xl" :close-by-clicking-away="false">
    <x-slot name="heading">Yapay Zekâ ile Ekipman Riskleri</x-slot>
    <x-slot name="description">Ekipman veya proses adını yazın; seçili yönteme ({{ config('isg.risk_yontemleri.'.$yontem, $yontem) }}) göre puanlanmış 3-5 teknik madde üretilir. Eklemeden önce her maddeyi kontrol edin.</x-slot>

    <form wire:submit="aiEkipmanUret" style="display:flex;flex-wrap:wrap;gap:.6rem;align-items:flex-start">
        <div style="flex:1;min-width:220px">
            <x-filament::input.wrapper :valid="! $errors->has('aiEkipmanAdi')">
                <x-filament::input type="text" wire:model="aiEkipmanAdi" placeholder="Örn. Plastik enjeksiyon makinesi, Şerit testere, Kumlama kabini" maxlength="120" />
            </x-filament::input.wrapper>
            @error('aiEkipmanAdi') <div style="color:#b91c1c;font-size:.78rem;margin-top:.25rem">{{ $message }}</div> @enderror
        </div>
        <x-filament::button type="submit" icon="heroicon-o-sparkles" wire:loading.attr="disabled" wire:target="aiEkipmanUret">
            Maddeleri Üret
        </x-filament::button>
    </form>

    <div wire:loading.flex wire:target="aiEkipmanUret" style="align-items:center;gap:.6rem;margin-top:1rem;font-size:.85rem;color:rgb(107 114 128)">
        <x-filament::loading-indicator style="width:1.25rem;height:1.25rem" />
        Yapay zekâ maddeleri hazırlıyor… (10-30 saniye sürebilir)
    </div>

    @if ($aiEkipmanHata)
        <div class="mehse-uyari-not">{{ $aiEkipmanHata }}</div>
    @endif

    @if ($aiEkipmanAdaylar)
        <div wire:loading.remove wire:target="aiEkipmanUret" style="margin-top:1rem;display:flex;flex-direction:column;gap:.6rem;max-height:55vh;overflow:auto">
            @foreach ($aiEkipmanAdaylar as $i => $m)
                @php
                    $isaretli = in_array($i, $aiEkipmanSecim, true);
                    $sayi = fn ($d) => rtrim(rtrim(number_format((float) $d, 1, ',', ''), '0'), ',');
                    // Mevcut ve önlem sonrası (rezidüel) puan; skor sistemce hesaplanır (RiskSkorlama).
                    if ($yontem === 'matris_5x5') {
                        $x = $m['matris'];
                        $ilk = \App\Support\RiskSkorlama::hesapla('matris_5x5', $x['olasilik'], $x['siddet']);
                        $son = \App\Support\RiskSkorlama::hesapla('matris_5x5', $x['son_olasilik'], $x['son_siddet']);
                        $ilkMetin = 'O '.$x['olasilik'].' × Ş '.$x['siddet'].' = '.$ilk['puan'];
                        $sonMetin = 'O '.$x['son_olasilik'].' × Ş '.$x['son_siddet'].' = '.$son['puan'];
                        $kabul = $son['puan'] <= \App\Support\GeminiEkipmanRiskUretici::HEDEF_5X5_EN_FAZLA;
                    } else {
                        $ilk = \App\Support\RiskSkorlama::hesapla('fine_kinney', $m['olasilik'], $m['siddet'], $m['frekans']);
                        $son = \App\Support\RiskSkorlama::hesapla('fine_kinney', $m['son_olasilik'], $m['son_siddet'], $m['son_frekans']);
                        $ilkMetin = 'O '.$sayi($m['olasilik']).' × F '.$sayi($m['frekans']).' × Ş '.$sayi($m['siddet']).' = '.$sayi($ilk['puan']);
                        $sonMetin = 'O '.$sayi($m['son_olasilik']).' × F '.$sayi($m['son_frekans']).' × Ş '.$sayi($m['son_siddet']).' = '.$sayi($son['puan']);
                        $kabul = $son['puan'] < \App\Support\GeminiEkipmanRiskUretici::HEDEF_FK_ALTI;
                    }
                @endphp
                <label wire:key="ai-aday-{{ $i }}" @style([
                    'display:flex;gap:.7rem;align-items:flex-start;padding:.75rem;border-radius:.6rem;cursor:pointer',
                    'border:2px solid '.($isaretli ? 'rgb(139 92 246)' : 'rgb(107 114 128 / .3)'),
                    'background:rgb(139 92 246 / .06)' => $isaretli,
                ])>
                    <input type="checkbox" @checked($isaretli) wire:click="aiEkipmanAdayToggle({{ $i }})" style="margin-top:.2rem">
                    <span style="min-width:0;font-size:.82rem;display:flex;flex-direction:column;gap:.25rem">
                        <span style="display:flex;flex-wrap:wrap;gap:.4rem;align-items:center">
                            <strong style="font-size:.9rem">{{ $m['tehlike'] }}</strong>
                        </span>
                        @if ($m['faaliyet']) <span class="mehse-soluk-metin">{{ $m['bolum'] }} · {{ $m['faaliyet'] }}</span> @endif
                        <span><strong>Risk:</strong> {{ $m['risk'] }}</span>
                        @if ($m['mevcut_onlem']) <span><strong>Mevcut durum:</strong> {{ $m['mevcut_onlem'] }}</span> @endif
                        <span><strong>Önerilen aksiyon:</strong> {{ $m['oneri'] }}</span>
                        @if ($m['mevzuat']) <span class="mehse-soluk-metin"><strong>Yasal dayanak:</strong> {{ $m['mevzuat'] }}</span> @endif
                        {{-- Mevcut risk → önlem sonrası (rezidüel) risk karşılaştırma şeridi --}}
                        <span style="display:flex;flex-wrap:wrap;gap:.45rem;align-items:center;margin-top:.3rem;padding-top:.45rem;border-top:1px dashed rgb(107 114 128 / .3)">
                            <x-filament::badge size="sm" color="danger" title="{{ $ilk['duzey'] }}">Mevcut Risk: {{ $ilkMetin }}</x-filament::badge>
                            <span aria-hidden="true" class="mehse-soluk-metin">→</span>
                            <x-filament::badge size="sm" :color="$kabul ? 'success' : 'warning'" title="{{ $son['duzey'] }}">Önlem Sonrası Risk: {{ $sonMetin }}</x-filament::badge>
                            @unless ($kabul)
                                <span style="font-size:.75rem;color:#b45309">Şiddet/frekans çok yüksek: olasılık en alta inse de kabul edilebilir seviyeye inmiyor — ek mühendislik önlemi gerekir.</span>
                            @endunless
                        </span>
                    </span>
                </label>
            @endforeach
        </div>
        <p style="margin-top:.6rem;font-size:.75rem;color:rgb(107 114 128)">Yapay zekâ çıktısıdır: mevzuat atıflarını ve puanları kendi değerlendirmenizle doğrulayın; eklenen maddeler Adım 5'te düzenlenebilir.</p>
    @endif

    <x-slot name="footer">
        <div style="display:flex;flex-wrap:wrap;gap:.75rem;align-items:center;justify-content:space-between;width:100%">
            @if ($this->aiKutuphaneyeKaydedebilir())
                <label style="display:flex;gap:.45rem;align-items:center;font-size:.82rem;cursor:pointer">
                    <input type="checkbox" wire:model="aiKutuphaneyeKaydet">
                    Bu maddeleri gelecekte tekrar kullanmak üzere Genel Kütüphaneye de kaydet
                </label>
            @else
                <span></span>
            @endif
            <div style="display:flex;gap:.5rem">
                <x-filament::button color="gray" x-on:click="close()">Kapat</x-filament::button>
                <x-filament::button icon="heroicon-o-plus" wire:click="aiEkipmanEkle" wire:loading.attr="disabled" :disabled="! $aiEkipmanAdaylar || ! $aiEkipmanSecim">
                    Seçilenleri Risk Tablosuna Ekle @if ($aiEkipmanAdaylar) ({{ count($aiEkipmanSecim) }}) @endif
                </x-filament::button>
            </div>
        </div>
    </x-slot>
</x-filament::modal>
