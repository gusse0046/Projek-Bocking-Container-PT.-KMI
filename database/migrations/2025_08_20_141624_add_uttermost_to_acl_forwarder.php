<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        try {
            // Find ACL forwarder
            $acl = DB::table('forwarders')->where('code', 'ACL')->first();
            
            if (!$acl) {
                Log::warning('ACL forwarder not found during UTTERMOST migration');
                return;
            }
            
            // Get current buyers
            $currentBuyers = json_decode($acl->buyers, true) ?? [];
            
            // Add THE UTTERMOST Co. variations
            $uttermostBuyers = [
                'THE UTTERMOST Co.',
                'THE UTTERMOST CO.',
                'UTTERMOST CO.',
                'THE UTTERMOST COMPANY',
                'UTTERMOST',
                'UTTERMOST LLC',
                'THE UTTERMOST LLC',
                'UTTERMOST COMPANY'
            ];
            
            // Merge with existing buyers
            $updatedBuyers = array_merge($currentBuyers, $uttermostBuyers);
            
            // Remove duplicates and reindex array
            $updatedBuyers = array_values(array_unique($updatedBuyers));
            
            // Update the forwarder
            $updateResult = DB::table('forwarders')
                ->where('code', 'ACL')
                ->update([
                    'buyers' => json_encode($updatedBuyers),
                    'updated_at' => now()
                ]);
            
            if ($updateResult) {
                Log::info('Successfully added UTTERMOST buyers to ACL forwarder', [
                    'added_buyers' => $uttermostBuyers,
                    'total_buyers' => count($updatedBuyers)
                ]);
            } else {
                Log::error('Failed to update ACL forwarder with UTTERMOST buyers');
            }
            
        } catch (\Exception $e) {
            Log::error('Error during UTTERMOST migration: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        try {
            // Find ACL forwarder
            $acl = DB::table('forwarders')->where('code', 'ACL')->first();
            
            if (!$acl) {
                Log::warning('ACL forwarder not found during UTTERMOST rollback');
                return;
            }
            
            $currentBuyers = json_decode($acl->buyers, true) ?? [];
            
            // Remove THE UTTERMOST variations
            $uttermostBuyers = [
                'THE UTTERMOST Co.',
                'THE UTTERMOST CO.',
                'UTTERMOST CO.',
                'THE UTTERMOST COMPANY',
                'UTTERMOST',
                'UTTERMOST LLC',
                'THE UTTERMOST LLC',
                'UTTERMOST COMPANY'
            ];
            
            // Filter out UTTERMOST buyers
            $updatedBuyers = array_diff($currentBuyers, $uttermostBuyers);
            $updatedBuyers = array_values($updatedBuyers);
            
            // Update the forwarder
            $updateResult = DB::table('forwarders')
                ->where('code', 'ACL')
                ->update([
                    'buyers' => json_encode($updatedBuyers),
                    'updated_at' => now()
                ]);
            
            if ($updateResult) {
                Log::info('Successfully removed UTTERMOST buyers from ACL forwarder');
            } else {
                Log::error('Failed to rollback ACL forwarder UTTERMOST buyers');
            }
            
        } catch (\Exception $e) {
            Log::error('Error during UTTERMOST rollback: ' . $e->getMessage());
            throw $e;
        }
    }
};