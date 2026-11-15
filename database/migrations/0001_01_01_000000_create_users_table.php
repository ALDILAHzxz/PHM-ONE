<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * PHM One — identity table.
 *
 * Design note: the master specification lists `User` and `Employee` as separate
 * entities. For this MVP they are consolidated into a single `users` table
 * because every application identity IS a PHM employee and a 1:1 split would
 * add joins without adding meaning. The employee attributes keep their domain
 * names (employee_number, full_name, department_id, position, status) so the
 * split can be reintroduced later without renaming columns.
 * See docs/DATABASE_SCHEMA.md.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('departments', function (Blueprint $table) {
            $table->id();
            $table->string('code', 32)->unique();
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('work_shifts', function (Blueprint $table) {
            $table->id();
            $table->string('code', 32)->unique();
            $table->string('name');
            $table->time('start_time');
            $table->time('end_time');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('users', function (Blueprint $table) {
            $table->id();

            // Nomor Induk Karyawan — internal company employee identifier.
            // This is NOT an Indonesian national ID (NIK Dukcapil).
            $table->string('employee_number', 32)->unique();

            $table->string('full_name');
            $table->string('email')->nullable()->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('phone', 32)->nullable();
            $table->string('password');

            $table->foreignId('department_id')->nullable()->constrained('departments')->nullOnDelete();
            $table->foreignId('work_shift_id')->nullable()->constrained('work_shifts')->nullOnDelete();

            $table->string('position')->nullable();        // Jabatan
            $table->string('work_location')->nullable();    // Lokasi kerja

            // active | suspended | inactive — only `active` may authenticate.
            $table->string('status', 24)->default('active');

            // Marks seeded demo identities so they can never be confused with
            // real employee accounts, and so demo login can only ever target them.
            $table->boolean('is_demo')->default(false);

            $table->timestamp('last_login_at')->nullable();
            $table->rememberToken();
            $table->timestamps();

            $table->index('status');
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('users');
        Schema::dropIfExists('work_shifts');
        Schema::dropIfExists('departments');
    }
};
