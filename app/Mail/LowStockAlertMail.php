<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;

class LowStockAlertMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly Collection $products
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "⚠️ Peringatan: {$this->products->count()} Produk Stok Hampir Habis",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.low-stock-alert',
            with: ['products' => $this->products],
        );
    }
}
