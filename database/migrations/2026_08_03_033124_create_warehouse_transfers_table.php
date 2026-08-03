<?php

use App\Enums\WarehouseTransferStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create("warehouse_transfers", function (Blueprint $table) {
            $table->id();
            $table
                ->foreignId("from_warehouse_id")
                ->constrained("warehouses")
                ->restrictOnDelete();
            $table
                ->foreignId("to_warehouse_id")
                ->constrained("warehouses")
                ->restrictOnDelete();
            $table
                ->foreignId("transferred_by")
                ->constrained("users")
                ->restrictOnDelete();
            $table
                ->foreignId("received_by")
                ->nullable()
                ->constrained("users")
                ->nullOnDelete();

            $table->string("transfer_number")->unique();
            $table->date("date");
            $table
                ->string("status")
                ->default(WarehouseTransferStatus::STATUS_PENDING);
            $table->text("notes")->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists("warehouse_transfers");
    }
};
