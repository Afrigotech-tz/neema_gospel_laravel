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
        // All required fields already exist in the transactions table
        // This migration is kept for record keeping but does nothing
        // Fields already present: currency, payment_reference, phone_number, account_number, response_data, error_message, paid_at
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No action needed as fields already exist
    }
};
