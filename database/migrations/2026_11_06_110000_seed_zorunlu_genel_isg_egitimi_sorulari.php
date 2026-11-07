<?php

use App\Models\EgitimPaketi;
use Illuminate\Database\Migrations\Migration;

/**
 * Zorunlu Temel İSG Eğitimi paketi için 10 soruluk havuz. Aynı havuz hem seviye tespit
 * (ön test) hem final sınavında kullanılır; her seferinde sıra rastgele gelir.
 */
return new class extends Migration
{
    public const PAKET = 'Zorunlu Temel İSG Eğitimi (Tüm İşyerleri)';

    public static function sorular(): array
    {
        return [
            ['İş sağlığı ve güvenliği eğitimini çalışanlara vermekle yükümlü olan kimdir?',
                ['Sendika temsilcisi', 'İşveren', 'Çalışanın kendisi', 'Belediye'], 1,
                '6331 sayılı Kanun gereği çalışanların İSG eğitimlerini sağlamak işverenin yükümlülüğüdür.'],
            ['Ciddi ve yakın tehlike bulunan bir işyerinde çalışan ne yapabilir?',
                ['Tehlike geçene kadar bekler, işe devam eder', 'Yalnızca arkadaşlarına söyler', 'Kurula veya işverene başvurarak işi bırakabilir ve bu nedenle haklarından yoksun bırakılamaz', 'İşyerinden ayrılmak için izin bekler'], 2,
                'Çalışan, kurula veya işverene başvurarak işi bırakma hakkına sahiptir; bu durumda hakları saklıdır.'],
            ['Aşağıdakilerden hangisi çalışanın yükümlülüklerinden biridir?',
                ['Kişisel koruyucu donanımı talimata uygun kullanmak', 'Risk değerlendirmesini tek başına hazırlamak', 'İşyeri hekimini görevlendirmek', 'İş güvenliği uzmanının ücretini ödemek'], 0,
                'Çalışan, verilen eğitim ve talimatlara uygun davranmak ve KKD\'yi doğru kullanmakla yükümlüdür.'],
            ['İş kazası, kazadan sonraki kaç iş günü içinde Sosyal Güvenlik Kurumu\'na bildirilmelidir?',
                ['Bir ay', 'Üç iş günü', 'Bir yıl', 'Bildirim zorunlu değildir'], 1,
                '5510 sayılı Kanun gereği iş kazası, kazadan sonraki üç iş günü içinde bildirilir.'],
            ['İş kazası veya meslek hastalığı sonucunda işveren ve ilgili kişiler hangi sorumluluklarla karşılaşabilir?',
                ['Yalnızca idari para cezası', 'Yalnızca tazminat', 'Hiçbir sorumluluk doğmaz', 'İdari, hukuki (tazminat) ve cezai sorumluluk'], 3,
                'İş kazası ve meslek hastalığı; idari yaptırım, tazminat ve ceza davası gibi sonuçlar doğurabilir.'],
            ['Acil çıkış yolları ve kapıları için aşağıdakilerden hangisi doğrudur?',
                ['Depolama için kullanılabilir', 'Mesai dışında kilitlenebilir', 'Her zaman açık, işaretli ve engelsiz tutulmalıdır', 'Yalnızca yangın tatbikatında açılmalıdır'], 2,
                'Acil çıkışlar her an kullanılabilir olmalı, önleri hiçbir malzeme ile kapatılmamalıdır.'],
            ['İSG eğitimleri hangi süre içinde verilir ve bu süre nasıl sayılır?',
                ['Çalışma saatleri dışında, ücretsiz', 'Tatil günlerinde', 'Çalışma saatleri içinde; süreler çalışma süresinden sayılır', 'Yalnızca öğle arasında'], 2,
                '6331 sayılı Kanun gereği eğitimler çalışma saatleri içinde yapılır ve ücret kaybı doğurmaz.'],
            ['Az tehlikeli sınıftaki işyerlerinde temel İSG eğitimi en az ne sıklıkla tekrarlanır?',
                ['Her yıl', 'İki yılda bir', 'Beş yılda bir', 'Üç yılda bir'], 3,
                'Çalışanların İSG Eğitimleri Yönetmeliği\'ne göre az tehlikeli işyerlerinde en az üç yılda bir tekrar edilir.'],
            ['Zeminde dökülen bir sıvıyı fark eden çalışan ilk olarak ne yapmalıdır?',
                ['Görmezden gelir', 'Yaşanabilecek kaymaya karşı alanı işaretler ve sıvıyı temizler ya da temizlenmesini sağlar', 'Üzerine kağıt koyup geçer', 'Mesai bitimini bekler'], 1,
                'Kayma ve düşmeleri önlemek için dökülen sıvı hemen uyarı işaretiyle belirtilir ve temizlenir.'],
            ['4857 sayılı İş Kanunu\'na göre genel olarak haftalık normal çalışma süresi en çok kaç saattir?',
                ['35 saat', '40 saat', '45 saat', '60 saat'], 2,
                'Haftalık çalışma süresi en çok 45 saattir; taraflarca günlere bölünerek uygulanır.'],
        ];
    }

    public function up(): void
    {
        $paket = EgitimPaketi::where('ad', self::PAKET)->first();

        if (! $paket || $paket->sorular()->exists()) {
            return;
        }

        foreach (self::sorular() as [$soru, $secenekler, $dogru, $aciklama]) {
            $paket->sorular()->create([
                'soru' => $soru,
                'secenekler' => $secenekler,
                'dogru_index' => $dogru,
                'aciklama' => $aciklama,
            ]);
        }

        $paket->update(['sinav_soru_sayisi' => 10]);
    }

    public function down(): void
    {
        EgitimPaketi::where('ad', self::PAKET)->first()?->sorular()->delete();
    }
};
