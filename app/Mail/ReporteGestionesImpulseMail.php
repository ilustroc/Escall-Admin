<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class ReporteGestionesImpulseMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $fechaTexto,     // 18/12/2025
        public string $filename2,
        public string $filename3,
        private string $data2,         // binario XLSX
        private string $data3          // binario XLSX
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Reporte de Gestiones - IMPULSE - ESCALL PERU'
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.reporte_gestiones_impulse'
        );
    }

    public function attachments(): array
    {
        $mime = 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';

        return [
            Attachment::fromData(fn () => $this->data2, $this->filename2)->withMime($mime),
            Attachment::fromData(fn () => $this->data3, $this->filename3)->withMime($mime),
        ];
    }
}
