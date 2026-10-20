@extends('layouts.admin')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1>Administración de "Contacto"</h1>
    </div>
    <hr>

    <div class="row">
        <div class="col-md-12">
            <div class="card shadow-sm">
                <div class="card-body">
                    <form action="{{ route('admin.contacto.store') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="nombre_empresa" class="form-label fw-bold">Nombre de la Empresa</label>
                                <input type="text" name="nombre_empresa" id="nombre_empresa" class="form-control"
                                    value="{{ old('nombre_empresa', $contacto->nombre_empresa ?? '') }}" required>
                            </div>
                            <div class="col-md-6">
                                <label for="email" class="form-label fw-bold">Correo Electrónico de Contacto</label>
                                <input type="email" name="email" id="email" class="form-control"
                                    value="{{ old('email', $contacto->email ?? '') }}" required>
                            </div>

                            <div class="col-md-3">
                                <label for="pais" class="form-label fw-bold">País</label>
                                <input type="text" name="pais" id="pais" class="form-control"
                                    value="{{ old('pais', $contacto->pais ?? '') }}" required>
                            </div>
                            <div class="col-md-3">
                                <label for="departamento" class="form-label fw-bold">Departamento / Estado</label>
                                <input type="text" name="departamento" id="departamento" class="form-control"
                                    value="{{ old('departamento', $contacto->departamento ?? '') }}" required>
                            </div>
                            <div class="col-md-3">
                                <label for="provincia" class="form-label fw-bold">Provincia / Ciudad</label>
                                <input type="text" name="provincia" id="provincia" class="form-control"
                                    value="{{ old('provincia', $contacto->provincia ?? '') }}" required>
                            </div>
                            <div class="col-md-3">
                                <label for="direccion" class="form-label fw-bold">Dirección Exacta</label>
                                <input type="text" name="direccion" id="direccion" class="form-control"
                                    value="{{ old('direccion', $contacto->direccion ?? '') }}" required>
                            </div>

                            <div class="col-md-4">
                                <label for="telefono_principal" class="form-label fw-bold">Teléfono Principal</label>
                                <input type="text" name="telefono_principal" id="telefono_principal" class="form-control"
                                    value="{{ old('telefono_principal', $contacto->telefono_principal ?? '') }}" required>
                            </div>
                            <div class="col-md-4">
                                <label for="telefono_secundario" class="form-label fw-bold">Teléfono Secundario (opcional)</label>
                                <input type="text" name="telefono_secundario" id="telefono_secundario" class="form-control"
                                    value="{{ old('telefono_secundario', $contacto->telefono_secundario ?? '') }}">
                            </div>
                            <div class="col-md-4">
                                <label for="whatsapp" class="form-label fw-bold">Enlace/Número de WhatsApp (opcional)</label>
                                <input type="text" name="whatsapp" id="whatsapp" class="form-control"
                                    value="{{ old('whatsapp', $contacto->whatsapp ?? '') }}" placeholder="ej: +51999888777">
                            </div>

                            <div class="col-md-6">
                                <label for="horario_atencion" class="form-label fw-bold">Horario de Atención</label>
                                <input type="text" name="horario_atencion" id="horario_atencion" class="form-control"
                                    value="{{ old('horario_atencion', $contacto->horario_atencion ?? '') }}" placeholder="ej: Lunes a Viernes de 9 AM a 6 PM" required>
                            </div>

                            <div class="col-md-6">
                                <label for="imagen_banner" class="form-label fw-bold">Banner o Imagen de Cabecera</label>
                                <input type="file" name="imagen_banner" id="imagen_banner" class="form-control" accept="image/*">
                                @if ($contacto && $contacto->imagen_banner)
                                    <div class="mt-2">
                                        <img src="{{ asset('storage/' . $contacto->imagen_banner) }}" alt="Preview" class="img-thumbnail" style="max-height: 120px;">
                                    </div>
                                @endif
                            </div>

                            <div class="col-12">
                                <label for="mensaje_bienvenida" class="form-label fw-bold">Mensaje de Bienvenida / Descripción de la página</label>
                                <textarea name="mensaje_bienvenida" id="mensaje_bienvenida" class="form-control" rows="3">{{ old('mensaje_bienvenida', $contacto->mensaje_bienvenida ?? '') }}</textarea>
                            </div>

                            <div class="col-12">
                                <label for="google_maps_link" class="form-label fw-bold">Enlace Embebido de Google Maps (Iframe o URL de inserción)</label>
                                <textarea name="google_maps_link" id="google_maps_link" class="form-control" rows="3" placeholder="Pegue el iframe o enlace del mapa aquí...">{{ old('google_maps_link', $contacto->google_maps_link ?? '') }}</textarea>
                            </div>

                            <!-- Social Links -->
                            <div class="col-12 mt-2">
                                <h4 class="text-dark fw-bold mb-3"><i class="bi bi-share text-primary"></i> Redes Sociales</h4>
                            </div>

                            <div class="col-md-6">
                                <label for="facebook_link" class="form-label fw-bold">Enlace de Facebook</label>
                                <input type="url" name="facebook_link" id="facebook_link" class="form-control"
                                    value="{{ old('facebook_link', $contacto->facebook_link ?? '') }}" placeholder="https://facebook.com/...">
                            </div>
                            <div class="col-md-6">
                                <label for="instagram_link" class="form-label fw-bold">Enlace de Instagram</label>
                                <input type="url" name="instagram_link" id="instagram_link" class="form-control"
                                    value="{{ old('instagram_link', $contacto->instagram_link ?? '') }}" placeholder="https://instagram.com/...">
                            </div>
                            <div class="col-md-6">
                                <label for="youtube_link" class="form-label fw-bold">Enlace de YouTube</label>
                                <input type="url" name="youtube_link" id="youtube_link" class="form-control"
                                    value="{{ old('youtube_link', $contacto->youtube_link ?? '') }}" placeholder="https://youtube.com/...">
                            </div>
                            <div class="col-md-6">
                                <label for="twitter_link" class="form-label fw-bold">Enlace de Twitter / X</label>
                                <input type="url" name="twitter_link" id="twitter_link" class="form-control"
                                    value="{{ old('twitter_link', $contacto->twitter_link ?? '') }}" placeholder="https://twitter.com/...">
                            </div>

                            <div class="col-12 mt-3">
                                <div class="form-check form-switch fs-5">
                                    <input class="form-check-input" type="checkbox" name="activo" id="activo" value="1"
                                        {{ old('activo', $contacto->activo ?? true) ? 'checked' : '' }}>
                                    <label class="form-check-label fw-bold text-dark" for="activo">Sección "Contacto" activa (Pública)</label>
                                </div>
                            </div>
                        </div>

                        @can('Editar Contacto')
                            <div class="mt-4 text-end">
                                <button type="submit" class="btn btn-primary px-5 py-2">
                                    <i class="bi bi-save"></i> Guardar Cambios
                                </button>
                            </div>
                        @else
                            <div class="alert alert-warning mt-3 py-2 px-3 fs-6">
                                <i class="bi bi-exclamation-triangle"></i> No tienes permisos para editar este módulo.
                            </div>
                        @endcan
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
