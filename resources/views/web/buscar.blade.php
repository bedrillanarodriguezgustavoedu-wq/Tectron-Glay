@extends('layouts.web')

@section('content')
    <section class="best-sellers section py-5">
        <div class="container section-title text-center mb-5" data-aos="fade-up">
            <h2 class="fw-bold">Resultados para: "{{ $query ?? '' }}"</h2>
            <p class="text-muted">Encuentra los componentes y equipos que estás buscando</p>
        </div>

        <div class="container" data-aos="fade-up" data-aos-delay="100">
            <!-- Mobile Filters Toggle Button -->
            <div class="d-lg-none mb-3">
                <button class="btn btn-primary w-100 rounded-3 py-2 fw-bold shadow-sm" type="button" data-bs-toggle="offcanvas" data-bs-target="#offcanvasFiltros" aria-controls="offcanvasFiltros">
                    <i class="bi bi-funnel-fill me-1"></i> Filtros y Categorías
                </button>
            </div>

            <div class="row g-4">
                <!-- Desktop Sidebar Column -->
                <div class="col-lg-3 d-none d-lg-block">
                    <div class="card border-0 shadow-sm rounded-4 p-4 sticky-top" style="top: 100px; z-index: 10;" id="desktop-filters-sidebar">
                        @include('partials.filtros')
                    </div>
                </div>

                <!-- Products Grid Column -->
                <div class="col-lg-9 col-12">
                    <div id="productos-grid-container">
                        @include('partials.productos_grid')
                    </div>
                </div>
            </div>
        </div>

        <!-- Mobile Offcanvas Filters -->
        <div class="offcanvas offcanvas-start" tabindex="-1" id="offcanvasFiltros" aria-labelledby="offcanvasFiltrosLabel">
            <div class="offcanvas-header border-bottom">
                <h5 class="offcanvas-title fw-bold" id="offcanvasFiltrosLabel">Filtros y Categorías</h5>
                <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
            </div>
            <div class="offcanvas-body" id="mobile-filters-placeholder">
                <!-- Form will be dynamically appended here on mobile -->
            </div>
        </div>
    </section>

    <style>
        .hover-translate-y:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 30px rgba(0,0,0,0.08) !important;
        }
        .product-item:hover .product-actions {
            opacity: 1 !important;
            transform: translate(-50%, -10px) scale(1) !important;
        }
        .product-item:hover img {
            transform: scale(1.08);
        }
        .text-truncate-2 {
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        .hover-primary:hover {
            color: var(--color-primary, #0d6efd) !important;
        }
        .filter-group input[type="checkbox"]:checked, .filter-group input[type="radio"]:checked {
            background-color: var(--color-primary, #0d6efd);
            border-color: var(--color-primary, #0d6efd);
        }
        .filter-list::-webkit-scrollbar {
            width: 4px;
        }
        .filter-list::-webkit-scrollbar-thumb {
            background-color: #ced4da;
            border-radius: 4px;
        }
    </style>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const form = document.getElementById('filtrosForm');
        const desktopContainer = document.getElementById('desktop-filters-sidebar');
        const mobileContainer = document.getElementById('mobile-filters-placeholder');
        
        function moveFiltersForm() {
            if (!form) return;
            if (window.innerWidth < 992) {
                if (mobileContainer && form.parentNode !== mobileContainer) {
                    mobileContainer.appendChild(form);
                }
            } else {
                if (desktopContainer && form.parentNode !== desktopContainer) {
                    desktopContainer.appendChild(form);
                }
            }
        }
        
        moveFiltersForm();
        window.addEventListener('resize', moveFiltersForm);

        function submitFilters() {
            if (!form) return;
            
            const gridContainer = document.getElementById('productos-grid-container');
            if (gridContainer) gridContainer.classList.add('opacity-50');
            
            const formData = new FormData(form);
            const queryParams = new URLSearchParams(formData).toString();
            const ajaxUrl = window.location.pathname + '?' + queryParams;
            
            history.pushState(null, '', ajaxUrl);
            
            fetch(ajaxUrl, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => {
                if (!response.ok) throw new Error('Error al filtrar productos.');
                return response.json();
            })
            .then(data => {
                if (gridContainer) {
                    gridContainer.innerHTML = data.html;
                    gridContainer.classList.remove('opacity-50');
                }
                
                // Hide offcanvas on mobile if open
                const offcanvasElement = document.getElementById('offcanvasFiltros');
                if (offcanvasElement) {
                    const bsOffcanvas = bootstrap.Offcanvas.getInstance(offcanvasElement);
                    if (bsOffcanvas) bsOffcanvas.hide();
                }
                
                document.querySelector('.best-sellers').scrollIntoView({ behavior: 'smooth' });
            })
            .catch(err => {
                console.error(err);
                if (gridContainer) gridContainer.classList.remove('opacity-50');
            });
        }

        // Listen for filter changes
        document.addEventListener('change', function(e) {
            if (e.target && (e.target.classList.contains('filter-checkbox') || e.target.classList.contains('filter-radio'))) {
                submitFilters();
            }
            if (e.target && e.target.id === 'ordenSelect') {
                const ordenInput = document.getElementById('ordenInput');
                if (ordenInput) {
                    ordenInput.value = e.target.value;
                    submitFilters();
                }
            }
        });

        document.addEventListener('click', function(e) {
            if (e.target && e.target.id === 'btnAplicarPrecio') {
                submitFilters();
            }
        });

        // Filter chips removal
        document.addEventListener('click', function(e) {
            const btn = e.target.closest('.btn-remove-chip');
            if (btn && form) {
                const key = btn.getAttribute('data-key');
                const value = btn.getAttribute('data-value');
                const keysToClear = btn.getAttribute('data-keys-to-clear');
                
                if (keysToClear) {
                    const keys = JSON.parse(keysToClear);
                    keys.forEach(k => {
                        const input = form.querySelector(`[name="${k}"]`);
                        if (input) input.value = '';
                    });
                } else if (key) {
                    const checkbox = form.querySelector(`input[name="${key}[]"][value="${value}"]`);
                    if (checkbox) {
                        checkbox.checked = false;
                    } else {
                        const input = form.querySelector(`[name="${key}"][value="${value}"]`) || form.querySelector(`[name="${key}"]`);
                        if (input) {
                            if (input.type === 'radio') {
                                input.checked = false;
                            } else {
                                input.value = '';
                            }
                        }
                    }
                }
                submitFilters();
            }
        });

        // Clear all filters
        document.addEventListener('click', function(e) {
            const btn = e.target.closest('#btnClearAllFilters');
            if (btn && form) {
                form.querySelectorAll('input[type="checkbox"]').forEach(cb => cb.checked = false);
                form.querySelectorAll('input[type="radio"]').forEach(rad => rad.checked = false);
                form.querySelectorAll('input[type="number"], input[type="text"]').forEach(inp => inp.value = '');
                const orden = document.getElementById('ordenInput');
                if (orden) orden.value = '';
                submitFilters();
            }
        });

        // Pagination
        document.addEventListener('click', function(e) {
            const link = e.target.closest('.ajax-pagination a');
            if (link) {
                e.preventDefault();
                const url = new URL(link.href);
                const page = url.searchParams.get('page');
                
                const pageInput = document.getElementById('pageInput') || document.createElement('input');
                if (!pageInput.id) {
                    pageInput.type = 'hidden';
                    pageInput.name = 'page';
                    pageInput.id = 'pageInput';
                    form.appendChild(pageInput);
                }
                pageInput.value = page;
                submitFilters();
                pageInput.remove();
            }
        });

        // Browser Back/Forward support
        window.addEventListener('popstate', function() {
            const urlParams = new URLSearchParams(window.location.search);
            if (form) {
                form.querySelectorAll('input[type="checkbox"]').forEach(cb => {
                    const name = cb.name.replace('[]', '');
                    const vals = urlParams.getAll(name + '[]');
                    cb.checked = vals.includes(cb.value);
                });
                form.querySelectorAll('input[type="radio"]').forEach(rad => {
                    rad.checked = urlParams.get(rad.name) === rad.value;
                });
                form.querySelectorAll('input[type="number"], input[type="text"]').forEach(inp => {
                    inp.value = urlParams.get(inp.name) || '';
                });
                
                const gridContainer = document.getElementById('productos-grid-container');
                if (gridContainer) gridContainer.classList.add('opacity-50');
                
                fetch(window.location.href, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                .then(res => res.json())
                .then(data => {
                    if (gridContainer) {
                        gridContainer.innerHTML = data.html;
                        gridContainer.classList.remove('opacity-50');
                    }
                })
                .catch(err => {
                    console.error(err);
                    if (gridContainer) gridContainer.classList.remove('opacity-50');
                });
            }
        });
    });
    </script>
@endsection
