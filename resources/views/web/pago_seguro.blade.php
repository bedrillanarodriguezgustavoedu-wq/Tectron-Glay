@extends('layouts.web')

@section('title', 'Pago seguro · ' . ($ajuste->nombre ?? 'GALY'))

@section('content')
<section class="section py-5">
    <div class="container py-4">
        <div class="row justify-content-center">
            <div class="col-lg-10 col-md-12">
                <div class="card border-0 shadow-sm rounded-4 p-5 bg-white">
                    <div class="text-center mb-4">
                        <i class="bi bi-shield-lock-fill text-success" style="font-size: 3rem;"></i>
                        <h1 class="fw-bold mt-2">Pago 100% seguro</h1>
                        <p class="text-muted">Tu información está protegida en cada paso de la compra.</p>
                    </div>

                    <div class="legal-content text-dark" style="line-height: 1.8;">
                        <h2 class="h4 fw-bold mt-4">¿Quién procesa tu pago?</h2>
                        <p>
                            Los pagos con tarjeta son procesados por <strong>Mercado Pago</strong>, una de las plataformas de pago más usadas en Latinoamérica.
                            La tienda <strong>nunca ve ni guarda</strong> el número de tu tarjeta ni el código de seguridad (CVV): esos datos se escriben directamente en el formulario cifrado de Mercado Pago.
                        </p>

                        <h2 class="h4 fw-bold mt-4">¿Qué datos enviamos para protegerte?</h2>
                        <ul>
                            <li>Tu correo electrónico y nombre.</li>
                            <li>Tu documento de identidad (DNI/CE) para validar que eres el titular.</li>
                            <li>Tu teléfono y dirección de facturación.</li>
                            <li>El monto exacto del pedido, tomado del servidor (no manipulable).</li>
                        </ul>
                        <p>Estos datos ayudan a Mercado Pago a aprobar tu pago más rápido y a evitar bloqueos por seguridad.</p>

                        <h2 class="h4 fw-bold mt-4">Consejos para que tu pago se apruebe</h2>
                        <ul>
                            <li>Usa una tarjeta activa con compras por internet habilitadas (en débito, actívalas desde la app de tu banco).</li>
                            <li>Verifica que el nombre del titular coincida con el documento que ingresas.</li>
                            <li>Asegúrate de tener saldo o cupo suficiente.</li>
                            <li>Si tu banco pide confirmación (3D Secure), complétala en la ventana de Mercado Pago.</li>
                            <li>Evita usar VPN o redes públicas al pagar.</li>
                        </ul>

                        <h2 class="h4 fw-bold mt-4">Otros medios de pago</h2>
                        <p>
                            Además de tarjeta, puedes pagar con <strong>Yape</strong> y <strong>Plin</strong> escaneando el código QR desde la app de tu banco.
                            Las transferencias se verifican manualmente y el comprobante llega al correo de tu pedido.
                        </p>

                        <h2 class="h4 fw-bold mt-4">Privacidad</h2>
                        <p>
                            Tus datos personales se tratan conforme a nuestra
                            <a href="{{ route('web.privacidad') }}">Política de Privacidad</a>.
                            Mercado Pago aplica el estándar <strong>PCI DSS</strong> para el manejo de datos de tarjeta.
                        </p>

                        <div class="text-center mt-4">
                            <a href="{{ route('web.carrito.index') }}" class="btn btn-success btn-lg">
                                <i class="bi bi-cart3 me-2"></i>Ir al carrito
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection