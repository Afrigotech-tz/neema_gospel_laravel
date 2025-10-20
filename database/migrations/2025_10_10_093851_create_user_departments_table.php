<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('user_departments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('department_id')->constrained('departments')->onDelete('cascade');
            $table->timestamps();

            $table->unique(['user_id', 'department_id']);
        });

        // Migrate existing data
        $usersWithDepartments = DB::table('users')->whereNotNull('department_id')->get();
        foreach ($usersWithDepartments as $user) {
            DB::table('user_departments')->insert([
                'user_id' => $user->id,
                'department_id' => $user->department_id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Drop the old column
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['department_id']);
            $table->dropColumn('department_id');
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Add back the column
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('department_id')->nullable()->constrained('departments')->onDelete('set null')->after('country_id');
        });

        // Migrate data back (this will lose multiple assignments, keeping only one)
        $userDepartments = DB::table('user_departments')->get();
        foreach ($userDepartments as $ud) {
            DB::table('users')->where('id', $ud->user_id)->update(['department_id' => $ud->department_id]);
        }

        Schema::dropIfExists('user_departments');

    }

    
};
