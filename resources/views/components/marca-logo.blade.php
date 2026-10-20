@props([
    'marca',
    'width' => '100px',
    'height' => '50px',
    'class' => '',
])

@php($imageUrl = $marca->image_url)

<span class="d-inline-flex align-items-center justify-content-center overflow-hidden {{ $class }}"
    style="width: {{ $width }}; height: {{ $height }}; flex-shrink: 0;">
    @if ($imageUrl)
        <img src="{{ $imageUrl }}" alt="Logo {{ $marca->nombre }}"
            style="max-width: 100%; max-height: 100%; object-fit: contain;"
            onerror="this.hidden = true; this.nextElementSibling.classList.replace('d-none', 'd-inline-flex');">
        <span class="d-none align-items-center justify-content-center w-100 h-100 rounded bg-light border text-secondary fw-bold"
            aria-label="Sin logotipo disponible para {{ $marca->nombre }}">
            {{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($marca->nombre, 0, 2)) }}
        </span>
    @else
        <span class="d-inline-flex align-items-center justify-content-center w-100 h-100 rounded bg-light border text-secondary fw-bold"
            aria-label="Sin logotipo disponible para {{ $marca->nombre }}">
            {{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($marca->nombre, 0, 2)) }}
        </span>
    @endif
</span>
