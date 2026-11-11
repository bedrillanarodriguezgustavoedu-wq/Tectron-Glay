@extends('layouts.web')
@section('meta_robots', 'noindex, nofollow')

@section('content')
    <style>
        .izipay-checkout {
            max-width: 720px;
        }
        .izipay-checkout__card {
            border: 0;
            border-radius: 18px;
            box-shadow: 0 18px 50px rgba(15, 23, 42, .10);
        }
        .izipay-checkout__total {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            padding: 14px 18px;
            border-radius: 14px;
            background: #f1f5f9;
            margin-bottom: 20px;
        }
        .izipay-checkout__total strong {
            font-size: 1.5rem;
        }
        .izipay-checkout .kr-embedded {
            margin: 0 auto;
        }
    </style>

    <section class="py-5">
        <div class="container izipay-checkout">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
                <div>
                    <span class="badge bg-success-subtle text-success-emphasis mb-2">
                        <i class="bi bi-shield-lock me-1" aria-hidden="true"></i>Pago seguro con Izipay
                    </span>
                    <h1 class="h3 fw-bold mb-1">Completa el pago con tarjeta</h1>
                    <p class="text-muted mb-0">Pedido #{{ $orden->id }} · {{ $orden->getMetodoPagoTexto() }}</p>
                </div>
                <a href="{{ route('web.pedidos.confirmacion', $orden->id) }}" class="btn btn-outline-secondary rounded-pill">
                    <i class="bi bi-arrow-left me-1" aria-hidden="true"></i>Volver a mi pedido
                </a>
            </div>

            @if (session('mensaje'))
                <div class="alert alert-{{ session('icono') === 'error' ? 'danger' : (session('icono') === 'success' ? 'success' : 'info') }} mb-3" role="alert">
                    {{ session('mensaje') }}
                </div>
            @endif

            <div class="card izipay-checkout__card">
                <div class="card-body p-4 p-md-5">
                    <div class="izipay-checkout__total">
                        <span class="text-muted">Total a pagar</span>
                        <strong class="text-success">{{ \App\Models\Ajuste::formatear_precio($orden->total) }}</strong>
                    </div>

                    <div id="izipay-formulario">
                        {{-- El formulario lo sirve Izipay con el formToken. La tienda
                             nunca recibe el número ni el CVV: los campos viven dentro
                             del iframe/componente de Izipay. --}}
                        <script type="text/javascript"
                            src="{{ $scriptUrl }}"
                            kr-public-key="{{ $publicKey }}"
                            kr-post-url-success="{{ $urlRetorno }}"
                            kr-post-url-refused="{{ $urlRetorno }}"
                            kr-language="es-ES"></script>
                        <div class="kr-embedded" kr-form-token="{{ $formToken }}">
                            <div class="kr-pan"></div>
                            <div class="kr-expiry"></div>
                            <div class="kr-security-code"></div>
                            <button class="kr-payment-button" type="submit">
                                Pagar {{ \App\Models\Ajuste::formatear_precio($orden->total) }}
                            </button>
                            <div class="kr-form-error"></div>
                        </div>
                    </div>

                    <div class="alert alert-info small mt-4 mb-0" role="note">
                        <i class="bi bi-info-circle me-1" aria-hidden="true"></i>
                        Si tu tarjeta es de débito, activa las compras por internet desde la app de tu banco.
                        Izipay confirma el pago al instante y te devuelve a la tienda; si el banco rechaza la
                        tarjeta, no se te cobra nada y puedes intentarlo otra vez.
                    </div>

                    <p class="small text-muted text-center mt-3 mb-0">
                        <i class="bi bi-lock-fill me-1" aria-hidden="true"></i>
                        La tienda no ve ni guarda el número, la fecha de vencimiento ni el CVV de tu tarjeta.
                    </p>
                </div>
            </div>
        </div>
    </section>
@endsection
