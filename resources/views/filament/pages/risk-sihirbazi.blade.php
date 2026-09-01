@php
    $adimlar = \App\Filament\Pages\RiskSihirbazi::ADIMLAR;
    $yontemler = \App\Filament\Pages\RiskSihirbazi::YONTEMLER;
    $ileriEtiket = \App\Filament\Pages\RiskSihirbazi::ILERI_ETIKET;
    $sorular = config('isg.risk_ai.sorular', []);
    $sektorler = config('isg.risk_ai.sektorler', []);
    $mor = 'rgb(139 92 246)';
    $yesil = 'rgb(34 197 94)';
    $grad = 'linear-gradient(90deg, rgb(139 92 246), rgb(217 70 239))';
    $kutu = 'border:1px solid rgb(107 114 128 / .3);border-radius:.6rem';
@endphp

<x-filament-panels::page>
    {{-- BAŞLIK + SIFIRLA -------------------------------------------------- --}}
    <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:1rem;flex-wrap:wrap">
        <div>
            <div style="font-size:1.15rem;font-weight:700">Risk Değerlendirme Sihirbazı</div>
            <div style="font-size:.85rem;color:rgb(107 114 128)">Adım adım risk değerlendirme raporu oluşturun</div>
        </div>
        <x-filament::button color="gray" size="sm" icon="heroicon-o-arrow-path"
            wire:click="sifirla" wire:confirm="Tüm sihirbaz verileri sıfırlansın mı?">
            Sıfırla
        </x-filament::button>
    </div>

    {{-- ADIM ÇUBUĞU ------------------------------------------------------- --}}
    <div style="display:flex;align-items:flex-start;gap:.25rem;overflow-x:auto;padding-bottom:.5rem">
        @foreach ($adimlar as $no => $ad)
            @php
                $tamam = $no < $adim; $aktif = $no === $adim;
                $renk = $tamam ? $yesil : ($aktif ? $mor : 'rgb(107 114 128)');
            @endphp
            <div style="display:flex;align-items:center;flex:1 1 0;min-width:110px">
                <button type="button" wire:click="adimaGit({{ $no }})"
                    style="display:flex;flex-direction:column;align-items:center;gap:.35rem;flex:1;background:none;border:none;cursor:pointer">
                    <span style="width:2.25rem;height:2.25rem;border-radius:9999px;display:flex;align-items:center;justify-content:center;
                        font-weight:700;color:#fff;background:{{ $renk }};box-shadow:{{ $aktif ? '0 0 0 4px rgb(139 92 246 / .25)' : 'none' }}">
                        {{ $tamam ? '✓' : $no }}
                    </span>
                    <span style="font-size:.72rem;text-align:center;color:{{ $aktif ? 'inherit' : 'rgb(107 114 128)' }};font-weight:{{ $aktif ? 600 : 400 }}">{{ $ad }}</span>
                </button>
                @if (! $loop->last)
                    <span style="height:2px;flex:1;background:{{ $tamam ? $yesil : 'rgb(107 114 128 / .35)' }};margin-top:-1.1rem"></span>
                @endif
            </div>
        @endforeach
    </div>

    {{-- ============================ ADIM 1 =============================== --}}
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
                </div>
            </div>
        </x-filament::section>
    @endif

    {{-- ============================ ADIM 2 =============================== --}}
    @if ($adim === 2)
        <x-filament::section icon="heroicon-o-queue-list" icon-color="primary">
            <x-slot name="heading">Risk Ekleme Yöntemi</x-slot>
            <x-slot name="description">Riskleri nasıl eklemek istediğinizi seçin</x-slot>

            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(210px,1fr));gap:1rem">
                @foreach ($yontemler as $anahtar => $y)
                    @php $secili = $yontemSecim === $anahtar; @endphp
                    <button type="button" wire:click="yontemSec('{{ $anahtar }}')"
                        @style([
                            'text-align:left;padding:1rem;border-radius:.75rem;cursor:pointer;position:relative;display:flex;flex-direction:column;gap:.5rem',
                            'border:2px solid '.($secili ? $mor : 'rgb(107 114 128 / .3)'),
                            'background:'.($secili ? 'rgb(139 92 246 / .08)' : 'transparent'),
                            'opacity:.55' => ! $y['hazir'],
                        ])>
                        @if ($y['onerilen'])
                            <span style="position:absolute;top:-.6rem;left:1rem;font-size:.62rem;font-weight:700;background:{{ $yesil }};color:#062;padding:.15rem .5rem;border-radius:9999px">ÖNERİLEN</span>
                        @endif
                        @unless ($y['hazir'])
                            <span style="position:absolute;top:.5rem;right:.5rem;font-size:.62rem;color:rgb(107 114 128)">yakında</span>
                        @endunless
                        <div style="font-weight:700">{{ $y['ad'] }}</div>
                        <div style="font-size:.8rem;color:rgb(107 114 128)">{{ $y['aciklama'] }}</div>
                        <ul style="margin:0;padding-left:1rem;font-size:.75rem;color:rgb(107 114 128)">
                            @foreach ($y['maddeler'] as $m) <li>{{ $m }}</li> @endforeach
                        </ul>
                    </button>
                @endforeach
            </div>

            @if ($yontemSecim)
                <div style="margin-top:1rem;border:1px solid {{ $mor }};border-radius:.5rem;padding:.75rem;font-size:.85rem;background:rgb(139 92 246 / .06)">
                    <strong>{{ $yontemler[$yontemSecim]['ad'] }}</strong> seçildi. Devam etmek için "{{ $ileriEtiket[2] }}" butonuna tıklayın.
                </div>
            @endif
        </x-filament::section>
    @endif

    {{-- ==================== ADIM 3 — MANUEL ============================= --}}
    @if ($adim === 3 && $yontemSecim === 'manuel')
        <x-filament::section icon="heroicon-o-book-open" icon-color="primary">
            <x-slot name="heading">Manuel Risk Seçimi</x-slot>
            <x-slot name="description">Risk Kütüphanesinden seçim yapın — seçilenler: {{ count($secilenler) }}</x-slot>

            <div style="display:flex;flex-direction:column;gap:.5rem">
                @foreach ($this->kategoriler as $kat)
                    @php $acik = in_array($kat->id, $acikKategoriler, true); @endphp
                    <div style="{{ $kutu }};overflow:hidden">
                        <div style="display:flex;align-items:center;justify-content:space-between;padding:.75rem 1rem;background:rgb(107 114 128 / .07)">
                            <button type="button" wire:click="kategoriToggle({{ $kat->id }})"
                                style="display:flex;align-items:center;gap:.5rem;background:none;border:none;cursor:pointer;font-weight:600">
                                <span>{{ $acik ? '▾' : '▸' }}</span> {{ $kat->ad }}
                                <x-filament::badge color="gray">{{ $kat->tehlikeler->count() }}</x-filament::badge>
                            </button>
                            <x-filament::button size="xs" color="gray" wire:click="kategoriTumunuEkle({{ $kat->id }})">Tümünü ekle</x-filament::button>
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
                                        <x-filament::button size="xs" :color="$ekli ? 'success' : 'primary'" :disabled="$ekli"
                                            wire:click="tehlikeEkle({{ $t->id }})" style="flex-shrink:0">
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
                <x-filament::button color="gray" icon="heroicon-o-plus" wire:click="bosMaddeEkle">Elle boş madde ekle</x-filament::button>
            </div>
        </x-filament::section>
    @endif

    {{-- ==================== ADIM 3 — YAPAY ZEKA ======================== --}}
    @if ($adim === 3 && $yontemSecim === 'ai')
        <x-filament::section>
            <x-slot name="heading">
                <span style="display:flex;align-items:center;gap:.5rem">
                    <x-filament::icon icon="heroicon-o-sparkles" style="width:1.25rem;height:1.25rem;color:{{ $mor }}"/>
                    Yapay Zeka ile Risk Ekleme
                </span>
            </x-slot>
            <x-slot name="description">İşyerinize özgü sorular → mevzuat referanslı risk maddeleri (kural tabanlı)</x-slot>

            {{-- Sohbet başlık şeridi --}}
            <div style="background:{{ $grad }};color:#fff;border-radius:.6rem;padding:.75rem 1rem;display:flex;align-items:center;justify-content:space-between;gap:1rem;flex-wrap:wrap">
                <div>
                    <div style="font-weight:700">AI İSG Uzmanı — Kural Tabanlı Motor</div>
                    <div style="font-size:.8rem;opacity:.9">Önce birkaç soru sorulur, sonra işyerinize özel risk önerileri üretilir.</div>
                </div>
                @if ($aiAsama === 'sohbet')
                    <div style="display:flex;gap:.4rem;flex-wrap:wrap">
                        <span style="background:rgb(255 255 255 / .2);padding:.2rem .55rem;border-radius:9999px;font-size:.72rem">Soru {{ $this->aiIlerleme() }}</span>
                        <span style="background:rgb(255 255 255 / .2);padding:.2rem .55rem;border-radius:9999px;font-size:.72rem">✨ {{ $this->aiTetiklenenSayisi() }} aday risk</span>
                    </div>
                @endif
            </div>

            {{-- Başlangıç --}}
            @if ($aiAsama === 'baslangic')
                <div style="text-align:center;padding:1.75rem 1rem;border:1px dashed rgb(139 92 246 / .5);border-radius:.75rem;margin-top:1rem">
                    <div style="font-size:1.5rem">✨</div>
                    <div style="font-weight:700;margin-top:.35rem">AI ile Risk Değerlendirmesi Başlat</div>
                    <p style="font-size:.85rem;color:rgb(107 114 128);max-width:34rem;margin:.5rem auto">
                        Önce işyeriniz hakkında sektöre özel sorular sorulacak, ardından hem kütüphaneden hem de
                        cevaplarınıza özel risk maddeleri önerilecek.
                    </p>
                    <x-filament::button icon="heroicon-o-chat-bubble-left-right" wire:click="aiBaslat">Sohbeti Başlat</x-filament::button>
                </div>
            @endif

            {{-- 1. Aşama — Ana Sektör --}}
            @if ($aiAsama === 'sektor')
                <div style="margin-top:1rem">
                    <div style="background:{{ $grad }};color:#fff;border-radius:.5rem;padding:.5rem .85rem;font-weight:600">1. Aşama — Ana Sektör Seç</div>
                    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:.75rem;margin-top:.75rem">
                        @foreach ($sektorler as $anahtar => $s)
                            @php $sec = $aiSektor === $anahtar; @endphp
                            <button type="button" wire:click="aiSektorSec('{{ $anahtar }}')"
                                style="text-align:left;padding:.85rem;border-radius:.6rem;cursor:pointer;
                                    border:2px solid {{ $sec ? $mor : 'rgb(107 114 128 / .3)' }};
                                    background:{{ $sec ? 'rgb(139 92 246 / .08)' : 'transparent' }}">
                                <div style="display:flex;align-items:center;gap:.5rem;font-weight:600">
                                    <x-filament::icon :icon="$s['ikon']" style="width:1.1rem;height:1.1rem"/>
                                    {{ $s['ad'] }}
                                </div>
                                <div style="font-size:.78rem;color:rgb(107 114 128);margin-top:.25rem">{{ $s['aciklama'] }}</div>
                            </button>
                        @endforeach
                    </div>
                    <div style="display:flex;justify-content:flex-end;margin-top:1rem">
                        <x-filament::button icon="heroicon-o-chevron-right" icon-position="after"
                            wire:click="aiSektorOnayla" :disabled="! $aiSektor">İleri</x-filament::button>
                    </div>
                </div>
            @endif

            {{-- 2. Aşama — Alt Kategori --}}
            @if ($aiAsama === 'altkategori')
                <div style="margin-top:1rem">
                    <div style="background:{{ $grad }};color:#fff;border-radius:.5rem;padding:.5rem .85rem;font-weight:600">2. Aşama — Alt Kategori Seç</div>
                    <div style="font-size:.82rem;color:rgb(107 114 128);margin:.5rem 0">
                        {{ $this->aiSektorEtiketi() }} — {{ count($aiAltKategoriler) }} alt kategori seçildi (boş bırakılabilir)
                    </div>
                    <div style="display:flex;flex-wrap:wrap;gap:.5rem">
                        @foreach (($sektorler[$aiSektor]['alt_kategoriler'] ?? []) as $ai => $ak)
                            @php $sec = in_array($ak, $aiAltKategoriler, true); @endphp
                            <button type="button" wire:click="aiAltKategoriToggle({{ $ai }})"
                                style="padding:.35rem .7rem;border-radius:9999px;cursor:pointer;font-size:.8rem;
                                    border:1px solid {{ $sec ? $mor : 'rgb(107 114 128 / .4)' }};
                                    background:{{ $sec ? 'rgb(139 92 246 / .12)' : 'transparent' }}">
                                {{ $sec ? '✓ ' : '' }}{{ $ak }}
                            </button>
                        @endforeach
                    </div>
                    <div style="display:flex;justify-content:space-between;margin-top:1rem">
                        <x-filament::button color="gray" wire:click="$set('aiAsama','sektor')">← Geri</x-filament::button>
                        <x-filament::button icon="heroicon-o-check" wire:click="aiAltKategoriOnayla">Onayla ve Sohbete Geç</x-filament::button>
                    </div>
                </div>
            @endif

            {{-- Sohbet — Sorular --}}
            @if ($aiAsama === 'sohbet')
                @php $siradaki = $this->aiSiradakiSoru(); @endphp
                <div style="margin-top:1rem;display:flex;flex-direction:column;gap:.6rem;max-height:22rem;overflow-y:auto;padding-right:.25rem">
                    @foreach ($sorular as $s)
                        @php
                            $cevap = $aiCevaplar[$s['anahtar']] ?? null;
                            $atlandi = in_array($s['anahtar'], $aiAtlananlar, true);
                        @endphp
                        @if ($cevap !== null || $atlandi)
                            <div style="align-self:flex-start;max-width:80%;background:rgb(107 114 128 / .12);padding:.5rem .75rem;border-radius:.75rem;font-size:.85rem">{{ $s['soru'] }}</div>
                            <div style="align-self:flex-end;max-width:80%;background:{{ $mor }};color:#fff;padding:.5rem .75rem;border-radius:.75rem;font-size:.85rem">
                                @if ($atlandi) [ATLANDI]
                                @else
                                    @php
                                        $degerler = (array) $cevap;
                                        $etiketler = collect($s['secenekler'])->whereIn('deger', $degerler)->pluck('etiket')->implode(', ');
                                    @endphp
                                    {{ $etiketler }}
                                @endif
                            </div>
                        @endif
                    @endforeach
                </div>

                @if ($siradaki)
                    <div style="{{ $kutu }};padding:.85rem;margin-top:.75rem">
                        <div style="font-weight:600">{{ $siradaki['soru'] }}</div>
                        <div style="font-size:.78rem;color:rgb(107 114 128);font-style:italic;margin:.4rem 0 .6rem">{{ $siradaki['ipucu'] }}</div>

                        @php $coklu = $siradaki['tip'] === 'coklu'; @endphp
                        <div style="display:flex;flex-direction:column;gap:.4rem">
                            @foreach ($siradaki['secenekler'] as $sec)
                                @php $isaretli = $coklu && in_array($sec['deger'], $aiGecici, true); @endphp
                                <button type="button"
                                    wire:click="aiCevapla('{{ $siradaki['anahtar'] }}', '{{ $sec['deger'] }}', {{ $coklu ? 'true' : 'false' }})"
                                    style="text-align:left;padding:.55rem .75rem;border-radius:.5rem;cursor:pointer;font-size:.85rem;
                                        border:1px solid {{ $isaretli ? $mor : 'rgb(107 114 128 / .35)' }};
                                        background:{{ $isaretli ? 'rgb(139 92 246 / .1)' : 'transparent' }}">
                                    {{ $coklu ? ($isaretli ? '☑ ' : '☐ ') : '○ ' }}{{ $sec['etiket'] }}
                                </button>
                            @endforeach
                        </div>

                        <div style="display:flex;align-items:center;justify-content:space-between;gap:.5rem;margin-top:.75rem">
                            <x-filament::button size="sm" color="gray" wire:click="aiOncekiSoru"
                                :disabled="empty($aiCevaplar) && empty($aiAtlananlar)">← Önceki Soru</x-filament::button>
                            <div style="display:flex;gap:.5rem">
                                @if ($coklu)
                                    <x-filament::button size="sm" icon="heroicon-o-paper-airplane"
                                        wire:click="aiCokluGonder('{{ $siradaki['anahtar'] }}')" :disabled="empty($aiGecici)">Cevabı Gönder</x-filament::button>
                                @endif
                                <x-filament::button size="sm" color="gray" wire:click="aiSoruAtla('{{ $siradaki['anahtar'] }}')">Atla »</x-filament::button>
                            </div>
                        </div>
                    </div>
                @else
                    <div style="margin-top:1rem;text-align:center">
                        <x-filament::button icon="heroicon-o-sparkles" wire:click="aiAdaylariUret">Risk adaylarını üret</x-filament::button>
                    </div>
                @endif
            @endif

            {{-- Sonuç — Aday riskler --}}
            @if ($aiAsama === 'sonuc')
                <div style="margin-top:1rem;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:.5rem">
                    <div style="font-weight:600">{{ count($aiAdaylar) }} aday risk — seçili: {{ count($aiSecilenAdaylar) }}</div>
                    <div style="display:flex;gap:.5rem">
                        <x-filament::button size="xs" color="gray" wire:click="aiTumAdaylar(true)">Tümünü Seç</x-filament::button>
                        <x-filament::button size="xs" color="gray" wire:click="aiTumAdaylar(false)">Tümünü Kaldır</x-filament::button>
                    </div>
                </div>
                <div style="margin-top:.6rem;display:flex;flex-direction:column;gap:.4rem;max-height:24rem;overflow-y:auto;padding-right:.25rem">
                    @foreach ($aiAdaylar as $aday)
                        @php $sec = in_array($aday['anahtar'], $aiSecilenAdaylar, true); @endphp
                        <button type="button" wire:click="aiAdayToggle('{{ $aday['anahtar'] }}')"
                            style="text-align:left;padding:.6rem .75rem;border-radius:.5rem;cursor:pointer;
                                border:1px solid {{ $sec ? $mor : 'rgb(107 114 128 / .3)' }};
                                background:{{ $sec ? 'rgb(139 92 246 / .08)' : 'transparent' }}">
                            <div style="font-weight:600;font-size:.85rem">{{ $sec ? '☑ ' : '☐ ' }}{{ $aday['tehlike'] }}</div>
                            <div style="font-size:.78rem;color:rgb(107 114 128)">
                                {{ $aday['risk'] }}
                                @if ($aday['mevzuat']) · <em>{{ $aday['mevzuat'] }}</em> @endif
                                @if ($aday['kaynak'] === 'ai') · <span style="color:{{ $mor }}">öneri O/Ş {{ $aday['olasilik'] }}/{{ $aday['siddet'] }}</span> @endif
                            </div>
                        </button>
                    @endforeach
                </div>
                <div style="display:flex;justify-content:space-between;margin-top:1rem">
                    <x-filament::button color="gray" wire:click="$set('aiAsama','sohbet')">← Sorulara dön</x-filament::button>
                    <x-filament::button icon="heroicon-o-plus" wire:click="aiAdaylariEkle" :disabled="empty($aiSecilenAdaylar)">
                        Seçili {{ count($aiSecilenAdaylar) }} riski ekle
                    </x-filament::button>
                </div>
            @endif
        </x-filament::section>
    @endif

    {{-- ============================ ADIM 4 =============================== --}}
    @if ($adim === 4)
        <x-filament::section icon="heroicon-o-adjustments-horizontal" icon-color="primary">
            <x-slot name="heading">Tercihler</x-slot>
            <x-slot name="description">Tüm maddelere uygulanacak varsayılanlar</x-slot>

            <div style="display:flex;flex-direction:column;gap:1rem;max-width:32rem">
                <div>
                    <label style="font-weight:600;font-size:.85rem">Puanlama yöntemi</label>
                    <select wire:model.live="yontem"
                        style="margin-top:.35rem;width:100%;padding:.55rem .75rem;border-radius:.5rem;border:1px solid rgb(107 114 128 / .35);background:transparent">
                        @foreach (config('isg.risk_yontemleri') as $k => $v)
                            <option value="{{ $k }}">{{ $v }}</option>
                        @endforeach
                    </select>
                </div>
                <label style="display:flex;align-items:center;gap:.6rem;font-size:.9rem">
                    <input type="checkbox" wire:model="etkilenenDiger">
                    Taşeron / ziyaretçiler de etkileniyor (tüm maddeler)
                </label>
                <div>
                    <label style="font-weight:600;font-size:.85rem">Varsayılan termin</label>
                    <input type="text" wire:model="varsayilanTermin" placeholder='gg.aa.yyyy veya "Sürekli"'
                        style="margin-top:.35rem;width:100%;padding:.55rem .75rem;border-radius:.5rem;border:1px solid rgb(107 114 128 / .35);background:transparent">
                </div>
            </div>
        </x-filament::section>
    @endif

    {{-- ============================ ADIM 5 =============================== --}}
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
                    <div wire:key="madde-{{ $m['anahtar'] }}" style="{{ $kutu }};padding:.85rem">
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
                                @if (!empty($m['mevzuat']))
                                    <div style="font-size:.72rem;color:rgb(107 114 128);margin-top:.3rem">📖 {{ $m['mevzuat'] }}</div>
                                @endif
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

    {{-- ============================ ADIM 6 =============================== --}}
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
                    <div style="{{ $kutu }};padding:.4rem .75rem;font-size:.85rem"><strong>{{ $adet }}</strong> · {{ $duzey }}</div>
                @endforeach
            </div>

            @unless ($this->tumMaddelerPuanli())
                <p style="margin-top:1rem;color:#ef4444;font-size:.85rem">Bazı maddelerin puanı eksik — Adım 5'e dönün.</p>
            @endunless

            <div style="margin-top:1.25rem;display:flex;gap:.75rem">
                <x-filament::button color="primary" icon="heroicon-o-check" wire:click="kaydet" :disabled="! $this->tumMaddelerPuanli()">Kaydet</x-filament::button>
                <x-filament::button color="gray" disabled icon="heroicon-o-document-arrow-down">PDF indir (yakında)</x-filament::button>
            </div>
        </x-filament::section>
    @endif

    {{-- NAVİGASYON ------------------------------------------------------- --}}
    <div style="display:flex;align-items:center;justify-content:space-between;margin-top:.5rem">
        <div>
            @if ($adim > 1)
                <x-filament::button color="gray" icon="heroicon-o-chevron-left" wire:click="geri">Geri</x-filament::button>
            @endif
        </div>
        <div style="font-size:.8rem;color:rgb(107 114 128)">{{ $adim }} / 6</div>
        <div>
            @if ($adim < 6 && ! ($adim === 3 && $yontemSecim === 'ai'))
                <x-filament::button icon="heroicon-o-chevron-right" icon-position="after"
                    wire:click="ileri" :disabled="! $this->adimGecerli($adim)">
                    {{ $ileriEtiket[$adim] ?? 'İleri' }}
                </x-filament::button>
            @endif
        </div>
    </div>
</x-filament-panels::page>
