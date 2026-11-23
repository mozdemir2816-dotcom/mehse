SET NAMES utf8mb4;
UPDATE risk_maddeleri
SET oneri = 'İSG kurulu kurulur; çok tehlikeli işyerinde ayda bir, tehlikeli işyerinde iki ayda bir, az tehlikeli işyerinde üç ayda bir toplanır, kararlar kayıt altına alınır.'
WHERE oneri LIKE '%ayda en az 1 (tehlikeli)%';
SELECT ROW_COUNT() AS guncellenen;
