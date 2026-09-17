<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', fn (Blueprint $table) => $table->string('role', 20)->default('customer'));
        Schema::create('service_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->string('equipment', 120);
            $table->string('serial_number', 100)->nullable();
            $table->text('description');
            $table->string('status', 30)->default('received');
            $table->unsignedInteger('quote_cents')->nullable();
            $table->text('diagnosis')->nullable();
            $table->timestamps();
            $table->index(['user_id', 'status']);
        });
        Schema::create('order_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->string('action', 30);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_events');
        Schema::dropIfExists('service_orders');
        Schema::table('users', fn (Blueprint $table) => $table->dropColumn('role'));
    }
};
