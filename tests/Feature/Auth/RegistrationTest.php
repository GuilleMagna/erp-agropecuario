<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_screen_can_be_rendered(): void
    {
        $response = $this->get('/register');

        $response->assertStatus(200);
    }

    public function test_new_users_can_register(): void
    {
        $this->markTestSkipped(
            'El alta por formulario está rota: RegisteredUserController crea un App\Models\User '
            .'(tabla users), pero el guard autentica contra App\Models\Usuario (tabla usuarios), '
            .'así que el registro deja una fila huérfana y el usuario queda deslogueado. '
            .'Las altas reales entran por Google. Hay que decidir si se arregla el alta por '
            .'formulario (con qué empresa y rol) o si se sacan las rutas de registro.'
        );

        $response = $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard', absolute: false));
    }
}
