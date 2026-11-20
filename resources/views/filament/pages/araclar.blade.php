@php
    $turuncu = 'rgb(180 83 9)';
    $kutu = 'border:1px solid rgb(107 114 128 / .3);border-radius:.75rem;padding:1rem';
    $girdi = 'margin-top:.3rem;width:100%;padding:.5rem .75rem;border-radius:.5rem;border:1px solid rgb(107 114 128 / .35);background:transparent';
@endphp

<x-filament-panels::page>
    <p class="mehse-bolum-aciklama">
        Firma seçimi gerektirmeyen, bağımsız İSG hesaplayıcıları. Girdiğiniz değerler kaydedilmez.
    </p>

    {{-- KAZA SIKLIK / AĞIRLIK HIZI --}}
    <x-filament::section icon="heroicon-o-calculator" icon-color="warning">
        <x-slot name="heading">Kaza Sıklık Hızı ve Ağırlık Hızı Hesaplayıcı</x-slot>
        <x-slot name="description">Sıklık Hızı = (Kaza Sayısı × 1.000.000) / Toplam Çalışma Saati &nbsp;·&nbsp; Ağırlık Hızı = (Kayıp Gün Sayısı × 1.000) / Toplam Çalışma Saati</x-slot>

        <div class="mehse-izgara">
            <div>
                <label class="mehse-etiket">Kaza Sayısı</label>
                <input type="number" min="0" wire:model.live="kazaSayisi" style="{{ $girdi }}">
            </div>
            <div>
                <label class="mehse-etiket">Toplam Kayıp Gün Sayısı</label>
                <input type="number" min="0" wire:model.live="kayipGunSayisi" style="{{ $girdi }}">
            </div>
            <div>
                <label class="mehse-etiket">Toplam Çalışma Saati (adam × saat)</label>
                <input type="number" min="0" step="0.01" wire:model.live="toplamCalismaSaati" style="{{ $girdi }}">
            </div>
        </div>

        <div style="margin-top:.75rem;padding:.65rem .85rem;border-radius:.55rem;border:1px dashed rgb(107 114 128 / .35);display:flex;align-items:end;gap:.65rem;flex-wrap:wrap">
            <div>
                <label class="mehse-etiket">Çalışan Sayısından Saat Hesapla (Kişi × 225 Saat × Ay)</label>
                <div style="display:flex;gap:.45rem;align-items:center;margin-top:.25rem">
                    <input type="number" min="1" placeholder="Çalışan sayısı" wire:model="kazaCalisanSayisi"
                        style="width:140px;padding:.4rem .6rem;border-radius:.45rem;border:1px solid rgb(107 114 128 / .35);background:transparent;font-size:.82rem">
                    <span style="font-size:.8rem;color:rgb(107 114 128)">×</span>
                    <input type="number" min="1" max="12" placeholder="Ay (12)" wire:model="kazaDonemAy"
                        style="width:80px;padding:.4rem .6rem;border-radius:.45rem;border:1px solid rgb(107 114 128 / .35);background:transparent;font-size:.82rem">
                    <span style="font-size:.78rem;color:rgb(107 114 128)">ay</span>
                </div>
            </div>
            <x-filament::button size="xs" color="warning" wire:click="calisanSayisindanSaatHesapla">Çalışma Saatini Hesapla</x-filament::button>
            <x-filament::button size="xs" color="gray" wire:click="kazaOrnekVeriDoldur">Örnek Veriyle Dene</x-filament::button>
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
                        <label class="mehse-etiket">Ölçülen Düzey (dB(A))</label>
                        <input type="number" step="0.1" wire:model.live="gurultuOlcumleri.{{ $i }}.db" style="{{ $girdi }}">
                    </div>
                    <div>
                        <label class="mehse-etiket">Maruziyet Süresi (saat)</label>
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

    {{-- NACE KOD / SGK SİCİL NO / FAALİYET ADI → TEHLİKE SINIFI SORGULA --}}
    <x-filament::section icon="heroicon-o-magnifying-glass" icon-color="warning">
        <x-slot name="heading">Tehlike Sınıfını Bul — NACE Kodu · 26 Haneli SGK Sicil No · Faaliyet Adı</x-slot>
        <x-slot name="description">6 haneli NACE Rev.2 koduyla, 26 haneli SGK işyeri sicil numarasıyla veya faaliyet adıyla işyerinin tehlike sınıfını ve 13 temel yasal İSG yükümlülüğünü sorgulayın.</x-slot>

        {{-- 1. 26 HANELİ SGK SİCİL NO ÇÖZÜMLEYİCİ --}}
        <div style="{{ $kutu }};margin-bottom:1rem;background:rgb(180 83 9 / .03)">
            <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:.5rem">
                <div style="font-weight:700;font-size:.88rem">🔢 26 Haneli SGK İşyeri Sicil Numarası Çözümleyici</div>
                <x-filament::button size="xs" color="gray" wire:click="ornekSgkSicilDene">Örnek Numarayla Dene</x-filament::button>
            </div>
            <div style="font-size:.78rem;color:rgb(107 114 128);margin-top:.15rem">
                SGK Vergi/İşe Giriş bildirgesindeki 26 haneli işyeri sicil numarasını yapıştırın (boşluk veya tire içerebilir); 2-5. hanedeki 4 haneli NACE işkolu otomatik ayrıştırılır.
            </div>
            <div style="display:flex;gap:.6rem;align-items:end;flex-wrap:wrap;margin-top:.5rem">
                <div style="flex:1;min-width:240px">
                    <input type="text" placeholder="Örn: 2 4120 01 01 1234567 034 01 85 000" wire:model="sgkSicilGirdi" wire:keydown.enter="sgkSicilCozumle" style="{{ $girdi }};margin-top:0;font-family:monospace">
                </div>
                <x-filament::button size="sm" color="warning" wire:click="sgkSicilCozumle">Sicil No Çözümle</x-filament::button>
            </div>

            @if ($sgkCozumSonucu)
                @if (! $sgkCozumSonucu['gecerli'] && $sgkCozumSonucu['hata'])
                    <div style="margin-top:.65rem;color:rgb(185 28 28);font-size:.82rem">{{ $sgkCozumSonucu['hata'] }}</div>
                @elseif ($sgkCozumSonucu['gecerli'])
                    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(135px,1fr));gap:.45rem;margin-top:.75rem">
                        @foreach ($sgkCozumSonucu['parcalar'] as $p)
                            <div style="padding:.45rem .6rem;border-radius:.45rem;border:1px solid {{ ($p['vurgu'] ?? false) ? $turuncu : 'rgb(107 114 128 / .25)' }};background:{{ ($p['vurgu'] ?? false) ? 'rgb(180 83 9 / .1)' : 'transparent' }}">
                                <div style="font-size:.68rem;color:rgb(107 114 128);font-weight:600">{{ $p['etiket'] }}</div>
                                <div style="font-size:.95rem;font-weight:800;font-family:monospace;color:{{ ($p['vurgu'] ?? false) ? $turuncu : 'inherit' }}">{{ $p['deger'] }}</div>
                                <div style="font-size:.68rem;color:rgb(107 114 128)">{{ $p['aciklama'] }}</div>
                            </div>
                        @endforeach
                    </div>

                    @if ($sgkNaceAdaylari->isNotEmpty())
                        <div style="margin-top:.75rem">
                            <div style="font-size:.78rem;font-weight:700;margin-bottom:.35rem">
                                Bu İşkoluna ({{ substr($sgkCozumSonucu['nace4'], 0, 2).'.'.substr($sgkCozumSonucu['nace4'], 2, 2) }}) Ait 6 Haneli NACE Faaliyet Kırılımları (Seçmek için tıklayın):
                            </div>
                            <div style="display:flex;flex-direction:column;gap:.3rem">
                                @foreach ($sgkNaceAdaylari as $aday)
                                    <button type="button" wire:click="naceKodSec('{{ $aday->kod }}')"
                                        style="display:flex;align-items:center;justify-content:space-between;gap:.5rem;padding:.4rem .65rem;border-radius:.45rem;border:1px solid rgb(107 114 128 / .3);background:transparent;cursor:pointer;text-align:left;font-size:.8rem">
                                        <span><strong style="font-family:monospace">{{ $aday->kod }}</strong> — {{ $aday->tanim }}</span>
                                        <span style="font-weight:700;color:{{ $turuncu }};white-space:nowrap">{{ config('isg.tehlike_siniflari')[$aday->tehlike_sinifi] ?? $aday->tehlike_sinifi }} →</span>
                                    </button>
                                @endforeach
                            </div>
                        </div>
                    @endif
                @endif
            @endif
        </div>

        {{-- 2. 6 HANELİ NACE KODU VEYA FAALİYET ADIYLA SORGULA --}}
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(260px,1fr));gap:.85rem;align-items:end">
            <div style="display:flex;gap:.5rem;align-items:end">
                <div style="flex:1">
                    <label class="mehse-etiket">6 Haneli NACE Kodu</label>
                    <input type="text" placeholder="Örn: 01.11.14 veya 011114" wire:model="naceKoduGirdi" wire:keydown.enter="naceSorgula" style="{{ $girdi }}">
                </div>
                <x-filament::button color="warning" wire:click="naceSorgula">Sorgula</x-filament::button>
            </div>

            <div style="display:flex;gap:.5rem;align-items:end">
                <div style="flex:1">
                    <label class="mehse-etiket">Veya Faaliyet / Sektör Adıyla Ara</label>
                    <input type="text" placeholder="Örn: inşaat, metal, fırın, tekstil..." wire:model="faaliyetAramaGirdi" wire:keydown.enter="faaliyetIleNaceAra" style="{{ $girdi }}">
                </div>
                <x-filament::button color="gray" wire:click="faaliyetIleNaceAra">Faaliyet Ara</x-filament::button>
            </div>
        </div>

        @if ($naceKoduGirdi || $naceSonuc || $sgkSicilGirdi || $faaliyetAramaGirdi)
            <div style="margin-top:.6rem">
                <x-filament::button size="xs" color="gray" wire:click="naceSifirla">Tümünü Temizle</x-filament::button>
            </div>
        @endif

        @if ($faaliyetSonuclari->isNotEmpty())
            <div style="margin-top:.75rem;max-height:240px;overflow-y:auto;border:1px solid rgb(107 114 128 / .25);border-radius:.5rem;padding:.5rem">
                <div style="font-size:.75rem;color:rgb(107 114 128);margin-bottom:.35rem">{{ $faaliyetSonuclari->count() }} faaliyet eşleşti (seçmek için tıklayın):</div>
                <div style="display:flex;flex-direction:column;gap:.25rem">
                    @foreach ($faaliyetSonuclari as $f)
                        <button type="button" wire:click="naceKodSec('{{ $f->kod }}')"
                            style="display:flex;align-items:center;justify-content:space-between;gap:.5rem;padding:.35rem .55rem;border-radius:.4rem;border:1px solid rgb(107 114 128 / .2);background:transparent;cursor:pointer;text-align:left;font-size:.79rem">
                            <span><strong style="font-family:monospace">{{ $f->kod }}</strong> — {{ $f->tanim }}</span>
                            <span style="font-weight:700;color:{{ $turuncu }};white-space:nowrap">{{ config('isg.tehlike_siniflari')[$f->tehlike_sinifi] ?? $f->tehlike_sinifi }}</span>
                        </button>
                    @endforeach
                </div>
            </div>
        @endif

        @if ($naceHata)
            <div style="margin-top:1rem;color:rgb(185 28 28);font-size:.85rem">{{ $naceHata }}</div>
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

        {{-- 3. 13 SATIRLIK TEHLİKE SINIFINA GÖRE YASAL İSG YÜKÜMLÜLÜKLERİ MATRİSİ --}}
        @php $seciliSinif = $naceSonuc?->tehlike_sinifi; @endphp
        <details @if($seciliSinif) open @endif style="margin-top:1rem;border:1px solid rgb(107 114 128 / .3);border-radius:.65rem;padding:.75rem">
            <summary style="cursor:pointer;font-weight:700;font-size:.86rem">
                📋 Tehlike Sınıfına Göre 13 Temel Yasal İSG Yükümlülüğü Karşılaştırma Tablosu
                @if ($seciliSinif)
                    <span style="color:{{ $turuncu }}">({{ config('isg.tehlike_siniflari')[$seciliSinif] }} sınıf sütunu vurgulandı)</span>
                @endif
            </summary>
            <div style="overflow-x:auto;margin-top:.65rem">
                <table style="width:100%;border-collapse:collapse;font-size:.79rem">
                    <thead>
                        <tr style="text-align:left;border-bottom:2px solid rgb(107 114 128 / .3)">
                            <th style="padding:.4rem .5rem">Yükümlülük &amp; Dayanak</th>
                            <th style="padding:.4rem .5rem;{{ $seciliSinif === 'az_tehlikeli' ? 'background:rgb(34 197 94 / .14);font-weight:800' : '' }}">Az Tehlikeli</th>
                            <th style="padding:.4rem .5rem;{{ $seciliSinif === 'tehlikeli' ? 'background:rgb(245 158 11 / .15);font-weight:800' : '' }}">Tehlikeli</th>
                            <th style="padding:.4rem .5rem;{{ $seciliSinif === 'cok_tehlikeli' ? 'background:rgb(239 68 68 / .14);font-weight:800' : '' }}">Çok Tehlikeli</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach (\App\Support\SgkSicilCozumleyici::yukumlulukMatrisi() as $satir)
                            <tr style="border-bottom:1px solid rgb(107 114 128 / .14)">
                                <td style="padding:.4rem .5rem">
                                    <div style="font-weight:600">{{ $satir['baslik'] }}</div>
                                    <div style="font-size:.7rem;color:rgb(107 114 128)">{{ $satir['dayanak'] }}</div>
                                </td>
                                <td style="padding:.4rem .5rem;{{ $seciliSinif === 'az_tehlikeli' ? 'background:rgb(34 197 94 / .09);font-weight:700' : '' }}">{{ $satir['az_tehlikeli'] }}</td>
                                <td style="padding:.4rem .5rem;{{ $seciliSinif === 'tehlikeli' ? 'background:rgb(245 158 11 / .1);font-weight:700' : '' }}">{{ $satir['tehlikeli'] }}</td>
                                <td style="padding:.4rem .5rem;{{ $seciliSinif === 'cok_tehlikeli' ? 'background:rgb(239 68 68 / .09);font-weight:700' : '' }}">{{ $satir['cok_tehlikeli'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </details>

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
                <label class="mehse-etiket">Meslek Adı veya Kodu</label>
                <input type="text" placeholder="Örn: Betonarme Demircisi veya 11UY0011" wire:model="mykAramaTerimi" wire:keydown.enter="mykAra" style="{{ $girdi }}">
            </div>
            <x-filament::button color="warning" wire:click="mykAra">Sorgula</x-filament::button>
            @if ($mykAramaTerimi || $mykArandi)
                <x-filament::button size="sm" color="gray" wire:click="mykSifirla">Temizle</x-filament::button>
            @endif
        </div>

        @if ($mykArandi)
            @if ($mykSonuclar->isEmpty())
                <div style="margin-top:1rem;color:rgb(185 28 28);font-size:.85rem">Eşleşen meslek bulunamadı.</div>
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
            <a href="https://portal.myk.gov.tr/index.php?belge_zorunlu=1&option=com_yeterlilik&view=arama" target="_blank" style="color:rgb(180 83 9)">resmi MYK kaynağını</a>
            kontrol edin.
        </p>
    </x-filament::section>

    {{-- MEVZUATA SOR & İSG MEVZUAT DANIŞMANI --}}
    <x-filament::section icon="heroicon-o-scale" icon-color="warning">
        <x-slot name="heading">⚖️ Mevzuata Sor — İSG Mevzuat Danışmanı &amp; Kütüphanesi</x-slot>
        <x-slot name="description">6331, 4857, 5510 sayılı kanunlar ve İSG yönetmeliklerindeki yasal süreler, kontenjanlar ve bildirim yükümlülükleri hakkında hızlı soru sorun.</x-slot>

        <div style="display:flex;flex-wrap:wrap;gap:.35rem;margin-bottom:.65rem">
            @foreach (\App\Support\GeminiMevzuatDanismani::HAZIR_SORULAR as $anahtar => $hazir)
                <button type="button" wire:click="hazirSoruSor('{{ $anahtar }}')"
                    style="font-size:.75rem;padding:.25rem .6rem;border-radius:999px;border:1px solid rgb(180 83 9 / .35);background:transparent;color:inherit;cursor:pointer;text-align:left">
                    💬 {{ $hazir['soru'] }}
                </button>
            @endforeach
        </div>

        <div style="display:flex;gap:.75rem;align-items:end;flex-wrap:wrap">
            <div style="flex:1;min-width:220px">
                <label class="mehse-etiket">İSG Mevzuat Sorunuz</label>
                <input type="text" placeholder="Örn: Risk değerlendirmesi kaç yılda bir yenilenir?" wire:model="mevzuatSoru" wire:keydown.enter="mevzuataSor" style="{{ $girdi }}">
            </div>
            <x-filament::button color="warning" wire:click="mevzuataSor">Mevzuata Sor</x-filament::button>
            @if ($danismanYaniti || $mevzuatSoru !== '')
                <x-filament::button size="sm" color="gray" wire:click="danismanTemizle">Temizle</x-filament::button>
            @endif
            <x-filament::button size="sm" color="gray" tag="a" :href="\App\Filament\Pages\Mevzuat::getUrl()">
                Tüm Mevzuat Kütüphanesi →
            </x-filament::button>
        </div>

        @if ($danismanYaniti)
            <div style="{{ $kutu }};margin-top:1rem;border-color:rgb(180 83 9 / .35)">
                <div style="font-weight:700;font-size:.84rem;color:{{ $turuncu }};margin-bottom:.35rem">📌 Mevzuat Danışmanı Yanıtı</div>
                <div style="font-size:.85rem;line-height:1.55;white-space:pre-line">{{ $danismanYaniti }}</div>

                @if ($danismanKaynaklari)
                    <div style="margin-top:.65rem;padding-top:.55rem;border-top:1px dashed rgb(107 114 128 / .3)">
                        <div style="font-size:.75rem;font-weight:700;color:rgb(107 114 128);margin-bottom:.3rem">İlgili Mevzuat Dayanakları:</div>
                        <div style="display:flex;flex-wrap:wrap;gap:.4rem">
                            @foreach ($danismanKaynaklari as $k)
                                <a href="{{ \App\Support\GeminiMevzuatDanismani::resmiMevzuatUrl($k['baslik']) }}" target="_blank" rel="noopener"
                                    style="font-size:.74rem;padding:.15rem .55rem;border-radius:.4rem;border:1px solid rgb(107 114 128 / .35);text-decoration:none;color:inherit">
                                    ⚖️ {{ $k['baslik'] }} ↗
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
        @endif
    </x-filament::section>

    {{-- İSG TERİMLER SÖZLÜĞÜ --}}
    <x-filament::section icon="heroicon-o-book-open" icon-color="warning" collapsible>
        <x-slot name="heading">📖 İSG Terimler Sözlüğü ({{ count(\App\Support\IsgSozlukKutuphanesi::tumu()) }} Temel Kavram)</x-slot>
        <x-slot name="description">Risk analizlerinde, ortam ölçümlerinde, kimyasal yönetiminde ve İSG mevzuatında geçen teknik terimlerin pratik açıklamaları.</x-slot>

        <div style="display:flex;gap:.5rem;flex-wrap:wrap;margin-bottom:.65rem">
            <x-filament::button size="xs" :color="$sozlukKategori === '' ? 'warning' : 'gray'" wire:click="$set('sozlukKategori', '')">Tümü</x-filament::button>
            @foreach (\App\Support\IsgSozlukKutuphanesi::KATEGORILER as $katAnahtar => $katEtiket)
                <x-filament::button size="xs" :color="$sozlukKategori === $katAnahtar ? 'warning' : 'gray'" wire:click="$set('sozlukKategori', '{{ $katAnahtar }}')">{{ $katEtiket }}</x-filament::button>
            @endforeach
        </div>

        <input type="text" placeholder="Terim veya açıklama ara (Örn: LOTO, Fine-Kinney, TWA, Ramak Kala, ATEX, EK-2...)"
            wire:model.live.debounce.300ms="sozlukArama" style="{{ $girdi }};margin-bottom:.75rem">

        @php $sozlukKayitlari = $this->sozlukSonuclari(); @endphp
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(270px,1fr));gap:.65rem;max-height:420px;overflow-y:auto;padding-right:.25rem">
            @forelse ($sozlukKayitlari as $t)
                <div style="{{ $kutu }}">
                    <div style="display:flex;align-items:center;justify-content:space-between;gap:.4rem">
                        <span style="font-weight:700;font-size:.86rem;color:{{ $turuncu }}">{{ $t['terim'] }}</span>
                        <span style="font-size:.68rem;padding:.1rem .45rem;border-radius:999px;background:rgb(107 114 128 / .14);color:rgb(107 114 128);white-space:nowrap">
                            {{ \App\Support\IsgSozlukKutuphanesi::KATEGORILER[$t['kategori']] ?? $t['kategori'] }}
                        </span>
                    </div>
                    <div style="font-size:.8rem;color:rgb(107 114 128);margin-top:.3rem;line-height:1.45">{{ $t['aciklama'] }}</div>
                </div>
            @empty
                <div style="font-size:.82rem;color:rgb(107 114 128)">Eşleşen İSG terimi bulunamadı.</div>
            @endforelse
        </div>
    </x-filament::section>

    {{-- İSG UZMANLIK DENEME SINAVI (ÖSYM MODU) & SAHA HAP NOTLARI --}}
    <x-filament::section icon="heroicon-o-academic-cap" icon-color="warning" collapsible>
        <x-slot name="heading">🎓 İSG Uzmanlık Deneme Sınavı (ÖSYM Modu) &amp; Saha Sayısal Hap Notlar (PDF)</x-slot>
        <x-slot name="description">A/B/C Sınıfı İSG Uzmanlığı ve İşyeri Hekimliği sınavlarında en sık çıkan mevzuat soruları, çözümlü açıklamaları ve tek sayfalık Saha Sayısal Hap Notlar PDF'i.</x-slot>

        @php
            $denemeSorular = \App\Support\IsgDenemeSinaviKutuphanesi::sorular();
            $hapNotlar = \App\Support\IsgDenemeSinaviKutuphanesi::hapNotlar();
            $dogruSayisi = 0;
            foreach ($denemeSorular as $idx => $s) {
                if (($denemeCevaplari[$idx] ?? -1) === $s['dogru']) {
                    $dogruSayisi++;
                }
            }
        @endphp

        <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:.5rem;margin-bottom:.75rem">
            <div style="font-size:.82rem;color:rgb(107 114 128)">
                İşaretlenen: <strong>{{ count($denemeCevaplari) }} / {{ count($denemeSorular) }}</strong>
                @if ($denemeTamamlandi)
                    &nbsp;·&nbsp; <strong style="color:rgb(22 163 74)">Doğru: {{ $dogruSayisi }} / {{ count($denemeSorular) }} (%{{ round(($dogruSayisi / max(1, count($denemeSorular))) * 100) }})</strong>
                @endif
            </div>
            <div style="display:flex;gap:.45rem;flex-wrap:wrap">
                <x-filament::button size="xs" color="warning" icon="heroicon-o-document-arrow-down" wire:click="hapNotlarPdfIndir">
                    📄 Saha &amp; Sınav Hap Notları İndir (PDF)
                </x-filament::button>
                <x-filament::button size="xs" color="success" wire:click="denemeKontrolEt">
                    ✓ Cevapları Kontrol Et &amp; Çözümleri Gör
                </x-filament::button>
                @if ($denemeCevaplari || $denemeTamamlandi)
                    <x-filament::button size="xs" color="gray" wire:click="denemeSifirla">Sıfırla</x-filament::button>
                @endif
            </div>
        </div>

        <details style="margin-bottom:.85rem;border:1px solid rgb(107 114 128 / .25);border-radius:.6rem;padding:.65rem .8rem">
            <summary style="cursor:pointer;font-weight:700;font-size:.85rem;color:{{ $turuncu }}">
                📌 Kritik Sayısal İSG Hap Notlar Tablosu ({{ count($hapNotlar) }} Başlık — Görüntülemek İçin Tıklayın)
            </summary>
            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:.65rem;margin-top:.65rem">
                @foreach ($hapNotlar as $hn)
                    <div style="{{ $kutu }}">
                        <div style="font-weight:700;font-size:.82rem;color:{{ $turuncu }};margin-bottom:.3rem">{{ $hn['baslik'] }}</div>
                        <ul style="margin:0;padding-left:1rem;font-size:.76rem;line-height:1.45;display:flex;flex-direction:column;gap:.2rem">
                            @foreach ($hn['maddeler'] as $m)
                                <li>{{ $m }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endforeach
            </div>
        </details>

        <div style="display:flex;flex-direction:column;gap:.65rem;max-height:480px;overflow-y:auto;padding-right:.25rem">
            @foreach ($denemeSorular as $sIndex => $s)
                @php
                    $secilen = $denemeCevaplari[$sIndex] ?? null;
                @endphp
                <div style="{{ $kutu }}">
                    <div style="display:flex;align-items:center;justify-content:space-between;gap:.4rem;margin-bottom:.3rem">
                        <span style="font-weight:700;font-size:.82rem">Soru {{ $sIndex + 1 }}</span>
                        <span style="font-size:.7rem;padding:.1rem .45rem;border-radius:999px;background:rgb(180 83 9 / .14);color:{{ $turuncu }}">{{ $s['konu'] }}</span>
                    </div>
                    <div style="font-size:.84rem;font-weight:600;margin-bottom:.5rem">{{ $s['soru'] }}</div>
                    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:.35rem">
                        @foreach ($s['siklar'] as $sikIndex => $sikMetin)
                            @php
                                $buSecili = $secilen === $sikIndex;
                                $buDogru = $s['dogru'] === $sikIndex;
                                $renkStil = match (true) {
                                    $denemeTamamlandi && $buDogru => 'border-color:rgb(22 163 74);background:rgb(22 163 74 / .14);font-weight:700',
                                    $denemeTamamlandi && $buSecili && ! $buDogru => 'border-color:rgb(220 38 38);background:rgb(220 38 38 / .12)',
                                    $buSecili => 'border-color:'.$turuncu.';background:rgb(180 83 9 / .12);font-weight:600',
                                    default => 'border-color:rgb(107 114 128 / .25);background:transparent',
                                };
                            @endphp
                            <button type="button" wire:click="denemeSikSec({{ $sIndex }}, {{ $sikIndex }})"
                                style="text-align:left;padding:.4rem .6rem;border-radius:.45rem;border:1px solid;cursor:pointer;font-size:.79rem;color:inherit;{{ $renkStil }}">
                                {{ $sikMetin }}
                            </button>
                        @endforeach
                    </div>

                    @if ($denemeTamamlandi)
                        <div style="margin-top:.55rem;padding:.5rem .65rem;border-radius:.45rem;background:rgb(107 114 128 / .08);font-size:.78rem">
                            <strong style="color:rgb(22 163 74)">✓ Çözüm &amp; Mevzuat Dayanağı:</strong> {{ $s['cozum'] }}
                        </div>
                    @endif
                </div>
            @endforeach
        </div>
    </x-filament::section>
</x-filament-panels::page>
