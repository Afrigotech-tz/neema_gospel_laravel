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
        Schema::create('donations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->decimal('amount', 15, 2);
            $table->string('currency', 3)->default('TZS');
            $table->string('payment_method')->nullable();
            $table->string('transaction_reference')->unique();
            $table->enum('status', ['pending', 'completed', 'failed', 'refunded'])->default('pending');
            $table->text('message')->nullable();
            $table->string('campaign')->default('Neema Gospel Foundation');
            $table->decimal('target_amount', 15, 2)->default(100000000.00);
            $table->date('deadline')->default('2023-12-10');
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index(['transaction_reference']);
            $table->index(['created_at']);

        });


    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('donations');
    }
};
