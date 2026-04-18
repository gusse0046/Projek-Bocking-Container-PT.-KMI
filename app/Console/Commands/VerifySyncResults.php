<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ExportData;

class VerifySyncResults extends Command
{
    protected $signature = 'sap:verify-sync {--sample=5 : Number of sample records to check}';
    protected $description = 'Verify SAP sync results and check field mapping';

    public function handle()
    {
        $this->info('Verifying SAP sync results...');
        
        // Get sample records
        $sampleSize = $this->option('sample');
        $records = ExportData::orderBy('sap_synced_at', 'desc')
                            ->limit($sampleSize)
                            ->get();

        if ($records->isEmpty()) {
            $this->warn('No synced records found');
            return;
        }

        $this->info("Checking last {$records->count()} synced records:");
        $this->newLine();

        // Check each record
        foreach ($records as $index => $record) {
            $this->info("=== RECORD " . ($index + 1) . " ===");
            $this->showRecordDetails($record);
            $this->newLine();
        }

        // Show statistics
        $this->showSyncStatistics();
        
        // Show delivery type distribution
        $this->showDeliveryTypeDistribution();
    }

    private function showRecordDetails($record)
    {
        $fields = [
            'delivery' => 'Delivery Number',
            'buyer' => 'Customer/Buyer',
            'delivery_type' => 'Delivery Type',
            'delivery_classification' => 'Classification',
            'operation_location' => 'Operation Location',
            'business_unit' => 'Business Unit',
            'sap_delivery_status' => 'SAP Status',
            'sap_customer_number' => 'Customer Number',
            'sap_sales_unit' => 'Sales Unit',
            'sap_weight_unit' => 'Weight Unit',
            'sap_volume_unit' => 'Volume Unit',
            'delivery_date' => 'Delivery Date',
            'created_date' => 'Created Date',
            'reference_invoice' => 'Reference Invoice',
            'container_number' => 'Container Number'
        ];

        foreach ($fields as $field => $label) {
            $value = $record->{$field};
            $displayValue = is_null($value) ? 'NULL' : 
                           (empty($value) ? 'EMPTY' : $value);
            $status = is_null($value) || empty($value) ? '❌' : '✅';
            
            $this->line(sprintf("%s %-20s: %s", $status, $label, $displayValue));
        }
    }

    private function showSyncStatistics()
    {
        $this->info('=== SYNC STATISTICS ===');
        
        $totalRecords = ExportData::count();
        $syncedRecords = ExportData::whereNotNull('sap_synced_at')->count();
        $classifiedRecords = ExportData::where('delivery_classification', '!=', 'UNKNOWN')->count();
        
        $fieldStats = [
            'sap_delivery_status' => ExportData::whereNotNull('sap_delivery_status')->count(),
            'sap_customer_number' => ExportData::whereNotNull('sap_customer_number')->count(),
            'sap_sales_unit' => ExportData::whereNotNull('sap_sales_unit')->count(),
            'delivery_classification' => ExportData::where('delivery_classification', '!=', 'UNKNOWN')->count(),
            'operation_location' => ExportData::whereNotNull('operation_location')->count(),
            'business_unit' => ExportData::whereNotNull('business_unit')->count(),
            'container_number' => ExportData::whereNotNull('container_number')->where('container_number', '!=', '')->count(),
        ];

        $this->table(
            ['Metric', 'Count', 'Percentage'],
            [
                ['Total Records', $totalRecords, '100%'],
                ['SAP Synced', $syncedRecords, $this->percentage($syncedRecords, $totalRecords)],
                ['Auto Classified', $classifiedRecords, $this->percentage($classifiedRecords, $totalRecords)],
                ['With SAP Status', $fieldStats['sap_delivery_status'], $this->percentage($fieldStats['sap_delivery_status'], $totalRecords)],
                ['With Customer Number', $fieldStats['sap_customer_number'], $this->percentage($fieldStats['sap_customer_number'], $totalRecords)],
                ['With Sales Unit', $fieldStats['sap_sales_unit'], $this->percentage($fieldStats['sap_sales_unit'], $totalRecords)],
                ['With Location', $fieldStats['operation_location'], $this->percentage($fieldStats['operation_location'], $totalRecords)],
                ['With Business Unit', $fieldStats['business_unit'], $this->percentage($fieldStats['business_unit'], $totalRecords)],
                ['With Container Number', $fieldStats['container_number'], $this->percentage($fieldStats['container_number'], $totalRecords)],
            ]
        );
    }

    private function showDeliveryTypeDistribution()
    {
        $this->info('=== DELIVERY TYPE DISTRIBUTION ===');
        
        $deliveryTypes = ExportData::selectRaw('delivery_type, delivery_classification, COUNT(*) as count')
                                  ->groupBy('delivery_type', 'delivery_classification')
                                  ->orderBy('count', 'desc')
                                  ->get();

        if ($deliveryTypes->isEmpty()) {
            $this->warn('No delivery type data found');
            return;
        }

        $tableData = [];
        foreach ($deliveryTypes as $type) {
            $tableData[] = [
                $type->delivery_type ?: 'EMPTY',
                $type->delivery_classification ?: 'UNCLASSIFIED', 
                $type->count
            ];
        }

        $this->table(['Delivery Type', 'Classification', 'Count'], $tableData);
        
        // Show mapping verification
        $this->newLine();
        $this->info('=== MAPPING VERIFICATION ===');
        $this->line('Expected delivery types from debug: ZDO1, ZDO2, ZDL1, ZDR2');
        $this->line('Actual delivery types in database: ' . 
            ExportData::distinct('delivery_type')
                     ->whereNotNull('delivery_type')
                     ->pluck('delivery_type')
                     ->implode(', ')
        );
    }

    private function percentage($value, $total)
    {
        return $total > 0 ? round(($value / $total) * 100, 1) . '%' : '0%';
    }
}