<?php

namespace Tests\Feature;

use Illuminate\Http\UploadedFile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MobileCheckoutControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_mobile_payment_methods_only_return_the_app_payment_configuration(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('pagos/qr/app-yape.png', 'qr');

        DB::table('ajustes')->insert([
            'nombre' => 'GALY',
            'descripcion' => 'Tienda',
            'sucursal' => 'Principal',
            'direccion' => 'Dirección',
            'telefono' => '999999999',
            'logo' => 'logo.png',
            'imagen_login' => 'login.png',
            'email' => 'tienda@example.com',
            'divisa' => 'PEN',
            'yape_numero' => '987654321',
            'yape_titular' => 'Yape app',
            'yape_qr' => 'pagos/qr/app-yape.png',
            'web_yape_numero' => '222222222',
            'web_yape_titular' => 'Yape web',
            'web_yape_qr' => 'pagos/qr/web/yape.png',
            'plin_numero' => '912345670',
            'plin_titular' => 'Plin app',
            'plin_qr' => 'pagos/qr/app-plin.png',
            'app_bcp_cci' => '00119100293810239273',
            'app_bcp_cuenta' => '191209381023092',
            'app_bcp_titular' => 'BCP app',
            'web_plin_numero' => '444444444',
            'web_plin_titular' => 'Plin web',
            'web_plin_qr' => 'pagos/qr/web/plin.png',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->getJson(route('api.mobile.payment-methods'))
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('methods.0.number', '987654321')
            ->assertJsonPath('methods.0.holder', 'Yape app')
            ->assertJsonPath('methods.1.number', '912345670')
            ->assertJsonPath('methods.1.holder', 'Plin app')
            ->assertJsonPath('methods.2.method', 'BCP')
            ->assertJsonPath('methods.2.number', '00119100293810239273')
            ->assertJsonPath('methods.2.account', '191209381023092')
            ->assertJsonPath('methods.2.holder', 'BCP app')
            ->assertJsonPath('methods.0.qr', 'storage/pagos/qr/app-yape.png')
            ->assertJsonPath('methods.1.qr', null)
            ->assertDontSee('Yape web')
            ->assertDontSee('Plin web');
    }

    public function test_mobile_payment_methods_hide_placeholder_destinations(): void
    {
        DB::table('ajustes')->insert([
            'nombre' => 'GALY',
            'descripcion' => 'Tienda',
            'sucursal' => 'Principal',
            'direccion' => 'Dirección',
            'telefono' => '999999999',
            'logo' => 'logo.png',
            'imagen_login' => 'login.png',
            'email' => 'tienda@example.com',
            'divisa' => 'PEN',
            'yape_numero' => '',
            'yape_titular' => '',
            'plin_numero' => '123456789',
            'plin_titular' => 'Plin app',
            'app_bcp_cci' => '00000000000000000000',
            'app_bcp_cuenta' => '191-00000000-0-00',
            'app_bcp_titular' => 'CUENTA DE PRUEBA - NO TRANSFERIR',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->getJson(route('api.mobile.payment-methods'))
            ->assertOk()
            ->assertJsonPath('methods', []);
    }

    public function test_mobile_receipt_endpoint_rejects_a_google_token_for_another_client(): void
    {
        config()->set('services.google.client_id', 'galy-web-client-id');
        Http::fake([
            'https://oauth2.googleapis.com/tokeninfo*' => Http::response([
                'aud' => 'another-client-id',
                'email' => 'customer@gmail.com',
                'email_verified' => 'true',
                'exp' => time() + 3600,
                'iss' => 'https://accounts.google.com',
            ]),
        ]);

        $this->postJson(route('api.mobile.payment-receipts'), [
            'id_token' => 'opaque-token',
            'pedido_id' => 1,
            'metodo_pago' => 'YAPE',
            'comprobante' => UploadedFile::fake()->createWithContent(
                'receipt.png',
                base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+/AacAAAAASUVORK5CYII=')
            ),
        ])
            ->assertUnauthorized()
            ->assertJsonPath('message', 'La sesión de Google no es válida o ha vencido.');
    }

}
