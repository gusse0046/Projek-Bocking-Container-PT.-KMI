<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class DebugSapData extends Command
{
    protected $signature = 'sap:debug-data {--sample=5 : Number of sample records to inspect}';
    protected $description = 'Debug SAP RFC data structure to see actual field names and values';

    public function handle()
    {
        $this->info('Fetching SAP RFC data for inspection...');
        
        try {
            $response = Http::timeout(60)->get('http://127.0.0.1:5023/api/export-data', [
                'check' => 'X',
                'timeout' => 60
            ]);

            if (!$response->successful()) {
                $this->error('Failed to fetch data from RFC API');
                return Command::FAILURE;
            }

            $result = $response->json();
            $data = $result['data'] ?? [];

            if (empty($data)) {
                $this->warn('No data returned from RFC');
                return Command::SUCCESS;
            }

            $sampleSize = min($this->option('sample'), count($data));
            $this->info("Inspecting first {$sampleSize} records from " . count($data) . " total records");
            
            // Show all unique field names across all records
            $this->info("\n=== ALL AVAILABLE FIELDS IN RFC DATA ===");
            $allFields = [];
            foreach ($data as $record) {
                $allFields = array_merge($allFields, array_keys($record));
            }
            $uniqueFields = array_unique($allFields);
            sort($uniqueFields);
            
            foreach ($uniqueFields as $field) {
                $this->line("- {$field}");
            }
            
            // Show sample records with actual values
            for ($i = 0; $i < $sampleSize; $i++) {
                $record = $data[$i];
                $this->info("\n=== SAMPLE RECORD " . ($i + 1) . " ===");
                
                foreach ($record as $key => $value) {
                    $displayValue = is_null($value) ? 'NULL' : 
                                   (is_string($value) && empty($value) ? 'EMPTY_STRING' : 
                                   (is_array($value) ? json_encode($value) : $value));
                    
                    $this->line(sprintf("%-25s: %s", $key, $displayValue));
                }
            }
            
            // Show field mapping analysis
            $this->info("\n=== FIELD MAPPING ANALYSIS ===");
            $expectedMappings = [
                'Delivery Number' => 'delivery',
                'Item Number' => 'no_item', 
                'Material Number' => 'material',
                'Material Description' => 'description',
                'Customer Name' => 'buyer',
                'Delivery Quantity' => 'quantity',
                'Volume' => 'volume',
                'Gross Weight' => 'weight',
                'Container Number' => 'container_number',
                'Reference Invoice' => 'reference_invoice',
                'Planned GI Date' => 'delivery_date',
                'Actual GI Date' => 'created_date',
                'Goods Movement Status' => 'sap_delivery_status',
                'Customer Number' => 'sap_customer_number',
                'Sales Unit' => 'sap_sales_unit',
                'Weight Unit' => 'sap_weight_unit',
                'Volume Unit' => 'sap_volume_unit',
                'Delivery Type' => 'delivery_type'
            ];
            
            $sampleRecord = $data[0];
            foreach ($expectedMappings as $rfcField => $dbField) {
                $exists = array_key_exists($rfcField, $sampleRecord);
                $value = $exists ? $sampleRecord[$rfcField] : 'FIELD_NOT_FOUND';
                $status = $exists ? '✓' : '✗';
                
                $this->line(sprintf("%s %-25s -> %-20s : %s", 
                    $status, $rfcField, $dbField, $value));
            }
            
            // Show delivery type analysis
            $this->info("\n=== DELIVERY TYPE ANALYSIS ===");
            $deliveryTypes = [];
            foreach ($data as $record) {
                $type = $record['Delivery Type'] ?? 'UNKNOWN';
                if (!isset($deliveryTypes[$type])) {
                    $deliveryTypes[$type] = 0;
                }
                $deliveryTypes[$type]++;
            }
            
            foreach ($deliveryTypes as $type => $count) {
                $this->line("Delivery Type '{$type}': {$count} records");
            }
            
            return Command::SUCCESS;
            
        } catch (\Exception $e) {
            $this->error('Debug failed: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}