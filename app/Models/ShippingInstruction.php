<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class ShippingInstructionMail extends Mailable
{
    use Queueable, SerializesModels;

    public $mailData;
    public $type;

    /**
     * Create a new message instance.
     */
    public function __construct(array $mailData, string $type = 'forwarder')
    {
        $this->mailData = $mailData;
        $this->type = $type;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $subject = $this->getSubjectByType();
        $instruction = $this->mailData['instruction'];
        
        return new Envelope(
            from: config('mail.from.address', 'exim_3@pawindo.com'),
            subject: $subject,
            tags: ['shipping_instruction', $this->type],
            metadata: [
                'instruction_id' => $instruction['instruction_id'],
                'ref_invoice' => $instruction['ref_invoice'] ?? null,
                'forwarder_name' => $instruction['forwarder_name'] ?? null,
            ],
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        $view = $this->getViewByType();
        
        return new Content(
            view: $view,
            with: [
                'instruction' => $this->mailData['instruction'],
                'forwarder' => $this->mailData['forwarder'],
                'company' => $this->mailData['company'],
                'type' => $this->type,
                'is_copy' => $this->type === 'copy'
            ]
        );
    }

    /**
     * Get the attachments for the message.
     */
    public function attachments(): array
    {
        $attachments = [];
        $instruction = $this->mailData['instruction'];
        
        try {
            // Attach PDF jika available
            if (isset($instruction['pdf_filename']) && $instruction['pdf_filename']) {
                $pdfPath = 'kmi_shipping_instructions/' . $instruction['pdf_filename'];
                
                if (Storage::disk('public')->exists($pdfPath)) {
                    $attachments[] = Attachment::fromStorageDisk('public', $pdfPath)
                        ->as($this->getPdfFileName())
                        ->withMime('application/pdf');
                    
                    Log::info('PDF attachment added to email', [
                        'instruction_id' => $instruction['instruction_id'],
                        'pdf_filename' => $instruction['pdf_filename'],
                        'pdf_path' => $pdfPath
                    ]);
                } else {
                    Log::warning('PDF file not found for email attachment', [
                        'instruction_id' => $instruction['instruction_id'],
                        'pdf_filename' => $instruction['pdf_filename'],
                        'pdf_path' => $pdfPath
                    ]);
                }
            }
        } catch (\Exception $e) {
            Log::error('Error adding PDF attachment to email', [
                'instruction_id' => $instruction['instruction_id'] ?? 'unknown',
                'error' => $e->getMessage()
            ]);
        }
        
        return $attachments;
    }

    /**
     * Get subject based on mail type
     */
    private function getSubjectByType(): string
    {
        $instruction = $this->mailData['instruction'];
        $instructionId = $instruction['instruction_id'] ?? 'Unknown';
        $refInvoice = $instruction['ref_invoice'] ?? 'N/A';
        
        switch ($this->type) {
            case 'forwarder':
                $priority = strtoupper($instruction['priority'] ?? 'NORMAL');
                $urgentFlag = $priority === 'URGENT' ? '🚨 URGENT - ' : '';
                return "{$urgentFlag}🚢 Shipping Instruction - {$refInvoice} [{$instructionId}]";
                
            case 'booking':
                return "📦 Container Booking Request - {$refInvoice} [{$instructionId}]";
                
            case 'export_response':
                return "📋 Forwarder Response Received - {$refInvoice} [{$instructionId}]";
                
            case 'copy':
                return "📄 COPY: Shipping Instruction - {$refInvoice} [{$instructionId}]";
                
            case 'import':
                return "📦 Import Delivery Instruction - [{$instructionId}]";
                
            default:
                return "📋 Shipping Instruction Notification - [{$instructionId}]";
        }
    }

    /**
     * Get email view based on type
     */
    private function getViewByType(): string
    {
        switch ($this->type) {
            case 'forwarder':
                return 'emails.shipping-instruction';
                
            case 'booking':
                return 'emails.booking-request';
                
            case 'export_response':
                return 'emails.forwarder-response-export';
                
            case 'copy':
                return 'emails.shipping-instruction';
                
            case 'import':
                return 'emails.import-instruction';
                
            default:
                return 'emails.shipping-instruction';
        }
    }

    /**
     * Get PDF file name
     */
    private function getPdfFileName(): string
    {
        $instruction = $this->mailData['instruction'];
        $instructionId = $instruction['instruction_id'] ?? 'Unknown';
        $refInvoice = str_replace(['/', '\\', ':', '*', '?', '"', '<', '>', '|'], '_', $instruction['ref_invoice'] ?? 'Invoice');
        
        return "Shipping_Instruction_{$instructionId}_{$refInvoice}.pdf";
    })