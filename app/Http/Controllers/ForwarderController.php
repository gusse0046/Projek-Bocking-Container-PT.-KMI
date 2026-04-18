<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Forwarder;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ForwarderController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware(function ($request, $next) {
            if (auth()->user()->role !== 'forwarder') {
                abort(403, 'Unauthorized access to Forwarder Portal');
            }
            return $next($request);
        });
    }

    /**
     * FIXED: Forwarder Dashboard with Real-time PDF Notifications
     */
    public function dashboard()
    {
        try {
            $user = auth()->user();
            $forwarder = Forwarder::where('code', $user->forwarder_code)->first();
            
            if (!$forwarder) {
                Log::warning('Forwarder not found in database', [
                    'user' => $user->email,
                    'forwarder_code' => $user->forwarder_code
                ]);
                
                $forwarderInfo = [
                    'code' => $user->forwarder_code,
                    'name' => 'Test Forwarder - ' . $user->forwarder_code,
                    'buyers' => [],
                    'destination' => 'Global'
                ];
            } else {
                $forwarderInfo = [
                    'code' => $forwarder->code,
                    'name' => $forwarder->name,
                    'buyers' => is_array($forwarder->buyers) ? $forwarder->buyers : json_decode($forwarder->buyers, true) ?? [],
                    'destination' => $forwarder->destination
                ];
            }
            
            // Get workflow statistics with PDF info
            $workflowStats = $this->getEnhancedWorkflowStatistics();
            
            // Get pending instructions with PDF files
            $pendingInstructions = $this->getPendingInstructionsWithPDF();
            
            // Get recent PDF notifications
            $recentPDFNotifications = $this->getRecentPDFNotifications();
            
            Log::info('Forwarder dashboard loaded with PDF system', [
                'forwarder_code' => $user->forwarder_code,
                'pending_instructions' => count($pendingInstructions),
                'pdf_notifications' => count($recentPDFNotifications),
                'workflow_stats' => $workflowStats
            ]);
            
            return view('forwarder.dashboard', compact(
                'forwarderInfo',
                'workflowStats',
                'pendingInstructions',
                'recentPDFNotifications'
            ));
            
        } catch (\Exception $e) {
            Log::error('Error loading forwarder dashboard', [
                'error' => $e->getMessage(),
                'user' => auth()->user()->email,
                'forwarder_code' => auth()->user()->forwarder_code
            ]);
            
            return view('forwarder.dashboard')->with('error', 'Failed to load dashboard data');
        }
    }

    /**
     * FIXED: Get pending instructions with PDF files
     */
   public function getPendingInstructionsWithPDF()
{
    try {
        $forwarderCode = auth()->user()->forwarder_code;
        
        if (!$forwarderCode) {
            Log::warning('No forwarder code found for user', ['user' => auth()->user()->email]);
            return [];
        }
        
        // ENHANCED: Get ALL workflow instructions from cache
        $allInstructions = cache()->get('all_workflow_instructions', []);
        
        // ENHANCED: Also check forwarder-specific cache
        $forwarderInstructions = cache()->get("forwarder_instructions_{$forwarderCode}", []);
        
        // ENHANCED: Merge instructions from both sources
        $combinedInstructions = array_merge($allInstructions, $forwarderInstructions);
        
        // ENHANCED: Debug logging
        Log::info('ENHANCED DEBUG: Checking workflow instructions', [
            'forwarder_code' => $forwarderCode,
            'total_all_instructions' => count($allInstructions),
            'total_forwarder_instructions' => count($forwarderInstructions),
            'combined_total' => count($combinedInstructions),
            'user_email' => auth()->user()->email
        ]);
        
        // ENHANCED: Log sample instruction for debugging
        if (!empty($combinedInstructions)) {
            $sampleInstruction = array_values($combinedInstructions)[0];
            Log::info('ENHANCED DEBUG: Sample instruction', [
                'instruction_forwarder_code' => $sampleInstruction['forwarder_code'] ?? 'NOT SET',
                'looking_for_forwarder_code' => $forwarderCode,
                'instruction_keys' => array_keys($sampleInstruction)
            ]);
        }
        
        $pendingInstructions = [];
        
        foreach ($combinedInstructions as $instructionId => $instruction) {
            try {
                // ENHANCED: Debug each instruction check
                $instructionForwarderCode = $instruction['forwarder_code'] ?? 'NOT_SET';
                
                Log::debug('ENHANCED DEBUG: Checking instruction', [
                    'instruction_id' => $instructionId,
                    'instruction_forwarder_code' => $instructionForwarderCode,
                    'user_forwarder_code' => $forwarderCode,
                    'match' => $instructionForwarderCode === $forwarderCode,
                    'instruction_status' => $instruction['status'] ?? 'NO_STATUS'
                ]);
                
                // Check if this instruction is for current forwarder
                if (!isset($instruction['forwarder_code']) || $instruction['forwarder_code'] !== $forwarderCode) {
                    continue;
                }
                
                // ENHANCED: Check if instruction needs forwarder action
                $status = $instruction['status'] ?? '';
                $needsAction = in_array($status, [
                    'sent', 'delivered', 'received', 'pending_response', 'generated'
                ]);
                
                Log::debug('ENHANCED DEBUG: Instruction status check', [
                    'instruction_id' => $instructionId,
                    'status' => $status,
                    'needs_action' => $needsAction
                ]);
                
                if ($needsAction) {
                    $transformedInstruction = [
                        // Basic instruction info
                        'instruction_id' => $instruction['instruction_id'],
                        'type' => $instruction['type'] ?? 'export',
                        'status' => $instruction['status'] ?? 'sent',
                        'priority' => $instruction['priority'] ?? 'normal',
                        'created_at' => $instruction['generated_at'] ?? $instruction['created_at'] ?? now()->toISOString(),
                        
                        // Forwarder info
                        'forwarder_code' => $instruction['forwarder_code'],
                        'forwarder_name' => $instruction['forwarder_name'] ?? '',
                        'notification_email' => $instruction['notification_email'] ?? '',
                        
                        // PDF Information
                        'pdf_available' => $instruction['pdf_generated'] ?? false,
                        'pdf_filename' => $instruction['pdf_filename'] ?? null,
                        'pdf_url' => $instruction['pdf_url'] ?? null,
                        'pdf_size' => $instruction['pdf_size'] ?? 0,
                        'pdf_size_formatted' => $this->formatFileSize($instruction['pdf_size'] ?? 0),
                        
                        // Instruction details
                        'ref_invoice' => $instruction['ref_invoice'] ?? 'N/A',
                        'is_combined' => $instruction['is_combined'] ?? false,
                        'pickup_location' => $instruction['pickup_location'] ?? '',
                        'expected_pickup_date' => $instruction['expected_pickup_date'] ?? '',
                        'container_type' => $instruction['container_type'] ?? '',
                        'port_loading' => $instruction['port_loading'] ?? '',
                        'port_destination' => $instruction['port_destination'] ?? '',
                        'contact_person' => $instruction['contact_person'] ?? '',
                        'special_instructions' => $instruction['special_instructions'] ?? '',
                        
                        // Summary data
                        'total_volume' => $instruction['total_volume'] ?? 0,
                        'total_weight' => $instruction['total_weight'] ?? 0,
                        'total_quantity' => $instruction['total_quantity'] ?? 0,
                        'unique_buyers' => $instruction['unique_buyers'] ?? [],
                        'primary_buyer' => $instruction['primary_buyer'] ?? 'N/A',
                        
                        // Workflow tracking
                        'sent_by' => $instruction['sent_by'] ?? '',
                        'sent_by_email' => $instruction['sent_by_email'] ?? '',
                        'sent_at' => $instruction['sent_at'] ?? null,
                        'received_at' => $instruction['forwarder_notification_sent_at'] ?? $instruction['sent_at'] ?? now()->toISOString(),
                        
                        // Notification status
                        'is_new_notification' => $this->isNewNotification($instructionId),
                        'notification_read' => $this->isNotificationRead($instructionId),
                        'can_respond' => true,
                        'has_attachments' => !empty($instruction['pdf_filename']),
                        
                        // Portal integration
                        'portal_delivered' => ($instruction['portal_status'] ?? '') === 'delivered',
                        'view_pdf_url' => $this->getPDFViewUrl($instruction),
                        'download_pdf_url' => $this->getPDFDownloadUrl($instruction)
                    ];
                    
                    $pendingInstructions[] = $transformedInstruction;
                    
                    Log::info('ENHANCED DEBUG: Added instruction to pending list', [
                        'instruction_id' => $instructionId,
                        'pdf_available' => $transformedInstruction['pdf_available'],
                        'pdf_filename' => $transformedInstruction['pdf_filename']
                    ]);
                }
            } catch (\Exception $e) {
                Log::error('Error processing individual instruction', [
                    'instruction_id' => $instructionId,
                    'error' => $e->getMessage()
                ]);
            }
        }
        
        // Sort by priority and date
        usort($pendingInstructions, function($a, $b) {
            $priorityOrder = ['urgent' => 3, 'high' => 2, 'normal' => 1];
            $aPriority = $priorityOrder[$a['priority']] ?? 1;
            $bPriority = $priorityOrder[$b['priority']] ?? 1;
            
            if ($aPriority === $bPriority) {
                return strtotime($b['received_at']) - strtotime($a['received_at']);
            }
            
            return $bPriority - $aPriority;
        });
        
        Log::info('ENHANCED FINAL: Filtered pending instructions', [
            'forwarder_code' => $forwarderCode,
            'pending_count' => count($pendingInstructions),
            'with_pdf' => count(array_filter($pendingInstructions, fn($i) => $i['pdf_available']))
        ]);
        
        return $pendingInstructions;
        
    } catch (\Exception $e) {
        Log::error('Error getting pending instructions with PDF', [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
            'forwarder_code' => auth()->user()->forwarder_code ?? 'unknown'
        ]);
        
        return [];
    }
}

    /**
     * Get enhanced workflow statistics
     */
    public function getEnhancedWorkflowStatistics()
    {
        try {
            $forwarderCode = auth()->user()->forwarder_code;
            $allInstructions = cache()->get('all_workflow_instructions', []);
            $forwarderInstructions = cache()->get("forwarder_instructions_{$forwarderCode}", []);
            
            // Merge and filter for this forwarder
            $combinedInstructions = array_merge($allInstructions, $forwarderInstructions);
            $forwarderFilteredInstructions = array_filter($combinedInstructions, function ($instruction) use ($forwarderCode) {
                return isset($instruction['forwarder_code']) && $instruction['forwarder_code'] === $forwarderCode;
            });
            
            // Calculate statistics
            $stats = [
                'new_instructions' => count(array_filter($forwarderFilteredInstructions, function ($i) {
                    return isset($i['status']) && $i['status'] === 'sent';
                })),
                'pending_response' => count(array_filter($forwarderFilteredInstructions, function ($i) {
                    return isset($i['status']) && in_array($i['status'], ['sent', 'received', 'delivered']);
                })),
                'total_instructions' => count($forwarderFilteredInstructions),
                'pdf_available' => count(array_filter($forwarderFilteredInstructions, function ($i) {
                    return isset($i['pdf_generated']) && $i['pdf_generated'] === true;
                })),
                'unread_notifications' => count(array_filter($forwarderFilteredInstructions, function ($i) {
                    return !$this->isNotificationRead($i['instruction_id']);
                })),
                'today_notifications' => count(array_filter($forwarderFilteredInstructions, function ($i) {
                    if (!isset($i['sent_at'])) return false;
                    $sentAt = \Carbon\Carbon::parse($i['sent_at']);
                    return $sentAt->isToday();
                }))
            ];
            
            return $stats;
            
        } catch (\Exception $e) {
            Log::error('Error getting enhanced workflow statistics', [
                'error' => $e->getMessage(),
                'forwarder_code' => auth()->user()->forwarder_code
            ]);
            
            return [
                'new_instructions' => 0,
                'pending_response' => 0,
                'total_instructions' => 0,
                'pdf_available' => 0,
                'unread_notifications' => 0,
                'today_notifications' => 0
            ];
        }
    }

    /**
     * Get recent PDF notifications
     */
    public function getRecentPDFNotifications()
    {
        try {
            $forwarderCode = auth()->user()->forwarder_code;
            $allInstructions = cache()->get('all_workflow_instructions', []);
            $forwarderInstructions = cache()->get("forwarder_instructions_{$forwarderCode}", []);
            
            $combinedInstructions = array_merge($allInstructions, $forwarderInstructions);
            $recentPDFs = [];
            $cutoffTime = now()->subHours(24); // Last 24 hours
            
            foreach ($combinedInstructions as $instructionId => $instruction) {
                if (isset($instruction['forwarder_code']) && 
                    $instruction['forwarder_code'] === $forwarderCode &&
                    isset($instruction['pdf_generated']) &&
                    $instruction['pdf_generated'] === true) {
                    
                    $sentAt = \Carbon\Carbon::parse($instruction['sent_at'] ?? now());
                    
                    if ($sentAt->greaterThan($cutoffTime)) {
                        $recentPDFs[] = [
                            'instruction_id' => $instruction['instruction_id'],
                            'ref_invoice' => $instruction['ref_invoice'] ?? 'N/A',
                            'pdf_filename' => $instruction['pdf_filename'] ?? null,
                            'pdf_size_formatted' => $this->formatFileSize($instruction['pdf_size'] ?? 0),
                            'sent_at' => $sentAt->format('Y-m-d H:i:s'),
                            'sent_at_human' => $sentAt->diffForHumans(),
                            'priority' => $instruction['priority'] ?? 'normal',
                            'container_type' => $instruction['container_type'] ?? 'N/A',
                            'is_read' => $this->isNotificationRead($instructionId)
                        ];
                    }
                }
            }
            
            // Sort by most recent first
            usort($recentPDFs, function($a, $b) {
                return strtotime($b['sent_at']) - strtotime($a['sent_at']);
            });
            
            return array_slice($recentPDFs, 0, 10); // Limit to 10 most recent
            
        } catch (\Exception $e) {
            Log::error('Error getting recent PDF notifications', [
                'error' => $e->getMessage(),
                'forwarder_code' => auth()->user()->forwarder_code
            ]);
            
            return [];
        }
    }

    /**
     * View PDF instruction
     */
   public function viewPDFInstruction(Request $request, $instructionId)
{
    try {
        $forwarderCode = auth()->user()->forwarder_code;
        
        // Get instruction from cache (kode yang sudah ada)
        $instruction = cache()->get("workflow_instruction_{$instructionId}");
        if (!$instruction) {
            $allInstructions = cache()->get('all_workflow_instructions', []);
            $instruction = $allInstructions[$instructionId] ?? null;
        }
        
        if (!$instruction) {
            return response()->json(['error' => 'Instruction not found'], 404);
        }
        
        // Verify authorization (kode yang sudah ada)
        if (($instruction['forwarder_code'] ?? '') !== $forwarderCode) {
            return response()->json(['error' => 'Unauthorized access'], 403);
        }
        
        // ==== GANTI BAGIAN INI ====
        // Hapus kode pencarian file yang lama dan ganti dengan:
        $pdfPath = $this->findPDFFile($instructionId, $instruction['pdf_filename'] ?? null);
        
        if (!$pdfPath) {
            Log::error('PDF not found', ['instruction_id' => $instructionId]);
            return response()->json(['error' => 'PDF not found'], 404);
        }
        // ==== SAMPAI SINI ====
        
        // Return file (kode yang sudah ada tetap sama)
        return response()->file($pdfPath, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . basename($pdfPath) . '"'
        ]);
        
    } catch (\Exception $e) {
        Log::error('Error viewing PDF', ['error' => $e->getMessage()]);
        return response()->json(['error' => 'Error accessing PDF'], 500);
    }
}

    /**
     * Download PDF instruction
     */
    public function downloadPDFInstruction(Request $request, $instructionId)
    {
        try {
            $forwarderCode = auth()->user()->forwarder_code;
            
            // Get instruction
            $instruction = cache()->get("workflow_instruction_{$instructionId}");
            if (!$instruction) {
                $forwarderInstructions = cache()->get("forwarder_instructions_{$forwarderCode}", []);
                $instruction = $forwarderInstructions[$instructionId] ?? null;
            }
            
            if (!$instruction) {
                abort(404, 'Instruction not found');
            }
            
            if ($instruction['forwarder_code'] !== $forwarderCode) {
                abort(403, 'Unauthorized access');
            }
            
            if (!isset($instruction['pdf_filename']) || !$instruction['pdf_generated']) {
                abort(404, 'PDF not available');
            }
            
            // Find PDF file
            $pdfPaths = [
                storage_path('app/public/shipping_instructions/' . $instruction['pdf_filename']),
                storage_path('app/public/kmi_shipping_instructions/' . $instruction['pdf_filename'])
            ];
            
            $pdfPath = null;
            foreach ($pdfPaths as $path) {
                if (file_exists($path)) {
                    $pdfPath = $path;
                    break;
                }
            }
            
            if (!$pdfPath) {
                abort(404, 'PDF file not found');
            }
            
            // Mark as read
            $this->markNotificationAsRead($instructionId);
            
            Log::info('PDF instruction downloaded by forwarder', [
                'instruction_id' => $instructionId,
                'forwarder_code' => $forwarderCode,
                'pdf_filename' => $instruction['pdf_filename']
            ]);
            
            return response()->download($pdfPath, $instruction['pdf_filename'], [
                'Content-Type' => 'application/pdf'
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error downloading PDF instruction', [
                'instruction_id' => $instructionId,
                'error' => $e->getMessage()
            ]);
            
            abort(500, 'Error downloading PDF');
        }
    }

    /**
     * Helper methods
     */
    
    private function formatFileSize($bytes)
    {
        if ($bytes == 0) return '0 B';
        
        $units = ['B', 'KB', 'MB', 'GB'];
        $base = log($bytes, 1024);
        
        return round(pow(1024, $base - floor($base)), 2) . ' ' . $units[floor($base)];
    }

    private function isNotificationRead($instructionId)
    {
        try {
            $forwarderCode = auth()->user()->forwarder_code;
            $cacheKey = "notification_read_{$forwarderCode}_{$instructionId}";
            
            return cache()->has($cacheKey);
        } catch (\Exception $e) {
            return false;
        }
    }

    private function isNewNotification($instructionId)
    {
        try {
            $instruction = cache()->get("workflow_instruction_{$instructionId}");
            
            if (!$instruction || !isset($instruction['sent_at'])) {
                return false;
            }
            
            $sentAt = \Carbon\Carbon::parse($instruction['sent_at']);
            $oneHourAgo = now()->subHour();
            
            return $sentAt->greaterThan($oneHourAgo) && !$this->isNotificationRead($instructionId);
        } catch (\Exception $e) {
            return false;
        }
    }

    private function markNotificationAsRead($instructionId)
    {
        try {
            $forwarderCode = auth()->user()->forwarder_code;
            $cacheKey = "notification_read_{$forwarderCode}_{$instructionId}";
            
            cache()->put($cacheKey, true, now()->addDays(30));
            
            Log::info('Notification marked as read', [
                'instruction_id' => $instructionId,
                'forwarder_code' => $forwarderCode
            ]);
        } catch (\Exception $e) {
            Log::error('Error marking notification as read', [
                'instruction_id' => $instructionId,
                'error' => $e->getMessage()
            ]);
        }
    }

    private function getPDFViewUrl($instruction)
    {
        if (!($instruction['pdf_generated'] ?? false) || !($instruction['pdf_filename'] ?? null)) {
            return null;
        }
        
        return route('forwarder.view-pdf-instruction', ['instructionId' => $instruction['instruction_id']]);
    }

    private function getPDFDownloadUrl($instruction)
    {
        if (!($instruction['pdf_generated'] ?? false) || !($instruction['pdf_filename'] ?? null)) {
            return null;
        }
        
        return route('forwarder.download-pdf-instruction', ['instructionId' => $instruction['instruction_id']]);
    }

    /**
     * API Endpoints for AJAX calls
     */
    public function refreshInstructions()
    {
        try {
            $pendingInstructions = $this->getPendingInstructionsWithPDF();
            $stats = $this->getEnhancedWorkflowStatistics();
            
            return response()->json([
                'success' => true,
                'message' => 'Instructions refreshed successfully with PDF system',
                'instructions' => $pendingInstructions,
                'statistics' => $stats,
                'count' => count($pendingInstructions),
                'pdf_count' => count(array_filter($pendingInstructions, fn($i) => $i['pdf_available'])),
                'timestamp' => now()->toISOString()
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to refresh instructions: ' . $e->getMessage()
            ]);
        }
    }

    public function getForwarderInfo()
    {
        try {
            $user = auth()->user();
            $forwarder = Forwarder::where('code', $user->forwarder_code)->first();
            
            if (!$forwarder) {
                return response()->json([
                    'success' => true,
                    'forwarder' => [
                        'code' => $user->forwarder_code,
                        'name' => 'Test Forwarder - ' . $user->forwarder_code,
                        'buyers' => [],
                        'destination' => 'Global',
                        'email' => strtolower($user->forwarder_code) . '@forwarder.com'
                    ]
                ]);
            }
            
            return response()->json([
                'success' => true,
                'forwarder' => [
                    'code' => $forwarder->code,
                    'name' => $forwarder->name,
                    'buyers' => is_array($forwarder->buyers) ? $forwarder->buyers : json_decode($forwarder->buyers, true) ?? [],
                    'destination' => $forwarder->destination,
                    'email' => $forwarder->primary_email ?? strtolower($forwarder->code) . '@forwarder.com'
                ]
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to get forwarder information: ' . $e->getMessage()
            ]);
        }
    }

    /**
 * Enhanced PDF file finder
 */
private function findPDFFile($instructionId, $filename = null)
{
    $searchPaths = [
        storage_path('app/public/shipping_instructions/'),
        storage_path('app/public/kmi_shipping_instructions/')
    ];
    
    foreach ($searchPaths as $dir) {
        if ($filename) {
            $path = $dir . $filename;
            if (file_exists($path)) return $path;
        }
        
        // Search by pattern
        $files = glob($dir . '*' . $instructionId . '*.pdf');
        if (!empty($files)) return $files[0];
    }
    
    return null;
}
}