<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('customer_number', 30)->unique();
            $table->enum('type', ['individual', 'corporate', 'fleet', 'government', 'other'])->default('individual')->index();
            $table->string('name');
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->string('address')->nullable();
            $table->string('tax_id')->nullable();
            $table->string('account_number')->nullable()->unique();
            $table->decimal('credit_limit', 14, 2)->default(0);
            $table->decimal('outstanding_balance', 14, 2)->default(0);
            $table->enum('status', ['active', 'inactive', 'blocked'])->default('active')->index();
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('fleet_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->string('account_number', 30)->unique();
            $table->string('company_name');
            $table->string('contact_person')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->decimal('fuel_limit_litres', 12, 3)->nullable();
            $table->decimal('credit_limit', 14, 2)->default(0);
            $table->boolean('monthly_statement')->default(false);
            $table->enum('status', ['active', 'inactive', 'blocked'])->default('active')->index();
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('fleet_account_fuel_product', function (Blueprint $table) {
            $table->foreignId('fleet_account_id')->constrained()->cascadeOnDelete();
            $table->foreignId('fuel_product_id')->constrained()->cascadeOnDelete();
            $table->primary(['fleet_account_id', 'fuel_product_id']);
        });

        Schema::create('customer_vehicles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('fleet_account_id')->nullable()->constrained()->nullOnDelete();
            $table->string('registration_number')->unique();
            $table->string('driver_name')->nullable();
            $table->string('driver_phone')->nullable();
            $table->decimal('fuel_limit_litres', 12, 3)->nullable();
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('fleet_vehicles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fleet_account_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->string('registration_number')->unique();
            $table->string('driver_name')->nullable();
            $table->string('driver_phone')->nullable();
            $table->decimal('fuel_limit_litres', 12, 3)->nullable();
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fleet_vehicles');
        Schema::dropIfExists('customer_vehicles');
        Schema::dropIfExists('fleet_account_fuel_product');
        Schema::dropIfExists('fleet_accounts');
        Schema::dropIfExists('customers');
    }
};