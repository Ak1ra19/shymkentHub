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
        Schema::create('workspace_schedule_settings', function (Blueprint $table) {
            $table->id();
            $table->date('starts_on')->unique();
            $table->time('starts_at')->default('09:00');
            $table->time('ends_at')->default('18:00');
            $table->string('note')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('workspace_schedule_settings');
    }
};
