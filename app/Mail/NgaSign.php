<?php

namespace App\Mail;

use App\Models\NgaReply;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Str;

class NgaSign extends Mailable
{
    use Queueable, SerializesModels;


    public string $author;
    public string $sign;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($author, $sign)
    {
        $this->author = $author;
        $this->sign = $sign;
    }

    /**
     * Get the message envelope.
     *
     * @return \Illuminate\Mail\Mailables\Envelope
     */
    public function envelope()
    {
        return new Envelope(
            subject: $this->author . '：' . Str::substr($this->sign, 0, 15)
        );
    }

    /**
     * Get the message content definition.
     *
     * @return \Illuminate\Mail\Mailables\Content
     */
    public function content()
    {
        return new Content(
            view: 'mail.ngasign'
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array
     */
    public function attachments()
    {
        return [];
    }
}
