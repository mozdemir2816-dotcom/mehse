@php
    $rehber = \App\Support\SayfaRehberi::bul($scopes ?? []);
    $yonlendirmeler = $rehber ? \App\Support\SayfaRehberi::yonlendirmeler($rehber, $scopes ?? []) : [];
    $bolumler = $rehber['bolumler'] ?? [];
    $sureTablosu = $rehber['sure_tablosu'] ?? [];
    $detayli = ! empty($bolumler) || ! empty($yonlendirmeler) || ! empty($sureTablosu);
@endphp

@if ($rehber)
    <div x-data="{ acik: false, sekme: 'rehber' }" class="mehse-nasil-kullanilir" style="display:inline-flex;align-items:center">
        <button type="button"
            x-on:click="acik = true; sekme = 'rehber'"
            style="display:inline-flex;align-items:center;gap:.35rem;padding:.45rem .78rem;border-radius:.55rem;border:1px solid rgb(107 114 128 / .35);background:transparent;color:inherit;font-size:.8rem;font-weight:600;cursor:pointer;white-space:nowrap"
            title="Bu modülün kullanım rehberini ve hızlı yönlendirmelerini aç">
            <span>❓ Nasıl Kullanılır?</span>
        </button>

        <template x-teleport="body">
            <div x-show="acik"
                x-transition.opacity
                x-on:click.self="acik = false"
                x-on:keydown.escape.window="acik = false"
                style="position:fixed;inset:0;z-index:80;background:rgb(15 23 42 / .55);display:flex;align-items:center;justify-content:center;padding:1rem"
                role="dialog" aria-modal="true" aria-label="{{ $rehber['baslik'] }} — Nasıl Kullanılır?">
                <div style="background:#ffffff;color:#111827;border-radius:1rem;box-shadow:0 24px 60px rgb(0 0 0 / .35);width:min(100%,{{ $detayli ? '50rem' : '36rem' }});max-height:90vh;display:flex;flex-direction:column;border:1px solid #e5e7eb">
                    {{-- Üst Başlık --}}
                    <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:.75rem;padding:1rem 1.25rem;border-bottom:1px solid #e5e7eb;background:#f8fafc;border-radius:1rem 1rem 0 0;flex-shrink:0">
                        <div>
                            <div style="font-size:.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:#1d4ed8">Hızlı Kullanım Rehberi</div>
                            <div style="font-size:1.05rem;font-weight:800;color:#0f172a;margin-top:.1rem">{{ $rehber['baslik'] }}</div>
                        </div>
                        <button type="button" x-on:click="acik = false" aria-label="Kapat"
                            style="min-width:2.2rem;min-height:2.2rem;border:none;background:transparent;font-size:1.15rem;cursor:pointer;color:#475569;border-radius:.4rem">✕</button>
                    </div>

                    {{-- Sekme Şeridi (Detaylı rehberlerde: Adım Adım | Yönlendirmeler | Yasal Süre Tablosu) --}}
                    @if ($detayli)
                        <div style="display:flex;gap:.35rem;flex-wrap:wrap;padding:.6rem 1.25rem;border-bottom:1px solid #e5e7eb;background:#ffffff;flex-shrink:0">
                            <button type="button" x-on:click="sekme = 'rehber'"
                                x-bind:style="sekme === 'rehber' ? 'background:#1d4ed8;color:#fff;border-color:#1d4ed8' : 'background:#f8fafc;color:#334155;border-color:#cbd5e1'"
                                style="padding:.38rem .78rem;border-radius:.5rem;border:1px solid;font-size:.78rem;font-weight:700;cursor:pointer">
                                📋 Adım Adım Kullanım
                            </button>
                            @if (! empty($yonlendirmeler))
                                <button type="button" x-on:click="sekme = 'yonlendirme'"
                                    x-bind:style="sekme === 'yonlendirme' ? 'background:#1d4ed8;color:#fff;border-color:#1d4ed8' : 'background:#f8fafc;color:#334155;border-color:#cbd5e1'"
                                    style="padding:.38rem .78rem;border-radius:.5rem;border:1px solid;font-size:.78rem;font-weight:700;cursor:pointer">
                                    🧭 Ekranlara Yönlendirme ({{ count($yonlendirmeler) }})
                                </button>
                            @endif
                            @if (! empty($sureTablosu))
                                <button type="button" x-on:click="sekme = 'sureler'"
                                    x-bind:style="sekme === 'sureler' ? 'background:#1d4ed8;color:#fff;border-color:#1d4ed8' : 'background:#f8fafc;color:#334155;border-color:#cbd5e1'"
                                    style="padding:.38rem .78rem;border-radius:.5rem;border:1px solid;font-size:.78rem;font-weight:700;cursor:pointer">
                                    ⏱️ Yasal Süre & Karma Dağılım Tablosu
                                </button>
                            @endif
                        </div>
                    @endif

                    {{-- İçerik Alanı (Kaydırılabilir) --}}
                    <div style="padding:1.05rem 1.25rem;overflow-y:auto;display:flex;flex-direction:column;gap:.9rem">
                        {{-- SEKME 1: ADIM ADIM KULLANIM & DETAYLI SENARYOLAR --}}
                        <div x-show="sekme === 'rehber'" style="display:flex;flex-direction:column;gap:.85rem">
                            <p style="margin:0;font-size:.86rem;color:#334155;line-height:1.55">
                                {{ $rehber['ozet'] }}
                            </p>

                            <div style="display:flex;flex-direction:column;gap:.55rem">
                                @foreach ($rehber['adimlar'] as $i => $adim)
                                    <div style="display:flex;gap:.7rem;align-items:flex-start;padding:.65rem .8rem;border-radius:.65rem;border:1px solid #e2e8f0;background:#f8fafc">
                                        <span style="flex-shrink:0;width:1.55rem;height:1.55rem;border-radius:999px;background:#2563eb;color:#ffffff;font-size:.78rem;font-weight:800;display:inline-flex;align-items:center;justify-content:center">
                                            {{ $i + 1 }}
                                        </span>
                                        <span style="font-size:.83rem;color:#1e293b;line-height:1.48">{{ $adim }}</span>
                                    </div>
                                @endforeach
                            </div>

                            @if (! empty($bolumler))
                                <div style="display:flex;flex-direction:column;gap:.65rem;margin-top:.15rem">
                                    @foreach ($bolumler as $b)
                                        <div style="border:1px solid #cbd5e1;border-radius:.75rem;overflow:hidden;background:#ffffff">
                                            <div style="display:flex;align-items:center;justify-content:space-between;gap:.5rem;padding:.55rem .85rem;background:#eff6ff;border-bottom:1px solid #dbeafe">
                                                <strong style="font-size:.84rem;color:#1e3a8a">{{ $b['baslik'] }}</strong>
                                                @if (! empty($b['etiket']))
                                                    <span style="font-size:.68rem;font-weight:700;padding:.12rem .5rem;border-radius:999px;background:#dbeafe;color:#1d4ed8">{{ $b['etiket'] }}</span>
                                                @endif
                                            </div>
                                            <ul style="margin:0;padding:.65rem 1rem .65rem 1.75rem;display:flex;flex-direction:column;gap:.38rem;font-size:.81rem;color:#1e293b;line-height:1.48">
                                                @foreach ($b['maddeler'] as $m)
                                                    <li>{{ $m }}</li>
                                                @endforeach
                                            </ul>
                                        </div>
                                    @endforeach
                                </div>
                            @endif

                            {{-- Hızlı Geçiş Şeridi (İlk sekmede de anında yönlendirme) --}}
                            @if (! empty($yonlendirmeler))
                                <div style="padding:.7rem .85rem;border-radius:.7rem;background:#f0fdf4;border:1px solid #bbf7d0">
                                    <div style="font-size:.75rem;font-weight:800;color:#166534;margin-bottom:.45rem">🧭 Hızlı Yönlendirme — İlgili Ekrana Doğrudan Git:</div>
                                    <div style="display:flex;flex-wrap:wrap;gap:.4rem">
                                        @foreach ($yonlendirmeler as $y)
                                            @if ($y['aktif'])
                                                <span style="display:inline-flex;align-items:center;gap:.3rem;padding:.28rem .6rem;border-radius:.45rem;background:#dcfce7;border:1px solid #86efac;color:#166534;font-size:.75rem;font-weight:700">
                                                    {{ $y['ikon'] }} {{ $y['etiket'] }} (Şu an buradasınız)
                                                </span>
                                            @else
                                                <a href="{{ $y['url'] }}"
                                                    style="display:inline-flex;align-items:center;gap:.3rem;padding:.28rem .62rem;border-radius:.45rem;background:#ffffff;border:1px solid #86efac;color:#15803d;font-size:.75rem;font-weight:700;text-decoration:none">
                                                    <span>{{ $y['ikon'] }} {{ $y['etiket'] }}</span>
                                                    <span style="font-weight:800">→</span>
                                                </a>
                                            @endif
                                        @endforeach
                                    </div>
                                </div>
                            @endif

                            <div style="padding:.7rem .85rem;border-radius:.65rem;background:#fffbeb;border:1px solid #fcd34d;font-size:.8rem;color:#92400e;line-height:1.45">
                                <strong>💡 Mevzuat / Pratik İpucu:</strong> {{ $rehber['ipucu'] }}
                            </div>
                        </div>

                        {{-- SEKME 2: DETAYLI EKRAN YÖNLENDİRME KARTLARI --}}
                        @if (! empty($yonlendirmeler))
                            <div x-show="sekme === 'yonlendirme'" x-cloak style="display:flex;flex-direction:column;gap:.65rem">
                                <p style="margin:0;font-size:.83rem;color:#475569">
                                    Hangi işlem için hangi ekranı kullanacağınızı aşağıdan seçip <strong>tek tıkla</strong> geçebilirsiniz (aktif seçili firmanız otomatik taşınır):
                                </p>
                                <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(290px,1fr));gap:.6rem">
                                    @foreach ($yonlendirmeler as $y)
                                        @if ($y['aktif'])
                                            <div style="padding:.7rem .85rem;border-radius:.7rem;border:2px solid #2563eb;background:#eff6ff;display:flex;flex-direction:column;gap:.25rem">
                                                <div style="display:flex;align-items:center;justify-content:space-between;gap:.4rem">
                                                    <strong style="font-size:.84rem;color:#1e3a8a">{{ $y['ikon'] }} {{ $y['etiket'] }}</strong>
                                                    <span style="font-size:.68rem;font-weight:700;padding:.1rem .45rem;border-radius:999px;background:#2563eb;color:#fff">Aktif Ekran</span>
                                                </div>
                                                <span style="font-size:.77rem;color:#334155;line-height:1.4">{{ $y['aciklama'] }}</span>
                                            </div>
                                        @else
                                            <a href="{{ $y['url'] }}"
                                                style="padding:.7rem .85rem;border-radius:.7rem;border:1px solid #cbd5e1;background:#f8fafc;text-decoration:none;display:flex;flex-direction:column;gap:.25rem;transition:border-color .15s"
                                                onmouseover="this.style.borderColor='#2563eb';this.style.background='#eff6ff'"
                                                onmouseout="this.style.borderColor='#cbd5e1';this.style.background='#f8fafc'">
                                                <div style="display:flex;align-items:center;justify-content:space-between;gap:.4rem">
                                                    <strong style="font-size:.84rem;color:#0f172a">{{ $y['ikon'] }} {{ $y['etiket'] }}</strong>
                                                    <span style="font-size:.74rem;font-weight:800;color:#1d4ed8">Git →</span>
                                                </div>
                                                <span style="font-size:.77rem;color:#475569;line-height:1.4">{{ $y['aciklama'] }}</span>
                                            </a>
                                        @endif
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        {{-- SEKME 3: YASAL SÜRE & KARMA DAĞILIM TABLOSU --}}
                        @if (! empty($sureTablosu))
                            <div x-show="sekme === 'sureler'" x-cloak style="display:flex;flex-direction:column;gap:.75rem">
                                <p style="margin:0;font-size:.83rem;color:#475569">
                                    <strong>Çalışanların İSG Eğitimlerinin Usul ve Esasları Hakkında Yönetmelik (RG 02.04.2026)</strong> ve sistem karma dağıtım kuralları:
                                </p>
                                <div style="overflow-x:auto">
                                    <table style="width:100%;border-collapse:collapse;font-size:.79rem;border:1px solid #cbd5e1">
                                        <thead>
                                            <tr style="background:#f1f5f9;color:#0f172a">
                                                <th style="padding:.5rem .6rem;border:1px solid #cbd5e1;text-align:left">Tehlike Sınıfı</th>
                                                <th style="padding:.5rem .6rem;border:1px solid #cbd5e1;text-align:left">İlk Defa Toplam</th>
                                                <th style="padding:.5rem .6rem;border:1px solid #cbd5e1;text-align:left;color:#1d4ed8">💻 Online (LMS)</th>
                                                <th style="padding:.5rem .6rem;border:1px solid #cbd5e1;text-align:left;color:#b45309">🏫 Yüz Yüze</th>
                                                <th style="padding:.5rem .6rem;border:1px solid #cbd5e1;text-align:left">Tekrar (Yenileme)</th>
                                                <th style="padding:.5rem .6rem;border:1px solid #cbd5e1;text-align:left">Periyot</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($sureTablosu as $satir)
                                                <tr>
                                                    <td style="padding:.5rem .6rem;border:1px solid #cbd5e1;font-weight:700">{{ $satir['sinif'] }}</td>
                                                    <td style="padding:.5rem .6rem;border:1px solid #cbd5e1;font-weight:700">{{ $satir['ilk'] }}</td>
                                                    <td style="padding:.5rem .6rem;border:1px solid #cbd5e1;color:#1d4ed8;font-weight:600">{{ $satir['online'] }}</td>
                                                    <td style="padding:.5rem .6rem;border:1px solid #cbd5e1;color:#b45309;font-weight:600">{{ $satir['yuz_yuze'] }}</td>
                                                    <td style="padding:.5rem .6rem;border:1px solid #cbd5e1">{{ $satir['tekrar'] }}</td>
                                                    <td style="padding:.5rem .6rem;border:1px solid #cbd5e1;font-weight:600">{{ $satir['periyot'] }}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                                <div style="padding:.65rem .85rem;border-radius:.65rem;background:#eff6ff;border:1px solid #bfdbfe;font-size:.79rem;color:#1e3a8a;line-height:1.45">
                                    <strong>📌 Önemli Kurallar:</strong>
                                    <ul style="margin:.3rem 0 0;padding-left:1.2rem">
                                        <li><strong>1 Ders Saati</strong> = 45 dk fiili ders + 15 dk dinlenme. Toplam 11 saati aştığında (12s ve 16s) form otomatik <strong>2 güne</strong> bölünür.</li>
                                        <li><strong>İşbaşı Eğitimi</strong> (en az 2 saat) fiilen işe başlamadan önce verilir, temel eğitim süresinden düşülmez.</li>
                                        <li><strong>Tehlikeli & Çok Tehlikeli</strong> sınıflarda 4. Bölüm (İşe ve İşyerine Özgü Riskler) <strong>yüz yüze zorunludur</strong>.</li>
                                    </ul>
                                </div>
                            </div>
                        @endif
                    </div>

                    {{-- Alt Şerit --}}
                    <div style="display:flex;justify-content:flex-end;padding:.75rem 1.25rem;border-top:1px solid #e5e7eb;background:#f8fafc;border-radius:0 0 1rem 1rem;flex-shrink:0">
                        <button type="button" x-on:click="acik = false"
                            style="padding:.48rem 1.15rem;border-radius:.55rem;border:none;background:#2563eb;color:#ffffff;font-size:.83rem;font-weight:700;cursor:pointer">
                            Anladım, Kapat
                        </button>
                    </div>
                </div>
            </div>
        </template>
    </div>
@endif
