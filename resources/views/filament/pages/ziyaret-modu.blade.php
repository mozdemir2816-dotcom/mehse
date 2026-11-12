@php
    use App\Support\ArsivKurali;

    $kart = 'border:1px solid rgb(107 114 128 / .2);border-radius:.9rem;background:var(--zm-kart);padding:.9rem 1rem';
    $baslik = 'font-size:.75rem;font-weight:700;letter-spacing:.06em;text-transform:uppercase;color:rgb(107 114 128)';
    $soluk = 'color:rgb(107 114 128)';
    $renk = ['gecikmis' => '#dc2626', 'yaklasan' => '#b45309', 'eksik' => '#6b7280', 'tamam' => '#15803d', 'muaf' => '#6b7280', 'takipsiz' => '#6b7280', 'yapilmayan' => '#b45309'];
    $buyukDugme = 'display:flex;flex-direction:column;align-items:center;justify-content:center;gap:.25rem;min-height:4.6rem;border-radius:.9rem;font-weight:700;font-size:.85rem;text-decoration:none;text-align:center;padding:.5rem';
    $f = $this->firma;
@endphp

<x-filament-panels::page>
    <style>
        :root { --zm-kart: #fff; }
        .dark { --zm-kart: rgb(255 255 255 / .03); }
        .zm-panel { background: #fff; color: #111827; }
        .dark .zm-panel { background: #18181b; color: #f4f4f5; }
    </style>

    {{-- FİRMA SEÇİMİ --}}
    @if (! $f)
        @if ($this->bugunPlanli->isNotEmpty())
            <div>
                <div style="{{ $baslik }};margin-bottom:.5rem">Bugün planlı ziyaretler</div>
                <div style="display:flex;flex-direction:column;gap:.5rem">
                    @foreach ($this->bugunPlanli as $z)
                        <button type="button" wire:click="firmaSec({{ $z['firma_id'] }})" style="{{ $kart }};text-align:left;cursor:pointer;color:inherit;border-left:4px solid {{ $z['durum'] === 'tamamlandi' ? '#15803d' : 'rgb(37 99 235)' }}">
                            <div style="font-weight:700;font-size:1rem">{{ $z['unvan'] }}</div>
                            <div style="font-size:.82rem;{{ $soluk }}">{{ $z['amac'] ?: 'Saha ziyareti' }} · {{ $z['durum'] === 'tamamlandi' ? 'Tamamlandı' : 'Planlandı' }}</div>
                        </button>
                    @endforeach
                </div>
            </div>
        @else
            <p style="{{ $soluk }}">Bugün ziyaret programında planlı firma yok. Ziyaret ettiğiniz firmayı seçin.</p>
        @endif

        <select wire:model.live="firmaId" style="width:100%;padding:.8rem;border-radius:.7rem;border:1px solid rgb(107 114 128 / .35);background:transparent;font-size:1rem">
            <option value="">Firma seçin…</option>
            @foreach ($this->firmalar as $id => $ad)<option value="{{ $id }}">{{ $ad }}</option>@endforeach
        </select>
    @else
        {{-- BAŞLIK --}}
        <div style="{{ $kart }};display:flex;gap:.6rem;align-items:center">
            <div style="flex:1;min-width:0">
                <div style="font-weight:800;font-size:1.05rem;line-height:1.25">{{ $f->unvan }}</div>
                <div style="font-size:.8rem;{{ $soluk }}">
                    {{ now()->translatedFormat('d F Y, l') }}
                    @if ($z = $this->bugunkuZiyaret) · {{ $z['girdi']['amac'] ?: 'Saha ziyareti' }} · <strong style="color:{{ ($z['girdi']['durum'] ?? '') === 'tamamlandi' ? '#15803d' : 'rgb(37 99 235)' }}">{{ ($z['girdi']['durum'] ?? '') === 'tamamlandi' ? 'Tamamlandı' : 'Planlandı' }}</strong>@endif
                </div>
            </div>
            <button type="button" wire:click="$set('firmaId', null)" style="background:none;border:1px solid rgb(107 114 128 / .3);border-radius:.5rem;padding:.35rem .6rem;cursor:pointer;color:inherit;font-size:.8rem">Değiştir</button>
        </div>

        {{-- HIZLI AKSİYONLAR --}}
        <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:.5rem">
            <a href="{{ \App\Filament\Pages\AiSahaAnalizi::getUrl(['firma' => $f->id]) }}" style="{{ $buyukDugme }};background:rgb(37 99 235);color:#fff"><span style="font-size:1.4rem">📷</span>Saha Gözlemi</a>
            <a href="{{ \App\Filament\Pages\HizliSahaBulgusu::getUrl(['firma' => $f->id]) }}" style="{{ $buyukDugme }};background:rgb(234 88 12 / .12);color:#c2410c;border:1px solid rgb(234 88 12 / .3)"><span style="font-size:1.4rem">⚠️</span>Hızlı Bulgu</a>
            <a href="{{ \App\Filament\Pages\TespitOneriDefteri::getUrl(['firma' => $f->id]) }}" style="{{ $buyukDugme }};background:rgb(107 114 128 / .1);color:inherit;border:1px solid rgb(107 114 128 / .25)"><span style="font-size:1.4rem">📖</span>Tespit-Öneri</a>
        </div>

        {{-- 1. BU AYIN PLAN MADDELERİ — 4 kategorili akordeon (Risk & Ekipman / Yangın & Acil Durum / Eğitim & KKD / Ortam & Hijyen) --}}
        @php
            $plan = $this->planMaddeleri;
            $yapilan = collect($plan['maddeler'])->where('gerceklesti', true)->count();
            $gruplar = $this->planGruplari;
            // İlk tamamlanmamış kategori açık başlar.
            $ilkAcik = collect($gruplar)->filter(fn ($g) => $g['yapilan'] < $g['toplam'])->keys()->first();
        @endphp
        <div style="{{ $kart }}" x-data="{ acik: @js($ilkAcik) }">
            <div style="display:flex;justify-content:space-between;align-items:baseline;margin-bottom:.5rem">
                <span style="{{ $baslik }}">Bu ziyarette yapılacaklar · {{ \App\Models\ZiyaretProgrami::AYLAR[$plan['ay']] }}</span>
                @if ($plan['maddeler'])<span style="font-size:.82rem;font-weight:700;color:{{ $yapilan === count($plan['maddeler']) ? '#15803d' : '#b45309' }}">{{ $yapilan }}/{{ count($plan['maddeler']) }}</span>@endif
            </div>

            @forelse ($gruplar as $kod => $g)
                @php $tamam = $g['yapilan'] === $g['toplam']; @endphp
                <div wire:key="pg-{{ $kod }}" style="border-top:1px solid rgb(107 114 128 / .15)">
                    <button type="button" x-on:click="acik = acik === '{{ $kod }}' ? null : '{{ $kod }}'" :aria-expanded="acik === '{{ $kod }}'"
                        style="display:flex;width:100%;align-items:center;justify-content:space-between;gap:.6rem;min-height:3rem;padding:.4rem 0;background:none;border:none;cursor:pointer;color:inherit;text-align:left">
                        <span style="display:flex;align-items:center;gap:.5rem;font-weight:700;font-size:.95rem">
                            <span x-text="acik === '{{ $kod }}' ? '▾' : '▸'" style="width:1rem"></span>
                            {{ $g['ad'] }}
                        </span>
                        <span style="font-size:.75rem;font-weight:700;padding:.1rem .6rem;border-radius:9999px;{{ $tamam ? 'background:rgb(22 163 74 / .14);color:#15803d' : 'background:rgb(217 119 6 / .14);color:#b45309' }}">{{ $g['yapilan'] }}/{{ $g['toplam'] }}</span>
                    </button>

                    <div x-show="acik === '{{ $kod }}'" x-cloak style="padding-bottom:.5rem">
                        @if (! $tamam)
                            <button type="button" wire:click="kategoriTumunuUygunIsaretle('{{ $kod }}')"
                                style="min-height:2.75rem;padding:0 1rem;margin-bottom:.4rem;border-radius:9999px;border:1px solid rgb(22 163 74 / .5);background:rgb(22 163 74 / .08);color:#15803d;font-weight:700;font-size:.82rem;cursor:pointer">
                                ✓ Tümünü Uygun İşaretle
                            </button>
                        @endif

                        @foreach ($g['maddeler'] as $m)
                            <div wire:key="pm-{{ $m['alan'] }}-{{ $m['index'] }}" style="display:flex;gap:.7rem;align-items:flex-start;padding:.55rem 0;border-top:1px solid rgb(107 114 128 / .1)">
                                <input type="checkbox" @checked($m['gerceklesti']) wire:click="planMaddesiIsaretle('{{ $m['alan'] }}', {{ $m['index'] }})" aria-label="Uygun" style="width:1.5rem;height:1.5rem;margin-top:.1rem;flex-shrink:0;cursor:pointer">
                                <span style="flex:1;{{ $m['gerceklesti'] ? 'text-decoration:line-through;color:rgb(107 114 128)' : '' }}">
                                    <span style="display:block;font-size:.92rem">{{ $m['baslik'] }}</span>
                                    <span style="display:block;font-size:.75rem;{{ $soluk }}">{{ $m['grup'] }}@if ($m['sorumlu']) · {{ $m['sorumlu'] }}@endif</span>
                                </span>
                                @unless ($m['gerceklesti'])
                                    <button type="button" wire:click="planMaddesiUygunsuz('{{ $m['alan'] }}', {{ $m['index'] }})" title="Uygunsuz — bulgu kaydı aç" aria-label="Uygunsuzluk işaretle"
                                        style="flex-shrink:0;min-width:2.75rem;min-height:2.75rem;border-radius:.6rem;border:1px solid rgb(234 88 12 / .4);background:rgb(234 88 12 / .08);color:#c2410c;font-size:1.05rem;cursor:pointer">⚠</button>
                                @endunless
                            </div>
                        @endforeach
                    </div>
                </div>
            @empty
                <p style="font-size:.85rem;{{ $soluk }}">{{ $plan['plan_var'] ? 'Bu ay için planlanmış madde yok.' : 'Bu yılın yıllık planı oluşturulmamış.' }}</p>
            @endforelse
        </div>

        {{-- 2. OSGB ARŞİV EVRAKLARI --}}
        @php
            $arsiv = collect($this->arsivDurumu);
            $eksikler = $arsiv->filter(fn ($d) => in_array($d['durum'], ['eksik', 'gecikmis', 'yaklasan'], true));
            $tamamlar = $arsiv->filter(fn ($d) => in_array($d['durum'], ['tamam', 'muaf'], true));
        @endphp
        <div style="{{ $kart }}">
            <div style="display:flex;justify-content:space-between;align-items:baseline;margin-bottom:.4rem">
                <span style="{{ $baslik }}">Arşiv evrakları</span>
                <a href="{{ \App\Filament\Pages\DokumanYonetimi::getUrl(['firma' => $f->id]) }}" style="font-size:.8rem;color:rgb(37 99 235)">Arşiv →</a>
            </div>
            <p style="font-size:.78rem;{{ $soluk }};margin-bottom:.4rem">İmzalı evrakın fotoğrafını çekin; çok sayfalıysa "＋ Sayfa" ile ekleyip "Kaydet"e basın.</p>
            @forelse ($eksikler as $anahtar => $d)
                <div wire:key="ar-{{ $anahtar }}" style="padding:.6rem 0;border-top:1px solid rgb(107 114 128 / .12);display:flex;flex-direction:column;gap:.4rem">
                    <div style="display:flex;gap:.5rem;align-items:baseline">
                        <span style="color:{{ $renk[$d['durum']] }};font-weight:700">{{ ['gecikmis' => '⚠', 'yaklasan' => '◷', 'eksik' => '○'][$d['durum']] }}</span>
                        <span style="flex:1">
                            <span style="display:block;font-weight:600;font-size:.92rem">{{ $d['ad'] }}</span>
                            <span style="display:block;font-size:.76rem;color:{{ $d['durum'] === 'eksik' ? 'rgb(107 114 128)' : $renk[$d['durum']] }}">{{ $d['mesaj'] }}</span>
                        </span>
                    </div>
                    @include('filament.components.hizli-arsiv', ['kategori' => $anahtar])
                </div>
            @empty
                <p style="font-size:.85rem;color:#15803d">✓ Takip edilen tüm arşiv evrakları güncel.</p>
            @endforelse
            @if ($tamamlar->isNotEmpty())
                <details style="margin-top:.4rem">
                    <summary style="cursor:pointer;font-size:.8rem;{{ $soluk }}">✓ {{ $tamamlar->count() }} evrak güncel — yine de yükle</summary>
                    @foreach ($tamamlar as $anahtar => $d)
                        <div wire:key="art-{{ $anahtar }}" style="padding:.5rem 0;border-top:1px solid rgb(107 114 128 / .12)">
                            <div style="font-size:.88rem;margin-bottom:.3rem">{{ $d['ad'] }} <span style="font-size:.75rem;{{ $soluk }}">· {{ $d['mesaj'] }}</span></div>
                            @include('filament.components.hizli-arsiv', ['kategori' => $anahtar])
                        </div>
                    @endforeach
                </details>
            @endif
        </div>

        {{-- 3. AÇIK UYGUNSUZLUKLAR --}}
        @php $acikSayi = $this->acikBulgular->count() + $this->acikDofler->count(); @endphp
        <div style="{{ $kart }}">
            <div style="{{ $baslik }};margin-bottom:.4rem">Açık uygunsuzluklar{{ $acikSayi ? " ($acikSayi)" : "" }}</div>
            <p style="font-size:.78rem;{{ $soluk }};margin-bottom:.3rem">Önceki ziyaretlerden kalanlar — yerinde kontrol edin, giderildiyse fotoğrafla kapatın.</p>

            @foreach ($this->acikBulgular as $b)
                @php $an = 'b'.$b->id; @endphp
                <div wire:key="ab-{{ $b->id }}" style="padding:.6rem 0;border-top:1px solid rgb(107 114 128 / .12)">
                    <div style="display:flex;gap:.6rem">
                        @if ($foto = ($b->fotograflar ?? [])[0] ?? null)
                            <img alt="Bulgu fotoğrafı" src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($foto) }}" style="width:3.6rem;height:3.6rem;object-fit:cover;border-radius:.45rem;flex-shrink:0">
                        @endif
                        <div style="flex:1;min-width:0">
                            <div style="font-size:.9rem;font-weight:600">{{ \Illuminate\Support\Str::limit($b->uygunsuzluk, 110) }}</div>
                            <div style="font-size:.75rem;color:{{ $b->terminGectiMi() ? '#dc2626' : 'rgb(107 114 128)' }}">
                                {{ $b->bulgu_no }}@if ($b->bolum) · {{ $b->bolum }}@endif @if ($b->termin) · Termin {{ $b->termin->format('d.m.Y') }}@endif
                            </div>
                        </div>
                    </div>
                    @include('filament.pages.partials.ziyaret-kapanis', ['an' => $an, 'kapat' => 'bulguKapat('.$b->id.')'])
                </div>
            @endforeach

            @foreach ($this->acikDofler as $s)
                @php $an = 'd'.$s['rapor_id'].'_'.$s['index']; $m = $s['madde']; @endphp
                <div wire:key="ad-{{ $an }}" style="padding:.6rem 0;border-top:1px solid rgb(107 114 128 / .12)">
                    <div style="display:flex;gap:.6rem">
                        @if (filled($m['foto_yolu'] ?? null))
                            <img alt="Madde fotoğrafı" src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($m['foto_yolu']) }}" style="width:3.6rem;height:3.6rem;object-fit:cover;border-radius:.45rem;flex-shrink:0">
                        @endif
                        <div style="flex:1;min-width:0">
                            <div style="font-size:.9rem;font-weight:600">{{ \Illuminate\Support\Str::limit((string) ($m['tespit'] ?? ''), 110) }}</div>
                            @php $terminGecti = filled($m['termin'] ?? null) && \Illuminate\Support\Carbon::parse($m['termin'])->lt(today()); @endphp
                            <div style="font-size:.75rem;color:{{ $terminGecti ? '#dc2626' : 'rgb(107 114 128)' }}">
                                DÖF {{ $s['belge_no'] }}@if (filled($m['sorumlu'] ?? null)) · {{ $m['sorumlu'] }}@endif @if (filled($m['termin'] ?? null)) · Termin {{ \Illuminate\Support\Carbon::parse($m['termin'])->format('d.m.Y') }}@endif
                            </div>
                        </div>
                    </div>
                    @include('filament.pages.partials.ziyaret-kapanis', ['an' => $an, 'kapat' => 'dofKapat('.$s['rapor_id'].', '.$s['index'].')'])
                </div>
            @endforeach

            @if (! $acikSayi)
                <p style="font-size:.85rem;color:#15803d">✓ Açık uygunsuzluk yok.</p>
            @endif
        </div>

        {{-- HIZLI İŞLEMLER — iş izni kapatma, ramak kala (eski "Saha Hızlı İşlem") --}}
        @include('filament.pages.partials.saha-hizli-islemler')

        {{-- 4. DİĞER EKSİKLER --}}
        @if ($this->gorevler)
            <div style="{{ $kart }}">
                <div style="{{ $baslik }};margin-bottom:.4rem">Diğer eksikler</div>
                @foreach ($this->gorevler as $g)
                    <a href="{{ $g['url'] ?? '#' }}" style="display:flex;gap:.5rem;padding:.55rem 0;border-top:1px solid rgb(107 114 128 / .12);text-decoration:none;color:inherit">
                        <span style="color:{{ $renk[$g['durum']] ?? '#6b7280' }};font-weight:700">{{ $g['durum'] === 'gecikmis' ? '⚠' : '◷' }}</span>
                        <span style="flex:1">
                            <span style="display:block;font-weight:600;font-size:.9rem">{{ $g['baslik'] }}</span>
                            <span style="display:block;font-size:.76rem;{{ $soluk }}">{{ $g['aciklama'] }}</span>
                        </span>
                        <span style="{{ $soluk }}">›</span>
                    </a>
                @endforeach
            </div>
        @endif

        {{-- ZİYARETİ BİTİR — bugünkü ziyaret zaten tamamlandıysa rapor düğmeleri (02.11.2026) --}}
        @if ($this->ziyaretTamamlandiMi())
            <div style="border:1px solid rgb(22 163 74 / .4);background:rgb(22 163 74 / .07);border-radius:.75rem;padding:.75rem;display:flex;flex-direction:column;gap:.5rem">
                <div style="font-weight:700;color:#15803d;text-align:center">✅ Bugünkü ziyaret tamamlandı</div>
                <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:.5rem">
                    <x-filament::button color="success" size="lg" icon="heroicon-o-chat-bubble-left-right" wire:click="$set('ozetAcik', true)">📲 Ziyaret Raporunu WhatsApp ile Gönder</x-filament::button>
                    <x-filament::button color="gray" size="lg" icon="heroicon-o-document-arrow-down" wire:click="ozetPdf">Ziyaret Raporu (PDF İndir)</x-filament::button>
                </div>
            </div>
        @else
            <x-filament::button color="success" size="xl" icon="heroicon-o-check-badge" wire:click="ziyaretiBitir" style="width:100%">Ziyareti Bitir</x-filament::button>
        @endif

        @if ($ozetAcik)
            <div style="position:fixed;inset:0;z-index:30;background:rgb(0 0 0 / .45);display:flex;align-items:flex-end;justify-content:center" wire:click.self="$set('ozetAcik', false)">
                <div class="zm-panel" style="width:100%;max-width:560px;max-height:88vh;overflow-y:auto;border-radius:1rem 1rem 0 0;padding:1.1rem;display:flex;flex-direction:column;gap:.7rem"
                    x-data="{ kopyalandi: false, isverenNo: @js($this->isverenWhatsappNo()), imza: @js($this->isverenImzasi()), paylas() {
                        const metin = this.$refs.ozet.value;
                        if (navigator.share) { navigator.share({ text: metin }).catch(() => {}); return; }
                        window.open('https://wa.me/?text=' + encodeURIComponent(metin), '_blank');
                    }, isverene() {
                        window.open('https://api.whatsapp.com/send?phone=' + this.isverenNo + '&text=' + encodeURIComponent(this.$refs.ozet.value + '\n\n' + this.imza), '_blank');
                    } }">
                    <div style="display:flex;justify-content:space-between;align-items:center">
                        <strong style="font-size:1.05rem">Ziyaret özeti</strong>
                        <button type="button" wire:click="$set('ozetAcik', false)" style="background:none;border:none;font-size:1.2rem;cursor:pointer;color:inherit">✕</button>
                    </div>
                    <textarea x-ref="ozet" readonly rows="12" style="width:100%;font-size:.85rem;padding:.6rem;border-radius:.5rem;border:1px solid rgb(107 114 128 / .3);background:transparent;color:inherit">{{ $this->ozetMetni() }}</textarea>
                    @if ($this->isverenWhatsappNo())
                        <x-filament::button color="success" size="lg" icon="heroicon-o-chat-bubble-left-right" x-on:click="isverene()" style="width:100%">📲 İşverene WhatsApp ile Gönder</x-filament::button>
                    @else
                        <div style="font-size:.75rem;color:rgb(180 83 9)">Firma yetkili telefonu tanımlı değil — işverene doğrudan göndermek için Firmalar ekranında cep telefonu (05xx…) girin. Aşağıdaki "Paylaş" ile kişiyi elle seçebilirsiniz.</div>
                    @endif
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:.5rem">
                        <x-filament::button color="gray" icon="heroicon-o-share" x-on:click="paylas()">WhatsApp / Paylaş</x-filament::button>
                        <x-filament::button color="gray" icon="heroicon-o-clipboard" x-on:click="navigator.clipboard.writeText($refs.ozet.value); kopyalandi = true">
                            <span x-text="kopyalandi ? 'Kopyalandı' : 'Kopyala'"></span>
                        </x-filament::button>
                    </div>
                </div>
            </div>
        @endif
    @endif
</x-filament-panels::page>
