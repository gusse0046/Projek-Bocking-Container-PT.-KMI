<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateImportDataTable extends Migration
{
    public function up()
    {
        Schema::create('import_data', function (Blueprint $table) {
            $table->id();
            $table->string('purchase_order');
            $table->string('vendor');
            $table->string('material_code');
            $table->text('material_description');
            $table->decimal('quantity', 10, 2);
            $table->decimal('value', 15, 2);
            $table->string('origin_country');
            $table->date('expected_arrival');
            $table->string('status')->default('pending');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('import_data');
    }
}