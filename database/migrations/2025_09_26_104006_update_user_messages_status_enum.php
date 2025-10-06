<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_messages', function (Blueprint $table) {
            $table->enum('status', ['pending', 'read', 'replied', 'closed'])
                  ->default('pending')
                  ->change();
        });
    }



    public function down(): void
    {
        Schema::table('user_messages', function (Blueprint $table) {
            $table->enum('status', ['read', 'replied', 'completed'])
                  ->default('read')
                  ->change();
        });
    }


};


