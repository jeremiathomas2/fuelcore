<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pumps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('station_id')->constrained()->cascadeOnDelete();
            $table->string('pump_number', 10);
            $table->string('manufacturer')->nullable();
            $table->string('model')->nullable();
            $table->string('serial_number')->nullable();
            $table->string('controller_address')->nullable();
            $table->string('device_identifier')->nullable();
            $table->enum('status', ['online', 'offline', 'maintenance', 'error'])->default('online')->index();
            $table->date('installation_date')->nullable();
            $table->timestamp('last_communication_at')->nullable();
            $table->string('totalizer_status')->nullable();
            $table->text('notes')->nullable();
            $table->softDeletes();
            $table->timestamps();
            $table->unique(['station_id', 'pump_number']);
        });

        Schema::create('nozzles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('station_id')->constrained()->cascadeOnDelete();
            $table->foreignId('pump_id')->constrained()->cascadeOnDelete();
            $table->foreignId('fuel_product_id')->constrained();
            $table->string('nozzle_number', 10);
            $table->enum('status', ['idle', 'dispensing', 'completed', 'offline', 'error', 'maintenance'])->default('idle')->index();
            $table->decimal('meter_start', 12, 3)->default(0);
            $table->decimal('meter_current', 12, 3)->default(0);
            $table->decimal('total_litres', 12, 3)->default(0);
            $table->timestamp('last_transaction_at')->nullable();
            $table->softDeletes();
            $table->timestamps();
            $table->unique(['pump_id', 'nozzle_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nozzles');
        Schema::dropIfExists('pumps');
    }
};