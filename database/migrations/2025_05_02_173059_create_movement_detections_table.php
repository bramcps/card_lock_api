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
        Schema::create('movement_detections', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('door_id')->references('id')->on('doors');
            $table->boolean('has_recent_authorization')->default(false);
            $table->integer('unauthorized_duration')->nullable()->comment('Duration in seconds since last authorized access');
            $table->timestamp('detected_at')->useCurrent();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('movement_detections');
    }
};
