@extends('layouts.admin')

@section('title', 'Usuarios · ' . ($ajuste->nombre ?? 'TECTRON'))

@section('content')
    @php
        $usuariosPagina = $usuarios->getCollection();
        $activosPagina = $usuariosPagina->where('estado', 1)->count();
        $inactivosPagina = $usuariosPagina->where('estado', 0)->count();
    @endphp

    <div class="ga-page-head">
        <div class="ga-page-head-main">
            <div class="ga-eyebrow">Equipo y accesos</div>
            <h1 class="ga-page-title">Usuarios</h1>
            <p class="ga-page-sub">Cuentas que pueden entrar al panel. Cada usuario hereda los permisos del rol que tenga asignado.</p>
        </div>
        <div class="ga-head-actions">
            <a href="{{ route('admin.roles.index') }}" class="btn-ga-ghost">
                <i class="bi bi-shield-check" aria-hidden="true"></i> Roles y permisos
            </a>
            <a href="{{ url('/admin/usuarios/create') }}" class="btn btn-primary">
                <i class="bi bi-plus-lg" aria-hidden="true"></i> Crear usuario
            </a>
            <span class="ga-count-pill">
                <i class="bi bi-people" aria-hidden="true"></i>
                <strong>{{ number_format($usuarios->total()) }}</strong> usuarios
            </span>
        </div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-6 col-xl-3">
            <div class="ga-kpi">
                <div class="ga-kpi-top">
                    <div>
                        <p class="ga-kpi-label">Total</p>
                        <p class="ga-kpi-value">{{ number_format($usuarios->total()) }}</p>
                    </div>
                    <span class="ga-kpi-icon is-info"><i class="bi bi-people-fill" aria-hidden="true"></i></span>
                </div>
                <span class="ga-kpi-foot"><i class="bi bi-person-badge" aria-hidden="true"></i> Cuentas del panel</span>
            </div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="ga-kpi">
                <div class="ga-kpi-top">
                    <div>
                        <p class="ga-kpi-label">En esta página</p>
                        <p class="ga-kpi-value">{{ number_format($usuarios->count()) }}</p>
                    </div>
                    <span class="ga-kpi-icon"><i class="bi bi-layers" aria-hidden="true"></i></span>
                </div>
                <span class="ga-kpi-foot"><i class="bi bi-eye" aria-hidden="true"></i> Visibles ahora</span>
            </div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="ga-kpi">
                <div class="ga-kpi-top">
                    <div>
                        <p class="ga-kpi-label">Activos</p>
                        <p class="ga-kpi-value">{{ number_format($activosPagina) }}</p>
                    </div>
                    <span class="ga-kpi-icon is-success"><i class="bi bi-person-check-fill" aria-hidden="true"></i></span>
                </div>
                <span class="ga-kpi-foot"><i class="bi bi-check2-circle" aria-hidden="true"></i> Pueden entrar</span>
            </div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="ga-kpi">
                <div class="ga-kpi-top">
                    <div>
                        <p class="ga-kpi-label">Inactivos</p>
                        <p class="ga-kpi-value">{{ number_format($inactivosPagina) }}</p>
                    </div>
                    <span class="ga-kpi-icon is-danger"><i class="bi bi-person-dash-fill" aria-hidden="true"></i></span>
                </div>
                <span class="ga-kpi-foot"><i class="bi bi-slash-circle" aria-hidden="true"></i> Sin acceso</span>
            </div>
        </div>
    </div>

    <div class="ga-surface ga-table-card">
        <div class="ga-card-head">
            <div>
                <h2>Usuarios registrados</h2>
                <p>Busca por nombre o correo</p>
            </div>
            @if ($usuarios->hasPages())
                <span class="badge bg-secondary">
                    Página {{ $usuarios->currentPage() }} de {{ $usuarios->lastPage() }}
                </span>
            @endif
        </div>
        <div class="card-body">
            <form action="{{ url('/admin/usuarios') }}" method="GET" class="ga-toolbar mb-3" role="search">
                <div class="ga-search">
                    <i class="bi bi-search" aria-hidden="true"></i>
                    <label class="visually-hidden" for="buscar-usuarios">Buscar usuarios</label>
                    <input type="search" id="buscar-usuarios" name="buscar" class="form-control"
                        placeholder="Buscar por nombre o correo" value="{{ $_REQUEST['buscar'] ?? '' }}">
                </div>
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-search" aria-hidden="true"></i> Buscar
                </button>
                @if (isset($_REQUEST['buscar']))
                    <a href="{{ url('/admin/usuarios') }}" class="btn btn-outline-secondary">
                        <i class="bi bi-x-lg" aria-hidden="true"></i> Limpiar
                    </a>
                @endif
            </form>

            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Nro</th>
                            <th>Usuario</th>
                            <th>Rol</th>
                            <th>Correo</th>
                            <th>Estado</th>
                            <th class="text-end">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php
                            $nro = ($usuarios->currentPage() - 1) * $usuarios->perPage() + 1;
                        @endphp
                        @forelse ($usuarios as $usuario)
                            <tr>
                                <td class="ga-muted">{{ $nro++ }}</td>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="ga-avatar ga-avatar-sm">{{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($usuario->name, 0, 1)) }}</span>
                                        <strong class="small">{{ $usuario->name }}</strong>
                                    </div>
                                </td>
                                <td>
                                    @forelse ($usuario->roles as $rol)
                                        <span class="badge bg-primary">{{ $rol->name }}</span>
                                    @empty
                                        <span class="badge bg-secondary">Sin rol</span>
                                    @endforelse
                                </td>
                                <td><small class="ga-muted">{{ $usuario->email }}</small></td>
                                <td>
                                    @if ($usuario->estado == 0)
                                        <span class="badge bg-danger">Inactivo</span>
                                    @else
                                        <span class="badge bg-success">Activo</span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    @if ($usuario->estado == 1)
                                        <div class="btn-group btn-group-sm" role="group" aria-label="Acciones del usuario">
                                            <a href="{{ url('/admin/usuario/' . $usuario->id) }}" class="btn btn-outline-secondary" title="Ver usuario">
                                                <i class="bi bi-eye" aria-hidden="true"></i>
                                                <span class="visually-hidden">Ver</span>
                                            </a>
                                            <a href="{{ url('/admin/usuario/' . $usuario->id . '/edit') }}" class="btn btn-outline-primary" title="Editar usuario">
                                                <i class="bi bi-pencil" aria-hidden="true"></i>
                                                <span class="visually-hidden">Editar</span>
                                            </a>
                                            <button type="submit" class="btn btn-outline-danger" title="Eliminar usuario"
                                                onclick="preguntar{{ $usuario->id }}(event)">
                                                <i class="bi bi-trash" aria-hidden="true"></i>
                                                <span class="visually-hidden">Eliminar</span>
                                            </button>
                                        </div>

                                        <form action="{{ url('/admin/usuario/' . $usuario->id) }}" method="post"
                                            id="miFormulario{{ $usuario->id }}" class="d-inline">
                                            @csrf
                                            @method('DELETE')
                                        </form>
                                        <script>
                                            function preguntar{{ $usuario->id }}(event) {
                                                event.preventDefault();
                                                Swal.fire({
                                                    title: '¿Desea eliminar este registro?',
                                                    text: '',
                                                    icon: 'question',
                                                    showDenyButton: true,
                                                    confirmButtonText: 'Eliminar',
                                                    confirmButtonColor: '#a5161d',
                                                    denyButtonColor: '#270a0a',
                                                    denyButtonText: 'Cancelar',
                                                }).then((result) => {
                                                    if (result.isConfirmed) {
                                                        document.getElementById('miFormulario{{ $usuario->id }}').submit();
                                                    }
                                                });
                                            }
                                        </script>
                                    @else
                                        <form action="{{ url('/admin/usuario/' . $usuario->id. '/restaurar') }}" method="post"
                                            id="miFormulario{{ $usuario->id }}" class="d-inline">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-outline-warning"
                                                onclick="preguntar{{ $usuario->id }}(event)">
                                                <i class="bi bi-arrow-counterclockwise"></i> Restaurar
                                            </button>
                                        </form>
                                        <script>
                                            function preguntar{{ $usuario->id }}(event) {
                                                event.preventDefault();
                                                Swal.fire({
                                                    title: '¿Desea restaurar este registro?',
                                                    text: '',
                                                    icon: 'question',
                                                    showDenyButton: true,
                                                    confirmButtonText: 'Restaurar',
                                                    confirmButtonColor: '#ffc107',
                                                    denyButtonColor: '#270a0a',
                                                    denyButtonText: 'Cancelar',
                                                }).then((result) => {
                                                    if (result.isConfirmed) {
                                                        document.getElementById('miFormulario{{ $usuario->id }}').submit();
                                                    }
                                                });
                                            }
                                        </script>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6">
                                    <div class="ga-empty">
                                        <span class="ga-empty-icon"><i class="bi bi-people" aria-hidden="true"></i></span>
                                        <strong>{{ isset($_REQUEST['buscar']) ? 'No encontramos usuarios con ese criterio' : 'Todavía no hay usuarios' }}</strong>
                                        <p>{{ isset($_REQUEST['buscar']) ? 'Prueba con otro nombre o correo.' : 'Crea el primer usuario para dar acceso al panel.' }}</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($usuarios->hasPages())
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mt-4 pt-3 border-top" style="border-color: var(--ga-line) !important;">
                    <div class="ga-muted small">
                        <i class="bi bi-list-ol" aria-hidden="true"></i>
                        Mostrando {{ $usuarios->firstItem() }} a {{ $usuarios->lastItem() }} de
                        {{ $usuarios->total() }} registros
                    </div>
                    <div>
                        {{ $usuarios->links('pagination::bootstrap-4') }}
                    </div>
                </div>
            @endif
        </div>
    </div>
@endsection
