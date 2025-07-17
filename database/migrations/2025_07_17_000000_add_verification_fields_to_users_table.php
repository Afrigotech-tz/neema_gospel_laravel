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
        Schema::table('users', function (Blueprint $table) {
            $table->enum('status', ['active', 'inactive'])->default('inactive')->after('email_verified_at');
            $table->string('otp_code', 6)->nullable()->after('status');
            $table->timestamp('otp_expires_at')->nullable()->after('otp_code');
            $table->enum('verification_method', ['email', 'mobile'])->default('email')->after('otp_expires_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['status', 'otp_code', 'otp_expires_at', 'verification_method']);
        });
    }
};
