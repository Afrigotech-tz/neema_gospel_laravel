<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateIpBlacklistsTable extends Migration
{
    public function up()
    {
        Schema::create('ip_blacklists', function (Blueprint $table) {
            $table->id();
            $table->string('ip', 45)->index();
            $table->string('reason')->nullable();
            $table->unsignedInteger('ban_seconds')->nullable(); 
            $table->timestamp('banned_at')->useCurrent();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('ip_blacklists');
    }
 

   
}

