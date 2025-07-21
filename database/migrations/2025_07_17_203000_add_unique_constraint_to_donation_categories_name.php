<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // First, handle duplicate entries by keeping the first occurrence and removing duplicates
        $duplicates = DB::table('donation_categories')
            ->select('name', DB::raw('MIN(id) as min_id'))
            ->groupBy('name')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        foreach ($duplicates as $duplicate) {
            // Delete all duplicates except the first one
            DB::table('donation_categories')
                ->where('name', $duplicate->name)
                ->where('id', '!=', $duplicate->min_id)
                ->delete();
        }

        // Now add the unique constraint
        Schema::table('donation_categories', function (Blueprint $table) {
            $table->unique('name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('donation_categories', function (Blueprint $table) {
            $table->dropUnique(['name']);
        });
    }
};
