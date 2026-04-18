<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

return new class extends Migration
{
    /**
     * Run the migrations - Add BRUNSWICK BILLIARDS-LIFE FITNE to MAXIMOS
     */
    public function up(): void
    {
        try {
            Log::info('Starting BRUNSWICK mapping to MAXIMOS forwarder...');
            
            // Find MAXIMOS forwarder
            $maximos = DB::table('forwarders')->where('code', 'MGL')->first();
            
            if (!$maximos) {
                Log::error('MAXIMOS (MGL) forwarder not found in database');
                // Create MAXIMOS if it doesn't exist
                $this->createMaximosForwarder();
                return;
            }
            
            // Get current buyers
            $currentBuyers = json_decode($maximos->buyers, true) ?? [];
            Log::info('Current MAXIMOS buyers:', $currentBuyers);
            
            // Add BRUNSWICK BILLIARDS-LIFE FITNE variations
            $brunswickBuyers = [
                'BRUNSWICK BILLIARDS-LIFE FITNE',
                'BRUNSWICK BILLIARDS-LIFE FITNESS',
                'BRUNSWICK BILLIARDS',
                'LIFE FITNESS',
                'BRUNSWICK',
                'BRUNSWICK CORPORATION',
                'BRUNSWICK BILLIARDS LLC',
                'LIFE FITNESS LLC',
                // Keep existing ESCALADE buyers
                'INDIAN INDUSTRIES DBA ESCALADE SPORTS',
                'ESCALADE SPORTS',
                'INDIAN INDUSTRIES',
                'ESCALADE SPORTS LLC',
                'INDIAN INDUSTRIES LLC'
            ];
            
            // Merge with existing buyers and remove duplicates
            $updatedBuyers = array_values(array_unique(array_merge($currentBuyers, $brunswickBuyers)));
            
            // Update MAXIMOS forwarder with enhanced information
            $updateData = [
                'name' => 'MAXIMOS GLOBAL LOGISTIK',
                'buyers' => json_encode($updatedBuyers),
                'emails' => json_encode([
                    'maya.febrioletta@maximos.co.id',
                    'apri.permatasari@maximos.co.id', 
                    'rika.triwidiati@maximos.co.id',
                    'lilly@escaladesports.cn',
                    'sales2@pawindo.com',
                    'export.mgl@maximos.co.id'
                ]),
                'primary_email' => 'maya.febrioletta@maximos.co.id',
                'whatsapp_numbers' => json_encode([
                    '+6281234567801', // Maya
                    '+6281234567802', // Apri
                    '+6281234567803', // Rika
                    '+6281234567804'  // Export Team
                ]),
                'primary_whatsapp' => '+6281234567801',
                'destination' => 'USA - Midwest & Global',
                'contact_person' => 'Maya Febrioletta / Apri Permatasari / Rika Triwidiati',
                'phone' => '+62-21-2922-6666',
                'address' => 'Maximos Building, Jl. Kyai Tapa No. 1, Jakarta 11440',
                'email_notifications_enabled' => true,
                'whatsapp_notifications_enabled' => true,
                'is_active' => true,
                'company_type' => 'freight_forwarder',
                'service_type' => 'both',
                'service_routes' => json_encode([
                    'Indonesia-USA Midwest',
                    'Indonesia-Europe', 
                    'Indonesia-Brunswick Network',
                    'Indonesia-Escalade Network'
                ]),
                'updated_at' => now()
            ];
            
            $updateResult = DB::table('forwarders')
                ->where('code', 'MGL')
                ->update($updateData);
            
            if ($updateResult) {
                Log::info('Successfully updated MAXIMOS forwarder with BRUNSWICK mapping', [
                    'forwarder_code' => 'MGL',
                    'total_buyers' => count($updatedBuyers),
                    'new_buyers' => $brunswickBuyers,
                    'primary_contact' => 'maya.febrioletta@maximos.co.id'
                ]);
                
                echo "✅ MAXIMOS forwarder updated successfully\n";
                echo "📧 Primary contact: maya.febrioletta@maximos.co.id\n";
                echo "👥 Total buyers: " . count($updatedBuyers) . "\n";
                echo "🎯 BRUNSWICK BILLIARDS-LIFE FITNE mapping added\n";
            } else {
                Log::error('Failed to update MAXIMOS forwarder');
                throw new \Exception('Failed to update MAXIMOS forwarder');
            }
            
        } catch (\Exception $e) {
            Log::error('Error during BRUNSWICK mapping: ' . $e->getMessage());
            echo "❌ Migration failed: " . $e->getMessage() . "\n";
            throw $e;
        }
    }

    /**
     * Create MAXIMOS forwarder if it doesn't exist
     */
    private function createMaximosForwarder(): void
    {
        $maximosData = [
            'name' => 'MAXIMOS GLOBAL LOGISTIK',
            'code' => 'MGL',
            'buyers' => json_encode([
                'BRUNSWICK BILLIARDS-LIFE FITNE',
                'BRUNSWICK BILLIARDS-LIFE FITNESS',
                'BRUNSWICK BILLIARDS',
                'LIFE FITNESS',
                'BRUNSWICK',
                'INDIAN INDUSTRIES DBA ESCALADE SPORTS',
                'ESCALADE SPORTS',
                'INDIAN INDUSTRIES',
                'ESCALADE SPORTS LLC',
                'INDIAN INDUSTRIES LLC'
            ]),
            'emails' => json_encode([
                'maya.febrioletta@maximos.co.id',
                'apri.permatasari@maximos.co.id',
                'rika.triwidiati@maximos.co.id',
                'lilly@escaladesports.cn',
                'sales2@pawindo.com',
                'export.mgl@maximos.co.id'
            ]),
            'primary_email' => 'maya.febrioletta@maximos.co.id',
            'whatsapp_numbers' => json_encode([
                '+6281234567801',
                '+6281234567802', 
                '+6281234567803',
                '+6281234567804'
            ]),
            'primary_whatsapp' => '+6281234567801',
            'destination' => 'USA - Midwest & Global',
            'contact_person' => 'Maya Febrioletta / Apri Permatasari / Rika Triwidiati',
            'phone' => '+62-21-2922-6666',
            'address' => 'Maximos Building, Jl. Kyai Tapa No. 1, Jakarta 11440',
            'email_notifications_enabled' => true,
            'whatsapp_notifications_enabled' => true,
            'is_active' => true,
            'company_type' => 'freight_forwarder',
            'service_type' => 'both',
            'service_routes' => json_encode([
                'Indonesia-USA Midwest',
                'Indonesia-Europe',
                'Indonesia-Brunswick Network'
            ]),
            'created_at' => now(),
            'updated_at' => now()
        ];
        
        DB::table('forwarders')->insert($maximosData);
        
        Log::info('MAXIMOS forwarder created successfully', [
            'code' => 'MGL',
            'name' => 'MAXIMOS GLOBAL LOGISTIK'
        ]);
        
        echo "✅ MAXIMOS forwarder created successfully\n";
    }

    /**
     * Reverse the migrations
     */
    public function down(): void
    {
        try {
            Log::info('Rolling back BRUNSWICK mapping from MAXIMOS...');
            
            // Find MAXIMOS forwarder
            $maximos = DB::table('forwarders')->where('code', 'MGL')->first();
            
            if (!$maximos) {
                Log::warning('MAXIMOS forwarder not found during rollback');
                return;
            }
            
            $currentBuyers = json_decode($maximos->buyers, true) ?? [];
            
            // Remove BRUNSWICK buyers but keep original ESCALADE ones
            $brunswickBuyers = [
                'BRUNSWICK BILLIARDS-LIFE FITNE',
                'BRUNSWICK BILLIARDS-LIFE FITNESS',
                'BRUNSWICK BILLIARDS',
                'LIFE FITNESS', 
                'BRUNSWICK',
                'BRUNSWICK CORPORATION',
                'BRUNSWICK BILLIARDS LLC',
                'LIFE FITNESS LLC'
            ];
            
            // Keep only non-Brunswick buyers
            $updatedBuyers = array_values(array_diff($currentBuyers, $brunswickBuyers));
            
            // Update forwarder
            $updateResult = DB::table('forwarders')
                ->where('code', 'MGL')
                ->update([
                    'buyers' => json_encode($updatedBuyers),
                    'contact_person' => 'Maya Febrioletta / Apri Permatasari',
                    'service_routes' => json_encode([
                        'Indonesia-USA Midwest',
                        'Indonesia-Europe'
                    ]),
                    'updated_at' => now()
                ]);
            
            if ($updateResult) {
                Log::info('Successfully rolled back BRUNSWICK mapping from MAXIMOS');
                echo "✅ BRUNSWICK mapping rolled back successfully\n";
            } else {
                Log::error('Failed to rollback BRUNSWICK mapping');
            }
            
        } catch (\Exception $e) {
            Log::error('Error during BRUNSWICK rollback: ' . $e->getMessage());
            throw $e;
        }
    }
};