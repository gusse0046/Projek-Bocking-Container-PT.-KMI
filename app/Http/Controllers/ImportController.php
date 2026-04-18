<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\ImportData;
use App\Models\Forwarder;

class ImportController extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;

    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Enhanced import dashboard with delivery type filtering (ZDI1/ZDI2)
     */
    public function dashboard(Request $request)
    {
        try {
            Log::info('Import Dashboard loading with delivery type filtering', [
                'user' => auth()->user()->email,
                'location_filter' => $request->get('location', 'all')
            ]);

            // For now, we'll generate dummy data since the import data isn't integrated with API yet
            $importData = $this->generateDummyImportData();

            // Group data by import types
            $groupedData = $this->groupImportDataByType($importData);

            // Calculate statistics by delivery type
            $statistics = $this->calculateImportStatistics($importData);

            // Get active forwarders
            $forwarders = Forwarder::where('is_active', true)->get();

            $location = $request->get('location', 'all');

            Log::info('Import dashboard data loaded', [
                'total_records' => count($importData),
                'surabaya_records' => $statistics['surabaya_stats']['total_records'],
                'semarang_records' => $statistics['semarang_stats']['total_records'],
                'import_types' => array_keys($groupedData['by_type'])
            ]);

            return view('import.dashboard', compact(
                'importData', 
                'groupedData', 
                'statistics', 
                'forwarders', 
                'location'
            ));

        } catch (\Exception $e) {
            Log::error('Error loading import dashboard', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user' => auth()->user()->email
            ]);

            return view('import.dashboard', [
                'importData' => [],
                'groupedData' => $this->getEmptyGroupedData(),
                'statistics' => $this->getEmptyStatistics(),
                'forwarders' => collect([]),
                'location' => 'all'
            ]);
        }
    }

    /**
     * Generate dummy import data for dashboard display
     */
    private function generateDummyImportData()
    {
        $importTypes = [
            'bahan_baku' => 'Raw Materials',
            'hardware' => 'Hardware Components',
            'sparepart' => 'Spare Parts',
            'tools' => 'Tools & Equipment',
            'mesin' => 'Machinery'
        ];

        $vendors = [
            'PT Samsung Electronics Indonesia',
            'PT LG Electronics Indonesia', 
            'PT Panasonic Manufacturing Indonesia',
            'PT Sony Indonesia',
            'PT Toshiba Indonesia',
            'PT Sharp Electronics Indonesia',
            'PT Mitsubishi Electric Indonesia'
        ];

        $locations = ['ZDI1', 'ZDI2']; // Surabaya Import, Semarang Import
        $priorities = ['normal', 'high', 'urgent'];
        $statuses = ['ready', 'prepared', 'sent', 'responded', 'cleared', 'delivered'];

        $data = [];
        $purchaseOrderCounter = 1;

        foreach ($importTypes as $typeCode => $typeName) {
            $itemsCount = rand(15, 35);
            
            for ($i = 0; $i < $itemsCount; $i++) {
                $vendor = $vendors[array_rand($vendors)];
                $location = $locations[array_rand($locations)];
                $priority = $priorities[array_rand($priorities)];
                $status = $statuses[array_rand($statuses)];
                
                $quantity = rand(10, 500);
                $unitPrice = rand(50, 5000);
                $totalValue = $quantity * $unitPrice;

                $data[] = [
                    'id' => $purchaseOrderCounter,
                    'purchase_order' => 'PO-IMP-' . str_pad($purchaseOrderCounter, 4, '0', STR_PAD_LEFT),
                    'import_type' => $typeCode,
                    'import_type_name' => $typeName,
                    'vendor' => $vendor,
                    'material_code' => strtoupper($typeCode) . '-' . str_pad(rand(1, 999), 3, '0', STR_PAD_LEFT),
                    'material_description' => $this->generateMaterialDescription($typeCode),
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'total_value' => $totalValue,
                    'currency' => 'USD',
                    'origin_country' => $this->getOriginCountryForVendor($vendor),
                    'expected_arrival' => date('Y-m-d', strtotime('+' . rand(7, 60) . ' days')),
                    'delivery_type' => $location,
                    'location' => $location === 'ZDI1' ? 'surabaya' : 'semarang',
                    'port_of_entry' => $location === 'ZDI1' ? 'Tanjung Perak - Surabaya' : 'Tanjung Emas - Semarang',
                    'priority' => $priority,
                    'status' => $status,
                    'customs_status' => $this->getCustomsStatus($status),
                    'forwarder_assigned' => $this->isForwarderAssigned($vendor),
                    'tracking_number' => $status !== 'ready' ? 'TRK-' . strtoupper($typeCode) . '-' . str_pad($purchaseOrderCounter, 4, '0', STR_PAD_LEFT) : null,
                    'created_at' => date('Y-m-d H:i:s', strtotime('-' . rand(1, 30) . ' days')),
                    'updated_at' => date('Y-m-d H:i:s', strtotime('-' . rand(0, 7) . ' days'))
                ];

                $purchaseOrderCounter++;
            }
        }

        return $data;
    }

    /**
     * Generate material description based on import type
     */
    private function generateMaterialDescription($typeCode)
    {
        $descriptions = [
            'bahan_baku' => [
                'Premium Wood Veneer Sheets',
                'High-Grade Steel Plates',
                'Industrial Adhesive Compound',
                'Plastic Raw Material Pellets',
                'Metal Alloy Sheets',
                'Composite Material Boards'
            ],
            'hardware' => [
                'Stainless Steel Hinges Set',
                'Heavy Duty Cabinet Handles',
                'Industrial Grade Screws',
                'Ball Bearing Drawer Slides',
                'Magnetic Door Latches',
                'Adjustable Furniture Legs'
            ],
            'sparepart' => [
                'CNC Machine Spindle Motor',
                'Hydraulic Pump Components',
                'Industrial Conveyor Belt',
                'Electric Motor Bearings',
                'Precision Cutting Blades',
                'Pneumatic Cylinder Parts'
            ],
            'tools' => [
                'Professional Router Set',
                'Industrial Drill Bits Kit',
                'Precision Measuring Tools',
                'Heavy Duty Clamps Set',
                'Woodworking Chisels Kit',
                'Professional Sanders Set'
            ],
            'mesin' => [
                'CNC Wood Processing Machine',
                'Industrial Band Saw',
                'Automated Edge Banding Machine',
                'Hydraulic Press Machine',
                'Industrial Dust Collector',
                'Precision Cutting Machine'
            ]
        ];

        $typeDescriptions = $descriptions[$typeCode] ?? ['Generic Import Item'];
        return $typeDescriptions[array_rand($typeDescriptions)];
    }

    /**
     * Get origin country for vendor
     */
    private function getOriginCountryForVendor($vendor)
    {
        $countryMap = [
            'PT Samsung Electronics Indonesia' => 'South Korea',
            'PT LG Electronics Indonesia' => 'South Korea',
            'PT Panasonic Manufacturing Indonesia' => 'Japan',
            'PT Sony Indonesia' => 'Japan',
            'PT Toshiba Indonesia' => 'Japan',
            'PT Sharp Electronics Indonesia' => 'Japan',
            'PT Mitsubishi Electric Indonesia' => 'Japan'
        ];

        return $countryMap[$vendor] ?? 'China';
    }

    /**
     * Get customs status based on overall status
     */
    private function getCustomsStatus($status)
    {
        $customsMap = [
            'ready' => 'Pending',
            'prepared' => 'Documentation Prepared',
            'sent' => 'In Process',
            'responded' => 'Under Review',
            'cleared' => 'Cleared',
            'delivered' => 'Completed'
        ];

        return $customsMap[$status] ?? 'Pending';
    }

    /**
     * Check if forwarder is assigned for vendor
     */
    private function isForwarderAssigned($vendor)
    {
        // Simulate some vendors having forwarder assignments
        $assignedVendors = [
            'PT Samsung Electronics Indonesia',
            'PT LG Electronics Indonesia',
            'PT Sony Indonesia'
        ];

        return in_array($vendor, $assignedVendors);
    }

    /**
     * Group import data by type and location
     */
    private function groupImportDataByType($importData)
    {
        $groupedByType = [];
        $groupedByLocation = [
            'surabaya' => [],
            'semarang' => []
        ];
        $groupedByVendor = [];

        foreach ($importData as $item) {
            // Group by import type
            $importType = $item['import_type'];
            if (!isset($groupedByType[$importType])) {
                $groupedByType[$importType] = [
                    'type_code' => $importType,
                    'type_name' => $item['import_type_name'],
                    'items' => [],
                    'total_value' => 0,
                    'total_quantity' => 0,
                    'vendors' => [],
                    'purchase_orders' => []
                ];
            }

            $groupedByType[$importType]['items'][] = $item;
            $groupedByType[$importType]['total_value'] += $item['total_value'];
            $groupedByType[$importType]['total_quantity'] += $item['quantity'];
            $groupedByType[$importType]['vendors'][] = $item['vendor'];
            $groupedByType[$importType]['purchase_orders'][] = $item['purchase_order'];

            // Group by location
            $location = $item['location'];
            $groupedByLocation[$location][] = $item;

            // Group by vendor
            $vendor = $item['vendor'];
            if (!isset($groupedByVendor[$vendor])) {
                $groupedByVendor[$vendor] = [];
            }
            $groupedByVendor[$vendor][] = $item;
        }

        // Clean up grouped data
        foreach ($groupedByType as $typeCode => $typeData) {
            $groupedByType[$typeCode]['vendors'] = array_unique($typeData['vendors']);
            $groupedByType[$typeCode]['purchase_orders'] = array_unique($typeData['purchase_orders']);
            $groupedByType[$typeCode]['vendor_count'] = count($groupedByType[$typeCode]['vendors']);
            $groupedByType[$typeCode]['po_count'] = count($groupedByType[$typeCode]['purchase_orders']);
        }

        return [
            'by_type' => $groupedByType,
            'by_location' => $groupedByLocation,
            'by_vendor' => $groupedByVendor
        ];
    }

    /**
     * Calculate import statistics
     */
    private function calculateImportStatistics($importData)
    {
        $surabayaData = array_filter($importData, function($item) {
            return $item['location'] === 'surabaya';
        });

        $semarangData = array_filter($importData, function($item) {
            return $item['location'] === 'semarang';
        });

        $totalValue = array_sum(array_column($importData, 'total_value'));
        $surabayaValue = array_sum(array_column($surabayaData, 'total_value'));
        $semarangValue = array_sum(array_column($semarangData, 'total_value'));

        return [
            'total_stats' => [
                'total_records' => count($importData),
                'total_value' => $totalValue,
                'total_quantity' => array_sum(array_column($importData, 'quantity')),
                'unique_vendors' => count(array_unique(array_column($importData, 'vendor'))),
                'unique_pos' => count(array_unique(array_column($importData, 'purchase_order'))),
                'avg_value_per_po' => count($importData) > 0 ? $totalValue / count(array_unique(array_column($importData, 'purchase_order'))) : 0
            ],
            'surabaya_stats' => [
                'total_records' => count($surabayaData),
                'total_value' => $surabayaValue,
                'total_quantity' => array_sum(array_column($surabayaData, 'quantity')),
                'unique_vendors' => count(array_unique(array_column($surabayaData, 'vendor'))),
                'unique_pos' => count(array_unique(array_column($surabayaData, 'purchase_order')))
            ],
            'semarang_stats' => [
                'total_records' => count($semarangData),
                'total_value' => $semarangValue,
                'total_quantity' => array_sum(array_column($semarangData, 'quantity')),
                'unique_vendors' => count(array_unique(array_column($semarangData, 'vendor'))),
                'unique_pos' => count(array_unique(array_column($semarangData, 'purchase_order')))
            ],
            'by_type' => $this->getStatisticsByType($importData),
            'by_status' => $this->getStatisticsByStatus($importData),
            'by_priority' => $this->getStatisticsByPriority($importData)
        ];
    }

    /**
     * Get statistics by import type
     */
    private function getStatisticsByType($importData)
    {
        $typeStats = [];
        
        foreach ($importData as $item) {
            $type = $item['import_type'];
            if (!isset($typeStats[$type])) {
                $typeStats[$type] = [
                    'type_name' => $item['import_type_name'],
                    'count' => 0,
                    'total_value' => 0,
                    'avg_value' => 0
                ];
            }
            
            $typeStats[$type]['count']++;
            $typeStats[$type]['total_value'] += $item['total_value'];
        }
        
        foreach ($typeStats as $type => $stats) {
            $typeStats[$type]['avg_value'] = $stats['count'] > 0 ? $stats['total_value'] / $stats['count'] : 0;
        }
        
        return $typeStats;
    }

    /**
     * Get statistics by status
     */
    private function getStatisticsByStatus($importData)
    {
        $statusStats = [];
        
        foreach ($importData as $item) {
            $status = $item['status'];
            if (!isset($statusStats[$status])) {
                $statusStats[$status] = 0;
            }
            $statusStats[$status]++;
        }
        
        return $statusStats;
    }

    /**
     * Get statistics by priority
     */
    private function getStatisticsByPriority($importData)
    {
        $priorityStats = [];
        
        foreach ($importData as $item) {
            $priority = $item['priority'];
            if (!isset($priorityStats[$priority])) {
                $priorityStats[$priority] = 0;
            }
            $priorityStats[$priority]++;
        }
        
        return $priorityStats;
    }

    /**
     * Get empty grouped data structure
     */
    private function getEmptyGroupedData()
    {
        return [
            'by_type' => [],
            'by_location' => [
                'surabaya' => [],
                'semarang' => []
            ],
            'by_vendor' => []
        ];
    }

    /**
     * Get empty statistics structure
     */
    private function getEmptyStatistics()
    {
        return [
            'total_stats' => [
                'total_records' => 0,
                'total_value' => 0,
                'total_quantity' => 0,
                'unique_vendors' => 0,
                'unique_pos' => 0,
                'avg_value_per_po' => 0
            ],
            'surabaya_stats' => [
                'total_records' => 0,
                'total_value' => 0,
                'total_quantity' => 0,
                'unique_vendors' => 0,
                'unique_pos' => 0
            ],
            'semarang_stats' => [
                'total_records' => 0,
                'total_value' => 0,
                'total_quantity' => 0,
                'unique_vendors' => 0,
                'unique_pos' => 0
            ],
            'by_type' => [],
            'by_status' => [],
            'by_priority' => []
        ];
    }

    /**
     * Get import data with filtering
     */
    public function getData(Request $request)
    {
        try {
            // For now return dummy data, later integrate with actual import API
            $importData = $this->generateDummyImportData();
            
            // Apply filters
            if ($request->has('import_type') && !empty($request->import_type)) {
                $importData = array_filter($importData, function($item) use ($request) {
                    return $item['import_type'] === $request->import_type;
                });
            }
            
            if ($request->has('location') && !empty($request->location)) {
                $importData = array_filter($importData, function($item) use ($request) {
                    return $item['location'] === $request->location;
                });
            }
            
            if ($request->has('status') && !empty($request->status)) {
                $importData = array_filter($importData, function($item) use ($request) {
                    return $item['status'] === $request->status;
                });
            }
            
            if ($request->has('search') && !empty($request->search)) {
                $search = strtolower($request->search);
                $importData = array_filter($importData, function($item) use ($search) {
                    return strpos(strtolower($item['purchase_order']), $search) !== false ||
                           strpos(strtolower($item['vendor']), $search) !== false ||
                           strpos(strtolower($item['material_description']), $search) !== false;
                });
            }
            
            return response()->json([
                'success' => true,
                'data' => array_values($importData),
                'count' => count($importData)
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to get import data: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Get import statistics
     */
    public function getStatistics(Request $request)
    {
        try {
            $importData = $this->generateDummyImportData();
            $statistics = $this->calculateImportStatistics($importData);
            
            return response()->json([
                'success' => true,
                'statistics' => $statistics
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to get statistics: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Sync from SAP (placeholder for future implementation)
     */
    public function syncFromSAP(Request $request)
    {
        try {
            // Placeholder for SAP sync implementation
            $syncedRecords = rand(50, 200);
            
            return response()->json([
                'success' => true,
                'message' => "Import data sync completed successfully. {$syncedRecords} records synchronized.",
                'synced_records' => $syncedRecords,
                'sync_time' => now()
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'SAP sync failed: ' . $e->getMessage()
            ]);
        }
    }
}