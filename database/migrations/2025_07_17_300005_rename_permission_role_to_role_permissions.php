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
        Schema::rename('permission_role', 'role_permissions');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::rename('role_permissions', 'permission_role');
    }
};
