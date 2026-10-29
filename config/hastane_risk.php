<?php

/*
|--------------------------------------------------------------------------
| Hastane risk şablonu (Bursa Şehir Hastanesi formu)
|--------------------------------------------------------------------------
| RG (risk grubu) harfi RP = O × F × Ş puanından, formun SKORLAMA sayfasındaki
| bantlarla belirlenir. Özet etiketleri formun alt bilgisindeki sayım bloğudur
| (A = çok yüksek … E = kabul edilebilir; 35 dosyanın 25'inde birebir tutuyor).
*/
return [
    'sablon_klasoru' => 'hastane-sablonlari',

    // Kaynak dosyası olmayan (sistemde sıfırdan açılan) hastane değerlendirmeleri bunu kalıp alır.
    'varsayilan_sablon' => ['dosya' => 'hastane-varsayilan.xlsx', 'sayfa' => 'HASTANE GENELİ'],

    'logo' => 'hastane-sablonlari/logo.jpg',

    // Üstten alta ilk eşleşen; min = alt sınır (dahil). RP ≥ 400 → A ...
    'bantlar' => [
        ['harf' => 'A', 'min' => 400, 'ad' => 'KIRMIZI', 'renk' => 'FF0000', 'ozet' => 'ÇOK YÜKSEK RİSK SAYISI'],
        ['harf' => 'B', 'min' => 200, 'ad' => 'KAHVERENGİ', 'renk' => 'FFC000', 'ozet' => 'YÜKSEK RİSK SAYISI'],
        ['harf' => 'C', 'min' => 70, 'ad' => 'SARI', 'renk' => 'FFFF00', 'ozet' => 'ÖNEMLİ RİSK SAYISI'],
        ['harf' => 'D', 'min' => 20, 'ad' => 'MAVİ', 'renk' => '00B0F0', 'ozet' => 'KESİN RİSK SAYISI'],
        ['harf' => 'E', 'min' => 0, 'ad' => 'YEŞİL', 'renk' => '92D050', 'ozet' => 'KABUL EDİLEBİLİR RİSK'],
    ],

    // Excel'de bir satır en çok bu kadar yüksek olabilir (Excel sınırı 409,5 pt).
    'azami_satir_yuksekligi' => 409.0,
];
