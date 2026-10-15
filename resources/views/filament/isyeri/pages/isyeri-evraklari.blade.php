@php
    $firma = $this->firma;
    $oz = $this->ozet;
    $kart = 'border:1px solid rgb(107 114 128 / .22);border-radius:.75rem;padding:1rem 1.1rem;background:rgb(255 255 255 / .6)';
    $etiket = 'font-size:.68rem;letter-spacing:.04em;text-transform:uppercase;color:rgb(107 114 128);font-weight:600';
@endphp

<x-filament-panels::page>
    {{-- KÜNYE --}}
    <div style="{{ $kart }}">
        <div style="display:flex;align-items:center;gap:.9rem;flex-wrap:wrap">
            @if ($firma->logo)
                <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($firma->logo) }}" alt="" style="height:44px;max-width:120px;object-fit:contain">
            @endif
            <div style="flex:1;min-width:14rem">
                <div style="{{ $etiket }}">Firma</div>
                <div style="font-size:1.15rem;font-weight:700">{{ $firma->unvan }}</div>
            </div>
            <span style="font-size:.75rem;padding:.2rem .6rem;border-radius:999px;background:rgb(16 185 129 / .12);color:#047857;font-weight:600">Aktif</span>
        </div>

        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(170px,1fr));gap:.6rem;margin-top:.9rem">
            @foreach ([
                'SGK sicil no' => $firma->sgk_sicil_no,
                'NACE kodu' => $firma->nace_kodu,
                'Tehlike sınıfı' => $firma->tehlike_sinifi ? $firma->tehlikeSinifiEtiketi() : null,
                'İş güvenliği uzmanı' => $firma->igu?->ad_soyad,
                'İşyeri hekimi' => $firma->isyeriHekimi?->ad_soyad,
                'Sözleşme' => $firma->sozlesme_baslangic ? $firma->sozlesme_baslangic->format('d.m.Y').($firma->sozlesme_bitis ? ' – '.$firma->sozlesme_bitis->format('d.m.Y') : '') : null,
            ] as $ad => $deger)
                <div style="border:1px solid rgb(107 114 128 / .15);border-radius:.5rem;padding:.5rem .65rem">
                    <div style="{{ $etiket }}">{{ $ad }}</div>
                    <div style="font-size:.85rem;margin-top:.15rem;word-break:break-word">{{ $deger ?: '—' }}</div>
                </div>
            @endforeach
        </div>
        @if ($firma->nace_aciklama || $firma->adres)
            <div style="font-size:.8rem;color:rgb(75 85 99);margin-top:.7rem">
                {{ $firma->nace_aciklama }}{{ $firma->nace_aciklama && $firma->adres ? ' · ' : '' }}{{ $firma->adres }}
            </div>
        @endif
    </div>

    {{-- ÖZET --}}
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:.75rem">
        @foreach ([['Aktif çalışan', $oz['calisan'], '#0f766e'], ['Evrak', $oz['evrak'], '#2563eb'], ['Aktif iş izni', $oz['aktif_izin'], '#10b981']] as [$ad, $sayi, $renk])
            <div style="{{ $kart }};border-left:4px solid {{ $renk }}">
                <div style="font-size:.75rem;color:rgb(107 114 128)">{{ $ad }}</div>
                <div style="font-size:1.6rem;font-weight:700;color:{{ $renk }}">{{ $sayi }}</div>
            </div>
        @endforeach
    </div>

    {{-- İSG BİLGİLENDİRMELERİ (Ön Bilgilendirme / Tebliğ) --}}
    @if ($this->bilgilendirmeler->isNotEmpty())
        @php $bekleyen = $this->bilgilendirmeler->whereNull('isyeri_okundu_at')->count(); @endphp
        <div style="{{ $kart }};border-left:4px solid {{ $bekleyen ? '#f59e0b' : '#10b981' }}">
            <div style="font-weight:700;margin-bottom:.2rem">İSG Bilgilendirmeleri</div>
            <div style="font-size:.8rem;color:rgb(107 114 128);margin-bottom:.6rem">
                İSG uzmanınızın işe başlamadan önce bildirdiği tedbirler.
                @if ($bekleyen)
                    <strong style="color:#92400e">{{ $bekleyen }} yazı okuma onayınızı bekliyor.</strong>
                @endif
            </div>
            @foreach ($this->bilgilendirmeler as $b)
                <div style="display:flex;gap:.6rem;align-items:center;flex-wrap:wrap;border-top:1px solid rgb(107 114 128 / .15);padding:.55rem 0">
                    <div style="flex:1;min-width:14rem">
                        <div style="font-weight:600;font-size:.88rem">{{ $b->konu }}</div>
                        <div style="font-size:.76rem;color:rgb(107 114 128)">
                            {{ $b->bildirim_tarihi?->format('d.m.Y') }}
                            @if ($b->isyeri_okundu_at)
                                · <span style="color:#047857">Onaylandı {{ $b->isyeri_okundu_at->format('d.m.Y H:i') }}</span>
                            @endif
                        </div>
                        @if ($b->aciklama)
                            <div style="font-size:.78rem;margin-top:.15rem">{{ $b->aciklama }}</div>
                        @endif
                    </div>
                    <x-filament::button size="sm" color="gray" icon="heroicon-o-arrow-down-tray" wire:click="bilgilendirmeIndir({{ $b->id }})">Yazıyı İndir</x-filament::button>
                    @unless ($b->isyeri_okundu_at)
                        <x-filament::button size="sm" color="warning" icon="heroicon-o-check-badge" wire:click="mountAction('tebellug', { id: {{ $b->id }} })">Okudum, tebellüğ ettim</x-filament::button>
                    @endunless
                </div>
            @endforeach
        </div>
    @endif

    {{-- PERSONEL --}}
    <div style="{{ $kart }}">
        <div style="font-weight:700;margin-bottom:.6rem">Personel</div>
        <div style="display:flex;gap:.5rem;flex-wrap:wrap">
            <x-filament::button color="gray" icon="heroicon-o-document-text" wire:click="personelListesi">Personel Listesi (PDF)</x-filament::button>
            <x-filament::button color="gray" icon="heroicon-o-table-cells" wire:click="personelDosyasi">Personel Dosyası — Eğitim ve Sağlık (Excel)</x-filament::button>
        </div>
    </div>

    {{-- EVRAKLAR --}}
    <div style="{{ $kart }}">
        <div style="display:flex;align-items:center;gap:.6rem;flex-wrap:wrap;margin-bottom:.6rem">
            <div style="font-weight:700;flex:1">İSG Evrakları</div>
            @if ($this->evraklar)
                <x-filament::button icon="heroicon-o-archive-box-arrow-down" wire:click="tumunuIndir">Tümünü İndir (ZIP)</x-filament::button>
            @endif
        </div>

        @if (! $this->evraklar)
            <p style="font-size:.85rem;color:rgb(107 114 128)">Henüz hazırlanmış evrak yok. İSG uzmanınız evrak hazırladıkça burada görünecek.</p>
        @else
            <div style="overflow-x:auto">
                <table style="width:100%;border-collapse:collapse;font-size:.85rem">
                    <tr>
                        <th style="text-align:left;padding:.45rem .5rem;border-bottom:1px solid rgb(107 114 128 / .3)">Evrak</th>
                        <th style="text-align:left;padding:.45rem .5rem;border-bottom:1px solid rgb(107 114 128 / .3);white-space:nowrap">Tarih</th>
                        <th style="border-bottom:1px solid rgb(107 114 128 / .3)"></th>
                    </tr>
                    @foreach ($this->evraklar as $e)
                        <tr style="border-bottom:1px solid rgb(107 114 128 / .12)">
                            <td style="padding:.45rem .5rem">{{ $e['tip'] }}</td>
                            <td style="padding:.45rem .5rem;white-space:nowrap;color:rgb(75 85 99)">{{ $e['tarih']->format('d.m.Y') }}</td>
                            <td style="padding:.45rem .5rem;text-align:right">
                                <x-filament::button size="xs" color="gray" icon="heroicon-o-arrow-down-tray" wire:click="evrakIndir('{{ addslashes($e['anahtar']) }}')">PDF</x-filament::button>
                            </td>
                        </tr>
                    @endforeach
                </table>
            </div>
        @endif
    </div>

    <p style="font-size:.75rem;color:rgb(107 114 128)">
        Bu sayfa yalnız görüntüleme içindir. Evraklarda değişiklik veya yeni evrak için İSG uzmanınızla iletişime geçin.
        Personel dosyası kişisel veri içerir; KVKK kapsamında yalnız yetkili kişilerle paylaşınız.
    </p>
</x-filament-panels::page>
