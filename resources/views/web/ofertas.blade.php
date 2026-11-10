@extends('layouts.web')

@section('content')
    @include('partials.catalogo', [
        'titulo' => 'Grandes ofertas',
        'descripcion' => 'Descuentos imperdibles en tecnología seleccionada.',
        'eyebrow' => 'Ahorra hoy',
        'icono' => 'bi-tags',
    ])
@endsection
