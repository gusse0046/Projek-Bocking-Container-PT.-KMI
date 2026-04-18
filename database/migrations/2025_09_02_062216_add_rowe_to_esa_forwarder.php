<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

return new class extends Migration
{
    /**
     * Run the migrations - Add ROWE FINE FURNITURE INC to ESA forwarder
     */
    public function up(): void
    {
        try {
            Log::info('Starting ROWE mapping to ESA forwarder...');
            
            // Find ESA forwarder
            $esa = DB::table('forwarders')->where('code', 'ESA')->first();
            
            if (!$esa) {
                Log::error('ESA (EVERGREEN SHIPPING AGENCY) forwarder not found in database');
                // Create ESA if it doesn't exist
                $this->createEsaForwarder();
                return;
            }
            
            // Get current buyers
            $currentBuyers = json_decode($esa->buyers, true) ?? [];
            Log::info('Current ESA buyers:', $currentBuyers);
            
            // Add ROWE FINE FURNITURE INC variations
            $roweBuyers = [
                'ROWE FINE FURNITURE INC',
                'ROWE FINE FURNITURE',
                'ROWE FURNITURE',
                'ROWE',
                'ROWE FINE FURNITURE INC.',
                'ROWE FINE FURNITURE LLC',
                'ROWE FURNITURE LLC',
                // Keep existing buyers
                'WEST ELM',
                'POTTERY BARN',
                'POTTERY BARN KIDS', 
                'WILLIAMS SONOMA',
                'WILLIAMS-SONOMA'
            ];
            
            // Merge with existing buyers and remove duplicates
            $updatedBuyers = array_values(array_unique(array_merge($currentBuyers, $roweBuyers)));
            
            // Update ESA forwarder with enhanced information
            $updateData = [
                'name' => 'EVERGREEN SHIPPING AGENCY, PT',
                'buyers' => json_encode($updatedBuyers),
                'emails' => json_encode([
                    'info@evergreen-shipping.co.id',
                    'operations@evergreen-shipping.co.id',
                    'export.esa@evergreen-shipping.co.id',
                    'booking@evergreen-shipping.co.id',
                    'rowe.support@evergreen-shipping.co.id'
                ]),
                'primary_email' => 'info@evergreen-shipping.co.id',
                'whatsapp_numbers' => json_encode([
                    '+6286789012345', // Operations
                    '+6286789012346', // Booking
                    '+6286789012347', // Export Team
                    '+6286789012348'  // ROWE Support
                ]),
                'primary_whatsapp' => '+6286789012345',
                'destination' => 'USA - Multi-Destination',
                'contact_person' => 'Evergreen Operations Team / ROWE Support',
                'phone' => '+62-21-2922-9999',
                'address' => 'Evergreen Building, Jl. Hayam Wuruk No. 1, Jakarta 11180',
                'email_notifications_enabled' => true,
                'whatsapp_notifications_enabled' => true,
                'is_active' => true,
                'company_type' => 'shipping_line',
                'service_type' => 'FCL',
                'service_routes' => json_encode([
                    'Indonesia-USA',
                    'Indonesia-Europe',
                    'Indonesia-Middle East',
                    'Indonesia-ROWE Network'
                ]),
                'updated_at' => now()
            ];
            
            $updateResult = DB::table('forwarders')
                ->where('code', 'ESA')
                ->update($updateData);
            
            if ($updateResult) {
                Log::info('Successfully updated ESA forwarder with ROWE mapping', [
                    'forwarder_code' => 'ESA',
                    'total_buyers' => count($updatedBuyers),
                    'new_buyers' => $roweBuyers,
                    'primary_contact' => 'info@evergreen-shipping.co.id'
                ]);
                
                echo "✅ ESA forwarder updated successfully\n";
                echo "📧 Primary contact: info@evergreen-shipping.co.id\n";
                echo "👥 Total buyers: " . count($updatedBuyers) . "\n";
                echo "🎯 ROWE FINE FURNITURE INC mapping added\n";
                
                // Update existing export data with ROWE buyer
                $this->updateRoweExportData();
                
            } else {
                Log::error('Failed to update ESA forwarder');
                throw new \Exception('Failed to update ESA forwarder');
            }
            
        } catch (\Exception $e) {
            Log::error('Error during ROWE mapping: ' . $e->getMessage());
            echo "❌ Migration failed: " . $e->getMessage() . "\n";
            throw $e;
        }
    }

    /**
     * Update existing export data with ROWE buyers to use ESA forwarder
     */
    private function updateRoweExportData(): void
    {
        try {
            $roweBuyerVariations = [
                'ROWE FINE FURNITURE INC',
                'ROWE FINE FURNITURE',
                'ROWE FURNITURE',
                'ROWE',
                'ROWE FINE FURNITURE INC.',
                'ROWE FINE FURNITURE LLC',
                'ROWE FURNITURE LLC'
            ];
            
            $updatedCount = 0;
            
            foreach ($roweBuyerVariations as $buyerName) {
                $updated = DB::table('export_data')
                    ->where('buyer', $buyerName)
                    ->whereNotNull('buyer')
                    ->update([
                        'forwarder_code' => 'ESA',
                        'sap_last_update' => now()
                    ]);
                
                $updatedCount += $updated;
                
                if ($updated > 0) {
                    Log::info("Updated {$updated} records for buyer: {$buyerName}");
                }
            }
            
            if ($updatedCount > 0) {
                echo "📊 Updated {$updatedCount} existing export records with ROWE → ESA mapping\n";
                Log::info("Successfully updated {$updatedCount} ROWE export data records to ESA forwarder");
            } else {
                echo "📊 No existing ROWE export records found to update\n";
            }
            
        } catch (\Exception $e) {
            Log::error('Error updating ROWE export data: ' . $e->getMessage());
            echo "⚠️ Warning: Could not update existing ROWE export data\n";
        }
    }

    /**
     * Create ESA forwarder if it doesn't exist
     */
    private function createEsaForwarder(): void
    {
        $esaData = [
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
            'emails' => json_encode([
                'info@evergreen-shipping.co.id',
                'operations@evergreen-shipping.co.id',
                'export.esa@evergreen-shipping.co.id',
                'booking@evergreen-shipping.co.id'
            ]),
            'primary_email' => 'info@evergreen-shipping.co.id',
            'whatsapp_numbers' => json_encode([
                '+6286789012345',
                '+6286789012346',
                '+6286789012347'
            ]),
            'primary_whatsapp' => '+6286789012345',
            'destination' => 'USA - Multi-Destination',
            'contact_person' => 'Evergreen Operations Team',
            'phone' => '+62-21-2922-9999',
            'address' => 'Evergreen Building, Jl. Hayam Wuruk No. 1, Jakarta 11180',
            'email_notifications_enabled' => true,
            'whatsapp_notifications_enabled' => true,
            'is_active' => true,
            'company_type' => 'shipping_line',
            'service_type' => 'FCL',
            'service_routes' => json_encode([
                'Indonesia-USA',
                'Indonesia-Europe',
                'Indonesia-Middle East'
            ]),
            'created_at' => now(),
            'updated_at' => now()
        ];
        
        DB::table('forwarders')->insert($esaData);
        
        Log::info('ESA forwarder created successfully with ROWE mapping', [
            'code' => 'ESA',
            'name' => 'EVERGREEN SHIPPING AGENCY, PT'
        ]);
        
        echo "✅ ESA forwarder created successfully with ROWE mapping\n";
    }

    /**
     * Reverse the migrations
     */
    public function down(): void
    {
        try {
            Log::info('Rolling back ROWE mapping from ESA...');
            
            // Find ESA forwarder
            $esa = DB::table('forwarders')->where('code', 'ESA')->first();
            
            if (!$esa) {
                Log::warning('ESA forwarder not found during rollback');
                return;
            }
            
            $currentBuyers = json_decode($esa->buyers, true) ?? [];
            
            // Remove ROWE buyers but keep original ones
            $roweBuyers = [
                'ROWE FINE FURNITURE INC',
                'ROWE FINE FURNITURE',
                'ROWE FURNITURE',
                'ROWE',
                'ROWE FINE FURNITURE INC.',
                'ROWE FINE FURNITURE LLC',
                'ROWE FURNITURE LLC'
            ];
            
            // Keep only non-ROWE buyers
            $updatedBuyers = array_values(array_diff($currentBuyers, $roweBuyers));
            
            // Update forwarder
            $updateResult = DB::table('forwarders')
                ->where('code', 'ESA')
                ->update([
                    'buyers' => json_encode($updatedBuyers),
                    'contact_person' => 'Evergreen Operations Team',
                    'service_routes' => json_encode([
                        'Indonesia-USA',
                        'Indonesia-Europe',
                        'Indonesia-Middle East'
                    ]),
                    'updated_at' => now()
                ]);
            
            if ($updateResult) {
                Log::info('Successfully rolled back ROWE mapping from ESA');
                echo "✅ ROWE mapping rolled back successfully\n";
                
                // Reset ROWE export data to unassigned
                DB::table('export_data')
                    ->whereIn('buyer', $roweBuyers)
                    ->update([
                        'forwarder_code' => 'UNASSIGNED',
                        'sap_last_update' => now()
                    ]);
                    
                echo "📊 ROWE export data reset to UNASSIGNED\n";
            } else {
                Log::error('Failed to rollback ROWE mapping');
            }
            
        } catch (\Exception $e) {
            Log::error('Error during ROWE rollback: ' . $e->getMessage());
            throw $e;
        }
    }
};