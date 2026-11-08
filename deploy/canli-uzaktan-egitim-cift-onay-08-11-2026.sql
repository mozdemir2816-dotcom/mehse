-- 08.11.2026 — Uzaktan eğitim: işyeri hekimi onayı + soru konu/zorluk alanları
-- Canlıya yüklemeden ÖNCE phpMyAdmin'de çalıştırın.
ALTER TABLE egitim_atamalari
  ADD COLUMN ih_onay_at TIMESTAMP NULL,
  ADD COLUMN ih_onaylayan_id BIGINT UNSIGNED NULL,
  ADD COLUMN ih_ad_soyad VARCHAR(255) NULL,
  ADD COLUMN ih_belge_no VARCHAR(255) NULL;

ALTER TABLE egitim_paketi_sorulari
  ADD COLUMN modul TINYINT UNSIGNED NULL,
  ADD COLUMN zorluk VARCHAR(20) NULL;

INSERT INTO `migrations` (`migration`,`batch`) SELECT '2026_11_08_120000_add_ih_onay_ve_soru_konusu', (SELECT COALESCE(MAX(`batch`),0)+1 FROM `migrations`) FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `migrations` WHERE `migration`='2026_11_08_120000_add_ih_onay_ve_soru_konusu');
