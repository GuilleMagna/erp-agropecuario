<?php

use App\Models\GastoNoArca;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('gastos_no_arca', fn (Blueprint $table) => $table->string('zona', 30)->default('santa_fe')->index());
        DB::table('gastos_no_arca')->orderBy('id')->chunkById(200, function ($gastos) {
            foreach ($gastos as $gasto) {
                DB::table('gastos_no_arca')->where('id', $gasto->id)->update(['zona' => GastoNoArca::zonaSugerida($gasto->nombre)]);
            }
        });
    }

    public function down(): void
    {
        Schema::table('gastos_no_arca', fn (Blueprint $table) => $table->dropColumn('zona'));
    }
};
