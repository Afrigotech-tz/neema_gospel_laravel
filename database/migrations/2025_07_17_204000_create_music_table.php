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
        Schema::create('music', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('picture')->nullable();
            $table->date('release_date');
            $table->string('choir');
            $table->string('audio_file')->nullable();
            $table->text('description')->nullable();
            $table->string('genre')->nullable();
            $table->integer('duration')->nullable(); // in seconds
            $table->bigInteger('file_size')->nullable(); // in bytes
            $table->string('mime_type')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('music');
    }
};
