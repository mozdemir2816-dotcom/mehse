-- 01.11.2026 — FOM MAKİNA RD-2026-123 düzeltmeleri. phpMyAdmin'de bir kez çalıştırın; tekrar çalıştırmak zararsızdır.

-- 1) Kapak alanları
UPDATE risk_degerlendirmeleri
SET proje_adi = 'FOM Makina İmalat ve Montaj Tesisi',
    kapsam_notu = 'Talaşlı İmalat (CNC/Torna/Freze), Sac İşleme, Kaynak, Depo ve İdari Alanlar'
WHERE belge_no = 'RD-2026-123';

-- Adres kopyasında "NO:NO:" kaldıysa
UPDATE risk_degerlendirmeleri SET firma_adres = REPLACE(firma_adres, 'NO:NO:', 'No:')
WHERE belge_no = 'RD-2026-123' AND firma_adres LIKE '%NO:NO:%';

-- 2) Önlem sonrası hâlâ Yüksek/Çok Yüksek kalan (ör. Madde 50 bant zımpara gövde kaçağı 240)
--    veya ilk puanından düşmeyen Önemli+ maddeler: son olasılık, son puanı 60 ve altına indiren en yüksek basamağa
--    (Madde 50: 1 × 6 × 40 = 240 → 0,2 × 6 × 40 = 48 OL)
UPDATE risk_maddeleri m
JOIN risk_degerlendirmeleri r ON r.id = m.risk_degerlendirmesi_id
SET m.son_olasilik = CASE
        WHEN m.olasilik > 3   AND 3   * m.son_frekans * m.son_siddet <= 60 THEN 3
        WHEN m.olasilik > 1   AND 1   * m.son_frekans * m.son_siddet <= 60 THEN 1
        WHEN m.olasilik > 0.5 AND 0.5 * m.son_frekans * m.son_siddet <= 60 THEN 0.5
        WHEN m.olasilik > 0.2 AND 0.2 * m.son_frekans * m.son_siddet <= 60 THEN 0.2
        ELSE 0.1 END
WHERE r.belge_no = 'RD-2026-123' AND r.yontem = 'fine_kinney'
  AND m.puan >= 70 AND (m.son_puan >= m.puan OR m.son_puan >= 200)
  AND m.son_frekans IS NOT NULL AND m.son_siddet IS NOT NULL;

UPDATE risk_maddeleri m
JOIN risk_degerlendirmeleri r ON r.id = m.risk_degerlendirmesi_id
SET m.son_puan = ROUND(m.son_olasilik * m.son_frekans * m.son_siddet, 1)
WHERE r.belge_no = 'RD-2026-123' AND r.yontem = 'fine_kinney'
  AND m.son_olasilik IS NOT NULL AND m.son_frekans IS NOT NULL AND m.son_siddet IS NOT NULL;

-- 3) Düzey adlarını standartlaştır (çıktıdaki kısaltmalar: KE / OL / ÖN / YK / ÇY)
UPDATE risk_maddeleri m
JOIN risk_degerlendirmeleri r ON r.id = m.risk_degerlendirmesi_id
SET m.duzey = CASE
        WHEN m.puan IS NULL THEN m.duzey
        WHEN m.puan >= 400 THEN 'Çok Yüksek Risk' WHEN m.puan >= 200 THEN 'Yüksek Risk'
        WHEN m.puan >= 70 THEN 'Önemli Risk' WHEN m.puan >= 20 THEN 'Olası Risk' ELSE 'Kabul Edilebilir Risk' END,
    m.son_duzey = CASE
        WHEN m.son_puan IS NULL THEN m.son_duzey
        WHEN m.son_puan >= 400 THEN 'Çok Yüksek Risk' WHEN m.son_puan >= 200 THEN 'Yüksek Risk'
        WHEN m.son_puan >= 70 THEN 'Önemli Risk' WHEN m.son_puan >= 20 THEN 'Olası Risk' ELSE 'Kabul Edilebilir Risk' END
WHERE r.belge_no = 'RD-2026-123' AND r.yontem = 'fine_kinney';

-- 4) Eski PDF'i sil (sonraki indirmede yeniden üretilir)
UPDATE risk_degerlendirmeleri SET pdf_yolu = NULL, pdf_hazir_at = NULL, pdf_talep_edildi_at = NULL
WHERE belge_no = 'RD-2026-123';

-- Kontrol: 0 satır dönmeli
SELECT m.sira, m.tehlike, m.puan, m.son_olasilik, m.son_puan, m.son_duzey
FROM risk_maddeleri m JOIN risk_degerlendirmeleri r ON r.id = m.risk_degerlendirmesi_id
WHERE r.belge_no = 'RD-2026-123' AND m.puan >= 70 AND (m.son_puan >= m.puan OR m.son_puan >= 200);
