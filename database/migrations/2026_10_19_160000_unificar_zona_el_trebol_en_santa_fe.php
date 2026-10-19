<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('compras')->where('zona', 'el_trebol')->update(['zona' => 'santa_fe']);
    }

    public function down(): void
    {
        // Santa Fe agrupa también registros que nunca pertenecieron a El Trébol.
    }
};
