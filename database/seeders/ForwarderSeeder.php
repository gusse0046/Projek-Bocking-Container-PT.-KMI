<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Forwarder;

class ForwarderSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $forwarders = [
            [
                'name' => 'ATLANTIC CONTAINER LINE, PT',
                'code' => 'ACL',
                'buyers' => json_encode([
                    'ETHAN ALLEN OPERATIONS INC',
                    'ETHAN ALLEN OPERATIONS, INC.',
                    'ETHAN ALLEN',
                    'ETHAN ALLEN OPERATIONS INC.',
                    'ETHAN ALLEN GLOBAL INC',
                    'THE UTTERMOST CO.',
                    'THE UTTERMOST CO',
                    'UTTERMOST CO.',
                    'UTTERMOST CO',
                    'UTTERMOST',
                    'THE UTTERMOST COMPANY',
                    'UTTERMOST COMPANY',
                    'UTTERMOST LLC',
                    'THE UTTERMOST LLC'
                ]),
                'destination' => 'USA'
            ],
            [
                'name' => 'CNL LOGISTICS INDONESIA PT.',
                'code' => 'CNL',
                'buyers' => json_encode([
                    'CRATE & BARREL',
                    'CRATE AND BARREL',
                    'CB2',
                    'CRATE & BARREL KIDS'
                ]),
                'destination' => 'USA'
            ],
            [
                'name' => 'SCHENKER PETROLOG UTAMA, PT',
                'code' => 'SPU',
                'buyers' => json_encode(['CRATE & BARREL']),
                'destination' => 'Malaysia'
            ],
            [
                'name' => 'EVERGREEN SHIPPING AGENCY, PT',
                'code' => 'ESA',
                'buyers' => json_encode([
                    'WEST ELM',
                    'POTTERY BARN',
                    'POTTERY BARN KIDS', 
                    'WILLIAMS SONOMA',
                    'WILLIAMS-SONOMA',
                    'ROWE FINE FURNITURE INC',
                    'ROWE FINE FURNITURE',
                    'ROWE FURNITURE',
                    'ROWE'
                ]),
                'destination' => 'USA'
            ],
            [
                'name' => 'EXPEDITORS INDONESIA, PT',
                'code' => 'EXP',
                'buyers' => json_encode([
                    'LULU AND GEORGIA',
                    'LULU & GEORGIA',
                    'ARHAUS',
                    'ANTHROPOLOGIE'
                ]),
                'destination' => 'USA'
            ],
            [
                'name' => 'MAXIMOS GLOBAL LOGISTIK, PT',
                'code' => 'MGL',
                'buyers' => json_encode([
                    'INDIAN INDUSTRIES DBA ESCALADE SPORTS',
                    'ESCALADE SPORTS',
                    'INDIAN INDUSTRIES',
                    'ESCALADE SPORTS LLC',
                    'INDIAN INDUSTRIES LLC',
                    'BRUNSWICK BILLIARDS-LIFE FITNE',
                    'BRUNSWICK BILLIARDS-LIFE FITNESS',
                    'BRUNSWICK BILLIARDS',
                    'BRUNSWICK',
                    'BRUNSWICK CORPORATION',
                    'BRUNSWICK BILLIARDS LLC',
                    'LIFE FITNESS',
                    'LIFE FITNESS LLC'
                ]),
                'destination' => 'USA'
            ],
            [
                'name' => 'RSL LOGISTIC INDONESIA, PT',
                'code' => 'RSL',
                'buyers' => json_encode([
                    'CENTURY FURNITURE',
                    'CENTURY FURNITURE LLC',
                    'CENTURY FURNITURE COMPANY'
                ]),
                'destination' => 'USA'
            ],
            [
                'name' => 'ORIENT STAR SHIPPING, PT',
                'code' => 'OSS',
                'buyers' => json_encode([
                    'VANGUARD FURNITURE',
                    'VANGUARD FURNITURE LLC',
                    'VANGUARD FURNITURE COMPANY'
                ]),
                'destination' => 'USA'
            ]
        ];

        foreach ($forwarders as $forwarder) {
            Forwarder::updateOrCreate(
                ['code' => $forwarder['code']],
                $forwarder
            );
        }

        $this->command->info('✅ Forwarder data seeded successfully with ROWE mapping!');
    }
}