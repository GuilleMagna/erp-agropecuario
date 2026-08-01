<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;

class ReporteComprobantesArca extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @param  Collection<string, Collection>  $comprasPorEmpresa  Razón social => compras nuevas de esa empresa
     */
    public function __construct(
        public Collection $comprasPorEmpresa,
        public string $desde,
        public string $hasta,
    ) {}

    public function envelope(): Envelope
    {
        $total = $this->comprasPorEmpresa->flatten()->count();

        return new Envelope(
            subject: "ARCA: {$total} comprobante(s) nuevo(s) sincronizado(s)",
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.reporte-comprobantes-arca',
            with: [
                'comprasPorEmpresa' => $this->comprasPorEmpresa,
                'desde' => $this->desde,
                'hasta' => $this->hasta,
                'totalGeneral' => $this->comprasPorEmpresa->flatten()->sum('total'),
            ],
        );
    }
}
