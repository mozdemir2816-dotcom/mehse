@php
    $adimlar = \App\Filament\Pages\RiskSihirbazi::ADIMLAR;
    $yontemler = \App\Filament\Pages\RiskSihirbazi::YONTEMLER;
    $mor = 'rgb(139 92 246)';
    $yesil = 'rgb(34 197 94)';
@endphp

<x-filament-panels::page>
    {{-- ADIM ÇUBUĞU --------------------------------------------------------- --}}
    <div style="display:flex;align-items:flex-start;gap:.25rem;overflow-x:auto;padding-bottom:.5rem">
        @foreach ($adimlar as $no => $ad)
            @php
                $tamam = $no < $adim;
                $aktif = $no === $adim;
                $renk = $tamam ? $yesil : ($aktif ? $mor : 'rgb(107 114 128)');
            @endphp
            <div style="display:flex;align-items:center;flex:1 1 0;min-width:110px">
                <button type="button" wire:click="adimaGit({{ $no }})"
                    style="display:flex;flex-direction:column;align-items:center;gap:.35rem;flex:1;background:none;border:none;cursor:pointer">
                    <span style="width:2.25rem;height:2.25rem;border-radius:9999px;display:flex;align-items:center;justify-content:center;
                        font-weight:700;color:#fff;background:{{ $renk }};box-shadow:{{ $aktif ? '0 0 0 4px rgb(139 92 246 / .25)' : 'none' }}">
                        {{ $tamam ? '✓' : $no }}
                    </span>
                    <span style="font-size:.72rem;text-align:center;color:{{ $aktif ? 'inherit' : 'rgb(107 114 128)' }};font-weight:{{ $aktif ? 600 : 400 }}">
                        {{ $ad }}
                    </span>
                </button>
                @if (! $loop->last)
                    <span style="height:2px;flex:1;background:{{ $tamam ? $yesil : 'rgb(107 114 128 / .35)' }};margin-top:-1.1rem"></span>
                @endif
            </div>
        @endforeach
    </div>

    {{-- ======================= ADIM 1: FİRMA BİLGİLERİ ===================== --}}
    @if ($adim === 1)
        <x-filament::section icon="heroicon-o-building-office-2" icon-color="primary">
            <x-slot name="heading">Firma Bilgileri</x-slot>
            <x-slot name="description">Raporda kullanılacak firma ve tarih bilgilerini seçin</x-slot>

            <div style="display:flex;flex-direction:column;gap:1rem">
                <div>
                    <label style="font-weight:600;font-size:.85rem">Firma Seçin <span style="color:#ef4444">*</span></label>
                    <select wire:model.live="firmaId"
                        style="margin-top:.35rem;width:100%;padding:.6rem .75rem;border-radius:.5rem;border:1px solid rgb(107 114 128 / .35);background:transparent">
                        <option value="">— Firma seçin —</option>
                        @foreach ($this->firmalar as $id => $ad)
                            <option value="{{ $id }}">{{ $ad }}</option>
                        @endforeach
                    </select>
                    @if (empty($this->firmalar))
                        <p style="margin-top:.35rem;font-size:.8rem;color:#f59e0b">
                            Henüz firma yok — önce <a href="{{ \App\Filament\Resources\Firmas\FirmaResource::getUrl() }}" style="text-decoration:underline">Firmalar</a>'dan ekleyin.
                        </p>
                    @endif
                </div>

                @if ($this->firma)
                    <div style="border:1px solid rgb(139 92 246 / .35);border-radius:.75rem;padding:1rem;background:rgb(139 92 246 / .06)">
                        <div style="font-weight:700">{{ $this->firma->unvan }}</div>
                        <div style="font-size:.85rem;color:rgb(107 114 128)">{{ $this->firma->adres ?: 'Adres girilmemiş' }}</div>
                        <div style="margin-top:.5rem;display:flex;gap:.5rem;flex-wrap:wrap">
                            <x-filament::badge>{{ $this->firma->tehlikeSinifiEtiketi() }}</x-filament::badge>
                            <x-filament::badge color="gray">Geçerlilik: +{{ $this->firma->riskGecerlilikYili() }} yıl</x-filament::badge>
                        </div>
                    </div>
                @endif

                <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:1rem">
                    <div>
                        <label style="font-weight:600;font-size:.85rem">Rapor Tarihi <span style="color:#ef4444">*</span></label>
                        <input type="date" wire:model.live="raporTarihi"
                            style="margin-top:.35rem;width:100%;padding:.55rem .75rem;border-radius:.5rem;border:1px solid rgb(107 114 128 / .35);background:transparent">
                    </div>
                    <div>
                        <label style="font-weight:600;font-size:.85rem">Geçerlilik Tarihi <span style="font-weight:400;color:rgb(107 114 128)">(otomatik, değiştirilebilir)</span></label>
                        <input type="date" wire:model="gecerlilikTarihi"
                            style="margin-top:.35rem;width:100%;padding:.55rem .75rem;border-radius:.5rem;border:1px solid rgb(107 114 128 / .35);background:transparent">
                    </div>
                    <div>
                        <label style="font-weight:600;font-size:.85rem">Yöntem</label>
                        <select wire:model.live="yontem"
                            style="margin-top:.35rem;width:100%;padding:.55rem .75rem;border-radius:.5rem;border:1px solid rgb(107 114 128 / .35);background:transparent">
                            @foreach (config('isg.risk_yontemleri') as $k => $v)
                                <option value="{{ $k }}">{{ $v }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>
        </x-filament::section>
    @endif

    {{-- ======================= ADIM 2: EKLEME YÖNTEMİ ==================== --}}
    @if ($adim === 2)
        <x-filament::section icon="heroicon-o-queue-list" icon-color="primary">
            <x-slot name="heading">Risk Ekleme Yöntemi</x-slot>
            <x-slot name="description">Riskleri nasıl eklemek istediğinizi seçin</x-slot>

            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:1rem">
                @foreach ($yontemler as $anahtar => $y)
                    @php $secili = $yontemSecim === $anahtar; @endphp
                    <button type="button" wire:click="yontemSec('{{ $anahtar }}')"
                        @style([
                            'text-align:left;padding:1rem;border-radius:.75rem;cursor:pointer;position:relative',
                            'border:2px solid '.($secili ? $mor : 'rgb(107 114 128 / .3)'),
                            'background:'.($secili ? 'rgb(139 92 246 / .08)' : 'transparent'),
                            'opacity:.55' => ! $y['hazir'],
                        ])>
                        @if ($y['onerilen'])
                            <span style="position:absolute;top:-.6rem;left:1rem;font-size:.65rem;font-weight:700;background:{{ $mor }};color:#fff;padding:.15rem .5rem;border-radius:9999px">ÖNERİLEN</span>
                        @endif
                        @unless ($y['hazir'])
                            <span style="position:absolute;top:.5rem;right:.5rem;font-size:.65rem;color:rgb(107 114 128)">yakında</span>
                        @endunless
                        <div style="font-weight:700;margin-bottom:.35rem">{{ $y['ad'] }}</div>
                        <div style="font-size:.8rem;color:rgb(107 114 128)">{{ $y['aciklama'] }}</div>
                    </button>
                @endforeach
            </div>

            @if ($yontemSecim)
                <div style="margin-top:1rem;border:1px solid {{ $mor }};border-radius:.5rem;padding:.75rem;font-size:.85rem;background:rgb(139 92 246 / .06)">
                    <strong>{{ $yontemler[$yontemSecim]['ad'] }}</strong> seçildi. Devam etmek için "İleri" butonuna tıklayın.
                </div>
            @endif
        </x-filament::section>
    @endif

    {{-- ======================= ADIM 3: RİSK EKLEME (MANUEL) ============= --}}
    @if ($adim === 3)
        <x-filament::section icon="heroicon-o-book-open" icon-color="primary">
            <x-slot name="heading">Manuel Risk Seçimi</x-slot>
            <x-slot name="description">Risk Kütüphanesinden seçim yapın — seçilenler: {{ count($secilenler) }}</x-slot>

            <div style="display:flex;flex-direction:column;gap:.5rem">
                @foreach ($this->kategoriler as $kat)
                    @php $acik = in_array($kat->id, $acikKategoriler, true); @endphp
                    <div style="border:1px solid rgb(107 114 128 / .3);border-radius:.6rem;overflow:hidden">
                        <div style="display:flex;align-items:center;justify-content:space-between;padding:.75rem 1rem;background:rgb(107 114 128 / .07)">
                            <button type="button" wire:click="kategoriToggle({{ $kat->id }})"
                                style="display:flex;align-items:center;gap:.5rem;background:none;border:none;cursor:pointer;font-weight:600">
                                <span>{{ $acik ? '▾' : '▸' }}</span>
                                {{ $kat->ad }}
                                <x-filament::badge color="gray">{{ $kat->tehlikeler->count() }}</x-filament::badge>
                            </button>
                            <x-filament::button size="xs" color="gray" wire:click="kategoriTumunuEkle({{ $kat->id }})">
                                Tümünü ekle
                            </x-filament::button>
                        </div>
                        @if ($acik)
                            <div style="display:flex;flex-direction:column">
                                @foreach ($kat->tehlikeler as $t)
                                    @php $ekli = $this->tehlikeSecili($t->id); @endphp
                                    <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:1rem;padding:.6rem 1rem;border-top:1px solid rgb(107 114 128 / .18)">
                                        <div style="font-size:.85rem">
                                            <div style="font-weight:600">{{ $t->tehlike }}</div>
                                            <div style="color:rgb(107 114 128)">{{ $t->bolum }} · {{ $t->faaliyet }}</div>
                                        </div>
                                        <x-filament::button size="xs" :color="$ekli ? 'success' : 'primary'"
                                            :disabled="$ekli" wire:click="tehlikeEkle({{ $t->id }})"
                                            style="flex-shrink:0">
                                            {{ $ekli ? 'Eklendi ✓' : '+ Ekle' }}
                                        </x-filament::button>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>

            <div style="margin-top:1rem">
                <x-filament::button color="gray" icon="heroicon-o-plus" wire:click="bosMaddeEkle">
                    Elle boş madde ekle
                </x-filament::button>
            </div>
        </x-filament::section>
    @endif

    {{-- ======================= ADIM 4: TERCİHLER ======================== --}}
    @if ($adim === 4)
        <x-filament::section icon="heroicon-o-adjustments-horizontal" icon-color="primary">
            <x-slot name="heading">Tercihler</x-slot>
            <x-slot name="description">Tüm maddelere uygulanacak varsayılanlar</x-slot>

            <div style="display:flex;flex-direction:column;gap:1rem;max-width:32rem">
                <label style="display:flex;align-items:center;gap:.6rem;font-size:.9rem">
                    <input type="checkbox" wire:model="etkilenenDiger">
                    Taşeron / ziyaretçiler de etkileniyor (tüm maddeler)
                </label>
                <div>
                    <label style="font-weight:600;font-size:.85rem">Varsayılan termin</label>
                    <input type="text" wire:model="varsayilanTermin" placeholder='gg.aa.yyyy veya "Sürekli"'
                        style="margin-top:.35rem;width:100%;padding:.55rem .75rem;border-radius:.5rem;border:1px solid rgb(107 114 128 / .35);background:transparent">
                    <p style="font-size:.78rem;color:rgb(107 114 128);margin-top:.3rem">Madde bazında boş bırakılan terminler bununla doldurulur.</p>
                </div>
            </div>
        </x-filament::section>
    @endif

    {{-- ======================= ADIM 5: RİSKLERİM ======================== --}}
    @if ($adim === 5)
        <x-filament::section icon="heroicon-o-table-cells" icon-color="primary">
            <x-slot name="heading">Risklerim ({{ count($secilenler) }})</x-slot>
            <x-slot name="description">
                {{ $this->fineKinney ? 'Olasılık × Frekans × Şiddet' : 'Olasılık × Şiddet' }} girin — puan otomatik hesaplanır
            </x-slot>

            @if (count($secilenler) === 0)
                <p style="color:rgb(107 114 128)">Henüz madde yok. Adım 3'e dönüp ekleyin.</p>
            @endif

            <div style="display:flex;flex-direction:column;gap:.75rem">
                @foreach ($secilenler as $i => $m)
                    @php $p = $this->maddePuani($m); @endphp
                    <div wire:key="madde-{{ $m['anahtar'] }}"
                        style="border:1px solid rgb(107 114 128 / .3);border-radius:.6rem;padding:.85rem">
                        <div style="display:flex;justify-content:space-between;gap:1rem">
                            <div style="flex:1">
                                <input type="text" wire:model.blur="secilenler.{{ $i }}.tehlike" placeholder="Tehlike"
                                    style="width:100%;font-weight:600;padding:.35rem .5rem;border-radius:.4rem;border:1px solid rgb(107 114 128 / .25);background:transparent">
                                <div style="display:grid;grid-template-columns:1fr 1fr;gap:.5rem;margin-top:.4rem">
                                    <input type="text" wire:model.blur="secilenler.{{ $i }}.bolum" placeholder="Bölüm"
                                        style="padding:.3rem .5rem;border-radius:.4rem;border:1px solid rgb(107 114 128 / .25);background:transparent;font-size:.82rem">
                                    <input type="text" wire:model.blur="secilenler.{{ $i }}.faaliyet" placeholder="Faaliyet"
                                        style="padding:.3rem .5rem;border-radius:.4rem;border:1px solid rgb(107 114 128 / .25);background:transparent;font-size:.82rem">
                                </div>
                            </div>
                            <button type="button" wire:click="maddeCikar('{{ $m['anahtar'] }}')"
                                style="background:none;border:none;color:#ef4444;cursor:pointer;font-size:1.1rem;flex-shrink:0">✕</button>
                        </div>

                        <div style="display:flex;flex-wrap:wrap;align-items:flex-end;gap:.6rem;margin-top:.6rem">
                            <div>
                                <label style="font-size:.72rem;color:rgb(107 114 128)">Olasılık</label>
                                <select wire:model.live="secilenler.{{ $i }}.olasilik"
                                    style="display:block;padding:.35rem .5rem;border-radius:.4rem;border:1px solid rgb(107 114 128 / .3);background:transparent">
                                    <option value="">—</option>
                                    @foreach (\App\Support\RiskSkorlama::olcek($yontem, 'olasilik') as $deger => $etiket)
                                        <option value="{{ $deger }}">{{ $deger }} — {{ $etiket }}</option>
                                    @endforeach
                                </select>
                            </div>
                            @if ($this->fineKinney)
                                <div>
                                    <label style="font-size:.72rem;color:rgb(107 114 128)">Frekans</label>
                                    <select wire:model.live="secilenler.{{ $i }}.frekans"
                                        style="display:block;padding:.35rem .5rem;border-radius:.4rem;border:1px solid rgb(107 114 128 / .3);background:transparent">
                                        <option value="">—</option>
                                        @foreach (\App\Support\RiskSkorlama::olcek('fine_kinney', 'frekans') as $deger => $etiket)
                                            <option value="{{ $deger }}">{{ $deger }} — {{ $etiket }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            @endif
                            <div>
                                <label style="font-size:.72rem;color:rgb(107 114 128)">Şiddet</label>
                                <select wire:model.live="secilenler.{{ $i }}.siddet"
                                    style="display:block;padding:.35rem .5rem;border-radius:.4rem;border:1px solid rgb(107 114 128 / .3);background:transparent">
                                    <option value="">—</option>
                                    @foreach (\App\Support\RiskSkorlama::olcek($yontem, 'siddet') as $deger => $etiket)
                                        <option value="{{ $deger }}">{{ $deger }} — {{ $etiket }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div style="padding:.3rem .7rem;border-radius:.4rem;font-weight:700;color:#fff;background:{{ $p['renk'] }}">
                                {{ $p['puan'] ? $p['puan'].' · '.$p['duzey'] : 'puan yok' }}
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </x-filament::section>
    @endif

    {{-- ======================= ADIM 6: ÖNİZLEME & KAYDET =============== --}}
    @if ($adim === 6)
        <x-filament::section icon="heroicon-o-document-check" icon-color="primary">
            <x-slot name="heading">Önizleme & Kaydet</x-slot>
            <x-slot name="description">Kontrol edin ve risk değerlendirmesini kaydedin</x-slot>

            <div style="display:flex;flex-wrap:wrap;gap:.75rem;margin-bottom:1rem">
                <x-filament::badge>{{ $this->firma?->unvan }}</x-filament::badge>
                <x-filament::badge color="gray">{{ config('isg.risk_yontemleri.'.$yontem) }}</x-filament::badge>
                <x-filament::badge color="gray">Rapor: {{ $raporTarihi }}</x-filament::badge>
                <x-filament::badge color="gray">Geçerlilik: {{ $gecerlilikTarihi }}</x-filament::badge>
                <x-filament::badge color="gray">{{ count($secilenler) }} madde</x-filament::badge>
            </div>

            <div style="display:flex;flex-wrap:wrap;gap:.5rem">
                @foreach ($this->duzeyDagilimi as $duzey => $adet)
                    <div style="border:1px solid rgb(107 114 128 / .3);border-radius:.5rem;padding:.4rem .75rem;font-size:.85rem">
                        <strong>{{ $adet }}</strong> · {{ $duzey }}
                    </div>
                @endforeach
            </div>

            @unless ($this->tumMaddelerPuanli())
                <p style="margin-top:1rem;color:#ef4444;font-size:.85rem">Bazı maddelerin puanı eksik — Adım 5'e dönün.</p>
            @endunless

            <div style="margin-top:1.25rem;display:flex;gap:.75rem">
                <x-filament::button color="primary" icon="heroicon-o-check" wire:click="kaydet"
                    :disabled="! $this->tumMaddelerPuanli()">
                    Kaydet
                </x-filament::button>
                <x-filament::button color="gray" disabled icon="heroicon-o-document-arrow-down">
                    PDF indir (yakında)
                </x-filament::button>
            </div>
        </x-filament::section>
    @endif

    {{-- NAVİGASYON --------------------------------------------------------- --}}
    <div style="display:flex;align-items:center;justify-content:space-between;margin-top:.5rem">
        <div>
            @if ($adim > 1)
                <x-filament::button color="gray" icon="heroicon-o-chevron-left" wire:click="geri">Geri</x-filament::button>
            @endif
        </div>
        <div style="font-size:.8rem;color:rgb(107 114 128)">{{ $adim }} / 6</div>
        <div>
            @if ($adim < 6)
                <x-filament::button icon="heroicon-o-chevron-right" icon-position="after"
                    wire:click="ileri" :disabled="! $this->adimGecerli($adim)">
                    İleri
                </x-filament::button>
            @endif
        </div>
    </div>
</x-filament-panels::page>
