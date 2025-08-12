<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            // Add payment_method_id column as foreign key
            $table->foreignId('payment_method_id')->nullable()->constrained('payment_methods')->onDelete('set null');

            // Make payment_method nullable since we're moving to foreign key
            $table->string('payment_method')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign(['payment_method_id']);
            $table->dropColumn('payment_method_id');

            // Revert payment_method to not nullable if needed
            $table->string('payment_method')->nullable(false)->change();
        });
    }
};
