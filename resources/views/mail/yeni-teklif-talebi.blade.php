Tanıtım sayfasından yeni bir teklif talebi geldi.

Firma: {{ $talep->firma_adi }}
Kişi: {{ $talep->kisi }}
Telefon: {{ $talep->telefon }}
Çalışan sayısı: {{ $talep->calisan_sayisi }}
Tehlike sınıfı: {{ $talep->tehlike_sinifi }}
Zaman: {{ $talep->created_at?->format('d.m.Y H:i') }}

Panelde görüntüle: {{ url('/admin/teklif-talepleri') }}
