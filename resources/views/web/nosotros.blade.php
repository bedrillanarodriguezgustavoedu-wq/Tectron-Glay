@extends('layouts.web')

@section('content')
    <section class="section py-5">
        <div class="container py-4">
            <div class="row align-items-center g-5">
                <div class="col-lg-6" data-aos="fade-right">
                    <span class="badge bg-primary-subtle text-primary rounded-pill px-3 py-2 mb-3">{{ $nosotros->subtitulo }}</span>
                    <h1 class="display-4 fw-bold text-dark mb-4">{{ $nosotros->titulo_principal }}</h1>
                    <p class="lead text-muted mb-4">{{ $nosotros->descripcion_principal }}</p>
                    <p class="text-muted mb-4" style="line-height: 1.7;">{{ $nosotros->historia }}</p>
                    
                    <!-- Stats Section -->
                    <div class="row g-4 mt-2 border-top pt-3">
                        <div class="col-4">
                            <h2 class="fw-bold text-primary mb-0">{{ $nosotros->experiencia_anos }}+</h2>
                            <span class="text-muted text-sm fw-semibold">Años de Exp.</span>
                        </div>
                        <div class="col-4">
                            <h2 class="fw-bold text-success mb-0">{{ $nosotros->clientes_count }}+</h2>
                            <span class="text-muted text-sm fw-semibold">Clientes</span>
                        </div>
                        <div class="col-4">
                            <h2 class="fw-bold text-info mb-0">{{ $nosotros->productos_count }}+</h2>
                            <span class="text-muted text-sm fw-semibold">Productos</span>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-6" data-aos="fade-left">
                    <div class="position-relative p-3">
                        <div class="bg-primary rounded-4 position-absolute w-100 h-100 top-0 start-0" style="transform: rotate(3deg); z-index: 1; opacity: 0.1;"></div>
                        @if ($nosotros->imagen_principal && file_exists(public_path('storage/' . $nosotros->imagen_principal)))
                            <img src="{{ asset('storage/' . $nosotros->imagen_principal) }}" alt="About Us" class="img-fluid rounded-4 shadow-lg w-100 position-relative" style="object-fit: cover; height: 420px; z-index: 2;">
                        @else
                            <img src="{{ asset('storage/productos/default.webp') }}" alt="About Us" class="img-fluid rounded-4 shadow-lg w-100 position-relative" style="object-fit: cover; height: 420px; z-index: 2;">
                        @endif
                    </div>
                </div>
            </div>

            <!-- Mission, Vision, Values Grid -->
            <div class="row g-4 mt-5">
                <div class="col-md-4" data-aos="fade-up" data-aos-delay="100">
                    <div class="card border-0 shadow-sm rounded-4 p-4 h-100 bg-light">
                        <div class="bg-primary-subtle text-primary p-3 rounded-4 d-inline-block mb-3" style="width: fit-content;">
                            <i class="bi bi-bullseye fs-3"></i>
                        </div>
                        <h4 class="fw-bold text-dark mb-2">Misión</h4>
                        <p class="text-muted mb-0" style="line-height: 1.6;">{{ $nosotros->mision }}</p>
                    </div>
                </div>

                <div class="col-md-4" data-aos="fade-up" data-aos-delay="200">
                    <div class="card border-0 shadow-sm rounded-4 p-4 h-100 bg-light">
                        <div class="bg-success-subtle text-success p-3 rounded-4 d-inline-block mb-3" style="width: fit-content;">
                            <i class="bi bi-eye fs-3"></i>
                        </div>
                        <h4 class="fw-bold text-dark mb-2">Visión</h4>
                        <p class="text-muted mb-0" style="line-height: 1.6;">{{ $nosotros->vision }}</p>
                    </div>
                </div>

                <div class="col-md-4" data-aos="fade-up" data-aos-delay="300">
                    <div class="card border-0 shadow-sm rounded-4 p-4 h-100 bg-light">
                        <div class="bg-info-subtle text-info p-3 rounded-4 d-inline-block mb-3" style="width: fit-content;">
                            <i class="bi bi-patch-check fs-3"></i>
                        </div>
                        <h4 class="fw-bold text-dark mb-2">Valores</h4>
                        <ul class="text-muted mb-0 ps-3">
                            @foreach (explode(',', $nosotros->valores) as $val)
                                <li class="mb-1">{{ trim($val) }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Optional Institutional Video -->
            @if ($nosotros->video_url)
                <div class="row mt-5 justify-content-center" data-aos="fade-up">
                    <div class="col-lg-10">
                        <div class="card border-0 shadow-lg rounded-4 overflow-hidden">
                            <div class="card-header bg-dark text-white fw-bold py-3 text-center fs-5">
                                <i class="bi bi-play-btn-fill text-danger me-1"></i> Conoce Más de Nosotros
                            </div>
                            <div class="ratio ratio-16x9">
                                @php
                                    // Parse Youtube video ID for embedding
                                    $embed_url = $nosotros->video_url;
                                    if (preg_match('%(?:youtube(?:-nocookie)?\.com/(?:[^/]+/.+/|(?:v|e(?:mbed)?)/|.*[?&]v=)|youtu\.be/)([^"&?/ ]{11})%i', $nosotros->video_url, $match)) {
                                        $embed_url = 'https://www.youtube.com/embed/' . $match[1];
                                    }
                                @endphp
                                <iframe src="{{ $embed_url }}" title="Institutional Video" allowfullscreen></iframe>
                            </div>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </section>
@endsection
