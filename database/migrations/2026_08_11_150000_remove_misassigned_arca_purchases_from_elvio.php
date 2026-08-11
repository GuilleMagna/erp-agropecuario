<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $empresaId = DB::table('empresas')
            ->whereIn('cuit', ['20-13543013-8', '20135430138'])
            ->value('id');

        if (! $empresaId) {
            return;
        }

        DB::table('compras')
            ->where('id_empresa', $empresaId)
            ->where('stock_registrado', false)
            ->where('observaciones', 'Sincronizado desde ARCA')
            ->whereIn('numero_comprobante', [
                '0007-00000088',
                '0004-00000033',
                '0003-00000077',
                '0005-00166036',
                '0002-00000020',
                '0012-00038844',
                '0017-00017576',
                '0017-00017657',
                '1004-00176929',
                '0003-00000070',
                '0003-00000071',
                '0003-00051975',
                '0005-00079164',
                '0100-00001080',
            ])
            ->delete();
    }

    public function down(): void
    {
        // La eliminación de datos incorrectamente importados no es reversible.
    }
};
