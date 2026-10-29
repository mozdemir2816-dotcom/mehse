# Canlıda e-posta (SMTP) kurulumu

Şifremi unuttum bağlantısı e-postayla gider. Canlıda `.env` dosyasında `MAIL_MAILER=log` iken e-posta
**gönderilmez**, `storage/logs/laravel.log` içine yazılır. `.env` deploy ile gitmez; canlıda (cPanel
Dosya Yöneticisi veya FTP) elle düzenlenir. Şifre bu dosyaya yazılır, sohbete/koda yazılmaz.

## Seçenek A — Hosting posta kutusu (önerilen)
cPanel → E-posta Hesapları → yeni hesap (ör. `bilgi@alanadiniz.com`). Gönderen adresi alan adınızla
aynı olunca e-postalar daha az spama düşer.

```
MAIL_MAILER=smtp
MAIL_HOST=mail.alanadiniz.com
MAIL_PORT=465
MAIL_SCHEME=smtps
MAIL_USERNAME=bilgi@alanadiniz.com
MAIL_PASSWORD=<posta kutusu şifresi>
MAIL_FROM_ADDRESS=bilgi@alanadiniz.com
MAIL_FROM_NAME="MEHSE İSG"
```
(Host/port cPanel'deki "E-posta hesapları → Bağlan" ekranında yazar. 587 kullanılırsa `MAIL_PORT=587` ve
`MAIL_SCHEME=null`.)

## Seçenek B — Gmail
Hesapta 2 adımlı doğrulama açık olmalı; Google Hesabı → Güvenlik → Uygulama şifreleri ile 16 haneli
uygulama şifresi oluşturulur (normal Gmail şifresi çalışmaz).

```
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=465
MAIL_SCHEME=smtps
MAIL_USERNAME=<gmail adresi>
MAIL_PASSWORD=<uygulama şifresi>
MAIL_FROM_ADDRESS=<aynı gmail adresi>
MAIL_FROM_NAME="MEHSE İSG"
```

## Ayardan sonra
1. `.env` kaydedildikten sonra gerekiyorsa `php artisan config:clear` (hosting'de yapılandırma önbelleği
   varsa; deploy.mjs bunu yapmaz).
2. Giriş ekranında **Şifremi unuttum?** → kendi e-postanızı girin → gelen kutusu/spam'e bakın.
3. Gelmezse `storage/logs/laravel.log` son satırlarındaki SMTP hatası nedenini gösterir.

Not: sıfırlama bağlantısı `APP_URL` ile üretilir; canlıda `APP_URL=https://alanadiniz.com` olmalı.
`password_reset_tokens` tablosu Laravel'in ilk migration'ıyla gelir; canlıda yoksa:
`SHOW TABLES LIKE 'password_reset_tokens';` boş dönerse haber verin, SQL'ini hazırlarız.
