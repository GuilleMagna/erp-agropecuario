<?php

namespace App\Livewire\Admin;

use App\Models\Establecimiento;
use App\Models\Usuario;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;
use Livewire\WithPagination;

class GestionEstablecimientos extends Component
{
    use WithPagination;

    const PROVINCIAS = [
        'Buenos Aires', 'Catamarca', 'Chaco', 'Chubut', 'Córdoba',
        'Corrientes', 'Entre Ríos', 'Formosa', 'Jujuy', 'La Pampa',
        'La Rioja', 'Mendoza', 'Misiones', 'Neuquén', 'Río Negro',
        'Salta', 'San Juan', 'San Luis', 'Santa Cruz', 'Santa Fe',
        'Santiago del Estero', 'Tierra del Fuego', 'Tucumán',
        'Ciudad Autónoma de Buenos Aires',
    ];

    const TIPOS_TENENCIA = [
        'propio'    => 'Propio',
        'arrendado' => 'Arrendado',
        'mixto'     => 'Mixto',
        'usufructo' => 'Usufructo',
    ];

    // Filtros
    public string $busqueda        = '';
    public string $filtroProvincia = '';
    public string $filtroActivo    = '';

    // Estado del modal
    public bool    $modalAbierto              = false;
    public bool    $modoEdicion               = false;
    public ?string $establecimientoEditandoId = null;

    // Campos del formulario
    public string $nombre                = '';
    public string $provincia             = '';
    public string $partido_departamento  = '';
    public string $localidad             = '';
    public string $latitud               = '';
    public string $longitud              = '';
    public string $superficie_total_ha   = '';
    public string $superficie_agricola_ha = '';
    public string $superficie_ganadera_ha = '';
    public string $tipo_tenencia         = 'propio';
    public string $partida_catastral     = '';
    public string $responsable_id        = '';
    public bool   $activo                = true;

    protected array $messages = [
        'nombre.required'                => 'El nombre del establecimiento es obligatorio.',
        'nombre.max'                     => 'El nombre no puede superar 150 caracteres.',
        'latitud.numeric'                => 'La latitud debe ser un número.',
        'latitud.between'                => 'La latitud debe estar entre -90 y 90.',
        'longitud.numeric'               => 'La longitud debe ser un número.',
        'longitud.between'               => 'La longitud debe estar entre -180 y 180.',
        'superficie_total_ha.numeric'    => 'La superficie total debe ser un número.',
        'superficie_total_ha.min'        => 'La superficie total no puede ser negativa.',
        'superficie_agricola_ha.numeric' => 'La superficie agrícola debe ser un número.',
        'superficie_agricola_ha.min'     => 'La superficie agrícola no puede ser negativa.',
        'superficie_ganadera_ha.numeric' => 'La superficie ganadera debe ser un número.',
        'superficie_ganadera_ha.min'     => 'La superficie ganadera no puede ser negativa.',
        'tipo_tenencia.in'               => 'El tipo de tenencia seleccionado no es válido.',
        'responsable_id.exists'          => 'El responsable seleccionado no existe.',
    ];

    protected function rules(): array
    {
        return [
            'nombre'               => 'required|string|max:150',
            'provincia'            => 'nullable|string|max:100',
            'partido_departamento' => 'nullable|string|max:100',
            'localidad'            => 'nullable|string|max:100',
            'latitud'              => 'nullable|numeric|between:-90,90',
            'longitud'             => 'nullable|numeric|between:-180,180',
            'superficie_total_ha'   => 'nullable|numeric|min:0',
            'superficie_agricola_ha'=> 'nullable|numeric|min:0',
            'superficie_ganadera_ha'=> 'nullable|numeric|min:0',
            'tipo_tenencia'        => 'required|in:propio,arrendado,mixto,usufructo',
            'partida_catastral'    => 'nullable|string|max:100',
            'responsable_id'       => 'nullable|exists:usuarios,id',
        ];
    }

    public function updatingBusqueda(): void        { $this->resetPage(); }
    public function updatingFiltroProvincia(): void { $this->resetPage(); }
    public function updatingFiltroActivo(): void    { $this->resetPage(); }

    public function abrirModalCrear(): void
    {
        Gate::authorize('admin.establecimientos.gestionar');
        $this->limpiarFormulario();
        $this->modalAbierto = true;
    }

    public function abrirModalEditar(string $id): void
    {
        Gate::authorize('admin.establecimientos.gestionar');

        $est = Establecimiento::findOrFail($id);

        $this->establecimientoEditandoId = $id;
        $this->modoEdicion               = true;
        $this->nombre                    = $est->nombre;
        $this->provincia                 = $est->provincia ?? '';
        $this->partido_departamento      = $est->partido_departamento ?? '';
        $this->localidad                 = $est->localidad ?? '';
        $this->latitud                   = $est->latitud !== null ? (string) $est->latitud : '';
        $this->longitud                  = $est->longitud !== null ? (string) $est->longitud : '';
        $this->superficie_total_ha       = $est->superficie_total_ha !== null ? (string) $est->superficie_total_ha : '';
        $this->superficie_agricola_ha    = $est->superficie_agricola_ha !== null ? (string) $est->superficie_agricola_ha : '';
        $this->superficie_ganadera_ha    = $est->superficie_ganadera_ha !== null ? (string) $est->superficie_ganadera_ha : '';
        $this->tipo_tenencia             = $est->tipo_tenencia;
        $this->partida_catastral         = $est->partida_catastral ?? '';
        $this->responsable_id            = $est->responsable_id ?? '';
        $this->activo                    = $est->activo;

        $this->modalAbierto = true;
    }

    public function guardar(): void
    {
        Gate::authorize('admin.establecimientos.gestionar');

        $datos = $this->validate();

        // Normalizar cadenas vacías a null en campos opcionales
        foreach (['provincia', 'partido_departamento', 'localidad', 'latitud', 'longitud',
                  'superficie_total_ha', 'superficie_agricola_ha', 'superficie_ganadera_ha',
                  'partida_catastral', 'responsable_id'] as $campo) {
            if (array_key_exists($campo, $datos) && $datos[$campo] === '') {
                $datos[$campo] = null;
            }
        }

        if ($this->modoEdicion) {
            $est = Establecimiento::findOrFail($this->establecimientoEditandoId);
            $est->update(array_merge($datos, ['activo' => $this->activo]));
            session()->flash('success', "Establecimiento \"{$est->nombre}\" actualizado correctamente.");
        } else {
            $est = Establecimiento::create($datos);
            session()->flash('success', "Establecimiento \"{$est->nombre}\" creado correctamente.");
        }

        $this->cerrarModal();
    }

    public function toggleActivo(string $id): void
    {
        Gate::authorize('admin.establecimientos.gestionar');

        $est = Establecimiento::findOrFail($id);
        $est->update(['activo' => ! $est->activo]);

        $estado = $est->activo ? 'activado' : 'desactivado';
        session()->flash('success', "Establecimiento \"{$est->nombre}\" {$estado}.");
    }

    public function cerrarModal(): void
    {
        $this->modalAbierto = false;
        $this->limpiarFormulario();
    }

    private function limpiarFormulario(): void
    {
        $this->reset([
            'establecimientoEditandoId', 'modoEdicion',
            'nombre', 'provincia', 'partido_departamento', 'localidad',
            'latitud', 'longitud',
            'superficie_total_ha', 'superficie_agricola_ha', 'superficie_ganadera_ha',
            'partida_catastral', 'responsable_id',
        ]);
        $this->tipo_tenencia = 'propio';
        $this->activo        = true;
        $this->resetValidation();
    }

    public function render(): \Illuminate\View\View
    {
        $establecimientos = Establecimiento::query()
            ->with('responsable')
            ->when($this->busqueda, function ($q) {
                $q->where(function ($inner) {
                    $inner->where('nombre', 'like', "%{$this->busqueda}%")
                          ->orWhere('localidad', 'like', "%{$this->busqueda}%")
                          ->orWhere('provincia', 'like', "%{$this->busqueda}%");
                });
            })
            ->when($this->filtroProvincia, fn ($q) => $q->where('provincia', $this->filtroProvincia))
            ->when($this->filtroActivo !== '', fn ($q) => $q->where('activo', $this->filtroActivo === '1'))
            ->orderBy('nombre')
            ->paginate(15);

        $provincias = Establecimiento::query()
            ->select('provincia')
            ->whereNotNull('provincia')
            ->distinct()
            ->orderBy('provincia')
            ->pluck('provincia');

        $usuariosOpciones = Usuario::query()
            ->where('id_empresa', auth()->user()->id_empresa)
            ->activos()
            ->orderBy('apellido')
            ->orderBy('nombre')
            ->get(['id', 'nombre', 'apellido']);

        return view('livewire.admin.gestion-establecimientos', [
            'establecimientos' => $establecimientos,
            'provincias'       => $provincias,
            'usuariosOpciones' => $usuariosOpciones,
            'tiposTenencia'    => self::TIPOS_TENENCIA,
            'provinciasLista'  => self::PROVINCIAS,
        ]);
    }
}
