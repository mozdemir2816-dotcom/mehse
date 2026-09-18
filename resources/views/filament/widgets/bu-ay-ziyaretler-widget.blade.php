<x-filament-widgets::widget>
    @php
        $mor = 'rgb(124 58 237)';
        $durumRenk = ['bos' => 'rgb(156 163 175)', 'planlandi' => 'rgb(180 83 9)', 'tamamlandi' => 'rgb(21 128 61)'];
        $durumEtiket = ['bos' => 'Boş', 'planlandi' => 'Planlandı', 'tamamlandi' => 'Tamamlandı'];

        $ayBaslangic = \Illuminate\Support\Carbon::parse($gosterilenAy.'-01');
        $gunSayisi = $ayBaslangic->daysInMonth;
        $baslangicBosluk = $ayBaslangic->dayOfWeekIso - 1;
        $gunler = $this->gunlukGruplar;
        $ziyaretler = $this->ayinZiyaretleri;
    @endphp

    <x-filament::section icon="heroicon-o-calendar-days" icon-color="primary">
        <x-slot name="heading">{{ $ayBaslangic->translatedFormat('F Y') }} — Ziyaret Takvimi</x-slot>
        <x-slot name="description">
            Bu ay gitmeyi planladığınız firmalar tek listede — durum rozetine tıklayarak
            Boş→Planlandı→Tamamlandı arasında geçiş yapın.
        </x-slot>

        <div style="display:grid;grid-template-columns:16rem 1fr;gap:1.25rem">
            <div>
                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:.6rem">
                    <button type="button" wire:click="ayDegistir(-1)" style="border:none;background:none;cursor:pointer;font-size:1rem">‹</button>
                    <div style="font-weight:700;font-size:.85rem">{{ $ayBaslangic->translatedFormat('F Y') }}</div>
                    <button type="button" wire:click="ayDegistir(1)" style="border:none;background:none;cursor:pointer;font-size:1rem">›</button>
                </div>
                <div style="display:grid;grid-template-columns:repeat(7,1fr);gap:2px;font-size:.68rem;text-align:center;color:rgb(107 114 128);margin-bottom:.3rem">
                    @foreach (['Pzt', 'Sal', 'Çar', 'Per', 'Cum', 'Cmt', 'Paz'] as $g)
                        <div>{{ $g }}</div>
                    @endforeach
                </div>
                <div style="display:grid;grid-template-columns:repeat(7,1fr);gap:2px">
                    @for ($bosluk = 0; $bosluk < $baslangicBosluk; $bosluk++)
                        <div></div>
                    @endfor
                    @for ($gun = 1; $gun <= $gunSayisi; $gun++)
                        @php
                            $tarih = $ayBaslangic->copy()->day($gun)->toDateString();
                            $doluMu = ! empty($gunler[$tarih]);
                            $seciliMi = $tarih === $seciliTarih;
                        @endphp
                        <button type="button" wire:click="gunSec('{{ $tarih }}')"
                            style="aspect-ratio:1;border-radius:.35rem;border:none;cursor:pointer;font-size:.75rem;
                                background:{{ $seciliMi ? $mor : ($doluMu ? 'rgb(139 92 246 / .15)' : 'transparent') }};
                                color:{{ $seciliMi ? '#fff' : 'inherit' }}">
                            {{ $gun }}
                        </button>
                    @endfor
                </div>
                @if ($seciliTarih)
                    <button type="button" wire:click="gunSec('{{ $seciliTarih }}')"
                        style="margin-top:.6rem;font-size:.72rem;color:{{ $mor }};background:none;border:none;cursor:pointer;padding:0">
                        × Gün filtresini kaldır, tüm ayı göster
                    </button>
                @endif
            </div>

            <div style="overflow-x:auto">
                @if (empty($ziyaretler))
                    <div style="text-align:center;padding:2rem;color:rgb(107 114 128);font-size:.85rem">
                        {{ $seciliTarih ? 'Bu tarihte planlanan ziyaret yok.' : 'Bu ay için planlanan ziyaret bulunmuyor.' }}
                    </div>
                @else
                    <table style="width:100%;border-collapse:collapse;font-size:.8rem;min-width:520px">
                        <tr>
                            <th style="text-align:left;padding:.4rem;width:90px">Tarih</th>
                            <th style="text-align:left;padding:.4rem">Firma</th>
                            <th style="text-align:left;padding:.4rem">Amaç / Kapsam</th>
                            <th style="text-align:left;padding:.4rem;width:110px">Durum</th>
                        </tr>
                        @foreach ($ziyaretler as $z)
                            <tr style="border-top:1px solid rgb(107 114 128 / .15)">
                                <td style="padding:.4rem;white-space:nowrap">{{ \Illuminate\Support\Carbon::parse($z['tarih'])->format('d.m.Y') }}</td>
                                <td style="padding:.4rem;font-weight:600">{{ $z['firma']?->unvan }}</td>
                                <td style="padding:.4rem;color:rgb(107 114 128)">
                                    {{ $z['amac'] ?: '—' }}
                                    @if ($z['sure_saat']) · {{ $z['sure_saat'] }} saat @endif
                                </td>
                                <td style="padding:.4rem">
                                    <button type="button"
                                        wire:click="durumDegistir({{ $z['program_id'] }}, {{ $z['ay_index'] }}, {{ $z['satir_index'] }})"
                                        style="width:100%;padding:.3rem;border-radius:.4rem;cursor:pointer;font-size:.72rem;font-weight:700;
                                            border:1px solid {{ $durumRenk[$z['durum']] }};color:{{ $durumRenk[$z['durum']] }};background:transparent">
                                        {{ $durumEtiket[$z['durum']] ?? $z['durum'] }}
                                    </button>
                                </td>
                            </tr>
                        @endforeach
                    </table>
                @endif
            </div>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
