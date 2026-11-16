-- 16.11.2026 — Uzaktan eğitim: firma ayarları (eğitimi verenler, kaşe/logo, içerik onayı)
-- Canlıya yüklemeden ÖNCE phpMyAdmin'de çalıştırın.
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
