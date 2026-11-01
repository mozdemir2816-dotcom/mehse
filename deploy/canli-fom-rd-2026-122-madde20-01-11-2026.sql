-- 01.11.2026 — RD-2026-122: önlem sonrası puanı ilk puanla AYNI kalan (ör. Madde 20 Acil Durum/Yangın 180 → 180)
-- Önemli ve üstü (puan >= 70) maddelerde son olasılık, son puanı 60 ve altına indiren en yüksek basamağa çekilir.
-- phpMyAdmin'de bir kez çalıştırın; tekrar çalıştırmak zararsızdır.

UPDATE risk_maddeleri m
JOIN risk_degerlendirmeleri r ON r.id = m.risk_degerlendirmesi_id
SET m.son_olasilik = CASE
        WHEN m.olasilik > 3   AND 3   * m.son_frekans * m.son_siddet <= 60 THEN 3
        WHEN m.olasilik > 1   AND 1   * m.son_frekans * m.son_siddet <= 60 THEN 1
        WHEN m.olasilik > 0.5 AND 0.5 * m.son_frekans * m.son_siddet <= 60 THEN 0.5
        WHEN m.olasilik > 0.2 AND 0.2 * m.son_frekans * m.son_siddet <= 60 THEN 0.2
        ELSE 0.1 END
WHERE r.belge_no = 'RD-2026-122' AND r.yontem = 'fine_kinney'
  AND m.puan >= 70 AND m.son_puan >= m.puan
  AND m.son_frekans IS NOT NULL AND m.son_siddet IS NOT NULL;

UPDATE risk_maddeleri m
JOIN risk_degerlendirmeleri r ON r.id = m.risk_degerlendirmesi_id
SET m.son_puan = ROUND(m.son_olasilik * m.son_frekans * m.son_siddet, 1)
WHERE r.belge_no = 'RD-2026-122' AND r.yontem = 'fine_kinney'
  AND m.son_olasilik IS NOT NULL AND m.son_frekans IS NOT NULL AND m.son_siddet IS NOT NULL;

UPDATE risk_maddeleri m
JOIN risk_degerlendirmeleri r ON r.id = m.risk_degerlendirmesi_id
SET m.son_duzey = CASE
        WHEN m.son_puan >= 400 THEN 'Çok Yüksek Risk' WHEN m.son_puan >= 200 THEN 'Yüksek Risk'
        WHEN m.son_puan >= 70 THEN 'Önemli Risk' WHEN m.son_puan >= 20 THEN 'Olası Risk' ELSE 'Kabul Edilebilir Risk' END
WHERE r.belge_no = 'RD-2026-122' AND r.yontem = 'fine_kinney' AND m.son_puan IS NOT NULL;

UPDATE risk_degerlendirmeleri SET pdf_yolu = NULL, pdf_hazir_at = NULL, pdf_talep_edildi_at = NULL
WHERE belge_no = 'RD-2026-122';

-- Kontrol: 0 satır dönmeli (önlem sonrası puanı ilkinden düşmeyen Önemli+ madde)
SELECT m.id, m.tehlike, m.puan, m.son_olasilik, m.son_puan, m.son_duzey
FROM risk_maddeleri m JOIN risk_degerlendirmeleri r ON r.id = m.risk_degerlendirmesi_id
WHERE r.belge_no = 'RD-2026-122' AND m.puan >= 70 AND m.son_puan >= m.puan;
