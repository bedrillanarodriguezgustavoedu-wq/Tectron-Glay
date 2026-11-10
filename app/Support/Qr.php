<?php

namespace App\Support;

use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;

class Qr
{
    /**
     * SVG en data URI: la vista lo mete en un <img> y así el enlace de la pasarela
     * nunca se convierte en HTML suelto dentro de la página.
     */
    public static function data_uri(string $contenido, int $tamano = 240): string
    {
        $escritor = new Writer(new ImageRenderer(new RendererStyle($tamano, 4), new SvgImageBackEnd));

        return 'data:image/svg+xml;base64,'.base64_encode($escritor->writeString($contenido));
    }
}
