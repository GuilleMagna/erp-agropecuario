<?php

namespace App\Livewire\Sistema;

use Illuminate\Support\Facades\Gate;
use Livewire\Component;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class GestionRoles extends Component
{
    // Roles del sistema: no se pueden eliminar
    const ROLES_SISTEMA = [
        'administrador_sistema', 'gerente', 'capataz',
        'administrativo', 'asesor_tecnico', 'auditor',
    ];

    // Nombres amigables para módulos (prefijo del permiso)
    const MODULOS_LABEL = [
        'admin'       => 'Administración',
        'campos'      => 'Campos y lotes',
        'agricultura' => 'Agricultura',
        'ganaderia'   => 'Ganadería',
        'feedlot'     => 'Feedlot',
        'insumos'     => 'Insumos',
        'compras'     => 'Compras',
        'ventas'      => 'Ventas',
        'finanzas'    => 'Finanzas',
        'rrhh'        => 'RRHH',
        'reportes'    => 'Reportes',
        'auditoria'   => 'Auditoría',
        'sistema'     => 'Sistema',
    ];

    // Estado modal permisos
    public bool    $modalAbierto        = false;
    public string  $rolEditandoId       = '';
    public string  $rolEditandoNombre   = '';
    public array   $permisosSeleccionados = [];

    // Estado modal crear rol
    public bool   $modalCrearAbierto = false;
    public string $nuevoRolNombre    = '';

    public function mount(): void
    {
        Gate::authorize('admin.roles.gestionar');
    }

    public function abrirModalCrear(): void
    {
        Gate::authorize('admin.roles.gestionar');
        $this->nuevoRolNombre    = '';
        $this->modalCrearAbierto = true;
        $this->resetValidation('nuevoRolNombre');
    }

    public function crearRol(): void
    {
        Gate::authorize('admin.roles.gestionar');

        $this->validate([
            'nuevoRolNombre' => 'required|string|min:3|max:50|unique:roles,name|alpha_dash',
        ], [
            'nuevoRolNombre.required'   => 'El nombre del rol es obligatorio.',
            'nuevoRolNombre.min'        => 'El nombre debe tener al menos 3 caracteres.',
            'nuevoRolNombre.unique'     => 'Ya existe un rol con ese nombre.',
            'nuevoRolNombre.alpha_dash' => 'Solo se permiten letras, números, guiones y guiones bajos.',
        ]);

        Role::create(['name' => $this->nuevoRolNombre]);

        session()->flash('success', "Rol '{$this->nuevoRolNombre}' creado correctamente.");
        $this->modalCrearAbierto = false;
        $this->nuevoRolNombre    = '';
    }

    public function editarPermisos(string $roleId): void
    {
        Gate::authorize('admin.roles.gestionar');

        $role = Role::with('permissions')->findOrFail($roleId);

        $this->rolEditandoId        = $roleId;
        $this->rolEditandoNombre    = $role->name;
        $this->permisosSeleccionados = $role->permissions->pluck('name')->toArray();
        $this->modalAbierto         = true;
    }

    public function guardarPermisos(): void
    {
        Gate::authorize('admin.roles.gestionar');

        $role = Role::findOrFail($this->rolEditandoId);
        $role->syncPermissions($this->permisosSeleccionados);

        $this->modalAbierto = false;
        session()->flash('success', "Permisos del rol '{$role->name}' actualizados correctamente.");
    }

    public function eliminarRol(string $roleId): void
    {
        Gate::authorize('admin.roles.gestionar');

        $role = Role::findOrFail($roleId);

        if (in_array($role->name, self::ROLES_SISTEMA)) {
            session()->flash('error', 'Los roles del sistema no se pueden eliminar.');
            return;
        }

        $usersCount = \App\Models\Usuario::role($role->name)->count();
        if ($usersCount > 0) {
            session()->flash('error', "El rol '{$role->name}' tiene {$usersCount} usuario(s) asignado(s). Reasignálos primero.");
            return;
        }

        $nombre = $role->name;
        $role->delete();
        session()->flash('success', "Rol '{$nombre}' eliminado correctamente.");
    }

    public function cerrarModal(): void
    {
        $this->modalAbierto      = false;
        $this->modalCrearAbierto = false;
        $this->reset(['rolEditandoId', 'rolEditandoNombre', 'permisosSeleccionados', 'nuevoRolNombre']);
    }

    public function render()
    {
        $roles = Role::with('permissions')
            ->withCount('users')
            ->orderBy('name')
            ->get()
            ->map(function ($role) {
                $role->es_sistema = in_array($role->name, self::ROLES_SISTEMA);
                return $role;
            });

        $permisosPorModulo = Permission::orderBy('name')
            ->get()
            ->groupBy(fn($p) => explode('.', $p->name)[0])
            ->sortKeys();

        return view('livewire.sistema.gestion-roles', [
            'roles'              => $roles,
            'permisosPorModulo'  => $permisosPorModulo,
            'modulosLabel'       => self::MODULOS_LABEL,
            'rolesSistema'       => self::ROLES_SISTEMA,
        ]);
    }
}
