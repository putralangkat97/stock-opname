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
        Schema::create('goods_receipt_items', function (Blueprint $table) {
            $table->id();
            $table
                ->foreignId('goods_receipt_id')
                ->constrained()
                ->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();

            // Historical snapshot — see design notes on why these exist alongside product_id.
            $table->string('product_sku_snapshot');
            $table->string('product_name_snapshot');

            $table->integer('qty');
            $table->decimal('unit_price', 15, 2);
            $table->decimal('subtotal', 15, 2);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('goods_receipt_items');
    }
};
