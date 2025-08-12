<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            // Rename shipping_address_id to address_id to match the model
            $table->renameColumn('shipping_address_id', 'address_id');

            // Update other columns to match model expectations
            $table->renameColumn('total', 'total_amount');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            // Revert the changes if needed
            $table->renameColumn('address_id', 'shipping_address_id');
            $table->renameColumn('total_amount', 'total');
        });
    }
};
