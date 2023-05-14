<?php

namespace App\Mail;

use App\Models\NgaReply;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class Nga extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @var NgaReply
     */
    public NgaReply $reply;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(NgaReply $reply)
    {
        $this->reply = $reply;
    }

    /**
     * Get the message envelope.
     *
     * @return \Illuminate\Mail\Mailables\Envelope
     */
    public function envelope()
    {
        return new Envelope(
            subject: $this->reply->author . '：' . $this->reply->content,
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
            view: 'mail.nga'
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
