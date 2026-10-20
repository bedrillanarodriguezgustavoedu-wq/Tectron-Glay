<?php

namespace App\Models\Concerns;

trait UsesGalyConnection
{
    public function getConnectionName(): ?string
    {
        return app()->environment('testing') ? null : 'galy';
    }
}
