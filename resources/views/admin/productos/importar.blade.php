@extends('layouts.admin')

@section('content')
    <h1>Importar productos</h1>
    <hr>

    <div class="alert alert-info" role="status">
        <strong>Carga masiva del inventario.</strong>
        Pega la lista desde Excel: una fila por producto y las columnas separadas por tabulación.
        Orden esperado:
        <code>DESCRIPCIÓN &nbsp; MODELO &nbsp; CANTIDAD &nbsp; PRECIO COMPRA &nbsp; PRECIO VENTA &nbsp; CATEGORÍA</code>.
        La categoría es opcional (se deduce del nombre) y también se acepta el número de fila del Excel.
        Los productos se crean en el catálogo compartido con la app Android.
    </div>

    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h4>Lista para importar</h4>
                </div>
                <div class="card-body">
                    <form action="{{ route('admin.productos.importar.guardar') }}" method="POST" id="importarForm">
                        @csrf
                        <input type="hidden" name="accion" id="accionImportar" value="previsualizar">

                        <div class="form-group">
                            <label for="filas">Filas (pégalas desde Excel)</label>
                            <textarea name="filas" id="filas" rows="12" class="form-control font-monospace @error('filas') is-invalid @enderror"
                                placeholder="POTECTOR DE TECLADO DE 15.6&#9;&#9;45&#9;15.00&#9;25.00&#9;PROTECTOR DE TECLADO&#10;MOUSE GENIUS&#9;DX 101&#9;6&#9;28.00&#9;45.00&#9;Mouses">{{ old('filas', $filasTexto ?? '') }}</textarea>
                            @error('filas')
                                <small class="text-danger">{{ $message }}</small>
                            @enderror
                            <small class="text-muted d-block mt-1">
                                Puedes pegar con el número de fila incluido (1, 2, 3...). Si tu Excel trae la columna
                                “PRECIO + IGV”, se ignora y se usa la última columna como precio de venta.
                            </small>
                        </div>

                        <div class="row mt-3">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="categoria_id">Categoría por defecto</label>
                                    <select name="categoria_id" id="categoria_id" class="form-control">
                                        <option value="">Deducir del nombre</option>
                                        @foreach ($categorias as $categoria)
                                            <option value="{{ $categoria->id }}"
                                                {{ (int) old('categoria_id', $categoriaSeleccionada ?? 0) === $categoria->id ? 'selected' : '' }}>
                                                {{ $categoria->nombre }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <small class="text-muted">Se usa solo cuando el nombre no permite deducirla.</small>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="marca_id">Marca por defecto</label>
                                    <select name="marca_id" id="marca_id" class="form-control">
                                        <option value="">Deducir del nombre</option>
                                        @foreach ($marcas as $marca)
                                            <option value="{{ $marca->id }}"
                                                {{ (int) old('marca_id', $marcaSeleccionada ?? 0) === $marca->id ? 'selected' : '' }}>
                                                {{ $marca->nombre }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <small class="text-muted">Si el nombre ya menciona una marca, se respeta esa.</small>
                                </div>
                            </div>
                            <div class="col-md-4 d-flex align-items-center">
                                <div class="form-check mt-3">
                                    <input type="checkbox" name="actualizar" id="actualizar" value="1" class="form-check-input"
                                        {{ old('actualizar', $actualizar ?? false) ? 'checked' : '' }}>
                                    <label for="actualizar" class="form-check-label">
                                        Actualizar stock y precio si el producto ya existe
                                    </label>
                                </div>
                            </div>
                        </div>

                        <div class="mt-4 d-flex flex-wrap gap-2">
                            <button type="submit" class="btn btn-outline-primary" onclick="document.getElementById('accionImportar').value='previsualizar'">
                                <i class="bi bi-eye"></i> Previsualizar
                            </button>
                            <button type="submit" class="btn btn-primary" onclick="document.getElementById('accionImportar').value='importar'">
                                <i class="bi bi-upload"></i> Importar productos
                            </button>
                            <a href="{{ route('admin.productos.index') }}" class="btn btn-outline-secondary">
                                <i class="bi bi-arrow-left"></i> Volver al listado
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    @isset($previa)
        @php
            $nuevos = collect($previa)->whereNull('existente')->count();
            $existentes = collect($previa)->whereNotNull('existente')->count();
            $conAvisos = collect($previa)->filter(fn ($f) => $f['avisos'] !== [])->count();
        @endphp

        <div class="card mt-4">
            <div class="card-header d-flex flex-wrap justify-content-between align-items-center">
                <h4 class="mb-0">Previsualización ({{ count($previa) }} filas)</h4>
                <span>
                    <span class="badge bg-success">{{ $nuevos }} nuevos</span>
                    <span class="badge bg-secondary">{{ $existentes }} ya existen</span>
                    <span class="badge bg-warning text-dark">{{ $conAvisos }} con avisos</span>
                </span>
            </div>
            <div class="card-body">
                @if ($conAvisos > 0)
                    <div class="alert alert-warning">
                        Las filas con avisos no se crearán hasta que tengan categoría y precio de venta.
                        Corrige la lista y vuelve a previsualizar.
                    </div>
                @endif

                <div class="table-responsive">
                    <table class="table table-sm table-bordered align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>#</th>
                                <th>Producto</th>
                                <th>Modelo</th>
                                <th class="text-end">Cant.</th>
                                <th class="text-end">P. compra</th>
                                <th class="text-end">P. venta</th>
                                <th>Categoría</th>
                                <th>Marca</th>
                                <th>Estado</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($previa as $i => $fila)
                                <tr class="{{ $fila['avisos'] !== [] ? 'table-warning' : '' }}">
                                    <td>{{ $i + 1 }}</td>
                                    <td>{{ $fila['nombre'] }}</td>
                                    <td>{{ $fila['modelo'] ?: '—' }}</td>
                                    <td class="text-end">{{ $fila['cantidad'] }}</td>
                                    <td class="text-end">S/ {{ number_format($fila['pc'], 2) }}</td>
                                    <td class="text-end">S/ {{ number_format($fila['pv'], 2) }}</td>
                                    <td>
                                        {{ $fila['categoria'] ? \App\Models\Categoria::find($fila['categoria'])?->nombre : '—' }}
                                    </td>
                                    <td>{{ $fila['marca'] ? \App\Models\Marca::find($fila['marca'])?->nombre : '—' }}</td>
                                    <td>
                                        @if ($fila['avisos'] !== [])
                                            <span class="text-danger">{{ implode(', ', $fila['avisos']) }}</span>
                                        @elseif ($fila['existente'])
                                            <span class="text-secondary">Ya existe ({{ $fila['existente_codigo'] }})</span>
                                        @else
                                            <span class="text-success">Se creará</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="alert alert-secondary mb-0">
                    Si todo está correcto, pulsa <strong>Importar productos</strong> otra vez para guardar.
                    @if ($existentes > 0)
                        Los que ya existen solo se actualizan si marcaste la casilla de actualizar.
                    @endif
                </div>
            </div>
        </div>
    @endisset
@endsection
