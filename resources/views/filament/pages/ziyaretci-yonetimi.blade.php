@php
    $inp = 'margin-top:.3rem;width:100%;padding:.5rem .75rem;border-radius:.5rem;border:1px solid rgb(107 114 128 / .35);background:transparent';
    $lbl = 'font-weight:600;font-size:.82rem';
    $th = 'text-align:left;padding:.4rem .5rem;border-bottom:1px solid rgb(107 114 128 / .3);font-size:.72rem;text-transform:uppercase;letter-spacing:.03em;color:rgb(107 114 128)';
    $td = 'padding:.4rem .5rem;border-bottom:1px solid rgb(107 114 128 / .12);vertical-align:top';
    $kutu = 'border:1px solid rgb(107 114 128 / .3);border-radius:.75rem;padding:.75rem 1rem';
    $renk = ['gecerli' => 'rgb(22 163 74 / .55)', 'icerde' => 'rgb(124 58 237 / .6)', 'planli' => 'rgb(245 158 11 / .6)', 'cikti' => 'rgb(107 114 128 / .45)', 'suresi_doldu' => 'rgb(220 38 38 / .6)', 'iptal' => 'rgb(220 38 38 / .6)'];
    $hata = fn (string $alan) => $errors->first($alan);
@endphp

<x-filament-panels::page>
    <p style="font-size:.85rem;color:rgb(107 114 128);margin-top:-.5rem">
        İşyerine gelen tedarikçi, denetçi, misafir gibi dış kişilere süreli ve QR'lı geçiş kartı verin.
        Güvenlik QR'ı okutarak kartın geçerliliğini görür. Giriş / çıkış kaydı tutulur ve acil durumda
        içerideki ziyaretçilerin sayım listesi alınabilir.
    </p>

    @php $o = $this->ozet; @endphp
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:.75rem">
        @foreach ([['Bugünkü ziyaretçi', $o['bugun'], 'inherit'], ['Şu an içeride', $o['iceride'], 'rgb(124 58 237)'], ['Geçerli kart', $o['gecerli'], '#16a34a']] as [$ad, $sayi, $c])
            <div style="{{ $kutu }}">
                <div style="font-size:.75rem;color:rgb(107 114 128)">{{ $ad }}</div>
                <div style="font-size:1.5rem;font-weight:700;color:{{ $sayi > 0 ? $c : 'inherit' }}">{{ $sayi }}</div>
            </div>
        @endforeach
    </div>

    {{-- GEÇİŞ FORMU --}}
    <x-filament::section icon="heroicon-o-identification" icon-color="primary">
        <x-slot name="heading">Yeni Geçiş Kartı</x-slot>

        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(240px,1fr));gap:1rem">
            <div>
                <label style="{{ $lbl }}">İşyeri *</label>
                <select wire:model="formFirmaId" style="{{ $inp }}">
                    <option value="">İşyeri seçin</option>
                    @foreach ($this->firmalar as $id => $ad)
                        <option value="{{ $id }}">{{ $ad }}</option>
                    @endforeach
                </select>
                @if ($hata('formFirmaId'))<div style="color:#dc2626;font-size:.75rem">{{ $hata('formFirmaId') }}</div>@endif
            </div>
            <div>
                <label style="{{ $lbl }}">Ad soyad *</label>
                <input type="text" wire:model="adSoyad" style="{{ $inp }}">
                @if ($hata('adSoyad'))<div style="color:#dc2626;font-size:.75rem">{{ $hata('adSoyad') }}</div>@endif
            </div>
            <div>
                <label style="{{ $lbl }}">Kurum</label>
                <input type="text" wire:model="kurum" style="{{ $inp }}">
            </div>
            <div>
                <label style="{{ $lbl }}">Telefon</label>
                <input type="tel" wire:model="telefon" style="{{ $inp }}">
            </div>
            <div>
                <label style="{{ $lbl }}">Ziyaret amacı</label>
                <input type="text" wire:model="ziyaretAmaci" placeholder="Örn. bakım, denetim, toplantı" style="{{ $inp }}">
            </div>
            <div>
                <label style="{{ $lbl }}">Ziyaret edilen kişi / refakatçi</label>
                <input type="text" wire:model="ziyaretEdilen" style="{{ $inp }}">
            </div>
            <div>
                <label style="{{ $lbl }}">Geçerlilik başlangıcı *</label>
                <input type="datetime-local" wire:model="baslangic" style="{{ $inp }}">
            </div>
            <div>
                <label style="{{ $lbl }}">Geçerlilik bitişi *</label>
                <input type="datetime-local" wire:model="bitis" style="{{ $inp }}">
                @if ($hata('bitis'))<div style="color:#dc2626;font-size:.75rem">{{ $hata('bitis') }}</div>@endif
            </div>
            <div>
                <label style="{{ $lbl }}">Verilen KKD</label>
                <input type="text" wire:model="verilenKkd" placeholder="Örn. Baret, yelek, gözlük" style="{{ $inp }}">
            </div>
            <div>
                <label style="{{ $lbl }}">Not</label>
                <input type="text" wire:model="notlar" style="{{ $inp }}">
            </div>
        </div>

        <div style="display:flex;justify-content:space-between;align-items:center;gap:1rem;flex-wrap:wrap;margin-top:1rem">
            <label style="display:flex;align-items:center;gap:.45rem;font-size:.85rem;cursor:pointer">
                <input type="checkbox" wire:model="isgBilgilendirme">
                Ziyaretçiye İSG kuralları ve acil durumda yapılacaklar anlatıldı
            </label>
            <x-filament::button icon="heroicon-o-qr-code" wire:click="gecisOlustur">Geçiş Oluştur (Kartı İndir)</x-filament::button>
        </div>
    </x-filament::section>

    {{-- LİSTE --}}
    <x-filament::section icon="heroicon-o-queue-list" icon-color="gray">
        <x-slot name="heading">Ziyaretçiler ({{ $this->ziyaretciler->count() }})</x-slot>

        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:.75rem;margin-bottom:.75rem">
            <select wire:model.live="firmaId" style="{{ $inp }}">
                <option value="">Tüm işyerleri</option>
                @foreach ($this->firmalar as $id => $ad)
                    <option value="{{ $id }}">{{ $ad }}</option>
                @endforeach
            </select>
            <select wire:model.live="donem" style="{{ $inp }}">
                <option value="bugun">Bugün + içeridekiler</option>
                <option value="hafta">Son 7 gün</option>
                <option value="ay">Son 30 gün</option>
                <option value="hepsi">Tümü</option>
            </select>
            <select wire:model.live="durumFiltre" style="{{ $inp }}">
                <option value="">Tüm durumlar</option>
                @foreach (config('isg.ziyaretci.durumlar') as $anahtar => $ad)
                    <option value="{{ $anahtar }}">{{ $ad }}</option>
                @endforeach
            </select>
            <input type="search" wire:model.live.debounce.400ms="arama" placeholder="Ad, kurum, kart no ara…" style="{{ $inp }}">
        </div>

        @if ($this->ziyaretciler->isNotEmpty())
            <div style="overflow-x:auto">
                <table style="width:100%;border-collapse:collapse;font-size:.8rem;min-width:860px">
                    <tr>
                        @foreach (['Ziyaretçi', 'İşyeri', 'Amaç', 'Geçerlilik', 'Giriş / Çıkış', 'Durum', ''] as $b)
                            <th style="{{ $th }}">{{ $b }}</th>
                        @endforeach
                    </tr>
                    @foreach ($this->ziyaretciler as $z)
                        @php $d = $z->durum(); @endphp
                        <tr>
                            <td style="{{ $td }}">
                                <strong>{{ $z->ad_soyad }}</strong>
                                <div style="font-size:.72rem;color:rgb(107 114 128)">{{ collect([$z->kurum, $z->kart_no])->filter()->implode(' · ') }}</div>
                            </td>
                            <td style="{{ $td }}">{{ $z->firma?->unvan }}</td>
                            <td style="{{ $td }}">
                                {{ $z->ziyaret_amaci ?: '—' }}
                                @if ($z->ziyaret_edilen)<div style="font-size:.72rem;color:rgb(107 114 128)">{{ $z->ziyaret_edilen }}</div>@endif
                            </td>
                            <td style="{{ $td }};white-space:nowrap">{{ $z->gecerlilik_baslangic->format('d.m H:i') }} – {{ $z->gecerlilik_bitis->format('d.m H:i') }}</td>
                            <td style="{{ $td }};white-space:nowrap">{{ $z->giris_zamani?->format('d.m H:i') ?? '—' }} / {{ $z->cikis_zamani?->format('d.m H:i') ?? '—' }}</td>
                            <td style="{{ $td }}">
                                <span style="font-size:.72rem;padding:.1rem .5rem;border-radius:999px;white-space:nowrap;border:1px solid {{ $renk[$d] }}">{{ $z->durumEtiketi() }}</span>
                                @if (! $z->isg_bilgilendirme)<div style="font-size:.68rem;color:#d97706">İSG bilgilendirmesi yok</div>@endif
                            </td>
                            <td style="{{ $td }};text-align:right;white-space:nowrap">
                                <x-filament::button size="xs" color="gray" icon="heroicon-o-qr-code" wire:click="kart({{ $z->id }})">Kart</x-filament::button>
                                @if (in_array($d, ['gecerli', 'planli'], true) && ! $z->giris_zamani)
                                    <x-filament::button size="xs" color="success" wire:click="giris({{ $z->id }})">Giriş</x-filament::button>
                                @endif
                                @if ($z->iceridemi())
                                    <x-filament::button size="xs" color="warning" wire:click="cikis({{ $z->id }})">Çıkış</x-filament::button>
                                @endif
                                @if (! $z->iptal && ! $z->cikis_zamani)
                                    <x-filament::button size="xs" color="gray" wire:click="iptalEt({{ $z->id }})" wire:confirm="Kart iptal edilsin mi? QR doğrulamasında geçersiz görünür.">İptal</x-filament::button>
                                @endif
                                <x-filament::button size="xs" color="danger" wire:click="sil({{ $z->id }})" wire:confirm="Kayıt silinsin mi?">Sil</x-filament::button>
                            </td>
                        </tr>
                    @endforeach
                </table>
            </div>
        @else
            <p style="font-size:.83rem;color:rgb(107 114 128);text-align:center;padding:1rem">Bu filtrelerle ziyaretçi kaydı yok.</p>
        @endif
    </x-filament::section>
</x-filament-panels::page>
