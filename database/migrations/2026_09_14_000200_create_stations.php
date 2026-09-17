<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stations', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique();
            $table->string('name');
            $table->string('region');
            $table->string('district')->nullable();
            $table->string('location')->nullable();
            $table->string('address')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->string('manager_name')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->date('opening_date')->nullable();
            $table->string('operating_hours')->default('24 hours');
            $table->enum('status', ['online', 'warning', 'maintenance', 'offline'])->default('online')->index();
            $table->text('notes')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('station_id')->nullable()->after('status')->constrained()->nullOnDelete();
        });

        Schema::create('station_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('station_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('station_role')->nullable();
            $table->boolean('is_assigned')->default(true);
            $table->timestamps();
            $table->unique(['station_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('station_user');
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['station_id']);
            $table->dropColumn('station_id');
        });
        Schema::dropIfExists('stations');
    }
};