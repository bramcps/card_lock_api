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
        Schema::create('door_statuses', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('door_id')->references('id')->on('doors');
            $table->enum('status', ['open', 'closed', 'locked', 'unlocked', 'error']);
            $table->timestamp('status_changed_at')->useCurrent();
            $table->foreignUlid('changed_by')->nullable()->references('id')->on('users');
            $table->enum('change_method', ['automatic', 'manual', 'scheduled', 'emergency']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('door_statuses');
    }
};
