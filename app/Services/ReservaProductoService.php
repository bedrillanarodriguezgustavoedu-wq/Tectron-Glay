<?php

namespace App\Services;

use DateTimeInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ReservaProductoService
{
    public function reserve(
        int $userId,
        int $productId,
        int $quantity,
        ?DateTimeInterface $expiresAt = null,
    ): int
    {
        return DB::connection('galy')->transaction(function () use ($userId, $productId, $quantity): int {
            $this->expireDueReservations();
            $product = DB::connection('galy')->table('productos')->where('id', $productId)->lockForUpdate()->first();
            if (! $product) {
                throw ValidationException::withMessages(['producto_id' => 'El producto ya no está disponible.']);
            }
            if ($product->stock < $quantity) {
                throw ValidationException::withMessages([
                    'cantidad' => "Solo quedan {$product->stock} unidades disponibles.",
                ]);
            }

            DB::connection('galy')->table('productos')->where('id', $productId)->decrement('stock', $quantity);

            return (int) DB::connection('galy')->table('reservas_productos')->insertGetId([
                'usuario_id' => $userId,
                'producto_id' => $productId,
                'cantidad' => $quantity,
                'estado' => 'activa',
                'expira_at' => $expiresAt ?? now()->addHours(24),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        });
    }

    public function cancel(int $reservationId, ?int $userId = null): bool
    {
        return DB::connection('galy')->transaction(function () use ($reservationId, $userId): bool {
            $reservation = DB::connection('galy')->table('reservas_productos')
                ->where('id', $reservationId)
                ->when($userId !== null, fn ($query) => $query->where('usuario_id', $userId))
                ->lockForUpdate()
                ->first();
            if (! $reservation || $reservation->estado !== 'activa') {
                return false;
            }

            if (now()->greaterThanOrEqualTo($reservation->expira_at)) {
                $this->releaseReservation($reservation);
                return false;
            }

            DB::connection('galy')->table('productos')->where('id', $reservation->producto_id)
                ->increment('stock', $reservation->cantidad);
            DB::connection('galy')->table('reservas_productos')->where('id', $reservationId)->update([
                'estado' => 'cancelada',
                'updated_at' => now(),
            ]);

            return true;
        });
    }

    public function expireDueReservations(): int
    {
        $reservations = DB::connection('galy')->table('reservas_productos')
            ->where('estado', 'activa')
            ->where('expira_at', '<=', now())
            ->orderBy('id')
            ->lockForUpdate()
            ->get();

        foreach ($reservations as $reservation) {
            $this->releaseReservation($reservation);
        }

        return $reservations->count();
    }

    public function consumeForOrder(int $userId, int $productId, int $quantity, int $orderId): void
    {
        $connection = DB::connection('galy');
        $this->expireDueReservations();

        $reservations = $connection->table('reservas_productos')
            ->where('usuario_id', $userId)
            ->where('producto_id', $productId)
            ->where('estado', 'activa')
            ->where('expira_at', '>', now())
            ->orderBy('id')
            ->lockForUpdate()
            ->get();

        $remainingOrderQuantity = $quantity;
        foreach ($reservations as $reservation) {
            if ($remainingOrderQuantity <= 0) {
                break;
            }
            $consumedQuantity = min($remainingOrderQuantity, (int) $reservation->cantidad);
            if ($consumedQuantity === (int) $reservation->cantidad) {
                $connection->table('reservas_productos')->where('id', $reservation->id)->update([
                    'estado' => 'convertida',
                    'pedido_id' => $orderId,
                    'updated_at' => now(),
                ]);
            } else {
                $connection->table('reservas_productos')->where('id', $reservation->id)->update([
                    'cantidad' => (int) $reservation->cantidad - $consumedQuantity,
                    'updated_at' => now(),
                ]);
                $connection->table('reservas_productos')->insert([
                    'usuario_id' => $userId,
                    'producto_id' => $productId,
                    'cantidad' => $consumedQuantity,
                    'estado' => 'convertida',
                    'expira_at' => $reservation->expira_at,
                    'pedido_id' => $orderId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
            $remainingOrderQuantity -= $consumedQuantity;
        }

        if ($remainingOrderQuantity > 0) {
            $affected = $connection->table('productos')
                ->where('id', $productId)
                ->where('stock', '>=', $remainingOrderQuantity)
                ->decrement('stock', $remainingOrderQuantity);
            if ($affected !== 1) {
                throw ValidationException::withMessages([
                    'stock' => 'El inventario cambió y ya no alcanza para completar el pedido.',
                ]);
            }
        }
    }

    public function activeQuantityForProduct(int $userId, int $productId): int
    {
        return (int) DB::connection('galy')->table('reservas_productos')
            ->where('usuario_id', $userId)
            ->where('producto_id', $productId)
            ->where('estado', 'activa')
            ->where('expira_at', '>', now())
            ->sum('cantidad');
    }

    /**
     * Keeps an existing customer reservation alive while a short-lived secure
     * checkout is open. Without this, an older reservation could expire after
     * the payment page is opened but before the gateway confirms the payment.
     */
    public function extendActiveReservations(int $userId, int $productId, DateTimeInterface $expiresAt): void
    {
        DB::connection('galy')->transaction(function () use ($userId, $productId, $expiresAt): void {
            $this->expireDueReservations();

            DB::connection('galy')->table('reservas_productos')
                ->where('usuario_id', $userId)
                ->where('producto_id', $productId)
                ->where('estado', 'activa')
                ->where('expira_at', '<', $expiresAt)
                ->update([
                    'expira_at' => $expiresAt,
                    'updated_at' => now(),
                ]);
        });
    }

    public function listForUser(int $userId): Collection
    {
        $this->expireDueReservations();

        return DB::connection('galy')->table('reservas_productos as reservation')
            ->join('productos as product', 'product.id', '=', 'reservation.producto_id')
            ->where('reservation.usuario_id', $userId)
            ->select([
                'reservation.*',
                'product.nombre as producto_nombre',
                'product.imagen_url',
                'product.precio_normal',
                'product.precio_oferta',
            ])
            ->orderByDesc('reservation.id')
            ->get();
    }

    private function releaseReservation(object $reservation): void
    {
        $connection = DB::connection('galy');
        $updated = $connection->table('reservas_productos')
            ->where('id', $reservation->id)
            ->where('estado', 'activa')
            ->update(['estado' => 'expirada', 'updated_at' => now()]);
        if ($updated === 1) {
            $connection->table('productos')->where('id', $reservation->producto_id)
                ->increment('stock', $reservation->cantidad);
        }
    }
}
