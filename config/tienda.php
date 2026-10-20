<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Reglas de envío de la tienda
    |--------------------------------------------------------------------------
    | Un solo lugar para los montos que antes estaban repetidos en la vista del
    | carrito, en su JavaScript y en el alta del pedido. Se pueden cambiar por
    | .env sin tocar código.
    */

    'envio' => [
        'umbral_gratis' => (float) env('ENVIO_UMBRAL_GRATIS', 300),
        'costo_estandar' => (float) env('ENVIO_COSTO_ESTANDAR', 20),
        'costo_express' => (float) env('ENVIO_COSTO_EXPRESS', 50),
    ],

];
