<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class GestionesDiariasMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $fecha,
        public string $hora,
        public int $deleted,
        public int $inserted,
        public int $total,
        public array $topStatus,
        public array $topTipificaciones,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Gestiones {$this->fecha} ({$this->hora})"
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.gestiones_diarias'
        );
    }
}
