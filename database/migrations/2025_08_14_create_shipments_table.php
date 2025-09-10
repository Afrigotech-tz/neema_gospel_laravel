<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     */
    
    public function up(): void
    {
        Schema::create('shipments', function (Blueprint $table) {

            $table->id();
            $table->foreignId('order_id')->constrained()->onDelete('cascade');
            $table->string('tracking_number')->unique();
            $table->string('carrier')->nullable();
            $table->string('service')->nullable();
            $table->decimal('shipping_cost', 10, 2)->default(0);
            $table->string('status')->default('preparing');
            $table->dateTime('shipped_at')->nullable();
            $table->dateTime('estimated_delivery')->nullable();
            $table->dateTime('delivered_at')->nullable();
            $table->text('shipping_address')->nullable();
            $table->text('notes')->nullable();
            $table->json('tracking_updates')->nullable();
            $table->timestamps();

            $table->index(['order_id', 'tracking_number']);
            $table->index(['status', 'shipped_at']);

            
        });



    }


    /**
     * Reverse the migrations.
     * 
     */

    public function down(): void
    {
        Schema::dropIfExists('shipments');
    }

    

};


