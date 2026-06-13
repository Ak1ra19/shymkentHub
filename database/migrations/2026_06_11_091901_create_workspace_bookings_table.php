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
        Schema::create('workspace_bookings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('workspace_number');
            $table->date('booking_date')->index();
            $table->time('starts_at');
            $table->time('ends_at');
            $table->string('status')->default('active')->index();
            $table->timestamps();

            $table->index(['workspace_number', 'booking_date', 'starts_at', 'ends_at'], 'workspace_booking_time_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('workspace_bookings');
    }
};
