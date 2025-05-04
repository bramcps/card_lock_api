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
        Schema::create('access_logs', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlId('user_id')->nullable()->references('id')->on('users');
            $table->foreignUlId('rfid_card_id')->nullable()->references('id')->on('rfid_cards');
            $table->foreignUlId('door_id')->references('id')->on('doors');
            $table->enum('access_type', ['authorized', 'unauthorized', 'denied']);
            $table->enum('status', ['success', 'failed']);
            $table->timestamp('accessed_at')->useCurrent();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('access_logs');
    }
};
