<?php

use App\Enums\StockOpnameStatus;
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
        Schema::create('stock_opnames', function (Blueprint $table) {
            $table->id();
            $table
                ->foreignId('warehouse_id')
                ->constrained()
                ->restrictOnDelete();
            // Single assignee for MVP. If you need multiple supervisors on one
            // opname later, swap this for a stock_opname_user pivot table.
            $table
                ->foreignId('assigned_to')
                ->constrained('users')
                ->restrictOnDelete();
            $table
                ->foreignId('approved_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->string('opname_number')->unique();
            $table->string('title');
            $table->date('start_date');
            $table->date('completed_date')->nullable();
            $table->string('status')->default(StockOpnameStatus::STATUS_DRAFT);

            $table->integer('total_system_qty')->default(0);
            $table->integer('total_physical_qty')->default(0);
            $table->integer('total_variance_qty')->default(0);
            $table->decimal('total_variance_value', 15, 2)->default(0);

            $table->text('notes')->nullable();
            $table->timestamp('approved_at')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_opnames');
    }
};
