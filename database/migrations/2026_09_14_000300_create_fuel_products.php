<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fuel_products', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique();
            $table->string('name');
            $table->string('description')->nullable();
            $table->string('unit')->default('litre');
            $table->decimal('price', 12, 2);
            $table->decimal('cost_price', 12, 2)->default(0);
            $table->decimal('tax_rate', 5, 2)->default(0);
            $table->decimal('min_stock', 12, 3)->default(0);
            $table->boolean('active')->default(true)->index();
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('fuel_product_prices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fuel_product_id')->constrained()->cascadeOnDelete();
            $table->decimal('price', 12, 2);
            $table->decimal('cost_price', 12, 2)->default(0);
            $table->foreignId('changed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->date('effective_date')->default(now()->toDateString());
            $table->timestamps();
            $table->index(['fuel_product_id', 'effective_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fuel_product_prices');
        Schema::dropIfExists('fuel_products');
    }
};