@extends('layouts.web')

@section('meta_robots', 'noindex, follow')
@section('title', 'Estamos de mantenimiento')

@section('content')
    @include('errors.parcial', [
        'codigo' => '503',
        'titulo' => 'La tienda está en mantenimiento',
        'texto' => 'Estamos ajustando algo y en unos minutos todo vuelve a estar disponible. Tus pedidos y pagos quedan guardados; no hace falta que repitas ninguno.',
    ])
@endsection
