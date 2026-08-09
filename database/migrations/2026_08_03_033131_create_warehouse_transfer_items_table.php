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
        Schema::create('warehouse_transfer_items', function (Blueprint $table) {
            $table->id();
            $table
                ->foreignId('warehouse_transfer_id')
                ->constrained()
                ->cascadeOnDelete();
            // References the SOURCE warehouse's product row — the destination
            // row is located/created dynamically when the transfer completes.
            $table->foreignId('product_id')->constrained()->restrictOnDelete();

            $table->string('product_sku_snapshot');
            $table->string('product_name_snapshot');
            $table->integer('qty');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('warehouse_transfer_items');
    }
};
