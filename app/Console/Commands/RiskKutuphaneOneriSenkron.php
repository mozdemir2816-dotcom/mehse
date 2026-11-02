<?php

namespace App\Console\Commands;

/**
 * Kütüphanedeki önerisi BOŞ maddeleri tamamlar (02.11.2026): önce doldurulmuş analizlerden (kotasız),
 * kalanı yapay zekâ ile. Aynı motor: risk:repair-library-all-fields --sadece-oneri (öneri boş maddelerin
 * diğer boş alanları — mevzuat, puan, sorumlu, termin — da aynı istekte dolar).
 */
class RiskKutuphaneOneriSenkron extends RiskKutuphaneOnar
{
    protected $signature = 'risk:sync-library-recommendations
        {--limit=80 : Yapay zekâya gönderilecek en fazla madde (40\'lık partiler)}
        {--kategori= : Yalnız bu kütüphane klasörü (tehlike_kategorileri.anahtar)}
        {--zamanlanmis : Zamanlayıcıdan çağrı — günlük istek sınırı uygulanır}
        {--sifirla : Daha önce denenip doldurulamayan maddeleri yeniden dene}
        {--sadece-oneri=1 : (sabit) yalnız önerisi boş maddeler}';

    protected $description = 'Risk kütüphanesinde önerisi boş maddeleri analizlerden ve yapay zekâ ile kalıcı doldurur';
}
