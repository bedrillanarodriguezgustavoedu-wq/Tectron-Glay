@extends('layouts.admin')

@section('content')
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <div>
            <h1 class="h3 mb-1">{{ $rol->name }}</h1>
            <p class="text-muted mb-0">Resumen del perfil y de los accesos asignados.</p>
        </div>
        <a href="{{ route('admin.roles.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1" aria-hidden="true"></i>Volver a roles
        </a>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-12 col-md-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="text-muted small">Usuarios con este rol</div>
                    <div class="display-6 fw-semibold">{{ $rol->users_count }}</div>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="text-muted small">Permisos asignados</div>
                    <div class="display-6 fw-semibold">{{ $rol->permissions_count }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white py-3">
            <h2 class="h5 mb-0">Permisos del rol</h2>
        </div>
        <div class="card-body">
            @forelse ($rol->permissions->sortBy('name')->groupBy(fn ($permission) => \Illuminate\Support\Str::before($permission->name, ' ')) as $prefix => $permissions)
                <div class="mb-3">
                    <h3 class="h6">{{ $prefix }}</h3>
                    <div class="d-flex flex-wrap gap-2">
                        @foreach ($permissions as $permission)
                            <span class="badge rounded-pill text-bg-light border">{{ $permission->name }}</span>
                        @endforeach
                    </div>
                </div>
            @empty
                <p class="text-muted mb-0">Este rol todavía no tiene permisos asignados.</p>
            @endforelse
        </div>
    </div>
@endsection
