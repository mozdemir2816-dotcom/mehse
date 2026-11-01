<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * OSGB başvurusu onaylanınca başvurana giden karşılama e-postası (giriş bilgileriyle).
 * Gönderim: App\Support\BasvuruIslemleri::onayla.
 */
class OsgbOnayMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $user,
        public string $geciciSifre,
        public string $osgbUnvani,
        public string $denemeBitis,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Mehse İSG Platformu - OSGB Hesabınız ve 90 Günlük Denemeniz Başlatıldı');
    }

    public function content(): Content
    {
        return new Content(view: 'emails.osgb_onay', with: [
            'girisUrl' => url('/admin/login'),
            'denemeGun' => (int) config('isg.kayit.osgb_deneme_gun', 90),
        ]);
    }
}
