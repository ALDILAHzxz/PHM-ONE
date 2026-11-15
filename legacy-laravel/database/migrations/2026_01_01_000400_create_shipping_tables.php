<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shipments', function (Blueprint $table) {
            $table->id();
            $table->string('shipment_number', 48)->unique();
            $table->foreignId('customer_order_id')->constrained()->restrictOnDelete();
            $table->unsignedInteger('quantity_shipped');
            $table->string('delivery_note_number', 64)->nullable(); // Nomor surat jalan
            $table->foreignId('driver_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('destination')->nullable();

            // disiapkan | dikirim | tiba | diterima | kendala | dibatalkan
            $table->string('status', 32)->default('disiapkan');

            $table->text('notes')->nullable();
            $table->timestamp('shipped_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('idempotency_key', 64)->nullable()->unique();
            $table->timestamps();

            $table->index(['status', 'created_at']);
            $table->index(['driver_id', 'status']);
        });

        Schema::create('delivery_status_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shipment_id')->constrained()->cascadeOnDelete();
            $table->string('status', 32);
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('recipient_name')->nullable(); // proof of delivery (minimal)
            $table->text('notes')->nullable();
            $table->timestamp('occurred_at');
            $table->timestamps();

            $table->index(['shipment_id', 'occurred_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('delivery_status_events');
        Schema::dropIfExists('shipments');
    }
};
