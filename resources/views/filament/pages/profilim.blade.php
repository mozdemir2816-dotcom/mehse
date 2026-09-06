@php
    $sekmeler = \App\Filament\Pages\Profilim::SEKMELER;
    $u = $this->kullanici;
    $o = $this->ozet;
    $mor = 'rgb(139 92 246)';
    $turkuaz = 'rgb(20 184 166)';
    $yesil = 'rgb(34 197 94)';
    $sari = 'rgb(245 158 11)';
    $kirmizi = 'rgb(239 68 68)';
    $kutu = 'border:1px solid rgb(128 116 148 / .3);border-radius:.75rem;padding:1rem';
    // Md.4 "önerilen" — öne çıkması gereken kartlar için belirgin gölge (çerçeve yerine).
    $golge = 'border:1px solid rgb(128 116 148 / .18);border-radius:.75rem;padding:1rem;'
        .'box-shadow:0 22px 46px -14px rgb(0 0 0 / .6), 0 8px 18px -6px rgb(0 0 0 / .5)';
    $bas = strtoupper(mb_substr($u->name ?? '?', 0, 1));
@endphp

<x-filament-panels::page>
    {{-- KÜNYE --}}
    <div style="{{ $golge }};display:flex;align-items:center;gap:1rem;flex-wrap:wrap">
        <div style="width:3.5rem;height:3.5rem;border-radius:9999px;background:{{ $mor }};color:#fff;display:flex;align-items:center;justify-content:center;font-size:1.3rem;font-weight:800;flex-shrink:0">{{ $bas }}</div>
        <div style="flex:1;min-width:180px">
            <div style="font-size:1.15rem;font-weight:700">{{ $u->name }}</div>
            <div style="font-size:.82rem;color:rgb(128 116 148)">{{ $u->email }}{{ $u->telefon ? ' · '.$u->telefon : '' }}</div>
        </div>
        <div style="display:flex;gap:.5rem;flex-wrap:wrap;align-items:center">
            <x-filament::badge>{{ $u->unvanEtiketi() }}</x-filament::badge>
            <x-filament::badge :color="$u->kase_gorseli ? 'success' : 'warning'">
                {{ $u->kase_gorseli ? 'Kaşe yüklü' : 'Kaşe eksik' }}
            </x-filament::badge>
            <x-filament::badge color="success">● Aktif</x-filament::badge>
            <x-filament::button size="sm" color="gray" icon="heroicon-o-cog-6-tooth" tag="a" :href="filament()->getProfileUrl()">
                Hesap Ayarları
            </x-filament::button>
        </div>
    </div>

    @if ($u->kase_gorseli || $u->imza_gorseli)
        <div style="{{ $kutu }};display:flex;gap:1.5rem;flex-wrap:wrap;align-items:center">
            <span style="font-weight:600;font-size:.85rem">Belge çıktısı görselleri:</span>
            @if ($u->kase_gorseli)
                <span style="text-align:center;font-size:.72rem;color:rgb(128 116 148)">
                    <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($u->kase_gorseli) }}" alt="Kaşe"
                        style="max-height:3.5rem;display:block;margin:0 auto .2rem">Kaşe
                </span>
            @endif
            @if ($u->imza_gorseli)
                <span style="text-align:center;font-size:.72rem;color:rgb(128 116 148)">
                    <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($u->imza_gorseli) }}" alt="İmza"
                        style="max-height:3.5rem;display:block;margin:0 auto .2rem">İmza
                </span>
            @endif
        </div>
    @endif

    {{-- SAYAÇ KARTLARI --}}
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(120px,1fr));gap:.6rem">
        @foreach ([
            ['Firmalarım', $o['firma'], 'heroicon-o-building-office-2', $mor],
            ['Çalışanlarım', $o['calisan'], 'heroicon-o-user-group', $yesil],
            ['Risk Değerlendirmesi', $o['risk_degerlendirmesi'], 'heroicon-o-sparkles', $sari],
            ['Risk Şablonları', $o['risk_sablonu'], 'heroicon-o-rectangle-stack', $mor],
            ['Önemli Risk', $o['onemli_risk'], 'heroicon-o-exclamation-triangle', $kirmizi],
            ['Raporlarım', 0, 'heroicon-o-document-text', 'rgb(128 116 148)'],
        ] as [$etiket, $deger, $ikon, $renk])
            <div style="{{ $golge }};padding:.75rem">
                <div style="display:flex;align-items:center;gap:.4rem;font-size:.7rem;color:rgb(128 116 148);text-transform:uppercase">
                    <x-filament::icon :icon="$ikon" style="width:.95rem;height:.95rem"/> {{ $etiket }}
                </div>
                <div style="font-size:1.4rem;font-weight:800;color:{{ $renk }}">{{ $deger }}</div>
            </div>
        @endforeach
    </div>

    {{-- SEKME PILL'LERİ --}}
    <div style="display:flex;gap:.4rem;flex-wrap:wrap;background:rgb(128 116 148 / .1);padding:.3rem;border-radius:.6rem">
        @foreach ($sekmeler as $anahtar => $ad)
            @php $aktif = $sekme === $anahtar; @endphp
            <button type="button" wire:click="sekmeSec('{{ $anahtar }}')"
                style="padding:.5rem .85rem;border:none;border-radius:.45rem;cursor:pointer;font-weight:600;font-size:.83rem;
                    background:{{ $aktif ? $turkuaz : 'transparent' }};color:{{ $aktif ? '#fff' : 'inherit' }}">
                {{ $ad }}
            </button>
        @endforeach
    </div>

    {{-- ==================== GENEL BAKIŞ ==================== --}}
    @if ($sekme === 'genel')
        {{-- Genel Bakış mini istatistikleri (isgpratik 6.jpg) --}}
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:.6rem">
            @foreach ([
                ['Eğitimsiz Çalışan', '—', 'Hiç eğitim kaydı olmayan (modül yakında)', 'rgb(128 116 148)'],
                ['Önemli Risk', $o['onemli_risk'], 'Risk skoru >'.config('isg.onemli_risk_esigi').' olan maddeler', $kirmizi],
                ['Çalışansız Firma', $o['calisansiz_firma'], 'Hiç aktif çalışan eklenmemiş firma', $sari],
                ['Evrak Eksiği', $o['evrak_eksigi'], 'Risk değerlendirmesi olmayan firma', $sari],
            ] as [$etiket, $deger, $alt, $renk])
                <div style="{{ $kutu }};padding:.7rem">
                    <div style="font-size:.7rem;color:rgb(128 116 148);text-transform:uppercase">{{ $etiket }}</div>
                    <div style="font-size:1.35rem;font-weight:800;color:{{ $renk }}">{{ $deger }}</div>
                    <div style="font-size:.68rem;color:rgb(128 116 148)">{{ $alt }}</div>
                </div>
            @endforeach
        </div>

        {{-- Dönemsel Aktivite Trendi (son 30 gün) --}}
        @php
            $akt = $this->aktivite;
            $son30 = array_slice($akt, -30, null, true);
            $enYuksek = max(1, max($son30 ?: [0]));
            $noktalar = [];
            $idx = 0; $adet = count($son30);
            foreach ($son30 as $sayi) {
                $x = $adet > 1 ? round($idx / ($adet - 1) * 100, 2) : 0;
                $y = round(100 - ($sayi / $enYuksek * 100), 2);
                $noktalar[] = "$x,$y";
                $idx++;
            }
        @endphp
        <div style="{{ $kutu }}">
            <div style="font-weight:700">Dönemsel Aktivite Trendi</div>
            <div style="font-size:.78rem;color:rgb(128 116 148);margin-bottom:.5rem">Son 30 gün — risk / şablon / firma / çalışan eklemeleri</div>
            <svg viewBox="0 0 100 100" preserveAspectRatio="none" style="width:100%;height:90px">
                <polyline points="{{ implode(' ', $noktalar) }}" fill="none" stroke="{{ $mor }}" stroke-width="1.5" vector-effect="non-scaling-stroke"/>
            </svg>
        </div>

        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(260px,1fr));gap:1rem">
            {{-- Uyumluluk skoru --}}
            <div style="{{ $kutu }};text-align:center">
                <div style="font-weight:700">Uyumluluk Skoru</div>
                @php $y = $o['uyum_yuzde']; $renk = $y < 40 ? $kirmizi : ($y < 75 ? $sari : $yesil); @endphp
                <div style="width:9rem;height:9rem;margin:1rem auto;border-radius:9999px;
                    background:conic-gradient({{ $renk }} {{ $y * 3.6 }}deg, rgb(128 116 148 / .2) 0);
                    display:flex;align-items:center;justify-content:center">
                    <div style="width:6.5rem;height:6.5rem;border-radius:9999px;background:var(--fi-color-white,#fff);
                        display:flex;flex-direction:column;align-items:center;justify-content:center;color:#111">
                        <span style="font-size:1.6rem;font-weight:800;color:{{ $renk }}">%{{ $y }}</span>
                        <span style="font-size:.65rem;color:#666">portföy geneli</span>
                    </div>
                </div>
                <div style="font-size:.8rem;color:rgb(128 116 148)">Takip edilen yasal kriterlerin ortalama karşılanma oranı</div>
            </div>

            {{-- Tehlike sınıfı dağılımı --}}
            <div style="{{ $kutu }}">
                <div style="font-weight:700">Tehlike Sınıfı Dağılımı</div>
                <div style="font-size:.8rem;color:rgb(128 116 148);margin-bottom:.6rem">Toplam {{ $o['firma'] }} firma</div>
                @php $renkler = [$yesil, $sari, $kirmizi]; $i = 0; @endphp
                @foreach ($o['tehlike_dagilimi'] as $ad => $sayi)
                    @php $yuzde = $o['firma'] > 0 ? round($sayi / $o['firma'] * 100) : 0; $c = $renkler[$i++ % 3]; @endphp
                    <div style="margin-bottom:.5rem">
                        <div style="display:flex;justify-content:space-between;font-size:.82rem">
                            <span>{{ $ad }}</span><strong>{{ $sayi }} · %{{ $yuzde }}</strong>
                        </div>
                        <div style="height:6px;border-radius:9999px;background:rgb(128 116 148 / .2);margin-top:.25rem;overflow:hidden">
                            <div style="height:100%;width:{{ max($yuzde, 1) }}%;background:{{ $c }}"></div>
                        </div>
                    </div>
                @endforeach
            </div>

            {{-- İlk yardım ihtiyacı --}}
            <div style="{{ $kutu }}">
                <div style="font-weight:700">İlk Yardım Sertifikası</div>
                <div style="font-size:.72rem;color:rgb(128 116 148);text-transform:uppercase;margin-top:.5rem">Toplam İlkyardımcı İhtiyacı</div>
                <div style="font-size:1.8rem;font-weight:800;color:{{ $yesil }}">{{ $this->ilkyardimciIhtiyaci }} kişi</div>
                <p style="font-size:.78rem;color:rgb(128 116 148);margin-top:.4rem">
                    Aktif firmalarınızın bildirdiği çalışan sayısına göre — İlkyardım Yönetmeliği md.19
                    (çok tehlikeli her 10, tehlikeli her 15, az tehlikeli her 20 çalışana 1 ilkyardımcı).
                </p>
            </div>

            {{-- Performans Profili (5 eksen) --}}
            @php
                $eksenler = $this->performansEksenleri;
                $n = count($eksenler);
                $merkez = 50; $yaricap = 42;
                $noktaHesapla = fn (float $yuzde, int $i) => [
                    $merkez + $yaricap * ($yuzde / 100) * cos((-90 + $i * 360 / $n) * M_PI / 180),
                    $merkez + $yaricap * ($yuzde / 100) * sin((-90 + $i * 360 / $n) * M_PI / 180),
                ];
                $poligonNoktalari = collect($eksenler)->values()
                    ->map(fn ($y, $i) => implode(',', $noktaHesapla($y, $i)))
                    ->implode(' ');
                $izgaraNoktalari = fn (float $yuzde) => collect(range(0, $n - 1))
                    ->map(fn ($i) => implode(',', $noktaHesapla($yuzde, $i)))
                    ->implode(' ');
            @endphp
            <div style="{{ $kutu }}">
                <div style="font-weight:700">Performans Profili</div>
                <div style="font-size:.78rem;color:rgb(128 116 148);margin-bottom:.4rem">Yalnız kurulu modüllerin portföy ortalaması</div>
                <svg viewBox="0 0 100 100" style="width:100%;max-width:220px;display:block;margin:0 auto">
                    @foreach ([25, 50, 75, 100] as $izgaraYuzde)
                        <polygon points="{{ $izgaraNoktalari($izgaraYuzde) }}" fill="none" stroke="rgb(128 116 148 / .2)" stroke-width="0.5"/>
                    @endforeach
                    @foreach (range(0, $n - 1) as $i)
                        @php [$x, $y] = $noktaHesapla(100, $i); @endphp
                        <line x1="{{ $merkez }}" y1="{{ $merkez }}" x2="{{ $x }}" y2="{{ $y }}" stroke="rgb(128 116 148 / .2)" stroke-width="0.5"/>
                    @endforeach
                    <polygon points="{{ $poligonNoktalari }}" fill="{{ $turkuaz }}" fill-opacity="0.25" stroke="{{ $turkuaz }}" stroke-width="1.5"/>
                </svg>
                <div style="display:flex;flex-direction:column;gap:.2rem;margin-top:.5rem">
                    @foreach ($eksenler as $ad => $yuzde)
                        <div style="display:flex;justify-content:space-between;font-size:.72rem">
                            <span style="color:rgb(128 116 148)">{{ $ad }}</span><strong>%{{ $yuzde }}</strong>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:1rem">
            {{-- Çalışan Dağılımı --}}
            <div style="{{ $kutu }};max-height:20rem;overflow-y:auto">
                <div style="font-weight:700">Çalışan Dağılımı</div>
                <div style="font-size:.78rem;color:rgb(128 116 148);margin-bottom:.5rem">Firma başına aktif çalışan sayısı</div>
                @forelse ($this->calisanDagilimi as $firmaAd => $sayi)
                    <div style="display:flex;justify-content:space-between;font-size:.82rem;padding:.3rem 0;border-top:1px solid rgb(128 116 148 / .12)">
                        <span style="overflow:hidden;text-overflow:ellipsis;white-space:nowrap;max-width:14rem">{{ $firmaAd }}</span>
                        <strong>{{ $sayi }}</strong>
                    </div>
                @empty
                    <div style="font-size:.82rem;color:rgb(128 116 148)">Firma yok.</div>
                @endforelse
            </div>

            {{-- Son 90 Gün Aktivite (heatmap) --}}
            <div style="{{ $kutu }}">
                <div style="display:flex;justify-content:space-between;align-items:center">
                    <div style="font-weight:700">Son 90 Gün Aktivite</div>
                    <div style="display:flex;align-items:center;gap:.25rem;font-size:.7rem;color:rgb(128 116 148)">
                        Az
                        @foreach (['rgb(128 116 148 / .2)','rgb(139 92 246 / .35)','rgb(139 92 246 / .6)','rgb(139 92 246 / .85)','rgb(139 92 246)'] as $c)
                            <span style="width:.7rem;height:.7rem;border-radius:2px;background:{{ $c }}"></span>
                        @endforeach
                        Çok
                    </div>
                </div>
                @php $enYuksekAkt = max(1, max($this->aktivite ?: [0])); @endphp
                <div style="display:grid;grid-template-columns:repeat(18,1fr);gap:3px;margin-top:.6rem">
                    @foreach ($this->aktivite as $gun => $sayi)
                        @php
                            $seviye = $sayi === 0 ? 0 : (int) ceil($sayi / $enYuksekAkt * 4);
                            $renkler = ['rgb(128 116 148 / .2)','rgb(139 92 246 / .35)','rgb(139 92 246 / .6)','rgb(139 92 246 / .85)','rgb(139 92 246)'];
                        @endphp
                        <span title="{{ \Illuminate\Support\Carbon::parse($gun)->format('d.m.Y') }} — {{ $sayi }} işlem"
                            style="aspect-ratio:1;border-radius:2px;background:{{ $renkler[$seviye] }}"></span>
                    @endforeach
                </div>
            </div>
        </div>
    @endif

    {{-- ==================== FİRMALAR ==================== --}}
    @if ($sekme === 'firmalar')
        <div style="{{ $kutu }};overflow-x:auto">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:.6rem">
                <div style="font-weight:700">Firmalarım ({{ $this->firmalar->count() }})</div>
                <x-filament::button size="xs" tag="a" :href="\App\Filament\Resources\Firmas\FirmaResource::getUrl()">Firmalar sayfası →</x-filament::button>
            </div>
            <table style="width:100%;border-collapse:collapse;font-size:.83rem">
                <thead><tr style="text-align:left;color:rgb(128 116 148)">
                    <th style="padding:.4rem">Firma</th><th style="padding:.4rem">SGK Sicil</th>
                    <th style="padding:.4rem">Çalışan</th><th style="padding:.4rem">Tehlike Sınıfı</th>
                </tr></thead>
                <tbody>
                @forelse ($this->firmalar as $f)
                    <tr style="border-top:1px solid rgb(128 116 148 / .15)">
                        <td style="padding:.4rem;font-weight:600">{{ $f->unvan }}</td>
                        <td style="padding:.4rem">{{ $f->sgk_sicil_no ?: '—' }}</td>
                        <td style="padding:.4rem">{{ $f->calisanlar_count ?: $f->calisan_sayisi }}</td>
                        <td style="padding:.4rem"><x-filament::badge>{{ $f->tehlikeSinifiEtiketi() }}</x-filament::badge></td>
                    </tr>
                @empty
                    <tr><td colspan="4" style="padding:1rem;text-align:center;color:rgb(128 116 148)">Henüz firma eklenmemiş.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    @endif

    {{-- ==================== ÇALIŞANLAR ==================== --}}
    @if ($sekme === 'calisanlar')
        <div style="{{ $kutu }};overflow-x:auto">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:.6rem">
                <div style="font-weight:700">Çalışanlarım ({{ $this->calisanlar->count() }})</div>
                <x-filament::button size="xs" tag="a" :href="\App\Filament\Resources\Calisans\CalisanResource::getUrl()">Çalışanlar sayfası →</x-filament::button>
            </div>
            @if ($this->calisanlar->isEmpty())
                <div style="text-align:center;padding:2rem;color:rgb(128 116 148)">
                    <div style="font-size:1.75rem">👥</div>Henüz çalışan eklenmemiş.
                </div>
            @else
                <table style="width:100%;border-collapse:collapse;font-size:.83rem">
                    <thead><tr style="text-align:left;color:rgb(128 116 148)">
                        <th style="padding:.4rem">Ad Soyad</th><th style="padding:.4rem">Firma</th>
                        <th style="padding:.4rem">Görev</th><th style="padding:.4rem">Durum</th>
                    </tr></thead>
                    <tbody>
                    @foreach ($this->calisanlar as $c)
                        <tr style="border-top:1px solid rgb(128 116 148 / .15)">
                            <td style="padding:.4rem;font-weight:600">{{ $c->ad_soyad }}</td>
                            <td style="padding:.4rem">{{ $c->firma?->unvan ?: '—' }}</td>
                            <td style="padding:.4rem">{{ $c->gorev ?: '—' }}</td>
                            <td style="padding:.4rem">
                                <x-filament::badge :color="$c->aktif ? 'success' : 'gray'">{{ $c->aktif ? 'Aktif' : 'Ayrıldı' }}</x-filament::badge>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            @endif
        </div>
    @endif

    {{-- ==================== EĞİTİMLER ==================== --}}
    @if ($sekme === 'egitimler')
        @php
            $durumRenk = ['gecerli' => $yesil, 'yakinda' => $sari, 'dolmus' => $kirmizi];
            $durumEtiket = ['gecerli' => 'Geçerli', 'yakinda' => 'Yakında', 'dolmus' => 'Dolmuş', 'eksik' => 'Eksik'];
            $ozet = $this->egitimOzet;
        @endphp
        <div style="{{ $kutu }};overflow-x:auto">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:.6rem;flex-wrap:wrap;gap:.5rem">
                <div>
                    <div style="font-weight:700">Eğitim Kayıtları ({{ $ozet['toplam'] }} çalışan · {{ $ozet['eksik'] }} eksik)</div>
                    <div style="font-size:.78rem;color:rgb(128 116 148)">Çalışan × eğitim türü tamamlanma tarihi. Yeşil = geçerli, sarı = 60 gün içinde dolacak, kırmızı = dolmuş/eksik. Varsayılan olarak yalnız Temel İSG Eğitimi takip edilir — "Konu Ekle" ile yeni sütun eklenir.</div>
                </div>
                <div style="display:flex;gap:.4rem">
                    {{ $this->egitimSablonIndirAction }}
                    {{ $this->egitimYukleAction }}
                    {{ $this->egitimTuruEkleAction }}
                </div>
            </div>

            {{-- AKTİF / İŞTEN AYRILAN + ARAMA + FİRMA --}}
            <div style="display:flex;flex-wrap:wrap;gap:.5rem;align-items:center;margin-bottom:.6rem">
                <div style="display:flex;background:rgb(128 116 148 / .08);border-radius:9999px;padding:.2rem">
                    <button type="button" wire:click="$set('egitimDurumTab','aktif')"
                        style="padding:.3rem .8rem;border-radius:9999px;border:none;cursor:pointer;font-size:.78rem;font-weight:600;
                            background:{{ $egitimDurumTab === 'aktif' ? $mor : 'transparent' }};color:{{ $egitimDurumTab === 'aktif' ? '#fff' : 'inherit' }}">Aktif Personel</button>
                    <button type="button" wire:click="$set('egitimDurumTab','ayrilan')"
                        style="padding:.3rem .8rem;border-radius:9999px;border:none;cursor:pointer;font-size:.78rem;font-weight:600;
                            background:{{ $egitimDurumTab === 'ayrilan' ? $mor : 'transparent' }};color:{{ $egitimDurumTab === 'ayrilan' ? '#fff' : 'inherit' }}">İşten Ayrılanlar</button>
                </div>
                <input type="text" wire:model.live.debounce.400ms="egitimArama" placeholder="Çalışan ara..."
                    style="padding:.35rem .7rem;border-radius:.5rem;border:1px solid rgb(128 116 148 / .35);background:transparent;font-size:.8rem;min-width:12rem">
                <select wire:model.live="egitimFirmaId"
                    style="padding:.35rem .6rem;border-radius:.5rem;border:1px solid rgb(128 116 148 / .35);background:transparent;font-size:.8rem">
                    <option value="0">Tüm Firmalar</option>
                    @foreach ($this->egitimFirmalari as $id => $ad)
                        <option value="{{ $id }}">{{ $ad }}</option>
                    @endforeach
                </select>
            </div>

            {{-- KATEGORİ FİLTRESİ --}}
            @php $kullanilanKategoriler = $this->egitimTurleri->map(fn ($t) => $t->kategori() ?? 'ozel')->unique(); @endphp
            @if ($kullanilanKategoriler->count() > 1)
                <div style="display:flex;flex-wrap:wrap;gap:.3rem;margin-bottom:.4rem">
                    <span style="font-size:.72rem;color:rgb(128 116 148);align-self:center;font-weight:700">KATEGORİ</span>
                    <button type="button" wire:click="$set('egitimKategoriFiltre','')"
                        style="padding:.15rem .6rem;border-radius:9999px;font-size:.72rem;cursor:pointer;
                            border:1px solid {{ $egitimKategoriFiltre === '' ? $mor : 'rgb(128 116 148 / .3)' }};
                            background:{{ $egitimKategoriFiltre === '' ? 'rgb(139 92 246 / .1)' : 'transparent' }}">Tümü</button>
                    @foreach ($kullanilanKategoriler as $kat)
                        <button type="button" wire:click="$set('egitimKategoriFiltre','{{ $kat }}')"
                            style="padding:.15rem .6rem;border-radius:9999px;font-size:.72rem;cursor:pointer;
                                border:1px solid {{ $egitimKategoriFiltre === $kat ? $mor : 'rgb(128 116 148 / .3)' }};
                                background:{{ $egitimKategoriFiltre === $kat ? 'rgb(139 92 246 / .1)' : 'transparent' }}">{{ \App\Filament\Pages\Profilim::EGITIM_KATEGORI_ETIKETLERI[$kat] ?? $kat }}</button>
                    @endforeach
                </div>
            @endif

            {{-- DURUM FİLTRESİ --}}
            <div style="display:flex;flex-wrap:wrap;gap:.3rem;margin-bottom:.7rem">
                <span style="font-size:.72rem;color:rgb(128 116 148);align-self:center;font-weight:700">DURUM</span>
                <button type="button" wire:click="$set('egitimDurumFiltre','')"
                    style="padding:.15rem .6rem;border-radius:9999px;font-size:.72rem;cursor:pointer;
                        border:1px solid {{ $egitimDurumFiltre === '' ? $mor : 'rgb(128 116 148 / .3)' }};
                        background:{{ $egitimDurumFiltre === '' ? 'rgb(139 92 246 / .1)' : 'transparent' }}">Tümü</button>
                @foreach ($durumEtiket as $anahtar => $etiket)
                    <button type="button" wire:click="$set('egitimDurumFiltre','{{ $anahtar }}')"
                        style="padding:.15rem .6rem;border-radius:9999px;font-size:.72rem;cursor:pointer;
                            border:1px solid {{ $egitimDurumFiltre === $anahtar ? ($durumRenk[$anahtar] ?? $mor) : 'rgb(128 116 148 / .3)' }};
                            color:{{ $egitimDurumFiltre === $anahtar ? ($durumRenk[$anahtar] ?? $mor) : 'inherit' }};
                            background:{{ $egitimDurumFiltre === $anahtar ? 'rgb(139 92 246 / .08)' : 'transparent' }}">{{ $etiket }}</button>
                @endforeach
            </div>

            @if (empty($this->egitimMatrisi))
                <div style="text-align:center;padding:2rem;color:rgb(128 116 148)">
                    {{ $egitimDurumTab === 'ayrilan' ? 'İşten ayrılan çalışan bulunamadı.' : 'Filtrelere uyan aktif çalışan bulunamadı.' }}
                </div>
            @else
                <table style="border-collapse:collapse;font-size:.76rem;white-space:nowrap">
                    <thead>
                        <tr style="color:rgb(128 116 148)">
                            <th style="padding:.35rem;text-align:left;position:sticky;left:0;background:inherit">Çalışan</th>
                            @foreach ($this->egitimTurleriGorunen as $t)
                                <th style="padding:.35rem;text-align:center;max-width:5.5rem;white-space:normal">
                                    {{ $t->ad }}
                                    <button type="button" title="Takipten kaldır" wire:click="egitimTuruKaldir('{{ $t->anahtar }}')"
                                        wire:confirm="'{{ $t->ad }}' takipten kaldırılsın mı? Geçmiş eğitim kayıtları silinmez."
                                        style="border:none;background:none;color:{{ $kirmizi }};cursor:pointer;font-weight:700;margin-left:.2rem">×</button>
                                </th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($this->egitimMatrisi as $satir)
                            <tr style="border-top:1px solid rgb(128 116 148 / .15)">
                                <td style="padding:.35rem;font-weight:600;position:sticky;left:0;background:inherit">
                                    {{ $satir['calisan']->ad_soyad }}
                                    <div style="font-weight:400;font-size:.7rem;color:rgb(128 116 148)">{{ $satir['calisan']->firma?->unvan }}</div>
                                </td>
                                @foreach ($this->egitimTurleriGorunen as $t)
                                    @php $h = $satir['hucreler'][$t->anahtar]; @endphp
                                    <td style="padding:.35rem;text-align:center;color:{{ $h['durum'] ? $durumRenk[$h['durum']] : $kirmizi }}">
                                        {{ $h['tarih'] ? $h['tarih']->format('d.m.Y') : 'Eksik' }}
                                    </td>
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>
    @endif

    {{-- ==================== FİRMA TAKİP (evrak matrisi) ==================== --}}
    @if ($sekme === 'firma_takip')
        <div style="{{ $kutu }};overflow-x:auto">
            <div style="margin-bottom:.6rem">
                <div style="font-weight:700">Firma × Yasal Kriter Matrisi</div>
                <div style="font-size:.8rem;color:rgb(128 116 148)">
                    Her firmanın {{ count($this->firmaTakipKriterleri) }} kriteri karşılama durumu.
                    Koyu ✓/✗ = mehse'de gerçek modül var; açık ✗ = modül henüz kurulmadı. Oran yalnız gerçek modüllere göre hesaplanır.
                </div>
            </div>
            <table style="border-collapse:collapse;font-size:.78rem;white-space:nowrap">
                <thead><tr style="color:rgb(128 116 148)">
                    <th style="padding:.35rem;text-align:left;position:sticky;left:0;background:inherit">Firma</th>
                    @foreach ($this->firmaTakipKriterleri as $k)
                        <th style="padding:.35rem;text-align:center;max-width:5rem;white-space:normal">{{ $k['ad'] }}</th>
                    @endforeach
                    <th style="padding:.35rem;text-align:center">Oran</th>
                </tr></thead>
                <tbody>
                @forelse ($this->firmaMatrisi as $satir)
                    <tr style="border-top:1px solid rgb(128 116 148 / .15)">
                        <td style="padding:.35rem;font-weight:600;position:sticky;left:0;background:inherit">{{ $satir['firma']->unvan }}</td>
                        @foreach ($this->firmaTakipKriterleri as $k)
                            @php $var = $satir['hucreler'][$k['anahtar']]; $renk = $var ? $yesil : ($k['hazir'] ? $kirmizi : 'rgb(239 68 68 / .55)'); @endphp
                            <td style="padding:.35rem;text-align:center;color:{{ $renk }}" title="{{ $k['hazir'] ? 'Gerçek modül' : 'Henüz modül yok' }}">
                                {{ $var ? '✓' : '✗' }}
                            </td>
                        @endforeach
                        <td style="padding:.35rem;text-align:center;font-weight:700">%{{ $satir['oran'] }}</td>
                    </tr>
                @empty
                    <tr><td colspan="{{ count($this->firmaTakipKriterleri) + 2 }}" style="padding:1rem;text-align:center;color:rgb(128 116 148)">Firma yok.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    @endif

    {{-- ==================== RİSKLERİM ==================== --}}
    @if ($sekme === 'risklerim')
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(240px,1fr));gap:1rem">
            <div style="{{ $kutu }}">
                <div style="font-weight:700">Sektör Şablonları</div>
                <div style="font-size:1.5rem;font-weight:800;color:{{ $mor }}">{{ $o['risk_sablonu'] }}</div>
                <p style="font-size:.8rem;color:rgb(128 116 148)">Sektöre etiketli risk setleri; sihirbazda tek tıkla uygulanır.</p>
                <x-filament::button size="xs" tag="a" :href="\App\Filament\Resources\RiskSablonus\RiskSablonuResource::getUrl()">Şablonları yönet →</x-filament::button>
            </div>
            <div style="{{ $kutu }}">
                <div style="font-weight:700">Risk Kütüphanesi</div>
                <div style="font-size:1.5rem;font-weight:800;color:{{ $yesil }}">{{ \App\Models\Tehlike::count() }}</div>
                <p style="font-size:.8rem;color:rgb(128 116 148)">Kategoriye göre gruplu tehlike/önlem maddeleri.</p>
                <x-filament::button size="xs" tag="a" :href="\App\Filament\Resources\Tehlikes\TehlikeResource::getUrl()">Kütüphaneye git →</x-filament::button>
            </div>
            <div style="{{ $kutu }}">
                <div style="font-weight:700">Risk Değerlendirmeleri</div>
                <div style="font-size:1.5rem;font-weight:800;color:{{ $sari }}">{{ $o['risk_degerlendirmesi'] }}</div>
                <p style="font-size:.8rem;color:rgb(128 116 148)">Kayıtlı firma risk raporları.</p>
                <x-filament::button size="xs" tag="a" :href="\App\Filament\Pages\RiskSihirbazi::getUrl()">Yeni sihirbaz →</x-filament::button>
            </div>
        </div>
    @endif

    {{-- ==================== PAZARLAMA (Saha CRM) ==================== --}}
    @if ($sekme === 'pazarlama')
        @php $po = $this->pazarlamaOzeti; @endphp
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:.6rem">
            @foreach ([
                ['Toplam Aday', $po['toplam'], $mor],
                ['Teklif Aşamasında', $po['teklif'], $sari],
                ['Kazanılanlar', $po['kazanilan'], $yesil],
                ['Açık Hatırlatmalar', $po['acik_hatirlatma'], $kirmizi],
            ] as [$etiket, $deger, $renk])
                <div style="{{ $kutu }};padding:.75rem">
                    <div style="font-size:.7rem;color:rgb(128 116 148);text-transform:uppercase">{{ $etiket }}</div>
                    <div style="font-size:1.4rem;font-weight:800;color:{{ $renk }}">{{ $deger }}</div>
                </div>
            @endforeach
        </div>

        <div style="{{ $kutu }};overflow-x:auto">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:.6rem;flex-wrap:wrap;gap:.5rem">
                <div style="display:flex;gap:.5rem;flex-wrap:wrap">
                    <input type="text" wire:model.live.debounce.400ms="pazarlamaArama" placeholder="Firma, yetkili veya telefon ara..."
                        style="padding:.4rem .6rem;border:1px solid rgb(128 116 148 / .3);border-radius:.4rem;font-size:.82rem;min-width:14rem">
                    <select wire:model.live="pazarlamaAsamaFiltre" style="padding:.4rem .6rem;border:1px solid rgb(128 116 148 / .3);border-radius:.4rem;font-size:.82rem">
                        <option value="">Tüm aşamalar</option>
                        @foreach (config('isg.pazarlama.asamalar') as $anahtar => $ad)
                            <option value="{{ $anahtar }}">{{ $ad }}</option>
                        @endforeach
                    </select>
                </div>
                <x-filament::button size="sm" wire:click="mountAction('adayFirmaKaydet')">+ Aday Firma Ekle</x-filament::button>
            </div>

            @if ($this->adayFirmalar->isEmpty())
                <div style="text-align:center;padding:2rem;color:rgb(128 116 148)">Bu filtrelerde aday firma bulunamadı.</div>
            @else
                <table style="width:100%;border-collapse:collapse;font-size:.83rem">
                    <thead><tr style="text-align:left;color:rgb(128 116 148)">
                        <th style="padding:.4rem">Unvan</th><th style="padding:.4rem">Yetkili</th>
                        <th style="padding:.4rem">Telefon</th><th style="padding:.4rem">Şehir</th>
                        <th style="padding:.4rem">Aşama</th><th style="padding:.4rem">Hatırlatma</th>
                        <th style="padding:.4rem">İşlemler</th>
                    </tr></thead>
                    <tbody>
                    @foreach ($this->adayFirmalar as $aday)
                        <tr style="border-top:1px solid rgb(128 116 148 / .15)">
                            <td style="padding:.4rem;font-weight:600">{{ $aday->unvan }}</td>
                            <td style="padding:.4rem">{{ $aday->yetkili_ad ?: '—' }}</td>
                            <td style="padding:.4rem">{{ $aday->telefon ?: '—' }}</td>
                            <td style="padding:.4rem">{{ $aday->sehir ?: '—' }}</td>
                            <td style="padding:.4rem">
                                <x-filament::badge :color="$aday->asama === 'kazanildi' ? 'success' : ($aday->asama === 'kaybedildi' ? 'danger' : 'warning')">
                                    {{ $aday->asamaEtiketi() }}
                                </x-filament::badge>
                            </td>
                            <td style="padding:.4rem;color:{{ $aday->acikHatirlatmasiMi() ? $kirmizi : 'inherit' }}">
                                {{ $aday->hatirlatma_tarihi?->format('d.m.Y') ?: '—' }}
                            </td>
                            <td style="padding:.4rem;white-space:nowrap">
                                <x-filament::icon-button icon="heroicon-o-pencil-square" label="Düzenle"
                                    wire:click="mountAction('adayFirmaKaydet', { id: {{ $aday->id }} })" />
                                <x-filament::icon-button icon="heroicon-o-trash" label="Sil" color="danger"
                                    wire:click="adayFirmaSil({{ $aday->id }})" wire:confirm="Bu aday firma silinsin mi?" />
                                @if ($aday->asama === 'kazanildi')
                                    <x-filament::icon-button icon="heroicon-o-arrow-right-circle" label="Firmaya Dönüştür" color="success"
                                        wire:click="adayFirmaDonustur({{ $aday->id }})" />
                                @endif
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            @endif
        </div>
        {{ $this->adayFirmaKaydetAction }}
    @endif

    {{-- ==================== ARŞİV ==================== --}}
    @if ($sekme === 'arsiv')
        <div style="{{ $kutu }}">
            <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:.5rem">
                <div>
                    <div style="font-weight:700">Dosya Arşivi</div>
                    <div style="font-size:.78rem;color:rgb(128 116 148)">{{ $this->arsivKullanilanMb }} MB / {{ config('isg.arsiv.kota_mb') }} MB kullanılıyor</div>
                </div>
                {{ $this->arsivYukleAction }}
            </div>
            <div style="height:6px;border-radius:9999px;background:rgb(128 116 148 / .2);margin-top:.5rem;overflow:hidden">
                <div style="height:100%;width:{{ min(100, round($this->arsivKullanilanMb / max(1, config('isg.arsiv.kota_mb')) * 100)) }}%;background:{{ $mor }}"></div>
            </div>
        </div>

        <div style="display:grid;grid-template-columns:16rem 1fr;gap:1rem">
            <div style="{{ $kutu }};max-height:28rem;overflow-y:auto">
                <div style="font-weight:700;margin-bottom:.5rem">Firmalar</div>
                @forelse ($this->arsivFirmalar as $f)
                    <button type="button" wire:click="arsivFirmaSec({{ $f->id }})"
                        style="display:block;width:100%;text-align:left;padding:.4rem .5rem;border:none;border-radius:.4rem;cursor:pointer;font-size:.8rem;margin-bottom:.15rem;
                            background:{{ $this->arsivSeciliFirmaId === $f->id ? $mor : 'transparent' }};color:{{ $this->arsivSeciliFirmaId === $f->id ? '#fff' : 'inherit' }}">
                        {{ $f->unvan }}
                    </button>
                @empty
                    <div style="font-size:.8rem;color:rgb(128 116 148)">Firma yok.</div>
                @endforelse
            </div>

            <div style="{{ $kutu }}">
                <div style="font-weight:700;margin-bottom:.6rem">{{ $this->arsivSeciliFirma?->unvan ?? 'Firma seçin' }}</div>
                @if ($this->arsivDosyalar->isEmpty())
                    <div style="text-align:center;padding:2rem;color:rgb(128 116 148)">Bu firmada henüz dosya yok.</div>
                @else
                    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(180px,1fr));gap:.6rem">
                        @foreach ($this->arsivDosyalar as $dosya)
                            <div style="border:1px solid rgb(128 116 148 / .2);border-radius:.5rem;padding:.6rem;text-align:center">
                                <div style="font-size:1.5rem">📄</div>
                                <div style="font-size:.75rem;font-weight:600;word-break:break-word">{{ $dosya->dosya_adi }}</div>
                                <div style="font-size:.68rem;color:rgb(128 116 148);margin-bottom:.4rem">{{ $dosya->boyutEtiketi() }}</div>
                                <div style="display:flex;justify-content:center;gap:.3rem">
                                    <a href="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($dosya->dosya_yolu) }}" target="_blank"
                                        style="font-size:.72rem;color:{{ $mor }}">İndir</a>
                                    <button type="button" wire:click="arsivDosyaSil({{ $dosya->id }})" wire:confirm="Bu dosya silinsin mi?"
                                        style="font-size:.72rem;color:{{ $kirmizi }};border:none;background:none;cursor:pointer">Sil</button>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    @endif

    {{-- ==================== RAPORLAR ==================== --}}
    @if ($sekme === 'raporlar')
        <div style="{{ $kutu }};overflow-x:auto">
            <div style="display:flex;gap:.5rem;flex-wrap:wrap;margin-bottom:.6rem">
                <input type="text" wire:model.live.debounce.400ms="raporArama" placeholder="Ara..."
                    style="padding:.4rem .6rem;border:1px solid rgb(128 116 148 / .3);border-radius:.4rem;font-size:.82rem;min-width:14rem">
                <select wire:model.live="raporTipFiltre" style="padding:.4rem .6rem;border:1px solid rgb(128 116 148 / .3);border-radius:.4rem;font-size:.82rem">
                    <option value="">Tüm tipler</option>
                    @foreach ($this->raporTipSecenekleri as $tip)
                        <option value="{{ $tip }}">{{ $tip }}</option>
                    @endforeach
                </select>
            </div>

            @if (empty($this->raporlar))
                <div style="text-align:center;padding:2rem;color:rgb(128 116 148)">Bu filtrelerde rapor bulunamadı.</div>
            @else
                <table style="width:100%;border-collapse:collapse;font-size:.83rem">
                    <thead><tr style="text-align:left;color:rgb(128 116 148)">
                        <th style="padding:.4rem">Rapor</th><th style="padding:.4rem">Tip</th>
                        <th style="padding:.4rem">Tarih</th><th style="padding:.4rem">İşlemler</th>
                    </tr></thead>
                    <tbody>
                    @foreach ($this->raporlar as $satir)
                        <tr style="border-top:1px solid rgb(128 116 148 / .15)">
                            <td style="padding:.4rem;font-weight:600">
                                {{ $satir['baslik'] }}
                                <div style="font-weight:400;font-size:.7rem;color:rgb(128 116 148)">Firma: {{ $satir['firma'] ?? '—' }}</div>
                            </td>
                            <td style="padding:.4rem"><x-filament::badge>{{ $satir['tip'] }}</x-filament::badge></td>
                            <td style="padding:.4rem">{{ $satir['tarih']?->format('d.m.Y H:i') }}</td>
                            <td style="padding:.4rem;white-space:nowrap">
                                <x-filament::button size="xs" color="gray"
                                    wire:click="mountAction('raporIndir', { model: @js($satir['kaynak']['model']), id: {{ $satir['kayit']->id }}, format: 'birincil' })">
                                    İndir
                                </x-filament::button>
                                @if (\App\Support\RaporKayitlari::ikincilUygunMu($satir['kaynak'], $satir['kayit']))
                                    <x-filament::button size="xs" color="gray"
                                        wire:click="mountAction('raporIndir', { model: @js($satir['kaynak']['model']), id: {{ $satir['kayit']->id }}, format: 'ikincil' })">
                                        {{ $satir['kaynak']['ikincil_etiket'] }}
                                    </x-filament::button>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            @endif
        </div>
        {{ $this->raporIndirAction }}
    @endif

    {{-- ==================== FİRMA ZİYARETLERİ ==================== --}}
    @if ($sekme === 'firma_ziyaretleri')
        @php
            $zo = $this->ziyaretOzeti;
            $gunler = $this->ziyaretGunlukGruplar;
            $ayBaslangic = \Illuminate\Support\Carbon::parse($ziyaretGosterilenAy.'-01');
            $gunSayisi = $ayBaslangic->daysInMonth;
            $baslangicBosluk = $ayBaslangic->dayOfWeekIso - 1;
        @endphp
        <div style="display:flex;justify-content:space-between;align-items:center">
            <div>
                <div style="font-weight:700;font-size:1.1rem">Firma Ziyaretlerim</div>
                <div style="font-size:.8rem;color:rgb(128 116 148)">Ziyaret Programı'ndaki tarihli kayıtlardan türetilir.</div>
            </div>
            <div style="display:flex;gap:.5rem">
                <div style="{{ $kutu }};padding:.5rem .9rem;text-align:center">
                    <div style="font-size:1.2rem;font-weight:800;color:{{ $mor }}">{{ $zo['ziyaret'] }}</div>
                    <div style="font-size:.68rem;color:rgb(128 116 148)">Ziyaret</div>
                </div>
                <div style="{{ $kutu }};padding:.5rem .9rem;text-align:center">
                    <div style="font-size:1.2rem;font-weight:800;color:{{ $yesil }}">{{ $zo['firma'] }}</div>
                    <div style="font-size:.68rem;color:rgb(128 116 148)">Firma</div>
                </div>
            </div>
        </div>

        <div style="display:grid;grid-template-columns:20rem 1fr;gap:1rem">
            <div style="{{ $kutu }}">
                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:.6rem">
                    <button type="button" wire:click="ziyaretAyDegistir(-1)" style="border:none;background:none;cursor:pointer;font-size:1rem">‹</button>
                    <div style="font-weight:700">{{ $ayBaslangic->translatedFormat('F Y') }}</div>
                    <button type="button" wire:click="ziyaretAyDegistir(1)" style="border:none;background:none;cursor:pointer;font-size:1rem">›</button>
                </div>
                <div style="display:grid;grid-template-columns:repeat(7,1fr);gap:2px;font-size:.7rem;text-align:center;color:rgb(128 116 148);margin-bottom:.3rem">
                    @foreach (['Pzt','Sal','Çar','Per','Cum','Cmt','Paz'] as $g)
                        <div>{{ $g }}</div>
                    @endforeach
                </div>
                <div style="display:grid;grid-template-columns:repeat(7,1fr);gap:2px">
                    @for ($i = 0; $i < $baslangicBosluk; $i++)
                        <div></div>
                    @endfor
                    @for ($gun = 1; $gun <= $gunSayisi; $gun++)
                        @php
                            $tarih = $ayBaslangic->copy()->day($gun)->toDateString();
                            $doluMu = ! empty($gunler[$tarih]);
                            $seciliMi = $tarih === $ziyaretSeciliTarih;
                        @endphp
                        <button type="button" wire:click="ziyaretTarihSec('{{ $tarih }}')"
                            style="aspect-ratio:1;border-radius:.35rem;border:none;cursor:pointer;font-size:.78rem;position:relative;
                                background:{{ $seciliMi ? $mor : ($doluMu ? 'rgb(139 92 246 / .15)' : 'transparent') }};
                                color:{{ $seciliMi ? '#fff' : 'inherit' }}">
                            {{ $gun }}
                        </button>
                    @endfor
                </div>
            </div>

            <div style="{{ $kutu }}">
                <div style="font-weight:700;margin-bottom:.6rem">{{ \Illuminate\Support\Carbon::parse($ziyaretSeciliTarih)->translatedFormat('d F Y') }}</div>
                @forelse ($this->ziyaretlerGunluk as $z)
                    <div style="border-top:1px solid rgb(128 116 148 / .15);padding:.5rem 0">
                        <div style="font-weight:600;font-size:.85rem">{{ $z['firma']?->unvan }}</div>
                        <div style="font-size:.78rem;color:rgb(128 116 148)">
                            {{ $z['amac'] ?: 'Amaç belirtilmemiş' }}
                            @if ($z['sure_saat']) · {{ $z['sure_saat'] }} saat @endif
                        </div>
                    </div>
                @empty
                    <div style="text-align:center;padding:2rem;color:rgb(128 116 148)">Bu tarihte ziyaret kaydınız bulunmuyor.</div>
                @endforelse
            </div>
        </div>
    @endif
</x-filament-panels::page>
