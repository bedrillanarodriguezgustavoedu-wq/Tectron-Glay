@extends('layouts.web')

@section('content')
    @include('partials.catalogo', [
        'titulo' => 'Resultados para "' . ($query ?? '') . '"',
        'descripcion' => 'Encuentra los componentes y equipos que estás buscando.',
        'eyebrow' => 'Búsqueda',
        'icono' => 'bi-search',
    ])
@endsection
