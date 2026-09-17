<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('alerts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('station_id')->nullable()->constrained()->cascadeOnDelete();
            $table->enum('severity', ['critical', 'warning', 'info'])->default('warning')->index();
            $table->string('title');
            $table->text('message');
            $table->string('entity_type')->nullable();
            $table->unsignedBigInteger('entity_id')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->foreignId('resolved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['severity', 'created_at']);
        });

        Schema::create('station_devices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('station_id')->constrained()->cascadeOnDelete();
            $table->string('device_code', 40)->unique();
            $table->string('name');
            $table->enum('device_type', ['fcc', 'pos', 'atg', 'gateway', 'other'])->default('fcc');
            $table->string('api_token', 100)->nullable();
            $table->timestamp('last_heartbeat_at')->nullable();
            $table->enum('status', ['online', 'offline', 'error'])->default('offline')->index();
            $table->json('config')->nullable();
            $table->timestamps();
        });

        Schema::create('integration_transactions', function (Blueprint $table) {
            $table->id();
            $table->string('station_code', 20)->index();
            $table->string('transaction_uuid', 40)->unique();
            $table->json('payload');
            $table->enum('status', ['received', 'processed', 'duplicate', 'failed'])->default('received')->index();
            $table->string('error_message')->nullable();
            $table->enum('sync_status', ['pending', 'success', 'failed'])->default('pending');
            $table->unsignedInteger('retry_count')->default(0);
            $table->dateTime('received_at');
            $table->dateTime('processed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('action');
            $table->string('module')->index();
            $table->unsignedBigInteger('record_id')->nullable();
            $table->string('description')->nullable();
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent')->nullable();
            $table->timestamps();
            $table->index(['module', 'record_id']);
        });

        Schema::create('system_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->string('group')->default('general')->index();
            $table->timestamps();
        });

        Schema::create('notifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('type');
            $table->morphs('notifiable');
            $table->text('data');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
        Schema::dropIfExists('system_settings');
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('integration_transactions');
        Schema::dropIfExists('station_devices');
        Schema::dropIfExists('alerts');
    }
};