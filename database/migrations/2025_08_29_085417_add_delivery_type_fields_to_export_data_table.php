<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('export_data', function (Blueprint $table) {
            // Add new delivery type fields
            $table->string('delivery_type', 10)->nullable()->after('sap_last_update')
                  ->comment('SAP Delivery Type (LFART): ZDO1=Export, ZDI1=Import, ZDL1=Local, etc.');
            
            $table->enum('delivery_classification', ['EXPORT', 'IMPORT', 'LOCAL', 'RETURN', 'OTHER', 'UNKNOWN'])
                  ->default('UNKNOWN')->after('delivery_type')
                  ->comment('Business classification based on delivery type');
            
            $table->string('operation_location', 50)->nullable()->after('delivery_classification')
                  ->comment('Operation location: Surabaya, Semarang, Jakarta, etc.');
            
            $table->string('route_destination', 100)->nullable()->after('operation_location')
                  ->comment('Final destination for routing purposes');
            
            $table->string('business_unit', 20)->nullable()->after('route_destination')
                  ->comment('Business unit: EXPORT_DEPT, IMPORT_DEPT, LOCAL_SALES');
            
            // Add indexes for efficient filtering
            $table->index('delivery_classification', 'idx_delivery_classification');
            $table->index('delivery_type', 'idx_delivery_type_full');
            $table->index('operation_location', 'idx_operation_location');
            $table->index('business_unit', 'idx_business_unit');
            
            // Composite indexes for dashboard filtering
            $table->index(['delivery_classification', 'operation_location'], 'idx_classification_location');
            $table->index(['delivery_classification', 'delivery_date'], 'idx_classification_date');
            $table->index(['delivery_classification', 'buyer'], 'idx_classification_buyer');
            $table->index(['delivery_classification', 'delivery_type', 'business_unit'], 'idx_full_dashboard_filter');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('export_data', function (Blueprint $table) {
            // Drop indexes first
            $table->dropIndex('idx_full_dashboard_filter');
            $table->dropIndex('idx_classification_buyer');
            $table->dropIndex('idx_classification_date');
            $table->dropIndex('idx_classification_location');
            $table->dropIndex('idx_business_unit');
            $table->dropIndex('idx_operation_location');
            $table->dropIndex('idx_delivery_type_full');
            $table->dropIndex('idx_delivery_classification');
            
            // Drop columns
            $table->dropColumn([
                'delivery_type',
                'delivery_classification', 
                'operation_location',
                'route_destination',
                'business_unit'
            ]);
        });
    }
};