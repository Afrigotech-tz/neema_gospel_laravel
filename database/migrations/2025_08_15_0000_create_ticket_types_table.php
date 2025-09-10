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
        Schema::create('ticket_types', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained('events')->onDelete('cascade');
            $table->string('name'); // e.g., Regular, VIP, Early Bird
            $table->text('description')->nullable();
            $table->decimal('price', 10, 2);
            $table->integer('quantity'); // total tickets available
            $table->integer('sold')->default(0); // tickets sold
            $table->timestamps();

            $table->index(['event_id', 'name']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ticket_types');
    }

};

