<?php

namespace App\Support;

/**
 * Sahada mobil cihazdan okunup imzalanabilen hazır Toolbox (iş başı) konuşma kartları — her biri 5 maddelik özet.
 * Kullanıcının yüklediği Word/PDF konuşmalarından ayrıdır; imza föyü PDF'i (pdf.toolbox-imza-foyu) kartın konusuyla üretilir.
 */
class ToolboxKartlari
{
    /** @return array<string, array{baslik: string, maddeler: array<int, string>}> */
    public static function liste(): array
    {
        return [
            'iskele' => ['baslik' => 'İskele Güvenliği', 'maddeler' => [
                'İskele kurulumu yetkin ve eğitimli personel tarafından yapılır; kullanım öncesi etiket ve kontrol formu kontrol edilir.',
                'Zemin sağlam ve düz olmalı, ayaklar taban plakasına oturmalıdır.',
                '2 metreyi aşan çalışma katlarında korkuluk, ara korkuluk ve topuk tahtası bulunmalıdır.',
                'Platformlara tırmanma yalnız iç merdivenlerden yapılır; iskele çaprazlarına tırmanılmaz.',
                'Rüzgar, yağış veya hasar şüphesinde çalışma durdurulur; iskele yeniden kontrol edilmeden kullanılmaz.',
            ]],
            'sicak_is' => ['baslik' => 'Sıcak İşler (Kaynak / Taşlama)', 'maddeler' => [
                'Sıcak iş izni alınmadan çalışmaya başlanmaz; izin süresi ve alanı kontrol edilir.',
                'Çalışma alanındaki yanıcı malzemeler uzaklaştırılır veya yanmaz örtüyle korunur.',
                'Yangın söndürücü hazır bulundurulur; gerekirse yangın gözcüsü görevlendirilir.',
                'Kaynak maskesi, eldiven ve alev almaz iş elbisesi giyilir; kıvılcım yönü kontrol edilir.',
                'İş bitiminde alan en az 30-60 dakika gözlenir; sıcak parçalar işaretlenir.',
            ]],
            'kapali_alan' => ['baslik' => 'Kapalı Alan Çalışması', 'maddeler' => [
                'Kapalı alana giriş için yazılı izin ve gaz ölçümü (O₂, yanıcı, toksik) zorunludur.',
                'Giriş yapan kişi dışında dışarıda sürekli gözetleyici bulunur; iletişim kesilmez.',
                'Havalandırma sağlanır; kurtarma ekipmanı ve emniyet kemeri/halatı hazır tutulur.',
                'Hiçbir koşulda kurtarma amacıyla yetkisiz kişi içeri girmez; önce yardım istenir.',
                'Çalışma bitince alan sayılır, izin kapatılır ve girişler kapatılır.',
            ]],
            'forklift' => ['baslik' => 'Forklift / İstif Makineleri', 'maddeler' => [
                'Yalnızca operatör belgesi olan kişiler forklift kullanır; günlük kontrol formu doldurulur.',
                'Yük kaldırılırken çatallar tam altına sokulur, yük düşük konumda taşınır.',
                'Yaya yollarında hız sınırına uyulur; köşelerde korna çalınır.',
                'Kapasite plakasındaki yük sınırı aşılmaz; çatalda insan taşınmaz.',
                'Park halinde çatallar yere indirilir, kontak kapatılır, el freni çekilir.',
            ]],
            'elektrik' => ['baslik' => 'Elektrik Güvenliği', 'maddeler' => [
                'Elektrik işleri yalnız yetkili personel tarafından yapılır; kilitleme-etiketleme uygulanır.',
                'Hasarlı kablo, priz ve fişler kullanılmaz; arızalı ekipman işaretlenerek kaldırılır.',
                'Islak ortamda çalışan ekipmanlarda kaçak akım rölesi ve topraklama kontrol edilir.',
                'Uzatma kablosu yerde ezilecek biçimde bırakılmaz; çoklu priz aşırı yüklenmez.',
                'Elektrik çarpmasında önce enerji kesilir, sonra müdahale edilir ve 112 aranır.',
            ]],
            'kimyasal' => ['baslik' => 'Kimyasal Madde Kullanımı', 'maddeler' => [
                'Kullanmadan önce Güvenlik Bilgi Formu (SDS) okunur; etiket ve piktogramlar kontrol edilir.',
                'Uygun KKD (eldiven, gözlük, maske) kullanılır; kimyasallar yalnız etiketli kaplarda saklanır.',
                'Birbiriyle tepkimeye girebilecek kimyasallar ayrı yerlerde depolanır.',
                'Dökülme halinde alan boşaltılır, uygun emici malzeme kullanılır, amir bilgilendirilir.',
                'Göz veya cilt temasında bol suyla en az 15 dakika yıkanır ve sağlık birimine başvurulur.',
            ]],
        ];
    }
}
