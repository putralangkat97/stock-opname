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
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained()->restrictOnDelete();
            $table->foreignId('brand_id')->constrained()->restrictOnDelete();
            $table->foreignId('unit_id')->constrained()->restrictOnDelete();
            $table
                ->foreignId('warehouse_id')
                ->constrained()
                ->restrictOnDelete();
            $table
                ->foreignId('bin_location_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            // Unique PER WAREHOUSE, not globally — the same SKU can have a row in
            // multiple warehouses (each row is that warehouse's stock of the SKU).
            // See Warehouse Transfer design notes for why this changed from a
            // straight ->unique().
            $table->string('sku');
            $table->string('barcode')->nullable()->unique();
            $table->string('qr_code')->nullable()->unique();
            $table->string('name');

            $table->integer('stock')->default(0);
            $table->integer('min_stock')->default(0);
            $table->integer('max_stock')->nullable();

            $table->decimal('cost_price', 15, 2)->default(0);
            $table->decimal('selling_price', 15, 2)->default(0);

            $table->date('last_opname_date')->nullable();
            $table->boolean('is_fast_moving')->default(false);
            $table->string('image_url')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->unique(['sku', 'warehouse_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
