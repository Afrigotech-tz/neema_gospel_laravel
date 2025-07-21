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
        Schema::create('donation_campaigns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained('donation_categories')->onDelete('cascade');
            $table->string('name');
            $table->text('overview')->nullable();
            $table->date('deadline');
            $table->decimal('fund_needed', 15, 2);
            $table->decimal('total_collected', 15, 2)->default(0);
            $table->json('price_options')->nullable();
            $table->boolean('allow_custom_price')->default(true);
            $table->enum('status', ['active', 'completed', 'cancelled'])->default('active');
            $table->timestamps();

            $table->index(['category_id', 'status']);
            $table->index(['deadline']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('donation_campaigns');
    }
};
