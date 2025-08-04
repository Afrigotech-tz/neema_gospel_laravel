<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('product_attribute_values', function (Blueprint $table) {
            $table->dropForeign(['product_id']); // If you have a foreign key
            $table->dropColumn('product_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    
    public function down(): void
    {
        Schema::table('product_attribute_values', function (Blueprint $table) {
            //
        });
    }
};
