@php
    $inp = 'margin-top:.3rem;width:100%;padding:.6rem .75rem;border-radius:.5rem;border:1px solid rgb(107 114 128 / .35);background:transparent;font-size:.95rem';
    $lbl = 'font-weight:600;font-size:.85rem';
    $hata = fn (string $alan) => $errors->first($alan);
    $sekmeStil = fn (string $s) => 'padding:.55rem .9rem;border-radius:.6rem;cursor:pointer;font-weight:600;font-size:.88rem;border:1px solid '
        .($sekme === $s ? 'rgb(124 58 237);background:rgb(124 58 237);color:#fff' : 'rgb(107 114 128 / .35);background:transparent');
@endphp

<x-filament-panels::page>
    <div style="max-width:40rem;width:100%">
        <div style="display:flex;gap:.5rem;flex-wrap:wrap;margin-bottom:1rem">
            <button type="button" wire:click="$set('sekme', 'ptw')" style="{{ $sekmeStil('ptw') }}">✓ PTW Kapat</button>
            <button type="button" wire:click="$set('sekme', 'ramak')" style="{{ $sekmeStil('ramak') }}">⚠ Ramak Kala + Foto</button>
            <button type="button" wire:click="$set('sekme', 'denetim')" style="{{ $sekmeStil('denetim') }}">📋 Saha Denetimi</button>
        </div>

        <label style="{{ $lbl }}">İşyeri</label>
        <select wire:model.live="firmaId" style="{{ $inp }}">
            <option value="">İşyeri seçin</option>
            @foreach ($this->firmalar as $id => $ad)
                <option value="{{ $id }}">{{ $ad }}</option>
            @endforeach
        </select>
        @if ($hata('firmaId'))<div style="color:#dc2626;font-size:.78rem">{{ $hata('firmaId') }}</div>@endif

        <div style="margin-top:1.1rem;display:flex;flex-direction:column;gap:.9rem">
            @if ($sekme === 'ptw')
                <div style="font-weight:700">Aktif çalışma iznini sahada kapat</div>
                <div>
                    <label style="{{ $lbl }}">Aktif izin</label>
                    <select wire:model="izinId" style="{{ $inp }}" @disabled(! $this->firma)>
                        <option value="">{{ $this->firma ? ($this->aktifIzinler->isEmpty() ? 'Kapatılacak aktif izin yok' : 'Seçiniz') : 'Önce işyeri seçin' }}</option>
                        @foreach ($this->aktifIzinler as $iz)
                            <option value="{{ $iz->id }}">
                                {{ $iz->izin_no ?: '#'.$iz->id }} — {{ $iz->calisma_alani ?: 'Alan belirtilmemiş' }}
                                ({{ config('isg.is_izin.durumlar.'.$iz->durum, $iz->durum) }})
                            </option>
                        @endforeach
                    </select>
                    @if ($hata('izinId'))<div style="color:#dc2626;font-size:.78rem">{{ $hata('izinId') }}</div>@endif
                </div>
                <div>
                    <label style="{{ $lbl }}">Kapanış notu</label>
                    <textarea wire:model="kapanisNotu" rows="3" placeholder="Saha temiz, ekipman toplandı, yangın nöbeti tamamlandı…" style="{{ $inp }}"></textarea>
                </div>
                <label style="display:flex;gap:.5rem;align-items:center;font-size:.9rem">
                    <input type="checkbox" wire:model="sahaTeslim"> Saha teslim alındı
                </label>
                <div>
                    <label style="{{ $lbl }}">Kamera kanıtı (isteğe bağlı)</label>
                    <input type="file" wire:model="kapanisFoto" accept="image/*" capture="environment" style="{{ $inp }}">
                    @if ($kapanisFoto && method_exists($kapanisFoto, 'temporaryUrl'))
                        <img src="{{ $kapanisFoto->temporaryUrl() }}" style="margin-top:.5rem;max-height:140px;border-radius:.4rem">
                    @endif
                    @if ($hata('kapanisFoto'))<div style="color:#dc2626;font-size:.78rem">{{ $hata('kapanisFoto') }}</div>@endif
                </div>
                <x-filament::button size="lg" icon="heroicon-o-check-circle" wire:click="izniKapat" wire:loading.attr="disabled">Saha kapanışını onayla</x-filament::button>
            @endif

            @if ($sekme === 'ramak')
                <div style="font-weight:700">Ramak kala olayını fotoğrafla kaydet</div>
                <div>
                    <label style="{{ $lbl }}">Tarih</label>
                    <input type="date" wire:model="tarih" style="{{ $inp }}">
                </div>
                <div>
                    <label style="{{ $lbl }}">Yer</label>
                    <input type="text" wire:model="yer" placeholder="Örn. Depo rampası" style="{{ $inp }}">
                </div>
                <div>
                    <label style="{{ $lbl }}">Sınıflandırma</label>
                    <select wire:model="siniflandirma" style="{{ $inp }}">
                        <option value="">Seçiniz</option>
                        @foreach (config('isg.olay.siniflandirmalar') as $k => $ad)
                            <option value="{{ $k }}">{{ $ad }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label style="{{ $lbl }}">Kısa özet (en az 20 karakter)</label>
                    <textarea wire:model="ozet" rows="2" style="{{ $inp }}"></textarea>
                    @if ($hata('ozet'))<div style="color:#dc2626;font-size:.78rem">{{ $hata('ozet') }}</div>@endif
                </div>
                <div>
                    <label style="{{ $lbl }}">Detay (en az 30 karakter — isteğe bağlı)</label>
                    <textarea wire:model="detay" rows="3" placeholder="Olay nasıl gelişti?" style="{{ $inp }}"></textarea>
                    @if ($hata('detay'))<div style="color:#dc2626;font-size:.78rem">{{ $hata('detay') }}</div>@endif
                </div>
                <div>
                    <label style="{{ $lbl }}">Kamera fotoğrafı (en fazla 3)</label>
                    <input type="file" wire:model="ramakFotolar" accept="image/*" multiple style="{{ $inp }}">
                    <div style="display:flex;gap:.4rem;flex-wrap:wrap;margin-top:.5rem">
                        @foreach ($ramakFotolar as $f)
                            @if (method_exists($f, 'temporaryUrl'))<img src="{{ $f->temporaryUrl() }}" style="width:72px;height:72px;object-fit:cover;border-radius:.4rem">@endif
                        @endforeach
                    </div>
                    @if ($hata('ramakFotolar') || $hata('ramakFotolar.*'))<div style="color:#dc2626;font-size:.78rem">{{ $hata('ramakFotolar') ?: $hata('ramakFotolar.*') }}</div>@endif
                </div>
                <x-filament::button size="lg" icon="heroicon-o-shield-exclamation" color="warning" wire:click="ramakKalaKaydet" wire:loading.attr="disabled">Ramak kala kaydını oluştur</x-filament::button>

                @if ($this->sonRamakKalalar->isNotEmpty())
                    <div style="font-size:.82rem;border-top:1px solid rgb(107 114 128 / .2);padding-top:.7rem">
                        <div style="font-weight:600;margin-bottom:.3rem">Son ramak kala kayıtları</div>
                        @foreach ($this->sonRamakKalalar as $o)
                            <div style="padding:.2rem 0">{{ $o->belge_no }} · {{ $o->olay_tarihi?->format('d.m.Y') }} · {{ \Illuminate\Support\Str::limit($o->olay_ozeti, 60) }}</div>
                        @endforeach
                        <a href="{{ \App\Filament\Pages\OlayKayitlari::getUrl(['firma' => $firmaId]) }}" style="color:rgb(124 58 237);text-decoration:underline">Olay Kayıtları'nda aç (kök neden, DÖF)</a>
                    </div>
                @endif
            @endif

            @if ($sekme === 'denetim')
                <div style="font-weight:700">Saha denetimi</div>
                @foreach ([
                    [\App\Filament\Pages\HizliSahaBulgusu::class, 'Hızlı Saha Bulgusu', 'Tek uygunsuzluğu fotoğraf, risk skoru ve aksiyonla kaydet', 'heroicon-o-camera'],
                    [\App\Filament\Pages\SahaDenetimi::class, 'Saha Denetimi (kontrol listesi)', '41 maddelik denetim, uygunluk yüzdesi ve PDF rapor', 'heroicon-o-clipboard-document-check'],
                    [\App\Filament\Pages\AiSahaAnalizi::class, 'AI Saha Analizi', 'Birden çok fotoğraftan AI tespitleri ve gözetim raporu', 'heroicon-o-sparkles'],
                ] as [$sayfa, $ad, $aciklama, $ikon])
                    <a href="{{ $sayfa::getUrl(array_filter(['firma' => $firmaId])) }}"
                        style="display:flex;gap:.75rem;align-items:center;border:1px solid rgb(107 114 128 / .3);border-radius:.75rem;padding:.9rem 1rem;text-decoration:none;color:inherit">
                        <x-filament::icon :icon="$ikon" style="width:1.6rem;height:1.6rem;color:rgb(124 58 237)" />
                        <span><strong>{{ $ad }}</strong><br><span style="font-size:.8rem;color:rgb(107 114 128)">{{ $aciklama }}</span></span>
                    </a>
                @endforeach
            @endif
        </div>
    </div>
</x-filament-panels::page>
