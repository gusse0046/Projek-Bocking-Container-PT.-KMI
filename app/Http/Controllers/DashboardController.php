<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Http;
use App\Models\ExportData;
use App\Models\ImportData;
use App\Models\Forwarder;
use App\Mail\ShippingInstructionMail;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;

class DashboardController extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;

    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Export Dashboard - Enhanced Version
     */
   public function exportDashboard(Request $request)
{
    try {
        Log::info('Export Dashboard loading', [
            'user' => auth()->user()->email ?? 'unknown',
            'location' => $request->get('location', 'all')
        ]);

        // Get export data
        $exportDataQuery = ExportData::whereNotNull('reference_invoice')
                                  ->where('reference_invoice', '!=', '');

        $location = $request->get('location', 'all');
        if ($location === 'surabaya') {
            $exportDataQuery->where('delivery_type', 'ZDO1');
        } elseif ($location === 'semarang') {
            $exportDataQuery->where('delivery_type', 'ZDO2');
        }

        $exportData = $exportDataQuery->orderBy('reference_invoice')
                                     ->orderBy('delivery_date', 'desc')
                                     ->get();

        // TAMBAHKAN KODE INI DI SINI:
        $cleanExportData = $exportData->map(function ($item) {
            return [
                'id' => $item->id,
                'reference_invoice' => $item->reference_invoice,
                'buyer' => $item->buyer,
                'quantity' => (float) ($item->quantity ?? 0),
                'volume' => (float) ($item->volume ?? 0),
                'weight' => (float) ($item->weight ?? 0),
                'delivery_type' => $item->delivery_type,
                'forwarder_code' => $item->forwarder_code,
                'export_destination' => $item->export_destination,
                'delivery_date' => $item->delivery_date,
                'created_at' => $item->created_at,
                'updated_at' => $item->updated_at
            ];
        })->toArray();

        $forwarders = Forwarder::where('is_active', true)->get();
        
        // Calculate simple statistics
        $statistics = $this->calculateSimpleStatistics($exportData);

        // UBAH return view ini:
        return view('dashboard', [
            'exportData' => $cleanExportData,
            'forwarders' => $forwarders,
            'statistics' => $statistics,
            'location' => $location
        ]);

    } catch (\Exception $e) {
        Log::error('Error in export dashboard', [
            'error' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine()
        ]);

        return view('dashboard', [
            'exportData' => collect([]),
            'forwarders' => collect([]),
            'statistics' => $this->getDefaultStatistics(),
            'location' => 'all'
        ]);
    }
}

    /**
     * Import Dashboard
     */
    public function importDashboard(Request $request)
    {
        try {
            $importData = ImportData::whereNotNull('reference_invoice')
                                  ->where('reference_invoice', '!=', '')
                                  ->get();

            $forwarders = Forwarder::where('is_active', true)->get();
            $statistics = $this->getDefaultStatistics();

            return view('import_dashboard', compact('importData', 'forwarders', 'statistics'));

        } catch (\Exception $e) {
            Log::error('Import dashboard error', ['error' => $e->getMessage()]);
            
            return view('import_dashboard', [
                'importData' => collect([]),
                'forwarders' => collect([]),
                'statistics' => $this->getDefaultStatistics()
            ]);
        }
    }

    /**
     * Auto-fill forwarder data - Enhanced
     */
    public function getForwarderDataForAutoFill(Request $request)
    {
        try {
            $refInvoice = $request->get('ref_invoice') ?? $request->input('ref_invoice');
            $location = $request->get('location', 'surabaya');
            $isCombined = $request->boolean('is_combined', false);

            Log::info('Auto-fill request received', [
                'ref_invoice' => $refInvoice,
                'location' => $location,
                'is_combined' => $isCombined
            ]);

            if (!$refInvoice) {
                return response()->json([
                    'success' => false,
                    'error' => 'Reference invoice is required'
                ]);
            }

            // Handle combined invoices
            $searchRefInvoice = $refInvoice;
            if ($isCombined && strpos($refInvoice, '(Combined') !== false) {
                preg_match('/^(.+?)\s*\(Combined/', $refInvoice, $matches);
                if (isset($matches[1])) {
                    $baseName = trim($matches[1]);
                    $numericPrefix = $this->extractStrictThreeDigitPrefix($baseName);
                    if ($numericPrefix) {
                        $exportData = ExportData::where('reference_invoice', 'LIKE', $numericPrefix . '%')
                                              ->whereNotNull('forwarder_code')
                                              ->first();
                        if ($exportData) {
                            $searchRefInvoice = $exportData->reference_invoice;
                        }
                    }
                }
            }

            // Get export data
            $exportItem = ExportData::where('reference_invoice', $searchRefInvoice)->first();

            if (!$exportItem) {
                return response()->json([
                    'success' => false,
                    'error' => 'No export data found for this invoice'
                ]);
            }

            // Get forwarder if mapped
            $forwarder = null;
            if ($exportItem->forwarder_code) {
                $forwarder = Forwarder::where('code', $exportItem->forwarder_code)
                                     ->where('is_active', true)
                                     ->first();
            }

            $defaultPort = $location === 'surabaya' ? 'Tanjung Perak - Surabaya' : 'Tanjung Emas - Semarang';

            // Build complete forwarder data
            $forwarderData = null;
            $autoFillData = [
                'forwarder_name' => $forwarder ? $forwarder->name : '',
                'notification_email' => $forwarder ? $forwarder->primary_email : '',
                'contact_person' => $forwarder ? $forwarder->contact_person : '',
                'port_loading' => $defaultPort,
                'pickup_location' => $forwarder ? $forwarder->address : 'To be confirmed',
                'phone' => $forwarder ? $forwarder->phone : '',
                'suggested_container_type' => '1 X 40 HC',
                'suggested_priority' => 'normal'
            ];

            if ($forwarder) {
                // Parse JSON arrays
                $emails = [];
                $whatsappNumbers = [];
                
                if ($forwarder->emails) {
                    $emails = is_string($forwarder->emails) ? 
                        json_decode($forwarder->emails, true) ?? [] : 
                        (is_array($forwarder->emails) ? $forwarder->emails : []);
                }
                
                if ($forwarder->whatsapp_numbers) {
                    $whatsappNumbers = is_string($forwarder->whatsapp_numbers) ? 
                        json_decode($forwarder->whatsapp_numbers, true) ?? [] : 
                        (is_array($forwarder->whatsapp_numbers) ? $forwarder->whatsapp_numbers : []);
                }

                $primaryEmail = $forwarder->primary_email ?: (!empty($emails) ? $emails[0] : '');
                $ccEmails = array_values(array_filter($emails, fn($email) => $email !== $primaryEmail));

                $forwarderData = [
                    'code' => $forwarder->code,
                    'name' => $forwarder->name,
                    'primary_email' => $primaryEmail,
                    'all_emails' => $emails,
                    'cc_emails' => $ccEmails,
                    'all_whatsapp' => $whatsappNumbers,
                    'contact_person' => $forwarder->contact_person,
                    'phone' => $forwarder->phone,
                    'address' => $forwarder->address
                ];

                // Update auto-fill data with complete info
                $autoFillData = array_merge($autoFillData, [
                    'all_emails' => $emails,
                    'cc_emails' => $ccEmails,
                    'all_whatsapp' => $whatsappNumbers
                ]);
            }

            return response()->json([
                'success' => true,
                'auto_fill_data' => $autoFillData,
                'forwarder_data' => $forwarderData,
                'location' => $location
            ]);

        } catch (\Exception $e) {
            Log::error('Error in auto-fill', [
                'error' => $e->getMessage(),
                'ref_invoice' => $request->get('ref_invoice') ?? 'unknown'
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to get forwarder data'
            ]);
        }
    }

    /**
     * Generate Shipping Instruction PDF - Enhanced for Combined Invoices
     */
   public function generateShippingInstructionPDF(Request $request)
{
    try {
        Log::info('PDF generation started', [
            'user' => auth()->user()->email,
            'ref_invoice' => $request->ref_invoice,
            'is_combined' => $request->boolean('is_combined', false)
        ]);

        // Validate required fields
        $request->validate([
            'ref_invoice' => 'required|string',
            'forwarder_name' => 'required|string',
            'notification_email' => 'required|email',
            'pickup_location' => 'required|string',
            'expected_pickup_date' => 'required|date',
            'container_type' => 'required|string',
            'port_loading' => 'required|string',
            'port_destination' => 'required|string',
            'contact_person' => 'required|string',
            'freight_payment' => 'required|string'
        ]);

        $refInvoice = $request->ref_invoice;
        $isCombined = $request->boolean('is_combined', false);
        $exportDataIds = $request->export_data_ids ?? [];

        // Get export data - handle combined invoices
        if ($isCombined && strpos($refInvoice, '(Combined') !== false) {
            // Extract base name and find by prefix
            preg_match('/^(.+?)\s*\(Combined/', $refInvoice, $matches);
            if (isset($matches[1])) {
                $baseName = trim($matches[1]);
                $numericPrefix = $this->extractStrictThreeDigitPrefix($baseName);
                if ($numericPrefix) {
                    $exportData = ExportData::where('reference_invoice', 'LIKE', $numericPrefix . '%')
                                          ->orderBy('reference_invoice')
                                          ->get();
                } else {
                    $exportData = collect([]);
                }
            } else {
                $exportData = collect([]);
            }
        } elseif (!empty($exportDataIds)) {
            // Use specific IDs
            $exportData = ExportData::whereIn('id', $exportDataIds)->get();
        } else {
            // Single invoice
            $exportData = ExportData::where('reference_invoice', $refInvoice)->get();
        }

        if ($exportData->isEmpty()) {
            return response()->json([
                'success' => false,
                'error' => 'No export data found for this reference invoice'
            ]);
        }

        // Calculate totals
        $totalVolume = $exportData->sum('volume') ?? 0;
        $totalWeight = $exportData->sum('weight') ?? 0;
        $totalQuantity = $exportData->sum('quantity') ?? 0;
        $uniqueBuyers = $exportData->pluck('buyer')->unique()->values()->toArray();
        $referenceInvoices = $exportData->pluck('reference_invoice')->unique()->values()->toArray();

        // Generate unique instruction ID
        $instructionId = 'SI-EXP-' . date('Ymd') . '-' . strtoupper(substr(str_replace(['/', '\\', ' '], '', $refInvoice), -6)) . '-' . date('His');

        // Build instruction data
        $instructionData = [
            'instruction_id' => $instructionId,
            'type' => 'export',
            'ref_invoice' => $refInvoice,
            'is_combined' => $isCombined,
            'export_data' => $exportData,
            'export_data_ids' => $exportData->pluck('id')->toArray(),
            
            // Form data
            'forwarder_name' => $request->forwarder_name,
            'notification_email' => $request->notification_email,
            'pickup_location' => $request->pickup_location,
            'expected_pickup_date' => $request->expected_pickup_date,
            'container_type' => $request->container_type,
            'priority' => $request->priority ?? 'normal',
            'port_loading' => $request->port_loading,
            'port_destination' => $request->port_destination,
            'contact_person' => $request->contact_person,
            'freight_payment' => $request->freight_payment,
            'special_instructions' => $request->special_instructions,
            
            // Totals
            'total_volume' => $totalVolume,
            'total_weight' => $totalWeight,
            'total_quantity' => $totalQuantity,
            'unique_buyers' => $uniqueBuyers,
            'reference_invoices' => $referenceInvoices,
            'primary_buyer' => $uniqueBuyers[0] ?? 'N/A',
            
            // Workflow tracking
            'status' => 'generated',
            'generated_at' => now(),
            'generated_by' => auth()->user()->name,
            'sent_by' => auth()->user()->name,
            'sent_by_email' => auth()->user()->email,
            'forwarder_code' => $exportData->first()->forwarder_code ?? 'UNKNOWN',
            
            // Additional data for combined invoices
            'sub_invoices' => $isCombined ? $this->buildSubInvoicesData($exportData) : null,
            'numeric_prefix' => $this->extractStrictThreeDigitPrefix($refInvoice)
        ];

        // Generate PDF
        $pdfResult = $this->createPDFFile($instructionData);
        
        if (!$pdfResult['success']) {
            return response()->json([
                'success' => false,
                'error' => $pdfResult['error']
            ]);
        }

        // Update instruction with PDF info
        $instructionData['pdf_generated'] = true;
        $instructionData['pdf_filename'] = $pdfResult['filename'];
        $instructionData['pdf_path'] = $pdfResult['path'];
        $instructionData['pdf_url'] = $pdfResult['url'];
        $instructionData['pdf_size'] = $pdfResult['size'];
        $instructionData['sent_at'] = now()->toISOString();

        // TAMBAHAN: Simpan WhatsApp data untuk send notification nanti
        if ($instructionData['forwarder_code']) {
            $forwarder = Forwarder::where('code', $instructionData['forwarder_code'])->first();
            if ($forwarder) {
                $forwarderData = $this->buildCompleteForwarderData($forwarder);
                if ($forwarderData) {
                    $instructionData['whatsapp_numbers'] = $forwarderData['all_whatsapp'] ?? [];
                    $instructionData['primary_whatsapp'] = $forwarderData['primary_whatsapp'] ?? '';
                    $instructionData['cc_emails'] = $forwarderData['cc_emails'] ?? [];
                    
                    Log::info('WhatsApp data saved to instruction cache', [
                        'instruction_id' => $instructionData['instruction_id'],
                        'forwarder_code' => $instructionData['forwarder_code'],
                        'whatsapp_count' => count($instructionData['whatsapp_numbers']),
                        'primary_whatsapp' => $instructionData['primary_whatsapp'],
                        'cc_emails_count' => count($instructionData['cc_emails'])
                    ]);
                }
            }
        }

        // Cache the instruction
        cache()->put("workflow_instruction_{$instructionId}", $instructionData, now()->addDays(30));
        
        // Update all instructions cache
        $allInstructions = cache()->get('all_workflow_instructions', []);
        $allInstructions[$instructionId] = $instructionData;
        cache()->put('all_workflow_instructions', $allInstructions, now()->addDays(30));

        Log::info('PDF generated successfully', [
            'instruction_id' => $instructionId,
            'pdf_filename' => $pdfResult['filename'],
            'is_combined' => $isCombined,
            'total_items' => $exportData->count()
        ]);

        return response()->json([
            'success' => true,
            'message' => 'PDF generated successfully',
            'instruction_id' => $instructionId,
            'pdf_filename' => $pdfResult['filename'],
            'pdf_url' => $pdfResult['url'],
            'pdf_size' => $this->formatFileSize($pdfResult['size']),
            'can_view' => true,
            'can_send' => true,
            'view_url' => route('pdf.view', ['filename' => $pdfResult['filename']]),
            'download_url' => route('pdf.download', ['filename' => $pdfResult['filename']]),
            'forwarder_code' => $instructionData['forwarder_code']
        ]);

    } catch (\Exception $e) {
        Log::error('PDF generation failed', [
            'error' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine()
        ]);

        return response()->json([
            'success' => false,
            'error' => 'PDF generation failed: ' . $e->getMessage()
        ]);
    }
}

    /**
     * ENHANCED: Send Container Booking Request - Complete Integration with WhatsApp
     */
    public function sendContainerBookingRequest(Request $request)
    {
        try {
            $instructionId = $request->instruction_id;
            
            Log::info('Send notification request received with WhatsApp enhancement', [
                'instruction_id' => $instructionId,
                'user' => auth()->user()->email,
                'request_data' => $request->all()
            ]);
            
            if (!$instructionId) {
                return response()->json([
                    'success' => false,
                    'error' => 'Instruction ID is required'
                ]);
            }

            // Get instruction from cache
            $instruction = cache()->get("workflow_instruction_{$instructionId}");
            
            if (!$instruction) {
                Log::error('Instruction not found in cache', ['instruction_id' => $instructionId]);
                return response()->json([
                    'success' => false,
                    'error' => 'Instruction not found in cache system'
                ]);
            }

            if (!($instruction['pdf_generated'] ?? false)) {
                return response()->json([
                    'success' => false,
                    'error' => 'PDF must be generated first before sending notifications'
                ]);
            }

            // Initialize results
            $results = [
                'email_sent' => false,
                'whatsapp_sent' => false,
                'forwarder_portal_sent' => false,
                'email_count' => 0,
                'whatsapp_count' => 0,
                'errors' => [],
                'details' => []
            ];

            // Send email notifications (keep existing logic)
            if ($request->boolean('send_email', true)) {
                Log::info('Attempting to send email notification', ['instruction_id' => $instructionId]);
                $emailResult = $this->sendEmailWithPDFFixed($instruction, $request);
                $results['email_sent'] = $emailResult['success'];
                $results['email_count'] = $emailResult['count'] ?? 0;
                $results['details']['email'] = $emailResult;
                
                if (!$emailResult['success']) {
                    $results['errors'][] = 'Email: ' . ($emailResult['error'] ?? 'Unknown error');
                }
            }

            // ENHANCED: AUTO-SEND WhatsApp when email is successful
            if ($results['email_sent']) {
                Log::info('Email sent successfully, now sending WhatsApp notification automatically', ['instruction_id' => $instructionId]);
                $whatsappResult = $this->sendWablasWhatsAppNotification($instruction, $request);
                $results['whatsapp_sent'] = $whatsappResult['success'];
                $results['whatsapp_count'] = $whatsappResult['count'] ?? 0;
                $results['details']['whatsapp'] = $whatsappResult;
                
                if (!$whatsappResult['success']) {
                    $results['errors'][] = 'WhatsApp: ' . ($whatsappResult['error'] ?? 'Unknown error');
                }
            }

            // Send to forwarder portal (always enabled)
            Log::info('Attempting to send to forwarder portal', ['instruction_id' => $instructionId]);
            $portalResult = $this->sendToForwarderPortalFixed($instruction);
            $results['forwarder_portal_sent'] = $portalResult['success'];
            $results['details']['portal'] = $portalResult;
            
            if (!$portalResult['success']) {
                $results['errors'][] = 'Forwarder Portal: ' . ($portalResult['error'] ?? 'Unknown error');
            }

            // Update instruction status
            $instruction['status'] = 'sent';
            $instruction['sent_at'] = now()->toISOString();
            $instruction['notification_sent'] = true;
            $instruction['notification_results'] = $results;
            $instruction['last_notification_attempt'] = now()->toISOString();

            // Update cache with results
            cache()->put("workflow_instruction_{$instructionId}", $instruction, now()->addDays(30));
            
            $allInstructions = cache()->get('all_workflow_instructions', []);
            $allInstructions[$instructionId] = $instruction;
            cache()->put('all_workflow_instructions', $allInstructions, now()->addDays(30));

            $successCount = ($results['email_sent'] ? 1 : 0) + ($results['whatsapp_sent'] ? 1 : 0) + ($results['forwarder_portal_sent'] ? 1 : 0);
            
            Log::info('Notification sending completed with WhatsApp', [
                'instruction_id' => $instructionId,
                'success_count' => $successCount,
                'total_errors' => count($results['errors']),
                'results' => $results
            ]);

            return response()->json([
                'success' => true,
                'message' => "Notifications sent successfully ({$successCount}/3 channels)" . 
                            ($results['whatsapp_sent'] ? " including WhatsApp" : ""),
                'results' => $results,
                'instruction_id' => $instructionId,
                'timestamp' => now()->toISOString()
            ]);

        } catch (\Exception $e) {
            Log::error('Critical error in sendContainerBookingRequest with WhatsApp', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'instruction_id' => $request->instruction_id ?? 'unknown',
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'System error: ' . $e->getMessage(),
                'instruction_id' => $request->instruction_id ?? 'unknown'
            ]);
        }
    }

    /**
     * FIXED: Send Email with PDF - Complete Implementation (unchanged)
     */
    private function sendEmailWithPDFFixed($instruction, $request)
    {
        try {
            Log::info('Starting email sending process', [
                'instruction_id' => $instruction['instruction_id'],
                'ref_invoice' => $instruction['ref_invoice'] ?? 'N/A'
            ]);

            // Get email recipients
            $primaryEmail = $instruction['notification_email'] ?? null;
            $ccEmails = $request->cc_emails ?? [];
            
            if (is_string($ccEmails)) {
                $ccEmails = json_decode($ccEmails, true) ?? [];
            }

            // Get forwarder data for complete email information
            $forwarderData = null;
            if ($instruction['forwarder_code']) {
                $forwarder = Forwarder::where('code', $instruction['forwarder_code'])->first();
                if ($forwarder) {
                    $forwarderData = $this->buildCompleteForwarderData($forwarder);
                    
                    // Use forwarder emails if not provided in instruction
                    if (!$primaryEmail && $forwarderData) {
                        $primaryEmail = $forwarderData['primary_email'];
                    }
                    if (empty($ccEmails) && $forwarderData) {
                        $ccEmails = $forwarderData['cc_emails'] ?? [];
                    }
                }
            }

            if (!$primaryEmail) {
                return [
                    'success' => false,
                    'error' => 'No primary email address available',
                    'forwarder_code' => $instruction['forwarder_code'] ?? 'unknown',
                    'forwarder_data_available' => $forwarderData ? true : false
                ];
            }

            // Validate email addresses
            $primaryEmail = trim($primaryEmail);
            if (!filter_var($primaryEmail, FILTER_VALIDATE_EMAIL)) {
                return [
                    'success' => false,
                    'error' => 'Invalid primary email address: ' . $primaryEmail
                ];
            }

            // Clean CC emails
            $ccEmails = array_filter($ccEmails, function($email) {
                return filter_var(trim($email), FILTER_VALIDATE_EMAIL);
            });
            $ccEmails = array_map('trim', $ccEmails);

            // Verify PDF file exists
            $pdfFilename = $instruction['pdf_filename'] ?? null;
            if (!$pdfFilename) {
                return [
                    'success' => false,
                    'error' => 'No PDF filename in instruction data'
                ];
            }

            // Check both possible PDF paths
            $pdfPaths = [
                storage_path('app/public/shipping_instructions/' . $pdfFilename),
                storage_path('app/public/kmi_shipping_instructions/' . $pdfFilename)
            ];

            $pdfPath = null;
            foreach ($pdfPaths as $path) {
                if (file_exists($path)) {
                    $pdfPath = $path;
                    break;
                }
            }

            if (!$pdfPath) {
                return [
                    'success' => false,
                    'error' => 'PDF file not found at any expected location',
                    'tried_paths' => $pdfPaths
                ];
            }

            // Company information
            $companyInfo = [
                'name' => 'PT. KAYU MEBEL INDONESIA',
                'email' => 'exim_3@pawindo.com',
                'phone' => '+62-31-8971234',
                'pic' => 'EKA WIJAYA',
                'address' => 'Jl. Industri Raya, Sidoarjo, Jawa Timur'
            ];

            // Get forwarder object
            $forwarder = null;
            if ($instruction['forwarder_code']) {
                $forwarder = Forwarder::where('code', $instruction['forwarder_code'])->first();
            }

            if (!$forwarder) {
                $forwarder = (object) [
                    'name' => $instruction['forwarder_name'] ?? 'Unknown Forwarder',
                    'code' => $instruction['forwarder_code'] ?? 'UNKNOWN'
                ];
            }

            // Prepare mail data using the correct structure
            $mailData = [
                'instruction' => $instruction,
                'forwarder' => $forwarder,
                'company' => $companyInfo,
                'pdf_path' => $pdfPath,
                'pdf_filename' => $pdfFilename
            ];

            // Check if SMTP is configured
            $smtpHost = config('mail.mailers.smtp.host');
            $smtpConfigured = !empty($smtpHost);

            Log::info('Email configuration check', [
                'smtp_configured' => $smtpConfigured,
                'smtp_host' => $smtpHost,
                'mail_driver' => config('mail.default'),
                'primary_email' => $primaryEmail,
                'cc_count' => count($ccEmails)
            ]);

            if (!$smtpConfigured) {
                // Development mode - log email details
                Log::info('DEVELOPMENT MODE: Email details logged', [
                    'instruction_id' => $instruction['instruction_id'],
                    'primary_email' => $primaryEmail,
                    'cc_emails' => $ccEmails,
                    'pdf_path' => $pdfPath,
                    'pdf_size' => file_exists($pdfPath) ? filesize($pdfPath) : 0
                ]);

                return [
                    'success' => true,
                    'sent_to' => [$primaryEmail],
                    'cc_emails' => $ccEmails,
                    'count' => 1 + count($ccEmails),
                    'pdf_attached' => true,
                    'note' => 'Email logged (SMTP not configured - development mode)',
                    'smtp_configured' => false,
                    'development_mode' => true
                ];
            }

            // Send actual email
            try {
                Mail::to($primaryEmail)
                    ->cc($ccEmails)
                    ->send(new ShippingInstructionMail($mailData, 'forwarder'));

                $sentToEmails = array_merge([$primaryEmail], $ccEmails);

                Log::info('Email sent successfully', [
                    'instruction_id' => $instruction['instruction_id'],
                    'sent_to' => $sentToEmails,
                    'total_recipients' => count($sentToEmails),
                    'pdf_attached' => true,
                    'pdf_size' => filesize($pdfPath)
                ]);

                return [
                    'success' => true,
                    'sent_to' => $sentToEmails,
                    'count' => count($sentToEmails),
                    'pdf_attached' => true,
                    'pdf_size' => $this->formatFileSize(filesize($pdfPath)),
                    'smtp_configured' => true,
                    'timestamp' => now()->toISOString()
                ];

            } catch (\Exception $mailError) {
                Log::error('Mail sending failed', [
                    'error' => $mailError->getMessage(),
                    'instruction_id' => $instruction['instruction_id'],
                    'primary_email' => $primaryEmail,
                    'cc_emails' => $ccEmails,
                    'pdf_path' => $pdfPath
                ]);

                return [
                    'success' => false,
                    'error' => 'Mail delivery failed: ' . $mailError->getMessage(),
                    'smtp_error' => true,
                    'primary_email' => $primaryEmail,
                    'cc_emails' => $ccEmails,
                    'mail_error_details' => $mailError->getMessage()
                ];
            }

        } catch (\Exception $e) {
            Log::error('Email system error', [
                'instruction_id' => $instruction['instruction_id'] ?? 'unknown',
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'error' => 'Email system error: ' . $e->getMessage(),
                'system_error' => true
            ];
        }
    }

    /**
     * ENHANCED: Send WhatsApp notification using WABLAS API
     */
    private function sendWablasWhatsAppNotification($instruction, $request)
    {
        try {
            Log::info('Starting WABLAS WhatsApp notification process', [
                'instruction_id' => $instruction['instruction_id']
            ]);

            // Get WhatsApp numbers from forwarder data
            $whatsappNumbers = $this->getForwarderWhatsAppNumbers($instruction);
            
            if (empty($whatsappNumbers)) {
                Log::warning('No WhatsApp numbers found for instruction', [
                    'instruction_id' => $instruction['instruction_id'],
                    'forwarder_code' => $instruction['forwarder_code'] ?? 'unknown'
                ]);
                
                return [
                    'success' => false,
                    'error' => 'No WhatsApp numbers available for this forwarder',
                    'forwarder_code' => $instruction['forwarder_code'] ?? 'unknown'
                ];
            }

            // Build WhatsApp message
            $message = $this->buildWablasWhatsAppMessage($instruction);
            
          // WABLAS API configuration
$wablasApiUrl = env('WABLAS_API_URL');
$wablasApiKey = env('WABLAS_API_KEY');
$wablasSecretKey = env('WABLAS_SECRET_KEY');

if (!$wablasApiUrl || !$wablasApiKey || !$wablasSecretKey) {
    Log::error('WABLAS API configuration missing', [
        'api_url' => $wablasApiUrl ? 'configured' : 'missing',
        'api_key' => $wablasApiKey ? 'configured' : 'missing',
        'secret_key' => $wablasSecretKey ? 'configured' : 'missing'
    ]);
    
    return [
        'success' => false,
        'error' => 'WABLAS API configuration is incomplete'
    ];
}

            $sentNumbers = [];
            $failedNumbers = [];

            // Send to each WhatsApp number
            foreach ($whatsappNumbers as $number) {
                try {
                    // Clean and format phone number for WABLAS
                    $cleanNumber = $this->formatPhoneNumberForWablas($number);
                    
                    if (!$cleanNumber) {
                        $failedNumbers[] = [
                            'number' => $number,
                            'error' => 'Invalid phone number format'
                        ];
                        continue;
                    }

                    Log::info('Sending WABLAS WhatsApp message', [
                        'instruction_id' => $instruction['instruction_id'],
                        'number' => $cleanNumber,
                        'message_length' => strlen($message)
                    ]);

  // Send via WABLAS API
$requestData = [
    'phone' => $cleanNumber,
    'message' => $message,
    'secret' => (string) $wablasSecretKey,  // Cast ke string
    'isGroup' => false
];

Log::info('WABLAS Request Data', [
    'instruction_id' => $instruction['instruction_id'],
    'url' => $wablasApiUrl . '/api/send-message',
    'phone' => $cleanNumber,
    'secret_length' => strlen($wablasSecretKey),
    'api_key_length' => strlen($wablasApiKey),
    'message_length' => strlen($message)
]);

$response = Http::withHeaders([
    'Authorization' => $wablasApiKey,
    'Content-Type' => 'application/json'
])->timeout(30)->post($wablasApiUrl . '/api/send-message', $requestData);

                    if ($response->successful()) {
                        $responseData = $response->json();
                        
                        if (isset($responseData['status']) && $responseData['status'] === true) {
                            $sentNumbers[] = $cleanNumber;
                            
                            Log::info('WABLAS WhatsApp message sent successfully', [
                                'instruction_id' => $instruction['instruction_id'],
                                'number' => $cleanNumber,
                                'message_id' => $responseData['data']['id'] ?? 'unknown',
                                'status' => $responseData['data']['status'] ?? 'sent'
                            ]);
                        } else {
                            $errorMsg = $responseData['message'] ?? 'Unknown WABLAS error';
                            $failedNumbers[] = [
                                'number' => $number,
                                'error' => $errorMsg
                            ];
                            
                            Log::warning('WABLAS API returned error', [
                                'instruction_id' => $instruction['instruction_id'],
                                'number' => $cleanNumber,
                                'error' => $errorMsg
                            ]);
                        }
                    } else {
                        $errorMsg = 'HTTP ' . $response->status() . ': ' . $response->body();
                        $failedNumbers[] = [
                            'number' => $number,
                            'error' => $errorMsg
                        ];
                        
                        Log::error('WABLAS API HTTP error', [
                            'instruction_id' => $instruction['instruction_id'],
                            'number' => $cleanNumber,
                            'http_status' => $response->status(),
                            'response_body' => $response->body()
                        ]);
                    }

                    // Rate limiting - wait between messages
                    usleep(500000); // 0.5 second delay between messages

                } catch (\Exception $e) {
                    $failedNumbers[] = [
                        'number' => $number,
                        'error' => $e->getMessage()
                    ];
                    
                    Log::error('Exception sending WABLAS WhatsApp', [
                        'instruction_id' => $instruction['instruction_id'],
                        'number' => $number,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            $totalSent = count($sentNumbers);
            $totalFailed = count($failedNumbers);
            $isSuccess = $totalSent > 0;

            Log::info('WABLAS WhatsApp notification completed', [
                'instruction_id' => $instruction['instruction_id'],
                'sent_count' => $totalSent,
                'failed_count' => $totalFailed,
                'success' => $isSuccess
            ]);

            return [
                'success' => $isSuccess,
                'sent_to' => $sentNumbers,
                'failed' => $failedNumbers,
                'count' => $totalSent,
                'total_attempted' => count($whatsappNumbers),
                'message_length' => strlen($message),
                'api_provider' => 'WABLAS',
                'timestamp' => now()->toISOString()
            ];

        } catch (\Exception $e) {
            Log::error('WABLAS WhatsApp notification system error', [
                'instruction_id' => $instruction['instruction_id'] ?? 'unknown',
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'error' => 'WhatsApp system error: ' . $e->getMessage()
            ];
        }
    }

    /**
     * ENHANCED: Get WhatsApp numbers from forwarder data
     */
    private function getForwarderWhatsAppNumbers($instruction)
    {
        $whatsappNumbers = [];

        // Try to get from forwarder database
        if ($instruction['forwarder_code']) {
            $forwarder = Forwarder::where('code', $instruction['forwarder_code'])->first();
            if ($forwarder) {
                $forwarderData = $this->buildCompleteForwarderData($forwarder);
                if ($forwarderData && !empty($forwarderData['all_whatsapp'])) {
                    $whatsappNumbers = $forwarderData['all_whatsapp'];
                    
                    Log::info('WhatsApp numbers retrieved from forwarder database', [
                        'instruction_id' => $instruction['instruction_id'],
                        'forwarder_code' => $instruction['forwarder_code'],
                        'numbers_count' => count($whatsappNumbers)
                    ]);
                }
            }
        }

        // Filter valid numbers only
        $validNumbers = array_filter($whatsappNumbers, function($number) {
            return $this->isValidWhatsAppNumber($number);
        });

        return array_values($validNumbers);
    }

    /**
     * ENHANCED: Format phone number for WABLAS API
     */
    private function formatPhoneNumberForWablas($phoneNumber)
    {
        if (!$phoneNumber) {
            return null;
        }

        // Remove all non-numeric characters except +
        $cleaned = preg_replace('/[^0-9+]/', '', trim($phoneNumber));
        
        // Handle Indonesian numbers
        if (preg_match('/^(\+62|62|0)/', $cleaned)) {
            // Remove leading 0, +62, or 62
            $cleaned = preg_replace('/^(\+62|62|0)/', '', $cleaned);
            // Add country code
            $cleaned = '62' . $cleaned;
        } elseif (preg_match('/^\+/', $cleaned)) {
            // Already has country code
            $cleaned = substr($cleaned, 1); // Remove +
        } elseif (preg_match('/^8/', $cleaned)) {
            // Indonesian mobile without prefix
            $cleaned = '62' . $cleaned;
        }

        // Validate final format (should be 10-15 digits)
        if (preg_match('/^\d{10,15}$/', $cleaned)) {
            return $cleaned;
        }

        Log::warning('Invalid phone number format for WABLAS', [
            'original' => $phoneNumber,
            'cleaned' => $cleaned
        ]);

        return null;
    }

    /**
     * ENHANCED: Validate WhatsApp number
     */
    private function isValidWhatsAppNumber($number)
    {
        if (!$number || !is_string($number)) {
            return false;
        }

        $cleaned = preg_replace('/[^0-9+]/', '', trim($number));
        
        // Must be at least 10 digits, max 15 digits
        return preg_match('/^(\+?[\d]{10,15})$/', $cleaned);
    }

    /**
     * ENHANCED: Build WhatsApp message for WABLAS
     */
    private function buildWablasWhatsAppMessage($instruction)
    {
        $message = "*SHIPPING INSTRUCTION NOTIFICATION*\n\n";
        $message .= "*Instruction ID:* {$instruction['instruction_id']}\n";
        $message .= "*Reference Invoice:* " . ($instruction['ref_invoice'] ?? 'N/A') . "\n";
        $message .= "*Forwarder:* " . ($instruction['forwarder_name'] ?? 'N/A') . "\n";
        $message .= "*Pickup Date:* " . ($instruction['expected_pickup_date'] ?? 'TBD') . "\n";
        $message .= "*Volume:* " . number_format($instruction['total_volume'] ?? 0, 2) . " CBM\n";
        $message .= "*Weight:* " . number_format($instruction['total_weight'] ?? 0, 0) . " KG\n";
        $message .= "*Container:* " . ($instruction['container_type'] ?? 'TBD') . "\n";
        $message .= "*Priority:* " . strtoupper($instruction['priority'] ?? 'NORMAL') . "\n\n";
        
        if ($instruction['is_combined'] ?? false) {
            $message .= "*COMBINED SHIPMENT* - Multiple invoices grouped\n\n";
        }
        
        $message .= "*Please check your email for complete PDF instruction*\n\n";
        $message .= "*Access Forwarder Portal:*\n" . config('app.url') . "/forwarder/dashboard\n\n";
        $message .= "*Contact:* " . ($instruction['contact_person'] ?? 'EKA WIJAYA') . "\n";
        $message .= "*Email:* " . ($instruction['sent_by_email'] ?? 'exim_3@pawindo.com') . "\n\n";
        $message .= "*PT. KAYU MEBEL INDONESIA*\n";
        $message .= "Export Department - Shipping Instruction";

        return $message;
    }

    /**
     * FIXED: Send to Forwarder Portal (unchanged)
     */
    private function sendToForwarderPortalFixed($instruction)
    {
        try {
            Log::info('Sending instruction to forwarder portal', [
                'instruction_id' => $instruction['instruction_id'],
                'forwarder_code' => $instruction['forwarder_code']
            ]);

            // Update instruction for portal access
            $instruction['forwarder_notification_sent'] = true;
            $instruction['forwarder_notification_sent_at'] = now()->toISOString();
            $instruction['portal_status'] = 'delivered';
            $instruction['portal_access_url'] = config('app.url') . '/forwarder/dashboard';

            // Update main instruction cache
            cache()->put("workflow_instruction_{$instruction['instruction_id']}", $instruction, now()->addDays(30));

            // Add to forwarder-specific cache for quick dashboard access
            $forwarderCode = $instruction['forwarder_code'];
            if ($forwarderCode) {
                $forwarderInstructionsKey = "forwarder_instructions_{$forwarderCode}";
                $forwarderInstructions = cache()->get($forwarderInstructionsKey, []);
                $forwarderInstructions[$instruction['instruction_id']] = $instruction;
                cache()->put($forwarderInstructionsKey, $forwarderInstructions, now()->addDays(30));

                Log::info('Instruction cached for forwarder dashboard', [
                    'instruction_id' => $instruction['instruction_id'],
                    'forwarder_code' => $forwarderCode,
                    'cache_key' => $forwarderInstructionsKey
                ]);
            }

            Log::info('Instruction successfully delivered to forwarder portal', [
                'instruction_id' => $instruction['instruction_id'],
                'forwarder_code' => $forwarderCode,
                'pdf_available' => $instruction['pdf_generated'] ?? false,
                'portal_url' => $instruction['portal_access_url']
            ]);

            return [
                'success' => true,
                'forwarder_code' => $forwarderCode,
                'sent_at' => $instruction['forwarder_notification_sent_at'],
                'portal_url' => $instruction['portal_access_url'],
                'pdf_available' => $instruction['pdf_generated'] ?? false,
                'instruction_cached' => true,
                'timestamp' => now()->toISOString()
            ];

        } catch (\Exception $e) {
            Log::error('Failed to send to forwarder portal', [
                'instruction_id' => $instruction['instruction_id'] ?? 'unknown',
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'error' => 'Portal delivery failed: ' . $e->getMessage()
            ];
        }
    }

    /**
     * View PDF
     */
    public function viewPDF($filename)
    {
        try {
            $filename = basename($filename);
            $paths = [
                storage_path('app/public/shipping_instructions/' . $filename),
                storage_path('app/public/kmi_shipping_instructions/' . $filename)
            ];
            
            $path = null;
            foreach ($paths as $p) {
                if (file_exists($p)) {
                    $path = $p;
                    break;
                }
            }
            
            if (!$path) {
                Log::error('PDF not found for viewing', [
                    'filename' => $filename,
                    'tried_paths' => $paths,
                    'user' => auth()->user()->email
                ]);
                abort(404, 'PDF file not found');
            }
            
            Log::info('PDF viewed', [
                'filename' => $filename,
                'path' => $path,
                'user' => auth()->user()->email
            ]);
            
            return response()->file($path, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="' . $filename . '"'
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error viewing PDF', [
                'filename' => $filename,
                'error' => $e->getMessage()
            ]);
            abort(500, 'Error viewing PDF');
        }
    }

    /**
     * Download PDF
     */
    public function downloadPDF($filename)
    {
        try {
            $filename = basename($filename);
            $paths = [
                storage_path('app/public/shipping_instructions/' . $filename),
                storage_path('app/public/kmi_shipping_instructions/' . $filename)
            ];
            
            $path = null;
            foreach ($paths as $p) {
                if (file_exists($p)) {
                    $path = $p;
                    break;
                }
            }
            
            if (!$path) {
                abort(404, 'PDF file not found');
            }
            
            return response()->download($path, $filename, [
                'Content-Type' => 'application/pdf'
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error downloading PDF', [
                'filename' => $filename,
                'error' => $e->getMessage()
            ]);
            abort(500, 'Error downloading PDF');
        }
    }

    // ================================
    // HELPER METHODS (unchanged)
    // ================================

    /**
     * Build complete forwarder data - ENHANCED for WhatsApp
     */
    private function buildCompleteForwarderData($forwarder)
    {
        if (!$forwarder) {
            return null;
        }

        try {
            // Parse JSON arrays safely
            $emails = $this->parseJsonField($forwarder->emails);
            $whatsappNumbers = $this->parseJsonField($forwarder->whatsapp_numbers);

            // Determine primary contacts
            $primaryEmail = $forwarder->primary_email ?: ($emails[0] ?? null);
            $primaryWhatsApp = $forwarder->primary_whatsapp ?: ($whatsappNumbers[0] ?? null);

            // Build CC emails and secondary WhatsApp
            $ccEmails = array_values(array_filter($emails, fn($email) => $email !== $primaryEmail));
            $secondaryWhatsApp = array_values(array_filter($whatsappNumbers, fn($number) => $number !== $primaryWhatsApp));

            return [
                'code' => $forwarder->code,
                'name' => $forwarder->name,
                'contact_person' => $forwarder->contact_person,
                'phone' => $forwarder->phone,
                'address' => $forwarder->address,
                
                'primary_email' => $primaryEmail,
                'all_emails' => $emails,
                'cc_emails' => $ccEmails,
                
                'primary_whatsapp' => $primaryWhatsApp,
                'all_whatsapp' => $whatsappNumbers, // ENHANCED: Ensure WhatsApp numbers available
                'secondary_whatsapp' => $secondaryWhatsApp,
                
                'email_notifications_enabled' => $forwarder->email_notifications_enabled ?? true,
                'whatsapp_notifications_enabled' => $forwarder->whatsapp_notifications_enabled ?? true, // ENHANCED: Default to true
                'is_active' => $forwarder->is_active ?? true
            ];

        } catch (\Exception $e) {
            Log::error('Error building forwarder data with WhatsApp enhancement', [
                'forwarder_code' => $forwarder->code ?? 'unknown',
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Parse JSON field safely
     */
    private function parseJsonField($field)
    {
        if (is_array($field)) {
            return $field;
        }
        
        if (is_string($field) && !empty($field)) {
            try {
                $parsed = json_decode($field, true);
                return is_array($parsed) ? $parsed : [];
            } catch (\Exception $e) {
                Log::warning('Failed to parse JSON field', ['field' => $field]);
                return [];
            }
        }
        
        return [];
    }

    /**
     * Create PDF File - Enhanced
     */
    private function createPDFFile($instructionData)
    {
        try {
            // Ensure directory exists
            $pdfDir = storage_path('app/public/shipping_instructions');
            if (!is_dir($pdfDir)) {
                mkdir($pdfDir, 0755, true);
            }

            // Generate filename
            $safeInstructionId = preg_replace('/[^A-Za-z0-9_\-]/', '_', $instructionData['instruction_id']);
            $filename = 'shipping_instruction_' . $safeInstructionId . '_' . date('Ymd_His') . '.pdf';
            $filePath = $pdfDir . '/' . $filename;

            // Prepare data for PDF template
            $pdfData = [
                'instruction_id' => $instructionData['instruction_id'],
                'ref_invoice' => $instructionData['ref_invoice'],
                'is_combined' => $instructionData['is_combined'],
                'sub_invoices' => $instructionData['sub_invoices'],
                'forwarder_name' => $instructionData['forwarder_name'],
                'generated_at' => Carbon::parse($instructionData['generated_at']),
                'generated_by' => $instructionData['generated_by'],
                'export_data' => $instructionData['export_data'],
                'total_volume' => $instructionData['total_volume'],
                'total_weight' => $instructionData['total_weight'],
                'total_quantity' => $instructionData['total_quantity'],
                'pickup_location' => $instructionData['pickup_location'],
                'expected_pickup_date' => $instructionData['expected_pickup_date'],
                'container_type' => $instructionData['container_type'],
                'port_loading' => $instructionData['port_loading'],
                'port_destination' => $instructionData['port_destination'],
                'contact_person' => $instructionData['contact_person'],
                'freight_payment' => $instructionData['freight_payment'],
                'special_instructions' => $instructionData['special_instructions'],
                'priority' => $instructionData['priority']
            ];

            // Create PDF using the template
            $pdf = Pdf::loadView('pdf.shipping_instruction_kmi', $pdfData);
            $pdf->setPaper('A4', 'portrait');
            $pdf->save($filePath);

            // Verify file was created
            if (!file_exists($filePath)) {
                throw new \Exception("PDF file was not created");
            }

            $fileSize = filesize($filePath);

            return [
                'success' => true,
                'filename' => $filename,
                'path' => 'shipping_instructions/' . $filename,
                'url' => route('pdf.view', ['filename' => $filename]),
                'size' => $fileSize,
                'full_path' => $filePath
            ];

        } catch (\Exception $e) {
            Log::error('PDF creation error', [
                'error' => $e->getMessage(),
                'instruction_id' => $instructionData['instruction_id'] ?? 'unknown'
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Extract 3-digit prefix from invoice
     */
    private function extractStrictThreeDigitPrefix($refInvoice)
    {
        if (!$refInvoice) return null;
        
        if (preg_match('/^(\d{3})/', $refInvoice, $matches)) {
            return $matches[1];
        }
        
        return null;
    }

    /**
     * Build sub-invoices data for combined invoices
     */
    private function buildSubInvoicesData($exportData)
    {
        $grouped = $exportData->groupBy('reference_invoice');
        $subInvoices = [];

        foreach ($grouped as $refInvoice => $items) {
            $subInvoices[$refInvoice] = [
                'ref_invoice' => $refInvoice,
                'items' => $items,
                'item_count' => $items->count(),
                'total_volume' => $items->sum('volume'),
                'total_weight' => $items->sum('weight'),
                'total_quantity' => $items->sum('quantity'),
                'buyers' => $items->pluck('buyer')->unique()->values()->toArray(),
                'primary_buyer' => $items->first()->buyer
            ];
        }

        return $subInvoices;
    }

    /**
     * Calculate simple statistics
     */
    private function calculateSimpleStatistics($exportData)
    {
        $surabayaData = $exportData->where('delivery_type', 'ZDO1');
        $semarangData = $exportData->where('delivery_type', 'ZDO2');

        return [
            'surabaya_stats' => [
                'total_records' => $surabayaData->count(),
                'total_volume' => round($surabayaData->sum('volume') ?? 0, 2),
                'total_weight' => round($surabayaData->sum('weight') ?? 0, 2),
                'unique_buyers' => $surabayaData->pluck('buyer')->unique()->count(),
                'unique_ref_invoices' => $surabayaData->pluck('reference_invoice')->unique()->count()
            ],
            'semarang_stats' => [
                'total_records' => $semarangData->count(),
                'total_volume' => round($semarangData->sum('volume') ?? 0, 2),
                'total_weight' => round($semarangData->sum('weight') ?? 0, 2),
                'unique_buyers' => $semarangData->pluck('buyer')->unique()->count(),
                'unique_ref_invoices' => $semarangData->pluck('reference_invoice')->unique()->count()
            ],
            'total_stats' => [
                'total_records' => $exportData->count(),
                'total_volume' => round($exportData->sum('volume') ?? 0, 2),
                'total_weight' => round($exportData->sum('weight') ?? 0, 2),
                'unique_buyers' => $exportData->pluck('buyer')->unique()->count(),
                'unique_ref_invoices' => $exportData->pluck('reference_invoice')->unique()->count()
            ]
        ];
    }

    /**
     * Get default statistics structure
     */
    private function getDefaultStatistics()
    {
        return [
            'surabaya_stats' => [
                'total_records' => 0,
                'total_volume' => 0,
                'total_weight' => 0,
                'unique_buyers' => 0,
                'unique_ref_invoices' => 0
            ],
            'semarang_stats' => [
                'total_records' => 0,
                'total_volume' => 0,
                'total_weight' => 0,
                'unique_buyers' => 0,
                'unique_ref_invoices' => 0
            ],
            'total_stats' => [
                'total_records' => 0,
                'total_volume' => 0,
                'total_weight' => 0,
                'unique_buyers' => 0,
                'unique_ref_invoices' => 0
            ]
        ];
    }

    /**
     * Format file size
     */
    private function formatFileSize($bytes)
    {
        if (!$bytes || $bytes <= 0) {
            return '0 B';
        }

        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        try {
            $base = log($bytes, 1024);
            $index = min(floor($base), count($units) - 1);
            $size = pow(1024, $base - $index);
            
            return round($size, 2) . ' ' . $units[$index];
        } catch (\Exception $e) {
            return $bytes . ' B';
        }
    }
}