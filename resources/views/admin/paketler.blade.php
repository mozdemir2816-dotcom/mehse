@extends('admin.layouts.hesap')

@section('baslik', 'Paketler')

{{-- Fiyatlar kullanıcı tarafından belirlenmedi: düğme WhatsApp'ta seçilen paketi belirterek teklif ister.
     Özellik listeleri platformdaki mevcut modüllerden; paket kapsamı değişirse burayı güncelleyin. --}}
@section('icerik')
    @php
        $tik = '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 6L9 17l-5-5"/></svg>';
        $paketler = [
            [
                'ad' => 'Bireysel Uzman',
                'kime' => 'Kendi firmalarına hizmet veren İSG uzmanları için.',
                'one' => false,
                'ozellikler' => [
                    '1 uzman kullanıcı',
                    'Firma ve çalışan kayıtları',
                    'Risk değerlendirmesi (5x5 matris / Fine-Kinney)',
                    'Eğitim kayıtları ve sertifika basımı',
                    'Saha denetimi ve tespit-öneri defteri',
                    'Periyodik kontrol ve belge süresi hatırlatmaları',
                ],
            ],
            [
                'ad' => 'Profesyonel OSGB',
                'kime' => 'Birden fazla uzman ve işyeriyle çalışan OSGB\'ler için.',
                'one' => true,
                'ozellikler' => [
                    'Bireysel Uzman paketindeki her şey',
                    'Birden fazla kullanıcı, sayfa ve firma bazlı yetkilendirme',
                    'Kontrol Merkezi ile tüm portföyün tek ekrandan takibi',
                    'İSG Kurulu, acil durum planı ve tatbikat kayıtları',
                    'Uzaktan eğitim, ön test / son test',
                    'Öncelikli WhatsApp desteği',
                ],
            ],
            [
                'ad' => 'Kurumsal & Sanayi',
                'kime' => 'Fabrika, hastane ve çok birimli büyük işletmeler için.',
                'one' => false,
                'ozellikler' => [
                    'Profesyonel OSGB paketindeki her şey',
                    'Çok birimli tesis ve hastane risk şablonları',
                    'Kimyasal envanter ve KKD zimmet yönetimi',
                    'Taşeron, iş izni ve ziyaretçi yönetimi',
                    'Kurulum ve mevcut verilerin aktarımında destek',
                ],
            ],
        ];
    @endphp

    @if ($user->hasActiveTrial())
        <a href="{{ url('/admin') }}" class="h-geri">&larr; Panele dön</a>
    @endif

    <div class="p-baslik">
        <h1 class="h-baslik">Size uygun paketi seçin</h1>
        <p class="h-alt-baslik">Paketi seçin, WhatsApp üzerinden size özel fiyat teklifini hemen iletelim. Verileriniz paket değişikliğinde aynen korunur.</p>
    </div>

    <div class="p-izgara">
        @foreach ($paketler as $p)
            @php
                $mesaj = rawurlencode("Merhaba, Mehse İSG panelinden yazıyorum. \"{$p['ad']}\" paketiyle ilgileniyorum, fiyat bilgisi alabilir miyim? Hesabım: {$user->email}");
            @endphp
            <div @class(['p-kart', 'p-one' => $p['one']])>
                @if ($p['one'])
                    <span class="p-rozet">En çok tercih edilen</span>
                @endif
                <h2 class="p-ad">{{ $p['ad'] }}</h2>
                <p class="p-kime">{{ $p['kime'] }}</p>
                <div class="p-fiyat">Fiyat: size özel teklif</div>
                <ul class="p-liste">
                    @foreach ($p['ozellikler'] as $o)
                        <li>{!! $tik !!}<span>{{ $o }}</span></li>
                    @endforeach
                </ul>
                <a href="https://wa.me/{{ $waNo }}?text={{ $mesaj }}" target="_blank" rel="noopener"
                   @class(['h-dugme', 'h-dugme-mavi' => $p['one'], 'h-dugme-yesil' => ! $p['one']])>
                    Bu Paketi Seç
                </a>
            </div>
        @endforeach
    </div>

    <p class="h-not" style="text-align:center">Sorularınız için <a href="tel:+905064754855">0506 475 48 55</a> · Hafta içi 09:00 - 18:00</p>
@endsection
