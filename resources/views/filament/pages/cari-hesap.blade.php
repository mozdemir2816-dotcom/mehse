@php
    $p = $this->portfoy;
    $girdi = 'width:100%;padding:.42rem .6rem;border-radius:.45rem;border:1px solid rgb(107 114 128 / .35);background:transparent;font-size:.83rem';
    $kutu = 'border:1px solid rgb(128 116 148 / .28);border-radius:.75rem;padding:.9rem';
@endphp

<x-filament-panels::page>
    <p class="mehse-bolum-aciklama">
        Hizmet verdiğiniz firmaların aylık İSG sözleşme bedellerini, borç/tahsilat hareketlerini ve güncel alacak bakiyelerini
        takip edin; tek tıkla resmi PDF cari hesap ekstresi alın.
    </p>

    {{-- PORTFÖY FİNANS ÖZET KARTLARI --}}
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(190px,1fr));gap:.75rem">
        <div style="{{ $kutu }}">
            <div style="font-size:.73rem;text-transform:uppercase;color:rgb(107 114 128);font-weight:700">Aylık Sözleşme Cirosu (KDV Dahil)</div>
            <div style="font-size:1.45rem;font-weight:800;color:#2563eb;margin-top:.2rem">{{ number_format($p['aylik_ciro'], 2, ',', '.') }} ₺</div>
        </div>
        <div style="{{ $kutu }}">
            <div style="font-size:.73rem;text-transform:uppercase;color:rgb(107 114 128);font-weight:700">Toplam Açık Alacak (Bakiye)</div>
            <div style="font-size:1.45rem;font-weight:800;color:{{ $p['toplam_alacak'] > 0 ? '#dc2626' : '#15803d' }};margin-top:.2rem">{{ number_format($p['toplam_alacak'], 2, ',', '.') }} ₺</div>
        </div>
        <div style="{{ $kutu }}">
            <div style="font-size:.73rem;text-transform:uppercase;color:rgb(107 114 128);font-weight:700">Bu Ay Tahsilat</div>
            <div style="font-size:1.45rem;font-weight:800;color:#15803d;margin-top:.2rem">{{ number_format($p['bu_ay_tahsilat'], 2, ',', '.') }} ₺</div>
        </div>
        <div style="{{ $kutu }}">
            <div style="font-size:.73rem;text-transform:uppercase;color:rgb(107 114 128);font-weight:700">Açık Bakiyeli Firma</div>
            <div style="font-size:1.45rem;font-weight:800;color:{{ $p['borclu_firma_sayisi'] > 0 ? '#b45309' : '#15803d' }};margin-top:.2rem">{{ $p['borclu_firma_sayisi'] }} firma</div>
        </div>
    </div>

    {{-- SEÇİLİ FİRMA CARİ DETAYI --}}
    @if ($this->secilenFirma && ($cari = $this->firmaCari))
        <x-filament::section icon="heroicon-o-building-office" icon-color="primary" style="margin-top:1rem">
            <x-slot name="heading">{{ $this->secilenFirma->unvan }} — Cari Hesap Detayı</x-slot>
            <x-slot name="headerEnd">
                <x-filament::button size="sm" color="gray" icon="heroicon-o-document-arrow-down" wire:click="ekstrePdf({{ $this->secilenFirma->id }})">
                    📄 PDF Hesap Ekstresi İndir
                </x-filament::button>
            </x-slot>

            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:1rem">
                {{-- Aylık ücret ayarı --}}
                <div style="{{ $kutu }}">
                    <div style="font-weight:700;font-size:.86rem;margin-bottom:.5rem">Aylık Hizmet Sözleşme Bedeli</div>
                    <div style="display:grid;grid-template-columns:1fr 90px auto;gap:.45rem;align-items:end">
                        <div>
                            <label class="mehse-etiket">Aylık Bedel (KDV Hariç ₺)</label>
                            <input type="number" step="0.01" min="0" wire:model="aylikUcret" style="{{ $girdi }};margin-top:.2rem">
                        </div>
                        <div>
                            <label class="mehse-etiket">KDV (%)</label>
                            <input type="number" min="0" max="100" wire:model="kdvOrani" style="{{ $girdi }};margin-top:.2rem">
                        </div>
                        <x-filament::button size="sm" wire:click="ucretKaydet">Kaydet</x-filament::button>
                    </div>
                    <div style="margin-top:.65rem;font-size:.8rem;color:rgb(107 114 128)">
                        Toplam Borç: <strong>{{ number_format($cari['toplam_borc'], 2, ',', '.') }} ₺</strong> ·
                        Tahsilat: <strong style="color:#15803d">{{ number_format($cari['toplam_tahsilat'], 2, ',', '.') }} ₺</strong> ·
                        Bakiye: <strong style="color:{{ $cari['bakiye'] > 0 ? '#dc2626' : '#15803d' }}">{{ number_format($cari['bakiye'], 2, ',', '.') }} ₺</strong>
                    </div>
                </div>

                {{-- Yeni hareket ekle --}}
                <div style="{{ $kutu }}">
                    <div style="font-weight:700;font-size:.86rem;margin-bottom:.5rem">+ Tahsilat veya Borç / Ek Hizmet İşle</div>
                    <div style="display:grid;grid-template-columns:120px 1fr 125px;gap:.45rem;margin-bottom:.45rem">
                        <select wire:model="hareketTur" style="{{ $girdi }}">
                            <option value="tahsilat">Tahsilat (+)</option>
                            <option value="borc">Borç / Fatura</option>
                        </select>
                        <input type="number" step="0.01" min="0" wire:model="hareketTutar" placeholder="Tutar (₺)" style="{{ $girdi }}">
                        <input type="date" wire:model="hareketTarih" style="{{ $girdi }}">
                    </div>
                    <div style="display:flex;gap:.45rem">
                        <input type="text" wire:model="hareketAciklama" placeholder="Açıklama (Örn: Kasım 2026 Havale / Risk Analizi Ek Ücreti)" style="{{ $girdi }};flex:1">
                        <x-filament::button size="sm" color="success" wire:click="hareketEkle">İşle</x-filament::button>
                    </div>
                </div>
            </div>

            {{-- Hareket geçmişi --}}
            <div style="overflow-x:auto;margin-top:.9rem">
                <table style="width:100%;border-collapse:collapse;font-size:.82rem">
                    <thead>
                        <tr style="text-align:left;border-bottom:1px solid rgb(128 116 148 / .25);color:rgb(107 114 128)">
                            <th style="padding:.4rem">Tarih</th>
                            <th style="padding:.4rem">Tür</th>
                            <th style="padding:.4rem">Açıklama</th>
                            <th style="padding:.4rem;text-align:right">Tutar</th>
                            <th style="padding:.4rem;text-align:right"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($cari['hareketler'] as $h)
                            <tr style="border-bottom:1px solid rgb(128 116 148 / .12)">
                                <td style="padding:.4rem">{{ \Illuminate\Support\Carbon::parse($h['tarih'])->format('d.m.Y') }}</td>
                                <td style="padding:.4rem">
                                    <span style="font-size:.72rem;font-weight:700;padding:.1rem .45rem;border-radius:999px;
                                        background:{{ $h['tur'] === 'tahsilat' ? 'rgb(22 163 74 / .14)' : 'rgb(220 38 38 / .12)' }};
                                        color:{{ $h['tur'] === 'tahsilat' ? '#15803d' : '#dc2626' }}">
                                        {{ $h['tur'] === 'tahsilat' ? 'Tahsilat' : 'Borç' }}
                                    </span>
                                </td>
                                <td style="padding:.4rem">{{ $h['aciklama'] }}</td>
                                <td style="padding:.4rem;text-align:right;font-weight:700">{{ number_format((float) $h['tutar'], 2, ',', '.') }} ₺</td>
                                <td style="padding:.4rem;text-align:right">
                                    <button type="button" wire:click="hareketSil('{{ $h['id'] }}')" style="border:none;background:none;color:#dc2626;cursor:pointer;font-size:.8rem" title="Sil">✕</button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" style="padding:1rem;text-align:center;color:rgb(107 114 128)">Bu firma için henüz hareket kaydı yok.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-filament::section>
    @endif

    {{-- TÜM FİRMALAR CARİ TABLOSU --}}
    <x-filament::section icon="heroicon-o-table-cells" icon-color="primary" style="margin-top:1rem">
        <x-slot name="heading">Aktif Firmalar Cari Listesi ({{ count($p['satirlar']) }} Firma)</x-slot>

        <div style="overflow-x:auto">
            <table style="width:100%;border-collapse:collapse;font-size:.82rem">
                <thead>
                    <tr style="text-align:left;border-bottom:1px solid rgb(128 116 148 / .25);color:rgb(107 114 128)">
                        <th style="padding:.45rem">Firma</th>
                        <th style="padding:.45rem;text-align:right">Aylık Bedel (KDV Dahil)</th>
                        <th style="padding:.45rem;text-align:right">Toplam Borç</th>
                        <th style="padding:.45rem;text-align:right">Tahsilat</th>
                        <th style="padding:.45rem;text-align:right">Kalan Bakiye</th>
                        <th style="padding:.45rem;text-align:right">İşlem</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($p['satirlar'] as $s)
                        <tr style="border-bottom:1px solid rgb(128 116 148 / .12);{{ $firmaId === $s['firma_id'] ? 'background:rgb(37 99 235 / .06)' : '' }}">
                            <td style="padding:.45rem">
                                <div style="font-weight:700">{{ $s['unvan'] }}</div>
                                <div style="font-size:.73rem;color:rgb(107 114 128)">{{ $s['tehlike'] }} · {{ $s['calisan'] }} çalışan</div>
                            </td>
                            <td style="padding:.45rem;text-align:right;font-variant-numeric:tabular-nums">
                                {{ $s['kdv_dahil_aylik'] > 0 ? number_format($s['kdv_dahil_aylik'], 2, ',', '.').' ₺' : '—' }}
                            </td>
                            <td style="padding:.45rem;text-align:right;font-variant-numeric:tabular-nums">{{ number_format($s['toplam_borc'], 2, ',', '.') }} ₺</td>
                            <td style="padding:.45rem;text-align:right;font-variant-numeric:tabular-nums;color:#15803d">{{ number_format($s['toplam_tahsilat'], 2, ',', '.') }} ₺</td>
                            <td style="padding:.45rem;text-align:right;font-variant-numeric:tabular-nums;font-weight:700;color:{{ $s['bakiye'] > 0.01 ? '#dc2626' : '#15803d' }}">
                                {{ number_format($s['bakiye'], 2, ',', '.') }} ₺
                            </td>
                            <td style="padding:.45rem;text-align:right;white-space:nowrap">
                                <button type="button" wire:click="firmaSec({{ $s['firma_id'] }})"
                                    style="font-size:.76rem;font-weight:700;color:#2563eb;border:none;background:none;cursor:pointer;margin-right:.4rem">
                                    Yönet
                                </button>
                                <button type="button" wire:click="ekstrePdf({{ $s['firma_id'] }})"
                                    style="font-size:.76rem;font-weight:600;color:rgb(107 114 128);border:none;background:none;cursor:pointer">
                                    PDF Ekstre
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" style="padding:1.5rem;text-align:center;color:rgb(107 114 128)">Aktif firma bulunamadı.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-filament::section>
</x-filament-panels::page>
