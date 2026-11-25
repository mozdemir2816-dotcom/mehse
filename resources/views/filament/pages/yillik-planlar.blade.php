@php
    $renkler = ['bos' => '#374151', 'planlandi' => '#b45309', 'tamamlandi' => '#10b981'];
    $etiketler = ['bos' => 'Boş', 'planlandi' => 'Planlandı', 'tamamlandi' => 'Tamamlandı'];
    $mor = 'rgb(37 99 235)';
    $p = $this->plan;
    $kilit = $this->kilitAyIndeksi;
    $egitimKategorileri = config('isg.yillik_plan.egitim_kategorileri');
@endphp

<x-filament-panels::page>
    @include('filament.pages.partials.acil-slate-stil')
    <p class="mehse-bolum-aciklama">
        @switch ($sekme)
            @case('egitim')
                Firmanın yıllık eğitim planı. Ay hücrelerine tıklayarak durumu Boş → Planlandı → Tamamlandı
                arasında değiştirin; o ay planlanan eğitimler Ziyaret Programı'ndaki yapılacaklar listesine gelir.
                @break
            @case('degerlendirme')
                İSG yıllık değerlendirme raporu — 36 çalışma satırı sistemdeki kayıtlardan otomatik dolar; eksikleri tamamlayıp çıktı alın.
                @break
            @default
                Her ayın P (Planlandı) ve G (Gerçekleşti) hücresine tıklayarak işaretleyin; o ay P olan
                maddeler Ziyaret Programı'nda o ayın ziyaret yapılacaklar listesine otomatik gelir.
        @endswitch
    </p>

    @php
        $yillikKriter = match ($sekme) {
            'egitim' => 'yillik_egitim_plani',
            'degerlendirme' => 'yillik_degerlendirme',
            default => 'yillik_calisma_plani',
        };
    @endphp
    @include('filament.pages.partials.eksik-firmalar', ['kriterAnahtari' => $yillikKriter])

    @if ($p)
        @php $oz = $p->gerceklesmeOzeti(); @endphp
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(170px,1fr));gap:.75rem">
            <div style="background:#fff;border:1px solid #e2e8f0;border-radius:1rem;padding:.8rem 1rem">
                <div style="font-size:.75rem;color:#64748b;font-weight:600">Yıllık Plan Gerçekleşme Oranı</div>
                <div style="font-size:1.6rem;font-weight:800;color:{{ $oz['oran'] === null ? '#64748b' : ($oz['oran'] >= 80 ? '#047857' : ($oz['oran'] >= 50 ? '#b45309' : '#dc2626')) }}">
                    {{ $oz['oran'] === null ? '—' : '%'.$oz['oran'] }}
                </div>
                <div style="font-size:.75rem;color:#64748b">{{ $oz['gerceklesen'] }} G / {{ $oz['planlanan'] }} vadesi gelen faaliyet (çalışma + eğitim)</div>
            </div>
            <div style="background:#fff;border:1px solid #e2e8f0;border-radius:1rem;padding:.8rem 1rem">
                <div style="font-size:.75rem;color:#64748b;font-weight:600">Geciken Faaliyet</div>
                <div style="font-size:1.6rem;font-weight:800;color:{{ $oz['geciken'] > 0 ? '#dc2626' : '#047857' }}">{{ $oz['geciken'] }}</div>
                <div style="font-size:.75rem;color:#64748b">P'de kalan, ayı geçmiş hücre (kırmızı nokta ●)</div>
            </div>
        </div>
    @endif

    @if ($p && $kilit > 0 && $kilit < 12)
        <div style="border:1px solid rgb(245 158 11 / .4);background:rgb(245 158 11 / .08);border-radius:.6rem;padding:.6rem .85rem;font-size:.8rem;color:#b45309">
            ⚠ Bu firmanın sözleşme başlangıcı (atanmış uzman tarihi) <strong>{{ \App\Filament\Pages\YillikPlanlar::AYLAR[$kilit] }}</strong> ayı — önceki aylar planda seçilemez ve otomatik doldurulmaz.
        </div>
    @elseif ($p && $kilit >= 12)
        <div style="border:1px solid rgb(239 68 68 / .4);background:rgb(239 68 68 / .08);border-radius:.6rem;padding:.6rem .85rem;font-size:.8rem;color:#b91c1c">
            ⚠ Bu firmanın sözleşme başlangıcı {{ $yil }} yılından sonra — bu yıl için plan ayları seçilemez.
        </div>
    @endif

    {{-- FİRMA & YIL --}}
    <x-filament::section icon="heroicon-o-calendar-days" icon-color="primary">
        <x-slot name="heading">Firma & Yıl</x-slot>
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:1rem">
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
            <div>
                <label class="mehse-etiket">Yıl</label>
                <select wire:model.live="yil"
                    class="mehse-girdi">
                    @foreach (range(now()->year - 1, now()->year + 2) as $y)
                        <option value="{{ $y }}">{{ $y }}</option>
                    @endforeach
                </select>
            </div>
        </div>
    </x-filament::section>

    @if ($p)
        {{-- Diğer yıllık plan sayfaları (aynı firma + yıl ile) --}}
        <div style="display:flex;gap:.4rem;margin-bottom:1rem;flex-wrap:wrap;font-size:.78rem;color:rgb(107 114 128);align-items:center">
            Aynı firmanın diğer planları:
            @foreach ([
                'calisma' => [\App\Filament\Pages\YillikPlan\YillikCalismaPlani::class, 'Yıllık Çalışma Planı'],
                'egitim' => [\App\Filament\Pages\YillikPlan\YillikEgitimPlani::class, 'Yıllık Eğitim Planı'],
                'degerlendirme' => [\App\Filament\Pages\YillikPlan\YillikDegerlendirmeRaporu::class, 'Yıllık Değerlendirme Raporu'],
            ] as $anahtar => [$sinif, $etiket])
                @continue($anahtar === $sekme)
                <x-filament::link :href="$sinif::getUrl(['firma' => $firmaId, 'yil' => $yil])" size="sm" icon="heroicon-o-arrow-top-right-on-square">
                    {{ $etiket }}
                </x-filament::link>
            @endforeach
        </div>

        @if ($sekme === 'calisma')
            @php
                $cizgi = 'border:1px solid rgb(107 114 128 / .25)';
                $th = $cizgi.';padding:.3rem .4rem;font-size:.68rem;font-weight:700;text-align:center;background:rgb(107 114 128 / .08)';
                $td = $cizgi.';padding:.3rem .4rem;vertical-align:top';
                $pRenk = '#b45309';
                $gRenk = '#15803d';
            @endphp
            <x-filament::section icon="heroicon-o-table-cells" icon-color="primary">
                <x-slot name="heading">
                    İş Sağlığı ve Güvenliği Yıllık Çalışma Planı – {{ $yil }}
                    <span style="font-weight:400;font-size:.78rem;color:rgb(107 114 128)">
                        (<strong style="color:{{ $pRenk }}">P</strong> Planlandı ·
                        <strong style="color:{{ $gRenk }}">G</strong> Gerçekleşti — hücreye tıklayarak işaretleyin)
                    </span>
                </x-slot>

                <div style="overflow-x:auto">
                    <table style="width:100%;border-collapse:collapse;font-size:.72rem;min-width:980px">
                        <tr>
                            <th rowspan="2" style="{{ $th }};width:1.75rem">No</th>
                            <th rowspan="2" style="{{ $th }};width:5.5rem">Ana Konu</th>
                            <th rowspan="2" style="{{ $th }};min-width:10rem">Planlanan Faaliyet</th>
                            <th rowspan="2" style="{{ $th }};width:4.75rem">Periyot</th>
                            <th rowspan="2" style="{{ $th }};width:5.5rem">Sorumlu</th>
                            @foreach (['OCA', 'ŞUB', 'MAR', 'NİS', 'MAY', 'HAZ', 'TEM', 'AĞU', 'EYL', 'EKİ', 'KAS', 'ARA'] as $ay)
                                <th colspan="2" style="{{ $th }};padding:.25rem .15rem">{{ $ay }}</th>
                            @endforeach
                            <th rowspan="2" style="{{ $th }};min-width:9rem">Mevzuat Dayanağı / Kayıt-Kanıt / Açıklama</th>
                            <th rowspan="2" style="{{ $th }};width:4.5rem">Durum</th>
                            <th rowspan="2" style="{{ $th }};width:1.5rem"></th>
                        </tr>
                        <tr>
                            @foreach (range(0, 11) as $_)
                                <th style="{{ $th }};width:1.15rem;padding:.2rem .1rem;color:{{ $pRenk }}">P</th>
                                <th style="{{ $th }};width:1.15rem;padding:.2rem .1rem;color:{{ $gRenk }}">G</th>
                            @endforeach
                        </tr>
                        @foreach (($p->faaliyetler ?? []) as $fi => $f)
                            <tr>
                                <td style="{{ $td }};text-align:center">{{ $fi + 1 }}</td>
                                <td style="{{ $td }};font-size:.68rem;font-weight:700;letter-spacing:.03em">{{ $f['ana_konu'] ?? '' }}</td>
                                <td style="{{ $td }};font-weight:600">{{ $f['faaliyet'] }}</td>
                                <td style="{{ $td }};font-size:.75rem">{{ $f['frekans'] ?? '' }}</td>
                                <td style="{{ $td }};font-size:.75rem">{{ $f['sorumlu'] ?? '' }}</td>
                                @foreach (($f['aylar'] ?? array_fill(0, 12, 'bos')) as $ai => $durum)
                                    @php
                                        $kilitli = $ai < $kilit;
                                        $hucreler = [
                                            'P' => [$durum !== 'bos', $pRenk, 'Planlandı'],
                                            'G' => [$durum === 'tamamlandi', $gRenk, 'Gerçekleşti'],
                                        ];
                                    @endphp
                                    @foreach ($hucreler as $hucre => [$dolu, $renk, $ad])
                                        <td style="{{ $cizgi }};padding:0;text-align:center;{{ $kilitli ? 'background:rgb(107 114 128 / .15)' : '' }}">
                                            <button type="button" @disabled($kilitli)
                                                @unless ($kilitli) wire:click="ayHucresiDegistir('faaliyetler', {{ $fi }}, {{ $ai }}, '{{ $hucre }}')" @endunless
                                                title="{{ $kilitli ? 'Uzman atanmadan önce — seçilemez' : \App\Models\ZiyaretProgrami::AYLAR[$ai].' — '.$ad.($dolu ? ' (kaldırmak için tıklayın)' : '') }}"
                                                style="width:100%;min-height:1.6rem;border:none;background:{{ $dolu ? $renk.'22' : 'transparent' }};color:{{ $renk }};font-weight:800;font-size:.75rem;{{ $kilitli ? 'cursor:not-allowed' : 'cursor:pointer' }}">{{ $dolu ? $hucre : '' }}@if ($hucre === 'G' && $durum === 'planlandi' && $ai < $p->gecikmeAyIndeksi())<span title="Gecikmiş faaliyet" class="mehse-hata">●</span>@endif</button>
                                        </td>
                                    @endforeach
                                @endforeach
                                <td style="{{ $td }};font-size:.68rem;color:rgb(107 114 128)">
                                    {{ $f['yasal_gereklilik'] ?? '' }}
                                    @if (filled($f['kayit_notu'] ?? null))
                                        <div style="margin-top:.2rem"><strong>Kayıt/Not:</strong> {{ $f['kayit_notu'] }}</div>
                                    @endif
                                </td>
                                @php
                                    $genelDurum = \App\Models\YillikPlan::faaliyetDurumu($f['aylar'] ?? []);
                                    $durumRenk = match ($genelDurum) {
                                        'Gerçekleşti' => $gRenk,
                                        'Devam Ediyor' => '#2563eb',
                                        'Planlandı' => $pRenk,
                                        default => 'rgb(107 114 128)',
                                    };
                                @endphp
                                <td style="{{ $td }};text-align:center;font-size:.68rem;font-weight:700;color:{{ $durumRenk }}">{{ $genelDurum }}</td>
                                <td style="{{ $td }};text-align:center">
                                    <button type="button" wire:click="faaliyetSil({{ $fi }})" wire:confirm="Bu faaliyet plandan silinsin mi?" class="mehse-sil-dugme">✕</button>
                                </td>
                            </tr>
                        @endforeach
                    </table>
                </div>

                <div style="margin-top:1rem;display:grid;grid-template-columns:1fr 2fr 1fr 1fr 1.5fr auto;gap:.5rem">
                    <input type="text" wire:model="yeniAnaKonu" placeholder="Ana konu" list="ana-konu-listesi"
                        class="mehse-girdi-kompakt">
                    <datalist id="ana-konu-listesi">
                        @foreach (collect($p->faaliyetler ?? [])->pluck('ana_konu')->filter()->unique() as $konu)
                            <option value="{{ $konu }}"></option>
                        @endforeach
                    </datalist>
                    <input type="text" wire:model="yeniFaaliyet" placeholder="Planlanan faaliyet"
                        class="mehse-girdi-kompakt">
                    <input type="text" wire:model="yeniPeriyot" placeholder="Periyot"
                        class="mehse-girdi-kompakt">
                    <input type="text" wire:model="yeniSorumlu" placeholder="Sorumlu"
                        class="mehse-girdi-kompakt">
                    <input type="text" wire:model="yeniAciklama" placeholder="Mevzuat dayanağı / açıklama"
                        class="mehse-girdi-kompakt">
                    <x-filament::button size="sm" wire:click="faaliyetEkle">+ Faaliyet Ekle</x-filament::button>
                </div>

                <div style="margin-top:.75rem;display:flex;justify-content:space-between;align-items:center;gap:.5rem;flex-wrap:wrap">
                    <x-filament::button size="xs" color="gray" wire:click="varsayilanaSifirla" wire:confirm="Çalışma planı standart şablona sıfırlansın mı? İşaretlenen P/G'ler silinir.">Varsayılana Sıfırla</x-filament::button>
                    <x-filament::button color="success" icon="heroicon-o-arrow-down-tray" wire:click="mountAction('calismaExcel')">
                        Çıktı Al (Excel — A4 yatay)
                    </x-filament::button>
                </div>
            </x-filament::section>
        @endif

        @if ($sekme === 'egitim')
            @php
                $egitimOzet = \App\Support\YillikEgitimPlanlayici::ozet($p->egitimler ?? [], $this->firma);
                $yontemStil = [
                    'uzaktan' => ['etiket' => '💻 Online', 'bg' => 'rgb(37 99 235 / .12)', 'renk' => '#1d4ed8', 'kenar' => 'rgb(37 99 235 / .35)'],
                    'yuz_yuze' => ['etiket' => '🏫 Yüz Yüze', 'bg' => 'rgb(217 119 6 / .12)', 'renk' => '#b45309', 'kenar' => 'rgb(217 119 6 / .35)'],
                    'karma' => ['etiket' => '🔀 Karma', 'bg' => 'rgb(124 58 237 / .12)', 'renk' => '#6d28d9', 'kenar' => 'rgb(124 58 237 / .35)'],
                ];
            @endphp
            <x-filament::section icon="heroicon-o-academic-cap" icon-color="primary">
                <x-slot name="heading">
                    Yıllık Eğitim Planı — {{ $yil }}
                    <span style="font-weight:400;font-size:.78rem;color:rgb(107 114 128)">(ay hücresine tıklayarak durumu, yöntem rozetine tıklayarak Online/Yüz Yüze seçimini değiştirin)</span>
                </x-slot>

                {{-- Online / Yüz Yüze Planlama Kontrol Şeridi & RG 02.04.2026 Uyum Özeti --}}
                <div style="border:1px solid {{ $egitimOzet['uyumlu'] ? 'rgb(34 197 94 / .35)' : 'rgb(239 68 68 / .45)' }};background:{{ $egitimOzet['uyumlu'] ? 'rgb(34 197 94 / .05)' : 'rgb(239 68 68 / .06)' }};border-radius:.85rem;padding:.85rem 1rem;margin-bottom:1rem">
                    <div style="display:flex;flex-wrap:wrap;align-items:center;justify-content:space-between;gap:.65rem;margin-bottom:.65rem">
                        <div style="display:flex;flex-wrap:wrap;align-items:center;gap:.45rem;font-size:.8rem">
                            <span style="font-weight:700;padding:.2rem .6rem;border-radius:9999px;background:rgb(15 23 42 / .08)">{{ $egitimOzet['tehlike_etiketi'] }}</span>
                            <span style="font-weight:700;padding:.2rem .6rem;border-radius:9999px;background:rgb(37 99 235 / .12);color:#1d4ed8">💻 Online: {{ \App\Support\YillikEgitimPlanlayici::saatMetni($egitimOzet['ilk_online']) }}</span>
                            <span style="font-weight:700;padding:.2rem .6rem;border-radius:9999px;background:rgb(217 119 6 / .12);color:#b45309">🏫 Yüz Yüze: {{ \App\Support\YillikEgitimPlanlayici::saatMetni($egitimOzet['ilk_yuz_yuze']) }}</span>
                            <span style="font-weight:700;padding:.2rem .6rem;border-radius:9999px;background:rgb(15 23 42 / .08)">Toplam İlk Defa: {{ \App\Support\YillikEgitimPlanlayici::saatMetni($egitimOzet['ilk_toplam']) }}</span>
                            <span style="font-size:.75rem;color:rgb(71 85 105)">Tekrar: {{ \App\Support\YillikEgitimPlanlayici::saatMetni($egitimOzet['tekrar_online']) }} Online + {{ \App\Support\YillikEgitimPlanlayici::saatMetni($egitimOzet['tekrar_yuz_yuze']) }} Yüz Yüze</span>
                            @if ($egitimOzet['uyumlu'])
                                <span style="font-weight:700;color:#15803d">✓ RG 02.04.2026 Uyumlu</span>
                            @else
                                <span style="font-weight:700;color:#b91c1c">⚠ Mevzuat Uyarısı</span>
                            @endif
                        </div>
                        @if (\App\Filament\Pages\UzaktanEgitimAtama::canAccess())
                            <x-filament::button size="xs" color="info" icon="heroicon-o-computer-desktop" tag="a" :href="\App\Filament\Pages\UzaktanEgitimAtama::getUrl(['firma' => $this->firmaId])">
                                Online Eğitim Ata →
                            </x-filament::button>
                        @endif
                    </div>

                    <div style="display:flex;flex-wrap:wrap;align-items:center;gap:.5rem">
                        <select wire:model="egitimPlanModu" class="mehse-girdi-kompakt" style="max-width:440px;font-size:.78rem">
                            @foreach (\App\Support\YillikEgitimPlanlayici::MODLAR as $modKod => $modAd)
                                <option value="{{ $modKod }}">{{ $modAd }}</option>
                            @endforeach
                        </select>
                        <x-filament::button size="xs" color="primary" icon="heroicon-o-sparkles" wire:click="egitimModuUygula">
                            Online / Yüz Yüze Otomatik Planla
                        </x-filament::button>
                        <span style="font-size:.73rem;color:rgb(100 116 139)">
                            {{ $egitimOzet['online_konu'] }} online · {{ $egitimOzet['yuz_yuze_konu'] }} yüz yüze{{ $egitimOzet['karma_konu'] > 0 ? ' · '.$egitimOzet['karma_konu'].' karma' : '' }} temel konu{{ $egitimOzet['diger_saat'] > 0 ? ' · Ek uygulamalı: '.\App\Support\YillikEgitimPlanlayici::saatMetni($egitimOzet['diger_saat']) : '' }}
                        </span>
                    </div>

                    <div style="display:flex;flex-wrap:wrap;align-items:center;gap:.5rem;margin-top:.55rem;padding-top:.55rem;border-top:1px dashed rgb(100 116 139 / .25)">
                        @php
                            $naceGrup = \App\Support\EgitimIcerikOlusturucu::naceSektoru($this->firma?->nace_kodu);
                            $naceGrupAd = $naceGrup ? ($this->egitimSektorSecenekleri[$naceGrup] ?? $naceGrup) : 'Tespit edilemedi';
                        @endphp
                        <span style="font-size:.75rem;font-weight:700;color:rgb(51 65 85)">4. Bölüm Gerçek İş Grubu:</span>
                        <select wire:model="firmaEgitimGrubu" class="mehse-girdi-kompakt" style="max-width:260px;font-size:.76rem">
                            <option value="">— NACE Otomatik ({{ $naceGrupAd }}) —</option>
                            @foreach ($this->egitimSektorSecenekleri as $sKod => $sAd)
                                <option value="{{ $sKod }}">{{ $sAd }}</option>
                            @endforeach
                        </select>
                        <x-filament::button size="xs" color="gray" wire:click="firmaEgitimGrubunuUygula(false)">Grubu Kaydet</x-filament::button>
                        <x-filament::button size="xs" color="info" wire:click="firmaEgitimGrubunuUygula(true)">4. Bölümü Gruba Göre Doldur</x-filament::button>
                        <x-filament::button size="xs" color="success" icon="heroicon-o-arrow-path" wire:click="dorduncuBolumuGrubaEsitle" wire:confirm="Bu firmanın 4. bölüm konuları aynı iş grubundaki tüm firmaların {{ $yil }} planına eşitlensin mi? (Diğer firmalardaki tamamlanmış G ayları ve firmaya özel yalıtılmış firmalar korunur.)">
                            Aynı İş Grubundaki Firmalara Eşitle ({{ $this->ayniIsGrubuFirmaSayisi }} firma)
                        </x-filament::button>
                        @if ($this->firmaEgitimOzelMi)
                            <span style="font-size:.72rem;font-weight:700;padding:.18rem .55rem;border-radius:9999px;background:rgb(180 83 9 / .12);color:#92400e">🔒 Yalnız Bu Firmaya Özel (Diğer şablonları etkilemez)</span>
                            <x-filament::button size="xs" color="gray" wire:click="firmaOzelDurumuDegistir(false)">Gruba Geri Aç</x-filament::button>
                        @else
                            <x-filament::button size="xs" color="warning" outlined wire:click="firmaOzelDurumuDegistir(true)" title="Bu firmanın NACE dışı özel konularını yalnız bu firmaya kilitler; diğer inşaat/NACE şablonlarına karışmaz.">
                                🔒 Yalnız Bu Firmaya Özel Kilitle
                            </x-filament::button>
                        @endif
                    </div>

                    {{-- İşyerinde Yapılan İş Kalemleri → 4. Bölüm & Eğitim Katılım Ekip Kaynağı --}}
                    <div style="margin-top:.65rem;padding-top:.6rem;border-top:1px dashed rgb(100 116 139 / .25)">
                        <div style="display:flex;flex-wrap:wrap;align-items:center;justify-content:space-between;gap:.45rem;margin-bottom:.45rem">
                            <div style="display:flex;flex-wrap:wrap;align-items:center;gap:.4rem">
                                <span style="font-size:.76rem;font-weight:700;color:rgb(15 23 42)">🏗️ İşyerinde Yapılan İş Kalemleri</span>
                                <span style="font-size:.71rem;color:rgb(100 116 139)">(Seçtikleriniz firmanın 4. Bölümünü ve Eğitim Katılım ekip butonlarını kurar)</span>
                            </div>
                            <div style="display:flex;flex-wrap:wrap;align-items:center;gap:.4rem">
                                <button type="button" wire:click="$toggle('tumIsKalemleriniGoster')"
                                    style="font-size:.72rem;color:#2563eb;text-decoration:underline;background:none;border:none;cursor:pointer">
                                    {{ $tumIsKalemleriniGoster ? 'Yalnız bu sektörün iş kalemlerini göster' : 'Diğer sektör iş kalemlerini göster' }}
                                </button>
                                <x-filament::button size="xs" color="success" icon="heroicon-o-check-circle" wire:click="isKalemleriniPlanaUygula">
                                    Seçili İş Kalemlerini 4. Bölüme Uygula ({{ count($seciliIsKalemleri) }})
                                </x-filament::button>
                            </div>
                        </div>

                        @php
                            $anaGrupAd = $this->anaIsKalemiGrubu;
                        @endphp
                        @foreach ($this->isKalemiGruplari as $grupAdi => $kalemler)
                            @php
                                $buGruptaSecili = collect(array_keys($kalemler))->contains(fn ($a) => in_array($a, $seciliIsKalemleri, true));
                                $goster = $tumIsKalemleriniGoster || $grupAdi === $anaGrupAd || $buGruptaSecili;
                            @endphp
                            @if ($goster)
                                <div style="margin-bottom:.4rem">
                                    <div style="font-size:.7rem;font-weight:700;color:rgb(71 85 105);margin-bottom:.25rem">
                                        {{ $grupAdi }}@if ($grupAdi === $anaGrupAd) <span style="font-weight:600;color:#0f766e">· Öncelikli Grup</span>@endif
                                    </div>
                                    <div style="display:flex;flex-wrap:wrap;gap:.3rem">
                                        @foreach ($kalemler as $kAnahtar => $kVeri)
                                            @php $aktif = in_array($kAnahtar, $seciliIsKalemleri, true); @endphp
                                            <button type="button" wire:click="isKalemiToggle('{{ $kAnahtar }}')"
                                                title="{{ $kVeri['egitim_konusu'] }}"
                                                style="padding:.22rem .58rem;border-radius:9999px;cursor:pointer;font-size:.73rem;font-weight:{{ $aktif ? '700' : '500' }};border:1px solid {{ $aktif ? '#047857' : 'rgb(107 114 128 / .35)' }};background:{{ $aktif ? 'rgb(16 185 129 / .14)' : '#fff' }};color:{{ $aktif ? '#065f46' : '#334155' }}">
                                                {{ $aktif ? '✓ ' : '+ ' }}{{ $kVeri['ad'] }}
                                            </button>
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                        @endforeach
                    </div>

                    @if (! empty($egitimOzet['uyarilar']))
                        <ul style="margin:.55rem 0 0;padding-left:1.1rem;font-size:.75rem;color:#b91c1c">
                            @foreach ($egitimOzet['uyarilar'] as $uyari)
                                <li>{{ $uyari }}</li>
                            @endforeach
                        </ul>
                    @endif
                </div>

                <div style="overflow-x:auto">
                    <table style="width:100%;border-collapse:collapse;font-size:.75rem;min-width:1100px">
                        <tr>
                            <th class="mehse-th">Kategori</th>
                            <th class="mehse-th">Eğitim Konusu</th>
                            <th class="mehse-th">Yöntem</th>
                            <th class="mehse-th">Süre</th>
                            <th class="mehse-th">Eğitici</th>
                            @foreach (\App\Filament\Pages\YillikPlanlar::AYLAR as $ay)
                                <th style="padding:.3rem .3rem;border-bottom:1px solid rgb(107 114 128 / .3);width:2.2rem">{{ $ay }}</th>
                            @endforeach
                            <th style="border-bottom:1px solid rgb(107 114 128 / .3)"></th>
                        </tr>
                        @foreach (($p->egitimler ?? []) as $ei => $e)
                            @php
                                $yontem = $e['yontem'] ?? 'yuz_yuze';
                                $ys = $yontemStil[$yontem] ?? $yontemStil['yuz_yuze'];
                                $zorunluYy = \App\Support\YillikEgitimPlanlayici::zorunluYuzYuzeMi($e, $this->firma);
                                $onSaat = (float) ($e['online_saat'] ?? 0);
                                $yySaat = (float) ($e['yuz_yuze_saat'] ?? 0);
                                $sureMetin = $yontem === 'karma' && $onSaat > 0 && $yySaat > 0
                                    ? \App\Support\YillikEgitimPlanlayici::saatSayi($onSaat).'s 💻 + '.\App\Support\YillikEgitimPlanlayici::saatSayi($yySaat).'s 🏫'
                                    : \App\Support\YillikEgitimPlanlayici::saatMetni($e['sure_saat'] ?? null);
                            @endphp
                            <tr>
                                <td style="padding:.3rem .5rem;color:rgb(107 114 128);font-size:.75rem">{{ $egitimKategorileri[$e['kategori'] ?? ''] ?? '—' }}</td>
                                <td style="padding:.3rem .5rem;font-weight:600">
                                    {{ $e['konu'] }}
                                    @if (filled($e['hedef_kitle'] ?? null))
                                        <span style="display:inline-block;margin-left:.35rem;padding:.05rem .45rem;border-radius:9999px;font-size:.66rem;font-weight:700;background:rgb(15 118 110 / .12);color:#0f766e">👷 {{ $e['hedef_kitle'] }}</span>
                                    @endif
                                </td>
                                <td style="padding:.3rem .4rem;white-space:nowrap">
                                    <button type="button"
                                        wire:click="egitimYontemDegistir({{ $ei }})"
                                        title="{{ $zorunluYy ? 'RG 02.04.2026 Md.12/3 & Md.7 gereği yüz yüze zorunlu' : 'Değiştirmek için tıklayın (Online / Yüz Yüze / Karma)' }}"
                                        style="padding:.18rem .5rem;border-radius:9999px;font-size:.7rem;font-weight:700;border:1px solid {{ $ys['kenar'] }};background:{{ $ys['bg'] }};color:{{ $ys['renk'] }};cursor:{{ $zorunluYy ? 'not-allowed' : 'pointer' }}">
                                        {{ $ys['etiket'] }}{{ $zorunluYy ? ' 🔒' : '' }}
                                    </button>
                                </td>
                                <td style="padding:.3rem .5rem;color:rgb(71 85 105);white-space:nowrap;font-variant-numeric:tabular-nums">{{ $sureMetin }}</td>
                                <td style="padding:.3rem .5rem;color:rgb(107 114 128)">{{ $e['egitici'] ?? '—' }}</td>
                                @foreach (($e['aylar'] ?? array_fill(0, 12, 'bos')) as $ai => $durum)
                                    @php $kilitli = $ai < $kilit; @endphp
                                    <td style="padding:.15rem;text-align:center">
                                        <button type="button" @disabled($kilitli)
                                            @unless ($kilitli) wire:click="ayDurumDegistir('egitimler', {{ $ei }}, {{ $ai }})" @endunless
                                            title="{{ $kilitli ? 'Uzman atanmadan önce — seçilemez' : ($etiketler[$durum] ?? $durum) }}"
                                            style="width:1.4rem;height:1.4rem;border-radius:.25rem;border:none;background:{{ $kilitli ? '#111827' : ($renkler[$durum] ?? $renkler['bos']) }};{{ $kilitli ? 'opacity:.3;cursor:not-allowed' : 'cursor:pointer' }}"></button>
                                    </td>
                                @endforeach
                                <td style="padding:.3rem .3rem;white-space:nowrap">
                                    @if (($e['kategori'] ?? null) === 'ise_ozgu' && blank($e['kutuphane_anahtari'] ?? null))<button type="button" wire:click="mountAction('kutuphaneyeKaydet', { index: {{ $ei }} })" title="Kütüphaneye kaydet" style="color:#b45309;cursor:pointer;background:none;border:none">★</button>@endif
                                    <button type="button" wire:click="egitimSil({{ $ei }})" class="mehse-sil-dugme">✕</button>
                                </td>
                            </tr>
                        @endforeach
                    </table>
                </div>

                <div style="margin-top:1rem;display:grid;grid-template-columns:1.1fr 1.8fr 1fr .8fr 1fr 1fr auto;gap:.5rem">
                    <select wire:model="yeniEgitimKategori" title="Eğitim planındaki bölüm"
                        class="mehse-girdi-kompakt">
                        @foreach ($egitimKategorileri as $anahtar => $ad)
                            <option value="{{ $anahtar }}">{{ $ad }}</option>
                        @endforeach
                    </select>
                    <input type="text" wire:model="yeniEgitimKonu" placeholder="Eğitim konusu"
                        class="mehse-girdi-kompakt">
                    <select wire:model="yeniEgitimYontem" title="Eğitim şekli"
                        class="mehse-girdi-kompakt">
                        @foreach (\App\Support\YillikEgitimPlanlayici::YONTEMLER as $yKod => $yAd)
                            <option value="{{ $yKod }}">{{ $yAd }}</option>
                        @endforeach
                    </select>
                    <input type="number" step="0.5" wire:model="yeniEgitimSure" placeholder="Süre (saat)"
                        class="mehse-girdi-kompakt">
                    <input type="text" wire:model="yeniEgitimEgitici" placeholder="Eğitici"
                        class="mehse-girdi-kompakt">
                    <input type="text" wire:model="yeniEgitimHedefKitle" placeholder="Hedef kitle"
                        class="mehse-girdi-kompakt">
                    <x-filament::button size="sm" wire:click="egitimEkle">+ Ekle</x-filament::button>
                </div>

                <div style="margin-top:.75rem;display:flex;gap:.4rem;flex-wrap:wrap;align-items:center">
                    <x-filament::button size="xs" color="primary" icon="heroicon-o-queue-list" wire:click="mountAction('iseOzguSec')">İşe Özgü Konuları Seç</x-filament::button>
                    <x-filament::button size="xs" color="info" icon="heroicon-o-sparkles" wire:click="mountAction('aiIseOzguUret')">✨ AI ile Bu Firmanın 4. Bölümünü Planla</x-filament::button>
                    <x-filament::button size="xs" color="success" icon="heroicon-o-building-office-2" wire:click="mountAction('aiTopluFirmaPlanla')">✨ AI ile Tüm Firmaları Planla</x-filament::button>
                    <x-filament::button size="xs" color="gray" tag="a" :href="\App\Filament\Pages\IseOzguEgitimKonulari::getUrl(['firmaId' => $this->firmaId])">Konu kütüphanesi</x-filament::button>
                    <x-filament::button size="xs" color="gray" wire:click="varsayilanaSifirla">Varsayılana Sıfırla</x-filament::button>
                    <span class="mehse-soluk">Ana kaynak Yıllık Eğitim Planıdır: burada planlanan 4. bölüm ve süreler Eğitim Katılım, Boş Form ve Sertifikalara birebir aktarılır.</span>
                </div>
            </x-filament::section>
        @endif

        @if ($sekme === 'degerlendirme')
            @php
                $ydKunye = \App\Support\YillikDegerlendirmeVerisi::kunye($this->firma, $yil);
                $ydSatirlar = collect($p->degerlendirmeler ?? []);
                $ydGirdi = 'width:100%;padding:.3rem .4rem;border-radius:.3rem;border:1px solid rgb(107 114 128 / .3);background:transparent;font-size:.75rem;font-family:inherit';
                $ydTh = 'text-align:left;padding:.35rem .5rem;border-bottom:1px solid rgb(107 114 128 / .3);font-size:.75rem';
            @endphp
            <x-filament::section icon="heroicon-o-clipboard-document-check" icon-color="primary">
                <x-slot name="heading">İSG Yıllık Değerlendirme Raporu — {{ $yil }}</x-slot>
                <x-slot name="description">Dönem: {{ $ydKunye['donem'] }} (atandığınız tarih → yıl sonu). Satırlar sayfa açılınca sistem kayıtlarından yenilenir; elle düzelttiğiniz satır korunur. Çıktıda görevli adı yazılmaz, imza alanına kaşe basılır.</x-slot>

                <div style="font-size:.75rem;color:rgb(107 114 128);margin-bottom:.6rem">
                    NACE / Faaliyet: {{ $ydKunye['nace'] ?: '—' }} · Çalışan: Erkek {{ $ydKunye['erkek'] }} / Kadın {{ $ydKunye['kadin'] }} / Toplam {{ $ydKunye['toplam'] }} | Genç {{ $ydKunye['genc'] }} / Çocuk {{ $ydKunye['cocuk'] }}
                    · Sistemden dolan: <strong>{{ $ydSatirlar->where('otomatik', true)->count() }}</strong> / {{ $ydSatirlar->count() }} satır
                </div>

                <div style="overflow-x:auto">
                    <table style="width:100%;border-collapse:collapse;font-size:.76rem;min-width:1000px">
                        <tr>
                            <th style="{{ $ydTh }};width:2.5rem">No</th>
                            <th style="{{ $ydTh }};width:9rem">Tarih / Dönem</th>
                            <th style="{{ $ydTh }};width:12rem">Yapılan çalışma</th>
                            <th style="{{ $ydTh }};width:10rem">Yapan / Ünvan</th>
                            <th style="{{ $ydTh }};width:11rem">Yöntem / Kanıt</th>
                            <th style="{{ $ydTh }}">Sonuç ve yorum</th>
                            <th style="{{ $ydTh }};width:3.5rem"></th>
                        </tr>
                        @php $no = 0; @endphp
                        @foreach (($p->degerlendirmeler ?? []) as $di => $d)
                            @continue(($d['tur'] ?? 'calisma') === 'genel')
                            @php $no++; @endphp
                            <tr wire:key="yd-{{ $di }}-{{ md5(json_encode($d)) }}" style="border-bottom:1px solid rgb(107 114 128 / .12);vertical-align:top">
                                <td style="padding:.3rem .5rem">{{ $no }}</td>
                                <td style="padding:.15rem"><input type="text" value="{{ $d['tarih'] ?? '' }}" placeholder="… / … / …" x-on:change="$wire.degerlendirmeGuncelle({{ $di }}, 'tarih', $event.target.value)" style="{{ $ydGirdi }}"></td>
                                <td style="padding:.3rem .5rem;font-weight:600">{{ $d['calisma'] }}
                                    @if ($d['otomatik'] ?? false)<div style="font-size:.68rem;color:rgb(21 128 61);font-weight:500">● sistemden</div>@elseif ($d['elle'] ?? false)<div style="font-size:.68rem;color:rgb(180 83 9);font-weight:500">✎ elle</div>@else<div style="font-size:.68rem;color:rgb(107 114 128);font-weight:500">kayıt yok — şablon</div>@endif
                                </td>
                                <td style="padding:.15rem"><textarea rows="2" x-on:change="$wire.degerlendirmeGuncelle({{ $di }}, 'yapan_kisi', $event.target.value)" style="{{ $ydGirdi }}">{{ $d['yapan_kisi'] ?? '' }}</textarea></td>
                                <td style="padding:.15rem"><textarea rows="2" x-on:change="$wire.degerlendirmeGuncelle({{ $di }}, 'yontem', $event.target.value)" style="{{ $ydGirdi }}">{{ $d['yontem'] ?? '' }}</textarea></td>
                                <td style="padding:.15rem"><textarea rows="3" x-on:change="$wire.degerlendirmeGuncelle({{ $di }}, 'sonuc', $event.target.value)" style="{{ $ydGirdi }}">{{ $d['sonuc'] ?? '' }}</textarea></td>
                                <td style="padding:.3rem .2rem;white-space:nowrap">
                                    @if (($d['elle'] ?? false) && ! str_starts_with((string) ($d['anahtar'] ?? ''), 'ek_'))
                                        <button type="button" wire:click="degerlendirmeOtomatigeDondur({{ $di }})" title="Sistem kaydına dön" style="color:rgb(15 118 110);cursor:pointer;background:none;border:none">↺</button>
                                    @endif
                                    <button type="button" wire:click="degerlendirmeSil({{ $di }})" wire:confirm="Satır silinsin mi?" class="mehse-sil-dugme">✕</button>
                                </td>
                            </tr>
                        @endforeach
                    </table>
                </div>

                <div style="margin-top:1rem;display:grid;grid-template-columns:1fr auto;gap:.5rem">
                    <input type="text" wire:model="yeniDegerlendirmeCalisma" placeholder="Ek çalışma (şablon dışı)"
                        class="mehse-girdi-kompakt">
                    <x-filament::button size="sm" wire:click="degerlendirmeEkle">+ Çalışma Ekle</x-filament::button>
                </div>

                @if ($ydSatirlar->where('tur', 'genel')->isNotEmpty())
                    <div style="margin-top:1.2rem;font-weight:700;font-size:.85rem">Genel sonuç, iyileştirme kararları ve gelecek yıl önerileri</div>
                    <table style="width:100%;border-collapse:collapse;font-size:.76rem;margin-top:.4rem">
                        @foreach (($p->degerlendirmeler ?? []) as $di => $d)
                            @continue(($d['tur'] ?? 'calisma') !== 'genel')
                            <tr wire:key="ydg-{{ $di }}-{{ md5(json_encode($d)) }}" style="border-bottom:1px solid rgb(107 114 128 / .12);vertical-align:top">
                                <td style="padding:.35rem .5rem;font-weight:600;width:14rem">{{ $d['calisma'] }}@if ($d['otomatik'] ?? false)<div style="font-size:.68rem;color:rgb(21 128 61);font-weight:500">● sistemden</div>@endif</td>
                                <td style="padding:.15rem"><textarea rows="2" x-on:change="$wire.degerlendirmeGuncelle({{ $di }}, 'sonuc', $event.target.value)" style="{{ $ydGirdi }}">{{ $d['sonuc'] ?? '' }}</textarea></td>
                            </tr>
                        @endforeach
                    </table>
                @endif

                <div style="margin-top:.75rem">
                    <x-filament::button size="xs" color="gray" wire:click="varsayilanaSifirla" wire:confirm="Değerlendirme şablona sıfırlanıp sistem kayıtlarından yeniden doldurulsun mu? Elle yazdıklarınız silinir.">Şablona Sıfırla</x-filament::button>
                </div>
            </x-filament::section>
        @endif
    @else
        <p class="mehse-uyari-not">Devam etmek için bir firma seçin.</p>
    @endif
</x-filament-panels::page>
