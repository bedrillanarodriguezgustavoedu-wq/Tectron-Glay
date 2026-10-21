@php
    $punto = $punto ?? null;
    $departamentos = $departamentos ?? collect();
    $provinciaSeleccionada = (int) old('provincia_id', $punto?->provincia_id);
@endphp

<div class="form-group mb-3">
    <label for="nombre" class="form-label fw-bold">Nombre del punto (*)</label>
    <div class="input-group">
        <span class="input-group-text"><i class="bi bi-shop"></i></span>
        <input type="text" name="nombre" id="nombre" class="form-control"
            value="{{ old('nombre', $punto?->nombre) }}" maxlength="100"
            placeholder="GALY Miraflores" required>
    </div>
    <small class="text-muted">Es lo que el cliente lee para decidir dónde recoger.</small>
    @error('nombre')
        <small class="text-danger d-block">{{ $message }}</small>
    @enderror
</div>

<div class="form-group mb-3">
    <label for="direccion" class="form-label fw-bold">Dirección (*)</label>
    <input type="text" name="direccion" id="direccion" class="form-control"
        value="{{ old('direccion', $punto?->direccion) }}" maxlength="255"
        placeholder="Calle Las Palmeras 245, Miraflores" required>
    @error('direccion')
        <small class="text-danger d-block">{{ $message }}</small>
    @enderror
</div>

<div class="form-group mb-3">
    <label for="horario" class="form-label fw-bold">Horario de recojo</label>
    <input type="text" name="horario" id="horario" class="form-control"
        value="{{ old('horario', $punto?->horario) }}" maxlength="100"
        placeholder="Lun a Sáb, 10:00 a 19:00">
    @error('horario')
        <small class="text-danger d-block">{{ $message }}</small>
    @enderror
</div>

<div class="form-group mb-3">
    <label for="telefono" class="form-label fw-bold">Teléfono del local</label>
    <div class="input-group">
        <span class="input-group-text"><i class="bi bi-telephone"></i></span>
        <input type="text" name="telefono" id="telefono" class="form-control"
            value="{{ old('telefono', $punto?->telefono) }}" maxlength="30" placeholder="987654321">
    </div>
    <small class="text-muted">Si lo dejas vacío, el carrito usa el teléfono general de la tienda.</small>
    @error('telefono')
        <small class="text-danger d-block">{{ $message }}</small>
    @enderror
</div>

<div class="form-group mb-3">
    <label for="provincia_id" class="form-label fw-bold">Agencia en una provincia</label>
    <select name="provincia_id" id="provincia_id" class="form-select">
        <option value="">— No es agencia: es punto de «Recojo en tienda» —</option>
        @foreach ($departamentos as $departamento)
            @if ($departamento->provincias->isNotEmpty())
                <optgroup label="{{ $departamento->nombre }}">
                    @foreach ($departamento->provincias as $provincia)
                        <option value="{{ $provincia->id }}" @selected($provinciaSeleccionada === $provincia->id)>
                            {{ $provincia->nombre }}
                        </option>
                    @endforeach
                </optgroup>
            @endif
        @endforeach
    </select>
    <small class="text-muted">
        Si eliges una provincia, este local se ofrecerá al cliente cuando marque <strong>Envío a provincias</strong>
        y su dirección esté en esa provincia. Las provincias se crean en Admin → Provincias.
    </small>
    @error('provincia_id')
        <small class="text-danger d-block">{{ $message }}</small>
    @enderror
</div>

<div class="form-group mb-3">
    <label for="orden" class="form-label fw-bold">Orden de aparición</label>
    <input type="number" name="orden" id="orden" class="form-control" style="max-width: 140px;"
        value="{{ old('orden', $punto?->orden ?? 0) }}" min="0" max="999">
    <small class="text-muted">El número más bajo aparece primero en el carrito.</small>
    @error('orden')
        <small class="text-danger d-block">{{ $message }}</small>
    @enderror
</div>

<div class="form-group mb-4">
    <div class="form-check form-switch mt-2">
        <input class="form-check-input" type="checkbox" name="activo" id="activo" value="1"
            @checked(old('activo', $punto?->activo ?? true))>
        <label class="form-check-label fw-bold" for="activo">Ofrecido en el carrito</label>
    </div>
</div>
