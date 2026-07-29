<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Usuario;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

class GoogleController extends Controller
{
    /**
     * Redirigir al usuario a la pantalla de autenticación de Google.
     */
    public function redirect(): RedirectResponse
    {
        return Socialite::driver('google')
            ->stateless()
            ->redirect();
    }

    /**
     * Procesar el callback de Google: vincula con un usuario existente por
     * email o crea uno nuevo. El control de acceso queda solo en la lista de
     * "test users" de Google Cloud Console (OAuth consent screen) — si Google
     * lo dejó llegar hasta acá, entra directo con rol "administrativo".
     */
    public function callback(): RedirectResponse
    {
        if (! request()->query->has('code')) {
            $requestUri = (string) request()->server('REQUEST_URI');
            parse_str((string) parse_url($requestUri, PHP_URL_QUERY), $query);

            if (is_string($query['code'] ?? null)) {
                request()->query->set('code', $query['code']);
            }
        }

        $googleUser = Socialite::driver('google')->stateless()->user();

        $usuario = Usuario::where('email', $googleUser->getEmail())->first();

        if (! $usuario) {
            $nombre = $googleUser->user['given_name'] ?? $googleUser->getName();
            $apellido = $googleUser->user['family_name'] ?? '';

            $usuario = Usuario::create([
                'id_empresa' => Usuario::query()->value('id_empresa'),
                'nombre' => $nombre,
                'apellido' => $apellido,
                'email' => $googleUser->getEmail(),
                'password' => Hash::make(Str::random(40)),
                'foto_url' => $googleUser->getAvatar(),
                'google_id' => $googleUser->getId(),
                'activo' => true,
            ]);
            $usuario->assignRole('administrativo');
        }

        if (! $usuario->google_id) {
            $usuario->google_id = $googleUser->getId();
            $usuario->foto_url = $usuario->foto_url ?: $googleUser->getAvatar();
            $usuario->save();
        }

        if (! $usuario->activo) {
            return redirect()->route('login')->withErrors([
                'email' => 'Tu cuenta está inactiva. Contactá a un administrador.',
            ]);
        }

        Auth::login($usuario, true);
        request()->session()->regenerate();
        $usuario->registrarAcceso();

        return redirect()->intended(route('dashboard', absolute: false));
    }
}
