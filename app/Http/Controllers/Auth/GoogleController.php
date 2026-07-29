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
        $state = Str::random(40);

        return Socialite::driver('google')
            ->stateless()
            ->with(['state' => $state])
            ->redirect()
            ->withCookie(cookie(
                'google_oauth_state',
                $state,
                10,
                secure: true,
                httpOnly: true,
                sameSite: 'lax',
            ));
    }

    /**
     * Procesar el callback de Google: vincula con un usuario existente por
     * email o crea uno nuevo. El control de acceso queda solo en la lista de
     * "test users" de Google Cloud Console (OAuth consent screen) — si Google
     * lo dejó llegar hasta acá, entra directo con rol "administrativo".
     */
    public function callback(): RedirectResponse
    {
        $state = request()->string('state')->toString();
        $expectedState = request()->cookie('google_oauth_state');
        $validState = is_string($expectedState)
            && strlen($expectedState) === 40
            && hash_equals($expectedState, $state);
        cookie()->queue(cookie()->forget('google_oauth_state'));

        if (! $validState) {
            return redirect()->route('login')->withErrors([
                'email' => 'La sesión de Google venció o no es válida. Intentá iniciar sesión nuevamente.',
            ]);
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
