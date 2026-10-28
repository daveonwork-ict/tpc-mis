<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE pages MODIFY menu_show ENUM('header','footer','policy') NOT NULL");
    }

    public function down(): void
    {
        DB::table('pages')->where('menu_show', 'policy')->update(['menu_show' => 'footer']);
        DB::statement("ALTER TABLE pages MODIFY menu_show ENUM('header','footer') NOT NULL");
    }
};
