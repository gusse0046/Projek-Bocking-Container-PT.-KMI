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
            // Change forwarder_code from VARCHAR(10) to VARCHAR(50)
            $table->string('forwarder_code', 50)->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('export_data', function (Blueprint $table) {
            // Revert back to VARCHAR(10)
            $table->string('forwarder_code', 10)->nullable()->change();
        });
    }
};