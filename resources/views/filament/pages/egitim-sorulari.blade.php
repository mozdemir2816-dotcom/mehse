@php
    $mor = 'rgb(168 85 247)';
    $kutu = 'border:1px solid rgb(107 114 128 / .3);border-radius:.75rem;padding:1rem';
@endphp

<x-filament-panels::page>
    <p style="font-size:.85rem;color:rgb(107 114 128);margin-top:-.5rem">
        Sektör ve zorluk seçip AI ile 10 soruluk sınav üretin (veya elle ekleyin),
        katılımcı listesi oluşturup PDF sınav kağıdını indirin.
    </p>

    {{-- 1. FİRMA & SEKTÖR/ZORLUK --}}
    <x-filament::section icon="heroicon-o-question-mark-circle" icon-color="warning">
        <x-slot name="heading">1. Firma & Sınav Ayarları</x-slot>

        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:1rem">
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
                <label style="font-weight:600;font-size:.82rem">Sektör</label>
                <select wire:model="sektorAnahtari"
                    style="margin-top:.3rem;width:100%;padding:.55rem .75rem;border-radius:.5rem;border:1px solid rgb(107 114 128 / .35);background:transparent">
                    @foreach ($this->sektorler as $anahtar => $ad)
                        <option value="{{ $anahtar }}">{{ $ad }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label style="font-weight:600;font-size:.82rem">Zorluk</label>
                <select wire:model="zorluk"
                    style="margin-top:.3rem;width:100%;padding:.55rem .75rem;border-radius:.5rem;border:1px solid rgb(107 114 128 / .35);background:transparent">
                    @foreach ($this->zorluklar as $anahtar => $ad)
                        <option value="{{ $anahtar }}">{{ $ad }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label style="font-weight:600;font-size:.82rem">Sınav Zamanı</label>
                <select wire:model="sinavZamani"
                    style="margin-top:.3rem;width:100%;padding:.55rem .75rem;border-radius:.5rem;border:1px solid rgb(107 114 128 / .35);background:transparent">
                    @foreach ($this->zamanlar as $anahtar => $ad)
                        <option value="{{ $anahtar }}">{{ $ad }}</option>
                    @endforeach
                </select>
            </div>
            <div style="display:flex;align-items:end">
                <label style="display:flex;align-items:center;gap:.4rem;font-size:.82rem;cursor:pointer">
                    <input type="checkbox" wire:model="cevapAnahtariDahil"> Cevap anahtarı PDF'e dahil edilsin
                </label>
            </div>
        </div>

        <div style="margin-top:1rem;display:flex;gap:.5rem;flex-wrap:wrap">
            @if ($this->aiAktif)
                <x-filament::button color="warning" icon="heroicon-o-sparkles" wire:click="aiIleUret">AI ile 10 Soru Üret</x-filament::button>
            @else
                <span style="font-size:.78rem;color:rgb(107 114 128)">AI devre dışı (GEMINI_API_KEY tanımlı değil) — aşağıdan elle soru ekleyin.</span>
            @endif
            @if ($sorular)
                <x-filament::button size="sm" color="gray" wire:click="sifirla">Soruları Sıfırla</x-filament::button>
            @endif
        </div>
    </x-filament::section>

    @if ($this->firma)
        {{-- 2. SORULAR --}}
        <x-filament::section icon="heroicon-o-list-bullet" icon-color="warning">
            <x-slot name="heading">2. Sorular ({{ count($sorular) }})</x-slot>

            @foreach ($sorular as $i => $s)
                <div style="{{ $kutu }};margin-bottom:.6rem">
                    <div style="display:flex;justify-content:space-between;gap:.5rem">
                        <div style="font-weight:600;font-size:.85rem">{{ $i + 1 }}. {{ $s['soru'] }}</div>
                        <button type="button" wire:click="soruSil({{ $i }})" style="color:#ef4444;cursor:pointer;background:none;border:none">✕</button>
                    </div>
                    <div style="margin-top:.4rem;display:flex;flex-direction:column;gap:.15rem">
                        @foreach ($s['secenekler'] as $j => $secenek)
                            <div style="font-size:.8rem;{{ $j === $s['dogru_index'] ? 'color:#10b981;font-weight:600' : '' }}">
                                {{ chr(65 + $j) }}) {{ $secenek }} {{ $j === $s['dogru_index'] ? '✓' : '' }}
                            </div>
                        @endforeach
                    </div>
                </div>
            @endforeach

            <details>
                <summary style="cursor:pointer;font-size:.82rem;font-weight:600;color:rgb(107 114 128)">+ Elle Soru Ekle</summary>
                <div style="margin-top:.5rem">
                    <input type="text" wire:model="yeniSoruMetni" placeholder="Soru metni"
                        style="width:100%;padding:.5rem .7rem;border-radius:.4rem;border:1px solid rgb(107 114 128 / .3);background:transparent;font-size:.82rem;margin-bottom:.5rem">
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:.5rem">
                        @foreach ([0,1,2,3] as $j)
                            <div style="display:flex;align-items:center;gap:.3rem">
                                <input type="radio" wire:model="yeniDogruIndex" value="{{ $j }}">
                                <input type="text" wire:model="yeniSecenekler.{{ $j }}" placeholder="{{ chr(65 + $j) }} şıkkı"
                                    style="flex:1;padding:.4rem .6rem;border-radius:.4rem;border:1px solid rgb(107 114 128 / .3);background:transparent;font-size:.8rem">
                            </div>
                        @endforeach
                    </div>
                    <x-filament::button size="sm" wire:click="soruEkle" style="margin-top:.5rem">Soruyu Ekle</x-filament::button>
                </div>
            </details>
        </x-filament::section>

        {{-- 3. KATILIMCILAR --}}
        <x-filament::section icon="heroicon-o-users" icon-color="warning">
            <x-slot name="heading">3. Sınav / Katılımcı Bilgileri ({{ count($katilimcilar) }})</x-slot>

            @if ($this->calisanlar->isNotEmpty())
                <div style="font-weight:600;font-size:.8rem;margin-bottom:.3rem">Firmadan Çalışan Seç</div>
                <div style="display:flex;gap:.4rem;flex-wrap:wrap;margin-bottom:.75rem">
                    @foreach ($this->calisanlar as $c)
                        <x-filament::button size="xs" color="gray" wire:click="katilimciHizliEkle({{ $c->id }})">+ {{ $c->ad_soyad }}</x-filament::button>
                    @endforeach
                </div>
            @endif

            <div style="display:grid;grid-template-columns:1fr 1fr 1fr auto;gap:.5rem;margin-bottom:.75rem">
                <input type="text" wire:model="yeniKatilimciAd" placeholder="Ad Soyad"
                    style="padding:.45rem .6rem;border-radius:.4rem;border:1px solid rgb(107 114 128 / .3);background:transparent;font-size:.82rem">
                <input type="text" wire:model="yeniKatilimciTc" placeholder="T.C. Kimlik No (ops.)"
                    style="padding:.45rem .6rem;border-radius:.4rem;border:1px solid rgb(107 114 128 / .3);background:transparent;font-size:.82rem">
                <input type="date" wire:model="yeniSinavTarihi"
                    style="padding:.45rem .6rem;border-radius:.4rem;border:1px solid rgb(107 114 128 / .3);background:transparent;font-size:.82rem">
                <x-filament::button size="sm" wire:click="katilimciEkle">Listeye Ekle</x-filament::button>
            </div>

            @if ($katilimcilar)
                <table style="width:100%;border-collapse:collapse;font-size:.82rem">
                    <tr>
                        <th style="text-align:left;padding:.3rem .5rem;border-bottom:1px solid rgb(107 114 128 / .3)">Ad Soyad</th>
                        <th style="text-align:left;padding:.3rem .5rem;border-bottom:1px solid rgb(107 114 128 / .3)">Sınav Tarihi</th>
                        <th style="border-bottom:1px solid rgb(107 114 128 / .3)"></th>
                    </tr>
                    @foreach ($katilimcilar as $i => $k)
                        <tr>
                            <td style="padding:.3rem .5rem">{{ $k['ad_soyad'] }}</td>
                            <td style="padding:.3rem .5rem">{{ $k['sinav_tarihi'] ?: '—' }}</td>
                            <td style="padding:.3rem .5rem;text-align:right">
                                <button type="button" wire:click="katilimciSil({{ $i }})" style="color:#ef4444;cursor:pointer;background:none;border:none">✕</button>
                            </td>
                        </tr>
                    @endforeach
                </table>
            @endif
        </x-filament::section>

        {{-- 4. GEÇMİŞ SINAVLAR --}}
        @if ($this->gecmisSinavlar->isNotEmpty())
            <x-filament::section icon="heroicon-o-clock" icon-color="gray">
                <x-slot name="heading">Geçmiş Sınavlar</x-slot>
                <table style="width:100%;border-collapse:collapse;font-size:.82rem">
                    @foreach ($this->gecmisSinavlar as $s)
                        <tr>
                            <td style="padding:.3rem .5rem">{{ $s->sektorEtiketi() }} — {{ config('isg.egitim_sorulari.zorluklar.'.$s->zorluk) }} ({{ count($s->sorular ?? []) }} soru, {{ count($s->katilimcilar ?? []) }} katılımcı)</td>
                            <td style="padding:.3rem .5rem;text-align:right;white-space:nowrap">
                                <x-filament::button size="xs" color="gray" wire:click="gecmisPdf({{ $s->id }})">PDF</x-filament::button>
                                <x-filament::button size="xs" color="danger" wire:click="gecmisSil({{ $s->id }})">Sil</x-filament::button>
                            </td>
                        </tr>
                    @endforeach
                </table>
            </x-filament::section>
        @endif
    @else
        <p style="margin-top:1rem;font-size:.85rem;color:#f59e0b">Devam etmek için bir firma seçin.</p>
    @endif
</x-filament-panels::page>
