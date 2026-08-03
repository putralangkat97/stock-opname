<?php

use App\Enums\GoodsReceiptStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create("goods_receipts", function (Blueprint $table) {
            $table->id();
            $table->foreignId("supplier_id")->constrained()->restrictOnDelete();
            $table
                ->foreignId("warehouse_id")
                ->constrained()
                ->restrictOnDelete();
            $table
                ->foreignId("received_by")
                ->constrained("users")
                ->restrictOnDelete();

            $table->string("receipt_number")->unique();
            $table->string("po_number")->nullable();
            $table->date("date");
            $table->string("status")->default(GoodsReceiptStatus::STATUS_DRAFT);
            $table->decimal("total_amount", 15, 2)->default(0);
            $table->text("notes")->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists("goods_receipts");
    }
};
