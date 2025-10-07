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
        Schema::create('ip_throttles', function (Blueprint $table) {
            $table->id();
            $table->string('ip_address')->unique();
            $table->integer('counts')->default(0);
            $table->timestamp('last_seen');
            $table->timestamp('block_until')->nullable();
            $table->integer('total_hits')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ip_throttles');
    }
    
};
