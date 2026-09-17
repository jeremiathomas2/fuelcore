<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shifts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('station_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employee_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('pump_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('opening_cash', 14, 2)->default(0);
            $table->decimal('expected_cash', 14, 2)->nullable();
            $table->decimal('actual_cash', 14, 2)->nullable();
            $table->decimal('cash_variance', 14, 2)->nullable();
            $table->decimal('opening_meter', 12, 3)->nullable();
            $table->decimal('closing_meter', 12, 3)->nullable();
            $table->enum('status', ['open', 'closing', 'closed', 'cancelled'])->default('open')->index();
            $table->dateTime('opened_at');
            $table->dateTime('closed_at')->nullable();
            $table->string('notes')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::table('fuel_transactions', function (Blueprint $table) {
            $table->foreign('shift_id')->references('id')->on('shifts')->nullOnDelete();
        });

        Schema::create('shift_readings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shift_id')->constrained()->cascadeOnDelete();
            $table->foreignId('pump_id')->constrained()->cascadeOnDelete();
            $table->foreignId('nozzle_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('meter_start', 12, 3)->default(0);
            $table->decimal('meter_end', 12, 3)->default(0);
            $table->decimal('litres', 12, 3)->default(0);
            $table->timestamps();
        });

        Schema::create('reconciliations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('station_id')->constrained()->cascadeOnDelete();
            $table->foreignId('shift_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('opening_stock', 14, 3)->default(0);
            $table->decimal('deliveries_qty', 14, 3)->default(0);
            $table->decimal('adjustments_qty', 14, 3)->default(0);
            $table->decimal('sales_qty', 14, 3)->default(0);
            $table->decimal('expected_closing', 14, 3)->default(0);
            $table->decimal('actual_reading', 14, 3)->default(0);
            $table->decimal('variance_litres', 14, 3)->default(0);
            $table->decimal('variance_pct', 8, 3)->default(0);
            $table->decimal('variance_value', 14, 2)->default(0);
            $table->enum('status', ['draft', 'reconciled', 'disputed'])->default('draft')->index();
            $table->timestamp('reconciled_at')->nullable();
            $table->foreignId('reconciled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reconciliations');
        Schema::dropIfExists('shift_readings');
        Schema::table('fuel_transactions', function (Blueprint $table) {
            $table->dropForeign(['shift_id']);
        });
        Schema::dropIfExists('shifts');
    }
};