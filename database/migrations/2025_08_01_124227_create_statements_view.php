<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        
        DB::statement('DROP VIEW IF EXISTS statements');

        // Create the view
        DB::statement("
            CREATE VIEW statements AS
            SELECT
                user_id,
                SUM(CASE WHEN type = 'income' THEN amount ELSE 0 END) AS income_total,
                SUM(CASE WHEN type = 'expense' THEN amount ELSE 0 END) AS expense_total,
                SUM(CASE WHEN type = 'income' THEN amount ELSE 0 END) -
                SUM(CASE WHEN type = 'expense' THEN amount ELSE 0 END) AS balance
            FROM transactions
            WHERE user_id IS NOT NULL
            GROUP BY user_id
        ");
    }

    public function down(): void
    {
        DB::statement('DROP VIEW IF EXISTS statements');
    }
};
