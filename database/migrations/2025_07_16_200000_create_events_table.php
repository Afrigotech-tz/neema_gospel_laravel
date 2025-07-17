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
        if (!Schema::hasTable('events')) {
            Schema::create('events', function (Blueprint $table) {
                $table->id();
                $table->string('title');
                $table->text('description')->nullable();
                $table->enum('type', ['live_recording', 'concert', 'service', 'conference', 'workshop', 'other']);
                $table->dateTime('start_date');
                $table->dateTime('end_date')->nullable();
                $table->string('venue');
                $table->string('location')->nullable();
                $table->string('city')->nullable();
                $table->string('country')->nullable();
                $table->decimal('latitude', 10, 8)->nullable();
                $table->decimal('longitude', 11, 8)->nullable();
                $table->string('image_url')->nullable();
                $table->integer('capacity')->nullable();
                $table->integer('attendees_count')->default(0);
                $table->boolean('is_featured')->default(false);
                $table->boolean('is_public')->default(true);
                $table->enum('status', ['upcoming', 'ongoing', 'completed', 'cancelled'])->default('upcoming');
                $table->decimal('ticket_price', 10, 2)->nullable();
                $table->string('ticket_url')->nullable();
                $table->json('tags')->nullable();
                $table->json('metadata')->nullable();
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('events');
    }
};
