-- 16.11.2026 — Olay Komuta 360°: olay kaydına yaralanan vücut bölgesi
ALTER TABLE `olay_kayitlari` ADD COLUMN `yaralanan_bolge` VARCHAR(191) NULL AFTER `yaralanma_turu`;

INSERT INTO `migrations` (`migration`,`batch`) SELECT '2026_11_16_100000_add_yaralanan_bolge_to_olay_kayitlari', (SELECT COALESCE(MAX(`batch`),0)+1 FROM `migrations`) FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `migrations` WHERE `migration`='2026_11_16_100000_add_yaralanan_bolge_to_olay_kayitlari');
-- 16.11.2026 — Uzaktan eğitim: olay günlüğü / zaman çizelgesi
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
-- 16.11.2026 — Uzaktan eğitim: firma ayarları (eğitimi verenler, kaşe/logo, içerik onayı)
CREATE TABLE IF NOT EXISTS `uzaktan_egitim_firma_ayarlari` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `firma_id` BIGINT UNSIGNED NOT NULL,
  `egitici_igu_ad` VARCHAR(255) NULL,
  `egitici_igu_unvan` VARCHAR(255) NULL,
  `egitici_hekim_ad` VARCHAR(255) NULL,
  `egitici_hekim_unvan` VARCHAR(255) NULL,
  `kase_bas` TINYINT(1) NOT NULL DEFAULT 1,
  `logo_konumu` VARCHAR(10) NOT NULL DEFAULT 'yok',
  `calisan_imza_alani` TINYINT(1) NOT NULL DEFAULT 0,
  `icerik_paket_id` BIGINT UNSIGNED NULL,
  `icerik_surumu` VARCHAR(40) NULL,
  `icerik_onay_at` TIMESTAMP NULL,
  `icerik_onaylayan` VARCHAR(255) NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uzaktan_egitim_firma_ayarlari_firma_id_unique` (`firma_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `migrations` (`migration`,`batch`) SELECT '2026_11_16_130000_create_uzaktan_egitim_firma_ayarlari_table', (SELECT COALESCE(MAX(`batch`),0)+1 FROM `migrations`) FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `migrations` WHERE `migration`='2026_11_16_130000_create_uzaktan_egitim_firma_ayarlari_table');
