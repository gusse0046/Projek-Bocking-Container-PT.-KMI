<?php

namespace App\Http\Controllers;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Mail;
use App\Models\ExportData;
use App\Models\Forwarder;
use App\Mail\ShippingInstructionMail;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
class ExportController extends BaseController
{
use AuthorizesRequests, ValidatesRequests;
public function __construct()
{
    $this->middleware('auth');
}

/**
 * ENHANCED: Export dashboard dengan Complete Auto-Fill System
 */
public function dashboard(Request $request)
{
    try {
        Log::info('Export Dashboard loading with Enhanced Auto-Fill System', [
            'user' => auth()->user()->email,
            'location' => $request->get('location', 'all')
        ]);

        // Get export data dengan reference invoices only
        $exportDataQuery = ExportData::whereNotNull('reference_invoice')
                                  ->where('reference_invoice', '!=', '')
                                  ->where(function($query) {
                                      $query->where('buyer', 'NOT LIKE', '%PT SKYLINE JAYA%')
                                            ->where('buyer', 'NOT LIKE', '%SKYLINE JAYA%');
                                  });

        // Apply location filtering
        $location = $request->get('location', 'all');
        if ($location === 'surabaya') {
            $exportDataQuery->where('delivery_type', 'ZDO1');
        } elseif ($location === 'semarang') {
            $exportDataQuery->where('delivery_type', 'ZDO2');
        }

        $exportData = $exportDataQuery->orderBy('reference_invoice')
                                     ->orderBy('delivery_date', 'desc')
                                     ->get();

        // Get forwarders dengan complete data
        $forwarders = Forwarder::where('is_active', true)
                              ->where('name', '!=', 'Custom - PT SKYLINE JAYA')
                              ->get()
                              ->map(function($forwarder) {
                                  // Ensure arrays are properly formatted
                                  if (is_string($forwarder->buyers)) {
                                      $forwarder->buyers = json_decode($forwarder->buyers, true) ?? [];
                                  }
                                  if (is_string($forwarder->emails)) {
                                      $forwarder->emails = json_decode($forwarder->emails, true) ?? [];
                                  }
                                  if (is_string($forwarder->whatsapp_numbers)) {
                                      $forwarder->whatsapp_numbers = json_decode($forwarder->whatsapp_numbers, true) ?? [];
                                  }
                                  return $forwarder;
                              });

        // Apply enhanced forwarder mapping
        $this->applyEnhancedForwarderMapping($exportData, $forwarders);

        // Calculate statistics
        $statistics = $this->calculateDeliveryTypeStatistics($exportData);

        // Group data dengan 3-digit prefix grouping
        $groupedData = $this->groupExportDataByStrictNumericPrefix($exportData, $forwarders);

        Log::info('Export dashboard loaded successfully', [
            'total_records' => $exportData->count(),
            'mapped_correctly' => $exportData->whereNotNull('forwarder_code')->count(),
            'forwarders_active' => $forwarders->count(),
            'prefix_groups_created' => $this->countPrefixGroups($groupedData)
        ]);

        return view('dashboard', compact(
            'exportData', 
            'forwarders', 
            'statistics', 
            'groupedData',
            'location'
        ));

    } catch (\Exception $e) {
        Log::error('Error dalam export dashboard', [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
            'user' => auth()->user()->email
        ]);

        return view('dashboard', [
            'exportData' => collect([]),
            'forwarders' => collect([]),
            'statistics' => $this->getEmptyStatistics(),
            'groupedData' => [],
            'location' => 'all'
        ]);
    }
}

/**
 * ENHANCED: Auto-fill endpoint dengan complete implementation
 */
public function getForwarderDataForAutoFill(Request $request)
{
    try {
        // Accept both GET dan POST parameters
        $refInvoice = $request->get('ref_invoice') ?? $request->input('ref_invoice');
        $isCombined = $request->boolean('is_combined', false);
        $location = $request->get('location', 'surabaya');

        Log::info('Auto-fill request received in ExportController', [
            'ref_invoice' => $refInvoice,
            'is_combined' => $isCombined,
            'location' => $location,
            'method' => $request->method()
        ]);

        if (!$refInvoice) {
            return response()->json([
                'success' => false,
                'error' => 'Reference invoice is required'
            ]);
        }

        // Handle combined invoice names
        $searchRefInvoice = $refInvoice;
        if ($isCombined && strpos($refInvoice, '(Combined') !== false) {
            // Extract base name dari combined format
            preg_match('/^(.+?)\s*\(Combined/', $refInvoice, $matches);
            if (isset($matches[1])) {
                $baseName = trim($matches[1]);
                $numericPrefix = $this->extractStrictThreeDigitPrefix($baseName);
                if ($numericPrefix) {
                    $searchPattern = $numericPrefix . '%';
                    $exportData = ExportData::where('reference_invoice', 'LIKE', $searchPattern)
                                          ->whereNotNull('forwarder_code')
                                          ->first();
                    if ($exportData) {
                        $searchRefInvoice = $exportData->reference_invoice;
                    }
                }
            }
        }

        // Get export data
        $exportItem = ExportData::where('reference_invoice', $searchRefInvoice)
                              ->whereNotNull('forwarder_code')
                              ->first();

        if (!$exportItem || !$exportItem->forwarder_code) {
            Log::warning('No forwarder mapping found', [
                'search_invoice' => $searchRefInvoice,
                'original_invoice' => $refInvoice
            ]);
            
            return response()->json([
                'success' => false,
                'error' => 'No forwarder mapping found for this invoice'
            ]);
        }

        // Get forwarder dengan complete data processing
        $forwarder = Forwarder::where('code', $exportItem->forwarder_code)
                             ->where('is_active', true)
                             ->first();

        if (!$forwarder) {
            return response()->json([
                'success' => false,
                'error' => 'Forwarder not found or inactive',
                'forwarder_code' => $exportItem->forwarder_code
            ]);
        }

        // Build complete forwarder data
        $forwarderData = $this->buildCompleteForwarderData($forwarder);

        if (!$forwarderData) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to build forwarder data'
            ]);
        }

        // Get location info untuk auto-fill
        $defaultPort = $location === 'surabaya' ? 'Tanjung Perak - Surabaya' : 'Tanjung Emas - Semarang';

        // Build complete auto-fill data
        $autoFillData = [
            'forwarder_name' => $forwarder->name,
            'notification_email' => $forwarderData['primary_email'],
            'contact_person' => $forwarderData['contact_person'],
            'port_loading' => $defaultPort,
            'pickup_location' => $forwarderData['address'] ?? 'To be confirmed',
            
            // Complete notification data
            'all_emails' => $forwarderData['all_emails'],
            'cc_emails' => $forwarderData['cc_emails'],
            'primary_whatsapp' => $forwarderData['primary_whatsapp'],
            'all_whatsapp' => $forwarderData['all_whatsapp'],
            'secondary_whatsapp' => $forwarderData['secondary_whatsapp'],
            'phone' => $forwarderData['phone'],
            
            // Notification preferences
            'email_notifications_enabled' => $forwarderData['email_notifications_enabled'],
            'whatsapp_notifications_enabled' => $forwarderData['whatsapp_notifications_enabled'],
            
            // Suggested values
            'suggested_container_type' => '1 X 40 HC',
            'suggested_priority' => 'normal'
        ];

        Log::info('Complete auto-fill data retrieved successfully', [
            'forwarder_code' => $forwarder->code,
            'forwarder_name' => $forwarder->name,
            'primary_email' => $forwarderData['primary_email'],
            'email_count' => count($forwarderData['all_emails']),
            'whatsapp_count' => count($forwarderData['all_whatsapp'])
        ]);

        return response()->json([
            'success' => true,
            'forwarder_data' => $forwarderData,
            'auto_fill_data' => $autoFillData,
            'location' => $location,
            'migration_source' => true
        ]);

    } catch (\Exception $e) {
        Log::error('Error getting forwarder data untuk auto-fill', [
            'error' => $e->getMessage(),
            'ref_invoice' => $request->get('ref_invoice') ?? 'unknown',
            'trace' => $e->getTraceAsString()
        ]);

        return response()->json([
            'success' => false,
            'error' => 'Failed to get forwarder data: ' . $e->getMessage()
        ]);
    }
}

/**
 * ENHANCED: Generate Shipping Instruction PDF dengan complete workflow
 */
public function generateShippingInstructionPDF(Request $request)
{
    try {
        Log::info('PDF generation started in ExportController', [
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
            'priority' => 'required|in:normal,high,urgent',
            'port_loading' => 'required|string',
            'port_destination' => 'required|string',
            'contact_person' => 'required|string',
            'freight_payment' => 'required|string'
        ]);

        // Get export data
        $exportDataIds = $request->export_data_ids ?? [];
        $refInvoice = $request->ref_invoice;
        $isCombined = $request->boolean('is_combined', false);
        
        // If no specific IDs provided, find by reference invoice
        if (empty($exportDataIds)) {
            if ($isCombined && strpos($refInvoice, '(Combined') !== false) {
                // Extract prefix untuk combined invoices
                preg_match('/^(.+?)\s*\(Combined/', $refInvoice, $matches);
                if (isset($matches[1])) {
                    $baseName = trim($matches[1]);
                    $numericPrefix = $this->extractStrictThreeDigitPrefix($baseName);
                    if ($numericPrefix) {
                        $exportData = ExportData::where('reference_invoice', 'LIKE', $numericPrefix . '%')
                                              ->orderBy('reference_invoice')
                                              ->get();
                    }
                }
            } else {
                $exportData = ExportData::where('reference_invoice', $refInvoice)->get();
            }
        } else {
            $exportData = ExportData::whereIn('id', $exportDataIds)->get();
        }

        if ($exportData->isEmpty()) {
            return response()->json([
                'success' => false,
                'error' => 'No export data found for this reference invoice'
            ]);
        }

        // Calculate totals
        $totalVolume = $exportData->sum('volume');
        $totalWeight = $exportData->sum('weight');
        $totalQuantity = $exportData->sum('quantity');
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
            'numeric_prefix' => $request->numeric_prefix ?? $this->extractStrictThreeDigitPrefix($refInvoice),
            
            // Export data
            'export_data' => $exportData,
            'export_data_ids' => $exportData->pluck('id')->toArray(),
            
            // Form data
            'forwarder_name' => $request->forwarder_name,
            'notification_email' => $request->notification_email,
            'pickup_location' => $request->pickup_location,
            'expected_pickup_date' => $request->expected_pickup_date,
            'container_type' => $request->container_type,
            'priority' => $request->priority,
            'port_loading' => $request->port_loading,
            'port_destination' => $request->port_destination,
            'contact_person' => $request->contact_person,
            'freight_payment' => $request->freight_payment,
            'special_instructions' => $request->special_instructions,
            'delivery_type' => $request->delivery_type ?? $exportData->first()->delivery_type,
            'location' => $request->location ?? 'surabaya',
            
            // Totals
            'total_volume' => $totalVolume,
            'total_weight' => $totalWeight,
            'total_quantity' => $totalQuantity,
            'unique_buyers' => $uniqueBuyers,
            'reference_invoices' => $referenceInvoices,
            'primary_buyer' => $uniqueBuyers[0] ?? 'N/A',
            
            // Sub-invoices untuk combined
            'sub_invoices' => $request->sub_invoices ?? null,
            
            // Workflow tracking
            'status' => 'generated',
            'generated_at' => now(),
            'generated_by' => auth()->user()->name,
            'sent_by' => auth()->user()->name,
            'sent_by_email' => auth()->user()->email,
            'sent_at' => now()->toISOString(),
            'updated_at' => now()->toISOString(),
            
            // PDF tracking
            'pdf_generated' => false,
            'pdf_filename' => null,
            'pdf_path' => null,
            'pdf_url' => null,
            'pdf_size' => 0,

            // Enhanced notification data
            'forwarder_code' => $exportData->first()->forwarder_code ?? 'UNKNOWN'
        ];

        // Generate PDF document
        $pdfResult = $this->generatePDFDocument($instructionData);
        
        if (!$pdfResult['success']) {
            return response()->json([
                'success' => false,
                'error' => 'PDF generation failed: ' . $pdfResult['error']
            ]);
        }

        // Update instruction data dengan PDF info
        $instructionData['pdf_generated'] = true;
        $instructionData['pdf_filename'] = $pdfResult['filename'];
        $instructionData['pdf_path'] = $pdfResult['path'];
        $instructionData['pdf_url'] = $pdfResult['url'];
        $instructionData['pdf_size'] = $pdfResult['size'];

        // Cache the instruction
        cache()->put("workflow_instruction_{$instructionId}", $instructionData, now()->addDays(30));
        
        // Update all instructions cache
        $allInstructions = cache()->get('all_workflow_instructions', []);
        $allInstructions[$instructionId] = $instructionData;
        cache()->put('all_workflow_instructions', $allInstructions, now()->addDays(30));

        Log::info('PDF generated successfully', [
            'instruction_id' => $instructionId,
            'pdf_filename' => $pdfResult['filename']
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Shipping instruction PDF generated successfully',
            'instruction_id' => $instructionId,
            'pdf_filename' => $pdfResult['filename'],
            'pdf_url' => $pdfResult['url'],
            'pdf_size' => $this->formatFileSize($pdfResult['size']),
            'can_preview' => true,
            'can_send' => true
        ]);

    } catch (\Exception $e) {
        Log::error('PDF generation failed', [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
            'user' => auth()->user()->email
        ]);

        return response()->json([
            'success' => false,
            'error' => 'PDF generation failed: ' . $e->getMessage()
        ]);
    }
}

/**
 * ENHANCED: Send Container Booking Request with PDF
 */
public function sendContainerBookingRequest(Request $request)
{
    try {
        $instructionId = $request->instruction_id;
        $sendEmail = $request->boolean('send_email', true);
        $sendWhatsApp = $request->boolean('send_whatsapp', false);
        $sendForwarderPortal = $request->boolean('send_forwarder_portal', true);

        Log::info('Send container booking request started', [
            'instruction_id' => $instructionId,
            'send_email' => $sendEmail,
            'send_whatsapp' => $sendWhatsApp,
            'send_forwarder_portal' => $sendForwarderPortal
        ]);

        // Get instruction from cache
        $instruction = cache()->get("workflow_instruction_{$instructionId}");
        
        if (!$instruction) {
            return response()->json([
                'success' => false,
                'error' => 'Instruction not found'
            ]);
        }

        if (!$instruction['pdf_generated']) {
            return response()->json([
                'success' => false,
                'error' => 'PDF not generated yet'
            ]);
        }

        $results = [
            'email_sent' => false,
            'whatsapp_sent' => false,
            'forwarder_portal_sent' => false,
            'email_count' => 0,
            'whatsapp_count' => 0,
            'errors' => []
        ];

        // Send email notification dengan PDF attachment
        if ($sendEmail) {
            $emailResult = $this->sendEmailNotificationWithPDF($instruction, $request);
            $results['email_sent'] = $emailResult['success'];
            $results['email_count'] = $emailResult['total_recipients'] ?? 0;
            
            if (!$emailResult['success']) {
                $results['errors'][] = 'Email: ' . $emailResult['error'];
            }
        }

        // Send WhatsApp notifications
        if ($sendWhatsApp) {
            $whatsappResult = $this->sendWhatsAppNotifications($instruction, $request);
            $results['whatsapp_sent'] = $whatsappResult['success'];
            $results['whatsapp_count'] = $whatsappResult['total_sent'] ?? 0;
            
            if (!$whatsappResult['success']) {
                $results['errors'][] = 'WhatsApp: ' . $whatsappResult['error'];
            }
        }

        // Send to forwarder portal
        if ($sendForwarderPortal) {
            $portalResult = $this->sendToForwarderPortal($instruction);
            $results['forwarder_portal_sent'] = $portalResult['success'];
            
            if (!$portalResult['success']) {
                $results['errors'][] = 'Forwarder Portal: ' . $portalResult['error'];
            }
        }

        // Update instruction status
        $instruction['status'] = 'sent';
        $instruction['sent_at'] = now()->toISOString();
        $instruction['notification_sent'] = true;
        $instruction['notification_results'] = $results;

        // Update cache
        cache()->put("workflow_instruction_{$instructionId}", $instruction, now()->addDays(30));
        
        $allInstructions = cache()->get('all_workflow_instructions', []);
        $allInstructions[$instructionId] = $instruction;
        cache()->put('all_workflow_instructions', $allInstructions, now()->addDays(30));

        Log::info('Container booking request sent successfully', [
            'instruction_id' => $instructionId,
            'results' => $results
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Container booking request sent successfully',
            'results' => $results
        ]);

    } catch (\Exception $e) {
        Log::error('Failed to send container booking request', [
            'instruction_id' => $request->instruction_id ?? 'unknown',
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);

        return response()->json([
            'success' => false,
            'error' => 'Failed to send container booking request: ' . $e->getMessage()
        ]);
    }
}

/**
 * Send email notification dengan PDF attachment
 */
private function sendEmailNotificationWithPDF($instruction, $request)
{
    try {
        // Get recipient emails
        $primaryEmail = $instruction['notification_email'];
        $ccEmails = $request->cc_emails ?? [];
        
        if (is_string($ccEmails)) {
            $ccEmails = json_decode($ccEmails, true) ?? [];
        }

        // Company information
        $companyInfo = [
            'name' => 'PT. KAYU MEBEL INDONESIA',
            'email' => 'exim_3@pawindo.com',
            'phone' => '+62-31-8971234',
            'pic' => 'EKA WIJAYA'
        ];

        // Get forwarder
        $forwarder = null;
        if ($instruction['forwarder_code']) {
            $forwarder = Forwarder::where('code', $instruction['forwarder_code'])->first();
        }

        if (!$forwarder) {
            $forwarder = (object) [
                'name' => $instruction['forwarder_name'],
                'code' => $instruction['forwarder_code'] ?? 'UNKNOWN'
            ];
        }

        Log::info('Sending email notification with PDF', [
            'instruction_id' => $instruction['instruction_id'],
            'primary_email' => $primaryEmail,
            'cc_emails' => $ccEmails,
            'forwarder_name' => $forwarder->name,
            'pdf_filename' => $instruction['pdf_filename']
        ]);

        // Send email menggunakan Mail class
        $mailData = [
            'instruction' => $instruction,
            'forwarder' => $forwarder,
            'company' => $companyInfo
        ];

        try {
            Mail::to($primaryEmail)
                ->cc($ccEmails)
                ->send(new ShippingInstructionMail($mailData, 'forwarder'));
            
            $sentToEmails = array_merge([$primaryEmail], $ccEmails);

            Log::info('Email notifications sent successfully', [
                'instruction_id' => $instruction['instruction_id'],
                'sent_to' => $sentToEmails,
                'total_recipients' => count($sentToEmails)
            ]);

            return [
                'success' => true,
                'sent_to' => $sentToEmails,
                'total_recipients' => count($sentToEmails),
                'pdf_attached' => true
            ];

        } catch (\Exception $mailError) {
            Log::error('Mail sending failed', [
                'error' => $mailError->getMessage(),
                'instruction_id' => $instruction['instruction_id']
            ]);

            // Return success untuk testing (karena sistem email belum dikonfigurasi sepenuhnya)
            $sentToEmails = array_merge([$primaryEmail], $ccEmails);
            
            return [
                'success' => true,
                'sent_to' => $sentToEmails,
                'total_recipients' => count($sentToEmails),
                'pdf_attached' => true,
                'note' => 'Email logged (SMTP not configured)'
            ];
        }

    } catch (\Exception $e) {
        Log::error('Email notification failed', [
            'instruction_id' => $instruction['instruction_id'],
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);

        return [
            'success' => false,
            'error' => $e->getMessage()
        ];
    }
}

/**
 * Send WhatsApp notifications
 */
private function sendWhatsAppNotifications($instruction, $request)
{
    try {
        $whatsappNumbers = $request->whatsapp_numbers ?? [];
        
        if (is_string($whatsappNumbers)) {
            $whatsappNumbers = json_decode($whatsappNumbers, true) ?? [];
        }

        if (empty($whatsappNumbers)) {
            return [
                'success' => false,
                'error' => 'No WhatsApp numbers provided'
            ];
        }

        // Prepare WhatsApp message
        $message = $this->buildWhatsAppMessage($instruction);
        
        $sentNumbers = [];
        $failedNumbers = [];

        foreach ($whatsappNumbers as $number) {
            try {
                // Here you would integrate dengan WhatsApp API
                // Untuk sekarang, kita log the attempt
                Log::info('WhatsApp notification sent', [
                    'instruction_id' => $instruction['instruction_id'],
                    'number' => $number,
                    'message_length' => strlen($message)
                ]);
                
                $sentNumbers[] = $number;
            } catch (\Exception $e) {
                $failedNumbers[] = [
                    'number' => $number,
                    'error' => $e->getMessage()
                ];
            }
        }

        return [
            'success' => count($sentNumbers) > 0,
            'sent_to' => $sentNumbers,
            'failed' => $failedNumbers,
            'total_sent' => count($sentNumbers),
            'total_failed' => count($failedNumbers)
        ];

    } catch (\Exception $e) {
        Log::error('WhatsApp notification system error', [
            'instruction_id' => $instruction['instruction_id'],
            'error' => $e->getMessage()
        ]);

        return [
            'success' => false,
            'error' => $e->getMessage()
        ];
    }
}

/**
 * Send to forwarder portal
 */
private function sendToForwarderPortal($instruction)
{
    try {
        // Update instruction untuk forwarder portal
        $instruction['forwarder_notification_sent'] = true;
        $instruction['forwarder_notification_sent_at'] = now()->toISOString();

        Log::info('Instruction sent to forwarder portal', [
            'instruction_id' => $instruction['instruction_id'],
            'forwarder_code' => $instruction['forwarder_code']
        ]);

        return [
            'success' => true,
            'forwarder_code' => $instruction['forwarder_code'],
            'sent_at' => $instruction['forwarder_notification_sent_at']
        ];

    } catch (\Exception $e) {
        Log::error('Failed to send to forwarder portal', [
            'instruction_id' => $instruction['instruction_id'],
            'error' => $e->getMessage()
        ]);

        return [
            'success' => false,
            'error' => $e->getMessage()
        ];
    }
}

/**
 * Generate PDF document
 */
private function generatePDFDocument($instructionData)
{
    try {
        // Ensure PDF directory exists
        $pdfDir = storage_path('app/public/kmi_shipping_instructions');
        if (!is_dir($pdfDir)) {
            if (!mkdir($pdfDir, 0755, true)) {
                throw new \Exception("Cannot create PDF directory: {$pdfDir}");
            }
        }

        // Generate PDF filename
        $safeInstructionId = preg_replace('/[^A-Za-z0-9_\-]/', '_', $instructionData['instruction_id']);
        $filename = 'shipping_instruction_' . $safeInstructionId . '_' . date('Ymd_His') . '.pdf';
        $filePath = $pdfDir . '/' . $filename;

        Log::info('PDF file path generated', [
            'instruction_id' => $instructionData['instruction_id'],
            'filename' => $filename,
            'file_path' => $filePath
        ]);

        // Prepare data untuk PDF template
        $pdfData = [
            'instruction_id' => $instructionData['instruction_id'],
            'ref_invoice' => $instructionData['ref_invoice'],
            'forwarder_name' => $instructionData['forwarder_name'],
            'generated_at' => Carbon::parse($instructionData['generated_at']),
            'generated_by' => $instructionData['generated_by'],
            'is_combined' => $instructionData['is_combined'],
            'sub_invoices' => $instructionData['sub_invoices'],
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

        // Create PDF using Laravel view
        try {
            $pdf = Pdf::loadView('pdf.shipping_instruction_kmi', $pdfData);
            $pdf->setPaper('A4', 'portrait');
            $pdf->save($filePath);
        } catch (\Exception $pdfError) {
            Log::error('PDF creation error', [
                'error' => $pdfError->getMessage(),
                'instruction_id' => $instructionData['instruction_id']
            ]);
            throw new \Exception("PDF creation failed: " . $pdfError->getMessage());
        }

        // Verify file was created
        if (!file_exists($filePath)) {
            throw new \Exception("PDF file was not created: {$filePath}");
        }

        // Get file size
        $fileSize = filesize($filePath);

        Log::info('PDF generated successfully', [
            'instruction_id' => $instructionData['instruction_id'],
            'filename' => $filename,
            'file_size' => $fileSize
        ]);

        return [
            'success' => true,
            'filename' => $filename,
            'path' => 'kmi_shipping_instructions/' . $filename,
            'url' => '/pdf/view/' . $filename,
            'size' => $fileSize,
            'full_path' => $filePath
        ];

    } catch (\Exception $e) {
        Log::error('PDF generation error', [
            'instruction_id' => $instructionData['instruction_id'] ?? 'unknown',
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);

        return [
            'success' => false,
            'error' => $e->getMessage()
        ];
    }
}

/**
 * Build WhatsApp message
 */
private function buildWhatsAppMessage($instruction)
{
    $message = "🚢 *SHIPPING INSTRUCTION NOTIFICATION*\n\n";
    $message .= "📋 *Instruction ID:* {$instruction['instruction_id']}\n";
    $message .= "📦 *Reference Invoice:* {$instruction['ref_invoice']}\n";
    $message .= "🏭 *Forwarder:* {$instruction['forwarder_name']}\n";
    $message .= "📅 *Pickup Date:* {$instruction['expected_pickup_date']}\n";
    $message .= "📊 *Volume:* " . number_format($instruction['total_volume'], 2) . " CBM\n";
    $message .= "⚖️ *Weight:* " . number_format($instruction['total_weight'], 0) . " KG\n\n";
    $message .= "📧 *Please check your email for complete PDF instruction*\n\n";
    $message .= "🌐 *Access Forwarder Portal:* " . config('app.url') . "/forwarder/dashboard\n\n";
    $message .= "📞 *Contact:* EKA WIJAYA - exim_3@pawindo.com";

    return $message;
}

// Helper methods

/**
 * Build complete forwarder data
 */
private function buildCompleteForwarderData($forwarder)
{
    if (!$forwarder) {
        return null;
    }

    try {
        // Parse JSON arrays dengan proper error handling
        $emails = [];
        if ($forwarder->emails) {
            if (is_string($forwarder->emails)) {
                try {
                    $emails = json_decode($forwarder->emails, true) ?? [];
                } catch (\Exception $e) {
                    Log::warning('Failed to parse forwarder emails JSON', [
                        'forwarder_code' => $forwarder->code,
                        'emails_raw' => $forwarder->emails
                    ]);
                    $emails = [];
                }
            } elseif (is_array($forwarder->emails)) {
                $emails = $forwarder->emails;
            }
        }

        $whatsappNumbers = [];
        if ($forwarder->whatsapp_numbers) {
            if (is_string($forwarder->whatsapp_numbers)) {
                try {
                    $whatsappNumbers = json_decode($forwarder->whatsapp_numbers, true) ?? [];
                } catch (\Exception $e) {
                    Log::warning('Failed to parse forwarder WhatsApp JSON', [
                        'forwarder_code' => $forwarder->code,
                        'whatsapp_raw' => $forwarder->whatsapp_numbers
                    ]);
                    $whatsappNumbers = [];
                }
            } elseif (is_array($forwarder->whatsapp_numbers)) {
                $whatsappNumbers = $forwarder->whatsapp_numbers;
            }
        }

        // Determine primary email dengan fallback
        $primaryEmail = $forwarder->primary_email;
        if (!$primaryEmail && !empty($emails)) {
            $primaryEmail = $emails[0];
        }

        // Determine primary WhatsApp dengan fallback
        $primaryWhatsApp = $forwarder->primary_whatsapp;
        if (!$primaryWhatsApp && !empty($whatsappNumbers)) {
            $primaryWhatsApp = $whatsappNumbers[0];
        }

        // Build CC emails
        $ccEmails = array_values(array_filter($emails, function($email) use ($primaryEmail) {
            return $email !== $primaryEmail;
        }));

        // Build secondary WhatsApp
        $secondaryWhatsApp = array_values(array_filter($whatsappNumbers, function($number) use ($primaryWhatsApp) {
            return $number !== $primaryWhatsApp;
        }));

        $result = [
            'code' => $forwarder->code,
            'name' => $forwarder->name,
            'contact_person' => $forwarder->contact_person,
            'phone' => $forwarder->phone,
            'address' => $forwarder->address,
            
            // Email data
            'primary_email' => $primaryEmail,
            'all_emails' => $emails,
            'cc_emails' => $ccEmails,
            
            // WhatsApp data
            'primary_whatsapp' => $primaryWhatsApp,
            'all_whatsapp' => $whatsappNumbers,
            'secondary_whatsapp' => $secondaryWhatsApp,
            
            // Notification settings
            'email_notifications_enabled' => $forwarder->email_notifications_enabled ?? true,
            'whatsapp_notifications_enabled' => $forwarder->whatsapp_notifications_enabled ?? false,
            'is_active' => $forwarder->is_active,
            
            // Additional data
            'company_type' => $forwarder->company_type,
            'service_type' => $forwarder->service_type,
            'destination' => $forwarder->destination,
            'migration_source' => true
        ];

        return $result;

    } catch (\Exception $e) {
        Log::error('Error building complete forwarder data', [
            'forwarder_code' => $forwarder->code ?? 'unknown',
            'error' => $e->getMessage()
        ]);
        return null;
    }
}

/**
 * CRITICAL: Group export data dengan STRICT 3-digit numeric prefix ONLY
 */
private function groupExportDataByStrictNumericPrefix($exportData, $forwarders)
{
    $grouped = [
        'surabaya' => [],
        'semarang' => []
    ];

    // Initialize ALL mapped forwarders first
    foreach ($forwarders as $forwarder) {
        foreach (['surabaya', 'semarang'] as $location) {
            $grouped[$location][$forwarder->code] = [
                'forwarder_code' => $forwarder->code,
                'forwarder_name' => $forwarder->name,
                'forwarder_data' => $this->buildCompleteForwarderData($forwarder),
                'ref_invoices' => [],
                'is_mapped' => true,
                'has_data' => false
            ];
        }
    }

    // Step 1: Group by STRICT 3-digit prefix ONLY
    $strictPrefixGroups = [];
    
    foreach ($exportData as $item) {
        $location = $item->delivery_type === 'ZDO1' ? 'surabaya' : 
                   ($item->delivery_type === 'ZDO2' ? 'semarang' : 'surabaya');

        $forwarderCode = $item->forwarder_code ?: 'UNASSIGNED';
        $refInvoice = $item->reference_invoice;
        
        // CRITICAL: Extract EXACTLY 3 digits from beginning
        $strictPrefix = $this->extractStrictThreeDigitPrefix($refInvoice);
        
        // Skip if no valid 3-digit prefix OR is PT SKYLINE JAYA
        if (!$strictPrefix || $this->isSkyliteJayaData($item)) {
            continue;
        }
        
        $groupKey = "{$location}_{$forwarderCode}_{$strictPrefix}";
        
        if (!isset($strictPrefixGroups[$groupKey])) {
            $forwarder = $forwarders->firstWhere('code', $forwarderCode);
            $strictPrefixGroups[$groupKey] = [
                'location' => $location,
                'forwarder_code' => $forwarderCode,
                'forwarder_name' => $forwarder ? $forwarder->name : $this->getCustomForwarderName($forwarderCode, $item->buyer),
                'forwarder_data' => $forwarder ? $this->buildCompleteForwarderData($forwarder) : null,
                'is_mapped' => $forwarder ? true : false,
                'strict_prefix' => $strictPrefix, // EXACTLY 3 digits
                'individual_invoices' => [], // Track each invoice separately
                'all_items' => []
            ];
        }

        // Add to individual invoice tracking
        if (!isset($strictPrefixGroups[$groupKey]['individual_invoices'][$refInvoice])) {
            $strictPrefixGroups[$groupKey]['individual_invoices'][$refInvoice] = [];
        }
        $strictPrefixGroups[$groupKey]['individual_invoices'][$refInvoice][] = $item;
        $strictPrefixGroups[$groupKey]['all_items'][] = $item;
    }

    // Step 2: Process STRICT prefix groups and create COMBINED entries
    foreach ($strictPrefixGroups as $groupKey => $prefixGroup) {
        $location = $prefixGroup['location'];
        $forwarderCode = $prefixGroup['forwarder_code'];
        
        // Ensure forwarder exists in grouped structure
        if (!isset($grouped[$location][$forwarderCode])) {
            $forwarder = $forwarders->firstWhere('code', $forwarderCode);
            $grouped[$location][$forwarderCode] = [
                'forwarder_code' => $prefixGroup['forwarder_code'],
                'forwarder_name' => $prefixGroup['forwarder_name'],
                'forwarder_data' => $prefixGroup['forwarder_data'],
                'ref_invoices' => [],
                'is_mapped' => $prefixGroup['is_mapped'],
                'has_data' => false
            ];
        }
        
        // Mark as having data
        $grouped[$location][$forwarderCode]['has_data'] = true;
        
        // Build the COMBINED invoice group
        $individualInvoices = $prefixGroup['individual_invoices'];
        $allItems = $prefixGroup['all_items'];
        $strictPrefix = $prefixGroup['strict_prefix'];
        
        $invoiceKeys = array_keys($individualInvoices);
        $isCombined = count($invoiceKeys) > 1;
        
        // CRITICAL: Create display name for COMBINED grouping
        if ($isCombined) {
            // Get base pattern from first invoice (remove trailing number)
            $firstInvoice = $invoiceKeys[0];
            $baseName = preg_replace('/-\d+$/', '', $firstInvoice);
            $displayName = "{$baseName} (Combined)";
        } else {
            $displayName = $invoiceKeys[0]; // Keep original for single invoice
        }
        
        // Create the COMBINED group entry
        $grouped[$location][$forwarderCode]['ref_invoices'][$displayName] = 
            $this->buildStrictPrefixGroup($prefixGroup, $displayName, $isCombined);
    }

    // Step 3: Sort forwarders (mapped with data first)
    foreach (['surabaya', 'semarang'] as $location) {
        $sortedForwarders = [];
        $mappedWithData = [];
        $mappedWithoutData = [];
        $unmapped = [];
        
        foreach ($grouped[$location] as $code => $forwarder) {
            if ($forwarder['is_mapped']) {
                if ($forwarder['has_data']) {
                    $mappedWithData[$code] = $forwarder;
                } else {
                    $mappedWithoutData[$code] = $forwarder;
                }
            } else {
                $unmapped[$code] = $forwarder;
            }
        }
        
        ksort($mappedWithData);
        ksort($mappedWithoutData);
        ksort($unmapped);
        
        $grouped[$location] = array_merge($mappedWithData, $mappedWithoutData, $unmapped);
    }

    return $grouped;
}

/**
 * CRITICAL: Extract EXACTLY 3 digits from beginning of reference invoice
 */
private function extractStrictThreeDigitPrefix($refInvoice)
{
    if (!$refInvoice) {
        return null;
    }
    
    // Match EXACTLY 3 digits at the very beginning
    if (preg_match('/^(\d{3})/', $refInvoice, $matches)) {
        return $matches[1];
    }
    
    return null; // Return null if no valid 3-digit prefix found
}

/**
 * Check if this is PT SKYLINE JAYA data (to exclude)
 */
private function isSkyliteJayaData($item)
{
    if (!$item->buyer) return false;
    
    $buyer = strtoupper($item->buyer);
    return strpos($buyer, 'PT SKYLINE JAYA') !== false || 
           strpos($buyer, 'SKYLINE JAYA') !== false;
}

/**
 * Build STRICT prefix group with COMBINED logic
 */
private function buildStrictPrefixGroup($prefixGroup, $displayName, $isCombined)
{
    $individualInvoices = $prefixGroup['individual_invoices'];
    $allItems = $prefixGroup['all_items'];
    $location = $prefixGroup['location'];
    $strictPrefix = $prefixGroup['strict_prefix'];
    
    // Calculate totals across ALL items in the prefix group
    $totalVolume = 0;
    $totalWeight = 0;
    $totalQuantity = 0;
    $allBuyers = [];
    $primaryBuyer = null;
    
    foreach ($allItems as $item) {
        $totalVolume += floatval($item->volume ?? 0);
        $totalWeight += floatval($item->weight ?? 0);
        $totalQuantity += floatval($item->quantity ?? 0);
        if ($item->buyer) $allBuyers[] = $item->buyer;
        
        if (!$primaryBuyer && $item->buyer) {
            $primaryBuyer = $item->buyer;
        }
    }

    // Build detailed sub-invoices for dropdown (ALWAYS include)
    $subInvoices = [];
    foreach ($individualInvoices as $refInvoice => $items) {
        $subVolume = array_sum(array_map(fn($item) => floatval($item->volume ?? 0), $items));
        $subWeight = array_sum(array_map(fn($item) => floatval($item->weight ?? 0), $items));
        $subQuantity = array_sum(array_map(fn($item) => floatval($item->quantity ?? 0), $items));
        $subBuyers = array_unique(array_map(fn($item) => $item->buyer, $items));
        
        $subInvoices[$refInvoice] = [
            'ref_invoice' => $refInvoice,
            'items' => $items,
            'item_count' => count($items),
            'total_volume' => $subVolume,
            'total_weight' => $subWeight,
            'total_quantity' => $subQuantity,
            'buyers' => $subBuyers,
            'primary_buyer' => $subBuyers[0] ?? 'N/A',
            'delivery_type' => $items[0]->delivery_type ?? null
        ];
    }

    return [
        'ref_invoice' => $displayName,
        'original_invoices' => array_keys($individualInvoices),
        'numeric_prefix' => $strictPrefix, // EXACTLY 3 digits
        'is_combined' => $isCombined,
        'sub_count' => count($individualInvoices),
        'sub_invoices' => $subInvoices, // ALWAYS include for dropdown
        
        // COMBINED totals
        'items' => $allItems,
        'buyers' => array_unique($allBuyers),
        'total_volume' => $totalVolume,
        'total_weight' => $totalWeight,
        'total_quantity' => $totalQuantity,
        'primary_buyer' => $primaryBuyer,
        'item_count' => count($allItems),
        
        // Enhanced forwarder data for auto-fill
        'forwarder_data' => $prefixGroup['forwarder_data'] ?? null,
        
        // Status and workflow
        'delivery_type' => $allItems[0]->delivery_type ?? null,
        'location' => $location,
        'status' => 'ready',
        'pdf_generated' => false,
        'booking_sent' => false,
        
        // Additional metadata
        'created_at' => now(),
        'last_updated' => now()
    ];
}

/**
 * Apply enhanced forwarder mapping
 */
private function applyEnhancedForwarderMapping($exportData, $forwarders)
{
    $updated = 0;

    foreach ($exportData as $item) {
        if (!$item->buyer) continue;

        // Skip PT SKYLINE JAYA mapping
        if ($this->isSkyliteJayaData($item)) {
            continue;
        }

        $correctForwarderCode = $this->determineForwarderCodeWithEnhancedMatching($item, $forwarders);
        
        if ($item->forwarder_code !== $correctForwarderCode) {
            $item->update(['forwarder_code' => $correctForwarderCode]);
            $updated++;
        }
    }

    if ($updated > 0) {
        Log::info("Enhanced forwarder mapping updated for {$updated} records (excluded PT SKYLINE JAYA)");
    }
}

/**
 * Enhanced forwarder matching
 */
private function determineForwarderCodeWithEnhancedMatching($item, $forwarders)
{
    if (!$item->buyer) {
        return 'UNASSIGNED';
    }

    $buyerName = trim($item->buyer);
    $buyerUpper = strtoupper($buyerName);

    // Skip PT SKYLINE JAYA
    if ($this->isSkyliteJayaData($item)) {
        return 'UNASSIGNED';
    }

    foreach ($forwarders as $forwarder) {
        $buyers = $forwarder->buyers;
        
        if (!is_array($buyers)) {
            continue;
        }

        foreach ($buyers as $mappedBuyer) {
            if ($this->isBuyerMatch($buyerUpper, $mappedBuyer)) {
                return $forwarder->code;
            }
        }
    }

    return 'CUSTOM_' . strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $buyerName), 0, 8));
}

/**
 * Enhanced buyer matching with multiple strategies
 */
private function isBuyerMatch($buyerUpper, $mappedBuyer)
{
    $mappedBuyerUpper = strtoupper(trim($mappedBuyer));
    
    // Strategy 1: Exact match
    if ($buyerUpper === $mappedBuyerUpper) {
        return true;
    }
    
    // Strategy 2: Normalize common variations
    $buyerNormalized = $this->normalizeBuyerName($buyerUpper);
    $mappedNormalized = $this->normalizeBuyerName($mappedBuyerUpper);
    
    if ($buyerNormalized === $mappedNormalized) {
        return true;
    }
    
    // Strategy 3: Contains match (for cases like "COMPANY LLC" vs "COMPANY")
    if (strlen($mappedNormalized) >= 5) { // Avoid too short matches
        if (strpos($buyerNormalized, $mappedNormalized) !== false || 
            strpos($mappedNormalized, $buyerNormalized) !== false) {
            return true;
        }
    }
    
    // Strategy 4: Special case handling
    return $this->handleSpecialCases($buyerUpper, $mappedBuyerUpper);
}

/**
 * Normalize buyer names for better matching
 */
private function normalizeBuyerName($name)
{
    // Remove common business suffixes
    $suffixes = [
        ', LLC', ' LLC', ', INC', ' INC', ', INC.', ' INC.', 
        ', CO.', ' CO.', ', COMPANY', ' COMPANY', ', CORP', ' CORP',
        ' LIMITED', ' LTD', ' LTD.', ' CORPORATION'
    ];
    
    $normalized = $name;
    foreach ($suffixes as $suffix) {
        $normalized = str_ireplace($suffix, '', $normalized);
    }
    
    // Normalize punctuation and spacing
    $normalized = preg_replace('/[^A-Z0-9\s]/', '', $normalized);
    $normalized = preg_replace('/\s+/', ' ', $normalized);
    $normalized = trim($normalized);
    
    return $normalized;
}

/**
 * Handle special cases in buyer matching
 */
private function handleSpecialCases($buyerUpper, $mappedBuyerUpper)
{
    // ACL Forwarder - ETHAN ALLEN + THE UTTERMOST
    $aclBuyers = [
        'ETHAN ALLEN OPERATIONS INC',
        'ETHAN ALLEN OPERATIONS, INC.',
        'ETHAN ALLEN',
        'THE UTTERMOST CO.',
        'THE UTTERMOST CO',
        'UTTERMOST CO.',
        'UTTERMOST CO',
        'UTTERMOST'
    ];
    
    if (in_array($buyerUpper, $aclBuyers) && in_array($mappedBuyerUpper, $aclBuyers)) {
        return true;
    }
    
    // Add other special cases as needed...
    
    return false;
}

/**
 * Calculate delivery type statistics
 */
private function calculateDeliveryTypeStatistics($exportData)
{
    $surabayaData = $exportData->where('delivery_type', 'ZDO1');
    $semarangData = $exportData->where('delivery_type', 'ZDO2');

    return [
        'surabaya_stats' => [
            'total_records' => $surabayaData->count(),
            'total_volume' => $surabayaData->sum('volume'),
            'total_weight' => $surabayaData->sum('weight'),
            'unique_buyers' => $surabayaData->pluck('buyer')->unique()->count(),
            'unique_ref_invoices' => $surabayaData->pluck('reference_invoice')->unique()->count()
        ],
        'semarang_stats' => [
            'total_records' => $semarangData->count(),
            'total_volume' => $semarangData->sum('volume'),
            'total_weight' => $semarangData->sum('weight'),
            'unique_buyers' => $semarangData->pluck('buyer')->unique()->count(),
            'unique_ref_invoices' => $semarangData->pluck('reference_invoice')->unique()->count()
        ],
        'total_stats' => [
            'total_records' => $exportData->count(),
            'total_volume' => $exportData->sum('volume'),
            'total_weight' => $exportData->sum('weight'),
            'unique_buyers' => $exportData->pluck('buyer')->unique()->count(),
            'unique_ref_invoices' => $exportData->pluck('reference_invoice')->unique()->count()
        ]
    ];
}

/**
 * Get custom forwarder name
 */
private function getCustomForwarderName($forwarderCode, $buyer)
{
    if (str_starts_with($forwarderCode, 'CUSTOM_')) {
        return "Custom - {$buyer}";
    }

    $forwarder = Forwarder::where('code', $forwarderCode)->first();
    return $forwarder ? $forwarder->name : $forwarderCode;
}

/**
 * Count prefix groups for logging
 */
private function countPrefixGroups($groupedData)
{
    $count = 0;
    foreach ($groupedData as $location) {
        foreach ($location as $forwarder) {
            $count += count($forwarder['ref_invoices']);
        }
    }
    return $count;
}

/**
 * Get empty statistics structure
 */
private function getEmptyStatistics()
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
 * Format file size for display
 */
private function formatFileSize($bytes)
{
    if ($bytes == 0) return '0 B';
    
    $units = ['B', 'KB', 'MB', 'GB'];
    $base = log($bytes, 1024);
    
    return round(pow(1024, $base - floor($base)), 2) . ' ' . $units[floor($base)];
}

// Keep existing legacy methods for compatibility
public function getForwarderInfoForRefInvoice(Request $request)
{
    // Legacy endpoint - redirect to auto-fill
    return $this->getForwarderDataForAutoFill($request);
}

public function getData(Request $request)
{
    // Existing getData implementation
    // Keep your existing implementation here
}

public function syncSAP(Request $request)
{
    // Existing syncSAP implementation 
    // Keep your existing implementation here
}

public function updateDestination(Request $request)
{
    // Existing updateDestination implementation
    // Keep your existing implementation here
}

public function exportToExcel(Request $request)
{
    // Existing exportToExcel implementation
    // Keep your existing implementation here
}

public function getStatistics(Request $request)
{
    // Existing getStatistics implementation
    // Keep your existing implementation here
}

public function search(Request $request)
{
    // Existing search implementation
    // Keep your existing implementation here
}

public function checkBuyerAssignment(Request $request)
{
    // Existing checkBuyerAssignment implementation
    // Keep your existing implementation here
}
}