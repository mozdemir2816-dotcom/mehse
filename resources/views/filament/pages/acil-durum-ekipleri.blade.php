@php
    use App\Models\AcilEkipUyesi;
    $firma = $this->firma;
    $o = $this->ozet;
    $girdi = 'padding:.45rem .7rem;border-radius:.5rem;border:1px solid rgb(107 114 128 / .35);background:transparent;font-size:.85rem';
    $kart = 'border:1px solid rgb(107 114 128 / .2);border-radius:.75rem;padding:.75rem 1rem';
    $th = 'text-align:left;padding:.5rem .6rem;font-size:.7rem;letter-spacing:.05em;text-transform:uppercase;color:rgb(107 114 128);border-bottom:1px solid rgb(107 114 128 / .2)';
    $td = 'padding:.5rem .6rem;border-bottom:1px solid rgb(107 114 128 / .12);vertical-align:top';
    $durumRenk = ['tam' => 'rgb(21 128 61)', 'eksik' => 'rgb(217 119 6)', 'kritik' => 'rgb(220 38 38)'];
    $durumAd = ['tam' => 'Tam', 'eksik' => 'Eksik', 'kritik' => 'Kritik'];
    $belgeRenk = ['gecerli' => 'rgb(21 128 61)', 'yaklasan' => 'rgb(217 119 6)', 'dolmus' => 'rgb(220 38 38)', 'yok' => 'rgb(107 114 128)'];
@endphp

<x-filament-panels::page>
    <div style="font-size:.85rem;color:rgb(107 114 128);margin-top:-.75rem">Söndürme, kurtarma, koruma, ilk yardım, tahliye ve haberleşme ekipleri ile destek elemanı görevlendirmeleri.</div>

    <div style="display:flex;flex-wrap:wrap;gap:.4rem">
        @if ($firma)
            <x-filament::button size="sm" icon="heroicon-o-user-plus" wire:click="mountAction('uyeEkle')" :disabled="$this->ekipler->isEmpty()">Destek Elemanı Ekle</x-filament::button>
            <x-filament::button size="sm" color="gray" icon="heroicon-o-plus" wire:click="mountAction('yeniEkip')">Yeni Ekip</x-filament::button>
            <x-filament::button size="sm" color="gray" icon="heroicon-o-squares-plus" wire:click="temelEkipler">Temel Ekipleri Oluştur</x-filament::button>
            <x-filament::button size="sm" color="gray" icon="heroicon-o-arrow-uturn-left" wire:click="$toggle('silinenlerAcik')">Silinenleri Geri Al</x-filament::button>
            <x-filament::button size="sm" color="gray" icon="heroicon-o-arrow-down-tray" wire:click="excel">Excel</x-filament::button>
            <x-filament::button size="sm" color="gray" icon="heroicon-o-document-arrow-down" wire:click="pdf">PDF</x-filament::button>
        @endif
    </div>

    <div style="{{ $kart }}">
        <label style="display:block;font-size:.75rem;font-weight:600">İşyeri</label>
        <select wire:model.live="firmaId" style="{{ $girdi }};width:100%;max-width:520px">
            @foreach ($this->firmalar as $id => $ad)<option value="{{ $id }}">{{ $ad }}</option>@endforeach
        </select>
        @if ($firma)
            <div style="font-size:.75rem;color:rgb(107 114 128);margin-top:.4rem">
                <strong>SGK Sicil:</strong> {{ $firma->sgk_sicil_no ?: '—' }} · <strong>Tehlike Sınıfı:</strong> {{ $firma->tehlikeSinifiEtiketi() }} · <strong>Personel:</strong> {{ $o['calisan'] ?: '—' }} · <strong>İSG Uzmanı:</strong> {{ $firma->igu?->ad_soyad ?: '—' }}
                · <a href="{{ \App\Filament\Pages\AcilDurumPlani::getUrl(['firma' => $firma->id]) }}" style="color:rgb(13 148 136);font-weight:600">Acil Durum Planı</a>
            </div>
        @endif
    </div>

    @if (! $firma)
        <x-filament::section><div style="text-align:center;padding:1rem;color:rgb(107 114 128)">Aktif işyeri yok.</div></x-filament::section>
    @else
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(120px,1fr));gap:.5rem">
            @foreach ([['Ekip', $o['ekip'], null], ['Üye', $o['uye'], null], ['Lider', $o['lider'], null], ['Tam Ekip', $o['tam'], 'rgb(21 128 61)'], ['Kritik Ekip', $o['kritik'], $o['kritik'] ? 'rgb(220 38 38)' : null], ['Belge Süresi Dolan', $o['belge_dolan'], $o['belge_dolan'] ? 'rgb(220 38 38)' : null], ['30 Gün İçinde', $o['yaklasan'], $o['yaklasan'] ? 'rgb(217 119 6)' : null]] as [$ad, $deger, $renk])
                <div style="{{ $kart }}">
                    <div style="font-size:.72rem;color:rgb(107 114 128)">{{ $ad }}</div>
                    <div style="font-size:1.35rem;font-weight:800;{{ $renk ? 'color:'.$renk : '' }}">{{ $deger }}</div>
                </div>
            @endforeach
        </div>

        @if ($this->silinenlerAcik)
            <x-filament::section heading="Silinen ekip ve üyeler">
                @php $sil = $this->silinenler; @endphp
                @if ($sil['ekipler']->isEmpty() && $sil['uyeler']->isEmpty())
                    <div style="font-size:.8rem;color:rgb(107 114 128)">Silinen kayıt yok.</div>
                @endif
                @foreach ($sil['ekipler'] as $e)
                    <div style="display:flex;justify-content:space-between;align-items:center;padding:.35rem 0;border-bottom:1px solid rgb(107 114 128 / .12);font-size:.82rem">
                        <span><strong>Ekip:</strong> {{ $e->ad }} <span style="color:rgb(107 114 128)">· {{ $e->deleted_at->format('d.m.Y H:i') }}</span></span>
                        <x-filament::button size="xs" color="gray" wire:click="geriAl('ekip', {{ $e->id }})">Geri Al</x-filament::button>
                    </div>
                @endforeach
                @foreach ($sil['uyeler'] as $u)
                    <div style="display:flex;justify-content:space-between;align-items:center;padding:.35rem 0;border-bottom:1px solid rgb(107 114 128 / .12);font-size:.82rem">
                        <span><strong>Üye:</strong> {{ $u->ad_soyad }} <span style="color:rgb(107 114 128)">· {{ $u->ekip?->ad }} · {{ $u->deleted_at->format('d.m.Y H:i') }}</span></span>
                        <x-filament::button size="xs" color="gray" wire:click="geriAl('uye', {{ $u->id }})">Geri Al</x-filament::button>
                    </div>
                @endforeach
            </x-filament::section>
        @endif

        @if ($o['oneriler'])
            <div style="border:1px solid rgb(217 119 6 / .5);border-left:4px solid rgb(217 119 6);border-radius:.6rem;padding:.6rem .9rem">
                <div style="font-weight:700;font-size:.85rem;margin-bottom:.3rem">⚠ Kontrol Önerileri</div>
                <ul style="margin:0;padding-left:1.1rem;font-size:.78rem;list-style:disc">
                    @foreach ($o['oneriler'] as $oneri)<li>{{ $oneri }}</li>@endforeach
                </ul>
            </div>
        @endif

        @if ($this->ekipler->isEmpty())
            <x-filament::section>
                <div style="text-align:center;padding:1rem">
                    <div style="font-weight:700">Bu işyerinde henüz acil durum ekibi yok</div>
                    <div style="font-size:.8rem;color:rgb(107 114 128);margin:.3rem 0 .8rem">Söndürme, kurtarma, koruma, ilk yardım, tahliye ve haberleşme ekiplerini tek tıkla oluşturun.</div>
                    <x-filament::button size="sm" wire:click="temelEkipler">Temel Ekipleri Oluştur</x-filament::button>
                </div>
            </x-filament::section>
        @else
            <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(250px,1fr));gap:.6rem">
                @foreach ($this->ekipler as $e)
                    @php $d = $o['durumlar'][$e->id]; @endphp
                    <div wire:key="ekip-{{ $e->id }}" style="{{ $kart }};display:flex;flex-direction:column;gap:.3rem">
                        <div style="display:flex;justify-content:space-between;gap:.4rem;align-items:flex-start">
                            <div>
                                <div style="font-weight:700;font-size:.9rem">{{ $e->ad }}</div>
                                <div style="font-size:.7rem;color:rgb(107 114 128)">{{ collect([$e->turEtiketi() !== $e->ad ? $e->turEtiketi() : null, $e->yasalOranMetni() ? 'yasal: '.$e->yasalOranMetni() : 'yasal oran yok, öneri'])->filter()->implode(' · ') }}</div>
                            </div>
                            <span style="font-size:.68rem;font-weight:700;padding:.1rem .5rem;border-radius:9999px;color:{{ $durumRenk[$d['durum']] }};background:color-mix(in srgb, {{ $durumRenk[$d['durum']] }} 12%, transparent)">{{ $durumAd[$d['durum']] }}</span>
                        </div>
                        <div style="font-size:.75rem">Asıl: <strong>{{ $d['asil'] }}</strong> · Yedek: <strong>{{ $d['yedek'] }}</strong> · Min: <strong>{{ $d['min'] }}</strong></div>
                        <div style="font-size:.75rem;color:rgb(107 114 128)">Lider: {{ $d['lider'] ?: '—' }}</div>
                        @if ($e->uyeler->isNotEmpty())
                            <div style="font-size:.74rem;line-height:1.5">
                                @foreach ($e->uyeler as $u)
                                    <div style="display:flex;justify-content:space-between;gap:.3rem">
                                        <span>{{ $u->ad_soyad }}@if ($u->lider) <strong style="color:rgb(13 148 136)">★</strong>@endif @if ($u->uyelik === 'yedek')<span style="color:rgb(107 114 128)">(yedek)</span>@endif</span>
                                        <span style="width:.5rem;height:.5rem;border-radius:9999px;margin-top:.35rem;flex:none;background:{{ $belgeRenk[$u->belgeDurumu()] }}" title="{{ AcilEkipUyesi::BELGE_DURUMLARI[$u->belgeDurumu()] }}"></span>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                        @if ($d['uyarilar'])
                            <div style="font-size:.7rem;color:{{ $durumRenk[$d['durum'] === 'tam' ? 'eksik' : $d['durum']] }}">{{ $d['uyarilar'][0] }}</div>
                        @endif
                        <div style="display:flex;gap:.3rem;margin-top:auto;padding-top:.3rem">
                            <x-filament::button size="xs" color="gray" icon="heroicon-o-user-plus" wire:click="mountAction('uyeEkle', { ekip: {{ $e->id }} })">Üye</x-filament::button>
                            <x-filament::button size="xs" color="gray" icon="heroicon-o-pencil" wire:click="mountAction('ekipDuzenle', { id: {{ $e->id }} })">Düzenle</x-filament::button>
                            <x-filament::button size="xs" color="danger" icon="heroicon-o-trash" wire:click="ekipSil({{ $e->id }})" wire:confirm="{{ $e->ad }} silinsin mi? (Geri alınabilir)">Sil</x-filament::button>
                        </div>
                    </div>
                @endforeach
            </div>

            <x-filament::section heading="Üye Filtreleri" description="Ekip üyelerini görev, ekip, üyelik, belge durumu ve vardiyaya göre süzün.">
                <x-slot name="afterHeader"><span style="font-size:.75rem;border:1px solid rgb(107 114 128 / .3);border-radius:9999px;padding:.1rem .6rem">{{ $this->uyeler->count() }} sonuç</span></x-slot>
                <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:.5rem;margin-bottom:.8rem">
                    <div><label style="font-size:.72rem;font-weight:600">Arama</label><input type="search" wire:model.live.debounce.400ms="arama" placeholder="Ad, görev, bölüm veya sicil no…" style="{{ $girdi }};width:100%"></div>
                    <div><label style="font-size:.72rem;font-weight:600">Ekip</label>
                        <select wire:model.live="ekipFiltre" style="{{ $girdi }};width:100%"><option value="">Tüm ekipler</option>@foreach ($this->ekipler as $e)<option value="{{ $e->id }}">{{ $e->ad }}</option>@endforeach</select></div>
                    <div><label style="font-size:.72rem;font-weight:600">Üyelik</label>
                        <select wire:model.live="uyelikFiltre" style="{{ $girdi }};width:100%"><option value="">Asıl + Yedek</option><option value="asil">Asıl</option><option value="yedek">Yedek</option></select></div>
                    <div><label style="font-size:.72rem;font-weight:600">Belge durumu</label>
                        <select wire:model.live="belgeFiltre" style="{{ $girdi }};width:100%"><option value="">Tüm belgeler</option>@foreach (AcilEkipUyesi::BELGE_DURUMLARI as $k => $ad)<option value="{{ $k }}">{{ $ad }}</option>@endforeach</select></div>
                    <div><label style="font-size:.72rem;font-weight:600">Vardiya</label>
                        <select wire:model.live="vardiyaFiltre" style="{{ $girdi }};width:100%"><option value="">Tümü</option>@foreach (config('isg.acil_durum.vardiyalar') as $v)<option value="{{ $v }}">{{ $v }}</option>@endforeach</select></div>
                </div>
                <div style="overflow-x:auto">
                    <table style="width:100%;border-collapse:collapse;font-size:.8rem">
                        <thead><tr><th style="{{ $th }}">Ad Soyad</th><th style="{{ $th }}">Ekip</th><th style="{{ $th }}">Üyelik</th><th style="{{ $th }}">Görev</th><th style="{{ $th }}">Vardiya</th><th style="{{ $th }}">Belge</th><th style="{{ $th }}">Bitiş</th><th style="{{ $th }}"></th></tr></thead>
                        <tbody>
                            @forelse ($this->uyeler as $u)
                                @php $bd = $u->belgeDurumu(); @endphp
                                <tr wire:key="uye-{{ $u->id }}">
                                    <td style="{{ $td }}"><strong>{{ $u->ad_soyad }}</strong>@if ($u->lider) <span style="color:rgb(13 148 136);font-size:.7rem">★ Lider</span>@endif</td>
                                    <td style="{{ $td }}">{{ $u->ekip?->ad }}</td>
                                    <td style="{{ $td }}">{{ $u->uyelikEtiketi() }}</td>
                                    <td style="{{ $td }}">{{ collect([$u->gorev, $u->bolum])->filter()->implode(' / ') ?: '—' }}</td>
                                    <td style="{{ $td }}">{{ $u->vardiya ?: '—' }}</td>
                                    <td style="{{ $td }};color:{{ $belgeRenk[$bd] }}">{{ AcilEkipUyesi::BELGE_DURUMLARI[$bd] }}@if ($u->belge_no)<div style="font-size:.7rem;color:rgb(107 114 128)">{{ $u->belge_no }}</div>@endif</td>
                                    <td style="{{ $td }}">{{ $u->belgeBitisTarihi()?->format('d.m.Y') ?? '—' }}</td>
                                    <td style="{{ $td }};white-space:nowrap">
                                        <x-filament::button size="xs" color="gray" wire:click="mountAction('uyeDuzenle', { id: {{ $u->id }} })">Düzenle</x-filament::button>
                                        <x-filament::button size="xs" color="danger" wire:click="uyeSil({{ $u->id }})" wire:confirm="{{ $u->ad_soyad }} ekipten çıkarılsın mı? (Geri alınabilir)">Sil</x-filament::button>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="8" style="{{ $td }};color:rgb(107 114 128)">Bu filtreye uygun üye bulunamadı.@if ($arama || $ekipFiltre || $uyelikFiltre || $belgeFiltre || $vardiyaFiltre) <button type="button" wire:click="filtreTemizle" style="color:rgb(13 148 136);font-weight:600">Filtreleri temizle</button>@endif</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </x-filament::section>
        @endif
    @endif

</x-filament-panels::page>
