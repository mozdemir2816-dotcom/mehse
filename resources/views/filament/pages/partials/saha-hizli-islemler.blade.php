{{--
    Saha hızlı işlemleri (Concerns\SahaHizliIslemleri) — Ziyaret Modu'nda.
    Önce ayrı "Saha Hızlı İşlem" sayfasıydı (saha kontrolleri 5. aşama, 08.10.2026).
--}}
@php
    $inp = 'margin-top:.3rem;width:100%;padding:.6rem .75rem;border-radius:.5rem;border:1px solid rgb(107 114 128 / .35);background:transparent;font-size:.95rem';
    $lbl = 'font-weight:600;font-size:.85rem';
    $hata = fn (string $alan) => $errors->first($alan);
    $dugme = fn (string $p) => 'flex:1;padding:.65rem .8rem;border-radius:.6rem;cursor:pointer;font-weight:600;font-size:.9rem;border:1px solid '
        .($hizliIslem === $p ? 'rgb(124 58 237);background:rgb(124 58 237);color:#fff' : 'rgb(107 114 128 / .35);background:transparent;color:inherit');
@endphp

<div style="{{ $kart }}">
    <div style="{{ $baslik }};margin-bottom:.5rem">Hızlı işlemler</div>
    <div style="display:flex;gap:.5rem;flex-wrap:wrap">
        <button type="button" wire:click="hizliIslemAc('ptw')" style="{{ $dugme('ptw') }}">✓ İş iznini kapat{{ $this->aktifIzinler->isNotEmpty() ? ' ('.$this->aktifIzinler->count().')' : '' }}</button>
        <button type="button" wire:click="hizliIslemAc('ramak')" style="{{ $dugme('ramak') }}">⚠ Ramak kala + foto</button>
    </div>

    @if ($hizliIslem === 'ptw')
        <div style="margin-top:.9rem;display:flex;flex-direction:column;gap:.8rem">
            <div>
                <label style="{{ $lbl }}">Aktif izin</label>
                <select wire:model="ptwIzinId" style="{{ $inp }}">
                    <option value="">{{ $this->aktifIzinler->isEmpty() ? 'Kapatılacak aktif izin yok' : 'Seçiniz' }}</option>
                    @foreach ($this->aktifIzinler as $iz)
                        <option value="{{ $iz->id }}">
                            {{ $iz->izin_no ?: '#'.$iz->id }} — {{ $iz->calisma_alani ?: 'Alan belirtilmemiş' }}
                            ({{ config('isg.is_izin.durumlar.'.$iz->durum, $iz->durum) }})
                        </option>
                    @endforeach
                </select>
                @if ($hata('ptwIzinId'))<div style="color:#dc2626;font-size:.78rem">{{ $hata('ptwIzinId') }}</div>@endif
            </div>
            <div>
                <label style="{{ $lbl }}">Kapanış notu</label>
                <textarea wire:model="ptwKapanisNotu" rows="3" placeholder="Saha temiz, ekipman toplandı, yangın nöbeti tamamlandı…" style="{{ $inp }}"></textarea>
            </div>
            <label style="display:flex;gap:.5rem;align-items:center;font-size:.9rem">
                <input type="checkbox" wire:model="ptwSahaTeslim"> Saha teslim alındı
            </label>
            <div>
                <label style="{{ $lbl }}">Kamera kanıtı (isteğe bağlı)</label>
                <input type="file" wire:model="ptwKapanisFoto" accept="image/*" capture="environment" style="{{ $inp }}">
                @if ($ptwKapanisFoto && method_exists($ptwKapanisFoto, 'temporaryUrl'))
                    <img src="{{ $ptwKapanisFoto->temporaryUrl() }}" style="margin-top:.5rem;max-height:140px;border-radius:.4rem">
                @endif
                @if ($hata('ptwKapanisFoto'))<div style="color:#dc2626;font-size:.78rem">{{ $hata('ptwKapanisFoto') }}</div>@endif
            </div>
            <x-filament::button size="lg" icon="heroicon-o-check-circle" wire:click="izniKapat" wire:loading.attr="disabled">Saha kapanışını onayla</x-filament::button>
        </div>
    @endif

    @if ($hizliIslem === 'ramak')
        <div style="margin-top:.9rem;display:flex;flex-direction:column;gap:.8rem">
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:.6rem">
                <div><label style="{{ $lbl }}">Tarih</label><input type="date" wire:model="ramakTarih" style="{{ $inp }}"></div>
                <div><label style="{{ $lbl }}">Yer</label><input type="text" wire:model="ramakYer" placeholder="Örn. Depo rampası" style="{{ $inp }}"></div>
            </div>
            <div>
                <label style="{{ $lbl }}">Sınıflandırma</label>
                <select wire:model="ramakSiniflandirma" style="{{ $inp }}">
                    <option value="">Seçiniz</option>
                    @foreach (config('isg.olay.siniflandirmalar') as $k => $ad)
                        <option value="{{ $k }}">{{ $ad }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label style="{{ $lbl }}">Kısa özet (en az 20 karakter)</label>
                <textarea wire:model="ramakOzet" rows="2" style="{{ $inp }}"></textarea>
                @if ($hata('ramakOzet'))<div style="color:#dc2626;font-size:.78rem">{{ $hata('ramakOzet') }}</div>@endif
            </div>
            <div>
                <label style="{{ $lbl }}">Detay (en az 30 karakter — isteğe bağlı)</label>
                <textarea wire:model="ramakDetay" rows="3" placeholder="Olay nasıl gelişti?" style="{{ $inp }}"></textarea>
                @if ($hata('ramakDetay'))<div style="color:#dc2626;font-size:.78rem">{{ $hata('ramakDetay') }}</div>@endif
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
        </div>
    @endif
</div>
