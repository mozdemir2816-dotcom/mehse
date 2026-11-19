<?php
    $kutu = 'border:1px solid rgb(107 114 128 / .3);border-radius:.75rem;padding:1rem';
    $girdi = 'margin-top:.3rem;width:100%;padding:.5rem .75rem;border-radius:.5rem;border:1px solid rgb(107 114 128 / .35);background:transparent';
?>
<x-filament-panels::page>
    @include('filament.pages.partials.acil-slate-stil')
    <p class="mehse-bolum-aciklama">
        İSG pratiğinde en sık başvurulan mevzuatın kısa dizini ve yapay zekâ destekli mevzuat danışmanı.
        Madde numarası/tarih gibi ayrıntılar için resmi kaynağa (mevzuat.gov.tr, resmigazete.gov.tr) bakınız.
    </p>

    {{-- MEVZUATA SOR (YAPAY ZEKA İSG MEVZUAT DANIŞMANI) --}}
    <div style="{{ $kutu }};margin-top:.75rem;background:rgb(180 83 9 / .04);border-color:rgb(180 83 9 / .3)">
        <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:.5rem">
            <div style="font-weight:700;font-size:.95rem">⚖️ Mevzuata Sor — İSG Mevzuat Danışmanı</div>
            <span style="font-size:.72rem;font-weight:600;padding:.15rem .55rem;border-radius:999px;background:rgb(180 83 9 / .14);color:rgb(180 83 9)">
                6331 · 4857 · 5510 &amp; İSG Yönetmelikleri
            </span>
        </div>
        <div style="font-size:.8rem;color:rgb(107 114 128);margin-top:.2rem">
            İSG mevzuatı, yasal süreler, kurul/temsilci/destek elemanı zorunlulukları veya bildirim süreleri hakkında soru sorun ya da aşağıdaki hazır sorulardan birine tıklayın:
        </div>

        <div style="display:flex;flex-wrap:wrap;gap:.35rem;margin-top:.6rem">
            @foreach (\App\Support\GeminiMevzuatDanismani::HAZIR_SORULAR as $anahtar => $hazir)
                <button type="button" wire:click="hazirSoruSor('{{ $anahtar }}')"
                    style="font-size:.75rem;padding:.25rem .6rem;border-radius:999px;border:1px solid rgb(180 83 9 / .35);background:transparent;color:inherit;cursor:pointer;text-align:left">
                    💬 {{ $hazir['soru'] }}
                </button>
            @endforeach
        </div>

        <div style="display:flex;gap:.5rem;align-items:center;flex-wrap:wrap;margin-top:.65rem">
            <div style="flex:1;min-width:220px">
                <input type="text"
                    placeholder="Örn: Çok tehlikeli sınıfta 45 çalışan için kaç ilk yardımcı ve destek elemanı gerekir?"
                    wire:model="mevzuatSoru"
                    wire:keydown.enter="mevzuataSor"
                    style="{{ $girdi }};margin-top:0">
            </div>
            <x-filament::button color="warning" wire:click="mevzuataSor">
                Mevzuata Sor
            </x-filament::button>
            @if ($danismanYaniti || $mevzuatSoru !== '')
                <x-filament::button color="gray" size="sm" wire:click="danismanTemizle">
                    Temizle
                </x-filament::button>
            @endif
        </div>

        @if ($danismanYaniti)
            <div style="margin-top:.85rem;padding:.85rem;border-radius:.6rem;border:1px solid rgb(180 83 9 / .35);background:rgb(107 114 128 / .06)">
                <div style="font-weight:700;font-size:.84rem;color:rgb(180 83 9);margin-bottom:.35rem">📌 Mevzuat Danışmanı Yanıtı</div>
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
    </div>

    {{-- 6331 SAYILI KANUN SADE ANLATIM & PRATİK UYGULAMA REHBERİ --}}
    @php $rehber6331 = \App\Support\GeminiMevzuatDanismani::kanun6331Rehberi(); @endphp
    <details style="{{ $kutu }};margin-top:.85rem">
        <summary style="cursor:pointer;font-weight:700;font-size:.92rem;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:.5rem">
            <span>📘 6331 Sayılı İş Sağlığı ve Güvenliği Kanunu — Sade Anlatım &amp; Pratik Uygulama Rehberi (39 Madde Özeti)</span>
            <span style="font-size:.75rem;font-weight:600;color:rgb(180 83 9)">Aç / Kapat ▾</span>
        </summary>

        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(260px,1fr));gap:.75rem;margin-top:.85rem">
            <div style="{{ $kutu }};background:rgb(59 130 246 / .05);border-color:rgb(59 130 246 / .3)">
                <div style="font-weight:700;font-size:.85rem;color:rgb(37 99 235)">🎯 Neyi Kapsar?</div>
                <div style="font-size:.8rem;margin-top:.3rem;line-height:1.45">{{ $rehber6331['kartlar']['neyi_kapsar'] }}</div>
            </div>
            <div style="{{ $kutu }};background:rgb(139 92 246 / .05);border-color:rgb(139 92 246 / .3)">
                <div style="font-weight:700;font-size:.85rem;color:rgb(124 58 237)">👥 Kimi Bağlar?</div>
                <div style="font-size:.8rem;margin-top:.3rem;line-height:1.45">{{ $rehber6331['kartlar']['kimi_baglar'] }}</div>
            </div>
            <div style="{{ $kutu }};background:rgb(34 197 94 / .05);border-color:rgb(34 197 94 / .3)">
                <div style="font-weight:700;font-size:.85rem;color:rgb(22 163 74)">✅ Pratikte Ne Yapmalı? (7 Adım)</div>
                <ul style="margin:.35rem 0 0;padding-left:1rem;font-size:.77rem;line-height:1.45;display:flex;flex-direction:column;gap:.2rem">
                    @foreach ($rehber6331['kartlar']['pratik_adimlar'] as $adim)
                        <li>{{ $adim }}</li>
                    @endforeach
                </ul>
            </div>
        </div>

        <div style="margin-top:.85rem">
            <div style="font-weight:700;font-size:.86rem;margin-bottom:.45rem">Sade Anlatımla 6331 Sayılı Kanun (8 Temel Başlık):</div>
            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:.65rem">
                @foreach ($rehber6331['bolumler'] as $b)
                    <div style="padding:.65rem .8rem;border-radius:.55rem;border:1px solid rgb(107 114 128 / .25)">
                        <div style="display:flex;align-items:center;justify-content:space-between;gap:.4rem">
                            <span style="font-weight:700;font-size:.82rem">{{ $b['baslik'] }}</span>
                            <span style="font-size:.7rem;font-weight:700;padding:.1rem .45rem;border-radius:999px;background:rgb(180 83 9 / .14);color:rgb(180 83 9);white-space:nowrap">{{ $b['maddeler'] }}</span>
                        </div>
                        <div style="font-size:.79rem;color:rgb(107 114 128);margin-top:.3rem;line-height:1.45">{{ $b['ozet'] }}</div>
                    </div>
                @endforeach
            </div>
        </div>

        <div style="margin-top:.75rem;display:flex;justify-content:flex-end">
            <a href="https://www.mevzuat.gov.tr/mevzuat?MevzuatNo=6331&MevzuatTur=1&MevzuatTertip=5" target="_blank" rel="noopener"
                style="font-size:.8rem;font-weight:700;color:rgb(180 83 9);text-decoration:none">
                Resmî Tam Metni Oku (mevzuat.gov.tr — 6331 s.K.) ↗
            </a>
        </div>
    </details>

    <div style="margin-top:1rem">
        <input type="text" placeholder="Mevzuat ara (ör. risk değerlendirmesi, KKD, gürültü...)" wire:model.live.debounce.300ms="arama" style="{{ $girdi }}">
    </div>

    <div style="display:flex;gap:.5rem;flex-wrap:wrap;margin-top:1rem">
        <x-filament::button size="sm" :color="$kategori === 'tumu' ? 'warning' : 'gray'" wire:click="kategoriSec('tumu')">Tümü</x-filament::button>
        @foreach (config('isg.mevzuat.kategoriler') as $anahtar => $etiket)
            <x-filament::button size="sm" :color="$kategori === $anahtar ? 'warning' : 'gray'" wire:click="kategoriSec('{{ $anahtar }}')">{{ $etiket }}</x-filament::button>
        @endforeach
    </div>

    @php $sonuclar = $this->sonuclar(); @endphp

    <p style="font-size:.78rem;color:rgb(107 114 128);margin-top:1rem">{{ count($sonuclar) }} kayıt</p>

    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:1rem;margin-top:.5rem">
        @forelse ($sonuclar as $m)
            <div style="{{ $kutu }};display:flex;flex-direction:column;justify-content:space-between">
                <div>
                    <x-filament::badge color="gray" size="sm">{{ config('isg.mevzuat.kategoriler')[$m['kategori']] }}</x-filament::badge>
                    <div style="font-weight:600;margin-top:.5rem">{{ $m['baslik'] }}</div>
                    <div style="font-size:.82rem;color:rgb(107 114 128);margin-top:.4rem">{{ $m['aciklama'] }}</div>
                </div>
                <div style="margin-top:.75rem;padding-top:.45rem;border-top:1px solid rgb(107 114 128 / .15);display:flex;justify-content:flex-end">
                    <a href="{{ \App\Support\GeminiMevzuatDanismani::resmiMevzuatUrl($m['baslik']) }}" target="_blank" rel="noopener"
                        style="font-size:.76rem;font-weight:600;color:rgb(180 83 9);text-decoration:none">
                        mevzuat.gov.tr'de Gör ↗
                    </a>
                </div>
            </div>
        @empty
            <p style="color:rgb(107 114 128);font-size:.85rem">Eşleşen mevzuat bulunamadı.</p>
        @endforelse
    </div>

    <p style="font-size:.78rem;color:rgb(107 114 128);margin-top:1.5rem">
        Bu dizin, mehse'nin kendi modüllerinde (Risk Değerlendirme, DÖF, Ceza-Tebliğ, KKD Formu vb.) dayanak
        olarak kullanılan mevzuatla sınırlı bir seçkidir — resmi mevzuat.gov.tr'nin tam kütüphanesi değildir.
        Resmi ve güncel metin için
        <a href="https://www.mevzuat.gov.tr" target="_blank" style="color:rgb(180 83 9)">mevzuat.gov.tr</a>
        veya
        <a href="https://www.resmigazete.gov.tr" target="_blank" style="color:rgb(180 83 9)">resmigazete.gov.tr</a>
        kullanılmalıdır.
    </p>
</x-filament-panels::page>
