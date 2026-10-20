@extends('layouts.web')

@section('content')
    <style>
        .contact-technical-service {
            scroll-margin-top: 110px;
            border: 1px solid #e2e8f0;
            border-radius: 24px;
            overflow: hidden;
            background: linear-gradient(135deg, #fff 0%, #f8fafc 100%);
            box-shadow: 0 18px 50px rgba(15, 23, 42, .08);
        }
        .contact-technical-service img {
            display: block;
            width: 100%;
            height: 100%;
            min-height: 300px;
            max-height: 520px;
            object-fit: contain;
            background: #111827;
        }
        .contact-technical-copy {
            padding: clamp(1.5rem, 4vw, 3rem);
        }
        .contact-technical-item {
            display: flex;
            align-items: center;
            gap: .7rem;
            padding: .65rem .8rem;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            background: #fff;
            color: #334155;
            font-weight: 600;
        }
        .contact-technical-item i {
            color: #e30613;
        }
        #contact-form {
            scroll-margin-top: 110px;
        }
        @media (max-width: 767.98px) {
            .contact-technical-service img {
                height: auto;
                min-height: 0;
                max-height: none;
            }
        }
    </style>
    <section class="section py-5 bg-light">
        <div class="container py-4">
            
            @if (session('mensaje'))
                <div class="alert alert-{{ session('icono') === 'success' ? 'success' : 'danger' }} alert-dismissible fade show rounded-4 p-3 mb-4 shadow-sm" role="alert">
                    <i class="bi {{ session('icono') === 'success' ? 'bi-check-circle-fill' : 'bi-exclamation-triangle-fill' }} me-2"></i>
                    {{ session('mensaje') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            <section id="servicio-tecnico" class="contact-technical-service mb-5" aria-labelledby="contact-technical-title">
                <div class="row g-0 align-items-stretch">
                    <div class="col-lg-5">
                        <img
                            src="{{ asset('storage/banners/servicio-tecnico-galy.png') }}"
                            alt="Afiche de servicio técnico GALY: diagnóstico, reparaciones y mantenimiento para computadoras, impresoras y laptops"
                            loading="lazy"
                        >
                    </div>
                    <div class="col-lg-7 d-flex align-items-center">
                        <div class="contact-technical-copy w-100">
                            <span class="badge rounded-pill px-3 py-2 mb-3" style="background:#fff0ed;color:#c92a1b;">Servicio técnico GALY</span>
                            <h1 id="contact-technical-title" class="h2 fw-bold mb-3">Diagnóstico, reparación y mantenimiento</h1>
                            <p class="text-secondary mb-4">Atendemos computadoras, impresoras y laptops. Cuéntanos qué problema presenta tu equipo y coordinaremos la atención contigo.</p>

                            <div class="row g-2 mb-4">
                                <div class="col-sm-6"><div class="contact-technical-item"><i class="bi bi-search" aria-hidden="true"></i> Diagnóstico</div></div>
                                <div class="col-sm-6"><div class="contact-technical-item"><i class="bi bi-tools" aria-hidden="true"></i> Reparaciones</div></div>
                                <div class="col-sm-6"><div class="contact-technical-item"><i class="bi bi-wrench-adjustable" aria-hidden="true"></i> Mantenimiento</div></div>
                                <div class="col-sm-6"><div class="contact-technical-item"><i class="bi bi-laptop" aria-hidden="true"></i> PC, laptop e impresora</div></div>
                            </div>

                            <div class="d-flex flex-wrap gap-2">
                                <a href="#contact-form" class="btn btn-primary rounded-pill px-4 py-2 fw-semibold">
                                    Agenda tu servicio <i class="bi bi-arrow-down ms-1" aria-hidden="true"></i>
                                </a>
                                @if ($contacto->whatsapp)
                                    @php
                                        $servicioWhatsapp = preg_replace('/\D+/', '', $contacto->whatsapp);
                                    @endphp
                                    @if ($servicioWhatsapp !== '')
                                        <a href="https://wa.me/{{ $servicioWhatsapp }}?text={{ rawurlencode('Hola, deseo información sobre el servicio técnico de GALY.') }}"
                                            class="btn btn-outline-success rounded-pill px-4 py-2 fw-semibold"
                                            target="_blank" rel="noopener noreferrer">
                                            <i class="bi bi-whatsapp me-1" aria-hidden="true"></i> Consultar por WhatsApp
                                        </a>
                                    @endif
                                @endif
                            </div>
                            @if ($contacto->telefono_principal)
                                <p class="small text-muted mt-3 mb-0">
                                    <i class="bi bi-telephone me-1" aria-hidden="true"></i>
                                    {{ $contacto->telefono_principal }}
                                </p>
                            @endif
                        </div>
                    </div>
                </div>
            </section>

            <div class="row g-5">
                <div class="col-lg-5" data-aos="fade-right">
                    <span class="badge bg-primary-subtle text-primary rounded-pill px-3 py-2 mb-3">Contacto</span>
                    <h2 class="display-5 fw-bold text-dark mb-4">¿Tienes dudas? Escríbenos</h2>
                    <p class="text-muted mb-5">{{ $contacto->mensaje_bienvenida }}</p>
                    
                    <div class="d-flex align-items-start gap-4 mb-4">
                        <div class="bg-white text-primary p-3 rounded-4 shadow-sm">
                            <i class="bi bi-geo-alt fs-3"></i>
                        </div>
                        <div>
                            <h5 class="fw-bold mb-1">Dirección Matriz</h5>
                            <span class="text-muted">
                                {{ $contacto->direccion }}<br>
                                <small class="text-muted">{{ $contacto->provincia }}, {{ $contacto->departamento }}, {{ $contacto->pais }}</small>
                            </span>
                        </div>
                    </div>

                    <div class="d-flex align-items-start gap-4 mb-4">
                        <div class="bg-white text-primary p-3 rounded-4 shadow-sm">
                            <i class="bi bi-telephone fs-3"></i>
                        </div>
                        <div>
                            <h5 class="fw-bold mb-1">Llámanos</h5>
                            <span class="text-muted d-block">{{ $contacto->telefono_principal }}</span>
                            @if ($contacto->telefono_secundario)
                                <span class="text-muted d-block">{{ $contacto->telefono_secundario }}</span>
                            @endif
                        </div>
                    </div>

                    <div class="d-flex align-items-start gap-4 mb-4">
                        <div class="bg-white text-primary p-3 rounded-4 shadow-sm">
                            <i class="bi bi-envelope fs-3"></i>
                        </div>
                        <div>
                            <h5 class="fw-bold mb-1">Correo Electrónico</h5>
                            <a href="mailto:{{ $contacto->email }}" class="text-muted text-decoration-none">{{ $contacto->email }}</a>
                        </div>
                    </div>

                    <div class="d-flex align-items-start gap-4 mb-4">
                        <div class="bg-white text-primary p-3 rounded-4 shadow-sm">
                            <i class="bi bi-clock fs-3"></i>
                        </div>
                        <div>
                            <h5 class="fw-bold mb-1">Horario de Atención</h5>
                            <span class="text-muted">{{ $contacto->horario_atencion }}</span>
                        </div>
                    </div>

                    <!-- Social Icons -->
                    <div class="mt-4 pt-3 border-top">
                        <h6 class="fw-bold text-dark mb-3">Síguenos en Redes Sociales</h6>
                        <div class="d-flex gap-2">
                            @if ($contacto->facebook_link)
                                <a href="{{ $contacto->facebook_link }}" target="_blank" class="btn btn-outline-primary btn-sm rounded-circle px-2 py-1.5 fs-5" style="width: 40px; height: 40px;"><i class="bi bi-facebook"></i></a>
                            @endif
                            @if ($contacto->instagram_link)
                                <a href="{{ $contacto->instagram_link }}" target="_blank" class="btn btn-outline-danger btn-sm rounded-circle px-2 py-1.5 fs-5" style="width: 40px; height: 40px;"><i class="bi bi-instagram"></i></a>
                            @endif
                            @if ($contacto->youtube_link)
                                <a href="{{ $contacto->youtube_link }}" target="_blank" class="btn btn-outline-danger btn-sm rounded-circle px-2 py-1.5 fs-5" style="width: 40px; height: 40px;"><i class="bi bi-youtube"></i></a>
                            @endif
                            @if ($contacto->twitter_link)
                                <a href="{{ $contacto->twitter_link }}" target="_blank" class="btn btn-outline-dark btn-sm rounded-circle px-2 py-1.5 fs-5" style="width: 40px; height: 40px;"><i class="bi bi-twitter-x"></i></a>
                            @endif
                            @if ($contacto->whatsapp)
                                @php
                                    $wa_number = preg_replace('/[^0-9]/', '', $contacto->whatsapp);
                                @endphp
                                <a href="https://wa.me/{{ $wa_number }}" target="_blank" class="btn btn-outline-success btn-sm rounded-circle px-2 py-1.5 fs-5" style="width: 40px; height: 40px;"><i class="bi bi-whatsapp"></i></a>
                            @endif
                        </div>
                    </div>
                </div>

                <div id="contact-form" class="col-lg-7" data-aos="fade-left">
                    <div class="card border-0 shadow-sm rounded-4 p-5 bg-white">
                        <form action="{{ route('web.contacto.store') }}" method="POST">
                            @csrf
                            <div class="row g-4">
                                <div class="col-md-6">
                                    <label for="nombre" class="form-label fw-semibold">Nombre Completo</label>
                                    <input type="text" name="nombre" id="nombre" class="form-control rounded-pill px-4 py-2.5 border-light-subtle bg-light" placeholder="Ej. Juan Pérez" required>
                                </div>
                                <div class="col-md-6">
                                    <label for="email" class="form-label fw-semibold">Correo Electrónico</label>
                                    <input type="email" name="email" id="email" class="form-control rounded-pill px-4 py-2.5 border-light-subtle bg-light" placeholder="Ej. juan@correo.com" required>
                                </div>
                                <div class="col-12">
                                    <label for="asunto" class="form-label fw-semibold">Asunto</label>
                                    <input type="text" name="asunto" id="asunto" class="form-control rounded-pill px-4 py-2.5 border-light-subtle bg-light" placeholder="Ej. Cotización de PC Gamer" required>
                                </div>
                                <div class="col-12">
                                    <label for="mensaje" class="form-label fw-semibold">Mensaje</label>
                                    <textarea name="mensaje" id="mensaje" rows="5" class="form-control rounded-4 px-4 py-3 border-light-subtle bg-light" placeholder="Escribe tu mensaje o detalles de cotización aquí..." required></textarea>
                                </div>
                                <div class="col-12">
                                    <button type="submit" class="btn btn-primary rounded-pill px-5 py-3 w-100 fw-bold shadow-sm">Enviar Mensaje</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Optional Google Map Embed -->
            @if ($contacto->google_maps_link)
                <div class="row mt-5" data-aos="fade-up">
                    <div class="col-12">
                        <div class="card border-0 shadow-sm rounded-4 overflow-hidden p-0">
                            <div style="height: 400px; width: 100%;">
                                {!! $contacto->google_maps_link !!}
                            </div>
                        </div>
                    </div>
                </div>
            @endif

        </div>
    </section>

    @if (session('mensaje'))
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                Swal.fire({
                    title: "{{ session('mensaje') }}",
                    icon: "{{ session('icono') ?? 'success' }}",
                    confirmButtonColor: '#3085d6',
                    confirmButtonText: 'Aceptar'
                });
            });
        </script>
    @endif
@endsection
