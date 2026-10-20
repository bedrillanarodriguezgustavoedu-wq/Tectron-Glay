<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pedido Enviado Nº {{ $orden->id }}</title>
</head>

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
                background:linear-gradient(135deg,#5e1220,#8b2635);
                padding:40px 30px;
                text-align:center;
            ">
                <h1 style="
                    margin:0;
                    color:#ffffff;
                    font-size:32px;
                    font-weight:700;
                ">
                    🚚 Pedido Enviado
                </h1>

                <p style="
                    margin-top:12px;
                    color:#f1d7dc;
                    font-size:15px;
                ">
                    Tu pedido ya está en camino
                </p>
            </div>

            <!-- CONTENIDO -->
            <div style="padding:35px 30px 25px;">

                <!-- MENSAJE PRINCIPAL -->
                <div style="
                    text-align:center;
                    margin-bottom:35px;
                ">

                    <div style="
                        width:70px;
                        height:70px;
                        margin:0 auto 20px;
                        border-radius:50%;
                        background:#fdf2f4;
                        line-height:70px;
                        font-size:35px;
                        color:#8b2635;
                        font-weight:bold;
                        border:2px solid #f3d7dc;
                    ">
                        🚚
                    </div>

                    <h2 style="
                        margin:0;
                        color:#111827;
                        font-size:26px;
                    ">
                        ¡Tu pedido fue enviado!
                    </h2>

                    <p style="
                        margin-top:12px;
                        color:#6b7280;
                        line-height:1.6;
                    ">
                        Hola <strong>{{ $orden->usuario->name }}</strong>, tu pedido ha sido despachado exitosamente y ya se encuentra en proceso de entrega.
                    </p>

                </div>

                <!-- RESUMEN DEL PEDIDO -->
                <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:30px;">
                    <tr>
                        <td style="
                            background:#f9fafb;
                            border:1px solid #e5e7eb;
                            border-left:5px solid #8b2635;
                            border-radius:8px;
                            padding:18px;
                            text-align:center;
                        ">
                            <div style="
                                font-size:12px;
                                color:#6b7280;
                                margin-bottom:5px;
                            ">
                                NÚMERO DE ORDEN
                            </div>

                            <div style="
                                font-size:22px;
                                font-weight:bold;
                                color:#111827;
                            ">
                                #{{ $orden->id }}
                            </div>
                        </td>
                    </tr>
                </table>

                <!-- ESTADO DEL PEDIDO -->
                <div style="margin-bottom:35px;">

                    <h3 style="
                        margin-top:0;
                        margin-bottom:20px;
                        color:#8b2635;
                        font-size:20px;
                    ">
                        Estado del Pedido
                    </h3>

                    <table width="100%" cellpadding="0" cellspacing="0">
                        <tr>

                            <td width="33%" align="center">

                                <div style="
                                    width:50px;
                                    height:50px;
                                    line-height:50px;
                                    border-radius:50%;
                                    background:#8b2635;
                                    color:#ffffff;
                                    margin:auto;
                                    font-size:18px;
                                ">
                                    ✓
                                </div>

                                <p style="
                                    color:#111827;
                                    font-size:13px;
                                    font-weight:bold;
                                ">
                                    Confirmado
                                </p>

                            </td>

                            <td width="33%" align="center">

                                <div style="
                                    width:50px;
                                    height:50px;
                                    line-height:50px;
                                    border-radius:50%;
                                    background:#8b2635;
                                    color:#ffffff;
                                    margin:auto;
                                    font-size:18px;
                                ">
                                    🚚
                                </div>

                                <p style="
                                    color:#111827;
                                    font-size:13px;
                                    font-weight:bold;
                                ">
                                    Enviado
                                </p>

                            </td>

                            <td width="33%" align="center">

                                <div style="
                                    width:50px;
                                    height:50px;
                                    line-height:50px;
                                    border-radius:50%;
                                    background:#e5e7eb;
                                    color:#9ca3af;
                                    margin:auto;
                                    font-size:18px;
                                ">
                                    📍
                                </div>

                                <p style="
                                    color:#9ca3af;
                                    font-size:13px;
                                ">
                                    Entregado
                                </p>

                            </td>

                        </tr>
                    </table>

                </div>

                <!-- INFORMACIÓN DE ENTREGA -->
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
                            font-size:18px;
                        ">
                            Información de Entrega
                        </h3>
                    </div>

                    <div style="padding:20px;">

                        <table width="100%" cellpadding="0" cellspacing="0">

                            <tr>
                                <td style="padding-bottom:15px;">
                                    <strong style="display:block;color:#111827;margin-bottom:4px;">
                                        Cliente
                                    </strong>

                                    <span style="color:#6b7280;">
                                        {{ $orden->usuario->name }}
                                    </span>
                                </td>
                            </tr>

                            <tr>
                                <td style="padding-bottom:15px;">
                                    <strong style="display:block;color:#111827;margin-bottom:4px;">
                                        Correo Electrónico
                                    </strong>

                                    <span style="color:#6b7280;">
                                        {{ $orden->usuario->email }}
                                    </span>
                                </td>
                            </tr>

                            <tr>
                                <td style="padding-bottom:15px;">
                                    <strong style="display:block;color:#111827;margin-bottom:4px;">
                                        Dirección de Envío
                                    </strong>

                                    <span style="color:#6b7280;">
                                        {{ $orden->direccion_envio }}
                                    </span>
                                </td>
                            </tr>

                            <tr>
                                <td>
                                    <strong style="display:block;color:#111827;margin-bottom:4px;">
                                        Nota de Seguimiento
                                    </strong>

                                    <span style="color:#6b7280;">
                                        @if(!empty($orden->nota))
                                            {!! $orden->nota !!}
                                        @else
                                            Sin observaciones.
                                        @endif
                                    </span>
                                </td>
                            </tr>

                        </table>

                    </div>

                </div>

                <!-- MENSAJE FINAL -->
                <div style="
                    padding:20px;
                    background:#f9fafb;
                    border:1px solid #e5e7eb;
                    border-radius:10px;
                ">
                    <p style="
                        margin:0;
                        color:#4b5563;
                        line-height:1.7;
                    ">
                        Gracias por confiar en nosotros. Tu pedido está siendo transportado y muy pronto llegará a su destino. Te notificaremos nuevamente cuando haya sido entregado.
                    </p>
                </div>

                <p style="
                    margin-top:30px;
                    color:#111827;
                    font-weight:bold;
                    font-size:16px;
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