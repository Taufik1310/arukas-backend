<?php

namespace App\Mail;

use App\Models\SaleTransaction;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SaleReceiptMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly SaleTransaction $transaction
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Struk Pembelian #{$this->transaction->code} — POS System",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.sale-receipt',
            with: [
                'transaction' => $this->transaction->load('items.product', 'user'),
            ]
        );
    }
}
