@extends('layouts.admin')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1>Configuración de Secciones del Home</h1>
    </div>
    <hr>

    <div class="row">
        <div class="col-md-12">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-light">
                    <h4 class="mb-0 text-dark">Componentes de la Página de Inicio</h4>
                </div>

                <div class="card-body">
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle-fill me-2"></i>
                        Aquí puede administrar el orden de visualización, la cantidad de elementos y la visibilidad de los diferentes bloques de contenido en la página de inicio pública de la tienda.
                    </div>

                    <div class="table-responsive mt-3">
                        <table class="table table-bordered table-hover table-striped align-middle">
                            <thead>
                                <tr>
                                    <th style="width: 80px; text-align: center;">Orden</th>
                                    <th>Sección (Componente)</th>
                                    <th>Identificador</th>
                                    <th style="text-align: center;">Límite de Elementos</th>
                                    <th style="text-align: center;">Estado (Visibilidad)</th>
                                    <th style="width: 150px; text-align: center;">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($secciones as $seccion)
                                    <tr>
                                        <td style="text-align: center; font-weight: bold; font-size: 16px;">
                                            <span class="badge bg-primary px-3 py-2 rounded-circle">{{ $seccion->orden }}</span>
                                        </td>
                                        <td>
                                            <div class="fw-bold fs-6 text-dark">{{ $seccion->titulo }}</div>
                                        </td>
                                        <td>
                                            <code>{{ $seccion->clave }}</code>
                                        </td>
                                        <td style="text-align: center; font-weight: 500;">
                                            {{ $seccion->limite }} elementos
                                        </td>
                                        <td style="text-align: center;">
                                            <form action="{{ route('admin.secciones_home.toggle', $seccion->id) }}" method="POST">
                                                @csrf
                                                @if ($seccion->activo)
                                                    <button type="submit" class="btn btn-success btn-sm px-3 rounded-pill">Visible</button>
                                                @else
                                                    <button type="submit" class="btn btn-danger btn-sm px-3 rounded-pill">Oculta</button>
                                                @endif
                                            </form>
                                        </td>
                                        <td style="text-align: center;">
                                            @can('Editar Sección Home')
                                                <a href="{{ route('admin.secciones_home.edit', $seccion->id) }}" class="btn btn-success btn-sm">
                                                    <i class="bi bi-pencil"></i> Configurar
                                                </a>
                                            @endcan
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
