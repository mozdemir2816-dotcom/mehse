{{-- Genel Ajanda: portföy takvimi (eski Saha Takvimi) — Concerns\PortfoyAjandasi --}}
@php
    $mor = 'rgb(139 92 246)';
    $yesil = 'rgb(34 197 94)';
    $mavi = 'rgb(59 130 246)';
    $kutu = 'border:1px solid rgb(128 116 148 / .3);border-radius:.75rem;padding:1rem';
    $ozet = $this->ozet;
    $gunler = $this->gunlukGruplar;
    $rozetler = $this->ayRozetleri;
    $ayBaslangic = \Illuminate\Support\Carbon::parse($gosterilenAy.'-01');
    $gunSayisi = $ayBaslangic->daysInMonth;
    $baslangicBosluk = $ayBaslangic->dayOfWeekIso - 1;
    $bugun = \Illuminate\Support\Carbon::today()->toDateString();
@endphp
    <div style="display:flex;justify-content:space-between;align-items:center">
        <div>
            <p style="font-size:.85rem;color:rgb(128 116 148)">
                Portföy genelinde, tüm firmaların Ziyaret Programı'ndaki tarihli kayıtları tek
                takvimde. Salt okunur — düzenlemek için ilgili firmanın Ziyaret Programı'na gidin.
            </p>
        </div>
        <div style="display:flex;gap:.5rem">
            <div style="{{ $kutu }};padding:.5rem .9rem;text-align:center">
                <div style="font-size:1.2rem;font-weight:800;color:{{ $mor }}">{{ $ozet['ziyaret'] }}</div>
                <div style="font-size:.68rem;color:rgb(128 116 148)">Ziyaret</div>
            </div>
            <div style="{{ $kutu }};padding:.5rem .9rem;text-align:center">
                <div style="font-size:1.2rem;font-weight:800;color:{{ $yesil }}">{{ $ozet['firma'] }}</div>
                <div style="font-size:.68rem;color:rgb(128 116 148)">Firma</div>
            </div>
            @php $kd = $this->katipDoluluk; $kdRenk = $kd['yuzde'] > 100 ? '#dc2626' : ($kd['yuzde'] >= 90 ? '#d97706' : '#16a34a'); @endphp
            <div style="{{ $kutu }};padding:.5rem .9rem;text-align:center" title="Aylık İSG-KATİP doluluk: aktif firmaların yasal süreleri toplamı / aylık kapasite">
                <div style="font-size:1.2rem;font-weight:800;color:{{ $kdRenk }}">%{{ $kd['yuzde'] }}</div>
                <div style="font-size:.68rem;color:rgb(128 116 148)">İSG-KATİP doluluk</div>
            </div>
        </div>
    </div>

    <div style="display:grid;grid-template-columns:24rem 1fr;gap:1rem">
        <div style="{{ $kutu }}">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:.6rem">
                <button type="button" wire:click="ayDegistir(-1)" style="border:none;background:none;cursor:pointer;font-size:1rem">‹ Önceki</button>
                <div style="font-weight:700">{{ $ayBaslangic->translatedFormat('F Y') }}</div>
                <button type="button" wire:click="ayDegistir(1)" style="border:none;background:none;cursor:pointer;font-size:1rem">Sonraki ›</button>
            </div>
            <div style="text-align:center;margin-bottom:.6rem">
                <button type="button" wire:click="buguneGit" style="border:1px solid rgb(128 116 148 / .35);border-radius:.4rem;background:none;cursor:pointer;font-size:.72rem;padding:.2rem .7rem">Bugün</button>
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
                        $r = $rozetler[$tarih] ?? null;
                        $seciliMi = $tarih === $seciliTarih;
                        $bugunMu = $tarih === $bugun;
                    @endphp
                    <button type="button" wire:click="tarihSec('{{ $tarih }}')"
                        style="min-height:3.1rem;border-radius:.35rem;cursor:pointer;font-size:.78rem;position:relative;padding:.2rem;
                            border:{{ $bugunMu ? '1.5px solid '.$mor : '1px solid transparent' }};
                            background:{{ $seciliMi ? $mor : ($r ? 'rgb(139 92 246 / .1)' : 'transparent') }};
                            color:{{ $seciliMi ? '#fff' : 'inherit' }}">
                        <div>{{ $gun }}</div>
                        @if ($r)
                            <div style="display:flex;flex-direction:column;gap:1px;margin-top:1px">
                                @if ($r['tamamlandi'])
                                    <span style="font-size:.6rem;font-weight:700;color:{{ $seciliMi ? '#fff' : $yesil }}">{{ $r['tamamlandi'] }} ziyaret</span>
                                @endif
                                @if ($r['planlandi'])
                                    <span style="font-size:.6rem;font-weight:700;color:{{ $seciliMi ? '#fff' : $mavi }}">{{ $r['planlandi'] }} planlı</span>
                                @endif
                            </div>
                        @endif
                    </button>
                @endfor
            </div>
        </div>

        <div style="{{ $kutu }}">
            <div style="font-weight:700;margin-bottom:.6rem">{{ \Illuminate\Support\Carbon::parse($seciliTarih)->translatedFormat('d F Y') }}</div>
            @forelse ($this->gunlukListe as $z)
                @php
                    $zFirma = $z['firma'];
                    $durum = $z['durum'];
                    $sureDk = \App\Support\YasalZiyaretSuresi::dakikayaCevir($z['sure_saat']);
                    $durumRenk = $durum === 'tamamlandi' ? '#059669' : ($durum === 'planlandi' ? '#f59e0b' : '#94a3b8');
                @endphp
                <div style="border:1px solid rgb(128 116 148 / .22);border-radius:1rem;padding:.8rem 1rem;margin-bottom:.6rem">
                    <div style="display:flex;justify-content:space-between;align-items:start;gap:.5rem">
                        <div style="min-width:0">
                            <div style="font-weight:700;font-size:.92rem">{{ $zFirma?->unvan }}</div>
                            <div style="font-size:.78rem;color:rgb(128 116 148);margin-top:.15rem">{{ $z['amac'] ?: 'Amaç belirtilmemiş' }}</div>
                        </div>
                        <span style="flex-shrink:0;font-size:.68rem;font-weight:700;padding:.15rem .6rem;border-radius:999px;color:#fff;background:{{ $durumRenk }}">
                            {{ $durum === 'tamamlandi' ? 'Tamamlandı' : ($durum === 'planlandi' ? 'Planlandı' : 'Boş') }}
                        </span>
                    </div>
                    <div style="display:flex;flex-wrap:wrap;gap:.3rem 1.2rem;margin-top:.5rem;font-size:.76rem;color:rgb(100 116 139)">
                        <span>👷 Atanan İGU: <strong style="color:inherit">{{ $zFirma?->igu?->ad_soyad ?: '—' }}</strong></span>
                        <span>⏱ Ziyaret süresi: <strong style="color:inherit">{{ $z['sure_saat'] ? \App\Support\YasalZiyaretSuresi::etiket($sureDk) : '—' }}</strong></span>
                    </div>
                    @if ($zFirma)
                        <div style="display:flex;flex-wrap:wrap;gap:.5rem;margin-top:.7rem">
                            @if ($durum !== 'tamamlandi')
                                <x-filament::button tag="a" :href="\App\Filament\Pages\ZiyaretModu::getUrl(['firma' => $zFirma->id])" icon="heroicon-o-play" style="min-height:2.75rem">Ziyaret Modunu Başlat</x-filament::button>
                            @endif
                            <x-filament::button color="gray" icon="heroicon-o-calendar-days" wire:click="planAc({{ $zFirma->id }})" style="min-height:2.75rem">Yıllık planı aç</x-filament::button>
                        </div>
                    @endif
                </div>
            @empty
                <div style="text-align:center;padding:2rem;color:rgb(128 116 148)">Bu tarihte ziyaret kaydı bulunmuyor.</div>
            @endforelse
        </div>
    </div>
