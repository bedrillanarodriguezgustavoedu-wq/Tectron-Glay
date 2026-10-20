<?php

namespace App\Console\Commands;

use App\Services\MercadoPagoService;
use Illuminate\Console\Command;

class ReconcileMercadoPagoPayments extends Command
{
    protected $signature = 'mercadopago:reconciliar {--limite=50 : Cuántos intentos revisar en cada pasada.}';

    protected $description = 'Pregunta a Mercado Pago por los pagos con tarjeta que siguen pendientes, por si se perdió el aviso.';

    public function handle(MercadoPagoService $mercadoPago): int
    {
        if (! $mercadoPago->habilitado()) {
            $this->warn('Mercado Pago no está configurado: no hay nada que conciliar.');

            return self::SUCCESS;
        }

        $resultado = $mercadoPago->reconciliarIntentosAbiertos((int) $this->option('limite'));

        $this->info("Intentos revisados: {$resultado['revisados']}");
        $this->info("Pagos confirmados: {$resultado['confirmados']}");

        return self::SUCCESS;
    }
}
