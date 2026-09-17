<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('suppliers', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique();
            $table->string('name');
            $table->string('contact_person')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->string('address')->nullable();
            $table->string('tax_id')->nullable();
            $table->enum('status', ['active', 'inactive'])->default('active')->index();
            $table->unsignedTinyInteger('performance_rating')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('fuel_deliveries', function (Blueprint $table) {
            $table->id();
            $table->string('delivery_number', 30)->unique();
            $table->foreignId('station_id')->constrained()->cascadeOnDelete();
            $table->foreignId('supplier_id')->constrained()->cascadeOnDelete();
            $table->string('driver_name')->nullable();
            $table->string('vehicle_number')->nullable();
            $table->decimal('ordered_qty', 12, 3)->default(0);
            $table->decimal('delivered_qty', 12, 3)->nullable();
            $table->decimal('expected_qty', 12, 3)->nullable();
            $table->dateTime('delivery_date');
            $table->string('receiving_employee')->nullable();
            $table->decimal('meter_reading_start', 12, 3)->nullable();
            $table->decimal('meter_reading_end', 12, 3)->nullable();
            $table->decimal('variance', 12, 3)->nullable();
            $table->enum('status', ['scheduled', 'in_transit', 'receiving', 'completed', 'reconciled', 'disputed', 'cancelled'])->default('scheduled')->index();
            $table->text('notes')->nullable();
            $table->foreignId('completed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('completed_at')->nullable();
            $table->softDeletes();
            $table->timestamps();
            $table->index(['station_id', 'status']);
        });

        Schema::create('fuel_delivery_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fuel_delivery_id')->constrained()->cascadeOnDelete();
            $table->foreignId('fuel_product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('tank_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('ordered_qty', 12, 3)->default(0);
            $table->decimal('delivered_qty', 12, 3)->nullable();
            $table->decimal('expected_qty', 12, 3)->nullable();
            $table->decimal('unit_price', 12, 2)->nullable();
            $table->decimal('total_cost', 14, 2)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fuel_delivery_items');
        Schema::dropIfExists('fuel_deliveries');
        Schema::dropIfExists('suppliers');
    }
};