-- Zorunlu Temel İSG Eğitimi (Tüm İşyerleri): paket + Modül 1 videosu + 10 soruluk ön test/son test havuzu.
-- phpMyAdmin > mehse veritabanı > SQL. BİR KEZ çalıştırın; paket varsa hiçbir şey eklemez (tekrar çalıştırmak güvenli).
-- Sırayla: ÖNCE bu SQL, SONRA kod deploy (Modül 4 gizleme, konu başlığı ilerlemesi, eski atama silme).

SET NAMES utf8mb4;

INSERT INTO `egitim_paketleri` (`user_id`,`ad`,`kod`,`aciklama`,`gecme_puani`,`video_zorunlu_yuzde`,`sinav_soru_sayisi`,`aktif`,`paylasildi`,`created_at`,`updated_at`)
SELECT (SELECT MIN(`id`) FROM `users`), 'Zorunlu Temel İSG Eğitimi (Tüm İşyerleri)',
  CONCAT('UE-',YEAR(NOW()),'-',LPAD((SELECT COUNT(*)+1 FROM `egitim_paketleri` p WHERE YEAR(p.`created_at`)=YEAR(NOW())),3,'0')),
  'Tüm işyerlerinin alması gereken zorunlu temel İSG eğitimi konuları.', 60, 90, 10, 1, 1, NOW(), NOW()
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `egitim_paketleri` WHERE `ad` = 'Zorunlu Temel İSG Eğitimi (Tüm İşyerleri)');

INSERT INTO `egitim_dersleri` (`egitim_paketi_id`,`modul`,`sira`,`baslik`,`video_url`,`saglayici`,`created_at`,`updated_at`)
SELECT (SELECT `id` FROM `egitim_paketleri` WHERE `ad` = 'Zorunlu Temel İSG Eğitimi (Tüm İşyerleri)' LIMIT 1), 1, 1, 'Temel İş Sağlığı ve Güvenliği Eğitimi | Genel Konular', 'https://www.youtube.com/watch?v=607t0w4AS5E', 'youtube', NOW(), NOW()
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `egitim_dersleri` WHERE `egitim_paketi_id` = (SELECT `id` FROM `egitim_paketleri` WHERE `ad` = 'Zorunlu Temel İSG Eğitimi (Tüm İşyerleri)' LIMIT 1));

INSERT INTO `egitim_paketi_sorulari` (`egitim_paketi_id`,`soru`,`secenekler`,`dogru_index`,`aciklama`,`created_at`,`updated_at`)
SELECT (SELECT `id` FROM `egitim_paketleri` WHERE `ad` = 'Zorunlu Temel İSG Eğitimi (Tüm İşyerleri)' LIMIT 1), 'İş sağlığı ve güvenliği eğitimini çalışanlara vermekle yükümlü olan kimdir?', '["Sendika temsilcisi","İşveren","Çalışanın kendisi","Belediye"]', 1, '6331 sayılı Kanun gereği çalışanların İSG eğitimlerini sağlamak işverenin yükümlülüğüdür.', NOW(), NOW()
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `egitim_paketi_sorulari` WHERE `egitim_paketi_id` = (SELECT `id` FROM `egitim_paketleri` WHERE `ad` = 'Zorunlu Temel İSG Eğitimi (Tüm İşyerleri)' LIMIT 1) AND `soru` = 'İş sağlığı ve güvenliği eğitimini çalışanlara vermekle yükümlü olan kimdir?');

INSERT INTO `egitim_paketi_sorulari` (`egitim_paketi_id`,`soru`,`secenekler`,`dogru_index`,`aciklama`,`created_at`,`updated_at`)
SELECT (SELECT `id` FROM `egitim_paketleri` WHERE `ad` = 'Zorunlu Temel İSG Eğitimi (Tüm İşyerleri)' LIMIT 1), 'Ciddi ve yakın tehlike bulunan bir işyerinde çalışan ne yapabilir?', '["Tehlike geçene kadar bekler, işe devam eder","Yalnızca arkadaşlarına söyler","Kurula veya işverene başvurarak işi bırakabilir ve bu nedenle haklarından yoksun bırakılamaz","İşyerinden ayrılmak için izin bekler"]', 2, 'Çalışan, kurula veya işverene başvurarak işi bırakma hakkına sahiptir; bu durumda hakları saklıdır.', NOW(), NOW()
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `egitim_paketi_sorulari` WHERE `egitim_paketi_id` = (SELECT `id` FROM `egitim_paketleri` WHERE `ad` = 'Zorunlu Temel İSG Eğitimi (Tüm İşyerleri)' LIMIT 1) AND `soru` = 'Ciddi ve yakın tehlike bulunan bir işyerinde çalışan ne yapabilir?');

INSERT INTO `egitim_paketi_sorulari` (`egitim_paketi_id`,`soru`,`secenekler`,`dogru_index`,`aciklama`,`created_at`,`updated_at`)
SELECT (SELECT `id` FROM `egitim_paketleri` WHERE `ad` = 'Zorunlu Temel İSG Eğitimi (Tüm İşyerleri)' LIMIT 1), 'Aşağıdakilerden hangisi çalışanın yükümlülüklerinden biridir?', '["Kişisel koruyucu donanımı talimata uygun kullanmak","Risk değerlendirmesini tek başına hazırlamak","İşyeri hekimini görevlendirmek","İş güvenliği uzmanının ücretini ödemek"]', 0, 'Çalışan, verilen eğitim ve talimatlara uygun davranmak ve KKD''yi doğru kullanmakla yükümlüdür.', NOW(), NOW()
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `egitim_paketi_sorulari` WHERE `egitim_paketi_id` = (SELECT `id` FROM `egitim_paketleri` WHERE `ad` = 'Zorunlu Temel İSG Eğitimi (Tüm İşyerleri)' LIMIT 1) AND `soru` = 'Aşağıdakilerden hangisi çalışanın yükümlülüklerinden biridir?');

INSERT INTO `egitim_paketi_sorulari` (`egitim_paketi_id`,`soru`,`secenekler`,`dogru_index`,`aciklama`,`created_at`,`updated_at`)
SELECT (SELECT `id` FROM `egitim_paketleri` WHERE `ad` = 'Zorunlu Temel İSG Eğitimi (Tüm İşyerleri)' LIMIT 1), 'İş kazası, kazadan sonraki kaç iş günü içinde Sosyal Güvenlik Kurumu''na bildirilmelidir?', '["Bir ay","Üç iş günü","Bir yıl","Bildirim zorunlu değildir"]', 1, '5510 sayılı Kanun gereği iş kazası, kazadan sonraki üç iş günü içinde bildirilir.', NOW(), NOW()
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `egitim_paketi_sorulari` WHERE `egitim_paketi_id` = (SELECT `id` FROM `egitim_paketleri` WHERE `ad` = 'Zorunlu Temel İSG Eğitimi (Tüm İşyerleri)' LIMIT 1) AND `soru` = 'İş kazası, kazadan sonraki kaç iş günü içinde Sosyal Güvenlik Kurumu''na bildirilmelidir?');

INSERT INTO `egitim_paketi_sorulari` (`egitim_paketi_id`,`soru`,`secenekler`,`dogru_index`,`aciklama`,`created_at`,`updated_at`)
SELECT (SELECT `id` FROM `egitim_paketleri` WHERE `ad` = 'Zorunlu Temel İSG Eğitimi (Tüm İşyerleri)' LIMIT 1), 'İş kazası veya meslek hastalığı sonucunda işveren ve ilgili kişiler hangi sorumluluklarla karşılaşabilir?', '["Yalnızca idari para cezası","Yalnızca tazminat","Hiçbir sorumluluk doğmaz","İdari, hukuki (tazminat) ve cezai sorumluluk"]', 3, 'İş kazası ve meslek hastalığı; idari yaptırım, tazminat ve ceza davası gibi sonuçlar doğurabilir.', NOW(), NOW()
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `egitim_paketi_sorulari` WHERE `egitim_paketi_id` = (SELECT `id` FROM `egitim_paketleri` WHERE `ad` = 'Zorunlu Temel İSG Eğitimi (Tüm İşyerleri)' LIMIT 1) AND `soru` = 'İş kazası veya meslek hastalığı sonucunda işveren ve ilgili kişiler hangi sorumluluklarla karşılaşabilir?');

INSERT INTO `egitim_paketi_sorulari` (`egitim_paketi_id`,`soru`,`secenekler`,`dogru_index`,`aciklama`,`created_at`,`updated_at`)
SELECT (SELECT `id` FROM `egitim_paketleri` WHERE `ad` = 'Zorunlu Temel İSG Eğitimi (Tüm İşyerleri)' LIMIT 1), 'Acil çıkış yolları ve kapıları için aşağıdakilerden hangisi doğrudur?', '["Depolama için kullanılabilir","Mesai dışında kilitlenebilir","Her zaman açık, işaretli ve engelsiz tutulmalıdır","Yalnızca yangın tatbikatında açılmalıdır"]', 2, 'Acil çıkışlar her an kullanılabilir olmalı, önleri hiçbir malzeme ile kapatılmamalıdır.', NOW(), NOW()
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `egitim_paketi_sorulari` WHERE `egitim_paketi_id` = (SELECT `id` FROM `egitim_paketleri` WHERE `ad` = 'Zorunlu Temel İSG Eğitimi (Tüm İşyerleri)' LIMIT 1) AND `soru` = 'Acil çıkış yolları ve kapıları için aşağıdakilerden hangisi doğrudur?');

INSERT INTO `egitim_paketi_sorulari` (`egitim_paketi_id`,`soru`,`secenekler`,`dogru_index`,`aciklama`,`created_at`,`updated_at`)
SELECT (SELECT `id` FROM `egitim_paketleri` WHERE `ad` = 'Zorunlu Temel İSG Eğitimi (Tüm İşyerleri)' LIMIT 1), 'İSG eğitimleri hangi süre içinde verilir ve bu süre nasıl sayılır?', '["Çalışma saatleri dışında, ücretsiz","Tatil günlerinde","Çalışma saatleri içinde; süreler çalışma süresinden sayılır","Yalnızca öğle arasında"]', 2, '6331 sayılı Kanun gereği eğitimler çalışma saatleri içinde yapılır ve ücret kaybı doğurmaz.', NOW(), NOW()
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `egitim_paketi_sorulari` WHERE `egitim_paketi_id` = (SELECT `id` FROM `egitim_paketleri` WHERE `ad` = 'Zorunlu Temel İSG Eğitimi (Tüm İşyerleri)' LIMIT 1) AND `soru` = 'İSG eğitimleri hangi süre içinde verilir ve bu süre nasıl sayılır?');

INSERT INTO `egitim_paketi_sorulari` (`egitim_paketi_id`,`soru`,`secenekler`,`dogru_index`,`aciklama`,`created_at`,`updated_at`)
SELECT (SELECT `id` FROM `egitim_paketleri` WHERE `ad` = 'Zorunlu Temel İSG Eğitimi (Tüm İşyerleri)' LIMIT 1), 'Az tehlikeli sınıftaki işyerlerinde temel İSG eğitimi en az ne sıklıkla tekrarlanır?', '["Her yıl","İki yılda bir","Beş yılda bir","Üç yılda bir"]', 3, 'Çalışanların İSG Eğitimleri Yönetmeliği''ne göre az tehlikeli işyerlerinde en az üç yılda bir tekrar edilir.', NOW(), NOW()
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `egitim_paketi_sorulari` WHERE `egitim_paketi_id` = (SELECT `id` FROM `egitim_paketleri` WHERE `ad` = 'Zorunlu Temel İSG Eğitimi (Tüm İşyerleri)' LIMIT 1) AND `soru` = 'Az tehlikeli sınıftaki işyerlerinde temel İSG eğitimi en az ne sıklıkla tekrarlanır?');

INSERT INTO `egitim_paketi_sorulari` (`egitim_paketi_id`,`soru`,`secenekler`,`dogru_index`,`aciklama`,`created_at`,`updated_at`)
SELECT (SELECT `id` FROM `egitim_paketleri` WHERE `ad` = 'Zorunlu Temel İSG Eğitimi (Tüm İşyerleri)' LIMIT 1), 'Zeminde dökülen bir sıvıyı fark eden çalışan ilk olarak ne yapmalıdır?', '["Görmezden gelir","Yaşanabilecek kaymaya karşı alanı işaretler ve sıvıyı temizler ya da temizlenmesini sağlar","Üzerine kağıt koyup geçer","Mesai bitimini bekler"]', 1, 'Kayma ve düşmeleri önlemek için dökülen sıvı hemen uyarı işaretiyle belirtilir ve temizlenir.', NOW(), NOW()
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `egitim_paketi_sorulari` WHERE `egitim_paketi_id` = (SELECT `id` FROM `egitim_paketleri` WHERE `ad` = 'Zorunlu Temel İSG Eğitimi (Tüm İşyerleri)' LIMIT 1) AND `soru` = 'Zeminde dökülen bir sıvıyı fark eden çalışan ilk olarak ne yapmalıdır?');

INSERT INTO `egitim_paketi_sorulari` (`egitim_paketi_id`,`soru`,`secenekler`,`dogru_index`,`aciklama`,`created_at`,`updated_at`)
SELECT (SELECT `id` FROM `egitim_paketleri` WHERE `ad` = 'Zorunlu Temel İSG Eğitimi (Tüm İşyerleri)' LIMIT 1), '4857 sayılı İş Kanunu''na göre genel olarak haftalık normal çalışma süresi en çok kaç saattir?', '["35 saat","40 saat","45 saat","60 saat"]', 2, 'Haftalık çalışma süresi en çok 45 saattir; taraflarca günlere bölünerek uygulanır.', NOW(), NOW()
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `egitim_paketi_sorulari` WHERE `egitim_paketi_id` = (SELECT `id` FROM `egitim_paketleri` WHERE `ad` = 'Zorunlu Temel İSG Eğitimi (Tüm İşyerleri)' LIMIT 1) AND `soru` = '4857 sayılı İş Kanunu''na göre genel olarak haftalık normal çalışma süresi en çok kaç saattir?');

INSERT INTO `migrations` (`migration`,`batch`) SELECT '2026_11_06_100000_seed_zorunlu_genel_isg_egitimi_paketi', (SELECT COALESCE(MAX(`batch`),0)+1 FROM `migrations`) FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `migrations` WHERE `migration`='2026_11_06_100000_seed_zorunlu_genel_isg_egitimi_paketi');
INSERT INTO `migrations` (`migration`,`batch`) SELECT '2026_11_06_110000_seed_zorunlu_genel_isg_egitimi_sorulari', (SELECT COALESCE(MAX(`batch`),0)+1 FROM `migrations`) FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `migrations` WHERE `migration`='2026_11_06_110000_seed_zorunlu_genel_isg_egitimi_sorulari');

-- DOĞRULAMA (beklenen: paket 1, ders 1, soru 10, migration 2):
SELECT (SELECT COUNT(*) FROM `egitim_paketleri` WHERE `ad`='Zorunlu Temel İSG Eğitimi (Tüm İşyerleri)') AS paket,
  (SELECT COUNT(*) FROM `egitim_dersleri` d JOIN `egitim_paketleri` p ON p.id=d.egitim_paketi_id WHERE p.`ad`='Zorunlu Temel İSG Eğitimi (Tüm İşyerleri)') AS ders,
  (SELECT COUNT(*) FROM `egitim_paketi_sorulari` s JOIN `egitim_paketleri` p ON p.id=s.egitim_paketi_id WHERE p.`ad`='Zorunlu Temel İSG Eğitimi (Tüm İşyerleri)') AS soru,
  (SELECT COUNT(*) FROM `migrations` WHERE `migration` LIKE '2026_11_06_1%') AS migration_kaydi;
