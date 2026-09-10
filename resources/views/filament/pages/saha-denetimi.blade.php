@php
    $turuncu = 'rgb(234 88 12)';
    $kutu = 'border:1px solid rgb(107 114 128 / .3);border-radius:.75rem;padding:1rem';
    $sonucRengi = fn ($s) => match ($s) {
        'uygun' => ['#16a34a', 'rgb(22 163 74 / .1)'],
        'uygun_degil' => ['#dc2626', 'rgb(220 38 38 / .1)'],
        'uygulanamaz' => ['rgb(107 114 128)', 'rgb(107 114 128 / .1)'],
        default => ['rgb(107 114 128)', 'transparent'],
    };
    $canli = $this->canliSonuc;
@endphp

<x-filament-panels::page>
    <p style="font-size:.85rem;color:rgb(107 114 128);margin-top:-.5rem">
        Firma ve denetim bilgilerini girin, 9 kategorideki 41 maddeyi Uygun / Uygun Değil /
        Uygulanamaz olarak işaretleyin; "Denetimi Tamamla" ile PDF raporu indirin.
    </p>

    @include('filament.pages.partials.eksik-firmalar', ['kriterAnahtari' => 'saha_denetim_formu'])

    @if ($this->firma && $taslakYuklendi)
        <div style="border:1px solid {{ $turuncu }};background:rgb(234 88 12 / .08);border-radius:.75rem;padding:.85rem 1rem;display:flex;justify-content:space-between;align-items:center;gap:1rem;flex-wrap:wrap">
            <div style="font-size:.83rem">
                <strong>Kaydedilmiş taslağınız yüklendi</strong> — {{ $this->firma->unvan }}.
                Yanlış firma veya işse temizleyin.
            </div>
            <x-filament::button size="xs" color="gray" wire:click="taslakTemizle">Taslağı Temizle</x-filament::button>
        </div>
    @endif

    {{-- 1. DENETİM BİLGİLERİ --}}
    <x-filament::section icon="heroicon-o-clipboard-document-list" icon-color="warning">
        <x-slot name="heading">1. Denetim Bilgileri</x-slot>

        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:1rem">
            <div>
                <label style="font-weight:600;font-size:.82rem">Firma Seçin <span style="color:#ef4444">*</span></label>
                <select wire:model.live="firmaId"
                    style="margin-top:.3rem;width:100%;padding:.55rem .75rem;border-radius:.5rem;border:1px solid rgb(107 114 128 / .35);background:transparent">
                    <option value="">— Firma seçin —</option>
                    @foreach ($this->firmalar as $id => $ad)
                        <option value="{{ $id }}">{{ $ad }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label style="font-weight:600;font-size:.82rem">İş Tanımı</label>
                <input type="text" wire:model="isTanimi"
                    style="margin-top:.3rem;width:100%;padding:.5rem .75rem;border-radius:.5rem;border:1px solid rgb(107 114 128 / .35);background:transparent">
            </div>
            <div>
                <label style="font-weight:600;font-size:.82rem">Şantiye Adı</label>
                <input type="text" wire:model="santiyeAdi"
                    style="margin-top:.3rem;width:100%;padding:.5rem .75rem;border-radius:.5rem;border:1px solid rgb(107 114 128 / .35);background:transparent">
            </div>
            <div>
                <label style="font-weight:600;font-size:.82rem">Şantiye Sorumlusu</label>
                <input type="text" wire:model="santiyeSorumlusu"
                    style="margin-top:.3rem;width:100%;padding:.5rem .75rem;border-radius:.5rem;border:1px solid rgb(107 114 128 / .35);background:transparent">
            </div>
            <div>
                <label style="font-weight:600;font-size:.82rem">İş Referans No</label>
                <input type="text" wire:model="isReferansNo"
                    style="margin-top:.3rem;width:100%;padding:.5rem .75rem;border-radius:.5rem;border:1px solid rgb(107 114 128 / .35);background:transparent">
            </div>
            <div>
                <label style="font-weight:600;font-size:.82rem">Denetim Tarihi</label>
                <input type="date" wire:model="denetimTarihi"
                    style="margin-top:.3rem;width:100%;padding:.5rem .75rem;border-radius:.5rem;border:1px solid rgb(107 114 128 / .35);background:transparent">
            </div>
            <div>
                <label style="font-weight:600;font-size:.82rem">Denetim Saati</label>
                <input type="time" wire:model="denetimSaati"
                    style="margin-top:.3rem;width:100%;padding:.5rem .75rem;border-radius:.5rem;border:1px solid rgb(107 114 128 / .35);background:transparent">
            </div>
            <div>
                <label style="font-weight:600;font-size:.82rem">Denetçi</label>
                <input type="text" wire:model="denetciAdi"
                    style="margin-top:.3rem;width:100%;padding:.5rem .75rem;border-radius:.5rem;border:1px solid rgb(107 114 128 / .35);background:transparent">
            </div>
            <div>
                <label style="font-weight:600;font-size:.82rem">Sektör (opsiyonel)</label>
                <select wire:model.live="sektorAnahtari"
                    style="margin-top:.3rem;width:100%;padding:.55rem .75rem;border-radius:.5rem;border:1px solid rgb(107 114 128 / .35);background:transparent">
                    <option value="">— Genel (sektörsüz) —</option>
                    @foreach ($this->sektorler as $anahtar => $ad)
                        <option value="{{ $anahtar }}">{{ $ad }}</option>
                    @endforeach
                </select>
                <p style="font-size:.72rem;color:rgb(107 114 128);margin-top:.2rem">Sektör seçilince aşağıya o sektöre eklediğiniz özel maddeler de dahil olur.</p>
            </div>
        </div>

        @if ($this->firma && ! $this->firma->igu)
            <p style="font-size:.78rem;color:rgb(107 114 128);margin-top:.75rem">
                Kaşe yok — <a href="{{ \App\Filament\Resources\IsgProfesyonelis\IsgProfesyoneliResource::getUrl() }}" style="color:{{ $turuncu }};text-decoration:underline">İSG Profesyonelleri</a>
                panelinden İGU ekleyip Firma düzenleme sayfasından atayın.
            </p>
        @endif
    </x-filament::section>

    {{-- SEKTÖRE ÖZEL KONTROL MADDELERİ (firma seçmeden de eklenebilir/yönetilebilir) --}}
    <x-filament::section icon="heroicon-o-squares-plus" icon-color="warning" collapsible collapsed>
        <x-slot name="heading">Kendi Kontrol Başlığı / Maddesi Ekle</x-slot>
        <x-slot name="description">
            İnşaat, metal, orman gibi sektörlere özel başlık (örn. "Kazı Kontrolü", "İskele") ve
            madde ekleyin. Sektör seçmezseniz madde tüm denetimlerde görünür; sektör seçerseniz
            yalnızca üstte o sektör seçiliyken kontrol listesine dahil olur. Var olan bir başlık
            adını yazarsanız madde o başlığın altına eklenir.
        </x-slot>

        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:.6rem;margin-bottom:.6rem">
            <div>
                <label style="font-size:.75rem;font-weight:600">Sektör</label>
                <select wire:model="yeniOzelSektorAnahtari"
                    style="margin-top:.2rem;width:100%;padding:.4rem .6rem;border-radius:.4rem;border:1px solid rgb(107 114 128 / .3);background:transparent;font-size:.8rem">
                    <option value="">Tüm Sektörler</option>
                    @foreach ($this->sektorler as $anahtar => $ad)
                        <option value="{{ $anahtar }}">{{ $ad }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label style="font-size:.75rem;font-weight:600">Başlık (Kategori) Adı</label>
                <input list="saha-denetimi-kategori-oneri" wire:model="yeniOzelKategoriAdi" placeholder="Örn: Kazı Kontrolü"
                    style="margin-top:.2rem;width:100%;padding:.4rem .6rem;border-radius:.4rem;border:1px solid rgb(107 114 128 / .3);background:transparent;font-size:.8rem">
                <datalist id="saha-denetimi-kategori-oneri">
                    @foreach ($this->kategoriler as $k)
                        <option value="{{ $k['ad'] }}"></option>
                    @endforeach
                </datalist>
            </div>
            <div style="grid-column:span 2">
                <label style="font-size:.75rem;font-weight:600">Madde İfadesi</label>
                <input type="text" wire:model="yeniOzelIfade" placeholder="Örn: Kazı şevi/iksa sistemi güvenli ve yönetmeliğe uygun mu?"
                    style="margin-top:.2rem;width:100%;padding:.4rem .6rem;border-radius:.4rem;border:1px solid rgb(107 114 128 / .3);background:transparent;font-size:.8rem">
            </div>
        </div>

        <div style="display:flex;gap:1rem;align-items:center;margin-bottom:.75rem;flex-wrap:wrap">
            <label style="display:flex;align-items:center;gap:.3rem;font-size:.8rem;cursor:pointer">
                <input type="checkbox" wire:model="yeniOzelKritik"> Kritik
            </label>
            <label style="display:flex;align-items:center;gap:.3rem;font-size:.8rem;cursor:pointer">
                <input type="checkbox" wire:model="yeniOzelUygulanamazIzni"> "Uygulanamaz" seçeneği olsun
            </label>
            <x-filament::button size="sm" wire:click="ozelMaddeEkle">Madde Ekle</x-filament::button>
        </div>

        @if ($this->ozelMaddeler->isNotEmpty())
            <table style="width:100%;border-collapse:collapse;font-size:.78rem">
                <tr>
                    <th style="text-align:left;padding:.3rem .5rem;border-bottom:1px solid rgb(107 114 128 / .3)">Sektör</th>
                    <th style="text-align:left;padding:.3rem .5rem;border-bottom:1px solid rgb(107 114 128 / .3)">Başlık</th>
                    <th style="text-align:left;padding:.3rem .5rem;border-bottom:1px solid rgb(107 114 128 / .3)">Madde</th>
                    <th style="text-align:left;padding:.3rem .5rem;border-bottom:1px solid rgb(107 114 128 / .3)">Kritik</th>
                    <th style="border-bottom:1px solid rgb(107 114 128 / .3)"></th>
                </tr>
                @foreach ($this->ozelMaddeler as $m)
                    <tr>
                        <td style="padding:.3rem .5rem">{{ $m->sektorEtiketi() }}</td>
                        <td style="padding:.3rem .5rem">{{ $m->kategori_ad }}</td>
                        <td style="padding:.3rem .5rem">{{ $m->ifade }}</td>
                        <td style="padding:.3rem .5rem">{{ $m->kritik ? 'Evet' : '—' }}</td>
                        <td style="padding:.3rem .5rem;text-align:right">
                            <button type="button" wire:click="ozelMaddeSil({{ $m->id }})" style="color:#ef4444;cursor:pointer;background:none;border:none">✕</button>
                        </td>
                    </tr>
                @endforeach
            </table>
        @endif
    </x-filament::section>

    @if ($this->firma)
        {{-- CANLI SONUÇ --}}
        <div style="{{ $kutu }};background:rgb(234 88 12 / .05);display:flex;gap:2rem;align-items:center;flex-wrap:wrap">
            <div><strong style="font-size:1.3rem">%{{ $canli['yuzde'] }}</strong> <span style="font-size:.8rem;color:rgb(107 114 128)">uygunluk</span></div>
            <div style="font-size:.85rem">Uygun: <strong style="color:#16a34a">{{ $canli['uygun'] }}</strong></div>
            <div style="font-size:.85rem">Uygun Değil: <strong style="color:#dc2626">{{ $canli['uygun_degil'] }}</strong></div>
            @if ($canli['kritik_var'])
                <div style="font-size:.8rem;color:#dc2626;font-weight:600">⚠ Kritik uygunsuzluk var</div>
            @endif
        </div>

        {{-- 2. KONTROL LİSTESİ --}}
        @foreach ($this->kategoriler as $kategoriAnahtari => $kategori)
            <x-filament::section icon="heroicon-o-list-bullet" icon-color="warning">
                <x-slot name="heading">{{ $kategori['ad'] }}</x-slot>

                <div style="display:flex;flex-direction:column;gap:.9rem">
                    @foreach ($kategori['maddeler'] as $madde)
                        @php
                            $kod = $madde['kod'];
                            $anahtar = $this->anahtar($kod);
                            $cevap = $cevaplar[$anahtar] ?? ['sonuc' => null, 'aciklama' => null];
                            [$renk, $bgRenk] = $sonucRengi($cevap['sonuc']);
                            $ifade = str_replace('{FIRMA}', $this->firma->unvan, $madde['ifade']);
                        @endphp
                        <div style="{{ $kutu }};border-left:4px solid {{ $renk }}">
                            <div style="display:flex;gap:.5rem;align-items:baseline;flex-wrap:wrap;margin-bottom:.5rem">
                                <span style="font-size:.72rem;font-weight:700;color:rgb(107 114 128)">{{ $kod }}</span>
                                @if ($madde['kritik'])
                                    <span style="font-size:.68rem;font-weight:700;padding:.1rem .4rem;border-radius:.3rem;color:#dc2626;background:rgb(220 38 38 / .1)">KRİTİK</span>
                                @endif
                                <span style="font-size:.85rem">{{ $ifade }}</span>
                            </div>
                            <div style="display:flex;gap:.4rem;flex-wrap:wrap">
                                <button type="button" wire:click="cevapVer('{{ $kod }}','uygun')"
                                    style="padding:.35rem .7rem;border-radius:.4rem;cursor:pointer;font-size:.78rem;
                                        border:1px solid {{ $cevap['sonuc'] === 'uygun' ? '#16a34a' : 'rgb(107 114 128 / .3)' }};
                                        background:{{ $cevap['sonuc'] === 'uygun' ? 'rgb(22 163 74 / .1)' : 'transparent' }};
                                        color:{{ $cevap['sonuc'] === 'uygun' ? '#16a34a' : 'inherit' }}">✓ Uygun</button>
                                <button type="button" wire:click="cevapVer('{{ $kod }}','uygun_degil')"
                                    style="padding:.35rem .7rem;border-radius:.4rem;cursor:pointer;font-size:.78rem;
                                        border:1px solid {{ $cevap['sonuc'] === 'uygun_degil' ? '#dc2626' : 'rgb(107 114 128 / .3)' }};
                                        background:{{ $cevap['sonuc'] === 'uygun_degil' ? 'rgb(220 38 38 / .1)' : 'transparent' }};
                                        color:{{ $cevap['sonuc'] === 'uygun_degil' ? '#dc2626' : 'inherit' }}">✕ Uygun Değil</button>
                                @if ($madde['uygulanamaz_izni'])
                                    <button type="button" wire:click="cevapVer('{{ $kod }}','uygulanamaz')"
                                        style="padding:.35rem .7rem;border-radius:.4rem;cursor:pointer;font-size:.78rem;
                                            border:1px solid {{ $cevap['sonuc'] === 'uygulanamaz' ? 'rgb(107 114 128)' : 'rgb(107 114 128 / .3)' }};
                                            background:{{ $cevap['sonuc'] === 'uygulanamaz' ? 'rgb(107 114 128 / .15)' : 'transparent' }}">Uygulanamaz</button>
                                @endif
                            </div>

                            @if ($cevap['sonuc'] === 'uygun_degil')
                                <div style="margin-top:.6rem">
                                    <label style="font-size:.75rem;font-weight:600;color:#dc2626">Uygunsuzluk açıklaması <span style="color:#ef4444">*</span></label>
                                    <textarea wire:model="cevaplar.{{ $anahtar }}.aciklama" rows="2"
                                        style="width:100%;margin-top:.2rem;padding:.4rem .6rem;border-radius:.4rem;border:1px solid rgb(220 38 38 / .4);background:transparent;font-size:.8rem"></textarea>
                                </div>
                            @endif

                            @php $yuklenenFoto = $fotoYuklemeleri[$anahtar] ?? null; @endphp
                            <div style="margin-top:.5rem;display:flex;align-items:center;gap:.5rem;flex-wrap:wrap">
                                <span style="font-size:.72rem;font-weight:600;color:rgb(107 114 128)">📷 Fotoğraf</span>
                                @if ($yuklenenFoto)
                                    <img src="{{ $yuklenenFoto->temporaryUrl() }}" style="width:44px;height:44px;object-fit:cover;border-radius:.3rem;border:1px solid rgb(107 114 128 / .4)">
                                @elseif (! empty($cevap['foto_yolu']))
                                    <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($cevap['foto_yolu']) }}" style="width:44px;height:44px;object-fit:cover;border-radius:.3rem;border:1px solid rgb(107 114 128 / .4)">
                                @endif
                                <input type="file" wire:model="fotoYuklemeleri.{{ $anahtar }}" accept="image/*" style="font-size:.75rem">
                                @if ($yuklenenFoto || ! empty($cevap['foto_yolu']))
                                    <button type="button" wire:click="fotoKaldir('{{ $kod }}')"
                                        style="color:#ef4444;background:none;border:none;cursor:pointer;font-size:.75rem">✕ kaldır</button>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </x-filament::section>
        @endforeach

        {{-- 3. EKİP, KKD VE NOTLAR --}}
        <x-filament::section icon="heroicon-o-user-group" icon-color="warning">
            <x-slot name="heading">Ekip Üyeleri, KKD ve Notlar</x-slot>

            @if ($this->calisanlar->isNotEmpty())
                <div style="font-weight:600;font-size:.82rem;margin-bottom:.4rem">Firma Çalışanları</div>
                <div style="display:flex;gap:.4rem;flex-wrap:wrap;margin-bottom:1rem">
                    @foreach ($this->calisanlar as $c)
                        <button type="button" wire:click="ekipHizliEkle({{ $c->id }})"
                            style="padding:.3rem .6rem;border-radius:.4rem;border:1px solid rgb(107 114 128 / .3);background:transparent;cursor:pointer;font-size:.78rem">
                            + {{ $c->ad_soyad }}
                        </button>
                    @endforeach
                </div>
            @endif

            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr)) auto;gap:.5rem;align-items:end;margin-bottom:.5rem">
                <div>
                    <input type="text" wire:model="yeniEkipAdSoyad" placeholder="Ad Soyad"
                        style="width:100%;padding:.45rem .6rem;border-radius:.4rem;border:1px solid rgb(107 114 128 / .3);background:transparent;font-size:.82rem">
                </div>
                <div>
                    <input type="text" wire:model="yeniEkipGorev" placeholder="Görevi"
                        style="width:100%;padding:.45rem .6rem;border-radius:.4rem;border:1px solid rgb(107 114 128 / .3);background:transparent;font-size:.82rem">
                </div>
                <x-filament::button size="sm" wire:click="ekipEkle">Ekle</x-filament::button>
            </div>

            <div style="display:flex;gap:.6rem;flex-wrap:wrap;margin-bottom:1rem">
                @foreach ($this->kkdSecenekleri as $kkd)
                    <label style="display:flex;align-items:center;gap:.3rem;font-size:.78rem;cursor:pointer">
                        <input type="checkbox" wire:model="yeniEkipKkd" value="{{ $kkd }}"> {{ $kkd }}
                    </label>
                @endforeach
            </div>

            @if ($ekipUyeleri)
                <table style="width:100%;border-collapse:collapse;font-size:.8rem;margin-bottom:1rem">
                    <tr>
                        <th style="text-align:left;padding:.3rem .5rem;border-bottom:1px solid rgb(107 114 128 / .3)">Ad Soyad</th>
                        <th style="text-align:left;padding:.3rem .5rem;border-bottom:1px solid rgb(107 114 128 / .3)">Görev</th>
                        <th style="text-align:left;padding:.3rem .5rem;border-bottom:1px solid rgb(107 114 128 / .3)">KKD</th>
                        <th style="border-bottom:1px solid rgb(107 114 128 / .3)"></th>
                    </tr>
                    @foreach ($ekipUyeleri as $i => $e)
                        <tr>
                            <td style="padding:.3rem .5rem">{{ $e['ad_soyad'] }}</td>
                            <td style="padding:.3rem .5rem">{{ $e['gorev'] ?: '—' }}</td>
                            <td style="padding:.3rem .5rem">{{ $e['kkd'] ?: '—' }}</td>
                            <td style="padding:.3rem .5rem;text-align:right">
                                <button type="button" wire:click="ekipSil({{ $i }})" style="color:#ef4444;cursor:pointer;background:none;border:none">✕</button>
                            </td>
                        </tr>
                    @endforeach
                </table>
            @endif

            <label style="font-weight:600;font-size:.82rem">Genel Notlar</label>
            <textarea wire:model="genelNotlar" rows="3"
                style="margin-top:.3rem;width:100%;padding:.5rem .75rem;border-radius:.5rem;border:1px solid rgb(107 114 128 / .35);background:transparent"></textarea>
        </x-filament::section>

        {{-- 4. GEÇMİŞ KAYITLAR --}}
        @if ($this->gecmisKayitlar->isNotEmpty())
            <x-filament::section icon="heroicon-o-clock" icon-color="gray">
                <x-slot name="heading">Geçmiş Saha Denetimleri</x-slot>
                <table style="width:100%;border-collapse:collapse;font-size:.82rem">
                    <tr>
                        <th style="text-align:left;padding:.35rem .5rem;border-bottom:1px solid rgb(107 114 128 / .3)">Revizyon</th>
                        <th style="text-align:left;padding:.35rem .5rem;border-bottom:1px solid rgb(107 114 128 / .3)">Tarih</th>
                        <th style="text-align:left;padding:.35rem .5rem;border-bottom:1px solid rgb(107 114 128 / .3)">Uygunluk</th>
                        <th style="text-align:left;padding:.35rem .5rem;border-bottom:1px solid rgb(107 114 128 / .3)">Sonuç</th>
                        <th style="border-bottom:1px solid rgb(107 114 128 / .3)"></th>
                    </tr>
                    @foreach ($this->gecmisKayitlar as $k)
                        <tr>
                            <td style="padding:.35rem .5rem">Rev {{ $k->revizyon }}</td>
                            <td style="padding:.35rem .5rem">{{ $k->denetim_tarihi?->format('d.m.Y') }}</td>
                            <td style="padding:.35rem .5rem">%{{ $k->uygunluk_yuzdesi ?? '—' }}</td>
                            <td style="padding:.35rem .5rem">{{ $k->sonucEtiketi() }}</td>
                            <td style="padding:.35rem .5rem;text-align:right;white-space:nowrap">
                                <x-filament::button size="xs" color="gray" wire:click="gecmisPdf({{ $k->id }})">PDF</x-filament::button>
                                <x-filament::button size="xs" color="danger" wire:click="gecmisSil({{ $k->id }})">Sil</x-filament::button>
                            </td>
                        </tr>
                    @endforeach
                </table>
            </x-filament::section>
        @endif
    @else
        <p style="margin-top:1rem;font-size:.85rem;color:#f59e0b">Devam etmek için bir firma seçin.</p>
    @endif
</x-filament-panels::page>
