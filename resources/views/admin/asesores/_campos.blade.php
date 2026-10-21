@php
    $modelo = $asesor ?? null;
@endphp

<div class="form-group mb-3">
    <label for="nombre" class="form-label fw-bold">Nombre y apellido (*)</label>
    <div class="input-group">
        <span class="input-group-text"><i class="bi bi-person"></i></span>
        <input type="text" name="nombre" id="nombre" class="form-control"
            value="{{ old('nombre', $modelo?->nombre) }}" maxlength="100" required>
    </div>
    @error('nombre')
        <small class="text-danger d-block">{{ $message }}</small>
    @enderror
</div>

<div class="form-group mb-3">
    <label for="cargo" class="form-label fw-bold">Cargo</label>
    <input type="text" name="cargo" id="cargo" class="form-control"
        value="{{ old('cargo', $modelo?->cargo) }}" maxlength="100" placeholder="Asesora de ventas online">
    @error('cargo')
        <small class="text-danger d-block">{{ $message }}</small>
    @enderror
</div>

<div class="form-group mb-3">
    <label for="whatsapp" class="form-label fw-bold">WhatsApp</label>
    <div class="input-group">
        <span class="input-group-text"><i class="bi bi-whatsapp"></i></span>
        <input type="text" name="whatsapp" id="whatsapp" class="form-control"
            value="{{ old('whatsapp', $modelo?->whatsapp) }}" maxlength="30" placeholder="51987654321">
    </div>
    <small class="text-muted">Con código de país y sin el signo +. Si queda vacío, la tarjeta no muestra botón de WhatsApp.</small>
    @if ($modelo)
        <small class="d-block text-muted mt-1">
            @if ($modelo->enlace_whatsapp)
                El botón abre: <code>{{ $modelo->enlace_whatsapp }}</code>
            @else
                Ahora mismo este asesor no muestra botón de WhatsApp.
            @endif
        </small>
    @endif
    @error('whatsapp')
        <small class="text-danger d-block">{{ $message }}</small>
    @enderror
</div>

<div class="form-group mb-3">
    <label for="email" class="form-label fw-bold">Correo</label>
    <div class="input-group">
        <span class="input-group-text"><i class="bi bi-envelope"></i></span>
        <input type="email" name="email" id="email" class="form-control"
            value="{{ old('email', $modelo?->email) }}" maxlength="150" placeholder="asesora@tectrongaly.com">
    </div>
    @error('email')
        <small class="text-danger d-block">{{ $message }}</small>
    @enderror
</div>

<div class="form-group mb-3">
    <label for="horario" class="form-label fw-bold">Horario de atención</label>
    <input type="text" name="horario" id="horario" class="form-control"
        value="{{ old('horario', $modelo?->horario) }}" maxlength="100" placeholder="Lun a Sáb, 9:00 a 19:00">
    @error('horario')
        <small class="text-danger d-block">{{ $message }}</small>
    @enderror
</div>

<div class="form-group mb-3">
    <label for="foto" class="form-label fw-bold">Fotografía</label>
    <input type="file" name="foto" id="foto" class="form-control" accept="image/*">
    @if ($modelo?->ruta_foto)
        <div class="mt-2">
            <img src="{{ $modelo->ruta_foto }}" alt="{{ $modelo->nombre }}" class="rounded-circle"
                style="width: 72px; height: 72px; object-fit: cover;">
        </div>
    @endif
    <small class="text-muted">Cuadrada, de buen contraste. Formatos: JPEG, PNG, JPG, WEBP. Máx. 2MB.</small>
    @error('foto')
        <small class="text-danger d-block">{{ $message }}</small>
    @enderror
</div>

<div class="form-group mb-3">
    <label for="orden" class="form-label fw-bold">Orden de aparición</label>
    <input type="number" name="orden" id="orden" class="form-control" style="max-width: 140px;"
        value="{{ old('orden', $modelo?->orden ?? 0) }}" min="0" max="999">
    <small class="text-muted">El número más bajo aparece primero.</small>
    @error('orden')
        <small class="text-danger d-block">{{ $message }}</small>
    @enderror
</div>

<div class="form-group mb-4">
    <div class="form-check form-switch mt-2">
        <input class="form-check-input" type="checkbox" name="activo" id="activo" value="1"
            @checked(old('activo', $modelo?->activo ?? true))>
        <label class="form-check-label fw-bold" for="activo">Visible en la tienda</label>
    </div>
</div>
