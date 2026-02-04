<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("
            CREATE VIEW ai_accessible_schema AS
            SELECT 
                c.TABLE_NAME as table_name,
                c.COLUMN_NAME as column_name,
                c.DATA_TYPE as data_type,
                c.IS_NULLABLE as is_nullable,
                c.COLUMN_KEY as column_key
            FROM information_schema.COLUMNS c
            WHERE c.TABLE_SCHEMA = DATABASE()
            AND c.TABLE_NAME IN ('workflows', 'tasks', 'customers', 'chat_threads', 'chat_messages')
            ORDER BY c.TABLE_NAME, c.ORDINAL_POSITION;
        ");
    }

    public function down(): void
    {
        DB::statement("DROP VIEW IF EXISTS ai_accessible_schema;");
    }
};
