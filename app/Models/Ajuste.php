<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Ajuste extends Model
{
    protected $table = 'ajustes';
    protected $fillable = [
        'nombre',
        'descripcion',
        'sucursal',
        'direccion',
        'telefono',
        'logo',
        'imagen_login',
        'email',
        'divisa',
        'pagina_web',
        'home_slider_velocidad',
        'home_slider_altura',
        'home_carrusel_velocidad',
        'home_max_categorias',
        'home_max_productos_categoria',
        'home_destacado_oferta_id',
        'home_mostrar_contador',
        
        // Colores de Apariencia
        'color_primario',
        'color_secundario',
        'color_acento',
        'color_botones',
        'color_enlaces',
        'color_textos_principales',
        'color_encabezado',
        'color_pie_pagina',
        'color_tarjetas',
        'color_badges',
        'color_etiquetas_ofertas',
        'color_botones_carrito',
        'color_botones_principales',

        // Colores y Personalización del Menú
        'menu_bg_color',
        'menu_text_color',
        'menu_hover_color',
        'menu_active_color',
        'menu_hover_bg_color',
        'menu_icon_color',
        'menu_active_border_color',
        'menu_opacity',

        // Configuración de Moneda
        'moneda_nombre',
        'moneda_simbolo',
        'moneda_decimales',
        'moneda_posicion',

        // Pagos manuales
        'yape_numero',
        'yape_titular',
        'yape_qr',
        'plin_numero',
        'plin_titular',
        'plin_qr',
        'web_yape_numero',
        'web_yape_titular',
        'web_yape_qr',
        'web_plin_numero',
        'web_plin_titular',
        'web_plin_qr',
        'web_bcp_cci',
        'web_bcp_cuenta',
        'web_bcp_titular',
        'app_bcp_cci',
        'app_bcp_cuenta',
        'app_bcp_titular',

        // Pago con tarjeta (Mercado Pago Checkout Pro)
        'web_mercadopago_activa',

        // Configuración de Reseñas
        'review_requiere_aprobacion',
        'review_permitir_imagenes',
        'review_max_imagenes',
        'review_longitud_maxima',
        'review_tiempo_edicion_dias',
        'review_permitir_eliminacion'
    ];

    /**
     * Format a price according to dynamic admin configurations.
     */
    public static function formatear_precio($monto)
    {
        $ajuste = self::first();
        if (!$ajuste) {
            return '$ ' . number_format($monto, 2);
        }

        $formateado = number_format((float)$monto, $ajuste->moneda_decimales);
        
        if ($ajuste->moneda_posicion === 'antes') {
            return $ajuste->moneda_simbolo . ' ' . $formateado;
        } else {
            return $formateado . ' ' . $ajuste->moneda_simbolo;
        }
    }

    /**
     * Motivo por el que un pago manual del checkout web no debe ofrecerse, o
     * `null` cuando el destino es de fiar. Un CCI o número escrito de prueba o
     * con un dígito de menos no recibe dinero: el cliente transfería igual y
     * perdía su pago, así que el método se oculta hasta corregirlo.
     */
    public static function motivoParaOcultarPagoWeb(?self $ajuste, string $metodo): ?string
    {
        if (! $ajuste) {
            return 'la tienda no tiene datos de pago configurados';
        }

        [$numero, $titular, $qr] = match ($metodo) {
            'yape' => [$ajuste->web_yape_numero, $ajuste->web_yape_titular, $ajuste->web_yape_qr],
            'plin' => [$ajuste->web_plin_numero, $ajuste->web_plin_titular, $ajuste->web_plin_qr],
            'bcp' => [$ajuste->web_bcp_cci, $ajuste->web_bcp_titular, null],
            default => [null, null, null],
        };

        return self::motivoDeDestinoPago($metodo, $numero, $titular, $qr);
    }

    /**
     * @return string|null `null` cuando el destino se puede mostrar al cliente.
     */
    public static function motivoDeDestinoPago(string $metodo, ?string $numero, ?string $titular, ?string $qr = null, bool $requiereQr = true): ?string
    {
        if (! filled($numero) || ! filled($titular)) {
            return $metodo === 'bcp'
                ? 'faltan la CCI o el titular de la cuenta'
                : 'faltan el número receptor o el titular';
        }

        if ($requiereQr && $metodo !== 'bcp' && ! filled($qr)) {
            return 'falta el código QR oficial';
        }

        $digitos = preg_replace('/\D/', '', (string) $numero) ?? '';
        $longitud = $metodo === 'bcp' ? 20 : 9;

        if (strlen($digitos) !== $longitud) {
            return $metodo === 'bcp'
                ? 'la CCI debe tener 20 dígitos (ahora tiene '.strlen($digitos).')'
                : 'el número receptor debe tener 9 dígitos (ahora tiene '.strlen($digitos).')';
        }

        if ($metodo !== 'bcp' && ! str_starts_with($digitos, '9')) {
            return 'un número de Yape o Plin empieza con 9; revisa si sobra el código de país';
        }

        if (count(array_unique(str_split($digitos))) === 1) {
            return 'el número parece de prueba (todos los dígitos iguales)';
        }

        if (in_array($digitos, ['0123456789', '1234567890', '123456789'], true)) {
            return 'el número parece de prueba (dígitos consecutivos)';
        }

        if (preg_match('/\b(prueba|demo|test|no transferir|ficticio|ejemplo)\b/iu', (string) $titular)) {
            return 'el titular está marcado como cuenta de prueba';
        }

        $palabras = array_values(array_filter(preg_split('/\s+/u', mb_strtolower(trim((string) $titular))) ?: []));

        if (count($palabras) > 1 && count(array_unique($palabras)) === 1) {
            return 'el titular no parece un nombre real (es la misma palabra repetida)';
        }

        return null;
    }

    /**
     * Yape y Plin pueden compartir el número del titular, pero no el código: el QR
     * de Yape abre Yape, y nadie termina pagando por Plin sin querer. El panel guarda
     * cada imagen con un nombre nuevo, así que pegar la misma foto en los dos métodos
     * produce dos rutas distintas con un mismo código. Se compara el contenido.
     *
     * @return array<string, string> canal => nombres de los métodos repetidos
     */
    public static function codigosQrRepetidos(?self $ajuste): array
    {
        if (! $ajuste) {
            return [];
        }

        $repetidos = [];

        foreach ([
            'web' => ['Yape' => $ajuste->web_yape_qr, 'Plin' => $ajuste->web_plin_qr],
            'app' => ['Yape' => $ajuste->yape_qr, 'Plin' => $ajuste->plin_qr],
        ] as $canal => $metodos) {
            $firmas = [];

            foreach ($metodos as $metodo => $ruta) {
                if (filled($ruta)) {
                    $firmas[$metodo] = self::firmaDelCodigoQr($ruta);
                }
            }

            if (count($firmas) === 2 && $firmas['Yape'] === $firmas['Plin']) {
                $repetidos[$canal] = implode(' y ', array_keys($firmas));
            }
        }

        return $repetidos;
    }

    /**
     * Sin archivo en el disco no hay contenido que comparar: queda la ruta, que es
     * lo único verificable y coincide solo si es literalmente la misma imagen.
     */
    private static function firmaDelCodigoQr(string $ruta): string
    {
        try {
            return md5((string) \Illuminate\Support\Facades\Storage::disk('public')->get($ruta));
        } catch (\Throwable) {
            return $ruta;
        }
    }

    /**
     * El checkout web muestra «Número de cuenta» tal cual al cliente, así que un
     * PAN guardado ahí aparecería como destino de pago y quedaría en la base de
     * datos. Las cuentas peruanas (191-…, o 2209… de 14 dígitos) no caen en este
     * patrón: se exige longitud de tarjeta y el prefijo de la marca.
     */
    public static function esNumeroDeTarjeta(?string $valor): bool
    {
        $digitos = preg_replace('/\D/', '', (string) $valor) ?? '';

        if (! in_array(strlen($digitos), [15, 16], true)) {
            return false;
        }

        if (! preg_match('/^(?:4|5[1-5]|2[2-7])\d+$/', $digitos)) {
            return false;
        }

        $longitudPar = strlen($digitos) % 2 === 0;
        $suma = 0;
        foreach (str_split($digitos) as $posicion => $digito) {
            $valor = (int) $digito;
            if (($posicion % 2 === 0) === $longitudPar) {
                $valor *= 2;
                if ($valor > 9) {
                    $valor -= 9;
                }
            }
            $suma += $valor;
        }

        return $suma % 10 === 0;
    }
}
