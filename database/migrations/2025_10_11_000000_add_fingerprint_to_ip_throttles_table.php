<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('ip_throttles', function (Blueprint $table) {
            $table->string('fingerprint')->nullable()->after('ip_address');
        });

        // Set a default fingerprint for existing records
        DB::statement("UPDATE ip_throttles SET fingerprint = '" . hash('sha256', 'legacy_device') . "' WHERE fingerprint IS NULL");

        Schema::table('ip_throttles', function (Blueprint $table) {
            $table->dropUnique(['ip_address']); // Drop existing unique constraint
            $table->string('fingerprint')->nullable(false)->change(); // Make it not null
            $table->unique(['ip_address', 'fingerprint']); // Add composite unique constraint
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ip_throttles', function (Blueprint $table) {
            $table->dropUnique(['ip_address', 'fingerprint']);
            $table->unique('ip_address');
            $table->dropColumn('fingerprint');
        });
    }
};
