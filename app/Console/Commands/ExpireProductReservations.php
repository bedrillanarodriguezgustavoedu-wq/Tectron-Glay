<?php

namespace App\Console\Commands;

use App\Services\ReservaProductoService;
use Illuminate\Console\Command;

class ExpireProductReservations extends Command
{
    protected $signature = 'reservas:expirar';

    protected $description = 'Libera el inventario de reservas de productos vencidas.';

    public function handle(ReservaProductoService $reservations): int
    {
        $expired = $reservations->expireDueReservations();
        $this->info("Reservas vencidas liberadas: {$expired}");

        return self::SUCCESS;
    }
}
