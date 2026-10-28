<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class FijarTunelPublico extends Command
{
    protected $signature = 'tunel:fijar
        {--log= : Archivo donde cloudflared está escribiendo su salida}
        {--host= : Hostname del túnel, si ya lo sabes}';

    protected $description = 'Punta el retorno de Mercado Pago al hostname público que está vivo ahora mismo.';

    private const ESPERA_MAXIMA_SEGUNDOS = 45;

    public function handle(): int
    {
        $host = (string) $this->option('host');

        if ($host === '' && ($this->option('log') !== null)) {
            $host = $this->esperarHostnameDelTunel((string) $this->option('log'));
        }

        if ($host === '') {
            $this->error('No encontré la dirección del túnel. ¿cloudflared está corriendo y su log se va escribiendo?');

            return self::FAILURE;
        }

        $url = $this->normalizar($host);

        if ($url === null) {
            $this->error("«{$host}» no es un nombre de host que se pueda escribir en el .env.");

            return self::FAILURE;
        }

        $antes = $this->leerUrlConfigurada();

        if ($antes === $url) {
            $this->info("El retorno de pagos ya apuntaba a {$url}.");

            return self::SUCCESS;
        }

        $this->escribirUrl($url);
        Artisan::call('config:clear');

        $this->info("Retorno de pagos: {$url}");

        if ($antes !== null) {
            $this->line("  antes: {$antes}");
        }

        $this->line('Si «php artisan serve» estaba abierto antes de este cambio, reinícialo:');
        $this->line('ese proceso sigue usando la dirección vieja que cargó al arrancar.');

        return self::SUCCESS;
    }

    /**
     * cloudflared tarda unos segundos en que Cloudflare le asigne el nombre, así
     * que se lee el log hasta que aparezca en lugar de adivinar un tiempo de espera.
     */
    private function esperarHostnameDelTunel(string $archivo): string
    {
        for ($intento = 0; $intento < self::ESPERA_MAXIMA_SEGUNDOS; $intento++) {
            if (is_readable($archivo)
                && preg_match('#https://[a-z0-9][a-z0-9-]*\.trycloudflare\.com#i', (string) file_get_contents($archivo), $coincidencia) === 1) {
                return $coincidencia[0];
            }

            sleep(1);
        }

        return '';
    }

    private function normalizar(string $host): ?string
    {
        $host = trim($host);
        $host = preg_replace('#^https?://#i', '', $host);
        $host = rtrim((string) $host, '/');

        if (preg_match('/^[a-z0-9][a-z0-9.-]*[a-z0-9]$/i', $host) !== 1) {
            return null;
        }

        return 'https://'.$host;
    }

    /** @return string|null */
    private function leerUrlConfigurada(): ?string
    {
        foreach ($this->lineasDelEnv() as $linea) {
            if (str_starts_with($linea, 'MERCADOPAGO_URL_BASE=')) {
                return trim(substr($linea, strlen('MERCADOPAGO_URL_BASE=')));
            }
        }

        return null;
    }

    private function escribirUrl(string $url): void
    {
        $lineas = $this->lineasDelEnv();
        $escrito = false;

        foreach ($lineas as $indice => $linea) {
            if (str_starts_with($linea, 'MERCADOPAGO_URL_BASE=')) {
                $lineas[$indice] = 'MERCADOPAGO_URL_BASE='.$url."\n";
                $escrito = true;
            }
        }

        if (! $escrito) {
            $lineas[] = "MERCADOPAGO_URL_BASE={$url}\n";
        }

        file_put_contents($this->archivoDelEnv(), implode('', $lineas));
    }

    /** @return array<int, string> */
    private function lineasDelEnv(): array
    {
        return file($this->archivoDelEnv()) ?: [];
    }

    private function archivoDelEnv(): string
    {
        return base_path('.env');
    }
}
