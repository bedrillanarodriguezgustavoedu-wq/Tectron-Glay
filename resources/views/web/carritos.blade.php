@extends('layouts.web')
@section("meta_robots", "noindex, follow")
@section('content')
    @php
        $yapeDisponible = \App\Models\Ajuste::motivoParaOcultarPagoWeb($ajuste, 'yape') === null;
        $plinDisponible = \App\Models\Ajuste::motivoParaOcultarPagoWeb($ajuste, 'plin') === null;
        // Decisión del dueño (2026-10-21): la transferencia BCP ya no se ofrece en
        // el carrito. Los datos siguen en Ajustes y el candado
        // `motivoParaOcultarPagoWeb` queda intacto, así que volver a ofrecerla es
        // cambiar este `false`; los pedidos ya pagados por BCP se siguen viendo en
        // el panel.
        $bcpDisponible = false;
        $mercadoPago = app(\App\Services\MercadoPagoService::class);
        // Nadie puede forzar aquí lo que Mercado Pago no aceptó: un `true` en
        // esta línea enseña la tarjeta, el cliente paga mal y el pedido queda
        // pendiente. Solo la credencial verificada la enciende.
        $tarjetaDisponible = (bool) ($ajuste?->web_mercadopago_activa ?? false)
            && $mercadoPago->sePuedeOfrecer();
        // La tarjeta se muestra gris con «Próximamente» en cualquier estado en el
        // que la tienda la quiere y la credencial existe pero todavía no vale:
        // retirada por un rechazo o nunca probada. Sin token no se anuncia nada: no
        // hay ninguna tarjeta que "próximamente" funcione.
        $tarjetaProximamente = (bool) ($ajuste?->web_mercadopago_activa ?? false)
            && $mercadoPago->habilitado() && ! $tarjetaDisponible;
        // Con Checkout Bricks la tarjeta se escribe dentro de la ventana de pago
        // del carrito; sin ella el cliente sale hacia Mercado Pago. El modal tiene
        // que prometer el camino que el cliente va a recorrer de verdad.
        $tarjetaEnTienda = $tarjetaDisponible && $mercadoPago->brickListo();
    @endphp
    <style>
        .checkout-stepper .checkout-step {
            position: relative;
            color: #7b8492;
            z-index: 0;
        }

        .checkout-stepper .checkout-step:not(:last-child)::after {
            content: "";
            position: absolute;
            top: 19px;
            left: calc(50% + 25px);
            width: calc(100% - 50px);
            border-top: 2px dashed #ced4da;
            z-index: -1;
        }

        .checkout-stepper .checkout-step-number {
            width: 40px;
            height: 40px;
            color: #6c757d;
            background: #e9ecef;
            font-weight: 700;
            transition: background-color .2s ease, color .2s ease;
        }

        .checkout-stepper .checkout-step-label {
            display: block;
            font-weight: 600;
        }

        .checkout-step-trigger {
            width: 100%;
            padding: 0;
            border: 0;
            background: transparent;
            color: inherit;
            font: inherit;
            cursor: pointer;
        }

        .checkout-step-trigger:focus-visible {
            outline: 3px solid rgba(13, 110, 253, .35);
            outline-offset: 4px;
            border-radius: .5rem;
        }

        /* El paso recorrido se pinta de verde y cambia el número por una palomita.
           El número sigue en el DOM, a tamaño cero, para que un lector de pantalla
           todavía pueda decir «paso 1» y no solo «paso, completado». */
        .checkout-stepper .checkout-step.is-complete {
            color: #198754;
        }

        .checkout-stepper .checkout-step.is-complete .checkout-step-number {
            color: #fff;
            background: #198754;
            font-size: 0;
        }

        .checkout-stepper .checkout-step.is-complete .checkout-step-number::after {
            content: "\2713";
            font-size: 1.15rem;
            line-height: 1;
        }

        /* El paso en curso es el único que se ve en rojo fuerte: es donde está el cliente. */
        .checkout-stepper .checkout-step.is-active {
            color: #dc3545;
        }

        .checkout-stepper .checkout-step.is-active .checkout-step-number {
            color: #fff;
            background: #dc3545;
        }

        .checkout-stepper .checkout-step.is-complete::after {
            border-top-color: #198754;
        }

        .checkout-stepper .checkout-step:not(.is-active):not(.is-complete) .checkout-step-number {
            color: #adb5bd;
            background: #f1f3f5;
        }

        /* Un paso verde se puede volver a tocar para corregir datos; el actual no,
           porque ya lo estás viendo. */
        .checkout-stepper .checkout-step.is-complete .checkout-step-trigger:hover .checkout-step-number {
            background: #157347;
        }

        .checkout-stepper .checkout-step.is-complete .checkout-step-trigger:hover .checkout-step-label {
            text-decoration: underline;
        }

        .checkout-stepper .checkout-step.is-active .checkout-step-trigger {
            cursor: default;
        }

        /* El tema fuerza `color: var(--color-primary) !important` sobre cualquier
           .btn-outline-primary (layouts/web.blade.php:128), así que la opción
           seleccionada quedaba con letra azul sobre fondo azul: el cliente veía un
           bloque vacío y no sabía qué modalidad había elegido. */
        .btn-check:checked + .btn-outline-primary {
            background-color: var(--color-primary, #0d6efd) !important;
            border-color: var(--color-primary, #0d6efd) !important;
            color: #fff !important;
        }

        .punto-recojo-card {
            background: var(--bs-primary-bg-subtle, #eef6ff);
            border: 1px solid var(--color-primary, #0d6efd);
            border-radius: .75rem;
        }

        .punto-recojo-opcion {
            background: #fff;
            border: 1px solid var(--bs-border-color, #dee2e6);
            border-radius: .75rem;
            padding: .75rem;
            cursor: pointer;
        }

        .btn-check:checked + .punto-recojo-opcion {
            background: var(--bs-primary-bg-subtle, #eef6ff);
            border-color: var(--color-primary, #0d6efd);
        }

        .btn-check:focus-visible + .punto-recojo-opcion {
            outline: 2px solid var(--color-primary, #0d6efd);
            outline-offset: 2px;
        }

        @media (min-width: 992px) {
            .cart-summary {
                position: sticky;
                top: 1.5rem;
            }
        }

        .checkout-step-panel[hidden] {
            display: none !important;
        }

        /* El paso 3 no repite la lista: un disparador muestra el método elegido
           y abre la ventana donde se cambia. */
        .checkout-method-picker {
            display: grid;
            grid-template-columns: auto minmax(0, 1fr) auto;
            gap: .85rem;
            align-items: center;
            width: 100%;
            padding: .9rem 1rem;
            text-align: start;
            background: #fff;
            border: 1.5px dashed #b9cdf7;
            border-radius: .9rem;
            color: inherit;
            transition: border-color .15s ease, background .15s ease;
        }

        .checkout-method-picker:hover,
        .checkout-method-picker:focus-visible {
            border-color: #0d6efd;
            background: #f8fbff;
        }

        .checkout-method-picker.is-selected {
            border-style: solid;
            border-color: #0d6efd;
            box-shadow: inset .3rem 0 0 #0d6efd;
        }

        .checkout-method-picker__icon {
            display: grid;
            place-items: center;
            width: 44px;
            height: 44px;
            border-radius: .85rem;
            background: #eef3fb;
            color: #0d6efd;
            font-size: 1.3rem;
        }

        .checkout-method-picker__icon img {
            width: 30px;
            height: 30px;
            object-fit: contain;
        }

        .checkout-method-picker__title {
            display: block;
            font-weight: 700;
            line-height: 1.25;
        }

        .checkout-method-picker__hint {
            display: block;
            font-size: .75rem;
            line-height: 1.3;
            color: #6c757d;
        }

        .checkout-method-picker__action {
            font-size: .8rem;
            font-weight: 700;
            color: #0d6efd;
            white-space: nowrap;
        }

        /* ------------------------------------------------------------------
           Ventana de pago: hoja inferior en móvil y tarjeta centrada en
           escritorio. Es la pantalla donde el cliente decide si confía, así
           que el monto manda y los métodos se leen como opciones grandes.
           ------------------------------------------------------------------ */
        .checkout-payment-modal {
            border: 0;
            border-radius: 1.5rem;
            background: #fff;
            overflow: hidden;
            box-shadow: 0 2rem 5rem rgba(2, 6, 23, .35);
        }

        .checkout-payment-modal .modal-header {
            position: relative;
            display: flex;
            align-items: center;
            gap: .7rem;
            padding: 1.1rem 1.15rem .85rem;
            border-bottom: 1px solid #eef2f8;
            background: linear-gradient(180deg, #fff 0%, #f8fafc 100%);
        }

        /* Asa de la hoja inferior: en móvil se reconoce como panel deslizable. */
        .checkout-payment-modal__handle {
            position: absolute;
            top: .5rem;
            left: 50%;
            transform: translateX(-50%);
            width: 44px;
            height: 4px;
            border-radius: 99px;
            background: #dbe3ee;
        }

        .checkout-payment-modal__brand {
            display: grid;
            place-items: center;
            flex: 0 0 auto;
            width: 40px;
            height: 40px;
            color: var(--color-primary, #0d6efd);
            background: color-mix(in srgb, var(--color-primary, #0d6efd) 10%, #fff);
            border-radius: 13px;
            font-size: 1.15rem;
        }

        .checkout-payment-modal__head-text {
            display: flex;
            flex-direction: column;
            min-width: 0;
            line-height: 1.2;
        }

        .checkout-payment-modal__head-title {
            font-size: .98rem;
            font-weight: 800;
            letter-spacing: -.01em;
            color: #0b1220;
        }

        .checkout-payment-modal__head-sub {
            display: flex;
            align-items: center;
            gap: .3rem;
            margin-top: .15rem;
            font-size: .72rem;
            color: #64748b;
        }

        .checkout-payment-modal__head-sub i {
            color: #16a34a;
            font-size: .78rem;
        }

        .checkout-payment-modal__head-actions {
            display: flex;
            align-items: center;
            gap: .45rem;
            margin-left: auto;
        }

        .checkout-payment-modal .modal-body {
            padding: 1rem 1.15rem 1.1rem;
        }

        /* Bootstrap pinta .modal-body con display:block y esa regla gana sobre
           el atributo hidden: sin esto los pasos aparecerían apilados. */
        .checkout-payment-modal [data-modal-paso][hidden] {
            display: none !important;
        }

        .checkout-payment-modal__dialog {
            max-width: 27rem;
        }

        .checkout-payment-modal__dialog--pago {
            max-width: 34rem;
        }

        .checkout-payment-modal .modal-footer {
            display: block;
            padding: .85rem 1.15rem calc(1rem + env(safe-area-inset-bottom, 0px));
            border-top: 1px solid #eef2f8;
            background: linear-gradient(180deg, #fff 0%, #f8fafc 100%);
        }

        .checkout-payment-modal .modal-footer .btn {
            min-height: 50px;
            border-radius: .9rem !important;
            font-size: .95rem;
        }

        .checkout-payment-modal__titulo {
            font-size: 1.06rem;
            font-weight: 800;
            letter-spacing: -.01em;
            margin-bottom: .2rem;
            color: #0b1220;
        }

        .checkout-payment-modal__guia {
            font-size: .82rem;
            color: #64748b;
            margin-bottom: .9rem;
        }

        /* Marca en qué parte del checkout está el cliente: la ventana tiene dos
           pasos y sin esto el «Volver» parecía una pantalla nueva. */
        .checkout-payment-modal__paso {
            display: inline-flex;
            align-items: center;
            margin-bottom: .3rem;
            padding: .16rem .5rem;
            border: 1px solid #dbe7ff;
            border-radius: 999px;
            background: #f1f6ff;
            color: #1d4ed8;
            font-size: .6rem;
            font-weight: 800;
            letter-spacing: .08em;
            text-transform: uppercase;
        }

        /* Monto destacado: fondo oscuro, cifra grande y el resumen de la compra
           dentro de la misma tarjeta para no dejar dudas de qué se está pagando. */
        .checkout-payment-modal__monto {
            position: relative;
            display: flex;
            flex-direction: column;
            gap: .05rem;
            margin-bottom: 1rem;
            padding: .95rem 1.1rem 1rem;
            color: #fff;
            border-radius: 1rem;
            overflow: hidden;
            background:
                radial-gradient(130% 150% at 100% 0%, #1e3a8a 0%, rgba(30, 58, 138, 0) 55%),
                linear-gradient(135deg, #0f172a 0%, #0b1220 100%);
            box-shadow: 0 14px 30px -18px rgba(11, 18, 32, .9);
        }

        .checkout-payment-modal__monto::after {
            content: "";
            position: absolute;
            right: -40px;
            bottom: -70px;
            width: 170px;
            height: 170px;
            border-radius: 50%;
            background: rgba(255, 255, 255, .055);
            pointer-events: none;
        }

        .checkout-payment-modal__monto-label {
            position: relative;
            z-index: 1;
            font-size: .68rem;
            font-weight: 700;
            letter-spacing: .09em;
            text-transform: uppercase;
            color: rgba(255, 255, 255, .62);
        }

        .checkout-payment-modal__monto strong {
            position: relative;
            z-index: 1;
            font-size: 1.65rem;
            font-weight: 800;
            letter-spacing: -.02em;
            line-height: 1.15;
        }

        .checkout-payment-modal__monto-resumen {
            position: relative;
            z-index: 1;
            margin-top: .3rem;
            font-size: .74rem;
            line-height: 1.4;
            color: rgba(255, 255, 255, .68);
        }

        /* Dos certezas dentro de la tarjeta del monto: qué se paga y que el
           importe ya está en soles, sin cargos que aparezcan al final. */
        .checkout-payment-modal__monto-tags {
            position: relative;
            z-index: 1;
            display: flex;
            flex-wrap: wrap;
            gap: .3rem;
            margin-top: .55rem;
        }

        .checkout-payment-modal__monto-tags span {
            display: inline-flex;
            align-items: center;
            gap: .25rem;
            padding: .16rem .45rem;
            border: 1px solid rgba(255, 255, 255, .16);
            border-radius: 999px;
            background: rgba(255, 255, 255, .06);
            color: rgba(255, 255, 255, .8);
            font-size: .62rem;
            font-weight: 600;
        }

        .checkout-payment-modal__monto-tags i {
            color: #86efac;
            font-size: .72rem;
        }

        /* Rótulo corto sobre la lista de métodos, como separador visual. */
        .checkout-payment-modal__seccion {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: .5rem;
            margin: .15rem 0 .55rem;
            font-size: .68rem;
            font-weight: 800;
            letter-spacing: .09em;
            text-transform: uppercase;
            color: #94a3b8;
        }

        .checkout-payment-modal__seccion span:last-child {
            font-weight: 600;
            letter-spacing: 0;
            text-transform: none;
            color: #b0bccd;
        }

        .checkout-payment-modal__nota {
            font-size: .78rem;
            line-height: 1.45;
            color: #64748b;
        }

        /* Ayuda (?) de la referencia. Es un botón de verdad: abre el panel que
           explica cuánto tarda cada medio de pago, que es lo que el cliente
           pregunta por WhatsApp cuando el pago manual todavía no se ve. */
        .checkout-payment-modal__ayuda {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 1.7rem;
            height: 1.7rem;
            padding: 0;
            font-size: .85rem;
            font-weight: 700;
            line-height: 1;
            color: #6c757d;
            background: #f1f5f9;
            border: 1px solid #e4eaf2;
            border-radius: 50%;
        }

        .checkout-payment-modal__ayuda:hover {
            color: #1f2937;
            background: #e9eef5;
        }

        .checkout-payment-modal__ayuda-lista {
            display: grid;
            gap: .55rem;
            margin: 0 0 .75rem;
            padding-left: 1.1rem;
            font-size: .85rem;
            color: #374151;
        }

        /* Las dos listas y la línea de confianza se muestran con display propio,
           y eso gana sobre el atributo hidden: hay que ocultarlos a mano o el
           paso 2 quedaría con los pasos vacíos pegados debajo del botón. */
        .checkout-payment-modal__ayuda-lista[hidden],
        .checkout-payment-modal__confianza[hidden],
        .checkout-payment-modal__recordatorio[hidden],
        .checkout-payment-modal__privacidad[hidden],
        .checkout-payment-modal__franja[hidden] {
            display: none !important;
        }

        /* El aviso del banco va por encima del botón: es lo único que el cliente
           puede resolver antes de que le rechacen la tarjeta. */
        .checkout-payment-modal__recordatorio {
            display: flex;
            align-items: flex-start;
            gap: .45rem;
            margin: .65rem 0 0;
            padding: .5rem .65rem;
            font-size: .78rem;
            font-weight: 600;
            line-height: 1.35;
            color: #0d5bd6;
            background: #f4f8ff;
            border: 1px solid #dbe7ff;
            border-radius: .7rem;
        }

        /* La franja de marcas bajo el botón, como en la pantalla de Mercado Pago:
           cierra la duda de «esta tarjeta sí vale aquí» justo al confirmar. */
        .checkout-payment-modal__franja {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: center;
            gap: .35rem;
            width: 100%;
            margin-top: .7rem;
        }

        /* En la franja final los logos van sueltos, como en la referencia;
           la casilla blanca es solo para la lista de métodos. Las chapas de
           texto (AMEX, DINERS) conservan su fondo porque ese color es el
           logotipo. */
        .checkout-payment-modal__franja .checkout-method-card__marca {
            background: transparent;
            border-color: transparent;
        }

        /* Debajo del botón verde, donde el cliente duda: dice quién cobra, que
           es la pregunta que frena un pago en la última pantalla. */
        .checkout-payment-modal__confianza {
            display: flex;
            align-items: flex-start;
            justify-content: center;
            gap: .35rem;
            width: 100%;
            margin: .45rem 0 0;
            font-size: .71rem;
            line-height: 1.4;
            color: #64748b;
            text-align: center;
        }

        .checkout-payment-modal__confianza i {
            flex: 0 0 auto;
            margin-top: .1rem;
            color: #15803d;
            font-size: .85rem;
        }

        /* Texto legal de la referencia, bajo el botón: dice qué se hace con
           los datos personales enlazando la política real de la tienda. */
        .checkout-payment-modal__privacidad {
            display: flex;
            align-items: flex-start;
            justify-content: center;
            gap: .3rem;
            width: 100%;
            margin: .45rem 0 0;
            color: #64748b;
            font-size: .69rem;
            line-height: 1.4;
            text-align: center;
        }

        .checkout-payment-modal__privacidad .bi {
            flex: 0 0 auto;
            margin-top: .05rem;
            color: #15803d;
            font-size: .8rem;
        }

        .checkout-payment-modal__privacidad a {
            color: #15803d;
            font-weight: 700;
            text-decoration: underline;
            text-underline-offset: 2px;
        }

        /* Sello PCI al final de la franja: cierra la idea de que la tarjeta
           se procesa en un entorno certificado, no en la tienda. */
        .checkout-payment-modal__pci {
            display: inline-flex;
            align-items: center;
            gap: .22rem;
            padding: .08rem .4rem .08rem .34rem;
            border: 1px solid #dbe7df;
            border-radius: .35rem;
            background: #f8fafc;
            color: #334155;
            font-size: .52rem;
            font-weight: 800;
            letter-spacing: .04em;
        }

        .checkout-payment-modal__pci .bi {
            color: #15803d;
            font-size: .72rem;
        }

        /* Franja de marcas: solo las que Mercado Pago tiene aprobadas en esta
           cuenta, en una fila bajo el nombre del método. */
        .checkout-method-card__marcas {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: .3rem;
            margin-top: .45rem;
        }

        /* Los logos vienen de tamaños distintos: encerrados en la misma casilla
           se leen como una franja pareja y no como cuatro imágenes sueltas. */
        .checkout-method-card__marca {
            display: inline-block;
            height: 1.4rem;
            width: 2.1rem;
            padding: .1rem .18rem;
            object-fit: contain;
            background: #fff;
            border: 1px solid #e5e9f2;
            border-radius: .3rem;
        }

        /* Las marcas sin archivo propio se escriben como chapita del color de
           la bandera: el cliente reconoce el nombre, no necesita el logotipo. */
        .checkout-method-card__marca--texto {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            height: 1.4rem;
            min-width: 2.1rem;
            padding: 0 .4rem;
            font-size: .62rem;
            font-weight: 700;
            letter-spacing: .02em;
            color: #fff;
            border-radius: .3rem;
        }

        /* Ningún método configurado: la lista se ve vacía y sin esto el cliente
           cree que la pantalla falló. */
        .checkout-payment-modal__vacio {
            display: grid;
            justify-items: center;
            gap: .5rem;
            padding: 1.25rem 1rem;
            text-align: center;
            font-size: .85rem;
            color: #6c757d;
            border: 1px dashed #dbe3ee;
            border-radius: .9rem;
            background: #f8fafc;
        }

        .checkout-payment-modal__vacio .bi {
            font-size: 1.5rem;
            color: #9aa4b2;
        }

        /* Sellos de confianza bajo los métodos: responden las tres dudas que
           frenan el último toque (¿es seguro?, ¿guardan mi tarjeta?, ¿y si
           falla?). En móvil se quedan solo los títulos para no empujar el
           botón fuera de la pantalla. */
        .checkout-payment-modal__sellos {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: .4rem;
            margin: .85rem 0 0;
            padding: 0;
            list-style: none;
        }

        .checkout-payment-modal__sellos li {
            display: flex;
            align-items: flex-start;
            gap: .4rem;
            padding: .55rem .5rem;
            border: 1px solid #edf1f7;
            border-radius: .7rem;
            background: #fbfcff;
        }

        .checkout-payment-modal__sellos i {
            flex: 0 0 auto;
            margin-top: .05rem;
            color: #15803d;
            font-size: .85rem;
        }

        .checkout-payment-modal__sellos strong {
            display: block;
            color: #0b1220;
            font-size: .68rem;
            line-height: 1.2;
        }

        .checkout-payment-modal__sellos small {
            display: block;
            margin-top: .1rem;
            color: #64748b;
            font-size: .6rem;
            line-height: 1.25;
        }

        /* El cliente que duda no cierra el checkout: escribe. El enlace lleva
           directo a WhatsApp con el número configurado en la tienda. */
        .checkout-payment-modal__soporte {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: .4rem;
            margin-top: .7rem;
            padding: .45rem .6rem;
            border-radius: .6rem;
            background: #f0fdf4;
            color: #166534;
            font-size: .72rem;
            text-align: center;
            text-decoration: none;
            transition: background-color .15s ease;
        }

        .checkout-payment-modal__soporte:hover {
            background: #dcfce7;
            color: #14532d;
        }

        .checkout-payment-modal__soporte i {
            color: #16a34a;
            font-size: .9rem;
        }

        .checkout-payment-modal__soporte strong { font-weight: 800; }

        /* Paso 2: el QR al costado, como en la referencia, para escanearlo sin
           salir del checkout. En pantallas angostas cae debajo del texto.
           Ojo: solo cuando el QR es hijo DIRECTO de este contenedor; en la
           tarjeta nueva vive dentro de .checkout-paycard__head y esta regla,
           como descendente, partiría la tarjeta en dos columnas y aplastaría
           el texto a una palabra por línea. */
        .checkout-payment-modal__detalle {
            display: grid;
            gap: 1rem;
        }

        @media (min-width: 576px) {
            .checkout-payment-modal__detalle:has(> .checkout-payment-modal__qr:not([hidden])) {
                grid-template-columns: minmax(0, 1fr) auto;
                align-items: start;
            }
        }

        .checkout-payment-modal__qr {
            margin: 0;
            padding: .75rem;
            text-align: center;
            border: 1px solid #e4eaf2;
            border-radius: .9rem;
            background: #fff;
        }

        .checkout-payment-modal__qr img {
            width: 100%;
            max-width: 170px;
            height: auto;
            aspect-ratio: 1;
            object-fit: contain;
        }

        .checkout-payment-modal__qr figcaption {
            margin-top: .4rem;
            font-size: .7rem;
            line-height: 1.3;
            color: #6c757d;
        }

        .checkout-payment-modal__qr[hidden] {
            display: none !important;
        }

        /* Paso 2 con la tarjeta blanca de la referencia: título verde, QR a
           la derecha, divisor, recordatorio del banco como titular centrado
           y la ilustración de los campos de la tarjeta. */
        .checkout-paycard {
            padding: 1rem .95rem .95rem;
            border: 1px solid #e6ebf3;
            border-radius: 1.1rem;
            background: #fff;
            box-shadow: 0 .5rem 1.4rem -8px rgba(15, 40, 30, .12);
        }

        /* Rejilla en vez de flex: la columna de texto siempre recibe el ancho
           que sobra y el QR mide fijo. Con flex, el QR se ajustaba al tamaño
           interno de su imagen y aplastaba el texto a una palabra por línea
           (se veía en Yape/Plin). */
        .checkout-paycard__head {
            display: grid;
            grid-template-columns: minmax(0, 1fr) auto;
            align-items: start;
            gap: .8rem;
        }

        .checkout-paycard__head-texto {
            min-width: 0;
        }

        .checkout-paycard__titulo {
            margin-bottom: .25rem;
            color: #15803d;
            font-size: 1.15rem;
            font-weight: 800;
            letter-spacing: .01em;
            text-transform: uppercase;
        }

        .checkout-paycard__guia {
            margin-bottom: 0;
        }

        /* El QR de la cabecera va suelto y con ancho fijo, igual que en la
           pantalla de referencia; el img al 100% ya no depende del tamaño
           interno de la imagen. */
        .checkout-paycard__qr {
            width: 120px;
            margin: 0;
            padding: .3rem;
            border: 0;
            background: transparent;
        }

        .checkout-paycard__qr img {
            display: block;
            width: 100%;
            max-width: none;
        }

        .checkout-paycard__divider {
            display: flex;
            align-items: center;
            gap: .6rem;
            margin: .8rem 0 .2rem;
            color: #94a3b8;
            font-size: .72rem;
        }

        .checkout-paycard__divider::before,
        .checkout-paycard__divider::after {
            content: '';
            flex: 1;
            border-top: 1px solid #e6ebf3;
        }

        .checkout-paycard__divider span {
            white-space: nowrap;
        }

        /* El recordatorio del banco se lee como titular, no como aviso en
           azul: es lo único que el cliente puede resolver antes de que le
           rechacen la tarjeta. */
        .checkout-paycard__recordatorio {
            display: block;
            margin: .55rem 0 0;
            padding: 0;
            text-align: center;
            font-size: .82rem;
            font-weight: 800;
            line-height: 1.35;
            color: #0b1220;
            background: transparent;
            border: 0;
        }

        .checkout-paycard__recordatorio .bi {
            display: none;
        }

        /* Bloque verde del paso de tarjeta: ya no muestra el QR de una
           billetera. Convive con el formulario de Mercado Pago: el cliente
           elige escribir la tarjeta o pedir aquí el QR real de este mismo
           pago, que solo se crea cuando toca el botón. */
        .checkout-paycard__atajo {
            display: flex;
            align-items: center;
            gap: .75rem;
            margin: .7rem 0 .15rem;
            padding: .65rem .75rem;
            border: 1px solid #cdeadd;
            border-radius: .9rem;
            background: linear-gradient(135deg, #f0fdf6 0%, #ecfdf5 100%);
        }

        .checkout-paycard__atajo[hidden],
        .checkout-paycard__atajo-aviso[hidden] {
            display: none;
        }

        .checkout-paycard__atajo-texto {
            flex: 1 1 auto;
            min-width: 0;
        }

        .checkout-paycard__atajo-eyebrow {
            display: block;
            margin-bottom: .18rem;
            color: #0f766e;
            font-size: .6rem;
            font-weight: 800;
            letter-spacing: .11em;
            text-transform: uppercase;
        }

        .checkout-paycard__atajo-titulo {
            margin: 0;
            color: #0b1220;
            font-size: .86rem;
            font-weight: 700;
            line-height: 1.3;
        }

        .checkout-paycard__atajo-guia {
            margin: .15rem 0 .45rem;
            color: #4b5563;
            font-size: .7rem;
            line-height: 1.35;
        }

        .checkout-paycard__atajo .btn {
            --bs-btn-padding-y: .26rem;
            --bs-btn-padding-x: .64rem;
            --bs-btn-font-size: .72rem;
            border-radius: 999px !important;
        }

        .checkout-paycard__atajo-qr {
            display: grid;
            flex: 0 0 auto;
            place-items: center;
            width: 96px;
            height: 96px;
            padding: .25rem;
            border: 1px solid #d7e7df;
            border-radius: .7rem;
            background: #fff;
            cursor: pointer;
        }

        .checkout-paycard__atajo-qr img {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }

        .checkout-paycard__atajo-qr:hover {
            border-color: #9fd6c2;
            background: #f8fffb;
        }

        .checkout-paycard__atajo-qr:focus-visible {
            outline: 3px solid rgba(15, 118, 110, .35);
            outline-offset: 2px;
        }

        .checkout-paycard__atajo-qr:disabled {
            cursor: default;
        }

        .checkout-paycard__atajo-spinner {
            display: grid;
            place-items: center;
            width: 100%;
            height: 100%;
            color: #0f766e;
        }

        /* El QR, su icono y el spinner se alternan con el atributo `hidden`;
           sin esta regla `display: grid` los dejaría siempre a la vista. */
        .checkout-paycard__atajo-qr img[hidden],
        .checkout-paycard__atajo-qr-icono[hidden],
        .checkout-paycard__atajo-spinner[hidden] {
            display: none;
        }

        .checkout-paycard__atajo-qr-icono {
            display: grid;
            place-items: center;
            width: 100%;
            height: 100%;
            color: #0f766e;
            font-size: 1.9rem;
            opacity: .45;
        }

        .checkout-paycard__atajo-pedido {
            display: inline-flex;
            align-items: center;
            gap: .3rem;
            margin-top: .35rem;
            color: #0f766e;
            font-size: .72rem;
            font-weight: 700;
            text-decoration: none;
        }

        .checkout-paycard__atajo-pedido:hover {
            color: #115e59;
            text-decoration: underline;
        }

        .checkout-paycard__atajo-aviso {
            margin: .45rem 0 0;
            padding: .45rem .6rem;
            border: 1px solid #f5c2c7;
            border-radius: .6rem;
            background: #fdf2f2;
            color: #842029;
            font-size: .7rem;
            line-height: 1.35;
        }

        .checkout-paycard__atajo-aviso button {
            padding: 0;
            border: 0;
            background: transparent;
            color: #842029;
            font-weight: 800;
            text-decoration: underline;
        }

        /* Monto junto al formulario de la tarjeta: el botón del brick no
           imprime el importe, así que se repite aquí antes de escribir los
           datos, como en la pantalla de Mercado Pago. */
        .checkout-paycard__cobro {
            display: flex;
            align-items: center;
            gap: .4rem;
            margin: 0 0 .55rem;
            color: #334155;
            font-size: .78rem;
            line-height: 1.35;
        }

        .checkout-paycard__cobro .bi {
            flex: 0 0 auto;
            color: #0f766e;
            font-size: .95rem;
        }

        .checkout-paycard__cobro strong {
            color: #0b1220;
            font-weight: 800;
        }

        /* Formulario real de tarjeta (Checkout Bricks): el iframe lo pinta
           Mercado Pago. La tienda solo reserva el marco y recuerda que el
           número y el CVV nunca pasan por aquí. */
        .checkout-paycard__brick {
            margin-top: .75rem;
            padding: .75rem;
            border: 1px solid #dbe7df;
            border-radius: .9rem;
            background: #fff;
        }

        .checkout-paycard__brick #cardPaymentBrickModal {
            min-height: 240px;
        }

        .checkout-paycard__brick-aviso {
            display: flex;
            align-items: flex-start;
            gap: .4rem;
            margin: .65rem 0 0;
            font-size: .7rem;
            line-height: 1.4;
            text-align: left;
            color: #334155;
        }

        .checkout-paycard__brick-aviso .bi {
            flex: 0 0 auto;
            margin-top: .05rem;
            font-size: .85rem;
            color: #15803d;
        }

        .checkout-paycard__brick-aviso--error {
            padding: .5rem .6rem;
            border-radius: .6rem;
            background: #fef2f2;
            color: #b91c1c;
            font-weight: 600;
        }

        .checkout-paycard__brick[hidden],
        .checkout-paycard__brick-aviso[hidden],
        .checkout-paycard__divider[hidden],
        .checkout-paycard__atajo[hidden] {
            display: none !important;
        }

        /* En móvil el modal se comporta como hoja inferior: se apoya en el
           borde, deja ver la tienda detrás y los pulgares alcanzan el botón.
           Todo va compacto para que los tres medios y el botón entren en una
           pantalla de 648 px sin obligar a desplazarse. */
        @media (max-width: 575.98px) {
            .checkout-payment-modal__dialog {
                position: fixed;
                right: 0;
                bottom: 0;
                left: 0;
                width: 100%;
                max-width: none;
                min-height: 0;
                margin: 0;
                align-items: flex-end;
            }

            .checkout-payment-modal__dialog .checkout-payment-modal {
                width: 100%;
                max-height: 94dvh;
                border-radius: 1.35rem 1.35rem 0 0;
            }

            .checkout-payment-modal .modal-header {
                padding: 1.25rem .95rem .6rem;
                gap: .55rem;
            }

            .checkout-payment-modal__brand {
                width: 34px;
                height: 34px;
                border-radius: 10px;
                font-size: 1rem;
            }

            .checkout-payment-modal__head-title {
                font-size: .9rem;
            }

            .checkout-payment-modal__head-sub {
                font-size: .68rem;
            }

            .checkout-payment-modal .modal-body {
                padding: .7rem .9rem .8rem;
                overscroll-behavior: contain;
            }

            .checkout-payment-modal__titulo {
                font-size: 1rem;
            }

            .checkout-payment-modal__guia {
                margin-bottom: .6rem;
                font-size: .76rem;
            }

            .checkout-payment-modal__monto {
                margin-bottom: .55rem;
                padding: .6rem .85rem;
                border-radius: .9rem;
            }

            .checkout-payment-modal__monto strong {
                font-size: 1.25rem;
            }

            .checkout-payment-modal__monto-resumen {
                margin-top: .15rem;
                font-size: .68rem;
            }

            .checkout-payment-modal__paso {
                margin-bottom: .22rem;
                padding: .13rem .42rem;
                font-size: .55rem;
            }

            /* En una pantalla de 648 px cada línea cuenta: las etiquetas del
               monto y los textos largos de los sellos se ocultan, pero los
               títulos siguen respondiendo las dudas. */
            .checkout-payment-modal__monto-tags { display: none; }

            .checkout-payment-modal__sellos {
                margin-top: .5rem;
                gap: .25rem;
            }

            .checkout-payment-modal__sellos li {
                gap: .28rem;
                padding: .4rem .3rem;
                border-radius: .55rem;
            }

            .checkout-payment-modal__sellos strong { font-size: .58rem; }
            .checkout-payment-modal__sellos small { display: none; }
            .checkout-payment-modal__sellos i { font-size: .75rem; }

            .checkout-payment-modal__soporte {
                margin-top: .45rem;
                padding: .35rem .5rem;
                font-size: .64rem;
            }

            .checkout-payment-modal__seccion {
                display: none;
            }

            .checkout-payment-modal__opciones {
                gap: .4rem;
            }

            .checkout-method-card {
                padding: .48rem .6rem;
                column-gap: .55rem;
                border-radius: .8rem;
            }

            .checkout-method-card__icon {
                width: 36px;
                height: 36px;
                border-radius: .65rem;
                font-size: 1rem;
            }

            .checkout-method-card__title {
                font-size: .82rem;
                gap: .3rem;
            }

            .checkout-method-card__hint {
                font-size: .68rem;
                line-height: 1.25;
            }

            .checkout-method-card__badge {
                padding: .08rem .36rem;
                font-size: .52rem;
            }

            .checkout-method-card__marcas {
                margin-top: .15rem;
                gap: .18rem;
            }

            .checkout-method-card__marca {
                height: 1.05rem;
                width: 1.6rem;
                padding: .04rem .1rem;
            }

            .checkout-method-card__marca--texto {
                height: 1.05rem;
                min-width: 1.6rem;
                padding: 0 .28rem;
                font-size: .52rem;
            }

            .checkout-paycard {
                padding: .8rem .7rem .75rem;
            }

            .checkout-paycard__titulo {
                font-size: .95rem;
            }

            .checkout-paycard__qr {
                width: 92px;
            }

            .checkout-paycard__brick {
                margin-top: .55rem;
                padding: .55rem;
            }

            .checkout-paycard__brick #cardPaymentBrickModal {
                min-height: 210px;
            }

            .checkout-paycard__brick-aviso {
                font-size: .66rem;
            }

            .checkout-paycard__recordatorio {
                font-size: .74rem;
            }

            .checkout-paycard__atajo {
                gap: .5rem;
                margin-top: .55rem;
                padding: .55rem .6rem;
            }

            .checkout-paycard__atajo-qr {
                width: 78px;
                height: 78px;
            }

            .checkout-paycard__atajo-guia {
                font-size: .66rem;
            }

            .checkout-paycard__cobro {
                font-size: .72rem;
            }

            .checkout-payment-modal__privacidad {
                font-size: .64rem;
            }

            .checkout-payment-modal__pci {
                font-size: .48rem;
            }

            .checkout-payment-modal .modal-footer {
                padding: .55rem .9rem calc(.6rem + env(safe-area-inset-bottom, 0px));
            }

            .checkout-payment-modal .modal-footer .btn {
                min-height: 46px;
                font-size: .9rem;
            }

            .checkout-payment-modal__confianza {
                margin-top: .25rem;
                font-size: .64rem;
                line-height: 1.3;
            }
        }

        /* Pantallas muy bajas (celular acostado): el contenido scrollea y el
           botón queda siempre a la vista. */
        @media (max-height: 620px) {
            .checkout-payment-modal__monto {
                padding: .7rem .9rem;
            }

            .checkout-payment-modal__monto strong {
                font-size: 1.3rem;
            }
        }

        /* Verde solo para la acción que cierra la compra: es el único avance
           irreversible de la pantalla y debe reconocerse al instante. */
        #btn-submit-checkout,
        .checkout-btn-pagar {
            background-image: linear-gradient(180deg, #17a34a 0%, #15803d 100%) !important;
            background-color: #15803d !important;
            border-color: #15803d !important;
            color: #fff !important;
            box-shadow: 0 .7rem 1.6rem -6px rgba(21, 128, 61, .55);
        }

        #btn-submit-checkout:hover,
        .checkout-btn-pagar:hover {
            filter: brightness(.96) !important;
        }

        /* El verde solo debe verse activo cuando algo va a pasar: «Continuar»
           nace deshabilitado y sin esta regla se pintaba igual que el botón
           que sí paga, así que el cliente tocaba un botón muerto. */
        #btn-submit-checkout:disabled,
        .checkout-btn-pagar:disabled {
            background-image: none !important;
            background-color: #d7e5db !important;
            border-color: #d7e5db !important;
            color: #94a3a0 !important;
            box-shadow: none;
        }

        /* Cada método es una tarjeta grande: icono, nombre y un selector a la
           derecha. Así el cliente elige por forma antes de leer el subtítulo. */
        .checkout-method-card {
            position: relative;
            display: grid;
            grid-template-columns: auto minmax(0, 1fr) auto;
            column-gap: .8rem;
            align-items: center;
            margin: 0;
            padding: .75rem .85rem;
            text-align: start;
            border: 1.5px solid #e6ebf2;
            border-radius: 1rem;
            background: #fff;
            cursor: pointer;
            transition: border-color .18s ease, background-color .18s ease,
                box-shadow .18s ease, transform .18s ease;
        }

        .checkout-method-card:hover {
            border-color: #c3d4f3;
            background: #fbfcff;
            transform: translateY(-1px);
        }

        .checkout-method-card:has(input:checked) {
            border-color: var(--color-primary, #0d6efd);
            background-color: color-mix(in srgb, var(--color-primary, #0d6efd) 6%, #fff);
            box-shadow: 0 .7rem 1.5rem -12px rgba(13, 110, 253, .55);
        }

        /* Barra lateral que confirma la elección incluso antes de mirar el
           radio: en una lista de tres tarjetas el borde solo no basta. */
        .checkout-method-card:has(input:checked)::before {
            content: "";
            position: absolute;
            top: 16%;
            bottom: 16%;
            left: 0;
            width: 3px;
            border-radius: 0 3px 3px 0;
            background: var(--color-primary, #0d6efd);
        }

        .checkout-method-card:has(input:focus-visible) {
            outline: 3px solid rgba(13, 110, 253, .3);
            outline-offset: 3px;
        }

        .checkout-method-card--proximamente {
            cursor: default;
            border-style: dashed;
            background: #f8fafc;
            opacity: .8;
            grid-template-columns: auto minmax(0, 1fr);
        }

        .checkout-method-card--proximamente:hover {
            border-color: #e6ebf2;
            box-shadow: none;
            transform: none;
        }

        .checkout-method-card__badge {
            display: inline-flex;
            align-items: center;
            font-size: .6rem;
            font-weight: 800;
            letter-spacing: .05em;
            text-transform: uppercase;
            color: #64748b;
            background: #e9eef5;
            border-radius: 999px;
            padding: .14rem .5rem;
            white-space: nowrap;
        }

        .checkout-method-card__badge--rapido {
            color: #0a5c36;
            background: #dcf5e7;
        }

        /* Selector propio: el radio nativo se reemplaza por un punto limpio que
           sigue siendo un input real (teclado y lector de pantalla intactos). */
        .checkout-method-card .form-check-input {
            grid-column: 3;
            grid-row: 1;
            justify-self: end;
            float: none;
            width: 1.15rem;
            height: 1.15rem;
            margin: 0;
            border: 2px solid #cbd5e1;
            border-radius: 50%;
            background-color: #fff;
            cursor: pointer;
            transition: border-color .18s ease, background .18s ease;
        }

        .checkout-method-card .form-check-input:checked {
            border-color: var(--color-primary, #0d6efd);
            background: radial-gradient(circle, #fff 0 2.8px, var(--color-primary, #0d6efd) 3.2px);
        }

        .checkout-method-card .form-check-input:focus {
            box-shadow: 0 0 0 3px rgba(13, 110, 253, .2);
        }

        .checkout-method-card__icon {
            display: grid;
            place-items: center;
            grid-column: 1;
            grid-row: 1;
            width: 46px;
            height: 46px;
            border-radius: .9rem;
            background: color-mix(in srgb, var(--color-primary, #0d6efd) 9%, #fff);
            color: var(--color-primary, #0d6efd);
            font-size: 1.3rem;
        }

        /* El logotipo de la marca ya trae su propio color y su forma: dentro del
           cuadro pintado del método se leería mal, así que va sobre blanco. */
        .checkout-method-card__icon--marca {
            background: #fff;
            border: 1px solid #e5e9f2;
        }

        .checkout-method-card__icon--marca img {
            width: 30px;
            height: 30px;
            object-fit: contain;
        }

        /* Cada billetera conserva su color: el cliente reconoce Plin y Yape
           antes de leer el título. La tarjeta se queda con el azul de la marca. */
        .checkout-method-card:has(input[value="plin"]) .checkout-method-card__icon {
            background: rgba(13, 148, 136, .1);
            color: #0f766e;
        }

        .checkout-method-card:has(input[value="yape"]) .checkout-method-card__icon {
            background: rgba(124, 58, 237, .1);
            color: #6d28d9;
        }

        .checkout-method-card:has(input:checked) .checkout-method-card__icon {
            box-shadow: 0 0 0 3px color-mix(in srgb, currentColor 16%, transparent);
        }

        .checkout-method-card__text {
            grid-column: 2;
            grid-row: 1;
            display: flex;
            flex-direction: column;
            gap: .12rem;
            min-width: 0;
        }

        .checkout-method-card__title {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: .4rem;
            font-size: .9rem;
            font-weight: 700;
            color: #0b1220;
            line-height: 1.25;
        }

        .checkout-method-card__hint {
            font-size: .75rem;
            line-height: 1.35;
            color: #64748b;
        }

        /* La fila bloqueada no tiene radio: el logo y el texto ocupan las dos
           columnas y el sello de aviso va dentro del título. */
        .checkout-method-card--proximamente .checkout-method-card__icon {
            grid-column: 1;
        }

        .checkout-method-card--proximamente .checkout-method-card__text {
            grid-column: 2;
        }

        .checkout-account {
            display: grid;
            gap: 1.25rem;
            justify-items: center;
            align-items: start;
        }

        @media (min-width: 768px) {
            .checkout-account {
                grid-template-columns: minmax(0, 280px) minmax(0, 1fr);
                justify-items: start;
            }
        }

        .checkout-account__data {
            width: 100%;
            max-width: 420px;
        }

        .checkout-account__actions {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: .5rem;
            margin-bottom: 1rem;
        }

        .payment-icons [data-payment-badge] {
            opacity: .5;
            transition: opacity .2s ease, background-color .2s ease, border-color .2s ease;
        }

        .payment-icons [data-payment-badge].is-selected {
            opacity: 1;
            background-color: #0d6efd !important;
            border-color: #0d6efd !important;
            color: #fff !important;
        }

        /* El panel de datos es el que decide si el QR va al lado: se consulta su
           propio ancho porque el carrito se angosta antes que la ventana. */
        .checkout-qr-preview {
            container-type: inline-size;
            border: 1px solid #dbe5f3;
            border-radius: 1rem;
            background: linear-gradient(145deg, #f8fbff, #fff);
        }

        .checkout-qr-preview__image {
            width: min(100%, 280px);
            aspect-ratio: 1;
            object-fit: contain;
            padding: .65rem;
            border: 1px solid #edf0f4;
            border-radius: .85rem;
            background: #fff;
        }

        .checkout-qr-preview__amount {
            display: inline-block;
            padding: .4rem .75rem;
            border-radius: 999px;
            background: #eef4ff;
            color: #174ea6;
            font-weight: 700;
        }

        .checkout-qr-preview__image-wrap {
            position: relative;
            display: inline-flex;
            padding: .55rem;
            border: 1px solid #e8edf4;
            border-radius: 1rem;
            background: #fff;
            box-shadow: 0 .5rem 1.5rem rgba(31, 45, 61, .08);
        }

        /* El QR sube como foto de pantalla: el cuadro entero se ve grande pero el
           código adentro es chico, así que agrandarlo no es un adorno. */
        /* El tema ya fija `button:not(:disabled) { cursor: pointer }`, así que
           sin el :not() esta regla empataría y perdería por orden de carga. */
        button.checkout-qr-preview__image-wrap:not(:disabled) {
            cursor: zoom-in;
            line-height: 0;
        }

        button.checkout-qr-preview__image-wrap:focus-visible {
            outline: 2px solid var(--color-primary, #0d6efd);
            outline-offset: 2px;
        }

        /* El QR real sube como foto de pantalla vertical: si se fuerza un cuadro
           1:1, la foto se encoge para caber y el código se ve más chico que la
           miniatura de abajo. Se deja que use el alto disponible. */
        .qr-zoom__image {
            width: auto;
            height: auto;
            max-width: min(100%, 460px);
            max-height: 78vh;
            padding: .75rem;
            border: 1px solid #edf0f4;
            border-radius: .85rem;
            background: #fff;
        }

        .checkout-bcp-data {
            max-width: 420px;
            display: grid;
            gap: .5rem;
        }

        .checkout-bcp-data__row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: .75rem;
            padding: .65rem .85rem;
            border: 1px solid #e4eaf2;
            border-radius: .75rem;
            background: #fff;
        }

        .checkout-bcp-data__label {
            display: block;
            font-size: .75rem;
            color: #6c757d;
        }

        /* El QR sube como foto de pantalla: si la columna se deja en `auto`, el
           navegador la mide por el ancho natural de la foto (900px) y le roba
           todo el sitio al texto, que queda a una letra por línea. */
        .checkout-quickpay {
            display: grid;
            grid-template-columns: 1fr;
            gap: 1.25rem;
            align-items: center;
        }

        /* 600px es lo mínimo para que el texto respire al lado del QR de 280px;
           por debajo, el QR sube arriba y todo queda a una columna. */
        @container (min-width: 600px) {
            .checkout-quickpay {
                grid-template-columns: minmax(0, 1fr) minmax(0, 280px);
            }
        }

        @container (max-width: 599.98px) {
            .checkout-quickpay__qr,
            .checkout-quickpay__aside {
                order: -1;
            }
        }

        .checkout-quickpay__titulo {
            font-size: 1.2rem;
            font-weight: 800;
            color: var(--color-primary, #0d6efd);
            margin: 0;
        }

        .checkout-quickpay__guia {
            font-size: .9rem;
            color: #495057;
            margin: .35rem 0 0;
        }

        .checkout-quickpay__divide {
            display: flex;
            align-items: center;
            gap: .6rem;
            margin: .95rem 0;
            font-size: .78rem;
            color: #6c757d;
        }

        .checkout-quickpay__divide::before,
        .checkout-quickpay__divide::after {
            content: "";
            flex: 1;
            height: 1px;
            background: #e4eaf2;
        }

        .checkout-quickpay__aviso {
            display: flex;
            gap: .5rem;
            align-items: flex-start;
            padding: .6rem .75rem;
            margin: 0 0 .9rem;
            border: 1px solid #ffe9b3;
            border-radius: .75rem;
            background: #fff8e6;
            color: #7a5b00;
            font-size: .8rem;
        }

        .checkout-quickpay__qr {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: .5rem;
            min-width: 0;
        }

        .checkout-quickpay__qr .checkout-qr-preview__image-wrap {
            max-width: 100%;
        }

        /* Dentro del panel de pago las filas aprovechan todo el ancho: el tope de
           420px era para la maqueta vieja de una sola columna. */
        .checkout-quickpay .checkout-bcp-data {
            max-width: none;
        }

        .checkout-quickpay__specs {
            width: 100%;
            max-width: 280px;
            display: grid;
            gap: .4rem;
            margin: 0;
            padding: 0;
            list-style: none;
            font-size: .78rem;
            color: #495057;
        }

        .checkout-quickpay__specs li {
            display: flex;
            gap: .45rem;
            align-items: baseline;
        }

        .checkout-quickpay__aside {
            display: grid;
            gap: .75rem;
            justify-items: center;
            align-content: start;
            min-width: 0;
        }

        .checkout-quickpay__marca {
            display: inline-flex;
            align-items: center;
            gap: .5rem;
            padding: .5rem .85rem;
            border: 1px solid #e8edf4;
            border-radius: 999px;
            background: #fff;
            box-shadow: 0 .5rem 1.5rem rgba(31, 45, 61, .08);
            font-size: .8rem;
            color: #495057;
        }

        .checkout-quickpay__pasos {
            width: 100%;
            max-width: 280px;
            display: grid;
            gap: .6rem;
            padding: .9rem 1rem;
            border: 1px solid #e4eaf2;
            border-radius: .9rem;
            background: #fff;
        }

        .checkout-quickpay__pasos-titulo {
            font-size: .75rem;
            color: #6c757d;
            margin: 0;
        }

        .checkout-quickpay__paso {
            display: grid;
            grid-template-columns: auto minmax(0, 1fr);
            gap: .6rem;
            align-items: start;
            font-size: .8rem;
            color: #495057;
        }

        .checkout-quickpay__paso b {
            display: grid;
            place-items: center;
            width: 22px;
            height: 22px;
            border-radius: 999px;
            background: #eef4ff;
            color: #174ea6;
            font-size: .72rem;
        }

        /* El atajo de tarjeta que se imprime en la página: es un submit con su
           propio name, así que «pago_celular=1» solo viaja si el cliente lo
           presiona y no el botón principal del pie. */
        .checkout-quickpay__celular {
            width: 100%;
            max-width: 280px;
            display: grid;
            grid-template-columns: auto minmax(0, 1fr);
            gap: .65rem;
            align-items: center;
            text-align: start;
            padding: .7rem .9rem;
            border: 1px dashed #9ec5fe;
            border-radius: .9rem;
            background: #f7fbff;
            color: #0a58ca;
            transition: background .15s ease, border-color .15s ease;
        }

        .checkout-quickpay__celular i {
            font-size: 1.45rem;
        }

        .checkout-quickpay__celular strong {
            display: block;
            font-size: .875rem;
            line-height: 1.2;
        }

        .checkout-quickpay__celular small {
            display: block;
            margin-top: .15rem;
            font-size: .72rem;
            line-height: 1.25;
            color: #495057;
        }

        .checkout-quickpay__celular:hover,
        .checkout-quickpay__celular:focus-visible {
            background: #eaf3ff;
            border-color: #0a58ca;
            color: #0a58ca;
        }

        /* El botón de siempre: si el panel de datos crece, el cliente pierde de
           vista lo que paga, así que la orden de pago se queda pegada abajo. */
        .checkout-cta {
            position: sticky;
            bottom: .75rem;
            z-index: 5;
            margin-top: 1rem;
            padding: .5rem;
            border-radius: 1.15rem;
            background: rgba(255, 255, 255, .94);
            box-shadow: 0 .6rem 1.6rem rgba(31, 45, 61, .16);
        }

        .checkout-cta__espera {
            margin-top: .6rem;
            padding-top: .6rem;
            border-top: 1px solid #e6ecf5;
        }

        .checkout-cta__barra {
            height: 3px;
            border-radius: 999px;
            background: #e9efff;
            overflow: hidden;
            margin-bottom: .55rem;
        }

        .checkout-cta__barra span {
            display: block;
            width: 40%;
            height: 100%;
            border-radius: 999px;
            background: #0d6efd;
            animation: checkout-espera-ida 1.15s ease-in-out infinite;
        }

        @keyframes checkout-espera-ida {
            0% {
                transform: translateX(-100%);
            }

            100% {
                transform: translateX(250%);
            }
        }

        @media (prefers-reduced-motion: reduce) {
            .checkout-cta__barra span {
                animation: none;
                width: 100%;
            }
        }

        @media (max-width: 575.98px) {
            .checkout-qr-preview__image {
                width: min(100%, 240px);
            }
        }

        .cart-items,
        #checkout-form .card,
        .cart-summary {
            border: 1px solid #edf0f4;
            border-radius: 1rem;
            box-shadow: 0 .25rem 1rem rgba(31, 45, 61, .06);
        }

        .cart-items {
            padding: 1rem;
        }

        .cart-items .cart-header {
            border-bottom: 1px solid #edf0f4;
            margin-bottom: .75rem;
        }

        .cart-item {
            border-bottom: 1px solid #edf0f4;
            padding: .5rem 0 1rem;
        }

        .cart-item:last-of-type {
            border-bottom: 0;
        }

        .cart-item .product-image img {
            width: 76px;
            height: 76px;
            object-fit: contain;
            border: 1px solid #edf0f4;
            border-radius: .75rem;
            background: #fff;
        }

        .cart-summary {
            padding: 1.25rem;
        }

        @media (max-width: 767.98px) {
            .cart-items,
            .cart-summary {
                padding: 1rem;
            }

            .cart-item .product-info {
                align-items: flex-start !important;
            }

            .cart-item .quantity-selector {
                min-height: 42px;
            }
        }
    </style>
    <!-- Cabecera del carrito -->
    <section class="g-page-head">
        <div class="container">
            <nav class="g-crumbs" aria-label="Ubicación">
                <a href="{{ url('/') }}">
                    <i class="bi bi-house-door" aria-hidden="true"></i> Principal
                </a>
                <i class="bi bi-chevron-right" aria-hidden="true"></i>
                <span aria-current="page">Carrito de compras</span>
            </nav>

            <div class="g-cart-head">
                <div class="g-cart-head-main">
                    <span class="g-eyebrow">Compra segura · GALY</span>
                    <h1>Tu carrito</h1>
                    <p>
                        @if ($carritos->isEmpty())
                            Aún no tienes productos en el carrito. Explora el catálogo y arma tu pedido.
                        @else
                            {{ $carritos->sum('cantidad') }}
                            {{ \Illuminate\Support\Str::plural('artículo', $carritos->sum('cantidad')) }}
                            listos para pedir. Revisa tu pedido y elige cómo pagar.
                        @endif
                    </p>
                </div>
                <ul class="g-cart-head-chips" aria-label="Ventajas de comprar en GALY">
                    <li><i class="bi bi-shield-check" aria-hidden="true"></i> Pago protegido</li>
                    <li><i class="bi bi-truck" aria-hidden="true"></i> Envíos a todo el Perú</li>
                    <li><i class="bi bi-phone" aria-hidden="true"></i> Yape · Plin</li>
                </ul>
            </div>
        </div>
    </section>

    <!-- Cart Section -->
    <section id="cart" class="cart section">

        <div class="container" data-aos="fade-up" data-aos-delay="100">
            @if (session('mensaje'))
                @php
                    $checkoutAlertType = match (session('icono')) {
                        'success' => 'success',
                        'warning' => 'warning',
                        'info' => 'info',
                        default => 'danger',
                    };
                @endphp
                <div class="alert alert-{{ $checkoutAlertType }} alert-dismissible fade show rounded-3 mb-4" role="alert">
                    {{ session('mensaje') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
                </div>
            @endif

            @if ($errors->any())
                <div class="alert alert-danger rounded-3 mb-4" role="alert" aria-live="assertive">
                    <strong>Revisa estos datos antes de continuar:</strong>
                    <ul class="mb-0 mt-2">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="row g-4 align-items-start">
                <div class="{{ $carritos->isEmpty() ? 'col-12' : 'col-lg-8' }}" data-aos="fade-up" data-aos-delay="200">
                    <div class="cart-items g-cart-card">
                        <div class="cart-header d-none d-lg-block">
                            <div class="row align-items-center">
                                <div class="col-lg-6">
                                    <h5>Productos</h5>
                                </div>
                                <div class="col-lg-2 text-center">
                                    <h5>Precio</h5>
                                </div>
                                <div class="col-lg-2 text-center">
                                    <h5>Cantidad</h5>
                                </div>
                                <div class="col-lg-2 text-end">
                                    <h5>Subtotal</h5>
                                </div>
                            </div>
                        </div>
                        @php
                            $subtotal_original_acumulado = 0;
                            $subtotal_acumulado = 0;
                        @endphp

                        @if ($carritos->isNotEmpty())
                            <form action="{{ route('web.carrito.update') }}" method="POST" id="form-actualizar-carrito">
                                @csrf
                                @method('PUT')
                        @endif

                        @forelse ($carritos as $carrito)
                            <!-- Cart Item -->
                            <div class="cart-item">
                                <div class="row align-items-center">

                                    <div class="col-lg-6 col-12 mb-3 mb-lg-0">
                                        <div class="product-info d-flex align-items-center">
                                            @php
                                                $imagen = $carrito->producto->imagen_principal_url;
                                                $precio_original = $carrito->producto->precio_venta;
                                                $precio_actual = ($carrito->producto->en_oferta && $carrito->producto->precio_oferta > 0)
                                                    ? $carrito->producto->precio_oferta
                                                    : $carrito->producto->precio_venta;
                                            @endphp

                                            <a class="product-image g-cart-thumb"
                                                href="{{ route('web.detalle_producto', $carrito->producto->id) }}"
                                                aria-label="Ver {{ $carrito->producto->nombre }}">
                                                <img src="{{ $imagen }}" alt="{{ $carrito->producto->nombre }}"
                                                    class="img-fluid" loading="lazy"
                                                    onerror="this.onerror=null;this.src='{{ asset('storage/productos/default.webp') }}';">
                                            </a>

                                            <div class="product-details">
                                                @if ($carrito->producto->marca_nombre)
                                                    <span class="g-cart-brand">{{ $carrito->producto->marca_nombre }}</span>
                                                @endif

                                                <a class="product-title"
                                                    href="{{ route('web.detalle_producto', $carrito->producto->id) }}">
                                                    {{ $carrito->producto->nombre }}
                                                </a>

                                                <div class="g-cart-meta">
                                                    <span class="g-cart-stock {{ $carrito->producto->stock <= 5 ? 'is-low' : '' }}">
                                                        <i class="bi {{ $carrito->producto->stock <= 5 ? 'bi-exclamation-circle' : 'bi-check-circle' }}"
                                                            aria-hidden="true"></i>
                                                        {{ $carrito->producto->stock }} disponibles
                                                    </span>
                                                    <span class="g-cart-unit">
                                                        {{ \App\Models\Ajuste::formatear_precio($precio_actual) }} c/u
                                                    </span>
                                                </div>

                                                <button class="g-cart-remove" type="button"
                                                    onclick="confirmarEliminarItem({{ $carrito->id }})"
                                                    aria-label="Quitar {{ $carrito->producto->nombre }} del carrito">
                                                    <i class="bi bi-trash3" aria-hidden="true"></i>
                                                    <span>Quitar</span>
                                                </button>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-lg-2 col-12 mb-2 mb-lg-0 text-center">
                                        <div class="price-tag g-cart-prices">
                                            @if ($carrito->producto->en_oferta && $carrito->producto->precio_oferta > 0)
                                                <span class="g-cart-price-old">
                                                    {{ \App\Models\Ajuste::formatear_precio($carrito->producto->precio_venta) }}
                                                </span>
                                                <span class="current-price g-cart-price">
                                                    {{ \App\Models\Ajuste::formatear_precio($carrito->producto->precio_oferta) }}
                                                </span>
                                                <span class="g-cart-off">-{{ $carrito->producto->descuento }}%</span>
                                            @else
                                                <span class="current-price g-cart-price">
                                                    {{ \App\Models\Ajuste::formatear_precio($carrito->producto->precio_venta) }}
                                                </span>
                                            @endif
                                        </div>
                                    </div>

                                    <div class="col-lg-2 col-12 mb-2 mb-lg-0 text-center">
                                        <div class="quantity-selector g-cart-qty d-flex align-items-center justify-content-center">
                                            <button type="button" class="quantity-btn decrease-qty"
                                                data-id="{{ $carrito->id }}"
                                                aria-label="Quitar una unidad de {{ $carrito->producto->nombre }}">
                                                <i class="bi bi-dash-lg" aria-hidden="true"></i>
                                            </button>
                                            <input type="number" class="quantity-input mx-1 text-center"
                                                id="quantity-input-{{ $carrito->id }}"
                                                value="{{ $carrito->cantidad }}" min="1"
                                                max="{{ $carrito->producto->stock }}" name="cantidades[{{ $carrito->id }}]"
                                                aria-label="Cantidad de {{ $carrito->producto->nombre }}">
                                            <button type="button" class="quantity-btn increase-qty"
                                                data-id="{{ $carrito->id }}"
                                                aria-label="Agregar una unidad de {{ $carrito->producto->nombre }}">
                                                <i class="bi bi-plus-lg" aria-hidden="true"></i>
                                            </button>
                                        </div>
                                    </div>

                                    <div class="col-lg-2 col-12 text-center">
                                        <div class="item-total g-cart-line-total">
                                            @php
                                                $subtotal_original_item = $precio_original * $carrito->cantidad;
                                                $subtotal_item = $precio_actual * $carrito->cantidad;
                                                $ahorro_item = $subtotal_original_item - $subtotal_item;

                                                $subtotal_original_acumulado += $subtotal_original_item;
                                                $subtotal_acumulado += $subtotal_item;
                                            @endphp

                                            <span class="g-cart-price">
                                                {{ \App\Models\Ajuste::formatear_precio($subtotal_item) }}
                                            </span>
                                            @if ($ahorro_item > 0)
                                                <small class="g-cart-saving">
                                                    Ahorras {{ \App\Models\Ajuste::formatear_precio($ahorro_item) }}
                                                </small>
                                            @endif
                                        </div>
                                    </div>

                                </div>
                            </div><!-- End Cart Item -->
                        @empty
                            <div class="g-cart-empty">
                                <span class="g-cart-empty-icon" aria-hidden="true">
                                    <i class="bi bi-bag"></i>
                                </span>
                                <h2>Tu carrito está vacío</h2>
                                <p>
                                    Explora el catálogo y agrega los productos que necesites.
                                    Tu carrito queda guardado cuando inicias sesión.
                                </p>
                                <div class="g-cart-empty-actions">
                                    <a href="{{ route('web.productos') }}" class="btn btn-primary">
                                        <i class="bi bi-grid" aria-hidden="true"></i> Ver productos
                                    </a>
                                    <a href="{{ route('web.ofertas') }}" class="btn btn-outline-primary">
                                        <i class="bi bi-percent" aria-hidden="true"></i> Ver ofertas
                                    </a>
                                </div>
                                <ul class="g-cart-empty-perks">
                                    <li><i class="bi bi-shield-check" aria-hidden="true"></i> Compra protegida</li>
                                    <li><i class="bi bi-truck" aria-hidden="true"></i> Envíos a todo el Perú</li>
                                    <li><i class="bi bi-arrow-repeat" aria-hidden="true"></i> Sigue tu pedido</li>
                                </ul>
                            </div>
                        @endforelse

                        @php
                            $reglasEnvio = (array) config('tienda.envio');
                            // El umbral de envío gratis mira el subtotal ANTES del cupón:
                            // si no, aplicar un descuento podría encarecer el pedido.
                            $envioGratis = $subtotal_acumulado >= $reglasEnvio['umbral_gratis'];
                            $costoEnvioInicial = $carritos->isNotEmpty() && ! $envioGratis
                                ? $reglasEnvio['costo_estandar']
                                : 0;
                            $descuentoCupon = (float) ($cupon['descuento'] ?? 0);
                            $porcentajeCupon = (int) ($cupon['porcentaje'] ?? 0);
                            $totalInicial = round($subtotal_acumulado - $descuentoCupon + $costoEnvioInicial, 2);
                            // Si el envío volvió con un error, la modalidad elegida
                            // no puede reiniciarse a «envío» sin avisar al cliente.
                            $modalidadActual = old('modalidad_entrega', 'envio');
                        @endphp

                        @if ($carritos->isNotEmpty())
                            </form>

                            <div class="cart-actions g-cart-actions">
                                <div class="g-cart-actions-left">
                                    <button type="submit" form="form-actualizar-carrito" class="btn btn-primary">
                                        <i class="bi bi-check2" aria-hidden="true"></i> Guardar cambios
                                    </button>
                                    <span class="g-cart-autosave">
                                        <i class="bi bi-cloud-check" aria-hidden="true"></i>
                                        Las cantidades también se guardan solas.
                                    </span>
                                </div>
                                <form action="{{ url('/carrito/limpiar') }}" method="POST" id="clear-cart-form">
                                    @csrf
                                    <button type="button" class="g-cart-clear" onclick="confirmarLimpiarCarrito()">
                                        <i class="bi bi-trash3" aria-hidden="true"></i> Vaciar carrito
                                    </button>
                                </form>
                            </div>
                        @endif
                    </div>

                                        @if ($carritos->isNotEmpty())
                                            <form action="{{ route('web.pedidos.pago_manual') }}" method="POST" id="checkout-form" class="mt-4" novalidate
                                                data-initial-step="{{ $errors->hasAny(['name', 'dni', 'telefono', 'privacy_consent']) ? 1 : ($errors->hasAny(['direccion_id', 'modalidad_entrega']) ? 2 : ($errors->any() ? 3 : 1)) }}">
                                                @csrf
                                                <input type="hidden" name="total" id="hidden-total" value="{{ $totalInicial }}">
                                                <input type="hidden" name="shipping_option" id="hidden-shipping-option" value="{{ $costoEnvioInicial === 0 ? 'free' : 'standard' }}">

                                                <div class="card border-0 shadow-sm rounded-4">
                                                    <div class="card-body p-3 p-lg-4">
                                                        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
                                                            <div>
                                                                <h2 class="h4 fw-bold mb-1">Finaliza tu compra</h2>
                                                                <p class="text-muted small mb-0">Completa los pasos para confirmar tu pedido. Los campos marcados con <span class="text-danger">*</span> son obligatorios.</p>
                                                            </div>
                                                            <span class="badge rounded-pill bg-light text-primary border px-3 py-2">
                                                                Paso <span id="checkout-current-step">1</span> de 3
                                                            </span>
                                                        </div>
                                                        <nav aria-label="Pasos de compra" class="checkout-stepper mb-4">
                                                            <ol class="list-unstyled d-flex align-items-start mb-0">
                                                                @foreach (['Identificación', 'Entrega', 'Pago'] as $stepIndex => $stepLabel)
                                                                    <li class="checkout-step flex-fill text-center"
                                                                        data-checkout-indicator="{{ $stepIndex + 1 }}">
                                                                        <button type="button" class="checkout-step-trigger" data-checkout-go="{{ $stepIndex + 1 }}"
                                                                            aria-label="Ir al paso {{ $stepIndex + 1 }}: {{ $stepLabel }}"
                                                                            aria-controls="checkout-panel-{{ $stepIndex + 1 }}">
                                                                            <span class="checkout-step-number d-flex align-items-center justify-content-center rounded-circle mx-auto mb-2">
                                                                                {{ $stepIndex + 1 }}
                                                                            </span>
                                                                            <span class="checkout-step-label small">{{ $stepLabel }}</span>
                                                                        </button>
                                                                    </li>
                                                                @endforeach
                                                            </ol>
                                                        </nav>

                                                        <section class="checkout-step-panel" id="checkout-panel-1" data-checkout-panel="1" aria-labelledby="checkout-step-1-title">
                                                            <h2 id="checkout-step-1-title" class="h4 fw-bold mb-2">
                                                                <i class="bi bi-person-check me-2 text-primary"></i>Tus datos personales
                                                            </h2>
                                                            <p class="text-muted mb-4">Completa tus datos para registrar correctamente el pedido.</p>
                                                            <div class="alert alert-light border small mb-3" role="note">
                                                                Usaremos estos datos para identificar tu pedido y coordinar la entrega. Tu DNI se guarda cifrado.
                                                            </div>
                                                            <div class="row g-3">
                                                                <div class="col-12">
                                                                    <label for="checkout-customer-name" class="form-label fw-semibold">Nombre completo <span class="text-danger">*</span></label>
                                                                    <input type="text" class="form-control @error('name') is-invalid @enderror"
                                                                        id="checkout-customer-name" name="name" value="{{ old('name', auth()->user()->name) }}"
                                                                        maxlength="255" autocomplete="name" required>
                                                                    @error('name')
                                                                        <div class="invalid-feedback">{{ $message }}</div>
                                                                    @enderror
                                                                </div>
                                                                <div class="col-12">
                                                                    <label for="checkout-customer-email" class="form-label fw-semibold">Correo de tu cuenta (Gmail/Google)</label>
                                                                    <input type="email" class="form-control bg-light" id="checkout-customer-email"
                                                                        value="{{ auth()->user()->email }}" readonly aria-describedby="checkout-email-help">
                                                                    <div id="checkout-email-help" class="form-text">
                                                                        @if (auth()->user()->google_id)
                                                                            <i class="bi bi-patch-check-fill text-success me-1"></i>Correo verificado con Google.
                                                                        @else
                                                                            @if (filled(config('services.google.client_id')) && filled(config('services.google.client_secret')))
                                                                                <a href="{{ route('auth.google.link') }}">Verifica este correo con Google</a> antes de pagar.
                                                                            @else
                                                                                Google aún no está configurado para verificar el correo.
                                                                            @endif
                                                                        @endif
                                                                    </div>
                                                                </div>
                                                                <div class="col-md-6">
                                                                    <label for="checkout-customer-dni" class="form-label fw-semibold">DNI <span class="text-danger">*</span></label>
                                                                    <input type="text" class="form-control @error('dni') is-invalid @enderror"
                                                                        id="checkout-customer-dni" name="dni" value="{{ old('dni', auth()->user()->dni) }}"
                                                                        inputmode="numeric" autocomplete="off" pattern="[0-9]{8}" minlength="8" maxlength="8"
                                                                        aria-describedby="checkout-dni-help" required>
                                                                    <div id="checkout-dni-help" class="form-text">Ingresa los 8 números de tu DNI.</div>
                                                                    @error('dni')
                                                                        <div class="invalid-feedback">{{ $message }}</div>
                                                                    @enderror
                                                                </div>
                                                                <div class="col-md-6">
                                                                    <label for="checkout-customer-phone" class="form-label fw-semibold">Teléfono <span class="text-danger">*</span></label>
                                                                    <input type="tel" class="form-control @error('telefono') is-invalid @enderror"
                                                                        id="checkout-customer-phone" name="telefono" value="{{ old('telefono', auth()->user()->telefono) }}"
                                                                        inputmode="tel" autocomplete="tel" pattern="[+]?[0-9\s\(\)\.\-]{7,20}" maxlength="20" required>
                                                                    @error('telefono')
                                                                        <div class="invalid-feedback">{{ $message }}</div>
                                                                    @enderror
                                                                </div>
                                                                <div class="col-12">
                                                                    <div class="form-check border rounded-3 p-3 ps-5 @error('privacy_consent') border-danger @enderror">
                                                                        <input class="form-check-input" type="checkbox" id="checkout-privacy-consent"
                                                                            name="privacy_consent" value="1" @checked(old('privacy_consent')) required>
                                                                        <label class="form-check-label" for="checkout-privacy-consent">
                                                                            He leído y acepto la
                                                                            <a href="{{ route('web.privacidad') }}" target="_blank" rel="noopener noreferrer">Política de Privacidad</a>.
                                                                            Entiendo que mis datos se usarán para registrar el pedido y coordinar la entrega. <span class="text-danger">*</span>
                                                                        </label>
                                                                        @error('privacy_consent')
                                                                            <div class="text-danger small mt-2">{{ $message }}</div>
                                                                        @enderror
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            <div class="d-flex justify-content-end mt-4">
                                                                <button type="button" class="btn btn-primary rounded-pill px-4" data-checkout-next="2">
                                                                    Continuar con la entrega <i class="bi bi-arrow-right ms-1"></i>
                                                                </button>
                                                            </div>
                                                        </section>

                                                        <section class="checkout-step-panel" id="checkout-panel-2" data-checkout-panel="2" aria-labelledby="checkout-step-2-title" hidden>
                                                            <h2 id="checkout-step-2-title" class="h4 fw-bold mb-2">
                                                                <i class="bi bi-truck me-2 text-primary"></i>Entrega de Productos
                                                            </h2>
                                                            <div class="border rounded-4 p-3 p-lg-4 mb-4">
                                                            <p class="fw-semibold mb-3">¿Cómo deseas que te entreguen los productos?</p>

                                                            <div class="mb-4">
                                                                <span class="summary-label fw-semibold d-block mb-2">Modalidad de entrega</span>
                                                                <div class="btn-group w-100 flex-column flex-lg-row" role="group" aria-label="Modalidad de entrega">
                                                                    <input type="radio" class="btn-check" name="modalidad_entrega" id="modalidad_envio" value="envio" @checked($modalidadActual === 'envio') autocomplete="off">
                                                                    <label class="btn btn-outline-primary py-2" for="modalidad_envio">
                                                                        <i class="bi bi-truck me-1"></i> Envío a domicilio
                                                                    </label>
                                                                    <input type="radio" class="btn-check" name="modalidad_entrega" id="modalidad_recojo" value="recojo" @checked($modalidadActual === 'recojo') autocomplete="off">
                                                                    <label class="btn btn-outline-primary py-2" for="modalidad_recojo">
                                                                        <i class="bi bi-shop me-1"></i> Recojo en tienda
                                                                    </label>
                                                                    <input type="radio" class="btn-check" name="modalidad_entrega" id="modalidad_provincia" value="provincia" @checked($modalidadActual === 'provincia') autocomplete="off">
                                                                    <label class="btn btn-outline-primary py-2" for="modalidad_provincia">
                                                                        <i class="bi bi-box-seam me-1"></i> Envío a provincias
                                                                    </label>
                                                                </div>
                                                                <div class="alert alert-info small mb-0 mt-3 {{ $modalidadActual === 'provincia' ? '' : 'd-none' }}" id="provincia-note" role="note">
                                                                    Despachamos a tu ciudad por agencia. <strong>El costo del envío a provincias se coordina contigo por WhatsApp o teléfono antes de despachar</strong>, y el pedido no sale hasta que lo confirmes.
                                                                </div>
                                                                @php
                                                                    // Con un solo punto no tiene sentido obligar a elegir; con
                                                                    // varios, el cliente decide dónde pasa a retirar.
                                                                    $puntosDisponibles = $puntosRecojo ?? collect();
                                                                    $puntoElegido = (int) old('punto_recojo_id', $puntosDisponibles->count() === 1 ? $puntosDisponibles->first()->id : 0);
                                                                @endphp
                                                                <div class="mt-3 {{ $modalidadActual === 'recojo' ? '' : 'd-none' }}" id="punto-recojo-card">
                                                                    @if ($puntosDisponibles->isNotEmpty())
                                                                        <span class="summary-label fw-semibold d-block mb-2">Elige dónde recoger</span>
                                                                        <div class="row g-2">
                                                                            @foreach ($puntosDisponibles as $punto)
                                                                                <div class="col-12 col-lg-6">
                                                                                    <input class="btn-check" type="radio" name="punto_recojo_id" id="punto-recojo-{{ $punto->id }}" value="{{ $punto->id }}" autocomplete="off" @checked($puntoElegido === $punto->id)>
                                                                                    <label class="punto-recojo-opcion w-100" for="punto-recojo-{{ $punto->id }}">
                                                                                        <span class="fw-semibold d-block">{{ $punto->nombre }}</span>
                                                                                        <span class="small d-block">{{ $punto->direccion }}</span>
                                                                                        @if (filled($punto->horario))
                                                                                            <span class="small text-muted d-block"><i class="bi bi-clock me-1"></i>{{ $punto->horario }}</span>
                                                                                        @endif
                                                                                        @if (filled($punto->telefono))
                                                                                            <span class="small text-muted d-block"><i class="bi bi-telephone me-1"></i>{{ $punto->telefono }}</span>
                                                                                        @endif
                                                                                    </label>
                                                                                </div>
                                                                            @endforeach
                                                                        </div>
                                                                        <div class="alert alert-warning py-2 px-3 mt-2 d-none" id="punto-recojo-alert" role="alert">
                                                                            Elige en qué punto quieres recoger tu pedido.
                                                                        </div>
                                                                        <p class="mb-0 small text-muted mt-2">Te avisamos por WhatsApp cuando el pedido esté listo para recoger.</p>
                                                                    @else
                                                                        <div class="punto-recojo-card p-3">
                                                                            <p class="fw-semibold mb-1"><i class="bi bi-shop me-1 text-primary"></i>Punto de recojo</p>
                                                                            @if (filled($ajuste?->direccion))
                                                                                <p class="mb-1">{{ $ajuste->direccion }}</p>
                                                                            @else
                                                                                <p class="mb-1 text-warning">La tienda aún no publicó su dirección de recojo: te la confirmamos por WhatsApp al aprobar el pedido.</p>
                                                                            @endif
                                                                            <p class="mb-0 small text-muted">Te avisamos por WhatsApp cuando el pedido esté listo para recoger.</p>
                                                                            @if (filled($ajuste?->telefono))
                                                                                <p class="mb-0 small text-muted">Teléfono de contacto: <strong>{{ $ajuste->telefono }}</strong>.</p>
                                                                            @endif
                                                                        </div>
                                                                    @endif
                                                                </div>
                                                            </div>

                                                            <div class="mb-4" id="shipping-address-container" style="display: {{ $modalidadActual === 'recojo' ? 'none' : 'block' }}">
                                                                <label for="direccion_id_select" class="summary-label fw-semibold mb-2 d-block">Dirección de destino <span class="text-danger">*</span></label>
                                                                <p class="small text-muted mb-2" id="shipping-address-hint">Usamos esta dirección para despachar tu pedido.</p>
                                                                <div id="no-addresses-alert" class="alert alert-warning py-2 px-3 my-2 {{ $direcciones->isNotEmpty() ? 'd-none' : '' }}" style="font-size: 13px;">
                                                                    <i class="bi bi-exclamation-triangle-fill me-1"></i>Registra y selecciona una dirección antes de continuar.
                                                                </div>
                                                                <select class="form-select {{ $direcciones->isEmpty() ? 'd-none' : '' }}" name="direccion_id" id="direccion_id_select" @required($direcciones->isNotEmpty())>
                                                                    @foreach ($direcciones as $dir)
                                                                        <option value="{{ $dir->id }}" data-provincia-id="{{ $dir->provincia_id }}" {{ $dir->es_predeterminada ? 'selected' : '' }}>
                                                                            [{{ $dir->alias }}] {{ $dir->direccion_exacta }} ({{ $dir->provincia->nombre }}, {{ $dir->departamento->nombre }})
                                                                        </option>
                                                                    @endforeach
                                                                </select>
                                                                <button type="button" class="btn btn-sm btn-outline-primary rounded-pill px-3 mt-2"
                                                                    data-bs-toggle="modal" data-bs-target="#expressAddressModal">
                                                                    <i class="bi bi-plus-circle me-1"></i>Agregar dirección
                                                                </button>

                                                                @php
                                                                    // Las agencias dependen de la ciudad del pedido, así que
                                                                    // se pintan todas y el JS deja visible solo el grupo de
                                                                    // la provincia elegida. Sin agencias en esa provincia,
                                                                    // el bloque entero no existe y el flujo no cambia nada.
                                                                    $agenciasDisponibles = $agencias ?? collect();
                                                                    $agenciaElegida = (int) old('agencia_provincia_id', 0);
                                                                @endphp
                                                                @if ($agenciasDisponibles->isNotEmpty())
                                                                    <div class="mt-3 d-none" id="agencias-provincia-card">
                                                                        @foreach ($agenciasDisponibles as $provinciaId => $agenciasDeProvincia)
                                                                            <div class="agencias-provincia-grupo d-none" data-provincia-id="{{ $provinciaId }}">
                                                                                <span class="summary-label fw-semibold d-block mb-2">Elige dónde recoger en {{ $agenciasDeProvincia->first()->provincia?->nombre }}</span>
                                                                                <div class="row g-2">
                                                                                    @foreach ($agenciasDeProvincia as $agencia)
                                                                                        <div class="col-12 col-lg-6">
                                                                                            <input class="btn-check" type="radio" name="agencia_provincia_id" id="agencia-{{ $agencia->id }}" value="{{ $agencia->id }}" autocomplete="off" @checked($agenciaElegida === $agencia->id)>
                                                                                            <label class="punto-recojo-opcion w-100" for="agencia-{{ $agencia->id }}">
                                                                                                <span class="fw-semibold d-block">{{ $agencia->nombre }}</span>
                                                                                                <span class="small d-block">{{ $agencia->direccion }}</span>
                                                                                                @if (filled($agencia->horario))
                                                                                                    <span class="small text-muted d-block"><i class="bi bi-clock me-1"></i>{{ $agencia->horario }}</span>
                                                                                                @endif
                                                                                                @if (filled($agencia->telefono))
                                                                                                    <span class="small text-muted d-block"><i class="bi bi-telephone me-1"></i>{{ $agencia->telefono }}</span>
                                                                                                @endif
                                                                                            </label>
                                                                                        </div>
                                                                                    @endforeach
                                                                                </div>
                                                                            </div>
                                                                        @endforeach
                                                                        <div class="alert alert-warning py-2 px-3 mt-2 d-none" id="agencia-provincia-alert" role="alert">
                                                                            Elige en qué agencia de tu ciudad quieres recoger tu pedido.
                                                                        </div>
                                                                        <div class="alert alert-warning py-2 px-3 mt-2 d-none" id="agencia-provincia-nota" role="note">
                                                                            Aún no tenemos agencia en la ciudad de esa dirección: el despacho se coordina contigo por WhatsApp antes de enviar.
                                                                        </div>
                                                                        <p class="mb-0 small text-muted mt-2">La agencia guarda tu pedido hasta que pases a buscarlo; te avisamos por WhatsApp cuando llegue.</p>
                                                                    </div>
                                                                @endif
                                                            </div>

                                                            <div class="mb-3" id="shipping-options-container" style="display: {{ $modalidadActual === 'envio' ? 'block' : 'none' }}">
                                                                <span class="summary-label fw-semibold d-block mb-2">Velocidad de entrega</span>
                                                                <div class="form-check border rounded-3 p-3 ps-5 mb-2">
                                                                    <input class="form-check-input shipping-radio" type="radio" name="shipping_selector" id="standard_shipping" value="standard" checked>
                                                                    <label class="form-check-label w-100" for="standard_shipping">
                                                                        <span class="fw-semibold">Estándar</span>
                                                                        <span class="float-end" data-shipping-standard-price>{{ $envioGratis ? 'Gratis' : \App\Models\Ajuste::formatear_precio($reglasEnvio['costo_estandar']) }}</span>
                                                                        <small class="d-block text-muted {{ $envioGratis ? 'd-none' : '' }}" data-shipping-umbral-hint>
                                                                            Se elimina el cargo en pedidos desde {{ \App\Models\Ajuste::formatear_precio($reglasEnvio['umbral_gratis']) }}.
                                                                        </small>
                                                                    </label>
                                                                </div>
                                                                <div class="form-check border rounded-3 p-3 ps-5">
                                                                    <input class="form-check-input shipping-radio" type="radio" name="shipping_selector" id="express_shipping" value="express">
                                                                    <label class="form-check-label w-100" for="express_shipping">
                                                                        <span class="fw-semibold">Urgente</span>
                                                                        <span class="float-end">{{ \App\Models\Ajuste::formatear_precio($reglasEnvio['costo_express']) }}</span>
                                                                        <small class="d-block text-muted">Cuesta lo mismo aunque tu pedido tenga envío gratis.</small>
                                                                    </label>
                                                                </div>
                                                            </div>

                                                            </div>

                                                            <div class="d-flex flex-wrap justify-content-between gap-2 mt-4">
                                                                <button type="button" class="btn btn-link text-muted px-0" data-checkout-previous="1">
                                                                    <i class="bi bi-arrow-left me-1"></i>Volver a mis datos
                                                                </button>
                                                                <button type="button" class="btn btn-primary rounded-pill px-4" data-checkout-next="3">
                                                                    Continuar con el pago <i class="bi bi-arrow-right ms-1"></i>
                                                                </button>
                                                            </div>
                                                        </section>

                                                        <section class="checkout-step-panel" id="checkout-panel-3" data-checkout-panel="3" aria-labelledby="checkout-step-3-title" hidden>
                                                            <h2 id="checkout-step-3-title" class="h4 fw-bold mb-2">
                                                                <i class="bi bi-credit-card me-2 text-primary"></i>Forma de Pago
                                                            </h2>
                                                            <div class="border rounded-4 p-3 p-lg-4 mb-4">
                                                            <p class="fw-semibold mb-2">¿Cómo quieres pagar tu pedido?</p>
                                                            <p class="text-muted small mb-4">Con Yape o Plin transfieres el total exacto y adjuntas tu comprobante; la tienda lo revisa antes de confirmar. Con tarjeta, Mercado Pago confirma el pago al instante.</p>
                                                            @php $metodoSolicitado = old('metodo_pago'); @endphp
                                                            <div class="mb-4" id="payment-options-container">
                                                                <span class="summary-label fw-semibold d-block mb-2" id="payment-method-label">Método de pago</span>
                                                                @if ($yapeDisponible || $plinDisponible || $tarjetaDisponible)
                                                                    <button type="button" class="checkout-method-picker" id="payment-method-picker"
                                                                        data-bs-toggle="modal" data-bs-target="#modal-metodos-pago"
                                                                        aria-haspopup="dialog" aria-controls="modal-metodos-pago">
                                                                        <span class="checkout-method-picker__icon" data-picker-icon aria-hidden="true"><i class="bi bi-wallet2"></i></span>
                                                                        <span class="checkout-method-picker__text">
                                                                            <strong class="checkout-method-picker__title" data-picker-title>Elige un medio de pago</strong>
                                                                            <small class="checkout-method-picker__hint" data-picker-hint>Tarjeta, código QR o Yape</small>
                                                                        </span>
                                                                        <span class="checkout-method-picker__action" data-picker-action>Elegir</span>
                                                                    </button>
                                                                @else
                                                                    {{-- Sin métodos configurados la ventana quedaría vacía:
                                                                         se dice por qué en el mismo lugar donde se elige. --}}
                                                                    <div class="alert alert-warning py-2 px-3 mb-0 small" role="status">
                                                                        Yape y Plin estarán disponibles cuando la tienda configure los datos de cada método.
                                                                    </div>
                                                                @endif
                                                            </div>

                                                                @php $hayMetodoSeleccionado = in_array($metodoSolicitado, ['yape', 'plin', 'bcp', 'tarjeta'], true); @endphp
                                                                @if ($yapeDisponible || $plinDisponible || $bcpDisponible || $tarjetaDisponible)
                                                                    <div class="checkout-qr-preview p-3 p-md-4 mt-3 @if (! $hayMetodoSeleccionado) d-none @endif" id="checkout-qr-preview" aria-live="polite">
                                                                        <div class="checkout-account__actions">
                                                                            <span class="summary-label fw-semibold mb-0">Datos del pago</span>
                                                                            <button type="button" class="btn btn-sm btn-link text-muted p-0" data-checkout-change-method>
                                                                                <i class="bi bi-arrow-left-short me-1" aria-hidden="true"></i>Cambiar método
                                                                            </button>
                                                                        </div>
                                                                        @if ($yapeDisponible)
                                                                            <div data-checkout-qr="yape" @if ($metodoSolicitado !== 'yape') hidden @endif>
                                                                                <div class="checkout-quickpay">
                                                                                    <div>
                                                                                        <p class="checkout-quickpay__titulo">Paga más rápido</p>
                                                                                        <p class="checkout-quickpay__guia">Escanea este QR con la billetera electrónica de tu banco, desde la app <strong>Yape</strong>.</p>
                                                                                        <div class="checkout-quickpay__divide">o verifica estos datos antes de pagar</div>
                                                                                        <p class="checkout-quickpay__aviso">
                                                                                            <i class="bi bi-shield-check" aria-hidden="true"></i>
                                                                                            <span>Comprueba que el nombre del destinatario coincida antes de pagar.</span>
                                                                                        </p>
                                                                                        <div class="checkout-bcp-data">
                                                                                            <div class="checkout-bcp-data__row">
                                                                                                <span class="text-start">
                                                                                                    <span class="checkout-bcp-data__label">Total exacto</span>
                                                                                                    <strong class="checkout-qr-preview__amount"><span data-checkout-qr-total>{{ \App\Models\Ajuste::formatear_precio($totalInicial) }}</span></strong>
                                                                                                </span>
                                                                                                <button type="button" class="btn btn-sm btn-outline-primary" data-copy-payment-number="{{ number_format((float) $totalInicial, (int) ($ajuste->moneda_decimales ?? 2), '.', '') }}" data-copy-total data-copy-name="El importe" data-copy-note="Escríbelo tal cual en Yape: si cambia un céntimo, el pago no cuadra." aria-label="Copiar el importe exacto de Yape">
                                                                                                    <i class="bi bi-copy" aria-hidden="true"></i><span class="visually-hidden">Copiar importe</span>
                                                                                                </button>
                                                                                            </div>
                                                                                            <div class="checkout-bcp-data__row">
                                                                                                <span class="text-start">
                                                                                                    <span class="checkout-bcp-data__label">Titular</span>
                                                                                                    <strong>{{ $ajuste->web_yape_titular }}</strong>
                                                                                                </span>
                                                                                            </div>
                                                                                        </div>
                                                                                    </div>
                                                                                    <div class="checkout-quickpay__qr">
                                                                                        <button type="button" class="checkout-qr-preview__image-wrap" data-bs-toggle="modal" data-bs-target="#qrZoomModal" data-qr-zoom="Yape" aria-label="Agrandar el código QR de Yape">
                                                                                            <img class="checkout-qr-preview__image" src="{{ asset('storage/'.ltrim($ajuste->web_yape_qr, '/')) }}"
                                                                                                alt="Código QR oficial de Yape de {{ $ajuste->web_yape_titular }}" loading="eager">
                                                                                        </button>
                                                                                        <p class="small text-muted mb-0">¿No lo puedes leer? Tócalo para agrandarlo.</p>
                                                                                    </div>
                                                                                </div>
                                                                            </div>
                                                                        @endif
                                                                        @if ($plinDisponible)
                                                                            <div data-checkout-qr="plin" @if ($metodoSolicitado !== 'plin') hidden @endif>
                                                                                <div class="checkout-quickpay">
                                                                                    <div>
                                                                                        <p class="checkout-quickpay__titulo">Paga más rápido</p>
                                                                                        <p class="checkout-quickpay__guia">Escanea este QR con la billetera electrónica de tu banco, desde la app <strong>Plin</strong>.</p>
                                                                                        <div class="checkout-quickpay__divide">o verifica estos datos antes de pagar</div>
                                                                                        <p class="checkout-quickpay__aviso">
                                                                                            <i class="bi bi-shield-check" aria-hidden="true"></i>
                                                                                            <span>Comprueba que el nombre del destinatario coincida antes de pagar.</span>
                                                                                        </p>
                                                                                        <div class="checkout-bcp-data">
                                                                                            <div class="checkout-bcp-data__row">
                                                                                                <span class="text-start">
                                                                                                    <span class="checkout-bcp-data__label">Total exacto</span>
                                                                                                    <strong class="checkout-qr-preview__amount"><span data-checkout-qr-total>{{ \App\Models\Ajuste::formatear_precio($totalInicial) }}</span></strong>
                                                                                                </span>
                                                                                                <button type="button" class="btn btn-sm btn-outline-primary" data-copy-payment-number="{{ number_format((float) $totalInicial, (int) ($ajuste->moneda_decimales ?? 2), '.', '') }}" data-copy-total data-copy-name="El importe" data-copy-note="Escríbelo tal cual en Plin: si cambia un céntimo, el pago no cuadra." aria-label="Copiar el importe exacto de Plin">
                                                                                                    <i class="bi bi-copy" aria-hidden="true"></i><span class="visually-hidden">Copiar importe</span>
                                                                                                </button>
                                                                                            </div>
                                                                                            <div class="checkout-bcp-data__row">
                                                                                                <span class="text-start">
                                                                                                    <span class="checkout-bcp-data__label">Titular</span>
                                                                                                    <strong>{{ $ajuste->web_plin_titular }}</strong>
                                                                                                </span>
                                                                                            </div>
                                                                                        </div>
                                                                                    </div>
                                                                                    <div class="checkout-quickpay__qr">
                                                                                        <button type="button" class="checkout-qr-preview__image-wrap" data-bs-toggle="modal" data-bs-target="#qrZoomModal" data-qr-zoom="Plin" aria-label="Agrandar el código QR de Plin">
                                                                                            <img class="checkout-qr-preview__image" src="{{ asset('storage/'.ltrim($ajuste->web_plin_qr, '/')) }}"
                                                                                                alt="Código QR oficial de Plin de {{ $ajuste->web_plin_titular }}" loading="eager">
                                                                                        </button>
                                                                                        <p class="small text-muted mb-0">¿No lo puedes leer? Tócalo para agrandarlo.</p>
                                                                                    </div>
                                                                                </div>
                                                                            </div>
                                                                        @endif
                                                                        @if ($bcpDisponible)
                                                                            <div data-checkout-qr="bcp" @if ($metodoSolicitado !== 'bcp') hidden @endif>
                                                                                <div class="checkout-account">
                                                                                    <div class="text-center text-md-start">
                                                                                        <h3 class="h6 fw-bold mb-2">Datos para tu transferencia BCP</h3>
                                                                                        <p class="small text-muted mb-3">Copia la CCI, transfiere el total exacto desde tu app bancaria y verifica que el titular mostrado coincida antes de confirmar.</p>
                                                                                    </div>
                                                                                    <div class="checkout-account__data">
                                                                                        <div class="checkout-bcp-data">
                                                                                            <div class="checkout-bcp-data__row">
                                                                                                <span class="text-start">
                                                                                                    <span class="checkout-bcp-data__label">Total exacto</span>
                                                                                                    <strong class="checkout-qr-preview__amount"><span data-checkout-qr-total>{{ \App\Models\Ajuste::formatear_precio($totalInicial) }}</span></strong>
                                                                                                </span>
                                                                                                <button type="button" class="btn btn-sm btn-outline-primary" data-copy-payment-number="{{ number_format((float) $totalInicial, (int) ($ajuste->moneda_decimales ?? 2), '.', '') }}" data-copy-total data-copy-name="El importe" data-copy-note="Escríbelo tal cual al transferir: si cambia un céntimo, el pago no cuadra." aria-label="Copiar el importe exacto de la transferencia BCP">
                                                                                                    <i class="bi bi-copy" aria-hidden="true"></i><span class="visually-hidden">Copiar importe</span>
                                                                                                </button>
                                                                                            </div>
                                                                                            <div class="checkout-bcp-data__row">
                                                                                                <span class="text-start">
                                                                                                    <span class="checkout-bcp-data__label">CCI (código interbancario)</span>
                                                                                                    <strong>{{ $ajuste->web_bcp_cci }}</strong>
                                                                                                </span>
                                                                                                <button type="button" class="btn btn-sm btn-outline-primary" data-copy-payment-number="{{ $ajuste->web_bcp_cci }}" aria-label="Copiar CCI de BCP">
                                                                                                    <i class="bi bi-copy" aria-hidden="true"></i><span class="visually-hidden">Copiar CCI</span>
                                                                                                </button>
                                                                                            </div>
                                                                                            @if (filled($ajuste->web_bcp_cuenta))
                                                                                                <div class="checkout-bcp-data__row">
                                                                                                    <span class="text-start">
                                                                                                        <span class="checkout-bcp-data__label">Cuenta corriente BCP (opcional)</span>
                                                                                                        <strong>{{ $ajuste->web_bcp_cuenta }}</strong>
                                                                                                    </span>
                                                                                                    <button type="button" class="btn btn-sm btn-outline-primary" data-copy-payment-number="{{ $ajuste->web_bcp_cuenta }}" aria-label="Copiar número de cuenta BCP">
                                                                                                        <i class="bi bi-copy" aria-hidden="true"></i><span class="visually-hidden">Copiar cuenta</span>
                                                                                                    </button>
                                                                                                </div>
                                                                                            @endif
                                                                                            <div class="checkout-bcp-data__row">
                                                                                                <span class="text-start">
                                                                                                    <span class="checkout-bcp-data__label">Titular de la cuenta</span>
                                                                                                    <strong>{{ $ajuste->web_bcp_titular }}</strong>
                                                                                                </span>
                                                                                            </div>
                                                                                        </div>
                                                                                    </div>
                                                                                </div>
                                                                            </div>
                                                                        @endif
                                                                        @if ($tarjetaDisponible)
                                                                            {{-- Misma forma que la maqueta de «Datos de la tarjeta»: lo que no va
                            dentro son los cuatro campos. Un número escrito en esta página
                            obliga a la tienda a auditar PCI DSS; el dato lo pide Mercado Pago. --}}
                                                                            <div data-checkout-qr="tarjeta" @if ($metodoSolicitado !== 'tarjeta') hidden @endif>
                                                                                <div class="checkout-quickpay">
                                                                                    <div>
                                                                                        <p class="checkout-quickpay__titulo">Paga con tu tarjeta</p>
                                                                                        <p class="checkout-quickpay__guia">
                                                                                            @if ($tarjetaEnTienda)
                                                                                                Escribe tu tarjeta aquí mismo: Mercado Pago la procesa dentro de la tienda, sin salir de esta pantalla.
                                                                                            @else
                                                                                                Dos caminos, el mismo pago: el botón te lleva a Mercado Pago en esta misma pestaña; «Pagar desde mi celular» guarda el pedido y te da el QR de ese mismo pago.
                                                                                            @endif
                                                                                        </p>
                                                                                        <div class="checkout-quickpay__divide">{{ $tarjetaEnTienda ? 'tus datos van cifrados a Mercado Pago' : 'aquí no se escribe ninguna tarjeta' }}</div>
                                                                                        <p class="checkout-quickpay__aviso">
                                                                                            <i class="bi bi-shield-check" aria-hidden="true"></i>
                                                                                            <span>La tienda no ve ni guarda el número, la fecha ni el CVV de tu tarjeta.</span>
                                                                                        </p>
                                                                                        <div class="checkout-bcp-data">
                                                                                            <div class="checkout-bcp-data__row">
                                                                                                <span class="text-start">
                                                                                                    <span class="checkout-bcp-data__label">Total a pagar</span>
                                                                                                    <strong class="checkout-qr-preview__amount"><span data-checkout-qr-total>{{ \App\Models\Ajuste::formatear_precio($totalInicial) }}</span></strong>
                                                                                                </span>
                                                                                            </div>
                                                                                            <div class="checkout-bcp-data__row">
                                                                                                <span class="text-start">
                                                                                                    <span class="checkout-bcp-data__label">Si la tarjeta es rechazada</span>
                                                                                                    <strong>Tu pedido queda guardado y puedes reintentar</strong>
                                                                                                    <small class="text-muted d-block">También puedes elegir Yape o Plin desde tu pedido.</small>
                                                                                                </span>
                                                                                            </div>
                                                                                        </div>
                                                                                    </div>
                                                                                    <div class="checkout-quickpay__aside">
                                                                                        <span class="checkout-quickpay__marca">
                                                                                            <img src="{{ asset('assets/img/pagos/visa.svg') }}" width="31" height="22" alt="Visa">
                                                                                            <span><i class="bi bi-lock-fill me-1" aria-hidden="true"></i>Pago seguro</span>
                                                                                        </span>
                                                                                        <div class="checkout-quickpay__pasos">
                                                                                            <p class="checkout-quickpay__pasos-titulo">Así funciona el pago con tarjeta</p>
                                                                                            <p class="checkout-quickpay__paso"><b>1</b><span>Eliges pagar en esta pestaña o con el QR de tu celular.</span></p>
                                                                                            @if ($tarjetaEnTienda)
                                                                                                <p class="checkout-quickpay__paso"><b>2</b><span>Escribes tu tarjeta en el formulario seguro de Mercado Pago, aquí mismo.</span></p>
                                                                                            @else
                                                                                                <p class="checkout-quickpay__paso"><b>2</b><span>Allí escribes tu tarjeta: la tienda nunca la ve.</span></p>
                                                                                            @endif
                                                                                            <p class="checkout-quickpay__paso"><b>3</b><span>Si la aprueban, el pedido queda pagado al instante.</span></p>
                                                                                        </div>
                                                                                        <ul class="checkout-quickpay__specs">
                                                                                            <li><i class="bi bi-credit-card" aria-hidden="true"></i><span>Tarjetas aceptadas: <strong>Visa</strong> débito o crédito.</span></li>
                                                                                            <li><i class="bi bi-clock" aria-hidden="true"></i><span>El enlace vence en {{ $mercadoPago->minutosVigencia() }} minutos y puedes abrirlo otra vez desde tu pedido.</span></li>
                                                                                        </ul>
                                                                                        <button type="submit" class="checkout-quickpay__celular" name="pago_celular" value="1">
                                                                                            <i class="bi bi-qr-code-scan" aria-hidden="true"></i>
                                                                                            <span>
                                                                                                <strong>Pagar desde mi celular</strong>
                                                                                                <small>Guardo el pedido y te doy el QR de este mismo pago</small>
                                                                                            </span>
                                                                                        </button>
                                                                                    </div>
                                                                                </div>
                                                                            </div>
                                                                        @endif
                                                                        <div class="small mt-2 text-center" id="payment-copy-feedback" role="status" aria-live="polite"></div>
                                                                    </div>
                                                                @endif
                                                                @php $notaManualVisible = in_array($metodoSolicitado, ['yape', 'plin', 'bcp'], true); @endphp
                                                                    <div class="alert alert-info small mb-0 @if (! $notaManualVisible) d-none @endif" id="payment-note-manual" role="status">
                                                                        <i class="bi bi-info-circle me-1"></i>El pago no se confirma automáticamente: al crear el pedido adjuntas tu comprobante y la tienda verifica el abono antes de marcarlo como pagado.
                                                                    </div>
                                                                    @if ($tarjetaDisponible)
                                                                        <div class="alert alert-info small mb-0 @if ($metodoSolicitado !== 'tarjeta') d-none @endif" id="payment-note-tarjeta" role="status">
                                                                            @if ($tarjetaEnTienda)
                                                                                <i class="bi bi-credit-card me-1"></i>Al continuar escribes tu tarjeta aquí mismo, en el formulario seguro de Mercado Pago. La tienda no ve ni guarda sus datos y el pedido queda pagado en el momento.
                                                                            @else
                                                                                <i class="bi bi-credit-card me-1"></i>Al crear el pedido te llevamos a Mercado Pago, en esta pestaña o por QR desde tu celular: allí escribes tu tarjeta. La tienda no ve ni guarda sus datos y el pedido queda pagado en el momento.
                                                                            @endif
                                                                        </div>
                                                                    @endif
                                                            </div>

                                                            @php
                                                                // Sin JavaScript este botón es el único que ve el cliente: su texto
                                                                // debe prometer exactamente lo que pasará con el método elegido.
                                                                $montoCheckout = \App\Models\Ajuste::formatear_precio($totalInicial);
                                                                $etiquetaInicial = match ($metodoSolicitado) {
                                                                    'tarjeta' => '<i class="bi bi-credit-card me-1"></i>Pagar ' . $montoCheckout . ' con tarjeta',
                                                                    'yape', 'plin', 'bcp' => '<i class="bi bi-arrow-repeat me-1"></i>Crear pedido y subir comprobante',
                                                                    default => '<i class="bi bi-lock me-1"></i>Elige un método de pago',
                                                                };
                                                            @endphp
                                                            <div class="checkout-cta">
                                                                <div class="d-flex align-items-center justify-content-between px-1 pb-1">
                                                                    <span class="small text-muted mb-0">Total a pagar</span>
                                                                    <span class="fw-bold"><span data-checkout-qr-total>{{ \App\Models\Ajuste::formatear_precio($totalInicial) }}</span></span>
                                                                </div>
                                                                <button type="submit" class="btn btn-primary btn-lg rounded-pill py-3 w-100" id="btn-submit-checkout"
                                                                    @disabled(! $yapeDisponible && ! $plinDisponible && ! $bcpDisponible && ! $tarjetaDisponible)>
                                                                    {!! $etiquetaInicial !!}
                                                                </button>
                                                                {{-- Mientras el servidor crea el pedido el botón queda bloqueado.
                    Sin esta salida el cliente se queda mirando un spinner sin poder
                    hacer nada. Cancelar solo navega: si el pedido llegó a crearse,
                    aparece en su lista y el pago se retoma desde ahí. --}}
                                                                <div class="checkout-cta__espera d-none" id="checkout-espera" role="status" aria-live="polite">
                                                                    <div class="checkout-cta__barra" aria-hidden="true"><span></span></div>
                                                                    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
                                                                        <p class="small mb-0">Estamos creando tu pedido y abriendo el pago…</p>
                                                                        <a class="btn btn-sm btn-outline-secondary rounded-pill" href="{{ route('web.dashboard') }}">
                                                                            Cancelar y ver mis pedidos
                                                                        </a>
                                                                    </div>
                                                                    <p class="small text-muted mb-0 mt-1" data-checkout-espera-larga hidden>
                                                                        Está tardando más de lo normal. Puedes cancelar sin riesgo: no se te cobra dos veces y el pago se retoma desde el pedido.
                                                                    </p>
                                                                </div>
                                                            </div>
                                                            <button type="button" class="btn btn-link text-muted w-100 mt-1" data-checkout-previous="2">
                                                                <i class="bi bi-arrow-left me-1" aria-hidden="true"></i>Volver a mis datos
                                                            </button>

                                                            <p class="small text-muted text-center mt-3 mb-0">
                                                                <i class="bi bi-shield-lock me-1" aria-hidden="true"></i>Tu pedido queda guardado aunque un pago falle; nunca se te cobra dos veces.
                                                                @if ($tarjetaDisponible)
                                                                    <span class="d-inline-flex align-items-center justify-content-center gap-2 mt-2">
                                                                        <img src="{{ asset('assets/img/pagos/visa.svg') }}" width="31" height="22" alt="Visa">
                                                                        <span>Procesado por Mercado Pago</span>
                                                                    </span>
                                                                @endif
                                                            </p>
                                                        </section>
                                                    </div>
                                                </div>
                                            </form>
                                        @endif
                </div>

                @if ($carritos->isNotEmpty())
                <div class="col-lg-4" data-aos="fade-up" data-aos-delay="300">
                    <aside class="cart-summary g-cart-summary" aria-label="Resumen del pedido">
                        <div class="g-cart-summary-head">
                            <div>
                                <span class="g-cart-summary-eyebrow">Tu pedido</span>
                                <h4 class="summary-title">Resumen del pedido</h4>
                            </div>
                            <span class="g-cart-summary-count">
                                {{ $carritos->sum('cantidad') }}
                                {{ \Illuminate\Support\Str::plural('artículo', $carritos->sum('cantidad')) }}
                            </span>
                        </div>

                        <div class="g-cart-mini-list" aria-live="polite" aria-label="Productos del pedido">
                            @foreach ($carritos as $carrito)
                                @php
                                    $precioResumen = $carrito->producto->en_oferta && $carrito->producto->precio_oferta > 0
                                        ? $carrito->producto->precio_oferta
                                        : $carrito->producto->precio_venta;
                                @endphp
                                <div class="g-cart-mini">
                                    <img src="{{ $carrito->producto->imagen_principal_url }}"
                                        alt="{{ $carrito->producto->nombre }}" width="46" height="46"
                                        loading="lazy" class="g-cart-mini-img"
                                        onerror="this.onerror=null;this.src='{{ asset('storage/productos/default.webp') }}';">
                                    <div class="g-cart-mini-info">
                                        <span class="g-cart-mini-name">{{ $carrito->producto->nombre }}</span>
                                        <small>
                                            <span data-cart-summary-quantity="{{ $carrito->id }}">{{ $carrito->cantidad }}</span>
                                            × {{ \App\Models\Ajuste::formatear_precio($precioResumen) }}
                                        </small>
                                    </div>
                                    <span class="g-cart-mini-total" data-cart-summary-total="{{ $carrito->id }}">
                                        {{ \App\Models\Ajuste::formatear_precio($precioResumen * $carrito->cantidad) }}
                                    </span>
                                </div>
                            @endforeach
                        </div>

                        <div class="coupon-box g-cart-coupon">
                            <label for="codigo-cupon" class="form-label fw-semibold small mb-1">
                                <i class="bi bi-ticket-perforated me-1 text-primary"></i>Cupón de descuento
                            </label>
                            <form action="{{ route('web.carrito.cupon') }}" method="POST" class="d-flex gap-2" id="form-cupon">
                                @csrf
                                <input type="text" name="codigo" id="codigo-cupon" value="{{ old('codigo_cupon', $cupon['codigo'] ?? '') }}"
                                    class="form-control form-control-sm text-uppercase @error('codigo') is-invalid @enderror"
                                    placeholder="Escribe el código" maxlength="30" autocomplete="off" aria-describedby="cupon-help">
                                <button type="submit" class="btn btn-sm btn-outline-primary px-3 text-nowrap">
                                    {{ ($cupon['codigo'] ?? null) ? 'Actualizar' : 'Aplicar' }}
                                </button>
                                @if (! empty($cupon['codigo']))
                                    <button type="submit" class="btn btn-sm btn-link text-muted text-nowrap px-1" name="quitar" value="1"
                                        aria-label="Quitar el cupón aplicado" title="Quitar el cupón">
                                        <i class="bi bi-x-lg"></i>
                                    </button>
                                @endif
                            </form>
                            <div id="cupon-help" class="form-text">
                                El descuento aplica solo a los productos; el envío se calcula igual.
                            </div>
                        </div>

                        @php
                            $umbralEnvio = (float) $reglasEnvio['umbral_gratis'];
                            $progresoEnvio = $umbralEnvio > 0
                                ? min(100, (int) round($subtotal_acumulado / $umbralEnvio * 100))
                                : 100;
                        @endphp

                        <div class="g-cart-ship {{ $envioGratis ? 'is-done' : '' }}" id="g-cart-ship">
                            <div class="g-cart-ship-head">
                                <span class="g-cart-ship-label">
                                    <i class="bi bi-truck" aria-hidden="true"></i>
                                    <strong id="g-cart-ship-title">
                                        {{ $envioGratis ? '¡Envío gratis desbloqueado!' : 'Envío gratis' }}
                                    </strong>
                                </span>
                                <span class="g-cart-ship-amount" id="g-cart-ship-amount">
                                    {{ $envioGratis
                                        ? 'Meta alcanzada'
                                        : \App\Models\Ajuste::formatear_precio($subtotal_acumulado) . ' / ' . \App\Models\Ajuste::formatear_precio($umbralEnvio) }}
                                </span>
                            </div>
                            <div class="g-cart-ship-track" id="g-cart-ship-bar" role="progressbar"
                                aria-label="Progreso hacia el envío gratis" aria-valuemin="0" aria-valuemax="100"
                                aria-valuenow="{{ $progresoEnvio }}">
                                <span style="width: {{ $progresoEnvio }}%"></span>
                            </div>
                            <p class="g-cart-ship-hint {{ $envioGratis ? 'text-success' : 'text-muted' }}" id="shipping-message" aria-live="polite">
                                @if ($envioGratis)
                                    <i class="bi bi-check-circle me-1"></i>Tu pedido tiene envío estándar gratis.
                                @else
                                    Te faltan {{ \App\Models\Ajuste::formatear_precio($reglasEnvio['umbral_gratis'] - $subtotal_acumulado) }} para obtener envío gratis.
                                @endif
                            </p>
                        </div>

                        <div class="g-cart-totals" aria-live="polite">
                            <div class="summary-item {{ $subtotal_original_acumulado > $subtotal_acumulado ? '' : 'd-none' }}" id="subtotal-original-container">
                                <span class="summary-label">Antes de descuentos</span>
                                <span class="summary-value" id="subtotal-original-display">{{ \App\Models\Ajuste::formatear_precio($subtotal_original_acumulado) }}</span>
                            </div>

                            <div class="summary-item text-danger {{ $subtotal_original_acumulado > $subtotal_acumulado ? '' : 'd-none' }}" id="descuento-container">
                                <span class="summary-label text-danger">Descuento aplicado</span>
                                <span class="summary-value text-danger" id="descuento-display">-{{ \App\Models\Ajuste::formatear_precio($subtotal_original_acumulado - $subtotal_acumulado) }}</span>
                            </div>

                            <div class="summary-item g-cart-subtotal">
                                <span class="summary-label">Subtotal</span>
                                <span class="summary-value" id="subtotal-display">{{ \App\Models\Ajuste::formatear_precio($subtotal_acumulado) }}</span>
                            </div>

                            <div class="summary-item {{ empty($cupon['codigo']) || $descuentoCupon <= 0 ? 'd-none' : '' }}" id="cupon-container">
                                <span class="summary-label text-danger" id="cupon-label">
                                    Cupón (<span id="cupon-codigo">{{ $cupon['codigo'] }}</span>
                                    <span id="cupon-porcentaje">−{{ $porcentajeCupon }}%</span>)
                                </span>
                                <span class="summary-value text-danger" id="cupon-display">-{{ \App\Models\Ajuste::formatear_precio($descuentoCupon) }}</span>
                            </div>

                            <div class="summary-item shipping-item">
                                <span class="summary-label" id="shipping-label">{{ $modalidadActual === 'recojo' ? 'Recojo en tienda' : ($modalidadActual === 'provincia' ? 'Envío a provincias' : 'Costo de envío') }}</span>
                                <span class="summary-value" id="shipping-display">
                                    {{ $modalidadActual === 'provincia' ? 'Se coordina' : ($costoEnvioInicial === 0 ? 'Gratis' : \App\Models\Ajuste::formatear_precio($costoEnvioInicial)) }}
                                </span>
                            </div>

                            <p class="small mb-0 text-muted d-none" id="delivery-point-line" aria-live="polite"></p>

                            <div class="summary-total g-cart-total">
                                <span class="summary-label">Total a pagar</span>
                                <span class="summary-value" id="total-display">{{ \App\Models\Ajuste::formatear_precio($totalInicial) }}</span>
                            </div>

                            <p class="g-cart-tax-note">
                                <i class="bi bi-info-circle" aria-hidden="true"></i>
                                Precio final en soles (S/). El envío se confirma en el siguiente paso.
                            </p>
                        </div>

                        <div class="g-cart-saving {{ $subtotal_original_acumulado > $subtotal_acumulado ? '' : 'd-none' }}" id="saving-badge">
                            <i class="bi bi-gift" aria-hidden="true"></i>
                            <span>¡Has ahorrado <strong id="saving-badge-amount">{{ \App\Models\Ajuste::formatear_precio($subtotal_original_acumulado - $subtotal_acumulado) }}</strong> en este pedido!</span>
                        </div>

                        <div class="g-cart-payments">
                            <span class="g-cart-payments-title">Métodos de pago aceptados</span>
                            <div class="g-cart-payment-icons">
                                @if ($yapeDisponible)
                                    <span class="g-cart-pay" data-payment-badge="yape">Yape</span>
                                @endif
                                @if ($bcpDisponible)
                                    <span class="g-cart-pay" data-payment-badge="bcp">BCP</span>
                                @endif
                                @if ($plinDisponible)
                                    <span class="g-cart-pay" data-payment-badge="plin">Plin</span>
                                @endif
                                @if ($tarjetaDisponible)
                                    <span class="g-cart-pay" data-payment-badge="tarjeta">Visa</span>
                                @endif
                                @if (! $yapeDisponible && ! $plinDisponible && ! $bcpDisponible && ! $tarjetaDisponible)
                                    <span class="small text-muted">Aún no configurados</span>
                                @endif
                            </div>
                        </div>

                        <div class="g-cart-secure">
                            <i class="bi bi-shield-check" aria-hidden="true"></i>
                            <div>
                                <strong>Compra segura</strong>
                                <span>Tu información está protegida y solo se usa para procesar tu pedido.</span>
                            </div>
                        </div>

                        <a href="{{ url('/') }}" class="g-cart-continue">
                            <i class="bi bi-arrow-left" aria-hidden="true"></i> Continuar comprando
                        </a>
                    </aside>
                </div>
                @endif
            </div>

        </div>

        {{-- Ventana de medios de pago. Vive fuera del <form> y fuera de los
             paneles del checkout porque ésos se ocultan con display:none !important
             y se llevarían el modal puesto; los radios y los botones de pago se
             enganchan al formulario con el atributo form=. --}}
        <div class="modal fade" id="modal-metodos-pago" tabindex="-1"
            aria-labelledby="modal-metodos-pago-label" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable checkout-payment-modal__dialog">
                <div class="modal-content checkout-payment-modal">
                    <div class="modal-header">
                        <span class="checkout-payment-modal__handle" aria-hidden="true"></span>
                        <span class="checkout-payment-modal__brand" aria-hidden="true">
                            <i class="bi bi-shield-lock"></i>
                        </span>
                        <span class="checkout-payment-modal__head-text">
                            <span class="checkout-payment-modal__head-title" id="modal-metodos-pago-label">Medio de pago</span>
                            <span class="checkout-payment-modal__head-sub"><i class="bi bi-patch-check-fill" aria-hidden="true"></i>Compra protegida · GALY</span>
                        </span>
                        <span class="checkout-payment-modal__head-actions">
                            <button type="button" class="checkout-payment-modal__ayuda" data-modal-ayuda
                                aria-label="Ver cómo se confirma cada pago">?</button>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                        </span>
                    </div>

                    <div class="modal-body" data-modal-paso="1">
                        <span class="checkout-payment-modal__paso">Paso 1 de 2</span>
                        <h3 class="checkout-payment-modal__titulo">Elige un medio de pago</h3>
                        <p class="checkout-payment-modal__guia">Puedes cambiarlo antes de confirmar el pedido.</p>
                        @error('metodo_pago')
                            <p class="small text-danger mb-3"><i class="bi bi-exclamation-circle me-1" aria-hidden="true"></i>{{ $message }}</p>
                        @enderror
                        @php
                            // El monto solo no identifica la compra: con dos carritos
                            // abiertos el cliente quiere leer de qué se trata antes de
                            // salir de la tienda.
                            $piezasCheckout = (int) $carritos->sum('cantidad');
                            $primeroCheckout = $carritos->first()?->producto?->nombre;
                            $otrosCheckout = max(0, $carritos->count() - 1);

                            $resumenCheckout = $piezasCheckout.' '.Str::plural('artículo', $piezasCheckout);

                            if (filled($primeroCheckout)) {
                                $resumenCheckout .= ' · '.$primeroCheckout;

                                if ($otrosCheckout > 0) {
                                    $resumenCheckout .= ' y '.$otrosCheckout.' '.Str::plural('producto', $otrosCheckout).' más';
                                }
                            }
                        @endphp
                        <div class="checkout-payment-modal__monto">
                            <span class="checkout-payment-modal__monto-label">Total a pagar</span>
                            <strong data-checkout-qr-total>{{ \App\Models\Ajuste::formatear_precio($totalInicial) }}</strong>
                            <span class="checkout-payment-modal__monto-resumen">{{ $resumenCheckout }}</span>
                            <div class="checkout-payment-modal__monto-tags">
                                <span><i class="bi bi-shield-check" aria-hidden="true"></i>Pago protegido</span>
                                <span><i class="bi bi-cash-coin" aria-hidden="true"></i>Precio final en soles</span>
                            </div>
                        </div>
                        <div class="checkout-payment-modal__seccion">
                            <span>Medios disponibles</span>
                            <span><i class="bi bi-lock-fill me-1" aria-hidden="true"></i>Pago cifrado</span>
                            <a href="{{ route('web.pago_seguro') }}" class="checkout-payment-modal__ayuda-link" target="_blank" rel="noopener">
                                <i class="bi bi-shield-check me-1" aria-hidden="true"></i>¿Cómo protegemos tu pago?
                            </a>
                        </div>
                        <div class="checkout-payment-modal__opciones" role="radiogroup" aria-labelledby="modal-metodos-pago-label">
                            @php
                                // El orden es el de la referencia: la tarjeta abre porque es la
                                // que confirma el pago al instante. Las marcas de la franja son
                                // las que Mercado Pago tiene aprobadas en esta cuenta. Si alguna
                                // de las nuevas no está habilitada en tu cuenta de MP, bórrala
                                // de la lista para no anunciar un método que no se puede usar.
                                // Las marcas que se anuncian son las que Mercado Pago tiene
                                // activas para esta cuenta: se consultan a la API y se
                                // cachean 1 h. Si la API no responde, se usan las cuatro
                                // históricas (Visa, Mastercard, Amex, Diners) como respaldo.
                                $marcasTarjeta = app(\App\Services\MercadoPagoService::class)->marcasTarjetaActivas();

                                $metodosModal = [
                                    'tarjeta' => [
                                        'logo' => 'assets/img/pagos/visa.svg',
                                        'icono' => 'bi-credit-card-2-front',
                                        'titulo' => 'Tarjeta de crédito y/o débito',
                                        'hint' => 'Realiza tu pago en cuotas o directo',
                                        'marcas' => $marcasTarjeta,
                                    ],
                                    'plin' => [
                                        'logo' => 'assets/img/pagos/plin.png',
                                        'icono' => 'bi-qr-code',
                                        'titulo' => 'Código QR',
                                        'hint' => 'Usando tu billetera electrónica, con Plin',
                                        'marcas' => [
                                            ['img' => 'assets/img/pagos/plin.png', 'alt' => 'Plin'],
                                        ],
                                    ],
                                    'yape' => [
                                        'logo' => 'assets/img/pagos/yape.png',
                                        'icono' => 'bi-phone',
                                        'titulo' => 'Pago con Yape',
                                        'hint' => 'Usando tu billetera electrónica',
                                        'marcas' => [
                                            ['img' => 'assets/img/pagos/yape.png', 'alt' => 'Yape'],
                                        ],
                                    ],
                                ];

                                // Un método solo se ofrece si su destino de pago está
                                // completo; si ninguno lo está, la lista se ve vacía y el
                                // cliente no sabe si es su conexión. Eso se dice.
                                $metodosVisibles = array_values(array_filter(
                                    array_keys($metodosModal),
                                    fn ($valor) => $valor === 'tarjeta'
                                        ? $tarjetaDisponible
                                        : \App\Models\Ajuste::motivoParaOcultarPagoWeb($ajuste, $valor) === null
                                ));
                            @endphp
                            @foreach ($metodosModal as $valor => $metodo)
                                @if (in_array($valor, $metodosVisibles, true))
                                    <label class="checkout-method-card" for="pago_{{ $valor }}">
                                        <input class="form-check-input" type="radio" name="metodo_pago" form="checkout-form"
                                            id="pago_{{ $valor }}" value="{{ $valor }}"
                                            data-logo="{{ asset($metodo['logo']) }}"
                                            data-titulo="{{ $metodo['titulo'] }}"
                                            data-hint="{{ $metodo['hint'] }}"
                                            data-marcas="{{ implode('|', array_column($metodo['marcas'], 'alt')) }}"
                                            @checked(old('metodo_pago') === $valor)>
                                        <span class="checkout-method-card__icon" aria-hidden="true">
                                            <i class="bi {{ $metodo['icono'] }}"></i>
                                        </span>
                                        <span class="checkout-method-card__text">
                                            <span class="checkout-method-card__title">
                                                {{ $metodo['titulo'] }}
                                                @if ($valor === 'tarjeta')
                                                    <span class="checkout-method-card__badge checkout-method-card__badge--rapido">El más rápido</span>
                                                @endif
                                            </span>
                                            <span class="checkout-method-card__hint">{{ $metodo['hint'] }}</span>
                                            <span class="checkout-method-card__marcas">
                                                @foreach ($metodo['marcas'] as $marca)
                                                    @if (isset($marca['img']))
                                                        <img class="checkout-method-card__marca" src="{{ asset($marca['img']) }}"
                                                            alt="{{ $marca['alt'] }}">
                                                    @else
                                                        <span class="checkout-method-card__marca--texto"
                                                            style="background: {{ $marca['fondo'] }}">{{ $marca['texto'] }}</span>
                                                    @endif
                                                @endforeach
                                            </span>
                                        </span>
                                    </label>
                                @elseif ($valor === 'tarjeta' && $tarjetaProximamente)
                                    <div class="checkout-method-card checkout-method-card--proximamente" aria-disabled="true">
                                        <span class="checkout-method-card__icon checkout-method-card__icon--marca" aria-hidden="true">
                                            <img src="{{ asset($metodo['logo']) }}" alt="" width="30" height="30">
                                        </span>
                                        <span class="checkout-method-card__text">
                                            <span class="checkout-method-card__title">
                                                Tarjeta de crédito y/o débito
                                                <span class="checkout-method-card__badge">Próximamente</span>
                                            </span>
                                            <span class="checkout-method-card__hint">Estamos activando este medio de pago</span>
                                        </span>
                                    </div>
                                @endif
                            @endforeach
                            @if ($metodosVisibles === [] && ! $tarjetaProximamente)
                                {{-- Sin credenciales la lista quedaba en blanco y el
                                     cliente leía una pantalla rota. --}}
                                <div class="checkout-payment-modal__vacio">
                                    <i class="bi bi-wallet2" aria-hidden="true"></i>
                                    <p class="mb-0">
                                        Todavía no hay ningún medio de pago activo en esta tienda.
                                        @if (filled($ajuste?->telefono))
                                            Escríbenos al <strong>{{ $ajuste->telefono }}</strong> y coordinamos tu pago por ahí; tu carrito queda guardado.
                                        @else
                                            Te avisamos cuando estén activos; tu carrito queda guardado.
                                        @endif
                                    </p>
                                </div>
                            @endif
                        </div>
                        <ul class="checkout-payment-modal__sellos" aria-label="Garantías de tu compra">
                            <li>
                                <i class="bi bi-lock-fill" aria-hidden="true"></i>
                                <span><strong>Pago cifrado</strong><small>Conexión segura de punta a punta</small></span>
                            </li>
                            <li>
                                <i class="bi bi-credit-card-2-front" aria-hidden="true"></i>
                                <span><strong>No guardamos tu tarjeta</strong><small>El cobro lo procesa la pasarela</small></span>
                            </li>
                            <li>
                                <i class="bi bi-arrow-repeat" aria-hidden="true"></i>
                                <span><strong>Reintento sin doble cobro</strong><small>Tu pedido queda guardado</small></span>
                            </li>
                        </ul>
                        @php
                            // wa.me exige el número en formato internacional y sin el
                            // cero de larga distancia: con «0998765432» el enlace abría
                            // un chat inválido. Se limpia y se antepone 51 al móvil.
                            $soporteTelefono = trim((string) ($ajuste?->telefono ?? ''));
                            $soporteDigitos = ltrim(preg_replace('/\D+/', '', $soporteTelefono), '0');
                            if (strlen($soporteDigitos) === 9) {
                                $soporteDigitos = '51'.$soporteDigitos;
                            }
                        @endphp
                        @if (filled($soporteTelefono) && strlen($soporteDigitos) >= 10)
                            <a class="checkout-payment-modal__soporte" href="https://wa.me/{{ $soporteDigitos }}"
                                target="_blank" rel="noopener noreferrer">
                                <i class="bi bi-whatsapp" aria-hidden="true"></i>
                                <span>¿Problemas para pagar? Escríbenos al <strong>{{ $soporteTelefono }}</strong></span>
                            </a>
                        @endif
                    </div>

                    <div class="modal-body" data-modal-paso="2" hidden>
                        <button type="button" class="btn btn-sm btn-link text-muted p-0 mb-2" data-modal-volver>
                            <i class="bi bi-arrow-left-short" aria-hidden="true"></i>Volver a elegir
                        </button>
                        <div class="checkout-payment-modal__detalle checkout-paycard">
                            <div class="checkout-paycard__head">
                                <div class="checkout-paycard__head-texto">
                                    <span class="checkout-payment-modal__paso">Paso 2 de 2</span>
                                    <h3 class="checkout-payment-modal__titulo checkout-paycard__titulo" data-detalle-titulo tabindex="-1">Paga más rápido</h3>
                                    <p class="checkout-payment-modal__guia checkout-paycard__guia" data-detalle-guia></p>
                                </div>
                                <figure class="checkout-payment-modal__qr checkout-paycard__qr" data-detalle-qr hidden>
                                    <img data-detalle-qr-imagen src="" alt="">
                                    <figcaption>Escanea este QR con la app de tu banco</figcaption>
                                </figure>
                            </div>
                            <div class="checkout-payment-modal__monto">
                                <span class="checkout-payment-modal__monto-label">Total exacto</span>
                                <strong data-checkout-qr-total>{{ \App\Models\Ajuste::formatear_precio($totalInicial) }}</strong>
                                <span class="checkout-payment-modal__monto-resumen">El comprobante llega al correo de tu pedido.</span>
                            </div>
                            @if ($tarjetaDisponible)
                                {{-- El QR de tarjeta no se puede imprimir antes del
                                     pedido: el enlace real de Mercado Pago nace con
                                     la preferencia. Por eso el bloque convive con el
                                     formulario y solo crea el pedido cuando el
                                     cliente pide el QR con este botón. Cuando el QR
                                     está listo el formulario se retira para no
                                     cobrar dos veces el mismo pedido. --}}
                                <div class="checkout-paycard__atajo checkout-paycard__atajo--tarjeta"
                                    data-detalle-tarjeta-qr hidden aria-live="polite">
                                    <div class="checkout-paycard__atajo-texto">
                                        <span class="checkout-paycard__atajo-eyebrow">Paga más rápido</span>
                                        <p class="checkout-paycard__atajo-titulo" data-tarjeta-qr-titulo>Escanea el QR y paga con <strong>tarjeta</strong></p>
                                        <p class="checkout-paycard__atajo-guia" data-tarjeta-qr-guia>Genera el QR de este mismo pago y escanéalo con la app de tu banco.</p>
                                        <button type="button" class="btn btn-sm btn-outline-success" data-tarjeta-qr-generar>
                                            <i class="bi bi-qr-code" aria-hidden="true"></i> Generar QR de pago
                                        </button>
                                        <a class="btn btn-sm btn-outline-success" data-tarjeta-qr-enlace href="#"
                                            target="_blank" rel="noopener noreferrer" hidden>
                                            <i class="bi bi-box-arrow-up-right" aria-hidden="true"></i> Abrir Mercado Pago
                                        </a>
                                        <a class="checkout-paycard__atajo-pedido" data-tarjeta-qr-pedido href="#" hidden>
                                            <i class="bi bi-receipt" aria-hidden="true"></i> Ver mi pedido
                                        </a>
                                    </div>
                                    <button type="button" class="checkout-paycard__atajo-qr" data-tarjeta-qr-zoom
                                        data-qr-zoom="Mercado Pago" data-bs-toggle="modal" data-bs-target="#qrZoomModal"
                                        aria-label="Agrandar el código QR de Mercado Pago para pagar con tarjeta" disabled>
                                        <span class="checkout-paycard__atajo-qr-icono" data-tarjeta-qr-icono aria-hidden="true">
                                            <i class="bi bi-qr-code"></i>
                                        </span>
                                        <span class="checkout-paycard__atajo-spinner" data-tarjeta-qr-spinner role="status"
                                            aria-label="Generando código QR" hidden>
                                            <span class="spinner-border spinner-border-sm" aria-hidden="true"></span>
                                        </span>
                                        <img data-tarjeta-qr-imagen src=""
                                            alt="Código QR de Mercado Pago para pagar con tarjeta" hidden>
                                    </button>
                                </div>
                                <p class="checkout-paycard__atajo-aviso" data-tarjeta-qr-aviso hidden role="alert"></p>
                            @endif
                            <div class="checkout-paycard__divider" data-detalle-divider hidden aria-hidden="true"><span>{{ $tarjetaEnTienda ? 'Completa tus datos' : 'Así pagas con tarjeta' }}</span></div>
                            {{-- Mercado Pago pone este recordatorio antes de la
                                 pantalla de la tarjeta porque es el motivo real de
                                 un rechazo: la débito viene sin compras por
                                 internet activadas. --}}
                            <p class="checkout-payment-modal__recordatorio checkout-paycard__recordatorio" data-detalle-recordatorio hidden>
                                <i class="bi bi-bank" aria-hidden="true"></i>
                                <span data-detalle-recordatorio-texto></span>
                            </p>
                            @if ($tarjetaEnTienda)
                                {{-- Checkout Bricks: Mercado Pago pinta aquí su
                                     formulario real dentro de un iframe. La tienda
                                     nunca recibe el número ni el CVV, así que este
                                     contenedor (y su script) solo existe cuando la
                                     Public Key está configurada. --}}
                                <div class="checkout-paycard__brick" data-detalle-campos hidden>
                                    <p class="checkout-paycard__cobro">
                                        <i class="bi bi-credit-card-2-front" aria-hidden="true"></i>
                                        <span>Vas a pagar <strong data-checkout-qr-total>{{ \App\Models\Ajuste::formatear_precio($totalInicial) }}</strong> con tarjeta.</span>
                                    </p>
                                    <div id="cardPaymentBrickModal" data-brick-contenedor></div>
                                    <p class="checkout-paycard__brick-aviso checkout-paycard__brick-aviso--error" data-brick-error hidden role="alert"></p>
                                    <p class="checkout-paycard__brick-aviso">
                                        <i class="bi bi-shield-lock" aria-hidden="true"></i>
                                        <span>El número y el CVV los escribe Mercado Pago en su formulario seguro: la tienda no los ve ni los guarda.</span>
                                    </p>
                                </div>
                            @endif
                            <p class="checkout-payment-modal__nota" data-detalle-nota></p>
                            <p class="checkout-payment-modal__nota" data-detalle-marcas hidden></p>
                            <ol class="checkout-payment-modal__ayuda-lista" data-detalle-pasos hidden></ol>
                        </div>
                    </div>

                    {{-- Lo que responde el interrogante del encabezado. Existe porque
                         la pregunta real que llega por WhatsApp es «ya pagué, ¿y mi
                         pedido?»: la tarjeta confirma sola y Yape/Plin no. --}}
                    <div class="modal-body" data-modal-paso="3" hidden>
                        <button type="button" class="btn btn-sm btn-link text-muted p-0 mb-2" data-ayuda-volver>
                            <i class="bi bi-arrow-left-short" aria-hidden="true"></i>Volver a los medios de pago
                        </button>
                        <h3 class="checkout-payment-modal__titulo" tabindex="-1">¿Cómo se confirma mi pago?</h3>
                        <ul class="checkout-payment-modal__ayuda-lista">
                            <li><strong>Tarjeta:</strong> pagas en Mercado Pago y el pedido queda confirmado al instante. La tienda no ve ni guarda tus datos.</li>
                            <li><strong>Yape o Plin:</strong> transfieres desde tu app y adjuntas el comprobante; la tienda revisa el abono antes de marcar el pedido como pagado.</li>
                            <li><strong>Si un pago falla</strong> no se cobra dos veces: el pedido queda guardado y puedes reintentar con otro medio.</li>
                        </ul>
                        <p class="checkout-payment-modal__nota">La verificación manual puede demorar unos minutos; te avisamos por el correo del pedido.</p>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn checkout-btn-pagar rounded-pill w-100 py-3 fw-bold" data-modal-continuar disabled>
                            <i class="bi bi-arrow-right me-1" aria-hidden="true"></i><span data-modal-continuar-texto>Continuar</span>
                        </button>
                        <button type="submit" class="btn checkout-btn-pagar rounded-pill w-100 py-3 fw-bold" form="checkout-form" data-modal-pagar hidden>
                            <i class="bi bi-lock me-1" aria-hidden="true"></i><span data-modal-pagar-texto>Pagar</span>
                        </button>
                        {{-- La duda final no es el monto, es «¿quién cobra esto y mis
                             datos están bien?». Se responde en la línea siguiente al
                             botón, que es donde el cliente deja de leer. --}}
                        <p class="checkout-payment-modal__confianza" data-modal-confianza>
                            <i class="bi bi-shield-check" aria-hidden="true"></i>
                            <span data-modal-confianza-texto>Pago procesado por Mercado Pago o tu billetera electrónica; la tienda no guarda tus datos de tarjeta.</span>
                        </p>
                        {{-- Texto legal de la referencia: enlaza la política real
                             en lugar de prometer un tratamiento que no existe. --}}
                        <p class="checkout-payment-modal__privacidad" data-detalle-privacidad hidden>
                            <i class="bi bi-shield-lock" aria-hidden="true"></i>
                            <span>Infórmate sobre el <a href="{{ route('web.privacidad') }}" target="_blank" rel="noopener noreferrer">tratamiento de tus datos personales aquí</a>.</span>
                        </p>
                        {{-- Las marcas cierran la pantalla, bajo el botón, igual que
                             en Mercado Pago: es el último vistazo antes de pagar. --}}
                        <div class="checkout-payment-modal__franja" data-detalle-chips hidden>
                            @foreach ($metodosModal['tarjeta']['marcas'] ?? [] as $marca)
                                @if (isset($marca['img']))
                                    <img class="checkout-method-card__marca" src="{{ asset($marca['img']) }}"
                                        alt="{{ $marca['alt'] }}">
                                @else
                                    <span class="checkout-method-card__marca--texto"
                                        style="background: {{ $marca['fondo'] }}">{{ $marca['texto'] }}</span>
                                @endif
                            @endforeach
                            <span class="checkout-payment-modal__pci" title="Pago seguro con estándar PCI DSS">
                                <i class="bi bi-shield-fill-check" aria-hidden="true"></i> PCI DSS
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal: QR agrandado, para escanearlo con el celular desde la pantalla -->
        <div class="modal fade" id="qrZoomModal" tabindex="-1" aria-labelledby="qrZoomModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content text-start">
                    <div class="modal-header">
                        <h5 class="modal-title fw-bold" id="qrZoomModalLabel">Código QR</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                    </div>
                    <div class="modal-body text-center">
                        <img id="qr-zoom-image" class="qr-zoom__image" src="" alt="">
                        <p class="small text-muted mt-3 mb-0">Acerca el celular a esta pantalla para escanearlo.</p>
                    </div>
                    <div class="modal-footer justify-content-center border-0">
                        <a id="qr-zoom-open" class="btn btn-sm btn-outline-primary rounded-pill px-3" href="#" target="_blank" rel="noopener">
                            <i class="bi bi-arrows-fullscreen me-1" aria-hidden="true"></i>Abrir imagen completa
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal: Register Express Address -->
        <div class="modal fade" id="expressAddressModal" tabindex="-1" aria-labelledby="expressAddressModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content text-start">
                    <div class="modal-header">
                        <h5 class="modal-title fw-bold" id="expressAddressModalLabel">Agregar nueva dirección</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form id="expressAddressForm">
                        @csrf
                        <div class="modal-body">
                            <div class="row g-3">
                                <div class="col-12">
                                    <label for="alias_checkout" class="form-label fw-bold">Alias (ej: Casa, Trabajo)</label>
                                    <input type="text" name="alias" id="alias_checkout" class="form-control" placeholder="ej: Casa, Oficina" required>
                                </div>
                                <div class="col-12">
                                    <label for="pais_id_checkout" class="form-label fw-bold">País</label>
                                    <select name="pais_id" id="pais_id_checkout" class="form-select" onchange="loadDepartmentsCheckout()" required>
                                        <option value="">-- Seleccionar país --</option>
                                        @foreach ($paises as $p)
                                            <option value="{{ $p->id }}">{{ $p->nombre }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label for="departamento_id_checkout" class="form-label fw-bold">Departamento</label>
                                    <select name="departamento_id" id="departamento_id_checkout" class="form-select" onchange="loadProvinciasCheckout()" required disabled>
                                        <option value="">-- Primero elija país --</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label for="provincia_id_checkout" class="form-label fw-bold">Provincia</label>
                                    <select name="provincia_id" id="provincia_id_checkout" class="form-select" required disabled>
                                        <option value="">-- Primero elija depto --</option>
                                    </select>
                                </div>
                                <div class="col-12">
                                    <label for="direccion_exacta_checkout" class="form-label fw-bold">Dirección Exacta</label>
                                    <input type="text" name="direccion_exacta" id="direccion_exacta_checkout" class="form-control" placeholder="ej: Av. Larco 123" required>
                                </div>
                                <div class="col-12">
                                    <label for="referencia_checkout" class="form-label fw-bold">Referencia (opcional)</label>
                                    <input type="text" name="referencia" id="referencia_checkout" class="form-control" placeholder="ej: Portón negro, frente al parque">
                                </div>
                                <div class="col-md-6">
                                    <label for="codigo_postal_checkout" class="form-label fw-bold">Código Postal (opcional)</label>
                                    <input type="text" name="codigo_postal" id="codigo_postal_checkout" class="form-control">
                                </div>
                                <div class="col-md-6">
                                    <label for="telefono_checkout" class="form-label fw-bold">Teléfono (opcional)</label>
                                    <input type="text" name="telefono" id="telefono_checkout" class="form-control">
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                            <button type="submit" class="btn btn-primary" id="saveExpressAddressBtn">Guardar y Seleccionar</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <!-- Formularios de eliminación individuales fuera del formulario principal para evitar anidación de forms -->
        @foreach ($carritos as $carrito)
            <form action="{{ route('web.carrito.destroy', $carrito->id) }}" method="post"
                id="delete-form-{{ $carrito->id }}" style="display: none;">
                @csrf
                @method('DELETE')
            </form>
        @endforeach

        @if ($tarjetaEnTienda)
            {{-- Solo con Checkout Bricks encendido. El número de tarjeta lo
                 captura Mercado Pago dentro de su iframe: la tienda nunca lo ve. --}}
            <script src="https://sdk.mercadopago.com/js/v2"></script>
        @endif

        <script>
            function loadDepartmentsCheckout() {
                const paisId = document.getElementById('pais_id_checkout').value;
                const deptSelect = document.getElementById('departamento_id_checkout');
                const provSelect = document.getElementById('provincia_id_checkout');

                deptSelect.innerHTML = '<option value="">Cargando...</option>';
                deptSelect.disabled = true;
                provSelect.innerHTML = '<option value="">-- Primero elija depto --</option>';
                provSelect.disabled = true;

                if (!paisId) {
                    deptSelect.innerHTML = '<option value="">-- Primero elija país --</option>';
                    return;
                }

                fetch(`{{ url('/api/paises') }}/${paisId}/departamentos`)
                    .then(response => response.json())
                    .then(data => {
                        deptSelect.innerHTML = '<option value="">-- Seleccionar departamento --</option>';
                        data.forEach(dept => {
                            const option = document.createElement('option');
                            option.value = dept.id;
                            option.textContent = dept.nombre;
                            deptSelect.appendChild(option);
                        });
                        deptSelect.disabled = false;
                    })
                    .catch(error => {
                        console.error('Error:', error);
                    });
            }

            function loadProvinciasCheckout() {
                const deptId = document.getElementById('departamento_id_checkout').value;
                const provSelect = document.getElementById('provincia_id_checkout');

                provSelect.innerHTML = '<option value="">Cargando...</option>';
                provSelect.disabled = true;

                if (!deptId) {
                    provSelect.innerHTML = '<option value="">-- Primero elija depto --</option>';
                    return;
                }

                fetch(`{{ url('/api/departamentos') }}/${deptId}/provincias`)
                    .then(response => response.json())
                    .then(data => {
                        provSelect.innerHTML = '<option value="">-- Seleccionar provincia --</option>';
                        data.forEach(prov => {
                            const option = document.createElement('option');
                            option.value = prov.id;
                            option.textContent = prov.nombre;
                            provSelect.appendChild(option);
                        });
                        provSelect.disabled = false;
                    })
                    .catch(error => {
                        console.error('Error:', error);
                    });
            }

            // AJAX form submission for checkout address
            document.getElementById('expressAddressForm').addEventListener('submit', function(e) {
                e.preventDefault();
                const saveBtn = document.getElementById('saveExpressAddressBtn');
                saveBtn.disabled = true;
                saveBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Guardando...';

                const formData = new FormData(this);

                fetch(`{{ route('web.direcciones.storeExpress') }}`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: formData
                })
                .then(res => {
                    const contentType = res.headers.get('content-type');
                    if (contentType && contentType.includes('application/json')) {
                        return res.json().then(data => {
                            if (!res.ok) {
                                if (res.status === 401 || data.code === 401) {
                                    window.location.href = '{{ route("web.login") }}';
                                    return;
                                }
                                return Promise.reject(data);
                            }
                            return data;
                        });
                    } else {
                        if (res.status === 401 || res.redirected) {
                            window.location.href = '{{ route("web.login") }}';
                            return;
                        }
                        throw new Error('El servidor devolvió una respuesta no válida (HTML).');
                    }
                })
                .then(res => {
                    if (!res) return;
                    saveBtn.disabled = false;
                    saveBtn.innerText = 'Guardar y Seleccionar';

                    if (res.success) {
                        // Close modal
                        const modalEl = document.getElementById('expressAddressModal');
                        const modal = bootstrap.Modal.getInstance(modalEl);
                        modal.hide();

                        // Reset form
                        document.getElementById('expressAddressForm').reset();
                        document.getElementById('departamento_id_checkout').disabled = true;
                        document.getElementById('provincia_id_checkout').disabled = true;

                        // Add new option to dropdown and select it
                        const select = document.getElementById('direccion_id_select');
                        const opt = document.createElement('option');
                        opt.value = res.direccion.id;
                        opt.textContent = `[${res.direccion.alias}] ${res.direccion.direccion_exacta} (${res.direccion.provincia.nombre}, ${res.direccion.departamento.nombre})`;
                        opt.selected = true;

                        select.appendChild(opt);
                        select.classList.remove('d-none');
                        select.required = true;

                        // Hide warning alert
                        const warning = document.getElementById('no-addresses-alert');
                        if (warning) warning.classList.add('d-none');

                        Swal.fire({
                            title: '¡Dirección registrada!',
                            text: 'La dirección se ha agregado a tu perfil y seleccionado para este pedido.',
                            icon: 'success',
                            timer: 2000,
                            showConfirmButton: false
                        });
                    } else {
                        Swal.fire('Error', res.message || 'No se pudo registrar la dirección.', 'error');
                    }
                })
                .catch(err => {
                    saveBtn.disabled = false;
                    saveBtn.innerText = 'Guardar y Seleccionar';
                    console.error(err);
                    Swal.fire('Error', err.message || 'Hubo un problema al procesar la solicitud.', 'error');
                });
            });

            // Recalculations and quantity actions
            function confirmarEliminarItem(id) {
                Swal.fire({
                    title: '¿Desea eliminar este producto del carrito?',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#3085d6',
                    confirmButtonText: 'Sí, eliminar',
                    cancelButtonText: 'Cancelar'
                }).then((result) => {
                    if (result.isConfirmed) {
                        document.getElementById('delete-form-' + id).submit();
                    }
                });
            }

            function confirmarLimpiarCarrito() {
                Swal.fire({
                    title: '¿Desea vaciar el carrito?',
                    text: "Esta acción eliminará todos los productos agregados.",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#3085d6',
                    confirmButtonText: 'Sí, vaciar',
                    cancelButtonText: 'Cancelar'
                }).then((result) => {
                    if (result.isConfirmed) {
                        document.getElementById('clear-cart-form').submit();
                    }
                });
            }

            document.addEventListener('DOMContentLoaded', function() {
                const checkoutForm = document.getElementById('checkout-form');
                const checkoutPanels = document.querySelectorAll('[data-checkout-panel]');
                const checkoutIndicators = document.querySelectorAll('[data-checkout-indicator]');
                const currentStepDisplay = document.getElementById('checkout-current-step');
                const noAddressesAlert = document.getElementById('no-addresses-alert');
                let activeCheckoutStep = Number(checkoutForm?.dataset.initialStep || 1);

                function showCheckoutStep(step) {
                    activeCheckoutStep = step;
                    if (currentStepDisplay) currentStepDisplay.textContent = step;
                    checkoutPanels.forEach(panel => {
                        panel.hidden = Number(panel.dataset.checkoutPanel) !== step;
                    });
                    checkoutIndicators.forEach(indicator => {
                        const indicatorStep = Number(indicator.dataset.checkoutIndicator);
                        indicator.classList.toggle('is-active', indicatorStep === step);
                        indicator.classList.toggle('is-complete', indicatorStep < step);
                        const trigger = indicator.querySelector('[data-checkout-go]');
                        if (indicatorStep === step) {
                            trigger?.setAttribute('aria-current', 'step');
                        } else {
                            trigger?.removeAttribute('aria-current');
                        }
                    });

                    const heading = document.getElementById(`checkout-step-${step}-title`);
                    if (heading && window.matchMedia('(max-width: 991px)').matches) {
                        heading.scrollIntoView({ behavior: 'smooth', block: 'start' });
                    }
                }

                const esRecojoEnTienda = () => document.querySelector('input[name="modalidad_entrega"]:checked')?.value === 'recojo';
                const esEnvioProvincia = () => document.querySelector('input[name="modalidad_entrega"]:checked')?.value === 'provincia';

                function navigateToCheckoutStep(targetStep) {
                    if (!Number.isInteger(targetStep) || targetStep < 1 || targetStep > checkoutPanels.length) return;
                    if (targetStep <= activeCheckoutStep) {
                        showCheckoutStep(targetStep);
                        return;
                    }

                    if (activeCheckoutStep <= 1 && targetStep > 1) {
                        const personalFields = [
                            document.getElementById('checkout-customer-name'),
                            document.getElementById('checkout-customer-dni'),
                            document.getElementById('checkout-customer-phone'),
                            document.getElementById('checkout-privacy-consent'),
                        ];
                        const firstInvalidField = personalFields.find(field => field && !field.checkValidity());
                        if (firstInvalidField) {
                            showCheckoutStep(1);
                            firstInvalidField.reportValidity();
                            firstInvalidField.focus();
                            return;
                        }
                    }

                    if (targetStep > 2 && !esRecojoEnTienda() && (!addressSelect || !addressSelect.value)) {
                        // El aviso vive en el panel de entrega: si se deja pasar al
                        // cliente hasta el pago, ya no podría verlo ni corregirlo.
                        noAddressesAlert?.classList.remove('d-none');
                        noAddressesAlert?.scrollIntoView({ behavior: 'smooth', block: 'center' });
                        return;
                    }

                    if (targetStep > 2 && esRecojoEnTienda()) {
                        const avisoPunto = document.getElementById('punto-recojo-alert');
                        // Solo cuando hay puntos listados: sin ellos el carrito muestra
                        // la dirección general de Ajustes y no hay nada que elegir.
                        if (document.querySelectorAll('input[name="punto_recojo_id"]').length
                            && !document.querySelector('input[name="punto_recojo_id"]:checked')) {
                            avisoPunto?.classList.remove('d-none');
                            avisoPunto?.scrollIntoView({ behavior: 'smooth', block: 'center' });
                            return;
                        }
                    }

                    document.getElementById('punto-recojo-alert')?.classList.add('d-none');

                    if (targetStep > 2 && esEnvioProvincia()) {
                        const avisoAgencia = document.getElementById('agencia-provincia-alert');
                        // Se pregunta por el grupo visible, no por los radios: los de
                        // otras ciudades están desactivados y no aplican a este pedido.
                        const grupoActivo = document.querySelector('.agencias-provincia-grupo:not(.d-none)');
                        if (grupoActivo && !grupoActivo.querySelector('input[name="agencia_provincia_id"]:checked')) {
                            avisoAgencia?.classList.remove('d-none');
                            avisoAgencia?.scrollIntoView({ behavior: 'smooth', block: 'center' });
                            return;
                        }
                    }

                    document.getElementById('agencia-provincia-alert')?.classList.add('d-none');

                    noAddressesAlert?.classList.add('d-none');
                    showCheckoutStep(targetStep);
                }

                document.querySelectorAll('[data-checkout-go]').forEach(button => {
                    button.addEventListener('click', () => {
                        navigateToCheckoutStep(Number(button.dataset.checkoutGo));
                    });
                });

                document.querySelectorAll('input[name="punto_recojo_id"]').forEach(radio => {
                    radio.addEventListener('change', () => {
                        document.getElementById('punto-recojo-alert')?.classList.add('d-none');
                        pintarPuntoElegido('recojo');
                    });
                });

                document.querySelectorAll('input[name="agencia_provincia_id"]').forEach(radio => {
                    radio.addEventListener('change', () => {
                        document.getElementById('agencia-provincia-alert')?.classList.add('d-none');
                        pintarPuntoElegido('provincia');
                    });
                });

                document.getElementById('direccion_id_select')?.addEventListener('change', () => {
                    // Cambiar de ciudad cambia las agencias disponibles: si no se
                    // repinta aquí, el cliente elegiría una agencia de otra provincia.
                    recalculateCart();
                });

                document.querySelectorAll('[data-checkout-next]').forEach(button => {
                    button.addEventListener('click', () => {
                        navigateToCheckoutStep(Number(button.dataset.checkoutNext));
                    });
                });

                document.querySelectorAll('[data-checkout-previous]').forEach(button => {
                    button.addEventListener('click', () => {
                        showCheckoutStep(Number(button.dataset.checkoutPrevious));
                    });
                });

                showCheckoutStep(activeCheckoutStep);

                let quantityUpdateTimer;
                function saveQuantityChange() {
                    window.clearTimeout(quantityUpdateTimer);
                    quantityUpdateTimer = window.setTimeout(() => {
                        document.getElementById('form-actualizar-carrito')?.requestSubmit();
                    }, 250);
                }

                // Handle increase qty click
                document.querySelectorAll('.increase-qty').forEach(btn => {
                    btn.addEventListener('click', function() {
                        const id = this.getAttribute('data-id');
                        const input = document.getElementById('quantity-input-' + id);
                        if (input) {
                            const current = parseInt(input.value) || 1;
                            const max = parseInt(input.getAttribute('max')) || 999;
                            if (current < max) {
                                input.value = current + 1;
                                saveQuantityChange();
                            }
                        }
                    });
                });

                // Handle decrease qty click
                document.querySelectorAll('.decrease-qty').forEach(btn => {
                    btn.addEventListener('click', function() {
                        const id = this.getAttribute('data-id');
                        const input = document.getElementById('quantity-input-' + id);
                        if (input) {
                            const current = parseInt(input.value) || 1;
                            if (current > 1) {
                                input.value = current - 1;
                                saveQuantityChange();
                            }
                        }
                    });
                });

                document.querySelectorAll('.quantity-input').forEach(input => {
                    input.addEventListener('change', () => {
                        const min = parseInt(input.min, 10) || 1;
                        const max = parseInt(input.max, 10) || 999;
                        const quantity = parseInt(input.value, 10);
                        input.value = Math.min(max, Math.max(min, Number.isNaN(quantity) ? min : quantity));
                        saveQuantityChange();
                    });
                });

                // Pricing parameters
                let subtotalAcumulado = parseFloat("{{ $subtotal_acumulado }}");
                // El porcentaje viene del servidor, nunca del cliente: el monto real
                // se recalcula otra vez al crear el pedido.
                let cuponCodigo = "{{ $cupon['codigo'] ?? '' }}";
                let cuponPorcentaje = parseInt("{{ $cupon['porcentaje'] ?? 0 }}", 10) || 0;
                const divisaSimbolo = "{{ $ajuste->moneda_simbolo ?? 'S/.' }}";
                const divisaPosicion = "{{ $ajuste->moneda_posicion ?? 'antes' }}";
                const divisaDecimales = parseInt("{{ $ajuste->moneda_decimales ?? 2 }}");

                function formatearPrecioJS(monto) {
                    const [entero, decimales] = parseFloat(monto).toFixed(divisaDecimales).split('.');
                    const miles = entero.replace(/\B(?=(\d{3})+(?!\d))/g, ',');
                    const formateado = decimales ? miles + '.' + decimales : miles;
                    if (divisaPosicion === 'antes') {
                        return divisaSimbolo + ' ' + formateado;
                    } else {
                        return formateado + ' ' + divisaSimbolo;
                    }
                }

                const shippingRadios = document.querySelectorAll('.shipping-radio');
                const deliveryRadios = document.querySelectorAll('input[name="modalidad_entrega"]');
                const hiddenTotalInput = document.getElementById('hidden-total');
                const hiddenShippingOption = document.getElementById('hidden-shipping-option');
                const shippingDisplay = document.getElementById('shipping-display');
                const shippingLabel = document.getElementById('shipping-label');
                const shippingMessage = document.getElementById('shipping-message');
                const gCartShip = document.getElementById('g-cart-ship');
                const gCartShipTitle = document.getElementById('g-cart-ship-title');
                const gCartShipAmount = document.getElementById('g-cart-ship-amount');
                const gCartShipBar = document.getElementById('g-cart-ship-bar');
                const gCartShipFill = gCartShipBar ? gCartShipBar.querySelector('span') : null;
                const totalDisplay = document.getElementById('total-display');
                const cuponContainer = document.getElementById('cupon-container');
                const cuponDisplay = document.getElementById('cupon-display');
                const cuponCodigoLabel = document.getElementById('cupon-codigo');
                const cuponPorcentajeLabel = document.getElementById('cupon-porcentaje');
                const shippingOptionsContainer = document.getElementById('shipping-options-container');
                const shippingAddressContainer = document.getElementById('shipping-address-container');
                const provinciaNote = document.getElementById('provincia-note');
                const recojoCard = document.getElementById('punto-recojo-card');
                const addressHint = document.getElementById('shipping-address-hint');
                const addressSelect = document.getElementById('direccion_id_select');
                const hayDirecciones = {{ $direcciones->isNotEmpty() ? 'true' : 'false' }};
                const agenciasCard = document.getElementById('agencias-provincia-card');
                const agenciasGrupos = document.querySelectorAll('.agencias-provincia-grupo');
                const agenciaRadios = document.querySelectorAll('input[name="agencia_provincia_id"]');

                const standardRadio = document.getElementById('standard_shipping');
                const expressRadio = document.getElementById('express_shipping');
                const standardPriceLabel = document.querySelector('[data-shipping-standard-price]');
                const umbralHint = document.querySelector('[data-shipping-umbral-hint]');
                const reglasEnvio = @json($reglasEnvio);

                const paymentOptions = document.querySelectorAll('input[name="metodo_pago"]');
                const btnSubmitCheckout = document.getElementById('btn-submit-checkout');
                const checkoutQrPreview = document.getElementById('checkout-qr-preview');
                const checkoutQrPanels = document.querySelectorAll('[data-checkout-qr]');
                const checkoutQrTotals = document.querySelectorAll('[data-checkout-qr-total]');
                const paymentCopyFeedback = document.getElementById('payment-copy-feedback');
                const checkoutCopyTotals = document.querySelectorAll('[data-copy-payment-number][data-copy-total]');

                const mensajeCopiado = (texto, variante = 'text-muted') => {
                    if (!paymentCopyFeedback) return;

                    paymentCopyFeedback.textContent = texto;
                    paymentCopyFeedback.className = `small mt-2 text-center ${variante}`;
                    window.clearTimeout(paymentCopyFeedback.dataset.tiempo);
                    paymentCopyFeedback.dataset.tiempo = window.setTimeout(() => {
                        paymentCopyFeedback.textContent = '';
                        paymentCopyFeedback.className = 'small mt-2 text-center';
                    }, 4000);
                };

                const marcarCopiado = (button) => {
                    const icono = button.querySelector('i');
                    const etiqueta = button.querySelector('.visually-hidden');
                    if (!icono || button.dataset.copiado === '1') return;

                    button.dataset.copiado = '1';
                    icono.className = 'bi bi-check-lg';
                    button.classList.remove('btn-outline-primary');
                    button.classList.add('btn-primary');
                    if (etiqueta) etiqueta.textContent = 'Copiado';

                    window.setTimeout(() => {
                        icono.className = 'bi bi-copy';
                        button.classList.remove('btn-primary');
                        button.classList.add('btn-outline-primary');
                        if (etiqueta) etiqueta.textContent = 'Copiar';
                        button.dataset.copiado = '0';
                    }, 1800);
                };

                document.querySelectorAll('[data-copy-payment-number]').forEach(button => {
                    button.addEventListener('click', async () => {
                        if (!navigator.clipboard || !window.isSecureContext) {
                            mensajeCopiado('Tu navegador no permite copiar automáticamente: selecciona el dato y cópialo a mano.', 'small mt-2 text-center text-warning');
                            return;
                        }

                        try {
                            await navigator.clipboard.writeText(button.dataset.copyPaymentNumber);
                            marcarCopiado(button);
                            const nota = button.dataset.copyNote
                                || 'Verifica el titular antes de confirmar la transferencia.';
                            mensajeCopiado('Copiado. ' + nota, 'small mt-2 text-center text-success');
                        } catch (error) {
                            mensajeCopiado('No se pudo copiar. Selecciona el dato y cópialo manualmente.', 'small mt-2 text-center text-warning');
                        }
                    });
                });

                // El QR se agranda con la imagen que ya está descargada en la
                // página: no se vuelve a pedir el archivo ni se expone otra ruta.
                const qrZoomModal = document.getElementById('qrZoomModal');
                if (qrZoomModal) {
                    document.querySelectorAll('[data-qr-zoom]').forEach(boton => {
                        boton.addEventListener('click', () => {
                            const miniatura = boton.querySelector('img');
                            if (!miniatura) return;

                            const grande = document.getElementById('qr-zoom-image');
                            grande.src = miniatura.src;
                            grande.alt = miniatura.alt;
                            document.getElementById('qr-zoom-open').href = miniatura.src;
                            qrZoomModal.querySelector('.modal-title').textContent = 'Código QR de '+boton.dataset.qrZoom;
                        });
                    });
                }

                function updatePaymentOptionAction() {
                    const paymentMethod = document.querySelector('input[name="metodo_pago"]:checked')?.value;

                    if (checkoutQrPreview) {
                        checkoutQrPreview.classList.toggle('d-none', !paymentMethod);
                        checkoutQrPanels.forEach(panel => {
                            panel.hidden = panel.dataset.checkoutQr !== paymentMethod;
                        });
                    }

                    document.querySelectorAll('[data-payment-badge]').forEach(badge => {
                        badge.classList.toggle('is-selected', badge.dataset.paymentBadge === paymentMethod);
                    });

                    const noteManual = document.getElementById('payment-note-manual');
                    const noteTarjeta = document.getElementById('payment-note-tarjeta');
                    const esPagoManual = ['yape', 'plin', 'bcp'].includes(paymentMethod);
                    if (noteManual) {
                        noteManual.classList.toggle('d-none', !esPagoManual);
                    }
                    if (noteTarjeta) {
                        noteTarjeta.classList.toggle('d-none', paymentMethod !== 'tarjeta');
                    }

                    if (!btnSubmitCheckout) return;

                    // El importe del botón es el que realmente se enviará: hidden-total
                    // lo escribe recalculateCart() justo antes de llegar aquí.
                    const montoBoton = parseFloat(hiddenTotalInput?.value || '0') || 0;
                    let etiqueta = '<i class="bi bi-exclamation-circle me-1"></i> Elige un método de pago';
                    if (paymentMethod === 'tarjeta') {
                        etiqueta = `<i class="bi bi-credit-card me-1"></i> Pagar ${formatearPrecioJS(montoBoton)} con tarjeta`;
                    } else if (paymentMethod) {
                        etiqueta = '<i class="bi bi-arrow-repeat me-1"></i> Crear pedido y subir comprobante';
                    }

                    btnSubmitCheckout.disabled = paymentOptions.length === 0 || !paymentMethod;
                    btnSubmitCheckout.innerHTML = etiqueta;
                    btnSubmitCheckout.className = 'btn btn-primary btn-lg rounded-pill py-3 w-100';

                    if (paymentMethod) {
                        recordarMetodoPago(paymentMethod);
                    }

                    pintarPickerMetodo(paymentMethod);
                    pintarModalMetodos(paymentMethod, montoBoton);
                }

                // El botón que queda en el carrito debe decir lo que el cliente
                // eligió dentro de la ventana; sin esto se quedaba leyendo
                // «Elige un medio de pago» con un método ya seleccionado.
                const pickerMetodo = document.getElementById('payment-method-picker');

                function pintarPickerMetodo(paymentMethod) {
                    if (!pickerMetodo) return;

                    const radio = paymentMethod
                        ? document.querySelector(`input[name="metodo_pago"][value="${paymentMethod}"]`)
                        : null;
                    const icono = pickerMetodo.querySelector('[data-picker-icon]');
                    const titulo = pickerMetodo.querySelector('[data-picker-title]');
                    const pista = pickerMetodo.querySelector('[data-picker-hint]');
                    const accion = pickerMetodo.querySelector('[data-picker-action]');

                    pickerMetodo.classList.toggle('is-selected', !!radio);

                    if (!radio) {
                        icono.innerHTML = '<i class="bi bi-wallet2"></i>';
                        titulo.textContent = 'Elige un medio de pago';
                        pista.textContent = 'Tarjeta, código QR o Yape';
                        accion.textContent = 'Elegir';
                        return;
                    }

                    icono.innerHTML = `<img src="${radio.dataset.logo}" alt="">`;
                    titulo.textContent = radio.dataset.titulo;
                    pista.textContent = radio.dataset.hint;
                    accion.textContent = 'Cambiar';
                }

                const modalMetodos = document.getElementById('modal-metodos-pago');

                // La última elección se recuerda en este navegador, así la ventana
                // abre con el medio ya marcado y el cliente recurrente se ahorra un
                // toque. Solo se hace con JavaScript: si el radio viniera marcado
                // desde el HTML, un cliente sin scripts pagaría con tarjeta sin
                // haber elegido nada.
                const MEMORIA_METODO = 'galy-metodo-pago';

                function leerMetodoRecordado() {
                    try {
                        return window.localStorage.getItem(MEMORIA_METODO);
                    } catch (error) {
                        return null;
                    }
                }

                function recordarMetodoPago(valor) {
                    try {
                        window.localStorage.setItem(MEMORIA_METODO, valor);
                    } catch (error) {
                        // Modo privado: se pierde la comodidad, nunca el pago.
                    }
                }

                function aplicarMetodoRecordado() {
                    if (!modalMetodos || document.querySelector('input[name="metodo_pago"]:checked')) {
                        return;
                    }

                    for (const valor of [leerMetodoRecordado(), 'tarjeta']) {
                        const radio = valor && modalMetodos.querySelector(`input[name="metodo_pago"][value="${valor}"]`);
                        if (radio) {
                            radio.checked = true;
                            updatePaymentOptionAction();

                            return;
                        }
                    }
                }

                @php
                    $guiaTarjeta = $tarjetaEnTienda
                        ? 'Escribe tu tarjeta aquí mismo: Mercado Pago muestra su formulario seguro dentro de la tienda.'
                        : 'Te llevamos a Mercado Pago en esta misma pestaña, o por QR desde tu celular: allí escribes tu tarjeta.';

                    $pasosTarjeta = $tarjetaEnTienda
                        ? [
                            'Completa el formulario de Mercado Pago que aparece en esta misma pantalla.',
                            'Elige crédito o débito, y las cuotas si te conviene.',
                            'Al confirmar, el pedido queda pagado sin que salgas de la tienda.',
                        ]
                        : [
                            'Al pagar te llevamos a Mercado Pago en esta misma pestaña.',
                            'Allí eliges crédito o débito y las cuotas si te conviene.',
                            'Al confirmar vuelves a esta tienda con el pedido pagado.',
                        ];
                @endphp
                const DETALLE_METODOS = {
                    yape: {
                        titulo: 'Paga más rápido',
                        guia: 'Escanea este QR con la billetera electrónica de tu banco, desde la app Yape.',
                        nota: 'Comprueba que el nombre del destinatario coincida antes de pagar.',
                        pasos: [
                            'Abre la app de Yape y toca «Escanear».',
                            'Apunta al QR y revisa el destinatario y el monto.',
                            'Termina la transferencia y vuelve aquí a subir el comprobante.',
                        ],
                        confianza: 'Nosotros verificamos tu transferencia con el comprobante antes de confirmar el pedido.',
                    },
                    plin: {
                        titulo: 'Paga más rápido',
                        guia: 'Escanea este QR con la billetera electrónica de tu banco, desde la app Plin.',
                        nota: 'Comprueba que el nombre del destinatario coincida antes de pagar.',
                        pasos: [
                            'Abre la app de Plin y toca «Escaneo QR».',
                            'Apunta al QR y revisa el destinatario y el monto.',
                            'Termina la transferencia y vuelve aquí a subir el comprobante.',
                        ],
                        confianza: 'Nosotros verificamos tu transferencia con el comprobante antes de confirmar el pedido.',
                    },
                    tarjeta: {
                        titulo: 'Paga con tu tarjeta',
                        guia: @json($guiaTarjeta),
                        nota: 'La tienda no ve ni guarda el número, la fecha ni el CVV de tu tarjeta.',
                        pasos: @json($pasosTarjeta),
                        recordatorio: 'Si tu tarjeta es de débito, activa las compras por internet desde la app de tu banco antes de pagar.',
                        confianza: 'Mercado Pago procesa la tarjeta: la tienda no ve ni guarda tu número ni el CVV.',
                    },
                };

                // La instancia del formulario de Mercado Pago vive aquí, fuera
                // del bloque de Checkout Bricks: el QR de tarjeta también la
                // consulta cuando la Public Key no está configurada.
                let brickTarjeta = null;

                @if ($tarjetaEnTienda)
                // Pago con tarjeta dentro de la tienda (Checkout Bricks):
                // Mercado Pago pinta su formulario real en este contenedor y
                // devuelve un token ya listo. La tienda nunca recibe el número
                // ni el CVV, por eso aquí no se crea ningún campo de tarjeta.
                const contenedorBrick = document.getElementById('cardPaymentBrickModal');
                const avisoBrick = modalMetodos?.querySelector('[data-brick-error]');
                let brickPendiente = null;
                let montoBrick = null;
                let brickFallido = false;

                function mostrarAvisoBrick(texto) {
                    if (!avisoBrick) return;
                    avisoBrick.textContent = texto;
                    avisoBrick.hidden = false;
                }

                function mostrarPagoAlterno() {
                    // Si el formulario de Mercado Pago no carga, el botón verde
                    // de siempre sigue disponible: abre el checkout hospedado.
                    brickFallido = true;
                    const pagar = modalMetodos?.querySelector('[data-modal-pagar]');
                    if (pagar) pagar.hidden = false;
                }

                function montoDelCheckout() {
                    const monto = parseFloat(hiddenTotalInput?.value || '0') || 0;
                    return Math.round(monto * 100) / 100;
                }

                function enviarTarjetaDelBrick(datos) {
                    if (!checkoutForm) return Promise.reject(new Error('Sin formulario'));

                    // El token llega ya generado por Mercado Pago: la tienda solo
                    // lo reenvía al crear el pedido, junto con los datos de envío.
                    const campos = {
                        brick_token: datos.token,
                        brick_payment_method_id: datos.paymentMethodId ?? datos.payment_method_id ?? '',
                        brick_issuer_id: datos.issuerId ?? datos.issuer_id ?? '',
                        brick_installments: datos.installments ?? 1,
                        brick_email: datos.payer?.email ?? '',
                    };

                    for (const [nombre, valor] of Object.entries(campos)) {
                        let input = checkoutForm.querySelector(`input[name="${nombre}"]`);
                        if (!input) {
                            input = document.createElement('input');
                            input.type = 'hidden';
                            input.name = nombre;
                            checkoutForm.appendChild(input);
                        }
                        input.value = valor ?? '';
                    }

                    checkoutForm.requestSubmit();

                    // Nunca se resuelve: el brick mostraría «pago exitoso» y
                    // borraría el formulario justo antes de navegar.
                    return new Promise(() => {});
                }

                function montarBrickTarjeta() {
                    if (!contenedorBrick || brickFallido) return;
                    // Con el QR del pedido ya generado el formulario sobra: montarlo
                    // dejaría dos caminos de cobro para la misma compra.
                    if (qrTarjeta.estado === 'listo') return;

                    const monto = montoDelCheckout();
                    if (monto <= 0) return;
                    if (montoBrick === monto && (brickTarjeta || brickPendiente)) return;

                    if (brickTarjeta) {
                        try { brickTarjeta.unmount(); } catch (error) {}
                        brickTarjeta = null;
                    }
                    brickPendiente = null;

                    contenedorBrick.replaceChildren();

                    if (typeof window.MercadoPago !== 'function') {
                        mostrarAvisoBrick('No se pudo cargar el formulario de tarjeta. Puedes pagar en la página de Mercado Pago con el botón de abajo.');
                        mostrarPagoAlterno();
                        return;
                    }

                    const mp = new window.MercadoPago(@json($mercadoPago->llavePublica()), { locale: 'es-PE' });

                    function manejarFalloBrick(error) {
                        if (brickFallido) return;

                        mostrarAvisoBrick(
                            (error && error.message)
                            || 'Mercado Pago no pudo mostrar el formulario de tarjeta. Puedes pagar en su página con el botón de abajo.'
                        );
                        mostrarPagoAlterno();
                    }

                    try {
                        const creacion = mp.bricks().create('cardPayment', 'cardPaymentBrickModal', {
                            initialization: { amount: monto },
                            callbacks: {
                                // El SDK exige las tres: sin `onReady` rechaza el
                                // brick con «Callbacks onReady and/or onError are
                                // required» aunque `onSubmit` y `onError` estén.
                                onReady: () => {},
                                onSubmit: enviarTarjetaDelBrick,
                                onError: manejarFalloBrick,
                            },
                        });

                        // `create()` devuelve una promesa. Si rechaza —Public Key
                        // inexistente, por ejemplo— no siempre llama a `onError`;
                        // sin este `catch` el contenedor quedaba vacío y el botón
                        // de pago oculto, sin salida para el cliente.
                        brickPendiente = creacion;

                        if (creacion && typeof creacion.then === 'function') {
                            creacion.then((instancia) => {
                                if (brickPendiente === creacion) {
                                    brickTarjeta = instancia || null;
                                    brickPendiente = null;
                                }
                            }).catch((error) => {
                                if (brickPendiente === creacion) {
                                    brickPendiente = null;
                                    manejarFalloBrick(error);
                                }
                            });
                        } else {
                            brickTarjeta = creacion;
                            brickPendiente = null;
                        }

                        // El SDK puede fallar sin llamar a `onError` (por ejemplo,
                        // si la Public Key no existe): el contenedor se queda
                        // vacío y el cliente no tiene ni formulario ni botón.
                        // Este vigilante devuelve el pago alternativo.
                        let intentos = 0;
                        const vigilante = window.setInterval(() => {
                            if (brickFallido) {
                                window.clearInterval(vigilante);
                                return;
                            }

                            if (contenedorBrick.querySelector('iframe')) {
                                window.clearInterval(vigilante);
                                return;
                            }

                            if (++intentos >= 16) {
                                window.clearInterval(vigilante);
                                manejarFalloBrick();
                            }
                        }, 500);
                    } catch (error) {
                        manejarFalloBrick(error);
                    }

                    montoBrick = monto;
                }
                @endif

                // El QR de tarjeta se pide al servidor solo cuando el cliente
                // toca «Generar QR de pago»: el pedido y el enlace de Mercado
                // Pago se crean una sola vez. El estado evita repetir la
                // petición al repintar o reabrir la ventana.
                const qrTarjeta = { estado: 'inactivo', datos: null };
                const bloqueQrTarjeta = modalMetodos?.querySelector('[data-detalle-tarjeta-qr]');
                const avisoQrTarjeta = modalMetodos?.querySelector('[data-tarjeta-qr-aviso]');
                const imagenQrTarjeta = modalMetodos?.querySelector('[data-tarjeta-qr-imagen]');
                const spinnerQrTarjeta = modalMetodos?.querySelector('[data-tarjeta-qr-spinner]');
                const iconoQrTarjeta = modalMetodos?.querySelector('[data-tarjeta-qr-icono]');
                const botonQrTarjeta = modalMetodos?.querySelector('[data-tarjeta-qr-zoom]');
                const botonGenerarQr = modalMetodos?.querySelector('[data-tarjeta-qr-generar]');
                const tituloQrTarjeta = modalMetodos?.querySelector('[data-tarjeta-qr-titulo]');
                const guiaQrTarjeta = modalMetodos?.querySelector('[data-tarjeta-qr-guia]');
                const enlaceQrTarjeta = modalMetodos?.querySelector('[data-tarjeta-qr-enlace]');
                const pedidoQrTarjeta = modalMetodos?.querySelector('[data-tarjeta-qr-pedido]');

                // Estado inicial del bloque: se ofrece el atajo pero no se crea
                // ningún pedido hasta que el cliente lo pida. Así el formulario
                // de Mercado Pago sigue siendo la vía principal.
                function mostrarQrTarjetaInactivo() {
                    qrTarjeta.estado = 'inactivo';
                    qrTarjeta.datos = null;

                    if (bloqueQrTarjeta) bloqueQrTarjeta.hidden = false;
                    if (tituloQrTarjeta) tituloQrTarjeta.innerHTML = 'Escanea el QR y paga con <strong>tarjeta</strong>';
                    if (guiaQrTarjeta) guiaQrTarjeta.textContent = 'Genera el QR de este mismo pago y escanéalo con la app de tu banco.';
                    if (spinnerQrTarjeta) spinnerQrTarjeta.hidden = true;
                    if (iconoQrTarjeta) iconoQrTarjeta.hidden = false;
                    if (imagenQrTarjeta) {
                        imagenQrTarjeta.hidden = true;
                        imagenQrTarjeta.removeAttribute('src');
                    }
                    if (botonQrTarjeta) {
                        botonQrTarjeta.hidden = false;
                        botonQrTarjeta.disabled = true;
                    }
                    if (botonGenerarQr) {
                        botonGenerarQr.hidden = false;
                        botonGenerarQr.disabled = false;
                    }
                    if (enlaceQrTarjeta) enlaceQrTarjeta.hidden = true;
                    if (pedidoQrTarjeta) pedidoQrTarjeta.hidden = true;
                    if (avisoQrTarjeta) avisoQrTarjeta.hidden = true;
                }

                function mostrarCargandoQrTarjeta() {
                    qrTarjeta.estado = 'creando';
                    if (!bloqueQrTarjeta) return;

                    bloqueQrTarjeta.hidden = false;
                    if (tituloQrTarjeta) tituloQrTarjeta.innerHTML = 'Escanea el QR y paga con <strong>tarjeta</strong>';
                    if (guiaQrTarjeta) guiaQrTarjeta.textContent = 'Estamos generando tu enlace seguro de Mercado Pago…';
                    if (spinnerQrTarjeta) spinnerQrTarjeta.hidden = false;
                    if (iconoQrTarjeta) iconoQrTarjeta.hidden = true;
                    if (imagenQrTarjeta) imagenQrTarjeta.hidden = true;
                    if (botonGenerarQr) botonGenerarQr.hidden = true;
                    if (botonQrTarjeta) {
                        botonQrTarjeta.hidden = false;
                        botonQrTarjeta.disabled = true;
                    }
                    if (enlaceQrTarjeta) enlaceQrTarjeta.hidden = true;
                    if (pedidoQrTarjeta) pedidoQrTarjeta.hidden = true;
                    if (avisoQrTarjeta) avisoQrTarjeta.hidden = true;
                }

                function mostrarErrorQrTarjeta(texto) {
                    // No se bloquea el atajo: el botón vuelve a quedar a la
                    // vista para reintentar, y el formulario de tarjeta sigue
                    // intacto como camino alternativo.
                    qrTarjeta.estado = 'inactivo';
                    if (bloqueQrTarjeta) bloqueQrTarjeta.hidden = false;
                    if (spinnerQrTarjeta) spinnerQrTarjeta.hidden = true;
                    if (iconoQrTarjeta) iconoQrTarjeta.hidden = false;
                    if (imagenQrTarjeta) imagenQrTarjeta.hidden = true;
                    if (botonQrTarjeta) {
                        botonQrTarjeta.hidden = false;
                        botonQrTarjeta.disabled = true;
                    }
                    if (botonGenerarQr) {
                        botonGenerarQr.hidden = false;
                        botonGenerarQr.disabled = false;
                    }
                    if (guiaQrTarjeta) {
                        guiaQrTarjeta.textContent = 'No pudimos generar el QR. Puedes pagar con el formulario de abajo o reintentar.';
                    }
                    if (avisoQrTarjeta) {
                        avisoQrTarjeta.textContent = texto;
                        avisoQrTarjeta.hidden = false;
                    }
                }

                // Cuando el pedido ya existe, el formulario de tarjeta y sus
                // botones se retiran: con el QR a la vista no debe quedar una
                // segunda vía que cree otro pedido o cobre dos veces.
                function aplicarEstadoQrTarjeta() {
                    if (!modalMetodos) return;

                    const pasoActual = Number(
                        modalMetodos.querySelector('[data-modal-paso]:not([hidden])')?.dataset.modalPaso || 0
                    );
                    if (qrTarjeta.estado !== 'listo' || pasoActual !== 2) return;

                    const campos = modalMetodos.querySelector('[data-detalle-campos]');
                    const divisor = modalMetodos.querySelector('[data-detalle-divider]');
                    const recordatorio = modalMetodos.querySelector('[data-detalle-recordatorio]');
                    const pagar = modalMetodos.querySelector('[data-modal-pagar]');
                    const volver = modalMetodos.querySelector('[data-modal-volver]');

                    if (campos) campos.hidden = true;
                    if (divisor) divisor.hidden = true;
                    if (recordatorio) recordatorio.hidden = true;
                    if (pagar) pagar.hidden = true;
                    if (volver) volver.hidden = true;
                    if (bloqueQrTarjeta) bloqueQrTarjeta.hidden = false;
                }

                function mostrarQrTarjetaListo(datos) {
                    qrTarjeta.estado = 'listo';
                    qrTarjeta.datos = datos;

                    if (bloqueQrTarjeta) bloqueQrTarjeta.hidden = false;
                    if (spinnerQrTarjeta) spinnerQrTarjeta.hidden = true;
                    if (iconoQrTarjeta) iconoQrTarjeta.hidden = true;
                    if (avisoQrTarjeta) avisoQrTarjeta.hidden = true;
                    if (botonGenerarQr) botonGenerarQr.hidden = true;
                    if (tituloQrTarjeta) tituloQrTarjeta.innerHTML = 'Paga este pedido con <strong>tarjeta</strong>';
                    if (guiaQrTarjeta) {
                        guiaQrTarjeta.textContent = `Pedido ${datos.orden_id ? '#' + datos.orden_id : ''} creado. Escanea el QR con la app de tu banco o ábrelo en Mercado Pago.`;
                    }
                    const guiaPrincipal = modalMetodos?.querySelector('[data-detalle-guia]');
                    if (guiaPrincipal) {
                        guiaPrincipal.textContent = 'Tu pedido ya está creado. Paga con tarjeta escaneando el QR o abriendo Mercado Pago.';
                    }
                    if (enlaceQrTarjeta) {
                        enlaceQrTarjeta.href = datos.checkout_url || '#';
                        enlaceQrTarjeta.hidden = !datos.checkout_url;
                    }
                    if (pedidoQrTarjeta) {
                        pedidoQrTarjeta.href = datos.confirmacion_url || '#';
                        pedidoQrTarjeta.hidden = !datos.confirmacion_url;
                    }

                    if (imagenQrTarjeta) {
                        imagenQrTarjeta.src = datos.qr || '';
                        imagenQrTarjeta.alt = `Código QR de Mercado Pago para pagar ${datos.total || ''} con tarjeta`;
                        imagenQrTarjeta.hidden = !datos.qr;
                    }
                    if (botonQrTarjeta) {
                        botonQrTarjeta.hidden = !datos.qr;
                        botonQrTarjeta.disabled = !datos.qr;
                    }

                    // Se apaga el formulario de Mercado Pago para que el cliente
                    // no escriba la tarjeta después de que el pedido ya se creó.
                    if (brickTarjeta) {
                        try { brickTarjeta.unmount(); } catch (error) {}
                        brickTarjeta = null;
                    }

                    aplicarEstadoQrTarjeta();
                }

                function solicitarQrTarjeta() {
                    const metodo = document.querySelector('input[name="metodo_pago"]:checked')?.value;

                    if (!modalMetodos || !bloqueQrTarjeta || metodo !== 'tarjeta') return;
                    if (qrTarjeta.estado === 'listo') {
                        aplicarEstadoQrTarjeta();
                        return;
                    }
                    if (qrTarjeta.estado === 'creando') return;

                    // El pedido solo puede crearse con datos completos: si falta
                    // algo, el formulario que está debajo lo señala y no se
                    // inventa un pedido a medias.
                    if (!checkoutForm || !checkoutForm.checkValidity()) {
                        mostrarErrorQrTarjeta('Cierra esta ventana, completa tus datos de envío y acepta la privacidad; después podrás generar el QR.');
                        return;
                    }

                    mostrarCargandoQrTarjeta();

                    const datos = new FormData(checkoutForm);
                    datos.set('metodo_pago', 'tarjeta');
                    datos.set('qr_tarjeta', '1');

                    fetch(checkoutForm.action, {
                        method: 'POST',
                        body: datos,
                        credentials: 'same-origin',
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                    }).then(async (respuesta) => {
                        if (respuesta.status === 422) {
                            // Datos incompletos: no se creó ningún pedido.
                            const json = await respuesta.json().catch(() => null);
                            mostrarErrorQrTarjeta(
                                json?.message || 'Revisa tus datos de envío para generar el QR.'
                            );
                            return;
                        }

                        if (!respuesta.ok) {
                            throw new Error('No se pudo generar el QR');
                        }

                        const tipo = respuesta.headers.get('content-type') || '';
                        if (!tipo.includes('application/json')) {
                            // Una redirección del servidor (carrito vacío, sesión
                            // vencida) devuelve HTML: no hubo pedido aquí.
                            mostrarErrorQrTarjeta('No pudimos generar el QR con este carrito. Revisa tu pedido e inténtalo otra vez.');
                            return;
                        }

                        const json = await respuesta.json();
                        if (!json || !json.checkout_url) {
                            throw new Error('Respuesta incompleta');
                        }

                        mostrarQrTarjetaListo(json);
                    }).catch(() => {
                        mostrarErrorQrTarjeta('No pudimos generar el QR de Mercado Pago.');
                    });
                }

                // El botón del bloque verde es la única puerta al QR: abrir el
                // paso ya no crea pedidos abandonados por su cuenta.
                botonGenerarQr?.addEventListener('click', solicitarQrTarjeta);

                function pintarModalMetodos(paymentMethod, monto) {
                    if (!modalMetodos) return;

                    const detalle = DETALLE_METODOS[paymentMethod];
                    const continuar = modalMetodos.querySelector('[data-modal-continuar]');

                    continuar.disabled = !paymentMethod;

                    // El botón dice con qué se va a continuar, no un genérico:
                    // el cliente confirma de un vistazo que eligió lo que quería.
                    const continuarTexto = modalMetodos.querySelector('[data-modal-continuar-texto]');
                    if (continuarTexto) {
                        const nombres = { tarjeta: 'tarjeta', plin: 'QR', yape: 'Yape' };
                        continuarTexto.textContent = paymentMethod && nombres[paymentMethod]
                            ? `Continuar con ${nombres[paymentMethod]}`
                            : 'Continuar';
                    }

                    if (!detalle) {
                        mostrarPasoModal(1);
                        return;
                    }

                    modalMetodos.querySelector('[data-detalle-titulo]').textContent = detalle.titulo;
                    modalMetodos.querySelector('[data-detalle-guia]').textContent = detalle.guia;
                    modalMetodos.querySelector('[data-detalle-nota]').textContent = detalle.nota;

                    // La vista previa de la tarjeta y el divisor solo acompañan
                    // a la tarjeta; en Yape/Plin el QR con sus pasos es la
                    // pantalla completa. La nota de «la tienda no guarda tu
                    // tarjeta» la cubren la vista previa y la línea de
                    // confianza del pie, y repetida tres veces apretaba la
                    // tarjeta.
                    const esTarjeta = paymentMethod === 'tarjeta';
                    const camposBrick = modalMetodos.querySelector('[data-detalle-campos]');

                    modalMetodos.querySelector('[data-detalle-nota]').hidden = esTarjeta;
                    // El marco del formulario y su divisor solo existen con
                    // Checkout Bricks encendido; sin él la pantalla es la de
                    // siempre y no se anuncia un formulario que no está.
                    if (camposBrick) camposBrick.hidden = !esTarjeta;
                    modalMetodos.querySelector('[data-detalle-divider]').hidden = !esTarjeta || !camposBrick;

                    // Las marcas se repiten aquí, a un toque de salir de la tienda:
                    // es justo donde el cliente busca si su tarjeta sirve.
                    const marcas = (document.querySelector(
                        `input[name="metodo_pago"][value="${paymentMethod}"]`
                    )?.dataset.marcas || '').split('|').filter(Boolean);
                    const marcasBloque = modalMetodos.querySelector('[data-detalle-marcas]');

                    // En la tarjeta las marcas ya cierran la pantalla, debajo
                    // del botón, como en la referencia; el párrafo solo sirve
                    // a Yape y Plin.
                    marcasBloque.hidden = esTarjeta || marcas.length < 2;
                    marcasBloque.textContent = marcasBloque.hidden
                        ? ''
                        : `En Mercado Pago puedes pagar con ${marcas.slice(0, -1).join(', ')} o ${marcas.at(-1)}.`;

                    // Los tres pasos van numerados porque el pago manual se abandona
                    // a mitad: el cliente necesita saber cuántos toques le faltan.
                    const pasosBloque = modalMetodos.querySelector('[data-detalle-pasos]');
                    const pasos = detalle.pasos || [];

                    pasosBloque.hidden = esTarjeta || pasos.length === 0;
                    pasosBloque.replaceChildren(...pasos.map(texto => {
                        const item = document.createElement('li');
                        item.textContent = texto;

                        return item;
                    }));

                    modalMetodos.querySelector('[data-modal-confianza-texto]').textContent =
                        detalle.confianza || '';

                    // El recordatorio del banco es de la tarjeta: en Yape o Plin no
                    // hay nada que activar y aparecería de más.
                    const recordatorio = modalMetodos.querySelector('[data-detalle-recordatorio]');

                    recordatorio.hidden = !detalle.recordatorio;
                    recordatorio.querySelector('[data-detalle-recordatorio-texto]').textContent =
                        detalle.recordatorio || '';

                    // El bloque verde es exclusivo de la tarjeta. Arranca
                    // ofreciendo el QR y, cuando ya existe, muestra el código.
                    // No se copia ningún QR de billetera: cada método conserva
                    // el suyo.
                    if (bloqueQrTarjeta) {
                        bloqueQrTarjeta.hidden = !esTarjeta;
                    }
                    if (!esTarjeta && avisoQrTarjeta) avisoQrTarjeta.hidden = true;
                    if (esTarjeta && qrTarjeta.estado === 'inactivo') mostrarQrTarjetaInactivo();

                    modalMetodos.querySelector('[data-modal-pagar-texto]').textContent =
                        paymentMethod === 'tarjeta'
                            ? `Pagar ${formatearPrecioJS(monto)} con tarjeta`
                            : `Ya pagué ${formatearPrecioJS(monto)} · subir comprobante`;

                    // El QR que ya se imprime en «Datos del pago» se copia aquí para
                    // que el cliente escanee sin cerrar la ventana. La tarjeta no
                    // tiene QR propio: Mercado Pago lo crea al confirmar el pedido.
                    const qrBloque = modalMetodos.querySelector('[data-detalle-qr]');
                    const qrImagen = modalMetodos.querySelector('[data-detalle-qr-imagen]');
                    const qrOrigen = document.querySelector(
                        `[data-checkout-qr="${paymentMethod}"] img.checkout-qr-preview__image`
                    );

                    if (qrBloque && qrImagen) {
                        qrBloque.hidden = !qrOrigen;
                        if (qrOrigen) {
                            qrImagen.src = qrOrigen.src;
                            qrImagen.alt = qrOrigen.alt || `Código QR de ${paymentMethod}`;
                        }
                    }
                }

                function mostrarPasoModal(paso) {
                    if (!modalMetodos) return;

                    modalMetodos.querySelectorAll('[data-modal-paso]').forEach(bloque => {
                        bloque.hidden = Number(bloque.dataset.modalPaso) !== paso;
                    });

                    // La lista se queda angosta, como la pantalla de Mercado Pago;
                    // el paso del QR necesita ancho para el código al lado del texto.
                    const dialogo = modalMetodos.querySelector('.modal-dialog');

                    if (dialogo) {
                        dialogo.classList.toggle('checkout-payment-modal__dialog--pago', paso === 2);
                    }

                    // Cada paso tiene su salida verde: «Continuar» en la lista y el
                    // pago real en el detalle. El QR de la tarjeta se pide dentro del
                    // propio paso, así que el pie no necesita un segundo atajo.
                    const metodo = document.querySelector('input[name="metodo_pago"]:checked')?.value || '';
                    const continuar = modalMetodos.querySelector('[data-modal-continuar]');
                    const pagar = modalMetodos.querySelector('[data-modal-pagar]');
                    const confianza = modalMetodos.querySelector('[data-modal-confianza]');
                    const chips = modalMetodos.querySelector('[data-detalle-chips]');

                    continuar.hidden = paso !== 1;
                    continuar.disabled = !metodo;

                    @if ($tarjetaEnTienda)
                    // Con el formulario real dentro de la ventana, el botón verde
                    // del pie sobra: Mercado Pago dibuja el suyo al final del
                    // formulario. Si el SDK falla, `mostrarPagoAlterno()` lo
                    // devuelve para no dejar al cliente sin salida.
                    const pagoPorBrick = metodo === 'tarjeta' && !!contenedorBrick && !brickFallido;
                    @else
                    const pagoPorBrick = false;
                    @endif

                    pagar.hidden = paso !== 2 || pagoPorBrick;

                    // Montar el formulario solo cuando su paso está visible: un
                    // brick creado dentro de un panel oculto mide cero y se pinta
                    // en blanco.
                    @if ($tarjetaEnTienda)
                    if (paso === 2 && metodo === 'tarjeta') {
                        montarBrickTarjeta();
                    }
                    @endif

                    // Entrar al paso ya no crea pedidos: el QR solo se pide al
                    // tocar su botón. Si el cliente ya lo generó, se conserva a
                    // la vista y el formulario sigue retirado.
                    if (!(paso === 2 && metodo === 'tarjeta') && avisoQrTarjeta) {
                        avisoQrTarjeta.hidden = true;
                    }
                    if (paso === 2 && metodo === 'tarjeta' && qrTarjeta.estado === 'inactivo') {
                        mostrarQrTarjetaInactivo();
                    }
                    aplicarEstadoQrTarjeta();

                    // Las marcas solo se repiten bajo el botón verde de la tarjeta;
                    // en la lista ya van dentro de su propia fila.
                    if (chips) chips.hidden = paso !== 2 || metodo !== 'tarjeta';

                    // La línea legal acompaña solo al paso de la tarjeta, que es
                    // donde Mercado Pago pide los datos personales.
                    const privacidad = modalMetodos.querySelector('[data-detalle-privacidad]');
                    if (privacidad) privacidad.hidden = paso !== 2 || metodo !== 'tarjeta';

                    // El panel de ayuda ya explica la confianza en tres puntos;
                    // repetir la línea debajo solo apretaría la pantalla.
                    if (confianza) confianza.hidden = paso === 3;

                    if (paso === 2) {
                        modalMetodos.querySelector('[data-detalle-titulo]')?.focus({ preventScroll: true });
                    }

                    if (paso === 3) {
                        modalMetodos.querySelector('[data-modal-paso="3"] h3')?.focus({ preventScroll: true });
                    }
                }

                modalMetodos?.addEventListener('shown.bs.modal', () => {
                    // Si el pedido ya se creó en esta visita, la ventana no puede
                    // volver a la lista de métodos: el carrito ya está consumido.
                    if (qrTarjeta.estado === 'listo') {
                        mostrarPasoModal(2);
                        return;
                    }

                    // Abrir siempre por la lista: el disparador ya muestra lo elegido
                    // y el cliente viene a cambiarlo, no a releer el detalle.
                    aplicarMetodoRecordado();
                    mostrarPasoModal(1);
                    const marcado = modalMetodos.querySelector('[data-modal-paso="1"] input[name="metodo_pago"]:checked');
                    (marcado || modalMetodos.querySelector('[data-modal-paso="1"] input[name="metodo_pago"]'))?.focus();
                });

                modalMetodos?.querySelector('[data-modal-volver]')?.addEventListener('click', () => mostrarPasoModal(1));

                // «Continuar» no paga: solo avanza a la pantalla donde está el
                // botón verde, para que un toque decida y no dos.
                modalMetodos?.querySelector('[data-modal-continuar]')?.addEventListener('click', () => {
                    const elegido = document.querySelector('input[name="metodo_pago"]:checked')?.value;
                    if (elegido) mostrarPasoModal(2);
                });

                // El interrogante se para encima del paso que el cliente ya estaba
                // viendo y «Volver» lo regresa al mismo, no al principio de la lista.
                let pasoAntesDeAyuda = 1;

                modalMetodos?.querySelector('[data-modal-ayuda]')?.addEventListener('click', () => {
                    pasoAntesDeAyuda = Number(
                        modalMetodos.querySelector('[data-modal-paso]:not([hidden])')?.dataset.modalPaso || 1
                    );
                    mostrarPasoModal(3);
                });

                modalMetodos?.querySelector('[data-ayuda-volver]')?.addEventListener('click', () => mostrarPasoModal(pasoAntesDeAyuda));

                function pintarAgenciasProvincia(mostrar) {
                    if (!agenciasCard) return;

                    const provinciaId = mostrar ? (addressSelect?.selectedOptions[0]?.dataset.provinciaId || '') : '';
                    let hayGrupo = false;

                    agenciasGrupos.forEach(grupo => {
                        const aplica = mostrar && provinciaId !== '' && grupo.dataset.provinciaId === provinciaId;
                        grupo.classList.toggle('d-none', !aplica);
                        hayGrupo = hayGrupo || aplica;

                        // Un radio de otra ciudad, aunque esté oculto, seguiría
                        // viajando en el pedido: desactivado desaparece del envío.
                        grupo.querySelectorAll('input[name="agencia_provincia_id"]').forEach(radio => {
                            radio.disabled = !aplica;
                            if (!aplica) radio.checked = false;
                        });
                    });

                    agenciasCard.classList.toggle('d-none', !hayGrupo);
                    if (!hayGrupo) {
                        document.getElementById('agencia-provincia-alert')?.classList.add('d-none');
                    }
                    // Sin agencia en esa ciudad el bloque desaparecía en silencio y el
                    // cliente leía una pantalla rota: aquí dice qué pasa en su lugar.
                    document.getElementById('agencia-provincia-nota')?.classList.toggle('d-none', !(mostrar && !hayGrupo));
                }

                function pintarPuntoElegido(deliveryMode) {
                    const linea = document.getElementById('delivery-point-line');
                    if (!linea) return;

                    const radio = deliveryMode === 'recojo'
                        ? document.querySelector('input[name="punto_recojo_id"]:checked')
                        : (deliveryMode === 'provincia'
                            ? document.querySelector('input[name="agencia_provincia_id"]:checked')
                            : null);
                    const tarjeta = radio ? document.querySelector('label[for="' + radio.id + '"]') : null;

                    if (!tarjeta) {
                        linea.classList.add('d-none');
                        linea.textContent = '';
                        return;
                    }

                    // El nombre del local va primero y después la dirección: del cartón
                    // del carrito solo interesa eso, no el horario ni el teléfono.
                    const partes = [...tarjeta.querySelectorAll('span')].map(span => span.textContent.trim()).filter(Boolean);
                    linea.textContent = (deliveryMode === 'recojo' ? 'Recojo en ' : 'Agencia ') + partes[0] + (partes[1] ? ' · ' + partes[1] : '');
                    linea.classList.remove('d-none');
                }

                function descuentoCuponActual(subtotal, porcentaje) {
                    if (!porcentaje) return 0;
                    const base = Math.max(0, subtotal);
                    const redondeado = (monto) => Math.round((monto + Number.EPSILON) * 100) / 100;
                    return Math.min(redondeado(base * porcentaje / 100), redondeado(base));
                }

                function recalcularCupon() {
                    const descuento = descuentoCuponActual(subtotalAcumulado, cuponPorcentaje);

                    if (cuponContainer) {
                        cuponContainer.classList.toggle('d-none', !cuponCodigo || descuento <= 0);
                    }
                    if (cuponCodigoLabel) cuponCodigoLabel.textContent = cuponCodigo;
                    if (cuponPorcentajeLabel) cuponPorcentajeLabel.textContent = `−${cuponPorcentaje}%`;
                    if (cuponDisplay) cuponDisplay.textContent = '-' + formatearPrecioJS(descuento);

                    return descuento;
                }

                function recalculateCart() {
                    if (!checkoutForm) return;

                    let shippingCost = 0;
                    let selectedOption = 'standard';
                    const envioEstandarGratis = subtotalAcumulado >= reglasEnvio.umbral_gratis;

                    let deliveryMode = 'envio';
                    deliveryRadios.forEach(radio => {
                        if (radio.checked) {
                            deliveryMode = radio.value;
                        }
                    });

                    const muestraDireccion = deliveryMode !== 'recojo';
                    if (shippingAddressContainer) shippingAddressContainer.style.display = muestraDireccion ? 'block' : 'none';
                    if (recojoCard) recojoCard.classList.toggle('d-none', deliveryMode !== 'recojo');
                    if (addressHint) {
                        addressHint.textContent = deliveryMode === 'provincia'
                            ? 'Indica la ciudad donde debe llegar tu pedido: lo despachamos por agencia.'
                            : 'Usamos esta dirección para despachar tu pedido.';
                    }
                    // Un <select> oculto y obligatorio invalida el formulario sin
                    // que el cliente entienda por qué no avanza.
                    if (addressSelect) addressSelect.required = muestraDireccion && hayDirecciones;
                    if (agenciasCard) pintarAgenciasProvincia(deliveryMode === 'provincia');

                    if (deliveryMode === 'recojo') {
                        if (shippingOptionsContainer) shippingOptionsContainer.style.display = 'none';
                        if (provinciaNote) provinciaNote.classList.add('d-none');
                        shippingCost = 0;
                        selectedOption = 'pickup';
                    } else if (deliveryMode === 'provincia') {
                        // El despacho por agencia no tiene tarifa fija en la tienda:
                        // se coordina antes de enviar, así que el carrito no suma nada.
                        if (shippingOptionsContainer) shippingOptionsContainer.style.display = 'none';
                        if (provinciaNote) provinciaNote.classList.remove('d-none');
                        shippingCost = 0;
                        selectedOption = 'provincia';
                    } else {
                        if (shippingOptionsContainer) shippingOptionsContainer.style.display = 'block';
                        if (provinciaNote) provinciaNote.classList.add('d-none');

                        if (standardPriceLabel) {
                            standardPriceLabel.textContent = envioEstandarGratis
                                ? 'Gratis'
                                : formatearPrecioJS(reglasEnvio.costo_estandar);
                        }
                        if (umbralHint) umbralHint.classList.toggle('d-none', envioEstandarGratis);

                        if (expressRadio && expressRadio.checked) {
                            shippingCost = parseFloat(reglasEnvio.costo_express);
                            selectedOption = 'express';
                        } else {
                            if (standardRadio) standardRadio.checked = true;
                            shippingCost = envioEstandarGratis ? 0 : parseFloat(reglasEnvio.costo_estandar);
                            selectedOption = envioEstandarGratis ? 'free' : 'standard';
                        }
                    }

                    if (shippingDisplay) {
                        // «Gratis» mentiría en provincias: ahí el costo no es cero,
                        // es un número que falta coordinar antes de despachar.
                        shippingDisplay.innerText = deliveryMode === 'provincia'
                            ? 'Se coordina'
                            : (shippingCost > 0 ? formatearPrecioJS(shippingCost) : 'Gratis');
                    }

                    if (shippingLabel) {
                        shippingLabel.textContent = deliveryMode === 'recojo'
                            ? 'Recojo en tienda'
                            : (deliveryMode === 'provincia' ? 'Envío a provincias' : 'Costo de envío');
                    }

                    pintarPuntoElegido(deliveryMode);

                    if (shippingMessage) {
                        shippingMessage.classList.remove('text-success', 'text-muted', 'text-info');

                        if (deliveryMode === 'recojo') {
                            shippingMessage.textContent = 'Sin costo de envío al recoger en tienda.';
                            shippingMessage.classList.add('text-info');
                        } else if (deliveryMode === 'provincia') {
                            shippingMessage.textContent = 'Envío a provincias: lo coordinamos antes de despachar.';
                            shippingMessage.classList.add('text-info');
                        } else if (selectedOption === 'express') {
                            shippingMessage.textContent = envioEstandarGratis
                                ? 'Elegiste envío urgente: tu pedido paga solo el urgente, el estándar ya era gratis.'
                                : 'Envío urgente para que llegue antes.';
                            shippingMessage.classList.add('text-muted');
                        } else if (envioEstandarGratis) {
                            shippingMessage.textContent = 'Tu pedido tiene envío estándar gratis.';
                            shippingMessage.classList.add('text-success');
                        } else {
                            shippingMessage.textContent = `Te faltan ${formatearPrecioJS(reglasEnvio.umbral_gratis - subtotalAcumulado)} para obtener envío gratis.`;
                            shippingMessage.classList.add('text-muted');
                        }
                    }

                    // Barra de progreso hacia el envío gratis: se apoya en el
                    // subtotal antes del cupón, igual que el cálculo del umbral.
                    const umbralGratis = parseFloat(reglasEnvio.umbral_gratis) || 0;
                    const progresoEnvio = umbralGratis > 0
                        ? Math.max(0, Math.min(100, Math.round(subtotalAcumulado / umbralGratis * 100)))
                        : 100;
                    const metaAlcanzada = progresoEnvio >= 100;

                    if (gCartShipBar && gCartShipFill) {
                        gCartShipBar.setAttribute('aria-valuenow', String(progresoEnvio));
                        gCartShipFill.style.width = progresoEnvio + '%';
                    }
                    if (gCartShip) gCartShip.classList.toggle('is-done', metaAlcanzada);
                    if (gCartShipTitle) {
                        gCartShipTitle.textContent = metaAlcanzada ? '¡Envío gratis desbloqueado!' : 'Envío gratis';
                    }
                    if (gCartShipAmount) {
                        gCartShipAmount.textContent = metaAlcanzada
                            ? 'Meta alcanzada'
                            : `${formatearPrecioJS(subtotalAcumulado)} / ${formatearPrecioJS(umbralGratis)}`;
                    }

                    const descuento = recalcularCupon();
                    const total = Math.max(0, subtotalAcumulado - descuento + shippingCost);
                    if (totalDisplay) {
                        totalDisplay.innerText = formatearPrecioJS(total);
                    }
                    checkoutQrTotals.forEach(amount => {
                        amount.textContent = formatearPrecioJS(total);
                    });

                    // El botón de copiar tiene que llevar el importe que se ve,
                    // no el que había al cargar la página: el envío y el cupón lo
                    // cambian después.
                    checkoutCopyTotals.forEach(button => {
                        button.dataset.copyPaymentNumber = total.toFixed(divisaDecimales);
                    });

                    if (hiddenTotalInput) hiddenTotalInput.value = total.toFixed(2);
                    if (hiddenShippingOption) hiddenShippingOption.value = selectedOption;

                    updatePaymentOptionAction();
                }

                if (shippingRadios.length > 0) {
                    shippingRadios.forEach(radio => {
                        radio.addEventListener('change', recalculateCart);
                    });
                }

                if (deliveryRadios.length > 0) {
                    deliveryRadios.forEach(radio => {
                        radio.addEventListener('change', recalculateCart);
                    });
                }

                paymentOptions.forEach(radio => {
                    radio.addEventListener('change', updatePaymentOptionAction);
                });

                document.querySelectorAll('[data-checkout-change-method]').forEach(button => {
                    button.addEventListener('click', () => {
                        // «Cambiar método» reabre la lista con lo elegido marcado:
                        // desmarcar aquí dejaría el pedido sin método si el cliente
                        // cierra la ventana sin tocar nada.
                        if (modalMetodos && window.bootstrap?.Modal) {
                            // Se aplica antes de la animación: «shown.bs.modal» llega
                            // tarde cuando el navegador pospone la transición y el
                            // disparador quedaría diciendo «Elige un medio de pago»
                            // con la tarjeta ya marcada dentro de la ventana.
                            aplicarMetodoRecordado();
                            window.bootstrap.Modal.getOrCreateInstance(modalMetodos).show();
                            return;
                        }
                        document.getElementById('payment-method-picker')?.focus();
                    });
                });

                recalculateCart();

                if (checkoutForm && btnSubmitCheckout) {
                    const espera = document.getElementById('checkout-espera');
                    const esperaLarga = document.querySelector('[data-checkout-espera-larga]');
                    const volverDatos = document.querySelector('[data-checkout-previous="2"]');
                    let temporizadorEspera = null;

                    // Los botones de pago del modal viven fuera del <form> (se
                    // enganchan con form=), así que buscarlos dentro del
                    // formulario los dejaba libres: un segundo toque creaba otro
                    // pedido encima del que ya estaba en vuelo.
                    const botonesDePago = () => document.querySelectorAll(
                        '#checkout-form button[type="submit"], button[type="submit"][form="checkout-form"]'
                    );

                    checkoutForm.addEventListener('submit', function(event) {
                        if (!document.querySelector('input[name="metodo_pago"]:checked')) {
                            return;
                        }
                        // Cualquier submit del mismo formulario se bloquea aquí:
                        // el atajo de pago de la página no debe crear un segundo
                        // pedido mientras el primero sigue en vuelo.
                        btnSubmitCheckout.disabled = true;
                        btnSubmitCheckout.setAttribute('aria-busy', 'true');
                        btnSubmitCheckout.innerHTML = '<span class="spinner-border spinner-border-sm me-2" aria-hidden="true"></span>Procesando pago...';
                        botonesDePago().forEach(function(boton) {
                            boton.disabled = true;
                        });
                        volverDatos?.setAttribute('disabled', 'disabled');
                        espera?.classList.remove('d-none');
                        temporizadorEspera = setTimeout(function() {
                            esperaLarga?.removeAttribute('hidden');
                        }, 12000);
                    });

                    // Al volver con el botón atrás desde Mercado Pago, el navegador
                    // devuelve esta pantalla tal como estaba: bloqueada.
                    window.addEventListener('pageshow', function(event) {
                        if (!event.persisted) {
                            return;
                        }
                        clearTimeout(temporizadorEspera);
                        espera?.classList.add('d-none');
                        esperaLarga?.setAttribute('hidden', 'hidden');
                        volverDatos?.removeAttribute('disabled');
                        botonesDePago().forEach(function(boton) {
                            boton.disabled = false;
                        });
                        btnSubmitCheckout.removeAttribute('aria-busy');
                        updatePaymentOptionAction();
                    });
                }

                // AJAX Form Submit for updating cart quantities
                const formActualizar = document.getElementById('form-actualizar-carrito');
                if (formActualizar) {
                    let cartUpdateInProgress = false;
                    const updateCartButton = document.querySelector('[form="form-actualizar-carrito"]');
                    const updateCartButtonLabel = updateCartButton?.innerHTML;

                    formActualizar.addEventListener('submit', function(e) {
                        e.preventDefault();
                        if (cartUpdateInProgress) return;

                        cartUpdateInProgress = true;
                        // El FormData se arma ANTES de desactivar nada: un input
                        // deshabilitado no es enviable y el navegador lo descarta,
                        // con lo que el carrito llegaría vacío al servidor.
                        const formData = new FormData(this);

                        if (updateCartButton) {
                            updateCartButton.disabled = true;
                            updateCartButton.innerHTML = '<span class="spinner-border spinner-border-sm me-2" aria-hidden="true"></span>Guardando...';
                        }
                        document.querySelectorAll('.quantity-input, .quantity-btn').forEach(control => {
                            control.disabled = true;
                        });

                        fetch(this.action, {
                            method: 'POST',
                            headers: {
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest'
                            },
                            body: formData
                        })
                        .then(res => {
                            const contentType = res.headers.get('content-type');
                            if (contentType && contentType.includes('application/json')) {
                                return res.json().then(data => {
                                    if (!res.ok) {
                                        if (res.status === 401 || data.code === 401) {
                                            window.location.href = '{{ route("web.login") }}';
                                            return;
                                        }
                                        return Promise.reject(data);
                                    }
                                    return data;
                                });
                            } else {
                                if (res.status === 401 || res.status === 419 || res.redirected) {
                                    window.location.href = '{{ route("web.login") }}';
                                    return;
                                }
                                throw new Error('El servidor devolvió una respuesta no válida (HTML).');
                            }
                        })
                        .then(data => {
                            if (!data) return;
                            if (data.success) {
                                Toast.fire({
                                    icon: 'success',
                                    title: data.mensaje || 'Carrito actualizado correctamente'
                                });

                                // Update item quantities, totals, and savings in the DOM
                                data.items.forEach(item => {
                                    const input = document.getElementById('quantity-input-' + item.id);
                                    if (input) {
                                        input.value = item.cantidad;
                                        const summaryQuantity = document.querySelector(`[data-cart-summary-quantity="${item.id}"]`);
                                        const summaryTotal = document.querySelector(`[data-cart-summary-total="${item.id}"]`);
                                        if (summaryQuantity) summaryQuantity.textContent = item.cantidad;
                                        if (summaryTotal) summaryTotal.textContent = item.subtotal_item_formateado;

                                        const row = input.closest('.row');
                                        if (row) {
                                            const itemTotalDisplay = row.querySelector('.item-total');
                                            if (itemTotalDisplay) {
                                                let html = `<span class="g-cart-price">${item.subtotal_item_formateado}</span>`;
                                                if (item.ahorro_item > 0) {
                                                    html += `<small class="g-cart-saving">Ahorras ${item.ahorro_item_formateado}</small>`;
                                                }
                                                itemTotalDisplay.innerHTML = html;
                                            }
                                        }
                                    }
                                });

                                // Update cart counters
                                const cartCounters = document.querySelectorAll('.cart-counter');
                                cartCounters.forEach(c => c.textContent = `(${data.contador})`);

                                // Update summary subtotals
                                const subtotalOriginalDisplay = document.getElementById('subtotal-original-display');
                                const subtotalOriginalContainer = document.getElementById('subtotal-original-container');
                                if (subtotalOriginalDisplay) {
                                    subtotalOriginalDisplay.textContent = data.subtotal_original_acumulado_formateado;
                                }

                                const subtotalDisplay = document.getElementById('subtotal-display');
                                if (subtotalDisplay) {
                                    subtotalDisplay.textContent = data.subtotal_acumulado_formateado;
                                }

                                // Update discount container and saving badge
                                const discountContainer = document.getElementById('descuento-container');
                                const discountDisplay = document.getElementById('descuento-display');
                                const savingBadge = document.getElementById('saving-badge');
                                const savingBadgeAmount = document.getElementById('saving-badge-amount');
                                if (subtotalOriginalContainer) {
                                    subtotalOriginalContainer.classList.toggle(
                                        'd-none',
                                        data.subtotal_original_acumulado <= data.subtotal_acumulado
                                    );
                                }

                                const diff = data.subtotal_original_acumulado - data.subtotal_acumulado;
                                if (diff > 0) {
                                    if (discountContainer) {
                                        discountContainer.classList.remove('d-none');
                                    }
                                    if (discountDisplay) {
                                        const diffFormateada = formatearPrecioJS(diff);
                                        discountDisplay.textContent = '-' + diffFormateada;
                                    }
                                    if (savingBadge) {
                                        savingBadge.classList.remove('d-none');
                                    }
                                    if (savingBadgeAmount) {
                                        savingBadgeAmount.textContent = formatearPrecioJS(diff);
                                    }
                                } else {
                                    if (discountContainer) {
                                        discountContainer.classList.add('d-none');
                                    }
                                    if (savingBadge) {
                                        savingBadge.classList.add('d-none');
                                    }
                                }

                                // Update the JS global variable
                                subtotalAcumulado = data.subtotal_acumulado;
                                cuponCodigo = data.cupon?.codigo ?? '';
                                cuponPorcentaje = data.cupon?.porcentaje ?? 0;

                                // Call recalculateCart to update standard/express/free shipping and total to pay
                                recalculateCart();

                                // Update mini cart dropdown dynamically
                                fetch(window.location.href)
                                    .then(res => res.text())
                                    .then(html => {
                                        const parser = new DOMParser();
                                        const doc = parser.parseFromString(html, 'text/html');
                                        const newDropdown = doc.querySelector('.mini-cart-dropdown-wrapper');
                                        const currentDropdown = document.querySelector('.mini-cart-dropdown-wrapper');
                                        if (newDropdown && currentDropdown) {
                                            currentDropdown.innerHTML = newDropdown.innerHTML;
                                        }
                                    });
                            } else {
                                Toast.fire({
                                    icon: 'error',
                                    title: data.mensaje || 'Error al actualizar el carrito.'
                                });
                            }
                        })
                        .catch(err => {
                            console.error(err);
                            Toast.fire({
                                icon: 'error',
                                title: err.mensaje || err.message || 'Ocurrió un error al actualizar el carrito.'
                            });
                        })
                        .finally(() => {
                            cartUpdateInProgress = false;
                            if (updateCartButton) {
                                updateCartButton.disabled = false;
                                updateCartButton.innerHTML = updateCartButtonLabel;
                            }
                            document.querySelectorAll('.quantity-input, .quantity-btn').forEach(control => {
                                control.disabled = false;
                            });
                        });
                    });
                }
            });
        </script>
    </section><!-- /Cart Section -->
@endsection
