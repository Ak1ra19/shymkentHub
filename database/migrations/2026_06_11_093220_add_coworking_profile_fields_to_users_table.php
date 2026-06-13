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
        Schema::table('users', function (Blueprint $table) {
            $table->string('email')->nullable()->change();
            $table->text('iin')->nullable()->after('email_verified_at');
            $table->string('iin_hash')->nullable()->unique()->after('iin');
            $table->string('phone')->nullable()->unique()->after('iin_hash');
            $table->string('position')->nullable()->after('phone');
            $table->string('company')->nullable()->after('position');
            $table->string('role')->default('user')->index()->after('company');
            $table->boolean('is_blocked')->default(false)->index()->after('role');
            $table->timestamp('rules_accepted_at')->nullable()->after('is_blocked');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['iin_hash']);
            $table->dropUnique(['phone']);
            $table->dropIndex(['role']);
            $table->dropIndex(['is_blocked']);
            $table->dropColumn([
                'iin',
                'iin_hash',
                'phone',
                'position',
                'company',
                'role',
                'is_blocked',
                'rules_accepted_at',
            ]);
            $table->string('email')->nullable(false)->change();
        });
    }
};
