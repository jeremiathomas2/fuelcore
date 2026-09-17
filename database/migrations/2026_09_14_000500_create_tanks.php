<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tanks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('station_id')->constrained()->cascadeOnDelete();
            $table->foreignId('fuel_product_id')->constrained();
            $table->string('tank_number', 10);
            $table->decimal('capacity', 12, 3);
            $table->decimal('current_volume', 12, 3)->default(0);
            $table->decimal('min_level', 12, 3)->default(0);
            $table->decimal('max_level', 12, 3)->nullable();
            $table->decimal('temperature', 5, 2)->default(25);
            $table->decimal('water_level', 8, 3)->default(0);
            $table->enum('leak_status', ['no', 'yes', 'unknown'])->default('no');
            $table->enum('status', ['normal', 'low', 'warning', 'critical'])->default('normal')->index();
            $table->timestamp('last_reading_at')->nullable();
            $table->softDeletes();
            $table->timestamps();
            $table->unique(['station_id', 'tank_number']);
        });

        Schema::create('tank_readings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tank_id')->constrained()->cascadeOnDelete();
            $table->foreignId('station_id')->constrained()->cascadeOnDelete();
            $table->decimal('reading_litres', 12, 3);
            $table->decimal('temperature', 5, 2)->nullable();
            $table->decimal('water_level', 8, 3)->default(0);
            $table->boolean('leak_detected')->default(false);
            $table->enum('source', ['manual', 'atg', 'integration'])->default('manual');
            $table->timestamp('reading_at');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['tank_id', 'reading_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tank_readings');
        Schema::dropIfExists('tanks');
    }
};