@extends('layouts.web')

@section('meta_robots', 'noindex, follow')
@section('title', 'La sesión caducó')

@section('content')
    @include('errors.parcial', [
        'codigo' => '419',
        'titulo' => '¡ups! La página caducó',
        'texto' => 'Por seguridad, el formulario dejó de aceptar envíos después de un rato sin actividad. Vuelve a la tienda, revisa tu carrito e intenta el envío otra vez.',
    ])
@endsection
