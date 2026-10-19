@php
    $teal = 'rgb(14 116 144)';
    $kutu = 'border:1px solid rgb(107 114 128 / .3);border-radius:.75rem;padding:1rem';
    $girdi = 'margin-top:.3rem;width:100%;padding:.5rem .75rem;border-radius:.5rem;border:1px solid rgb(107 114 128 / .35);background:transparent';
    $kucukGirdi = 'width:100%;padding:.3rem .45rem;border-radius:.4rem;border:1px solid rgb(107 114 128 / .3);background:transparent;font-size:.78rem';
    $etk = 'font-weight:600;font-size:.78rem';
    $muayeneModeli = \App\Models\MuayeneFormu::class;
@endphp

<x-filament-panels::page>
    <p style="font-size:.85rem;color:rgb(107 114 128);margin-top:-.5rem">
        EK-2 İşe Giriş / Periyodik Muayene Formu'nu doldurun; sonuç ve kanaat ile PDF çıktısı üretilir.
    </p>

    @include('filament.pages.partials.eksik-firmalar', ['kriterAnahtari' => 'saglik_raporu'])

    {{-- 1. FİRMA & ÇALIŞAN --}}
    <x-filament::section icon="heroicon-o-heart" icon-color="info">
        <x-slot name="heading">1. Firma & Çalışan</x-slot>

        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:1rem">
            <div>
                <label style="font-weight:600;font-size:.82rem">Firma Seçin <span style="color:#ef4444">*</span></label>
                <select wire:model.live="firmaId" style="{{ $girdi }}">
                    <option value="">— Firma seçin —</option>
                    @foreach ($this->firmalar as $id => $ad)
                        <option value="{{ $id }}">{{ $ad }}</option>
                    @endforeach
                </select>
            </div>
            @if ($this->firma)
                <div>
                    <label style="font-weight:600;font-size:.82rem">Hızlı Çalışan Seç</label>
                    <select wire:model.live="calisanHizliSecId" style="{{ $girdi }}">
                        <option value="">Firmaya kayıtlı çalışan yok / manuel gir</option>
                        @foreach ($this->calisanlar as $c)
                            <option value="{{ $c->id }}">{{ $c->ad_soyad }}</option>
                        @endforeach
                    </select>
                </div>
            @endif
        </div>

        @if ($this->firma)
            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:1rem;margin-top:1rem">
                <div>
                    <label style="font-weight:600;font-size:.8rem">Ad Soyad <span style="color:#ef4444">*</span></label>
                    <input type="text" wire:model="calisanAdSoyad" style="{{ $girdi }}">
                </div>
                <div>
                    <label style="font-weight:600;font-size:.8rem">T.C. Kimlik No</label>
                    <input type="text" wire:model="calisanTc" style="{{ $girdi }}">
                </div>
                <div>
                    <label style="font-weight:600;font-size:.8rem">Doğum Tarihi</label>
                    <input type="date" wire:model="calisanDogumTarihi" style="{{ $girdi }}">
                </div>
                <div>
                    <label style="font-weight:600;font-size:.8rem">Görevi</label>
                    <input type="text" wire:model="calisanGorevi" style="{{ $girdi }}">
                </div>
                <div>
                    <label style="font-weight:600;font-size:.8rem">İşe Giriş Tarihi</label>
                    <input type="date" wire:model="iseGirisTarihi" style="{{ $girdi }}">
                </div>
                <div>
                    <label style="font-weight:600;font-size:.8rem">Muayene Tarihi</label>
                    <input type="date" wire:model="muayeneTarihi" style="{{ $girdi }}">
                </div>
                <div>
                    <label style="font-weight:600;font-size:.8rem">Muayene Türü</label>
                    <select wire:model="muayeneTuru" style="{{ $girdi }}">
                        @foreach ($this->muayeneTurleri as $anahtar => $etiket)
                            <option value="{{ $anahtar }}">{{ $etiket }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:1rem;margin-top:1rem">
                <div><label style="{{ $etk }}">İşyeri Tel / Faks</label><input type="text" wire:model="ek2.isyeri_tel" style="{{ $girdi }}"></div>
                <div><label style="{{ $etk }}">İşyeri E-Posta</label><input type="text" wire:model="ek2.isyeri_eposta" style="{{ $girdi }}"></div>
                <div><label style="{{ $etk }}">Kan Grubu</label>
                    <select wire:model="ek2.kan_grubu" style="{{ $girdi }}"><option value="">—</option>@foreach ($muayeneModeli::KAN_GRUPLARI as $k)<option>{{ $k }}</option>@endforeach</select></div>
                <div><label style="{{ $etk }}">Doğum Yeri</label><input type="text" wire:model="ek2.dogum_yeri" style="{{ $girdi }}"></div>
                <div><label style="{{ $etk }}">Cinsiyeti</label>
                    <select wire:model="ek2.cinsiyet" style="{{ $girdi }}"><option value="">—</option><option>Kadın</option><option>Erkek</option></select></div>
                <div><label style="{{ $etk }}">Eğitim Durumu</label>
                    <select wire:model="ek2.egitim_durumu" style="{{ $girdi }}"><option value="">—</option>@foreach ($muayeneModeli::EGITIM_DURUMLARI as $e)<option>{{ $e }}</option>@endforeach</select></div>
                <div><label style="{{ $etk }}">Medeni Durumu</label>
                    <select wire:model="ek2.medeni_durum" style="{{ $girdi }}"><option value="">—</option><option>Bekar</option><option>Evli</option></select></div>
                <div><label style="{{ $etk }}">Çocuk Sayısı</label><input type="number" min="0" wire:model="ek2.cocuk_sayisi" style="{{ $girdi }}"></div>
                <div><label style="{{ $etk }}">Çalışan Tel No</label><input type="text" wire:model="ek2.calisan_tel" style="{{ $girdi }}"></div>
                <div><label style="{{ $etk }}">Mesleği</label><input type="text" wire:model="ek2.meslegi" style="{{ $girdi }}"></div>
                <div><label style="{{ $etk }}">Çalıştığı Bölüm</label><input type="text" wire:model="ek2.calistigi_bolum" style="{{ $girdi }}"></div>
                <div><label style="{{ $etk }}">Yaptığı İş (Ayrıntılı)</label><input type="text" wire:model="ek2.yaptigi_is" style="{{ $girdi }}"></div>
                <div style="grid-column:1/-1"><label style="{{ $etk }}">Ev Adresi</label><input type="text" wire:model="ek2.ev_adresi" style="{{ $girdi }}"></div>
            </div>
        @endif
    </x-filament::section>

    @if ($this->firma)
        {{-- 2. ÖNCEKİ İŞYERLERİ + BEYAN --}}
        <x-filament::section icon="heroicon-o-briefcase" icon-color="info">
            <x-slot name="heading">2. Daha Önce Çalıştığı Yerler ve Beyan</x-slot>
            <table style="width:100%;border-collapse:collapse;font-size:.8rem">
                <tr><th style="text-align:left;padding:.3rem">İşyeri Unvanı / Sektör</th><th style="text-align:left;padding:.3rem">Yaptığı İş / Görev</th><th style="width:140px">Başlama</th><th style="width:140px">Ayrılma</th><th style="width:34px"></th></tr>
                @foreach ($ek2['onceki'] as $i => $o)
                    <tr wire:key="onceki-{{ $i }}">
                        <td style="padding:.25rem"><input type="text" wire:model="ek2.onceki.{{ $i }}.isyeri" style="{{ $kucukGirdi }}"></td>
                        <td style="padding:.25rem"><input type="text" wire:model="ek2.onceki.{{ $i }}.is" style="{{ $kucukGirdi }}"></td>
                        <td style="padding:.25rem"><input type="date" wire:model="ek2.onceki.{{ $i }}.baslama" style="{{ $kucukGirdi }}"></td>
                        <td style="padding:.25rem"><input type="date" wire:model="ek2.onceki.{{ $i }}.ayrilma" style="{{ $kucukGirdi }}"></td>
                        <td style="padding:.25rem;text-align:center"><button type="button" wire:click="oncekiIsSil({{ $i }})" style="color:#dc2626;font-weight:700;cursor:pointer;border:none;background:none">×</button></td>
                    </tr>
                @endforeach
            </table>
            <x-filament::button size="xs" color="gray" wire:click="oncekiIsEkle" style="margin-top:.4rem">+ Satır Ekle</x-filament::button>

            <label style="{{ $etk }};display:block;margin-top:.9rem">Meslek Öyküsü / Maruz Kalınan Riskler (serbest not)</label>
            <textarea wire:model="meslekOykusu" rows="2" placeholder="Daha önce çalıştığı işler, süreleri..." style="{{ $kucukGirdi }};font-family:inherit;margin-top:.2rem"></textarea>
            <textarea wire:model="maruzKalinanRiskler" rows="2" placeholder="Gürültü, kimyasal, toz, titreşim..." style="{{ $kucukGirdi }};font-family:inherit;margin-top:.3rem"></textarea>

            <div style="margin-top:.9rem;padding:.7rem .9rem;border:1px solid rgb(107 114 128 / .3);border-radius:.5rem;font-size:.78rem">
                <strong>Çalışan beyanı:</strong> "İşe giriş / periyodik muayene olmayı kabul ettiğimi, muayene sırasında verdiğim bilgilerin doğru ve eksiksiz olduğunu ve tahlil sonuçlarım hakkında bilgilendirildiğimi beyan ederim."
                <div style="max-width:200px;margin-top:.4rem"><label style="{{ $etk }}">Beyan Tarihi</label><input type="date" wire:model="ek2.beyan_tarih" style="{{ $kucukGirdi }}"></div>
            </div>
        </x-filament::section>

        {{-- 3. TIBBİ ANAMNEZ --}}
        <x-filament::section icon="heroicon-o-document-text" icon-color="info">
            <x-slot name="heading">3. Tıbbi Anamnez</x-slot>
            <x-slot name="afterHeader"><x-filament::button size="xs" color="gray" wire:click="anamnezHayir">Tümünü "Hayır" İşaretle</x-filament::button></x-slot>

            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(320px,1fr));gap:1rem">
                <div>
                    <label style="{{ $etk }}">3.1. Bağışıklama (Aşılar)</label>
                    <table style="width:100%;border-collapse:collapse;font-size:.78rem;margin-top:.3rem">
                        <tr><th style="text-align:left;padding:.2rem">Aşı</th><th>Durum</th><th>Son Uygulama / Doz</th></tr>
                        @foreach (['tetanoz' => ['Tetanoz', ['Tam' => 'Tam / Güncel', 'Eksik' => 'Eksik', 'Bilinmiyor' => 'Bilinmiyor']], 'hepatit' => ['Hepatit B', ['Tam' => 'Tam / Bağışık', 'Eksik' => 'Eksik', 'Bilinmiyor' => 'Bilinmiyor']], 'diger' => ['Diğer', ['Var' => 'Uygulandı', 'Yok' => 'Yok']]] as $anahtar => [$ad, $secenekler])
                            <tr>
                                <td style="padding:.2rem"><strong>{{ $ad }}</strong></td>
                                <td style="padding:.2rem"><select wire:model="ek2.asi.{{ $anahtar }}.durum" style="{{ $kucukGirdi }}"><option value="">—</option>@foreach ($secenekler as $d => $e)<option value="{{ $d }}">{{ $e }}</option>@endforeach</select></td>
                                <td style="padding:.2rem"><input type="text" wire:model="ek2.asi.{{ $anahtar }}.not" style="{{ $kucukGirdi }}"></td>
                            </tr>
                        @endforeach
                    </table>
                </div>
                <div>
                    <label style="{{ $etk }}">3.2. Soygeçmiş (Ailede Kronik Hastalık Öyküsü)</label>
                    <table style="width:100%;border-collapse:collapse;font-size:.78rem;margin-top:.3rem">
                        @foreach (['anne' => 'Anne', 'baba' => 'Baba', 'kardes' => 'Kardeşler', 'cocuk' => 'Çocuklar'] as $anahtar => $ad)
                            <tr>
                                <td style="padding:.2rem;width:90px"><strong>{{ $ad }}</strong></td>
                                <td style="padding:.2rem"><input type="text" wire:model="ek2.soygecmis.{{ $anahtar }}" placeholder="HT, DM, KAH vb." style="{{ $kucukGirdi }}"></td>
                            </tr>
                        @endforeach
                    </table>
                </div>
            </div>

            <label style="{{ $etk }};display:block;margin-top:1rem">3.3. Özgeçmiş (Kişisel Tıbbi Geçmiş ve Semptom Sorgulaması)</label>
            <table style="width:100%;border-collapse:collapse;font-size:.78rem;margin-top:.3rem">
                <tr><th style="text-align:left;padding:.2rem">Sorgulanan Durum / Semptom</th><th style="width:150px">Durum</th><th style="width:30%">"Evet" ise Açıklama / Tanı / Tarih</th></tr>
                @foreach ($muayeneModeli::OZGECMIS_SORULARI as $no => $soru)
                    <tr style="border-top:1px solid rgb(107 114 128 / .15)">
                        <td style="padding:.3rem">{{ $no }}. {{ $soru }}</td>
                        <td style="padding:.3rem;text-align:center;white-space:nowrap">
                            <label><input type="radio" wire:model="ek2.oz.{{ $no }}.cevap" value="Hayır"> Hayır</label>
                            <label style="margin-left:.5rem"><input type="radio" wire:model="ek2.oz.{{ $no }}.cevap" value="Evet"> Evet</label>
                        </td>
                        <td style="padding:.3rem"><input type="text" wire:model="ek2.oz.{{ $no }}.aciklama" placeholder="{{ $muayeneModeli::OZGECMIS_IPUCLARI[$no] ?? '' }}" style="{{ $kucukGirdi }}"></td>
                    </tr>
                @endforeach
            </table>

            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(300px,1fr));gap:1rem;margin-top:1rem">
                <div>
                    <label style="{{ $etk }}">3.4. Sigara Kullanımı</label>
                    <div style="display:flex;gap:.4rem;margin-top:.3rem">
                        <select wire:model="ek2.sigara.durum" style="{{ $kucukGirdi }};width:130px"><option value="Hayır">Hayır (Hiç)</option><option value="Bırakmış">Bırakmış</option><option value="Evet">Evet (Kullanıyor)</option></select>
                        <input type="text" wire:model="ek2.sigara.detay" placeholder="Günde kaç adet / kaç yıl / ne zaman bıraktı?" style="{{ $kucukGirdi }}">
                    </div>
                </div>
                <div>
                    <label style="{{ $etk }}">3.5. Alkol / Diğer Alışkanlıklar</label>
                    <div style="display:flex;gap:.4rem;margin-top:.3rem">
                        <select wire:model="ek2.alkol.durum" style="{{ $kucukGirdi }};width:130px"><option value="Hayır">Hayır</option><option value="Bırakmış">Bırakmış</option><option value="Evet">Evet</option></select>
                        <input type="text" wire:model="ek2.alkol.detay" placeholder="Sıklığı ve miktarı" style="{{ $kucukGirdi }}">
                    </div>
                </div>
            </div>

            <label style="{{ $etk }};display:block;margin-top:1rem">Ek notlar (serbest özgeçmiş / soygeçmiş)</label>
            <textarea wire:model="ozgecmis" rows="2" placeholder="Kronik hastalık, ilaçlar, ameliyatlar, alerjiler..." style="{{ $kucukGirdi }};font-family:inherit;margin-top:.2rem"></textarea>
            <textarea wire:model="soygecmis" rows="2" placeholder="Ailede bilinen kronik hastalık öyküsü..." style="{{ $kucukGirdi }};font-family:inherit;margin-top:.3rem"></textarea>
        </x-filament::section>

        {{-- 4. FİZİK MUAYENE SONUÇLARI --}}
        <x-filament::section icon="heroicon-o-clipboard-document-check" icon-color="info">
            <x-slot name="heading">4. Fizik Muayene Sonuçları</x-slot>
            <x-slot name="afterHeader"><x-filament::button size="xs" color="gray" wire:click="fizikNormalDoldur">Tüm Sistemleri "Normal" Doldur</x-filament::button></x-slot>

            @php $bki = $muayeneModeli::bki($ek2['vital']['boy'] ?? null, $ek2['vital']['kilo'] ?? null); @endphp
            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(130px,1fr));gap:.7rem;padding:.7rem;border-radius:.5rem;background:rgb(14 116 144 / .07)">
                <div><label style="{{ $etk }}">Boy (cm)</label><input type="number" wire:model.live.debounce.400ms="ek2.vital.boy" style="{{ $kucukGirdi }}"></div>
                <div><label style="{{ $etk }}">Kilo (kg)</label><input type="number" step="0.1" wire:model.live.debounce.400ms="ek2.vital.kilo" style="{{ $kucukGirdi }}"></div>
                <div><label style="{{ $etk }}">BKİ (kg/m²)</label>
                    <div style="padding:.3rem .45rem;font-weight:700;font-size:.85rem">{{ $bki ? $bki['deger'].' — '.$bki['sinif'] : '—' }}</div></div>
                <div><label style="{{ $etk }}">Kan Basıncı (TA - mmHg)</label><input type="text" wire:model="ek2.vital.kan_basinci" placeholder="Örn: 120/80" style="{{ $kucukGirdi }}"></div>
                <div><label style="{{ $etk }}">Nabız (/dk - Ritmik)</label><input type="text" wire:model="ek2.vital.nabiz" placeholder="Örn: 76 /dk" style="{{ $kucukGirdi }}"></div>
            </div>

            <table style="width:100%;border-collapse:collapse;font-size:.8rem;margin-top:.8rem">
                <tr><th style="text-align:left;padding:.3rem;width:28%">Muayene Edilen Sistem / Organ</th><th style="text-align:left;padding:.3rem">Fizik Muayene Bulguları</th></tr>
                @foreach ($muayeneModeli::FIZIK_SISTEMLERI as $anahtar => [$ad, $alt, $normal])
                    <tr style="border-top:1px solid rgb(107 114 128 / .15)">
                        <td style="padding:.3rem"><strong>{{ $ad }}</strong>@if ($alt)<br><span style="font-size:.7rem;color:rgb(107 114 128)">{{ $alt }}</span>@endif</td>
                        <td style="padding:.3rem"><input type="text" wire:model="ek2.fizik.{{ $anahtar }}" placeholder="{{ $normal }}" style="{{ $kucukGirdi }}"></td>
                    </tr>
                @endforeach
            </table>
        </x-filament::section>

        {{-- 5. LABORATUVAR BULGULARI VE DİĞER TETKİKLER --}}
        <x-filament::section icon="heroicon-o-beaker" icon-color="info">
            <x-slot name="heading">5. Laboratuvar Bulguları ve Diğer Tetkikler</x-slot>
            <table style="width:100%;border-collapse:collapse;font-size:.8rem">
                <tr><th style="text-align:left;padding:.3rem;width:30%">Tetkik Grubu</th><th style="width:150px">Tarih</th><th style="text-align:left;padding:.3rem">Sonuç / Değerlendirme</th></tr>
                @foreach ($muayeneModeli::LAB_GRUPLARI as $anahtar => [$ad, $alt])
                    <tr style="border-top:1px solid rgb(107 114 128 / .15)">
                        <td style="padding:.3rem"><strong>{{ $ad }}</strong>@if ($alt)<br><span style="font-size:.7rem;color:rgb(107 114 128)">{{ $alt }}</span>@endif</td>
                        <td style="padding:.3rem"><input type="date" wire:model="ek2.lab.{{ $anahtar }}.tarih" style="{{ $kucukGirdi }}"></td>
                        <td style="padding:.3rem"><input type="text" wire:model="ek2.lab.{{ $anahtar }}.sonuc" style="{{ $kucukGirdi }}"></td>
                    </tr>
                @endforeach
            </table>
        </x-filament::section>
        {{-- 6. SONUÇ VE KANAAT --}}
        <x-filament::section icon="heroicon-o-check-badge" icon-color="info">
            <x-slot name="heading">6. Sonuç ve Kanaat</x-slot>
            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:.5rem">
                @foreach ($this->sonucKanaatleri as $anahtar => $etiket)
                    @php $secili = $sonucKanaati === $anahtar; @endphp
                    <button type="button" wire:click="$set('sonucKanaati', '{{ $anahtar }}')"
                        style="text-align:left;padding:.6rem;border-radius:.5rem;cursor:pointer;font-size:.82rem;font-weight:600;
                            border:2px solid {{ $secili ? $teal : 'rgb(107 114 128 / .3)' }};
                            background:{{ $secili ? 'rgb(14 116 144 / .08)' : 'transparent' }}">
                        {{ $etiket }}
                    </button>
                @endforeach
            </div>

            @if ($sonucKanaati === 'sartli_uygun')
                <textarea wire:model="sartAciklamasi" rows="2" placeholder="Şart açıklaması (örn: gece vardiyasında çalışamaz)"
                    style="width:100%;margin-top:.75rem;padding:.5rem .7rem;border-radius:.4rem;border:1px solid rgb(107 114 128 / .3);background:transparent;font-family:inherit;font-size:.82rem"></textarea>
            @endif

            <div style="display:grid;grid-template-columns:1fr auto;gap:.5rem;align-items:end;margin-top:1rem;max-width:420px">
                <div>
                    <label style="font-weight:600;font-size:.8rem">Önerilen Bir Sonraki Kontrol Tarihi</label>
                    <input type="date" wire:model="onerilenKontrolTarihi" style="{{ $girdi }}">
                </div>
                <x-filament::button size="sm" color="gray" wire:click="kontrolTarihiOner">
                    Tehlike Sınıfına Göre Öner
                </x-filament::button>
            </div>

            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:1rem;margin-top:1rem">
                <div style="grid-column:1/-1"><label style="{{ $etk }}">Çalışacağı Görev / İş Tanımı</label>
                    <input type="text" wire:model="ek2.kanaat.gorev" placeholder="Boşsa 'Yaptığı İş' yazılır" style="{{ $girdi }}"></div>
                <label style="display:flex;gap:.5rem;align-items:center;font-size:.82rem;font-weight:600"><input type="checkbox" wire:model="ek2.kanaat.gece"> Gece ve vardiyalı çalışma koşullarında çalışabilir.</label>
                <label style="display:flex;gap:.5rem;align-items:center;font-size:.82rem;font-weight:600"><input type="checkbox" wire:model="ek2.kanaat.yuksekte"> Yüksekte / tehlikeli işlerde çalışabilir.</label>
            </div>

            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:1rem;margin-top:1rem">
                <div><label style="{{ $etk }}">Muayeneyi Yapan Hekim (Adı ve Soyadı)</label><input type="text" wire:model="hekimAdi" style="{{ $girdi }}"></div>
                <div><label style="{{ $etk }}">Diploma Tarih / No</label><input type="text" wire:model="ek2.hekim.diploma" style="{{ $girdi }}"></div>
                <div><label style="{{ $etk }}">İşyeri Hekimliği Belge No</label><input type="text" wire:model="ek2.hekim.belge_no" style="{{ $girdi }}"></div>
            </div>
        </x-filament::section>

        {{-- 7. GEÇMİŞ KAYITLAR --}}
        @if ($this->gecmisKayitlar->isNotEmpty())
            <x-filament::section icon="heroicon-o-clock" icon-color="gray">
                <x-slot name="heading">Geçmiş Muayene Kayıtları</x-slot>
                <table style="width:100%;border-collapse:collapse;font-size:.82rem">
                    @foreach ($this->gecmisKayitlar as $m)
                        <tr>
                            <td style="padding:.3rem .5rem">{{ $m->belge_no }} — {{ $m->calisan_ad_soyad }} ({{ $m->muayene_tarihi?->format('d.m.Y') }})</td>
                            <td style="padding:.3rem .5rem;text-align:right;white-space:nowrap">
                                <x-filament::button size="xs" color="gray" wire:click="gecmisPdf({{ $m->id }})">PDF</x-filament::button>
                                <x-filament::button size="xs" color="danger" wire:click="gecmisSil({{ $m->id }})">Sil</x-filament::button>
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
