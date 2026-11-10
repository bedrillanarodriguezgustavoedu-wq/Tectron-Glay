@props([
    'marca',
    'width' => '100px',
    'height' => '50px',
    'class' => '',
])

@php
    $imageUrl = $marca->image_url;
    $iniciales = \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr(trim($marca->nombre), 0, 2));
@endphp

<span class="marca-logo d-inline-flex align-items-center justify-content-center overflow-hidden {{ $class }}"
    style="width: {{ $width }}; height: {{ $height }}; flex-shrink: 0;">
    @if ($imageUrl)
        <img src="{{ $imageUrl }}" alt="Logo {{ $marca->nombre }}" loading="lazy" decoding="async"
            class="marca-logo-img"
            style="max-width: 100%; max-height: 100%; object-fit: contain;"
            onerror="this.hidden = true; this.nextElementSibling.classList.replace('d-none', 'd-inline-flex');">
        <span class="marca-logo-fallback d-none align-items-center justify-content-center w-100 h-100"
            aria-label="Sin logotipo disponible para {{ $marca->nombre }}">
            {{ $iniciales }}
        </span>
    @else
        <span class="marca-logo-fallback d-inline-flex align-items-center justify-content-center w-100 h-100"
            aria-label="Sin logotipo disponible para {{ $marca->nombre }}">
            {{ $iniciales }}
        </span>
    @endif
</span>
