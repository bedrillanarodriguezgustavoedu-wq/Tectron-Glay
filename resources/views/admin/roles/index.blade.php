@extends('layouts.admin')

@section('title', 'Roles y permisos · ' . ($ajuste->nombre ?? 'TECTRON'))

@section('content')
    <div class="ga-page-head">
        <div class="ga-page-head-main">
            <div class="ga-eyebrow">Equipo y accesos</div>
            <h1 class="ga-page-title">Roles y permisos</h1>
            <p class="ga-page-sub">Define qué puede hacer cada perfil en el administrador web. Los permisos se agrupan por módulo y se aplican a todos los usuarios del rol.</p>
        </div>
        <div class="ga-head-actions">
            <a href="{{ route('admin.usuarios.index') }}" class="btn-ga-ghost">
                <i class="bi bi-people" aria-hidden="true"></i> Usuarios
            </a>
            @can('Crear Rol')
                <a href="{{ route('admin.roles.create') }}" class="btn btn-primary">
                    <i class="bi bi-plus-lg" aria-hidden="true"></i> Crear rol
                </a>
            @endcan
            <span class="ga-count-pill">
                <i class="bi bi-shield-check" aria-hidden="true"></i>
                <strong>{{ number_format($roles->total()) }}</strong> roles
            </span>
        </div>
    </div>

    @if (session('mensaje'))
        <div class="alert alert-{{ session('icono') === 'success' ? 'success' : 'info' }}" role="status">
            {{ session('mensaje') }}
        </div>
    @endif
    @if ($errors->has('role'))
        <div class="alert alert-warning" role="alert">{{ $errors->first('role') }}</div>
    @endif

    <div class="ga-surface ga-table-card">
        <div class="ga-card-head">
            <div>
                <h2>Perfiles registrados</h2>
                <p>Un rol agrupa permisos; el usuario hereda todo lo que permita su rol</p>
            </div>
            @if ($roles->hasPages())
                <span class="badge bg-secondary">
                    Página {{ $roles->currentPage() }} de {{ $roles->lastPage() }}
                </span>
            @endif
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>Rol</th>
                        <th class="text-center">Usuarios</th>
                        <th class="text-center">Permisos</th>
                        <th class="text-end">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($roles as $role)
                        <tr>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <span class="ga-avatar ga-avatar-sm"><i class="bi bi-shield-lock" aria-hidden="true"></i></span>
                                    <span>
                                        <strong class="d-block small">{{ $role->name }}</strong>
                                        @if ($role->name === 'SUPER ADMIN')
                                            <span class="badge bg-dark">Protegido</span>
                                        @else
                                            <small class="ga-muted">Rol personalizado</small>
                                        @endif
                                    </span>
                                </div>
                            </td>
                            <td class="text-center">
                                <span class="badge bg-secondary">{{ number_format($role->users_count) }}</span>
                            </td>
                            <td class="text-center">
                                <span class="badge bg-primary">{{ number_format($role->permissions_count) }}</span>
                            </td>
                            <td class="text-end">
                                <div class="d-inline-flex flex-wrap justify-content-end gap-1" role="group" aria-label="Acciones del rol {{ $role->name }}">
                                    @can('Ver detalle de Rol')
                                        <a href="{{ route('admin.roles.show', $role->id) }}" class="btn btn-outline-secondary btn-sm" title="Ver detalle">
                                            <i class="bi bi-eye" aria-hidden="true"></i>
                                            <span class="visually-hidden">Ver {{ $role->name }}</span>
                                        </a>
                                    @endcan
                                    @if ($role->name !== 'SUPER ADMIN')
                                        @can('Ver detalle de Permisos de rol')
                                            <a href="{{ route('admin.roles.permisos', $role->id) }}" class="btn btn-outline-primary btn-sm" title="Gestionar permisos">
                                                <i class="bi bi-shield-check" aria-hidden="true"></i>
                                                <span class="visually-hidden">Permisos de {{ $role->name }}</span>
                                            </a>
                                        @endcan
                                        @can('Editar Rol')
                                            <a href="{{ route('admin.roles.edit', $role->id) }}" class="btn btn-outline-success btn-sm" title="Editar rol">
                                                <i class="bi bi-pencil" aria-hidden="true"></i>
                                                <span class="visually-hidden">Editar {{ $role->name }}</span>
                                            </a>
                                        @endcan
                                        @can('Eliminar Rol')
                                            <form action="{{ route('admin.roles.destroy', $role->id) }}" method="POST"
                                                onsubmit="return confirm('¿Eliminar el rol {{ $role->name }}?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-outline-danger btn-sm"
                                                    aria-label="Eliminar {{ $role->name }}">
                                                    <i class="bi bi-trash" aria-hidden="true"></i>
                                                </button>
                                            </form>
                                        @endcan
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4">
                                <div class="ga-empty">
                                    <span class="ga-empty-icon"><i class="bi bi-shield-check" aria-hidden="true"></i></span>
                                    <strong>Todavía no hay roles registrados</strong>
                                    <p>Crea un rol para agrupar permisos y asignarlo a los usuarios del panel.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($roles->hasPages())
            <div class="card-body d-flex flex-wrap justify-content-between align-items-center gap-2 border-top" style="border-color: var(--ga-line) !important;">
                <span class="ga-muted small">
                    <i class="bi bi-list-ol" aria-hidden="true"></i>
                    Mostrando {{ $roles->firstItem() }}–{{ $roles->lastItem() }} de {{ $roles->total() }} roles
                </span>
                {{ $roles->links('pagination::bootstrap-4') }}
            </div>
        @endif
    </div>
@endsection
