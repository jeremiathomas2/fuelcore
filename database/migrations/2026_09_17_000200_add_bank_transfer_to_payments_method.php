<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->enum('method', ['cash', 'mobile_money', 'card', 'bank_transfer', 'fleet_account', 'credit'])
                ->default('cash')
                ->change();
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->enum('method', ['cash', 'mobile_money', 'card', 'fleet_account', 'credit'])
                ->default('cash')
                ->change();
        });
    }
};