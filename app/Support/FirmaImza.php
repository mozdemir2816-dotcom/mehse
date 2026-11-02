<?php

namespace App\Support;

use App\Models\Firma;

/**
 * Belgelerdeki imza / künye kişileri tek yerden (02.11.2026): firmaya atanmış İGU (yoksa hesap sahibi)
 * ve işveren / işveren vekili. DÖF raporu, Tespit-Öneri Defteri PDF'leri ve WhatsApp imzası kullanır.
 */
class FirmaImza
{
    /**
     * @return array{ad: ?string, unvan: string, kisa_unvan: string, sinif: ?string, belge_no: ?string}
     */
    public static function uzman(?Firma $firma): array
    {
        $igu = $firma?->igu;
        $sahip = $firma?->user;
        $ham = (string) ($igu?->unvan ?: ($sahip?->unvan ? $sahip->unvanEtiketi() : ''));

        // "A SINIFI İŞ GÜVENLİĞİ UZMANI" / "A Sınıfı …" → sınıf harfinden standart unvan.
        $sinif = preg_match('/\b([ABC])\s*SINIFI/u', mb_strtoupper(strtr($ham, ['ı' => 'I', 'i' => 'İ']), 'UTF-8'), $e) ? $e[1] : null;

        return [
            'ad' => ($igu?->ad_soyad ?: $sahip?->name) ?: null,
            'unvan' => $sinif ? $sinif.' Sınıfı İş Güvenliği Uzmanı' : ($ham !== '' ? $ham : 'İş Güvenliği Uzmanı'),
            'kisa_unvan' => $sinif ? $sinif.' Sınıfı İGU' : 'İGU',
            'sinif' => $sinif,
            'belge_no' => ($igu?->sertifika_no ?: $sahip?->sertifika_no) ?: null,
        ];
    }

    public static function isveren(?Firma $firma): ?string
    {
        return trim((string) ($firma?->isveren_vekili ?: $firma?->isveren_ad)) ?: null;
    }
}
