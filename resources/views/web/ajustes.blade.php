@extends('layouts.web')
@section("meta_robots", "noindex, follow")
@section('content')
    <!-- Page Title -->
    <div class="page-title light-background">
        <div class="container d-lg-flex justify-content-between align-items-center">
            <h1 class="mb-2 mb-lg-0">Ajustes de mi cuenta</h1>
            <nav class="breadcrumbs">
                <ol>
                    <li><a href="{{ url('/') }}">Principal</a></li>
                    <li class="current">Ajustes</li>
                </ol>
            </nav>
        </div>
    </div><!-- End Page Title -->

    <!-- Account Section -->
    <section id="account" class="account section">

        <div class="container" data-aos="fade-up" data-aos-delay="100">

            <!-- Mobile Menu Toggle -->
            <div class="mobile-menu d-lg-none mb-4">
                <button class="mobile-menu-toggle" type="button" data-bs-toggle="collapse" data-bs-target="#profileMenu">
                    <i class="bi bi-grid"></i>
                    <span>Menu</span>
                </button>
            </div>

            <div class="row g-4">
                <!-- Profile Menu -->
                <div class="col-lg-3">
                    <div class="profile-menu collapse d-lg-block" id="profileMenu">
                        <!-- User Info -->
                        <div class="user-info" data-aos="fade-right">
                            <div class="user-avatar">
                                <img src="{{ asset('storage/' . $ajuste->logo) }}" alt="Profile" loading="lazy">
                                <span class="status-badge"><i class="bi bi-shield-check"></i></span>
                            </div>
                            <h4>{{ Auth::user()->name }}</h4>
                            <div class="user-status">
                                <i class="bi bi-award"></i>
                                <span>{{ Auth::user()->roles()->pluck('name')->implode(',') }}</span>
                            </div>
                        </div>

                        <!-- Navigation Menu -->
                        <nav class="menu-nav">
                            <ul class="nav flex-column" role="tablist">
                                <li class="nav-item mb-2">
                                    <a class="nav-link active d-flex align-items-center gap-2" data-bs-toggle="tab" href="#settings-pane">
                                        <i class="bi bi-person-fill-gear fs-5"></i>
                                        <span>Mi Perfil</span>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link d-flex align-items-center gap-2" data-bs-toggle="tab" href="#addresses-pane">
                                        <i class="bi bi-geo-alt-fill fs-5"></i>
                                        <span>Mis Direcciones</span>
                                    </a>
                                </li>
                            </ul>
                        </nav>
                    </div>
                </div>

                <!-- Content Area -->
                <div class="col-lg-9">
                    <div class="content-area shadow-sm rounded-4 p-4 bg-white">
                        <div class="tab-content">
                            
                            <!-- Tab: Settings (Profile Info & Password) -->
                            <div class="tab-pane fade show active" id="settings-pane" role="tabpanel">
                                <div class="section-header mb-4">
                                    <h2 class="fw-bold mb-0">Ajustes de la cuenta</h2>
                                </div>

                                <div class="settings-content">
                                    <!-- Personal Information -->
                                    <div class="settings-section mb-4 p-3 bg-light rounded-3" data-aos="fade-up">
                                        <h4 class="fw-bold text-dark mb-3"><i class="bi bi-person-badge text-primary me-2"></i>Información personal</h4>
                                        <form class="settings-form" action="{{ url('/ajustes/informacion_personal') }}" method="POST">
                                            @csrf
                                            @method('PUT')
                                            <div class="row g-3">
                                                <div class="col-md-6">
                                                    <label for="firstName" class="form-label fw-semibold">Nombre de usuario</label>
                                                    <input type="text" name="name" class="form-control" id="firstName"
                                                        value="{{ Auth::user()->name }}" required>
                                                </div>

                                                <div class="col-md-6">
                                                    <label for="email" class="form-label fw-semibold">Email</label>
                                                    <input type="email" class="form-control" name="email" id="email"
                                                        value="{{ Auth::user()->email }}" required>
                                                </div>
                                            </div>

                                            <div class="form-buttons mt-3 text-end">
                                                <button type="submit" class="btn btn-primary px-4 rounded-pill">Guardar cambios</button>
                                            </div>
                                        </form>
                                    </div>

                                    <!-- Security Settings -->
                                    <div class="settings-section p-3 bg-light rounded-3" data-aos="fade-up" data-aos-delay="200">
                                        <h4 class="fw-bold text-dark mb-3"><i class="bi bi-shield-lock text-primary me-2"></i>Seguridad</h4>
                                        <form class="settings-form" action="{{ url('/ajustes/actualizar_password') }}" method="POST">
                                            @csrf
                                            @method('PUT')
                                            <div class="row g-3">
                                                <div class="col-md-12">
                                                    <label for="currentPassword" class="form-label fw-semibold">Contraseña actual</label>
                                                    <input type="password" class="form-control" name="current_password" id="currentPassword" required>
                                                    @error('current_password')
                                                        <div role="alert" class="mt-1">
                                                            <small style="color: red">{{ $message }}</small>
                                                        </div>
                                                    @enderror
                                                </div>

                                                <div class="col-md-6">
                                                    <label for="newPassword" class="form-label fw-semibold">Nueva contraseña</label>
                                                    <input type="password" class="form-control" name="password" id="newPassword" required>
                                                    @error('password')
                                                        <div role="alert" class="mt-1">
                                                            <small style="color: red">{{ $message }}</small>
                                                        </div>
                                                    @enderror
                                                </div>

                                                <div class="col-md-6">
                                                    <label for="confirmPassword" class="form-label fw-semibold">Confirmación de contraseña</label>
                                                    <input type="password" class="form-control" name="password_confirmation" id="confirmPassword" required>
                                                    @error('password_confirmation')
                                                        <div role="alert" class="mt-1">
                                                            <small style="color: red">{{ $message }}</small>
                                                        </div>
                                                    @enderror
                                                </div>
                                            </div>

                                            <div class="form-buttons mt-3 text-end">
                                                <button type="submit" class="btn btn-primary px-4 rounded-pill">Actualizar contraseña</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>

                            <!-- Tab: Address Book -->
                            <div class="tab-pane fade" id="addresses-pane" role="tabpanel">
                                <div class="d-flex justify-content-between align-items-center mb-4">
                                    <h2 class="fw-bold mb-0">Mis Direcciones</h2>
                                    <button type="button" class="btn btn-primary rounded-pill px-3 py-1.5" data-bs-toggle="modal" data-bs-target="#createAddressModal">
                                        <i class="bi bi-plus-circle-fill me-1"></i> Nueva Dirección
                                    </button>
                                </div>

                                @if ($direcciones->isEmpty())
                                    <div class="text-center py-5 bg-light rounded-4 border border-dashed">
                                        <i class="bi bi-geo-alt display-2 text-muted"></i>
                                        <h4 class="fw-bold text-dark mt-3">Aún no tienes direcciones registradas</h4>
                                        <p class="text-muted mb-3">Registra tus direcciones frecuentes para agilizar tus compras.</p>
                                        <button type="button" class="btn btn-primary rounded-pill px-4" data-bs-toggle="modal" data-bs-target="#createAddressModal">
                                            Añadir Dirección
                                        </button>
                                    </div>
                                @else
                                    <div class="row g-3">
                                        @foreach ($direcciones as $direccion)
                                            <div class="col-md-6">
                                                <div class="card h-100 border-2 shadow-sm rounded-3 {{ $direccion->es_predeterminada ? 'border-primary' : 'border-light' }}">
                                                    <div class="card-body">
                                                        <div class="d-flex justify-content-between align-items-start mb-2">
                                                            <h5 class="card-title fw-bold text-dark mb-0">
                                                                @if (strtolower($direccion->alias) == 'casa')
                                                                    <i class="bi bi-house-door-fill text-primary me-1"></i>
                                                                @elseif (strtolower($direccion->alias) == 'trabajo' || strtolower($direccion->alias) == 'oficina')
                                                                    <i class="bi bi-briefcase-fill text-primary me-1"></i>
                                                                @else
                                                                    <i class="bi bi-geo-alt-fill text-primary me-1"></i>
                                                                @endif
                                                                {{ $direccion->alias }}
                                                            </h5>
                                                            @if ($direccion->es_predeterminada)
                                                                <span class="badge bg-primary rounded-pill"><i class="bi bi-star-fill me-1"></i>Predeterminada</span>
                                                            @endif
                                                        </div>
                                                        <p class="card-text text-muted mb-1 fs-6">
                                                            <strong>Dirección:</strong> {{ $direccion->direccion_exacta }}
                                                        </p>
                                                        @if ($direccion->referencia)
                                                            <p class="card-text text-muted mb-1 fs-6">
                                                                <strong>Referencia:</strong> {{ $direccion->referencia }}
                                                            </p>
                                                        @endif
                                                        <p class="card-text text-muted mb-1 fs-6">
                                                            <strong>Ubicación:</strong> {{ $direccion->provincia->nombre }}, {{ $direccion->departamento->nombre }}, {{ $direccion->pais->nombre }}
                                                        </p>
                                                        @if ($direccion->codigo_postal)
                                                            <p class="card-text text-muted mb-1 fs-6">
                                                                <strong>C.P.:</strong> {{ $direccion->codigo_postal }}
                                                            </p>
                                                        @endif
                                                        @if ($direccion->telefono)
                                                            <p class="card-text text-muted mb-0 fs-6">
                                                                <strong>Telf:</strong> {{ $direccion->telefono }}
                                                            </p>
                                                        @endif
                                                    </div>
                                                    <div class="card-footer bg-transparent border-0 d-flex justify-content-between pb-3">
                                                        <div>
                                                            @if (!$direccion->es_predeterminada)
                                                                <form action="{{ route('web.direcciones.default', $direccion->id) }}" method="POST" class="d-inline">
                                                                    @csrf
                                                                    <button type="submit" class="btn btn-link btn-sm text-decoration-none p-0 text-secondary">
                                                                        <i class="bi bi-star"></i> Definir por defecto
                                                                    </button>
                                                                </form>
                                                            @endif
                                                        </div>
                                                        <div class="d-flex gap-2">
                                                            <!-- Edit Button -->
                                                            <button type="button" class="btn btn-outline-primary btn-sm rounded-pill px-2 py-0.5" data-bs-toggle="modal" data-bs-target="#editAddressModal{{ $direccion->id }}">
                                                                <i class="bi bi-pencil-fill"></i>
                                                            </button>

                                                            <!-- Delete Button -->
                                                            <form action="{{ route('web.direcciones.destroy', $direccion->id) }}" method="POST" id="deleteAddrForm{{ $direccion->id }}" class="d-inline">
                                                                @csrf
                                                                @method('DELETE')
                                                                <button type="button" class="btn btn-outline-danger btn-sm rounded-pill px-2 py-0.5" onclick="confirmAddressDelete({{ $direccion->id }})">
                                                                    <i class="bi bi-trash-fill"></i>
                                                                </button>
                                                            </form>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Modal: Edit Address -->
                                            <div class="modal fade" id="editAddressModal{{ $direccion->id }}" tabindex="-1" aria-hidden="true">
                                                <div class="modal-dialog">
                                                    <div class="modal-content text-start">
                                                        <div class="modal-header">
                                                            <h5 class="modal-title fw-bold">Editar Dirección</h5>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                        </div>
                                                        <form action="{{ route('web.direcciones.update', $direccion->id) }}" method="POST">
                                                            @csrf
                                                            @method('PUT')
                                                            <div class="modal-body">
                                                                <div class="row g-3">
                                                                    <div class="col-12">
                                                                        <label for="alias{{ $direccion->id }}" class="form-label fw-bold">Alias (ej: Casa, Oficina)</label>
                                                                        <input type="text" name="alias" id="alias{{ $direccion->id }}" class="form-control" value="{{ $direccion->alias }}" required>
                                                                    </div>
                                                                    <div class="col-12">
                                                                        <label for="pais_id{{ $direccion->id }}" class="form-label fw-bold">País</label>
                                                                        <select name="pais_id" id="pais_id{{ $direccion->id }}" class="form-select" onchange="loadDepartmentsEdit({{ $direccion->id }})" required>
                                                                            @foreach ($paises as $p)
                                                                                <option value="{{ $p->id }}" {{ $direccion->pais_id == $p->id ? 'selected' : '' }}>{{ $p->nombre }}</option>
                                                                            @endforeach
                                                                        </select>
                                                                    </div>
                                                                    
                                                                    @php
                                                                        $depts = \App\Models\Departamento::where('pais_id', $direccion->pais_id)->where('activo', true)->get();
                                                                        $provs = \App\Models\Provincia::where('departamento_id', $direccion->departamento_id)->where('activo', true)->get();
                                                                    @endphp

                                                                    <div class="col-md-6">
                                                                        <label for="departamento_id{{ $direccion->id }}" class="form-label fw-bold">Departamento</label>
                                                                        <select name="departamento_id" id="departamento_id{{ $direccion->id }}" class="form-select" onchange="loadProvinciasEdit({{ $direccion->id }})" required>
                                                                            @foreach ($depts as $d)
                                                                                <option value="{{ $d->id }}" {{ $direccion->departamento_id == $d->id ? 'selected' : '' }}>{{ $d->nombre }}</option>
                                                                            @endforeach
                                                                        </select>
                                                                    </div>
                                                                    <div class="col-md-6">
                                                                        <label for="provincia_id{{ $direccion->id }}" class="form-label fw-bold">Provincia</label>
                                                                        <select name="provincia_id" id="provincia_id{{ $direccion->id }}" class="form-select" required>
                                                                            @foreach ($provs as $pr)
                                                                                <option value="{{ $pr->id }}" {{ $direccion->provincia_id == $pr->id ? 'selected' : '' }}>{{ $pr->nombre }}</option>
                                                                            @endforeach
                                                                        </select>
                                                                    </div>
                                                                    <div class="col-12">
                                                                        <label for="direccion_exacta{{ $direccion->id }}" class="form-label fw-bold">Dirección exacta (Calle, Avenida, Mz, Lote)</label>
                                                                        <input type="text" name="direccion_exacta" id="direccion_exacta{{ $direccion->id }}" class="form-control" value="{{ $direccion->direccion_exacta }}" required>
                                                                    </div>
                                                                    <div class="col-12">
                                                                        <label for="referencia{{ $direccion->id }}" class="form-label fw-bold">Referencia (opcional)</label>
                                                                        <input type="text" name="referencia" id="referencia{{ $direccion->id }}" class="form-control" value="{{ $direccion->referencia }}">
                                                                    </div>
                                                                    <div class="col-md-6">
                                                                        <label for="codigo_postal{{ $direccion->id }}" class="form-label fw-bold">Código Postal (opcional)</label>
                                                                        <input type="text" name="codigo_postal" id="codigo_postal{{ $direccion->id }}" class="form-control" value="{{ $direccion->codigo_postal }}">
                                                                    </div>
                                                                    <div class="col-md-6">
                                                                        <label for="telefono{{ $direccion->id }}" class="form-label fw-bold">Teléfono de contacto (opcional)</label>
                                                                        <input type="text" name="telefono" id="telefono{{ $direccion->id }}" class="form-control" value="{{ $direccion->telefono }}">
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            <div class="modal-footer">
                                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                                                                <button type="submit" class="btn btn-primary">Actualizar Dirección</button>
                                                            </div>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                @endif
                            </div>

                        </div>
                    </div>
                </div>
            </div>

        </div>

    </section><!-- /Account Section -->

    <!-- Modal: Create Address -->
    <div class="modal fade" id="createAddressModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content text-start">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">Registrar Nueva Dirección</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="{{ route('web.direcciones.store') }}" method="POST">
                    @csrf
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-12">
                                <label for="alias_new" class="form-label fw-bold">Alias (ej: Casa, Trabajo, Oficina)</label>
                                <input type="text" name="alias" id="alias_new" class="form-control" placeholder="ej: Casa, Trabajo" required>
                            </div>
                            <div class="col-12">
                                <label for="pais_id_new" class="form-label fw-bold">País</label>
                                <select name="pais_id" id="pais_id_new" class="form-select" onchange="loadDepartmentsNew()" required>
                                    <option value="">-- Seleccionar país --</option>
                                    @foreach ($paises as $p)
                                        <option value="{{ $p->id }}">{{ $p->nombre }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="departamento_id_new" class="form-label fw-bold">Departamento</label>
                                <select name="departamento_id" id="departamento_id_new" class="form-select" onchange="loadProvinciasNew()" required disabled>
                                    <option value="">-- Primero elija país --</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="provincia_id_new" class="form-label fw-bold">Provincia</label>
                                <select name="provincia_id" id="provincia_id_new" class="form-select" required disabled>
                                    <option value="">-- Primero elija depto --</option>
                                </select>
                            </div>
                            <div class="col-12">
                                <label for="direccion_exacta_new" class="form-label fw-bold">Dirección exacta (Calle, Avenida, Mz, Lote)</label>
                                <input type="text" name="direccion_exacta" id="direccion_exacta_new" class="form-control" placeholder="ej: Av. Larco 123, dpto 401" required>
                            </div>
                            <div class="col-12">
                                <label for="referencia_new" class="form-label fw-bold">Referencia (opcional)</label>
                                <input type="text" name="referencia" id="referencia_new" class="form-control" placeholder="ej: A una cuadra del parque central">
                            </div>
                            <div class="col-md-6">
                                <label for="codigo_postal_new" class="form-label fw-bold">Código Postal (opcional)</label>
                                <input type="text" name="codigo_postal" id="codigo_postal_new" class="form-control" placeholder="ej: 15074">
                            </div>
                            <div class="col-md-6">
                                <label for="telefono_new" class="form-label fw-bold">Teléfono de contacto (opcional)</label>
                                <input type="text" name="telefono" id="telefono_new" class="form-control" placeholder="ej: 987654321">
                            </div>
                            <div class="col-12">
                                <div class="form-check form-switch mt-2">
                                    <input class="form-check-input" type="checkbox" name="es_predeterminada" value="1" id="es_predeterminada_new">
                                    <label class="form-check-label fw-semibold text-muted" for="es_predeterminada_new">Definir como dirección predeterminada</label>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Registrar Dirección</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- JavaScript dynamic load functions -->
    <script>
        function loadDepartmentsNew() {
            const paisId = document.getElementById('pais_id_new').value;
            const deptSelect = document.getElementById('departamento_id_new');
            const provSelect = document.getElementById('provincia_id_new');

            deptSelect.innerHTML = '<option value="">Cargando...</option>';
            deptSelect.disabled = true;
            provSelect.innerHTML = '<option value="">-- Primero elija depto --</option>';
            provSelect.disabled = true;

            if (!paisId) {
                deptSelect.innerHTML = '<option value="">-- Primero elija país --</option>';
                return;
            }

            fetch(`{{ url('/api/paises') }}/${paisId}/departamentos`)
                .then(response => response.json())
                .then(data => {
                    deptSelect.innerHTML = '<option value="">-- Seleccionar departamento --</option>';
                    data.forEach(dept => {
                        const option = document.createElement('option');
                        option.value = dept.id;
                        option.textContent = dept.nombre;
                        deptSelect.appendChild(option);
                    });
                    deptSelect.disabled = false;
                })
                .catch(error => {
                    console.error('Error:', error);
                    deptSelect.innerHTML = '<option value="">Error al cargar</option>';
                });
        }

        function loadProvinciasNew() {
            const deptId = document.getElementById('departamento_id_new').value;
            const provSelect = document.getElementById('provincia_id_new');

            provSelect.innerHTML = '<option value="">Cargando...</option>';
            provSelect.disabled = true;

            if (!deptId) {
                provSelect.innerHTML = '<option value="">-- Primero elija depto --</option>';
                return;
            }

            fetch(`{{ url('/api/departamentos') }}/${deptId}/provincias`)
                .then(response => response.json())
                .then(data => {
                    provSelect.innerHTML = '<option value="">-- Seleccionar provincia --</option>';
                    data.forEach(prov => {
                        const option = document.createElement('option');
                        option.value = prov.id;
                        option.textContent = prov.nombre;
                        provSelect.appendChild(option);
                    });
                    provSelect.disabled = false;
                })
                .catch(error => {
                    console.error('Error:', error);
                    provSelect.innerHTML = '<option value="">Error al cargar</option>';
                });
        }

        // Edit Modals Cascading
        function loadDepartmentsEdit(id) {
            const paisId = document.getElementById(`pais_id${id}`).value;
            const deptSelect = document.getElementById(`departamento_id${id}`);
            const provSelect = document.getElementById(`provincia_id${id}`);

            deptSelect.innerHTML = '<option value="">Cargando...</option>';
            deptSelect.disabled = true;
            provSelect.innerHTML = '<option value="">-- Primero elija depto --</option>';
            provSelect.disabled = true;

            fetch(`{{ url('/api/paises') }}/${paisId}/departamentos`)
                .then(response => response.json())
                .then(data => {
                    deptSelect.innerHTML = '<option value="">-- Seleccionar departamento --</option>';
                    data.forEach(dept => {
                        const option = document.createElement('option');
                        option.value = dept.id;
                        option.textContent = dept.nombre;
                        deptSelect.appendChild(option);
                    });
                    deptSelect.disabled = false;
                })
                .catch(error => {
                    console.error('Error:', error);
                });
        }

        function loadProvinciasEdit(id) {
            const deptId = document.getElementById(`departamento_id${id}`).value;
            const provSelect = document.getElementById(`provincia_id${id}`);

            provSelect.innerHTML = '<option value="">Cargando...</option>';
            provSelect.disabled = true;

            fetch(`{{ url('/api/departamentos') }}/${deptId}/provincias`)
                .then(response => response.json())
                .then(data => {
                    provSelect.innerHTML = '<option value="">-- Seleccionar provincia --</option>';
                    data.forEach(prov => {
                        const option = document.createElement('option');
                        option.value = prov.id;
                        option.textContent = prov.nombre;
                        provSelect.appendChild(option);
                    });
                    provSelect.disabled = false;
                })
                .catch(error => {
                    console.error('Error:', error);
                });
        }

        function confirmAddressDelete(id) {
            Swal.fire({
                title: '¿Desea eliminar esta dirección?',
                text: "Esta dirección se removerá de tu libreta de direcciones.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Sí, eliminar',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    document.getElementById('deleteAddrForm' + id).submit();
                }
            });
        }
    </script>
@endsection

