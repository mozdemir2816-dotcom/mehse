   CREATE TABLE IF NOT EXISTS `ozel_ekipman_tipleri` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `ad` VARCHAR(191) NOT NULL,
  `anahtar` VARCHAR(80) NOT NULL,
  `kategori` VARCHAR(40) NOT NULL,
  `periyot_ay` SMALLINT UNSIGNED NOT NULL DEFAULT 12,
  `standart` VARCHAR(191) NULL,
  `deney` VARCHAR(191) NULL,
  `kontrol_sorulari` JSON NULL,
  `resim_sahnesi` TEXT NULL,
  `yz_taslak` TINYINT(1) NOT NULL DEFAULT 1,
  `onaylandi_at` TIMESTAMP NULL,
  `onaylayan_id` BIGINT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `ozel_ekipman_tipleri_ad_unique` (`ad`),
  UNIQUE KEY `ozel_ekipman_tipleri_anahtar_unique` (`anahtar`),
  KEY `ozel_ekipman_tipleri_onaylayan_id_foreign` (`onaylayan_id`),
  CONSTRAINT `ozel_ekipman_tipleri_onaylayan_id_foreign` FOREIGN KEY (`onaylayan_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

   INSERT INTO `migrations` (`migration`,`batch`) SELECT '2026_11_21_120000_create_ozel_ekipman_tipleri_table', (SELECT COALESCE(MAX(`batch`),0)+1 FROM `migrations`) FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `migrations` WHERE `migration`='2026_11_21_120000_create_ozel_ekipman_tipleri_table');

   SHOW TABLES LIKE 'ozel_ekipman_tipleri';
