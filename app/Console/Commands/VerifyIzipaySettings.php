<?php

namespace App\Console\Commands;

use App\Services\IzipayService;
use Illuminate\Console\Command;

class VerifyIzipaySettings extends Command
{
    protected $signature = 'izipay:verificar {--probar : Pide un formulario de prueba a Izipay para confirmar las credenciales}';

    protected $description = 'Revisa qué falta para que el pago con tarjeta (Izipay) esté disponible en la tienda.';

    public function handle(IzipayService $izipay): int
    {
        $filas = [
            ['IZIPAY_ENABLED', config('services.izipay.enabled') === true ? 'sí' : 'NO',
                'IZIPAY_ENABLED=true en .env'],
            ['IZIPAY_SHOP_ID', filled($izipay->shopId()) ? 'sí' : 'NO',
                'Código de comercio del Back Office de Izipay'],
            ['IZIPAY_PASSWORD', filled($izipay->password()) ? 'sí' : 'NO',
                'Clave API del botón de pagos'],
            ['IZIPAY_PUBLIC_KEY', filled($izipay->publicKey()) ? 'sí' : 'NO',
                'Clave pública RSA del Back Office'],
            ['IZIPAY_HMAC_KEY', filled($izipay->hmacKey()) ? 'sí' : 'NO',
                'Clave HMAC-SHA-256 para validar kr-answer'],
            ['Entorno', $izipay->esSandbox() ? 'sandbox (pruebas)' : 'producción',
                'IZIPAY_ENVIRONMENT=sandbox para probar sin cobrar de verdad'],
            ['URL pública de pagos', $izipay->urlBase(), $this->problemaDeUrl($izipay->urlBase())],
            ['Interruptor en Admin → Ajustes', \App\Models\Ajuste::first()?->web_izipay_activa ? 'activado' : 'desactivado',
                'Marca "Ofrecer pago con tarjeta (Izipay)" en Admin → Ajustes'],
        ];

        $this->table(['Qué se revisa', 'Estado', 'Cómo se arregla'], $filas);

        if ($izipay->sePuedeOfrecer()) {
            $this->info('Izipay SÍ puede ofrecerse en el checkout (el interruptor del Admin decide si se ve).');
        } else {
            $this->warn('Izipay NO se puede ofrecer todavía: arregla las filas marcadas con NO.');
        }

        if ($this->option('probar')) {
            if (! $izipay->habilitado()) {
                $this->error('Faltan credenciales para probar.');

                return self::FAILURE;
            }

            $resultado = $izipay->probarCredenciales();

            if (! $resultado['ok']) {
                $this->error('Izipay no aceptó las credenciales: '.$resultado['mensaje']);

                return self::FAILURE;
            }

            $this->info($resultado['mensaje']);
        }

        $this->line('URL de retorno (configúrala en el Back Office si la pide): '.$izipay->urlRetorno());
        $this->line('URL de IPN (Reglas de notificación → URL al final del pago): '.$izipay->urlIpn());

        return self::SUCCESS;
    }

    private function problemaDeUrl(string $url): string
    {
        if ($url === '' || $url === '/') {
            return 'Falta APP_URL o IZIPAY_URL_BASE';
        }

        if (str_starts_with($url, 'http://') && ! app()->environment('local', 'testing')) {
            return 'La URL debe ser HTTPS en producción';
        }

        return 'OK';
    }
}
