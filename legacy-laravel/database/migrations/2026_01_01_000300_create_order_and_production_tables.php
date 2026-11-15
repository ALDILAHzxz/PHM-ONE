<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_number', 48)->unique();
            $table->foreignId('partner_id')->constrained()->restrictOnDelete();
            $table->string('po_number', 64);
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->unsignedInteger('quantity');
            $table->date('delivery_target_date');
            // baru | diproses | siap_kirim | sebagian_terkirim | selesai | dibatalkan
            $table->string('status', 32)->default('baru');
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['partner_id', 'po_number']);
            $table->index(['status', 'delivery_target_date']);
        });

        Schema::create('work_orders', function (Blueprint $table) {
            $table->id();
            $table->string('wo_number', 48)->unique();
            $table->foreignId('customer_order_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->unsignedInteger('target_quantity');
            $table->date('planned_date');
            // direncanakan | dirilis | berjalan | selesai | ditutup
            $table->string('status', 32)->default('direncanakan');
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['status', 'planned_date']);
        });

        Schema::create('production_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('work_order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->foreignId('work_shift_id')->nullable()->constrained('work_shifts')->nullOnDelete();
            $table->foreignId('production_stage_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('work_station_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedInteger('assigned_quantity');
            $table->date('planned_date');
            // ditugaskan | berjalan | selesai | ditutup | dibatalkan
            $table->string('status', 32)->default('ditugaskan');
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['user_id', 'planned_date', 'status']);
            $table->index(['work_order_id', 'status']);
        });

        Schema::create('production_reports', function (Blueprint $table) {
            $table->id();
            $table->string('report_number', 48)->unique();
            $table->foreignId('production_assignment_id')->constrained()->restrictOnDelete();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();

            $table->unsignedInteger('quantity_completed');

            $table->boolean('has_issue')->default(false);
            // mesin | material | kualitas | lainnya
            $table->string('issue_category', 32)->nullable();
            $table->text('issue_description')->nullable();
            $table->text('additional_notes')->nullable();

            // menunggu_pemeriksaan | terverifikasi | perlu_perbaikan | dibatalkan
            // Counting rules (see docs/DATABASE_SCHEMA.md):
            //   reported  = menunggu_pemeriksaan + terverifikasi
            //   verified  = terverifikasi only
            //   excluded  = perlu_perbaikan, dibatalkan
            $table->string('status', 32)->default('menunggu_pemeriksaan');

            // Set when accepted above the remaining target under the documented
            // demo overproduction exception. Flagged for supervisor attention.
            $table->boolean('is_exception')->default(false);

            $table->timestamp('submitted_at');

            // Guards against double submission (double click / retried request).
            $table->string('idempotency_key', 64)->unique();

            $table->timestamps();

            $table->index(['production_assignment_id', 'status']);
            $table->index(['status', 'submitted_at']);
            $table->index(['user_id', 'submitted_at']);
        });

        Schema::create('production_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('production_report_id')->constrained()->cascadeOnDelete();
            $table->foreignId('reviewed_by')->constrained('users')->restrictOnDelete();
            // terverifikasi | perlu_perbaikan
            $table->string('status', 32);
            $table->text('review_notes')->nullable();
            $table->timestamp('reviewed_at');
            $table->timestamps();

            $table->index(['production_report_id', 'reviewed_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('production_reviews');
        Schema::dropIfExists('production_reports');
        Schema::dropIfExists('production_assignments');
        Schema::dropIfExists('work_orders');
        Schema::dropIfExists('customer_orders');
    }
};
