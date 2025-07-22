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
        Schema::create('news', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('content');
            $table->string('excerpt')->nullable();
            $table->string('featured_image')->nullable();
            $table->dateTime('published_at');
            $table->string('category')->default('news'); // news, event, announcement, etc.
            $table->string('type')->default('article'); // article, video, audio, event
            $table->integer('duration')->nullable(); // in minutes for videos/audio
            $table->string('location')->nullable(); // for events
            $table->boolean('is_featured')->default(false);
            $table->boolean('is_published')->default(true);
            $table->integer('view_count')->default(0);
            $table->json('tags')->nullable();
            $table->string('author')->nullable();
            $table->timestamps();

            $table->index(['published_at', 'is_published']);
            $table->index(['category', 'is_published']);
            $table->index(['is_featured', 'is_published']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('news');
    }
    
};
