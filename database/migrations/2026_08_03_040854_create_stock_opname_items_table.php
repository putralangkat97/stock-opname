<?php

use App\Enums\StockOpnameItemStatus;
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
        Schema::create('stock_opname_items', function (Blueprint $table) {
            $table->id();
            $table
                ->foreignId('stock_opname_id')
                ->constrained()
                ->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table
                ->foreignId('scanned_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->string('product_sku_snapshot');
            $table->string('product_name_snapshot');

            // Snapshotted from product.stock the moment this line is added to the opname.
            $table->integer('system_qty');
            // Null until a Supervisor actually scans/counts this line.
            $table->integer('physical_qty')->nullable();

            $table->timestamp('scanned_at')->nullable();
            $table->text('notes')->nullable();
            $table
                ->string('status')
                ->default(StockOpnameItemStatus::STATUS_UNCOUNTED);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_opname_items');
    }
};
