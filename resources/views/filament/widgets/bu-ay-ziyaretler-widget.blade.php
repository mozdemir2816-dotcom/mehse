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

        {{-- SAYAÇLAR (02.11.2026 sadeleştirme: "Eksik saha süresi" tablosu ve "gitmeniz gereken N firma"
             düğme yığını kaldırıldı; ayın takviminde planı olan firmaya süre uyarısı verilmez) --}}
        @php
            $ao = $this->ayOzeti;
            $oz = $this->ziyaretOzeti;
            $plansiz = collect($oz['plansiz']);
        @endphp
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(130px,1fr));gap:.6rem;margin-bottom:.8rem">
            @foreach ([
                ['Toplam ziyaret', $ao['toplam'], 'inherit', null],
                ['Planlı', $ao['planli'], 'rgb(180 83 9)', null],
                ['Tamamlanan', $ao['tamamlanan'], 'rgb(21 128 61)', null],
                ['Gecikmiş', $ao['gecikmis'], 'rgb(220 38 38)', null],
                ['Gidilen firma', $oz['toplam'] ? $oz['gidilen'].' / '.$oz['toplam'] : '—', 'rgb(13 148 136)', $oz['yuzde'] !== null ? '%'.$oz['yuzde'] : null],
                ['Plansız firma', $plansiz->count(), 'rgb(220 38 38)', null],
            ] as [$ad, $sayi, $renk, $ek])
                <div style="border:1px solid rgb(107 114 128 / .2);border-radius:.6rem;padding:.5rem .8rem">
                    <div style="font-size:.75rem;color:rgb(107 114 128)">{{ $ad }}</div>
                    <div style="font-size:1.3rem;font-weight:800;color:{{ (is_int($sayi) ? $sayi > 0 : true) ? $renk : 'inherit' }}">
                        {{ $sayi }}@if ($ek)<span style="font-size:.75rem;font-weight:700;margin-left:.35rem">{{ $ek }}</span>@endif
                    </div>
                </div>
            @endforeach
        </div>

        @if ($ao['gecikmis'] > 0)
            <div style="border-left:3px solid rgb(217 119 6);background:rgb(245 158 11 / .07);border-radius:.4rem;padding:.45rem .8rem;margin-bottom:.8rem;font-size:.8rem">
                • {{ $ao['gecikmis'] }} ziyaretin tarihi geçti ama "Tamamlandı" işaretlenmedi.
            </div>
        @endif

        {{-- Yalnız o ayın takviminde HİÇ kaydı olmayan firmalar — tek satır (tamamı açılır) --}}
        @if ($plansiz->isNotEmpty())
            <details style="border:1px solid rgb(220 38 38 / .3);background:rgb(220 38 38 / .04);border-radius:.6rem;padding:.45rem .8rem;margin-bottom:.9rem">
                <summary style="cursor:pointer;font-size:.8rem;display:flex;align-items:center;gap:.5rem;list-style:none;min-width:0">
                    <strong style="color:rgb(185 28 28);white-space:nowrap">⚠ Ziyaret planlanmamış {{ $plansiz->count() }} firma</strong>
                    <span style="flex:1;min-width:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;color:rgb(107 114 128)">{{ $plansiz->pluck('firma')->map(fn ($f) => \Illuminate\Support\Str::limit($f, 28))->implode(' · ') }}</span>
                    <span style="white-space:nowrap;font-size:.75rem;color:rgb(185 28 28);font-weight:600">tümünü göster ▾</span>
                </summary>
                <div style="display:flex;flex-wrap:wrap;gap:.3rem;margin-top:.5rem">
                    @foreach ($plansiz as $f)
                        <a href="{{ \App\Filament\Pages\ZiyaretProgrami::getUrl(['firma' => $f['firma_id']]) }}"
                           style="font-size:.75rem;border-radius:.4rem;padding:.12rem .5rem;border:1px solid rgb(220 38 38 / .45);color:rgb(185 28 28);text-decoration:none">+ {{ \Illuminate\Support\Str::limit($f['firma'], 34) }}</a>
                    @endforeach
                </div>
            </details>
        @endif

        {{-- ZİYARET TAKVİMİ — tam genişlik: solda ay takvimi, sağda seçili günün (ya da ayın) ziyaretleri --}}
        <div style="border:1px solid rgb(107 114 128 / .2);border-radius:.75rem;padding:.9rem 1rem">
            <div style="display:grid;grid-template-columns:minmax(250px,300px) minmax(0,1fr);gap:1.25rem" class="mehse-ziyaret-izgara">
                    <div>
                        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:.5rem">
                            <button type="button" wire:click="ayDegistir(-1)" aria-label="Önceki ay" style="border:none;background:none;cursor:pointer;font-size:1rem">‹</button>
                            <div style="font-weight:700;font-size:.85rem">{{ $ayBaslangic->translatedFormat('F Y') }}</div>
                            <button type="button" wire:click="ayDegistir(1)" aria-label="Sonraki ay" style="border:none;background:none;cursor:pointer;font-size:1rem">›</button>
                        </div>
                        <div style="display:flex;gap:.4rem;justify-content:center;margin-bottom:.5rem">
                            <button type="button" wire:click="bugun" style="font-size:.75rem;padding:.15rem .6rem;border-radius:9999px;border:1px solid rgb(107 114 128 / .35);background:transparent;cursor:pointer">Bugün</button>
                            <button type="button" wire:click="tumunuGoster" style="font-size:.75rem;padding:.15rem .6rem;border-radius:9999px;border:1px solid rgb(107 114 128 / .35);background:transparent;cursor:pointer">Tümünü göster</button>
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
                                    $doluMu = isset($this->takvimGunleri[$tarih]);
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
                                style="margin-top:.6rem;font-size:.75rem;color:{{ $mor }};background:none;border:none;cursor:pointer;padding:0">
                                × Gün filtresini kaldır, tüm ayı göster
                            </button>
                        @endif
                    </div>


                    {{-- Ziyaret listesi (geciken ziyaretler "Planlandı (Gecikmiş)" rozetiyle) --}}
                    <div style="min-width:0">
                        <div style="display:flex;justify-content:space-between;align-items:center;gap:.5rem;margin-bottom:.5rem;flex-wrap:wrap">
                            <div style="font-weight:700;font-size:.88rem">
                                {{ $seciliTarih ? \Illuminate\Support\Carbon::parse($seciliTarih)->locale('tr')->translatedFormat('j F l') : $ayBaslangic->translatedFormat('F').' — tüm ziyaretler' }}
                                <span style="font-weight:600;color:rgb(107 114 128)">({{ count($ziyaretler) }})</span>
                            </div>
                            @if ($this->tamamlananSayisi > 0)
                                <button type="button" wire:click="tamamlananlariDegistir" style="font-size:.75rem;padding:.1rem .55rem;border-radius:9999px;border:1px solid rgb(107 114 128 / .35);background:transparent;cursor:pointer">
                                    {{ $tamamlananlariGoster ? 'Tamamlananları gizle' : 'Tamamlananları göster ('.$this->tamamlananSayisi.')' }}
                                </button>
                            @endif
                        </div>
                        @if (empty($ziyaretler))
                            <div style="padding:2rem .5rem;text-align:center;color:rgb(107 114 128);font-size:.84rem;border:1px dashed rgb(107 114 128 / .3);border-radius:.6rem">
                                {{ $seciliTarih ? 'Bu tarihte planlanan ziyaret yok.' : 'Bu ay için planlanan ziyaret bulunmuyor.' }}
                            </div>
                        @else
                            <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(250px,1fr));gap:.5rem;max-height:360px;overflow-y:auto;padding-right:.15rem;align-content:start">
                                @foreach ($ziyaretler as $z)
                                    @php
                                        $gecikmis = \App\Filament\Widgets\BuAyZiyaretlerWidget::gecikmisMi($z);
                                        $renk = $gecikmis && $z['durum'] !== 'tamamlandi' ? 'rgb(220 38 38)' : $durumRenk[$z['durum']];
                                        $yil = (int) substr($z['tarih'], 0, 4);
                                        $say = $z['firma'] ? ($this->yapilacakSayilari[$z['firma']->id.'-'.$yil.'-'.$z['ay_index']] ?? null) : null;
                                    @endphp
                                    <div style="border:1px solid rgb(107 114 128 / .2);border-radius:.6rem;padding:.45rem .6rem">
                                        <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:.4rem">
                                            <div style="font-weight:600;font-size:.76rem;line-height:1.3">{{ $z['firma']?->unvan }}</div>
                                            <button type="button"
                                                wire:click="durumDegistir({{ $z['program_id'] }}, {{ $z['ay_index'] }}, {{ $z['satir_index'] }})"
                                                style="flex-shrink:0;padding:.12rem .55rem;border-radius:9999px;cursor:pointer;font-size:.68rem;font-weight:700;
                                                    border:1px solid {{ $renk }};color:{{ $renk }};background:transparent">
                                                {{ $durumEtiket[$z['durum']] ?? $z['durum'] }}{{ $gecikmis ? ' (Gecikmiş)' : '' }}
                                            </button>
                                        </div>
                                        <div style="font-size:.75rem;color:rgb(107 114 128);margin-top:.15rem">
                                            {{ \Illuminate\Support\Carbon::parse($z['tarih'])->format('d.m.Y') }}
                                            · {{ $z['amac'] ?: '—' }}@if ($z['sure_saat']) · {{ $z['sure_saat'] }} saat @endif
                                            @if ($say && $say['toplam'] > 0)
                                                ·
                                                <a href="{{ \App\Filament\Pages\ZiyaretProgrami::getUrl(['firma' => $z['firma']->id, 'yil' => $yil, 'ay' => $z['ay_index'] + 1]) }}"
                                                    title="Yıllık plandan bu ay yapılacaklar"
                                                    style="font-weight:700;color:{{ $say['yapilan'] === $say['toplam'] ? 'rgb(21 128 61)' : 'var(--primary)' }};text-decoration:none">
                                                    📋 {{ $say['yapilan'] }}/{{ $say['toplam'] }} iş
                                                </a>
                                            @endif
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>
            </div>

    </x-filament::section>
</x-filament-widgets::widget>
