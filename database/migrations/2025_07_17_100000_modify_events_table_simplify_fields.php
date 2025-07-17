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
        Schema::table('events', function (Blueprint $table) {
            // Add date column first (nullable to avoid issues with existing data)
            $table->date('date')->nullable()->after('type');

            // Rename image_url to picture
            $table->renameColumn('image_url', 'picture');

            // Drop unnecessary columns
            $table->dropColumn([
                'description',
                'start_date',
                'end_date',
                'venue',
                'city',
                'country',
                'latitude',
                'longitude',
                'capacity',
                'attendees_count',
                'is_featured',
                'is_public',
                'status',
                'ticket_price',
                'ticket_url',
                'tags',
                'metadata'
            ]);

            // Make date column not nullable after data migration
            $table->date('date')->nullable(false)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            // Add back all the dropped columns
            $table->text('description')->nullable()->after('title');
            $table->dateTime('start_date')->after('type');
            $table->dateTime('end_date')->nullable()->after('start_date');
            $table->string('venue')->after('location');
            $table->string('city')->nullable()->after('location');
            $table->string('country')->nullable()->after('city');
            $table->decimal('latitude', 10, 8)->nullable()->after('country');
            $table->decimal('longitude', 11, 8)->nullable()->after('latitude');
            $table->integer('capacity')->nullable()->after('picture');
            $table->integer('attendees_count')->default(0)->after('capacity');
            $table->boolean('is_featured')->default(false)->after('attendees_count');
            $table->boolean('is_public')->default(true)->after('is_featured');
            $table->enum('status', ['upcoming', 'ongoing', 'completed', 'cancelled'])->default('upcoming')->after('is_public');
            $table->decimal('ticket_price', 10, 2)->nullable()->after('status');
            $table->string('ticket_url')->nullable()->after('ticket_price');
            $table->json('tags')->nullable()->after('ticket_url');
            $table->json('metadata')->nullable()->after('tags');

            // Rename picture back to image_url
            $table->renameColumn('picture', 'image_url');

            // Drop date column
            $table->dropColumn('date');
        });
    }
};
