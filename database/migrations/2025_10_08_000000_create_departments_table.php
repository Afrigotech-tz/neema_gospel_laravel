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
        Schema::create('departments', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('department_id')->nullable()->constrained('departments')->onDelete('set null')->after('country_id');
            $table->boolean('is_partner')->default(false)->after('department_id');
            $table->enum('partner_registration_method', ['self', 'office'])->nullable()->after('is_partner');
        });

        Schema::table('donations', function (Blueprint $table) {
            $table->enum('donation_type', ['manual', 'mobile'])->default('mobile')->after('status');
            $table->boolean('is_manual_entry')->default(false)->after('donation_type');
            $table->foreignId('partner_id')->nullable()->constrained('users')->onDelete('set null')->after('user_id');
        });

        Schema::create('department_permissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('department_id')->constrained('departments')->onDelete('cascade');
            $table->foreignId('permission_id')->constrained('permissions')->onDelete('cascade');
            $table->timestamps();

            $table->unique(['department_id', 'permission_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('donations', function (Blueprint $table) {
            $table->dropForeign(['partner_id']);
            $table->dropColumn(['donation_type', 'is_manual_entry', 'partner_id']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['department_id']);
            $table->dropColumn(['department_id', 'is_partner', 'partner_registration_method']);
        });

        Schema::dropIfExists('department_permissions');
        Schema::dropIfExists('departments');
    }
};
