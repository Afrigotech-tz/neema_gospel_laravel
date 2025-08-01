<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_methods', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('type'); // mobile, bank, card
            $table->string('provider'); // m-pesa, mixx, yas, airtel, halotel, nmb, crdb
            $table->string('code')->unique(); // mpesa, mixx, yas, airtel, halotel, nmb, crdb
            $table->boolean('is_active')->default(true);
            $table->string('logo_url')->nullable();
            $table->text('description')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_methods');
    }
};
