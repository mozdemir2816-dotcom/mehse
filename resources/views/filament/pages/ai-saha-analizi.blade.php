@php
    use App\Models\SahaAnalizi;
    use Illuminate\Support\Facades\Storage;

    $mor = 'rgb(139 92 246)';
    $kutu = 'border:1px solid rgb(107 114 128 / .3);border-radius:.75rem;padding:1rem';
    $girdi = 'width:100%;padding:.5rem .75rem;border-radius:.5rem;border:1px solid rgb(107 114 128 / .35);background:transparent;font-size:.85rem';
    $etiket = 'font-size:.72rem;font-weight:600;color:rgb(107 114 128);text-transform:uppercase;letter-spacing:.03em';
    $kilitli = $this->kilitliMi();
    $fk = config('isg.risk_fine_kinney');
    $onayli = collect($bulgular)->where('durum', 'onaylandi')->count();
    $dofSayisi = count($this->dofIndexleri());
@endphp

<x-filament-panels::page>
    <style>
        .sgr-panel { background: #fff; color: #111827; }
        .dark .sgr-panel { background: #18181b; color: #f4f4f5; }
        .sgr-panel input { color: inherit; }
    </style>

    {{-- DURUM ŞERİDİ --}}
    <div style="{{ $kutu }};display:flex;flex-wrap:wrap;gap:.6rem;align-items:center">
        <div style="flex:1;min-width:200px">
            <div style="font-weight:700;letter-spacing:.02em">SAHA GÖZLEM RAPORU {{ $this->kayit?->belge_no ? '· '.$this->kayit->belge_no : '' }}</div>
            <div style="font-size:.78rem;color:rgb(107 114 128)">
                @if ($kilitli)
                    Tamamlandı {{ $this->kayit?->tamamlanma_tarihi?->format('d.m.Y H:i') }} — rapor artık düzenlenemez.
                    @if ($this->kayit?->dofRaporu)
                        DÖF: <strong>{{ $this->kayit->dofRaporu->belge_no }}</strong> ({{ count($this->kayit->dofRaporu->maddeler ?? []) }} madde)
                    @endif
                @elseif ($kayitId)
                    Taslak kayıtlı — kaldığınız yerden devam edebilirsiniz.
                @else
                    Yeni rapor — firma seçince maddeler taslak olarak kaydedilir.
                @endif
            </div>
        </div>
        @if ($kilitli)
            <span style="font-size:.75rem;font-weight:700;padding:.25rem .6rem;border-radius:.4rem;color:#15803d;background:rgb(22 163 74 / .12);border:1px solid rgb(22 163 74 / .35)">✓ TAMAMLANDI</span>
            <x-filament::button size="sm" color="gray" icon="heroicon-o-plus" wire:click="yeniRapor">Yeni Rapor</x-filament::button>
        @else
            <span style="font-size:.75rem;font-weight:700;padding:.25rem .6rem;border-radius:.4rem;color:#9a3412;background:rgb(234 88 12 / .1);border:1px solid rgb(234 88 12 / .35)">◷ TASLAK</span>
            @if ($kayitId)
                <x-filament::button size="sm" color="danger" outlined icon="heroicon-o-x-mark" wire:click="taslagiIptal"
                    wire:confirm="Taslak silinecek. Emin misiniz?">Taslağı İptal</x-filament::button>
            @endif
        @endif
    </div>

    @if (! $this->aiAktif)
        <div style="{{ $kutu }};background:rgb(245 158 11 / .08);border-color:rgb(245 158 11 / .4);font-size:.82rem;color:#b45309">
            Gemini API anahtarı tanımlı değil — "Fotoğraftan" ve "Açıklamadan" yapay zekâ yöntemleri kullanılamıyor.
            Uygunsuzlukları <strong>Manuel</strong> yöntemiyle ekleyebilirsiniz.
        </div>
    @endif

    {{-- 1. GENEL BİLGİLER --}}
    <details @if (! $firmaId) open @endif style="{{ $kutu }}">
        <summary style="cursor:pointer;display:flex;justify-content:space-between;gap:.5rem">
            <span>
                <span style="{{ $etiket }}">Genel Bilgiler</span><br>
                <strong>{{ $this->firma?->unvan ?? 'Firma seçin' }}</strong>
            </span>
            <span style="color:rgb(107 114 128)">▾</span>
        </summary>

        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:1rem;margin-top:1rem">
            <div>
                <label style="font-weight:600;font-size:.82rem">Firma <span style="color:#ef4444">*</span></label>
                <select wire:model.live="firmaId" @disabled($kilitli) style="margin-top:.3rem;{{ $girdi }}">
                    <option value="">— Firma seçin —</option>
                    @foreach ($this->firmalar as $id => $ad)
                        <option value="{{ $id }}">{{ $ad }}</option>
                    @endforeach
                </select>
            </div>
            @foreach ([
                'alanBolge' => ['Alan / Bölge', 'text', 'Örn: Şantiye, B blok'],
                'gozetimTarihAraligi' => ['Gözlem Tarih Aralığı', 'text', 'Örn: 01.10.2026 - 02.10.2026'],
                'raporTarihi' => ['Rapor Tarihi', 'date', null],
                'gozetimYapan' => ['Hazırlayan (İSG Uzmanı)', 'text', null],
                'gozetimYapanSertifikaNo' => ['İSG Sertifika No', 'text', null],
                'sorumluKisi' => ['Sorumlu Kişi', 'text', null],
                'isverenVekiliAdi' => ['Onaylayan (İşveren / Vekili)', 'text', null],
            ] as $alan => [$ad, $tip, $ornek])
                <div>
                    <label style="font-weight:600;font-size:.82rem">{{ $ad }}</label>
                    <input type="{{ $tip }}" wire:model="{{ $alan }}" @if ($ornek) placeholder="{{ $ornek }}" @endif @disabled($kilitli)
                        style="margin-top:.3rem;{{ $girdi }}">
                </div>
            @endforeach
        </div>

        @if ($this->firma && ! $this->firma->igu)
            <p style="font-size:.78rem;color:rgb(107 114 128);margin-top:.75rem">
                Firmaya İGU atanmamış — kaşe/imza için <a href="{{ \App\Filament\Resources\IsgProfesyonelis\IsgProfesyoneliResource::getUrl() }}" style="color:{{ $mor }};text-decoration:underline">İSG Profesyonelleri</a>
                panelinden İGU ekleyip Firma düzenleme sayfasından atayın (yoksa Profilim'deki kaşe/imza kullanılır).
            </p>
        @endif
    </details>

    @unless ($kilitli)
        {{-- 2. UYGUNSUZLUK EKLE --}}
        <div style="{{ $kutu }}">
            <div style="font-weight:700">Uygunsuzluk Ekle</div>
            <div style="font-size:.8rem;color:rgb(107 114 128);margin-bottom:.75rem">Bir yöntem seçin, yapay zekâ ile ya da elle.</div>

            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:.6rem">
                @foreach ([
                    'foto' => ['📷', 'Fotoğraftan', 'Yüklediğiniz fotoğraflardan uygunsuzluk bulunsun', true],
                    'aciklama' => ['✦', 'Açıklamadan', 'Kısaca yazın, yapay zekâ maddeyi oluştursun', true],
                    'manuel' => ['✎', 'Manuel', 'Alanları kendiniz doldurun', false],
                ] as $anahtar => [$ikon, $ad, $acik, $ai])
                    <button type="button" wire:click="$set('eklemeYontemi', '{{ $anahtar }}')"
                        style="text-align:left;display:flex;gap:.7rem;align-items:flex-start;padding:.8rem;border-radius:.7rem;cursor:pointer;background:{{ $eklemeYontemi === $anahtar ? 'rgb(59 130 246 / .08)' : 'transparent' }};border:{{ $eklemeYontemi === $anahtar ? '2px solid rgb(59 130 246)' : '1px solid rgb(107 114 128 / .3)' }}">
                        <span style="font-size:1.2rem;width:2.2rem;height:2.2rem;display:flex;align-items:center;justify-content:center;border-radius:.5rem;background:rgb(107 114 128 / .08)">{{ $ikon }}</span>
                        <span style="flex:1">
                            <span style="display:flex;gap:.4rem;align-items:center;flex-wrap:wrap">
                                <strong>{{ $ad }}</strong>
                                @if ($ai)<span style="font-size:.65rem;padding:.05rem .35rem;border-radius:.25rem;border:1px solid rgb(59 130 246 / .4);color:rgb(37 99 235)">✦ Yapay Zekâ</span>@endif
                            </span>
                            <span style="display:block;font-size:.76rem;color:rgb(107 114 128)">{{ $acik }}</span>
                        </span>
                    </button>
                @endforeach
            </div>

            <div style="margin-top:.9rem">
                @if ($eklemeYontemi === 'foto')
                    <p style="font-size:.8rem;color:rgb(107 114 128);margin-bottom:.5rem">
                        Aşağıdaki <strong>Rapor Fotoğrafları</strong> alanından fotoğraf çekin/yükleyin, analiz edilecekleri seçip
                        <strong>Seçilenleri analiz et</strong>'e basın.
                    </p>
                    <input type="text" wire:model="baglamNotu" placeholder="Opsiyonel bağlam notu — örn: inşaat sahası, kalıp katı" style="{{ $girdi }}">
                    <details style="margin-top:.6rem;border:1px solid rgb(107 114 128 / .3);border-radius:.6rem;padding:.6rem .8rem" x-data="{ ara: '' }">
                        <summary style="cursor:pointer;font-weight:600;font-size:.82rem">
                            İncelenecek tehlikeler ({{ count($odakKategoriler) }} / {{ count(config('isg.saha_tehlike_kategorileri')) }} kategori)
                        </summary>
                        <label style="display:flex;gap:.45rem;align-items:flex-start;font-size:.8rem;margin:.6rem 0">
                            <input type="checkbox" wire:model.live="tumunuTara" style="margin-top:.15rem">
                            <span><strong>Tüm görünür tehlikeleri tara</strong><br><span style="color:rgb(107 114 128)">Kategori seçimi AI'yı diğer açıkça görülen tehlikeleri değerlendirmekten alıkoymaz.</span></span>
                        </label>
                        <div style="display:flex;gap:.5rem;margin-bottom:.5rem;flex-wrap:wrap">
                            <input type="search" x-model="ara" placeholder="Kategori ara…" style="flex:1;min-width:180px;padding:.4rem .6rem;border-radius:.4rem;border:1px solid rgb(107 114 128 / .35);background:transparent;font-size:.8rem">
                            <x-filament::button size="xs" color="gray" wire:click="$set('odakKategoriler', {{ \Illuminate\Support\Js::from(config('isg.saha_tehlike_kategorileri')) }})">Tümünü seç</x-filament::button>
                            <x-filament::button size="xs" color="gray" wire:click="$set('odakKategoriler', [])">Temizle</x-filament::button>
                        </div>
                        <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(230px,1fr));gap:.2rem .8rem;max-height:18rem;overflow-y:auto">
                            @foreach (config('isg.saha_tehlike_kategorileri') as $k)
                                <label x-show="! ara || {{ \Illuminate\Support\Js::from(mb_strtolower($k)) }}.includes(ara.toLocaleLowerCase('tr'))" style="display:flex;gap:.35rem;align-items:center;font-size:.78rem;cursor:pointer">
                                    <input type="checkbox" wire:model="odakKategoriler" value="{{ $k }}"> {{ $k }}
                                </label>
                            @endforeach
                        </div>
                    </details>
                @elseif ($eklemeYontemi === 'aciklama')
                    <textarea wire:model="aciklamaMetni" rows="3" placeholder="Örn: 3. kat döşeme kenarında korkuluk yok, kolon filizleri açıkta" style="{{ $girdi }}"></textarea>
                    @if ($seciliFotograflar)
                        <p style="font-size:.75rem;color:rgb(107 114 128);margin-top:.3rem">Seçili ilk fotoğraf maddeye eklenecek.</p>
                    @endif
                    <x-filament::button color="primary" icon="heroicon-o-sparkles" wire:click="aciklamadanEkle" wire:loading.attr="disabled" style="margin-top:.6rem" :disabled="! $this->aiAktif">
                        <span wire:loading.remove wire:target="aciklamadanEkle">Maddeyi Oluştur</span>
                        <span wire:loading wire:target="aciklamadanEkle">Oluşturuluyor…</span>
                    </x-filament::button>
                @else
                    <x-filament::button color="success" icon="heroicon-o-pencil-square" wire:click="manuelEkle">Boş Madde Ekle</x-filament::button>
                    @if ($seciliFotograflar)
                        <span style="font-size:.75rem;color:rgb(107 114 128);margin-left:.5rem">Seçili ilk fotoğraf maddeye eklenecek.</span>
                    @endif
                @endif
            </div>
        </div>
    @endunless

    {{-- 3. RAPOR FOTOĞRAFLARI --}}
    <div style="{{ $kutu }}">
        @php
            $kullanimda = collect($fotograflar)->filter(fn ($f) => $this->kullanilanFotoSayisi($f['yol']) > 0)->count();
            $bekleyenFoto = collect($fotograflar)->where('analiz_edildi', false)->count();
        @endphp
        <div style="font-weight:700">🖼 Rapor Fotoğrafları</div>
        <div style="font-size:.8rem;color:rgb(107 114 128);margin-bottom:.6rem">
            @if ($fotograflar)
                {{ count($fotograflar) }} fotoğraf · {{ $kullanimda }} kullanımda · {{ $bekleyenFoto }} analiz bekliyor
            @else
                Henüz fotoğraf yok, saha fotoğraflarınızı buraya yükleyin.
            @endif
        </div>

        @unless ($kilitli)
            <div style="display:flex;justify-content:space-between;align-items:center;font-size:.8rem;color:rgb(107 114 128);margin-bottom:.5rem">
                <span>Analiz edilecekleri seçin, en fazla {{ \App\Filament\Pages\AiSahaAnalizi::MAX_FOTOGRAF }}</span>
                @if ($seciliFotograflar)
                    <button type="button" wire:click="seciminiBirak" style="background:none;border:none;cursor:pointer;color:inherit">✕ Seçimi bırak</button>
                @endif
            </div>
        @endunless

        <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(110px,1fr));gap:.6rem">
            @foreach ($fotograflar as $f)
                @php
                    $secili = in_array($f['yol'], $seciliFotograflar, true);
                    $kullanim = $this->kullanilanFotoSayisi($f['yol']);
                @endphp
                <div wire:key="foto-{{ md5($f['yol']) }}">
                    <div @unless ($kilitli) wire:click="fotoSeciminiDegistir('{{ addslashes($f['yol']) }}')" @endunless
                        style="position:relative;aspect-ratio:1;border-radius:.5rem;overflow:hidden;cursor:{{ $kilitli ? 'default' : 'pointer' }};border:{{ $secili ? '3px solid rgb(37 99 235)' : '1px solid rgb(107 114 128 / .3)' }}">
                        <img src="{{ Storage::disk('public')->url($f['yol']) }}" style="width:100%;height:100%;object-fit:cover">
                        @if ($secili)
                            <span style="position:absolute;top:.3rem;right:.3rem;background:rgb(37 99 235);color:#fff;border-radius:999px;width:1.3rem;height:1.3rem;display:flex;align-items:center;justify-content:center;font-size:.75rem">✓</span>
                        @endif
                    </div>
                    <div style="display:flex;justify-content:space-between;align-items:center;margin-top:.2rem;gap:.2rem">
                        @if ($kullanim)
                            <span style="font-size:.66rem;padding:.05rem .3rem;border-radius:.25rem;background:rgb(22 163 74 / .12);color:#15803d">{{ $kullanim }} maddede</span>
                        @elseif (! $f['analiz_edildi'])
                            <span style="font-size:.66rem;padding:.05rem .3rem;border-radius:.25rem;background:rgb(59 130 246 / .1);color:rgb(37 99 235)">Analiz edilmedi</span>
                        @else
                            <span style="font-size:.66rem;color:rgb(107 114 128)">Analiz edildi</span>
                        @endif
                        @if (! $kilitli && ! $kullanim)
                            <button type="button" wire:click="fotografSil('{{ addslashes($f['yol']) }}')" title="Fotoğrafı kaldır" style="background:none;border:none;cursor:pointer;color:#ef4444;font-size:.75rem">✕</button>
                        @endif
                    </div>
                </div>
            @endforeach

            @unless ($kilitli)
                <label style="aspect-ratio:1;border-radius:.5rem;background:rgb(37 99 235);color:#fff;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:.3rem;cursor:pointer;font-size:.8rem;font-weight:600">
                    <span style="font-size:1.4rem">📷</span> Fotoğraf çek
                    <input type="file" wire:model="yeniFotograflar" accept="image/*" capture="environment" style="display:none">
                </label>
                <label style="aspect-ratio:1;border-radius:.5rem;background:rgb(59 130 246 / .1);border:1px solid rgb(59 130 246 / .4);color:rgb(37 99 235);display:flex;flex-direction:column;align-items:center;justify-content:center;gap:.3rem;cursor:pointer;font-size:.8rem;font-weight:600">
                    <span style="font-size:1.4rem">🖼</span> Galeriden seç
                    <input type="file" wire:model="yeniFotograflar" accept="image/*" multiple style="display:none">
                </label>
            @endunless
        </div>
        <div wire:loading wire:target="yeniFotograflar" style="font-size:.78rem;color:rgb(107 114 128);margin-top:.4rem">Yükleniyor…</div>

        @unless ($kilitli)
            <div style="display:flex;justify-content:space-between;align-items:center;gap:.5rem;margin-top:.8rem;padding:.6rem .8rem;border-radius:.6rem;background:rgb(107 114 128 / .08)">
                <span style="font-size:.85rem">{{ count($seciliFotograflar) }} fotoğraf seçildi</span>
                <x-filament::button color="primary" icon="heroicon-o-magnifying-glass" wire:click="fotograflariAnalizEt" wire:loading.attr="disabled"
                    :disabled="! $this->aiAktif || (! $seciliFotograflar && ! $bekleyenFoto)">
                    <span wire:loading.remove wire:target="fotograflariAnalizEt">Seçilenleri analiz et</span>
                    <span wire:loading wire:target="fotograflariAnalizEt">Analiz ediliyor…</span>
                </x-filament::button>
            </div>
        @endunless
    </div>

    {{-- 4. UYGUNSUZLUK ANALİZ ÖZETİ --}}
    <div>
        <div style="display:flex;justify-content:space-between;align-items:center;gap:.5rem;margin:.25rem 0 .5rem;flex-wrap:wrap">
            <span style="{{ $etiket }};font-size:.82rem">Uygunsuzluk Analiz Özeti
                @if ($bulgular)<span style="text-transform:none;font-weight:400">— {{ count($bulgular) }} madde · {{ $onayli }} onaylı · {{ $dofSayisi }} DÖF açılacak</span>@endif
            </span>
            @if (! $kilitli && $bulgular && $onayli < count($bulgular))
                <x-filament::button size="xs" color="gray" icon="heroicon-o-hand-thumb-up" wire:click="tumunuOnayla">Tümünü Onayla</x-filament::button>
            @endif
        </div>

        @if (! $bulgular)
            <div style="{{ $kutu }};text-align:center;color:rgb(107 114 128);font-size:.88rem">Henüz uygunsuzluk eklenmemiş</div>
        @endif

        <div style="display:flex;flex-direction:column;gap:.75rem">
            @foreach ($bulgular as $i => $b)
                @php
                    $skor = SahaAnalizi::fineKinneySkoru($b);
                    $bant = SahaAnalizi::fineKinneyBandi($skor);
                    $onaylandi = ($b['durum'] ?? 'bekliyor') === 'onaylandi';
                    $duzenlenebilir = ! $kilitli && ! $onaylandi;
                @endphp
                <div wire:key="bulgu-{{ $i }}" style="{{ $kutu }};border-left:4px solid {{ $bant['renk'] ?? 'rgb(59 130 246)' }}">
                    <div style="display:flex;justify-content:space-between;align-items:center;gap:.5rem;flex-wrap:wrap;margin-bottom:.6rem">
                        <div style="display:flex;gap:.4rem;align-items:center;flex-wrap:wrap">
                            <strong style="font-size:1.05rem">{{ $i + 1 }}</strong>
                            @if ($onaylandi)
                                <span style="font-size:.7rem;font-weight:600;padding:.12rem .45rem;border-radius:.3rem;background:rgb(22 163 74 / .12);color:#15803d">Onaylandı</span>
                            @else
                                <span style="font-size:.7rem;font-weight:600;padding:.12rem .45rem;border-radius:.3rem;background:rgb(245 158 11 / .12);color:#b45309">Onay bekliyor</span>
                            @endif
                            @if (($b['kaynak'] ?? 'manuel') !== 'manuel')
                                <span style="font-size:.66rem;padding:.1rem .35rem;border-radius:.25rem;border:1px solid rgb(59 130 246 / .4);color:rgb(37 99 235)">✦ AI</span>
                            @endif
                            @if ($b['kategori'] ?? null)
                                <span style="font-size:.7rem;padding:.12rem .45rem;border-radius:.3rem;background:rgb(107 114 128 / .1);color:rgb(107 114 128)">{{ $b['kategori'] }}</span>
                            @endif
                        </div>
                        <label style="display:flex;gap:.35rem;align-items:center;font-size:.75rem;font-weight:600;padding:.15rem .5rem;border-radius:.3rem;cursor:{{ $kilitli ? 'default' : 'pointer' }};background:{{ ($b['dof_acilacak'] ?? false) ? 'rgb(37 99 235)' : 'rgb(107 114 128 / .1)' }};color:{{ ($b['dof_acilacak'] ?? false) ? '#fff' : 'inherit' }}">
                            <input type="checkbox" wire:model.live="bulgular.{{ $i }}.dof_acilacak" @disabled($kilitli)> DÖF açılacak
                        </label>
                    </div>

                    <div style="display:flex;gap:.8rem;flex-wrap:wrap">
                        <div style="width:120px;flex-shrink:0">
                            @if ($b['foto_yolu'] ?? null)
                                <img src="{{ Storage::disk('public')->url($b['foto_yolu']) }}" style="width:120px;height:120px;object-fit:cover;border-radius:.5rem">
                            @else
                                <div style="width:120px;height:120px;border-radius:.5rem;border:1px dashed rgb(107 114 128 / .4);display:flex;align-items:center;justify-content:center;font-size:.72rem;color:rgb(107 114 128);text-align:center">Görsel yok</div>
                            @endif
                            @if ($duzenlenebilir && $fotograflar)
                                <select wire:model.live="bulgular.{{ $i }}.foto_yolu" style="margin-top:.3rem;width:120px;font-size:.7rem;padding:.2rem;border-radius:.3rem;border:1px solid rgb(107 114 128 / .3);background:transparent">
                                    <option value="">— görsel yok —</option>
                                    @foreach ($fotograflar as $n => $f)
                                        <option value="{{ $f['yol'] }}">Fotoğraf {{ $n + 1 }}</option>
                                    @endforeach
                                </select>
                            @endif
                        </div>

                        <div style="flex:1;min-width:220px">
                            @if ($duzenlenebilir)
                                <label style="{{ $etiket }}">Bina / Bölge (görsel alt yazısı)</label>
                                <input type="text" wire:model="bulgular.{{ $i }}.bina_bolge" style="{{ $girdi }};margin-bottom:.5rem">
                                <label style="{{ $etiket }}">Uygunsuzluk</label>
                                <textarea wire:model="bulgular.{{ $i }}.tespit" rows="3" style="{{ $girdi }};margin-bottom:.5rem"></textarea>
                                <label style="{{ $etiket }}">Alınması gereken önlemler (her satır bir madde)</label>
                                <textarea wire:model="bulgular.{{ $i }}.oneriler_metni" rows="4" style="{{ $girdi }};margin-bottom:.5rem"></textarea>
                                <label style="{{ $etiket }}">Mevzuat referansı</label>
                                <input type="text" wire:model="bulgular.{{ $i }}.yasal_gerekce" style="{{ $girdi }};margin-bottom:.5rem">
                            @else
                                @if ($b['bina_bolge'] ?? null)<div style="font-size:.75rem;color:rgb(107 114 128);font-style:italic;margin-bottom:.3rem">{{ $b['bina_bolge'] }}</div>@endif
                                <div style="font-size:.88rem;margin-bottom:.5rem">{{ $b['tespit'] }}</div>
                                <div style="{{ $etiket }}">Alınması gereken önlemler</div>
                                <ul style="list-style:disc;padding-left:1.1rem;font-size:.85rem;margin:.2rem 0 .5rem">
                                    @foreach (array_filter(preg_split('/\r\n|\r|\n/', (string) ($b['oneriler_metni'] ?? ''))) as $o)
                                        <li>{{ $o }}</li>
                                    @endforeach
                                </ul>
                                <div style="{{ $etiket }}">Mevzuat referansı</div>
                                <div style="font-size:.85rem;margin-bottom:.5rem">{{ ($b['yasal_gerekce'] ?? null) ?: '—' }}</div>
                            @endif
                        </div>
                    </div>

                    {{-- Fine-Kinney --}}
                    <div style="margin-top:.4rem;padding:.7rem;border-radius:.6rem;background:rgb(107 114 128 / .06);display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:.6rem;align-items:end">
                        @foreach (['siddet' => 'Şiddet', 'olasilik' => 'Olasılık', 'frekans' => 'Etki (Sıklık)'] as $olcek => $ad)
                            <div>
                                <label style="{{ $etiket }}">{{ $ad }}</label>
                                @if ($duzenlenebilir)
                                    <select wire:model.live="bulgular.{{ $i }}.{{ $olcek }}" style="{{ $girdi }};padding:.35rem .5rem;font-size:.78rem">
                                        <option value="">—</option>
                                        @foreach ($fk[$olcek] as $deger => $acik)
                                            <option value="{{ $deger }}">{{ $deger }} — {{ $acik }}</option>
                                        @endforeach
                                    </select>
                                @else
                                    <div style="font-size:.85rem"><strong>{{ $b[$olcek] ?? '—' }}</strong> <span style="color:rgb(107 114 128);font-size:.75rem">{{ SahaAnalizi::olcekEtiketi($olcek, $b[$olcek] ?? null) }}</span></div>
                                @endif
                            </div>
                        @endforeach
                        <div>
                            <label style="{{ $etiket }}">Risk Skoru</label>
                            @if ($bant)
                                <div style="display:flex;gap:.4rem;align-items:center;flex-wrap:wrap">
                                    <span style="font-weight:800;font-size:1.05rem;color:#fff;background:{{ $bant['renk'] }};padding:.25rem .7rem;border-radius:.35rem">{{ rtrim(rtrim(number_format($skor, 2, '.', ''), '0'), '.') }}</span>
                                    <span style="font-size:.75rem;font-weight:600">{{ $bant['ad'] }}</span>
                                </div>
                            @else
                                <div style="font-size:.78rem;color:rgb(107 114 128)">Üç değeri seçin</div>
                            @endif
                        </div>
                    </div>

                    {{-- İyileştir paneli --}}
                    @if ($iyilestirIndex === $i && ! $kilitli)
                        <div style="margin-top:.6rem;padding:.7rem;border-radius:.6rem;border:1px solid rgb(59 130 246 / .4);background:rgb(59 130 246 / .05)">
                            <label style="{{ $etiket }}">Yapay zekâya not (isteğe bağlı)</label>
                            <textarea wire:model="iyilestirNotu" rows="2" placeholder="Örn: önlemleri daha kısa yaz, Yapı İşleri Yönetmeliği madde numarasını ekle" style="{{ $girdi }}"></textarea>
                            <div style="display:flex;gap:.4rem;margin-top:.5rem">
                                <x-filament::button size="sm" icon="heroicon-o-sparkles" wire:click="bulguIyilestir" wire:loading.attr="disabled" :disabled="! $this->aiAktif">
                                    <span wire:loading.remove wire:target="bulguIyilestir">Yeniden Yaz</span>
                                    <span wire:loading wire:target="bulguIyilestir">Yazılıyor…</span>
                                </x-filament::button>
                                <x-filament::button size="sm" color="gray" wire:click="$set('iyilestirIndex', null)">Vazgeç</x-filament::button>
                            </div>
                        </div>
                    @endif

                    @unless ($kilitli)
                        <div style="display:grid;grid-template-columns:1fr 1fr;gap:.45rem;margin-top:.7rem">
                            @if ($onaylandi)
                                <x-filament::button color="gray" icon="heroicon-o-pencil" wire:click="bulguOnayiniKaldir({{ $i }})">Düzelt</x-filament::button>
                            @else
                                <x-filament::button color="success" icon="heroicon-o-hand-thumb-up" wire:click="bulguOnayla({{ $i }})">Onayla</x-filament::button>
                            @endif
                            <x-filament::button color="gray" icon="heroicon-o-hand-thumb-down" wire:click="iyilestirAc({{ $i }})" :disabled="! $this->aiAktif">İyileştir</x-filament::button>
                            <x-filament::button color="danger" icon="heroicon-o-trash" wire:click="bulguSil({{ $i }})" wire:confirm="Bu madde silinsin mi?" style="grid-column:1 / -1">Sil</x-filament::button>
                        </div>
                    @endunless
                </div>
            @endforeach
        </div>
    </div>

    {{-- 5. MEVZUAT REFERANSLARI --}}
    <div style="{{ $kutu }}">
        <div style="display:flex;justify-content:space-between;align-items:center;gap:.5rem;flex-wrap:wrap">
            <strong>MEVZUAT REFERANSLARI</strong>
            @if (! $kilitli && $bulgular)
                <x-filament::button size="xs" color="gray" wire:click="bulgulardanReferansTopla">Maddelerden Topla</x-filament::button>
            @endif
        </div>
        <p style="font-size:.75rem;color:rgb(107 114 128);margin:.2rem 0 .6rem">Bağlantısı olan referanslar PDF'te QR kodla basılır.</p>

        @forelse ($mevzuatReferanslari as $r => $ref)
            <div style="display:flex;gap:.6rem;align-items:center;padding:.45rem 0;border-top:1px solid rgb(107 114 128 / .15)">
                <span style="font-size:1.1rem">{{ $ref['url'] ? '▣' : '§' }}</span>
                <div style="flex:1;min-width:0">
                    <div style="font-size:.85rem">{{ $ref['ad'] }}</div>
                    @if ($ref['url'])<a href="{{ $ref['url'] }}" target="_blank" style="font-size:.72rem;color:rgb(37 99 235);word-break:break-all">{{ $ref['url'] }}</a>@endif
                </div>
                @unless ($kilitli)
                    <button type="button" wire:click="referansSil({{ $r }})" style="background:none;border:none;cursor:pointer;color:#ef4444">✕</button>
                @endunless
            </div>
        @empty
            <div style="text-align:center;color:rgb(107 114 128);font-size:.85rem;padding:.8rem">Herhangi bir referans mevcut değil.</div>
        @endforelse

        @unless ($kilitli)
            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:.5rem;margin-top:.6rem">
                <input type="text" wire:model="yeniRefAd" placeholder="Mevzuat adı / maddesi" style="{{ $girdi }}">
                <input type="url" wire:model="yeniRefUrl" placeholder="https://www.mevzuat.gov.tr/… (isteğe bağlı)" style="{{ $girdi }}">
            </div>
            <div style="display:flex;gap:.4rem;flex-wrap:wrap;margin-top:.5rem;align-items:center">
                <x-filament::button size="sm" icon="heroicon-o-plus" wire:click="referansEkle">Referans Ekle</x-filament::button>
                <select x-data x-on:change="if ($event.target.value) { $wire.hazirReferansEkle($event.target.value); $event.target.value = '' }"
                    style="padding:.4rem .6rem;border-radius:.5rem;border:1px solid rgb(107 114 128 / .35);background:transparent;font-size:.8rem;max-width:100%">
                    <option value="">Hazır listeden ekle…</option>
                    @foreach ($this->hazirReferanslar as $anahtar => $r)
                        <option value="{{ $anahtar }}">{{ $r['ad'] }}</option>
                    @endforeach
                </select>
            </div>
        @endunless
    </div>

    {{-- 6. ALT AKSİYONLAR --}}
    @if ($this->firma && ! $kilitli)
        <div style="display:flex;flex-direction:column;gap:.5rem">
            <x-filament::button color="primary" icon="heroicon-o-check" wire:click="tamamlaPaneliAc" :disabled="! $bulgular">Raporu Tamamla</x-filament::button>
            <x-filament::button color="gray" icon="heroicon-o-bookmark" wire:click="taslagiKaydet">Taslağı Kaydet</x-filament::button>
        </div>
    @elseif (! $this->firma)
        <p style="font-size:.85rem;color:#f59e0b">Raporu kaydetmek ve PDF almak için Genel Bilgiler'den bir firma seçin (fotoğraf analizi firma seçmeden de yapılabilir).</p>
    @endif

    {{-- 7. RAPORU TAMAMLA PANELİ --}}
    @if ($tamamlaPaneli && ! $kilitli)
        @php $hazir = collect($this->dofIndexleri())->filter(fn ($i) => $this->dofHazirMi($i))->count(); @endphp
        <div style="position:fixed;inset:0;z-index:50;background:rgb(0 0 0 / .45);display:flex;align-items:flex-end;justify-content:center">
            <div class="sgr-panel" style="width:100%;max-width:640px;max-height:90vh;display:flex;flex-direction:column;border-radius:1rem 1rem 0 0">
                <div style="padding:1rem 1.2rem .6rem;display:flex;justify-content:space-between;gap:.5rem">
                    <div>
                        <div style="font-size:1.15rem;font-weight:700">Raporu tamamla</div>
                        <div style="font-size:.82rem;color:rgb(107 114 128)">
                            @if ($dofSayisi)
                                İşaretlediğiniz {{ $dofSayisi }} uygunsuzluk için DÖF açılacak. Bilgileri tamamlayın veya işareti kaldırın.
                            @else
                                DÖF açılacak işaretli madde yok. Tamamlandıktan sonra rapor düzenlenemez.
                            @endif
                        </div>
                    </div>
                    <button type="button" wire:click="$set('tamamlaPaneli', false)" style="background:none;border:none;cursor:pointer;font-size:1.2rem;color:rgb(107 114 128)">✕</button>
                </div>

                <div style="overflow-y:auto;padding:0 1.2rem 1rem;display:flex;flex-direction:column;gap:.8rem">
                    @if ($dofSayisi)
                        <div style="padding:.8rem;border-radius:.7rem;background:rgb(59 130 246 / .07);border:1px solid rgb(59 130 246 / .2)">
                            <div style="font-weight:600;font-size:.85rem;margin-bottom:.35rem">Sorumlu, hepsine</div>
                            <div style="display:grid;grid-template-columns:1fr 1fr;border-radius:.5rem;overflow:hidden;border:1px solid rgb(107 114 128 / .3)">
                                @foreach (['kendim' => 'Kendim', 'dis' => 'Dış kişi'] as $tip => $ad)
                                    <button type="button" wire:click="$set('topluSorumluTipi', '{{ $tip }}')" style="padding:.5rem;cursor:pointer;border:none;background:{{ $topluSorumluTipi === $tip ? 'rgb(37 99 235)' : 'transparent' }};color:{{ $topluSorumluTipi === $tip ? '#fff' : 'inherit' }}">{{ $ad }}</button>
                                @endforeach
                            </div>
                            @if ($topluSorumluTipi === 'dis')
                                <input type="text" wire:model="topluSorumlu" placeholder="Sorumlu adı soyadı / unvanı" style="{{ $girdi }};margin-top:.4rem">
                            @else
                                <div style="font-size:.75rem;color:rgb(107 114 128);margin-top:.3rem">Bana atanır: {{ auth()->user()?->name }}</div>
                            @endif
                            <div style="font-weight:600;font-size:.85rem;margin:.6rem 0 .3rem">Termin, hepsine</div>
                            <input type="date" wire:model="topluTermin" style="{{ $girdi }}">
                            <x-filament::button color="gray" wire:click="hepsineUygula" style="width:100%;margin-top:.6rem">Hepsine uygula</x-filament::button>
                        </div>

                        @foreach ($this->dofIndexleri() as $i)
                            @php
                                $b = $bulgular[$i];
                                $skor = SahaAnalizi::fineKinneySkoru($b);
                            @endphp
                            <div wire:key="dof-{{ $i }}" style="padding:.8rem;border-radius:.7rem;border:1px solid {{ $this->dofHazirMi($i) ? 'rgb(22 163 74 / .5)' : 'rgb(59 130 246 / .5)' }}">
                                <div style="display:flex;justify-content:space-between;gap:.5rem;margin-bottom:.4rem">
                                    <strong style="font-size:.85rem">{{ $i + 1 }}. {{ \Illuminate\Support\Str::limit($b['tespit'], 90) }}</strong>
                                    <button type="button" wire:click="dofIsaretiniKaldir({{ $i }})" style="background:none;border:none;cursor:pointer;font-size:.75rem;color:#ef4444;white-space:nowrap">İşareti kaldır</button>
                                </div>
                                <div style="font-size:.72rem;color:rgb(107 114 128);margin-bottom:.4rem">Düzeltici faaliyet, "alınması gereken önlemler" alanından doldurulur.</div>
                                <div style="font-weight:600;font-size:.8rem;margin-bottom:.25rem">Sorumlu</div>
                                <div style="display:grid;grid-template-columns:1fr 1fr;border-radius:.5rem;overflow:hidden;border:1px solid rgb(107 114 128 / .3)">
                                    @foreach (['kendim' => 'Kendim', 'dis' => 'Dış kişi'] as $tip => $ad)
                                        <button type="button" wire:click="$set('bulgular.{{ $i }}.dof_sorumlu_tipi', '{{ $tip }}')" style="padding:.45rem;cursor:pointer;border:none;background:{{ ($b['dof_sorumlu_tipi'] ?? 'kendim') === $tip ? 'rgb(37 99 235)' : 'transparent' }};color:{{ ($b['dof_sorumlu_tipi'] ?? 'kendim') === $tip ? '#fff' : 'inherit' }}">{{ $ad }}</button>
                                    @endforeach
                                </div>
                                @if (($b['dof_sorumlu_tipi'] ?? 'kendim') === 'dis')
                                    <input type="text" wire:model.live.debounce.400ms="bulgular.{{ $i }}.dof_sorumlu" placeholder="Sorumlu adı soyadı / unvanı" style="{{ $girdi }};margin-top:.4rem">
                                @else
                                    <div style="font-size:.75rem;color:rgb(107 114 128);margin-top:.3rem">Bana atanır: {{ auth()->user()?->name }}</div>
                                @endif
                                <div style="display:grid;grid-template-columns:1fr 1fr;gap:.5rem;margin-top:.5rem">
                                    <div>
                                        <div style="font-weight:600;font-size:.8rem;margin-bottom:.2rem">Termin</div>
                                        <input type="date" wire:model.live="bulgular.{{ $i }}.dof_termin" style="{{ $girdi }}">
                                    </div>
                                    <div>
                                        <div style="font-weight:600;font-size:.8rem;margin-bottom:.2rem">Hedef artık skor <span style="font-weight:400;color:rgb(107 114 128)">(isteğe bağlı)</span></div>
                                        <input type="number" step="0.1" min="0" wire:model="bulgular.{{ $i }}.dof_hedef_skor" style="{{ $girdi }}">
                                    </div>
                                </div>
                                <div style="font-size:.72rem;color:rgb(107 114 128);margin-top:.25rem">Şu anki skor {{ $skor !== null ? number_format($skor, 2, '.', '') : '—' }}</div>
                            </div>
                        @endforeach
                    @endif
                </div>

                <div style="padding:.8rem 1.2rem;border-top:1px solid rgb(107 114 128 / .2);display:flex;align-items:center;gap:.6rem">
                    @if ($dofSayisi)
                        <span style="font-size:.8rem;color:rgb(107 114 128)">{{ $hazir }} / {{ $dofSayisi }} hazır</span>
                    @endif
                    <x-filament::button color="gray" wire:click="$set('tamamlaPaneli', false)" style="margin-left:auto">İptal</x-filament::button>
                    <x-filament::button color="success" wire:click="raporuTamamla" :disabled="$hazir < $dofSayisi"
                        wire:confirm="Raporu tamamlamak istediğinizden emin misiniz? Bu işlem geri alınamaz ve rapor artık düzenlenemez.">
                        {{ $dofSayisi ? "Tamamla ve {$dofSayisi} DÖF aç" : 'Evet, Tamamla' }}
                    </x-filament::button>
                </div>
            </div>
        </div>
    @endif

    {{-- 8. GEÇMİŞ RAPORLAR --}}
    @if ($this->firma && $this->gecmisKayitlar->isNotEmpty())
        <x-filament::section icon="heroicon-o-clock" icon-color="gray" collapsible>
            <x-slot name="heading">Bu Firmanın Saha Gözlem Raporları</x-slot>
            <div style="overflow-x:auto">
                <table style="width:100%;border-collapse:collapse;font-size:.82rem">
                    <tr>
                        @foreach (['Belge No', 'Durum', 'Alan/Bölge', 'Tarih', 'Madde', ''] as $baslik)
                            <th style="text-align:left;padding:.35rem .5rem;border-bottom:1px solid rgb(107 114 128 / .3)">{{ $baslik }}</th>
                        @endforeach
                    </tr>
                    @foreach ($this->gecmisKayitlar as $k)
                        <tr style="{{ $k->id === $kayitId ? 'background:rgb(59 130 246 / .06)' : '' }}">
                            <td style="padding:.35rem .5rem">{{ $k->belge_no }}</td>
                            <td style="padding:.35rem .5rem">
                                @if ($k->tamamlandiMi())
                                    <span style="color:#15803d">Tamamlandı</span>
                                @else
                                    <span style="color:#b45309">Taslak</span>
                                @endif
                            </td>
                            <td style="padding:.35rem .5rem">{{ $k->alan_bolge ?: '—' }}</td>
                            <td style="padding:.35rem .5rem">{{ $k->rapor_tarihi?->format('d.m.Y') }}</td>
                            <td style="padding:.35rem .5rem">{{ count($k->bulgular ?? []) }}</td>
                            <td style="padding:.35rem .5rem;text-align:right;white-space:nowrap">
                                @if ($k->id !== $kayitId)
                                    <x-filament::button size="xs" color="primary" wire:click="raporAc({{ $k->id }})">{{ $k->tamamlandiMi() ? 'Görüntüle' : 'Devam Et' }}</x-filament::button>
                                @endif
                                <x-filament::button size="xs" color="gray" wire:click="gecmisPdf({{ $k->id }})">PDF</x-filament::button>
                                <x-filament::button size="xs" color="danger" wire:click="gecmisSil({{ $k->id }})" wire:confirm="Rapor silinsin mi?">Sil</x-filament::button>
                            </td>
                        </tr>
                    @endforeach
                </table>
            </div>
        </x-filament::section>
    @endif

    <div style="{{ $kutu }};background:rgb(107 114 128 / .05);font-size:.78rem;color:rgb(107 114 128)">
        Yapay zekâ çıktıları ön değerlendirmedir; saha koşullarının İSG profesyoneli tarafından doğrulanması
        olmadan resmî tespit, ölçüm veya uygunluk beyanı yerine geçmez. Bu nedenle her madde rapora girmeden önce onaylanmalıdır.
    </div>
</x-filament-panels::page>
