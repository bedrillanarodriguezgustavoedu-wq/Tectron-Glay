<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Actualización de Pedido Nº {{ $orden->id }}</title>
</head>

@php
    $estado_datos = [
        'En proceso' => ['icon' => '⚙️', 'color' => '#0d6efd', 'title' => 'Pedido en proceso', 'msg' => 'Estamos procesando tu pedido y preparando la compra.'],
        'Recibido' => ['icon' => '📥', 'color' => '#0dcaf0', 'title' => 'Pedido recibido', 'msg' => 'Tu pedido ha sido recibido en nuestro almacén y se está empaquetando.'],
        'Enviado' => ['icon' => '🚚', 'color' => '#ffc107', 'title' => 'Pedido enviado', 'msg' => 'Tu pedido ha sido despachado y está en manos de la courier.'],
        'En transporte' => ['icon' => '🛣️', 'color' => '#6c757d', 'title' => 'Pedido en transporte', 'msg' => 'Tu pedido se encuentra en ruta de entrega hacia tu dirección.'],
        'Listo para su recojo' => ['icon' => '🏪', 'color' => '#198754', 'title' => 'Listo para recoger', 'msg' => 'Tu pedido está listo para recoger en tienda. ¡Puedes pasar por él!'],
        'Entregado' => ['icon' => '🎉', 'color' => '#198754', 'title' => 'Pedido entregado', 'msg' => 'Tu pedido ha sido entregado correctamente. ¡Gracias por tu compra!'],
    ];

    $datos = $estado_datos[$estado] ?? ['icon' => '📦', 'color' => '#212529', 'title' => 'Pedido actualizado', 'msg' => 'Tu pedido ha cambiado de estado.'];
    $flujo = $orden->getFlujoEstados();
    $current_index = array_search($estado, $flujo);
@endphp

<body style="margin:0;padding:0;background-color:#f3f4f6;font-family:Arial,Helvetica,sans-serif;color:#374151;">

    <div style="padding:40px 15px;">

        <div style="
            max-width:700px;
            margin:auto;
            background:#ffffff;
            border-radius:12px;
            overflow:hidden;
            border:1px solid #e5e7eb;
            box-shadow:0 10px 25px rgba(0,0,0,.08);
        ">

            <!-- CABECERA -->
            <div style="
                background: linear-gradient(135deg, {{ $datos['color'] }}, #212529);
                padding:40px 30px;
                text-align:center;
            ">
                <h1 style="
                    margin:0;
                    color:#ffffff;
                    font-size:32px;
                    font-weight:700;
                ">
                    {{ $datos['icon'] }} {{ $datos['title'] }}
                </h1>

                <p style="
                    margin-top:12px;
                    color:#f3f4f6;
                    font-size:15px;
                ">
                    Pedido Nro: #{{ $orden->id }}
                </p>
            </div>

            <!-- CONTENIDO -->
            <div style="padding:35px 30px 25px;">

                <!-- MENSAJE PRINCIPAL -->
                <div style="
                    text-align:center;
                    margin-bottom:35px;
                ">
                    <p style="
                        margin-top:12px;
                        color:#4b5563;
                        font-size:16px;
                        line-height:1.6;
                    ">
                        Hola <strong>{{ $orden->usuario->name }}</strong>, {{ $datos['msg'] }}
                    </p>
                </div>

                <!-- RESUMEN DEL PEDIDO -->
                <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:30px;">
                    <tr>
                        <td style="
                            background:#f9fafb;
                            border:1px solid #e5e7eb;
                            border-left:5px solid {{ $datos['color'] }};
                            border-radius:8px;
                            padding:18px;
                            text-align:center;
                        ">
                            <div style="
                                font-size:12px;
                                color:#6b7280;
                                margin-bottom:5px;
                            ">
                                ESTADO ACTUAL
                            </div>

                            <div style="
                                font-size:22px;
                                font-weight:bold;
                                color:#111827;
                            ">
                                {{ $estado }}
                            </div>
                        </td>
                    </tr>
                </table>

                <!-- ESTADO DEL PEDIDO (Timeline horizontal simplificado) -->
                <div style="margin-bottom:35px;">
                    <h3 style="
                        margin-top:0;
                        margin-bottom:20px;
                        color:#111827;
                        font-size:18px;
                        border-bottom: 2px solid #f3f4f6;
                        padding-bottom: 10px;
                    ">
                        Progreso de tu pedido
                    </h3>

                    <table width="100%" cellpadding="0" cellspacing="0" style="text-align:center;">
                        <tr>
                            @foreach ($flujo as $idx => $f_estado)
                                @php
                                    $is_completed = $idx <= $current_index;
                                    $is_current = $idx == $current_index;
                                    $color = $is_completed ? $datos['color'] : '#e5e7eb';
                                    $text_color = $is_completed ? '#111827' : '#9ca3af';
                                @endphp
                                <td align="center" style="vertical-align: top;">
                                    <div style="
                                        width:36px;
                                        height:36px;
                                        line-height:36px;
                                        border-radius:50%;
                                        background:{{ $color }};
                                        color:#ffffff;
                                        margin:auto;
                                        font-size:14px;
                                        font-weight:bold;
                                    ">
                                        {{ $is_current ? '📍' : ($is_completed ? '✓' : $idx + 1) }}
                                    </div>

                                    <p style="
                                        color:{{ $text_color }};
                                        font-size:11px;
                                        margin-top:8px;
                                        font-weight:{{ $is_current ? 'bold' : 'normal' }};
                                        line-height:1.2;
                                    ">
                                        {{ $f_estado }}
                                    </p>
                                </td>
                            @endforeach
                        </tr>
                    </table>
                </div>

                <!-- DETALLE DE LA ENTREGA -->
                <div style="
                    border:1px solid #e5e7eb;
                    border-radius:10px;
                    margin-bottom:30px;
                    overflow:hidden;
                ">
                    <div style="
                        padding:15px 20px;
                        background:#f9fafb;
                        border-bottom:1px solid #e5e7eb;
                    ">
                        <h3 style="
                            margin:0;
                            color:#111827;
                            font-size:16px;
                        ">
                            Detalles del envío
                        </h3>
                    </div>

                    <div style="padding:20px;">
                        <table width="100%" cellpadding="0" cellspacing="0" style="font-size:14px; line-height: 1.5;">
                            <tr>
                                <td style="padding-bottom:10px; width:35%;"><strong>Modalidad:</strong></td>
                                <td style="padding-bottom:10px; color:#4b5563;">{{ $orden->getModalidadTexto() }}</td>
                            </tr>
                            @if ($orden->modalidad_entrega !== 'recojo')
                                <tr>
                                    <td style="padding-bottom:10px;"><strong>Dirección de envío:</strong></td>
                                    <td style="padding-bottom:10px; color:#4b5563;">{{ $orden->direccion_envio }}</td>
                                </tr>
                            @endif
                            @if (!empty($orden->nota))
                                <tr>
                                    <td style="padding-bottom:10px; vertical-align: top;"><strong>Observaciones:</strong></td>
                                    <td style="padding-bottom:10px; color:#4b5563; background: #fafafa; padding: 8px; border-radius: 4px;">
                                        {!! $orden->nota !!}
                                    </td>
                                </tr>
                            @endif
                            <tr>
                                <td><strong>Total a pagar:</strong></td>
                                <td style="font-weight: bold; color: {{ $datos['color'] }}">{{ $orden->divisa }} {{ number_format($orden->total, 2) }}</td>
                            </tr>
                        </table>
                    </div>
                </div>

                <!-- BOTÓN DE ACCESO -->
                <div style="text-align: center; margin-bottom: 30px;">
                    <a href="{{ url('/dashboard') }}" style="
                        display: inline-block;
                        padding: 12px 30px;
                        background-color: {{ $datos['color'] }};
                        color: #ffffff;
                        text-decoration: none;
                        font-weight: bold;
                        border-radius: 6px;
                        box-shadow: 0 4px 6px rgba(0,0,0,0.1);
                    ">
                        Seguir mi Pedido en la Web
                    </a>
                </div>

                <!-- MENSAJE FINAL -->
                <div style="
                    padding:20px;
                    background:#f9fafb;
                    border:1px solid #e5e7eb;
                    border-radius:10px;
                    text-align: center;
                ">
                    <p style="
                        margin:0;
                        color:#4b5563;
                        font-size: 13px;
                        line-height:1.7;
                    ">
                        Gracias por confiar en nosotros. Te mantendremos informado de cualquier novedad respecto a tu compra.
                    </p>
                </div>

                <p style="
                    margin-top:30px;
                    color:#111827;
                    font-weight:bold;
                    font-size:16px;
                    text-align: center;
                ">
                    {{ $ajuste->nombre }}
                </p>

            </div>

            <!-- FOOTER -->
            <div style="
                background:#111827;
                padding:25px;
                text-align:center;
            ">
                <p style="
                    margin:0;
                    color:#9ca3af;
                    font-size:12px;
                ">
                    Este es un correo automático generado por el sistema.<br>
                    Por favor, no respondas a este mensaje.
                </p>

                <p style="
                    margin-top:10px;
                    color:#6b7280;
                    font-size:11px;
                ">
                    © {{ date('Y') }} {{ $ajuste->nombre }}. Todos los derechos reservados.
                </p>
            </div>

        </div>

    </div>

</body>

</html>
