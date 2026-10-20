@extends('layouts.web')

@section('content')
    <section class="section py-5 bg-light">
        <div class="container section-title text-center mb-5" data-aos="fade-up">
            <h2 class="fw-bold">Nuestras Marcas Aliadas</h2>
            <p class="text-muted">Encuentra los mejores fabricantes tecnológicos del mercado mundial</p>
        </div>

        <div class="container" data-aos="fade-up" data-aos-delay="100">
            <div class="row g-4 justify-content-center">
                @forelse($marcas as $marca)
                    <div class="col-6 col-md-4 col-lg-3">
                        <div class="card h-100 border-0 shadow-sm rounded-4 text-center p-4 bg-white d-flex flex-column align-items-center justify-content-center transition-all hover-brand">
                            <div class="brand-logo-container mb-3 bg-light rounded-3 d-flex align-items-center justify-content-center overflow-hidden" style="width: 120px; height: 100px; padding: 10px;">
                                <x-marca-logo :marca="$marca" width="100px" height="80px" />
                            </div>
                            <h4 class="fw-bold text-dark mb-1">{{ $marca->nombre }}</h4>
                            <span class="badge bg-primary-subtle text-primary rounded-pill mb-3">{{ $marca->productos_count }} Productos</span>
                            <a href="{{ route('web.productos', ['marca' => $marca->slug]) }}" class="btn btn-outline-primary btn-sm rounded-pill px-4">Ver Catálogo</a>
                        </div>
                    </div>
                @empty
                    <div class="col-12">
                        <div class="alert alert-warning text-center py-4" role="alert">
                            No se encontraron marcas registradas.
                        </div>
                    </div>
                @endforelse
            </div>
        </div>
    </section>

    <style>
        .hover-brand {
            transition: all 0.3s ease-in-out;
        }
        .hover-brand:hover {
            transform: translateY(-8px);
            box-shadow: 0 15px 30px rgba(0,0,0,0.1) !important;
            border-bottom: 3px solid var(--color-primary, #0d6efd) !important;
        }
    </style>
@endsection
