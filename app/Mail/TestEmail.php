<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class TestEmail extends Mailable
{
    public function envelope(): Envelope
    {
        return new Envelope(subject: "Test email from Ameer's CMS");
    }

    public function content(): Content
    {
        return new Content(htmlString: '<p>If you can read this, the CMS can send email.</p>');
    }
}
