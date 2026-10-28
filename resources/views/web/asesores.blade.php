@extends('layouts.web')

@section('title', 'Asesores Comerciales')
@section('meta_descripcion', 'Escríbele directo al asesor de GALY que atiende tu rubro: precios, stock y formas de pago por WhatsApp, correo o teléfono.')

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
                @if ($rubros->count() > 1)
                    <div class="d-flex flex-wrap justify-content-center gap-2 mb-4" role="group" aria-label="Filtrar asesores por rubro">
                        <button type="button" class="btn btn-sm rounded-pill px-3 asesores-filtro" data-rubro=""
                            aria-pressed="true">
                            <i class="bi bi-collection me-1" aria-hidden="true"></i>Todos
                        </button>
                        @foreach ($rubros as $rubro)
                            <button type="button" class="btn btn-sm rounded-pill px-3 asesores-filtro" data-rubro="{{ $rubro }}"
                                aria-pressed="false">
                                {{ $rubro }}
                            </button>
                        @endforeach
                    </div>
                @endif

                <div class="row g-4 justify-content-center" id="grid-asesores">
                    @foreach ($asesores as $asesor)
                        <div class="col-sm-6 {{ $asesores->count() > 3 ? 'col-lg-3' : 'col-lg-4' }} asesor-tarjeta"
                            data-rubro="{{ $asesor->rubro }}" data-aos="fade-up" data-aos-delay="{{ $loop->index * 80 }}">
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
                                    @if ($asesor->rubro)
                                        <span class="badge bg-dark-subtle text-dark rounded-pill px-3 py-2 position-absolute top-0 start-0 m-3">
                                            {{ $asesor->rubro }}
                                        </span>
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

                                    <div class="d-flex flex-wrap justify-content-center gap-2">
                                        @if ($asesor->enlace_whatsapp)
                                            <a href="{{ $asesor->enlaceWhatsAppConMensaje() }}"
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
                                        @if ($asesor->enlace_telefono)
                                            <a href="{{ $asesor->enlace_telefono }}"
                                                class="btn btn-outline-secondary btn-sm rounded-pill px-3">
                                                <i class="bi bi-telephone me-1" aria-hidden="true"></i>Llamar
                                            </a>
                                        @endif
                                        @if ($asesor->numero_whatsapp)
                                            <button type="button"
                                                class="btn btn-outline-secondary btn-sm rounded-pill px-2 copiar-numero"
                                                data-numero="{{ $asesor->numero_whatsapp }}"
                                                title="Copiar el número {{ $asesor->numero_whatsapp }}"
                                                aria-label="Copiar el número de WhatsApp de {{ $asesor->nombre }}">
                                                <i class="bi bi-clipboard" aria-hidden="true"></i>
                                            </button>
                                        @endif
                                    </div>

                                    @if (! $asesor->enlace_whatsapp && ! $asesor->email && ! $asesor->enlace_telefono)
                                        <p class="text-muted small mb-0 mt-3">Este asesor atiende por la página de Contacto.</p>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <p class="text-center text-muted small mt-4 d-none" id="asesores-sin-resultados" aria-live="polite">
                    Ningún asesor tiene ese rubro todavía; prueba con «Todos».
                </p>

                <div class="card border-0 bg-primary-subtle rounded-4 mt-5">
                    <div class="card-body d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 p-4">
                        <div>
                            <h5 class="fw-bold text-dark mb-1">¿No sabes con quién hablar?</h5>
                            <p class="text-muted small mb-0">Cuéntanos qué buscas y te derivamos con el asesor de ese rubro.</p>
                        </div>
                        <a href="{{ route('web.contacto') }}" class="btn btn-primary rounded-pill px-4 text-nowrap">
                            <i class="bi bi-chat-dots me-1" aria-hidden="true"></i>Escríbenos
                        </a>
                    </div>
                </div>
            @endif
        </div>
    </section>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const filtros = document.querySelectorAll('.asesores-filtro');
            const tarjetas = document.querySelectorAll('.asesor-tarjeta');
            const sinResultados = document.getElementById('asesores-sin-resultados');

            filtros.forEach(function (boton) {
                boton.addEventListener('click', function () {
                    const rubro = this.dataset.rubro;
                    let visibles = 0;

                    tarjetas.forEach(function (tarjeta) {
                        const coincide = rubro === '' || tarjeta.dataset.rubro === rubro;
                        tarjeta.classList.toggle('d-none', !coincide);
                        if (coincide) visibles++;
                    });

                    filtros.forEach(function (otro) {
                        const activo = otro === this;
                        otro.classList.toggle('btn-primary', activo);
                        otro.classList.toggle('text-white', activo);
                        otro.setAttribute('aria-pressed', activo ? 'true' : 'false');
                    }, this);

                    if (sinResultados) sinResultados.classList.toggle('d-none', visibles !== 0);
                });
            });

            // Sin selección previa, «Todos» queda marcado para que se vea qué filtro
            // está activo desde la primera pantalla.
            const primero = Array.from(filtros).find(function (b) { return b.dataset.rubro === ''; });
            if (primero) {
                primero.classList.add('btn-primary', 'text-white');
            }

            document.querySelectorAll('.copiar-numero').forEach(function (boton) {
                boton.addEventListener('click', async function () {
                    const numero = this.dataset.numero;

                    try {
                        await navigator.clipboard.writeText(numero);
                    } catch (error) {
                        // Sin API de portapapeles (navegador viejo o HTTP plano) se
                        // copia por el método clásico: el número no se pierde.
                        const campo = document.createElement('textarea');
                        campo.value = numero;
                        campo.setAttribute('readonly', 'readonly');
                        campo.style.position = 'fixed';
                        campo.style.opacity = '0';
                        document.body.appendChild(campo);
                        campo.select();
                        document.execCommand('copy');
                        campo.remove();
                    }

                    const icono = this.querySelector('i');
                    icono.className = 'bi bi-check2';
                    window.setTimeout(() => { icono.className = 'bi bi-clipboard'; }, 1800);
                });
            });
        });
    </script>
@endsection
