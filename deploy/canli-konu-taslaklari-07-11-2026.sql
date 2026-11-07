-- Zorunlu Temel İSG Eğitimi paketine Ek-1 Sağlık (2) ve Teknik (3) konu başlıkları: video bekleyen taslak dersler.
-- Taslak dersler çalışana görünmez; panelde Videolar listesinde "Video Bekleniyor" yazar, video linki bağlanınca yayına girer.
-- phpMyAdmin > mehse veritabanı > SQL. BİR KEZ çalıştırın (tekrar çalıştırmak güvenli: başlıkları olan modüle tekrar eklemez).
-- Sırayla: ÖNCE bu SQL, SONRA kod deploy.

SET NAMES utf8mb4;

ALTER TABLE `egitim_dersleri` MODIFY `video_url` varchar(255) NULL;

INSERT INTO `egitim_dersleri` (`egitim_paketi_id`,`modul`,`sira`,`baslik`,`video_url`,`saglayici`,`created_at`,`updated_at`)
SELECT (SELECT `id` FROM `egitim_paketleri` WHERE `ad` = 'Zorunlu Temel İSG Eğitimi (Tüm İşyerleri)' LIMIT 1), 2, 1, 'Meslek hastalıklarının sebepleri', NULL, 'diger', NOW(), NOW()
FROM DUAL WHERE (SELECT `id` FROM `egitim_paketleri` WHERE `ad` = 'Zorunlu Temel İSG Eğitimi (Tüm İşyerleri)' LIMIT 1) IS NOT NULL AND NOT EXISTS (SELECT 1 FROM `egitim_dersleri` WHERE `egitim_paketi_id` = (SELECT `id` FROM `egitim_paketleri` WHERE `ad` = 'Zorunlu Temel İSG Eğitimi (Tüm İşyerleri)' LIMIT 1) AND `modul` = 2 AND `video_url` IS NULL AND `baslik` = 'Meslek hastalıklarının sebepleri');

INSERT INTO `egitim_dersleri` (`egitim_paketi_id`,`modul`,`sira`,`baslik`,`video_url`,`saglayici`,`created_at`,`updated_at`)
SELECT (SELECT `id` FROM `egitim_paketleri` WHERE `ad` = 'Zorunlu Temel İSG Eğitimi (Tüm İşyerleri)' LIMIT 1), 2, 2, 'Hastalıktan korunma prensipleri ve korunma tekniklerinin uygulanması', NULL, 'diger', NOW(), NOW()
FROM DUAL WHERE (SELECT `id` FROM `egitim_paketleri` WHERE `ad` = 'Zorunlu Temel İSG Eğitimi (Tüm İşyerleri)' LIMIT 1) IS NOT NULL AND NOT EXISTS (SELECT 1 FROM `egitim_dersleri` WHERE `egitim_paketi_id` = (SELECT `id` FROM `egitim_paketleri` WHERE `ad` = 'Zorunlu Temel İSG Eğitimi (Tüm İşyerleri)' LIMIT 1) AND `modul` = 2 AND `video_url` IS NULL AND `baslik` = 'Hastalıktan korunma prensipleri ve korunma tekniklerinin uygulanması');

INSERT INTO `egitim_dersleri` (`egitim_paketi_id`,`modul`,`sira`,`baslik`,`video_url`,`saglayici`,`created_at`,`updated_at`)
SELECT (SELECT `id` FROM `egitim_paketleri` WHERE `ad` = 'Zorunlu Temel İSG Eğitimi (Tüm İşyerleri)' LIMIT 1), 2, 3, 'Biyolojik ve psikososyal risk etmenleri', NULL, 'diger', NOW(), NOW()
FROM DUAL WHERE (SELECT `id` FROM `egitim_paketleri` WHERE `ad` = 'Zorunlu Temel İSG Eğitimi (Tüm İşyerleri)' LIMIT 1) IS NOT NULL AND NOT EXISTS (SELECT 1 FROM `egitim_dersleri` WHERE `egitim_paketi_id` = (SELECT `id` FROM `egitim_paketleri` WHERE `ad` = 'Zorunlu Temel İSG Eğitimi (Tüm İşyerleri)' LIMIT 1) AND `modul` = 2 AND `video_url` IS NULL AND `baslik` = 'Biyolojik ve psikososyal risk etmenleri');

INSERT INTO `egitim_dersleri` (`egitim_paketi_id`,`modul`,`sira`,`baslik`,`video_url`,`saglayici`,`created_at`,`updated_at`)
SELECT (SELECT `id` FROM `egitim_paketleri` WHERE `ad` = 'Zorunlu Temel İSG Eğitimi (Tüm İşyerleri)' LIMIT 1), 2, 4, 'İlk yardım', NULL, 'diger', NOW(), NOW()
FROM DUAL WHERE (SELECT `id` FROM `egitim_paketleri` WHERE `ad` = 'Zorunlu Temel İSG Eğitimi (Tüm İşyerleri)' LIMIT 1) IS NOT NULL AND NOT EXISTS (SELECT 1 FROM `egitim_dersleri` WHERE `egitim_paketi_id` = (SELECT `id` FROM `egitim_paketleri` WHERE `ad` = 'Zorunlu Temel İSG Eğitimi (Tüm İşyerleri)' LIMIT 1) AND `modul` = 2 AND `video_url` IS NULL AND `baslik` = 'İlk yardım');

INSERT INTO `egitim_dersleri` (`egitim_paketi_id`,`modul`,`sira`,`baslik`,`video_url`,`saglayici`,`created_at`,`updated_at`)
SELECT (SELECT `id` FROM `egitim_paketleri` WHERE `ad` = 'Zorunlu Temel İSG Eğitimi (Tüm İşyerleri)' LIMIT 1), 2, 5, 'Bağımlılık yapıcı maddelerin zararları ve teknoloji bağımlılığı', NULL, 'diger', NOW(), NOW()
FROM DUAL WHERE (SELECT `id` FROM `egitim_paketleri` WHERE `ad` = 'Zorunlu Temel İSG Eğitimi (Tüm İşyerleri)' LIMIT 1) IS NOT NULL AND NOT EXISTS (SELECT 1 FROM `egitim_dersleri` WHERE `egitim_paketi_id` = (SELECT `id` FROM `egitim_paketleri` WHERE `ad` = 'Zorunlu Temel İSG Eğitimi (Tüm İşyerleri)' LIMIT 1) AND `modul` = 2 AND `video_url` IS NULL AND `baslik` = 'Bağımlılık yapıcı maddelerin zararları ve teknoloji bağımlılığı');

INSERT INTO `egitim_dersleri` (`egitim_paketi_id`,`modul`,`sira`,`baslik`,`video_url`,`saglayici`,`created_at`,`updated_at`)
SELECT (SELECT `id` FROM `egitim_paketleri` WHERE `ad` = 'Zorunlu Temel İSG Eğitimi (Tüm İşyerleri)' LIMIT 1), 3, 1, 'Kimyasal, fiziksel ve ergonomik risk etmenleri', NULL, 'diger', NOW(), NOW()
FROM DUAL WHERE (SELECT `id` FROM `egitim_paketleri` WHERE `ad` = 'Zorunlu Temel İSG Eğitimi (Tüm İşyerleri)' LIMIT 1) IS NOT NULL AND NOT EXISTS (SELECT 1 FROM `egitim_dersleri` WHERE `egitim_paketi_id` = (SELECT `id` FROM `egitim_paketleri` WHERE `ad` = 'Zorunlu Temel İSG Eğitimi (Tüm İşyerleri)' LIMIT 1) AND `modul` = 3 AND `video_url` IS NULL AND `baslik` = 'Kimyasal, fiziksel ve ergonomik risk etmenleri');

INSERT INTO `egitim_dersleri` (`egitim_paketi_id`,`modul`,`sira`,`baslik`,`video_url`,`saglayici`,`created_at`,`updated_at`)
SELECT (SELECT `id` FROM `egitim_paketleri` WHERE `ad` = 'Zorunlu Temel İSG Eğitimi (Tüm İşyerleri)' LIMIT 1), 3, 2, 'Elle kaldırma ve taşıma', NULL, 'diger', NOW(), NOW()
FROM DUAL WHERE (SELECT `id` FROM `egitim_paketleri` WHERE `ad` = 'Zorunlu Temel İSG Eğitimi (Tüm İşyerleri)' LIMIT 1) IS NOT NULL AND NOT EXISTS (SELECT 1 FROM `egitim_dersleri` WHERE `egitim_paketi_id` = (SELECT `id` FROM `egitim_paketleri` WHERE `ad` = 'Zorunlu Temel İSG Eğitimi (Tüm İşyerleri)' LIMIT 1) AND `modul` = 3 AND `video_url` IS NULL AND `baslik` = 'Elle kaldırma ve taşıma');

INSERT INTO `egitim_dersleri` (`egitim_paketi_id`,`modul`,`sira`,`baslik`,`video_url`,`saglayici`,`created_at`,`updated_at`)
SELECT (SELECT `id` FROM `egitim_paketleri` WHERE `ad` = 'Zorunlu Temel İSG Eğitimi (Tüm İşyerleri)' LIMIT 1), 3, 3, 'Parlama, patlama', NULL, 'diger', NOW(), NOW()
FROM DUAL WHERE (SELECT `id` FROM `egitim_paketleri` WHERE `ad` = 'Zorunlu Temel İSG Eğitimi (Tüm İşyerleri)' LIMIT 1) IS NOT NULL AND NOT EXISTS (SELECT 1 FROM `egitim_dersleri` WHERE `egitim_paketi_id` = (SELECT `id` FROM `egitim_paketleri` WHERE `ad` = 'Zorunlu Temel İSG Eğitimi (Tüm İşyerleri)' LIMIT 1) AND `modul` = 3 AND `video_url` IS NULL AND `baslik` = 'Parlama, patlama');

INSERT INTO `egitim_dersleri` (`egitim_paketi_id`,`modul`,`sira`,`baslik`,`video_url`,`saglayici`,`created_at`,`updated_at`)
SELECT (SELECT `id` FROM `egitim_paketleri` WHERE `ad` = 'Zorunlu Temel İSG Eğitimi (Tüm İşyerleri)' LIMIT 1), 3, 4, 'Yangın ve yangından korunma', NULL, 'diger', NOW(), NOW()
FROM DUAL WHERE (SELECT `id` FROM `egitim_paketleri` WHERE `ad` = 'Zorunlu Temel İSG Eğitimi (Tüm İşyerleri)' LIMIT 1) IS NOT NULL AND NOT EXISTS (SELECT 1 FROM `egitim_dersleri` WHERE `egitim_paketi_id` = (SELECT `id` FROM `egitim_paketleri` WHERE `ad` = 'Zorunlu Temel İSG Eğitimi (Tüm İşyerleri)' LIMIT 1) AND `modul` = 3 AND `video_url` IS NULL AND `baslik` = 'Yangın ve yangından korunma');

INSERT INTO `egitim_dersleri` (`egitim_paketi_id`,`modul`,`sira`,`baslik`,`video_url`,`saglayici`,`created_at`,`updated_at`)
SELECT (SELECT `id` FROM `egitim_paketleri` WHERE `ad` = 'Zorunlu Temel İSG Eğitimi (Tüm İşyerleri)' LIMIT 1), 3, 5, 'İş ekipmanlarının güvenli kullanımı', NULL, 'diger', NOW(), NOW()
FROM DUAL WHERE (SELECT `id` FROM `egitim_paketleri` WHERE `ad` = 'Zorunlu Temel İSG Eğitimi (Tüm İşyerleri)' LIMIT 1) IS NOT NULL AND NOT EXISTS (SELECT 1 FROM `egitim_dersleri` WHERE `egitim_paketi_id` = (SELECT `id` FROM `egitim_paketleri` WHERE `ad` = 'Zorunlu Temel İSG Eğitimi (Tüm İşyerleri)' LIMIT 1) AND `modul` = 3 AND `video_url` IS NULL AND `baslik` = 'İş ekipmanlarının güvenli kullanımı');

INSERT INTO `egitim_dersleri` (`egitim_paketi_id`,`modul`,`sira`,`baslik`,`video_url`,`saglayici`,`created_at`,`updated_at`)
SELECT (SELECT `id` FROM `egitim_paketleri` WHERE `ad` = 'Zorunlu Temel İSG Eğitimi (Tüm İşyerleri)' LIMIT 1), 3, 6, 'Ekranlı araçlarla çalışma', NULL, 'diger', NOW(), NOW()
FROM DUAL WHERE (SELECT `id` FROM `egitim_paketleri` WHERE `ad` = 'Zorunlu Temel İSG Eğitimi (Tüm İşyerleri)' LIMIT 1) IS NOT NULL AND NOT EXISTS (SELECT 1 FROM `egitim_dersleri` WHERE `egitim_paketi_id` = (SELECT `id` FROM `egitim_paketleri` WHERE `ad` = 'Zorunlu Temel İSG Eğitimi (Tüm İşyerleri)' LIMIT 1) AND `modul` = 3 AND `video_url` IS NULL AND `baslik` = 'Ekranlı araçlarla çalışma');

INSERT INTO `egitim_dersleri` (`egitim_paketi_id`,`modul`,`sira`,`baslik`,`video_url`,`saglayici`,`created_at`,`updated_at`)
SELECT (SELECT `id` FROM `egitim_paketleri` WHERE `ad` = 'Zorunlu Temel İSG Eğitimi (Tüm İşyerleri)' LIMIT 1), 3, 7, 'Elektrik, tehlikeleri, riskleri ve önlemleri', NULL, 'diger', NOW(), NOW()
FROM DUAL WHERE (SELECT `id` FROM `egitim_paketleri` WHERE `ad` = 'Zorunlu Temel İSG Eğitimi (Tüm İşyerleri)' LIMIT 1) IS NOT NULL AND NOT EXISTS (SELECT 1 FROM `egitim_dersleri` WHERE `egitim_paketi_id` = (SELECT `id` FROM `egitim_paketleri` WHERE `ad` = 'Zorunlu Temel İSG Eğitimi (Tüm İşyerleri)' LIMIT 1) AND `modul` = 3 AND `video_url` IS NULL AND `baslik` = 'Elektrik, tehlikeleri, riskleri ve önlemleri');

INSERT INTO `egitim_dersleri` (`egitim_paketi_id`,`modul`,`sira`,`baslik`,`video_url`,`saglayici`,`created_at`,`updated_at`)
SELECT (SELECT `id` FROM `egitim_paketleri` WHERE `ad` = 'Zorunlu Temel İSG Eğitimi (Tüm İşyerleri)' LIMIT 1), 3, 8, 'İş kazalarının sebepleri ve korunma prensipleri ile tekniklerinin uygulanması', NULL, 'diger', NOW(), NOW()
FROM DUAL WHERE (SELECT `id` FROM `egitim_paketleri` WHERE `ad` = 'Zorunlu Temel İSG Eğitimi (Tüm İşyerleri)' LIMIT 1) IS NOT NULL AND NOT EXISTS (SELECT 1 FROM `egitim_dersleri` WHERE `egitim_paketi_id` = (SELECT `id` FROM `egitim_paketleri` WHERE `ad` = 'Zorunlu Temel İSG Eğitimi (Tüm İşyerleri)' LIMIT 1) AND `modul` = 3 AND `video_url` IS NULL AND `baslik` = 'İş kazalarının sebepleri ve korunma prensipleri ile tekniklerinin uygulanması');

INSERT INTO `egitim_dersleri` (`egitim_paketi_id`,`modul`,`sira`,`baslik`,`video_url`,`saglayici`,`created_at`,`updated_at`)
SELECT (SELECT `id` FROM `egitim_paketleri` WHERE `ad` = 'Zorunlu Temel İSG Eğitimi (Tüm İşyerleri)' LIMIT 1), 3, 9, 'Sağlık ve güvenlik işaretleri', NULL, 'diger', NOW(), NOW()
FROM DUAL WHERE (SELECT `id` FROM `egitim_paketleri` WHERE `ad` = 'Zorunlu Temel İSG Eğitimi (Tüm İşyerleri)' LIMIT 1) IS NOT NULL AND NOT EXISTS (SELECT 1 FROM `egitim_dersleri` WHERE `egitim_paketi_id` = (SELECT `id` FROM `egitim_paketleri` WHERE `ad` = 'Zorunlu Temel İSG Eğitimi (Tüm İşyerleri)' LIMIT 1) AND `modul` = 3 AND `video_url` IS NULL AND `baslik` = 'Sağlık ve güvenlik işaretleri');

INSERT INTO `egitim_dersleri` (`egitim_paketi_id`,`modul`,`sira`,`baslik`,`video_url`,`saglayici`,`created_at`,`updated_at`)
SELECT (SELECT `id` FROM `egitim_paketleri` WHERE `ad` = 'Zorunlu Temel İSG Eğitimi (Tüm İşyerleri)' LIMIT 1), 3, 10, 'Kişisel koruyucu donanım kullanımı', NULL, 'diger', NOW(), NOW()
FROM DUAL WHERE (SELECT `id` FROM `egitim_paketleri` WHERE `ad` = 'Zorunlu Temel İSG Eğitimi (Tüm İşyerleri)' LIMIT 1) IS NOT NULL AND NOT EXISTS (SELECT 1 FROM `egitim_dersleri` WHERE `egitim_paketi_id` = (SELECT `id` FROM `egitim_paketleri` WHERE `ad` = 'Zorunlu Temel İSG Eğitimi (Tüm İşyerleri)' LIMIT 1) AND `modul` = 3 AND `video_url` IS NULL AND `baslik` = 'Kişisel koruyucu donanım kullanımı');

INSERT INTO `egitim_dersleri` (`egitim_paketi_id`,`modul`,`sira`,`baslik`,`video_url`,`saglayici`,`created_at`,`updated_at`)
SELECT (SELECT `id` FROM `egitim_paketleri` WHERE `ad` = 'Zorunlu Temel İSG Eğitimi (Tüm İşyerleri)' LIMIT 1), 3, 11, 'İş sağlığı ve güvenliği genel kuralları ve güvenlik kültürü', NULL, 'diger', NOW(), NOW()
FROM DUAL WHERE (SELECT `id` FROM `egitim_paketleri` WHERE `ad` = 'Zorunlu Temel İSG Eğitimi (Tüm İşyerleri)' LIMIT 1) IS NOT NULL AND NOT EXISTS (SELECT 1 FROM `egitim_dersleri` WHERE `egitim_paketi_id` = (SELECT `id` FROM `egitim_paketleri` WHERE `ad` = 'Zorunlu Temel İSG Eğitimi (Tüm İşyerleri)' LIMIT 1) AND `modul` = 3 AND `video_url` IS NULL AND `baslik` = 'İş sağlığı ve güvenliği genel kuralları ve güvenlik kültürü');

INSERT INTO `egitim_dersleri` (`egitim_paketi_id`,`modul`,`sira`,`baslik`,`video_url`,`saglayici`,`created_at`,`updated_at`)
SELECT (SELECT `id` FROM `egitim_paketleri` WHERE `ad` = 'Zorunlu Temel İSG Eğitimi (Tüm İşyerleri)' LIMIT 1), 3, 12, 'Acil durumlar, tahliye ve kurtarma', NULL, 'diger', NOW(), NOW()
FROM DUAL WHERE (SELECT `id` FROM `egitim_paketleri` WHERE `ad` = 'Zorunlu Temel İSG Eğitimi (Tüm İşyerleri)' LIMIT 1) IS NOT NULL AND NOT EXISTS (SELECT 1 FROM `egitim_dersleri` WHERE `egitim_paketi_id` = (SELECT `id` FROM `egitim_paketleri` WHERE `ad` = 'Zorunlu Temel İSG Eğitimi (Tüm İşyerleri)' LIMIT 1) AND `modul` = 3 AND `video_url` IS NULL AND `baslik` = 'Acil durumlar, tahliye ve kurtarma');

INSERT INTO `migrations` (`migration`,`batch`) SELECT '2026_11_07_120000_make_video_url_nullable_on_egitim_dersleri', (SELECT COALESCE(MAX(`batch`),0)+1 FROM `migrations`) FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `migrations` WHERE `migration`='2026_11_07_120000_make_video_url_nullable_on_egitim_dersleri');
INSERT INTO `migrations` (`migration`,`batch`) SELECT '2026_11_07_120100_seed_zorunlu_paket_konu_taslaklari', (SELECT COALESCE(MAX(`batch`),0)+1 FROM `migrations`) FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `migrations` WHERE `migration`='2026_11_07_120100_seed_zorunlu_paket_konu_taslaklari');

-- DOĞRULAMA (beklenen: taslak 17, migration_kaydi 2):
SELECT (SELECT COUNT(*) FROM `egitim_dersleri` d JOIN `egitim_paketleri` p ON p.id=d.egitim_paketi_id WHERE p.`ad`='Zorunlu Temel İSG Eğitimi (Tüm İşyerleri)' AND d.`video_url` IS NULL) AS taslak,
  (SELECT COUNT(*) FROM `migrations` WHERE `migration` LIKE '2026_11_07_12%') AS migration_kaydi;
