<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Confirmación de Pedido Nº {{ $orden->id }}</title>
</head>
<body style="margin:0;padding:0;background-color:#f3f4f6;font-family:Arial,Helvetica,sans-serif;color:#374151;">
    <div style="padding:40px 15px;">
        <div
            style="max-width:700px;
                    margin:auto;
                    background:#ffffff;
                    border-radius:12px;
                    overflow:hidden;
                    border:1px solid #e5e7eb;
                    box-shadow:0 10px 25px rgba(0,0,0,.08);">
            <!-- CABECERA -->
            <div
                style="background:#1f2937;
                        padding:40px 30px;
                        text-align:center;">
                <h1
                    style="margin:0;
                           color:#ffffff;
                           font-size:32px;
                           font-weight:700;">
                    Confirmación de Pedido
                </h1>
                <p
                    style="margin-top:12px;
                          color:#d1d5db;
                          font-size:15px;">
                    Gracias por tu compra. Hemos recibido tu pedido correctamente.
                </p>
            </div>
            <!-- CONTENIDO -->
            <div style="padding:35px 30px 25px;">
                <!-- MENSAJE PRINCIPAL -->
                <div style="text-align:center;
                            margin-bottom:35px;">
                    <div
                        style="width:70px;
                                height:70px;
                                margin:0 auto 20px;
                                border-radius:50%;
                                background:#f3f4f6;
                                line-height:70px;
                                font-size:35px;
                                color:#111827;
                                font-weight:bold;">
                        ✓
                    </div>
                    <h2
                        style="margin:0;
                               color:#111827;
                               font-size:26px;">
                        Pedido Confirmado
                    </h2>
                    <p
                        style="margin-top:12px;
                              color:#6b7280;
                              line-height:1.6;">
                        Tu pedido ha sido procesado exitosamente.
                        A continuación encontrarás el resumen completo de tu compra.
                    </p>
                </div>
                <!-- RESUMEN DEL PEDIDO -->
                <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:30px;">
                    <tr>
                        <td
                            style="background:#f9fafb;
                                   border:1px solid #e5e7eb;
                                   border-radius:8px;
                                   padding:18px;
                                   text-align:center;">
                            <div
                                style="font-size:12px;
                                        color:#6b7280;
                                        margin-bottom:5px;">
                                NÚMERO DE ORDEN
                            </div>
                            <div
                                style="font-size:20px;
                                        font-weight:bold;
                                        color:#111827;">
                                #{{ $orden->id }}
                            </div>
                        </td>
                    </tr>
                </table>
                <!-- INFORMACIÓN DE ENTREGA -->
                <div
                    style="border:1px solid #e5e7eb;
                            border-radius:10px;
                            margin-bottom:30px;">
                    <div
                        style="padding:15px 20px;
                                background:#f9fafb;
                                border-bottom:1px solid #e5e7eb;">
                        <h3
                            style="margin:0;
                                   color:#111827;
                                   font-size:18px;">
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
                                        {{ $orden->usuario->name ?? 'Cliente' }}
                                    </span>
                                </td>
                            </tr>
                            <tr>
                                <td style="padding-bottom:15px;">
                                    <strong style="display:block;color:#111827;margin-bottom:4px;">
                                        Correo Electrónico
                                    </strong>
                                    <span style="color:#6b7280;">
                                        {{ $orden->usuario->email ?? 'email' }}
                                    </span>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <strong style="display:block;color:#111827;margin-bottom:4px;">
                                        Dirección de Envío
                                    </strong>
                                    <span style="color:#6b7280;">
                                        {{ $orden->direccion_envio }}
                                    </span>
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>
                <!-- PRODUCTOS -->
                <div style="margin-bottom:30px;">
                    <h3
                        style="margin-top:0;
                               margin-bottom:15px;
                               color:#111827;
                               font-size:20px;">
                        Productos Adquiridos
                    </h3>
                    @foreach ($orden->detalles as $detalle)
                        <div
                            style="display:flex;
                                    align-items:center;
                                    padding:15px;
                                    border:1px solid #e5e7eb;
                                    border-radius:10px;
                                    margin-bottom:10px;
                                    background:#fafafa;">
                            @if ($detalle->producto)
                                @php
                                    $imagen = $detalle->producto->imagen_principal_url;
                                @endphp
                                <img src="{{ $imagen }}" alt="{{ $detalle->producto->nombre }}"
                                    style="width:70px;
                                            height:70px;
                                            object-fit:cover;
                                            border-radius:8px;
                                            border:1px solid #d1d5db;
                                            margin-right:15px;">
                            @else
                                <div
                                    style="width:70px;
                                            height:70px;
                                            background:#e5e7eb;
                                            border-radius:8px;
                                            margin-right:15px;">
                                </div>
                            @endif
                            <div style="flex:1;">
                                <div
                                    style="font-size:15px;
                                            font-weight:bold;
                                            color:#111827;
                                            margin-bottom:5px;">
                                    {{ $detalle->producto->nombre }}
                                </div>
                                <div style="font-size:13px;
                                            color:#6b7280;">
                                    Cantidad:
                                    {{ $detalle->cantidad }}
                                </div>
                                <div style="font-size:13px;
                                            color:#6b7280;">
                                    Precio Unitario:
                                    {{ \App\Models\Ajuste::formatear_precio($detalle->precio) }}
                                </div>
                            </div>
                            <div style="text-align:right;">
                                <div
                                    style="font-size:16px;
                                            font-weight:bold;
                                            color:#111827;">
                                    {{ \App\Models\Ajuste::formatear_precio($detalle->cantidad * $detalle->precio) }}
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
                <!-- RESUMEN DE PAGO -->
                <div
                    style="border:1px solid #e5e7eb;
                            border-radius:10px;
                            overflow:hidden;">
                    <div
                        style="padding:15px 20px;
                                background:#f9fafb;
                                border-bottom:1px solid #e5e7eb;">
                        <h3
                            style="margin:0;
                                   color:#111827;
                                   font-size:18px;">
                            Resumen del Pago
                        </h3>
                    </div>
                    <table width="100%" cellpadding="20" cellspacing="0">
                        <tr>
                            <td style="font-size:15px;color:#6b7280;">
                                Total Final
                            </td>
                            <td align="right"
                                style="font-size:26px;
                                       font-weight:bold;
                                       color:#111827;">
                                {{ \App\Models\Ajuste::formatear_precio($orden->total) }}
                            </td>
                        </tr>
                    </table>
                </div>
                <!-- ACCIONES -->
                <div style="margin-top:30px;text-align:center;">
                    <a href="{{ url('/pedido/' . $orden->id . '/confirmacion') }}"
                        style="display:inline-block;padding:12px 24px;background:#111827;color:#ffffff;
                               text-decoration:none;border-radius:8px;font-weight:600;margin:0 6px 8px;">
                        Ver mi pedido
                    </a>
                </div>
                <!-- MENSAJE FINAL -->
                <div
                    style="margin-top:30px;
                            padding:20px;
                            background:#f9fafb;
                            border:1px solid #e5e7eb;
                            border-radius:10px;">
                    <p
                        style="margin:0;
                              color:#4b5563;
                              line-height:1.7;">
                        Hemos recibido tu pedido y pronto comenzaremos a procesarlo.
                        Recibirás una nueva notificación cuando el estado de tu compra cambie o cuando el pedido sea
                        enviado.
                    </p>
                </div>
            </div>
            <!-- FOOTER -->
            <div
                style="background:#111827;
                        padding:25px;
                        text-align:center;">
                <p style="margin:0;
                          color:#9ca3af;
                          font-size:12px;">
                    Este es un correo automático generado por el sistema.<br>
                    Por favor, no respondas a este mensaje.
                </p>
            </div>
        </div>
    </div>
</body>
</html>
