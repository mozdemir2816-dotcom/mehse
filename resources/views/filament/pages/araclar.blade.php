@php
    $turuncu = 'rgb(217 119 6)';
    $kutu = 'border:1px solid rgb(107 114 128 / .3);border-radius:.75rem;padding:1rem';
    $girdi = 'margin-top:.3rem;width:100%;padding:.5rem .75rem;border-radius:.5rem;border:1px solid rgb(107 114 128 / .35);background:transparent';
@endphp

<x-filament-panels::page>
    <p style="font-size:.85rem;color:rgb(107 114 128);margin-top:-.5rem">
        Firma seçimi gerektirmeyen, bağımsız İSG hesaplayıcıları. Girdiğiniz değerler kaydedilmez.
    </p>

    {{-- KAZA SIKLIK / AĞIRLIK HIZI --}}
    <x-filament::section icon="heroicon-o-calculator" icon-color="warning">
        <x-slot name="heading">Kaza Sıklık Hızı ve Ağırlık Hızı Hesaplayıcı</x-slot>
        <x-slot name="description">Sıklık Hızı = (Kaza Sayısı × 1.000.000) / Toplam Çalışma Saati &nbsp;·&nbsp; Ağırlık Hızı = (Kayıp Gün Sayısı × 1.000) / Toplam Çalışma Saati</x-slot>

        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:1rem">
            <div>
                <label style="font-weight:600;font-size:.82rem">Kaza Sayısı</label>
                <input type="number" min="0" wire:model.live="kazaSayisi" style="{{ $girdi }}">
            </div>
            <div>
                <label style="font-weight:600;font-size:.82rem">Toplam Kayıp Gün Sayısı</label>
                <input type="number" min="0" wire:model.live="kayipGunSayisi" style="{{ $girdi }}">
            </div>
            <div>
                <label style="font-weight:600;font-size:.82rem">Toplam Çalışma Saati (adam × saat)</label>
                <input type="number" min="0" step="0.01" wire:model.live="toplamCalismaSaati" style="{{ $girdi }}">
            </div>
        </div>

        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:1rem;margin-top:1rem">
            <div style="{{ $kutu }};text-align:center">
                <div style="font-size:.78rem;color:rgb(107 114 128)">Kaza Sıklık Hızı</div>
                <div style="font-size:1.5rem;font-weight:800;color:{{ $turuncu }}">{{ $this->sikikHizi() ?? '—' }}</div>
            </div>
            <div style="{{ $kutu }};text-align:center">
                <div style="font-size:.78rem;color:rgb(107 114 128)">Kaza Ağırlık Hızı</div>
                <div style="font-size:1.5rem;font-weight:800;color:{{ $turuncu }}">{{ $this->agirlikHizi() ?? '—' }}</div>
            </div>
        </div>

        <div style="margin-top:.75rem">
            <x-filament::button size="xs" color="gray" wire:click="kazaHesaplayiciSifirla">Sıfırla</x-filament::button>
        </div>
    </x-filament::section>

    {{-- GÜRÜLTÜ MARUZİYET DÜZEYİ --}}
    <x-filament::section icon="heroicon-o-speaker-wave" icon-color="warning">
        <x-slot name="heading">Gürültü Maruziyet Düzeyi (Lex,8h) Hesaplayıcı</x-slot>
        <x-slot name="description">Farklı gürültü seviyelerinde geçirilen süreleri girin; günlük 8 saatlik normalize maruziyet düzeyi hesaplanır.</x-slot>

        <div style="display:flex;flex-direction:column;gap:.6rem">
            @foreach ($gurultuOlcumleri as $i => $olcum)
                <div style="display:grid;grid-template-columns:1fr 1fr auto;gap:.75rem;align-items:end">
                    <div>
                        <label style="font-weight:600;font-size:.82rem">Ölçülen Düzey (dB(A))</label>
                        <input type="number" step="0.1" wire:model.live="gurultuOlcumleri.{{ $i }}.db" style="{{ $girdi }}">
                    </div>
                    <div>
                        <label style="font-weight:600;font-size:.82rem">Maruziyet Süresi (saat)</label>
                        <input type="number" step="0.1" min="0" max="24" wire:model.live="gurultuOlcumleri.{{ $i }}.saat" style="{{ $girdi }}">
                    </div>
                    <div>
                        @if (count($gurultuOlcumleri) > 1)
                            <x-filament::icon-button icon="heroicon-o-trash" color="danger" wire:click="gurultuOlcumSil({{ $i }})"/>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>

        <div style="margin-top:.6rem">
            <x-filament::button size="xs" color="gray" icon="heroicon-o-plus" wire:click="gurultuOlcumEkle">Ölçüm Ekle</x-filament::button>
        </div>

        @php $seviye = $this->gurultuSeviyesi(); $lex = $this->lex8h(); @endphp
        <div style="{{ $kutu }};margin-top:1rem;text-align:center;{{ $seviye ? 'background:rgb('.($seviye['renk'] === 'danger' ? '220 38 38' : '217 119 6').' / .08)' : '' }}">
            <div style="font-size:.78rem;color:rgb(107 114 128)">Günlük Maruziyet Düzeyi — Lex,8h</div>
            <div style="font-size:1.5rem;font-weight:800;color:{{ $turuncu }}">{{ $lex !== null ? $lex.' dB(A)' : '—' }}</div>
            @if ($lex !== null)
                <div style="margin-top:.4rem">
                    @if ($seviye)
                        <x-filament::badge color="{{ $seviye['renk'] }}">{{ $seviye['etiket'] }}</x-filament::badge>
                        <div style="font-size:.78rem;color:rgb(107 114 128);margin-top:.4rem">{{ $seviye['aciklama'] }}</div>
                    @else
                        <x-filament::badge color="success">{{ config('isg.araclar.gurultu_guvenli_mesaj') }}</x-filament::badge>
                    @endif
                </div>
            @endif
        </div>

        <div style="margin-top:.75rem">
            <x-filament::button size="xs" color="gray" wire:click="gurultuHesaplayiciSifirla">Sıfırla</x-filament::button>
        </div>
    </x-filament::section>

    {{-- NACE KOD → TEHLİKE SINIFI SORGULA --}}
    <x-filament::section icon="heroicon-o-magnifying-glass" icon-color="warning">
        <x-slot name="heading">NACE Kod → Tehlike Sınıfı Sorgula</x-slot>
        <x-slot name="description">6 haneli NACE Rev.2 faaliyet kodunu girin (ör. 01.11.14), İşyeri Tehlike Sınıfları Tebliği EK-1'deki tehlike sınıfını görün.</x-slot>

        <div style="display:flex;gap:.75rem;align-items:end;flex-wrap:wrap">
            <div style="flex:1;min-width:200px">
                <label style="font-weight:600;font-size:.82rem">NACE Kodu</label>
                <input type="text" placeholder="Örn: 01.11.14 veya 011114" wire:model="naceKoduGirdi" wire:keydown.enter="naceSorgula" style="{{ $girdi }}">
            </div>
            <x-filament::button color="warning" wire:click="naceSorgula">Sorgula</x-filament::button>
            @if ($naceKoduGirdi || $naceSonuc)
                <x-filament::button size="sm" color="gray" wire:click="naceSifirla">Temizle</x-filament::button>
            @endif
        </div>

        @if ($naceHata)
            <div style="margin-top:1rem;color:rgb(220 38 38);font-size:.85rem">{{ $naceHata }}</div>
        @endif

        @if ($naceSonuc)
            @php
                $renk = match ($naceSonuc->tehlike_sinifi) {
                    'cok_tehlikeli' => 'danger',
                    'tehlikeli' => 'warning',
                    default => 'success',
                };
            @endphp
            <div style="{{ $kutu }};margin-top:1rem">
                <div style="font-size:.78rem;color:rgb(107 114 128)">{{ $naceSonuc->kod }} @if($naceSonuc->sektor_adi) · {{ $naceSonuc->sektor_adi }} @endif</div>
                <div style="font-weight:600;margin-top:.25rem">{{ $naceSonuc->tanim }}</div>
                <div style="margin-top:.6rem">
                    <x-filament::badge color="{{ $renk }}">{{ config('isg.tehlike_siniflari')[$naceSonuc->tehlike_sinifi] }}</x-filament::badge>
                </div>
            </div>
        @endif

        <p style="font-size:.78rem;color:rgb(107 114 128);margin-top:1rem">
            Kaynak: 26/12/2012 tarihli ve 28509 sayılı Resmî Gazete, İş Sağlığı ve Güvenliğine İlişkin İşyeri
            Tehlike Sınıfları Tebliği, EK-1 (taban liste). Tebliğ 2013-2026 arasında 16 kez kısmen değiştirildi;
            burada gösterilen sınıf 2012 taban metnindendir — sık değişen sektörlerde (inşaat, gıda, kimya vb.)
            güncel Resmî Gazete metniyle teyit edilmesi önerilir.
        </p>
    </x-filament::section>

    {{-- MYK ZORUNLULUK SORGULA --}}
    <x-filament::section icon="heroicon-o-identification" icon-color="warning">
        <x-slot name="heading">MYK Zorunluluk Sorgula</x-slot>
        <x-slot name="description">Meslek adı veya yeterlilik kodu ile MYK (Mesleki Yeterlilik Belgesi) zorunluluğu kapsamındaki meslekleri arayın.</x-slot>

        <div style="display:flex;gap:.75rem;align-items:end;flex-wrap:wrap">
            <div style="flex:1;min-width:200px">
                <label style="font-weight:600;font-size:.82rem">Meslek Adı veya Kodu</label>
                <input type="text" placeholder="Örn: Betonarme Demircisi veya 11UY0011" wire:model="mykAramaTerimi" wire:keydown.enter="mykAra" style="{{ $girdi }}">
            </div>
            <x-filament::button color="warning" wire:click="mykAra">Sorgula</x-filament::button>
            @if ($mykAramaTerimi || $mykArandi)
                <x-filament::button size="sm" color="gray" wire:click="mykSifirla">Temizle</x-filament::button>
            @endif
        </div>

        @if ($mykArandi)
            @if ($mykSonuclar->isEmpty())
                <div style="margin-top:1rem;color:rgb(220 38 38);font-size:.85rem">Eşleşen meslek bulunamadı.</div>
            @else
                <div style="margin-top:1rem;display:flex;flex-direction:column;gap:.6rem">
                    @foreach ($mykSonuclar->groupBy('yeterlilik_adi') as $ad => $kayitlar)
                        <div style="{{ $kutu }}">
                            <div style="font-weight:600">{{ $ad }}</div>
                            <div style="display:flex;gap:.4rem;flex-wrap:wrap;margin-top:.4rem">
                                @foreach ($kayitlar as $k)
                                    <span style="font-size:.78rem;font-family:monospace;background:rgb(107 114 128 / .12);border-radius:.4rem;padding:.15rem .5rem">{{ $k->yeterlilik_kodu }}</span>
                                @endforeach
                            </div>
                            <div style="font-size:.78rem;color:rgb(107 114 128);margin-top:.4rem">
                                Belge Zorunluluk Tarihi: {{ $kayitlar->first()->belge_zorunluluk_tarihi ?? 'Henüz belirlenmemiş' }}
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        @endif

        <p style="font-size:.78rem;color:rgb(107 114 128);margin-top:1rem">
            Kaynak: MYK (Mesleki Yeterlilik Kurumu) resmi portalı, "Belge Zorunluluğu Kapsamındaki Meslekler"
            sorgu sayfası — 05.09.2026 anlık görüntüsü, {{ \App\Models\MykMeslek::count() }} yeterlilik kodu.
            MYK bu listeyi periyodik olarak yeni meslek eklemeleriyle günceller; kesinleştirme için
            <a href="https://portal.myk.gov.tr/index.php?belge_zorunlu=1&option=com_yeterlilik&view=arama" target="_blank" style="color:rgb(217 119 6)">resmi MYK kaynağını</a>
            kontrol edin.
        </p>
    </x-filament::section>
</x-filament-panels::page>
