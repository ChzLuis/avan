<?php

namespace App\Mail;

use App\Models\DemoRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class DemoCreada extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public DemoRequest $demo,
        public string $password,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: '🎉 Tu demo de BIXO está lista — ' . $this->demo->business_name);
    }

    public function content(): Content
    {
        return new Content(view: 'emails.demo-creada');
    }
}
