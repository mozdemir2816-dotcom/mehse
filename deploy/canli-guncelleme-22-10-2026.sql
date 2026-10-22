-- ============================================================================
-- Canlı veritabanı güncellemesi — 22.10.2026
-- phpMyAdmin'de u2858154_mehse veritabanı seçiliyken SQL sekmesine yapıştırıp
-- çalıştırın. Tüm adımlar GÜVENLİ ŞEKİLDE TEKRAR ÇALIŞTIRILABİLİR (idempotent,
-- IF NOT EXISTS) — bir satır zaten uygulanmışsa sessizce atlanır, veri SİLİNMEZ.
-- ============================================================================


-- ----------------------------------------------------------------------------
-- 1) TÜM TABLOLARI InnoDB'YE ÇEVİR
-- ----------------------------------------------------------------------------
-- Mevcut tablolar MyISAM; yeni foreign key'li tablolar (isg_katip_sozlesmeleri,
-- tabela_tasarimlari, kkd_zimmetleri.kkd_zimmet_formu_id FK) InnoDB gerektirir.
-- Zaten InnoDB olan bir tabloda bu komut no-op'tur, veri/index kaybı olmaz.

ALTER TABLE `acil_durum_krokileri` ENGINE=InnoDB;
ALTER TABLE `acil_durum_planlari` ENGINE=InnoDB;
ALTER TABLE `aday_firmalar` ENGINE=InnoDB;
ALTER TABLE `arsiv_dosyalari` ENGINE=InnoDB;
ALTER TABLE `atama_yazilari` ENGINE=InnoDB;
ALTER TABLE `cache` ENGINE=InnoDB;
ALTER TABLE `cache_locks` ENGINE=InnoDB;
ALTER TABLE `calisanlar` ENGINE=InnoDB;
ALTER TABLE `calisan_temsilcisi_secimleri` ENGINE=InnoDB;
ALTER TABLE `ceza_teblig_tutanaklari` ENGINE=InnoDB;
ALTER TABLE `dof_raporlari` ENGINE=InnoDB;
ALTER TABLE `egitim_atamalari` ENGINE=InnoDB;
ALTER TABLE `egitim_dersleri` ENGINE=InnoDB;
ALTER TABLE `egitim_ders_ilerlemeleri` ENGINE=InnoDB;
ALTER TABLE `egitim_katilimlari` ENGINE=InnoDB;
ALTER TABLE `egitim_kayitlari` ENGINE=InnoDB;
ALTER TABLE `egitim_paketi_sorulari` ENGINE=InnoDB;
ALTER TABLE `egitim_paketleri` ENGINE=InnoDB;
ALTER TABLE `egitim_sinavlari` ENGINE=InnoDB;
ALTER TABLE `egitim_sinav_sonuclari` ENGINE=InnoDB;
ALTER TABLE `egitim_sunumlari` ENGINE=InnoDB;
ALTER TABLE `egitim_turleri` ENGINE=InnoDB;
ALTER TABLE `failed_jobs` ENGINE=InnoDB;
ALTER TABLE `firmalar` ENGINE=InnoDB;
ALTER TABLE `firma_checklist_vadeleri` ENGINE=InnoDB;
ALTER TABLE `firma_jsa` ENGINE=InnoDB;
ALTER TABLE `ipc_tebligleri` ENGINE=InnoDB;
ALTER TABLE `isbasi_egitim_tutanaklari` ENGINE=InnoDB;
ALTER TABLE `ise_donus_belgeleri` ENGINE=InnoDB;
ALTER TABLE `isg_afisleri` ENGINE=InnoDB;
ALTER TABLE `isg_profesyonelleri` ENGINE=InnoDB;
ALTER TABLE `is_ekipmanlari` ENGINE=InnoDB;
ALTER TABLE `is_izin_formlari` ENGINE=InnoDB;
ALTER TABLE `is_izin_sablonlari` ENGINE=InnoDB;
ALTER TABLE `is_kazasi_raporlari` ENGINE=InnoDB;
ALTER TABLE `jobs` ENGINE=InnoDB;
ALTER TABLE `job_batches` ENGINE=InnoDB;
ALTER TABLE `jsa_sablonlari` ENGINE=InnoDB;
ALTER TABLE `kaza_istatistikleri` ENGINE=InnoDB;
ALTER TABLE `kimyasal_risk_degerlendirmeleri` ENGINE=InnoDB;
ALTER TABLE `kimyasal_urunler` ENGINE=InnoDB;
ALTER TABLE `kkd_matrisleri` ENGINE=InnoDB;
ALTER TABLE `kkd_zimmet_formlari` ENGINE=InnoDB;
ALTER TABLE `kullanici_firmalar` ENGINE=InnoDB;
ALTER TABLE `kurul_toplantilari` ENGINE=InnoDB;
ALTER TABLE `meslek_hastaligi_bildirimleri` ENGINE=InnoDB;
ALTER TABLE `migrations` ENGINE=InnoDB;
ALTER TABLE `muayene_formlari` ENGINE=InnoDB;
ALTER TABLE `myk_meslekleri` ENGINE=InnoDB;
ALTER TABLE `nace_kodlari` ENGINE=InnoDB;
ALTER TABLE `olay_kayitlari` ENGINE=InnoDB;
ALTER TABLE `onayli_defter_nushalari` ENGINE=InnoDB;
ALTER TABLE `ortam_olcumleri` ENGINE=InnoDB;
ALTER TABLE `password_reset_tokens` ENGINE=InnoDB;
ALTER TABLE `periyodik_kontroller` ENGINE=InnoDB;
ALTER TABLE `push_abonelikleri` ENGINE=InnoDB;
ALTER TABLE `risk_degerlendirmeleri` ENGINE=InnoDB;
ALTER TABLE `risk_maddeleri` ENGINE=InnoDB;
ALTER TABLE `risk_prosedurleri` ENGINE=InnoDB;
ALTER TABLE `risk_sablonlari` ENGINE=InnoDB;
ALTER TABLE `saglik_gozetimleri` ENGINE=InnoDB;
ALTER TABLE `saha_analizleri` ENGINE=InnoDB;
ALTER TABLE `saha_denetimi_ozel_maddeleri` ENGINE=InnoDB;
ALTER TABLE `saha_denetimleri` ENGINE=InnoDB;
ALTER TABLE `sayfa_yetkileri` ENGINE=InnoDB;
ALTER TABLE `sertifikalar` ENGINE=InnoDB;
ALTER TABLE `sessions` ENGINE=InnoDB;
ALTER TABLE `soru_bankasi_sorulari` ENGINE=InnoDB;
ALTER TABLE `talimatlar` ENGINE=InnoDB;
ALTER TABLE `talimat_sablonlari` ENGINE=InnoDB;
ALTER TABLE `tatbikat_tutanaklari` ENGINE=InnoDB;
ALTER TABLE `tehlikeler` ENGINE=InnoDB;
ALTER TABLE `tehlike_cakismalari` ENGINE=InnoDB;
ALTER TABLE `tehlike_kategorileri` ENGINE=InnoDB;
ALTER TABLE `tespit_oneri_defterleri` ENGINE=InnoDB;
ALTER TABLE `toolbox_konusmalari` ENGINE=InnoDB;
ALTER TABLE `users` ENGINE=InnoDB;
ALTER TABLE `yangin_guvenligi_degerlendirmeleri` ENGINE=InnoDB;
ALTER TABLE `yillik_planlar` ENGINE=InnoDB;
ALTER TABLE `ziyaret_programlari` ENGINE=InnoDB;


-- ----------------------------------------------------------------------------
-- 2) EKSİK KOLON/TABLO DEĞİŞİKLİKLERİ (18-22 Ekim arası migration'lar)
-- ----------------------------------------------------------------------------

ALTER TABLE `risk_maddeleri`
  ADD COLUMN IF NOT EXISTS `tehlike_turu` VARCHAR(20) NULL,
  ADD COLUMN IF NOT EXISTS `maruz_kisi` INT UNSIGNED NULL;

ALTER TABLE `firmalar`
  ADD COLUMN IF NOT EXISTS `deleted_at` TIMESTAMP NULL DEFAULT NULL,
  ADD INDEX IF NOT EXISTS `firmalar_deleted_at_index` (`deleted_at`);

ALTER TABLE `tehlike_kategorileri`
  ADD COLUMN IF NOT EXISTS `yontem` VARCHAR(20) NULL AFTER `ekipman_tipleri`,
  ADD COLUMN IF NOT EXISTS `nace_gruplari` JSON NULL AFTER `yontem`,
  ADD INDEX IF NOT EXISTS `tehlike_kategorileri_yontem_index` (`yontem`);

ALTER TABLE `tehlikeler`
  ADD COLUMN IF NOT EXISTS `matris` JSON NULL;

ALTER TABLE `muayene_formlari`
  ADD COLUMN IF NOT EXISTS `ek2` JSON NULL AFTER `hekim_kase`;

ALTER TABLE `acil_durum_krokileri`
  ADD COLUMN IF NOT EXISTS `kroki3b` JSON NULL;

ALTER TABLE `kkd_matrisleri`
  ADD COLUMN IF NOT EXISTS `gecerli_bolumler` JSON NULL AFTER `satirlar`;

ALTER TABLE `kkd_zimmetleri`
  ADD COLUMN IF NOT EXISTS `kkd_zimmet_formu_id` BIGINT UNSIGNED NULL AFTER `kkd_stok_karti_id`;

-- Foreign key: "IF NOT EXISTS" bu komutta desteklenmiyor. Bu satır "Duplicate
-- foreign key" ya da benzer bir hatayla biterse SORUN DEĞİL (zaten eklenmiş
-- demektir) — sadece bu TEK satırı atlayıp script'in geri kalanını çalıştırın.
ALTER TABLE `kkd_zimmetleri`
  ADD CONSTRAINT `kkd_zimmetleri_kkd_zimmet_formu_id_foreign`
  FOREIGN KEY (`kkd_zimmet_formu_id`) REFERENCES `kkd_zimmet_formlari`(`id`) ON DELETE SET NULL;

CREATE TABLE IF NOT EXISTS `tabela_tasarimlari` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `firma_id` BIGINT UNSIGNED NOT NULL,
  `baslik` VARCHAR(255) NOT NULL,
  `tasarim` JSON NOT NULL,
  `gorsel_yolu` VARCHAR(255) NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  INDEX `tabela_tasarimlari_firma_id_index` (`firma_id`),
  CONSTRAINT `tabela_tasarimlari_firma_id_foreign` FOREIGN KEY (`firma_id`) REFERENCES `firmalar`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ----------------------------------------------------------------------------
-- 3) YENİ ÖZELLİK: İSG-KATİP Sözleşmeleri (bu oturumda eklendi)
-- ----------------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS `isg_katip_sozlesmeleri` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_id` BIGINT UNSIGNED NOT NULL,
  `firma_id` BIGINT UNSIGNED NULL,
  `katip_sozlesme_id` VARCHAR(255) NOT NULL,
  `sozlesme_adi` VARCHAR(255) NULL,
  `oto_guncelleme_izni` TINYINT(1) NULL,
  `mevzuata_uygun_mu` TINYINT(1) NULL,
  `kisi_tc` VARCHAR(255) NULL,
  `kisi_ad` VARCHAR(255) NULL,
  `sertifika_tipi` VARCHAR(255) NULL,
  `sertifika_no` VARCHAR(255) NULL,
  `sozlesme_tipi` VARCHAR(255) NULL,
  `calisma_suresi` VARCHAR(255) NULL,
  `hizmet_veren_unvan` VARCHAR(255) NULL,
  `hizmet_veren_sgk_no` VARCHAR(255) NULL,
  `hizmet_veren_ili` VARCHAR(255) NULL,
  `hizmet_veren_yetki_no` VARCHAR(255) NULL,
  `hizmet_alan_unvan` VARCHAR(255) NULL,
  `hizmet_alan_sgk_no` VARCHAR(255) NULL,
  `hizmet_alan_ili` VARCHAR(255) NULL,
  `hizmet_alan_calisan_sayisi` INT UNSIGNED NULL,
  `hizmet_alan_tehlike_sinifi` VARCHAR(255) NULL,
  `baslangic_tarihi` DATE NULL,
  `bitis_tarihi` DATE NULL,
  `statu` VARCHAR(255) NULL,
  `onay_durumu` VARCHAR(255) NULL,
  `onay_tarihi` DATE NULL,
  `onaylayan_ad` VARCHAR(255) NULL,
  `sonlandiran_ad` VARCHAR(255) NULL,
  `sonlandirma_nedeni` VARCHAR(255) NULL,
  `ham_veri` JSON NULL,
  `son_iceaktarim_at` TIMESTAMP NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  UNIQUE KEY `isg_katip_sozlesmeleri_user_id_katip_sozlesme_id_unique` (`user_id`, `katip_sozlesme_id`),
  KEY `isg_katip_sozlesmeleri_firma_id_foreign` (`firma_id`),
  CONSTRAINT `isg_katip_sozlesmeleri_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  CONSTRAINT `isg_katip_sozlesmeleri_firma_id_foreign` FOREIGN KEY (`firma_id`) REFERENCES `firmalar`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ----------------------------------------------------------------------------
-- 4) migrations TABLOSUNU GÜNCELLE (Laravel'e "bu migration'lar uygulandı" de)
-- ----------------------------------------------------------------------------
-- Her INSERT, o migration zaten kayıtlıysa atlanır (WHERE NOT EXISTS).

INSERT INTO `migrations` (`migration`, `batch`)
SELECT * FROM (SELECT '2026_10_03_150000_add_analitik_alanlari_to_risk_maddeleri' AS migration, 999 AS batch) t
WHERE NOT EXISTS (SELECT 1 FROM `migrations` WHERE `migration` = t.migration);

INSERT INTO `migrations` (`migration`, `batch`)
SELECT * FROM (SELECT '2026_10_13_120000_add_deleted_at_to_firmalar_table' AS migration, 999 AS batch) t
WHERE NOT EXISTS (SELECT 1 FROM `migrations` WHERE `migration` = t.migration);

INSERT INTO `migrations` (`migration`, `batch`)
SELECT * FROM (SELECT '2026_10_18_120000_add_yontem_nace_to_tehlike_kategorileri_table' AS migration, 999 AS batch) t
WHERE NOT EXISTS (SELECT 1 FROM `migrations` WHERE `migration` = t.migration);

INSERT INTO `migrations` (`migration`, `batch`)
SELECT * FROM (SELECT '2026_10_19_120000_add_matris_to_tehlikeler_table' AS migration, 999 AS batch) t
WHERE NOT EXISTS (SELECT 1 FROM `migrations` WHERE `migration` = t.migration);

INSERT INTO `migrations` (`migration`, `batch`)
SELECT * FROM (SELECT '2026_10_19_200000_add_ek2_to_muayene_formlari_table' AS migration, 999 AS batch) t
WHERE NOT EXISTS (SELECT 1 FROM `migrations` WHERE `migration` = t.migration);

INSERT INTO `migrations` (`migration`, `batch`)
SELECT * FROM (SELECT '2026_10_20_080000_add_kroki3b_to_acil_durum_krokileri_table' AS migration, 999 AS batch) t
WHERE NOT EXISTS (SELECT 1 FROM `migrations` WHERE `migration` = t.migration);

INSERT INTO `migrations` (`migration`, `batch`)
SELECT * FROM (SELECT '2026_10_20_120000_add_kkd_zimmet_formu_id_to_kkd_zimmetleri_table' AS migration, 999 AS batch) t
WHERE NOT EXISTS (SELECT 1 FROM `migrations` WHERE `migration` = t.migration);

INSERT INTO `migrations` (`migration`, `batch`)
SELECT * FROM (SELECT '2026_10_20_120500_add_gecerli_bolumler_to_kkd_matrisleri_table' AS migration, 999 AS batch) t
WHERE NOT EXISTS (SELECT 1 FROM `migrations` WHERE `migration` = t.migration);

INSERT INTO `migrations` (`migration`, `batch`)
SELECT * FROM (SELECT '2026_10_21_120000_create_tabela_tasarimlari_table' AS migration, 999 AS batch) t
WHERE NOT EXISTS (SELECT 1 FROM `migrations` WHERE `migration` = t.migration);

INSERT INTO `migrations` (`migration`, `batch`)
SELECT * FROM (SELECT '2026_10_22_043736_create_isg_katip_sozlesmeleri_table' AS migration, 999 AS batch) t
WHERE NOT EXISTS (SELECT 1 FROM `migrations` WHERE `migration` = t.migration);

-- ============================================================================
-- Bitti. Kontrol için:  SELECT COUNT(*) FROM isg_katip_sozlesmeleri;  → 0 dönmeli (yeni tablo, boş).
-- ============================================================================
