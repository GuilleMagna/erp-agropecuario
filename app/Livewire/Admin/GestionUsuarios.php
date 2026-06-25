<?php

namespace App\Livewire\Admin;

use App\Models\Usuario;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;
use Livewire\WithPagination;
use Spatie\Permission\Models\Role;

class GestionUsuarios extends Component
{
    use WithPagination;

    protected string $paginationTheme = 'bootstrap';

    // Filtros
    public string $busqueda     = '';
    public string $filtroRol    = '';
    public string $filtroActivo = '';

    // Estado del modal
    public bool    $modalAbierto      = false;
    public bool    $modoEdicion       = false;
    public ?string $usuarioEditandoId = null;

    // Campos del formulario
    public string $nombre                = '';
    public string $apellido              = '';
    public string $email                 = '';
    public string $telefono              = '';
    public string $password              = '';
    public string $password_confirmation = '';
    public string $rol                   = '';
    public bool   $activo                = true;

    protected function rules(): array
    {
        $emailUnico = $this->modoEdicion
            ? 'required|email|max:150|unique:usuarios,email,' . $this->usuarioEditandoId
            : 'required|email|max:150|unique:usuarios,email';

        $passwordReq = $this->modoEdicion
            ? 'nullable|min:8|confirmed'
            : 'required|min:8|confirmed';

        return [
            'nombre'               => 'required|string|max:100',
            'apellido'             => 'required|string|max:100',
            'email'                => $emailUnico,
            'telefono'             => 'nullable|string|max:30',
            'password'             => $passwordReq,
            'password_confirmation'=> 'nullable',
            'rol'                  => 'required|exists:roles,name',
        ];
    }

    protected $messages = [
        'nombre.required'    => 'El nombre es obligatorio.',
        'apellido.required'  => 'El apellido es obligatorio.',
        'email.required'     => 'El email es obligatorio.',
        'email.email'        => 'Ingresá un email válido.',
        'email.unique'       => 'Ya existe un usuario con ese email.',
        'password.required'  => 'La contraseña es obligatoria.',
        'password.min'       => 'La contraseña debe tener al menos 8 caracteres.',
        'password.confirmed' => 'Las contraseñas no coinciden.',
        'rol.required'       => 'Debés asignar un rol al usuario.',
        'rol.exists'         => 'El rol seleccionado no es válido.',
    ];

    public function updatingBusqueda(): void    { $this->resetPage(); }
    public function updatingFiltroRol(): void   { $this->resetPage(); }
    public function updatingFiltroActivo(): void { $this->resetPage(); }

    public function abrirModalCrear(): void
    {
        Gate::authorize('admin.usuarios.crear');
        $this->limpiarFormulario();
        $this->modoEdicion  = false;
        $this->modalAbierto = true;
    }

    public function abrirModalEditar(string $id): void
    {
        Gate::authorize('admin.usuarios.editar');

        $usuario = Usuario::findOrFail($id);

        $this->usuarioEditandoId    = $id;
        $this->nombre               = $usuario->nombre;
        $this->apellido             = $usuario->apellido;
        $this->email                = $usuario->email;
        $this->telefono             = $usuario->telefono ?? '';
        $this->activo               = $usuario->activo;
        $this->rol                  = $usuario->roles->first()?->name ?? '';
        $this->password             = '';
        $this->password_confirmation = '';

        $this->modoEdicion  = true;
        $this->modalAbierto = true;
    }

    public function guardar(): void
    {
        $this->validate();

        if ($this->modoEdicion) {
            Gate::authorize('admin.usuarios.editar');

            $usuario = Usuario::findOrFail($this->usuarioEditandoId);

            $datos = [
                'nombre'   => $this->nombre,
                'apellido' => $this->apellido,
                'email'    => $this->email,
                'telefono' => $this->telefono ?: null,
                'activo'   => $this->activo,
            ];
            if ($this->password) {
                $datos['password'] = $this->password;
            }

            $usuario->update($datos);
            $usuario->syncRoles([$this->rol]);

            session()->flash('success', "Usuario {$usuario->nombre_completo} actualizado correctamente.");
        } else {
            Gate::authorize('admin.usuarios.crear');

            $usuario = Usuario::create([
                'id_empresa' => auth()->user()->id_empresa,
                'nombre'     => $this->nombre,
                'apellido'   => $this->apellido,
                'email'      => $this->email,
                'telefono'   => $this->telefono ?: null,
                'password'   => $this->password,
                'activo'     => true,
            ]);
            $usuario->assignRole($this->rol);

            session()->flash('success', "Usuario {$usuario->nombre_completo} creado correctamente.");
        }

        $this->cerrarModal();
    }

    public function toggleActivo(string $id): void
    {
        Gate::authorize('admin.usuarios.inactivar');

        $usuario = Usuario::findOrFail($id);

        if ($usuario->id === auth()->id()) {
            session()->flash('error', 'No podés inactivarte a vos mismo.');
            return;
        }

        $usuario->activo = ! $usuario->activo;
        $usuario->save();

        $estado = $usuario->activo ? 'activado' : 'inactivado';
        session()->flash('success', "Usuario {$usuario->nombre_completo} {$estado}.");
    }

    public function cerrarModal(): void
    {
        $this->modalAbierto = false;
        $this->limpiarFormulario();
    }

    private function limpiarFormulario(): void
    {
        $this->reset([
            'usuarioEditandoId', 'nombre', 'apellido', 'email',
            'telefono', 'password', 'password_confirmation', 'rol',
        ]);
        $this->activo = true;
        $this->resetValidation();
    }

    public function render()
    {
        $usuarios = Usuario::query()
            ->with('roles')
            ->where('id_empresa', auth()->user()->id_empresa)
            ->when($this->busqueda, fn ($q) =>
                $q->where(fn ($q2) =>
                    $q2->where('nombre', 'like', "%{$this->busqueda}%")
                       ->orWhere('apellido', 'like', "%{$this->busqueda}%")
                       ->orWhere('email', 'like', "%{$this->busqueda}%")
                )
            )
            ->when($this->filtroRol, fn ($q) =>
                $q->whereHas('roles', fn ($q2) => $q2->where('name', $this->filtroRol))
            )
            ->when($this->filtroActivo !== '', fn ($q) =>
                $q->where('activo', $this->filtroActivo === '1')
            )
            ->orderBy('apellido')
            ->orderBy('nombre')
            ->paginate(15);

        $roles = Role::orderBy('name')->get();

        return view('livewire.admin.gestion-usuarios', compact('usuarios', 'roles'));
    }
}
