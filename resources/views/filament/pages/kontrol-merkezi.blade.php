@php
    $sekmeler = \App\Filament\Pages\KontrolMerkezi::SEKMELER;
    $mor = '#2563EB';
    $turkuaz = '#2563EB';
    $yesil = '#059669';
    $kirmizi = '#DC2626';
    $sari = '#D97706';
    $gri = '#64748B';
    $kutu = 'background:rgb(128 116 148 / .03);border:1px solid rgb(128 116 148 / .24);border-radius:12px;padding:1rem;box-shadow:0 1px 2px rgb(15 23 42 / .04)';
@endphp

<x-filament-panels::page>
    {{-- BAŞLIK: sayfa başlığı zaten "İSG Komuta Merkezi" — burada yalnız canlı rozeti (13.10.2026). --}}
    <div style="display:flex;align-items:center;gap:.6rem;flex-wrap:wrap">
        <span style="display:inline-flex;align-items:center;gap:.35rem;background:rgb(34 197 94 / .15);color:{{ $yesil }};font-size:.75rem;font-weight:600;padding:.2rem .6rem;border-radius:9999px">
            <span style="width:.5rem;height:.5rem;border-radius:9999px;background:{{ $yesil }}"></span> Canlı İSG Takibi
        </span>
    </div>
    <p style="font-size:.85rem;color:rgb(95 87 112);margin-top:-.5rem">
        Tüm firmalarınızın yasal belgeleri, yaklaşan işleri ve çalışan eksiklerini tek kontrol merkezinde.
    </p>

    {{-- SEKME PILL'LERİ --}}
    <div style="display:flex;gap:.4rem;flex-wrap:wrap;background:rgb(128 116 148 / .1);padding:.3rem;border-radius:.6rem">
        @foreach ($sekmeler as $anahtar => $ad)
            @php $aktif = $sekme === $anahtar; @endphp
            <button type="button" wire:click="sekmeSec('{{ $anahtar }}')"
                style="flex:1;min-width:120px;padding:.5rem .75rem;border:none;border-radius:.45rem;cursor:pointer;font-weight:600;font-size:.85rem;
                    background:{{ $aktif ? $turkuaz : 'transparent' }};color:{{ $aktif ? '#fff' : 'inherit' }}">
                {{ $ad }}
            </button>
        @endforeach
    </div>

    {{-- FİRMA SEÇ (firma / calisan / hizli_evrak sekmeleri) --}}
    @if (in_array($sekme, ['firma', 'calisan', 'hizli_evrak'], true))
        @include('filament.pages.partials.komuta-firma-cubugu', ['tumPortfoy' => true])
    @endif

    {{-- ==================== GÜNLÜK AKIŞ ==================== --}}
    {{-- 13.11.2026: tüm süreli yasal yükümlülükler (App\Support\YasalTakvim) — süresi geçenler + 90 gün içinde dolanlar. --}}
    @if ($sekme === 'gunluk')
        @php
            $acikBulgular = $this->acikSahaBulgulari;
            $isler = collect($this->yaklasanIsler);
            $turAdlari = ['risk' => 'Risk', 'acil' => 'Acil durum', 'kurul' => 'Kurul', 'egitim' => 'Eğitim', 'ekipman' => 'Periyodik kontrol', 'saglik' => 'Sağlık', 'olcum' => 'Ölçüm', 'kkd' => 'KKD', 'sozlesme' => 'Sözleşme'];
            $gruplar = [
                ['ad' => 'Süresi geçenler', 'renk' => $kirmizi, 'liste' => $isler->where('durum', 'gecikti')],
                ['ad' => 'Önümüzdeki '.\App\Filament\Pages\KontrolMerkezi::TAKVIM_GUN.' gün', 'renk' => $sari, 'liste' => $isler->where('durum', 'yaklasiyor')],
            ];
        @endphp

        @if ($acikBulgular)
            <div style="{{ $kutu }};padding:.75rem .9rem">
                <div style="display:flex;align-items:center;justify-content:space-between;gap:.5rem;margin-bottom:.5rem;flex-wrap:wrap">
                    <div style="display:flex;align-items:center;gap:.45rem">
                        <span style="font-weight:700;font-size:.9rem">⚡ Benim Günüm — Açık Saha Bulguları (Hızlı Aksiyon)</span>
                        <span style="font-size:.73rem;font-weight:700;color:{{ $sari }};background:color-mix(in srgb, {{ $sari }} 15%, transparent);padding:.05rem .45rem;border-radius:9999px">{{ count($acikBulgular) }}</span>
                    </div>
                    <span style="font-size:.74rem;color:{{ $gri }}">Ekrandan ayrılmadan +7 gün erteleyin veya tek tıkla kapatın</span>
                </div>
                <div style="display:flex;flex-direction:column">
                    @foreach ($acikBulgular as $b)
                        <div style="display:flex;align-items:center;justify-content:space-between;gap:.75rem;flex-wrap:wrap;padding:.45rem 0;border-top:1px solid rgb(128 116 148 / .14)">
                            <div style="min-width:220px;flex:1">
                                <div style="font-size:.84rem;font-weight:600">
                                    <span style="font-size:.7rem;color:{{ $b['gecikti'] ? $kirmizi : $gri }};font-weight:700">{{ $b['bulgu_no'] }}</span>
                                    @if (! empty($b['hiyerarsi']))
                                        <span style="font-size:.66rem;font-weight:700;padding:.05rem .4rem;border-radius:999px;color:#fff;background:{{ $b['hiyerarsi_renk'] ?: '#2563eb' }}">{{ $b['hiyerarsi'] }}</span>
                                    @endif
                                    · {{ \Illuminate\Support\Str::limit($b['uygunsuzluk'], 110) }}
                                </div>
                                <div style="font-size:.75rem;color:{{ $gri }}">
                                    {{ $b['firma'] }} · Termin: <strong style="color:{{ $b['gecikti'] ? $kirmizi : 'inherit' }}">{{ $b['termin'] ?: 'Belirtilmedi' }}</strong>
                                    @if ($b['gecikti']) <span style="color:{{ $kirmizi }};font-weight:700">(Gecikti)</span> @endif
                                </div>
                            </div>
                            <div style="display:flex;align-items:center;gap:.35rem;flex-wrap:wrap">
                                <button type="button" wire:click="bulguErtele({{ $b['id'] }}, 7)" wire:loading.attr="disabled"
                                    style="font-size:.74rem;font-weight:600;padding:.26rem .55rem;border-radius:.4rem;border:1px solid rgb(245 158 11 / .45);background:rgb(245 158 11 / .1);color:#b45309;cursor:pointer">
                                    ⏱ +7 Gün Ertele
                                </button>
                                <button type="button" wire:click="bulguTamamla({{ $b['id'] }})" wire:confirm="Bu saha bulgusunu hızlıca 'Giderildi' olarak kapatmak istiyor musunuz? Kanıt/artık risk eklemek için 'Aç →' bağlantısını kullanın." wire:loading.attr="disabled"
                                    style="font-size:.74rem;font-weight:600;padding:.26rem .55rem;border-radius:.4rem;border:1px solid rgb(22 163 74 / .45);background:rgb(22 163 74 / .1);color:#15803d;cursor:pointer">
                                    ✓ Tamamlandı
                                </button>
                                <a href="{{ $b['url'] }}" style="font-size:.76rem;font-weight:600;color:{{ $mor }};text-decoration:none;padding-left:.2rem">Aç →</a>
                            </div>
                        </div>
                    @endforeach
                </div>
                @if (count($acikBulgular) >= 15)
                    <div style="margin-top:.5rem;padding-top:.45rem;border-top:1px solid rgb(128 116 148 / .14);text-align:right">
                        <a href="{{ \App\Filament\Pages\HizliSahaBulgusu::getUrl() }}" style="font-size:.78rem;font-weight:700;color:{{ $mor }};text-decoration:none">Tüm Açık Saha Bulgularını Gör →</a>
                    </div>
                @endif
            </div>
        @endif
        @if ($isler->isEmpty())
            <div style="{{ $kutu }};text-align:center;padding:2.5rem 1rem">
                <div style="font-weight:700">Tüm yasal süreler güncel</div>
                <p style="font-size:.85rem;color:{{ $gri }}">
                    Süresi geçen ya da {{ \App\Filament\Pages\KontrolMerkezi::TAKVIM_GUN }} gün içinde dolan yükümlülük yok.
                </p>
            </div>
        @else
            <p style="font-size:.8rem;color:{{ $gri }};margin:0">
                Risk değerlendirmesi, acil durum planı, tatbikat, kurul, eğitim, periyodik kontrol, sağlık gözetimi, ortam ölçümü, KKD ve İSG-KATİP sözleşme süreleri — tüm aktif firmalar.
            </p>
            @foreach ($gruplar as $grup)
                @continue($grup['liste']->isEmpty())
                <div style="{{ $kutu }};padding:.75rem .9rem">
                    <div style="display:flex;align-items:center;gap:.5rem;margin-bottom:.5rem">
                        <span style="width:.6rem;height:.6rem;border-radius:9999px;background:{{ $grup['renk'] }}"></span>
                        <span style="font-weight:700;font-size:.9rem">{{ $grup['ad'] }}</span>
                        <span style="font-size:.75rem;font-weight:700;color:{{ $grup['renk'] }};background:color-mix(in srgb, {{ $grup['renk'] }} 14%, transparent);padding:.05rem .45rem;border-radius:9999px">{{ $grup['liste']->count() }}</span>
                    </div>
                    <div style="display:flex;flex-direction:column">
                        @foreach ($grup['liste'] as $is)
                            <div style="display:grid;grid-template-columns:minmax(0,1fr) auto;gap:.25rem 1rem;align-items:center;padding:.5rem 0;border-top:1px solid rgb(128 116 148 / .14)">
                                <div style="min-width:0">
                                    <div style="display:flex;flex-wrap:wrap;align-items:center;gap:.4rem">
                                        <span style="font-size:.68rem;font-weight:700;text-transform:uppercase;letter-spacing:.03em;color:{{ $gri }};border:1px solid rgb(128 116 148 / .3);border-radius:.3rem;padding:0 .3rem">{{ $turAdlari[$is['tur']] ?? $is['tur'] }}</span>
                                        <span style="font-weight:600;font-size:.86rem">{{ $is['baslik'] }}</span>
                                        <span style="font-size:.8rem;color:{{ $gri }}">· {{ $is['firma'] }}</span>
                                    </div>
                                    <div style="font-size:.78rem;color:{{ $gri }};margin-top:.15rem">{{ $is['aciklama'] }}</div>
                                </div>
                                <div style="display:flex;align-items:center;gap:.8rem;white-space:nowrap">
                                    <span style="font-size:.8rem;font-weight:600;color:{{ $grup['renk'] }};font-variant-numeric:tabular-nums;text-align:right">
                                        @if ($is['tarih'])
                                            {{ $is['tarih'] }}
                                            <span style="font-weight:400;color:{{ $gri }}">· {{ $is['kalan_gun'] < 0 ? abs($is['kalan_gun']).' gün geçti' : ($is['kalan_gun'] === 0 ? 'bugün' : $is['kalan_gun'].' gün kaldı') }}</span>
                                        @else
                                            Kayıt yok
                                        @endif
                                    </span>
                                    @if ($is['url'])
                                        <a href="{{ $is['url'] }}" style="font-size:.8rem;font-weight:600;color:{{ $mor }}">Aç →</a>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endforeach
        @endif
    @endif

    {{-- ==================== FİRMA ASİSTANI ==================== --}}
    @if ($sekme === 'firma')
        @php
            $o = $this->ozet;
            $ozetKartlari = [
                ['ad' => 'Toplam Firma', 'deger' => $o['firma'], 'ikon' => 'heroicon-o-building-office-2', 'renk' => $gri],
                ['ad' => 'Eksik Evrakı Olan Firma', 'deger' => $o['evrak_eksigi'], 'ikon' => 'heroicon-o-document-text', 'renk' => $kirmizi],
                ['ad' => 'Çalışan Kaydı Eksik Firma', 'deger' => $o['calisan_uyarili'], 'ikon' => 'heroicon-o-exclamation-triangle', 'renk' => $sari],
                ['ad' => 'Tam Uyumlu Firma', 'deger' => $o['tam_uyumlu'], 'ikon' => 'heroicon-o-check-circle', 'renk' => $yesil],
            ];
            $uyumRengi = $o['uyum_yuzde'] < 40 ? $kirmizi : ($o['uyum_yuzde'] < 75 ? $sari : $yesil);
        @endphp
        {{-- Seçili Firma Kapsam Bilgisi --}}
        @if ($this->secilenFirma)
            <div style="{{ $kutu }};border-color:rgb(37 99 235 / .35);background:rgb(37 99 235 / .05);display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:.75rem">
                <div style="font-size:.83rem">
                    <strong>Seçili Firma: {{ $this->secilenFirma->unvan }}</strong>
                    <span style="color:{{ $gri }}"> · Aşağıdaki portföy özeti tüm firmalarınızı gösterir; bu firmanın tekil evrak durumu için Hızlı Evrak Matrisi'ni açabilirsiniz.</span>
                </div>
                <div style="display:flex;gap:.5rem;flex-wrap:wrap">
                    <button type="button" wire:click="sekmeSec('hizli_evrak')" style="font-size:.78rem;font-weight:700;padding:.32rem .65rem;border-radius:.45rem;border:1px solid rgb(37 99 235 / .4);background:#2563EB;color:#fff;cursor:pointer">⚡ Firma Evrak Matrisini Aç</button>
                    <button type="button" wire:click="sekmeSec('calisan')" style="font-size:.78rem;font-weight:700;padding:.32rem .65rem;border-radius:.45rem;border:1px solid rgb(128 116 148 / .35);background:transparent;color:inherit;cursor:pointer">👷 Çalışan Asistanı</button>
                </div>
            </div>
        @endif
        {{-- İSG Portföy Özeti --}}
        <div style="{{ $kutu }}">
            <div style="display:flex;align-items:center;justify-content:space-between;gap:1rem;flex-wrap:wrap">
                <div>
                    <div style="font-weight:700">İSG Portföy Özeti</div>
                    <div style="font-size:.8rem;color:{{ $gri }}">{{ $o['firma'] }} Firma · {{ $o['calisan'] }} kayıtlı çalışan{{ $o['calisan_bildirilen'] !== $o['calisan'] ? ' (bildirilen '.$o['calisan_bildirilen'].')' : '' }} — anlık yasal uyum tablosu</div>
                </div>
                {{-- Uyum yüzdesi — dolgu halkası (conic-gradient + mask, tema-bağımsız) --}}
                <div style="display:flex;align-items:center;gap:.7rem">
                    <div style="position:relative;width:3.4rem;height:3.4rem;flex-shrink:0">
                        <div style="width:100%;height:100%;border-radius:9999px;
                                background:conic-gradient({{ $uyumRengi }} {{ $o['uyum_yuzde'] }}%, rgb(128 116 148 / .18) 0);
                                -webkit-mask:radial-gradient(farthest-side, transparent calc(100% - 7px), #000 calc(100% - 7px));
                                mask:radial-gradient(farthest-side, transparent calc(100% - 7px), #000 calc(100% - 7px))"></div>
                        <div style="position:absolute;inset:0;display:flex;align-items:center;justify-content:center;font-size:.75rem;font-weight:800;color:{{ $uyumRengi }}">
                            %{{ $o['uyum_yuzde'] }}
                        </div>
                    </div>
                    <div style="text-align:right">
                        <div style="font-size:1.3rem;font-weight:800;color:{{ $uyumRengi }}">%{{ $o['uyum_yuzde'] }}</div>
                        <div style="font-size:.75rem;color:{{ $gri }}">{{ $o['tam_uyumlu'] }} firma tam uyumlu</div>
                    </div>
                </div>
            </div>

            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:.6rem;margin-top:1rem">
                @foreach ($ozetKartlari as $kart)
                    <div style="border-radius:.65rem;padding:.85rem;background:color-mix(in srgb, {{ $kart['renk'] }} 14%, transparent)">
                        <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:.5rem">
                            <span style="font-size:.75rem;font-weight:700;letter-spacing:.03em;text-transform:uppercase;color:{{ $gri }}">{{ $kart['ad'] }}</span>
                            <span style="width:1.8rem;height:1.8rem;border-radius:.5rem;background:rgb(128 116 148 / .12);
                                    display:flex;align-items:center;justify-content:center;flex-shrink:0">
                                <x-filament::icon :icon="$kart['ikon']" style="width:1rem;height:1rem;color:{{ $kart['renk'] }}"/>
                            </span>
                        </div>
                        <div style="font-size:1.55rem;font-weight:800;color:{{ $kart['renk'] }};margin-top:.35rem">{{ $kart['deger'] }}</div>
                    </div>
                @endforeach
            </div>

            {{-- Çalışan kaydı eksikleri (13.11.2026): eğitim / muayene / temsilci takibi çalışan listesiyle hesaplanır. --}}
            @if ($this->calisanEksikleri)
                <details style="margin-top:.9rem;border:1px solid rgb(128 116 148 / .25);border-radius:.6rem;padding:.6rem .8rem">
                    <summary style="cursor:pointer;font-size:.85rem;font-weight:700">
                        Çalışan kaydı eksik firmalar ({{ count($this->calisanEksikleri) }})
                        <span style="font-weight:400;color:{{ $gri }}"> — eğitim, sağlık gözetimi ve temsilci takibi bu listeyle yapılır; Çalışanlar sayfasından Excel ile toplu eklenebilir.</span>
                    </summary>
                    <div style="overflow-x:auto;margin-top:.6rem">
                        <table style="width:100%;border-collapse:collapse;font-size:.82rem">
                            <thead>
                                <tr style="text-align:left;color:{{ $gri }};border-bottom:1px solid rgb(128 116 148 / .25)">
                                    <th style="padding:.35rem .5rem">Firma</th>
                                    <th style="padding:.35rem .5rem;text-align:right">Kayıtlı / Bildirilen</th>
                                    <th style="padding:.35rem .5rem;text-align:right">TC boş</th>
                                    <th style="padding:.35rem .5rem;text-align:right">İşe giriş boş</th>
                                    <th style="padding:.35rem .5rem"></th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($this->calisanEksikleri as $s)
                                    <tr style="border-bottom:1px solid rgb(128 116 148 / .12)">
                                        <td style="padding:.35rem .5rem">{{ $s['unvan'] }}</td>
                                        <td style="padding:.35rem .5rem;text-align:right;font-variant-numeric:tabular-nums;{{ $s['kayitli'] < $s['bildirilen'] ? 'color:'.$kirmizi.';font-weight:700' : '' }}">{{ $s['kayitli'] }} / {{ $s['bildirilen'] }}</td>
                                        <td style="padding:.35rem .5rem;text-align:right;font-variant-numeric:tabular-nums">{{ $s['tc_bos'] ?: '—' }}</td>
                                        <td style="padding:.35rem .5rem;text-align:right;font-variant-numeric:tabular-nums">{{ $s['giris_bos'] ?: '—' }}</td>
                                        <td style="padding:.35rem .5rem;text-align:right"><a href="{{ $s['url'] }}" style="font-weight:600;color:{{ $mor }}">Çalışanlar →</a></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </details>
            @endif

            {{-- İlk Yardımcı & Acil Durum Destek Elemanı Yasal Kontenjan Hesaplayıcı --}}
            @php
                $kontenjanlar = $this->ekipKontenjanTablosu;
                $eksikKontenjanFirmaSayisi = collect($kontenjanlar)->filter(fn ($r) => $r['ilkyardim_eksik'] > 0 || $r['destek_eksik'] > 0)->count();
            @endphp
            @if ($kontenjanlar)
                <details style="margin-top:.75rem;border:1px solid rgb(128 116 148 / .25);border-radius:.6rem;padding:.6rem .8rem">
                    <summary style="cursor:pointer;font-size:.85rem;font-weight:700">
                        🚑 İlk Yardımcı & Acil Durum Destek Elemanı Yasal Kontenjan Hesaplayıcı ({{ $eksikKontenjanFirmaSayisi }} firmada kontenjan açığı)
                        <span style="font-weight:400;color:{{ $gri }}"> — İlk Yardım Yönetmeliği m.19 (10/15/20 çalışana 1) ve Acil Durumlar Yönetmeliği m.11 (30/40/50 çalışana 1×3 ekip)</span>
                    </summary>
                    <div style="overflow-x:auto;margin-top:.6rem">
                        <table style="width:100%;border-collapse:collapse;font-size:.81rem">
                            <thead>
                                <tr style="text-align:left;color:{{ $gri }};border-bottom:1px solid rgb(128 116 148 / .25)">
                                    <th style="padding:.35rem .5rem">Firma</th>
                                    <th style="padding:.35rem .5rem">Tehlike Sınıfı</th>
                                    <th style="padding:.35rem .5rem;text-align:right">Çalışan</th>
                                    <th style="padding:.35rem .5rem;text-align:center">İlk Yardımcı (Atanan / Yasal)</th>
                                    <th style="padding:.35rem .5rem;text-align:center">Söndürme·Kurtarma·Koruma (Atanan / Yasal)</th>
                                    <th style="padding:.35rem .5rem;text-align:right">İşlem</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($kontenjanlar as $kr)
                                    <tr style="border-bottom:1px solid rgb(128 116 148 / .12)">
                                        <td style="padding:.35rem .5rem;font-weight:600">{{ $kr['unvan'] }}</td>
                                        <td style="padding:.35rem .5rem">{{ $kr['tehlike_etiketi'] }}</td>
                                        <td style="padding:.35rem .5rem;text-align:right;font-variant-numeric:tabular-nums">{{ $kr['calisan'] }}</td>
                                        <td style="padding:.35rem .5rem;text-align:center;font-variant-numeric:tabular-nums;{{ ($kr['ilkyardim_belge_eksik'] ?? $kr['ilkyardim_eksik']) > 0 ? 'color:'.$kirmizi.';font-weight:700' : 'color:'.$yesil }}">
                                            {{ $kr['ilkyardim_gecerli_belge'] ?? $kr['ilkyardim_atanan'] }} / {{ $kr['ilkyardim_gereken'] }}
                                            @if (($kr['ilkyardim_atanan'] ?? 0) > ($kr['ilkyardim_gecerli_belge'] ?? $kr['ilkyardim_atanan']))
                                                <span style="font-size:.7rem;color:{{ $sari }}" title="Atanan ilk yardımcıların bir kısmının sertifika süresi dolmuş">(atanan: {{ $kr['ilkyardim_atanan'] }})</span>
                                            @endif
                                            @if (($kr['ilkyardim_belge_eksik'] ?? $kr['ilkyardim_eksik']) > 0)
                                                <span>({{ $kr['ilkyardim_belge_eksik'] ?? $kr['ilkyardim_eksik'] }} eksik)</span>
                                            @else
                                                <span>✓</span>
                                            @endif
                                        </td>
                                        <td style="padding:.35rem .5rem;text-align:center;font-variant-numeric:tabular-nums;{{ $kr['destek_eksik'] > 0 ? 'color:'.$sari.';font-weight:700' : 'color:'.$yesil }}">
                                            {{ $kr['destek_atanan'] }} / {{ $kr['destek_toplam_gereken'] }}
                                            @if ($kr['destek_eksik'] > 0)
                                                <span>({{ $kr['destek_eksik'] }} eksik)</span>
                                            @else
                                                <span>✓</span>
                                            @endif
                                        </td>
                                        <td style="padding:.35rem .5rem;text-align:right;white-space:nowrap">
                                            <a href="{{ $kr['url'] }}" style="font-weight:600;color:{{ $mor }}">Ekipler →</a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </details>
            @endif
        </div>

        {{-- Yasal Kriterlerin Portföy Tamamlanma Oranları --}}
        <div style="{{ $kutu }}">
            <div style="font-weight:700">Yasal Kriterlerin Portföy Tamamlanma Oranları</div>
            <div style="font-size:.8rem;color:{{ $gri }};margin-bottom:.75rem">
                Takip edilen {{ count($this->kriterler) }} kriterin firmalarınızdaki karşılanma yüzdeleri · {{ $o['firma'] }} firma tarandı
            </div>
            <div style="font-size:.75rem;color:{{ $gri }};margin-bottom:.5rem">
                Eksiği olan bir kritere tıklayın — hangi firmalarda eksik olduğu listelenir.
            </div>
            {{-- 3 mantıksal grup: Temel Yasal Belgeler / Saha & Kurul Süreçleri / Sağlık & Takip (App\Support\PortfoyKarne::grupla) --}}
            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(min(100%,330px),1fr));gap:.75rem;align-items:start">
                @foreach ($this->kriterGruplari as $grup)
                    @php $gRenk = $grup['kapsam'] === 0 ? $gri : ($grup['yuzde'] < 40 ? $kirmizi : ($grup['yuzde'] < 75 ? $sari : $yesil)); @endphp
                    <div style="border:1px solid rgb(128 116 148 / .25);border-radius:.6rem;padding:.7rem .8rem">
                        <div style="display:flex;align-items:center;justify-content:space-between;gap:.5rem;margin-bottom:.5rem">
                            <span style="display:flex;align-items:center;gap:.45rem;font-size:.88rem;font-weight:700">
                                <x-filament::icon :icon="$grup['ikon']" style="width:1.05rem;height:1.05rem;color:{{ $gRenk }}"/>
                                {{ $grup['ad'] }}
                            </span>
                            <span style="font-size:.75rem;font-weight:700;padding:.12rem .55rem;border-radius:9999px;color:{{ $gRenk }};background:color-mix(in srgb, {{ $gRenk }} 14%, transparent)">
                                %{{ $grup['yuzde'] }}
                            </span>
                        </div>
                        <div style="height:5px;border-radius:9999px;background:rgb(128 116 148 / .2);margin-bottom:.55rem;overflow:hidden">
                            <div style="height:100%;width:{{ max($grup['yuzde'], 1) }}%;background:{{ $gRenk }}"></div>
                        </div>

                        <div style="display:flex;flex-direction:column">
                            @foreach ($grup['kriterler'] as $k)
                                @php
                                    $kRenk = $k['takip'] ? $gri : ($k['yuzde'] < 40 ? $kirmizi : ($k['yuzde'] < 75 ? $sari : $yesil));
                                    $eksikSayi = max($k['toplam'] - $k['tamam'], 0);
                                    $tiklanir = $k['hazir'] && ! $k['takip'] && $eksikSayi > 0;
                                    $acik = $acikKriter === $k['anahtar'];
                                @endphp
                                <div style="border-top:1px solid rgb(128 116 148 / .15);padding:.4rem 0">
                                    <div @if ($tiklanir) wire:click="kriterDetayAc('{{ $k['anahtar'] }}')" wire:keydown.enter="kriterDetayAc('{{ $k['anahtar'] }}')" wire:keydown.space.prevent="kriterDetayAc('{{ $k['anahtar'] }}')" role="button" tabindex="0" @endif
                                        style="display:flex;align-items:center;justify-content:space-between;gap:.5rem;{{ $tiklanir ? 'cursor:pointer' : '' }}">
                                        <span style="display:flex;align-items:center;gap:.4rem;font-size:.78rem;font-weight:600;min-width:0">
                                            <x-filament::icon :icon="$k['ikon']" style="width:.85rem;height:.85rem;flex-shrink:0;color:{{ $kRenk }}"/>
                                            <span style="overflow:hidden;text-overflow:ellipsis" title="{{ $k['ad'] }}">{{ $k['ad'] }}</span>
                                            @unless ($k['hazir']) <span style="font-size:.68rem;color:{{ $gri }};white-space:nowrap">(yakında)</span> @endunless
                                            @if ($k['takip']) <span style="font-size:.68rem;color:{{ $gri }};white-space:nowrap" title="Olay bazlı kayıt — eksik sayılmaz, uyum yüzdesine girmez">(yalnız takip)</span> @endif
                                        </span>
                                        <span style="display:flex;align-items:center;gap:.3rem;white-space:nowrap">
                                            <span style="font-size:.75rem;font-weight:700;padding:.08rem .45rem;border-radius:9999px;color:{{ $kRenk }};background:color-mix(in srgb, {{ $kRenk }} 14%, transparent)">%{{ $k['yuzde'] }}</span>
                                            @if ($k['hazir'] && ! $k['takip'] && $eksikSayi > 0)
                                                <span style="font-size:.68rem;font-weight:700;padding:.08rem .45rem;border-radius:9999px;color:{{ $kirmizi }};background:color-mix(in srgb, {{ $kirmizi }} 12%, transparent)" title="Bu kriteri karşılamayan firma sayısı">{{ $eksikSayi }} eksik</span>
                                            @elseif ($k['hazir'] && ! $k['takip'])
                                                <span style="font-size:.68rem;color:{{ $yesil }}">✓</span>
                                            @endif
                                        </span>
                                    </div>

                                    @if ($acik)
                                        @php $eksikFirmalar = $this->acikKriterEksikFirmalar; @endphp
                                        <div style="margin-top:.5rem;padding-top:.45rem;border-top:1px dashed rgb(128 116 148 / .3)" onclick="event.stopPropagation()">
                                            <div style="display:flex;align-items:center;justify-content:space-between;gap:.5rem;flex-wrap:wrap;margin-bottom:.35rem">
                                                <div style="font-size:.75rem;font-weight:700;color:{{ $kirmizi }}">
                                                    Bu kriterin eksik olduğu {{ count($eksikFirmalar) }} firma (tıklayınca ilgili modülde açılır):
                                                </div>
                                                @if ($eksikFirmalar)
                                                    @php $ilkFirmaId = array_key_first($eksikFirmalar); @endphp
                                                    <a href="{{ \App\Support\PortfoyKarne::kriterSayfaUrl($k['anahtar'], $ilkFirmaId) }}"
                                                        style="font-size:.73rem;font-weight:700;color:{{ $mor }};text-decoration:none">
                                                        İlk Eksik Firmadan Başla →
                                                    </a>
                                                @endif
                                            </div>
                                            <div style="display:flex;flex-wrap:wrap;gap:.3rem">
                                                @forelse ($eksikFirmalar as $fId => $fUnvan)
                                                    <a href="{{ \App\Support\PortfoyKarne::kriterSayfaUrl($k['anahtar'], $fId) }}"
                                                        style="display:inline-block;padding:.12rem .5rem;border-radius:9999px;font-size:.75rem;
                                                            border:1px solid {{ $kirmizi }};color:{{ $kirmizi }};text-decoration:none">
                                                        {{ $fUnvan }} →
                                                    </a>
                                                @empty
                                                    <span style="font-size:.75rem;color:{{ $gri }}">Eksik firma yok.</span>
                                                @endforelse
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:1rem">
            {{-- Yenileme Süreleri --}}
            <div style="{{ $kutu }}">
                <div style="font-weight:700">Yasal Evrak Yenileme Süreleri</div>
                <div style="font-size:.78rem;color:rgb(95 87 112);margin-bottom:.6rem">6331 Sayılı İSG Kanunu uyarınca zorunlu periyotlar</div>
                @foreach (config('isg.tehlike_siniflari') as $anahtar => $ad)
                    <div style="display:flex;justify-content:space-between;padding:.35rem 0;border-top:1px solid rgb(128 116 148 / .15);font-size:.85rem">
                        <span>{{ $ad }} Sınıf</span>
                        <strong>{{ config('isg.risk_gecerlilik_yili.'.$anahtar) }} Yılda Bir</strong>
                    </div>
                @endforeach
            </div>

            {{-- Uzman Tavsiyeleri --}}
            <div style="{{ $kutu }}">
                <div style="font-weight:700">Uzman Tavsiyeleri & Aksiyonlar</div>
                <div style="font-size:.78rem;color:rgb(95 87 112);margin-bottom:.6rem">Denetim öncesi dikkat edilmesi gereken noktalar</div>
                <ul style="margin:0;padding-left:1.1rem;font-size:.82rem;display:flex;flex-direction:column;gap:.4rem">
                    @foreach (config('isg.kontrol_merkezi.uzman_tavsiyeleri') as $t)
                        <li><strong>{{ $t['baslik'] }}:</strong> {{ $t['metin'] }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    @endif

    {{-- ==================== ÇALIŞAN ASİSTANI ==================== --}}
    @if ($sekme === 'calisan')
        @if (! $this->secilenFirma)
            <div style="{{ $kutu }};text-align:center;padding:2.5rem 1rem">
                <div style="font-size:2rem">👥</div>
                <div style="font-weight:700;margin-top:.4rem">Çalışan İSG Asistanı</div>
                <p style="font-size:.85rem;color:rgb(95 87 112)">
                    Muayene, İSG eğitimi, MYK ve genç çalışan eksiklerini listelemek için yukarıdan bir firma seçin.
                </p>
            </div>
        @else
            @php $k = $this->calisanKarne; @endphp
            <div style="{{ $kutu }}">
                <div style="font-weight:700">{{ $this->secilenFirma->unvan }}</div>
                <div style="font-size:.82rem;color:rgb(95 87 112)">{{ $k['toplam'] }} aktif çalışan</div>
                <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:.6rem;margin-top:.75rem">
                    <div style="border:1px solid rgb(128 116 148 / .25);border-radius:.5rem;padding:.6rem">
                        <div style="font-size:.75rem;color:rgb(95 87 112);text-transform:uppercase">Genç Çalışan (&lt;18)</div>
                        <div style="font-size:1.3rem;font-weight:800;color:{{ $k['genc']->count() ? $sari : 'inherit' }}">{{ $k['genc']->count() }}</div>
                    </div>
                    <div style="border:1px solid rgb(128 116 148 / .25);border-radius:.5rem;padding:.6rem">
                        <div style="font-size:.75rem;color:rgb(95 87 112);text-transform:uppercase">Mesleki Eğitim Gerektiren (m.17/3)</div>
                        <div style="font-size:1.3rem;font-weight:800">{{ $k['agir_tehlikeli'] }}</div>
                    </div>
                    <div style="border:1px solid rgb(128 116 148 / .25);border-radius:.5rem;padding:.6rem">
                        <div style="font-size:.75rem;color:rgb(95 87 112);text-transform:uppercase">İSG Eğitimi Eksik</div>
                        <div style="font-size:1.3rem;font-weight:800;color:{{ ($k['egitim_eksik'] ?? collect())->count() ? $kirmizi : $yesil }}">{{ ($k['egitim_eksik'] ?? collect())->count() }}</div>
                    </div>
                    <div style="border:1px solid rgb(128 116 148 / .25);border-radius:.5rem;padding:.6rem">
                        <div style="font-size:.75rem;color:rgb(95 87 112);text-transform:uppercase">Sağlık Muayenesi (EK-2) Eksik</div>
                        <div style="font-size:1.3rem;font-weight:800;color:{{ ($k['muayene_eksik'] ?? collect())->count() ? $kirmizi : $yesil }}">{{ ($k['muayene_eksik'] ?? collect())->count() }}</div>
                    </div>
                    <div style="border:1px solid rgb(128 116 148 / .25);border-radius:.5rem;padding:.6rem">
                        <div style="font-size:.75rem;color:rgb(95 87 112);text-transform:uppercase">T.C. Kimlik No Boş</div>
                        <div style="font-size:1.3rem;font-weight:800;color:{{ ($k['tc_eksik'] ?? collect())->count() ? $sari : $yesil }}">{{ ($k['tc_eksik'] ?? collect())->count() }}</div>
                    </div>
                </div>
            </div>

            @if (($k['egitim_eksik'] ?? collect())->isNotEmpty())
                <div style="{{ $kutu }}">
                    <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:.5rem">
                        <div style="font-weight:700;color:{{ $kirmizi }}">🎓 Geçerli İSG Eğitimi Olmayan Çalışanlar ({{ $k['egitim_eksik']->count() }})@if (count($k['egitim_suresi_dolan'] ?? [])) <span style="font-weight:500;font-size:.8rem">· {{ count($k['egitim_suresi_dolan']) }} kişinin süresi dolmuş</span>@endif</div>
                        <a href="{{ \App\Filament\Pages\EgitimKatilim::getUrl(['firma' => $this->secilenFirma->id]) }}" style="font-size:.82rem;font-weight:700;color:{{ $mor }}">Eğitim Katılım Formu Oluştur →</a>
                    </div>
                    <div style="display:flex;flex-wrap:wrap;gap:.35rem;margin-top:.5rem">
                        @foreach ($k['egitim_eksik']->take(40) as $c)
                            <span style="font-size:.78rem;padding:.15rem .55rem;border-radius:999px;border:1px solid rgb(239 68 68 / .35);background:rgb(239 68 68 / .06)">
                                {{ $c->ad_soyad }}{{ $c->gorev ? ' ('.$c->gorev.')' : '' }}@if (in_array($c->id, $k['egitim_suresi_dolan'] ?? [], true)) · <strong>süresi doldu</strong>@endif
                            </span>
                        @endforeach
                        @if ($k['egitim_eksik']->count() > 40)
                            <a href="{{ \App\Filament\Pages\EgitimYenilemeTakibi::getUrl(['firma' => $this->secilenFirma->id]) }}" style="font-size:.78rem;font-weight:700;padding:.15rem .55rem;color:{{ $mor }}">
                                +{{ $k['egitim_eksik']->count() - 40 }} kişi daha (Tümünü Gör →)
                            </a>
                        @endif
                    </div>
                </div>
            @endif

            @if (($k['muayene_eksik'] ?? collect())->isNotEmpty())
                <div style="{{ $kutu }}">
                    <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:.5rem">
                        <div style="font-weight:700;color:{{ $kirmizi }}">🩺 Geçerli Sağlık Muayenesi (EK-2 / Gözetim) Olmayan Çalışanlar ({{ $k['muayene_eksik']->count() }})@if (count($k['muayene_suresi_dolan'] ?? [])) <span style="font-weight:500;font-size:.8rem">· {{ count($k['muayene_suresi_dolan']) }} kişinin süresi dolmuş</span>@endif</div>
                        <a href="{{ \App\Filament\Pages\SaglikGozetimi::getUrl(['firma' => $this->secilenFirma->id]) }}" style="font-size:.82rem;font-weight:700;color:{{ $mor }}">Sağlık Gözetimine Git →</a>
                    </div>
                    <div style="display:flex;flex-wrap:wrap;gap:.35rem;margin-top:.5rem">
                        @foreach ($k['muayene_eksik']->take(40) as $c)
                            <a href="{{ \App\Filament\Pages\MuayeneFormu::getUrl(['firma' => $this->secilenFirma->id, 'calisan' => $c->id]) }}"
                                style="font-size:.78rem;padding:.15rem .55rem;border-radius:999px;border:1px solid rgb(20 184 166 / .45);background:rgb(20 184 166 / .08);text-decoration:none;color:inherit">
                                {{ $c->ad_soyad }}@if (in_array($c->id, $k['muayene_suresi_dolan'] ?? [], true)) · süresi doldu @endif · EK-2 Hazırla →
                            </a>
                        @endforeach
                        @if ($k['muayene_eksik']->count() > 40)
                            <a href="{{ \App\Filament\Pages\SaglikGozetimi::getUrl(['firma' => $this->secilenFirma->id]) }}" style="font-size:.78rem;font-weight:700;padding:.15rem .55rem;color:{{ $mor }}">
                                +{{ $k['muayene_eksik']->count() - 40 }} kişi daha (Tümünü Gör →)
                            </a>
                        @endif
                    </div>
                </div>
            @endif

            @if (($k['tc_eksik'] ?? collect())->isNotEmpty())
                <details style="{{ $kutu }}">
                    <summary style="cursor:pointer;font-weight:700;color:{{ $sari }}">🪪 T.C. Kimlik No Eksik Çalışanlar ({{ $k['tc_eksik']->count() }})</summary>
                    <div style="display:flex;flex-wrap:wrap;gap:.35rem;margin-top:.5rem">
                        @foreach ($k['tc_eksik']->take(40) as $c)
                            <span style="font-size:.78rem;padding:.15rem .55rem;border-radius:999px;border:1px solid rgb(245 158 11 / .4);background:rgb(245 158 11 / .08)">{{ $c->ad_soyad }}</span>
                        @endforeach
                        @if ($k['tc_eksik']->count() > 40)
                            <span style="font-size:.78rem;color:{{ $gri }}">+{{ $k['tc_eksik']->count() - 40 }} kişi daha</span>
                        @endif
                    </div>
                </details>
            @endif

            @if ($k['genc']->isNotEmpty())
                <div style="{{ $kutu }}">
                    <div style="font-weight:700;color:{{ $sari }}">⚠ Genç Çalışanlar — özel koruma gerektirir</div>
                    <ul style="margin:.4rem 0 0;padding-left:1.1rem;font-size:.85rem">
                        @foreach ($k['genc'] as $c)
                            <li>{{ $c->ad_soyad }} — {{ $c->gorev ?: 'görev belirtilmemiş' }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
        @endif
    @endif

    {{-- ==================== HIZLI EVRAK MATRİSİ ==================== --}}
    @if ($sekme === 'hizli_evrak')
        @if (! $this->secilenFirma)
            <div style="{{ $kutu }};text-align:center;padding:2.5rem 1rem">
                <div style="font-size:2rem">⚡</div>
                <div style="font-weight:700;margin-top:.4rem">Hızlı Evrak Matrisi (Firma & Çalışan Evrakları)</div>
                <p style="font-size:.85rem;color:rgb(95 87 112)">
                    Tek ekranda bir firmanın tüm zorunlu İSG firma evraklarını ve çalışan dosyası evraklarını görmek, eksikleri tek tıkla hazırlamak veya toplu ZIP indirmek için yukarıdan bir firma seçin.
                </p>
            </div>
        @else
            @php
                $matris = $this->hizliEvrakMatrisi;
                $mOzet = $matris['ozet'];
                $mRenk = $mOzet['oran'] < 40 ? $kirmizi : ($mOzet['oran'] < 75 ? $sari : $yesil);
            @endphp
            <div style="{{ $kutu }}">
                <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:.75rem">
                    <div>
                        <div style="font-weight:700;font-size:1rem">{{ $this->secilenFirma->unvan }} — Hızlı Evrak Matrisi</div>
                        <div style="font-size:.8rem;color:{{ $gri }}">
                            {{ $this->secilenFirma->tehlikeSinifiEtiketi() }} · {{ $this->secilenFirma->calisan_sayisi }} Çalışan ·
                            <strong style="color:{{ $yesil }}">{{ $mOzet['tamam'] }} Tamam</strong> ·
                            <strong style="color:{{ $mOzet['eksik'] > 0 ? $kirmizi : $gri }}">{{ $mOzet['eksik'] }} Eksik</strong>
                            @if ($mOzet['muaf'] > 0)
                                · <span>{{ $mOzet['muaf'] }} Kapsam Dışı (Muaf)</span>
                            @endif
                        </div>
                    </div>
                    <div style="display:flex;align-items:center;gap:.55rem;flex-wrap:wrap">
                        <span style="font-size:.88rem;font-weight:800;padding:.25rem .7rem;border-radius:999px;color:{{ $mRenk }};background:color-mix(in srgb, {{ $mRenk }} 15%, transparent)">
                            Uyum: %{{ $mOzet['oran'] }}
                        </span>
                        <x-filament::button size="sm" color="gray" icon="heroicon-o-document-chart-bar" wire:click="isverenKarnesiPdf">
                            İşveren Karnesi PDF
                        </x-filament::button>
                        @if ($paylasim = $this->isverenKarnePaylasim)
                            <span x-data="{ kopyalandi: false }" style="display:inline-flex;align-items:center;gap:.4rem;flex-wrap:wrap">
                                <button type="button"
                                    x-on:click="navigator.clipboard?.writeText(@js($paylasim['link'])).then(() => { kopyalandi = true; setTimeout(() => kopyalandi = false, 2500); })"
                                    style="font-size:.78rem;font-weight:600;padding:.38rem .7rem;border-radius:.5rem;border:1px solid rgb(107 114 128 / .35);background:transparent;color:inherit;cursor:pointer"
                                    title="İşveren için giriş gerektirmeyen 30 günlük salt-okunur PDF bağlantısını kopyala">
                                    <span x-text="kopyalandi ? '✓ Bağlantı Kopyalandı (30 Gün)' : '🔗 İşveren Linki Kopyala (30 Gün)'">🔗 İşveren Linki Kopyala (30 Gün)</span>
                                </button>
                                @if ($paylasim['whatsapp'])
                                    <a href="{{ $paylasim['whatsapp'] }}" target="_blank" rel="noopener"
                                        style="font-size:.78rem;font-weight:600;padding:.38rem .7rem;border-radius:.5rem;border:1px solid rgb(22 163 74 / .45);background:rgb(22 163 74 / .1);color:#15803d;text-decoration:none">
                                        💬 WhatsApp ile Gönder
                                    </a>
                                @endif
                            </span>
                        @endif
                        <x-filament::button size="sm" color="primary" icon="heroicon-o-archive-box-arrow-down" wire:click="mountAction('hizliEvrakPaketi')">
                            Seçili Firmanın Evraklarını ZIP İndir
                        </x-filament::button>
                    </div>
                </div>
            </div>

            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(min(100%,440px),1fr));gap:1rem;align-items:start">
                {{-- SOL: FİRMA YASAL EVRAKLARI --}}
                <div style="{{ $kutu }}">
                    <div style="font-weight:700;font-size:.95rem;margin-bottom:.2rem">🏢 Firma Yasal İSG Evrakları</div>
                    <div style="font-size:.78rem;color:{{ $gri }};margin-bottom:.75rem">
                        Firmanın yasal zorunlu dosyaları — eksik evraklara tıklayarak doğrudan ilgili modülde oluşturabilirsiniz.
                    </div>

                    <div style="display:flex;flex-direction:column">
                        @foreach ($matris['firma_evraklari'] as $evrak)
                            @php
                                $dRenk = match (true) {
                                    $evrak['muaf'] => $gri,
                                    $evrak['tamam'] => $yesil,
                                    $evrak['takip'] => $gri,
                                    $evrak['durum'] === 'yakin' => $sari,
                                    default => $kirmizi,
                                };
                                $dEtiket = match (true) {
                                    $evrak['muaf'] => 'Muaf',
                                    $evrak['tamam'] => 'Hazır ✓',
                                    $evrak['takip'] => 'Olay Bazlı',
                                    $evrak['durum'] === 'yakin' => 'Vadesi Yakın',
                                    default => 'Eksik',
                                };
                            @endphp
                            <div style="display:flex;align-items:center;justify-content:space-between;gap:.6rem;padding:.45rem 0;border-top:1px solid rgb(128 116 148 / .14)">
                                <div style="min-width:0">
                                    <div style="font-size:.83rem;font-weight:600;display:flex;align-items:center;gap:.4rem;flex-wrap:wrap">
                                        <span>{{ $evrak['ad'] }}</span>
                                        <span style="font-size:.68rem;font-weight:700;padding:.05rem .45rem;border-radius:999px;color:{{ $dRenk }};background:color-mix(in srgb, {{ $dRenk }} 14%, transparent)">
                                            {{ $dEtiket }}
                                        </span>
                                    </div>
                                    @if ($evrak['vade_tarihi'])
                                        <div style="font-size:.73rem;color:{{ $gri }}">Vade / Geçerlilik: {{ $evrak['vade_tarihi']->format('d.m.Y') }}</div>
                                    @endif
                                </div>
                                <a href="{{ $evrak['url'] }}"
                                    style="font-size:.78rem;font-weight:700;color:{{ $evrak['tamam'] ? $turkuaz : $mor }};text-decoration:none;white-space:nowrap">
                                    {{ $evrak['tamam'] ? 'Görüntüle →' : 'Hazırla →' }}
                                </a>
                            </div>
                        @endforeach
                    </div>
                </div>

                {{-- SAĞ: ÇALIŞAN ÖZLÜK & SAHA EVRAKLARI --}}
                <div style="{{ $kutu }}">
                    <div style="font-weight:700;font-size:.95rem;margin-bottom:.2rem">👷 Çalışan Özlük & İSG Dosyası Evrakları</div>
                    <div style="font-size:.78rem;color:{{ $gri }};margin-bottom:.75rem">
                        Personel özlük dosyasında bulunması gereken bireysel ve toplu İSG kayıtları (canlı imza ve PDF çıktılı).
                    </div>

                    <div style="display:flex;flex-direction:column">
                        @foreach ($matris['calisan_evraklari'] as $cEvrak)
                            @php $var = $cEvrak['adet'] > 0; @endphp
                            <div style="display:flex;align-items:center;justify-content:space-between;gap:.6rem;padding:.5rem 0;border-top:1px solid rgb(128 116 148 / .14)">
                                <div style="min-width:0">
                                    <div style="font-size:.84rem;font-weight:600;display:flex;align-items:center;gap:.4rem;flex-wrap:wrap">
                                        <x-filament::icon :icon="$cEvrak['ikon']" style="width:.95rem;height:.95rem;color:{{ $var ? $yesil : $turkuaz }}"/>
                                        <span>{{ $cEvrak['ad'] }}</span>
                                        <span style="font-size:.68rem;font-weight:700;padding:.05rem .45rem;border-radius:999px;color:{{ $var ? $yesil : $gri }};background:color-mix(in srgb, {{ $var ? $yesil : $gri }} 14%, transparent)">
                                            {{ $cEvrak['adet'] }} kayıt
                                        </span>
                                    </div>
                                    <div style="font-size:.75rem;color:{{ $gri }};margin-top:.1rem">{{ $cEvrak['aciklama'] }}</div>
                                </div>
                                <a href="{{ $cEvrak['url'] }}"
                                    style="font-size:.78rem;font-weight:700;color:{{ $mor }};text-decoration:none;white-space:nowrap">
                                    Oluştur / Aç →
                                </a>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        @endif
    @endif

    {{-- ==================== OSGB KADRO, KARNE & İHMAL EDİLEN FİRMALAR (F4 & F6) ==================== --}}
    @if ($sekme === 'kadro')
        @php
            $kp = $this->kadroPanosu;
            $uyusmaz = $this->sinifUyusmazliklari;
            $ihmal = $this->ihmalEdilenFirmalar;
            $karneler = $this->uzmanKarneleri;
            $duyurular = $this->ekipDuyurulari;
            $aktifKullaniciId = (int) auth()->id();
        @endphp

        {{-- FTE KAPASİTE ÖZETİ --}}
        <div class="mehse-kpi-4">
            <div style="{{ $kutu }};border-left:4px solid #2563eb">
                <div style="font-size:.74rem;color:{{ $gri }};font-weight:600">Gereken İGU Kapasitesi (FTE)</div>
                <div style="font-size:1.45rem;font-weight:800;color:#1d4ed8">{{ number_format($kp['gereken_igu_fte'], 2, ',', '.') }} kişi</div>
                <div style="font-size:.72rem;color:{{ $gri }}">{{ number_format($kp['toplam_igu_dk'], 0, ',', '.') }} dk/ay</div>
            </div>
            <div style="{{ $kutu }};border-left:4px solid #059669">
                <div style="font-size:.74rem;color:{{ $gri }};font-weight:600">Gereken Hekim Kapasitesi (FTE)</div>
                <div style="font-size:1.45rem;font-weight:800;color:#059669">{{ number_format($kp['gereken_hekim_fte'], 2, ',', '.') }} kişi</div>
                <div style="font-size:.72rem;color:{{ $gri }}">{{ number_format($kp['toplam_hekim_dk'], 0, ',', '.') }} dk/ay</div>
            </div>
            <div style="{{ $kutu }};border-left:4px solid {{ $kp['bantlar']['asan'] > 0 ? $kirmizi : $yesil }}">
                <div style="font-size:.74rem;color:{{ $gri }};font-weight:600">Kapasite Bant Dağılımı</div>
                <div style="font-size:.82rem;font-weight:700;margin-top:.25rem;display:flex;gap:.45rem;flex-wrap:wrap">
                    <span style="color:#dc2626">Aşan: {{ $kp['bantlar']['asan'] }}</span>
                    <span style="color:#d97706">Sınırda: {{ $kp['bantlar']['sinirda'] }}</span>
                    <span style="color:#059669">Normal: {{ $kp['bantlar']['normal'] }}</span>
                    <span style="color:#2563eb">Boşta: {{ $kp['bantlar']['bosta'] }}</span>
                </div>
            </div>
            <div style="{{ $kutu }};border-left:4px solid {{ count($uyusmaz) > 0 ? $kirmizi : $yesil }}">
                <div style="font-size:.74rem;color:{{ $gri }};font-weight:600">Belge Sınıfı Uyuşmazlığı</div>
                <div style="font-size:1.45rem;font-weight:800;color:{{ count($uyusmaz) > 0 ? $kirmizi : $yesil }}">{{ count($uyusmaz) }} firma</div>
                <div style="font-size:.72rem;color:{{ $gri }}">{{ count($uyusmaz) > 0 ? 'Acil atama düzeltmesi gerekli' : 'Tüm atamalar sınıfa uygun' }}</div>
            </div>
        </div>

        {{-- BELGE SINIFI UYUŞMAZLIKLARI --}}
        @if (count($uyusmaz))
            <div style="{{ $kutu }};border-left:4px solid {{ $kirmizi }}">
                <div style="font-weight:700;font-size:.92rem;color:{{ $kirmizi }};margin-bottom:.4rem">🚨 Uzman Belge Sınıfı Uyuşmazlık Uyarıları</div>
                @foreach ($uyusmaz as $u)
                    <div style="display:flex;justify-content:space-between;align-items:center;gap:.6rem;flex-wrap:wrap;padding:.4rem 0;border-top:1px solid rgb(128 116 148 / .14);font-size:.83rem">
                        <div>
                            <strong>{{ $u['unvan'] }}</strong> ({{ $u['tehlike_sinifi'] }}) — Atanan: <strong>{{ $u['igu_ad'] }}</strong>
                            <span style="color:{{ $kirmizi }};font-weight:700">({{ $u['belge_sinifi'] }} Sınıfı uygun değil — {{ $u['gereken'] }} gerekir)</span>
                        </div>
                        <a href="{{ \App\Filament\Resources\Firmas\FirmaResource::getUrl('edit', ['record' => $u['firma_id']]) }}" style="color:{{ $mor }};font-weight:700;text-decoration:none">Düzelt →</a>
                    </div>
                @endforeach
            </div>
        @endif

        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(340px,1fr));gap:.9rem;align-items:start">
            {{-- KADRO DOLULUK PANOSU --}}
            <div style="{{ $kutu }}">
                <div style="font-weight:700;font-size:.95rem;margin-bottom:.2rem">👥 OSGB Kadro Doluluk Panosu</div>
                <div style="font-size:.77rem;color:{{ $gri }};margin-bottom:.65rem">180 saat/ay (10.800 dk) tam zamanlı kapasite baz alınarak hesaplanır.</div>
                @forelse ($kp['personeller'] as $p)
                    <div style="padding:.5rem 0;border-top:1px solid rgb(128 116 148 / .14)">
                        <div style="display:flex;justify-content:space-between;align-items:center;gap:.5rem;font-size:.83rem">
                            <div>
                                <strong>{{ $p['ad_soyad'] }}</strong>
                                <span style="font-size:.73rem;color:{{ $gri }}">· {{ $p['tip'] }}@if ($p['sinif']) ({{ $p['sinif'] }})@endif · {{ $p['firma_sayisi'] }} firma</span>
                                @if ($p['tam_sureli_firma'])
                                    <span style="font-size:.68rem;font-weight:700;padding:.05rem .4rem;border-radius:999px;background:#fef3c7;color:#92400e">🔒 Tam Süreli</span>
                                @endif
                            </div>
                            <span style="font-size:.74rem;font-weight:700;color:{{ $p['bant_renk'] }}">%{{ $p['doluluk_yuzde'] }} ({{ $p['toplam_saat'] }} sa)</span>
                        </div>
                        <div style="height:6px;background:#e2e8f0;border-radius:999px;overflow:hidden;margin-top:.3rem">
                            <div style="height:100%;width:{{ min(100, $p['doluluk_yuzde']) }}%;background:{{ $p['bant_renk'] }}"></div>
                        </div>
                    </div>
                @empty
                    <p style="font-size:.82rem;color:{{ $gri }}">Aktif İSG profesyoneli tanımlı değil.</p>
                @endforelse
            </div>

            {{-- İHMAL EDİLEN FİRMALAR --}}
            <div style="{{ $kutu }}">
                <div style="font-weight:700;font-size:.95rem;margin-bottom:.2rem">⏳ İhmal Edilen Firmalar (Son Ziyaret Takibi)</div>
                <div style="font-size:.77rem;color:{{ $gri }};margin-bottom:.65rem">En uzun süredir saha denetimi / ziyaret kanıtı / bulgu kaydı girilmeyen aktif firmalar.</div>
                @forelse ($ihmal as $ih)
                    <div style="display:flex;justify-content:space-between;align-items:center;gap:.5rem;padding:.45rem 0;border-top:1px solid rgb(128 116 148 / .14);font-size:.82rem">
                        <div style="min-width:0">
                            <div style="font-weight:600">{{ $ih['unvan'] }}</div>
                            <div style="font-size:.73rem;color:{{ $gri }}">{{ $ih['tehlike_sinifi'] }} · {{ $ih['igu_ad'] }}</div>
                        </div>
                        <div style="text-align:right;white-space:nowrap">
                            <span style="font-size:.74rem;font-weight:700;padding:.1rem .5rem;border-radius:999px;color:{{ $ih['kritik'] ? $kirmizi : $yesil }};background:color-mix(in srgb, {{ $ih['kritik'] ? $kirmizi : $yesil }} 12%, transparent)">
                                {{ $ih['gecen_gun'] === null ? 'Hiç gidilmedi' : $ih['gecen_gun'].' gündür yok' }}
                            </span>
                            <div style="font-size:.7rem;color:{{ $gri }}">{{ $ih['son_ziyaret'] ? 'Son: '.$ih['son_ziyaret'] : 'Kayıt yok' }}</div>
                        </div>
                    </div>
                @empty
                    <p style="font-size:.82rem;color:{{ $gri }}">Aktif firma bulunamadı.</p>
                @endforelse
            </div>
        </div>

        {{-- AYLIK UZMAN DEĞERLENDİRME KARNESİ (0-100 — F6) --}}
        <div style="{{ $kutu }}">
            <div style="font-weight:700;font-size:.95rem;margin-bottom:.2rem">🏆 Aylık Uzman Değerlendirme Karnesi (0–100)</div>
            <div style="font-size:.77rem;color:{{ $gri }};margin-bottom:.7rem">Ağırlıklar: Aylık Ziyaret Uyumu (%40) · DÖF Kapanış Oranı (%35) · Portföy &amp; Sınıf Uyumu (%25).</div>
            @if (count($karneler))
                <div style="overflow-x:auto">
                    <table style="width:100%;border-collapse:collapse;font-size:.82rem">
                        <thead>
                            <tr style="text-align:left;border-bottom:1px solid #e2e8f0;color:{{ $gri }}">
                                <th style="padding:.45rem">Sıra / Uzman</th>
                                <th style="padding:.45rem">Firma</th>
                                <th style="padding:.45rem">Ziyaret (%40)</th>
                                <th style="padding:.45rem">DÖF (%35)</th>
                                <th style="padding:.45rem">Uyum (%25)</th>
                                <th style="padding:.45rem">Toplam Puan</th>
                                <th style="padding:.45rem">Erken Uyarılar</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($karneler as $idx => $kn)
                                <tr style="border-bottom:1px solid rgb(128 116 148 / .12)">
                                    <td style="padding:.45rem"><strong>#{{ $idx + 1 }} {{ $kn['ad_soyad'] }}</strong><div style="font-size:.72rem;color:{{ $gri }}">{{ $kn['tip'] }}</div></td>
                                    <td style="padding:.45rem">{{ $kn['firma_sayisi'] }}</td>
                                    <td style="padding:.45rem">%{{ $kn['ziyaret_puan'] }}</td>
                                    <td style="padding:.45rem">%{{ $kn['dof_puan'] }}</td>
                                    <td style="padding:.45rem">%{{ $kn['uyum_puan'] }}</td>
                                    <td style="padding:.45rem"><span style="font-weight:800;font-size:.95rem;color:{{ $kn['renk'] }}">{{ $kn['toplam_puan'] }} / 100</span></td>
                                    <td style="padding:.45rem">
                                        @forelse ($kn['uyarilar'] as $uy)
                                            <span style="display:inline-block;font-size:.7rem;font-weight:600;padding:.05rem .45rem;border-radius:999px;background:#fef2f2;color:#b91c1c;margin:.1rem .15rem .1rem 0">⚠ {{ $uy }}</span>
                                        @empty
                                            <span style="font-size:.74rem;color:{{ $yesil }};font-weight:600">✓ Sorun yok</span>
                                        @endforelse
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <p style="font-size:.82rem;color:{{ $gri }}">Aktif firma ataması bulunan İSG profesyoneli yok.</p>
            @endif
        </div>

        {{-- EKİP İÇİ DUYURU & OKUNDU TAKİBİ (F6) --}}
        <div style="{{ $kutu }}">
            <div style="font-weight:700;font-size:.95rem;margin-bottom:.2rem">📣 Ekip İçi Duyuru &amp; Okundu Takibi</div>
            <div style="font-size:.77rem;color:{{ $gri }};margin-bottom:.75rem">OSGB içi talimat, mevzuat değişikliği veya saha uyarılarını ekibinize yayınlayın; kimin ne zaman okuyup onayladığını görün.</div>
            <div style="display:grid;grid-template-columns:2fr 1fr;gap:.55rem;margin-bottom:.55rem">
                <input type="text" wire:model="duyuruBaslik" placeholder="Duyuru / Talimat başlığı..." style="padding:.45rem .65rem;border:1px solid #cbd5e1;border-radius:.5rem;font-size:.83rem">
                <select wire:model="duyuruOncelik" style="padding:.45rem .65rem;border:1px solid #cbd5e1;border-radius:.5rem;font-size:.83rem">
                    @foreach (\App\Models\EkipDuyurusu::ONCELIKLER as $k => $ad)<option value="{{ $k }}">{{ $ad }}</option>@endforeach
                </select>
            </div>
            <div style="display:flex;gap:.55rem;align-items:flex-end;flex-wrap:wrap;margin-bottom:.85rem">
                <textarea wire:model="duyuruIcerik" rows="2" placeholder="Ekibe iletilecek talimat / mesaj..." style="flex:1;min-width:240px;padding:.45rem .65rem;border:1px solid #cbd5e1;border-radius:.5rem;font-size:.83rem"></textarea>
                <x-filament::button wire:click="ekipDuyurusuGonder" icon="heroicon-o-paper-airplane">Duyuruyu Yayınla</x-filament::button>
            </div>

            @forelse ($duyurular as $d)
                @php $okudu = $d->okuduMu($aktifKullaniciId); @endphp
                <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:.75rem;flex-wrap:wrap;padding:.6rem 0;border-top:1px solid rgb(128 116 148 / .14)">
                    <div style="flex:1;min-width:240px">
                        <div style="font-weight:700;font-size:.85rem">
                            @if ($d->oncelik === 'acil') <span style="color:#dc2626">[ACİL]</span> @elseif ($d->oncelik === 'onemli') <span style="color:#d97706">[ÖNEMLİ]</span> @endif
                            {{ $d->baslik }}
                        </div>
                        <div style="font-size:.8rem;color:#334155;margin-top:.15rem">{{ $d->icerik }}</div>
                        <div style="font-size:.72rem;color:{{ $gri }};margin-top:.25rem">
                            {{ $d->gonderen?->name ?: 'Yönetici' }} · {{ $d->created_at?->format('d.m.Y H:i') }} ·
                            <strong>{{ $d->okuyanSayisi() }} kişi okuyup onayladı</strong>
                            @if ($d->okuyanlar)
                                ({{ collect($d->okuyanlar)->pluck('ad')->implode(', ') }})
                            @endif
                        </div>
                    </div>
                    <div>
                        @if ($okudu)
                            <span style="font-size:.75rem;font-weight:700;color:{{ $yesil }}">✓ Okundu Onaylı</span>
                        @else
                            <x-filament::button size="xs" color="success" wire:click="ekipDuyurusuOkundu({{ $d->id }})">Okudum &amp; Onayladım</x-filament::button>
                        @endif
                    </div>
                </div>
            @empty
                <p style="font-size:.82rem;color:{{ $gri }}">Henüz ekip duyurusu yok.</p>
            @endforelse
        </div>
    @endif
</x-filament-panels::page>
