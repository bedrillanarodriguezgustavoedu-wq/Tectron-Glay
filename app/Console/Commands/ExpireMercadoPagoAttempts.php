<?php

namespace App\Console\Commands;

use App\Services\MercadoPagoService;
use Illuminate\Console\Command;

class ExpireMercadoPagoAttempts extends Command
{
    protected $signature = 'mercadopago:expirar';

    protected $description = 'Marca como expirados los intentos de pago con tarjeta que vencieron sin pagarse.';

    public function handle(MercadoPagoService $mercadoPago): int
    {
        $expirados = $mercadoPago->caducarVencidos();
        $this->info("Intentos de pago con tarjeta expirados: {$expirados}");

        return self::SUCCESS;
    }
}
