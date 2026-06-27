<?php

namespace App\Livewire\Sistema;

use Illuminate\Support\Facades\Hash;
use Livewire\Component;

class GestionPerfil extends Component
{
    // Datos personales
    public string $nombre   = '';
    public string $apellido = '';
    public string $email    = '';
    public string $telefono = '';

    // Cambio de contraseña
    public string $password_actual       = '';
    public string $password_nuevo        = '';
    public string $password_confirmacion = '';

    public function mount(): void
    {
        $user = auth()->user();
        $this->nombre   = $user->nombre;
        $this->apellido = $user->apellido;
        $this->email    = $user->email;
        $this->telefono = $user->telefono ?? '';
    }

    public function guardarDatos(): void
    {
        $this->validate([
            'nombre'   => 'required|string|max:100',
            'apellido' => 'required|string|max:100',
            'email'    => 'required|email|max:150|unique:usuarios,email,' . auth()->id(),
            'telefono' => 'nullable|string|max:30',
        ], [
            'nombre.required'   => 'El nombre es obligatorio.',
            'apellido.required' => 'El apellido es obligatorio.',
            'email.required'    => 'El email es obligatorio.',
            'email.email'       => 'Ingresá un email válido.',
            'email.unique'      => 'Ese email ya está en uso por otro usuario.',
        ]);

        auth()->user()->update([
            'nombre'   => $this->nombre,
            'apellido' => $this->apellido,
            'email'    => $this->email,
            'telefono' => $this->telefono ?: null,
        ]);

        session()->flash('success_datos', 'Datos personales actualizados correctamente.');
    }

    public function cambiarPassword(): void
    {
        $this->validate([
            'password_actual'       => 'required',
            'password_nuevo'        => 'required|min:8',
            'password_confirmacion' => 'required|same:password_nuevo',
        ], [
            'password_actual.required'       => 'Ingresá tu contraseña actual.',
            'password_nuevo.required'        => 'Ingresá la nueva contraseña.',
            'password_nuevo.min'             => 'La nueva contraseña debe tener al menos 8 caracteres.',
            'password_confirmacion.required' => 'Confirmá la nueva contraseña.',
            'password_confirmacion.same'     => 'Las contraseñas no coinciden.',
        ]);

        if (!Hash::check($this->password_actual, auth()->user()->password)) {
            $this->addError('password_actual', 'La contraseña actual no es correcta.');
            return;
        }

        auth()->user()->update(['password' => $this->password_nuevo]);

        $this->reset(['password_actual', 'password_nuevo', 'password_confirmacion']);
        $this->resetValidation(['password_actual', 'password_nuevo', 'password_confirmacion']);

        session()->flash('success_password', 'Contraseña actualizada correctamente.');
    }

    public function render()
    {
        return view('livewire.sistema.gestion-perfil');
    }
}
