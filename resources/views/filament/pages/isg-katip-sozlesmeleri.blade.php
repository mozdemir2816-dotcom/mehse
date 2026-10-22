@php
    $mor = 'rgb(139 92 246)';
    $yesil = 'rgb(34 197 94)';
    $kirmizi = 'rgb(239 68 68)';
    $sari = 'rgb(234 179 8)';
    $kutu = 'border:1px solid rgb(107 114 128 / .3);border-radius:.75rem;padding:1rem';
@endphp

<x-filament-panels::page>
    @if ($this->tumu->isEmpty())
        <div style="{{ $kutu }};text-align:center;padding:2.5rem 1rem">
            <x-filament::icon icon="heroicon-o-document-arrow-up" style="width:2rem;height:2rem;color:{{ $mor }};margin:0 auto .5rem"/>
            <div style="font-weight:700">Henüz sözleşme yüklenmedi</div>
            <p style="font-size:.85rem;color:rgb(107 114 128);max-width:28rem;margin:.35rem auto 0">
                İSG-KATİP &gt; Süreç Yönetimi &gt; İSG Hizmet Sözleşmeleri ekranında <strong>"Tümünü Dışa aktar"</strong>
                butonuyla indirdiğiniz Excel'i yukarıdaki <strong>"Excel Yükle"</strong> ile buraya yükleyin.
            </p>
        </div>
    @else
        <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:.5rem">
            <div style="font-size:.8rem;color:rgb(107 114 128)">
                {{ $this->tumu->count() }} sözleşme · son yükleme {{ $this->sonIceAktarim ?? '—' }}
            </div>
        </div>

        {{-- KİŞİ BAZLI ÖZET --}}
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(260px,1fr));gap:.75rem;margin-top:.75rem">
            @foreach ($this->kisiler as $kisi)
                @php
                    $secili = $this->seciliKisi === $kisi['anahtar'];
                    $uyariVar = $kisi['suresi_gecmis'] > 0 || $kisi['suresi_yaklasan'] > 0 || $kisi['onay_bekleyen'] > 0;
                @endphp
                <button
                    wire:click="kisiSec('{{ $secili ? '' : $kisi['anahtar'] }}')"
                    style="{{ $kutu }};text-align:left;cursor:pointer;background:{{ $secili ? 'rgb(139 92 246 / .08)' : 'transparent' }};border-color:{{ $secili ? $mor : 'rgb(107 114 128 / .3)' }}"
                >
                    <div style="display:flex;align-items:start;justify-content:space-between;gap:.5rem">
                        <div>
                            <div style="font-weight:700;font-size:.92rem">{{ $kisi['ad'] }}</div>
                            <div style="font-size:.76rem;color:rgb(107 114 128)">{{ $kisi['sertifika'] ?? '—' }}</div>
                        </div>
                        @if ($kisi['suresi_gecmis'] > 0)
                            <x-filament::badge color="danger" size="sm">{{ $kisi['suresi_gecmis'] }} süresi geçti</x-filament::badge>
                        @elseif ($kisi['suresi_yaklasan'] > 0)
                            <x-filament::badge color="warning" size="sm">{{ $kisi['suresi_yaklasan'] }} yaklaşıyor</x-filament::badge>
                        @endif
                    </div>

                    <div style="display:flex;gap:1rem;margin-top:.6rem;font-size:.8rem">
                        <div><span style="font-weight:700">{{ $kisi['aktif'] }}</span> <span style="color:rgb(107 114 128)">aktif sözleşme</span></div>
                        <div><span style="font-weight:700">{{ $kisi['firmalar']->count() }}</span> <span style="color:rgb(107 114 128)">firma</span></div>
                        @if ($kisi['onay_bekleyen'] > 0)
                            <div style="color:{{ $sari }}"><span style="font-weight:700">{{ $kisi['onay_bekleyen'] }}</span> onay bekliyor</div>
                        @endif
                    </div>

                    @if ($kisi['sureler']->isNotEmpty())
                        <div style="display:flex;gap:.35rem;flex-wrap:wrap;margin-top:.5rem">
                            @foreach ($kisi['sureler'] as $sure => $adet)
                                <x-filament::badge color="gray" size="sm">{{ $sure }}: {{ $adet }}</x-filament::badge>
                            @endforeach
                        </div>
                    @endif

                    @if ($kisi['en_yakin_bitis'])
                        <div style="font-size:.76rem;color:{{ $kisi['suresi_gecmis'] > 0 ? $kirmizi : ($kisi['suresi_yaklasan'] > 0 ? $sari : 'rgb(107 114 128)') }};margin-top:.4rem">
                            En yakın bitiş: {{ $kisi['en_yakin_bitis']->format('d.m.Y') }}
                        </div>
                    @endif
                </button>
            @endforeach
        </div>

        {{-- SEÇİLİ KİŞİNİN SÖZLEŞMELERİ --}}
        @if ($this->seciliKisi && $this->secilenKisiOzet)
            <div style="{{ $kutu }};margin-top:1rem">
                <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:.5rem">
                    <div style="font-weight:700">{{ $this->secilenKisiOzet['ad'] }} — sözleşmeler</div>
                    <x-filament::button size="xs" color="gray" wire:click="kisiSec(null)">Kapat</x-filament::button>
                </div>

                <div style="overflow-x:auto;margin-top:.75rem">
                    <table style="width:100%;border-collapse:collapse;font-size:.82rem">
                        <thead>
                            <tr style="text-align:left;border-bottom:1px solid rgb(107 114 128 / .3)">
                                <th style="padding:.4rem .5rem">Firma</th>
                                <th style="padding:.4rem .5rem">Tehlike Sınıfı</th>
                                <th style="padding:.4rem .5rem">Süre</th>
                                <th style="padding:.4rem .5rem">Başlangıç</th>
                                <th style="padding:.4rem .5rem">Bitiş</th>
                                <th style="padding:.4rem .5rem">Statü</th>
                                <th style="padding:.4rem .5rem">Onay</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($this->seciliKisiSozlesmeleri as $s)
                                <tr style="border-bottom:1px solid rgb(107 114 128 / .15);opacity:{{ $s->aktifMi() ? 1 : .55 }}">
                                    <td style="padding:.4rem .5rem">
                                        {{ $s->hizmet_alan_unvan }}
                                        @if ($s->firma) <x-filament::badge color="gray" size="sm">kayıtlı firma</x-filament::badge> @endif
                                    </td>
                                    <td style="padding:.4rem .5rem">{{ $s->hizmet_alan_tehlike_sinifi ?? '—' }}</td>
                                    <td style="padding:.4rem .5rem">{{ $s->calisma_suresi ?? '—' }}</td>
                                    <td style="padding:.4rem .5rem">{{ $s->baslangic_tarihi?->format('d.m.Y') ?? '—' }}</td>
                                    <td style="padding:.4rem .5rem;color:{{ $s->suresiGecmisMi() ? $kirmizi : ($s->suresiYaklasiyorMu($this->yaklasanGun) ? $sari : 'inherit') }}">
                                        {{ $s->bitis_tarihi?->format('d.m.Y') ?? '—' }}
                                    </td>
                                    <td style="padding:.4rem .5rem">{{ $s->statu ?? '—' }}</td>
                                    <td style="padding:.4rem .5rem">
                                        @if ($s->onayBekliyorMu())
                                            <x-filament::badge color="warning" size="sm">{{ $s->onay_durumu }}</x-filament::badge>
                                        @else
                                            {{ $s->onay_durumu ?? '—' }}
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif
    @endif
</x-filament-panels::page>
