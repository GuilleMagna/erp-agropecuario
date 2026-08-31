<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    private const PERMISO = 'finanzas.reintegros.gestionar';

    public function up(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permiso = Permission::findOrCreate(self::PERMISO, 'web');
        $rol = Role::findOrCreate('cargador_datos', 'web');

        $rol->givePermissionTo($permiso);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $rol = Role::findByName('cargador_datos', 'web');
        $rol->revokePermissionTo(self::PERMISO);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
