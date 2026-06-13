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
        Schema::table('workspace_bookings', function (Blueprint $table) {
            $table->foreignId('workspace_id')
                ->nullable()
                ->after('user_id')
                ->constrained()
                ->nullOnDelete();

            $table->index(['workspace_id', 'booking_date', 'starts_at', 'ends_at'], 'workspace_booking_workspace_time_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('workspace_bookings', function (Blueprint $table) {
            $table->dropForeign(['workspace_id']);
            $table->dropIndex('workspace_booking_workspace_time_index');
            $table->dropColumn('workspace_id');
        });
    }
};
