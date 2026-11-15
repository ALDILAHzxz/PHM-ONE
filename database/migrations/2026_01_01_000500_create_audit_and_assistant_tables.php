<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Single append-only trail. The specification lists ActivityLog and
        // AuditEvent separately; they are merged here because both record
        // "who did what to which record when". The management "Aktivitas
        // Terbaru" feed is a filtered read of this table.
        Schema::create('audit_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action', 64);        // production_report.created
            $table->string('entity_type', 64);   // ProductionReport
            $table->unsignedBigInteger('entity_id')->nullable();
            $table->string('summary')->nullable(); // human readable, Bahasa Indonesia
            $table->json('metadata')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->boolean('is_visible_feed')->default(true);
            $table->timestamps();

            $table->index(['entity_type', 'entity_id']);
            $table->index(['action', 'created_at']);
            $table->index('created_at');
        });

        Schema::create('beesha_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('conversation_id', 64)->index();
            $table->string('author', 16);          // user | assistant
            $table->text('content');
            $table->string('tool_used', 64)->nullable();
            $table->string('provider', 32)->nullable(); // demo | anthropic
            $table->json('data_context')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('beesha_messages');
        Schema::dropIfExists('audit_events');
    }
};
