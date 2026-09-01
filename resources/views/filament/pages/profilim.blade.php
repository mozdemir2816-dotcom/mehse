@php
    $sekmeler = \App\Filament\Pages\Profilim::SEKMELER;
    $bekleyen = \App\Filament\Pages\Profilim::BEKLEYEN_SEKMELER;
    $u = $this->kullanici;
    $o = $this->ozet;
    $mor = 'rgb(139 92 246)';
    $yesil = 'rgb(34 197 94)';
    $sari = 'rgb(245 158 11)';
    $kirmizi = 'rgb(239 68 68)';
    $kutu = 'border:1px solid rgb(107 114 128 / .3);border-radius:.75rem;padding:1rem';
    $bas = strtoupper(mb_substr($u->name ?? '?', 0, 1));
@endphp

<x-filament-panels::page>
    {{-- KÜNYE --}}
    <div style="{{ $kutu }};display:flex;align-items:center;gap:1rem;flex-wrap:wrap">
        <div style="width:3.5rem;height:3.5rem;border-radius:9999px;background:{{ $mor }};color:#fff;display:flex;align-items:center;justify-content:center;font-size:1.3rem;font-weight:800;flex-shrink:0">{{ $bas }}</div>
        <div style="flex:1;min-width:180px">
            <div style="font-size:1.15rem;font-weight:700">{{ $u->name }}</div>
            <div style="font-size:.82rem;color:rgb(107 114 128)">{{ $u->email }}</div>
        </div>
        <div style="display:flex;gap:.5rem;flex-wrap:wrap;align-items:center">
            <x-filament::badge>{{ $u->unvanEtiketi() }}</x-filament::badge>
            <x-filament::badge color="success">● Aktif</x-filament::badge>
            <x-filament::button size="sm" color="gray" icon="heroicon-o-cog-6-tooth" tag="a" :href="filament()->getProfileUrl()">
                Hesap Ayarları
            </x-filament::button>
        </div>
    </div>

    {{-- SAYAÇ KARTLARI --}}
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(120px,1fr));gap:.6rem">
        @foreach ([
            ['Firmalarım', $o['firma'], 'heroicon-o-building-office-2', $mor],
            ['Çalışanlarım', $o['calisan'], 'heroicon-o-user-group', $yesil],
            ['Risk Değerlendirmesi', $o['risk_degerlendirmesi'], 'heroicon-o-sparkles', $sari],
            ['Risk Şablonları', $o['risk_sablonu'], 'heroicon-o-rectangle-stack', $mor],
            ['Önemli Risk', $o['onemli_risk'], 'heroicon-o-exclamation-triangle', $kirmizi],
            ['Raporlarım', 0, 'heroicon-o-document-text', 'rgb(107 114 128)'],
        ] as [$etiket, $deger, $ikon, $renk])
            <div style="{{ $kutu }};padding:.75rem">
                <div style="display:flex;align-items:center;gap:.4rem;font-size:.7rem;color:rgb(107 114 128);text-transform:uppercase">
                    <x-filament::icon :icon="$ikon" style="width:.95rem;height:.95rem"/> {{ $etiket }}
                </div>
                <div style="font-size:1.4rem;font-weight:800;color:{{ $renk }}">{{ $deger }}</div>
            </div>
        @endforeach
    </div>

    {{-- SEKME PILL'LERİ --}}
    <div style="display:flex;gap:.4rem;flex-wrap:wrap;background:rgb(107 114 128 / .1);padding:.3rem;border-radius:.6rem">
        @foreach ($sekmeler as $anahtar => $ad)
            @php $aktif = $sekme === $anahtar; @endphp
            <button type="button" wire:click="sekmeSec('{{ $anahtar }}')"
                style="padding:.5rem .85rem;border:none;border-radius:.45rem;cursor:pointer;font-weight:600;font-size:.83rem;
                    background:{{ $aktif ? $mor : 'transparent' }};color:{{ $aktif ? '#fff' : 'inherit' }}">
                {{ $ad }}
            </button>
        @endforeach
    </div>

    {{-- ==================== GENEL BAKIŞ ==================== --}}
    @if ($sekme === 'genel')
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(260px,1fr));gap:1rem">
            {{-- Uyumluluk skoru --}}
            <div style="{{ $kutu }};text-align:center">
                <div style="font-weight:700">Uyumluluk Skoru</div>
                @php $y = $o['uyum_yuzde']; $renk = $y < 40 ? $kirmizi : ($y < 75 ? $sari : $yesil); @endphp
                <div style="width:9rem;height:9rem;margin:1rem auto;border-radius:9999px;
                    background:conic-gradient({{ $renk }} {{ $y * 3.6 }}deg, rgb(107 114 128 / .2) 0);
                    display:flex;align-items:center;justify-content:center">
                    <div style="width:6.5rem;height:6.5rem;border-radius:9999px;background:var(--fi-color-white,#fff);
                        display:flex;flex-direction:column;align-items:center;justify-content:center;color:#111">
                        <span style="font-size:1.6rem;font-weight:800;color:{{ $renk }}">%{{ $y }}</span>
                        <span style="font-size:.65rem;color:#666">portföy geneli</span>
                    </div>
                </div>
                <div style="font-size:.8rem;color:rgb(107 114 128)">Takip edilen yasal kriterlerin ortalama karşılanma oranı</div>
            </div>

            {{-- Tehlike sınıfı dağılımı --}}
            <div style="{{ $kutu }}">
                <div style="font-weight:700">Tehlike Sınıfı Dağılımı</div>
                <div style="font-size:.8rem;color:rgb(107 114 128);margin-bottom:.6rem">Toplam {{ $o['firma'] }} firma</div>
                @php $renkler = [$yesil, $sari, $kirmizi]; $i = 0; @endphp
                @foreach ($o['tehlike_dagilimi'] as $ad => $sayi)
                    @php $yuzde = $o['firma'] > 0 ? round($sayi / $o['firma'] * 100) : 0; $c = $renkler[$i++ % 3]; @endphp
                    <div style="margin-bottom:.5rem">
                        <div style="display:flex;justify-content:space-between;font-size:.82rem">
                            <span>{{ $ad }}</span><strong>{{ $sayi }} · %{{ $yuzde }}</strong>
                        </div>
                        <div style="height:6px;border-radius:9999px;background:rgb(107 114 128 / .2);margin-top:.25rem;overflow:hidden">
                            <div style="height:100%;width:{{ max($yuzde, 1) }}%;background:{{ $c }}"></div>
                        </div>
                    </div>
                @endforeach
            </div>

            {{-- İlk yardım / bilgi --}}
            <div style="{{ $kutu }}">
                <div style="font-weight:700">İlk Yardım Sertifikası</div>
                <p style="font-size:.82rem;color:rgb(107 114 128);margin-top:.4rem">
                    Aktif firmalarınızın yasal ilkyardımcı bulundurma zorunlulukları
                    (İlk Yardım Yön. m.16 — çok tehlikeli 10, tehlikeli 15, az tehlikeli 20 kişide 1).
                    Çalışan modülü tamamlanınca firma bazlı eksik listesi burada çıkacak.
                </p>
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
                <thead><tr style="text-align:left;color:rgb(107 114 128)">
                    <th style="padding:.4rem">Firma</th><th style="padding:.4rem">SGK Sicil</th>
                    <th style="padding:.4rem">Çalışan</th><th style="padding:.4rem">Tehlike Sınıfı</th>
                </tr></thead>
                <tbody>
                @forelse ($this->firmalar as $f)
                    <tr style="border-top:1px solid rgb(107 114 128 / .15)">
                        <td style="padding:.4rem;font-weight:600">{{ $f->unvan }}</td>
                        <td style="padding:.4rem">{{ $f->sgk_sicil_no ?: '—' }}</td>
                        <td style="padding:.4rem">{{ $f->calisanlar_count ?: $f->calisan_sayisi }}</td>
                        <td style="padding:.4rem"><x-filament::badge>{{ $f->tehlikeSinifiEtiketi() }}</x-filament::badge></td>
                    </tr>
                @empty
                    <tr><td colspan="4" style="padding:1rem;text-align:center;color:rgb(107 114 128)">Henüz firma eklenmemiş.</td></tr>
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
                <div style="text-align:center;padding:2rem;color:rgb(107 114 128)">
                    <div style="font-size:1.75rem">👥</div>Henüz çalışan eklenmemiş.
                </div>
            @else
                <table style="width:100%;border-collapse:collapse;font-size:.83rem">
                    <thead><tr style="text-align:left;color:rgb(107 114 128)">
                        <th style="padding:.4rem">Ad Soyad</th><th style="padding:.4rem">Firma</th>
                        <th style="padding:.4rem">Görev</th><th style="padding:.4rem">Durum</th>
                    </tr></thead>
                    <tbody>
                    @foreach ($this->calisanlar as $c)
                        <tr style="border-top:1px solid rgb(107 114 128 / .15)">
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

    {{-- ==================== FİRMA TAKİP (evrak matrisi) ==================== --}}
    @if ($sekme === 'firma_takip')
        <div style="{{ $kutu }};overflow-x:auto">
            <div style="font-weight:700">Firma × Yasal Kriter Matrisi</div>
            <div style="font-size:.8rem;color:rgb(107 114 128);margin-bottom:.6rem">
                Her firmanın 12 yasal kriteri karşılama durumu. "—" = ilgili modül henüz kurulmadı.
            </div>
            <table style="border-collapse:collapse;font-size:.78rem;white-space:nowrap">
                <thead><tr style="color:rgb(107 114 128)">
                    <th style="padding:.35rem;text-align:left;position:sticky;left:0;background:inherit">Firma</th>
                    @foreach (config('isg.kontrol_merkezi.kriterler') as $k)
                        <th style="padding:.35rem;text-align:center;max-width:5rem;white-space:normal">{{ $k['ad'] }}</th>
                    @endforeach
                    <th style="padding:.35rem;text-align:center">Oran</th>
                </tr></thead>
                <tbody>
                @forelse ($this->firmaMatrisi as $satir)
                    <tr style="border-top:1px solid rgb(107 114 128 / .15)">
                        <td style="padding:.35rem;font-weight:600;position:sticky;left:0;background:inherit">{{ $satir['firma']->unvan }}</td>
                        @foreach (config('isg.kontrol_merkezi.kriterler') as $k)
                            @php $var = $satir['hucreler'][$k['anahtar']]; @endphp
                            <td style="padding:.35rem;text-align:center;color:{{ $k['hazir'] ? ($var ? $yesil : $kirmizi) : 'rgb(107 114 128)' }}">
                                {{ $k['hazir'] ? ($var ? '✓' : '✗') : '—' }}
                            </td>
                        @endforeach
                        <td style="padding:.35rem;text-align:center;font-weight:700">%{{ $satir['oran'] }}</td>
                    </tr>
                @empty
                    <tr><td colspan="14" style="padding:1rem;text-align:center;color:rgb(107 114 128)">Firma yok.</td></tr>
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
                <p style="font-size:.8rem;color:rgb(107 114 128)">Sektöre etiketli risk setleri; sihirbazda tek tıkla uygulanır.</p>
                <x-filament::button size="xs" tag="a" :href="\App\Filament\Resources\RiskSablonus\RiskSablonuResource::getUrl()">Şablonları yönet →</x-filament::button>
            </div>
            <div style="{{ $kutu }}">
                <div style="font-weight:700">Risk Kütüphanesi</div>
                <div style="font-size:1.5rem;font-weight:800;color:{{ $yesil }}">{{ \App\Models\Tehlike::count() }}</div>
                <p style="font-size:.8rem;color:rgb(107 114 128)">Kategoriye göre gruplu tehlike/önlem maddeleri.</p>
                <x-filament::button size="xs" tag="a" :href="\App\Filament\Resources\Tehlikes\TehlikeResource::getUrl()">Kütüphaneye git →</x-filament::button>
            </div>
            <div style="{{ $kutu }}">
                <div style="font-weight:700">Risk Değerlendirmeleri</div>
                <div style="font-size:1.5rem;font-weight:800;color:{{ $sari }}">{{ $o['risk_degerlendirmesi'] }}</div>
                <p style="font-size:.8rem;color:rgb(107 114 128)">Kayıtlı firma risk raporları.</p>
                <x-filament::button size="xs" tag="a" :href="\App\Filament\Pages\RiskSihirbazi::getUrl()">Yeni sihirbaz →</x-filament::button>
            </div>
        </div>
    @endif

    {{-- ==================== DİĞER (bekleyen sekmeler) ==================== --}}
    @if ($sekme === 'diger')
        <div style="{{ $kutu }}">
            <div style="font-weight:700">isgpratik'te olan, sıradaki fazlarda gelecek sekmeler</div>
            <div style="display:flex;flex-direction:column;gap:.5rem;margin-top:.75rem">
                @foreach ($bekleyen as $ad => $not)
                    <div style="border:1px solid rgb(107 114 128 / .2);border-radius:.5rem;padding:.6rem .8rem">
                        <div style="font-weight:600;font-size:.88rem">🚧 {{ $ad }}</div>
                        <div style="font-size:.78rem;color:rgb(107 114 128)">{{ $not }}</div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif
</x-filament-panels::page>
