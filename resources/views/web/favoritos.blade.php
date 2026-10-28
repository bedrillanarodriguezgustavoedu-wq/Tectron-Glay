@extends('layouts.web')
@section("meta_robots", "noindex, follow")
@section('content')
    <!-- Page Title -->
    <div class="page-title light-background">
        <div class="container d-lg-flex justify-content-between align-items-center">
            <h1 class="mb-2 mb-lg-0">Cuenta</h1>
            <nav class="breadcrumbs">
                <ol>
                    <li><a href="{{ url('/') }}">Principal</a></li>
                    <li class="current">Cuenta</li>
                </ol>
            </nav>
        </div>
    </div><!-- End Page Title -->

    <!-- Account Section -->
    <section id="account" class="account section">

        <div class="container" data-aos="fade-up" data-aos-delay="100">

            <!-- Mobile Menu Toggle -->
            <div class="mobile-menu d-lg-none mb-4">
                <button class="mobile-menu-toggle" type="button" data-bs-toggle="collapse" data-bs-target="#profileMenu">
                    <i class="bi bi-grid"></i>
                    <span>Menu</span>
                </button>
            </div>

            <div class="row g-4">
                <!-- Profile Menu -->
                <div class="col-lg-3">
                    <div class="profile-menu collapse d-lg-block" id="profileMenu">
                        <!-- User Info -->
                        <div class="user-info" data-aos="fade-right">
                            <div class="user-avatar">
                                @if (Auth::user()->ruta_foto)
                                    <img src="{{ Auth::user()->ruta_foto }}" alt="Tu foto de perfil" loading="lazy">
                                @elseif ($ajuste?->logo)
                                    <img src="{{ asset('storage/' . $ajuste->logo) }}" alt="Logo de {{ $ajuste->nombre ?? 'la tienda' }}" loading="lazy">
                                @else
                                    <span class="account-avatar-fallback" aria-hidden="true">{{ Auth::user()->iniciales }}</span>
                                @endif
                                <span class="status-badge"><i class="bi bi-shield-check"></i></span>
                            </div>
                            <h4>{{ Auth::user()->name }}</h4>
                            <div class="user-status">
                                <i class="bi bi-award"></i>
                                <span>{{ Auth::user()->roles()->pluck('name')->implode(', ') ?: 'Cliente' }}</span>
                            </div>
                        </div>

                        <!-- Navigation Menu -->
                        <nav class="menu-nav">
                            <ul class="nav flex-column" role="tablist">
                                <li class="nav-item">
                                    <a class="nav-link active" data-bs-toggle="tab" href="#wishlist">
                                        <i class="bi bi-heart"></i>
                                        <span>Favoritos</span>
                                        <span class="badge">{{ $productos_favoritos->count() }}</span>
                                    </a>
                                </li>
                            </ul>
                        </nav>
                    </div>
                </div>

                <!-- Content Area -->
                <div class="col-lg-9">
                    <div class="content-area">
                        <div class="tab-content">

                            <!-- Wishlist Tab -->
                            <div class="tab-pane fade show active" id="wishlist">
                                <div class="section-header" data-aos="fade-up">
                                    <h2>Mis productos favoritos</h2>
                                    <div class="header-actions">
                                        <button type="button" class="btn-add-all" id="btnAddAllToCart">Agregar todo al carrito</button>
                                    </div>
                                </div>

                                <div class="wishlist-grid">
                                    @foreach ($productos_favoritos as $favorito)
                                        <div class="wishlist-card" data-aos="fade-up" data-aos-delay="100">
                                            <div class="wishlist-image">
                                                @php
                                                    $imagen = $favorito->producto->imagen_principal_url;
                                                @endphp

                                                <a href="{{ url('/producto/' . $favorito->producto->id) }}">
                                                    <img src="{{ $imagen }}" alt="Product"
                                                        loading="lazy">
                                                </a>

                                                <form action="{{ url('/favorito/' . $favorito->id) }}" method="post"
                                                    id="miFormulario{{ $favorito->id }}" class="d-inline">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button class="btn-remove" type="submit"
                                                        aria-label="Remove from wishlist"
                                                        onclick="preguntar{{ $favorito->id }}(event)">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                </form>
                                                <script>
                                                    function preguntar{{ $favorito->id }}(event) {
                                                        event.preventDefault();
                                                        Swal.fire({
                                                            title: 'Quitar producto de favoritos?',
                                                            text: '',
                                                            icon: 'question',
                                                            showDenyButton: true,
                                                            confirmButtonText: 'Eliminar',
                                                            confirmButtonColor: '#a5161d',
                                                            denyButtonColor: '#270a0a',
                                                            denyButtonText: 'Cancelar',
                                                        }).then((result) => {
                                                            if (result.isConfirmed) {
                                                                document.getElementById('miFormulario{{ $favorito->id }}').submit();
                                                            }
                                                        });
                                                    }
                                                </script>

                                                <div class="sale-badge">
                                                    {{ $favorito->producto->stock }} disponibles
                                                </div>
                                            </div>

                                            <div class="wishlist-content">
                                                <a href="{{ url('/producto/' . $favorito->producto->id) }}">
                                                    <h4>{{ $favorito->producto->nombre }}</h4>
                                                </a>

                                                <div class="product-meta">
                                                    <div class="rating">
                                                        <i class="bi bi-star-fill"></i>
                                                        <i class="bi bi-star-fill"></i>
                                                        <i class="bi bi-star-fill"></i>
                                                        <i class="bi bi-star-fill"></i>
                                                        <i class="bi bi-star-half"></i>
                                                        <span>(4.5)</span>
                                                    </div>

                                                    <div class="price">
                                                        <span class="current">
                                                            {{ $ajuste->divisa . ' ' . $favorito->producto->precio_venta }}
                                                        </span>
                                                    </div>
                                                </div>

                                                <form action="{{ url('/carrito/agregar') }}" method="post">
                                                    @csrf
                                                    <input type="hidden" name="producto_id"
                                                        value="{{ $favorito->producto->id }}">
                                                    <input type="hidden" name="cantidad" value="1">
                                                    <button class="btn-add-cart">Agregar al carrito</button>
                                                </form>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>

                                @if ($productos_favoritos->count() > 0)
                                    <script>
                                        document.addEventListener('DOMContentLoaded', function() {
                                            const btnAddAll = document.getElementById('btnAddAllToCart');
                                            if (btnAddAll) {
                                                btnAddAll.addEventListener('click', function() {
                                                    Swal.fire({
                                                        title: '¿Agregar todos los favoritos al carrito?',
                                                        text: 'Se agregarán todos los productos al carrito de compras.',
                                                        icon: 'question',
                                                        showCancelButton: true,
                                                        confirmButtonText: 'Sí, agregar todo',
                                                        cancelButtonText: 'Cancelar',
                                                        confirmButtonColor: '#198754',
                                                    }).then((result) => {
                                                        if (result.isConfirmed) {
                                                            fetch('{{ route("web.favoritos.agregar_todo_al_carrito") }}', {
                                                                method: 'POST',
                                                                headers: {
                                                                    'Content-Type': 'application/json',
                                                                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                                                    'Accept': 'application/json',
                                                                    'X-Requested-With': 'XMLHttpRequest'
                                                                }
                                                            })
                                                            .then(res => {
                                                                const contentType = res.headers.get('content-type');
                                                                if (contentType && contentType.includes('application/json')) {
                                                                    return res.json().then(data => {
                                                                        if (!res.ok) {
                                                                            if (res.status === 401 || data.code === 401) {
                                                                                window.location.href = '{{ route("web.login") }}';
                                                                                return;
                                                                            }
                                                                            return Promise.reject(data);
                                                                        }
                                                                        return data;
                                                                    });
                                                                } else {
                                                                    if (res.status === 401 || res.redirected) {
                                                                        window.location.href = '{{ route("web.login") }}';
                                                                        return;
                                                                    }
                                                                    throw new Error('El servidor devolvió una respuesta no válida.');
                                                                }
                                                            })
                                                            .then(data => {
                                                                if (!data) return;
                                                                if (data.success) {
                                                                    const cartCounters = document.querySelectorAll('.cart-counter');
                                                                    cartCounters.forEach(c => c.textContent = `(${data.cart_count})`);

                                                                    const favCounters = document.querySelectorAll('.fav-counter');
                                                                    favCounters.forEach(c => c.textContent = `(${data.fav_count})`);

                                                                    Swal.fire({
                                                                        icon: 'success',
                                                                        title: '¡Completado!',
                                                                        text: data.mensaje,
                                                                        timer: 2500,
                                                                        showConfirmButton: false
                                                                    }).then(() => {
                                                                        window.location.href = '{{ route("web.carrito.index") }}';
                                                                    });
                                                                } else {
                                                                    Swal.fire({
                                                                        icon: 'error',
                                                                        title: 'Error',
                                                                        text: data.message || 'No se pudo completar la acción.'
                                                                    });
                                                                }
                                                            })
                                                            .catch(err => {
                                                                console.error(err);
                                                                Swal.fire({
                                                                    icon: 'error',
                                                                    title: 'Error',
                                                                    text: err.message || 'Ocurrió un error inesperado.'
                                                                });
                                                            });
                                                        }
                                                    });
                                                });
                                            }
                                        });
                                    </script>
                                @endif
                            </div>

                        </div>
                    </div>
                </div>
            </div>

        </div>

    </section><!-- /Account Section -->
@endsection
