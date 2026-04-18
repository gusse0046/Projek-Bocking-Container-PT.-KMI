<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class SyncSapExportData extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'sap:sync-export-data 
                            {--timeout=180 : API timeout in seconds}
                            {--endpoint=http://127.0.0.1:5023/api/export-data : SAP API endpoint}
                            {--force : Force sync even if already synced today}
                            {--test : Run in test mode without saving data}
                            {--debug : Show debug information}';

    /**
     * The console command description.
     */
    protected $description = 'Sync ALL Data from SAP RFC to SQL - No filtering, store everything with delivery_type field';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // Increase memory limit even more and set time limit
        ini_set('memory_limit', '2048M');
        ini_set('max_execution_time', 600);
        
        $this->info('Starting SAP RFC to SQL Data Sync...');
        $this->info('Note: All RFC data will be stored with delivery_type field - no filtering applied');
        $this->newLine();

        try {
            // Check if already synced today (unless forced)
            if (!$this->option('force') && $this->alreadySyncedToday()) {
                $this->warn('Data already synced today. Use --force to override.');
                return Command::SUCCESS;
            }

            // Test API connectivity
            $this->info('Testing SAP RFC API connectivity...');
            if (!$this->testApiConnection()) {
                $this->error('Failed to connect to SAP RFC API');
                return Command::FAILURE;
            }

            // Fetch ALL data from SAP RFC
            $this->info('Fetching ALL data from SAP RFC...');
            $sapData = $this->fetchSapData();

            if (empty($sapData)) {
                $this->warn('No new data received from SAP RFC');
                return Command::SUCCESS;
            }

            // Process and save ALL data to SQL using raw queries
            $this->info("Processing " . count($sapData) . " records from RFC to SQL...");
            $processed = $this->processSapDataRaw($sapData);

            // Update sync status
            $this->updateSyncStatus($processed);

            // Display results
            $this->displayResults($processed);

            $this->newLine();
            $this->info('SAP RFC to SQL Sync completed successfully!');

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error('SAP RFC Sync failed: ' . $e->getMessage());
            
            if ($this->option('debug')) {
                $this->error('Debug trace: ' . $e->getTraceAsString());
            }
            
            Log::error('SAP RFC Sync Command Failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return Command::FAILURE;
        }
    }

    /**
     * Test API connection
     */
    private function testApiConnection(): bool
    {
        try {
            $this->info('Testing connection to Python API...');
            
            $healthEndpoint = 'http://127.0.0.1:5023/api/export-health-check';
            
            if ($this->option('debug')) {
                $this->comment("Testing endpoint: {$healthEndpoint}");
            }
            
            $response = Http::timeout(30)
                ->withHeaders([
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json'
                ])
                ->get($healthEndpoint);
            
            if ($this->option('debug')) {
                $this->comment("Response status: {$response->status()}");
                $this->comment("Response body: " . $response->body());
            }
            
            if ($response->successful()) {
                $data = $response->json();
                
                if (isset($data['service'])) {
                    $this->info("Connected to: {$data['service']} v{$data['version']}");
                    $this->info("SAP Function: {$data['rfc_function']}");
                    return true;
                } else {
                    $this->info("API responding but different format");
                    return true;
                }
            }
            
            $this->error("API responded with HTTP {$response->status()}");
            return false;
            
        } catch (\Exception $e) {
            $this->error("Connection test failed: {$e->getMessage()}");
            return $this->tryAlternativeConnection();
        }
    }

    /**
     * Try alternative connection methods
     */
    private function tryAlternativeConnection(): bool
    {
        $this->info('Trying alternative connection...');
        
        $alternatives = [
            'http://127.0.0.1:5023/',
            'http://127.0.0.1:5023/health',
            'http://localhost:5023/api/export-health-check'
        ];
        
        foreach ($alternatives as $endpoint) {
            try {
                $this->comment("Testing: {$endpoint}");
                
                $response = Http::timeout(10)->get($endpoint);
                
                if ($response->successful()) {
                    $this->info("Alternative connection successful: {$endpoint}");
                    return true;
                }
                
            } catch (\Exception $e) {
                $this->comment("{$endpoint} failed: {$e->getMessage()}");
                continue;
            }
        }
        
        return false;
    }

    /**
     * Fetch ALL data from SAP RFC - store everything, no filtering
     */
    private function fetchSapData(): array
    {
        $endpoint = $this->option('endpoint');
        $timeout = $this->option('timeout');

        $this->info("Calling RFC endpoint: {$endpoint}");
        $this->info("Timeout: {$timeout} seconds");
        $this->info("Mode: Store ALL RFC data with delivery_type field");

        try {
            $response = Http::timeout($timeout)->get($endpoint, [
                'check' => 'X',
                'timeout' => $timeout
            ]);

            if (!$response->successful()) {
                throw new \Exception("SAP RFC API call failed: HTTP {$response->status()} - {$response->body()}");
            }

            $result = $response->json();

            if (!isset($result['status']) || $result['status'] !== 'success') {
                throw new \Exception("SAP RFC API returned error: " . ($result['error'] ?? 'Unknown error'));
            }

            $this->info("Response time: {$result['response_time']}");
            $this->info("Records received: {$result['total_records']}");

            if (isset($result['statistics'])) {
                $stats = $result['statistics'];
                $containerCount = isset($stats['container_count']) ? $stats['container_count'] : 0;
                $invoiceCount = isset($stats['invoice_count']) ? $stats['invoice_count'] : 0;
                $uniqueCustomers = isset($stats['unique_customers']) ? $stats['unique_customers'] : 0;
                
                $this->info("With container numbers: {$containerCount}");
                $this->info("With invoices: {$invoiceCount}");
                $this->info("Unique customers: {$uniqueCustomers}");
                
                // Show delivery type variety if available
                if (isset($stats['delivery_types'])) {
                    $this->info("Delivery types found: " . implode(', ', $stats['delivery_types']));
                }
            }

            return $result['data'] ?? [];
            
        } catch (\Exception $e) {
            $this->error("Failed to fetch SAP RFC data: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Process SAP data using raw database operations to minimize memory usage
     */
    private function processSapDataRaw(array $sapData): array
    {
        $processed = [
            'total' => count($sapData),
            'new' => 0,
            'updated' => 0,
            'skipped' => 0,
            'errors' => 0
        ];

        $batchSize = 25; // Smaller batch size
        $batches = array_chunk($sapData, $batchSize);
        
        $this->info("Processing {$processed['total']} records in " . count($batches) . " batches of {$batchSize} using raw SQL");

        foreach ($batches as $batchIndex => $batch) {
            $this->info("Processing batch " . ($batchIndex + 1) . "/" . count($batches));
            
            try {
                if ($this->option('test')) {
                    // Test mode - just validate
                    foreach ($batch as $record) {
                        $this->validateSapRecord($record);
                        $processed['new']++;
                    }
                } else {
                    $batchResult = $this->processBatchRaw($batch);
                    $processed['new'] += $batchResult['new'];
                    $processed['updated'] += $batchResult['updated'];
                    $processed['errors'] += $batchResult['errors'];
                }
                
                // Force cleanup after each batch
                unset($batch);
                gc_collect_cycles();
                
                // Show memory usage
                $memoryUsage = round(memory_get_usage(true) / 1024 / 1024, 2);
                $this->comment("Memory usage after batch: {$memoryUsage} MB");
                
            } catch (\Exception $e) {
                $this->error("Batch processing failed: " . $e->getMessage());
                $processed['errors'] += count($batch);
            }
        }

        return $processed;
    }

    /**
     * Process a batch using raw SQL operations
     */
    private function processBatchRaw(array $batch): array
    {
        $result = ['new' => 0, 'updated' => 0, 'errors' => 0];
        
        foreach ($batch as $record) {
            try {
                $data = $this->mapSapToRawData($record);
                
                // Check if record exists using raw query
                $existing = DB::selectOne(
                    "SELECT id FROM export_data WHERE delivery = ? AND no_item = ? LIMIT 1",
                    [$data['delivery'], $data['no_item']]
                );
                
                if ($existing) {
                    // Update using raw query
                    DB::update(
                        "UPDATE export_data SET 
                            material = ?, description = ?, buyer = ?, quantity = ?, volume = ?, weight = ?,
                            container_number = ?, reference_invoice = ?, delivery_date = ?, created_date = ?,
                            sap_delivery_status = ?, sap_customer_number = ?, sap_sales_unit = ?, 
                            sap_weight_unit = ?, sap_volume_unit = ?, delivery_type = ?, 
                            delivery_classification = ?, operation_location = ?, sap_synced_at = ?, updated_at = ?
                         WHERE delivery = ? AND no_item = ?",
                        [
                            $data['material'], $data['description'], $data['buyer'], $data['quantity'], 
                            $data['volume'], $data['weight'], $data['container_number'], $data['reference_invoice'],
                            $data['delivery_date'], $data['created_date'], $data['sap_delivery_status'], 
                            $data['sap_customer_number'], $data['sap_sales_unit'], $data['sap_weight_unit'], 
                            $data['sap_volume_unit'], $data['delivery_type'], $data['delivery_classification'], 
                            $data['operation_location'], $data['sap_synced_at'], $data['updated_at'],
                            $data['delivery'], $data['no_item']
                        ]
                    );
                    $result['updated']++;
                } else {
                    // Insert using raw query
                    DB::insert(
                        "INSERT INTO export_data (
                            delivery, no_item, material, description, buyer, quantity, volume, weight,
                            container_number, reference_invoice, delivery_date, created_date,
                            sap_delivery_status, sap_customer_number, sap_sales_unit, sap_weight_unit, 
                            sap_volume_unit, delivery_type, delivery_classification, operation_location,
                            proforma_shipping_instruction, export_destination, forwarder_code,
                            route_destination, business_unit, sap_synced_at, created_at, updated_at
                        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
                        [
                            $data['delivery'], $data['no_item'], $data['material'], $data['description'], 
                            $data['buyer'], $data['quantity'], $data['volume'], $data['weight'],
                            $data['container_number'], $data['reference_invoice'], $data['delivery_date'], 
                            $data['created_date'], $data['sap_delivery_status'], $data['sap_customer_number'], 
                            $data['sap_sales_unit'], $data['sap_weight_unit'], $data['sap_volume_unit'], 
                            $data['delivery_type'], $data['delivery_classification'], $data['operation_location'],
                            $data['proforma_shipping_instruction'], $data['export_destination'], 
                            $data['forwarder_code'], $data['route_destination'], $data['business_unit'],
                            $data['sap_synced_at'], $data['created_at'], $data['updated_at']
                        ]
                    );
                    $result['new']++;
                }
                
            } catch (\Exception $e) {
                $result['errors']++;
                if ($this->option('debug')) {
                    $this->error("Error in record: " . $e->getMessage());
                }
            }
        }
        
        return $result;
    }

    /**
     * Map SAP data to raw array format
     */
    private function mapSapToRawData(array $sapRecord): array
    {
        $deliveryType = trim($sapRecord['Delivery Type'] ?? '');
        $now = date('Y-m-d H:i:s');
        
        return [
            'delivery' => trim($sapRecord['Delivery Number'] ?? ''),
            'no_item' => trim($sapRecord['Item Number'] ?? '10'),
            'material' => trim($sapRecord['Material Number'] ?? ''),
            'description' => trim($sapRecord['Material Description'] ?? ''),
            'buyer' => trim($sapRecord['Customer Name'] ?? ''),
            'quantity' => $this->cleanNumeric($sapRecord['Delivery Quantity'] ?? 0),
            'volume' => $this->cleanNumeric($sapRecord['Volume'] ?? 0),
            'weight' => $this->cleanNumeric($sapRecord['Gross Weight'] ?? 0),
            'container_number' => $this->cleanFieldAllowEmpty($sapRecord['Container Number'] ?? ''),
            'reference_invoice' => trim($sapRecord['Reference Invoice'] ?? ''),
            'delivery_date' => $this->parseDateSimple($sapRecord['Planned GI Date'] ?? ''),
            'created_date' => $this->parseDateSimple($sapRecord['Actual GI Date'] ?? ''),
            'sap_delivery_status' => trim($sapRecord['Goods Movement Status'] ?? ''),
            'sap_customer_number' => trim($sapRecord['Customer Number'] ?? ''),
            'sap_sales_unit' => trim($sapRecord['Sales Unit'] ?? ''),
            'sap_weight_unit' => trim($sapRecord['Weight Unit'] ?? ''),
            'sap_volume_unit' => trim($sapRecord['Volume Unit'] ?? ''),
            'delivery_type' => $deliveryType,
            'delivery_classification' => $this->getInitialClassification($deliveryType),
            'operation_location' => $this->getInitialLocation($deliveryType),
            'proforma_shipping_instruction' => "PSI-" . date('Y') . "-" . trim($sapRecord['Delivery Number'] ?? ''),
            'export_destination' => $this->determineDestination($sapRecord),
            'forwarder_code' => $this->assignForwarderSimple($sapRecord['Customer Name'] ?? ''),
            'route_destination' => $this->getInitialRoute($deliveryType),
            'business_unit' => $this->getInitialBusinessUnit($deliveryType),
            'sap_synced_at' => $now,
            'created_at' => $now,
            'updated_at' => $now
        ];
    }

    /**
     * Simple date parsing without Carbon
     */
    private function parseDateSimple(?string $dateStr): ?string
    {
        if (empty($dateStr)) {
            return null;
        }

        // Handle DD.MM.YYYY format
        if (preg_match('/^(\d{2})\.(\d{2})\.(\d{4})$/', $dateStr, $matches)) {
            return $matches[3] . '-' . $matches[2] . '-' . $matches[1];
        }
        
        return null;
    }

    /**
     * Simplified forwarder assignment
     */
    private function assignForwarderSimple(string $buyer): ?string
    {
        if (empty($buyer)) {
            return null;
        }

        $upperBuyer = strtoupper($buyer);
        
        if (str_contains($upperBuyer, 'ETHAN ALLEN') || 
            str_contains($upperBuyer, 'CRATE') ||
            str_contains($upperBuyer, 'POTTERY BARN')) {
            return 'ACL';
        }

        if (str_contains($upperBuyer, 'IKEA') || 
            str_contains($upperBuyer, 'EUROPEAN')) {
            return 'CNL';
        }

        if (str_contains($upperBuyer, 'MUJI') || 
            str_contains($upperBuyer, 'JAPAN')) {
            return 'ESA';
        }

        return 'EXP';
    }

    // ... [Include all the other helper methods like cleanField, getInitialClassification, etc. - same as before]

    private function cleanFieldAllowEmpty(?string $value): ?string
    {
        if (is_null($value)) {
            return null;
        }
        
        $cleaned = trim($value);
        return $cleaned === '' ? null : $cleaned;
    }

    private function getInitialClassification(string $deliveryType): string
    {
        return match($deliveryType) {
            'ZDO1', 'ZDO2' => 'EXPORT',
            'ZDI1', 'ZDI2' => 'IMPORT', 
            'ZDL1', 'ZDL2' => 'LOCAL',
            'ZDR1', 'ZDR2' => 'RETURN',
            default => 'UNKNOWN'
        };
    }

    private function getInitialLocation(string $deliveryType): ?string
    {
        return match($deliveryType) {
            'ZDO1', 'ZDI1', 'ZDL1', 'ZDR1' => 'Surabaya',
            'ZDO2', 'ZDI2', 'ZDL2', 'ZDR2' => 'Semarang',
            default => null
        };
    }

    private function getInitialRoute(string $deliveryType): ?string
    {
        return match($deliveryType) {
            'ZDO1' => 'Export via Surabaya Port',
            'ZDO2' => 'Export via Semarang Port',
            'ZDI1' => 'Import via Surabaya Port',
            'ZDI2' => 'Import via Semarang Port',
            'ZDL1' => 'Local Distribution - Surabaya',
            'ZDL2' => 'Local Distribution - Semarang',
            'ZDR1' => 'Return to Surabaya',
            'ZDR2' => 'Return to Semarang',
            default => null
        };
    }

    private function getInitialBusinessUnit(string $deliveryType): ?string
    {
        return match($deliveryType) {
            'ZDO1', 'ZDO2' => 'EXPORT_DEPT',
            'ZDI1', 'ZDI2' => 'IMPORT_DEPT',
            'ZDL1', 'ZDL2' => 'LOCAL_SALES',
            'ZDR1', 'ZDR2' => 'LOGISTICS',
            default => null
        };
    }

    private function cleanNumeric($value): float
    {
        if (is_numeric($value)) {
            return (float) $value;
        }
        
        $cleaned = str_replace(',', '', (string) $value);
        return is_numeric($cleaned) ? (float) $cleaned : 0.0;
    }

    private function determineDestination(array $record): string
    {
        $customer = $record['Customer Name'] ?? '';

        if (str_contains(strtoupper($customer), 'USA') || 
            str_contains(strtoupper($customer), 'AMERICA') ||
            str_contains(strtoupper($customer), 'ETHAN ALLEN')) {
            return 'USA - New York';
        }

        if (str_contains(strtoupper($customer), 'EUROPE') ||
            str_contains(strtoupper($customer), 'IKEA')) {
            return 'Europe - Hamburg';
        }

        if (str_contains(strtoupper($customer), 'JAPAN') ||
            str_contains(strtoupper($customer), 'MUJI')) {
            return 'Japan - Tokyo';
        }

        return 'USA - New York';
    }

    private function validateSapRecord(array $record): void
    {
        $required = ['Delivery Number', 'Material Number', 'Customer Name'];
        
        foreach ($required as $field) {
            if (empty($record[$field])) {
                throw new \Exception("Missing required field: {$field}");
            }
        }
    }

    private function alreadySyncedToday(): bool
    {
        $lastSync = cache()->get('export_last_sap_sync');
        
        if ($lastSync) {
            $lastSyncDate = Carbon::parse($lastSync);
            return $lastSyncDate->isToday();
        }
        
        return false;
    }

    private function updateSyncStatus(array $processed): void
    {
        cache()->put('export_last_sap_sync', now()->toDateTimeString(), now()->addDays(7));
        cache()->put('export_last_sync_stats', $processed, now()->addDays(7));
    }

    private function displayResults(array $processed): void
    {
        $this->newLine();
        $this->info('Processing Results:');
        $this->table(
            ['Type', 'Count'],
            [
                ['Total Records', $processed['total']],
                ['New Records', $processed['new']],
                ['Updated Records', $processed['updated']],
                ['Skipped Records', $processed['skipped']],
                ['Error Records', $processed['errors']]
            ]
        );

        if ($processed['errors'] > 0) {
            $this->warn("{$processed['errors']} records had errors. Check logs for details.");
        }

        if ($this->option('test')) {
            $this->comment('Test mode - No data was saved to database');
        }

        $this->newLine();
        $this->info('Important Notes:');
        $this->comment('- ALL RFC data stored in export_data table with delivery_type field');
        $this->comment('- Dashboard filtering will be handled by Laravel controllers');
        $this->comment('- Export/Import/Local classification happens at query level');
        $this->comment('- Use delivery_type field to route records to appropriate dashboards');
    }
}