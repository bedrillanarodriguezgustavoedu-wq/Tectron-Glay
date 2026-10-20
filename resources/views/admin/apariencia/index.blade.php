@extends('layouts.admin')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1>Apariencia y Configuración de Moneda</h1>
    </div>
    <hr>

    <div class="row">
        <div class="col-md-12">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white py-3">
                    <h4 class="card-title text-white mb-0"><i class="bi bi-palette-fill"></i> Personalizar Identidad Visual y Moneda</h4>
                </div>
                <div class="card-body py-4">
                    <form action="{{ route('admin.apariencia.store') }}" method="POST">
                        @csrf
                        
                        <!-- Colores Section -->
                        <h4 class="text-dark fw-bold mb-3 border-bottom pb-2"><i class="bi bi-palette"></i> Colores del Tema</h4>
                        <div class="row g-3 mb-5">
                            <div class="col-md-3">
                                <label for="color_primario" class="form-label fw-bold">Color Primario</label>
                                <div class="d-flex gap-2">
                                    <input type="color" name="color_primario" id="color_primario" class="form-control form-control-color" 
                                        value="{{ old('color_primario', $ajuste->color_primario ?? '#0d6efd') }}" style="width: 50px; height: 38px;">
                                    <input type="text" class="form-control color-text-input" value="{{ $ajuste->color_primario ?? '#0d6efd' }}" readonly>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <label for="color_secundario" class="form-label fw-bold">Color Secundario</label>
                                <div class="d-flex gap-2">
                                    <input type="color" name="color_secundario" id="color_secundario" class="form-control form-control-color" 
                                        value="{{ old('color_secundario', $ajuste->color_secundario ?? '#6c757d') }}" style="width: 50px; height: 38px;">
                                    <input type="text" class="form-control color-text-input" value="{{ $ajuste->color_secundario ?? '#6c757d' }}" readonly>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <label for="color_acento" class="form-label fw-bold">Color de Acento</label>
                                <div class="d-flex gap-2">
                                    <input type="color" name="color_acento" id="color_acento" class="form-control form-control-color" 
                                        value="{{ old('color_acento', $ajuste->color_acento ?? '#0dcaf0') }}" style="width: 50px; height: 38px;">
                                    <input type="text" class="form-control color-text-input" value="{{ $ajuste->color_acento ?? '#0dcaf0' }}" readonly>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <label for="color_textos_principales" class="form-label fw-bold">Textos Principales</label>
                                <div class="d-flex gap-2">
                                    <input type="color" name="color_textos_principales" id="color_textos_principales" class="form-control form-control-color" 
                                        value="{{ old('color_textos_principales', $ajuste->color_textos_principales ?? '#212529') }}" style="width: 50px; height: 38px;">
                                    <input type="text" class="form-control color-text-input" value="{{ $ajuste->color_textos_principales ?? '#212529' }}" readonly>
                                </div>
                            </div>

                            <div class="col-md-3">
                                <label for="color_encabezado" class="form-label fw-bold">Color Encabezado (Header)</label>
                                <div class="d-flex gap-2">
                                    <input type="color" name="color_encabezado" id="color_encabezado" class="form-control form-control-color" 
                                        value="{{ old('color_encabezado', $ajuste->color_encabezado ?? '#ffffff') }}" style="width: 50px; height: 38px;">
                                    <input type="text" class="form-control color-text-input" value="{{ $ajuste->color_encabezado ?? '#ffffff' }}" readonly>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <label for="color_pie_pagina" class="form-label fw-bold">Color Pie de Página (Footer)</label>
                                <div class="d-flex gap-2">
                                    <input type="color" name="color_pie_pagina" id="color_pie_pagina" class="form-control form-control-color" 
                                        value="{{ old('color_pie_pagina', $ajuste->color_pie_pagina ?? '#212529') }}" style="width: 50px; height: 38px;">
                                    <input type="text" class="form-control color-text-input" value="{{ $ajuste->color_pie_pagina ?? '#212529' }}" readonly>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <label for="color_tarjetas" class="form-label fw-bold">Color de Tarjetas (Cards)</label>
                                <div class="d-flex gap-2">
                                    <input type="color" name="color_tarjetas" id="color_tarjetas" class="form-control form-control-color" 
                                        value="{{ old('color_tarjetas', $ajuste->color_tarjetas ?? '#ffffff') }}" style="width: 50px; height: 38px;">
                                    <input type="text" class="form-control color-text-input" value="{{ $ajuste->color_tarjetas ?? '#ffffff' }}" readonly>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <label for="color_enlaces" class="form-label fw-bold">Color de Enlaces (Links)</label>
                                <div class="d-flex gap-2">
                                    <input type="color" name="color_enlaces" id="color_enlaces" class="form-control form-control-color" 
                                        value="{{ old('color_enlaces', $ajuste->color_enlaces ?? '#0d6efd') }}" style="width: 50px; height: 38px;">
                                    <input type="text" class="form-control color-text-input" value="{{ $ajuste->color_enlaces ?? '#0d6efd' }}" readonly>
                                </div>
                            </div>

                            <div class="col-md-3">
                                <label for="color_botones" class="form-label fw-bold">Color Botones (Normal)</label>
                                <div class="d-flex gap-2">
                                    <input type="color" name="color_botones" id="color_botones" class="form-control form-control-color" 
                                        value="{{ old('color_botones', $ajuste->color_botones ?? '#0d6efd') }}" style="width: 50px; height: 38px;">
                                    <input type="text" class="form-control color-text-input" value="{{ $ajuste->color_botones ?? '#0d6efd' }}" readonly>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <label for="color_botones_principales" class="form-label fw-bold">Botones Principales</label>
                                <div class="d-flex gap-2">
                                    <input type="color" name="color_botones_principales" id="color_botones_principales" class="form-control form-control-color" 
                                        value="{{ old('color_botones_principales', $ajuste->color_botones_principales ?? '#0d6efd') }}" style="width: 50px; height: 38px;">
                                    <input type="text" class="form-control color-text-input" value="{{ $ajuste->color_botones_principales ?? '#0d6efd' }}" readonly>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <label for="color_botones_carrito" class="form-label fw-bold">Botón "Agregar al Carrito"</label>
                                <div class="d-flex gap-2">
                                    <input type="color" name="color_botones_carrito" id="color_botones_carrito" class="form-control form-control-color" 
                                        value="{{ old('color_botones_carrito', $ajuste->color_botones_carrito ?? '#0d6efd') }}" style="width: 50px; height: 38px;">
                                    <input type="text" class="form-control color-text-input" value="{{ $ajuste->color_botones_carrito ?? '#0d6efd' }}" readonly>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <label for="color_badges" class="form-label fw-bold">Color de Badges</label>
                                <div class="d-flex gap-2">
                                    <input type="color" name="color_badges" id="color_badges" class="form-control form-control-color" 
                                        value="{{ old('color_badges', $ajuste->color_badges ?? '#0d6efd') }}" style="width: 50px; height: 38px;">
                                    <input type="text" class="form-control color-text-input" value="{{ $ajuste->color_badges ?? '#0d6efd' }}" readonly>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <label for="color_etiquetas_ofertas" class="form-label fw-bold">Etiquetas de Ofertas/Descuento</label>
                                <div class="d-flex gap-2">
                                    <input type="color" name="color_etiquetas_ofertas" id="color_etiquetas_ofertas" class="form-control form-control-color" 
                                        value="{{ old('color_etiquetas_ofertas', $ajuste->color_etiquetas_ofertas ?? '#dc3545') }}" style="width: 50px; height: 38px;">
                                    <input type="text" class="form-control color-text-input" value="{{ $ajuste->color_etiquetas_ofertas ?? '#dc3545' }}" readonly>
                                </div>
                            </div>
                        </div>

                        <!-- Menú Section -->
                        <h4 class="text-dark fw-bold mb-3 border-bottom pb-2 mt-4"><i class="bi bi-menu-button-wide-fill text-primary me-2"></i>Personalización del Menú de Navegación</h4>
                        <div class="row g-3 mb-4">
                            <div class="col-md-3">
                                <label for="menu_bg_color" class="form-label fw-bold">Fondo del Menú</label>
                                <div class="d-flex gap-2">
                                    <input type="color" name="menu_bg_color" id="menu_bg_color" class="form-control form-control-color" 
                                        value="{{ old('menu_bg_color', $ajuste->menu_bg_color ?? '#ffffff') }}" style="width: 50px; height: 38px;">
                                    <input type="text" class="form-control color-text-input" value="{{ $ajuste->menu_bg_color ?? '#ffffff' }}" readonly>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <label for="menu_text_color" class="form-label fw-bold">Texto del Menú</label>
                                <div class="d-flex gap-2">
                                    <input type="color" name="menu_text_color" id="menu_text_color" class="form-control form-control-color" 
                                        value="{{ old('menu_text_color', $ajuste->menu_text_color ?? '#475569') }}" style="width: 50px; height: 38px;">
                                    <input type="text" class="form-control color-text-input" value="{{ $ajuste->menu_text_color ?? '#475569' }}" readonly>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <label for="menu_hover_color" class="form-label fw-bold">Texto en Hover</label>
                                <div class="d-flex gap-2">
                                    <input type="color" name="menu_hover_color" id="menu_hover_color" class="form-control form-control-color" 
                                        value="{{ old('menu_hover_color', $ajuste->menu_hover_color ?? '#0d6efd') }}" style="width: 50px; height: 38px;">
                                    <input type="text" class="form-control color-text-input" value="{{ $ajuste->menu_hover_color ?? '#0d6efd' }}" readonly>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <label for="menu_active_color" class="form-label fw-bold">Texto Enlace Activo</label>
                                <div class="d-flex gap-2">
                                    <input type="color" name="menu_active_color" id="menu_active_color" class="form-control form-control-color" 
                                        value="{{ old('menu_active_color', $ajuste->menu_active_color ?? '#0d6efd') }}" style="width: 50px; height: 38px;">
                                    <input type="text" class="form-control color-text-input" value="{{ $ajuste->menu_active_color ?? '#0d6efd' }}" readonly>
                                </div>
                            </div>

                            <div class="col-md-3">
                                <label for="menu_hover_bg_color" class="form-label fw-bold">Fondo en Hover</label>
                                <div class="d-flex gap-2">
                                    <input type="color" name="menu_hover_bg_color" id="menu_hover_bg_color" class="form-control form-control-color" 
                                        value="{{ old('menu_hover_bg_color', $ajuste->menu_hover_bg_color ?? '#f1f5f9') }}" style="width: 50px; height: 38px;">
                                    <input type="text" class="form-control color-text-input" value="{{ $ajuste->menu_hover_bg_color ?? '#f1f5f9' }}" readonly>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <label for="menu_icon_color" class="form-label fw-bold">Color de Iconos</label>
                                <div class="d-flex gap-2">
                                    <input type="color" name="menu_icon_color" id="menu_icon_color" class="form-control form-control-color" 
                                        value="{{ old('menu_icon_color', $ajuste->menu_icon_color ?? '#475569') }}" style="width: 50px; height: 38px;">
                                    <input type="text" class="form-control color-text-input" value="{{ $ajuste->menu_icon_color ?? '#475569' }}" readonly>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <label for="menu_active_border_color" class="form-label fw-bold">Indicador/Borde Activo</label>
                                <div class="d-flex gap-2">
                                    <input type="color" name="menu_active_border_color" id="menu_active_border_color" class="form-control form-control-color" 
                                        value="{{ old('menu_active_border_color', $ajuste->menu_active_border_color ?? '#0d6efd') }}" style="width: 50px; height: 38px;">
                                    <input type="text" class="form-control color-text-input" value="{{ $ajuste->menu_active_border_color ?? '#0d6efd' }}" readonly>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <label for="menu_opacity" class="form-label fw-bold">Opacidad del Menú (<span id="opacity_val">{{ number_format($ajuste->menu_opacity ?? 1.00, 2) }}</span>)</label>
                                <div class="d-flex align-items-center gap-3">
                                    <input type="range" class="form-range" name="menu_opacity" id="menu_opacity" min="0" max="1" step="0.05" 
                                        value="{{ old('menu_opacity', $ajuste->menu_opacity ?? 1.00) }}" style="flex: 1;">
                                </div>
                            </div>
                        </div>

                        <div class="d-flex justify-content-between align-items-center border-top pt-3 mt-4">
                            <div>
                                <button type="button" class="btn btn-outline-secondary" id="btnRestaurarMenu">
                                    <i class="bi bi-arrow-counterclockwise"></i> Restaurar Menú Predeterminado
                                </button>
                            </div>
                            @can('Gestionar Apariencia')
                                <button type="submit" class="btn btn-primary px-5 py-2">
                                    <i class="bi bi-save"></i> Guardar Cambios
                                </button>
                            @else
                                <div class="alert alert-warning mb-0 py-2 px-3 fs-6">
                                    <i class="bi bi-exclamation-triangle"></i> No tienes permisos para gestionar la apariencia.
                                </div>
                            @endcan
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Update hex code input reading dynamically when picking color
            const colorPickers = document.querySelectorAll('.form-control-color');
            colorPickers.forEach(picker => {
                picker.addEventListener('input', function() {
                    const textInput = this.nextElementSibling;
                    if (textInput) {
                        textInput.value = this.value;
                    }
                });
            });

            // Update opacity range value label
            const opacityInput = document.getElementById('menu_opacity');
            const opacityVal = document.getElementById('opacity_val');
            if (opacityInput && opacityVal) {
                opacityInput.addEventListener('input', function() {
                    opacityVal.textContent = parseFloat(this.value).toFixed(2);
                });
            }

            // Restore menu default values
            const btnRestaurar = document.getElementById('btnRestaurarMenu');
            if (btnRestaurar) {
                btnRestaurar.addEventListener('click', function() {
                    const defaults = {
                        'menu_bg_color': '#ffffff',
                        'menu_text_color': '#475569',
                        'menu_hover_color': '#0d6efd',
                        'menu_active_color': '#0d6efd',
                        'menu_hover_bg_color': '#f1f5f9',
                        'menu_icon_color': '#475569',
                        'menu_active_border_color': '#0d6efd',
                        'menu_opacity': '1.00'
                    };

                    Object.keys(defaults).forEach(id => {
                        const element = document.getElementById(id);
                        if (element) {
                            element.value = defaults[id];
                            if (element.type === 'color') {
                                const textInput = element.nextElementSibling;
                                if (textInput) {
                                    textInput.value = defaults[id];
                                }
                            }
                            if (id === 'menu_opacity' && opacityVal) {
                                opacityVal.textContent = '1.00';
                            }
                        }
                    });
                });
            }
        });
    </script>
@endsection
