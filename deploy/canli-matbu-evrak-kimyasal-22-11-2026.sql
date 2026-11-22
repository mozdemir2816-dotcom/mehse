   ALTER TABLE `kimyasal_urunler` ADD COLUMN IF NOT EXISTS `ozet` JSON NULL AFTER `aciklama`;
   ALTER TABLE `kimyasal_urunler` ADD COLUMN IF NOT EXISTS `ozet_yz_taslak` TINYINT(1) NOT NULL DEFAULT 0 AFTER `ozet`;
   ALTER TABLE `kimyasal_urunler` ADD COLUMN IF NOT EXISTS `ozet_onaylandi_at` TIMESTAMP NULL AFTER `ozet_yz_taslak`;
   ALTER TABLE `kimyasal_urunler` ADD COLUMN IF NOT EXISTS `evrak_cikti_at` TIMESTAMP NULL AFTER `ozet_onaylandi_at`;
   INSERT INTO `migrations` (`migration`,`batch`) SELECT '2026_11_22_100000_add_matbu_evrak_alanlari_to_kimyasal_urunler', (SELECT COALESCE(MAX(`batch`),0)+1 FROM `migrations`) FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `migrations` WHERE `migration`='2026_11_22_100000_add_matbu_evrak_alanlari_to_kimyasal_urunler');
   SHOW COLUMNS FROM `kimyasal_urunler` LIKE '%ozet%';
