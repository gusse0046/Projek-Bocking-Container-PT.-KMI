<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Internal Users - Export Department
        User::updateOrCreate(
            ['email' => 'export@company.com'],
            [
                'name' => 'Export Manager',
                'email' => 'export@company.com',
                'password' => Hash::make('export123'),
                'role' => 'export',
                'forwarder_code' => null
            ]
        );

        // Internal Users - Import Department  
        User::updateOrCreate(
            ['email' => 'import@company.com'],
            [
                'name' => 'Import Manager',
                'email' => 'import@company.com',
                'password' => Hash::make('import123'),
                'role' => 'import',
                'forwarder_code' => null
            ]
        );

        // Forwarder Users
        $forwarders = [
            [
                'name' => 'Atlantic Container Line User',
                'email' => 'acl@forwarder.com',
                'code' => 'ACL'
            ],
            [
                'name' => 'CNL Logistics User',
                'email' => 'cnl@forwarder.com',
                'code' => 'CNL'
            ],
            [
                'name' => 'Schenker Petrolog User',
                'email' => 'spu@forwarder.com',
                'code' => 'SPU'
            ],
            [
                'name' => 'Evergreen Shipping User',
                'email' => 'esa@forwarder.com',
                'code' => 'ESA'
            ],
            [
                'name' => 'Expeditors Indonesia User',
                'email' => 'exp@forwarder.com',
                'code' => 'EXP'
            ],
            [
                'name' => 'Maximos Global User',
                'email' => 'mgl@forwarder.com',
                'code' => 'MGL'
            ],
            [
                'name' => 'RSL Logistic User',
                'email' => 'rsl@forwarder.com',
                'code' => 'RSL'
            ],
            [
                'name' => 'Orient Star Shipping User',
                'email' => 'oss@forwarder.com',
                'code' => 'OSS'
            ]
        ];

        foreach ($forwarders as $forwarder) {
            User::updateOrCreate(
                ['email' => $forwarder['email']],
                [
                    'name' => $forwarder['name'],
                    'email' => $forwarder['email'],
                    'password' => Hash::make('forwarder123'),
                    'role' => 'forwarder',
                    'forwarder_code' => $forwarder['code']
                ]
            );
        }

        $this->command->info('✅ Users seeded successfully!');
        $this->command->line('');
        $this->command->line('🔐 Login Credentials:');
        $this->command->line('Export: export@company.com / export123');
        $this->command->line('Import: import@company.com / import123');
        $this->command->line('Forwarders: {code}@forwarder.com / forwarder123');
    }
}