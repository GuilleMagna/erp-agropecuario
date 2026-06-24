<?php

namespace App\Models;

use App\Traits\UsaUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Permission\Traits\HasRoles;

/**
 * Modelo Usuario
 *
 * Documento 03, sección 4.3. Extiende Authenticatable (no Model simple)
 * porque Laravel lo necesita así para el sistema de autenticación/sesiones.
 *
 * Incorpora:
 * - HasRoles (spatie/laravel-permission): roles y permisos del Documento 02,
 *   sección 1.2. Los roles predefinidos se cargan en el RolesSeeder.
 * - LogsActivity (spatie/laravel-activitylog): registra automáticamente los
 *   cambios sobre los propios usuarios en el log de actividad, parte del
 *   requerimiento RF-004 (Documento 09).
 *
 * NOTA: PerteneceAEmpresa NO se aplica acá. El usuario ya tiene id_empresa
 * como columna propia y es, de hecho, la entidad desde la que el Global
 * Scope obtiene el id_empresa de los demás modelos (via auth()->user()->id_empresa).
 * Aplicar el scope al propio Usuario generaría una dependencia circular.
 *
 * @property string $id
 * @property string $id_empresa
 * @property string $nombre
 * @property string $apellido
 * @property string $email
 * @property string|null $telefono
 * @property string $password
 * @property string|null $foto_url
 * @property bool $mfa_habilitado
 * @property bool $activo
 * @property \Carbon\Carbon|null $ultimo_acceso
 */
class Usuario extends Authenticatable
{
    use HasFactory, UsaUuid, HasRoles, LogsActivity, Notifiable;

    protected $table = 'usuarios';

    protected $fillable = [
        'id_empresa',
        'nombre',
        'apellido',
        'email',
        'telefono',
        'password',
        'foto_url',
        'mfa_habilitado',
        'activo',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'mfa_habilitado'       => 'boolean',
        'activo'               => 'boolean',
        'ultimo_acceso'        => 'datetime',
        'email_verified_at'    => 'datetime',
        'password'             => 'hashed',
    ];

    // -------------------------------------------------------------------------
    // Configuración de spatie/laravel-activitylog
    // -------------------------------------------------------------------------

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['nombre', 'apellido', 'email', 'activo'])
            ->logOnlyDirty()       // solo loguear campos que realmente cambiaron
            ->dontSubmitEmptyLogs(); // no crear registro si no cambió nada
    }

    // -------------------------------------------------------------------------
    // Relaciones
    // -------------------------------------------------------------------------

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class, 'id_empresa');
    }

    /** Establecimientos a los que tiene acceso (N:M). */
    public function establecimientos(): BelongsToMany
    {
        return $this->belongsToMany(
            Establecimiento::class,
            'usuarios_establecimientos',
            'id_usuario',
            'id_establecimiento'
        );
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /** Nombre completo para mostrar en la interfaz. */
    public function getNombreCompletoAttribute(): string
    {
        return "{$this->nombre} {$this->apellido}";
    }

    /** Actualizar la marca de tiempo de último acceso al iniciar sesión. */
    public function registrarAcceso(): void
    {
        $this->timestamps = false; // no tocar updated_at
        $this->ultimo_acceso = now();
        $this->save();
        $this->timestamps = true;
    }

    // -------------------------------------------------------------------------
    // Scopes locales
    // -------------------------------------------------------------------------

    public function scopeActivos($query)
    {
        return $query->where('activo', true);
    }
}
