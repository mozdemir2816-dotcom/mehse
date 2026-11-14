@php
    use App\Models\KontrolKarti as Kart;
    use App\Models\SahaAnalizi;

    $sayim = Kart::sayim($maddeler);
    $bitti = $sayim['toplam'] - $sayim['bos'];
    $olcekler = [
        'olasilik' => config('isg.risk_fine_kinney.olasilik', []),
        'frekans' => config('isg.risk_fine_kinney.frekans', []),
        'siddet' => config('isg.risk_fine_kinney.siddet', []),
    ];
    $fotoUrl = $fotoYolu ? \Illuminate\Support\Facades\Storage::disk('public')->url($fotoYolu) : null;
    $durumAdi = Kart::DURUMLAR;
@endphp

<x-filament-panels::page>
    <style>
        .kk { --kk-grafit:#22272B; --kk-mavi:#0057A8; --kk-kirmizi:#B3122E; --kk-yesil:#00704A; --kk-gri:#6B7280;
              --kk-zemin:#fff; --kk-cizgi:rgb(34 39 43 / .14); --kk-soluk:rgb(34 39 43 / .62); --kk-yuzey:#F4F5F7; }
        .dark .kk { --kk-zemin:rgb(24 24 27); --kk-cizgi:rgb(255 255 255 / .12); --kk-soluk:rgb(255 255 255 / .62); --kk-yuzey:rgb(255 255 255 / .04);
              --kk-kirmizi:#E5486A; --kk-yesil:#3DBB8A; --kk-mavi:#5B9EE6; --kk-gri:#9CA3AF; }
        .kk-izgara { display:grid; gap:.75rem; grid-template-columns:repeat(auto-fit,minmax(200px,1fr)); }
        .kk-etiket { display:block; font-weight:600; font-size:.82rem; margin-bottom:.25rem; }
        .kk-alan { width:100%; padding:.5rem .6rem; border-radius:.45rem; border:1px solid var(--kk-cizgi); background:transparent; font-size:.85rem; min-height:2.4rem; }
        .kk-alan:focus-visible, .kk-dugme:focus-visible, .kk-isaret:focus-visible { outline:2px solid var(--kk-mavi); outline-offset:2px; }
        .kk-duzen { display:grid; gap:1rem; grid-template-columns:1fr; align-items:start; }
        @media (min-width:1100px) { .kk-duzen { grid-template-columns:minmax(0,5fr) minmax(0,7fr); } .kk-foto-panel { position:sticky; top:4.5rem; } }
        .kk-kutu { border:1px solid var(--kk-cizgi); border-radius:.75rem; background:var(--kk-zemin); }
        .kk-foto { position:relative; line-height:0; border-radius:.75rem .75rem 0 0; overflow:hidden; background:var(--kk-grafit); }
        .kk-foto img { width:100%; height:auto; display:block; user-select:none; }
        .kk-foto.isaretleniyor { cursor:crosshair; }
        .kk-foto.isaretleniyor img { opacity:.85; }
        .kk-isaret { position:absolute; transform:translate(-50%,-50%); width:1.75rem; height:1.75rem; line-height:1.75rem; text-align:center;
            font-size:.8rem; font-weight:700; color:#fff; border:2px solid #fff; box-shadow:0 1px 3px rgb(0 0 0 / .45); cursor:pointer; padding:0; }
        .kk-sekil-uygun { border-radius:.3rem; background:var(--kk-yesil); }
        .kk-sekil-uygunsuz { border-radius:50%; background:var(--kk-kirmizi); }
        .kk-sekil-kapsam_disi { border-radius:.3rem; background:var(--kk-gri); }
        .kk-sekil-bos { border-radius:50%; background:var(--kk-mavi); }
        .kk-foto-alt { padding:.7rem .85rem; font-size:.8rem; color:var(--kk-soluk); display:flex; gap:.5rem; flex-wrap:wrap; align-items:center; justify-content:space-between; line-height:1.4; }
        .kk-yukle { display:flex; flex-direction:column; align-items:center; justify-content:center; gap:.4rem; min-height:14rem; padding:1.5rem;
            border:2px dashed var(--kk-cizgi); border-radius:.75rem; text-align:center; cursor:pointer; color:var(--kk-soluk); font-size:.85rem; }
        .kk-yukle:hover { border-color:var(--kk-mavi); }
        .kk-ozet { display:flex; gap:1rem; flex-wrap:wrap; align-items:center; padding:.75rem .9rem; border-bottom:1px solid var(--kk-cizgi); font-size:.84rem; }
        .kk-ozet b { font-size:1.05rem; }
        .kk-cubuk { flex-basis:100%; height:.35rem; border-radius:1rem; background:var(--kk-yuzey); overflow:hidden; display:flex; }
        .kk-madde { border-bottom:1px solid var(--kk-cizgi); padding:.75rem .9rem; }
        .kk-madde:last-child { border-bottom:0; }
        .kk-madde.aktif { background:rgb(0 87 168 / .07); }
        .kk-satir { display:flex; gap:.7rem; align-items:flex-start; }
        .kk-no { flex:none; width:1.75rem; height:1.75rem; line-height:1.75rem; text-align:center; color:#fff; font-weight:700; font-size:.8rem; }
        .kk-baslik { font-weight:600; font-size:.9rem; }
        .kk-soru { font-size:.8rem; color:var(--kk-soluk); margin-top:.1rem; line-height:1.4; }
        .kk-dugmeler { display:flex; gap:.35rem; flex-wrap:wrap; margin-top:.55rem; }
        .kk-dugme { min-height:2.25rem; padding:.3rem .7rem; border-radius:.45rem; border:1px solid var(--kk-cizgi); background:transparent; font-size:.8rem;
            font-weight:600; cursor:pointer; display:inline-flex; align-items:center; gap:.3rem; color:inherit; }
        .kk-dugme:hover { border-color:var(--kk-grafit); }
        .dark .kk-dugme:hover { border-color:#fff; }
        .kk-dugme[aria-pressed="true"].d-uygun { background:var(--kk-yesil); border-color:var(--kk-yesil); color:#fff; }
        .kk-dugme[aria-pressed="true"].d-uygunsuz { background:var(--kk-kirmizi); border-color:var(--kk-kirmizi); color:#fff; }
        .kk-dugme[aria-pressed="true"].d-kapsam_disi { background:var(--kk-gri); border-color:var(--kk-gri); color:#fff; }
        .kk-dugme.ince { font-weight:500; color:var(--kk-soluk); }
        .kk-ayrinti { margin-top:.7rem; padding:.75rem; border-radius:.6rem; background:var(--kk-yuzey); border-left:3px solid var(--kk-kirmizi); }
        .kk-ayrinti h4 { font-size:.8rem; font-weight:700; margin:.4rem 0 .45rem; }
        .kk-ayrinti h4:first-child { margin-top:0; }
        .kk-skor { display:flex; align-items:center; gap:.5rem; flex-wrap:wrap; font-size:.82rem; margin-top:.45rem; }
        .kk-bant { padding:.15rem .55rem; border-radius:.35rem; font-weight:700; font-size:.78rem; }
        @media (max-width:640px) { .kk-dugme { min-height:2.75rem; } }
        @media (prefers-reduced-motion:no-preference) { .kk-madde { transition:background-color .2s ease; } }
    </style>

    <div class="kk" x-data="{ aktif: null }" x-on:keydown.escape.window="aktif = null">
        <p class="mehse-bolum-aciklama">
            Alanın genel fotoğrafını yükleyin, her maddeyi Uygun / Uygunsuz / Kapsam dışı olarak işaretleyin ve
            maddenin fotoğraftaki yerini gösterin. Uygunsuz maddelerde mevcut ve ek önlem sonrası Fine-Kinney riski kartta yer alır.
        </p>

        <x-filament::section icon="heroicon-o-viewfinder-circle" icon-color="primary">
            <x-slot name="heading">Kart bilgileri</x-slot>
            <div class="kk-izgara">
                <div>
                    <label class="kk-etiket" for="kk-firma">Firma</label>
                    <select id="kk-firma" class="kk-alan" wire:model.live="firmaId">
                        <option value="">Firma seçin</option>
                        @foreach ($this->firmalar as $id => $ad)
                            <option value="{{ $id }}">{{ $ad }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="kk-etiket" for="kk-sablon">Kontrol listesi</label>
                    <select id="kk-sablon" class="kk-alan" wire:model.live="sablon" aria-describedby="kk-sablon-not">
                        @foreach ($this->sablonlar as $a => $ad)
                            <option value="{{ $a }}">{{ $ad }}</option>
                        @endforeach
                    </select>
                    <span id="kk-sablon-not" style="font-size:.75rem;color:var(--kk-soluk)">Değiştirmek işaretlenen maddeleri sıfırlar.</span>
                </div>
                <div>
                    <label class="kk-etiket" for="kk-alan">Kartın başlığı</label>
                    <input id="kk-alan" type="text" class="kk-alan" wire:model.blur="alanAdi">
                </div>
                <div>
                    <label class="kk-etiket" for="kk-konum">Konum / bölüm</label>
                    <input id="kk-konum" type="text" class="kk-alan" wire:model.blur="konum" placeholder="ör. Kaynakhane arkası">
                </div>
                <div>
                    <label class="kk-etiket" for="kk-tarih">Kontrol tarihi</label>
                    <input id="kk-tarih" type="date" class="kk-alan" wire:model.blur="kontrolTarihi">
                </div>
                <div>
                    <label class="kk-etiket" for="kk-eden">Kontrol eden</label>
                    <input id="kk-eden" type="text" class="kk-alan" wire:model.blur="kontrolEden">
                </div>
            </div>
        </x-filament::section>

        @if (! $this->firma)
            <div class="kk-kutu" style="margin-top:1rem;padding:1.25rem;font-size:.88rem">
                Kartı doldurmak için önce firmayı seçin. Fotoğrafı Gemini'de değerlendirdiyseniz firmayı seçtikten sonra
                <strong>"Gemini'den Yükle"</strong> ile cevabı yapıştırabilirsiniz.
            </div>
        @else
            <div class="kk-duzen" style="margin-top:1rem">
                {{-- Fotoğraf --}}
                <div class="kk-foto-panel">
                    @if ($fotoUrl)
                        <div class="kk-kutu">
                            <div class="kk-foto" x-ref="foto" x-bind:class="aktif !== null && 'isaretleniyor'"
                                 x-on:click="if (aktif === null || $event.target.closest('.kk-isaret')) return;
                                             const r = $refs.foto.getBoundingClientRect();
                                             $wire.isaretKoy(aktif, ($event.clientX - r.left) / r.width * 100, ($event.clientY - r.top) / r.height * 100);
                                             aktif = null;">
                                <img src="{{ $fotoUrl }}" alt="{{ $alanAdi }} fotoğrafı" draggable="false">
                                @foreach ($maddeler as $i => $m)
                                    @if (! empty($m['isaret']))
                                        <button type="button" class="kk-isaret kk-sekil-{{ $m['durum'] ?: 'bos' }}"
                                            style="left:{{ $m['isaret']['x'] }}%;top:{{ $m['isaret']['y'] }}%"
                                            x-on:click.stop="aktif = {{ $i }}; document.getElementById('kk-madde-{{ $i }}')?.scrollIntoView({ block: 'nearest' })"
                                            aria-label="{{ $i + 1 }}. {{ $m['baslik'] }}: {{ $durumAdi[$m['durum']] ?? 'bakılmadı' }}">{{ $i + 1 }}</button>
                                    @endif
                                @endforeach
                            </div>
                            <div class="kk-foto-alt">
                                <span x-show="aktif === null">Maddenin yerini göstermek için listeden <strong>"Fotoğrafta göster"</strong>e basın, sonra fotoğrafta tıklayın.</span>
                                <span x-show="aktif !== null" x-cloak style="color:var(--kk-mavi);font-weight:600">
                                    <span x-text="(aktif + 1) + '. maddenin yerine tıklayın'"></span> · Vazgeçmek için Esc
                                </span>
                                <span style="display:flex;gap:.4rem">
                                    <label class="kk-dugme ince" style="cursor:pointer">
                                        Fotoğrafı değiştir
                                        <input type="file" accept="image/*" wire:model="foto" style="display:none">
                                    </label>
                                    <button type="button" class="kk-dugme ince" wire:click="fotoKaldir" wire:confirm="Fotoğraf ve tüm işaretler kaldırılsın mı?">Kaldır</button>
                                </span>
                            </div>
                        </div>
                    @else
                        <label class="kk-yukle">
                            <x-filament::icon icon="heroicon-o-camera" class="h-8 w-8" />
                            <strong style="color:inherit">Alanın genel fotoğrafını yükleyin</strong>
                            <span>Tüm alan tek karede görünsün; maddeleri bu fotoğraf üzerinde numarayla göstereceksiniz.</span>
                            <input type="file" accept="image/*" wire:model="foto" style="display:none">
                            <span wire:loading wire:target="foto" style="color:var(--kk-mavi);font-weight:600">Yükleniyor…</span>
                        </label>
                    @endif
                    @error('foto') <p style="color:var(--kk-kirmizi);font-size:.8rem;margin-top:.4rem">{{ $message }}</p> @enderror
                </div>

                {{-- Kontrol listesi --}}
                <div class="kk-kutu">
                    <div class="kk-ozet" role="status" aria-live="polite">
                        <span><b>{{ $bitti }}</b>/{{ $sayim['toplam'] }} madde kontrol edildi</span>
                        <span style="color:var(--kk-yesil)"><b>{{ $sayim['uygun'] }}</b> uygun</span>
                        <span style="color:var(--kk-kirmizi)"><b>{{ $sayim['uygunsuz'] }}</b> uygunsuz</span>
                        @if ($sayim['kapsam_disi'])<span style="color:var(--kk-gri)"><b>{{ $sayim['kapsam_disi'] }}</b> kapsam dışı</span>@endif
                        @if ($sayim['bos'] && $bitti)
                            <button type="button" class="kk-dugme ince" style="margin-left:auto" wire:click="kalanlariUygunYap">Bakılmayan {{ $sayim['bos'] }} maddeyi uygun say</button>
                        @endif
                        <span class="kk-cubuk" aria-hidden="true">
                            @if ($sayim['toplam'])
                                <span style="width:{{ $sayim['uygun'] / $sayim['toplam'] * 100 }}%;background:var(--kk-yesil)"></span>
                                <span style="width:{{ $sayim['uygunsuz'] / $sayim['toplam'] * 100 }}%;background:var(--kk-kirmizi)"></span>
                                <span style="width:{{ $sayim['kapsam_disi'] / $sayim['toplam'] * 100 }}%;background:var(--kk-gri)"></span>
                            @endif
                        </span>
                    </div>

                    @foreach ($maddeler as $i => $m)
                        @php
                            $d = $m['durum'] ?: null;
                            $skor = Kart::skor($m);
                            $yeni = Kart::yeniSkor($m);
                            $bant = SahaAnalizi::fineKinneyBandi($skor);
                            $yeniBant = SahaAnalizi::fineKinneyBandi($yeni);
                            $koyuMetin = fn ($b) => in_array($b['renk'] ?? '', ['#84cc16', '#22c55e', '#f59e0b'], true) ? '#22272B' : '#fff';
                        @endphp
                        <div class="kk-madde" id="kk-madde-{{ $i }}" wire:key="kk-{{ $sablon }}-{{ $i }}" x-bind:class="aktif === {{ $i }} && 'aktif'">
                            <div class="kk-satir">
                                <span class="kk-no kk-sekil-{{ $d ?? 'bos' }}" aria-hidden="true">{{ $i + 1 }}</span>
                                <div style="flex:1;min-width:0">
                                    <div class="kk-baslik">{{ $m['baslik'] }}</div>
                                    <div class="kk-soru">{{ $m['kontrol'] }}</div>
                                    <div class="kk-dugmeler" role="group" aria-label="{{ $i + 1 }}. madde durumu">
                                        <button type="button" class="kk-dugme d-uygun" aria-pressed="{{ $d === 'uygun' ? 'true' : 'false' }}" wire:click="durumSec({{ $i }}, 'uygun')">✓ Uygun</button>
                                        <button type="button" class="kk-dugme d-uygunsuz" aria-pressed="{{ $d === 'uygunsuz' ? 'true' : 'false' }}" wire:click="durumSec({{ $i }}, 'uygunsuz')">✕ Uygunsuz</button>
                                        <button type="button" class="kk-dugme d-kapsam_disi" aria-pressed="{{ $d === 'kapsam_disi' ? 'true' : 'false' }}" wire:click="durumSec({{ $i }}, 'kapsam_disi')">– Kapsam dışı</button>
                                        @if ($fotoUrl)
                                            <button type="button" class="kk-dugme ince" x-on:click="aktif = (aktif === {{ $i }} ? null : {{ $i }}); $refs.foto?.scrollIntoView({ block: 'nearest' })"
                                                x-text="aktif === {{ $i }} ? 'Fotoğrafa tıklayın…' : '{{ empty($m['isaret']) ? 'Fotoğrafta göster' : 'İşareti taşı' }}'">{{ empty($m['isaret']) ? 'Fotoğrafta göster' : 'İşareti taşı' }}</button>
                                            @if (! empty($m['isaret']))
                                                <button type="button" class="kk-dugme ince" wire:click="isaretKaldir({{ $i }})">İşareti kaldır</button>
                                            @endif
                                        @endif
                                    </div>

                                    @if ($d === 'uygunsuz')
                                        <div class="kk-ayrinti">
                                            <h4>Tespit ve mevcut risk</h4>
                                            <div class="kk-izgara">
                                                <div style="grid-column:1/-1"><label class="kk-etiket" for="kk-{{ $i }}-tespit">Tespit</label><textarea id="kk-{{ $i }}-tespit" class="kk-alan" rows="2" wire:model.blur="maddeler.{{ $i }}.tespit"></textarea></div>
                                                <div><label class="kk-etiket" for="kk-{{ $i }}-sonuc">Risk (olası sonuç)</label><input id="kk-{{ $i }}-sonuc" type="text" class="kk-alan" wire:model.blur="maddeler.{{ $i }}.olasi_sonuc"></div>
                                                <div><label class="kk-etiket" for="kk-{{ $i }}-etk">Etkilenenler</label><input id="kk-{{ $i }}-etk" type="text" class="kk-alan" wire:model.blur="maddeler.{{ $i }}.etkilenenler"></div>
                                                <div><label class="kk-etiket" for="kk-{{ $i }}-mevcut">Mevcut önlem</label><input id="kk-{{ $i }}-mevcut" type="text" class="kk-alan" wire:model.blur="maddeler.{{ $i }}.mevcut_onlem"></div>
                                                @foreach (['olasilik' => 'Olasılık', 'frekans' => 'Frekans', 'siddet' => 'Şiddet'] as $alan => $ad)
                                                    <div>
                                                        <label class="kk-etiket" for="kk-{{ $i }}-{{ $alan }}">{{ $ad }}</label>
                                                        <select id="kk-{{ $i }}-{{ $alan }}" class="kk-alan" wire:model.live="maddeler.{{ $i }}.{{ $alan }}">
                                                            <option value="">Seçin</option>
                                                            @foreach ($olcekler[$alan] as $v => $e)<option value="{{ $v }}">{{ $v }} — {{ $e }}</option>@endforeach
                                                        </select>
                                                    </div>
                                                @endforeach
                                            </div>
                                            <div class="kk-skor">
                                                Skor <strong>{{ $skor !== null ? rtrim(rtrim(number_format($skor, 2, ',', ''), '0'), ',') : '—' }}</strong>
                                                @if ($bant)<span class="kk-bant" style="background:{{ $bant['renk'] }};color:{{ $koyuMetin($bant) }}">{{ $bant['ad'] }}</span>@endif
                                            </div>

                                            <h4 style="margin-top:.85rem">Ek önlem ve beklenen risk</h4>
                                            <div class="kk-izgara">
                                                <div style="grid-column:1/-1"><label class="kk-etiket" for="kk-{{ $i }}-oneri">Ek önlem / aksiyon</label><textarea id="kk-{{ $i }}-oneri" class="kk-alan" rows="2" wire:model.blur="maddeler.{{ $i }}.oneri"></textarea></div>
                                                <div><label class="kk-etiket" for="kk-{{ $i }}-sorumlu">Sorumlu</label><input id="kk-{{ $i }}-sorumlu" type="text" class="kk-alan" wire:model.blur="maddeler.{{ $i }}.sorumlu"></div>
                                                <div><label class="kk-etiket" for="kk-{{ $i }}-gun">Hedef süre (gün)</label><input id="kk-{{ $i }}-gun" type="number" min="1" class="kk-alan" wire:model.blur="maddeler.{{ $i }}.hedef_gun"></div>
                                                @foreach (['yeni_olasilik' => ['olasilik', 'Yeni olasılık'], 'yeni_frekans' => ['frekans', 'Yeni frekans'], 'yeni_siddet' => ['siddet', 'Yeni şiddet']] as $alan => [$olcek, $ad])
                                                    <div>
                                                        <label class="kk-etiket" for="kk-{{ $i }}-{{ $alan }}">{{ $ad }}</label>
                                                        <select id="kk-{{ $i }}-{{ $alan }}" class="kk-alan" wire:model.live="maddeler.{{ $i }}.{{ $alan }}">
                                                            <option value="">Seçin</option>
                                                            @foreach ($olcekler[$olcek] as $v => $e)<option value="{{ $v }}">{{ $v }} — {{ $e }}</option>@endforeach
                                                        </select>
                                                    </div>
                                                @endforeach
                                            </div>
                                            <div class="kk-skor">
                                                Yeni skor <strong>{{ $yeni !== null ? rtrim(rtrim(number_format($yeni, 2, ',', ''), '0'), ',') : '—' }}</strong>
                                                @if ($yeniBant)<span class="kk-bant" style="background:{{ $yeniBant['renk'] }};color:{{ $koyuMetin($yeniBant) }}">{{ $yeniBant['ad'] }}</span>@endif
                                            </div>

                                            <h4 style="margin-top:.85rem">Dayanak</h4>
                                            <div class="kk-izgara">
                                                <div><label class="kk-etiket" for="kk-{{ $i }}-yon">Yönetmelik</label><input id="kk-{{ $i }}-yon" type="text" class="kk-alan" wire:model.blur="maddeler.{{ $i }}.yonetmelik"></div>
                                                <div><label class="kk-etiket" for="kk-{{ $i }}-std">Standart</label><input id="kk-{{ $i }}-std" type="text" class="kk-alan" wire:model.blur="maddeler.{{ $i }}.standart"></div>
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            @if ($this->gecmisKayitlar->isNotEmpty())
                <x-filament::section icon="heroicon-o-clock" icon-color="gray" style="margin-top:1rem">
                    <x-slot name="heading">Bu firmanın kontrol kartları</x-slot>
                    <table class="mehse-tablo">
                        @foreach ($this->gecmisKayitlar as $k)
                            @php $s = Kart::sayim($k->maddeler ?? []); @endphp
                            <tr style="border-top:1px solid rgb(107 114 128 / .15)">
                                <td class="mehse-td">{{ $k->belge_no }}</td>
                                <td class="mehse-td">{{ $k->kontrol_tarihi?->format('d.m.Y') }}</td>
                                <td class="mehse-td">{{ $k->alan_adi ?: $k->sablonAdi() }}@if ($k->konum) — {{ $k->konum }}@endif</td>
                                <td class="mehse-td">{{ $s['uygunsuz'] }} uygunsuz / {{ $s['toplam'] }}</td>
                                <td style="padding:.35rem .5rem;text-align:right;white-space:nowrap">
                                    <x-filament::button size="xs" color="gray" wire:click="gecmisAc({{ $k->id }})">Aç</x-filament::button>
                                    <x-filament::button size="xs" color="gray" wire:click="gecmisPdf({{ $k->id }})">PDF</x-filament::button>
                                    <x-filament::button size="xs" color="danger" wire:click="gecmisSil({{ $k->id }})" wire:confirm="{{ $k->belge_no }} silinsin mi?">Sil</x-filament::button>
                                </td>
                            </tr>
                        @endforeach
                    </table>
                </x-filament::section>
            @endif
        @endif
    </div>
</x-filament-panels::page>
