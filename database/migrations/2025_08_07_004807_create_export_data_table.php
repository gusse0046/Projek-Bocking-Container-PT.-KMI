<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateExportDataTable extends Migration
{
    public function up()
    {
        Schema::create('export_data', function (Blueprint $table) {
            $table->id();
            $table->string('delivery');
            $table->string('no_item');
            $table->string('material');
            $table->text('description');
            $table->string('proforma_shipping_instruction');
            $table->string('buyer');
            $table->decimal('quantity', 10, 2);
            $table->decimal('volume', 10, 2);
            $table->decimal('weight', 10, 2);
            $table->string('export_destination')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('export_data');
    }
}