<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement('ALTER TABLE user_messages DROP CONSTRAINT IF EXISTS user_messages_status_check');

        DB::statement("ALTER TABLE user_messages ADD CONSTRAINT user_messages_status_check CHECK (status::text = ANY (ARRAY['pending'::character varying, 'read'::character varying, 'replied'::character varying, 'closed'::character varying]::text[]))");

        // Update default if needed, but since we're changing constraint, set default via raw if necessary
        DB::statement("UPDATE user_messages SET status = 'pending' WHERE status IS NULL");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('ALTER TABLE user_messages DROP CONSTRAINT IF EXISTS user_messages_status_check');

        DB::statement("ALTER TABLE user_messages ADD CONSTRAINT user_messages_status_check CHECK (status::text = ANY (ARRAY['read'::character varying, 'replied'::character varying, 'completed'::character varying]::text[]))");
    }


};

