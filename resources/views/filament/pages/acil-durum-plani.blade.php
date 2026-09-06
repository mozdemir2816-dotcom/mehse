@php
    $konular = config('isg.acil_durum.konular');
    $cerceveler = config('isg.acil_durum.kapak_cerceveleri');
    $ekipler = config('isg.acil_durum.ekipler');
    $afisler = config('isg.acil_durum.afisler');
    $mor = 'rgb(139 92 246)';
    $kirmizi = 'rgb(239 68 68)';
    $kutu = 'border:1px solid rgb(107 114 128 / .3);border-radius:.75rem;padding:1rem';
@endphp

<x-filament-panels::page>
    <p style="font-size:.85rem;color:rgb(107 114 128);margin-top:-.5rem">
        Firma bilgilerini seçin, dahil edilecek acil durum konularını işaretleyin ve
        Acil Durum Eylem Planı PDF’ini oluşturun. 6331 SK kapsamında her işyerinde zorunludur.
    </p>

    @include('filament.pages.partials.eksik-firmalar', ['kriterAnahtari' => 'acil_durum_plani'])

    {{-- 1. FİRMA & RAPOR BİLGİLERİ --}}
    <x-filament::section icon="heroicon-o-building-office-2" icon-color="danger">
        <x-slot name="heading">1. Firma & Rapor Bilgileri</x-slot>
        <x-slot name="description">Kayıtlı firmalarınızdan birini seçin ve rapor tarihini girin</x-slot>

        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:1rem">
            <div>
                <label style="font-weight:600;font-size:.82rem">Firma Seçin <span style="color:#ef4444">*</span></label>
                <select wire:model.live="firmaId"
                    style="margin-top:.3rem;width:100%;padding:.55rem .75rem;border-radius:.5rem;border:1px solid rgb(107 114 128 / .35);background:transparent">
                    <option value="">— Firma seçin —</option>
                    @foreach ($this->firmalar as $id => $ad)
                        <option value="{{ $id }}">{{ $ad }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label style="font-weight:600;font-size:.82rem">Rapor Tarihi <span style="color:#ef4444">*</span></label>
                <input type="date" wire:model="raporTarihi"
                    style="margin-top:.3rem;width:100%;padding:.5rem .75rem;border-radius:.5rem;border:1px solid rgb(107 114 128 / .35);background:transparent">
            </div>
            <div>
                <label style="font-weight:600;font-size:.82rem">Doküman No</label>
                <input type="text" wire:model="dokumanNo" placeholder="Örn: AD-01"
                    style="margin-top:.3rem;width:100%;padding:.5rem .75rem;border-radius:.5rem;border:1px solid rgb(107 114 128 / .35);background:transparent">
            </div>
            <div>
                <label style="font-weight:600;font-size:.82rem">Rev. Tarihi / No</label>
                <input type="text" wire:model="revizyonNo" placeholder="Örn: Rev.01 — 15.03.2027"
                    style="margin-top:.3rem;width:100%;padding:.5rem .75rem;border-radius:.5rem;border:1px solid rgb(107 114 128 / .35);background:transparent">
            </div>
        </div>

        @if ($this->firma)
            <div style="{{ $kutu }};margin-top:1rem;background:rgb(239 68 68 / .05);border-color:rgb(239 68 68 / .3)">
                <div style="font-weight:700">{{ $this->firma->unvan }}</div>
                <div style="display:flex;gap:.5rem;flex-wrap:wrap;margin:.4rem 0">
                    <x-filament::badge color="danger">{{ $this->firma->tehlikeSinifiEtiketi() }}</x-filament::badge>
                    <x-filament::badge color="gray">SGK: {{ $this->firma->sgk_sicil_no ?: '—' }}</x-filament::badge>
                    <x-filament::badge color="gray">{{ $this->firma->calisan_sayisi ?: '—' }} çalışan</x-filament::badge>
                </div>
                <div style="font-size:.8rem;color:rgb(107 114 128)">{{ $this->firma->adres ?: 'Adres girilmemiş' }}</div>
            </div>

            {{-- Toplanma yeri + dışarıdan etkileyebilecek işyerleri (yönetmelik gereği zorunlu) --}}
            <div style="margin-top:1rem;display:grid;grid-template-columns:repeat(auto-fit,minmax(240px,1fr));gap:1rem">
                <div>
                    <label style="font-weight:600;font-size:.82rem">Toplanma Yeri</label>
                    <div style="font-size:.72rem;color:rgb(107 114 128);margin-bottom:.2rem">İşyeri dışında, güvenli, tarif edilebilir bir nokta</div>
                    <input type="text" wire:model="toplanmaYeri" placeholder="Örn: İnşaat alanı girişi, ana yol kenarı açık saha"
                        style="width:100%;padding:.5rem .75rem;border-radius:.5rem;border:1px solid rgb(107 114 128 / .35);background:transparent">
                </div>
                <div>
                    <label style="font-weight:600;font-size:.82rem">İşyerini Dışarıdan Etkileyebilecek İşyerleri</label>
                    <div style="font-size:.72rem;color:rgb(107 114 128);margin-bottom:.2rem">Her satıra bir işyeri: unvan, faaliyet konusu, olası etki</div>
                    <textarea wire:model="disaridanEtkileyebilecekIsyerleri" rows="2" placeholder="Örn: Komşu Akaryakıt A.Ş. — Akaryakıt istasyonu — Patlama/yangın sıçraması riski"
                        style="width:100%;padding:.5rem .75rem;border-radius:.5rem;border:1px solid rgb(107 114 128 / .35);background:transparent;font-family:inherit;font-size:.85rem"></textarea>
                </div>
            </div>

            {{-- Destek ekipleri --}}
            <div style="margin-top:1rem">
                <div style="font-weight:600;font-size:.85rem">Destek Elemanı Atamaları</div>
                <div style="font-size:.75rem;color:rgb(107 114 128);margin-bottom:.5rem">İsimleri virgülle ayırın. Çalışan modülü tamamlanınca seçim listesinden gelecek.</div>
                <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:.6rem">
                    @foreach ($ekipler as $anahtar => $ad)
                        <div>
                            <label style="font-size:.78rem;color:rgb(107 114 128)">{{ $ad }}</label>
                            <input type="text" wire:model="ekipMetni.{{ $anahtar }}" placeholder="Ad Soyad, Ad Soyad"
                                style="margin-top:.2rem;width:100%;padding:.45rem .6rem;border-radius:.4rem;border:1px solid rgb(107 114 128 / .3);background:transparent;font-size:.82rem">
                        </div>
                    @endforeach
                </div>
            </div>

            <div style="margin-top:1rem">
                <x-filament::button icon="heroicon-o-check" wire:click="kaydet">Kaydet</x-filament::button>
            </div>
        @else
            <p style="margin-top:1rem;font-size:.85rem;color:#f59e0b">Devam etmek için bir firma seçin.</p>
        @endif
    </x-filament::section>

    @if ($this->firma)
        {{-- 2. ACİL DURUM KONULARI --}}
        <x-filament::section icon="heroicon-o-exclamation-triangle" icon-color="danger">
            <x-slot name="heading">
                Acil Durum Konuları
                <span style="font-weight:400;font-size:.8rem;color:rgb(107 114 128)">({{ count($konular) }} konudan {{ count($this->konular) }} seçili)</span>
            </x-slot>
            <x-slot name="description">PDF çıktısına dahil edilecek acil durum sayfalarını seçin</x-slot>

            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(240px,1fr));gap:.35rem">
                @foreach ($konular as $i => $k)
                    @php $secili = in_array($k['anahtar'], $this->konular, true); @endphp
                    <button type="button" wire:click="konuToggle('{{ $k['anahtar'] }}')"
                        style="text-align:left;padding:.5rem .7rem;border-radius:.45rem;cursor:pointer;font-size:.82rem;
                            border:1px solid {{ $secili ? $kirmizi : 'rgb(107 114 128 / .3)' }};
                            background:{{ $secili ? 'rgb(239 68 68 / .1)' : 'transparent' }}">
                        {{ $secili ? '☑' : '☐' }}
                        <span style="display:inline-block;width:1.4rem;text-align:center;color:rgb(107 114 128)">{{ $i + 1 }}</span>
                        ACİL DURUM: {{ mb_strtoupper($k['ad'], 'UTF-8') }}
                    </button>
                @endforeach
            </div>
            <div style="margin-top:.6rem;display:flex;gap:.5rem">
                <x-filament::button size="xs" color="gray" wire:click="tumKonular(true)">Tümünü Seç</x-filament::button>
                <x-filament::button size="xs" color="gray" wire:click="tumKonular(false)">Tümünü Kaldır</x-filament::button>
            </div>
        </x-filament::section>

        {{-- 3. KAPAK ÇERÇEVESİ --}}
        <x-filament::section icon="heroicon-o-swatch" icon-color="danger">
            <x-slot name="heading">Kapak Çerçevesi</x-slot>
            <x-slot name="description">Kapak sayfası için çerçeve stili seçin</x-slot>
            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:.5rem">
                @foreach ($cerceveler as $anahtar => $ad)
                    @php $secili = $kapakCercevesi === $anahtar; @endphp
                    <button type="button" wire:click="$set('kapakCercevesi','{{ $anahtar }}')"
                        style="padding:.6rem;border-radius:.5rem;cursor:pointer;font-size:.8rem;
                            border:2px solid {{ $secili ? $mor : 'rgb(107 114 128 / .3)' }};
                            background:{{ $secili ? 'rgb(139 92 246 / .08)' : 'transparent' }}">
                        <div style="font-weight:600">{{ Str::before($ad, ' — ') }}</div>
                        <div style="font-size:.7rem;color:rgb(107 114 128)">{{ Str::after($ad, ' — ') }}</div>
                    </button>
                @endforeach
            </div>
        </x-filament::section>

        {{-- 4. ACİL DURUM AFİŞLERİ --}}
        <x-filament::section icon="heroicon-o-printer" icon-color="danger">
            <x-slot name="heading">Acil Durum Afişleri</x-slot>
            <x-slot name="description">Firmalara asılmak üzere A3 / A4 talimat afişleri</x-slot>

            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:.5rem">
                @foreach ($afisler as $anahtar => $afis)
                    @php $secili = $afisTipi === $anahtar; @endphp
                    <button type="button" wire:click="$set('afisTipi','{{ $anahtar }}')"
                        style="padding:.6rem;border-radius:.5rem;cursor:pointer;font-size:.8rem;text-align:center;
                            border:2px solid {{ $secili ? $kirmizi : 'rgb(107 114 128 / .3)' }};
                            background:{{ $secili ? 'rgb(239 68 68 / .1)' : 'transparent' }}">
                        ⚠ {{ $afis['ad'] }}
                    </button>
                @endforeach
            </div>

            <div style="display:flex;align-items:center;gap:1rem;margin-top:.85rem;flex-wrap:wrap">
                <div style="display:flex;gap:.3rem">
                    @foreach (['a4' => 'A4 Boyutu', 'a3' => 'A3 Boyutu'] as $e => $et)
                        <button type="button" wire:click="$set('afisEbat','{{ $e }}')"
                            style="padding:.35rem .7rem;border-radius:.4rem;cursor:pointer;font-size:.8rem;
                                border:1px solid {{ $afisEbat === $e ? $kirmizi : 'rgb(107 114 128 / .3)' }};
                                background:{{ $afisEbat === $e ? 'rgb(239 68 68 / .1)' : 'transparent' }}">{{ $et }}</button>
                    @endforeach
                </div>
                <x-filament::button color="danger" icon="heroicon-o-arrow-down-tray" wire:click="afisIndir">
                    Afişi İndir ({{ strtoupper($afisEbat) }})
                </x-filament::button>
            </div>

            {{-- seçili afiş önizleme (adım listesi) --}}
            <div style="{{ $kutu }};margin-top:.85rem">
                <div style="font-weight:700;color:{{ $kirmizi }};font-size:.9rem">{{ $afisler[$afisTipi]['baslik'] }}</div>
                <ol style="margin:.5rem 0 0;padding-left:1.2rem;font-size:.82rem;display:flex;flex-direction:column;gap:.25rem">
                    @foreach ($afisler[$afisTipi]['adimlar'] as $adim) <li>{{ $adim }}</li> @endforeach
                </ol>
            </div>
        </x-filament::section>
    @endif

    <div style="{{ $kutu }};background:rgb(245 158 11 / .06);border-color:rgb(245 158 11 / .3);font-size:.82rem">
        <strong>⚠ Acil Durum Eylem Planı Hakkında</strong><br>
        {{ config('isg.acil_durum.hakkinda') }}
    </div>
</x-filament-panels::page>
