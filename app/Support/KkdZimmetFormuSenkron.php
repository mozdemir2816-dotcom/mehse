<?php

namespace App\Support;

use App\Models\KkdZimmet;
use App\Models\KkdZimmetFormu;

/**
 * KkdFormu (toplu teslim formu) kaydedildiğinde, aynı teslimi KkdTakip'in
 * kalıcı sicilinde (kkd_zimmetleri) de görünür kılar. Formdan gelen
 * zimmetlerde hiçbir zaman kkd_stok_karti_id atanmaz: KkdFormu akışında
 * stok kartı seçimi yoktur, otomatik/hatalı bir eşleştirme yanlış stok
 * düşümüne yol açabilir — bu satırlar KkdTakip'teki "stok kartı seçmeden
 * devam et" ile aynı opsiyonel davranışı taklit eder, stok sayımını
 * etkilemez (bkz. App\Support\KkdStok — stokKarti ilişkisi null'sa hareket
 * oluşturulmaz).
 *
 * Her senkronEt() çağrısı, bu forma ait önceki otomatik satırları SİLİP
 * yeniden oluşturur (idempotent re-save) — form tekrar kaydedilirse, o
 * forma bağlı satırlardan biri KkdTakip ekranından elle "iade edildi" gibi
 * değiştirilmiş olsa bile bu değişiklik kaybolur. Bu, KaydetSecenegi'nin
 * zaten forma uyguladığı "tekrar kaydet = tam güncelleme" davranışıyla
 * tutarlıdır.
 */
class KkdZimmetFormuSenkron
{
    /**
     * @param  array<int, array{ad_soyad: string, tc: ?string, departman: ?string, calisan_id: ?int}>  $calisanlar
     * @param  array<int, array{ad: string, standart: ?string, kategori_anahtari: ?string, kategori_adi: string}>  $kkdKalemleri
     */
    public static function senkronEt(KkdZimmetFormu $form, array $calisanlar, array $kkdKalemleri): void
    {
        KkdZimmet::where('kkd_zimmet_formu_id', $form->id)->delete();

        if (! $calisanlar || ! $kkdKalemleri) {
            return;
        }

        foreach ($calisanlar as $calisan) {
            foreach ($kkdKalemleri as $kalem) {
                KkdZimmet::create([
                    'firma_id' => $form->firma_id,
                    'calisan_id' => $calisan['calisan_id'] ?? null,
                    'kkd_stok_karti_id' => null,
                    'kkd_zimmet_formu_id' => $form->id,
                    'personel_ad_soyad' => $calisan['ad_soyad'],
                    'bolum' => $calisan['departman'] ?? null,
                    'teslim_tarihi' => $form->teslim_tarihi,
                    'adet' => 1,
                    'kategori' => $kalem['kategori_anahtari'] ?? $kalem['kategori_adi'],
                    'tur' => $kalem['ad'],
                    'son_kullanma' => null,
                    'yenileme_tarihi' => $form->periyodik_kontrol_tarihi,
                    'durum' => 'teslim_edildi',
                    'teslim_eden' => $form->teslim_eden,
                ]);
            }
        }
    }
}
