@php $kart = 'border:1px solid rgb(107 114 128 / .22);border-radius:.75rem;padding:1rem 1.1rem;background:rgb(255 255 255 / .6)'; @endphp
<x-filament-panels::page>
    <p style="font-size:.85rem;color:rgb(75 85 99);margin:0">
        Çalışanlarınızı İSG uzmanınızın hazırladığı online eğitime ekleyin. Birden fazla kişiyi işaretleyip
        "Seçilenleri eğitime ekle" ile toplu ekleyebilirsiniz. Eğitime başlamış çalışan çıkarılamaz.
        Listede yalnız çalışan personel görünür; yeni çalışanı önce <a href="{{ \App\Filament\Isyeri\Pages\Personel::getUrl() }}" style="color:#0f766e;font-weight:600;text-decoration:underline">Personel</a> sayfasından ekleyin.
    </p>

    @if ($this->paketler->isEmpty())
        <div style="{{ $kart }};border-left:4px solid #b45309">
            <div style="font-weight:700">Henüz eklenebilecek eğitim yok</div>
            <div style="font-size:.85rem;color:rgb(75 85 99)">İSG uzmanınız online eğitimi hazırladığında burada seçilebilir olacak.</div>
        </div>
    @endif

    {{ $this->table }}

    @if ($girisBilgileri)
        <div style="{{ $kart }};border-left:4px solid #0f766e">
            <div style="font-weight:700;margin-bottom:.2rem">Giriş bilgileri</div>
            <div style="font-size:.8rem;color:rgb(75 85 99);margin-bottom:.6rem">
                Portal: <strong>{{ url('/egitim') }}</strong> · Şifre yalnız şimdi gösterilir; çalışan ilk girişte kendi şifresini belirler.
            </div>
            <div style="overflow-x:auto">
                <table style="width:100%;border-collapse:collapse;font-size:.85rem">
                    <thead>
                        <tr style="text-align:left;color:rgb(107 114 128);border-bottom:1px solid rgb(107 114 128 / .25)">
                            <th style="padding:.4rem .5rem">Ad soyad</th>
                            <th style="padding:.4rem .5rem">Kullanıcı kodu</th>
                            <th style="padding:.4rem .5rem">Geçici şifre</th>
                            <th style="padding:.4rem .5rem"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($girisBilgileri as $g)
                            <tr style="border-bottom:1px solid rgb(107 114 128 / .12)">
                                <td style="padding:.45rem .5rem">{{ $g['ad_soyad'] }}</td>
                                <td style="padding:.45rem .5rem;font-family:ui-monospace,monospace">{{ $g['eposta'] }}</td>
                                <td style="padding:.45rem .5rem;font-family:ui-monospace,monospace">{{ $g['sifre'] ?? 'Kendi belirlediği şifre' }}</td>
                                <td style="padding:.45rem .5rem;text-align:right">
                                    <a href="{{ $this->whatsappLinki($g) }}" target="_blank" rel="noopener" style="color:#047857;font-weight:600">WhatsApp ile gönder</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif
</x-filament-panels::page>
