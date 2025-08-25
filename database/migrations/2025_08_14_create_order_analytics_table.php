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
        Schema::create('order_analytics', function (Blueprint $table) {
            $table->id();
            $table->date('date');
            $table->integer('total_orders')->default(0);
            $table->decimal('total_revenue', 15, 2)->default(0);
            $table->integer('completed_orders')->default(0);
            $table->integer('pending_orders')->default(0);
            $table->integer('cancelled_orders')->default(0);
            $table->decimal('average_order_value', 10, 2)->default(0);
            $table->integer('unique_customers')->default(0);
            $table->json('top_products')->nullable();
            $table->json('payment_method_stats')->nullable();
            $table->timestamps();

            $table->unique('date');
            $table->index(['date', 'total_revenue']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_analytics');
    }
};
