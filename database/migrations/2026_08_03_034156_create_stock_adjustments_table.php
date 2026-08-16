<?php

use App\Enums\StockAdjustmentStatus;
use App\Enums\StockAdjustmentType;
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
        Schema::create('stock_adjustments', function (Blueprint $table) {
            $table->id();
            $table
                ->foreignId('warehouse_id')
                ->constrained()
                ->restrictOnDelete();
            $table
                ->foreignId('adjusted_by')
                ->constrained('users')
                ->restrictOnDelete();

            $table->string('adjustment_number')->unique();
            $table->enum(
                'type',
                array_column(StockAdjustmentType::cases(), 'value'),
            );
            $table->string('reason');
            $table->date('date');
            $table
                ->string('status')
                ->default(StockAdjustmentStatus::STATUS_PENDING);
            $table->text('notes')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_adjustments');
    }
};
