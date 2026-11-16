-- 16.11.2026 — Uzaktan eğitim: olay günlüğü / zaman çizelgesi
-- Canlıya yüklemeden ÖNCE phpMyAdmin'de çalıştırın.
CREATE TABLE IF NOT EXISTS `egitim_olaylari` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `egitim_atamasi_id` BIGINT UNSIGNED NOT NULL,
  `calisan_id` BIGINT UNSIGNED NULL,
  `tur` VARCHAR(20) NOT NULL,
  `baslik` VARCHAR(255) NOT NULL,
  `aciklama` TEXT NULL,
  `ip` VARCHAR(45) NULL,
  `olay_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `egitim_olaylari_egitim_atamasi_id_index` (`egitim_atamasi_id`),
  KEY `egitim_olaylari_calisan_id_index` (`calisan_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `migrations` (`migration`,`batch`) SELECT '2026_11_16_120000_create_egitim_olaylari_table', (SELECT COALESCE(MAX(`batch`),0)+1 FROM `migrations`) FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `migrations` WHERE `migration`='2026_11_16_120000_create_egitim_olaylari_table');
