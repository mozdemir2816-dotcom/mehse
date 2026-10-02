@php
    $konular = config('isg.acil_durum.konular');
    $cerceveler = config('isg.acil_durum.kapak_cerceveleri');
    $ekipler = config('isg.acil_durum.ekipler');
    $afisler = config('isg.acil_durum.afisler');
    $sablonlar = config('isg.acil_durum.word_sablonlari');
    $mor = 'rgb(139 92 246)';
    $kirmizi = 'rgb(239 68 68)';
    $kutu = 'border:1px solid rgb(107 114 128 / .3);border-radius:.75rem;padding:1rem';
@endphp

<x-filament-panels::page>
    <p style="font-size:.85rem;color:rgb(107 114 128);margin-top:-.5rem">
        Firma bilgilerini seçin, dahil edilecek acil durum konularını işaretleyin ve
        Acil Durum Eylem Planı PDF’ini oluşturun. 6331 SK kapsamında her işyerinde zorunludur.
    </p>

    @include('filament.pages.partials.eksik-firmalar', ['kriterAnahtari' => 'acil_durum_plani'])

    {{-- PLAN PORTFÖYÜ (isgsuite "İşyerlerinizin acil durum hazırlığı") --}}
    @php
        $pf = $this->portfoy;
        $pfSay = ['toplam' => $pf->filter(fn ($r) => $r['kontrol'])->count(), 'hazir' => $pf->filter(fn ($r) => ($r['kontrol']['durum'] ?? null) === 'hazir')->count(),
            'aksiyon' => $pf->filter(fn ($r) => ($r['kontrol']['durum'] ?? null) === 'aksiyon')->count(), 'gozden' => $pf->filter(fn ($r) => ($r['kontrol']['durum'] ?? null) === 'gozden_gecirme')->count()];
    @endphp
    <x-filament::section icon="heroicon-o-shield-check" icon-color="danger" collapsible :collapsed="(bool) $this->firma">
        <x-slot name="heading">İşyerlerinizin acil durum hazırlığı</x-slot>
        <x-slot name="description">Hazırlık düzeyi hukuki uygunluk beyanı değildir; saha doğrulaması, işveren onayı, ekip eğitimleri ve tatbikat kayıtları ayrıca tamamlanmalıdır.</x-slot>
        <x-slot name="afterHeader"><x-filament::button size="sm" color="gray" icon="heroicon-o-table-cells" wire:click="portfoyExcel">Excel dışa aktar</x-filament::button></x-slot>
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:.6rem;margin-bottom:.8rem">
            @foreach ([['Toplam plan', $pfSay['toplam'], 'inherit', 'Kayıtlı planlar'], ['Hazır', $pfSay['hazir'], 'rgb(21 128 61)', 'Tüm kontroller tamam'], ['Aksiyon gerekli', $pfSay['aksiyon'], 'rgb(217 119 6)', 'Eksik başlık veya kroki'], ['Gözden geçirme', $pfSay['gozden'], 'rgb(220 38 38)', 'Termin geçmiş plan']] as [$ad, $sayi, $renk, $alt])
                <div style="{{ $kutu }};padding:.6rem .8rem">
                    <div style="font-size:.72rem;color:rgb(107 114 128)">{{ $ad }}</div>
                    <div style="font-size:1.4rem;font-weight:800;color:{{ $renk }}">{{ $sayi }}</div>
                    <div style="font-size:.68rem;color:rgb(107 114 128)">{{ $alt }}</div>
                </div>
            @endforeach
        </div>
        <div style="max-height:260px;overflow:auto">
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

    {{-- 1. FİRMA & RAPOR BİLGİLERİ --}}
    <x-filament::section icon="heroicon-o-building-office-2" icon-color="danger">
        <x-slot name="heading">1. Firma & Rapor Bilgileri</x-slot>
        <x-slot name="description">Kayıtlı firmalarınızdan birini seçin ve rapor tarihini girin</x-slot>

        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:1rem">
            <div>
                <label style="font-weight:600;font-size:.82rem">Firma Seçin <span style="color:#ef4444">*</span></label>
                <select wire:model.live="firmaId"
                    style="margin-top:.3rem;width:100%;padding:.55rem .75rem;border-radius:.5rem;border:1px solid rgb(107 114 128 / .35);background:transparent">
                    <option value="">— Firma seçin —</option>
                    @foreach ($this->firmalar as $id => $ad)
                        <option value="{{ $id }}">{{ $ad }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label style="font-weight:600;font-size:.82rem">Rapor Tarihi <span style="color:#ef4444">*</span></label>
                <input type="date" wire:model="raporTarihi"
                    style="margin-top:.3rem;width:100%;padding:.5rem .75rem;border-radius:.5rem;border:1px solid rgb(107 114 128 / .35);background:transparent">
            </div>
            <div>
                <label style="font-weight:600;font-size:.82rem">Doküman No</label>
                <input type="text" wire:model="dokumanNo" placeholder="Örn: AD-01"
                    style="margin-top:.3rem;width:100%;padding:.5rem .75rem;border-radius:.5rem;border:1px solid rgb(107 114 128 / .35);background:transparent">
            </div>
            <div>
                <label style="font-weight:600;font-size:.82rem">Rev. Tarihi / No</label>
                <input type="text" wire:model="revizyonNo" placeholder="Örn: Rev.01 — 15.03.2027"
                    style="margin-top:.3rem;width:100%;padding:.5rem .75rem;border-radius:.5rem;border:1px solid rgb(107 114 128 / .35);background:transparent">
            </div>
        </div>

        @if ($this->firma)
            <div style="{{ $kutu }};margin-top:1rem;background:rgb(239 68 68 / .05);border-color:rgb(239 68 68 / .3)">
                <div style="font-weight:700">{{ $this->firma->unvan }}</div>
                <div style="display:flex;gap:.5rem;flex-wrap:wrap;margin:.4rem 0">
                    <x-filament::badge color="danger">{{ $this->firma->tehlikeSinifiEtiketi() }}</x-filament::badge>
                    <x-filament::badge color="gray">SGK: {{ $this->firma->sgk_sicil_no ?: '—' }}</x-filament::badge>
                    <x-filament::badge color="gray">{{ $this->firma->calisan_sayisi ?: '—' }} çalışan</x-filament::badge>
                </div>
                <div style="font-size:.8rem;color:rgb(107 114 128)">{{ $this->firma->adres ?: 'Adres girilmemiş' }}</div>
            </div>

            {{-- Toplanma yeri + dışarıdan etkileyebilecek işyerleri (yönetmelik gereği zorunlu) --}}
            <div style="margin-top:1rem;display:grid;grid-template-columns:repeat(auto-fit,minmax(240px,1fr));gap:1rem">
                <div>
                    <label style="font-weight:600;font-size:.82rem">Toplanma Yeri</label>
                    <div style="font-size:.72rem;color:rgb(107 114 128);margin-bottom:.2rem">İşyeri dışında, güvenli, tarif edilebilir bir nokta</div>
                    <input type="text" wire:model="toplanmaYeri" placeholder="Örn: İnşaat alanı girişi, ana yol kenarı açık saha"
                        style="width:100%;padding:.5rem .75rem;border-radius:.5rem;border:1px solid rgb(107 114 128 / .35);background:transparent">
                </div>
                <div>
                    <label style="font-weight:600;font-size:.82rem">İşyerini Dışarıdan Etkileyebilecek İşyerleri</label>
                    <div style="font-size:.72rem;color:rgb(107 114 128);margin-bottom:.2rem">Her satıra bir işyeri: unvan, faaliyet konusu, olası etki</div>
                    <textarea wire:model="disaridanEtkileyebilecekIsyerleri" rows="2" placeholder="Örn: Komşu Akaryakıt A.Ş. — Akaryakıt istasyonu — Patlama/yangın sıçraması riski"
                        style="width:100%;padding:.5rem .75rem;border-radius:.5rem;border:1px solid rgb(107 114 128 / .35);background:transparent;font-family:inherit;font-size:.85rem"></textarea>
                </div>
            </div>

            {{-- Destek ekipleri --}}
            <div style="margin-top:1rem">
                <div style="font-weight:600;font-size:.85rem">Destek Elemanı Atamaları</div>
                <div style="font-size:.75rem;color:rgb(107 114 128);margin-bottom:.5rem">İsimleri virgülle ayırın. Çalışan modülü tamamlanınca seçim listesinden gelecek.</div>
                <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:.6rem">
                    @foreach ($ekipler as $anahtar => $ad)
                        <div>
                            <label style="font-size:.78rem;color:rgb(107 114 128)">{{ $ad }}</label>
                            <input type="text" wire:model="ekipMetni.{{ $anahtar }}" placeholder="Ad Soyad, Ad Soyad"
                                style="margin-top:.2rem;width:100%;padding:.45rem .6rem;border-radius:.4rem;border:1px solid rgb(107 114 128 / .3);background:transparent;font-size:.82rem">
                        </div>
                    @endforeach
                </div>
            </div>

            <div style="margin-top:1rem">
                <x-filament::button icon="heroicon-o-check" wire:click="kaydet">Kaydet</x-filament::button>
            </div>
        @else
            <p style="margin-top:1rem;font-size:.85rem;color:#f59e0b">Devam etmek için bir firma seçin.</p>
        @endif
    </x-filament::section>

    @if ($this->firma)
        {{-- 2. ACİL DURUM KONULARI --}}
        <x-filament::section icon="heroicon-o-exclamation-triangle" icon-color="danger">
            <x-slot name="heading">
                Acil Durum Konuları
                <span style="font-weight:400;font-size:.8rem;color:rgb(107 114 128)">({{ count($konular) }} konudan {{ count($this->konular) }} seçili)</span>
            </x-slot>
            <x-slot name="description">PDF çıktısına dahil edilecek acil durum sayfalarını seçin</x-slot>

            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(240px,1fr));gap:.35rem">
                @foreach ($konular as $i => $k)
                    @php $secili = in_array($k['anahtar'], $this->konular, true); @endphp
                    <button type="button" wire:click="konuToggle('{{ $k['anahtar'] }}')"
                        style="text-align:left;padding:.5rem .7rem;border-radius:.45rem;cursor:pointer;font-size:.82rem;
                            border:1px solid {{ $secili ? $kirmizi : 'rgb(107 114 128 / .3)' }};
                            background:{{ $secili ? 'rgb(239 68 68 / .1)' : 'transparent' }}">
                        {{ $secili ? '☑' : '☐' }}
                        <span style="display:inline-block;width:1.4rem;text-align:center;color:rgb(107 114 128)">{{ $i + 1 }}</span>
                        ACİL DURUM: {{ mb_strtoupper($k['ad'], 'UTF-8') }}
                    </button>
                @endforeach
            </div>
            <div style="margin-top:.6rem;display:flex;gap:.5rem">
                <x-filament::button size="xs" color="gray" wire:click="tumKonular(true)">Tümünü Seç</x-filament::button>
                <x-filament::button size="xs" color="gray" wire:click="tumKonular(false)">Tümünü Kaldır</x-filament::button>
            </div>
        </x-filament::section>

        {{-- RİSK, TEDBİR VE UYGULAMA (isgsuite plan sihirbazı 2-3. adım) --}}
        @php $alan = 'width:100%;padding:.45rem .6rem;border-radius:.45rem;border:1px solid rgb(107 114 128 / .35);background:transparent;font-size:.82rem'; $lb = 'display:block;font-size:.78rem;font-weight:600;margin-bottom:.2rem'; @endphp
        <x-filament::section icon="heroicon-o-clipboard-document-list" icon-color="danger" collapsible>
            <x-slot name="heading">Risk, tedbir ve uygulama</x-slot>
            <x-slot name="description">Tahliye anında herkes ne yapacak? Bu bilgiler kroki, ekip ve tatbikat süreçleriyle birlikte kullanılır. Kaydet ile saklanır.</x-slot>

            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:.8rem">
                <div style="grid-column:1/-1"><label style="{{ $lb }}">Önleyici ve sınırlandırıcı tedbirler</label>
                    <textarea wire:model="uygulama.onleyici_tedbirler" rows="3" style="{{ $alan }}" placeholder="Örn. yanıcı malzemelerin depolama koşulları, periyodik kontroller, alarm ve enerji izolasyonu…"></textarea></div>
                <div><label style="{{ $lb }}">Ölçüm ve değerlendirme notu</label>
                    <textarea wire:model="uygulama.olcum_notu" rows="3" style="{{ $alan }}" placeholder="Gerekli ölçümler, mevcut raporlar veya neden uygulanamaz olduğu"></textarea></div>
                <div><label style="{{ $lb }}">Acil durum ekipmanı ve KKD listesi</label>
                    <textarea wire:model="uygulama.ekipman_kkd" rows="3" style="{{ $alan }}" placeholder="Yangın, ilk yardım, kurtarma ve gerekiyorsa KKD ekipmanları"></textarea></div>
                @foreach (['ozel_risk_alanlari' => 'Özel risk alanları', 'enerji_kesme' => 'Enerji kesme / vana noktaları'] as $anahtar => $ad)
                    <div style="{{ $kutu }};padding:.6rem .8rem"><span style="{{ $lb }}">{{ $ad }}</span>
                        <div style="display:flex;gap:.8rem;flex-wrap:wrap;font-size:.8rem">
                            @foreach (\App\Support\AcilDurumHazirlik::UC_DURUM as $k => $v)
                                <label style="display:flex;gap:.3rem;align-items:center"><input type="radio" wire:model="uygulama.{{ $anahtar }}" value="{{ $k }}"> {{ $v }}</label>
                            @endforeach
                        </div>
                    </div>
                @endforeach
                <div style="grid-column:1/-1"><label style="{{ $lb }}">Müdahale, haberleşme ve tahliye yöntemi</label>
                    <textarea wire:model="uygulama.mudahale_yontemi" rows="3" style="{{ $alan }}" placeholder="İhbar yöntemi, ilk müdahale, ekiplerin görev sırası, tahliye, toplanma ve yoklama adımları"></textarea></div>
                <div style="grid-column:1/-1"><label style="{{ $lb }}">Özel desteğe ihtiyaç duyan kişiler için yöntem</label>
                    <textarea wire:model="uygulama.ozel_destek" rows="2" style="{{ $alan }}" placeholder="Engelli, yaşlı, gebe, çocuk veya refakat ihtiyacı olan kişiler için destek yöntemi"></textarea></div>
                <div style="grid-column:1/-1;display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:.5rem">
                    @foreach (['ziyaretci_dahil' => ['Ziyaretçiler dahil', 'Girişte bilgilendirme / refakat akışı'], 'gecici_calisan_dahil' => ['Geçici çalışanlar dahil', 'İşe başlama ve saha bilgilendirmesi'], 'coklu_isveren' => ['Birden fazla işveren / ortak saha', 'Koordinasyon kontrolü gerektirir']] as $anahtar => [$ad, $alt])
                        <label style="{{ $kutu }};padding:.55rem .7rem;display:flex;gap:.5rem;align-items:flex-start;cursor:pointer">
                            <input type="checkbox" wire:model="uygulama.{{ $anahtar }}" style="margin-top:.2rem">
                            <span><span style="font-size:.8rem;font-weight:600">{{ $ad }}</span><br><span style="font-size:.7rem;color:rgb(107 114 128)">{{ $alt }}</span></span>
                        </label>
                    @endforeach
                </div>

                <div style="grid-column:1/-1;{{ $kutu }};padding:.7rem .8rem">
                    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:.4rem">
                        <span><span style="font-size:.8rem;font-weight:700">Acil iletişim listesi</span><br><span style="font-size:.7rem;color:rgb(107 114 128)">112 yanında işyerine uygun yerel ve tesis irtibatlarını ekleyin.</span></span>
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
                </div>

                <div style="grid-column:1/-1;{{ $kutu }};padding:.7rem .8rem">
                    <div style="font-size:.8rem;font-weight:700">Yayın, onay ve tatbikat doğrulaması</div>
                    <div style="font-size:.7rem;color:rgb(107 114 128);margin-bottom:.5rem">Tatbikat modülünde "tamamlandı" kaydı varsa sistem onu öncelikli kaynak kabul eder. Onay seçimi belgeyle ayrıca doğrulanmalıdır.</div>
                    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:.6rem">
                        <div><label style="{{ $lb }}">Onay / imza durumu</label>
                            <select wire:model="uygulama.onay_durumu" style="{{ $alan }}">@foreach (\App\Support\AcilDurumHazirlik::ONAY as $k => $v)<option value="{{ $k }}">{{ $v }}</option>@endforeach</select></div>
                        <div><label style="{{ $lb }}">Son tatbikat (manuel kayıt)</label><input type="date" wire:model="uygulama.son_tatbikat" style="{{ $alan }}"></div>
                        <div><label style="{{ $lb }}">Planlanan sonraki tatbikat</label><input type="date" wire:model="uygulama.sonraki_tatbikat" style="{{ $alan }}"></div>
                        <div><label style="{{ $lb }}">Tutanak / kayıt referansı</label><input type="text" wire:model="uygulama.tutanak_ref" placeholder="Örn. TAT-2026-004" style="{{ $alan }}"></div>
                    </div>
                    <div style="display:flex;gap:1.2rem;flex-wrap:wrap;margin-top:.6rem;font-size:.8rem">
                        <label style="display:flex;gap:.35rem;align-items:center"><input type="checkbox" wire:model="uygulama.kroki_asildi"> Krokiler görünür yerlere asıldı <span style="font-size:.7rem;color:rgb(107 114 128)">(giriş, çıkış ve kat seviyeleri)</span></label>
                        <label style="display:flex;gap:.35rem;align-items:center"><input type="checkbox" wire:model="uygulama.calisan_bilgilendirildi"> Çalışan bilgilendirmesi tamamlandı <span style="font-size:.7rem;color:rgb(107 114 128)">(yeni ve geçici çalışanlar dahil)</span></label>
                    </div>
                </div>

                <div style="grid-column:1/-1"><label style="{{ $lb }}">Ek not</label>
                    <textarea wire:model="uygulama.ek_not" rows="2" style="{{ $alan }}" placeholder="Planın saha uygulamasına ilişkin ek notlar"></textarea></div>
            </div>
            <div style="margin-top:.7rem"><x-filament::button icon="heroicon-o-check" wire:click="kaydet">Kaydet</x-filament::button></div>
        </x-filament::section>

        {{-- HAZIRLIK KONTROLÜ --}}
        @if ($h = $this->hazirlik)
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

        {{-- 3. KAPAK ÇERÇEVESİ --}}
        <x-filament::section icon="heroicon-o-swatch" icon-color="danger">
            <x-slot name="heading">Kapak Çerçevesi</x-slot>
            <x-slot name="description">Kapak sayfası için çerçeve stili seçin</x-slot>
            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:.5rem">
                @foreach ($cerceveler as $anahtar => $ad)
                    @php $secili = $kapakCercevesi === $anahtar; @endphp
                    <button type="button" wire:click="$set('kapakCercevesi','{{ $anahtar }}')"
                        style="padding:.6rem;border-radius:.5rem;cursor:pointer;font-size:.8rem;
                            border:2px solid {{ $secili ? $mor : 'rgb(107 114 128 / .3)' }};
                            background:{{ $secili ? 'rgb(139 92 246 / .08)' : 'transparent' }}">
                        <div style="font-weight:600">{{ Str::before($ad, ' — ') }}</div>
                        <div style="font-size:.7rem;color:rgb(107 114 128)">{{ Str::after($ad, ' — ') }}</div>
                    </button>
                @endforeach
            </div>
        </x-filament::section>

        {{-- 4. GERÇEK ŞABLON (WORD / KAPAK) --}}
        <x-filament::section icon="heroicon-o-document-duplicate" icon-color="danger">
            <x-slot name="heading">Acil Durum Planı Şablonu</x-slot>
            <x-slot name="description">"Word (Orijinal Şablon)" ve "Kapak Sayfası" aksiyonları seçili şablonu üretir — zamanla eklenecek farklı örnekler arasında seçim yapın</x-slot>
            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:.5rem">
                @foreach ($sablonlar as $anahtar => $s)
                    @php $secili = $sablonId === $anahtar; @endphp
                    <button type="button" wire:click="$set('sablonId','{{ $anahtar }}')"
                        style="padding:.6rem;border-radius:.5rem;cursor:pointer;font-size:.8rem;text-align:left;
                            border:2px solid {{ $secili ? $kirmizi : 'rgb(107 114 128 / .3)' }};
                            background:{{ $secili ? 'rgb(239 68 68 / .1)' : 'transparent' }}">
                        <div style="font-weight:600">{{ $s['ad'] }}</div>
                        <div style="font-size:.7rem;color:rgb(107 114 128)">{{ $s['aciklama'] }}</div>
                    </button>
                @endforeach
            </div>
        </x-filament::section>

        {{-- 5. ACİL DURUM AFİŞLERİ --}}
        <x-filament::section icon="heroicon-o-printer" icon-color="danger">
            <x-slot name="heading">Acil Durum Afişleri</x-slot>
            <x-slot name="description">Firmalara asılmak üzere A3 / A4 talimat afişleri — plan kaydedilince firmaya uygun olanlar otomatik tanımlanır (✓ işaretli)</x-slot>

            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:.5rem">
                @foreach ($afisler as $anahtar => $afis)
                    @php $secili = $afisTipi === $anahtar; $tanimli = in_array($anahtar, $this->afisTipleri, true); @endphp
                    <button type="button" wire:click="$set('afisTipi','{{ $anahtar }}')"
                        style="position:relative;padding:.6rem;border-radius:.5rem;cursor:pointer;font-size:.8rem;text-align:center;
                            border:2px solid {{ $secili ? $kirmizi : 'rgb(107 114 128 / .3)' }};
                            background:{{ $secili ? 'rgb(239 68 68 / .1)' : 'transparent' }};
                            opacity:{{ $tanimli ? 1 : .55 }}">
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
                            style="padding:.35rem .7rem;border-radius:.4rem;cursor:pointer;font-size:.8rem;
                                border:1px solid {{ $afisEbat === $e ? $kirmizi : 'rgb(107 114 128 / .3)' }};
                                background:{{ $afisEbat === $e ? 'rgb(239 68 68 / .1)' : 'transparent' }}">{{ $et }}</button>
                    @endforeach
                </div>
                <x-filament::button color="danger" icon="heroicon-o-arrow-down-tray" wire:click="afisIndir">
                    Afişi İndir ({{ strtoupper($afisEbat) }})
                </x-filament::button>
                <x-filament::button color="gray" icon="heroicon-o-archive-box-arrow-down" wire:click="afislerZipIndir">
                    Tüm Afişleri İndir — {{ count($this->afisTipleri) }} adet (ZIP, {{ strtoupper($afisEbat) }})
                </x-filament::button>
            </div>

            {{-- seçili afiş önizleme (adım listesi) --}}
            <div style="{{ $kutu }};margin-top:.85rem">
                <div style="font-weight:700;color:{{ $kirmizi }};font-size:.9rem">{{ $afisler[$afisTipi]['baslik'] }}</div>
                <ol style="margin:.5rem 0 0;padding-left:1.2rem;font-size:.82rem;display:flex;flex-direction:column;gap:.25rem">
                    @foreach ($afisler[$afisTipi]['adimlar'] as $adim) <li>{{ $adim }}</li> @endforeach
                </ol>
            </div>
        </x-filament::section>
    @endif

    <div style="{{ $kutu }};background:rgb(245 158 11 / .06);border-color:rgb(245 158 11 / .3);font-size:.82rem">
        <strong>⚠ Acil Durum Eylem Planı Hakkında</strong><br>
        {{ config('isg.acil_durum.hakkinda') }}
    </div>
</x-filament-panels::page>
