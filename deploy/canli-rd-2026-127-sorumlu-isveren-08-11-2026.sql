-- RD-2026-127 (AHMET BULUT): sorumlusu boş ya da varsayılan ("İşveren / İşveren Vekili") olan maddelere
-- sorumlu = "İşveren" yazılır (kullanıcı kararı 08.11.2026). Yalnız bu rapor; dolu sorumlular değişmez.
-- "İşveren" varsayılandan farklı olduğu için yapay zekâ tamamlaması bu alanı artık eksik saymaz.

SET NAMES utf8mb4;

-- Önce kontrol (beklenen: 81)
SELECT COUNT(*) AS degisecek
FROM risk_maddeleri m
JOIN risk_degerlendirmeleri r ON r.id = m.risk_degerlendirmesi_id
WHERE r.belge_no = 'RD-2026-127'
  AND (m.sorumlu IS NULL OR TRIM(m.sorumlu) = '' OR TRIM(m.sorumlu) = 'İşveren / İşveren Vekili');

UPDATE risk_maddeleri m
JOIN risk_degerlendirmeleri r ON r.id = m.risk_degerlendirmesi_id
SET m.sorumlu = 'İşveren', m.updated_at = NOW()
WHERE r.belge_no = 'RD-2026-127'
  AND (m.sorumlu IS NULL OR TRIM(m.sorumlu) = '' OR TRIM(m.sorumlu) = 'İşveren / İşveren Vekili');

-- Sonra kontrol (beklenen: 0)
SELECT COUNT(*) AS kalan
FROM risk_maddeleri m
JOIN risk_degerlendirmeleri r ON r.id = m.risk_degerlendirmesi_id
WHERE r.belge_no = 'RD-2026-127'
  AND (m.sorumlu IS NULL OR TRIM(m.sorumlu) = '' OR TRIM(m.sorumlu) = 'İşveren / İşveren Vekili');
