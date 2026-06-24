<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * RolesSeeder
 *
 * Carga los roles predefinidos del Documento 02, sección 1.2, y los permisos
 * base por módulo (Documento 02, sección 1.3 — patrón CRUD + Aprobar).
 *
 * Los permisos siguen el patrón: "modulo.recurso.accion"
 * Ejemplos: "agricultura.siembra.crear", "finanzas.ventas.aprobar"
 *
 * USO:
 *   php artisan db:seed --class=RolesSeeder
 * O desde DatabaseSeeder:
 *   $this->call(RolesSeeder::class);
 */
class RolesSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cached roles y permissions (evita problemas al re-correr el seeder)
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // =====================================================================
        // PERMISOS — organizados por módulo, siguiendo el Documento 02
        // =====================================================================

        $permisos = [
            // --- Administración ---
            'admin.usuarios.ver',
            'admin.usuarios.crear',
            'admin.usuarios.editar',
            'admin.usuarios.inactivar',
            'admin.roles.gestionar',
            'admin.establecimientos.gestionar',

            // --- Campos y Establecimientos ---
            'campos.establecimientos.ver',
            'campos.establecimientos.gestionar',
            'campos.lotes.ver',
            'campos.lotes.crear',
            'campos.lotes.editar',

            // --- Agricultura ---
            'agricultura.campanas.ver',
            'agricultura.campanas.gestionar',
            'agricultura.siembra.ver',
            'agricultura.siembra.crear',
            'agricultura.siembra.editar',
            'agricultura.labores.ver',
            'agricultura.labores.crear',
            'agricultura.labores.editar',
            'agricultura.cosecha.ver',
            'agricultura.cosecha.registrar',

            // --- Ganadería ---
            'ganaderia.animales.ver',
            'ganaderia.animales.crear',
            'ganaderia.animales.editar',
            'ganaderia.movimientos.registrar',
            'ganaderia.pesajes.registrar',
            'ganaderia.sanidad.ver',
            'ganaderia.sanidad.registrar',
            'ganaderia.reproduccion.ver',
            'ganaderia.reproduccion.registrar',

            // --- Feedlot / Encierre a corral ---
            'feedlot.corrales.ver',
            'feedlot.corrales.gestionar',
            'feedlot.consumos.registrar',

            // --- Insumos ---
            'insumos.stock.ver',
            'insumos.stock.ajustar',

            // --- Compras ---
            'compras.proveedores.ver',
            'compras.proveedores.gestionar',
            'compras.ordenes.ver',
            'compras.ordenes.crear',
            'compras.facturas.ver',
            'compras.facturas.cargar',
            'compras.facturas.aprobar',

            // --- Ventas ---
            'ventas.granos.ver',
            'ventas.granos.registrar',
            'ventas.granos.aprobar',
            'ventas.hacienda.ver',
            'ventas.hacienda.registrar',
            'ventas.hacienda.aprobar',

            // --- Finanzas ---
            'finanzas.caja.ver',
            'finanzas.caja.registrar',
            'finanzas.bancos.ver',
            'finanzas.bancos.conciliar',
            'finanzas.cuentas_corrientes.ver',
            'finanzas.iva.ver',
            'finanzas.rentabilidad.ver',

            // --- RRHH ---
            'rrhh.personal.ver',
            'rrhh.personal.gestionar',
            'rrhh.jornales.registrar',

            // --- Reportes ---
            'reportes.productivos.ver',
            'reportes.economicos.ver',
            'reportes.fiscales.ver',
            'reportes.exportar',

            // --- Auditoría ---
            'auditoria.ver',
        ];

        foreach ($permisos as $permiso) {
            Permission::firstOrCreate(['name' => $permiso]);
        }

        // =====================================================================
        // ROLES — Documento 02, sección 1.2
        // =====================================================================

        /**
         * Administrador del sistema
         * Acceso total, configuración del sistema, gestión de usuarios.
         * Todos los establecimientos.
         */
        $adminSistema = Role::firstOrCreate(['name' => 'administrador_sistema']);
        $adminSistema->givePermissionTo(Permission::all());

        /**
         * Gerente / Dueño
         * Ve todo, puede aprobar operaciones críticas, accede a todos los
         * reportes financieros. Todos los establecimientos.
         */
        $gerente = Role::firstOrCreate(['name' => 'gerente']);
        $gerente->givePermissionTo([
            'campos.establecimientos.ver', 'campos.lotes.ver',
            'agricultura.campanas.ver', 'agricultura.siembra.ver',
            'agricultura.labores.ver', 'agricultura.cosecha.ver',
            'ganaderia.animales.ver', 'ganaderia.movimientos.registrar',
            'ganaderia.pesajes.registrar', 'ganaderia.sanidad.ver',
            'ganaderia.reproduccion.ver',
            'feedlot.corrales.ver',
            'insumos.stock.ver',
            'compras.ordenes.ver', 'compras.facturas.ver', 'compras.facturas.aprobar',
            'ventas.granos.ver', 'ventas.granos.aprobar',
            'ventas.hacienda.ver', 'ventas.hacienda.aprobar',
            'finanzas.caja.ver', 'finanzas.bancos.ver',
            'finanzas.cuentas_corrientes.ver', 'finanzas.iva.ver',
            'finanzas.rentabilidad.ver',
            'reportes.productivos.ver', 'reportes.economicos.ver',
            'reportes.fiscales.ver', 'reportes.exportar',
            'auditoria.ver',
        ]);

        /**
         * Encargado de campo / Capataz
         * Registra operaciones de campo. Ve solo sus establecimientos asignados.
         */
        $capataz = Role::firstOrCreate(['name' => 'capataz']);
        $capataz->givePermissionTo([
            'campos.lotes.ver',
            'agricultura.campanas.ver',
            'agricultura.siembra.ver', 'agricultura.siembra.crear', 'agricultura.siembra.editar',
            'agricultura.labores.ver', 'agricultura.labores.crear', 'agricultura.labores.editar',
            'agricultura.cosecha.ver', 'agricultura.cosecha.registrar',
            'ganaderia.animales.ver', 'ganaderia.animales.crear', 'ganaderia.animales.editar',
            'ganaderia.movimientos.registrar',
            'ganaderia.pesajes.registrar',
            'ganaderia.sanidad.ver', 'ganaderia.sanidad.registrar',
            'ganaderia.reproduccion.ver', 'ganaderia.reproduccion.registrar',
            'feedlot.corrales.ver', 'feedlot.consumos.registrar',
            'insumos.stock.ver',
            'rrhh.jornales.registrar',
            'reportes.productivos.ver',
        ]);

        /**
         * Administrativo / Contable
         * Gestiona compras, ventas, caja, bancos, cuentas corrientes.
         */
        $administrativo = Role::firstOrCreate(['name' => 'administrativo']);
        $administrativo->givePermissionTo([
            'campos.establecimientos.ver',
            'compras.proveedores.ver', 'compras.proveedores.gestionar',
            'compras.ordenes.ver', 'compras.ordenes.crear',
            'compras.facturas.ver', 'compras.facturas.cargar',
            'ventas.granos.ver', 'ventas.granos.registrar',
            'ventas.hacienda.ver', 'ventas.hacienda.registrar',
            'insumos.stock.ver',
            'finanzas.caja.ver', 'finanzas.caja.registrar',
            'finanzas.bancos.ver', 'finanzas.bancos.conciliar',
            'finanzas.cuentas_corrientes.ver', 'finanzas.iva.ver',
            'reportes.economicos.ver', 'reportes.fiscales.ver', 'reportes.exportar',
            'rrhh.personal.ver',
        ]);

        /**
         * Asesor técnico (agrónomo / veterinario)
         * Solo lectura de historiales técnicos, puede sugerir planes.
         */
        $asesor = Role::firstOrCreate(['name' => 'asesor_tecnico']);
        $asesor->givePermissionTo([
            'campos.lotes.ver',
            'agricultura.campanas.ver', 'agricultura.siembra.ver',
            'agricultura.labores.ver', 'agricultura.cosecha.ver',
            'ganaderia.animales.ver', 'ganaderia.sanidad.ver',
            'ganaderia.reproduccion.ver', 'ganaderia.pesajes.registrar',
            'insumos.stock.ver',
            'reportes.productivos.ver',
        ]);

        /**
         * Solo lectura / Auditor
         * Ve reportes e información, no puede modificar nada.
         */
        $auditor = Role::firstOrCreate(['name' => 'auditor']);
        $auditor->givePermissionTo([
            'campos.establecimientos.ver', 'campos.lotes.ver',
            'agricultura.campanas.ver', 'agricultura.siembra.ver',
            'agricultura.labores.ver', 'agricultura.cosecha.ver',
            'ganaderia.animales.ver',
            'insumos.stock.ver',
            'finanzas.cuentas_corrientes.ver', 'finanzas.rentabilidad.ver',
            'reportes.productivos.ver', 'reportes.economicos.ver', 'reportes.exportar',
            'auditoria.ver',
        ]);

        $this->command->info('Roles y permisos cargados correctamente.');
        $this->command->table(
            ['Rol', 'Permisos'],
            Role::all()->map(fn($r) => [$r->name, $r->permissions->count()])
        );
    }
}
