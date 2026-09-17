<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fuel_transactions', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('transaction_number', 30)->unique();
            $table->foreignId('station_id')->constrained()->cascadeOnDelete();
            $table->foreignId('pump_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('nozzle_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('fuel_product_id')->constrained();
            $table->foreignId('attendant_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('fleet_account_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('vehicle_id')->nullable()->constrained('customer_vehicles')->nullOnDelete();
            $table->foreignId('shift_id')->nullable();
            $table->decimal('meter_start', 12, 3)->default(0);
            $table->decimal('meter_end', 12, 3)->default(0);
            $table->decimal('litres', 12, 3);
            $table->decimal('price', 12, 2);
            $table->decimal('unit_cost', 12, 2)->nullable();
            $table->decimal('amount', 14, 2);
            $table->decimal('discount', 14, 2)->default(0);
            $table->decimal('tax', 14, 2)->default(0);
            $table->decimal('net_amount', 14, 2);
            $table->enum('payment_status', ['pending', 'paid', 'failed', 'cancelled', 'refunded'])->default('paid')->index();
            $table->enum('status', ['pending', 'dispensing', 'completed', 'cancelled', 'voided', 'refunded'])->default('completed')->index();
            $table->enum('sync_status', ['local', 'queued', 'synced', 'failed'])->default('synced')->index();
            $table->string('external_id')->nullable()->unique();
            $table->timestamp('received_at')->nullable();
            $table->timestamp('synced_at')->nullable();
            $table->dateTime('transacted_at');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('void_reason')->nullable();
            $table->timestamps();
            $table->index(['station_id', 'transacted_at']);
            $table->index(['attendant_id', 'transacted_at']);
        });

        Schema::create('fuel_transaction_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fuel_transaction_id')->constrained()->cascadeOnDelete();
            $table->foreignId('fuel_product_id')->constrained();
            $table->decimal('quantity', 12, 3);
            $table->decimal('price', 12, 2);
            $table->decimal('amount', 14, 2);
            $table->timestamps();
        });

        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->string('payment_number', 30)->unique();
            $table->foreignId('transaction_id')->nullable()->constrained('fuel_transactions')->nullOnDelete();
            $table->decimal('amount', 14, 2);
            $table->enum('method', ['cash', 'mobile_money', 'card', 'fleet_account', 'credit'])->default('cash')->index();
            $table->string('reference')->nullable();
            $table->string('provider')->nullable();
            $table->enum('status', ['pending', 'paid', 'failed', 'cancelled', 'refunded'])->default('paid')->index();
            $table->timestamp('paid_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('inventory_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('station_id')->constrained()->cascadeOnDelete();
            $table->foreignId('fuel_product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('tank_id')->nullable()->constrained()->nullOnDelete();
            $table->enum('type', ['opening_balance', 'purchase', 'delivery', 'sale', 'adjustment', 'transfer_in', 'transfer_out', 'return', 'correction'])->index();
            $table->decimal('quantity', 14, 3);
            $table->decimal('unit_cost', 12, 2)->nullable();
            $table->decimal('total_cost', 14, 2)->nullable();
            $table->string('ref_type')->nullable();
            $table->unsignedBigInteger('ref_id')->nullable();
            $table->decimal('balance_after', 14, 3)->nullable();
            $table->string('note')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['ref_type', 'ref_id']);
            $table->index(['station_id', 'fuel_product_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_movements');
        Schema::dropIfExists('payments');
        Schema::dropIfExists('fuel_transaction_items');
        Schema::dropIfExists('fuel_transactions');
    }
};