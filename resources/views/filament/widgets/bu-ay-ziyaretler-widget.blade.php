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

        {{-- SAYAÇLAR (isgsuite "Saha Takvimi") --}}
        @php
            $ao = $this->ayOzeti;
            $eksikler = $this->sureEksikleri;
            $plansizEksik = collect($eksikler)->where('plan_var', false)->count();
        @endphp
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(120px,1fr));gap:.6rem;margin-bottom:1rem">
            @foreach ([
                ['Toplam ziyaret', $ao['toplam'], 'inherit'],
                ['Planlı', $ao['planli'], 'rgb(180 83 9)'],
                ['Tamamlanan', $ao['tamamlanan'], 'rgb(21 128 61)'],
                ['Gecikmiş', $ao['gecikmis'], 'rgb(220 38 38)'],
                ['Eksik süre (firma)', count($eksikler), 'rgb(220 38 38)'],
            ] as [$ad, $sayi, $renk])
                <div style="border:1px solid rgb(107 114 128 / .2);border-radius:.6rem;padding:.55rem .8rem">
                    <div style="font-size:.72rem;color:rgb(107 114 128)">{{ $ad }}</div>
                    <div style="font-size:1.35rem;font-weight:800;color:{{ $sayi > 0 ? $renk : 'inherit' }}">{{ $sayi }}</div>
                </div>
            @endforeach
        </div>

        @if ($plansizEksik > 0 || $ao['gecikmis'] > 0)
            <div style="border-left:3px solid rgb(217 119 6);background:rgb(245 158 11 / .07);border-radius:.4rem;padding:.55rem .8rem;margin-bottom:1rem;font-size:.8rem">
                @if ($plansizEksik > 0)
                    <div>• {{ $plansizEksik }} firmada saha süresi eksik ve bu ay için kalan planlı ziyaret yok.</div>
                @endif
                @if ($ao['gecikmis'] > 0)
                    <div>• {{ $ao['gecikmis'] }} ziyaretin tarihi geçti ama "Tamamlandı" işaretlenmedi.</div>
                @endif
            </div>
        @endif

        {{-- AYLIK ZİYARET GERÇEKLEŞMESİ — gidilmesi gereken firmaların yüzde kaçına gidildi
             (çubuktaki dikey çizgi = ayın geçen kısmı, takvime göre kıyas) --}}
        @php
            $oz = $this->ziyaretOzeti;
            $yuzdeRenk = match (true) {
                $oz['yuzde'] === null => 'rgb(156 163 175)',
                $oz['yuzde'] >= $oz['takvim_yuzde'] => 'rgb(21 128 61)',
                $oz['takvim_yuzde'] - $oz['yuzde'] <= 15 => 'rgb(180 83 9)',
                default => 'rgb(220 38 38)',
            };
        @endphp
        <div style="border:1px solid rgb(107 114 128 / .2);border-radius:.6rem;padding:.8rem 1rem;margin-bottom:1rem">
            @if ($oz['toplam'] === 0)
                <div style="font-size:.82rem;color:rgb(107 114 128)">
                    {{ $ayBaslangic->translatedFormat('F Y') }} için Ziyaret Programı'nda tarihi girilmiş firma ziyareti yok.
                </div>
            @else
                <div style="display:flex;justify-content:space-between;align-items:baseline;flex-wrap:wrap;gap:.4rem;margin-bottom:.4rem">
                    <div style="font-size:.88rem">
                        <strong>{{ $ayBaslangic->translatedFormat('F') }}</strong> ayında gitmeniz gereken
                        <strong>{{ $oz['toplam'] }}</strong> firmanın <strong>{{ $oz['gidilen'] }}</strong>'ine gittiniz
                    </div>
                    <div style="font-size:1.5rem;font-weight:800;color:{{ $yuzdeRenk }}">%{{ $oz['yuzde'] }}</div>
                </div>
                <div style="position:relative;height:.65rem;border-radius:999px;background:rgb(107 114 128 / .15)">
                    <div style="height:100%;border-radius:999px;width:{{ $oz['yuzde'] }}%;background:{{ $yuzdeRenk }}"></div>
                    @if ($oz['takvim_yuzde'] > 0 && $oz['takvim_yuzde'] < 100)
                        <div title="Bugün: ayın %{{ $oz['takvim_yuzde'] }}'i geçti" style="position:absolute;top:-.2rem;bottom:-.2rem;left:{{ $oz['takvim_yuzde'] }}%;width:2px;background:rgb(55 65 81)"></div>
                    @endif
                </div>
                <div style="font-size:.72rem;color:rgb(107 114 128);margin-top:.3rem">
                    @if ($oz['takvim_yuzde'] > 0 && $oz['takvim_yuzde'] < 100)
                        Çizgi: bugün (ayın %{{ $oz['takvim_yuzde'] }}'i geçti)
                    @endif
                </div>

                {{-- Tamamlananlar gösterilmez (kullanıcı 08.10.2026) — yalnız planlı ama henüz gidilmeyenler --}}
                @php $bekleyen = collect($oz['firmalar'])->where('gidildi', false); @endphp
                @if ($bekleyen->isNotEmpty())
                    <div style="font-size:.72rem;color:rgb(107 114 128);margin-top:.5rem">Planlı, henüz gidilmeyen ({{ $bekleyen->count() }}):</div>
                    <div style="display:flex;flex-wrap:wrap;gap:.35rem;margin-top:.25rem">
                        @foreach ($bekleyen as $f)
                            <span style="font-size:.72rem;border-radius:.4rem;padding:.12rem .5rem;border:1px solid rgb(156 163 175)">
                                ○ {{ \Illuminate\Support\Str::limit($f['firma'], 30) }}
                            </span>
                        @endforeach
                    </div>
                @endif
            @endif
        </div>

        {{-- Her aktif firmaya ayda en az bir ziyaret — o ay hiç planı olmayanlar --}}
        @if ($oz['plansiz'])
            <div style="border:1px solid rgb(220 38 38 / .35);background:rgb(220 38 38 / .05);border-radius:.6rem;padding:.7rem 1rem;margin-bottom:1rem">
                <div style="font-size:.85rem;font-weight:700;color:rgb(185 28 28)">
                    ⚠ {{ $ayBaslangic->translatedFormat('F') }} ayında ziyaret planlanmamış {{ count($oz['plansiz']) }} firma
                </div>
                <div style="font-size:.72rem;color:rgb(107 114 128);margin:.15rem 0 .45rem">Her firmaya ayda en az bir ziyaret gerekir. Firmaya tıklayarak Ziyaret Programı'nda tarih girin.</div>
                <div style="display:flex;flex-wrap:wrap;gap:.35rem">
                    @foreach ($oz['plansiz'] as $f)
                        <a href="{{ \App\Filament\Pages\ZiyaretProgrami::getUrl(['firma' => $f['firma_id']]) }}"
                           style="font-size:.72rem;border-radius:.4rem;padding:.15rem .55rem;border:1px solid rgb(220 38 38 / .5);color:rgb(185 28 28);text-decoration:none">
                            + {{ \Illuminate\Support\Str::limit($f['firma'], 34) }}
                        </a>
                    @endforeach
                </div>
            </div>
        @endif

        @php
            // Dakikayı "13 sa 40 dk" biçiminde gösterir.
            $sd = fn (int $dk) => match (true) {
                $dk <= 0 => '0',
                $dk < 60 => $dk.' dk',
                $dk % 60 === 0 => intdiv($dk, 60).' sa',
                default => intdiv($dk, 60).' sa '.($dk % 60).' dk',
            };
            $kartStil = 'border:1px solid rgb(107 114 128 / .2);border-radius:.75rem;padding:.8rem 1rem';
        @endphp

        {{-- Alt grid: solda Ziyaret Takvimi & Planlama, sağda Eksik saha süresi (iki eşit kolon) --}}
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(min(100%,460px),1fr));gap:1rem;align-items:start">

            {{-- SOL: Ziyaret Takvimi & Planlama --}}
            <div style="{{ $kartStil }}">
                <div style="font-weight:700;font-size:.9rem;margin-bottom:.15rem">Ziyaret Takvimi &amp; Planlama</div>
                <div style="font-size:.72rem;color:rgb(107 114 128);margin-bottom:.7rem">Durum rozetine tıklayarak Boş → Planlandı → Tamamlandı arasında geçiş yapın.</div>

                <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:1rem">
                    <div>
                        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:.5rem">
                            <button type="button" wire:click="ayDegistir(-1)" aria-label="Önceki ay" style="border:none;background:none;cursor:pointer;font-size:1rem">‹</button>
                            <div style="font-weight:700;font-size:.85rem">{{ $ayBaslangic->translatedFormat('F Y') }}</div>
                            <button type="button" wire:click="ayDegistir(1)" aria-label="Sonraki ay" style="border:none;background:none;cursor:pointer;font-size:1rem">›</button>
                        </div>
                        <div style="display:flex;gap:.4rem;justify-content:center;margin-bottom:.5rem">
                            <button type="button" wire:click="bugun" style="font-size:.72rem;padding:.15rem .6rem;border-radius:9999px;border:1px solid rgb(107 114 128 / .35);background:transparent;cursor:pointer">Bugün</button>
                            <button type="button" wire:click="tumunuGoster" style="font-size:.72rem;padding:.15rem .6rem;border-radius:9999px;border:1px solid rgb(107 114 128 / .35);background:transparent;cursor:pointer">Tümünü göster</button>
                        </div>
                        <div style="display:grid;grid-template-columns:repeat(7,1fr);gap:2px;font-size:.66rem;text-align:center;color:rgb(107 114 128);margin-bottom:.3rem">
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
                                    style="aspect-ratio:1;border-radius:.35rem;border:none;cursor:pointer;font-size:.72rem;
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

                    {{-- Ziyaret listesi (geciken ziyaretler "Planlandı (Gecikmiş)" rozetiyle) --}}
                    <div>
                        <div style="display:flex;justify-content:space-between;align-items:center;gap:.5rem;margin-bottom:.4rem">
                            <div style="font-weight:600;font-size:.8rem">Ziyaretler ({{ count($ziyaretler) }})</div>
                            @if ($this->tamamlananSayisi > 0)
                                <button type="button" wire:click="tamamlananlariDegistir" style="font-size:.68rem;padding:.1rem .55rem;border-radius:9999px;border:1px solid rgb(107 114 128 / .35);background:transparent;cursor:pointer">
                                    {{ $tamamlananlariGoster ? 'Tamamlananları gizle' : 'Tamamlananları göster ('.$this->tamamlananSayisi.')' }}
                                </button>
                            @endif
                        </div>
                        @if (empty($ziyaretler))
                            <div style="padding:1.2rem .5rem;color:rgb(107 114 128);font-size:.82rem">
                                {{ $seciliTarih ? 'Bu tarihte planlanan ziyaret yok.' : 'Bu ay için planlanan ziyaret bulunmuyor.' }}
                            </div>
                        @else
                            <div style="display:flex;flex-direction:column;gap:.4rem;max-height:300px;overflow-y:auto;padding-right:.15rem">
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
                                                style="flex-shrink:0;padding:.12rem .55rem;border-radius:9999px;cursor:pointer;font-size:.66rem;font-weight:700;
                                                    border:1px solid {{ $renk }};color:{{ $renk }};background:transparent">
                                                {{ $durumEtiket[$z['durum']] ?? $z['durum'] }}{{ $gecikmis ? ' (Gecikmiş)' : '' }}
                                            </button>
                                        </div>
                                        <div style="font-size:.7rem;color:rgb(107 114 128);margin-top:.15rem">
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

            {{-- SAĞ: Eksik saha süresi — plan önerisi (en çok 6 satır görünür, kart içinde kaydırılır) --}}
            @if ($eksikler)
                <div style="{{ $kartStil }}">
                    <div style="font-weight:700;font-size:.9rem;margin-bottom:.15rem">Eksik saha süresi — plan önerisi</div>
                    <div style="font-size:.72rem;color:rgb(107 114 128);margin-bottom:.6rem">
                        Gereken süre = çalışan sayısı × aylık asgari İGU süresi (az tehlikeli 10, tehlikeli 20, çok tehlikeli 40 dk).
                        Ziyaret süreleri Ziyaret Programı'ndaki "saat" alanından alınır. Toplam {{ count($eksikler) }} firma.
                    </div>
                    <div style="max-height:350px;overflow:auto">
                        <table style="width:100%;border-collapse:collapse;font-size:.76rem;min-width:520px">
                            <thead>
                                <tr>
                                    @foreach (['Firma', 'Çalışan', 'Gereken', 'Tamamlanan', 'Planlı', 'Eksik', 'Plan'] as $b)
                                        <th style="position:sticky;top:0;background:var(--fi-body-bg, #fff);text-align:left;padding:.4rem;border-bottom:1px solid rgb(107 114 128 / .25);font-size:.7rem">{{ $b }}</th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($eksikler as $s)
                                    <tr style="border-top:1px solid rgb(107 114 128 / .15)">
                                        <td style="padding:.4rem;font-weight:600;min-width:130px">
                                            <a href="{{ \App\Filament\Pages\ZiyaretProgrami::getUrl(['firma' => $s['firma_id'], 'yil' => (int) substr($gosterilenAy, 0, 4)]) }}" style="color:inherit;text-decoration:underline">{{ \Illuminate\Support\Str::limit($s['firma'], 38) }}</a>
                                        </td>
                                        <td style="padding:.4rem">{{ $s['calisan'] }}</td>
                                        <td style="padding:.4rem;white-space:nowrap">{{ $sd((int) $s['gerekli']) }}</td>
                                        <td style="padding:.4rem;white-space:nowrap">{{ $sd((int) $s['yapilan']) }}</td>
                                        <td style="padding:.4rem;white-space:nowrap">{{ $sd((int) $s['planli']) }}</td>
                                        <td style="padding:.4rem;white-space:nowrap;font-weight:700;color:rgb(220 38 38)">
                                            {{ $sd((int) $s['eksik']) }} <span style="font-weight:400;font-size:.68rem;color:rgb(107 114 128)">({{ $s['eksik'] }} dk)</span>
                                        </td>
                                        <td style="padding:.4rem">
                                            @if ($s['plan_var'])
                                                <span style="white-space:nowrap;font-size:.66rem;font-weight:700;padding:.1rem .5rem;border-radius:9999px;background:rgb(217 119 6 / .12);color:rgb(180 83 9)">Var</span>
                                            @else
                                                <span style="white-space:nowrap;font-size:.66rem;font-weight:700;padding:.1rem .5rem;border-radius:9999px;background:rgb(225 29 72 / .1);color:rgb(190 18 60)">⚠ Yok</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
