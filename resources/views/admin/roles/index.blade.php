@extends('layouts.admin')

@section('content')
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <div>
            <h1 class="h3 mb-1">Roles y permisos</h1>
            <p class="text-muted mb-0">Define qué puede hacer cada perfil en el administrador web.</p>
        </div>
        @can('Crear Rol')
            <a href="{{ route('admin.roles.create') }}" class="btn btn-primary">
                <i class="bi bi-plus-circle me-1" aria-hidden="true"></i>Crear rol
            </a>
        @endcan
    </div>

    @if (session('mensaje'))
        <div class="alert alert-{{ session('icono') === 'success' ? 'success' : 'info' }}" role="status">
            {{ session('mensaje') }}
        </div>
    @endif
    @if ($errors->has('role'))
        <div class="alert alert-warning" role="alert">{{ $errors->first('role') }}</div>
    @endif

    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white py-3">
            <h2 class="h5 mb-0">Perfiles registrados</h2>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th scope="col" class="ps-3">Rol</th>
                            <th scope="col">Usuarios</th>
                            <th scope="col">Permisos</th>
                            <th scope="col" class="text-end pe-3">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($roles as $role)
                            <tr>
                                <td class="ps-3">
                                    <span class="fw-semibold">{{ $role->name }}</span>
                                    @if ($role->name === 'SUPER ADMIN')
                                        <span class="badge text-bg-dark ms-2">Protegido</span>
                                    @endif
                                </td>
                                <td>{{ $role->users_count }}</td>
                                <td>{{ $role->permissions_count }}</td>
                                <td class="text-end pe-3">
                                    <div class="d-inline-flex flex-wrap justify-content-end gap-1">
                                        @can('Ver detalle de Rol')
                                            <a href="{{ route('admin.roles.show', $role->id) }}" class="btn btn-outline-secondary btn-sm">
                                                <i class="bi bi-eye" aria-hidden="true"></i><span class="visually-hidden">Ver {{ $role->name }}</span>
                                            </a>
                                        @endcan
                                        @if ($role->name !== 'SUPER ADMIN')
                                            @can('Ver detalle de Permisos de rol')
                                                <a href="{{ route('admin.roles.permisos', $role->id) }}" class="btn btn-outline-primary btn-sm">
                                                    <i class="bi bi-shield-check me-1" aria-hidden="true"></i>Permisos
                                                </a>
                                            @endcan
                                            @can('Editar Rol')
                                                <a href="{{ route('admin.roles.edit', $role->id) }}" class="btn btn-outline-success btn-sm">
                                                    <i class="bi bi-pencil" aria-hidden="true"></i><span class="visually-hidden">Editar {{ $role->name }}</span>
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
                                <td colspan="4" class="text-center text-muted py-5">Todavía no hay roles registrados.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if ($roles->hasPages())
            <div class="card-footer bg-white d-flex flex-wrap justify-content-between align-items-center gap-2">
                <span class="small text-muted">
                    Mostrando {{ $roles->firstItem() }}–{{ $roles->lastItem() }} de {{ $roles->total() }} roles
                </span>
                {{ $roles->links('pagination::bootstrap-4') }}
            </div>
        @endif
    </div>
@endsection
