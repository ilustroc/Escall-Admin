<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ReporteGestionesKpInvestMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $fechaInicio,
        public string $fechaFin,
        public string $filename,
        public string $data
    ) {}

    public function build()
    {
        return $this->subject('Reporte de Gestiones - KP INVEST - ESCALL PERU')
            ->view('emails.reporte_gestiones_kpinvest')
            ->attachData($this->data, $this->filename, [
                'mime' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
            ]);
    }
}
