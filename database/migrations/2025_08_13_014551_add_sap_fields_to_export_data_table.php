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
            // SAP Integration Fields - Primary
            $table->string('container_number')->nullable()->after('export_destination')->comment('Container number from SAP text Z202');
            $table->string('reference_invoice')->nullable()->after('container_number')->comment('Reference invoice from SAP text Z501');
            $table->string('plant', 10)->nullable()->after('reference_invoice')->comment('SAP Plant code');
            $table->string('shipping_point', 10)->nullable()->after('plant')->comment('SAP Shipping point');
            $table->date('delivery_date')->nullable()->after('shipping_point')->comment('SAP Delivery date');
            $table->date('created_date')->nullable()->after('delivery_date')->comment('SAP Created date');
            $table->string('forwarder_code', 10)->nullable()->after('created_date')->comment('Auto-assigned forwarder code');
            $table->timestamp('sap_synced_at')->nullable()->after('forwarder_code')->comment('Last SAP sync timestamp');
            
            // SAP Integration Fields - Extended
            $table->string('sap_delivery_status', 20)->nullable()->after('sap_synced_at')->comment('SAP goods movement status');
            $table->string('sap_customer_number', 20)->nullable()->after('sap_delivery_status')->comment('SAP customer number');
            $table->string('sap_material_group', 20)->nullable()->after('sap_customer_number')->comment('SAP material group');
            $table->string('sap_sales_unit', 10)->nullable()->after('sap_material_group')->comment('SAP sales unit of measure');
            $table->string('sap_weight_unit', 10)->nullable()->after('sap_sales_unit')->comment('SAP weight unit');
            $table->string('sap_volume_unit', 10)->nullable()->after('sap_weight_unit')->comment('SAP volume unit');
            $table->string('sap_created_by', 50)->nullable()->after('sap_volume_unit')->comment('SAP created by user');
            $table->timestamp('sap_last_update')->nullable()->after('sap_created_by')->comment('SAP last update timestamp');
            
            // NEW: Delivery Type Classification Fields
            $table->string('delivery_type', 10)->nullable()->after('sap_last_update')->comment('LFART field from SAP (ZDL1, ZDO1, ZDL2, ZDO2, ZDR1, ZDR2)');
            $table->string('delivery_type_classification', 20)->default('UNKNOWN')->after('delivery_type')->comment('Classification: EXPORT/IMPORT/DOMESTIC/UNKNOWN');
            $table->string('export_location', 50)->nullable()->after('delivery_type_classification')->comment('Export location: Surabaya/Semarang/Return/Other');
            
            // Indexes for performance
            $table->index(['delivery', 'no_item'], 'idx_delivery_item');
            $table->index('sap_synced_at', 'idx_sap_synced');
            $table->index('delivery_date', 'idx_delivery_date');
            $table->index('forwarder_code', 'idx_forwarder_code');
            $table->index(['buyer', 'forwarder_code'], 'idx_buyer_forwarder');
            $table->index('container_number', 'idx_container_number');
            $table->index('plant', 'idx_plant');
            
            // NEW: Indexes for delivery type filtering
            $table->index('delivery_type_classification', 'idx_export_classification');
            $table->index('delivery_type', 'idx_delivery_type');
            $table->index('export_location', 'idx_export_location');
            $table->index(['delivery_type_classification', 'export_location'], 'idx_class_location');
            $table->index(['delivery_type_classification', 'delivery_type'], 'idx_class_type');
            $table->index(['delivery_type_classification', 'reference_invoice'], 'idx_class_invoice');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('export_data', function (Blueprint $table) {
            // Drop indexes first
            $table->dropIndex('idx_delivery_item');
            $table->dropIndex('idx_sap_synced');
            $table->dropIndex('idx_delivery_date');
            $table->dropIndex('idx_forwarder_code');
            $table->dropIndex('idx_buyer_forwarder');
            $table->dropIndex('idx_container_number');
            $table->dropIndex('idx_plant');
            
            // Drop new delivery type indexes
            $table->dropIndex('idx_class_invoice');
            $table->dropIndex('idx_class_type');
            $table->dropIndex('idx_class_location');
            $table->dropIndex('idx_export_location');
            $table->dropIndex('idx_delivery_type');
            $table->dropIndex('idx_export_classification');
            
            // Drop SAP integration columns
            $table->dropColumn([
                'container_number',
                'reference_invoice',
                'plant',
                'shipping_point',
                'delivery_date',
                'created_date',
                'forwarder_code',
                'sap_synced_at',
                'sap_delivery_status',
                'sap_customer_number',
                'sap_material_group',
                'sap_sales_unit',
                'sap_weight_unit',
                'sap_volume_unit',
                'sap_created_by',
                'sap_last_update',
                // NEW: Drop delivery type columns
                'delivery_type',
                'delivery_type_classification',
                'export_location'
            ]);
        });
    }
};