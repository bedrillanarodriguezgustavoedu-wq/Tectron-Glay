<?php

namespace Tests\Feature;

use App\Models\User;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Psr7\Request as GuzzleRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as GoogleUser;
use Mockery;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class GoogleAuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_storefront_login_offers_google_only_when_credentials_are_configured(): void
    {
        config()->set('services.google.client_id', 'google-client-id');
        config()->set('services.google.client_secret', 'google-client-secret');
        config()->set('services.google.redirect', 'http://localhost/auth/google/callback');

        $this->get(route('web.login'))
            ->assertOk()
            ->assertSee(route('auth.google.redirect'), false)
            ->assertSee('Comprar con Google')
            ->assertSee('Entrarás a la tienda como cliente, aunque este Gmail también sea administrador.')
            ->assertSee('Administradores: usa aquí tu contraseña GALY para entrar al panel.');
    }

    public function test_google_redirect_explains_when_credentials_are_missing(): void
    {
        config()->set('services.google.client_id', null);
        config()->set('services.google.client_secret', null);

        $this->get(route('auth.google.redirect'))
            ->assertRedirect(route('web.login'))
            ->assertSessionHas('google_login_error', 'El acceso con Google aún no está configurado.');
    }

    public function test_google_login_redirect_always_asks_which_google_account_to_use(): void
    {
        config()->set('services.google.client_id', 'google-client-id');
        config()->set('services.google.client_secret', 'google-client-secret');
        config()->set('services.google.redirect', 'http://localhost/auth/google/callback');

        $provider = Mockery::mock();
        $provider->shouldReceive('scopes')
            ->once()
            ->with(['openid', 'email', 'profile'])
            ->andReturnSelf();
        $provider->shouldReceive('with')
            ->once()
            ->with(['prompt' => 'select_account'])
            ->andReturnSelf();
        $provider->shouldReceive('redirect')->once()->andReturn(redirect('https://accounts.google.com/oauth'));
        Socialite::shouldReceive('driver')->once()->with('google')->andReturn($provider);

        $this->get(route('auth.google.redirect'))
            ->assertRedirect('https://accounts.google.com/oauth');
    }

    public function test_verified_google_customer_is_linked_to_the_matching_galy_account(): void
    {
        config()->set('services.google.client_id', 'google-client-id');
        config()->set('services.google.client_secret', 'google-client-secret');
        config()->set('services.google.redirect', 'http://localhost/auth/google/callback');

        DB::connection('galy')->table('usuarios')->insert([
            'nombre' => 'Cliente GALY',
            'email' => 'cliente@gmail.com',
            'password' => 'not-used-for-google-login',
        ]);

        $googleUser = GoogleUser::fake([
            'id' => 'google-customer-123',
            'email' => 'CLIENTE@gmail.com',
            'name' => 'Nombre Google',
            'user' => ['verified_email' => true],
        ]);

        $provider = Mockery::mock();
        $provider->shouldReceive('user')->once()->andReturn($googleUser);
        Socialite::shouldReceive('driver')->once()->with('google')->andReturn($provider);

        $this->get(route('auth.google.callback'))
            ->assertRedirect('/dashboard');

        $user = User::where('email', 'cliente@gmail.com')->firstOrFail();
        $this->assertAuthenticatedAs($user);
        $this->assertSame('google-customer-123', $user->google_id);
        $this->assertSame(1, $user->galy_user_id);
        $this->assertNotNull($user->email_verified_at);
        $this->assertSame(1, session('galy_user_id'));
    }

    public function test_verified_google_account_without_a_galy_user_is_registered_as_a_customer(): void
    {
        config()->set('services.google.client_id', 'google-client-id');
        config()->set('services.google.client_secret', 'google-client-secret');
        config()->set('services.google.redirect', 'http://localhost/auth/google/callback');

        $googleUser = GoogleUser::fake([
            'id' => 'google-new-customer-456',
            'email' => 'nueva.cliente@gmail.com',
            'name' => 'Nueva Cliente',
            'user' => ['verified_email' => true],
        ]);
        $provider = Mockery::mock();
        $provider->shouldReceive('user')->once()->andReturn($googleUser);
        Socialite::shouldReceive('driver')->once()->with('google')->andReturn($provider);

        $this->get(route('auth.google.callback'))
            ->assertRedirect('/dashboard');

        $galyUser = DB::connection('galy')
            ->table('usuarios')
            ->where('email', 'nueva.cliente@gmail.com')
            ->first();
        $this->assertNotNull($galyUser);
        $this->assertSame('Nueva Cliente', $galyUser->nombre);
        $this->assertNotSame('', $galyUser->password);
        $this->assertSame(0, (int) ($galyUser->es_admin ?? false));

        $user = User::where('email', 'nueva.cliente@gmail.com')->firstOrFail();
        $this->assertAuthenticatedAs($user);
        $this->assertSame('google-new-customer-456', $user->google_id);
        $this->assertSame((int) $galyUser->id, $user->galy_user_id);
        $this->assertFalse($user->galy_es_admin);
        $this->assertTrue($user->hasRole(Role::firstOrCreate(['name' => 'CLIENTE'])));
    }

    public function test_galy_admin_can_shop_with_google_without_getting_admin_access_in_that_session(): void
    {
        config()->set('services.google.client_id', 'google-client-id');
        config()->set('services.google.client_secret', 'google-client-secret');
        config()->set('services.google.redirect', 'http://localhost/auth/google/callback');

        Schema::connection('galy')->table('usuarios', function ($table): void {
            $table->boolean('es_admin')->default(false);
        });
        DB::connection('galy')->table('usuarios')->insert([
            'nombre' => 'Admin GALY',
            'email' => 'admin@gmail.com',
            'password' => 'not-used-for-google-login',
            'es_admin' => true,
        ]);

        $googleUser = GoogleUser::fake([
            'id' => 'google-admin-123',
            'email' => 'ADMIN@gmail.com',
            'name' => 'Admin Google',
            'user' => ['verified_email' => true],
        ]);
        $provider = Mockery::mock();
        $provider->shouldReceive('user')->once()->andReturn($googleUser);
        Socialite::shouldReceive('driver')->once()->with('google')->andReturn($provider);

        $this->get(route('auth.google.callback'))
            ->assertRedirect('/dashboard')
            ->assertSessionHas('google_customer_mode', true);

        $admin = User::where('email', 'admin@gmail.com')->firstOrFail();
        $this->assertAuthenticatedAs($admin);
        $this->assertTrue($admin->galy_es_admin);
        $this->assertFalse($admin->hasRole('CLIENTE'));
        $this->assertSame(1, DB::connection('galy')->table('usuarios')->where('email', 'admin@gmail.com')->count());
        $this->assertFalse(session()->has('galy_admin_token'));

        $this->get('/home')->assertForbidden();
        $this->get('/admin')->assertForbidden();
    }

    public function test_google_session_for_a_local_admin_is_limited_to_the_storefront(): void
    {
        config()->set('services.google.client_id', 'google-client-id');
        config()->set('services.google.client_secret', 'google-client-secret');
        config()->set('services.google.redirect', 'http://localhost/auth/google/callback');

        $admin = User::factory()->create([
            'email' => 'web-admin@gmail.com',
            'galy_es_admin' => true,
        ]);
        $admin->assignRole(Role::firstOrCreate([
            'name' => 'SUPER ADMIN',
            'guard_name' => 'web',
        ]));

        $permission = \Spatie\Permission\Models\Permission::firstOrCreate([
            'name' => 'Ver dashboard del Admin',
            'guard_name' => 'web',
        ]);
        $admin->givePermissionTo($permission);

        $googleUser = GoogleUser::fake([
            'id' => 'google-web-admin-789',
            'email' => 'web-admin@gmail.com',
            'user' => ['verified_email' => true],
        ]);
        $provider = Mockery::mock();
        $provider->shouldReceive('user')->once()->andReturn($googleUser);
        Socialite::shouldReceive('driver')->once()->with('google')->andReturn($provider);

        $this->get(route('auth.google.callback'))
            ->assertRedirect('/dashboard')
            ->assertSessionHas('google_customer_mode', true);

        $this->assertAuthenticatedAs($admin);
        $this->assertTrue($admin->fresh()->hasRole('SUPER ADMIN'));
        $this->assertTrue($admin->fresh()->galy_es_admin);
        $this->assertSame(
            1,
            DB::connection('galy')->table('usuarios')->where('email', 'web-admin@gmail.com')->count()
        );
        $this->get('/home')->assertForbidden();
    }

    public function test_signed_in_customer_can_link_their_matching_google_identity(): void
    {
        config()->set('services.google.client_id', 'google-client-id');
        config()->set('services.google.client_secret', 'google-client-secret');
        config()->set('services.google.redirect', 'http://localhost/auth/google/callback');

        $user = User::factory()->create([
            'email' => 'link-customer@gmail.com',
            'galy_user_id' => 5,
        ]);
        DB::connection('galy')->table('usuarios')->insert([
            'id' => 5,
            'nombre' => 'Cliente Vinculado',
            'email' => 'link-customer@gmail.com',
            'password' => 'not-used-for-google-login',
        ]);
        $googleUser = GoogleUser::fake([
            'id' => 'google-link-555',
            'email' => 'LINK-CUSTOMER@gmail.com',
            'user' => ['verified_email' => true],
        ]);
        $provider = Mockery::mock();
        $provider->shouldReceive('user')->once()->andReturn($googleUser);
        Socialite::shouldReceive('driver')->once()->with('google')->andReturn($provider);

        $this->actingAs($user)
            ->withSession(['google_link_user_id' => $user->id])
            ->get(route('auth.google.callback'))
            ->assertRedirect('/dashboard');

        $this->assertAuthenticatedAs($user);
        $this->assertSame('google-link-555', $user->fresh()->google_id);
        $this->assertNull(session('google_link_user_id'));
    }

    public function test_signed_in_customer_can_start_google_account_linking(): void
    {
        config()->set('services.google.client_id', 'google-client-id');
        config()->set('services.google.client_secret', 'google-client-secret');
        config()->set('services.google.redirect', 'http://localhost/auth/google/callback');

        $user = User::factory()->create(['email' => 'link-customer@gmail.com']);
        $provider = Mockery::mock();
        $provider->shouldReceive('scopes')
            ->once()
            ->with(['openid', 'email', 'profile'])
            ->andReturnSelf();
        $provider->shouldReceive('with')
            ->once()
            ->with(['prompt' => 'select_account'])
            ->andReturnSelf();
        $provider->shouldReceive('redirect')
            ->once()
            ->andReturn(redirect('https://accounts.google.com/o/oauth2/auth'));
        Socialite::shouldReceive('driver')->once()->with('google')->andReturn($provider);

        $this->actingAs($user)
            ->get(route('auth.google.link'))
            ->assertRedirect('https://accounts.google.com/o/oauth2/auth')
            ->assertSessionHas('google_link_user_id', $user->id);
    }

    public function test_google_callback_rejects_an_unverified_email(): void
    {
        config()->set('services.google.client_id', 'google-client-id');
        config()->set('services.google.client_secret', 'google-client-secret');
        config()->set('services.google.redirect', 'http://localhost/auth/google/callback');

        $googleUser = GoogleUser::fake([
            'email' => 'unverified@gmail.com',
            'user' => ['verified_email' => false],
        ]);
        $provider = Mockery::mock();
        $provider->shouldReceive('user')->once()->andReturn($googleUser);
        Socialite::shouldReceive('driver')->once()->with('google')->andReturn($provider);

        $this->get(route('auth.google.callback'))
            ->assertRedirect(route('web.login'))
            ->assertSessionHas('google_login_error', 'Google debe confirmar un correo válido antes de iniciar sesión.');
        $this->assertGuest();
    }

    public function test_google_callback_handles_provider_tls_failures_without_a_server_error(): void
    {
        config()->set('services.google.client_id', 'google-client-id');
        config()->set('services.google.client_secret', 'google-client-secret');
        config()->set('services.google.redirect', 'http://localhost/auth/google/callback');

        $provider = Mockery::mock();
        $provider->shouldReceive('user')
            ->once()
            ->andThrow(new ConnectException(
                'SSL certificate verification failed.',
                new GuzzleRequest('POST', 'https://www.googleapis.com/oauth2/v4/token')
            ));
        Socialite::shouldReceive('driver')->once()->with('google')->andReturn($provider);
        Log::shouldReceive('warning')
            ->once()
            ->with('Google OAuth provider request failed.', [
                'exception_class' => ConnectException::class,
            ]);

        $this->get(route('auth.google.callback'))
            ->assertRedirect(route('web.login'))
            ->assertSessionHas(
                'google_login_error',
                'No se pudo conectar de forma segura con Google. Revisa los certificados SSL del servidor e inténtalo nuevamente.'
            );
    }

    public function test_privacy_page_explains_checkout_data_handling(): void
    {
        $this->get(route('web.privacidad'))
            ->assertOk()
            ->assertSee('DNI')
            ->assertSee('cifrado')
            ->assertSee('Yape y Plin')
            ->assertSee('Google')
            ->assertSee('mientras tu cuenta esté activa')
            ->assertSee('obligaciones legales aplicables');
    }
}
