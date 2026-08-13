<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            // The original migration's enum('type', [StockAdjustmentType::class])
            // built this constraint from the literal class-name string instead
            // of the enum's actual case values — it only ever allowed the
            // string "App\Enums\StockAdjustmentType", never 'IN' or 'OUT'.
            DB::statement('ALTER TABLE stock_adjustments DROP CONSTRAINT IF EXISTS stock_adjustments_type_check');
            DB::statement("ALTER TABLE stock_adjustments ADD CONSTRAINT stock_adjustments_type_check CHECK (type IN ('IN', 'OUT'))");
        }

        // SQLite's enum() implementation didn't enforce this the same way,
        // which is why this only ever surfaced on Postgres — nothing to
        // repair there.
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE stock_adjustments DROP CONSTRAINT IF EXISTS stock_adjustments_type_check');
        }
    }
};
