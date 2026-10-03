@php
    $inp = 'margin-top:.3rem;width:100%;padding:.5rem .75rem;border-radius:.5rem;border:1px solid rgb(107 114 128 / .35);background:transparent';
    $lbl = 'font-weight:600;font-size:.82rem';
    $kart = 'border:1px solid rgb(107 114 128 / .22);border-radius:.6rem;padding:.6rem .8rem';
    $th = 'text-align:left;padding:.5rem .6rem;font-size:.7rem;letter-spacing:.05em;text-transform:uppercase;color:rgb(107 114 128);border-bottom:1px solid rgb(107 114 128 / .2)';
    $td = 'padding:.5rem .6rem;border-bottom:1px solid rgb(107 114 128 / .12);vertical-align:top';
    $durumRenk = ['taslak' => '#6b7280', 'incelemede' => '#f59e0b', 'onaylandi' => '#10b981', 'arsiv' => '#9ca3af'];
    $kapsamaRenk = ['yeterli' => 'rgb(21 128 61)', 'kismi' => 'rgb(217 119 6)', 'eksik' => 'rgb(107 114 128)'];
    $kapsamaAd = ['yeterli' => 'Yeterli', 'kismi' => 'Kısmi', 'eksik' => 'Eksik'];
    $nk = $this->naceKapsama;
    $hedef = $nk['hedef'];
@endphp

<x-filament-panels::page>
    {{-- BAŞLIK --}}
    <div style="border-radius:.9rem;padding:1.1rem 1.3rem;background:linear-gradient(120deg,#0f2b46,#115e59);color:#fff">
        <div style="font-size:.68rem;letter-spacing:.08em;text-transform:uppercase;opacity:.85;font-weight:700">Denetlenebilir içerik · editoryal onay</div>
        <div style="font-size:1.25rem;font-weight:800;margin:.25rem 0">İSG soru bankası ve sınav havuzu</div>
        <div style="font-size:.82rem;opacity:.9;max-width:720px">
            Sorular kod + sürüm, doğru cevabın gerekçesi ve doğrulanabilir kaynaklarla tutulur; taslak → incelemede → yayımlandı akışından geçer.
            Eğitim Soruları ve Uzaktan Eğitim sınavları yalnızca <strong>yayımlanmış</strong> ve işyerinin sektörüne / NACE koduna uyan soruları kullanır.
        </div>
        <div style="display:flex;gap:.4rem;margin-top:.75rem;flex-wrap:wrap">
            <x-filament::button size="xs" color="gray" icon="heroicon-o-document-arrow-down" wire:click="jsonSablonu">JSON şablonu</x-filament::button>
            <x-filament::button size="xs" color="gray" icon="heroicon-o-arrow-up-tray" wire:click="mountAction('topluYukle')">Toplu taslak yükle</x-filament::button>
        </div>
    </div>

    {{-- DURUM SAYAÇLARI --}}
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(140px,1fr));gap:.6rem">
        @foreach (['taslak' => ['Taslak', 'taslak'], 'incelemede' => ['İncelemede', 'incelemede'], 'onayli' => ['Yayımlanmış', 'onaylandi'], 'arsiv' => ['Kaldırılmış', 'arsiv']] as $k => [$ad, $durum])
            <button type="button" wire:click="$set('durumFiltre', '{{ $durumFiltre === $durum ? '' : $durum }}')" style="{{ $kart }};text-align:left;display:flex;justify-content:space-between;align-items:center;{{ $durumFiltre === $durum ? 'outline:2px solid '.$durumRenk[$durum] : '' }}">
                <span style="font-size:.72rem;font-weight:700;padding:.1rem .5rem;border-radius:9999px;color:{{ $durumRenk[$durum] }};background:color-mix(in srgb, {{ $durumRenk[$durum] }} 14%, transparent)">● {{ $ad }}</span>
                <span style="font-size:1.35rem;font-weight:800">{{ $this->ozet[$k] }}</span>
            </button>
        @endforeach
    </div>

    {{-- NACE KAPSAMA --}}
    <x-filament::section icon="heroicon-o-squares-2x2" collapsible>
        <x-slot name="heading">{{ number_format($nk['toplam'], 0, ',', '.') }} NACE için soru kapsaması</x-slot>
        <x-slot name="description">Her NACE için hedef: {{ $hedef['temel'] }} temel (ortak) + {{ $hedef['ise_ozgu'] }} işe özgü (NACE kapsamlı) yayımlanmış soru. Havuz yetersizse eğitim akışı durmaz; sınav ortak ve sektör sorularıyla kurulur, eksik NACE soruları AI ile taslak üretilip incelemeden sonra yayımlanabilir.</x-slot>

        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(170px,1fr));gap:.5rem;margin-bottom:.8rem">
            <div style="{{ $kart }}"><div style="font-size:1.2rem;font-weight:800">{{ number_format($nk['toplam'], 0, ',', '.') }}</div><div style="font-size:.72rem;color:rgb(107 114 128)">Resmi NACE</div></div>
            <div style="{{ $kart }}"><div style="font-size:1.2rem;font-weight:800;color:{{ $nk['ortak'] >= $hedef['temel'] ? 'rgb(21 128 61)' : 'rgb(217 119 6)' }}">{{ $nk['ortak'] }}</div><div style="font-size:.72rem;color:rgb(107 114 128)">Yayımlanmış ortak (temel) soru</div></div>
            <div style="{{ $kart }}"><div style="font-size:1.2rem;font-weight:800">{{ $nk['naceli_yayinli'] }}</div><div style="font-size:.72rem;color:rgb(107 114 128)">Yayımlanmış NACE kapsamlı soru</div></div>
            <div style="{{ $kart }}"><div style="font-size:1.2rem;font-weight:800;color:rgb(21 128 61)">{{ $nk['yeterli'] }}</div><div style="font-size:.72rem;color:rgb(107 114 128)">Havuzu yeterli NACE · {{ $nk['kismi'] }} kısmi</div></div>
        </div>

        <div style="display:flex;gap:.5rem;flex-wrap:wrap;margin-bottom:.6rem">
            <input type="search" wire:model.live.debounce.400ms="naceArama" placeholder="NACE kodu veya faaliyet ara" style="{{ $inp }};margin-top:0;flex:1;min-width:220px">
            <select wire:model.live="naceDurum" style="{{ $inp }};margin-top:0;width:auto">
                <option value="">Tüm NACE faaliyetleri</option>
                <option value="yeterli">Yeterli</option>
                <option value="kismi">Kısmi</option>
                <option value="eksik">Eksik</option>
            </select>
        </div>

        <div style="overflow-x:auto">
            <table style="width:100%;border-collapse:collapse;font-size:.8rem">
                <thead><tr><th style="{{ $th }}">NACE / Faaliyet</th><th style="{{ $th }}" title="Yayımlanmış ortak soru">Temel</th><th style="{{ $th }}" title="Yayımlanmış NACE kapsamlı soru">İşe özgü</th><th style="{{ $th }}" title="Taslak / incelemede NACE sorusu">Bekleyen</th><th style="{{ $th }}">Durum</th><th style="{{ $th }}"></th></tr></thead>
                <tbody>
                    @forelse ($nk['satirlar'] as $s)
                        <tr wire:key="nace-{{ $s['kod'] }}">
                            <td style="{{ $td }}"><div style="font-weight:700;color:rgb(13 148 136)">{{ $s['kod'] }}</div><div>{{ $s['tanim'] }}</div><div style="font-size:.7rem;color:rgb(107 114 128)">{{ config('isg.tehlike_siniflari.'.$s['tehlike'], $s['tehlike']) }}</div></td>
                            <td style="{{ $td }}">{{ min($s['ortak'], $hedef['temel']) }}/{{ $hedef['temel'] }}</td>
                            <td style="{{ $td }}">{{ $s['nace'] }}/{{ $hedef['ise_ozgu'] }}</td>
                            <td style="{{ $td }}">{{ $s['bekleyen'] }}</td>
                            <td style="{{ $td }}"><span style="font-size:.7rem;font-weight:700;padding:.1rem .5rem;border-radius:9999px;color:{{ $kapsamaRenk[$s['durum']] }};background:color-mix(in srgb, {{ $kapsamaRenk[$s['durum']] }} 12%, transparent)">{{ $kapsamaAd[$s['durum']] }}</span></td>
                            <td style="{{ $td }};white-space:nowrap">
                                @if ($s['durum'] !== 'yeterli' && $this->aiAktif)
                                    <x-filament::button size="xs" color="gray" icon="heroicon-o-sparkles" wire:click="naceIcinUret('{{ $s['kod'] }}')" wire:loading.attr="disabled">Taslak üret</x-filament::button>
                                @endif
                                <x-filament::button size="xs" color="gray" wire:click="$set('yeniNace', '{{ $s['kod'] }}')" x-on:click="document.getElementById('soru-formu')?.scrollIntoView({behavior:'smooth'})">Soru yaz</x-filament::button>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" style="{{ $td }};color:rgb(107 114 128)">Aramaya uyan NACE yok.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($nk['son_sayfa'] > 1)
            <div style="display:flex;gap:.4rem;align-items:center;justify-content:flex-end;margin-top:.6rem;font-size:.78rem">
                <span style="color:rgb(107 114 128)">{{ $nk['bulunan'] }} kayıt · sayfa {{ $nk['sayfa'] }}/{{ $nk['son_sayfa'] }}</span>
                <x-filament::button size="xs" color="gray" wire:click="$set('naceSayfa', {{ max(1, $nk['sayfa'] - 1) }})" :disabled="$nk['sayfa'] <= 1">‹ Önceki</x-filament::button>
                <x-filament::button size="xs" color="gray" wire:click="$set('naceSayfa', {{ min($nk['son_sayfa'], $nk['sayfa'] + 1) }})" :disabled="$nk['sayfa'] >= $nk['son_sayfa']">Sonraki ›</x-filament::button>
            </div>
        @endif
    </x-filament::section>

    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(380px,1fr));gap:1rem;align-items:start">
        {{-- YENİ SORU --}}
        <x-filament::section icon="heroicon-o-plus-circle" icon-color="primary" id="soru-formu">
            <x-slot name="heading">{{ $duzenlenenId ? 'Taslağı düzenle' : 'Kaynaklı soru taslağı' }}</x-slot>
            <x-slot name="description">Kaydedilen soru taslak olarak girer; yayımlamak için gerekçe ve en az bir doğrulanabilir kaynak gerekir.</x-slot>

            <div style="display:grid;grid-template-columns:1fr 1fr;gap:.6rem">
                <div><label style="{{ $lbl }}">Soru kodu</label><input type="text" wire:model="yeniKod" placeholder="Örn. NACE-30.11-001" style="{{ $inp }}"></div>
                <div><label style="{{ $lbl }}">Konu *</label>
                    <select wire:model="yeniKonu" style="{{ $inp }}">@foreach ($this->konular as $a => $ad)<option value="{{ $a }}">{{ $ad }}</option>@endforeach</select></div>
                <div><label style="{{ $lbl }}">Zorluk</label>
                    <select wire:model="yeniZorluk" style="{{ $inp }}">@foreach ($this->zorluklar as $a => $ad)<option value="{{ $a }}">{{ $ad }}</option>@endforeach</select></div>
                <div><label style="{{ $lbl }}">Sektör kapsamı</label>
                    <select wire:model="yeniSektor" style="{{ $inp }}">@foreach ($this->sektorler as $a => $ad)<option value="{{ $a }}">{{ $ad }}</option>@endforeach</select></div>
            </div>
            <div style="margin-top:.6rem">
                <label style="{{ $lbl }}">NACE kapsamı</label>
                <input type="text" wire:model="yeniNace" placeholder="Örn. 41, 43.21 — boşsa ortak / sektör sorusu" style="{{ $inp }}">
                <div style="font-size:.7rem;color:rgb(107 114 128);margin-top:.2rem">NACE değeri alt faaliyetleri kapsar: "30.11" yazılırsa 30.11 ile başlayan tüm kodlara uygulanır. Virgülle birden çok girilebilir.</div>
            </div>

            <div style="margin-top:.6rem"><label style="{{ $lbl }}">Soru metni *</label>
                <textarea wire:model="yeniSoru" rows="2" placeholder="Tek anlamlı, açık ve sektöre uygun soru yazın." style="{{ $inp }};font-family:inherit;font-size:.85rem"></textarea></div>

            <div style="margin-top:.6rem;{{ $kart }}">
                <div style="{{ $lbl }};margin-bottom:.3rem">Cevap seçenekleri ve doğru cevap *</div>
                @foreach (range(0, 3) as $i)
                    <label style="display:flex;align-items:center;gap:.5rem;font-size:.82rem;margin-bottom:.3rem">
                        <input type="radio" wire:model="yeniDogruIndex" value="{{ $i }}" title="Doğru şık">
                        <span style="font-weight:700;width:1.2rem">{{ chr(65 + $i) }}</span>
                        <input type="text" wire:model="yeniSecenekler.{{ $i }}" placeholder="{{ chr(65 + $i) }} seçeneği" style="flex:1;padding:.4rem .6rem;border-radius:.4rem;border:1px solid rgb(107 114 128 / .3);background:transparent">
                    </label>
                @endforeach
                <div style="font-size:.7rem;color:rgb(107 114 128)">Doğru cevabın yanındaki yuvarlağı seçin. Seçenekler birbirinden farklı olmalıdır.</div>
            </div>

            <div style="margin-top:.6rem"><label style="{{ $lbl }}">Doğru cevabın gerekçesi (yayım için zorunlu)</label>
                <textarea wire:model="yeniAciklama" rows="2" placeholder="Neden doğru olduğunu mevzuat ve uygulama açısından açıklayın." style="{{ $inp }};font-family:inherit;font-size:.85rem"></textarea></div>

            <div style="margin-top:.6rem;{{ $kart }}">
                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:.3rem">
                    <span style="{{ $lbl }}">Doğrulanabilir kaynaklar *</span>
                    <x-filament::button size="xs" color="gray" icon="heroicon-o-plus" wire:click="kaynakEkle">Kaynak ekle</x-filament::button>
                </div>
                @foreach ($yeniKaynaklar as $i => $k)
                    <div wire:key="kaynak-{{ $i }}" style="border:1px dashed rgb(107 114 128 / .3);border-radius:.5rem;padding:.5rem;margin-bottom:.4rem">
                        <div style="display:flex;justify-content:space-between;font-size:.75rem;font-weight:700">Kaynak {{ $i + 1 }}
                            @if (count($yeniKaynaklar) > 1)<button type="button" wire:click="kaynakSil({{ $i }})" style="color:rgb(220 38 38)">Sil</button>@endif
                        </div>
                        <input type="text" wire:model="yeniKaynaklar.{{ $i }}.ad" placeholder="Resmî kaynağın adı" list="kaynak-listesi" style="{{ $inp }}">
                        <input type="url" wire:model="yeniKaynaklar.{{ $i }}.url" placeholder="https://..." style="{{ $inp }}">
                        <div style="display:grid;grid-template-columns:1fr 1fr;gap:.4rem">
                            <input type="text" wire:model="yeniKaynaklar.{{ $i }}.madde" placeholder="Madde / bölüm / sayfa" style="{{ $inp }}">
                            <input type="date" wire:model="yeniKaynaklar.{{ $i }}.tarih" title="Erişim / yürürlük tarihi" style="{{ $inp }}">
                        </div>
                    </div>
                @endforeach
                <datalist id="kaynak-listesi">@foreach (config('isg.soru_bankasi.ornek_kaynaklar') as $ok)<option value="{{ $ok }}">@endforeach</datalist>
            </div>

            <div style="margin-top:.6rem"><label style="{{ $lbl }}">İnceleme notu</label>
                <textarea wire:model="yeniIncelemeNotu" rows="2" placeholder="Sınavı hazırlayan İSG uzmanının inceleme notu" style="{{ $inp }};font-family:inherit;font-size:.85rem"></textarea></div>

            <div style="margin-top:.75rem;display:flex;gap:.4rem">
                <x-filament::button icon="heroicon-o-document-check" wire:click="soruEkle">{{ $duzenlenenId ? 'Taslağı güncelle' : 'Taslak olarak kaydet' }}</x-filament::button>
                <x-filament::button color="gray" wire:click="formuTemizle">Temizle</x-filament::button>
            </div>
        </x-filament::section>

        {{-- SORULAR --}}
        <x-filament::section icon="heroicon-o-rectangle-stack">
            <x-slot name="heading">Sorular ve onay durumu ({{ $this->sorular->count() }})</x-slot>
            <x-slot name="description">Yalnız yayımlanmış sorular sınav üretiminde kullanılır.</x-slot>

            <input type="search" wire:model.live.debounce.400ms="arama" placeholder="Kod veya soru ara" style="{{ $inp }};margin-top:0">
            <div style="display:flex;gap:.3rem;flex-wrap:wrap;margin:.5rem 0">
                @foreach (['' => 'Tümü'] + $this->durumlar as $a => $ad)
                    <button type="button" wire:click="$set('durumFiltre', '{{ $a }}')" style="font-size:.74rem;font-weight:600;padding:.2rem .7rem;border-radius:9999px;border:1px solid rgb(107 114 128 / .3);{{ $durumFiltre === $a ? 'background:#0f2b46;color:#fff' : '' }}">{{ $ad }}</button>
                @endforeach
            </div>
            <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:.4rem;margin-bottom:.6rem">
                <select wire:model.live="sektorFiltre" style="{{ $inp }};margin-top:0;font-size:.78rem">
                    <option value="">Tüm kapsamlar</option>
                    <option value="__ortak__">Ortak</option>
                    <option value="__nace__">NACE kapsamlı</option>
                    @foreach ($this->sektorler as $a => $ad)@if ($a !== '')<option value="{{ $a }}">{{ $ad }}</option>@endif @endforeach
                </select>
                <select wire:model.live="konuFiltre" style="{{ $inp }};margin-top:0;font-size:.78rem"><option value="">Tüm konular</option>@foreach ($this->konular as $a => $ad)<option value="{{ $a }}">{{ $ad }}</option>@endforeach</select>
                <select wire:model.live="zorlukFiltre" style="{{ $inp }};margin-top:0;font-size:.78rem"><option value="">Tüm zorluklar</option>@foreach ($this->zorluklar as $a => $ad)<option value="{{ $a }}">{{ $ad }}</option>@endforeach</select>
            </div>

            @if (in_array($durumFiltre, ['taslak', 'incelemede'], true) && $this->sorular->isNotEmpty())
                <div style="margin-bottom:.6rem">
                    <x-filament::button size="xs" color="success" wire:click="tumTaslaklariOnayla" wire:confirm="Filtredeki gerekçesi ve kaynağı tam olan sorular yayımlanacak. Emin misiniz?">Filtredekileri toplu yayımla</x-filament::button>
                </div>
            @endif

            @if ($this->sorular->isEmpty())
                <p style="font-size:.83rem;color:rgb(107 114 128)">Bu filtrede soru yok.</p>
            @else
                <div style="display:flex;flex-direction:column;gap:.6rem;max-height:1400px;overflow-y:auto">
                    @foreach ($this->sorular as $s)
                        @php $kaynaklar = $s->kaynakListesi(); $yayinlanabilir = \App\Filament\Pages\SoruBankasi::yayimlanabilirMi($s); @endphp
                        <div wire:key="soru-{{ $s->id }}" style="{{ $kart }}">
                            <div style="display:flex;justify-content:space-between;gap:.4rem;align-items:flex-start">
                                <div style="font-size:.72rem;font-weight:800;color:rgb(13 148 136)">{{ $s->kodEtiketi() }}</div>
                                <span style="font-size:.66rem;font-weight:700;padding:.05rem .5rem;border-radius:9999px;color:{{ $durumRenk[$s->durum] ?? '#6b7280' }};background:color-mix(in srgb, {{ $durumRenk[$s->durum] ?? '#6b7280' }} 14%, transparent)">● {{ $s->durumEtiketi() }}</span>
                            </div>
                            <div style="font-weight:700;font-size:.86rem;margin:.2rem 0">{{ $s->soru }}</div>
                            <div style="display:flex;gap:.3rem;flex-wrap:wrap;align-items:center;font-size:.68rem;color:rgb(107 114 128)">
                                <span>{{ $s->konuEtiketi() }} · {{ $s->zorlukEtiketi() }}</span>
                                @if ($s->naceKapsami())<span style="border:1px solid rgb(13 148 136 / .4);border-radius:.3rem;padding:0 .3rem;color:rgb(13 148 136)">NACE: {{ implode(', ', $s->naceKapsami()) }}</span>
                                @elseif ($s->sektor_anahtari)<span style="border:1px solid rgb(107 114 128 / .3);border-radius:.3rem;padding:0 .3rem">Sektör: {{ $s->sektorEtiketi() }}</span>
                                @else<span style="border:1px solid rgb(107 114 128 / .3);border-radius:.3rem;padding:0 .3rem">Ortak: *</span>@endif
                                @if ($s->uretim_kaynagi === 'ai')<span style="color:#8b5cf6">✨ AI</span>@endif
                                @if ($s->uretim_kaynagi === 'json')<span>JSON</span>@endif
                                @if ($s->user_id === null)<span>sistem havuzu</span>@endif
                                <span style="margin-left:auto">Güncelleme: {{ $s->updated_at?->format('d.m.Y') }}</span>
                            </div>

                            <details style="margin-top:.4rem;border:1px solid rgb(107 114 128 / .15);border-radius:.4rem;padding:.35rem .6rem">
                                <summary style="cursor:pointer;font-size:.75rem;font-weight:600">Cevap, gerekçe ve kaynakları göster</summary>
                                <ol type="A" style="margin:.4rem 0 .3rem 1.1rem;padding:0;font-size:.8rem">
                                    @foreach ($s->secenekler as $si => $sec)
                                        <li style="{{ $si === $s->dogru_index ? 'color:#10b981;font-weight:600' : '' }}">{{ $sec }}{{ $si === $s->dogru_index ? ' ✓' : '' }}</li>
                                    @endforeach
                                </ol>
                                <div style="font-size:.75rem"><strong>Gerekçe:</strong> {!! $s->aciklama ? e($s->aciklama) : '<span style="color:rgb(220 38 38)">girilmemiş</span>' !!}</div>
                                <div style="font-size:.75rem;margin-top:.2rem"><strong>Kaynaklar:</strong>
                                    @forelse ($kaynaklar as $k)
                                        <div style="margin-left:.6rem">• @if ($k['url'])<a href="{{ $k['url'] }}" target="_blank" rel="noopener" style="color:rgb(13 148 136);text-decoration:underline">{{ $k['ad'] }}</a>@else{{ $k['ad'] }}@endif{{ $k['madde'] ? ' — '.$k['madde'] : '' }}{{ $k['tarih'] ? ' ('.\Illuminate\Support\Carbon::parse($k['tarih'])->format('d.m.Y').')' : '' }}</div>
                                    @empty
                                        <span style="color:rgb(220 38 38)">girilmemiş</span>
                                    @endforelse
                                </div>
                                @if ($s->inceleme_notu)<div style="font-size:.75rem;margin-top:.2rem"><strong>İnceleme notu:</strong> {{ $s->inceleme_notu }}</div>@endif
                                @if ($s->onaylayan)<div style="font-size:.7rem;color:rgb(107 114 128);margin-top:.2rem">Yayımlayan: {{ $s->onaylayan }} · {{ $s->onay_tarihi?->format('d.m.Y') }}</div>@endif
                            </details>

                            <div style="margin-top:.5rem;display:flex;gap:.3rem;flex-wrap:wrap">
                                @if (in_array($s->durum, ['taslak', 'incelemede'], true))
                                    <x-filament::button size="xs" color="gray" icon="heroicon-o-pencil" wire:click="duzenle({{ $s->id }})" x-on:click="document.getElementById('soru-formu')?.scrollIntoView({behavior:'smooth'})">Düzenle</x-filament::button>
                                @endif
                                @if ($s->durum === 'taslak')
                                    <x-filament::button size="xs" color="warning" wire:click="incelemeyeGonder({{ $s->id }})">İncelemeye gönder</x-filament::button>
                                @endif
                                @if (in_array($s->durum, ['taslak', 'incelemede'], true))
                                    <x-filament::button size="xs" color="success" wire:click="onayla({{ $s->id }})" :tooltip="$yayinlanabilir ? null : 'Gerekçe ve kaynak gerekli'">Yayımla</x-filament::button>
                                @endif
                                @if ($s->durum === 'onaylandi')
                                    <x-filament::button size="xs" color="gray" icon="heroicon-o-archive-box" wire:click="arsivle({{ $s->id }})">Kullanımdan kaldır</x-filament::button>
                                @endif
                                @if (in_array($s->durum, ['onaylandi', 'arsiv'], true))
                                    <x-filament::button size="xs" color="gray" icon="heroicon-o-document-duplicate" wire:click="yeniSurum({{ $s->id }})" x-on:click="document.getElementById('soru-formu')?.scrollIntoView({behavior:'smooth'})">Yeni sürüm</x-filament::button>
                                @endif
                                @if ($s->durum === 'arsiv')
                                    <x-filament::button size="xs" color="gray" wire:click="taslagaAl({{ $s->id }})">Taslağa al</x-filament::button>
                                @endif
                                @if ($s->user_id !== null && $s->durum !== 'onaylandi')
                                    <x-filament::button size="xs" color="danger" wire:click="sil({{ $s->id }})" wire:confirm="Soru silinsin mi?">Sil</x-filament::button>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </x-filament::section>
    </div>
</x-filament-panels::page>
