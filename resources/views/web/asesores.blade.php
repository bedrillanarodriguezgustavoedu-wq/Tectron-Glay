@extends('layouts.web')

@section('content')
    <section class="section py-5">
        <div class="container py-4">
            <div class="text-center mb-5" data-aos="fade-up">
                <span class="badge bg-primary-subtle text-primary rounded-pill px-3 py-2 mb-3">Equipo GALY</span>
                <h1 class="display-5 fw-bold text-dark mb-3">Asesores Comerciales</h1>
                <p class="lead text-muted mx-auto" style="max-width: 640px;">
                    Escríbele directo al asesor que atiende tu rubro. Conoce precios, stock y formas de pago sin esperar respuesta.
                </p>
            </div>

            @if ($asesores->isEmpty())
                <div class="card border-0 shadow-sm rounded-4 p-5 text-center">
                    <div class="bg-primary-subtle text-primary p-3 rounded-4 d-inline-block mb-3" style="width: fit-content;">
                        <i class="bi bi-people fs-3"></i>
                    </div>
                    <h4 class="fw-bold text-dark mb-2">Estamos asignando los asesores de esta línea</h4>
                    <p class="text-muted mb-4">Mientras tanto, escríbenos y te derivamos con quien corresponde.</p>
                    <div>
                        <a href="{{ route('web.contacto') }}" class="btn btn-primary rounded-pill px-4">Ir a Contacto</a>
                    </div>
                </div>
            @else
                <div class="row g-4 justify-content-center">
                    @foreach ($asesores as $asesor)
                        <div class="col-sm-6 col-lg-3" data-aos="fade-up" data-aos-delay="{{ $loop->index * 80 }}">
                            <div class="card border-0 shadow-sm rounded-4 h-100 overflow-hidden">
                                <div class="position-relative">
                                    @if ($asesor->ruta_foto)
                                        <img src="{{ $asesor->ruta_foto }}" alt="{{ $asesor->nombre }}"
                                            class="w-100" style="height: 240px; object-fit: cover; object-position: top center;">
                                    @else
                                        <div class="d-flex align-items-center justify-content-center bg-primary-subtle w-100"
                                            style="height: 240px;">
                                            <span class="display-4 fw-bold text-primary">{{ $asesor->iniciales }}</span>
                                        </div>
                                    @endif
                                </div>
                                <div class="card-body text-center">
                                    <h5 class="fw-bold text-dark mb-1">{{ $asesor->nombre }}</h5>
                                    @if ($asesor->cargo)
                                        <p class="text-muted small mb-2">{{ $asesor->cargo }}</p>
                                    @endif
                                    @if ($asesor->horario)
                                        <p class="text-muted small mb-3">
                                            <i class="bi bi-clock me-1" aria-hidden="true"></i>{{ $asesor->horario }}
                                        </p>
                                    @endif

                                    <div class="d-flex justify-content-center gap-2">
                                        @if ($asesor->enlace_whatsapp)
                                            <a href="{{ $asesor->enlace_whatsapp }}?text={{ rawurlencode('Hola '.$asesor->nombre.', vengo de la tienda virtual de GALY y necesito asesoría.') }}"
                                                target="_blank" rel="noopener"
                                                class="btn btn-success btn-sm rounded-pill px-3">
                                                <i class="bi bi-whatsapp me-1" aria-hidden="true"></i>WhatsApp
                                            </a>
                                        @endif
                                        @if ($asesor->email)
                                            <a href="mailto:{{ $asesor->email }}"
                                                class="btn btn-outline-primary btn-sm rounded-pill px-3">
                                                <i class="bi bi-envelope me-1" aria-hidden="true"></i>Correo
                                            </a>
                                        @endif
                                    </div>

                                    @if (! $asesor->enlace_whatsapp && ! $asesor->email)
                                        <p class="text-muted small mb-0 mt-3">Este asesor atiende por la página de Contacto.</p>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </section>
@endsection
