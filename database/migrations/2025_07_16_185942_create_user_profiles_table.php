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
        if (!Schema::hasTable('user_profiles')) {
            Schema::create('user_profiles', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->onDelete('cascade');

                // Profile Picture
                $table->string('profile_picture')->nullable(); // Path to uploaded image

                // Location Information
                $table->string('address')->nullable(); // Street address
                $table->string('city')->nullable();
                $table->string('state_province')->nullable();
                $table->string('postal_code')->nullable();
                // Additional Profile Info
                $table->text('bio')->nullable(); // User biography/description
                $table->date('date_of_birth')->nullable();
                $table->string('occupation')->nullable();
                // Privacy Settings
                $table->boolean('location_public')->default(false); // Whether location is public
                $table->boolean('profile_public')->default(true); // Whether profile is public

                $table->timestamps();
                // Indexes
                $table->index(['user_id']);

            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_profiles');
    }
};
