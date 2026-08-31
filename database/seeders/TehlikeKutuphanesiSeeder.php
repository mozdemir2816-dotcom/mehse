<?php

namespace Database\Seeders;

use App\Models\Tehlike;
use App\Models\TehlikeKategorisi;
use Illuminate\Database\Seeder;

/**
 * Risk Kütüphanesi başlangıç seti — yaygın işyeri tehlikeleri.
 * "Kütüphaneyi senkronize et" ile genişletilebilir.
 */
class TehlikeKutuphanesiSeeder extends Seeder
{
    public function run(): void
    {
        $set = [
            'genel_isyeri' => ['Genel İşyeri', [
                ['Elektrik Odası', 'Bakım / kontrol', 'Elektrik panosunda topraklama eksikliği', 'Elektrik çarpması, yangın', 'Periyodik topraklama ve pano ölçümü yaptırılır; pano önü boş bırakılır.', 'Elektrik Tesisatı Yönetmeliği'],
                ['Ofis', 'Ekranlı araçla çalışma', 'Ergonomik olmayan çalışma istasyonu', 'Kas-iskelet sistemi rahatsızlıkları', 'Ekran göz hizasına ayarlanır, ayarlanabilir koltuk sağlanır, mola planlanır.', 'Ekranlı Araçlarla Çalışmalarda İSG Yön.'],
                ['Merdiven / geçiş', 'Bina içi hareket', 'Kaygan / düzensiz zemin, korkuluksuz merdiven', 'Aynı seviyede / yüksekten düşme', 'Kaymaz zemin kaplaması, çift taraflı küpeşte, aydınlatma sağlanır.', 'İşyeri Bina ve Eklentileri Yön.'],
                ['Depo', 'İstifleme', 'Raf devrilmesi, yüksek istif', 'Malzeme düşmesi, ezilme', 'Raflar duvara sabitlenir, istif yüksekliği sınırlanır, forklift yolu ayrılır.', ''],
                ['Genel', 'Acil durum', 'Yetersiz yangın söndürücü / işaretlenmemiş çıkış', 'Yangında tahliye gecikmesi', 'Yeterli sayıda söndürücü, aylık kontrol, fosforlu yönlendirme levhaları.', 'İşyerlerinde Acil Durumlar Yön.'],
            ]],
            'insaat' => ['İnşaat / Yapı', [
                ['Şantiye', 'Yüksekte çalışma', 'Kenar koruması olmadan döşeme kenarında çalışma', 'Yüksekten düşme (ölüm)', 'Kenar koruma sistemi (korkuluk 1 m), güvenlik ağı, tam vücut kemeri + yaşam hattı.', 'Yapı İşlerinde İSG Yön. Ek-4'],
                ['Şantiye', 'Kazı', 'İksasız derin kazı', 'Göçük altında kalma', 'Şev açısı / iksa hesabı, kazı kenarında yük yasağı, güvenli iniş merdiveni.', 'Yapı İşlerinde İSG Yön.'],
                ['Şantiye', 'İskele', 'Kurulum kontrolü yapılmamış iskele', 'İskele çökmesi / düşme', 'Yetkili kişi kontrolü + scafftag, tam platform, çıkma korkuluğu.', ''],
                ['Şantiye', 'Kaldırma', 'Vinç altında personel bulunması', 'Yük düşmesi / çarpma', 'Yük altı yasak bölge, işaretçi, kaldırma planı, sapan kontrolü.', 'İş Ekipmanlarının Kullanımında İSG Yön.'],
            ]],
            'atolye' => ['Atölye / İmalat', [
                ['Atölye', 'Talaşlı imalat', 'Koruyucusu çıkarılmış tezgah', 'El / parmak kaptırma, kesik', 'Sabit ve hareketli koruyucular, acil stop, LOTO uygulaması.', 'İş Ekipmanları Yön.'],
                ['Atölye', 'Kaynak', 'Havalandırmasız ortamda kaynak dumanı', 'Solunum yolu hastalıkları, göz hasarı', 'Lokal duman emiş, kaynak maskesi (DIN), kıvılcım perdesi.', 'Kimyasal Maddelerle Çalışmalarda İSG Yön.'],
                ['Atölye', 'Elle taşıma', 'Ağır parçanın tek kişi taşınması', 'Bel incinmesi', 'Kaldırma yardımcıları, 2 kişi kuralı, doğru kaldırma eğitimi.', 'Elle Taşıma İşleri Yön.'],
                ['Atölye', 'Gürültü', '85 dB(A) üstü sürekli gürültü', 'Gürültüye bağlı işitme kaybı', 'Ortam ölçümü, kulak koruyucu, kaynakta izolasyon.', 'Çalışanların Gürültü ile İlgili Risklerden Korunmaları Yön.'],
            ]],
        ];

        foreach ($set as $anahtar => [$ad, $tehlikeler]) {
            $kategori = TehlikeKategorisi::updateOrCreate(
                ['anahtar' => $anahtar],
                ['ad' => $ad, 'sira' => array_search($anahtar, array_keys($set), true)],
            );

            foreach ($tehlikeler as $i => [$bolum, $faaliyet, $tehlike, $risk, $onlem, $mevzuat]) {
                Tehlike::updateOrCreate(
                    ['tehlike_kategorisi_id' => $kategori->id, 'tehlike' => $tehlike],
                    [
                        'kod' => strtoupper($anahtar[0]).($i + 1),
                        'bolum' => $bolum, 'faaliyet' => $faaliyet, 'risk' => $risk,
                        'mevcut_onlem' => $onlem, 'mevzuat' => $mevzuat ?: null,
                    ],
                );
            }
        }
    }
}
