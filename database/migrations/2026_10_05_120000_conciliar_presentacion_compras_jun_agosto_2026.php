<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Libros IVA Compras ELVIO/WILMAR junio-agosto 2026: ausentes de la presentación.
        // Las 103 coincidencias ya estaban presentadas; los casos dudosos se preservan.
        $comprobantes = [
            ['03cf2d13-71ba-47ce-91bc-527bdf73e067', '08394302-bfe4-4fe6-9904-5259d4350d5a', '5a4c080b-ec1f-4349-8495-3a9ca2a70a88', '2026-07-08', 'factura_b', '0107-00000461'],
            ['0b95ea56-4459-428e-b935-65058ffcbcb4', '08394302-bfe4-4fe6-9904-5259d4350d5a', '19554309-45ec-4fe4-85a6-b424cedf0248', '2026-08-29', 'factura_a', '0104-14981126'],
            ['1392c41f-98fa-4daa-a2b9-3c7ea1f2fb33', '96e4c5f7-f5cf-4374-a4f9-e861e46a8f09', 'bd6eafba-f56f-437d-a9d2-a407b2826e69', '2026-08-04', 'factura_c', '0003-00000071'],
            ['1a093618-bc70-4aa9-a2f8-4bfc2326018b', '08394302-bfe4-4fe6-9904-5259d4350d5a', '321aa3da-5bc2-49da-b4f3-3327aa56cd57', '2026-08-15', 'factura_a', '0017-00018835'],
            ['1a66d64d-7ad1-4de0-9835-9c7899405547', '08394302-bfe4-4fe6-9904-5259d4350d5a', '321aa3da-5bc2-49da-b4f3-3327aa56cd57', '2026-07-31', 'factura_a', '0017-00017960'],
            ['1ce37ff8-2416-426f-a1b8-11074cc2e99e', '08394302-bfe4-4fe6-9904-5259d4350d5a', '5a4c080b-ec1f-4349-8495-3a9ca2a70a88', '2026-07-08', 'factura_b', '0107-00000430'],
            ['2462fae0-b399-475f-ae0a-53193fd95ee9', '08394302-bfe4-4fe6-9904-5259d4350d5a', 'c1d250d3-680d-424f-8fd4-965cc2a6101d', '2026-07-29', 'factura_b', '0300-00279121'],
            ['330814fc-ac7b-4d2d-8255-f175b5c549e9', '96e4c5f7-f5cf-4374-a4f9-e861e46a8f09', '69c7973f-3095-4007-9051-9cd74ab2f90d', '2026-07-01', 'factura_b', '1004-00172476'],
            ['3371bdfe-f62c-46ba-bed1-f4ed1e44a351', '08394302-bfe4-4fe6-9904-5259d4350d5a', '8bb74d41-012d-4c30-838a-728d7dc700c7', '2026-08-12', 'factura_c', '0049-00023563'],
            ['3859bb4a-913b-4cfe-ba4d-7aae1fd58858', '08394302-bfe4-4fe6-9904-5259d4350d5a', '19554309-45ec-4fe4-85a6-b424cedf0248', '2026-08-14', 'factura_a', '0104-14868503'],
            ['3ae6cce0-db7b-4bc9-a398-010e4e031a38', '08394302-bfe4-4fe6-9904-5259d4350d5a', '2b36894a-27c7-464f-8bb7-56ac3d985209', '2026-07-22', 'factura_a', '0017-00017778'],
            ['3b7c2020-ec44-4e3a-b8c0-3049e5c25370', '08394302-bfe4-4fe6-9904-5259d4350d5a', '5a4c080b-ec1f-4349-8495-3a9ca2a70a88', '2026-08-06', 'factura_b', '0107-00000539'],
            ['3d7d1d6b-efd9-46b7-b3ae-fb8bcfff1bd3', '08394302-bfe4-4fe6-9904-5259d4350d5a', '8c0f6dbc-17a8-4227-824e-562aa94e984d', '2026-08-05', 'factura_b', '0007-00007306'],
            ['498c373a-aa11-400a-acc3-94bbb7da98c3', '08394302-bfe4-4fe6-9904-5259d4350d5a', '4327f96a-f2bf-4979-886a-912e7ed51491', '2026-06-25', 'factura_a', '0003-00003418'],
            ['5334e002-2109-46c4-b9e9-5b8f7c9e0273', '08394302-bfe4-4fe6-9904-5259d4350d5a', '8bb74d41-012d-4c30-838a-728d7dc700c7', '2026-08-01', 'factura_c', '0031-06192873'],
            ['55c6a53e-c39a-48e5-99bf-99cbd5a90410', '08394302-bfe4-4fe6-9904-5259d4350d5a', '8bb74d41-012d-4c30-838a-728d7dc700c7', '2026-08-11', 'factura_c', '9095-00179076'],
            ['59992e5f-6632-4338-bd50-d354c75d7594', '08394302-bfe4-4fe6-9904-5259d4350d5a', '5a4c080b-ec1f-4349-8495-3a9ca2a70a88', '2026-08-06', 'factura_b', '0107-00000538'],
            ['698d7ea4-c40a-46c2-b207-6e9a379f878d', '08394302-bfe4-4fe6-9904-5259d4350d5a', '5a4c080b-ec1f-4349-8495-3a9ca2a70a88', '2026-07-08', 'factura_b', '0107-00000460'],
            ['6a329b01-8432-441d-b664-92228c2ac8ed', '08394302-bfe4-4fe6-9904-5259d4350d5a', '5a4c080b-ec1f-4349-8495-3a9ca2a70a88', '2026-08-06', 'factura_b', '0107-00000508'],
            ['6dad6e1e-e26f-4eaf-8ce1-4f60074a1e18', '08394302-bfe4-4fe6-9904-5259d4350d5a', '167e4285-f58c-4dba-a864-e47cf55b3253', '2026-08-13', 'factura_b', '0293-00015112'],
            ['7293046f-85c3-4275-ab4b-f35d06303eaf', '96e4c5f7-f5cf-4374-a4f9-e861e46a8f09', '688719e9-60cf-426c-a61f-46faa3bd8dc5', '2026-07-31', 'factura_c', '0003-00000292'],
            ['79816830-4c00-4b6c-89ad-96440c9d96e7', '08394302-bfe4-4fe6-9904-5259d4350d5a', '167e4285-f58c-4dba-a864-e47cf55b3253', '2026-07-17', 'factura_b', '0285-00004041'],
            ['81e31ddc-73fb-4c7a-b08e-a574fd07a66a', '08394302-bfe4-4fe6-9904-5259d4350d5a', '4b1ce02a-6e8b-4655-9b64-d1ae2e8b0858', '2026-08-04', 'factura_a', '0050-01844755'],
            ['854d7138-feaa-48c5-a334-f8601ec96ff0', '08394302-bfe4-4fe6-9904-5259d4350d5a', 'a6345e0c-2238-4f82-aca3-2e0c97a4cade', '2026-07-24', 'factura_c', '0001-00000022'],
            ['8d09c814-64d6-4d9d-aeb8-9089010bcf4d', '96e4c5f7-f5cf-4374-a4f9-e861e46a8f09', '0de307be-654e-410b-aab3-ce997c6fdf74', '2026-07-03', 'factura_c', '0003-00000037'],
            ['8ed49252-ac5f-4a9f-9590-35ffe5c07d5d', '08394302-bfe4-4fe6-9904-5259d4350d5a', '321aa3da-5bc2-49da-b4f3-3327aa56cd57', '2026-08-18', 'factura_a', '0017-00018948'],
            ['9b4d900e-ba99-47cc-8cae-7e6852f23e84', '08394302-bfe4-4fe6-9904-5259d4350d5a', '66652083-7932-44ef-b508-29a56ef81603', '2026-07-21', 'factura_b', '0005-01735254'],
            ['9dedb35e-29e9-4752-bf25-6aa73b1445b9', '08394302-bfe4-4fe6-9904-5259d4350d5a', '5eaccdbb-06f6-47cd-987f-48da836b9571', '2026-08-07', 'factura_a', '0003-00011292'],
            ['a779f3c8-f334-423d-8679-de78241570c2', '08394302-bfe4-4fe6-9904-5259d4350d5a', '5a4c080b-ec1f-4349-8495-3a9ca2a70a88', '2026-08-06', 'factura_b', '0107-00000509'],
            ['b044d869-1489-4483-b4c4-e689a4aca262', '08394302-bfe4-4fe6-9904-5259d4350d5a', '8bb74d41-012d-4c30-838a-728d7dc700c7', '2026-07-01', 'factura_c', '0031-06133150'],
            ['befc4234-59d0-4d36-b7c9-3637ebae800c', '08394302-bfe4-4fe6-9904-5259d4350d5a', '5a4c080b-ec1f-4349-8495-3a9ca2a70a88', '2026-07-08', 'factura_b', '0107-00000431'],
            ['c196eb33-5b7e-4f6b-bf9b-62be4db33a5e', '08394302-bfe4-4fe6-9904-5259d4350d5a', '321aa3da-5bc2-49da-b4f3-3327aa56cd57', '2026-08-12', 'factura_a', '0017-00018614'],
            ['c7e0ca50-67e1-49a5-b230-3e8dd8542e8c', '08394302-bfe4-4fe6-9904-5259d4350d5a', '19554309-45ec-4fe4-85a6-b424cedf0248', '2026-07-30', 'factura_a', '0104-14757360'],
            ['c958e392-18e3-4c23-bec6-082cc0a53204', '08394302-bfe4-4fe6-9904-5259d4350d5a', 'fb477ec9-aba4-41ae-9a4f-357b2f1e7c87', '2026-08-25', 'factura_c', '0041-02073622'],
            ['c9dd2b98-e00b-4d3d-8cb8-6b88843207a3', '08394302-bfe4-4fe6-9904-5259d4350d5a', '19554309-45ec-4fe4-85a6-b424cedf0248', '2026-07-14', 'factura_a', '0104-14507718'],
            ['d0ae77ce-9fa9-4c7b-beea-4874294b2da8', '96e4c5f7-f5cf-4374-a4f9-e861e46a8f09', '015f9fc5-a4d1-4e3f-a22d-96305cb79917', '2026-08-10', 'liquidacion', '0005-00079164'],
            ['e03ae3dd-6142-4eb7-bfdb-a900feaa2d16', '08394302-bfe4-4fe6-9904-5259d4350d5a', '8c0f6dbc-17a8-4227-824e-562aa94e984d', '2026-08-05', 'factura_b', '0007-00008907'],
            ['e44b43ac-7807-4dc8-833c-503041aba423', '96e4c5f7-f5cf-4374-a4f9-e861e46a8f09', '69c7973f-3095-4007-9051-9cd74ab2f90d', '2026-08-01', 'factura_b', '1004-00176929'],
            ['ee0675f0-8783-4013-a45c-895f33374a8e', '08394302-bfe4-4fe6-9904-5259d4350d5a', '321aa3da-5bc2-49da-b4f3-3327aa56cd57', '2026-07-21', 'factura_a', '0017-00017377'],
            ['faf46031-5dbe-4b5d-8c1d-ee21c98c78aa', '08394302-bfe4-4fe6-9904-5259d4350d5a', 'c587b419-08d2-4eb6-80a4-04407fd3a7b1', '2026-07-20', 'factura_a', '0027-00010007'],
            ['fc747bd6-b7ee-4be0-812b-a948e14d6c99', '08394302-bfe4-4fe6-9904-5259d4350d5a', '4b1ce02a-6e8b-4655-9b64-d1ae2e8b0858', '2026-08-04', 'factura_a', '0111-00828475'],
            ['fd4d531c-a1f9-48a2-87f0-7ea3d4d67a90', '08394302-bfe4-4fe6-9904-5259d4350d5a', 'fb477ec9-aba4-41ae-9a4f-357b2f1e7c87', '2026-07-22', 'factura_c', '0041-01946393'],
            ['fdee0488-fe1f-45d4-91f9-b6ad2b3a2633', '08394302-bfe4-4fe6-9904-5259d4350d5a', '28f8ffe4-d5d6-42e2-92c2-799fb17d4c5b', '2026-07-17', 'factura_a', '0002-00000317'],
            ['ffcddf54-c477-4830-ada4-dd47af30174e', '96e4c5f7-f5cf-4374-a4f9-e861e46a8f09', 'bd6eafba-f56f-437d-a9d2-a407b2826e69', '2026-08-04', 'factura_c', '0003-00000070'],
        ];
        DB::transaction(function () use ($comprobantes) {
            foreach ($comprobantes as [$id, $empresa, $proveedor, $fecha, $tipo, $numero]) {
                DB::table('compras')->where('id', $id)->where('id_empresa', $empresa)
                    ->where('id_proveedor', $proveedor)->whereDate('fecha', $fecha)
                    ->where('tipo_comprobante', $tipo)->where('numero_comprobante', $numero)
                    ->where('presentado_arca', true)
                    ->update(['presentado_arca' => false, 'updated_at' => now()]);
            }
        });
    }

    public function down(): void
    {
        // No revertir marcas fiscales automáticamente: podrían haber sido revisadas luego.
    }
};
