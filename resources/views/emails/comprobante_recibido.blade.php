@php
    $cliente = $orden->usuario;
    $metodo = ucfirst((string) ($pago->metodo_pago ?: $orden->metodo_pago));
@endphp
<div style="font-family: system-ui, sans-serif; max-width: 620px; margin: 0 auto; color: #1f2d3d;">
    <h2 style="margin: 0 0 .5rem;">Un cliente subió su comprobante</h2>
    <p style="margin: 0 0 1.25rem; color: #495057;">
        Yape y Plin no avisan solos: este correo es la señal de que hay un abono que verificar en la app del banco.
    </p>

    <table style="width: 100%; border-collapse: collapse; font-size: 15px;">
        <tr>
            <td style="padding: .5rem 0; color: #6c757d;">Pedido</td>
            <td style="padding: .5rem 0; text-align: right;"><strong>#{{ $orden->id }}</strong></td>
        </tr>
        <tr>
            <td style="padding: .5rem 0; color: #6c757d;">Método</td>
            <td style="padding: .5rem 0; text-align: right;"><strong>{{ $metodo }}</strong></td>
        </tr>
        <tr>
            <td style="padding: .5rem 0; color: #6c757d;">Importe a verificar</td>
            <td style="padding: .5rem 0; text-align: right;">
                <strong style="font-size: 18px;">{{ \App\Models\Ajuste::formatear_precio($orden->total) }}</strong>
            </td>
        </tr>
        <tr>
            <td style="padding: .5rem 0; color: #6c757d;">Cliente</td>
            <td style="padding: .5rem 0; text-align: right;">{{ $cliente->name ?? ($orden->cliente_email ?: 'sin nombre') }}</td>
        </tr>
        @if (! empty($cliente->telefono))
            <tr>
                <td style="padding: .5rem 0; color: #6c757d;">Teléfono del cliente</td>
                <td style="padding: .5rem 0; text-align: right;">{{ $cliente->telefono }}</td>
            </tr>
        @endif
        <tr>
            <td style="padding: .5rem 0; color: #6c757d;">Hora del comprobante</td>
            <td style="padding: .5rem 0; text-align: right;">{{ $pago->comprobante_enviado_at?->format('d/m/Y H:i') ?? 'ahora' }}</td>
        </tr>
    </table>

    <p style="margin: 1.25rem 0 .75rem; padding: .9rem 1rem; background: #fff8e6; border: 1px solid #ffe08a; border-radius: 8px; font-size: 14px;">
        Abre la app {{ $metodo }} y comprueba que el abono esté y que el importe coincida al centavo.
        No confirmes el pedido solo por la imagen del comprobante.
    </p>

    <p style="margin: 0;">
        <a href="{{ $urlRevision }}"
           style="display: inline-block; padding: .7rem 1.2rem; background: #0d6efd; color: #fff; border-radius: 999px; text-decoration: none; font-weight: 600;">
            Revisar el pedido #{{ $orden->id }}
        </a>
    </p>
</div>
