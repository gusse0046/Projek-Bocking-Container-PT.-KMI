<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // ==========================================
        // SAP EXPORT DATA SYNC - DAILY AUTOMATION AT 5 AM
        // ==========================================
        
        // Main daily SAP sync at 5:00 AM (before work hours)
        $schedule->command('sap:sync-export-data --force')
            ->dailyAt('05:00')
            ->timezone('Asia/Jakarta')
            ->emailOutputOnFailure('admin@company.com')
            ->appendOutputTo(storage_path('logs/sap-sync-daily.log'))
            ->withoutOverlapping(120) // Prevent overlapping runs (120 min max)
            ->runInBackground()
            ->description('Daily SAP Export Data Sync - 5 AM Auto')
            ->onSuccess(function () {
                \Log::info('Daily SAP Export Sync completed successfully at ' . now());
                
                // Cache sync success status with detailed info
                $syncStats = cache()->get('export_last_sync_stats', []);
                cache()->put('daily_sap_sync_status', [
                    'status' => 'success',
                    'last_run' => now(),
                    'next_run' => now()->addDay()->setTime(5, 0),
                    'records_processed' => $syncStats['total'] ?? 0,
                    'records_updated' => $syncStats['updated'] ?? 0,
                    'records_new' => $syncStats['new'] ?? 0,
                    'execution_type' => 'automatic_daily'
                ], now()->addDays(2));
                
                \Log::info('Daily sync status cached successfully');
            })
            ->onFailure(function () {
                \Log::error('Daily SAP Export Sync failed at ' . now());
                
                // Cache sync failure status
                cache()->put('daily_sap_sync_status', [
                    'status' => 'failed',
                    'last_run' => now(),
                    'next_run' => now()->addDay()->setTime(5, 0),
                    'error_logged' => true,
                    'execution_type' => 'automatic_daily_failed'
                ], now()->addDays(2));
                
                // Send notification to admin
                try {
                    \Notification::route('mail', 'admin@company.com')
                        ->notify(new \App\Notifications\SapSyncFailedNotification([
                            'sync_time' => now(),
                            'sync_type' => 'Daily Automatic',
                            'next_attempt' => now()->addDay()->setTime(5, 0)
                        ]));
                } catch (\Exception $e) {
                    \Log::error('Failed to send sync failure notification: ' . $e->getMessage());
                }
            });

        // Weekly cleanup before daily sync (Sundays at 4:30 AM)
        $schedule->command('sap:cleanup-and-resync --force')
            ->weekly()
            ->sundays()
            ->at('04:30')
            ->timezone('Asia/Jakarta')
            ->appendOutputTo(storage_path('logs/sap-cleanup-weekly.log'))
            ->withoutOverlapping()
            ->description('Weekly SAP Data Cleanup - Before Daily Sync')
            ->when(function () {
                // Only run cleanup if there are many incomplete records
                $incompleteCount = \App\Models\ExportData::where(function($q) {
                    $q->whereNull('delivery_type')
                      ->orWhere('delivery_type', '')
                      ->orWhere('delivery_classification', 'UNKNOWN')
                      ->orWhereNull('sap_delivery_status');
                })->count();
                
                \Log::info("Weekly cleanup check: {$incompleteCount} incomplete records found");
                return $incompleteCount > 50; // Only cleanup if more than 50 incomplete records
            })
            ->onSuccess(function () {
                \Log::info('Weekly SAP data cleanup completed successfully');
                cache()->put('weekly_cleanup_status', [
                    'last_run' => now(),
                    'status' => 'success',
                    'next_run' => now()->addWeek()->setTime(4, 30)
                ], now()->addWeek());
            });

        // Backup sync at 2:00 PM on weekdays (optional fallback)
        $schedule->command('sap:sync-export-data')
            ->dailyAt('14:00')
            ->timezone('Asia/Jakarta')
            ->weekdays()
            ->appendOutputTo(storage_path('logs/sap-sync-afternoon.log'))
            ->withoutOverlapping(30)
            ->runInBackground()
            ->description('Afternoon SAP Export Data Sync - Backup')
            ->when(function () {
                // Only run if morning sync failed or hasn't run today
                $morningStatus = cache()->get('daily_sap_sync_status');
                $needsBackup = !$morningStatus || 
                              $morningStatus['status'] === 'failed' || 
                              !isset($morningStatus['last_run']) ||
                              !\Carbon\Carbon::parse($morningStatus['last_run'])->isToday();
                
                if ($needsBackup) {
                    \Log::info('Afternoon backup sync triggered - morning sync failed or missed');
                }
                
                return $needsBackup;
            });

        // Weekend sync (lighter schedule)
        $schedule->command('sap:sync-export-data --timeout=60')
            ->weekends()
            ->at('08:00')
            ->timezone('Asia/Jakarta')
            ->appendOutputTo(storage_path('logs/sap-sync-weekend.log'))
            ->withoutOverlapping()
            ->description('Weekend SAP Export Data Sync');

        // ==========================================
        // SAP CONNECTION HEALTH CHECK
        // ==========================================
        
        // Health check every 2 hours during business hours
        $schedule->call(function () {
            try {
                $response = \Http::timeout(30)->get('http://127.0.0.1:5023/api/export-health-check');
                
                if ($response->successful()) {
                    $data = $response->json();
                    \Log::info('SAP API Health Check: OK', [
                        'service' => $data['service'] ?? 'Unknown',
                        'version' => $data['version'] ?? 'Unknown',
                        'response_time' => $data['response_time'] ?? 'Unknown'
                    ]);
                    
                    // Cache health status with detailed info
                    cache()->put('sap_health_status', [
                        'status' => 'healthy',
                        'last_check' => now(),
                        'response_time' => $data['response_time'] ?? 'good',
                        'service_info' => [
                            'name' => $data['service'] ?? 'SAP Export Data API',
                            'version' => $data['version'] ?? '1.1.0',
                            'rfc_function' => $data['rfc_function'] ?? 'Z_FM_EXIM'
                        ],
                        'api_features' => $data['features'] ?? []
                    ], now()->addHours(3));
                    
                } else {
                    \Log::warning('SAP API Health Check: Failed', [
                        'status' => $response->status(),
                        'body' => $response->body()
                    ]);
                    
                    cache()->put('sap_health_status', [
                        'status' => 'unhealthy',
                        'last_check' => now(),
                        'error' => 'HTTP ' . $response->status(),
                        'response_body' => $response->body()
                    ], now()->addHours(3));
                }
            } catch (\Exception $e) {
                \Log::error('SAP API Health Check: Connection Error', [
                    'error' => $e->getMessage()
                ]);
                
                cache()->put('sap_health_status', [
                    'status' => 'connection_error',
                    'last_check' => now(),
                    'error' => $e->getMessage()
                ], now()->addHours(3));
            }
        })
        ->hourly()
        ->between('07:00', '18:00')
        ->weekdays()
        ->name('sap-health-check')
        ->description('SAP API Health Check - Business Hours');

        // ==========================================
        // SYNC STATUS MONITORING AND ALERTS
        // ==========================================
        
        // Check sync status and send alerts if sync hasn't run
        $schedule->call(function () {
            try {
                $lastSyncStatus = cache()->get('daily_sap_sync_status');
                $currentHour = now()->hour;
                
                // Check if it's past 6 AM and sync hasn't run today
                if ($currentHour >= 6) {
                    $shouldHaveRun = true;
                    $syncRanToday = false;
                    
                    if ($lastSyncStatus && isset($lastSyncStatus['last_run'])) {
                        $lastRun = \Carbon\Carbon::parse($lastSyncStatus['last_run']);
                        $syncRanToday = $lastRun->isToday() && $lastRun->hour <= 6;
                    }
                    
                    if ($shouldHaveRun && !$syncRanToday) {
                        \Log::warning('Daily SAP sync alert: Sync has not run today after 6 AM', [
                            'current_time' => now(),
                            'last_sync_status' => $lastSyncStatus
                        ]);
                        
                        // Send alert notification
                        try {
                            \Notification::route('mail', 'admin@company.com')
                                ->notify(new \App\Notifications\SapSyncMissedNotification([
                                    'expected_time' => '05:00',
                                    'current_time' => now(),
                                    'last_sync' => $lastSyncStatus['last_run'] ?? 'Never'
                                ]));
                        } catch (\Exception $e) {
                            \Log::error('Failed to send missed sync notification: ' . $e->getMessage());
                        }
                    }
                }
                
            } catch (\Exception $e) {
                \Log::error('Sync status monitoring failed: ' . $e->getMessage());
            }
        })
        ->hourly()
        ->between('06:00', '09:00')
        ->name('sync-status-monitor')
        ->description('Monitor Daily Sync Status and Send Alerts');

        // ==========================================
        // DELIVERY TYPE STATISTICS TRACKING
        // ==========================================
        
        // Track delivery type statistics daily
        $schedule->call(function () {
            try {
                $stats = [
                    'total_records' => \App\Models\ExportData::count(),
                    'by_delivery_type' => \App\Models\ExportData::selectRaw('delivery_type, COUNT(*) as count, delivery_classification')
                                                              ->whereNotNull('delivery_type')
                                                              ->where('delivery_type', '!=', '')
                                                              ->groupBy('delivery_type', 'delivery_classification')
                                                              ->get()
                                                              ->map(function($item) {
                                                                  return [
                                                                      'type' => $item->delivery_type,
                                                                      'classification' => $item->delivery_classification,
                                                                      'count' => $item->count
                                                                  ];
                                                              })
                                                              ->toArray(),
                    'by_classification' => \App\Models\ExportData::selectRaw('delivery_classification, COUNT(*) as count')
                                                                 ->groupBy('delivery_classification')
                                                                 ->pluck('count', 'delivery_classification')
                                                                 ->toArray(),
                    'incomplete_records' => \App\Models\ExportData::where(function($q) {
                                                                      $q->whereNull('delivery_type')
                                                                        ->orWhere('delivery_type', '')
                                                                        ->orWhere('delivery_classification', 'UNKNOWN');
                                                                  })->count(),
                    'generated_at' => now()
                ];
                
                cache()->put('delivery_type_daily_stats', $stats, now()->addDays(2));
                \Log::info('Daily delivery type statistics updated', [
                    'total_records' => $stats['total_records'],
                    'incomplete_records' => $stats['incomplete_records']
                ]);
                
            } catch (\Exception $e) {
                \Log::error('Delivery type statistics tracking failed: ' . $e->getMessage());
            }
        })
        ->dailyAt('06:30')
        ->timezone('Asia/Jakarta')
        ->name('delivery-type-stats')
        ->description('Daily Delivery Type Statistics Tracking');

        // ==========================================
        // WORKFLOW MAINTENANCE TASKS
        // ==========================================
        
        // Clean up old workflow cache data (older than 30 days)
        $schedule->call(function () {
            try {
                $allInstructions = cache()->get('all_workflow_instructions', []);
                $cleaned = 0;
                
                foreach ($allInstructions as $id => $instruction) {
                    $createdAt = \Carbon\Carbon::parse($instruction['created_at']);
                    
                    if ($createdAt->diffInDays(now()) > 30) {
                        cache()->forget("workflow_instruction_{$id}");
                        unset($allInstructions[$id]);
                        $cleaned++;
                    }
                }
                
                if ($cleaned > 0) {
                    cache()->put('all_workflow_instructions', $allInstructions, now()->addDays(30));
                    \Log::info("Cleaned up {$cleaned} old workflow instructions");
                }
                
                // Cache cleanup statistics
                cache()->put('workflow_cleanup_stats', [
                    'last_cleanup' => now(),
                    'items_cleaned' => $cleaned,
                    'remaining_items' => count($allInstructions)
                ], now()->addWeek());
                
            } catch (\Exception $e) {
                \Log::error('Workflow cleanup failed: ' . $e->getMessage());
            }
        })
        ->weekly()
        ->sundays()
        ->at('02:00')
        ->name('cleanup-workflow')
        ->description('Weekly Workflow Cache Cleanup');

        // ==========================================
        // LOG MAINTENANCE
        // ==========================================
        
        // Rotate SAP sync logs weekly
        $schedule->call(function () {
            try {
                $logFiles = [
                    storage_path('logs/sap-sync-daily.log'),
                    storage_path('logs/sap-sync-afternoon.log'),
                    storage_path('logs/sap-sync-weekend.log'),
                    storage_path('logs/sap-cleanup-weekly.log')
                ];
                
                $rotatedCount = 0;
                
                foreach ($logFiles as $logFile) {
                    if (file_exists($logFile) && filesize($logFile) > 10 * 1024 * 1024) { // 10MB
                        $backupFile = $logFile . '.' . date('Y-m-d');
                        if (rename($logFile, $backupFile)) {
                            \Log::info("Rotated log file: {$logFile}");
                            $rotatedCount++;
                        }
                    }
                }
                
                if ($rotatedCount > 0) {
                    \Log::info("Log rotation completed. {$rotatedCount} files rotated.");
                }
                
            } catch (\Exception $e) {
                \Log::error('Log rotation failed: ' . $e->getMessage());
            }
        })
        ->weekly()
        ->mondays()
        ->at('01:00')
        ->name('rotate-logs')
        ->description('Weekly Log Rotation');

        // ==========================================
        // FORWARDER NOTIFICATION DIGEST
        // ==========================================
        
        // Send daily digest to forwarders about pending instructions
        $schedule->call(function () {
            try {
                $forwarders = \App\Models\User::where('role', 'forwarder')->get();
                $digestStats = [
                    'forwarders_processed' => 0,
                    'notifications_sent' => 0,
                    'total_pending_instructions' => 0
                ];
                
                foreach ($forwarders as $forwarder) {
                    try {
                        $dashboardController = app(\App\Http\Controllers\DashboardController::class);
                        
                        // Get pending instructions for this forwarder
                        $request = new \Illuminate\Http\Request();
                        $request->merge(['forwarder_code' => $forwarder->forwarder_code]);
                        
                        $activeInstructionsResponse = $dashboardController->getActiveInstructions($request);
                        
                        if ($activeInstructionsResponse instanceof \Illuminate\Http\JsonResponse) {
                            $responseData = $activeInstructionsResponse->getData(true);
                            $instructions = $responseData['instructions'] ?? [];
                        } else {
                            $instructions = $activeInstructionsResponse['instructions'] ?? [];
                        }
                        
                        $pendingCount = count($instructions);
                        $digestStats['forwarders_processed']++;
                        $digestStats['total_pending_instructions'] += $pendingCount;
                        
                        if ($pendingCount > 0) {
                            \Log::info("Forwarder {$forwarder->email} has {$pendingCount} pending instructions");
                            $digestStats['notifications_sent']++;
                            
                            // TODO: Implement actual email sending
                            // \Mail::to($forwarder->email)->send(new \App\Mail\ForwarderDailyDigest($instructions));
                        }
                        
                    } catch (\Exception $e) {
                        \Log::error("Failed to process digest for forwarder {$forwarder->email}: " . $e->getMessage());
                    }
                }
                
                \Log::info('Forwarder digest completed', $digestStats);
                
                // Cache digest statistics
                cache()->put('forwarder_digest_stats', [
                    'last_run' => now(),
                    'statistics' => $digestStats
                ], now()->addDays(2));
                
            } catch (\Exception $e) {
                \Log::error('Forwarder digest failed: ' . $e->getMessage());
            }
        })
        ->dailyAt('08:00')
        ->weekdays()
        ->name('forwarder-digest')
        ->description('Daily Forwarder Instruction Digest');

        // ==========================================
        // DATABASE MAINTENANCE
        // ==========================================
        
        // Clean up old export data (older than 1 year) - optional
        $schedule->call(function () {
            try {
                $oldDataCount = \App\Models\ExportData::where('created_at', '<', now()->subYear())->count();
                
                if ($oldDataCount > 0) {
                    $deleted = \App\Models\ExportData::where('created_at', '<', now()->subYear())->delete();
                    \Log::info("Archived {$deleted} old export data records");
                    
                    // Cache cleanup statistics
                    cache()->put('database_cleanup_stats', [
                        'last_cleanup' => now(),
                        'records_archived' => $deleted,
                        'cleanup_type' => 'yearly_archive'
                    ], now()->addMonth());
                }
                
            } catch (\Exception $e) {
                \Log::error('Database cleanup failed: ' . $e->getMessage());
            }
        })
        ->monthly()
        ->name('archive-export-data')
        ->description('Monthly Export Data Archival');

        // ==========================================
        // SYSTEM MONITORING AND BACKUP REMINDERS
        // ==========================================
        
        // System health check and backup reminder
        $schedule->call(function () {
            try {
                $systemStats = [
                    'total_export_records' => \App\Models\ExportData::count(),
                    'total_workflow_instructions' => count(cache()->get('all_workflow_instructions', [])),
                    'last_sap_sync' => cache()->get('export_last_sap_sync'),
                    'daily_sync_status' => cache()->get('daily_sap_sync_status'),
                    'sap_health' => cache()->get('sap_health_status', ['status' => 'unknown']),
                    'storage_usage' => $this->getStorageUsage(),
                    'delivery_type_stats' => cache()->get('delivery_type_daily_stats')
                ];
                
                \Log::info('WEEKLY SYSTEM HEALTH CHECK & BACKUP REMINDER', [
                    'reminder' => 'Perform database backup and verify system health',
                    'system_stats' => $systemStats,
                    'recommended_actions' => [
                        'Backup database',
                        'Verify SAP connection',
                        'Check sync automation',
                        'Review delivery type distribution',
                        'Monitor log file sizes',
                        'Check email functionality'
                    ]
                ]);
                
                // Cache backup reminder with comprehensive stats
                cache()->put('system_health_stats', [
                    'last_check' => now(),
                    'system_statistics' => $systemStats,
                    'health_indicators' => [
                        'sap_connection' => $systemStats['sap_health']['status'] ?? 'unknown',
                        'daily_sync' => $systemStats['daily_sync_status']['status'] ?? 'unknown',
                        'data_completeness' => isset($systemStats['delivery_type_stats']['incomplete_records']) 
                            ? ($systemStats['delivery_type_stats']['incomplete_records'] < 10 ? 'good' : 'needs_attention')
                            : 'unknown'
                    ]
                ], now()->addWeek());
                
            } catch (\Exception $e) {
                \Log::error('System health check failed: ' . $e->getMessage());
            }
        })
        ->weekly()
        ->fridays()
        ->at('17:00')
        ->name('system-health-check')
        ->description('Weekly System Health Check & Backup Reminder');

        // ==========================================
        // CACHE OPTIMIZATION
        // ==========================================
        
        // Weekly cache optimization
        $schedule->call(function () {
            try {
                \Log::info('Starting weekly cache optimization...');
                
                // Clear old cache entries
                $keysCleared = 0;
                $oldCacheKeys = [
                    'old_sap_sync_*',
                    'temp_export_*',
                    'expired_dashboard_*',
                    'old_workflow_*'
                ];
                
                foreach ($oldCacheKeys as $pattern) {
                    try {
                        cache()->forget($pattern);
                        $keysCleared++;
                    } catch (\Exception $e) {
                        // Continue if individual cache deletion fails
                    }
                }
                
                \Log::info("Cache optimization completed. {$keysCleared} cache patterns cleared.");
                
                // Cache optimization statistics
                cache()->put('cache_optimization_stats', [
                    'last_optimization' => now(),
                    'patterns_cleared' => $keysCleared,
                    'next_optimization' => now()->addWeek()->setTime(3, 0)
                ], now()->addWeek());
                
            } catch (\Exception $e) {
                \Log::error('Cache optimization failed: ' . $e->getMessage());
            }
        })
        ->weekly()
        ->saturdays()
        ->at('03:00')
        ->name('cache-optimization')
        ->description('Weekly Cache Optimization');
    }

    /**
     * Helper method to get storage usage
     */
    private function getStorageUsage()
    {
        try {
            $storagePath = storage_path();
            $totalSpace = disk_total_space($storagePath);
            $freeSpace = disk_free_space($storagePath);
            $usedSpace = $totalSpace - $freeSpace;
            
            return [
                'total_gb' => round($totalSpace / (1024 * 1024 * 1024), 2),
                'used_gb' => round($usedSpace / (1024 * 1024 * 1024), 2),
                'free_gb' => round($freeSpace / (1024 * 1024 * 1024), 2),
                'usage_percent' => round(($usedSpace / $totalSpace) * 100, 2)
            ];
        } catch (\Exception $e) {
            return ['error' => 'Unable to get storage info'];
        }
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}