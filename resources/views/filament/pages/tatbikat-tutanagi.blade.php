@php
    $kirmizi = 'rgb(239 68 68)';
    $kutu = 'border:1px solid rgb(107 114 128 / .3);border-radius:.75rem;padding:1rem';
    $cevapRenk = ['evet' => '#10b981', 'hayir' => '#ef4444', 'kismen' => '#f59e0b'];
@endphp

<x-filament-panels::page>
    @include('filament.pages.partials.acil-slate-stil')
    <p class="mehse-bolum-aciklama">
        Acil Durumlar Yönetmeliği md.13 — yılda en az bir tatbikat zorunlu, tutanakla
        denetime kanıttır. Senaryo seçin, tatbikat bilgilerini doldurup PDF alın.
    </p>

    @include('filament.pages.partials.eksik-firmalar', ['kriterAnahtari' => 'acil_durum_tatbikat'])

    {{-- 1. FİRMA & SENARYO --}}
    <x-filament::section icon="heroicon-o-fire" icon-color="danger">
        <x-slot name="heading">1. Firma & Tatbikat Senaryosu</x-slot>

        <div style="margin-bottom:1rem">
            <label class="mehse-etiket">Firma Seçin <span class="mehse-hata">*</span></label>
            <select wire:model.live="firmaId"
                style="margin-top:.3rem;width:100%;max-width:24rem;padding:.55rem .75rem;border-radius:.5rem;border:1px solid rgb(107 114 128 / .35);background:transparent">
                <option value="">— Firma seçin —</option>
                @foreach ($this->firmalar as $id => $ad)
                    <option value="{{ $id }}">{{ $ad }}</option>
                @endforeach
            </select>
        </div>

        {{-- Hızlı kombinasyonlar (02.11.2026: çoklu / bileşik tatbikat) --}}
        <div style="display:flex;gap:.5rem;flex-wrap:wrap;margin-bottom:.6rem">
            @foreach ($this->kombinasyonlar as $kAnahtar => $k)
                @php $aktif = $k['senaryolar'] !== [] && \App\Models\TatbikatTutanagi::siraliSenaryolar($k['senaryolar']) === $secilenSenaryolar; @endphp
                <x-filament::button size="sm" :color="$aktif ? 'danger' : 'gray'" :outlined="! $aktif" wire:click="hazirKombinasyon('{{ $kAnahtar }}')">
                    {{ $k['ad'] }}
                </x-filament::button>
            @endforeach
        </div>
        <div style="font-size:.75rem;color:rgb(107 114 128);margin-bottom:.5rem">
            Birden fazla senaryo işaretleyebilirsiniz; seçilenler olayların birbirini tetikleme sırasına göre tek bir bileşik tatbikatta birleştirilir.
        </div>

        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:.5rem">
            @foreach ($this->senaryolar as $anahtar => $s)
                @php $secili = in_array($anahtar, $secilenSenaryolar, true); $no = array_search($anahtar, $secilenSenaryolar, true); @endphp
                <button type="button" wire:click="senaryoSec('{{ $anahtar }}')"
                    class="acil-merkez-secenek"
                    style="position:relative;text-align:center;padding:.75rem;cursor:pointer;font-size:.8rem;
                        border:2px solid {{ $secili ? '#e11d48' : '#e2e8f0' }};
                        background:{{ $secili ? '#fff1f2' : '#fff' }};color:{{ $secili ? '#9f1239' : '#334155' }}">
                    <span style="position:absolute;top:.35rem;left:.45rem;font-size:.9rem">{{ $secili ? '☑' : '☐' }}</span>
                    @if ($secili && count($secilenSenaryolar) > 1)
                        <span style="position:absolute;top:.3rem;right:.45rem;font-size:.75rem;font-weight:700;background:#e11d48;color:#fff;border-radius:999px;padding:0 .4rem">{{ $no + 1 }}</span>
                    @endif
                    <div style="font-weight:700">{{ $s['ad'] }}</div>
                    <div class="mehse-soluk">≈{{ $s['sure_dk'] }} dk</div>
                </button>
            @endforeach
        </div>

        @if ($secilenSenaryolar)
            <div style="margin-top:.7rem;padding:.55rem .8rem;border-radius:.5rem;border:1px solid rgb(225 29 72 / .35);background:rgb(225 29 72 / .05);font-size:.82rem">
                <b>{{ \App\Models\TatbikatTutanagi::senaryoBasligi($secilenSenaryolar) }}</b>
                @if (count($secilenSenaryolar) > 1)
                    <div style="font-size:.75rem;margin-top:.15rem">Akış: {{ collect($secilenSenaryolar)->map(fn ($a) => config('isg.tatbikat.bilesik.'.$a.'.kisa'))->implode(' → ') }}</div>
                @endif
                <div style="font-size:.75rem;margin-top:.15rem;color:rgb(107 114 128)">
                    Görev alması gereken ekipler: {{ collect(\App\Models\TatbikatTutanagi::gerekenEkipTurleri($secilenSenaryolar))->map(fn ($t) => \App\Models\TatbikatTutanagi::ekipEtiketi($t))->implode(', ') ?: '—' }}
                </div>
            </div>
        @endif
    </x-filament::section>

    @if ($this->firma)
        {{-- 2. FİRMA & TATBİKAT BİLGİLERİ --}}
        <x-filament::section icon="heroicon-o-clipboard-document-list" icon-color="danger">
            <x-slot name="heading">2. Tatbikat Bilgileri</x-slot>

            @if ($duzenlenenId)
                <div style="{{ $kutu }};padding:.55rem .9rem;margin-bottom:.9rem;background:rgb(245 158 11 / .08);border-color:rgb(245 158 11 / .4);font-size:.82rem;display:flex;gap:.75rem;align-items:center;flex-wrap:wrap">
                    <span>✏️ Kayıtlı bir tatbikat düzenleniyor — kaydettiğinizde bu kayıt güncellenir.</span>
                    <x-filament::button size="xs" color="gray" wire:click="yeniKayit">Vazgeç / Yeni Tatbikat</x-filament::button>
                </div>
            @endif

            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:1rem">
                <div>
                    <label class="mehse-etiket">Durum</label>
                    <select wire:model.live="durum"
                        class="mehse-girdi-kompakt mehse-tam">
                        @foreach ($this->durumlar as $anahtar => $ad)
                            <option value="{{ $anahtar }}">{{ $ad }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="mehse-etiket">Tatbikat Tarihi{{ $durum === 'planlandi' ? ' (planlanan)' : '' }}</label>
                    <input type="date" wire:model="tatbikatTarihi"
                        class="mehse-girdi-kompakt mehse-tam">
                </div>
                <div>
                    <label class="mehse-etiket">Tatbikat Yeri</label>
                    <input type="text" wire:model.live.blur="tatbikatYeri" placeholder="Örn: Üretim sahası ve idari bina"
                        class="mehse-girdi-kompakt mehse-tam">
                </div>
                <div>
                    <label class="mehse-etiket">Toplanma Alanı</label>
                    <input type="text" wire:model.live.blur="toplanmaAlani" placeholder="Örn: Ana kapı önü otopark"
                        class="mehse-girdi-kompakt mehse-tam">
                </div>
                <div>
                    <label class="mehse-etiket">Katılımcı Sayısı</label>
                    <input type="number" min="0" wire:model="katilimciSayisi" placeholder="Boş = listedeki katılımcılar ({{ count($katilimcilar) }})"
                        class="mehse-girdi-kompakt mehse-tam">
                </div>
                <div>
                    <label class="mehse-etiket">Başlama Saati</label>
                    <input type="time" wire:model="baslamaSaati"
                        class="mehse-girdi-kompakt mehse-tam">
                </div>
                <div>
                    <label class="mehse-etiket">Bitiş Saati</label>
                    <input type="time" wire:model="bitisSaati"
                        class="mehse-girdi-kompakt mehse-tam">
                </div>
                <div>
                    <label class="mehse-etiket">Gerçekleşen Tahliye Süresi (dk)</label>
                    <input type="number" min="0" wire:model.live.debounce.500ms="tahliyeDk" placeholder="Kronometre — örn: 4"
                        class="mehse-girdi-kompakt mehse-tam">
                </div>
                <div>
                    <label class="mehse-etiket">Hedef (Planlanan) Tahliye Süresi (dk)</label>
                    <input type="number" min="0" wire:model.live.debounce.500ms="hedefTahliyeDk" placeholder="Senaryo seçince önerilen gelir"
                        class="mehse-girdi-kompakt mehse-tam">
                    @if ($tahliyeDk && $hedefTahliyeDk)
                        <div style="font-size:.75rem;margin-top:.2rem;color:{{ $tahliyeDk <= $hedefTahliyeDk ? '#16a34a' : '#dc2626' }}">
                            {{ $tahliyeDk <= $hedefTahliyeDk ? '✓ Tahliye hedef süre içinde' : '✗ Hedef süre '.($tahliyeDk - $hedefTahliyeDk).' dk aşıldı — DÖF önerisi ekleyin' }}
                        </div>
                    @endif
                </div>
                <div>
                    <label class="mehse-etiket">İşveren / Vekili</label>
                    <input type="text" wire:model="isverenVekili"
                        class="mehse-girdi-kompakt mehse-tam">
                </div>
                <div>
                    <label class="mehse-etiket">İş Güvenliği Uzmanı</label>
                    <input type="text" wire:model="isGuvenligiUzmani"
                        class="mehse-girdi-kompakt mehse-tam">
                </div>
                <div>
                    <label class="mehse-etiket">Tatbikat Koordinatörü</label>
                    <input type="text" wire:model="tatbikatKoordinatoru"
                        class="mehse-girdi-kompakt mehse-tam">
                </div>
                <div>
                    <label class="mehse-etiket">Belge Tarihi</label>
                    <input type="date" wire:model="belgeTarihi"
                        class="mehse-girdi-kompakt mehse-tam">
                </div>
            </div>
            <div style="margin-top:.75rem;display:flex;gap:1.5rem;flex-wrap:wrap">
                <label style="display:flex;align-items:center;gap:.4rem;font-size:.82rem;cursor:pointer">
                    <input type="checkbox" wire:model="haberliTatbikat"> Haberli tatbikat
                </label>
                <label style="display:flex;align-items:center;gap:.4rem;font-size:.82rem;cursor:pointer">
                    <input type="checkbox" wire:model="yillikPlanDahilinde"> Yıllık plan dahilinde
                </label>
                <label style="display:flex;align-items:center;gap:.4rem;font-size:.82rem;cursor:pointer">
                    <input type="checkbox" wire:model="isyeriHekimiImzasi"> İşyeri Hekimi imzası ekle
                </label>
            </div>
        </x-filament::section>

        {{-- 3. SENARYO METNİ --}}
        <x-filament::section icon="heroicon-o-document-text" icon-color="danger">
            <x-slot name="heading">3. Senaryo Metni</x-slot>
            <div style="font-size:.75rem;color:rgb(107 114 128);margin-bottom:.4rem">Senaryo seçildiğinde firma unvanı, adresi, tatbikat yeri ve toplanma alanı metne otomatik yerleşir (önce yer / toplanma alanını yazıp sonra senaryo seçin). Metni dilediğiniz gibi düzenleyebilirsiniz.</div>
            @if ($secilenSenaryolar)
                <div style="margin-bottom:.4rem"><x-filament::button size="xs" color="gray" icon="heroicon-o-arrow-path" wire:click="metniYenidenOlustur" wire:confirm="Senaryo metni seçime göre yeniden oluşturulsun mu? Elle yaptığınız değişiklikler kaybolur.">Metni seçime göre yeniden oluştur</x-filament::button></div>
            @endif
            <textarea wire:model="senaryoMetni" rows="{{ count($secilenSenaryolar) > 1 ? 14 : 8 }}" placeholder="Yukarıdan bir senaryo seçin — hazır metin buraya gelir ve düzenlenebilir."
                style="width:100%;padding:.5rem .7rem;border-radius:.4rem;border:1px solid rgb(107 114 128 / .3);background:transparent;font-family:inherit;font-size:.85rem"></textarea>
        </x-filament::section>

        {{-- 4. GÖREV ALAN EKİPLER --}}
        <x-filament::section icon="heroicon-o-user-group" icon-color="danger">
            <x-slot name="heading">4. Görev Alan Ekipler ({{ count($ekipler) }})</x-slot>

            @if ($this->calisanlar->isNotEmpty())
                <div style="margin-bottom:.75rem">
                    <div style="font-weight:600;font-size:.8rem;margin-bottom:.3rem">Firma Çalışanlarından Hızlı Ekle</div>
                    <div style="display:flex;gap:.4rem;flex-wrap:wrap">
                        @foreach ($this->calisanlar as $c)
                            @foreach ($this->ekipSecenekleri as $ekip)
                                <x-filament::button size="xs" color="gray" wire:click="ekipHizliEkle({{ $c->id }}, '{{ $ekip }}')">+ {{ $c->ad_soyad }} ({{ $ekip }})</x-filament::button>
                            @endforeach
                        @endforeach
                    </div>
                </div>
            @endif

            <div style="display:grid;grid-template-columns:1fr 1fr auto;gap:.5rem;margin-bottom:.5rem">
                <input type="text" wire:model="yeniEkipAd" placeholder="Ad Soyad"
                    class="mehse-girdi-kompakt">
                <select wire:model="yeniEkipTipi"
                    class="mehse-girdi-kompakt">
                    <option value="">Ekip seçin</option>
                    @foreach ($this->ekipSecenekleri as $ekip)
                        <option value="{{ $ekip }}">{{ $ekip }}</option>
                    @endforeach
                </select>
                <x-filament::button size="sm" wire:click="ekipEkle">+ Ekle</x-filament::button>
            </div>

            @if ($ekipler)
                <table class="mehse-tablo">
                    @foreach ($ekipler as $i => $e)
                        <tr>
                            <td style="padding:.25rem .5rem">{{ $e['ad_soyad'] }} — {{ $e['ekip'] ?: '—' }}</td>
                            <td style="padding:.25rem .5rem;text-align:right">
                                <button type="button" wire:click="ekipSil({{ $i }})" class="mehse-sil-dugme">✕</button>
                            </td>
                        </tr>
                    @endforeach
                </table>
            @endif
        </x-filament::section>

        {{-- 5. DEĞERLENDİRME KONTROL LİSTESİ --}}
        <x-filament::section icon="heroicon-o-check-circle" icon-color="danger">
            <x-slot name="heading">5. Değerlendirme Kontrol Listesi</x-slot>

            @foreach ($degerlendirmeler as $i => $d)
                <div style="display:flex;justify-content:space-between;align-items:center;gap:.5rem;{{ $kutu }};margin-bottom:.4rem;padding:.5rem .7rem">
                    <span style="font-size:.82rem;flex:1">{{ $d['soru'] }}</span>
                    <div style="display:flex;gap:.3rem;flex-shrink:0">
                        @foreach ($this->degerlendirmeSecenekleri as $anahtar => $etiket)
                            @php $secili = $d['cevap'] === $anahtar; @endphp
                            <button type="button" wire:click="degerlendirmeCevapla({{ $i }}, '{{ $anahtar }}')"
                                style="padding:.25rem .6rem;border-radius:.3rem;cursor:pointer;font-size:.75rem;color:#fff;
                                    background:{{ $secili ? $cevapRenk[$anahtar] : 'rgb(107 114 128 / .4)' }}">
                                {{ $etiket }}
                            </button>
                        @endforeach
                        <button type="button" wire:click="degerlendirmeSil({{ $i }})" class="mehse-sil-dugme">✕</button>
                    </div>
                </div>
            @endforeach

            <div style="display:grid;grid-template-columns:1fr auto;gap:.5rem;margin-top:.5rem">
                <input type="text" wire:model="yeniOzelSoru" placeholder="Özel değerlendirme sorusu ekle..."
                    class="mehse-girdi-kompakt">
                <x-filament::button size="sm" wire:click="ozelSoruEkle">+ Ekle</x-filament::button>
            </div>
        </x-filament::section>

        {{-- 6. GÖZLEM VE GENEL DEĞERLENDİRME --}}
        <x-filament::section icon="heroicon-o-eye" icon-color="danger">
            <x-slot name="heading">6. Gözlem ve Genel Değerlendirme</x-slot>
            <textarea wire:model="gozlem" rows="2" placeholder="Tatbikat sırasındaki gözlemler, genel değerlendirme..."
                style="width:100%;padding:.5rem .7rem;border-radius:.4rem;border:1px solid rgb(107 114 128 / .3);background:transparent;font-family:inherit;font-size:.85rem"></textarea>
        </x-filament::section>

        {{-- 7. TESPİT EDİLEN EKSİKLİKLER --}}
        <x-filament::section icon="heroicon-o-exclamation-triangle" icon-color="danger">
            <x-slot name="heading">7. Tespit Edilen Eksiklikler ({{ count($eksiklikler) }})</x-slot>
            <div style="display:grid;grid-template-columns:1fr auto;gap:.5rem;margin-bottom:.5rem">
                <input type="text" wire:model="yeniEksiklik" placeholder="Örn: Alarm sesi depo alanında zayıf duyuldu"
                    class="mehse-girdi-kompakt">
                <x-filament::button size="sm" wire:click="eksiklikEkle">+ Ekle</x-filament::button>
            </div>
            @foreach ($eksiklikler as $i => $e)
                <div style="display:flex;justify-content:space-between;font-size:.82rem;padding:.2rem .4rem">
                    <span>{{ $e }}</span>
                    <button type="button" wire:click="eksiklikSil({{ $i }})" class="mehse-sil-dugme">✕</button>
                </div>
            @endforeach
        </x-filament::section>

        {{-- 8. YAPILACAK DÜZENLEMELER (DÖF) --}}
        <x-filament::section icon="heroicon-o-wrench" icon-color="danger">
            <x-slot name="heading">8. Yapılacak Düzenlemeler (DÖF) ({{ count($dofOnerileri) }})</x-slot>
            <div style="display:grid;grid-template-columns:2fr 1fr 1fr auto;gap:.5rem;margin-bottom:.5rem">
                <input type="text" wire:model="yeniDofFaaliyet" placeholder="Faaliyet (örn: Depo alanına ek siren montajı)"
                    class="mehse-girdi-kompakt">
                <input type="text" wire:model="yeniDofSorumlu" placeholder="Sorumlu"
                    class="mehse-girdi-kompakt">
                <input type="date" wire:model="yeniDofTarih"
                    class="mehse-girdi-kompakt">
                <x-filament::button size="sm" wire:click="dofOnerisiEkle">+ Ekle</x-filament::button>
            </div>
            @foreach ($dofOnerileri as $i => $d)
                <div style="display:flex;justify-content:space-between;font-size:.82rem;padding:.2rem .4rem">
                    <span>{{ $d['faaliyet'] }} — {{ $d['sorumlu'] ?: '—' }} — {{ $d['tarih'] ?: '—' }}</span>
                    <button type="button" wire:click="dofOnerisiSil({{ $i }})" class="mehse-sil-dugme">✕</button>
                </div>
            @endforeach
        </x-filament::section>

        {{-- 9. KATILIMCILAR --}}
        <x-filament::section icon="heroicon-o-users" icon-color="danger">
            <x-slot name="heading">9. Firma Çalışanları / Katılımcılar ({{ count($katilimcilar) }})</x-slot>

            @if ($this->calisanlar->isNotEmpty())
                <div style="margin-bottom:.75rem;display:flex;gap:.4rem;flex-wrap:wrap">
                    @foreach ($this->calisanlar as $c)
                        <x-filament::button size="xs" color="gray" wire:click="katilimciHizliEkle({{ $c->id }})">+ {{ $c->ad_soyad }}</x-filament::button>
                    @endforeach
                </div>
            @endif

            <div style="display:grid;grid-template-columns:1fr 1fr 1fr auto;gap:.5rem;margin-bottom:.5rem">
                <input type="text" wire:model="yeniKatilimciAd" placeholder="Ad Soyad"
                    class="mehse-girdi-kompakt">
                <input type="text" wire:model="yeniKatilimciTc" placeholder="T.C. Kimlik No"
                    class="mehse-girdi-kompakt">
                <input type="text" wire:model="yeniKatilimciGorev" placeholder="Görevi"
                    class="mehse-girdi-kompakt">
                <x-filament::button size="sm" wire:click="katilimciEkle">+ Ekle</x-filament::button>
            </div>

            @if ($katilimcilar)
                <table class="mehse-tablo">
                    @foreach ($katilimcilar as $i => $k)
                        <tr>
                            <td style="padding:.25rem .5rem">{{ $k['ad_soyad'] }}</td>
                            <td style="padding:.25rem .5rem;text-align:right">
                                <button type="button" wire:click="katilimciSil({{ $i }})" class="mehse-sil-dugme">✕</button>
                            </td>
                        </tr>
                    @endforeach
                </table>
            @endif
        </x-filament::section>

        {{-- 10. FOTOĞRAFLAR --}}
        <x-filament::section icon="heroicon-o-camera" icon-color="danger">
            <x-slot name="heading">10. Fotoğraflar ({{ count($yeniFotograflar) }})</x-slot>

            @if ($yeniFotograflar)
                <div style="display:flex;gap:.5rem;flex-wrap:wrap;margin-bottom:.75rem">
                    @foreach ($yeniFotograflar as $i => $dosya)
                        <div style="position:relative">
                            <img alt="Yüklenen fotoğraf" src="{{ $dosya->temporaryUrl() }}" style="width:70px;height:70px;object-fit:cover;border-radius:.4rem;border:1px solid rgb(107 114 128 / .3)">
                            <span style="position:absolute;bottom:2px;left:2px;background:rgb(0 0 0 / .65);color:#fff;border-radius:.25rem;padding:0 .3rem;font-size:.75rem;line-height:1.3">{{ $i + 1 }}</span>
                            <button type="button" wire:click="fotoSil({{ $i }})"
                                style="position:absolute;top:-6px;right:-6px;background:#ef4444;color:#fff;border:none;border-radius:50%;width:18px;height:18px;font-size:.75rem;cursor:pointer;line-height:1">✕</button>
                        </div>
                    @endforeach
                </div>
            @endif

            <input type="file" wire:model="yeniFotograflar" multiple accept="image/*" style="font-size:.82rem">
            <p style="font-size:.75rem;color:rgb(107 114 128);margin-top:.3rem">
                Tatbikatın toplanma yeri, ekiplerin müdahalesi vb. fotoğrafları — PDF'in sonuna kanıt sayfası olarak eklenir.
            </p>
        </x-filament::section>

    @else
        <p class="mehse-uyari-not">Yeni tatbikat için bir firma seçin. Aşağıda tüm firmaların tatbikat kayıtları listelenir.</p>
    @endif

    {{-- TATBİKAT KAYITLARI (isgsuite "Tatbikat Yönetimi") --}}
    @php
        $o = $this->ozet;
        $th = 'text-align:left;padding:.4rem .5rem;border-bottom:1px solid rgb(107 114 128 / .3);font-size:.75rem;text-transform:uppercase;letter-spacing:.03em;color:rgb(107 114 128)';
        $td = 'padding:.4rem .5rem;border-bottom:1px solid rgb(107 114 128 / .12);vertical-align:top';
        $girdi = 'width:100%;padding:.45rem .6rem;border-radius:.4rem;border:1px solid rgb(107 114 128 / .3);background:transparent;font-size:.82rem';
        $durumRenk = ['planlandi' => 'rgb(245 158 11 / .6)', 'yapildi' => 'rgb(22 163 74 / .55)', 'takip' => 'rgb(220 38 38 / .6)', 'iptal' => 'rgb(107 114 128 / .45)'];
    @endphp
    <x-filament::section icon="heroicon-o-clock" icon-color="gray">
        <x-slot name="heading">Tatbikat Kayıtları {{ $this->firma ? '— '.$this->firma->unvan : '— Tüm firmalar' }}</x-slot>

        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(130px,1fr));gap:.6rem;margin-bottom:.9rem">
            @foreach ([
                ['Toplam kayıt', $o['toplam'], 'inherit', null],
                ['Planlandı', $o['planlandi'], '#d97706', 'planlandi'],
                ['Yapıldı', $o['yapildi'], '#16a34a', 'yapildi'],
                ['Takip gereken', $o['takip'], '#dc2626', 'takip'],
                ['Tarihi geçen plan', $o['gecikmis'], '#dc2626', 'gecikmis'],
            ] as [$ad, $sayi, $renk, $filtre])
                <button type="button" wire:click="$set('listeDurum', {{ $filtre ? "'".$filtre."'" : 'null' }})"
                    style="text-align:left;{{ $kutu }};padding:.6rem .85rem;background:transparent;cursor:pointer;{{ $listeDurum === $filtre ? 'border-color:rgb(124 58 237)' : '' }}">
                    <div class="mehse-soluk">{{ $ad }}</div>
                    <div style="font-size:1.35rem;font-weight:700;color:{{ $sayi > 0 ? $renk : 'inherit' }}">{{ $sayi }}</div>
                </button>
            @endforeach
        </div>

        <div style="display:grid;grid-template-columns:2fr 1fr;gap:.75rem;margin-bottom:.75rem">
            <input type="search" wire:model.live.debounce.400ms="listeArama" placeholder="Tatbikat türü, sorumlu, senaryo veya firma ara…" style="{{ $girdi }}">
            <select wire:model.live="listeDurum" style="{{ $girdi }}">
                <option value="">Tüm durumlar</option>
                @foreach ($this->durumlar as $anahtar => $ad)
                    <option value="{{ $anahtar }}">{{ $ad }}</option>
                @endforeach
                <option value="gecikmis">Tarihi geçen plan</option>
            </select>
        </div>

        @if ($this->kayitlar->isNotEmpty())
            <div style="overflow-x:auto">
                <table style="width:100%;border-collapse:collapse;font-size:.8rem;min-width:860px">
                    <tr>
                        @foreach (['Tür', 'İşyeri', 'Tarih', 'Saat', 'Sorumlu', 'Katılımcı', 'Foto', 'Durum', ''] as $b)
                            <th style="{{ $th }}">{{ $b }}</th>
                        @endforeach
                    </tr>
                    @foreach ($this->kayitlar as $t)
                        <tr @if ($duzenlenenId === $t->id) style="background:rgb(245 158 11 / .08)" @endif>
                            <td style="{{ $td }}">{{ $t->senaryoEtiketi() }}</td>
                            <td style="{{ $td }}">{{ $t->firma?->unvan }}</td>
                            <td style="{{ $td }};white-space:nowrap">{{ $t->tatbikat_tarihi?->format('d.m.Y') ?? '—' }}</td>
                            <td style="{{ $td }};white-space:nowrap">{{ collect([$t->baslama_saati, $t->bitis_saati])->filter()->implode(' – ') ?: '—' }}</td>
                            <td style="{{ $td }}">{{ $t->tatbikat_koordinatoru ?: '—' }}</td>
                            <td style="{{ $td }}">{{ $t->katilimciSayisi() }}</td>
                            <td style="{{ $td }}">{{ count($t->fotograflar ?? []) }}</td>
                            <td style="{{ $td }}">
                                <span style="font-size:.75rem;padding:.1rem .5rem;border-radius:999px;white-space:nowrap;border:1px solid {{ $durumRenk[$t->durum] ?? 'rgb(107 114 128 / .45)' }}">{{ $t->durumEtiketi() }}</span>
                                @if ($t->gecikmisMi())<div style="font-size:.75rem;color:#b91c1c">Planlanan tarih geçti</div>@endif
                            </td>
                            <td style="{{ $td }};text-align:right;white-space:nowrap">
                                @if ($t->durum === 'planlandi')
                                    <x-filament::button size="xs" color="success" wire:click="duzenle({{ $t->id }}, 'yapildi')">Yapıldı olarak doldur</x-filament::button>
                                @endif
                                <x-filament::button size="xs" color="primary" wire:click="duzenle({{ $t->id }})">Düzenle</x-filament::button>
                                @if ($t->yapildiMi())
                                    <x-filament::button size="xs" color="gray" wire:click="kayitPdf({{ $t->id }})">PDF</x-filament::button>
                                    <x-filament::button size="xs" color="gray" wire:click="kayitWord({{ $t->id }})">Word</x-filament::button>
                                @endif
                                <x-filament::button size="xs" color="danger" wire:click="gecmisSil({{ $t->id }})" wire:confirm="Tatbikat kaydı silinsin mi?">Sil</x-filament::button>
                            </td>
                        </tr>
                    @endforeach
                </table>
            </div>
        @else
            <p style="font-size:.83rem;color:rgb(107 114 128)">Henüz tatbikat kaydı yok.</p>
        @endif
    </x-filament::section>
</x-filament-panels::page>
