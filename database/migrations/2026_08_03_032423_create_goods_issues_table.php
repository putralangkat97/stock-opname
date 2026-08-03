<?php

use App\Enums\GoodsIssueStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create("goods_issues", function (Blueprint $table) {
            $table->id();
            $table->foreignId("customer_id")->constrained()->restrictOnDelete();
            $table
                ->foreignId("warehouse_id")
                ->constrained()
                ->restrictOnDelete();
            $table
                ->foreignId("issued_by")
                ->constrained("users")
                ->restrictOnDelete();

            $table->string("issue_number")->unique();
            $table->string("so_number")->nullable();
            $table->date("date");
            $table->string("status")->default(GoodsIssueStatus::STATUS_DRAFT);
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
        Schema::dropIfExists("goods_issues");
    }
};
