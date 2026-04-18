<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ExportData;

class ExportDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $exportData = [
            [
                'delivery' => '8000123456',
                'no_item' => '10',
                'material' => 'FG-WC-001',
                'description' => 'Wooden Chair Premium Teak',
                'proforma_shipping_instruction' => 'PSI-2025-001-EA',
                'buyer' => 'ETHAN ALLEN',
                'quantity' => 100.00,
                'volume' => 25.50,
                'weight' => 1250.00,
                'export_destination' => 'USA - New York'
            ],
            [
                'delivery' => '8000123457',
                'no_item' => '20',
                'material' => 'FG-CT-002',
                'description' => 'Coffee Table Oak Modern',
                'proforma_shipping_instruction' => 'PSI-2025-002-CB',
                'buyer' => 'CRATE & BARREL',
                'quantity' => 50.00,
                'volume' => 15.20,
                'weight' => 800.00,
                'export_destination' => 'USA - California'
            ],
            [
                'delivery' => '8000123458',
                'no_item' => '30',
                'material' => 'FG-DT-003',
                'description' => 'Dining Table Set 6 Seater',
                'proforma_shipping_instruction' => 'PSI-2025-003-UT',
                'buyer' => 'UTTERMOST',
                'quantity' => 25.00,
                'volume' => 45.80,
                'weight' => 2100.00,
                'export_destination' => 'USA - Texas'
            ],
            [
                'delivery' => '8000123459',
                'no_item' => '40',
                'material' => 'FG-OD-004',
                'description' => 'Office Desk Executive Modern',
                'proforma_shipping_instruction' => 'PSI-2025-004-RW',
                'buyer' => 'ROWE',
                'quantity' => 75.00,
                'volume' => 18.90,
                'weight' => 950.00,
                'export_destination' => 'USA - Florida'
            ],
            [
                'delivery' => '8000123460',
                'no_item' => '50',
                'material' => 'FG-BS-005',
                'description' => 'Bedroom Set Premium Collection',
                'proforma_shipping_instruction' => 'PSI-2025-005-LG',
                'buyer' => 'LULU & GEORGIA',
                'quantity' => 30.00,
                'volume' => 55.70,
                'weight' => 2800.00,
                'export_destination' => 'USA - Georgia'
            ],
            [
                'delivery' => '8000123461',
                'no_item' => '60',
                'material' => 'FG-CB-006',
                'description' => 'Cabinet Storage Mahogany',
                'proforma_shipping_instruction' => 'PSI-2025-006-BR',
                'buyer' => 'BRUNSWICK',
                'quantity' => 40.00,
                'volume' => 28.30,
                'weight' => 1600.00,
                'export_destination' => 'USA - Maine'
            ],
            [
                'delivery' => '8000123462',
                'no_item' => '70',
                'material' => 'FG-SF-007',
                'description' => 'Sofa 3 Seater Leather Premium',
                'proforma_shipping_instruction' => 'PSI-2025-007-CT',
                'buyer' => 'CENTURY',
                'quantity' => 20.00,
                'volume' => 35.40,
                'weight' => 1400.00,
                'export_destination' => 'USA - Illinois'
            ],
            [
                'delivery' => '8000123463',
                'no_item' => '80',
                'material' => 'FG-WR-008',
                'description' => 'Wardrobe 4 Door Classic',
                'proforma_shipping_instruction' => 'PSI-2025-008-VG',
                'buyer' => 'VANGUARD',
                'quantity' => 15.00,
                'volume' => 42.60,
                'weight' => 1800.00,
                'export_destination' => 'USA - Virginia'
            ],
            [
                'delivery' => '8000123464',
                'no_item' => '90',
                'material' => 'FG-BT-009',
                'description' => 'Bookshelf Tall Oak Finish',
                'proforma_shipping_instruction' => 'PSI-2025-009-CB',
                'buyer' => 'CRATE & BARREL',
                'quantity' => 60.00,
                'volume' => 22.40,
                'weight' => 1100.00,
                'export_destination' => 'Malaysia - Kuala Lumpur'
            ],
            [
                'delivery' => '8000123465',
                'no_item' => '100',
                'material' => 'FG-AR-010',
                'description' => 'Armchair Luxury Velvet',
                'proforma_shipping_instruction' => 'PSI-2025-010-EA',
                'buyer' => 'ETHAN ALLEN',
                'quantity' => 35.00,
                'volume' => 12.80,
                'weight' => 700.00,
                'export_destination' => 'USA - North Carolina'
            ]
        ];

        foreach ($exportData as $data) {
            ExportData::updateOrCreate(
                [
                    'delivery' => $data['delivery'],
                    'no_item' => $data['no_item']
                ],
                $data
            );
        }

        $this->command->info('✅ Export data seeded successfully!');
        $this->command->line('📦 Total records: ' . count($exportData));
    }
}