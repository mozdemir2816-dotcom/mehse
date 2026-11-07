<?php

use App\Models\EgitimPaketi;
use Illuminate\Database\Migrations\Migration;

/**
 * Zorunlu Temel İSG Eğitimi paketi: eğitim sonrası (final) sınav havuzu.
 * Mevcut 10 soru yalnız ön teste ayrılır; final sınavı bu 30 sorudan
 * (her konu başlığından 10) rastgele 20 soru çeker. Her denemede sorular
 * ve sıraları değişir.
 */
return new class extends Migration
{
    public const PAKET = 'Zorunlu Temel İSG Eğitimi (Tüm İşyerleri)';

    /** @return array<int, array{0: string, 1: array<int, string>, 2: int, 3: string}> */
    public static function sorular(): array
    {
        return [
            // ── Genel Konular ──
            ['Risk değerlendirmesi yapmak veya yaptırmakla yükümlü olan kimdir?',
                ['Çalışan temsilcisi', 'İşveren', 'Çalışma ve Sosyal Güvenlik Bakanlığı', 'İşyeri hekimi tek başına'], 1,
                '6331 sayılı Kanun md.10: risk değerlendirmesi yapmak veya yaptırmak işverenin yükümlülüğüdür; çalışanlar da sürece katılır.'],
            ['Çok tehlikeli sınıftaki bir işyerinde risk değerlendirmesi en geç kaç yılda bir yenilenir?',
                ['Her yıl', 'İki yılda bir', 'Dört yılda bir', 'Altı yılda bir'], 1,
                'Risk Değerlendirmesi Yönetmeliği: çok tehlikeli 2, tehlikeli 4, az tehlikeli 6 yılda bir yenilenir.'],
            ['Aşağıdakilerden hangisi "ramak kala olay"dır?',
                ['Çalışanın yaralandığı kaza', 'Yaralanma veya hasar olmadan atlatılan, kazaya dönüşebilecek olay', 'Yıllık izin kullanımı', 'Meslek hastalığı tanısı'], 1,
                'Ramak kala olay; yaralanma ya da hasar oluşmadan atlatılan ama kazaya dönüşebilecek olaydır. Bildirilmesi benzer kazaları önler.'],
            ['İş sağlığı ve güvenliği önlemlerinin maliyeti için aşağıdakilerden hangisi doğrudur?',
                ['Çalışanın maaşından kesilir', 'Yarısı çalışana yansıtılır', 'Çalışana yansıtılamaz', 'Yalnızca KKD bedeli çalışandan alınır'], 2,
                '6331 sayılı Kanun md.4/3: İSG tedbirlerinin maliyeti çalışanlara yansıtılamaz.'],
            ['50 ve daha fazla çalışanı olan ve altı aydan fazla süren sürekli işlerin yapıldığı işyerlerinde hangisinin kurulması zorunludur?',
                ['İş sağlığı ve güvenliği kurulu', 'Disiplin kurulu', 'Sendika şubesi', 'Kalite kurulu'], 0,
                '6331 sayılı Kanun md.22 gereği bu işyerlerinde İSG kurulu oluşturulur.'],
            ['2 ile 50 arasında çalışanı bulunan bir işyerinde en az kaç çalışan temsilcisi görevlendirilir?',
                ['Hiç gerekmez', '1', '2', '3'], 1,
                '6331 sayılı Kanun md.20: 2–50 çalışanı olan işyerinde 1 çalışan temsilcisi görevlendirilir.'],
            ['Çalışan, işe gidip gelirken işverenin sağladığı servis aracında kaza geçirirse bu olay nasıl değerlendirilir?',
                ['Trafik kazası sayılır, iş kazası değildir', 'İş kazası sayılır', 'Çalışanın kusuru sayılır', 'Hiçbir şekilde kayda geçmez'], 1,
                '5510 sayılı Kanun md.13: işverence sağlanan taşıtla işin yapıldığı yere gidiş-gelişte meydana gelen kaza iş kazasıdır.'],
            ['Çok tehlikeli sınıftaki işyerlerinde çalışanların temel İSG eğitimi en az ne sıklıkla tekrarlanır?',
                ['Her yıl', 'İki yılda bir', 'Üç yılda bir', 'Beş yılda bir'], 0,
                'Çalışanların İSG Eğitimleri Yönetmeliği: çok tehlikeli yılda bir, tehlikeli iki yılda bir, az tehlikeli üç yılda bir.'],
            ['İşyerinde bir tehlike fark eden çalışan ne yapmalıdır?',
                ['Kendi işine bakar, kimseye söylemez', 'Durumu derhal işverene veya çalışan temsilcisine bildirir', 'Mesai bitiminde sosyal medyada paylaşır', 'Yalnızca bir sonraki eğitimde dile getirir'], 1,
                '6331 sayılı Kanun md.19: çalışan, ciddi ve yakın tehlike ile koruma tedbirlerindeki eksiklikleri işverene veya temsilciye hemen bildirir.'],
            ['Uyuşturucu veya alkollü olarak işe gelinmesi için aşağıdakilerden hangisi doğrudur?',
                ['Az miktarda alkol serbesttir', 'Yalnızca gece vardiyasında yasaktır', 'Hem çalışanın hem çevresindekilerin güvenliğini tehlikeye attığı için yasaktır', 'Ustabaşı izin verirse serbesttir'], 2,
                'Alkol ve uyuşturucu dikkat ve refleksleri bozar; işyerine bu şekilde gelmek ve çalışmak yasaktır.'],

            // ── Sağlık Konuları ──
            ['Meslek hastalığı nedir?',
                ['Evde geçirilen kaza', 'Mesleki risklere maruziyet sonucu ortaya çıkan hastalık', 'Mevsimsel grip', 'Doğuştan gelen hastalık'], 1,
                'Meslek hastalığı; yapılan işin niteliği veya çalışma koşulları nedeniyle tekrarlanan maruziyet sonucu oluşan hastalıktır.'],
            ['Taş ocağı, kum püskürtme ve maden işlerinde solunan serbest silis tozunun neden olduğu meslek hastalığı hangisidir?',
                ['Silikozis', 'Diyabet', 'Migren', 'Sarılık'], 0,
                'Serbest kristal silis tozu akciğerde kalıcı hasar bırakan silikozise yol açar; toz maskesi ve ıslak çalışma önemlidir.'],
            ['İşe giriş ve periyodik sağlık muayenelerinin masrafını kim karşılar?',
                ['Çalışan', 'İşveren', 'Çalışan ile işveren yarı yarıya', 'Sendika'], 1,
                '6331 sayılı Kanun md.15: sağlık gözetimi işverence yaptırılır, maliyeti çalışana yansıtılamaz.'],
            ['İlk yardımda olay yerinde yapılacak ilk iş aşağıdakilerden hangisidir?',
                ['Yaralıyı hemen ayağa kaldırmak', 'Olay yerinin güvenliğini sağlamak ve 112\'yi aramak', 'Yaralıya su içirmek', 'Yaralıyı kendi aracıyla hastaneye götürmek'], 1,
                'Önce kendinizin ve yaralının güvenliği sağlanır, ardından 112 aranır; omurga yaralanması şüphesinde hasta hareket ettirilmez.'],
            ['Gürültü Yönetmeliği\'ne göre kulak koruyucu kullanımının zorunlu olduğu üst maruziyet eylem değeri kaçtır?',
                ['65 dB(A)', '75 dB(A)', '85 dB(A)', '120 dB(A)'], 2,
                'Alt eylem değeri 80 dB(A), üst eylem değeri 85 dB(A), maruziyet sınır değeri 87 dB(A)\'dır; 85 dB(A) ve üzerinde kulak koruyucu kullanımı zorunludur.'],
            ['Ağır bir yükü elle kaldırırken doğru vücut duruşu hangisidir?',
                ['Dizler düz, belden eğilerek kaldırmak', 'Dizleri bükerek, sırt düz ve yük vücuda yakın kaldırmak', 'Yükü kollar uzatılmış şekilde tutmak', 'Kaldırırken gövdeyi yana çevirmek'], 1,
                'Dizler bükülür, sırt düz tutulur, yük vücuda yakın taşınır; kaldırırken gövde döndürülmez.'],
            ['Ekranlı araçlarla (bilgisayar) çalışırken aşağıdakilerden hangisi doğrudur?',
                ['Ekran göz hizasında veya biraz altında olmalı, belirli aralıklarla mola verilmelidir', 'Ekran mümkün olduğunca yüksekte olmalıdır', 'Hiç ara vermeden çalışmak gözleri dinlendirir', 'Pencere tam karşıdan ekrana yansımalıdır'], 0,
                'Ekranlı Araçlarla Çalışmalarda Sağlık ve Güvenlik Önlemleri Yönetmeliği: uygun yükseklik, yansımasız aydınlatma ve düzenli molalar.'],
            ['Kan ve vücut sıvılarıyla temas riski olan işlerde biyolojik risklere karşı en etkili önlemlerden biri hangisidir?',
                ['Eldiven ve el hijyeni, uygun olduğunda Hepatit B aşısı', 'Sadece bol su içmek', 'Kesici aletleri kapağını takarak atmak', 'Eldiveni yıkayıp tekrar kullanmak'], 0,
                'Eldiven kullanımı, el hijyeni ve aşılama biyolojik riskleri azaltır; kullanılmış iğnelerin kapağı tekrar takılmaz.'],
            ['Uzun süreli iş stresi, aşırı iş yükü ve işyerinde psikolojik taciz (mobbing) hangi risk grubuna girer?',
                ['Fiziksel riskler', 'Kimyasal riskler', 'Psikososyal riskler', 'Elektriksel riskler'], 2,
                'Stres, iş yükü, vardiya düzeni ve mobbing psikososyal risk etmenleridir ve risk değerlendirmesinde ele alınır.'],
            ['Kimyasal madde sıçrayan göz için ilk yardımda ne yapılır?',
                ['Göz ovuşturulur', 'Göz en az 15 dakika bol temiz suyla yıkanır ve sağlık kuruluşuna başvurulur', 'Göze merhem sürülür', 'Göz kapatılıp beklenir'], 1,
                'Kimyasal teması olan göz hemen bol akan suyla en az 15 dakika yıkanır; göz duşu bu amaçla bulundurulur.'],

            // ── Teknik Konular ──
            ['Elektrik çarpmasına uğramış bir kişiye müdahalede ilk yapılması gereken nedir?',
                ['Kişiyi çıplak elle hemen çekmek', 'Elektrik akımını kesmek veya kişiyi yalıtkan bir cisimle akımdan ayırmak', 'Üzerine su dökmek', 'Kişiyi sarsarak uyandırmak'], 1,
                'Akım kesilmeden kazazedeye dokunulmaz; sigorta kapatılır ya da kuru tahta gibi yalıtkan bir cisimle ayrılır.'],
            ['Elektrik kaynaklı bir yangında aşağıdaki söndürücülerden hangisi kullanılmamalıdır?',
                ['Karbondioksitli (CO2) söndürücü', 'Kuru kimyevi tozlu söndürücü', 'Su', 'Hiçbiri, hepsi kullanılabilir'], 2,
                'Su elektriği iletir; enerjili elektrik yangınlarında CO2 veya kuru kimyevi toz kullanılır.'],
            ['Yangın söndürme cihazı nasıl kullanılır?',
                ['Rüzgarı arkaya alarak, alevin dibine doğru', 'Rüzgara karşı, alevin tepesine doğru', 'Yangına sırtı dönük şekilde', 'Cihaz yere bırakılarak'], 0,
                'Söndürücü rüzgar arkadan alınarak ve alevin üst kısmına değil dibine (yanıcı maddeye) doğru sıkılır.'],
            ['Güvenlik işaretlerinde yuvarlak, mavi zeminli beyaz semboller ne anlama gelir?',
                ['Yasak', 'Uyarı', 'Zorunluluk (emredici)', 'Acil çıkış'], 2,
                'Mavi yuvarlak emredici işarettir (ör. baret takınız); kırmızı yasak, sarı üçgen uyarı, yeşil acil çıkış/ilk yardımdır.'],
            ['Bir kimyasal maddenin tehlikeleri, kullanımı, depolanması ve ilk yardım bilgileri hangi belgede yer alır?',
                ['Fatura', 'Güvenlik Bilgi Formu (GBF / SDS)', 'İrsaliye', 'Garanti belgesi'], 1,
                'Güvenlik Bilgi Formu 16 bölümden oluşur; kimyasalla çalışmadan önce okunmalı ve kolay ulaşılır yerde bulunmalıdır.'],
            ['Bir makinenin bakım veya onarımına başlamadan önce ne yapılmalıdır?',
                ['Makine çalışırken hızlıca müdahale edilir', 'Enerjisi kesilir, kilitlenir ve etiketlenir', 'Koruyucular çıkarılıp makine çalıştırılarak denenir', 'Yalnızca acil stop butonuna basılır'], 1,
                'Kilitleme-etiketleme ile makinenin bakım sırasında yanlışlıkla çalıştırılması önlenir.'],
            ['Makine koruyucuları (muhafazalar) için hangisi doğrudur?',
                ['İşi hızlandırmak için çıkarılabilir', 'Yerinde ve çalışır durumda tutulmalı, çıkarılmamalıdır', 'Sadece denetim günü takılır', 'Usta çalışanlar için gerekli değildir'], 1,
                'Koruyucular hareketli parçalara temas, sıkışma ve fırlayan parçalara karşı korur; devre dışı bırakılmaz.'],
            ['Yüksekte çalışırken düşmeye karşı kullanılması gereken kişisel koruyucu donanım hangisidir?',
                ['Bel kemeri', 'Sağlam bir noktaya bağlanmış paraşüt tipi emniyet kemeri', 'Kulak tıkacı', 'Toz maskesi'], 1,
                'Toplu koruma (korkuluk, ağ) yetersizse, sağlam bir ankraj noktasına bağlanmış paraşüt tipi emniyet kemeri kullanılır.'],
            ['Forklift gibi iş ekipmanlarını kim kullanabilir?',
                ['İşyerindeki her çalışan', 'Yalnızca bu iş için eğitim almış ve yetkilendirilmiş çalışan', 'Ehliyeti olan herkes', 'Stajyerler'], 1,
                'İş Ekipmanlarının Kullanımında Sağlık ve Güvenlik Şartları Yönetmeliği: özel risk taşıyan ekipmanı yalnız eğitimli ve görevli çalışan kullanır.'],
            ['Acil durum alarmı çaldığında çalışan ne yapmalıdır?',
                ['Eşyalarını toplamak için masasına döner', 'Asansörü kullanarak hızla çıkar', 'Panik yapmadan, işaretli kaçış yollarını kullanarak toplanma alanına gider', 'Alarmın gerçek olup olmadığını öğrenmek için bekler'], 2,
                'Tahliyede asansör kullanılmaz; kaçış yolları izlenerek toplanma alanına gidilir ve sayım için orada beklenir.'],
        ];
    }

    public function up(): void
    {
        $paket = EgitimPaketi::where('ad', self::PAKET)->first();

        if (! $paket || $paket->sorular()->where('kullanim', 'final')->exists()) {
            return;
        }

        // Mevcut havuz yalnız ön teste (seviye tespiti) ayrılır.
        $paket->sorular()->update(['kullanim' => 'on_test']);

        foreach (self::sorular() as [$soru, $secenekler, $dogru, $aciklama]) {
            $paket->sorular()->create([
                'soru' => $soru,
                'secenekler' => $secenekler,
                'dogru_index' => $dogru,
                'aciklama' => $aciklama,
                'kullanim' => 'final',
            ]);
        }

        $paket->update(['sinav_soru_sayisi' => 20]);
    }

    public function down(): void
    {
        $paket = EgitimPaketi::where('ad', self::PAKET)->first();
        $paket?->sorular()->where('kullanim', 'final')->delete();
        $paket?->sorular()->update(['kullanim' => 'ortak']);
        $paket?->update(['sinav_soru_sayisi' => 10]);
    }
};
