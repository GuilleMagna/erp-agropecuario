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
            // Alta/edición de empresas y datos fiscales. Separado de
            // admin.roles.gestionar a propósito: gestionar empresas no debe
            // implicar poder editar roles y permisos (con eso, cualquiera que
            // administre empresas podría auto-asignarse permisos).
            'admin.empresas.gestionar',
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
            'ganaderia.movimientos.ver',
            'ganaderia.movimientos.registrar',
            'ganaderia.pesajes.ver',
            'ganaderia.pesajes.registrar',
            'ganaderia.sanidad.ver',
            'ganaderia.sanidad.registrar',
            'ganaderia.reproduccion.ver',
            'ganaderia.reproduccion.registrar',

            // --- Feedlot / Encierre a corral ---
            'feedlot.corrales.ver',
            'feedlot.corrales.gestionar',
            'feedlot.tropas.ver',
            'feedlot.tropas.gestionar',
            'feedlot.consumos.registrar',

            // --- Insumos ---
            'insumos.catalogo.ver',
            'insumos.catalogo.gestionar',
            'insumos.movimientos.ver',
            'insumos.movimientos.registrar',

            // --- Compras ---
            'compras.proveedores.gestionar',
            'compras.ver',
            'compras.crear',
            'compras.editar',
            // Separado de compras.crear/editar a propósito: importar/sincronizar
            // desde ARCA es una acción distinta a cargar una compra a mano, y hay
            // roles que deben poder hacer una sin la otra (ver rol cargador_datos).
            'compras.arca.gestionar',

            // --- Ventas ---
            'ventas.granos.ver',
            'ventas.granos.registrar',
            'ventas.granos.aprobar',
            'ventas.hacienda.ver',
            'ventas.hacienda.registrar',
            'ventas.hacienda.aprobar',
            'ventas.compradores.gestionar',

            // --- Finanzas ---
            'finanzas.cuentas.ver',
            'finanzas.cuentas.gestionar',
            'finanzas.transacciones.ver',
            'finanzas.transacciones.crear',
            'finanzas.transacciones.editar',
            'finanzas.periodos.gestionar',
            'finanzas.reintegros.gestionar',
            'finanzas.gastos.gestionar',
            'finanzas.inmuebles.gestionar',

            // --- RRHH ---
            'rrhh.personal.ver',
            'rrhh.personal.gestionar',
            'rrhh.jornales.ver',
            'rrhh.jornales.registrar',

            // --- Reportes ---
            'reportes.productivos.ver',
            'reportes.economicos.ver',
            'reportes.fiscales.ver',
            'reportes.iva.ver',
            'reportes.ganancias.ver',
            'reportes.inmuebles.ver',
            'reportes.exportar',

            // --- Auditoría ---
            'auditoria.ver',

            // --- Sistema ---
            // Habilita el selector de empresa (arriba a la derecha) para cambiar
            // entre ELVIO/WILMAR/SOCIEDAD. Por defecto solo administrador_sistema
            // lo tiene; otros roles pueden necesitarlo si trabajan con más de una
            // empresa (ver rol cargador_datos).
            'sistema.empresas.cambiar',
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
            'ganaderia.animales.ver', 'ganaderia.movimientos.ver', 'ganaderia.movimientos.registrar',
            'ganaderia.pesajes.ver', 'ganaderia.pesajes.registrar', 'ganaderia.sanidad.ver',
            'ganaderia.reproduccion.ver',
            'feedlot.corrales.ver', 'feedlot.tropas.ver',
            'insumos.catalogo.ver', 'insumos.movimientos.ver',
            'compras.ver',
            'ventas.granos.ver', 'ventas.granos.aprobar',
            'ventas.hacienda.ver', 'ventas.hacienda.aprobar',
            'finanzas.cuentas.ver', 'finanzas.transacciones.ver',
            'finanzas.periodos.gestionar', 'finanzas.reintegros.gestionar',
            'rrhh.personal.ver', 'rrhh.jornales.ver',
            'reportes.productivos.ver', 'reportes.economicos.ver',
            'reportes.fiscales.ver', 'reportes.iva.ver', 'reportes.ganancias.ver', 'reportes.inmuebles.ver', 'reportes.exportar',
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
            'ganaderia.movimientos.ver', 'ganaderia.movimientos.registrar',
            'ganaderia.pesajes.ver', 'ganaderia.pesajes.registrar',
            'ganaderia.sanidad.ver', 'ganaderia.sanidad.registrar',
            'ganaderia.reproduccion.ver', 'ganaderia.reproduccion.registrar',
            'feedlot.corrales.ver', 'feedlot.tropas.ver', 'feedlot.tropas.gestionar', 'feedlot.consumos.registrar',
            'insumos.catalogo.ver', 'insumos.movimientos.ver', 'insumos.movimientos.registrar',
            'rrhh.personal.ver', 'rrhh.jornales.ver', 'rrhh.jornales.registrar',
            'reportes.productivos.ver',
        ]);

        /**
         * Administrativo / Contable
         * Gestiona compras, ventas, caja, bancos, cuentas corrientes.
         * Trabaja con las 3 empresas (ELVIO/WILMAR/SOCIEDAD): necesita el
         * selector de empresa y poder administrarlas. No gestiona roles.
         */
        $administrativo = Role::firstOrCreate(['name' => 'administrativo']);
        $administrativo->givePermissionTo([
            'sistema.empresas.cambiar',
            'admin.empresas.gestionar',
            'campos.establecimientos.ver',
            'compras.proveedores.gestionar',
            'compras.ver', 'compras.crear', 'compras.editar', 'compras.arca.gestionar',
            'ventas.granos.ver', 'ventas.granos.registrar',
            'ventas.hacienda.ver', 'ventas.hacienda.registrar',
            'ventas.compradores.gestionar',
            'insumos.catalogo.ver', 'insumos.movimientos.ver', 'insumos.movimientos.registrar',
            'finanzas.cuentas.ver', 'finanzas.cuentas.gestionar',
            'finanzas.transacciones.ver', 'finanzas.transacciones.crear', 'finanzas.transacciones.editar',
            'finanzas.periodos.gestionar', 'finanzas.reintegros.gestionar',
            'rrhh.personal.ver', 'rrhh.personal.gestionar',
            'rrhh.jornales.ver', 'rrhh.jornales.registrar',
            'finanzas.inmuebles.gestionar',
            'reportes.economicos.ver', 'reportes.fiscales.ver',
            'reportes.iva.ver', 'reportes.ganancias.ver', 'reportes.inmuebles.ver', 'reportes.exportar',
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
            'ganaderia.reproduccion.ver', 'ganaderia.pesajes.ver', 'ganaderia.pesajes.registrar',
            'insumos.catalogo.ver', 'insumos.movimientos.ver',
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
            'insumos.catalogo.ver',
            'compras.ver',
            'finanzas.cuentas.ver', 'finanzas.transacciones.ver',
            'reportes.productivos.ver', 'reportes.economicos.ver', 'reportes.exportar',
            'auditoria.ver',
        ]);

        /**
         * Cargador de datos
         * Gestiona los movimientos diarios: ventas (granos y hacienda), compras
         * (a mano y sincronizadas/importadas desde ARCA), y carga de gastos NO
         * ARCA (servicios). Puede cambiar entre las 3 empresas porque carga
         * datos de todas.
         */
        $cargadorDatos = Role::firstOrCreate(['name' => 'cargador_datos']);
        $cargadorDatos->givePermissionTo([
            'ventas.granos.ver', 'ventas.granos.registrar',
            'ventas.hacienda.ver', 'ventas.hacienda.registrar',
            'ventas.compradores.gestionar',
            'compras.ver', 'compras.crear', 'compras.editar',
            'compras.arca.gestionar',
            'compras.proveedores.gestionar',
            'finanzas.gastos.gestionar',
            'sistema.empresas.cambiar',
        ]);

        $this->command->info('Roles y permisos cargados correctamente.');
        $this->command->table(
            ['Rol', 'Permisos'],
            Role::all()->map(fn ($r) => [$r->name, $r->permissions->count()])
        );
    }
}
