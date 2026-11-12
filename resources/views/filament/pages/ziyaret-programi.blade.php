@php
    $mor = 'rgb(124 58 237)';
    $girdi = 'width:100%;padding:.4rem .5rem;border-radius:.4rem;border:1px solid rgb(107 114 128 / .3);background:transparent;font-size:.78rem';
    $durumRenk = ['bos' => 'rgb(156 163 175)', 'planlandi' => 'rgb(180 83 9)', 'tamamlandi' => 'rgb(21 128 61)'];
    $durumEtiket = ['bos' => 'Boş', 'planlandi' => 'Planlandı', 'tamamlandi' => 'Tamamlandı'];
@endphp

<x-filament-panels::page>
    {{-- Görünüm seçici: Genel Ajanda (portföy takvimi) / Firma Bazlı Yıllık Plan (12 ay) --}}
    <div style="display:flex;gap:.5rem;flex-wrap:wrap" role="tablist" aria-label="Planlama görünümü">
        @foreach (['ajanda' => 'Genel Ajanda (Portföy Takvimi)', 'plan' => 'Firma Bazlı Yıllık Plan (12 Ay)'] as $kod => $ad)
            <button type="button" role="tab" aria-selected="{{ $gorunum === $kod ? 'true' : 'false' }}" wire:click="gorunumSec('{{ $kod }}')"
                style="min-height:2.75rem;padding:0 1.1rem;border-radius:9999px;cursor:pointer;font-size:.88rem;font-weight:700;
                    border:1px solid {{ $gorunum === $kod ? '#0284C7' : 'rgb(107 114 128 / .35)' }};
                    background:{{ $gorunum === $kod ? 'rgb(2 132 199 / .1)' : 'transparent' }};
                    color:{{ $gorunum === $kod ? '#0369A1' : 'inherit' }}">{{ $ad }}</button>
        @endforeach
    </div>

    @if ($gorunum === 'ajanda')
        @include('filament.pages.partials.planlama-ajanda')
    @else
    <p class="mehse-bolum-aciklama">
        Firma başına yıllık, 12 aylık saha ziyaret programı. Durum hücresine tıklayarak
        Boş→Planlandı→Tamamlandı arasında geçiş yapın; isterseniz AI'dan o ay için kısa
        bir amaç/kapsam önerisi alın. Bir ayda birden fazla ziyaret varsa "+" ile ek
        satır ekleyebilirsiniz. Her ayın "Yapılacaklar" listesi Yıllık Çalışma/Eğitim
        Planı'nda o ay planlanan maddelerden otomatik gelir.
    </p>

    <div class="mehse-izgara">
        <div>
            <label class="mehse-etiket">Firma Seçin <span class="mehse-hata">*</span></label>
            <select wire:model.live="firmaId" style="{{ $girdi }};margin-top:.3rem;padding:.55rem .75rem">
                <option value="">— Firma seçin —</option>
                @foreach ($this->firmalar as $id => $ad)
                    <option value="{{ $id }}">{{ $ad }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="mehse-etiket">Yıl</label>
            <input type="number" wire:model.live="yil" style="{{ $girdi }};margin-top:.3rem;padding:.55rem .75rem">
        </div>
    </div>

    @if ($this->firma)
        @php
            $yDk = $this->yasalDakika;
            $sk = \App\Support\YasalZiyaretSuresi::siklik($this->firma->tehlike_sinifi);
            $kisaYasal = \App\Support\YasalZiyaretSuresi::etiket($yDk);
            $yDkKatip = (int) $this->firma->katip_aylik_dk > 0;
        @endphp
        {{-- YASAL ASGARİ SÜRE (otomatik): çalışan × tehlike sınıfı dakikası ya da İSG-KATİP süresi --}}
        <div style="border:1px solid rgb(2 132 199 / .35);background:rgb(2 132 199 / .06);border-radius:1rem;padding:.8rem 1rem;display:flex;flex-wrap:wrap;gap:.8rem 1.5rem;align-items:center;justify-content:space-between">
            <div style="display:flex;flex-wrap:wrap;gap:.5rem 1.5rem;align-items:center">
                <div>
                    <div style="font-size:.75rem;color:rgb(107 114 128);text-transform:uppercase;letter-spacing:.04em">Aylık yasal asgari süre</div>
                    <div style="font-size:1.5rem;font-weight:800;color:#0369A1;line-height:1.1">{{ $kisaYasal }}</div>
                    <div class="mehse-soluk">
                        @if ($yDkKatip)
                            İSG-KATİP sözleşme süresi · {{ $yDk }} dk
                        @else
                            {{ (int) $this->firma->calisan_sayisi }} çalışan × {{ (int) config('isg.igu_aylik_dk.'.$this->firma->tehlike_sinifi) }} dk ({{ $this->firma->tehlikeSinifiEtiketi() }}) = {{ $yDk }} dk
                        @endif
                    </div>
                </div>
                <div>
                    <div style="font-size:.75rem;color:rgb(107 114 128);text-transform:uppercase;letter-spacing:.04em">Önerilen ziyaret sıklığı</div>
                    <div style="font-size:1.1rem;font-weight:800">{{ $sk['etiket'] }}</div>
                    <div class="mehse-soluk">{{ $this->firma->tehlikeSinifiEtiketi() }} sınıf</div>
                </div>
            </div>
            @if ($yDk > 0)
                <x-filament::button color="gray" icon="heroicon-o-clock" wire:click="yasalSureyiYaz" style="min-height:2.75rem">Boş Aylara Yasal Süreyi Yaz</x-filament::button>
            @endif
        </div>

        <x-filament::section icon="heroicon-o-calendar" icon-color="primary">
            <x-slot name="heading">{{ $yil }} Yılı Ziyaret Programı</x-slot>

            <div style="overflow-x:auto">
                <table style="width:100%;border-collapse:collapse;font-size:.8rem;min-width:900px">
                    <tr>
                        <th style="text-align:left;padding:.4rem;width:70px">Ay</th>
                        <th style="text-align:left;padding:.4rem;width:130px">Tarih</th>
                        <th style="text-align:left;padding:.4rem">Amaç / Kapsam</th>
                        <th style="text-align:left;padding:.4rem;width:110px" title="Firma için aylık yasal asgari süre">Yasal süre</th>
                        <th style="text-align:left;padding:.4rem;width:90px">Süre (sa.)</th>
                        <th style="text-align:left;padding:.4rem;width:110px">Durum</th>
                        <th style="text-align:left;padding:.4rem;width:160px">Notlar</th>
                        <th style="text-align:left;padding:.4rem;width:180px" title="Yıllık plandan o ay yapılacaklar (gerçekleşen/toplam) ve ayın öne çıkan ★ görevleri">Yapılacaklar</th>
                        <th style="text-align:left;padding:.4rem;width:60px"></th>
                    </tr>
                    @foreach (\App\Models\ZiyaretProgrami::AYLAR as $i => $ayAdi)
                        @php $girdiler = \App\Models\ZiyaretProgrami::ayGirdileri($this->program->ziyaretler[$i] ?? null); @endphp
                        @foreach ($girdiler as $s => $z)
                            <tr style="border-top:1px solid rgb(107 114 128 / .15)">
                                <td style="padding:.4rem;font-weight:600">
                                    {{ $s === 0 ? $ayAdi : '' }}
                                </td>
                                <td style="padding:.4rem">
                                    <input type="date" value="{{ $z['tarih'] }}"
                                        wire:change="ayGuncelle({{ $i }}, {{ $s }}, 'tarih', $event.target.value)" style="{{ $girdi }}">
                                </td>
                                <td style="padding:.4rem">
                                    <div style="display:flex;gap:.3rem">
                                        <input type="text" list="amac-katalogu" value="{{ $z['amac'] }}"
                                            wire:change="ayGuncelle({{ $i }}, {{ $s }}, 'amac', $event.target.value)" style="{{ $girdi }}">
                                        <x-filament::icon-button icon="heroicon-o-sparkles" color="primary" size="sm"
                                            wire:click="aiAmacOner({{ $i }}, {{ $s }})" tooltip="AI'dan öneri al"/>
                                    </div>
                                </td>
                                <td style="padding:.4rem;white-space:nowrap">
                                    @if ($s === 0 && $yDk > 0)
                                        @php $planDk = \App\Support\YasalZiyaretSuresi::dakikayaCevir($z['sure_saat']); $yetersiz = filled($z['sure_saat']) && $planDk < $yDk - 1; @endphp
                                        <span style="display:inline-block;font-size:.75rem;font-weight:700;padding:.15rem .55rem;border-radius:9999px;
                                            {{ $yetersiz ? 'background:rgb(217 119 6 / .15);color:#b45309' : 'background:rgb(2 132 199 / .1);color:#0369A1' }}"
                                            title="{{ $yetersiz ? 'Planlanan süre yasal asgari sürenin altında' : 'Yasal asgari süre' }}">{{ $kisaYasal }}{{ $yetersiz ? ' ⚠' : '' }}</span>
                                    @endif
                                </td>
                                <td style="padding:.4rem">
                                    <input type="number" step="0.5" min="0" value="{{ $z['sure_saat'] }}"
                                        wire:change="ayGuncelle({{ $i }}, {{ $s }}, 'sure_saat', $event.target.value)" style="{{ $girdi }}">
                                </td>
                                <td style="padding:.4rem">
                                    <button type="button" wire:click="durumDegistir({{ $i }}, {{ $s }})"
                                        style="width:100%;padding:.4rem;border-radius:.4rem;cursor:pointer;font-size:.75rem;font-weight:700;
                                            border:1px solid {{ $durumRenk[$z['durum']] }};color:{{ $durumRenk[$z['durum']] }};background:transparent">
                                        {{ $durumEtiket[$z['durum']] }}
                                    </button>
                                </td>
                                <td style="padding:.4rem">
                                    <input type="text" value="{{ $z['notlar'] }}"
                                        wire:change="ayGuncelle({{ $i }}, {{ $s }}, 'notlar', $event.target.value)" style="{{ $girdi }}">
                                </td>
                                <td style="padding:.4rem">
                                    @if ($s === 0)
                                        @php $say = $this->aylikYapilacakSayilari[$i] ?? ['toplam' => 0, 'yapilan' => 0]; @endphp
                                        <button type="button" wire:click="yapilacakAySec({{ $i }})" title="{{ $ayAdi }} ziyaretinde yapılacakları göster"
                                            style="padding:.3rem .5rem;border-radius:.4rem;cursor:pointer;font-size:.75rem;font-weight:700;background:transparent;
                                                border:1px solid {{ $i === $yapilacakAy ? $mor : 'rgb(107 114 128 / .3)' }};color:{{ $say['toplam'] && $say['yapilan'] === $say['toplam'] ? 'rgb(21 128 61)' : 'inherit' }}">
                                            📋 {{ $say['yapilan'] }}/{{ $say['toplam'] }}
                                        </button>
                                        {{-- Ayın öne çıkan (★) en fazla 2 görevi: kompakt etiket --}}
                                        @foreach ($this->ayKritikGorevleri[$i] ?? [] as $k)
                                            <button type="button" wire:click="yapilacakAySec({{ $i }})" title="{{ $k['baslik'] }}"
                                                style="display:block;max-width:11rem;margin-top:.25rem;padding:.12rem .5rem;border-radius:9999px;cursor:pointer;font-size:.68rem;font-weight:700;text-align:left;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;
                                                    border:1px solid {{ $k['gerceklesti'] ? 'rgb(21 128 61 / .5)' : 'rgb(217 119 6 / .55)' }};
                                                    background:{{ $k['gerceklesti'] ? 'rgb(21 128 61 / .1)' : 'rgb(217 119 6 / .1)' }};color:{{ $k['gerceklesti'] ? '#15803d' : '#b45309' }}">
                                                {{ $k['gerceklesti'] ? '✓' : '★' }} {{ \Illuminate\Support\Str::limit($k['baslik'], 28) }}
                                            </button>
                                        @endforeach
                                    @endif
                                </td>
                                <td style="padding:.4rem;white-space:nowrap">
                                    @if ($s === count($girdiler) - 1)
                                        <x-filament::icon-button icon="heroicon-o-plus" color="gray" size="sm"
                                            wire:click="ziyaretEkle({{ $i }})" tooltip="Bu aya ek ziyaret ekle"/>
                                    @endif
                                    @if (count($girdiler) > 1)
                                        <x-filament::icon-button icon="heroicon-o-x-mark" color="danger" size="sm"
                                            wire:click="ziyaretSil({{ $i }}, {{ $s }})" tooltip="Bu satırı sil"/>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    @endforeach
                </table>
                <datalist id="amac-katalogu">
                    @foreach ($this->amacKategorileri as $k)
                        <option value="{{ $k }}"></option>
                    @endforeach
                </datalist>
            </div>
            <div style="margin-top:.7rem"><x-filament::button icon="heroicon-o-check" wire:click="kaydet">Kaydet</x-filament::button></div>
        </x-filament::section>

        {{-- YAPILACAKLAR PENCERESİ: ayın tüm yapılacakları (sayfayı uzatmasın diye modal); ★ ile en fazla 2 öne çıkan görev --}}
        @if ($yapilacakModalAcik)
            @php
                $yapilacakAyAdi = \App\Models\ZiyaretProgrami::AYLAR[$yapilacakAy];
                $yapilacakGruplari = collect($this->yapilacaklar)->groupBy('grup');
                $ayinSayisi = $this->aylikYapilacakSayilari[$yapilacakAy] ?? ['toplam' => 0, 'yapilan' => 0];
                $secilenKritik = collect($this->ayKritikGorevleri[$yapilacakAy] ?? [])->map(fn ($k) => $k['alan'].':'.$k['index'])->all();
            @endphp
            <div style="position:fixed;inset:0;z-index:60;background:rgb(15 23 42 / .55);display:flex;align-items:center;justify-content:center;padding:1rem"
                 wire:click.self="yapilacakModalKapat" x-data x-on:keydown.escape.window="$wire.yapilacakModalKapat()" role="dialog" aria-modal="true" aria-label="{{ $yapilacakAyAdi }} ziyaretinde yapılacaklar">
                <div style="background:#fff;color:#111827;border-radius:1rem;box-shadow:0 20px 50px rgb(0 0 0 / .35);width:min(100%,46rem);max-height:88vh;display:flex;flex-direction:column;overflow:hidden">
                    <div style="display:flex;justify-content:space-between;align-items:center;gap:.5rem;padding:.9rem 1.1rem;border-bottom:1px solid #e5e7eb">
                        <div style="font-weight:800;font-size:1rem">
                            {{ $yapilacakAyAdi }} Ziyaretinde Yapılacaklar
                            <span style="font-weight:400;font-size:.78rem;color:#6b7280">({{ $ayinSayisi['yapilan'] }}/{{ $ayinSayisi['toplam'] }} gerçekleşti)</span>
                        </div>
                        <button type="button" wire:click="yapilacakModalKapat" aria-label="Kapat" style="min-width:2.5rem;min-height:2.5rem;border:none;background:none;font-size:1.2rem;cursor:pointer;color:#374151">✕</button>
                    </div>

                    <div style="overflow-y:auto;padding:.9rem 1.1rem">
                        <p style="font-size:.76rem;color:#6b7280;margin:0 0 .7rem">
                            Liste {{ $yil }} Yıllık Çalışma ve Eğitim Planı'nda bu ay <strong>P</strong> işaretli maddelerden gelir; kutucuk plandaki <strong>G</strong> hücresini işaretler.
                            <strong>★</strong> ile ayın en fazla {{ \App\Filament\Pages\ZiyaretProgrami::KRITIK_GOREV_SINIRI }} öne çıkan görevini seçin; satırda etiket olarak görünür.
                        </p>

                        <div style="display:flex;flex-wrap:wrap;gap:.3rem;margin-bottom:1rem">
                            @foreach (\App\Models\ZiyaretProgrami::AYLAR as $ai => $ayAdi)
                                @php
                                    $say = $this->aylikYapilacakSayilari[$ai] ?? ['toplam' => 0, 'yapilan' => 0];
                                    $aktif = $ai === $yapilacakAy;
                                @endphp
                                <button type="button" wire:click="yapilacakAySec({{ $ai }})"
                                    style="min-height:2.25rem;padding:.3rem .6rem;border-radius:9999px;cursor:pointer;font-size:.75rem;font-weight:600;
                                        border:1px solid {{ $aktif ? '#0284C7' : '#d1d5db' }};background:{{ $aktif ? 'rgb(2 132 199 / .1)' : 'transparent' }};color:{{ $aktif ? '#0369A1' : 'inherit' }}">
                                    {{ mb_substr($ayAdi, 0, 3) }}
                                    <span style="font-weight:400;color:#6b7280">{{ $say['yapilan'] }}/{{ $say['toplam'] }}</span>
                                </button>
                            @endforeach
                        </div>

                        @forelse ($yapilacakGruplari as $grup => $maddeler)
                            <div style="margin-bottom:.8rem">
                                <div style="font-size:.68rem;font-weight:700;letter-spacing:.04em;color:#0369A1;margin-bottom:.25rem">{{ $grup }}</div>
                                @foreach ($maddeler as $m)
                                    @php $yildizli = in_array($m['alan'].':'.$m['index'], $secilenKritik, true); @endphp
                                    <div style="display:flex;gap:.6rem;align-items:flex-start;padding:.4rem .2rem;border-top:1px solid #eef0f3">
                                        <input type="checkbox" @checked($m['gerceklesti']) wire:click="yapilacakGerceklesti('{{ $m['alan'] }}', {{ $m['index'] }})" aria-label="Gerçekleşti"
                                            style="margin-top:.2rem;width:1.3rem;height:1.3rem;accent-color:#15803d;cursor:pointer;flex-shrink:0">
                                        <span style="flex:1;font-size:.82rem;{{ $m['gerceklesti'] ? 'text-decoration:line-through;color:#6b7280' : '' }}">
                                            {{ $m['baslik'] }}
                                            @if (filled($m['sorumlu']))<span style="display:block;font-size:.75rem;color:#6b7280;text-decoration:none">{{ $m['sorumlu'] }}</span>@endif
                                        </span>
                                        <button type="button" wire:click="kritikGorevToggle('{{ $m['alan'] }}', {{ $m['index'] }})" title="{{ $yildizli ? 'Öne çıkandan kaldır' : 'Ayın öne çıkan görevi yap' }}" aria-label="Öne çıkan görev"
                                            style="flex-shrink:0;min-width:2.5rem;min-height:2.5rem;border-radius:.5rem;border:1px solid {{ $yildizli ? '#b45309' : '#e5e7eb' }};background:{{ $yildizli ? 'rgb(217 119 6 / .12)' : 'transparent' }};color:{{ $yildizli ? '#b45309' : '#9ca3af' }};font-size:1.05rem;cursor:pointer">{{ $yildizli ? '★' : '☆' }}</button>
                                        <span style="flex-shrink:0;width:5.2rem;font-size:.75rem;font-weight:700;color:{{ $m['gerceklesti'] ? '#15803d' : '#b45309' }}">{{ $m['gerceklesti'] ? 'G · Yapıldı' : 'P · Planlı' }}</span>
                                    </div>
                                @endforeach
                            </div>
                        @empty
                            <div style="text-align:center;padding:1.5rem;color:#6b7280;font-size:.85rem">{{ $yapilacakAyAdi }} ayı için yıllık planda planlanmış madde yok.</div>
                        @endforelse
                    </div>

                    <div style="display:flex;justify-content:space-between;align-items:center;gap:.5rem;padding:.7rem 1.1rem;border-top:1px solid #e5e7eb">
                        <a href="{{ \App\Filament\Pages\YillikPlan\YillikCalismaPlani::getUrl(['firma' => $this->firmaId, 'yil' => $yil]) }}" style="font-size:.8rem;color:#0369A1;text-decoration:underline">Yıllık Plan'da aç ↗</a>
                        <x-filament::button wire:click="yapilacakModalKapat" style="min-height:2.5rem">Kapat</x-filament::button>
                    </div>
                </div>
            </div>
        @endif

        @php
            $takvimAyBaslangic = \Illuminate\Support\Carbon::parse($this->takvimGosterilenAy.'-01');
            $takvimGunSayisi = $takvimAyBaslangic->daysInMonth;
            $takvimBosluk = $takvimAyBaslangic->dayOfWeekIso - 1;
            $takvimGunler = $this->takvimGunler;
            $seciliGunler = $takvimGunler[$takvimSeciliTarih] ?? [];
        @endphp
        <x-filament::section icon="heroicon-o-map" icon-color="primary">
            <x-slot name="heading">Nereye Gideceğim — Aylık Takvim</x-slot>
            <p style="font-size:.78rem;color:rgb(107 114 128);margin-top:-.5rem;margin-bottom:.8rem">
                Yukarıdaki tabloya girdiğiniz tarihli ziyaretler burada işaretlenir — Saha
                Takvimi'ndeki takvimle aynı kayıtlardan gelir, sadece bu firmaya süzülmüştür.
            </p>
            <div style="display:grid;grid-template-columns:20rem 1fr;gap:1rem">
                <div>
                    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:.6rem">
                        <button type="button" wire:click="takvimAyDegistir(-1)" style="border:none;background:none;cursor:pointer;font-size:1rem">‹</button>
                        <div style="font-weight:700">{{ $takvimAyBaslangic->translatedFormat('F Y') }}</div>
                        <button type="button" wire:click="takvimAyDegistir(1)" style="border:none;background:none;cursor:pointer;font-size:1rem">›</button>
                    </div>
                    <div style="display:grid;grid-template-columns:repeat(7,1fr);gap:2px;font-size:.75rem;text-align:center;color:rgb(107 114 128);margin-bottom:.3rem">
                        @foreach (['Pzt', 'Sal', 'Çar', 'Per', 'Cum', 'Cmt', 'Paz'] as $g)
                            <div>{{ $g }}</div>
                        @endforeach
                    </div>
                    <div style="display:grid;grid-template-columns:repeat(7,1fr);gap:2px">
                        @for ($bosluk = 0; $bosluk < $takvimBosluk; $bosluk++)
                            <div></div>
                        @endfor
                        @for ($gun = 1; $gun <= $takvimGunSayisi; $gun++)
                            @php
                                $tarih = $takvimAyBaslangic->copy()->day($gun)->toDateString();
                                $doluMu = ! empty($takvimGunler[$tarih]);
                                $seciliMi = $tarih === $takvimSeciliTarih;
                            @endphp
                            <button type="button" wire:click="takvimGunSec('{{ $tarih }}')"
                                style="aspect-ratio:1;border-radius:.35rem;border:none;cursor:pointer;font-size:.78rem;
                                    background:{{ $seciliMi ? $mor : ($doluMu ? 'rgb(139 92 246 / .15)' : 'transparent') }};
                                    color:{{ $seciliMi ? '#fff' : 'inherit' }}">
                                {{ $gun }}
                            </button>
                        @endfor
                    </div>
                </div>
                <div>
                    <div style="font-weight:700;margin-bottom:.6rem">{{ \Illuminate\Support\Carbon::parse($takvimSeciliTarih)->translatedFormat('d F Y') }}</div>
                    @forelse ($seciliGunler as $z)
                        <div style="border-top:1px solid rgb(107 114 128 / .15);padding:.5rem 0">
                            <div style="font-weight:600;font-size:.85rem">{{ $z['amac'] ?: 'Amaç belirtilmemiş' }}</div>
                            <div style="font-size:.78rem;color:rgb(107 114 128)">
                                {{ $durumEtiket[$z['durum']] ?? $z['durum'] }}
                                @if ($z['sure_saat']) · {{ $z['sure_saat'] }} saat @endif
                            </div>
                        </div>
                    @empty
                        <div style="text-align:center;padding:2rem;color:rgb(107 114 128)">Bu tarihte ziyaret kaydınız bulunmuyor.</div>
                    @endforelse
                </div>
            </div>
        </x-filament::section>
    @else
        <p class="mehse-uyari-not">Devam etmek için bir firma seçin.</p>
    @endif
    @endif
</x-filament-panels::page>
