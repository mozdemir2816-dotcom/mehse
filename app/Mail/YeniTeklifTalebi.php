<?php

namespace App\Mail;

use App\Models\TeklifTalebi;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/** Tanıtım sayfasından yeni teklif talebi geldiğinde sahibe giden bildirim. */
class YeniTeklifTalebi extends Mailable
{
    public function __construct(public TeklifTalebi $talep) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Yeni teklif talebi — '.$this->talep->firma_adi);
    }

    public function content(): Content
    {
        return new Content(text: 'mail.yeni-teklif-talebi');
    }
}
