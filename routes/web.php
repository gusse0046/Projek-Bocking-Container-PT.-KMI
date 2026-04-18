<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\ExportController;
use App\Http\Controllers\ImportController;
use App\Http\Controllers\ForwarderController;
use App\Http\Controllers\DashboardController;

// ========================================
// AUTHENTICATION ROUTES
// ========================================
Auth::routes();

// ========================================
// MAIN DASHBOARD REDIRECT
// ========================================
Route::get('/', function () {
    if (auth()->check()) {
        $user = auth()->user();
        switch ($user->role) {
            case 'export':
                return redirect()->route('dashboard.export');
            case 'import':
                return redirect()->route('dashboard.import');
            case 'forwarder':
                return redirect()->route('forwarder.dashboard');
            default:
                return redirect()->route('dashboard');
        }
    }
    return redirect()->route('login');
})->name('home');

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware('auth')->name('dashboard');

// ========================================
// FIXED: COMPLETE NOTIFICATION SYSTEM ROUTES
// ========================================

Route::middleware(['auth'])->group(function () {
    
    // ===== MAIN DASHBOARD ROUTES =====
    Route::get('/export', [DashboardController::class, 'exportDashboard'])->name('dashboard.export');
    Route::get('/export/surabaya', [DashboardController::class, 'exportDashboard'])
         ->defaults('location', 'surabaya')->name('dashboard.export.surabaya');
    Route::get('/export/semarang', [DashboardController::class, 'exportDashboard'])
         ->defaults('location', 'semarang')->name('dashboard.export.semarang');
    
    Route::get('/import', [DashboardController::class, 'importDashboard'])->name('dashboard.import');
    
    // ===== FIXED: PDF GENERATION & SEND SYSTEM =====
    
    /**
     * CRITICAL: Generate PDF - Must work before sending
     */
    Route::post('/dashboard/generate-shipping-instruction-pdf', [DashboardController::class, 'generateShippingInstructionPDF'])
        ->name('dashboard.generate-shipping-instruction-pdf');
    
    /**
     * CRITICAL: Send Notifications - Complete implementation
     */
    Route::post('/dashboard/send-container-booking-request', [DashboardController::class, 'sendContainerBookingRequest'])
        ->name('dashboard.send-container-booking-request');
    
    /**
     * FIXED: Auto-fill support
     */
    Route::match(['GET', 'POST'], '/dashboard/get-forwarder-data-for-autofill', [DashboardController::class, 'getForwarderDataForAutoFill'])
        ->name('dashboard.get-forwarder-data-for-autofill');
    
    /**
     * PDF Access Routes
     */
    Route::get('/pdf/view/{filename}', [DashboardController::class, 'viewPDF'])
        ->name('pdf.view')
        ->where('filename', '[a-zA-Z0-9_\-\.]+');
    
    Route::get('/pdf/download/{filename}', [DashboardController::class, 'downloadPDF'])
        ->name('pdf.download')
        ->where('filename', '[a-zA-Z0-9_\-\.]+');
});

// ========================================
// FIXED: FORWARDER PORTAL WITH PDF INTEGRATION
// ========================================

Route::middleware(['auth'])->prefix('forwarder')->name('forwarder.')->group(function () {
    
    // ===== MAIN DASHBOARD =====
    Route::get('/dashboard', [ForwarderController::class, 'dashboard'])->name('dashboard');
    Route::get('/info', [ForwarderController::class, 'getForwarderInfo'])->name('info');
    
    // ===== PDF ACCESS FOR FORWARDERS =====
    Route::get('/instruction/{instructionId}/pdf/view', [ForwarderController::class, 'viewPDFInstruction'])
        ->name('view-pdf-instruction');
    
    Route::get('/instruction/{instructionId}/pdf/download', [ForwarderController::class, 'downloadPDFInstruction'])
        ->name('download-pdf-instruction');
    
    // ===== API ENDPOINTS =====
    Route::get('/pending-instructions', function(Request $request) {
        try {
            $forwarderCode = auth()->user()->forwarder_code;
            $forwarderController = app(ForwarderController::class);
            $pendingInstructions = $forwarderController->getPendingInstructionsWithPDF();
            $stats = $forwarderController->getEnhancedWorkflowStatistics();
            $recentPDFs = $forwarderController->getRecentPDFNotifications();
            
            return response()->json([
                'success' => true,
                'instructions' => $pendingInstructions,
                'statistics' => $stats,
                'recent_pdfs' => $recentPDFs,
                'forwarder_code' => $forwarderCode,
                'total_pending' => count($pendingInstructions),
                'pdf_available_count' => count(array_filter($pendingInstructions, fn($i) => $i['pdf_available'])),
                'unread_notifications' => $stats['unread_notifications'],
                'system_enhanced' => true
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error in forwarder pending-instructions endpoint', [
                'error' => $e->getMessage(),
                'forwarder_code' => auth()->user()->forwarder_code
            ]);
            
            return response()->json([
                'success' => false,
                'error' => 'Failed to get pending instructions: ' . $e->getMessage(),
                'instructions' => []
            ], 500);
        }
    })->name('pending-instructions');
    
    Route::get('/refresh-instructions', [ForwarderController::class, 'refreshInstructions'])
        ->name('refresh-instructions');
});

// ========================================
// TESTING & DEBUGGING ROUTES
// ========================================

Route::middleware(['auth'])->group(function () {
    
    /**
     * Test email configuration
     */
    Route::get('/test-email-config', function() {
        return response()->json([
            'mail_driver' => config('mail.default'),
            'mail_host' => config('mail.mailers.smtp.host'),
            'mail_port' => config('mail.mailers.smtp.port'),
            'mail_username' => config('mail.mailers.smtp.username'),
            'mail_from' => config('mail.from.address'),
            'mail_encryption' => config('mail.mailers.smtp.encryption'),
            'smtp_configured' => !empty(config('mail.mailers.smtp.host')),
            'timestamp' => now()->toISOString()
        ]);
    })->name('test-email-config');
    
    /**
     * Test email sending with actual forwarder data
     */
    Route::get('/test-send-email', function() {
        if (!auth()->check() || !in_array(auth()->user()->role, ['export', 'import'])) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }
        
        try {
            // Get first active forwarder for testing
            $forwarder = \App\Models\Forwarder::where('is_active', true)->first();
            
            if (!$forwarder) {
                return response()->json([
                    'success' => false,
                    'error' => 'No active forwarders found for testing'
                ]);
            }
            
            // Parse forwarder emails
            $emails = [];
            if ($forwarder->emails) {
                $emails = is_array($forwarder->emails) ? $forwarder->emails : json_decode($forwarder->emails, true) ?? [];
            }
            
            $primaryEmail = $forwarder->primary_email ?: ($emails[0] ?? 'test@example.com');
            
            // Create test instruction data
            $testInstruction = [
                'instruction_id' => 'TEST-' . date('YmdHis'),
                'ref_invoice' => 'TEST-EMAIL-001',
                'forwarder_name' => $forwarder->name,
                'forwarder_code' => $forwarder->code,
                'priority' => 'normal',
                'expected_pickup_date' => now()->addDays(3)->format('Y-m-d'),
                'container_type' => '1 X 40 HC',
                'total_volume' => 45.50,
                'total_weight' => 2500,
                'total_quantity' => 150,
                'pickup_location' => 'Factory Warehouse - Sidoarjo',
                'port_loading' => 'Tanjung Perak - Surabaya',
                'port_destination' => 'LOS ANGELES',
                'contact_person' => 'Test Contact',
                'sent_by' => auth()->user()->name,
                'sent_by_email' => auth()->user()->email ?? 'test@pawindo.com'
            ];
            
            $testData = [
                'instruction' => $testInstruction,
                'forwarder' => $forwarder,
                'company' => [
                    'name' => 'PT. KAYU MEBEL INDONESIA',
                    'email' => 'exim_3@pawindo.com',
                    'phone' => '+62-31-8971234',
                    'pic' => 'EKA WIJAYA'
                ]
            ];
            
            // Check SMTP configuration
            $smtpConfigured = !empty(config('mail.mailers.smtp.host'));
            
            if (!$smtpConfigured) {
                return response()->json([
                    'success' => false,
                    'error' => 'SMTP not configured',
                    'action' => 'Please configure SMTP settings in .env file',
                    'test_data' => $testInstruction,
                    'forwarder_email' => $primaryEmail
                ]);
            }
            
            // Send test email
            Mail::to($primaryEmail)
                ->send(new \App\Mail\ShippingInstructionMail($testData, 'forwarder'));
            
            return response()->json([
                'success' => true,
                'message' => 'Test email sent successfully',
                'sent_to' => $primaryEmail,
                'forwarder_code' => $forwarder->code,
                'forwarder_name' => $forwarder->name,
                'test_instruction_id' => $testInstruction['instruction_id']
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Email sending failed: ' . $e->getMessage(),
                'smtp_configured' => !empty(config('mail.mailers.smtp.host'))
            ]);
        }
    })->name('test-send-email');
    
    /**
     * Test notification system end-to-end
     */
    Route::post('/test-notification-system', function(Request $request) {
        try {
            // Create test instruction in cache
            $testInstructionId = 'TEST-NOTIFY-' . date('YmdHis');
            $testInstruction = [
                'instruction_id' => $testInstructionId,
                'ref_invoice' => 'TEST-NOTIFICATION-001',
                'forwarder_code' => $request->input('forwarder_code', 'ACL'),
                'forwarder_name' => 'PT. ATLANTIC CONTAINER LINE',
                'notification_email' => $request->input('email', 'test@example.com'),
                'priority' => 'normal',
                'expected_pickup_date' => now()->addDays(2)->format('Y-m-d'),
                'container_type' => '1 X 40 HC',
                'total_volume' => 50.00,
                'total_weight' => 3000,
                'pdf_generated' => true,
                'pdf_filename' => 'test_shipping_instruction.pdf',
                'status' => 'generated',
                'generated_at' => now()->toISOString()
            ];
            
            // Store in cache
            cache()->put("workflow_instruction_{$testInstructionId}", $testInstruction, now()->addHours(1));
            
            // Test the send system
            $dashboardController = app(DashboardController::class);
            $testRequest = new Request([
                'instruction_id' => $testInstructionId,
                'send_email' => true,
                'send_whatsapp' => false,
                'send_forwarder_portal' => true
            ]);
            
            $result = $dashboardController->sendContainerBookingRequest($testRequest);
            
            return $result;
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Notification test failed: ' . $e->getMessage()
            ]);
        }
    })->name('test-notification-system');

     Route::get('/debug-forwarder-mapping', function() {
        if (!auth()->check()) {
            return redirect('/login');
        }
        
        $user = auth()->user();
        $results = [];
        
        // Check current user forwarder code
        $results['current_user'] = [
            'email' => $user->email,
            'role' => $user->role,
            'forwarder_code' => $user->forwarder_code
        ];
        
        // Check forwarder exists in database
        $forwarder = \App\Models\Forwarder::where('code', $user->forwarder_code)->first();
        $results['forwarder_in_db'] = $forwarder ? [
            'code' => $forwarder->code,
            'name' => $forwarder->name,
            'is_active' => $forwarder->is_active
        ] : 'NOT FOUND';
        
        // Check export data with this forwarder code
        $exportData = \App\Models\ExportData::where('forwarder_code', $user->forwarder_code)->take(5)->get();
        $results['export_data_samples'] = $exportData->map(function($item) {
            return [
                'reference_invoice' => $item->reference_invoice,
                'forwarder_code' => $item->forwarder_code,
                'buyer' => $item->buyer
            ];
        });
        
        // Check cached instructions
        $allInstructions = cache()->get('all_workflow_instructions', []);
        $results['cached_instructions_total'] = count($allInstructions);
        
        $forwarderInstructions = array_filter($allInstructions, function($instruction) use ($user) {
            return isset($instruction['forwarder_code']) && $instruction['forwarder_code'] === $user->forwarder_code;
        });
        $results['forwarder_instructions'] = array_map(function($instruction) {
            return [
                'instruction_id' => $instruction['instruction_id'],
                'forwarder_code' => $instruction['forwarder_code'],
                'status' => $instruction['status'] ?? 'unknown',
                'pdf_generated' => $instruction['pdf_generated'] ?? false
            ];
        }, $forwarderInstructions);
        
        return response()->json($results, 200, [], JSON_PRETTY_PRINT);
    })->name('debug-forwarder-mapping');
});

// ========================================
// SYSTEM HEALTH CHECK
// ========================================

Route::get('/system-health', function () {
    try {
        $health = [
            'status' => 'healthy',
            'timestamp' => now()->toISOString(),
            'email_system' => [
                'smtp_configured' => !empty(config('mail.mailers.smtp.host')),
                'smtp_host' => config('mail.mailers.smtp.host'),
                'mail_driver' => config('mail.default'),
                'from_address' => config('mail.from.address')
            ],
            'storage_system' => [
                'pdf_directory_1' => [
                    'path' => storage_path('app/public/shipping_instructions'),
                    'exists' => is_dir(storage_path('app/public/shipping_instructions')),
                    'writable' => is_writable(storage_path('app/public/shipping_instructions'))
                ],
                'pdf_directory_2' => [
                    'path' => storage_path('app/public/kmi_shipping_instructions'),
                    'exists' => is_dir(storage_path('app/public/kmi_shipping_instructions')),
                    'writable' => is_writable(storage_path('app/public/kmi_shipping_instructions'))
                ]
            ],
            'cache_system' => [
                'driver' => config('cache.default'),
                'workflow_instructions_count' => count(cache()->get('all_workflow_instructions', [])),
                'working' => true
            ],
            'routes_registered' => [
                'send_notifications' => route('dashboard.send-container-booking-request', [], false),
                'generate_pdf' => route('dashboard.generate-shipping-instruction-pdf', [], false),
                'forwarder_dashboard' => route('forwarder.dashboard', [], false),
                'test_email' => route('test-send-email', [], false)
            ]
        ];
        
        // Auto-create PDF directories if missing
        $pdfDirs = [
            storage_path('app/public/shipping_instructions'),
            storage_path('app/public/kmi_shipping_instructions')
        ];
        
        foreach ($pdfDirs as $dir) {
            if (!is_dir($dir)) {
                try {
                    mkdir($dir, 0755, true);
                    $health['storage_system']['auto_created'][] = $dir;
                } catch (\Exception $e) {
                    $health['storage_system']['create_errors'][] = $e->getMessage();
                    $health['status'] = 'warning';
                }
            }
        }
        
        return response()->json($health);
        
    } catch (\Exception $e) {
        return response()->json([
            'status' => 'error',
            'error' => $e->getMessage(),
            'timestamp' => now()->toISOString()
        ], 500);
    }
})->name('system-health');

// ========================================
// LEGACY EXPORT ROUTES (maintained for compatibility)
// ========================================

Route::middleware(['auth'])->prefix('export')->name('export.')->group(function () {
    Route::get('/dashboard', [ExportController::class, 'dashboard'])->name('dashboard');
    Route::post('/get-forwarder-info-for-ref-invoice', [ExportController::class, 'getForwarderInfoForRefInvoice'])
        ->name('get-forwarder-info-for-ref-invoice');
});



Route::get('/test-wablas-config', function() {
    return response()->json([
        'wablas_api_url' => env('WABLAS_API_URL'),
        'api_key_configured' => !empty(env('WABLAS_API_KEY')),
        'secret_key_configured' => !empty(env('WABLAS_SECRET_KEY')),
        'current_ip' => request()->ip(),
        'timestamp' => now()
    ]);
})->middleware('auth');

Route::get('/test-wablas-connection', function() {
    try {
        $response = \Illuminate\Support\Facades\Http::withHeaders([
            'Authorization' => env('WABLAS_API_KEY'),
            'Content-Type' => 'application/json'
        ])->timeout(10)->get(env('WABLAS_API_URL') . '/api/device/status');
        
        return response()->json([
            'success' => $response->successful(),
            'status_code' => $response->status(),
            'response' => $response->json(),
            'ip_whitelisted' => true
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'error' => $e->getMessage()
        ]);
    }
})->middleware('auth');



// ========================================
// FALLBACK ROUTE
// ========================================

Route::fallback(function () {
    if (request()->expectsJson()) {
        return response()->json([
            'success' => false,
            'error' => 'Endpoint not found',
            'available_endpoints' => [
                'export_dashboard' => '/export',
                'generate_pdf' => '/dashboard/generate-shipping-instruction-pdf',
                'send_notifications' => '/dashboard/send-container-booking-request',
                'forwarder_dashboard' => '/forwarder/dashboard',
                'test_email' => '/test-send-email',
                'system_health' => '/system-health'
            ]
        ], 404);
    }
    
    return redirect('/');
});

