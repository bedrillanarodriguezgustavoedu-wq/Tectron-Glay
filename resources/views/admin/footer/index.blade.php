@extends('layouts.admin')

@section('content')
    <style>
        .card-header-tabs .nav-link {
            color: rgba(255, 255, 255, 0.8) !important;
            border: none !important;
            border-bottom: 3px solid transparent !important;
            font-weight: 500;
            padding: 12px 20px;
            transition: all 0.2s ease;
        }
        .card-header-tabs .nav-link.active {
            background-color: transparent !important;
            color: #ffffff !important;
            border-bottom: 3px solid #ffffff !important;
            font-weight: bold;
        }
        .card-header-tabs .nav-link:hover:not(.active) {
            color: #ffffff !important;
            background-color: rgba(255, 255, 255, 0.15) !important;
        }
        .text-monospace {
            font-family: SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace;
        }
    </style>

    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1>Administración del Pie de Página (Footer)</h1>
    </div>
    <hr>

    <form action="{{ route('admin.footer.store_settings') }}" method="POST" enctype="multipart/form-data">
        @csrf
        
        <!-- Contenedor de marcas eliminadas de enlaces -->
        <div id="hidden-deletions-container"></div>

        <div class="row">
            <div class="col-md-12">
                <div class="card shadow-sm">
                    <div class="card-header bg-primary text-white p-0">
                        <ul class="nav nav-tabs card-header-tabs ms-3 mt-1" id="footerTab" role="tablist" style="border-bottom: none;">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active" id="general-tab" data-bs-toggle="tab" data-bs-target="#general" type="button" role="tab">
                                    <i class="bi bi-info-circle-fill"></i> General & Contacto
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="legal-tab" data-bs-toggle="tab" data-bs-target="#legal" type="button" role="tab">
                                    <i class="bi bi-file-earmark-text-fill"></i> Páginas Legales
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="links-tab" data-bs-toggle="tab" data-bs-target="#links" type="button" role="tab">
                                    <i class="bi bi-link-45deg"></i> Enlaces Rápidos
                                </button>
                            </li>
                        </ul>
                    </div>
                    
                    <div class="card-body py-4">
                        <div class="tab-content" id="footerTabContent">
                            
                            <!-- TAB 1: GENERAL & CONTACT -->
                            <div class="tab-pane fade show active" id="general" role="tabpanel">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label for="nombre_empresa" class="form-label fw-bold">Nombre de la Empresa</label>
                                        <input type="text" name="nombre_empresa" id="nombre_empresa" class="form-control"
                                            value="{{ old('nombre_empresa', $settings->nombre_empresa ?? 'Tectron Galy') }}" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="copyright_texto" class="form-label fw-bold">Texto de Copyright</label>
                                        <input type="text" name="copyright_texto" id="copyright_texto" class="form-control"
                                            value="{{ old('copyright_texto', $settings->copyright_texto ?? '© Copyright Tectron Galy. All Rights Reserved.') }}">
                                    </div>

                                    <div class="col-12">
                                        <label for="descripcion" class="form-label fw-bold">Descripción del Pie de Página</label>
                                        <textarea name="descripcion" id="descripcion" class="form-control" rows="3">{{ old('descripcion', $settings->descripcion ?? '') }}</textarea>
                                    </div>

                                    <div class="col-md-6">
                                        <label for="logo" class="form-label fw-bold">Logotipo del Footer (Opcional)</label>
                                        <input type="file" name="logo" id="logo" class="form-control" accept="image/*">
                                        @if ($settings && $settings->logo)
                                            <div class="mt-2">
                                                <img src="{{ asset('storage/' . $settings->logo) }}" alt="Preview" class="img-thumbnail" style="max-height: 80px;">
                                            </div>
                                        @endif
                                    </div>

                                    <!-- Los datos compartidos de contacto se administran en Contacto. -->
                                    <div class="col-12 mt-4">
                                        <div class="alert alert-info mb-0">
                                            <i class="bi bi-info-circle"></i>
                                            Teléfono, WhatsApp, correo, dirección, horario y redes compartidas se administran desde
                                            <a href="{{ route('admin.contacto.index') }}">Contacto</a> para mantener sincronizados la web y GALY.
                                        </div>
                                    </div>

                                    <!-- Redes sociales adicionales que no están en el perfil compartido de contacto. -->
                                    <div class="col-12 mt-4">
                                        <h5 class="text-primary fw-bold mb-3"><i class="bi bi-share"></i> Redes Sociales Adicionales</h5>
                                        <p class="text-muted">TikTok y LinkedIn se configuran aquí. Las redes compartidas se administran desde Contacto.</p>
                                    </div>

                                    <div class="col-md-4">
                                        <label for="tiktok_link" class="form-label fw-bold">TikTok</label>
                                        <input type="url" name="tiktok_link" id="tiktok_link" class="form-control" value="{{ old('tiktok_link', $settings->tiktok_link ?? '') }}">
                                    </div>
                                    <div class="col-md-4">
                                        <label for="linkedin_link" class="form-label fw-bold">LinkedIn</label>
                                        <input type="url" name="linkedin_link" id="linkedin_link" class="form-control" value="{{ old('linkedin_link', $settings->linkedin_link ?? '') }}">
                                    </div>

                                    <!-- Apps -->
                                    <div class="col-12 mt-4">
                                        <h5 class="text-primary fw-bold mb-3"><i class="bi bi-phone"></i> Enlaces de Aplicaciones</h5>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="app_store_link" class="form-label fw-bold">App Store (iOS)</label>
                                        <input type="url" name="app_store_link" id="app_store_link" class="form-control" value="{{ old('app_store_link', $settings->app_store_link ?? '') }}">
                                    </div>
                                    <div class="col-md-6">
                                        <label for="google_play_link" class="form-label fw-bold">Google Play (Android)</label>
                                        <input type="url" name="google_play_link" id="google_play_link" class="form-control" value="{{ old('google_play_link', $settings->google_play_link ?? '') }}">
                                    </div>

                                    <!-- Categorías destacadas -->
                                    <div class="col-12 mt-4">
                                        <h5 class="text-primary fw-bold mb-3"><i class="bi bi-bookmark-star"></i> Categorías Destacadas (Seleccionar hasta 6)</h5>
                                        <div class="row">
                                            @foreach ($categorias as $cat)
                                                <div class="col-md-3 mb-2">
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="checkbox" name="categorias_destacadas[]" value="{{ $cat->id }}" id="cat_{{ $cat->id }}"
                                                            {{ in_array($cat->id, $settings->categorias_destacadas ?? []) ? 'checked' : '' }}>
                                                        <label class="form-check-label" for="cat_{{ $cat->id }}">
                                                            {{ $cat->nombre }}
                                                        </label>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- TAB 2: LEGAL CONTENT -->
                            <div class="tab-pane fade" id="legal" role="tabpanel">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label for="terminos_condiciones_contenido" class="form-label fw-bold">Términos y Condiciones (HTML)</label>
                                        <textarea name="terminos_condiciones_contenido" id="terminos_condiciones_contenido" class="form-control" rows="8">{{ old('terminos_condiciones_contenido', $settings->terminos_condiciones_contenido ?? '') }}</textarea>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="politica_privacidad_contenido" class="form-label fw-bold">Política de Privacidad (HTML)</label>
                                        <textarea name="politica_privacidad_contenido" id="politica_privacidad_contenido" class="form-control" rows="8">{{ old('politica_privacidad_contenido', $settings->politica_privacidad_contenido ?? '') }}</textarea>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="politica_cookies_contenido" class="form-label fw-bold">Política de Cookies (HTML)</label>
                                        <textarea name="politica_cookies_contenido" id="politica_cookies_contenido" class="form-control" rows="8">{{ old('politica_cookies_contenido', $settings->politica_cookies_contenido ?? '') }}</textarea>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="cambios_devoluciones_contenido" class="form-label fw-bold">Política de Cambios y Devoluciones (HTML)</label>
                                        <textarea name="cambios_devoluciones_contenido" id="cambios_devoluciones_contenido" class="form-control" rows="8">{{ old('cambios_devoluciones_contenido', $settings->cambios_devoluciones_contenido ?? '') }}</textarea>
                                    </div>
                                </div>
                            </div>

                            <!-- TAB 3: QUICK LINKS -->
                            <div class="tab-pane fade" id="links" role="tabpanel">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <h5 class="fw-bold mb-0">Enlaces Rápidos Configurados</h5>
                                    @can('Gestionar Footer')
                                        <button class="btn btn-success btn-sm rounded-pill px-3" type="button" onclick="agregarNuevoLinkDOM()">
                                            <i class="bi bi-plus-circle"></i> Agregar Enlace
                                        </button>
                                    @endcan
                                </div>

                                <div class="table-responsive">
                                    <table class="table table-striped table-hover align-middle">
                                        <thead>
                                            <tr>
                                                <th style="width: 100px; text-align: center;">Orden</th>
                                                <th>Título del Enlace</th>
                                                <th>Dirección URL</th>
                                                <th style="text-align: center; width: 120px;">Estado Activo</th>
                                                <th style="text-align: center; width: 80px;">Remover</th>
                                            </tr>
                                        </thead>
                                        <tbody id="enlaces-table-body">
                                            @forelse ($enlaces as $link)
                                                <tr>
                                                    <td class="text-center">
                                                        <input type="number" name="enlaces[{{ $link->id }}][orden]" value="{{ $link->orden }}" min="0" required class="form-control form-control-sm text-center fw-bold" style="width: 80px;">
                                                    </td>
                                                    <td>
                                                        <input type="text" name="enlaces[{{ $link->id }}][titulo]" value="{{ $link->titulo }}" required class="form-control form-control-sm fw-semibold">
                                                    </td>
                                                    <td>
                                                        <input type="text" name="enlaces[{{ $link->id }}][url]" value="{{ $link->url }}" required class="form-control form-control-sm text-monospace">
                                                    </td>
                                                    <td class="text-center">
                                                        <div class="form-check form-switch d-inline-block">
                                                            <input class="form-check-input" type="checkbox" name="enlaces[{{ $link->id }}][activo]" value="1" {{ $link->activo ? 'checked' : '' }}>
                                                        </div>
                                                    </td>
                                                    <td class="text-center">
                                                        @can('Gestionar Footer')
                                                            <button type="button" class="btn btn-outline-danger btn-sm border-0" onclick="eliminarLinkDOM(this, {{ $link->id }})">
                                                                <i class="bi bi-trash-fill"></i>
                                                            </button>
                                                        @endcan
                                                    </td>
                                                </tr>
                                            @empty
                                                <tr id="enlaces-empty-row">
                                                    <td colspan="5" class="text-center py-4 text-muted">No hay enlaces configurados. Presiona "Agregar Enlace" para configurar uno nuevo.</td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                        </div>
                    </div>

                    <!-- CARD FOOTER CON BOTÓN DE GUARDADO ÚNICO -->
                    @can('Gestionar Footer')
                        <div class="card-footer bg-light py-3 d-flex justify-content-end">
                            <button type="submit" class="btn btn-primary btn-lg px-5 shadow-sm fw-bold">
                                <i class="bi bi-check-circle-fill me-2"></i> Guardar Cambios
                            </button>
                        </div>
                    @endcan
                </div>
            </div>
        </div>
    </form>

    <script>
        let nuevoLinkIndex = 0;

        function eliminarLinkDOM(button, linkId) {
            Swal.fire({
                title: '¿Desea remover este enlace rápido?',
                text: "Se eliminará definitivamente al pulsar el botón 'Guardar Cambios'.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Sí, remover',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    const container = document.getElementById('hidden-deletions-container');
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = 'enlaces_eliminados[]';
                    input.value = linkId;
                    container.appendChild(input);

                    const row = button.closest('tr');
                    row.remove();

                    verificarTablaVacia();
                }
            });
        }

        function eliminarNuevaFilaDOM(button) {
            const row = button.closest('tr');
            row.remove();
            verificarTablaVacia();
        }

        function agregarNuevoLinkDOM() {
            const tbody = document.getElementById('enlaces-table-body');
            const emptyState = document.getElementById('enlaces-empty-row');
            if (emptyState) {
                emptyState.remove();
            }

            const row = document.createElement('tr');
            row.innerHTML = `
                <td class="text-center">
                    <input type="number" name="enlaces_nuevos[${nuevoLinkIndex}][orden]" value="${tbody.children.length + 1}" min="0" required class="form-control form-control-sm text-center fw-bold" style="width: 80px;">
                </td>
                <td>
                    <input type="text" name="enlaces_nuevos[${nuevoLinkIndex}][titulo]" placeholder="Ej. Soporte" required class="form-control form-control-sm fw-semibold">
                </td>
                <td>
                    <input type="text" name="enlaces_nuevos[${nuevoLinkIndex}][url]" placeholder="Ej. /soporte" required class="form-control form-control-sm text-monospace">
                </td>
                <td class="text-center">
                    <div class="form-check form-switch d-inline-block">
                        <input class="form-check-input" type="checkbox" name="enlaces_nuevos[${nuevoLinkIndex}][activo]" value="1" checked>
                    </div>
                </td>
                <td class="text-center">
                    <button type="button" class="btn btn-outline-danger btn-sm border-0" onclick="eliminarNuevaFilaDOM(this)">
                        <i class="bi bi-trash-fill"></i>
                    </button>
                </td>
            `;
            tbody.appendChild(row);
            nuevoLinkIndex++;
        }

        function verificarTablaVacia() {
            const tbody = document.getElementById('enlaces-table-body');
            if (tbody.children.length === 0) {
                tbody.innerHTML = `
                    <tr id="enlaces-empty-row">
                        <td colspan="5" class="text-center py-4 text-muted">No hay enlaces configurados. Presiona "Agregar Enlace" para configurar uno nuevo.</td>
                    </tr>
                `;
            }
        }
    </script>
@endsection
