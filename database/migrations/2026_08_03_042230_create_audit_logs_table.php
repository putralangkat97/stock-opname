<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create("audit_logs", function (Blueprint $table) {
            $table->id();
            $table
                ->foreignId("user_id")
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            // Snapshot, not a FK — the role name as it was AT THE TIME of the
            // action. Roles can change later (via Spatie); this must not.
            $table->string("role_snapshot")->nullable();

            $table->string("action"); // created, updated, deleted, approved, rejected, started, completed...
            $table->string("module"); // human-readable label, e.g. "Goods Receipt"

            // Polymorphic link to the actual record this log is about, so you can
            // query "show me everything that happened to this document" directly,
            // not just filter by module name.
            $table->nullableMorphs("auditable");

            $table->json("details")->nullable();
            $table->string("ip_address")->nullable();

            // created_at only, really — audit logs are never updated, but keeping
            // both is harmless and consistent with the rest of the schema.
            $table->timestamps();

            // Audit logs are intentionally NOT soft-deletable — they must be
            // immutable/append-only to be trustworthy as an audit trail.
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists("audit_logs");
    }
};
