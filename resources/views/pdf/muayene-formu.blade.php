<!doctype html>
<html lang="tr">
<head>
<meta charset="utf-8">
{{--
    EK-2 İşe Giriş / Periyodik Muayene Formu — kullanıcının referans şablonu (ise_giris_muayene_formu_ek2.html):
    1 İşyeri, 2 Çalışan (+ önceki işyerleri + beyan), 3 Tıbbi anamnez, 4 Fizik muayene, 5 Laboratuvar, 6 Kanaat ve sonuç.
    Şablondaki etiket ve yasal metinler değiştirilmez. ek2 sütunu boş eski kayıtlar da basılır (eski alanlar ayrıca gösterilir).
--}}
<style>
    * { font-family: DejaVu Sans, sans-serif; }
    @page { margin: 22px 26px; }
    body { margin: 0; color: #111; font-size: 8.4px; }
    h1 { font-size: 13.5px; margin: 0 0 2px; }
    .alt-baslik { font-size: 7.5px; color: #555; margin: 0; }
    .ek-rozet { display: inline-block; border: 1.2px solid #0f4c81; color: #0f4c81; font-weight: bold; font-size: 10px; padding: 0 6px; margin-bottom: 3px; }
    table { width: 100%; border-collapse: collapse; }
    .ust td { vertical-align: top; }
    .tur-kutu { border: 1px solid #bae6fd; background: #e8f1f8; padding: 5px 8px; font-size: 8.5px; line-height: 1.7; }
    .foto { width: 82px; height: 98px; border: 1px dashed #64748b; text-align: center; font-size: 7.5px; color: #666; }
    .bolum { margin-top: 5px; border: 1px solid #cbd5e1; page-break-inside: avoid; }
    .bolum-baslik { background: #0f4c81; color: #fff; font-weight: bold; font-size: 9.5px; padding: 3px 8px; }
    .bolum-govde { padding: 3px 6px; }
    td.e, th.e { border: 1px solid #cbd5e1; padding: 2px 4px; vertical-align: top; }
    th.e { background: #f1f5f9; font-size: 7.5px; color: #475569; text-align: left; }
    .etk { font-size: 6.8px; color: #475569; font-weight: bold; display: block; }
    .deger { font-size: 8.4px; min-height: 10px; }
    .kutu { border: 1px solid #cbd5e1; background: #f8fafc; padding: 5px 7px; font-size: 8.2px; }
    .kucuk { font-size: 7px; color: #555; }
    .imza-kutu { border: 1.3px solid #64748b; padding: 6px 8px; height: 118px; }
    .imza-cizgi { border-top: 1px dashed #64748b; margin-top: 34px; padding-top: 3px; text-align: center; font-size: 7.5px; color: #555; font-weight: bold; }
    .imza-kutu img { max-height: 34px; }
    .anormal { color: #b91c1c; font-weight: bold; }
</style>
</head>
<body>
@php
    $e = \App\Models\MuayeneFormu::ek2Birlestir($form->ek2);
    $eskiKayit = $form->ek2 === null;
    $tr = fn ($t) => filled($t) ? \Illuminate\Support\Carbon::parse($t)->format('d.m.Y') : '';
    $isaret = fn (bool $secili) => $secili ? '☒' : '☐';
    $turEsle = in_array($form->muayene_turu, ['ise_donus', 'araya_giren'], true) ? 'ise_donus' : $form->muayene_turu;
    $bki = \App\Models\MuayeneFormu::bki($e['vital']['boy'], $e['vital']['kilo']);
    $gorev = $e['kanaat']['gorev'] ?: ($e['yaptigi_is'] ?: $form->calisan_gorevi);
    $imzali = $imzali ?? true;
    $oncekiDolu = collect($e['onceki'])->filter(fn ($o) => collect($o)->filter()->isNotEmpty());
    $oncekiSatirlar = $oncekiDolu->isNotEmpty() ? $oncekiDolu->values()->all() : [['isyeri' => '', 'is' => '', 'baslama' => '', 'ayrilma' => ''], ['isyeri' => '', 'is' => '', 'baslama' => '', 'ayrilma' => '']];
    $asiAdlari = ['tetanoz' => 'Tetanoz', 'hepatit' => 'Hepatit B', 'diger' => 'Diğer'];
    $asiDurum = ['Tam' => 'Tam / Güncel', 'Eksik' => 'Eksik', 'Bilinmiyor' => 'Bilinmiyor', 'Var' => 'Uygulandı', 'Yok' => 'Yok'];
@endphp

{{-- FORM BAŞLIĞI --}}
<table class="ust" style="border-bottom:2px solid #0f4c81;padding-bottom:6px;margin-bottom:6px">
    <tr>
        <td style="width:57%">
            <span class="ek-rozet">EK-2</span>
            <h1>İŞE GİRİŞ / PERİYODİK MUAYENE FORMU</h1>
            <p class="alt-baslik">6331 Sayılı İş Sağlığı ve Güvenliği Kanunu &amp; İşyeri Hekimi ve Diğer Sağlık Personelinin Görev, Yetki, Sorumluluk ve Eğitimleri Hakkında Yönetmelik</p>
            <p class="alt-baslik" style="margin-top:2px">Belge No: {{ $form->belge_no }}</p>
        </td>
        <td style="width:27%;padding-left:6px">
            <div class="tur-kutu">
                {{ $isaret($turEsle === 'ise_giris') }} İşe Giriş Muayenesi<br>
                {{ $isaret($turEsle === 'periyodik') }} Periyodik Muayene<br>
                {{ $isaret($turEsle === 'ise_donus') }} İşe Dönüş / Ek Muayene
            </div>
        </td>
        <td style="width:16%;text-align:right"><div class="foto" style="margin-left:auto"><br><br><strong>FOTOĞRAF</strong></div></td>
    </tr>
</table>

{{-- 1. İŞYERİNİN BİLGİLERİ --}}
<div class="bolum">
    <div class="bolum-baslik">1. İŞYERİNİN BİLGİLERİ</div>
    <div class="bolum-govde">
        <table>
            <tr>
                <td class="e" style="width:66%" colspan="2"><span class="etk">İşyerinin Unvanı</span><span class="deger">{{ $firma?->unvan }}</span></td>
                <td class="e"><span class="etk">SGK Sicil No</span><span class="deger">{{ $firma?->sgk_sicil_no }}</span></td>
            </tr>
            <tr><td class="e" colspan="3"><span class="etk">Adresi</span><span class="deger">{{ $firma?->adres }}</span></td></tr>
            <tr>
                <td class="e" style="width:33%"><span class="etk">Tel ve Faks No</span><span class="deger">{{ $e['isyeri_tel'] ?: $firma?->telefon }}</span></td>
                <td class="e" style="width:33%"><span class="etk">E-Posta</span><span class="deger">{{ $e['isyeri_eposta'] ?: $firma?->eposta }}</span></td>
                <td class="e"><span class="etk">Tehlike Sınıfı</span><span class="deger">{{ $firma?->tehlikeSinifiEtiketi() }}</span></td>
            </tr>
        </table>
    </div>
</div>

{{-- 2. ÇALIŞANIN BİLGİLERİ --}}
<div class="bolum">
    <div class="bolum-baslik">2. ÇALIŞANIN BİLGİLERİ</div>
    <div class="bolum-govde">
        <table>
            <tr>
                <td class="e" style="width:50%" colspan="2"><span class="etk">Adı ve Soyadı</span><span class="deger">{{ $form->calisan_ad_soyad }}</span></td>
                <td class="e" style="width:25%"><span class="etk">T.C. Kimlik No</span><span class="deger">{{ $form->calisan_tc }}</span></td>
                <td class="e"><span class="etk">Kan Grubu</span><span class="deger">{{ $e['kan_grubu'] }}</span></td>
            </tr>
            <tr>
                <td class="e" style="width:25%"><span class="etk">Doğum Yeri</span><span class="deger">{{ $e['dogum_yeri'] }}</span></td>
                <td class="e" style="width:25%"><span class="etk">Doğum Tarihi</span><span class="deger">{{ $tr($form->calisan_dogum_tarihi) }}</span></td>
                <td class="e"><span class="etk">Cinsiyeti</span><span class="deger">{{ $e['cinsiyet'] }}</span></td>
                <td class="e"><span class="etk">Eğitim Durumu</span><span class="deger">{{ $e['egitim_durumu'] }}</span></td>
            </tr>
            <tr>
                <td class="e"><span class="etk">Medeni Durumu</span><span class="deger">{{ $e['medeni_durum'] }}</span></td>
                <td class="e"><span class="etk">Çocuk Sayısı</span><span class="deger">{{ $e['cocuk_sayisi'] }}</span></td>
                <td class="e"><span class="etk">Tel No</span><span class="deger">{{ $e['calisan_tel'] }}</span></td>
                <td class="e"><span class="etk">Mesleği</span><span class="deger">{{ $e['meslegi'] ?: $form->calisan_gorevi }}</span></td>
            </tr>
            <tr>
                <td class="e" colspan="2"><span class="etk">Ev Adresi</span><span class="deger">{{ $e['ev_adresi'] }}</span></td>
                <td class="e"><span class="etk">Çalıştığı Bölüm</span><span class="deger">{{ $e['calistigi_bolum'] }}</span></td>
                <td class="e"><span class="etk">Yaptığı İş (Ayrıntılı)</span><span class="deger">{{ $e['yaptigi_is'] ?: $form->calisan_gorevi }}</span></td>
            </tr>
            @if ($form->ise_giris_tarihi)
                <tr><td class="e" colspan="4"><span class="etk">İşe Giriş Tarihi</span><span class="deger">{{ $tr($form->ise_giris_tarihi) }}</span></td></tr>
            @endif
        </table>

        <div class="etk" style="margin-top:6px">Daha Önce Çalıştığı Yerler</div>
        <table>
            <tr><th class="e" style="width:32%">İşyeri Unvanı / Sektör</th><th class="e" style="width:32%">Yaptığı İş / Görev</th><th class="e" style="width:18%">Başlama Tarihi</th><th class="e">Ayrılma Tarihi</th></tr>
            @foreach ($oncekiSatirlar as $o)
                <tr>
                    <td class="e"><span class="deger">{{ $o['isyeri'] }}</span></td>
                    <td class="e"><span class="deger">{{ $o['is'] }}</span></td>
                    <td class="e"><span class="deger">{{ $tr($o['baslama']) }}</span></td>
                    <td class="e"><span class="deger">{{ $tr($o['ayrilma']) }}</span></td>
                </tr>
            @endforeach
        </table>
        @if (filled($form->meslek_oykusu) || filled($form->maruz_kalinan_riskler))
            <div class="kucuk" style="margin-top:3px">
                @if (filled($form->meslek_oykusu))<strong>Meslek öyküsü:</strong> {{ $form->meslek_oykusu }}<br>@endif
                @if (filled($form->maruz_kalinan_riskler))<strong>Maruz kalınan riskler:</strong> {{ $form->maruz_kalinan_riskler }}@endif
            </div>
        @endif

        <div class="kutu" style="margin-top:6px">
            <strong>"İşe giriş / periyodik muayene olmayı kabul ettiğimi, muayene sırasında verdiğim bilgilerin doğru ve eksiksiz olduğunu ve tahlil sonuçlarım hakkında bilgilendirildiğimi beyan ederim."</strong>
            <table style="margin-top:4px">
                <tr>
                    <td class="e" style="width:22%"><span class="etk">Tarih</span><span class="deger">{{ $tr($e['beyan_tarih']) }}</span></td>
                    <td class="e" style="width:44%"><span class="etk">Çalışanın Adı Soyadı</span><span class="deger">{{ $form->calisan_ad_soyad }}</span></td>
                    <td class="e"><span class="etk">Çalışanın İmzası</span><span class="deger">&nbsp;<br>&nbsp;</span></td>
                </tr>
            </table>
        </div>
    </div>
</div>

{{-- 3. TIBBİ ANAMNEZ --}}
<div class="bolum" style="page-break-inside:auto">
    <div class="bolum-baslik">3. TIBBİ ANAMNEZ</div>
    <div class="bolum-govde">
        <table>
            <tr>
                <td style="width:50%;vertical-align:top;padding-right:4px">
                    <span class="etk">3.1. Bağışıklama (Aşılar)</span>
                    <table>
                        <tr><th class="e">Aşı Türü</th><th class="e">Durum</th><th class="e">Son Uygulama Tarihi / Doz</th></tr>
                        @foreach ($asiAdlari as $anahtar => $ad)
                            <tr>
                                <td class="e"><strong>{{ $ad }}</strong></td>
                                <td class="e">{{ $asiDurum[$e['asi'][$anahtar]['durum']] ?? '' }}</td>
                                <td class="e">{{ $e['asi'][$anahtar]['not'] }}</td>
                            </tr>
                        @endforeach
                    </table>
                </td>
                <td style="width:50%;vertical-align:top;padding-left:4px">
                    <span class="etk">3.2. Soygeçmiş (Ailede Kronik Hastalık Öyküsü)</span>
                    <table>
                        <tr><th class="e" style="width:26%">Yakınlık</th><th class="e">Sağlık Durumu / Kronik Hastalıklar (HT, DM, KAH vb.)</th></tr>
                        @foreach (['anne' => 'Anne', 'baba' => 'Baba', 'kardes' => 'Kardeşler', 'cocuk' => 'Çocuklar'] as $anahtar => $ad)
                            <tr><td class="e"><strong>{{ $ad }}</strong></td><td class="e">{{ $e['soygecmis'][$anahtar] }}&nbsp;</td></tr>
                        @endforeach
                    </table>
                </td>
            </tr>
        </table>

        <span class="etk" style="margin-top:6px">3.3. Özgeçmiş (Kişisel Tıbbi Geçmiş ve Semptom Sorgulaması)</span>
        <table>
            <tr><th class="e" style="width:50%">Sorgulanan Durum / Semptom</th><th class="e" style="width:16%">Durum</th><th class="e">"Evet" ise Açıklama / Tanı / Tarih</th></tr>
            @foreach (\App\Models\MuayeneFormu::OZGECMIS_SORULARI as $no => $soru)
                @php $oz = $e['oz'][$no] ?? ['cevap' => null, 'aciklama' => null]; @endphp
                <tr>
                    <td class="e">{{ $no }}. {{ $soru }}</td>
                    <td class="e" style="white-space:nowrap">{{ $isaret($oz['cevap'] === 'Hayır') }} Hayır &nbsp; {{ $isaret($oz['cevap'] === 'Evet') }} Evet</td>
                    <td class="e">{{ $oz['aciklama'] }}&nbsp;</td>
                </tr>
            @endforeach
        </table>

        <table style="margin-top:5px">
            <tr>
                <td class="e" style="width:50%"><span class="etk">3.4. Sigara Kullanımı</span><span class="deger">{{ $e['sigara']['durum'] }}@if (filled($e['sigara']['detay'])) — {{ $e['sigara']['detay'] }}@endif</span></td>
                <td class="e"><span class="etk">3.5. Alkol / Diğer Alışkanlıklar</span><span class="deger">{{ $e['alkol']['durum'] }}@if (filled($e['alkol']['detay'])) — {{ $e['alkol']['detay'] }}@endif</span></td>
            </tr>
        </table>
        @if (filled($form->ozgecmis) || filled($form->soygecmis))
            <div class="kucuk" style="margin-top:3px">
                @if (filled($form->ozgecmis))<strong>Ek not (özgeçmiş):</strong> {{ $form->ozgecmis }}<br>@endif
                @if (filled($form->soygecmis))<strong>Ek not (soygeçmiş):</strong> {{ $form->soygecmis }}@endif
            </div>
        @endif
    </div>
</div>

{{-- 4. FİZİK MUAYENE SONUÇLARI --}}
<div class="bolum" style="page-break-before:always;page-break-inside:auto">
    <div class="bolum-baslik">4. FİZİK MUAYENE SONUÇLARI</div>
    <div class="bolum-govde">
        <table style="background:#e8f1f8;border:1px solid #bae6fd">
            <tr>
                <td class="e"><span class="etk">Boy (cm)</span><span class="deger">{{ $e['vital']['boy'] }}</span></td>
                <td class="e"><span class="etk">Kilo (kg)</span><span class="deger">{{ $e['vital']['kilo'] }}</span></td>
                <td class="e"><span class="etk">BKİ (kg/m²)</span><span class="deger">@if ($bki){{ $bki['deger'] }} — {{ $bki['sinif'] }}@endif</span></td>
                <td class="e"><span class="etk">Kan Basıncı (TA - mmHg)</span><span class="deger">{{ $e['vital']['kan_basinci'] }}</span></td>
                <td class="e"><span class="etk">Nabız (/dk - Ritmik)</span><span class="deger">{{ $e['vital']['nabiz'] }}</span></td>
            </tr>
        </table>

        <table style="margin-top:6px">
            <tr><th class="e" style="width:28%">Muayene Edilen Sistem / Organ</th><th class="e">Fizik Muayene Bulguları</th></tr>
            @foreach (\App\Models\MuayeneFormu::FIZIK_SISTEMLERI as $anahtar => [$ad, $alt])
                <tr>
                    <td class="e"><strong>{{ $ad }}</strong>@if ($alt)<br><span class="kucuk">{{ $alt }}</span>@endif</td>
                    <td class="e"><span class="deger">{{ $e['fizik'][$anahtar] }}</span>&nbsp;</td>
                </tr>
            @endforeach
            @if ($eskiKayit)
                @foreach (($form->sistemik_muayene ?? []) as $s)
                    <tr>
                        <td class="e"><strong>{{ $s['baslik'] ?? '' }}</strong></td>
                        <td class="e {{ ($s['sonuc'] ?? '') === 'anormal' ? 'anormal' : '' }}">{{ ($s['sonuc'] ?? '') === 'anormal' ? 'Anormal' : 'Normal' }}@if (filled($s['not'] ?? null)) — {{ $s['not'] }}@endif</td>
                    </tr>
                @endforeach
            @endif
        </table>
    </div>
</div>

{{-- 5. LABORATUVAR BULGULARI VE DİĞER TETKİKLER --}}
<div class="bolum">
    <div class="bolum-baslik">5. LABORATUVAR BULGULARI VE DİĞER TETKİKLER</div>
    <div class="bolum-govde">
        <table>
            <tr><th class="e" style="width:30%">Tetkik Grubu</th><th class="e" style="width:15%">Tarih</th><th class="e">Sonuç / Değerlendirme</th></tr>
            @foreach (\App\Models\MuayeneFormu::LAB_GRUPLARI as $anahtar => [$ad, $alt])
                <tr>
                    <td class="e"><strong>{{ $ad }}</strong>@if ($alt)<br><span class="kucuk">{{ $alt }}</span>@endif</td>
                    <td class="e"><span class="deger">{{ $tr($e['lab'][$anahtar]['tarih']) }}</span></td>
                    <td class="e"><span class="deger">{{ $e['lab'][$anahtar]['sonuc'] }}</span>&nbsp;</td>
                </tr>
            @endforeach
            @if ($eskiKayit)
                @foreach (($form->tetkikler ?? []) as $t)
                    @continue(! ($t['yapildi'] ?? false))
                    <tr>
                        <td class="e">{{ config('isg.muayene.tetkikler.'.($t['anahtar'] ?? ''), $t['anahtar'] ?? '') }}</td>
                        <td class="e"></td>
                        <td class="e {{ ($t['sonuc'] ?? '') === 'anormal' ? 'anormal' : '' }}">{{ ($t['sonuc'] ?? '') === 'anormal' ? 'Anormal' : (($t['sonuc'] ?? '') === 'normal' ? 'Normal' : '') }}@if (filled($t['not'] ?? null)) — {{ $t['not'] }}@endif</td>
                    </tr>
                @endforeach
            @endif
        </table>
    </div>
</div>

{{-- 6. KANAAT VE SONUÇ --}}
<div class="bolum">
    <div class="bolum-baslik">6. KANAAT VE SONUÇ (*)</div>
    <div class="bolum-govde">
        <table>
            <tr>
                <td style="width:60%;vertical-align:top;padding-right:6px">
                    <div class="kutu">
                        <span class="etk">Çalışacağı Görev / İş Tanımı</span>
                        <div class="deger" style="margin-bottom:4px">{{ $gorev }}</div>

                        <div style="margin-bottom:3px">{{ $isaret($form->sonuc_kanaati === 'uygun') }} <strong>1.</strong> Yukarıda belirtilen işinde bedenen ve ruhen çalışmaya <strong>ELVERİŞLİDİR</strong>.</div>
                        <div style="margin-bottom:3px">{{ $isaret($form->sonuc_kanaati === 'sartli_uygun') }} <strong>2.</strong> Belirtilen şartlarla çalışmaya <strong>ELVERİŞLİDİR</strong> (Şartlı Uygunluk):
                            @if ($form->sonuc_kanaati === 'sartli_uygun' && filled($form->sart_aciklamasi)) <em>{{ $form->sart_aciklamasi }}</em>@endif</div>
                        @if (in_array($form->sonuc_kanaati, ['uygun_degil', 'is_degisikligi'], true))
                            <div style="margin-bottom:3px">☒ <strong>{{ $form->sonucKanaatiEtiketi() }}</strong></div>
                        @endif

                        <div style="border-top:1px solid #cbd5e1;margin-top:4px;padding-top:4px">
                            {{ $isaret((bool) $e['kanaat']['gece']) }} Gece ve vardiyalı çalışma koşullarında çalışabilir.<br>
                            {{ $isaret((bool) $e['kanaat']['yuksekte']) }} Yüksekte / tehlikeli işlerde çalışabilir.
                        </div>
                        <table style="margin-top:4px">
                            <tr>
                                <td class="e" style="width:50%"><span class="etk">Muayene Tarihi</span><span class="deger">{{ $tr($form->muayene_tarihi) }}</span></td>
                                <td class="e"><span class="etk">Bir Sonraki Periyodik Muayene Tarihi</span><span class="deger">{{ $tr($form->onerilen_kontrol_tarihi) }}</span></td>
                            </tr>
                        </table>
                    </div>
                </td>
                <td style="vertical-align:top">
                    <div class="imza-kutu">
                        <div style="font-size:8px;font-weight:bold;color:#0f4c81;margin-bottom:4px">İŞYERİ HEKİMİNİN KİMLİK VE ONAY BİLGİLERİ</div>
                        <span class="etk">Adı ve Soyadı</span><div class="deger">{{ $form->hekim_adi }}</div>
                        <table style="margin-top:2px">
                            <tr>
                                <td style="width:48%;padding-right:3px"><span class="etk">Diploma Tarih / No</span><span class="deger">{{ $e['hekim']['diploma'] }}</span></td>
                                <td><span class="etk">İşyeri Hekimliği Belge No</span><span class="deger">{{ $e['hekim']['belge_no'] }}</span></td>
                            </tr>
                        </table>
                        @if ($imzali && $form->hekim_kase)
                            <div style="text-align:center;margin-top:3px"><img src="{{ storage_path('app/public/'.$form->hekim_kase) }}"></div>
                        @endif
                        <div class="imza-cizgi" @if ($imzali && $form->hekim_kase) style="margin-top:2px" @endif>İmza / Kaşe</div>
                    </div>
                </td>
            </tr>
        </table>

        <p class="kucuk" style="margin-top:6px;font-style:italic">
            (*) Yapılan muayene sonucunda çalışanın gece veya vardiyalı çalışma koşullarında çalışıp çalışamayacağı ile vücut sağlığını ve bütünlüğünü tamamlayıcı uygun alet, teçhizat vb. bulunması durumunda çalışan için bu koşullarla çalışmaya elverişli olup olmadığı kanaati belirtilecektir.
        </p>
    </div>
</div>
</body>
</html>
