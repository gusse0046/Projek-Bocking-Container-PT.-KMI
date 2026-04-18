<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ExportData;
use Illuminate\Support\Facades\Http;

class CleanupAndResync extends Command
{
    protected $signature = 'sap:cleanup-and-resync 
                            {--force : Force cleanup without confirmation}
                            {--dry-run : Show what would be deleted without actually deleting}';

    protected $description = 'Clean up incomplete records and resync with complete field validation';

    public function handle()
    {
        $this->info('SAP Data Cleanup and Complete Resync Process');
        $this->newLine();

        // Step 1: Analyze incomplete data
        $incompleteRecords = $this->analyzeIncompleteData();

        // Step 2: Clean up incomplete records
        if ($this->option('dry-run')) {
            $this->showCleanupPreview($incompleteRecords);
        } else {
            $this->performCleanup($incompleteRecords);
            // Step 3: Fresh sync with complete data validation
            $this->performFreshSync();
        }

        return Command::SUCCESS;
    }

    private function analyzeIncompleteData()
    {
        $this->info('=== ANALYZING INCOMPLETE DATA ===');

        // Define required fields that should not be empty
        $requiredFields = [
            'delivery_type',
            'delivery_classification', 
            'sap_delivery_status',
            'sap_customer_number',
            'sap_sales_unit',
            'sap_weight_unit',
            'sap_volume_unit'
        ];

        $totalRecords = ExportData::count();
        $incompleteConditions = [];

        $this->info('Field completeness analysis:');
        
        foreach ($requiredFields as $field) {
            $nullCount = ExportData::whereNull($field)->count();
            $emptyCount = ExportData::where($field, '')->count();
            $incompleteCount = $nullCount + $emptyCount;
            
            $status = $incompleteCount > 0 ? '❌' : '✅';
            $this->line(sprintf('%s %-25s: %d NULL, %d EMPTY (%s incomplete)', 
                $status, $field, $nullCount, $emptyCount, $incompleteCount));

            if ($incompleteCount > 0) {
                $incompleteConditions[] = [
                    'field' => $field,
                    'count' => $incompleteCount
                ];
            }
        }

        // Find records that are incomplete in multiple ways
        $incompleteRecords = ExportData::where(function($query) use ($requiredFields) {
            foreach ($requiredFields as $field) {
                $query->orWhereNull($field)->orWhere($field, '');
            }
        })->get();

        $this->newLine();
        $this->table(
            ['Status', 'Count', 'Percentage'],
            [
                ['Total Records', $totalRecords, '100%'],
                ['Complete Records', $totalRecords - $incompleteRecords->count(), $this->percentage($totalRecords - $incompleteRecords->count(), $totalRecords)],
                ['Incomplete Records', $incompleteRecords->count(), $this->percentage($incompleteRecords->count(), $totalRecords)]
            ]
        );

        return $incompleteRecords;
    }

    private function showCleanupPreview($incompleteRecords)
    {
        $this->warn('=== DRY RUN - CLEANUP PREVIEW ===');
        $this->line("Would delete {$incompleteRecords->count()} incomplete records");
        
        if ($incompleteRecords->count() > 0) {
            $this->newLine();
            $this->info('Sample incomplete records that would be deleted:');
            
            $sample = $incompleteRecords->take(5);
            foreach ($sample as $record) {
                $issues = [];
                if (empty($record->delivery_type)) $issues[] = 'no delivery_type';
                if (empty($record->delivery_classification) || $record->delivery_classification === 'UNKNOWN') $issues[] = 'no classification';
                if (empty($record->sap_delivery_status)) $issues[] = 'no SAP status';
                if (empty($record->sap_customer_number)) $issues[] = 'no customer number';
                
                $this->line("  Delivery {$record->delivery}: " . implode(', ', $issues));
            }
            
            if ($incompleteRecords->count() > 5) {
                $this->line("  ... and " . ($incompleteRecords->count() - 5) . " more records");
            }
        }

        $this->newLine();
        $this->comment('Run without --dry-run to perform actual cleanup and resync');
    }

    private function performCleanup($incompleteRecords)
    {
        if ($incompleteRecords->count() === 0) {
            $this->info('No incomplete records found. Skipping cleanup.');
            return;
        }

        if (!$this->option('force')) {
            if (!$this->confirm("Delete {$incompleteRecords->count()} incomplete records?")) {
                $this->info('Cleanup cancelled.');
                return;
            }
        }

        $this->info('=== PERFORMING CLEANUP ===');
        
        $progressBar = $this->output->createProgressBar($incompleteRecords->count());
        $progressBar->start();

        $deleted = 0;
        foreach ($incompleteRecords as $record) {
            try {
                $record->delete();
                $deleted++;
            } catch (\Exception $e) {
                $this->error("Failed to delete record {$record->id}: " . $e->getMessage());
            }
            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine();
        $this->info("Successfully deleted {$deleted} incomplete records");
    }

    private function performFreshSync()
    {
        $this->info('=== PERFORMING FRESH SYNC WITH VALIDATION ===');
        
        try {
            // Call SAP sync command
            $this->call('sap:sync-export-data', ['--force' => true]);
            
            $this->newLine();
            $this->info('=== POST-SYNC VALIDATION ===');
            
            // Validate results
            $this->validateSyncResults();
            
        } catch (\Exception $e) {
            $this->error('Fresh sync failed: ' . $e->getMessage());
        }
    }

    private function validateSyncResults()
    {
        $requiredFields = [
            'delivery_type',
            'delivery_classification', 
            'sap_delivery_status',
            'sap_customer_number',
            'sap_sales_unit',
            'sap_weight_unit',
            'sap_volume_unit'
        ];

        $totalRecords = ExportData::count();
        $validationResults = [];

        foreach ($requiredFields as $field) {
            $completeCount = ExportData::whereNotNull($field)
                                    ->where($field, '!=', '')
                                    ->where($field, '!=', 'UNKNOWN')
                                    ->count();
            
            $validationResults[] = [
                $field,
                $completeCount,
                $this->percentage($completeCount, $totalRecords),
                $completeCount === $totalRecords ? '✅' : '❌'
            ];
        }

        $this->table(
            ['Field', 'Complete Count', 'Percentage', 'Status'],
            $validationResults
        );

        // Show delivery type distribution after sync
        $this->newLine();
        $this->info('=== DELIVERY TYPE DISTRIBUTION AFTER SYNC ===');
        
        $deliveryTypes = ExportData::selectRaw('delivery_type, delivery_classification, COUNT(*) as count')
                                 ->whereNotNull('delivery_type')
                                 ->where('delivery_type', '!=', '')
                                 ->groupBy('delivery_type', 'delivery_classification')
                                 ->orderBy('count', 'desc')
                                 ->get();

        $distributionData = [];
        foreach ($deliveryTypes as $type) {
            $distributionData[] = [
                $type->delivery_type,
                $type->delivery_classification,
                $type->count
            ];
        }

        $this->table(['Delivery Type', 'Classification', 'Count'], $distributionData);

        // Final summary
        $completeRecords = ExportData::whereNotNull('delivery_type')
                                   ->where('delivery_type', '!=', '')
                                   ->where('delivery_classification', '!=', 'UNKNOWN')
                                   ->whereNotNull('sap_delivery_status')
                                   ->whereNotNull('sap_customer_number')
                                   ->count();

        $this->newLine();
        if ($completeRecords === $totalRecords) {
            $this->info("✅ SUCCESS: All {$totalRecords} records are now complete!");
        } else {
            $this->warn("⚠️  WARNING: {$completeRecords}/{$totalRecords} records are complete");
            $this->comment('Some records may still need manual review');
        }
    }

    private function percentage($value, $total)
    {
        return $total > 0 ? round(($value / $total) * 100, 1) . '%' : '0%';
    }
}