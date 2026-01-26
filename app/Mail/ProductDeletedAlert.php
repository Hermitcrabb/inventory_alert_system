<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ProductDeletedAlert extends Mailable
{
    use Queueable, SerializesModels;

    public $productTitle;
    public $sku;
    public $variantTitle;
    public $quantity;
    public $inventoryItemId;
    public $deletedFrom;

    /**
     * Create a new message instance.
     *
     * @param array $details
     * @param string $deletedFrom 'Shopify Webhook' or 'Dashboard Manual Action'
     */
    public function __construct(array $details, string $deletedFrom = 'Shopify')
    {
        $this->productTitle = $details['product_title'] ?? 'Unknown Product';
        $this->sku = $details['sku'] ?? 'N/A';
        $this->variantTitle = $details['variant_title'] ?? 'N/A';
        $this->quantity = $details['quantity'] ?? 0;
        $this->inventoryItemId = $details['inventory_item_id'] ?? 'N/A';
        $this->deletedFrom = $deletedFrom;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Product Deleted: ' . $this->productTitle . ' (' . $this->sku . ')',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.product-deleted',
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
