@php
    $sari = 'rgb(245 158 11)';
    $kutu = 'border:1px solid rgb(107 114 128 / .3);border-radius:.75rem;padding:1rem';
    $oncelikRenk = fn ($o) => match ($o) { 'yuksek' => '#ef4444', 'dusuk' => '#6b7280', default => '#f59e0b' };
    $oncelikEtiket = fn ($o) => match ($o) { 'yuksek' => 'Yüksek', 'dusuk' => 'Düşük', default => 'Orta' };
@endphp

<x-filament-panels::page>
    <p class="mehse-bolum-aciklama">
        Onaylı deftere yazılacak tespit ve önerileri hazır katalogdan seçin veya
        serbest yazın; firma için kaydedip PDF çıktısını alın.
    </p>

    @include('filament.pages.partials.eksik-firmalar', ['kriterAnahtari' => 'tespit_oneri'])

    {{-- 1. FİRMA --}}
    <x-filament::section icon="heroicon-o-book-open" icon-color="warning">
        <x-slot name="heading">1. Firma</x-slot>
        <div class="mehse-izgara">
            <div>
                <label class="mehse-etiket">Firma Seçin <span class="mehse-hata">*</span></label>
                <select wire:model.live="firmaId"
                    class="mehse-girdi">
                    <option value="">— Firma seçin —</option>
                    @foreach ($this->firmalar as $id => $ad)
                        <option value="{{ $id }}">{{ $ad }}</option>
                    @endforeach
                </select>
            </div>
        </div>
    </x-filament::section>

    @if ($this->firma)
        @php $maddeler = $this->defter->maddeler ?? []; $f = $this->firma; @endphp

        {{-- İki kolon: solda hazır öneri kataloğu + serbest giriş, sağda canlı onaylı defter önizlemesi (A4 kâğıt) --}}
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(min(100%,440px),1fr));gap:1rem;align-items:start">

            {{-- SOL: KATALOG + SERBEST --}}
            <div style="display:flex;flex-direction:column;gap:1rem;min-width:0">
                {{-- 2. HAZIR ÖNERİ KATALOĞU --}}
                <x-filament::section icon="heroicon-o-clipboard-document-list" icon-color="warning">
                    <x-slot name="heading">2. Hazır Öneri Kataloğu</x-slot>

                    <div style="display:grid;grid-template-columns:1fr 2fr;gap:.75rem;margin-bottom:1rem">
                        <select wire:model.live="konuFiltre"
                            style="min-height:2.75rem;padding:.5rem .7rem;border-radius:.5rem;border:1px solid rgb(107 114 128 / .35);background:transparent;font-size:.82rem">
                            <option value="">Tüm konular</option>
                            @foreach ($this->konular as $k)
                                <option value="{{ $k }}">{{ $k }}</option>
                            @endforeach
                        </select>
                        <input type="text" wire:model.live.debounce.400ms="arama" placeholder="Tespit veya mevzuat maddesi ara (örn. 6331 m.10)..."
                            style="min-height:2.75rem;padding:.5rem .7rem;border-radius:.5rem;border:1px solid rgb(107 114 128 / .35);background:transparent;font-size:.82rem">
                    </div>

                    <div style="display:flex;flex-direction:column;gap:1rem;max-height:min(560px,60vh);overflow-y:auto;overscroll-behavior:auto;padding-right:.3rem">
                        @forelse ($this->katalog as $kategori => $maddelerKat)
                            <div>
                                <div style="font-weight:700;font-size:.82rem;color:{{ $sari }};margin-bottom:.4rem">{{ $kategori }} ({{ count($maddelerKat) }})</div>
                                <div style="display:flex;flex-direction:column;gap:.5rem">
                                    @foreach ($maddelerKat as $m)
                                        <div style="{{ $kutu }};border-radius:1rem">
                                            <div style="display:flex;justify-content:space-between;gap:.5rem;align-items:start">
                                                <div>
                                                    <span style="display:inline-block;color:#fff;padding:1px 8px;border-radius:9999px;font-size:.75rem;background:{{ $oncelikRenk($m['oncelik']) }}">{{ $oncelikEtiket($m['oncelik']) }} Öncelik</span>
                                                    <div style="font-size:.82rem;font-weight:600;margin-top:.3rem">{{ $m['tespit'] }}</div>
                                                    <div style="font-size:.78rem;color:rgb(107 114 128);margin-top:.2rem">{{ $m['oneri'] }}</div>
                                                    <div style="font-size:.75rem;color:rgb(107 114 128);margin-top:.2rem">Dayanak: {{ $m['dayanak'] }}</div>
                                                </div>
                                                <x-filament::button size="sm" color="warning" wire:click="katalogdanEkle('{{ addslashes($m['tespit']) }}')" style="min-height:2.5rem;flex-shrink:0">+ Ekle</x-filament::button>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @empty
                            <p style="font-size:.82rem;color:rgb(107 114 128)">Sonuç bulunamadı.</p>
                        @endforelse
                    </div>
                </x-filament::section>

                {{-- 3. SERBEST TESPİT/ÖNERİ --}}
                <x-filament::section icon="heroicon-o-pencil-square" icon-color="warning">
                    <x-slot name="heading">3. Diğer (Kendiniz Yazın)</x-slot>

                    <textarea wire:model="serbestTespit" rows="2" placeholder="Tespitinizi yazın (örn: İşyerinde acil çıkış kapısının kilitli olduğu tespit edilmiştir.)"
                        style="width:100%;padding:.5rem .7rem;border-radius:.4rem;border:1px solid rgb(107 114 128 / .3);background:transparent;font-size:.82rem;font-family:inherit;margin-bottom:.5rem"></textarea>

                    @if ($this->aiAktif)
                        <x-filament::button size="xs" color="warning" wire:click="aiOnerisiAl">✨ Yapay Zekadan Öneri Al</x-filament::button>
                    @endif

                    <textarea wire:model="serbestOneri" rows="2" placeholder="Önerinizi yazın (örn: Acil çıkış kapısı her zaman açılabilir durumda bulundurulmalıdır.)"
                        style="width:100%;padding:.5rem .7rem;border-radius:.4rem;border:1px solid rgb(107 114 128 / .3);background:transparent;font-size:.82rem;font-family:inherit;margin-top:.5rem;margin-bottom:.5rem"></textarea>

                    <div style="display:flex;align-items:center;gap:.6rem;margin-bottom:.5rem">
                        <input type="file" wire:model="yeniFoto" accept="image/*" style="font-size:.8rem">
                        @if ($yeniFoto)
                            <img alt="Tespit fotoğrafı" src="{{ $yeniFoto->temporaryUrl() }}" style="width:44px;height:44px;object-fit:cover;border-radius:.35rem;border:1px solid rgb(107 114 128 / .3)">
                            <button type="button" wire:click="$set('yeniFoto', null)" style="color:#dc2626;cursor:pointer;background:none;border:none;font-size:.8rem">Kaldır</button>
                        @endif
                    </div>
                    <p style="font-size:.75rem;color:rgb(107 114 128);margin-bottom:.5rem">
                        İsteğe bağlı fotoğraf kanıtı — PDF'te ilgili maddenin ardından ayrı bir sayfa olarak eklenir.
                    </p>

                    <x-filament::button size="sm" wire:click="serbestEkle" style="min-height:2.75rem">+ Listeye Ekle</x-filament::button>
                </x-filament::section>
            </div>

            {{-- SAĞ: CANLI ONAYLI DEFTER ÖNİZLEMESİ --}}
            <div style="min-width:0;position:sticky;top:4.5rem">
                <div style="border:1px solid rgb(107 114 128 / .25);border-radius:1rem;padding:.9rem 1rem">
                    <div style="display:flex;justify-content:space-between;align-items:center;gap:.5rem;flex-wrap:wrap;margin-bottom:.7rem">
                        <div style="font-weight:800;font-size:.95rem">4. Canlı Onaylı Defter Önizlemesi
                            <span style="font-weight:400;font-size:.78rem;color:rgb(107 114 128)">· {{ count($maddeler) }} madde</span>
                        </div>
                    </div>

                    <div style="display:flex;gap:.45rem;flex-wrap:wrap;margin-bottom:.8rem">
                        <x-filament::button size="sm" icon="heroicon-o-arrow-down-tray" wire:click="mountAction('pdf')" style="min-height:2.75rem">PDF Olarak İndir (İmzaya Hazır)</x-filament::button>
                        <x-filament::button size="sm" color="gray" icon="heroicon-o-document-arrow-up" wire:click="mountAction('wordAktar')" style="min-height:2.75rem">Word/Excel Aktar</x-filament::button>
                        <x-filament::button size="sm" color="gray" icon="heroicon-o-envelope" tag="a" :href="\App\Filament\Pages\OnBilgilendirmeler::getUrl(['firma' => $f->id])" style="min-height:2.75rem">Tebliğ Tutanağı Oluştur</x-filament::button>
                    </div>

                    <div style="display:flex;gap:.5rem;margin-bottom:.8rem">
                        <input type="text" wire:model="genelNot" placeholder="Genel not (opsiyonel) — örn: 12.06.2026 tarihli saha ziyareti" aria-label="Genel not"
                            style="flex:1;min-height:2.5rem;padding:.4rem .7rem;border-radius:.5rem;border:1px solid rgb(107 114 128 / .3);background:transparent;font-size:.8rem">
                        <x-filament::button size="sm" color="gray" wire:click="genelNotuKaydet" style="min-height:2.5rem">Kaydet</x-filament::button>
                    </div>

                    {{-- A4 kâğıt --}}
                    <div style="background:#fff;color:#111827;border:1px solid #e2e8f0;border-radius:3px;box-shadow:0 10px 28px rgb(15 23 42 / .16);padding:1.5rem 1.6rem;min-height:30rem;font-family:Georgia,'Times New Roman',serif;max-height:min(720px,75vh);overflow-y:auto">
                        <div style="text-align:center;border-bottom:2px solid #111827;padding-bottom:.6rem;margin-bottom:.8rem">
                            <div style="font-size:1.05rem;font-weight:700;letter-spacing:.06em">TESPİT VE ÖNERİ DEFTERİ</div>
                            <div style="font-size:.78rem;margin-top:.2rem">{{ $f->unvan }}</div>
                            <div style="font-size:.75rem;color:#4b5563">
                                {{ collect([$f->nace_kodu ? 'NACE '.$f->nace_kodu : null, $f->tehlikeSinifiEtiketi(), $f->sgk_sicil_no ? 'SGK Sicil '.$f->sgk_sicil_no : null])->filter()->implode(' · ') }}
                            </div>
                        </div>

                        @if ($this->defter?->genel_not)
                            <p style="font-size:.75rem;font-style:italic;color:#374151;margin:0 0 .6rem">Not: {{ $this->defter->genel_not }}</p>
                        @endif

                        @if ($maddeler)
                            <table style="width:100%;border-collapse:collapse;font-size:.75rem">
                                <tr>
                                    <th style="width:2rem;text-align:left;padding:.3rem .3rem;border-bottom:1px solid #111827">No</th>
                                    <th style="text-align:left;padding:.3rem .4rem;border-bottom:1px solid #111827">Tespit</th>
                                    <th style="text-align:left;padding:.3rem .4rem;border-bottom:1px solid #111827">Öneri</th>
                                    <th style="width:1.5rem;border-bottom:1px solid #111827"></th>
                                </tr>
                                @foreach ($maddeler as $i => $m)
                                    <tr wire:key="defter-madde-{{ $i }}">
                                        <td style="padding:.35rem .3rem;vertical-align:top;border-bottom:1px solid #e5e7eb">{{ $i + 1 }}</td>
                                        <td style="padding:.35rem .4rem;white-space:pre-line;vertical-align:top;border-bottom:1px solid #e5e7eb">@if (($m['tur'] ?? null) === 'bilgilendirme')<span style="display:inline-block;background:#2563eb;color:#fff;border-radius:4px;padding:0 .35rem;font-size:.68rem;font-family:sans-serif">Ön Bilgilendirme{{ ! empty($m['tarih']) ? ' — '.$m['tarih'] : '' }}</span> @endif @if (! empty($m['foto_yolu']))<span title="Fotoğraf kanıtı ekli">📷</span> @endif{{ $m['tespit'] }}</td>
                                        @php $oneriBos = blank(trim((string) ($m['oneri'] ?? ''))); @endphp
                                        <td style="padding:.35rem .4rem;white-space:pre-line;vertical-align:top;border-bottom:1px solid #e5e7eb;{{ $oneriBos ? 'color:#6b7280;font-style:italic' : '' }}" @if ($oneriBos) title="Öneri boş — PDF'e bu varsayılan metin basılır" @endif>{{ $oneriBos ? \App\Models\TespitOneriDefteri::VARSAYILAN_ONERI : $m['oneri'] }}</td>
                                        <td style="padding:.35rem 0;text-align:right;vertical-align:top;border-bottom:1px solid #e5e7eb">
                                            <button type="button" wire:click="maddeSil({{ $i }})" title="Maddeyi sil" aria-label="Maddeyi sil" style="color:#dc2626;cursor:pointer;background:none;border:none;min-width:1.5rem;min-height:1.5rem">✕</button>
                                        </td>
                                    </tr>
                                @endforeach
                            </table>

                            <div style="display:flex;justify-content:space-between;gap:1rem;margin-top:1.8rem;font-size:.75rem;font-family:sans-serif">
                                <div style="flex:1;border-top:1px solid #111827;padding-top:.25rem;text-align:center">İş Güvenliği Uzmanı<br>{{ $f->igu?->ad_soyad ?: $f->user?->name }}</div>
                                <div style="flex:1;border-top:1px solid #111827;padding-top:.25rem;text-align:center">İşveren / Vekili<br>{{ $f->isveren_vekili ?: $f->isveren_ad }}</div>
                            </div>
                        @else
                            <p style="font-size:.78rem;color:#6b7280;text-align:center;margin-top:3rem;font-family:sans-serif">Henüz madde eklenmedi.<br>Soldaki katalogdan <strong>+ Ekle</strong> ile ya da serbest yazarak madde ekleyin; defter burada canlı görünür.</p>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    @else
        <p class="mehse-uyari-not">Devam etmek için bir firma seçin.</p>
    @endif
</x-filament-panels::page>
