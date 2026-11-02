@php
    $konular = config('isg.acil_durum.konular');
    $cerceveler = config('isg.acil_durum.kapak_cerceveleri');
    $ekipler = config('isg.acil_durum.ekipler');
    $afisler = config('isg.acil_durum.afisler');
    $sablonlar = config('isg.acil_durum.word_sablonlari');
    $ornekMetinler = config('isg.acil_durum.ornek_metinler', []);
    $mor = 'rgb(139 92 246)';
    $kirmizi = 'rgb(239 68 68)';
    $kutu = 'border:1px solid rgb(107 114 128 / .3);border-radius:.75rem;padding:1rem';
    $alan = 'width:100%;padding:.45rem .6rem;border-radius:.45rem;border:1px solid rgb(107 114 128 / .35);background:transparent;font-size:.82rem';
    $lb = 'display:block;font-size:.78rem;font-weight:600;margin-bottom:.2rem';
    $kucukAlan = $alan.';padding:.3rem .4rem;font-size:.75rem';
    $adimlar = \App\Filament\Pages\AcilDurumPlani::ADIMLAR;
@endphp

<x-filament-panels::page>
    @include('filament.pages.partials.acil-slate-stil')
    <p style="font-size:.85rem;color:rgb(107 114 128);margin-top:-.5rem">
        Acil Durum Eylem Planı 4 adımda hazırlanır. Senaryolar firmanın NACE kodundan, resmî kurum telefonları adresinden
        otomatik gelir; siz yalnız eksik saha bilgilerini (şalter, vana, toplanma yeri…) girersiniz. 6331 SK kapsamında her işyerinde zorunludur.
    </p>

    @include('filament.pages.partials.eksik-firmalar', ['kriterAnahtari' => 'acil_durum_plani'])

    {{-- KAYITLI PLANLAR — varsayılan kapalı akordeon --}}
    @php
        $pf = $this->portfoy;
        $pfSay = ['toplam' => $pf->filter(fn ($r) => $r['kontrol'])->count(), 'hazir' => $pf->filter(fn ($r) => ($r['kontrol']['durum'] ?? null) === 'hazir')->count(),
            'aksiyon' => $pf->filter(fn ($r) => ($r['kontrol']['durum'] ?? null) === 'aksiyon')->count(), 'gozden' => $pf->filter(fn ($r) => ($r['kontrol']['durum'] ?? null) === 'gozden_gecirme')->count()];
    @endphp
    <x-filament::section icon="heroicon-o-folder-open" icon-color="danger" collapsible collapsed>
        <x-slot name="heading">📂 Kayıtlı Planlar ve Hazırlık Durumu ({{ $pf->count() }})</x-slot>
        <x-slot name="description">{{ $pfSay['toplam'] }} plan · {{ $pfSay['hazir'] }} hazır · {{ $pfSay['aksiyon'] }} aksiyon gerekli · {{ $pfSay['gozden'] }} gözden geçirme — açmak için tıklayın</x-slot>
        <x-slot name="afterHeader"><x-filament::button size="sm" color="gray" icon="heroicon-o-table-cells" wire:click="portfoyExcel">Excel dışa aktar</x-filament::button></x-slot>
        <div style="font-size:.74rem;color:rgb(107 114 128);margin-bottom:.6rem">Hazırlık düzeyi hukuki uygunluk beyanı değildir; saha doğrulaması, işveren onayı, ekip eğitimleri ve tatbikat kayıtları ayrıca tamamlanmalıdır.</div>
        <div style="max-height:320px;overflow:auto">
            <table style="width:100%;border-collapse:collapse;font-size:.8rem">
                @foreach ($pf as $r)
                    @php $k = $r['kontrol']; $renk = ['hazir' => 'rgb(21 128 61)', 'aksiyon' => 'rgb(217 119 6)', 'gozden_gecirme' => 'rgb(220 38 38)'][$k['durum'] ?? ''] ?? 'rgb(107 114 128)'; @endphp
                    <tr style="border-top:1px solid rgb(107 114 128 / .12)">
                        <td style="padding:.35rem .4rem">{{ $r['firma']->unvan }}</td>
                        <td style="padding:.35rem .4rem;white-space:nowrap;color:{{ $renk }};font-weight:700">{{ $k ? \App\Support\AcilDurumHazirlik::DURUM_ETIKET[$k['durum']].' · %'.$k['yuzde'] : 'Plan yok' }}</td>
                        <td style="padding:.35rem .4rem;white-space:nowrap;color:rgb(107 114 128)">{{ $r['plan']?->gecerlilik_tarihi ? 'Gözden geçirme '.$r['plan']->gecerlilik_tarihi->format('d.m.Y') : '' }}</td>
                        <td style="padding:.35rem .4rem;text-align:right"><a href="{{ \App\Filament\Pages\AcilDurumPlani::getUrl(['firma' => $r['firma']->id]) }}" style="color:rgb(124 58 237);font-size:.75rem">{{ $k ? 'Aç →' : 'Plan oluştur →' }}</a></td>
                    </tr>
                @endforeach
            </table>
        </div>
    </x-filament::section>

    {{-- ADIM ÇUBUĞU --}}
    @php
        $eksikSoru = $this->firma ? $this->eksikSorular() : [];
        $h = $this->firma ? $this->hazirlik : null;
        $kurumSayisi = collect($this->uygulama['iletisim'] ?? [])->reject(fn ($i) => trim((string) ($i['telefon'] ?? '')) === '112')->count();
        $durumlar = [
            1 => $this->firma ? count($this->konular).' senaryo' : 'firma seçin',
            2 => $this->firma ? ($eksikSoru ? count($eksikSoru).' eksik' : 'tamam ✓') : '',
            3 => $this->firma ? $kurumSayisi.' kurum' : '',
            4 => $h ? '%'.$h['yuzde'] : '',
        ];
    @endphp
    <div style="display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:.4rem" role="tablist">
        @foreach ($adimlar as $no => $ad)
            @php $aktif = $adim === $no; $kilitli = $no > 1 && ! $this->firma; @endphp
            <button type="button" role="tab" aria-selected="{{ $aktif ? 'true' : 'false' }}" wire:click="adimaGit({{ $no }})" @disabled($kilitli)
                style="text-align:left;padding:.6rem .75rem;border-radius:.6rem;cursor:{{ $kilitli ? 'not-allowed' : 'pointer' }};opacity:{{ $kilitli ? .5 : 1 }};
                    border:2px solid {{ $aktif ? '#e11d48' : 'rgb(107 114 128 / .25)' }};background:{{ $aktif ? '#fff1f2' : 'transparent' }}">
                <div style="display:flex;align-items:center;gap:.45rem">
                    <span style="flex:none;width:1.5rem;height:1.5rem;border-radius:9999px;display:grid;place-items:center;font-size:.75rem;font-weight:800;color:#fff;background:{{ $aktif ? '#e11d48' : 'rgb(107 114 128 / .6)' }}">{{ $no }}</span>
                    <span style="font-weight:700;font-size:.82rem;color:{{ $aktif ? '#9f1239' : 'inherit' }}">{{ $ad }}</span>
                </div>
                <div style="font-size:.7rem;color:rgb(107 114 128);margin:.2rem 0 0 1.95rem">{{ $durumlar[$no] }}</div>
            </button>
        @endforeach
    </div>

    {{-- Firma seçilince resmî kurum telefonları arka planda (ayrı istekle) aranır --}}
    @if ($this->firma && $this->iletisimOtomatikGerekli())
        <div wire:key="iletisim-oto-{{ $this->firmaId }}" x-data x-init="$wire.iletisimOtomatikBul()"
            style="display:flex;align-items:center;gap:.5rem;font-size:.78rem;padding:.45rem .7rem;border-radius:.5rem;background:rgb(37 99 235 / .07);border:1px solid rgb(37 99 235 / .25)">
            <x-filament::loading-indicator style="width:1rem;height:1rem" />
            Adrese göre en yakın hastane, itfaiye, emniyet, kaymakamlık, elektrik ve doğalgaz arıza telefonları arka planda aranıyor…
        </div>
    @endif

    {{-- ==================== ADIM 1 — FİRMA & SENARYOLAR ==================== --}}
    @if ($adim === 1)
        <x-filament::section icon="heroicon-o-building-office-2" icon-color="danger">
            <x-slot name="heading">Firma & Rapor Bilgileri</x-slot>

            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:1rem">
                <div>
                    <label style="font-weight:600;font-size:.82rem">Firma Seçin <span style="color:#ef4444">*</span></label>
                    <select wire:model.live="firmaId" style="margin-top:.3rem;{{ $alan }};padding:.55rem .75rem">
                        <option value="">— Firma seçin —</option>
                        @foreach ($this->firmalar as $id => $ad)
                            <option value="{{ $id }}">{{ $ad }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label style="font-weight:600;font-size:.82rem">Rapor Tarihi <span style="color:#ef4444">*</span></label>
                    <input type="date" wire:model="raporTarihi" style="margin-top:.3rem;{{ $alan }}">
                </div>
                <div>
                    <label style="font-weight:600;font-size:.82rem">Geçerlilik / Gözden Geçirme</label>
                    <div style="margin-top:.3rem;{{ $alan }};color:rgb(107 114 128)">{{ $this->plan()?->gecerlilik_tarihi?->format('d.m.Y') ?? 'Kaydedince hesaplanır' }}</div>
                </div>
                <div>
                    <label style="font-weight:600;font-size:.82rem">Doküman No</label>
                    <input type="text" wire:model="dokumanNo" placeholder="Örn: AD-01" style="margin-top:.3rem;{{ $alan }}">
                </div>
                <div>
                    <label style="font-weight:600;font-size:.82rem">Rev. Tarihi / No</label>
                    <input type="text" wire:model="revizyonNo" placeholder="Örn: Rev.01 — 15.03.2027" style="margin-top:.3rem;{{ $alan }}">
                </div>
            </div>

            @if ($this->firma)
                <div style="{{ $kutu }};margin-top:1rem;background:rgb(239 68 68 / .05);border-color:rgb(239 68 68 / .3)">
                    <div style="font-weight:700">{{ $this->firma->unvan }}</div>
                    <div style="display:flex;gap:.5rem;flex-wrap:wrap;margin:.4rem 0">
                        <x-filament::badge color="danger">{{ $this->firma->tehlikeSinifiEtiketi() }}</x-filament::badge>
                        <x-filament::badge color="gray">NACE {{ $this->firma->nace_kodu ?: '—' }}</x-filament::badge>
                        <x-filament::badge color="gray">SGK: {{ $this->firma->sgk_sicil_no ?: '—' }}</x-filament::badge>
                        <x-filament::badge color="gray">{{ $this->firma->calisan_sayisi ?: '—' }} çalışan</x-filament::badge>
                    </div>
                    <div style="font-size:.8rem;color:rgb(107 114 128)">{{ $this->firma->adres ?: 'Adres girilmemiş' }}</div>
                </div>

                @php $sektorOneri = \App\Support\AcilDurumSektor::oner($this->firma); @endphp
                <div style="margin-top:1rem;max-width:520px">
                    <label style="{{ $lb }}">Sektör (plana sektöre özgü önlem ve senaryo metni ekler)</label>
                    <select wire:model="uygulama.sektor" style="{{ $alan }}">
                        @foreach (\App\Support\AcilDurumSektor::liste() as $sk => $skAd)
                            <option value="{{ $sk }}">{{ $skAd }}{{ $sk === $sektorOneri ? ' — NACE önerisi' : '' }}</option>
                        @endforeach
                    </select>
                </div>
            @else
                <p style="margin-top:1rem;font-size:.85rem;color:#f59e0b">Devam etmek için bir firma seçin.</p>
            @endif
        </x-filament::section>

        @if ($this->firma)
            <x-filament::section icon="heroicon-o-exclamation-triangle" icon-color="danger">
                <x-slot name="heading">
                    Acil Durum Senaryoları
                    <span style="font-weight:400;font-size:.8rem;color:rgb(107 114 128)">({{ count($konular) }} konudan {{ count($this->konular) }} seçili)</span>
                </x-slot>
                <x-slot name="description">NACE koduna ve risk analizine göre otomatik işaretlendi — yalnız seçilenler PDF / Word'e (EK-1 ve risk tablosu) girer. 🔒 işaretliler her planda bulunur.</x-slot>

                <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(240px,1fr));gap:.35rem">
                    @php $zorunlu = \App\Support\AcilDurumKonuSecici::zorunlu(); $sira = 0; @endphp
                    @foreach ($konular as $i => $k)
                        @php $secili = in_array($k['anahtar'], $this->konular, true); $kilit = in_array($k['anahtar'], $zorunlu, true); @endphp
                        <button type="button" wire:click="konuToggle('{{ $k['anahtar'] }}')" class="acil-merkez-secenek"
                            style="text-align:left;padding:.55rem .75rem;cursor:pointer;font-size:.8rem;font-weight:{{ $secili ? 600 : 500 }};
                                border:1px solid {{ $secili ? '#e11d48' : '#e2e8f0' }};background:{{ $secili ? '#fff1f2' : '#fff' }};color:{{ $secili ? '#9f1239' : '#334155' }}">
                            {{ $kilit ? '🔒' : ($secili ? '☑' : '☐') }}
                            <span style="display:inline-block;width:1.4rem;text-align:center;color:rgb(107 114 128)">{{ $secili ? ++$sira : '' }}</span>
                            ACİL DURUM: {{ mb_strtoupper($k['ad'], 'UTF-8') }}
                        </button>
                    @endforeach
                </div>
                <div style="margin-top:.6rem;display:flex;gap:.5rem;flex-wrap:wrap">
                    <x-filament::button size="xs" color="danger" icon="heroicon-o-sparkles" wire:click="riskAnalizindenBelirle">Risk Analizinden Otomatik Belirle</x-filament::button>
                    <x-filament::button size="xs" color="gray" wire:click="tumKonular(true)">Tümünü Seç</x-filament::button>
                    <x-filament::button size="xs" color="gray" wire:click="tumKonular(false)">Zorunlular Hariç Kaldır</x-filament::button>
                </div>

                {{-- Özel senaryolar (yapay zekâ) --}}
                @php $ozeller = $this->uygulama['ozel_senaryolar'] ?? []; $kutuphane = $this->ozelKutuphanesi(); @endphp
                <details style="margin-top:.9rem" @if ($ozeller || $ozelPanel) open @endif>
                    <summary style="cursor:pointer;font-size:.8rem;font-weight:700">✨ Listede olmayan özel senaryolar ({{ count($ozeller) }}) <span style="font-weight:400;color:rgb(107 114 128)">— EK-1'de seçili senaryolardan sonra numaralanır</span></summary>
                    @foreach ($ozeller as $oi => $o)
                        <div style="display:flex;justify-content:space-between;align-items:center;gap:.5rem;margin-top:.35rem;padding:.45rem .7rem;border:1px solid #e11d48;background:#fff1f2;color:#9f1239;font-size:.8rem;font-weight:600" wire:key="ozel-{{ $o['anahtar'] }}">
                            <span>✨ <span style="display:inline-block;width:1.4rem;text-align:center;color:rgb(107 114 128)">{{ $sira + $loop->iteration }}</span> ACİL DURUM: {{ mb_strtoupper(str_replace('i', 'İ', $o['ad']), 'UTF-8') }}
                                <span style="font-weight:400;color:rgb(107 114 128);font-size:.72rem">— {{ count($o['mudahale']) }} müdahale, {{ count($o['olay_sonrasi']) }} olay sonrası, {{ count($o['onleyici']) }} önleyici, {{ count($o['kkd']) }} KKD maddesi</span></span>
                            <x-filament::icon-button icon="heroicon-o-trash" color="danger" size="sm" wire:click="ozelSenaryoSil({{ $oi }})" wire:confirm="Bu özel senaryo plandan kaldırılsın mı? (Kütüphanenizde kalır.)" label="Kaldır" />
                        </div>
                    @endforeach

                    @php $kutuphaneEklenebilir = collect($kutuphane)->reject(fn ($k) => collect($ozeller)->pluck('ad')->map(fn ($a) => mb_strtolower($a))->contains(mb_strtolower($k['ad']))); @endphp
                    @if ($kutuphaneEklenebilir->isNotEmpty())
                        <div style="display:flex;flex-wrap:wrap;gap:.35rem;margin-top:.45rem;align-items:center">
                            <span style="font-size:.72rem;color:rgb(107 114 128)">Kütüphanenizden ekle:</span>
                            @foreach ($kutuphaneEklenebilir as $ki => $k)
                                <button type="button" wire:click="kutuphanedenEkle({{ $ki }})" wire:key="kutuphane-{{ $ki }}"
                                    style="padding:.25rem .55rem;border-radius:.4rem;font-size:.72rem;cursor:pointer;border:1px dashed #e11d48;background:#fff;color:#9f1239">+ {{ $k['ad'] }}</button>
                            @endforeach
                        </div>
                    @endif

                    <div style="margin-top:.5rem">
                        @unless ($ozelPanel)
                            <x-filament::button size="xs" color="danger" outlined icon="heroicon-o-sparkles" wire:click="$set('ozelPanel', true)">✨ Listede Olmayan Özel Senaryo Ekle (Yapay Zekâ)</x-filament::button>
                        @else
                            @php $oAlan = 'width:100%;padding:.4rem .55rem;border-radius:.4rem;border:1px solid rgb(107 114 128 / .35);background:transparent;font-size:.78rem'; @endphp
                            <div style="border:1px solid #fda4af;border-radius:.6rem;padding:.75rem .85rem;background:rgb(255 241 242 / .5)">
                                <div style="font-size:.82rem;font-weight:700;margin-bottom:.35rem">✨ Özel senaryo üret</div>
                                <div style="display:flex;gap:.5rem;flex-wrap:wrap">
                                    <input type="text" wire:model="ozelBaslik" wire:keydown.enter="ozelSenaryoUret" placeholder="Örn: COVID-19 Benzeri Solunum Yolu Salgını · Amonyak Tankı Sızıntısı · Lityum İyon Batarya Termal Kaçağı" style="{{ $oAlan }};flex:1;min-width:260px">
                                    <x-filament::button size="sm" color="danger" icon="heroicon-o-sparkles" wire:click="ozelSenaryoUret" wire:loading.attr="disabled" wire:target="ozelSenaryoUret">
                                        <span wire:loading.remove wire:target="ozelSenaryoUret">Yapay Zekâ ile Üret</span>
                                        <span wire:loading wire:target="ozelSenaryoUret">Üretiliyor…</span>
                                    </x-filament::button>
                                    <x-filament::button size="sm" color="gray" wire:click="$set('ozelPanel', false)">Kapat</x-filament::button>
                                </div>

                                @if ($ozelOnizleme)
                                    <div style="margin-top:.7rem;font-size:.74rem;color:rgb(107 114 128)">Önizleme — maddeleri okuyup gerekirse düzeltin (her satır bir madde). Onaylamadan plana hiçbir şey eklenmez.</div>
                                    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(300px,1fr));gap:.55rem;margin-top:.4rem">
                                        <div><label style="font-size:.74rem;font-weight:600">Başlık</label><input type="text" wire:model="ozelOnizleme.ad" style="{{ $oAlan }}"></div>
                                        <div style="grid-column:1/-1"><label style="font-size:.74rem;font-weight:600">Tanım</label><textarea wire:model="ozelOnizleme.aciklama" rows="3" style="{{ $oAlan }}"></textarea></div>
                                        @foreach (\App\Support\GeminiAcilSenaryoUretici::LISTELER as $lk => $lad)
                                            <div><label style="font-size:.74rem;font-weight:600">{{ $lad }}</label><textarea wire:model="ozelOnizleme.{{ $lk }}" rows="7" style="{{ $oAlan }}"></textarea></div>
                                        @endforeach
                                    </div>
                                    <div style="margin-top:.6rem;display:flex;gap:.8rem;align-items:center;flex-wrap:wrap">
                                        <x-filament::button size="sm" icon="heroicon-o-check" wire:click="ozelSenaryoOnayla">Onayla ve Plana Ekle</x-filament::button>
                                        <label style="display:flex;gap:.35rem;align-items:center;font-size:.76rem"><input type="checkbox" wire:model="ozelKutuphaneye"> Senaryo kütüphaneme de kaydet (diğer firmalarda tek tıkla eklenir)</label>
                                        <x-filament::button size="sm" color="gray" wire:click="$set('ozelOnizleme', null)">Vazgeç</x-filament::button>
                                    </div>
                                @endif
                            </div>
                        @endunless
                    </div>
                </details>

                @php $kkdSecili = $this->kkdFaaliyetleri(); @endphp
                <details style="margin-top:.7rem">
                    <summary style="cursor:pointer;font-size:.8rem;font-weight:700">EK-3 KKD tablosu — işyerindeki faaliyetler ({{ count($kkdSecili) }} seçili)</summary>
                    <div style="display:flex;flex-wrap:wrap;gap:.35rem;margin-top:.35rem">
                        @foreach (config('isg.acil_durum.kkd_matrisi.faaliyetler') as $f)
                            @php $on = in_array($f, $kkdSecili, true); @endphp
                            <button type="button" wire:click="kkdFaaliyetToggle(@js($f))" wire:key="kkd-{{ $loop->index }}"
                                style="padding:.3rem .55rem;border-radius:.4rem;font-size:.72rem;cursor:pointer;border:1px solid {{ $on ? '#e11d48' : '#e2e8f0' }};background:{{ $on ? '#fff1f2' : '#fff' }};color:{{ $on ? '#9f1239' : '#64748b' }}">{{ $on ? '☑' : '☐' }} {{ $f }}</button>
                        @endforeach
                    </div>
                </details>
            </x-filament::section>
        @endif
    @endif

    {{-- ==================== ADIM 2 — SAHA BİLGİLERİ ==================== --}}
    @if ($adim === 2 && $this->firma)
        @php
            $sorular = \App\Support\AcilDurumHazirlik::SIHIRBAZ_SORULARI;
            $kartlar = [
                ['📍 Fiziksel Konumlar', 'heroicon-o-map-pin', ['toplanma_yeri', 'yedek_toplanma', 'elektrik_salteri', 'gaz_vanasi', 'kroki_kat', 'kroki_hazirlayan']],
                ['👤 Yetkililer', 'heroicon-o-user-group', ['koordinator_ad', 'koordinator_tel', 'koordinator_vekil', 'koordinator_vekil_tel', 'refakatci', 'tatbikat_sorumlusu']],
                ['🏢 Bina Özellikleri', 'heroicon-o-building-office', ['bina_bilgisi', 'vardiyalar', 'alarm_yontemi', 'kayit_yedekleme']],
            ];
        @endphp
        <p style="font-size:.8rem;color:rgb(107 114 128);margin:0">○ boş alan belgede noktalı (elle doldurulacak) satır olarak çıkar · ✓ dolu. Hastane bilgisi 3. adımda adrese göre otomatik gelir.</p>
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(320px,1fr));gap:1rem;align-items:start">
            @foreach ($kartlar as [$baslik, $ikon, $alanlar])
                @php $kartEksik = count(array_intersect($alanlar, array_keys($eksikSoru))); @endphp
                <x-filament::section :icon="$ikon" icon-color="danger">
                    <x-slot name="heading">{{ $baslik }}</x-slot>
                    <x-slot name="description"><span style="color:{{ $kartEksik ? 'rgb(217 119 6)' : 'rgb(22 163 74)' }}">{{ count($alanlar) - $kartEksik }} / {{ count($alanlar) }} tamam</span></x-slot>
                    <div style="display:flex;flex-direction:column;gap:.6rem">
                        @foreach ($alanlar as $alanAd)
                            @php [$soru, $ornek] = $sorular[$alanAd]; $bos = array_key_exists($alanAd, $eksikSoru); $model = $alanAd === 'toplanma_yeri' ? 'toplanmaYeri' : 'uygulama.'.$alanAd; @endphp
                            <div wire:key="soru-{{ $alanAd }}">
                                <label style="display:block;font-size:.76rem;font-weight:600;margin-bottom:.2rem;color:{{ $bos ? 'rgb(180 83 9)' : 'rgb(21 128 61)' }}">{{ $bos ? '○' : '✓' }} {{ $soru }}</label>
                                @if (in_array($alanAd, ['koordinator_ad', 'koordinator_vekil'], true))
                                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:.35rem">
                                        <select style="{{ $alan }}" wire:change="koordinatorSec('{{ $alanAd === 'koordinator_ad' ? 'koordinator' : 'koordinator_vekil' }}', $event.target.value)">
                                            <option value="">{{ $this->calisanSecenekleri->isEmpty() ? 'Çalışan kaydı yok' : 'Çalışanlardan seç…' }}</option>
                                            @foreach ($this->calisanSecenekleri as $c)
                                                <option value="{{ $c->id }}">{{ $c->ad_soyad }}{{ $c->gorev ? ' — '.$c->gorev : '' }}</option>
                                            @endforeach
                                        </select>
                                        <input type="text" wire:model="{{ $model }}" placeholder="veya elle yazın" style="{{ $alan }}">
                                    </div>
                                @else
                                    <input type="text" wire:model="{{ $model }}" placeholder="Örn: {{ $ornek }}" style="{{ $alan }}">
                                @endif
                            </div>
                        @endforeach

                        @if ($baslik === '📍 Fiziksel Konumlar')
                            <div>
                                <label style="{{ $lb }}">İşyerini dışarıdan etkileyebilecek işyerleri</label>
                                <textarea wire:model="disaridanEtkileyebilecekIsyerleri" rows="2" placeholder="Her satıra bir işyeri: unvan — faaliyet — olası etki" style="{{ $alan }}"></textarea>
                            </div>
                        @elseif ($baslik === '👤 Yetkililer')
                            <div style="display:grid;grid-template-columns:1fr 1fr;gap:.4rem">
                                <div><label style="{{ $lb }}">İşveren telefonu</label><input type="text" wire:model="uygulama.isveren_tel" placeholder="Boşsa firma telefonu" style="{{ $alan }}"></div>
                                <div><label style="{{ $lb }}">İşveren vekili telefonu</label><input type="text" wire:model="uygulama.isveren_vekili_tel" style="{{ $alan }}"></div>
                            </div>
                        @else
                            <div style="display:flex;flex-direction:column;gap:.35rem">
                                @foreach (['ziyaretci_dahil' => 'Ziyaretçiler dahil', 'gecici_calisan_dahil' => 'Geçici çalışanlar dahil', 'coklu_isveren' => 'Birden fazla işveren / ortak saha'] as $anahtar => $ad)
                                    <label style="display:flex;gap:.4rem;align-items:center;font-size:.8rem"><input type="checkbox" wire:model="uygulama.{{ $anahtar }}"> {{ $ad }}</label>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </x-filament::section>
            @endforeach
        </div>

        <x-filament::section icon="heroicon-o-users" icon-color="danger" collapsible collapsed>
            <x-slot name="heading">Destek Elemanı Atamaları</x-slot>
            <x-slot name="description">İsimleri virgülle ayırın. Acil Durum Ekipleri sayfasında üyesi girilen ekip plana oradan gelir ve buradakinin yerine geçer.</x-slot>
            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:.6rem">
                @foreach ($ekipler as $anahtar => $ad)
                    @php $sayac = $this->ekipSayaclari()[$anahtar] ?? null; @endphp
                    <div>
                        <label style="font-size:.78rem;color:rgb(107 114 128)">{{ $ad }}</label>
                        @if ($sayac)
                            <span style="margin-left:.3rem;font-size:.7rem;font-weight:600;color:{{ $sayac['eksik'] ? 'rgb(225 29 72)' : 'rgb(5 150 105)' }}">Gereken {{ $sayac['gereken'] }} | Atanan {{ $sayac['atanan'] }}{{ $sayac['eksik'] ? ' (⚠ '.$sayac['eksik'].' eksik)' : ' ✓' }}</span>
                        @endif
                        <input type="text" wire:model="ekipMetni.{{ $anahtar }}" placeholder="Ad Soyad, Ad Soyad" style="margin-top:.2rem;{{ $alan }}">
                    </div>
                @endforeach
            </div>
            <a href="{{ \App\Filament\Pages\AcilDurumEkipleri::getUrl(['firma' => $this->firmaId]) }}" style="display:inline-block;margin-top:.5rem;color:rgb(13 148 136);font-weight:600;font-size:.78rem">Acil Durum Ekipleri sayfasına git →</a>
        </x-filament::section>
    @endif

    {{-- ==================== ADIM 3 — MÜDAHALE & İLETİŞİM ==================== --}}
    @if ($adim === 3 && $this->firma)
        @php $sAlan = $alan; @endphp
        <x-filament::section icon="heroicon-o-phone" icon-color="danger">
            <x-slot name="heading">Acil İletişim ve Resmî Kurumlar (EK-2)</x-slot>
            <x-slot name="description">Adres: {{ $this->firma->adres ?: 'girilmemiş — Firmalar ekranından açık adresi (ilçe / il) doldurun' }}</x-slot>
            <x-slot name="afterHeader">
                <x-filament::button size="sm" color="danger" icon="heroicon-o-sparkles" wire:click="iletisimBul" wire:loading.attr="disabled" wire:target="iletisimBul,iletisimOtomatikBul" :disabled="blank($this->firma->adres)">
                    <span wire:loading.remove wire:target="iletisimBul,iletisimOtomatikBul">Adrese Göre Kurumları Bul (Yapay Zekâ)</span>
                    <span wire:loading wire:target="iletisimBul,iletisimOtomatikBul">Aranıyor…</span>
                </x-filament::button>
            </x-slot>

            @if ($iletisimOnerileri)
                <div style="padding:.45rem .6rem;border-radius:.4rem;background:rgb(217 119 6 / .1);font-size:.74rem">
                    ⚠ Telefonu bulunamayan kurumlar — numarayı girip işaretleyin. Telefonlar {{ $iletisimAramali ? 'web aramasıyla' : 'yapay zekânın kendi bilgisinden' }} geldi; acil çağrı her durumda 112'dir.
                </div>
                <div style="display:grid;gap:.35rem;margin-top:.5rem">
                    @foreach ($iletisimOnerileri as $i => $o)
                        <div style="display:grid;grid-template-columns:auto 170px 2fr 1fr 1.4fr;gap:.4rem;align-items:center;font-size:.75rem" wire:key="oneri-{{ $i }}">
                            <input type="checkbox" wire:model="iletisimOnerileri.{{ $i }}.sec">
                            <b style="font-size:.7rem">{{ $o['birim'] }}</b>
                            <input type="text" wire:model="iletisimOnerileri.{{ $i }}.ad" style="{{ $sAlan }}">
                            <input type="text" wire:model="iletisimOnerileri.{{ $i }}.telefon" placeholder="Telefon (bulunamadı)" style="{{ $sAlan }}">
                            <input type="text" wire:model="iletisimOnerileri.{{ $i }}.not" placeholder="Not" style="{{ $sAlan }}">
                        </div>
                    @endforeach
                </div>
                <div style="margin:.55rem 0 .8rem;display:flex;gap:.5rem;flex-wrap:wrap">
                    <x-filament::button size="sm" icon="heroicon-o-check" wire:click="iletisimOnerileriniEkle">Seçilenleri EK-2'ye Ekle ve Kaydet</x-filament::button>
                    <x-filament::button size="sm" color="gray" wire:click="$set('iletisimOnerileri', [])">Vazgeç</x-filament::button>
                </div>
            @endif

            <div style="display:grid;grid-template-columns:2fr 1fr;gap:.4rem;margin-bottom:.6rem">
                <div><label style="{{ $lb }}">En yakın hastane</label><input type="text" wire:model="uygulama.hastane_ad" placeholder="Adrese göre bulucu doldurur" style="{{ $alan }}"></div>
                <div><label style="{{ $lb }}">Hastane telefonu</label><input type="text" wire:model="uygulama.hastane_tel" style="{{ $alan }}"></div>
            </div>
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:.4rem">
                <span style="font-size:.8rem;font-weight:700">Acil iletişim listesi</span>
                <x-filament::button size="xs" color="gray" icon="heroicon-o-plus" wire:click="iletisimEkle">İrtibat ekle</x-filament::button>
            </div>
            @foreach ($this->uygulama['iletisim'] ?? [] as $i => $irtibat)
                <div style="display:grid;grid-template-columns:2fr 1fr 2fr auto;gap:.4rem;margin-bottom:.35rem" wire:key="irtibat-{{ $i }}">
                    <input type="text" wire:model="uygulama.iletisim.{{ $i }}.ad" placeholder="Kurum / kişi" style="{{ $alan }}">
                    <input type="text" wire:model="uygulama.iletisim.{{ $i }}.telefon" placeholder="Telefon" style="{{ $alan }}">
                    <input type="text" wire:model="uygulama.iletisim.{{ $i }}.aciklama" placeholder="Açıklama" style="{{ $alan }}">
                    <x-filament::icon-button icon="heroicon-o-x-mark" color="danger" wire:click="iletisimSil({{ $i }})" />
                </div>
            @endforeach
        </x-filament::section>

        {{-- Standart metinler — tek satır özet, tıklayınca açılır --}}
        <x-filament::section icon="heroicon-o-document-text" icon-color="danger">
            <x-slot name="heading">Müdahale ve Tedbir Metinleri</x-slot>
            <x-slot name="description">Boş kutular hazır mevzuat metniyle gelir. Değiştirmek istediğiniz başlığa tıklayın.</x-slot>
            <div style="display:flex;flex-direction:column;gap:.4rem">
                @foreach ([
                    'onleyici_tedbirler' => ['Önleyici ve sınırlandırıcı tedbirler', 8],
                    'mudahale_yontemi' => ['Müdahale, haberleşme ve tahliye yöntemi', 7],
                    'ekipman_kkd' => ['Acil durum ekipmanı ve KKD listesi', 5],
                    'olcum_notu' => ['Ölçüm ve değerlendirme notu', 5],
                    'ozel_destek' => ['Özel desteğe ihtiyaç duyan kişiler için yöntem', 3],
                ] as $metinAlani => [$baslik, $satir])
                    @php
                        $deger = trim((string) ($this->uygulama[$metinAlani] ?? ''));
                        $ornekMi = $deger !== '' && $deger === trim((string) ($ornekMetinler[$metinAlani] ?? ''));
                    @endphp
                    <div x-data="{ acik: false }" wire:key="metin-{{ $metinAlani }}" style="border:1px solid rgb(107 114 128 / .25);border-radius:.55rem">
                        <button type="button" x-on:click="acik = ! acik" style="width:100%;display:flex;align-items:center;gap:.6rem;padding:.55rem .75rem;background:none;border:none;cursor:pointer;text-align:left">
                            <span x-text="acik ? '▾' : '▸'" style="color:rgb(107 114 128)">▸</span>
                            <span style="font-size:.82rem;font-weight:600;flex:none">{{ $baslik }}</span>
                            @if ($deger === '')
                                <x-filament::badge color="warning" size="sm">Boş</x-filament::badge>
                            @elseif ($ornekMi)
                                <x-filament::badge color="success" size="sm">✓ Örnek mevzuat metni uygulandı</x-filament::badge>
                            @else
                                <x-filament::badge color="info" size="sm">Düzenlendi</x-filament::badge>
                            @endif
                            <span x-show="! acik" style="flex:1;min-width:0;font-size:.74rem;color:rgb(107 114 128);white-space:nowrap;overflow:hidden;text-overflow:ellipsis">{{ \Illuminate\Support\Str::limit(preg_replace('/\s+/', ' ', $deger), 140) }}</span>
                        </button>
                        <div x-show="acik" x-cloak style="padding:0 .75rem .7rem">
                            <textarea wire:model="uygulama.{{ $metinAlani }}" rows="{{ $satir }}" style="{{ $alan }}"></textarea>
                            <button type="button" wire:click="ornekMetin('{{ $metinAlani }}')" wire:confirm="Kutudaki metin örnek metinle değiştirilecek. Devam edilsin mi?" style="margin-top:.25rem;font-size:.7rem;color:rgb(217 119 6);text-decoration:underline;background:none;border:none;cursor:pointer">Örnek metni geri getir</button>
                        </div>
                    </div>
                @endforeach
            </div>

            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:.6rem;margin-top:.8rem">
                @foreach ([
                    'ozel_risk_alanlari' => ['Özel risk alanları', 'ozel_risk_notu', 'Kimyasal yayılım, parlama / patlama riski olan bölümler — örn. boya deposu, LPG tüp alanı (Md.12/1-f-6)'],
                    'enerji_kesme' => ['Enerji kesme / vana noktaları', 'enerji_kesme_notu', 'Ana elektrik şalteri ve doğalgaz / LPG vanalarının yeri — örn. giriş sağı ana pano (Md.12/1-f-7)'],
                ] as $anahtar => [$ad, $notAnahtar, $ipucu])
                    <div style="{{ $kutu }};padding:.6rem .8rem"><span style="{{ $lb }}">{{ $ad }}</span>
                        <div style="display:flex;gap:.8rem;flex-wrap:wrap;font-size:.8rem">
                            @foreach (\App\Support\AcilDurumHazirlik::UC_DURUM as $k => $v)
                                <label style="display:flex;gap:.3rem;align-items:center"><input type="radio" wire:model.live="uygulama.{{ $anahtar }}" value="{{ $k }}"> {{ $v }}</label>
                            @endforeach
                        </div>
                        @if (($uygulama[$anahtar] ?? null) === 'var')
                            <textarea wire:model="uygulama.{{ $notAnahtar }}" rows="2" style="{{ $alan }};margin-top:.4rem" placeholder="{{ $ipucu }}"></textarea>
                        @endif
                    </div>
                @endforeach
            </div>
        </x-filament::section>

        <x-filament::section icon="heroicon-o-table-cells" icon-color="danger" collapsible collapsed>
            <x-slot name="heading">Risk Puanları ve Çevresel Riskler</x-slot>
            <x-slot name="description">Olası acil durumların 1-5 puanları ve komşu tesis / çevresel riskler (Md.8, Md.10/7).</x-slot>
            @if ($puanlarSablondan)
                <div style="margin-bottom:.4rem;padding:.4rem .6rem;border-radius:.4rem;background:rgb(37 99 235 / .08);font-size:.75rem">Bu firmaya puan girilmemişti; puanlar ve çevresel riskler <b>varsayılanınızdan</b> getirildi.</div>
            @endif
            <div style="overflow-x:auto">
                <table style="width:100%;border-collapse:collapse;font-size:.75rem">
                    <thead><tr>
                        <th style="text-align:left;padding:.25rem">Risk</th>
                        @foreach (config('isg.acil_durum.risk_kolonlari') as $kolon)
                            <th style="padding:.25rem;font-weight:600">{{ $kolon }}</th>
                        @endforeach
                    </tr></thead>
                    <tbody>
                        @foreach (\App\Support\AcilDurumKonuSecici::riskSatirlari($this->konular) as $r => $riskAd)
                            <tr style="border-top:1px solid rgb(107 114 128 / .2)">
                                <td style="padding:.2rem .25rem;white-space:nowrap">{{ $riskAd }}</td>
                                @foreach (array_keys(config('isg.acil_durum.risk_kolonlari')) as $k)
                                    <td style="padding:.15rem"><select wire:model="uygulama.risk_puanlari.{{ $r }}.{{ $k }}" style="{{ $kucukAlan }}"><option value="">—</option>@for ($p = 1; $p <= 5; $p++)<option value="{{ $p }}">{{ $p }}</option>@endfor</select></td>
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div style="display:grid;gap:.35rem;margin-top:.8rem">
                @foreach (config('isg.acil_durum.cevresel_riskler') as $i => $cr)
                    <div style="display:grid;grid-template-columns:minmax(180px,2fr) 90px 3fr;gap:.4rem;align-items:center;font-size:.75rem" wire:key="cevre-{{ $i }}">
                        <span>{{ $cr['ad'] }}</span>
                        <select wire:model="uygulama.cevresel_riskler.{{ $i }}.durum" style="{{ $kucukAlan }}"><option value="">—</option><option value="var">VAR</option><option value="yok">YOK</option></select>
                        <input type="text" wire:model="uygulama.cevresel_riskler.{{ $i }}.not" placeholder="Konum / tedbir / sorumlu" style="{{ $kucukAlan }}">
                    </div>
                @endforeach
            </div>
            <div style="margin-top:.7rem">
                <x-filament::button size="sm" color="gray" icon="heroicon-o-document-duplicate" wire:click="puanlariVarsayilanYap" wire:confirm="Bu risk puanları ve çevresel riskler, puanı girilmemiş tüm firmalarınızda otomatik gelsin mi? Puanı kayıtlı planlar değişmez.">Bu puanları tüm firmalarım için varsayılan yap</x-filament::button>
            </div>
        </x-filament::section>
    @endif

    {{-- ==================== ADIM 4 — ONAY & ÇIKTI ==================== --}}
    @if ($adim === 4 && $this->firma)
        <x-filament::section icon="heroicon-o-arrow-down-tray" icon-color="danger">
            <x-slot name="heading">Çıktı ve Gönderim</x-slot>
            <div style="display:flex;gap:.5rem;flex-wrap:wrap">
                <x-filament::button color="danger" icon="heroicon-o-document-arrow-down" wire:click="mountAction('pdf')">Plan Çıktısı (PDF / Word)</x-filament::button>
                <x-filament::button color="success" icon="heroicon-o-chat-bubble-left-right" wire:click="mountAction('whatsapp')">📲 WhatsApp ile İşverene Gönder</x-filament::button>
                <x-filament::button color="gray" icon="heroicon-o-document" wire:click="mountAction('word')">Word (seçili şablon)</x-filament::button>
                <x-filament::button color="gray" icon="heroicon-o-map" tag="a" :href="\App\Filament\Pages\AcilDurumKrokisi::getUrl(['firma' => $this->firmaId])">Kroki Planı</x-filament::button>
            </div>
        </x-filament::section>

        @if ($h)
            <x-filament::section icon="heroicon-o-check-badge" icon-color="danger">
                <x-slot name="heading">Hazırlık kontrolü — %{{ $h['yuzde'] }} · {{ \App\Support\AcilDurumHazirlik::DURUM_ETIKET[$h['durum']] }}</x-slot>
                <x-slot name="description">Kaydedilmiş verilere göre; değişikliklerden sonra Kaydet'e basın.</x-slot>
                <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(300px,1fr));gap:.4rem">
                    @foreach ($h['kontroller'] as $k)
                        <div style="display:flex;gap:.5rem;align-items:flex-start;padding:.45rem .6rem;border-radius:.45rem;border:1px solid {{ $k['tamam'] ? 'rgb(21 128 61 / .35)' : 'rgb(217 119 6 / .45)' }};background:{{ $k['tamam'] ? 'rgb(21 128 61 / .05)' : 'rgb(217 119 6 / .06)' }}">
                            <span style="font-weight:800;color:{{ $k['tamam'] ? 'rgb(21 128 61)' : 'rgb(217 119 6)' }}">{{ $k['tamam'] ? '✓' : '!' }}</span>
                            <span><span style="font-size:.8rem;font-weight:600">{{ $k['baslik'] }}</span><br><span style="font-size:.72rem;color:rgb(107 114 128)">{{ $k['aciklama'] }}</span></span>
                        </div>
                    @endforeach
                </div>
            </x-filament::section>
        @endif

        <x-filament::section icon="heroicon-o-clipboard-document-check" icon-color="danger">
            <x-slot name="heading">Yayın, Onay ve Tatbikat</x-slot>
            <x-slot name="description">Tatbikat modülünde "tamamlandı" kaydı varsa sistem onu öncelikli kaynak kabul eder.</x-slot>
            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:.6rem">
                <div><label style="{{ $lb }}">Onay / imza durumu</label>
                    <select wire:model="uygulama.onay_durumu" style="{{ $alan }}">@foreach (\App\Support\AcilDurumHazirlik::ONAY as $k => $v)<option value="{{ $k }}">{{ $v }}</option>@endforeach</select></div>
                <div><label style="{{ $lb }}">Son tatbikat (manuel kayıt)</label><input type="date" wire:model="uygulama.son_tatbikat" style="{{ $alan }}"></div>
                <div><label style="{{ $lb }}">Planlanan sonraki tatbikat</label><input type="date" wire:model="uygulama.sonraki_tatbikat" style="{{ $alan }}"></div>
                <div><label style="{{ $lb }}">Tutanak / kayıt referansı</label><input type="text" wire:model="uygulama.tutanak_ref" placeholder="Örn. TAT-2026-004" style="{{ $alan }}"></div>
            </div>
            <div style="display:flex;gap:1.2rem;flex-wrap:wrap;margin-top:.6rem;font-size:.8rem">
                <label style="display:flex;gap:.35rem;align-items:center"><input type="checkbox" wire:model="uygulama.kroki_asildi"> Krokiler görünür yerlere asıldı</label>
                <label style="display:flex;gap:.35rem;align-items:center"><input type="checkbox" wire:model="uygulama.calisan_bilgilendirildi"> Çalışan bilgilendirmesi tamamlandı</label>
            </div>
            <div style="margin-top:.6rem"><label style="{{ $lb }}">Ek not</label>
                <textarea wire:model="uygulama.ek_not" rows="2" style="{{ $alan }}" placeholder="Planın saha uygulamasına ilişkin ek notlar"></textarea></div>
        </x-filament::section>

        <x-filament::section icon="heroicon-o-swatch" icon-color="danger" collapsible collapsed>
            <x-slot name="heading">Kapak Çerçevesi ve Şablon</x-slot>
            <x-slot name="description">Seçili: {{ \Illuminate\Support\Str::before($cerceveler[$kapakCercevesi] ?? $kapakCercevesi, ' — ') }} · {{ $sablonlar[$sablonId]['ad'] ?? $sablonId }}</x-slot>
            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:.5rem">
                @foreach ($cerceveler as $anahtar => $ad)
                    @php $secili = $kapakCercevesi === $anahtar; @endphp
                    <button type="button" wire:click="$set('kapakCercevesi','{{ $anahtar }}')"
                        style="padding:.6rem;border-radius:.5rem;cursor:pointer;font-size:.8rem;border:2px solid {{ $secili ? $mor : 'rgb(107 114 128 / .3)' }};background:{{ $secili ? 'rgb(139 92 246 / .08)' : 'transparent' }}">
                        <div style="font-weight:600">{{ Str::before($ad, ' — ') }}</div>
                        <div style="font-size:.7rem;color:rgb(107 114 128)">{{ Str::after($ad, ' — ') }}</div>
                    </button>
                @endforeach
            </div>
            <div style="margin-top:.8rem;font-size:.8rem;font-weight:700">Word / kapak şablonu</div>
            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:.5rem;margin-top:.35rem">
                @foreach ($sablonlar as $anahtar => $s)
                    @php $secili = $sablonId === $anahtar; @endphp
                    <button type="button" wire:click="$set('sablonId','{{ $anahtar }}')"
                        style="padding:.6rem;border-radius:.5rem;cursor:pointer;font-size:.8rem;text-align:left;border:2px solid {{ $secili ? $kirmizi : 'rgb(107 114 128 / .3)' }};background:{{ $secili ? 'rgb(239 68 68 / .1)' : 'transparent' }}">
                        <div style="font-weight:600">{{ $s['ad'] }}</div>
                        <div style="font-size:.7rem;color:rgb(107 114 128)">{{ $s['aciklama'] }}</div>
                    </button>
                @endforeach
            </div>
        </x-filament::section>

        <x-filament::section icon="heroicon-o-printer" icon-color="danger" collapsible collapsed>
            <x-slot name="heading">Acil Durum Afişleri ({{ count($this->afisTipleri) }} firmaya uygun)</x-slot>
            <x-slot name="description">A3 / A4 talimat afişleri — ✓ işaretliler firmaya otomatik tanımlı</x-slot>
            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:.5rem">
                @foreach ($afisler as $anahtar => $afis)
                    @php $secili = $afisTipi === $anahtar; $tanimli = in_array($anahtar, $this->afisTipleri, true); @endphp
                    <button type="button" wire:click="$set('afisTipi','{{ $anahtar }}')"
                        style="position:relative;padding:.6rem;border-radius:.5rem;cursor:pointer;font-size:.8rem;text-align:center;border:2px solid {{ $secili ? $kirmizi : 'rgb(107 114 128 / .3)' }};background:{{ $secili ? 'rgb(239 68 68 / .1)' : 'transparent' }};opacity:{{ $tanimli ? 1 : .55 }}">
                        @if ($tanimli)
                            <span style="position:absolute;top:2px;right:4px;color:#16a34a;font-size:.75rem" title="Firmaya otomatik tanımlı">✓</span>
                        @endif
                        ⚠ {{ $afis['ad'] }}
                    </button>
                @endforeach
            </div>
            <div style="display:flex;align-items:center;gap:1rem;margin-top:.85rem;flex-wrap:wrap">
                <div style="display:flex;gap:.3rem">
                    @foreach (['a4' => 'A4 Boyutu', 'a3' => 'A3 Boyutu'] as $e => $et)
                        <button type="button" wire:click="$set('afisEbat','{{ $e }}')"
                            style="padding:.35rem .7rem;border-radius:.4rem;cursor:pointer;font-size:.8rem;border:1px solid {{ $afisEbat === $e ? $kirmizi : 'rgb(107 114 128 / .3)' }};background:{{ $afisEbat === $e ? 'rgb(239 68 68 / .1)' : 'transparent' }}">{{ $et }}</button>
                    @endforeach
                </div>
                <x-filament::button color="danger" icon="heroicon-o-arrow-down-tray" wire:click="afisIndir">Afişi İndir ({{ strtoupper($afisEbat) }})</x-filament::button>
                <x-filament::button color="gray" icon="heroicon-o-archive-box-arrow-down" wire:click="afislerZipIndir">Tüm Afişleri İndir — {{ count($this->afisTipleri) }} adet (ZIP, {{ strtoupper($afisEbat) }})</x-filament::button>
            </div>
            <div style="{{ $kutu }};margin-top:.85rem">
                <div style="font-weight:700;color:{{ $kirmizi }};font-size:.9rem">{{ $afisler[$afisTipi]['baslik'] }}</div>
                <ol style="margin:.5rem 0 0;padding-left:1.2rem;font-size:.82rem;display:flex;flex-direction:column;gap:.25rem">
                    @foreach ($afisler[$afisTipi]['adimlar'] as $afisAdim) <li>{{ $afisAdim }}</li> @endforeach
                </ol>
            </div>
        </x-filament::section>
    @endif

    {{-- ADIM GEZİNME --}}
    @if ($this->firma)
        <div style="display:flex;justify-content:space-between;align-items:center;gap:.5rem;flex-wrap:wrap">
            <div>
                @if ($adim > 1)
                    <x-filament::button color="gray" icon="heroicon-o-chevron-left" wire:click="adimaGit({{ $adim - 1 }})">{{ $adimlar[$adim - 1] }}</x-filament::button>
                @endif
            </div>
            <div style="display:flex;gap:.5rem">
                <x-filament::button color="gray" icon="heroicon-o-check" wire:click="kaydet">Kaydet</x-filament::button>
                @if ($adim < 4)
                    <x-filament::button icon="heroicon-o-chevron-right" icon-position="after" wire:click="kaydetVeIlerle">Kaydet ve İleri: {{ $adimlar[$adim + 1] }}</x-filament::button>
                @endif
            </div>
        </div>
    @endif

    <div style="{{ $kutu }};background:rgb(245 158 11 / .06);border-color:rgb(245 158 11 / .3);font-size:.82rem">
        <strong>⚠ Acil Durum Eylem Planı Hakkında</strong><br>
        {{ config('isg.acil_durum.hakkinda') }}
    </div>
</x-filament-panels::page>
