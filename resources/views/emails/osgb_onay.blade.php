<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Mehse İSG - OSGB hesabınız açıldı</title>
</head>
<body style="margin:0;padding:0;background-color:#f1f5f9;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color:#f1f5f9;">
<tr>
<td align="center" style="padding:24px 12px;">
<table role="presentation" width="600" cellpadding="0" cellspacing="0" border="0" style="width:100%;max-width:600px;background-color:#ffffff;border-radius:12px;overflow:hidden;font-family:Arial,Helvetica,sans-serif;color:#1e293b;">

    {{-- 1. Üst başlık --}}
    <tr>
        <td style="background-color:#0f172a;padding:24px 32px;">
            <div style="font-size:22px;font-weight:bold;color:#ffffff;letter-spacing:1px;">MEHSE İSG</div>
            <div style="font-size:13px;color:#94a3b8;margin-top:4px;">Kurumsal İSG Bulut Yönetim Platformu</div>
        </td>
    </tr>

    {{-- 2. Karşılama --}}
    <tr>
        <td style="padding:32px 32px 8px 32px;font-size:15px;line-height:24px;">
            <p style="margin:0 0 12px 0;">Sayın {{ $user->name }},</p>
            <p style="margin:0;">{{ $osgbUnvani }} adına ilettiğiniz kurumsal OSGB başvurusu onaylanmıştır. Mehse İSG platformuna hoş geldiniz.</p>
        </td>
    </tr>

    {{-- 3. Deneme süresi kutusu --}}
    <tr>
        <td style="padding:16px 32px;">
            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color:#e0f2fe;border-left:4px solid #0284c7;border-radius:6px;">
                <tr>
                    <td style="padding:16px 20px;font-size:14px;line-height:22px;color:#0c4a6e;">
                        <strong>{{ $denemeGun }} günlük ücretsiz deneme süreniz başladı.</strong><br>
                        Deneme süreniz <strong>{{ $denemeBitis }}</strong> tarihine kadar geçerlidir. Hesabınıza tanımlanan modüllere bu süre boyunca ücretsiz erişebilirsiniz.
                    </td>
                </tr>
            </table>
        </td>
    </tr>

    {{-- 4. Giriş bilgileri kartı --}}
    <tr>
        <td style="padding:8px 32px 16px 32px;">
            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="border:1px solid #e2e8f0;border-radius:8px;">
                <tr>
                    <td colspan="2" style="padding:14px 20px;background-color:#f8fafc;border-bottom:1px solid #e2e8f0;font-size:14px;font-weight:bold;color:#0f172a;">Giriş bilgileriniz</td>
                </tr>
                <tr>
                    <td style="padding:12px 20px 6px 20px;font-size:13px;color:#64748b;width:120px;">Panel adresi</td>
                    <td style="padding:12px 20px 6px 0;font-size:14px;"><a href="{{ $girisUrl }}" style="color:#0284c7;text-decoration:none;">{{ $girisUrl }}</a></td>
                </tr>
                <tr>
                    <td style="padding:6px 20px;font-size:13px;color:#64748b;">E-posta</td>
                    <td style="padding:6px 20px 6px 0;font-size:14px;">{{ $user->email }}</td>
                </tr>
                <tr>
                    <td style="padding:6px 20px 14px 20px;font-size:13px;color:#64748b;">Geçici parola</td>
                    <td style="padding:6px 20px 14px 0;font-size:15px;font-family:Consolas,Menlo,monospace;font-weight:bold;color:#0f172a;">{{ $geciciSifre }}</td>
                </tr>
            </table>
            <p style="margin:10px 0 0 0;font-size:12px;line-height:18px;color:#64748b;">Güvenliğiniz için ilk girişinizde Güvenlik sayfasından parolanızı değiştirmenizi öneririz.</p>
        </td>
    </tr>

    {{-- 5. CTA --}}
    <tr>
        <td align="center" style="padding:8px 32px 24px 32px;">
            <table role="presentation" cellpadding="0" cellspacing="0" border="0">
                <tr>
                    <td align="center" style="background-color:#0284c7;border-radius:8px;">
                        <a href="{{ $girisUrl }}" style="display:inline-block;padding:14px 32px;font-size:15px;font-weight:bold;color:#ffffff;text-decoration:none;">Panele Giriş Yap &rarr;</a>
                    </td>
                </tr>
            </table>
        </td>
    </tr>

    {{-- 6. Hızlı başlangıç --}}
    <tr>
        <td style="padding:0 32px 24px 32px;">
            <div style="font-size:15px;font-weight:bold;color:#0f172a;margin-bottom:10px;">Hızlı başlangıç</div>
            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="font-size:14px;line-height:22px;">
                <tr>
                    <td valign="top" style="width:28px;padding:4px 0;color:#0284c7;font-weight:bold;">1.</td>
                    <td style="padding:4px 0;"><strong>Firmalarınızı ekleyin:</strong> Hizmet verdiğiniz işyerlerini unvan, SGK sicil ve tehlike sınıfıyla kaydedin.</td>
                </tr>
                <tr>
                    <td valign="top" style="padding:4px 0;color:#0284c7;font-weight:bold;">2.</td>
                    <td style="padding:4px 0;"><strong>Risk analizi yapın:</strong> 5x5 matris veya Fine-Kinney yöntemiyle değerlendirmenizi hazırlayıp çıktısını alın.</td>
                </tr>
                <tr>
                    <td valign="top" style="padding:4px 0;color:#0284c7;font-weight:bold;">3.</td>
                    <td style="padding:4px 0;"><strong>Eğitim ve sertifika:</strong> Eğitim kayıtlarını girin, katılımcı sertifikalarını toplu olarak basın.</td>
                </tr>
            </table>
        </td>
    </tr>

    {{-- 7. İletişim & destek --}}
    <tr>
        <td style="background-color:#0f172a;padding:20px 32px;font-size:13px;line-height:20px;color:#cbd5e1;">
            <strong style="color:#ffffff;">İletişim &amp; Destek</strong><br>
            WhatsApp: <a href="https://wa.me/905064754855" style="color:#38bdf8;text-decoration:none;">0506 475 48 55</a><br>
            E-posta: <a href="mailto:mozdemir2816@gmail.com" style="color:#38bdf8;text-decoration:none;">mozdemir2816@gmail.com</a><br>
            <span style="color:#64748b;">Hafta içi 09:00 - 18:00</span>
        </td>
    </tr>

</table>
</td>
</tr>
</table>
</body>
</html>
