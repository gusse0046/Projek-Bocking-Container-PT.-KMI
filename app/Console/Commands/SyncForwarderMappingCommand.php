<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ExportData;
use App\Models\Forwarder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SyncForwarderMappingCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'forwarder:sync-mapping {--force : Force sync all records}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync forwarder mapping for all export data based on buyer names';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('Starting forwarder mapping synchronization...');
        
        $force = $this->option('force');
        
        // Get all active forwarders
        $forwarders = Forwarder::where('is_active', true)
                              ->whereNotNull('buyers')
                              ->get()
                              ->map(function($forwarder) {
                                  if (is_string($forwarder->buyers)) {
                                      $forwarder->buyers = json_decode($forwarder->buyers, true) ?? [];
                                  }
                                  return $forwarder;
                              });

        if ($forwarders->isEmpty()) {
            $this->error('No active forwarders found with buyer mappings');
            return Command::FAILURE;
        }

        $this->info("Found {$forwarders->count()} active forwarders");
        
        // Get export data to process
        $query = ExportData::whereNotNull('buyer')
                          ->where('buyer', '!=', '');
        
        if (!$force) {
            $query->where(function($q) {
                $q->whereNull('forwarder_code')
                  ->orWhere('forwarder_code', '')
                  ->orWhere('forwarder_code', 'like', 'CUSTOM_%');
            });
        }
        
        $exportData = $query->get();
        $this->info("Processing {$exportData->count()} export records...");
        
        $stats = [
            'processed' => 0,
            'mapped' => 0,
            'unmapped' => 0,
            'updated' => 0,
            'errors' => 0
        ];
        
        $progressBar = $this->output->createProgressBar($exportData->count());
        
        foreach ($exportData as $item) {
            $progressBar->advance();
            $stats['processed']++;
            
            try {
                $oldCode = $item->forwarder_code;
                $newCode = $this->determineForwarderCode($item, $forwarders);
                
                if ($newCode !== $oldCode) {
                    $item->update(['forwarder_code' => $newCode]);
                    $stats['updated']++;
                }
                
                if (str_starts_with($newCode, 'CUSTOM_') || $newCode === 'UNASSIGNED') {
                    $stats['unmapped']++;
                } else {
                    $stats['mapped']++;
                }
                
            } catch (\Exception $e) {
                $stats['errors']++;
                Log::error('Error processing export data item', [
                    'item_id' => $item->id,
                    'buyer' => $item->buyer,
                    'error' => $e->getMessage()
                ]);
            }
        }
        
        $progressBar->finish();
        $this->newLine(2);
        
        // Display results
        $this->info('Forwarder mapping synchronization completed!');
        $this->table(['Metric', 'Count'], [
            ['Processed', $stats['processed']],
            ['Updated', $stats['updated']],
            ['Mapped to Forwarders', $stats['mapped']],
            ['Unmapped (Custom)', $stats['unmapped']],
            ['Errors', $stats['errors']]
        ]);
        
        // Show sample mappings
        $this->showSampleMappings($forwarders);
        
        return Command::SUCCESS;
    }
    
    /**
     * Determine forwarder code for export item
     */
    private function determineForwarderCode($item, $forwarders)
    {
        if (!$item->buyer) {
            return 'UNASSIGNED';
        }

        $buyerName = trim($item->buyer);
        $buyerUpper = strtoupper($buyerName);

        foreach ($forwarders as $forwarder) {
            if (!is_array($forwarder->buyers)) {
                continue;
            }

            foreach ($forwarder->buyers as $mappedBuyer) {
                if ($this->isBuyerMatch($buyerUpper, $mappedBuyer)) {
                    return $forwarder->code;
                }
            }
        }

        return 'CUSTOM_' . strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $buyerName), 0, 8));
    }
    
    /**
     * Enhanced buyer matching logic
     */
    private function isBuyerMatch($buyerUpper, $mappedBuyer)
    {
        $mappedBuyerUpper = strtoupper(trim($mappedBuyer));
        
        // Exact match
        if ($buyerUpper === $mappedBuyerUpper) {
            return true;
        }
        
        // Normalized match
        $buyerNormalized = $this->normalizeBuyerName($buyerUpper);
        $mappedNormalized = $this->normalizeBuyerName($mappedBuyerUpper);
        
        if ($buyerNormalized === $mappedNormalized) {
            return true;
        }
        
        // Contains match
        if (strlen($mappedNormalized) >= 5) {
            if (strpos($buyerNormalized, $mappedNormalized) !== false || 
                strpos($mappedNormalized, $buyerNormalized) !== false) {
                return true;
            }
        }
        
        // Special cases
        return $this->handleSpecialCases($buyerUpper, $mappedBuyerUpper);
    }
    
    /**
     * Normalize buyer names
     */
    private function normalizeBuyerName($name)
    {
        $suffixes = [
            ', LLC', ' LLC', ', INC', ' INC', ', INC.', ' INC.', 
            ', CO.', ' CO.', ', COMPANY', ' COMPANY', ', CORP', ' CORP'
        ];
        
        $normalized = $name;
        foreach ($suffixes as $suffix) {
            $normalized = str_ireplace($suffix, '', $normalized);
        }
        
        return trim(preg_replace('/\s+/', ' ', preg_replace('/[^A-Z0-9\s]/', '', $normalized)));
    }
    
    /**
     * Handle special buyer cases
     */
    private function handleSpecialCases($buyerUpper, $mappedBuyerUpper)
    {
        // ACL cases
        $aclBuyers = [
            'ETHAN ALLEN OPERATIONS INC', 'ETHAN ALLEN', 'THE UTTERMOST CO.',
            'UTTERMOST', 'ETHAN ALLEN OPERATIONS, INC.', 'ETHAN ALLEN GLOBAL INC',
            'THE UTTERMOST CO', 'UTTERMOST CO.', 'UTTERMOST LLC'
        ];
        if (in_array($buyerUpper, $aclBuyers) && in_array($mappedBuyerUpper, $aclBuyers)) {
            return true;
        }

        // MGL cases (ESCALADE + BRUNSWICK)
        $mglBuyers = [
            'INDIAN INDUSTRIES DBA ESCALADE SPORTS', 'ESCALADE SPORTS', 'INDIAN INDUSTRIES',
            'BRUNSWICK BILLIARDS-LIFE FITNE', 'BRUNSWICK BILLIARDS', 'BRUNSWICK',
            'LIFE FITNESS', 'ESCALADE SPORTS LLC', 'BRUNSWICK CORPORATION'
        ];
        if (in_array($buyerUpper, $mglBuyers) && in_array($mappedBuyerUpper, $mglBuyers)) {
            return true;
        }

        // OSS cases
        $ossBuyers = ['VANGUARD FURNITURE', 'VANGUARD FURNITURE LLC'];
        if (in_array($buyerUpper, $ossBuyers) && in_array($mappedBuyerUpper, $ossBuyers)) {
            return true;
        }

        // RSL cases
        $rslBuyers = ['CENTURY FURNITURE', 'CENTURY FURNITURE LLC'];
        if (in_array($buyerUpper, $rslBuyers) && in_array($mappedBuyerUpper, $rslBuyers)) {
            return true;
        }

        // CNL cases
        $cnlBuyers = ['CRATE & BARREL', 'CRATE AND BARREL', 'CB2'];
        if (in_array($buyerUpper, $cnlBuyers) && in_array($mappedBuyerUpper, $cnlBuyers)) {
            return true;
        }

        // ESA cases
        $esaBuyers = [
            'WEST ELM', 'POTTERY BARN', 'WILLIAMS SONOMA',
            'ROWE FINE FURNITURE INC', 'ROWE FINE FURNITURE', 'ROWE FURNITURE', 'ROWE'
        ];
        if (in_array($buyerUpper, $esaBuyers) && in_array($mappedBuyerUpper, $esaBuyers)) {
            return true;
        }

        // EXP cases
        $expBuyers = ['LULU AND GEORGIA', 'LULU & GEORGIA', 'ARHAUS', 'ANTHROPOLOGIE'];
        if (in_array($buyerUpper, $expBuyers) && in_array($mappedBuyerUpper, $expBuyers)) {
            return true;
        }

        return false;
    }
    
    /**
     * Show sample mappings for verification
     */
    private function showSampleMappings($forwarders)
    {
        $this->info("\nSample Forwarder Mappings:");
        
        foreach ($forwarders->take(5) as $forwarder) {
            $buyersList = is_array($forwarder->buyers) ? 
                implode(', ', array_slice($forwarder->buyers, 0, 3)) : 
                'No buyers';
                
            $this->line("• {$forwarder->code}: {$forwarder->name}");
            $this->line("  Buyers: {$buyersList}" . (count($forwarder->buyers ?? []) > 3 ? '...' : ''));
        }
        
        // Check for unmapped buyers
        $unmappedBuyers = ExportData::select('buyer')
                                   ->distinct()
                                   ->whereNotNull('buyer')
                                   ->where('buyer', '!=', '')
                                   ->where(function($q) {
                                       $q->where('forwarder_code', 'like', 'CUSTOM_%')
                                         ->orWhere('forwarder_code', 'UNASSIGNED')
                                         ->orWhereNull('forwarder_code');
                                   })
                                   ->pluck('buyer')
                                   ->take(10);
        
        if ($unmappedBuyers->isNotEmpty()) {
            $this->warn("\nUnmapped Buyers (sample):");
            foreach ($unmappedBuyers as $buyer) {
                $this->line("• {$buyer}");
            }
        }
    }
}