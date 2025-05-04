<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('alerts', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('door_id')->references('id')->on(table: 'doors');
            $table->foreignUlid('movement_detection_id')->nullable()->references('id')->on('movement_detections');
            $table->enum('alert_type', ['unauthorized_movement', 'tamper_attempt', 'system_error', 'other']);
            $table->text('description');
            $table->boolean('is_acknowledged')->default(false);
            $table->foreignUlid('acknowledged_by')->nullable()->references('id')->on('users');
            $table->timestamp('acknowledged_at')->nullable();
            $table->timestamp('triggered_at')->useCurrent();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('alerts');
    }
};
