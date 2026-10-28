<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\Galy\GalyAccountBridge;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class WebLoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_storefront_login_page_has_working_login_and_password_reset_links(): void
    {
        $this->get(route('web.login'))
            ->assertOk()
            ->assertSee(route('web.autenticacion'), false)
            ->assertSee(route('password.request'), false)
            ->assertSee('name="remember"', false)
            ->assertSee('Comprar con Google')
            ->assertSee('Entrarás a la tienda como cliente, aunque este Gmail también sea administrador.')
            ->assertSee('Administradores: usa aquí tu contraseña GALY para entrar al panel.')
            ->assertSee('La contraseña de Google no se usa en este formulario.')
            ->assertSee('data-password-toggle="loginPassword"', false)
            ->assertSee('Para cambiar de Gmail, cierra sesión y elige otra cuenta.')
            ->assertSee('Inicia sesión para continuar con tu cuenta.')
            ->assertDontSee('Inicie sesíon');

        $this->get(route('password.request'))->assertOk();
    }

    public function test_customer_registration_page_explains_account_role_and_supports_accessible_password_controls(): void
    {
        $this->get(route('web.registro'))
            ->assertOk()
            ->assertSee('Cuenta de cliente')
            ->assertSee('Los permisos de administrador solo los asigna un administrador autorizado.')
            ->assertSee('data-password-toggle="password"', false)
            ->assertSee('data-password-toggle="confirmPassword"', false)
            ->assertSee('name="password_confirmation"', false)
            ->assertSee('maxlength="100"', false);
    }

    public function test_password_recovery_pages_use_the_storefront_layout_and_spanish_copy(): void
    {
        $this->get(route('password.request'))
            ->assertOk()
            ->assertSee('Recupera tu acceso')
            ->assertSee('Enviar enlace de recuperación')
            ->assertSee(route('web.login'), false)
            ->assertDontSee('Reset Password')
            ->assertDontSee('Send Password Reset Link');

        $this->get(route('password.reset', ['token' => 'sample-reset-token', 'email' => 'cliente@example.com']))
            ->assertOk()
            ->assertSee('Crear contraseña nueva')
            ->assertSee('Protege tu cuenta')
            ->assertSee('name="password_confirmation"', false)
            ->assertSee(route('web.login'), false)
            ->assertDontSee('Reset Password');
    }

    public function test_galy_account_linking_login_explains_which_credentials_to_use(): void
    {
        $this->get(route('web.login', ['galy' => 'connect']))
            ->assertOk()
            ->assertSee('inicia sesión con el mismo correo y la contraseña de GALY')
            ->assertSee('name="galy" value="connect"', false)
            ->assertSee('Solo GALY puede confirmar los permisos de administrador.');

        $this->get(route('web.login', ['galy' => 'renew']))
            ->assertOk()
            ->assertSee('Inicia sesión otra vez con tu cuenta administradora GALY');
    }

    public function test_connect_mode_uses_galy_credentials_even_when_a_local_password_matches(): void
    {
        $user = User::factory()->create([
            'email' => 'connect-customer@example.com',
            'password' => Hash::make('same-password'),
        ]);

        Http::fake([
            'http://localhost/galy_api/login.php' => Http::response([
                'success' => true,
                'user' => [
                    'id' => 88,
                    'nombre' => 'Cliente Vinculado',
                    'email' => 'connect-customer@example.com',
                    'es_admin' => false,
                ],
                'access_token' => 'shared-access-token',
            ]),
        ]);

        $this->post(route('web.autenticacion'), [
            'email' => $user->email,
            'password' => 'same-password',
            'galy' => 'connect',
        ])->assertRedirect('/dashboard')
            ->assertSessionHas('galy_access_token', 'shared-access-token');

        $this->assertAuthenticatedAs($user);
        $this->assertSame(88, $user->fresh()->galy_user_id);
        Http::assertSent(fn ($request) => $request->url() === 'http://localhost/galy_api/login.php');
    }

    public function test_storefront_login_reports_invalid_credentials(): void
    {
        Http::fake([
            'http://localhost/galy_api/login.php' => Http::response(['success' => false], 401),
        ]);

        $this->from(route('web.login'))
            ->post(route('web.autenticacion'), [
                'email' => 'customer@example.com',
                'password' => 'wrong-password',
            ])
            ->assertRedirect(route('web.login'))
            ->assertSessionHasErrors('email');
    }

    public function test_local_super_admin_can_sign_in_when_galy_backend_is_unavailable(): void
    {
        Http::fake([
            '*' => Http::response(['success' => false], 500),
        ]);

        Role::firstOrCreate(['name' => 'SUPER ADMIN']);

        $user = User::factory()->create([
            'email' => 'admin@gmail.com',
            'password' => Hash::make('12345678'),
        ]);
        $user->assignRole('SUPER ADMIN');

        $this->from('/web/login')
            ->post('/web/login', [
                'email' => 'admin@gmail.com',
                'password' => '12345678',
            ])
            ->assertRedirect('/home');

        $this->assertAuthenticatedAs($user);
        $this->assertFalse($user->fresh()->galy_es_admin);
    }

    public function test_linked_admin_uses_galy_authentication_to_start_a_shared_admin_session(): void
    {
        Role::firstOrCreate(['name' => 'SUPER ADMIN']);

        $user = User::factory()->create([
            'email' => 'linked-admin@example.com',
            'password' => Hash::make('shared-password'),
            'galy_user_id' => 12,
            'galy_es_admin' => true,
        ]);
        $user->assignRole('SUPER ADMIN');

        $bridge = \Mockery::mock(GalyAccountBridge::class);
        $bridge->shouldReceive('authenticate')
            ->once()
            ->with('linked-admin@example.com', 'shared-password', false)
            ->andReturnUsing(function () use ($user): User {
                Auth::login($user);
                request()->session()->regenerate();
                request()->session()->put('galy_admin_token', 'shared-admin-token');

                return $user;
            });
        $this->app->instance(GalyAccountBridge::class, $bridge);

        $this->post('/web/login', [
            'email' => ' Linked-Admin@Example.com ',
            'password' => 'shared-password',
        ])->assertRedirect('/home')
            ->assertSessionHas('galy_admin_token', 'shared-admin-token');

        $this->assertAuthenticatedAs($user);
    }

    public function test_galy_customer_login_does_not_remove_an_existing_local_admin_role(): void
    {
        Role::firstOrCreate(['name' => 'SUPER ADMIN']);
        $user = User::factory()->create([
            'email' => 'shared-admin@example.com',
            'password' => Hash::make('shared-password'),
        ]);
        $user->assignRole('SUPER ADMIN');

        Http::fake([
            'http://localhost/galy_api/login.php' => Http::response([
                'success' => true,
                'user' => [
                    'id' => 42,
                    'nombre' => 'Administrador Local',
                    'email' => 'shared-admin@example.com',
                    'es_admin' => false,
                ],
                'access_token' => 'customer-access-token',
            ]),
        ]);

        $this->post('/web/login', [
            'email' => $user->email,
            'password' => 'shared-password',
        ])->assertRedirect('/home');

        $user->refresh();
        $this->assertAuthenticatedAs($user);
        $this->assertSame(42, $user->galy_user_id);
        $this->assertFalse($user->galy_es_admin);
        $this->assertTrue($user->hasRole('SUPER ADMIN'));
        $this->assertFalse($user->hasRole('CLIENTE'));
    }

    public function test_local_user_with_legacy_plaintext_password_can_still_sign_in(): void
    {
        Http::fake([
            'http://localhost/galy_api/login.php' => Http::response(['success' => false], 401),
        ]);
        Role::firstOrCreate(['name' => 'SUPER ADMIN']);

        $user = User::factory()->create([
            'email' => 'legacy-admin@example.com',
            'password' => '12345678',
        ]);
        $user->assignRole('SUPER ADMIN');

        $this->from('/login')
            ->post('/login', [
                'email' => 'legacy-admin@example.com',
                'password' => '12345678',
            ])
            ->assertRedirect('/home');

        $this->assertAuthenticatedAs($user);
        $this->assertTrue(password_get_info($user->fresh()->password)['algo'] !== 0);
    }

    public function test_storefront_login_accepts_credentials_and_remembers_the_user(): void
    {
        $user = User::factory()->create([
            'email' => 'customer@example.com',
            'password' => Hash::make('correct-password'),
        ]);
        Http::fake([
            'http://localhost/galy_api/login.php' => Http::response(['success' => false], 401),
        ]);

        $this->post(route('web.autenticacion'), [
            'email' => 'customer@example.com',
            'password' => 'correct-password',
            'remember' => '1',
        ])
            ->assertRedirect('/dashboard')
            ->assertSessionHas('_token');

        $this->assertAuthenticatedAs($user);
        $this->assertNotNull($user->fresh()->remember_token);
    }

    public function test_customer_logout_clears_the_web_and_galy_sessions_before_another_customer_signs_in(): void
    {
        $user = User::factory()->create([
            'email' => 'google-customer@example.com',
            'google_id' => 'google-customer-id',
            'galy_user_id' => 42,
        ]);

        $this->actingAs($user)
            ->withSession([
                'galy_user_id' => 42,
                'galy_access_token' => 'customer-access-token',
                'galy_admin_token' => null,
                'google_link_user_id' => $user->id,
            ])
            ->post(route('logout'))
            ->assertRedirect('/');

        $this->assertGuest();
        $this->assertGuest('web');
        $this->assertFalse(session()->has('galy_user_id'));
        $this->assertFalse(session()->has('galy_access_token'));
        $this->assertFalse(session()->has('google_link_user_id'));
    }

    public function test_customer_can_register_without_preseeded_roles(): void
    {
        Http::fake([
            'http://localhost/galy_api/register.php' => Http::response(['success' => true]),
            'http://localhost/galy_api/login.php' => Http::response([
                'success' => true,
                'user' => [
                    'id' => 52,
                    'nombre' => 'Nuevo Cliente',
                    'email' => 'new-customer@example.com',
                    'es_admin' => false,
                ],
                'access_token' => 'new-customer-access-token',
            ]),
        ]);

        $this->post(route('web.crear_cuenta'), [
            'name' => 'Nuevo Cliente',
            'email' => 'new-customer@example.com',
            'password' => 'strong-password',
            'password_confirmation' => 'strong-password',
        ])
            ->assertRedirect('/dashboard');

        $user = User::where('email', 'new-customer@example.com')->firstOrFail();

        $this->assertAuthenticatedAs($user);
        $this->assertTrue($user->hasRole('CLIENTE'));
        $this->assertSame(52, $user->galy_user_id);
        $this->assertDatabaseHas('roles', [
            'name' => 'CLIENTE',
            'guard_name' => 'web',
        ]);
    }

    public function test_customer_registration_does_not_create_a_web_only_account_when_galy_is_unavailable(): void
    {
        Http::fake([
            '*' => Http::response(['success' => false], 500),
        ]);

        $this->from(route('web.registro'))
            ->post(route('web.crear_cuenta'), [
                'name' => 'Cliente Local',
                'email' => 'local-customer@example.com',
                'password' => 'strong-password',
                'password_confirmation' => 'strong-password',
            ])
            ->assertRedirect(route('web.registro'))
            ->assertSessionHasErrors('email');

        $this->assertGuest();
        $this->assertDatabaseMissing('users', ['email' => 'local-customer@example.com']);
    }

    public function test_a_customer_can_register_when_the_galy_backend_is_unreachable_and_local_accounts_are_allowed(): void
    {
        Http::fake(function (): void {
            throw new ConnectionException('cURL error 7: Failed to connect');
        });
        config()->set('galy.registro_local', true);

        $this->from(route('web.registro'))
            ->post(route('web.crear_cuenta'), [
                'name' => 'Cliente del Servidor',
                'email' => 'servidor@example.com',
                'password' => 'strong-password',
                'password_confirmation' => 'strong-password',
            ])
            ->assertRedirect('/dashboard');

        $user = User::where('email', 'servidor@example.com')->firstOrFail();
        $this->assertAuthenticatedAs($user);
        $this->assertTrue($user->hasRole('CLIENTE'));
        $this->assertNotNull($user->galy_user_id);
        $this->assertTrue(Hash::check('strong-password', $user->password));
        $this->assertDatabaseHas('usuarios', ['email' => 'servidor@example.com'], 'galy');

        // Lo que decide si la tienda puede vender: el carrito se arma sobre el id
        // de GALY, así que una cuenta sin esa fila no serviría para nada.
        $this->get(route('web.carrito.index'))->assertOk();
    }

    public function test_a_rejected_registration_is_not_retried_locally_even_with_the_switch_on(): void
    {
        Http::fake([
            '*' => Http::response(['success' => false], 500),
        ]);
        config()->set('galy.registro_local', true);

        $this->from(route('web.registro'))
            ->post(route('web.crear_cuenta'), [
                'name' => 'Rechazado',
                'email' => 'rechazado@example.com',
                'password' => 'strong-password',
                'password_confirmation' => 'strong-password',
            ])
            ->assertRedirect(route('web.registro'))
            ->assertSessionHasErrors('email');

        $this->assertGuest();
        $this->assertDatabaseMissing('users', ['email' => 'rechazado@example.com']);
        $this->assertDatabaseMissing('usuarios', ['email' => 'rechazado@example.com'], 'galy');
    }

    public function test_an_unreachable_backend_does_not_duplicate_an_email_that_galy_already_owns(): void
    {
        DB::connection('galy')->table('usuarios')->insert([
            'nombre' => 'Dueño GALY',
            'email' => 'debaja@example.com',
            'password' => Hash::make('otra-contrasena'),
        ]);

        Http::fake(function (): void {
            throw new ConnectionException('cURL error 7: Failed to connect');
        });
        config()->set('galy.registro_local', true);

        $this->from(route('web.registro'))
            ->post(route('web.crear_cuenta'), [
                'name' => 'Duplicado',
                'email' => 'debaja@example.com',
                'password' => 'strong-password',
                'password_confirmation' => 'strong-password',
            ])
            ->assertRedirect(route('web.registro'))
            ->assertSessionHasErrors('email');

        $this->assertGuest();
        $this->assertDatabaseMissing('users', ['email' => 'debaja@example.com']);
    }

    public function test_registration_rejects_duplicate_email_and_mismatched_passwords(): void
    {
        User::factory()->create(['email' => 'existing@example.com']);

        $this->from(route('web.registro'))
            ->post(route('web.crear_cuenta'), [
                'name' => 'Nuevo Cliente',
                'email' => 'existing@example.com',
                'password' => 'strong-password',
                'password_confirmation' => 'different-password',
            ])
            ->assertRedirect(route('web.registro'))
            ->assertSessionHasErrors(['email', 'password']);

        $this->assertDatabaseCount('users', 1);
    }

    public function test_customer_can_reset_password_using_a_reset_link(): void
    {
        Notification::fake();
        $user = User::factory()->create(['email' => 'reset-customer@example.com']);

        $this->post(route('password.email'), ['email' => $user->email])
            ->assertSessionHas('status');

        $token = null;
        Notification::assertSentTo($user, ResetPassword::class, function (ResetPassword $notification) use (&$token): bool {
            $token = $notification->token;

            return true;
        });

        $this->assertNotNull($token);
        $this->post(route('password.update'), [
            'email' => $user->email,
            'token' => $token,
            'password' => 'replacement-password',
            'password_confirmation' => 'replacement-password',
        ])->assertRedirect('/home');

        $this->assertTrue(Hash::check('replacement-password', $user->fresh()->password));
    }

    public function test_password_reset_updates_the_linked_galy_account_too(): void
    {
        Notification::fake();
        $user = User::factory()->create([
            'email' => 'linked-reset@example.com',
            'galy_user_id' => 63,
        ]);
        DB::connection('galy')->table('usuarios')->insert([
            'id' => 63,
            'nombre' => 'Cliente GALY',
            'email' => 'linked-reset@example.com',
            'password' => Hash::make('old-password'),
        ]);

        $this->post(route('password.email'), ['email' => $user->email])
            ->assertSessionHas('status');

        $token = null;
        Notification::assertSentTo($user, ResetPassword::class, function (ResetPassword $notification) use (&$token): bool {
            $token = $notification->token;

            return true;
        });

        $this->post(route('password.update'), [
            'email' => $user->email,
            'token' => $token,
            'password' => 'replacement-password',
            'password_confirmation' => 'replacement-password',
        ])->assertRedirect('/home');

        $this->assertTrue(Hash::check('replacement-password', $user->fresh()->password));
        $this->assertTrue(Hash::check(
            'replacement-password',
            DB::connection('galy')->table('usuarios')->where('id', 63)->value('password')
        ));
    }

    public function test_password_reset_links_and_updates_a_matching_galy_account(): void
    {
        Notification::fake();
        $user = User::factory()->create(['email' => 'unlinked-reset@example.com']);
        DB::connection('galy')->table('usuarios')->insert([
            'id' => 64,
            'nombre' => 'Cuenta GALY sin vínculo previo',
            'email' => 'unlinked-reset@example.com',
            'password' => Hash::make('old-password'),
        ]);

        $this->post(route('password.email'), ['email' => $user->email])
            ->assertSessionHas('status');

        $token = null;
        Notification::assertSentTo($user, ResetPassword::class, function (ResetPassword $notification) use (&$token): bool {
            $token = $notification->token;

            return true;
        });

        $this->post(route('password.update'), [
            'email' => $user->email,
            'token' => $token,
            'password' => 'replacement-password',
            'password_confirmation' => 'replacement-password',
        ])->assertRedirect('/home');

        $this->assertSame(64, $user->fresh()->galy_user_id);
        $this->assertTrue(Hash::check(
            'replacement-password',
            DB::connection('galy')->table('usuarios')->where('id', 64)->value('password')
        ));
    }
}
