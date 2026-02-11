<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->onDelete('cascade');
            $table->foreignId('payment_method_id')->constrained('payment_methods')->onDelete('cascade');
            $table->string('transaction_id')->unique();
            $table->decimal('amount', 10, 2);
            $table->string('currency')->default('TZS');
            $table->string('status')->default('pending'); // pending, completed, failed, refunded
            $table->string('payment_reference')->nullable(); // M-Pesa transaction ID, bank reference, etc.
            $table->string('phone_number')->nullable(); // For mobile payments
            $table->string('account_number')->nullable(); // For bank payments
            $table->text('response_data')->nullable(); // API response data
            $table->text('error_message')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
        });

        
    }

    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }

};

