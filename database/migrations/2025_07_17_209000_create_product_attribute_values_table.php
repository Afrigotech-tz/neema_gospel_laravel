<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_attribute_values', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->onDelete('cascade');
            $table->foreignId('attribute_id')->constrained('product_attributes')->onDelete('cascade');
            $table->string('value'); // e.g., 'Large', 'Red', 'Cotton'
            $table->decimal('price_adjustment', 10, 2)->default(0);
            $table->integer('stock_quantity')->default(0);
            $table->string('sku')->nullable();
            $table->timestamps();

            $table->unique(['product_id', 'attribute_id', 'value']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_attribute_values');
    }

    
};
