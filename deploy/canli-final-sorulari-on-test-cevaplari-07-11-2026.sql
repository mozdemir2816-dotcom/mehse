-- Uzaktan eğitim: ön test cevaplarının kaydı + ayrı final sınavı soru havuzu (Zorunlu Temel İSG Eğitimi, 30 soru).
-- Sırayla: ÖNCE bu SQL (phpMyAdmin > mehse veritabanı > SQL), SONRA kod deploy. Aksi halde portal ve paket ekranı hata verir.
-- BİR KEZ çalıştırın. "Duplicate column name" alırsanız o sütun zaten eklenmiştir; ALTER satırını atlayıp devam edin.
-- Soru ekleme kısmı tekrar çalıştırılırsa ikinci kez eklemez.

ALTER TABLE `egitim_atamalari`
  ADD COLUMN `on_test_cevaplar` json DEFAULT NULL AFTER `on_test_at`;

ALTER TABLE `egitim_paketi_sorulari`
  ADD COLUMN `kullanim` varchar(10) NOT NULL DEFAULT 'ortak' AFTER `aciklama`;

-- Mevcut 10 soru yalnız ön teste ayrılır (final soruları henüz yoksa).
UPDATE `egitim_paketi_sorulari` s
JOIN `egitim_paketleri` p ON p.id = s.egitim_paketi_id AND p.ad = 'Zorunlu Temel İSG Eğitimi (Tüm İşyerleri)'
SET s.kullanim = 'on_test'
WHERE NOT EXISTS (SELECT 1 FROM (SELECT egitim_paketi_id FROM `egitim_paketi_sorulari` WHERE kullanim = 'final') f WHERE f.egitim_paketi_id = p.id);

-- 30 final sorusu (her konu başlığından 10).
INSERT INTO `egitim_paketi_sorulari` (`egitim_paketi_id`, `soru`, `secenekler`, `dogru_index`, `aciklama`, `kullanim`, `created_at`, `updated_at`)
SELECT p.id, v.soru, v.secenekler, v.dogru_index, v.aciklama, 'final', NOW(), NOW()
FROM `egitim_paketleri` p
JOIN (
  SELECT 'Risk değerlendirmesi yapmak veya yaptırmakla yükümlü olan kimdir?' AS soru, '["Çalışan temsilcisi","İşveren","Çalışma ve Sosyal Güvenlik Bakanlığı","İşyeri hekimi tek başına"]' AS secenekler, 1 AS dogru_index, '6331 sayılı Kanun md.10: risk değerlendirmesi yapmak veya yaptırmak işverenin yükümlülüğüdür; çalışanlar da sürece katılır.' AS aciklama
  UNION ALL
  SELECT 'Çok tehlikeli sınıftaki bir işyerinde risk değerlendirmesi en geç kaç yılda bir yenilenir?' AS soru, '["Her yıl","İki yılda bir","Dört yılda bir","Altı yılda bir"]' AS secenekler, 1 AS dogru_index, 'Risk Değerlendirmesi Yönetmeliği: çok tehlikeli 2, tehlikeli 4, az tehlikeli 6 yılda bir yenilenir.' AS aciklama
  UNION ALL
  SELECT 'Aşağıdakilerden hangisi "ramak kala olay"dır?' AS soru, '["Çalışanın yaralandığı kaza","Yaralanma veya hasar olmadan atlatılan, kazaya dönüşebilecek olay","Yıllık izin kullanımı","Meslek hastalığı tanısı"]' AS secenekler, 1 AS dogru_index, 'Ramak kala olay; yaralanma ya da hasar oluşmadan atlatılan ama kazaya dönüşebilecek olaydır. Bildirilmesi benzer kazaları önler.' AS aciklama
  UNION ALL
  SELECT 'İş sağlığı ve güvenliği önlemlerinin maliyeti için aşağıdakilerden hangisi doğrudur?' AS soru, '["Çalışanın maaşından kesilir","Yarısı çalışana yansıtılır","Çalışana yansıtılamaz","Yalnızca KKD bedeli çalışandan alınır"]' AS secenekler, 2 AS dogru_index, '6331 sayılı Kanun md.4/3: İSG tedbirlerinin maliyeti çalışanlara yansıtılamaz.' AS aciklama
  UNION ALL
  SELECT '50 ve daha fazla çalışanı olan ve altı aydan fazla süren sürekli işlerin yapıldığı işyerlerinde hangisinin kurulması zorunludur?' AS soru, '["İş sağlığı ve güvenliği kurulu","Disiplin kurulu","Sendika şubesi","Kalite kurulu"]' AS secenekler, 0 AS dogru_index, '6331 sayılı Kanun md.22 gereği bu işyerlerinde İSG kurulu oluşturulur.' AS aciklama
  UNION ALL
  SELECT '2 ile 50 arasında çalışanı bulunan bir işyerinde en az kaç çalışan temsilcisi görevlendirilir?' AS soru, '["Hiç gerekmez","1","2","3"]' AS secenekler, 1 AS dogru_index, '6331 sayılı Kanun md.20: 2–50 çalışanı olan işyerinde 1 çalışan temsilcisi görevlendirilir.' AS aciklama
  UNION ALL
  SELECT 'Çalışan, işe gidip gelirken işverenin sağladığı servis aracında kaza geçirirse bu olay nasıl değerlendirilir?' AS soru, '["Trafik kazası sayılır, iş kazası değildir","İş kazası sayılır","Çalışanın kusuru sayılır","Hiçbir şekilde kayda geçmez"]' AS secenekler, 1 AS dogru_index, '5510 sayılı Kanun md.13: işverence sağlanan taşıtla işin yapıldığı yere gidiş-gelişte meydana gelen kaza iş kazasıdır.' AS aciklama
  UNION ALL
  SELECT 'Çok tehlikeli sınıftaki işyerlerinde çalışanların temel İSG eğitimi en az ne sıklıkla tekrarlanır?' AS soru, '["Her yıl","İki yılda bir","Üç yılda bir","Beş yılda bir"]' AS secenekler, 0 AS dogru_index, 'Çalışanların İSG Eğitimleri Yönetmeliği: çok tehlikeli yılda bir, tehlikeli iki yılda bir, az tehlikeli üç yılda bir.' AS aciklama
  UNION ALL
  SELECT 'İşyerinde bir tehlike fark eden çalışan ne yapmalıdır?' AS soru, '["Kendi işine bakar, kimseye söylemez","Durumu derhal işverene veya çalışan temsilcisine bildirir","Mesai bitiminde sosyal medyada paylaşır","Yalnızca bir sonraki eğitimde dile getirir"]' AS secenekler, 1 AS dogru_index, '6331 sayılı Kanun md.19: çalışan, ciddi ve yakın tehlike ile koruma tedbirlerindeki eksiklikleri işverene veya temsilciye hemen bildirir.' AS aciklama
  UNION ALL
  SELECT 'Uyuşturucu veya alkollü olarak işe gelinmesi için aşağıdakilerden hangisi doğrudur?' AS soru, '["Az miktarda alkol serbesttir","Yalnızca gece vardiyasında yasaktır","Hem çalışanın hem çevresindekilerin güvenliğini tehlikeye attığı için yasaktır","Ustabaşı izin verirse serbesttir"]' AS secenekler, 2 AS dogru_index, 'Alkol ve uyuşturucu dikkat ve refleksleri bozar; işyerine bu şekilde gelmek ve çalışmak yasaktır.' AS aciklama
  UNION ALL
  SELECT 'Meslek hastalığı nedir?' AS soru, '["Evde geçirilen kaza","Mesleki risklere maruziyet sonucu ortaya çıkan hastalık","Mevsimsel grip","Doğuştan gelen hastalık"]' AS secenekler, 1 AS dogru_index, 'Meslek hastalığı; yapılan işin niteliği veya çalışma koşulları nedeniyle tekrarlanan maruziyet sonucu oluşan hastalıktır.' AS aciklama
  UNION ALL
  SELECT 'Taş ocağı, kum püskürtme ve maden işlerinde solunan serbest silis tozunun neden olduğu meslek hastalığı hangisidir?' AS soru, '["Silikozis","Diyabet","Migren","Sarılık"]' AS secenekler, 0 AS dogru_index, 'Serbest kristal silis tozu akciğerde kalıcı hasar bırakan silikozise yol açar; toz maskesi ve ıslak çalışma önemlidir.' AS aciklama
  UNION ALL
  SELECT 'İşe giriş ve periyodik sağlık muayenelerinin masrafını kim karşılar?' AS soru, '["Çalışan","İşveren","Çalışan ile işveren yarı yarıya","Sendika"]' AS secenekler, 1 AS dogru_index, '6331 sayılı Kanun md.15: sağlık gözetimi işverence yaptırılır, maliyeti çalışana yansıtılamaz.' AS aciklama
  UNION ALL
  SELECT 'İlk yardımda olay yerinde yapılacak ilk iş aşağıdakilerden hangisidir?' AS soru, '["Yaralıyı hemen ayağa kaldırmak","Olay yerinin güvenliğini sağlamak ve 112''yi aramak","Yaralıya su içirmek","Yaralıyı kendi aracıyla hastaneye götürmek"]' AS secenekler, 1 AS dogru_index, 'Önce kendinizin ve yaralının güvenliği sağlanır, ardından 112 aranır; omurga yaralanması şüphesinde hasta hareket ettirilmez.' AS aciklama
  UNION ALL
  SELECT 'Gürültü Yönetmeliği''ne göre kulak koruyucu kullanımının zorunlu olduğu üst maruziyet eylem değeri kaçtır?' AS soru, '["65 dB(A)","75 dB(A)","85 dB(A)","120 dB(A)"]' AS secenekler, 2 AS dogru_index, 'Alt eylem değeri 80 dB(A), üst eylem değeri 85 dB(A), maruziyet sınır değeri 87 dB(A)''dır; 85 dB(A) ve üzerinde kulak koruyucu kullanımı zorunludur.' AS aciklama
  UNION ALL
  SELECT 'Ağır bir yükü elle kaldırırken doğru vücut duruşu hangisidir?' AS soru, '["Dizler düz, belden eğilerek kaldırmak","Dizleri bükerek, sırt düz ve yük vücuda yakın kaldırmak","Yükü kollar uzatılmış şekilde tutmak","Kaldırırken gövdeyi yana çevirmek"]' AS secenekler, 1 AS dogru_index, 'Dizler bükülür, sırt düz tutulur, yük vücuda yakın taşınır; kaldırırken gövde döndürülmez.' AS aciklama
  UNION ALL
  SELECT 'Ekranlı araçlarla (bilgisayar) çalışırken aşağıdakilerden hangisi doğrudur?' AS soru, '["Ekran göz hizasında veya biraz altında olmalı, belirli aralıklarla mola verilmelidir","Ekran mümkün olduğunca yüksekte olmalıdır","Hiç ara vermeden çalışmak gözleri dinlendirir","Pencere tam karşıdan ekrana yansımalıdır"]' AS secenekler, 0 AS dogru_index, 'Ekranlı Araçlarla Çalışmalarda Sağlık ve Güvenlik Önlemleri Yönetmeliği: uygun yükseklik, yansımasız aydınlatma ve düzenli molalar.' AS aciklama
  UNION ALL
  SELECT 'Kan ve vücut sıvılarıyla temas riski olan işlerde biyolojik risklere karşı en etkili önlemlerden biri hangisidir?' AS soru, '["Eldiven ve el hijyeni, uygun olduğunda Hepatit B aşısı","Sadece bol su içmek","Kesici aletleri kapağını takarak atmak","Eldiveni yıkayıp tekrar kullanmak"]' AS secenekler, 0 AS dogru_index, 'Eldiven kullanımı, el hijyeni ve aşılama biyolojik riskleri azaltır; kullanılmış iğnelerin kapağı tekrar takılmaz.' AS aciklama
  UNION ALL
  SELECT 'Uzun süreli iş stresi, aşırı iş yükü ve işyerinde psikolojik taciz (mobbing) hangi risk grubuna girer?' AS soru, '["Fiziksel riskler","Kimyasal riskler","Psikososyal riskler","Elektriksel riskler"]' AS secenekler, 2 AS dogru_index, 'Stres, iş yükü, vardiya düzeni ve mobbing psikososyal risk etmenleridir ve risk değerlendirmesinde ele alınır.' AS aciklama
  UNION ALL
  SELECT 'Kimyasal madde sıçrayan göz için ilk yardımda ne yapılır?' AS soru, '["Göz ovuşturulur","Göz en az 15 dakika bol temiz suyla yıkanır ve sağlık kuruluşuna başvurulur","Göze merhem sürülür","Göz kapatılıp beklenir"]' AS secenekler, 1 AS dogru_index, 'Kimyasal teması olan göz hemen bol akan suyla en az 15 dakika yıkanır; göz duşu bu amaçla bulundurulur.' AS aciklama
  UNION ALL
  SELECT 'Elektrik çarpmasına uğramış bir kişiye müdahalede ilk yapılması gereken nedir?' AS soru, '["Kişiyi çıplak elle hemen çekmek","Elektrik akımını kesmek veya kişiyi yalıtkan bir cisimle akımdan ayırmak","Üzerine su dökmek","Kişiyi sarsarak uyandırmak"]' AS secenekler, 1 AS dogru_index, 'Akım kesilmeden kazazedeye dokunulmaz; sigorta kapatılır ya da kuru tahta gibi yalıtkan bir cisimle ayrılır.' AS aciklama
  UNION ALL
  SELECT 'Elektrik kaynaklı bir yangında aşağıdaki söndürücülerden hangisi kullanılmamalıdır?' AS soru, '["Karbondioksitli (CO2) söndürücü","Kuru kimyevi tozlu söndürücü","Su","Hiçbiri, hepsi kullanılabilir"]' AS secenekler, 2 AS dogru_index, 'Su elektriği iletir; enerjili elektrik yangınlarında CO2 veya kuru kimyevi toz kullanılır.' AS aciklama
  UNION ALL
  SELECT 'Yangın söndürme cihazı nasıl kullanılır?' AS soru, '["Rüzgarı arkaya alarak, alevin dibine doğru","Rüzgara karşı, alevin tepesine doğru","Yangına sırtı dönük şekilde","Cihaz yere bırakılarak"]' AS secenekler, 0 AS dogru_index, 'Söndürücü rüzgar arkadan alınarak ve alevin üst kısmına değil dibine (yanıcı maddeye) doğru sıkılır.' AS aciklama
  UNION ALL
  SELECT 'Güvenlik işaretlerinde yuvarlak, mavi zeminli beyaz semboller ne anlama gelir?' AS soru, '["Yasak","Uyarı","Zorunluluk (emredici)","Acil çıkış"]' AS secenekler, 2 AS dogru_index, 'Mavi yuvarlak emredici işarettir (ör. baret takınız); kırmızı yasak, sarı üçgen uyarı, yeşil acil çıkış/ilk yardımdır.' AS aciklama
  UNION ALL
  SELECT 'Bir kimyasal maddenin tehlikeleri, kullanımı, depolanması ve ilk yardım bilgileri hangi belgede yer alır?' AS soru, '["Fatura","Güvenlik Bilgi Formu (GBF \\/ SDS)","İrsaliye","Garanti belgesi"]' AS secenekler, 1 AS dogru_index, 'Güvenlik Bilgi Formu 16 bölümden oluşur; kimyasalla çalışmadan önce okunmalı ve kolay ulaşılır yerde bulunmalıdır.' AS aciklama
  UNION ALL
  SELECT 'Bir makinenin bakım veya onarımına başlamadan önce ne yapılmalıdır?' AS soru, '["Makine çalışırken hızlıca müdahale edilir","Enerjisi kesilir, kilitlenir ve etiketlenir","Koruyucular çıkarılıp makine çalıştırılarak denenir","Yalnızca acil stop butonuna basılır"]' AS secenekler, 1 AS dogru_index, 'Kilitleme-etiketleme ile makinenin bakım sırasında yanlışlıkla çalıştırılması önlenir.' AS aciklama
  UNION ALL
  SELECT 'Makine koruyucuları (muhafazalar) için hangisi doğrudur?' AS soru, '["İşi hızlandırmak için çıkarılabilir","Yerinde ve çalışır durumda tutulmalı, çıkarılmamalıdır","Sadece denetim günü takılır","Usta çalışanlar için gerekli değildir"]' AS secenekler, 1 AS dogru_index, 'Koruyucular hareketli parçalara temas, sıkışma ve fırlayan parçalara karşı korur; devre dışı bırakılmaz.' AS aciklama
  UNION ALL
  SELECT 'Yüksekte çalışırken düşmeye karşı kullanılması gereken kişisel koruyucu donanım hangisidir?' AS soru, '["Bel kemeri","Sağlam bir noktaya bağlanmış paraşüt tipi emniyet kemeri","Kulak tıkacı","Toz maskesi"]' AS secenekler, 1 AS dogru_index, 'Toplu koruma (korkuluk, ağ) yetersizse, sağlam bir ankraj noktasına bağlanmış paraşüt tipi emniyet kemeri kullanılır.' AS aciklama
  UNION ALL
  SELECT 'Forklift gibi iş ekipmanlarını kim kullanabilir?' AS soru, '["İşyerindeki her çalışan","Yalnızca bu iş için eğitim almış ve yetkilendirilmiş çalışan","Ehliyeti olan herkes","Stajyerler"]' AS secenekler, 1 AS dogru_index, 'İş Ekipmanlarının Kullanımında Sağlık ve Güvenlik Şartları Yönetmeliği: özel risk taşıyan ekipmanı yalnız eğitimli ve görevli çalışan kullanır.' AS aciklama
  UNION ALL
  SELECT 'Acil durum alarmı çaldığında çalışan ne yapmalıdır?' AS soru, '["Eşyalarını toplamak için masasına döner","Asansörü kullanarak hızla çıkar","Panik yapmadan, işaretli kaçış yollarını kullanarak toplanma alanına gider","Alarmın gerçek olup olmadığını öğrenmek için bekler"]' AS secenekler, 2 AS dogru_index, 'Tahliyede asansör kullanılmaz; kaçış yolları izlenerek toplanma alanına gidilir ve sayım için orada beklenir.' AS aciklama
) v
WHERE p.ad = 'Zorunlu Temel İSG Eğitimi (Tüm İşyerleri)'
  AND NOT EXISTS (SELECT 1 FROM (SELECT egitim_paketi_id FROM `egitim_paketi_sorulari` WHERE kullanim = 'final') f WHERE f.egitim_paketi_id = p.id);

UPDATE `egitim_paketleri` SET `sinav_soru_sayisi` = 20 WHERE `ad` = 'Zorunlu Temel İSG Eğitimi (Tüm İşyerleri)';

INSERT INTO `migrations` (`migration`, `batch`)
SELECT m.ad, (SELECT COALESCE(MAX(`batch`), 0) + 1 FROM `migrations`)
FROM (SELECT '2026_11_07_130000_add_on_test_cevaplar_ve_soru_kullanimi' AS ad UNION ALL SELECT '2026_11_07_130100_seed_zorunlu_paket_final_sorulari') m
WHERE NOT EXISTS (SELECT 1 FROM `migrations` x WHERE x.`migration` = m.ad);

-- DOĞRULAMA (beklenen: sutun 2, on_test 10, final 30, migration_kaydi 2):
SELECT (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE()
          AND ((table_name = 'egitim_atamalari' AND column_name = 'on_test_cevaplar')
            OR (table_name = 'egitim_paketi_sorulari' AND column_name = 'kullanim'))) AS sutun,
       (SELECT COUNT(*) FROM `egitim_paketi_sorulari` s JOIN `egitim_paketleri` p ON p.id = s.egitim_paketi_id WHERE p.ad = 'Zorunlu Temel İSG Eğitimi (Tüm İşyerleri)' AND s.kullanim = 'on_test') AS on_test,
       (SELECT COUNT(*) FROM `egitim_paketi_sorulari` s JOIN `egitim_paketleri` p ON p.id = s.egitim_paketi_id WHERE p.ad = 'Zorunlu Temel İSG Eğitimi (Tüm İşyerleri)' AND s.kullanim = 'final') AS final,
       (SELECT COUNT(*) FROM `migrations` WHERE `migration` IN ('2026_11_07_130000_add_on_test_cevaplar_ve_soru_kullanimi', '2026_11_07_130100_seed_zorunlu_paket_final_sorulari')) AS migration_kaydi;
