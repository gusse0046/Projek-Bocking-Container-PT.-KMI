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
        
        Log::info('ShippingInstructionMail created', [
            'type' => $type,
            'instruction_id' => $mailData['instruction']['instruction_id'] ?? 'unknown',
            'has_pdf' => isset($mailData['pdf_path']) || isset($mailData['pdf_filename'])
        ]);
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
                'instruction_id' => $instruction['instruction_id'] ?? 'unknown',
                'ref_invoice' => $instruction['ref_invoice'] ?? null,
                'forwarder_name' => $instruction['forwarder_name'] ?? null,
                'type' => $this->type
            ],
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.shipping-instruction',
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
            // Method 1: Use direct PDF path if provided
            if (isset($this->mailData['pdf_path']) && file_exists($this->mailData['pdf_path'])) {
                $attachments[] = Attachment::fromPath($this->mailData['pdf_path'])
                    ->as($this->getPdfFileName())
                    ->withMime('application/pdf');
                
                Log::info('PDF attached via direct path', [
                    'instruction_id' => $instruction['instruction_id'],
                    'pdf_path' => $this->mailData['pdf_path'],
                    'file_size' => filesize($this->mailData['pdf_path'])
                ]);
                
                return $attachments;
            }

            // Method 2: Use PDF filename with storage locations
            if (isset($instruction['pdf_filename']) && $instruction['pdf_filename']) {
                $pdfFilename = $instruction['pdf_filename'];
                
                // Try multiple possible storage locations
                $possiblePaths = [
                    'shipping_instructions/' . $pdfFilename,
                    'kmi_shipping_instructions/' . $pdfFilename,
                    'public/shipping_instructions/' . $pdfFilename,
                    'public/kmi_shipping_instructions/' . $pdfFilename
                ];

                $attachmentAdded = false;
                foreach ($possiblePaths as $storagePath) {
                    if (Storage::disk('public')->exists($storagePath)) {
                        $attachments[] = Attachment::fromStorageDisk('public', $storagePath)
                            ->as($this->getPdfFileName())
                            ->withMime('application/pdf');
                        
                        $fullPath = Storage::disk('public')->path($storagePath);
                        Log::info('PDF attached via storage disk', [
                            'instruction_id' => $instruction['instruction_id'],
                            'storage_path' => $storagePath,
                            'full_path' => $fullPath,
                            'file_size' => file_exists($fullPath) ? filesize($fullPath) : 0
                        ]);
                        
                        $attachmentAdded = true;
                        break;
                    }
                }

                if (!$attachmentAdded) {
                    // Try direct file paths
                    $directPaths = [
                        storage_path('app/public/shipping_instructions/' . $pdfFilename),
                        storage_path('app/public/kmi_shipping_instructions/' . $pdfFilename)
                    ];

                    foreach ($directPaths as $directPath) {
                        if (file_exists($directPath)) {
                            $attachments[] = Attachment::fromPath($directPath)
                                ->as($this->getPdfFileName())
                                ->withMime('application/pdf');
                            
                            Log::info('PDF attached via direct file path', [
                                'instruction_id' => $instruction['instruction_id'],
                                'file_path' => $directPath,
                                'file_size' => filesize($directPath)
                            ]);
                            
                            $attachmentAdded = true;
                            break;
                        }
                    }
                }

                if (!$attachmentAdded) {
                    Log::warning('PDF file not found for email attachment', [
                        'instruction_id' => $instruction['instruction_id'],
                        'pdf_filename' => $pdfFilename,
                        'tried_storage_paths' => $possiblePaths,
                        'tried_direct_paths' => $directPaths ?? []
                    ]);
                }
            } else {
                Log::warning('No PDF filename provided for email attachment', [
                    'instruction_id' => $instruction['instruction_id'],
                    'mail_data_keys' => array_keys($this->mailData)
                ]);
            }

        } catch (\Exception $e) {
            Log::error('Error adding PDF attachment to email', [
                'instruction_id' => $instruction['instruction_id'] ?? 'unknown',
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
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
                $urgentFlag = match($priority) {
                    'URGENT' => '🚨 URGENT - ',
                    'HIGH' => '⚡ HIGH - ',
                    default => ''
                };
                $combinedFlag = ($instruction['is_combined'] ?? false) ? ' (COMBINED)' : '';
                return "{$urgentFlag}🚢 Shipping Instruction - {$refInvoice}{$combinedFlag} [{$instructionId}]";
                
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
     * Get PDF file name for attachment
     */
    private function getPdfFileName(): string
    {
        $instruction = $this->mailData['instruction'];
        $instructionId = $instruction['instruction_id'] ?? 'Unknown';
        $refInvoice = preg_replace('/[^a-zA-Z0-9_-]/', '_', $instruction['ref_invoice'] ?? 'Invoice');
        $combinedSuffix = ($instruction['is_combined'] ?? false) ? '_Combined' : '';
        $timestamp = date('Ymd');
        
        return "Shipping_Instruction_{$instructionId}_{$refInvoice}{$combinedSuffix}_{$timestamp}.pdf";
    }
}