@extends('layouts.web')

@section('meta_robots', 'noindex, follow')
@section('title', 'Algo salió mal')

@section('content')
    @include('errors.parcial', [
        'codigo' => '500',
        'titulo' => 'Algo salió mal de nuestro lado',
        'texto' => 'El problema quedó registrado y no necesitas repetir ningún pago: si llegaste a finalizar una transferencia o un cobro, tu pedido sigue guardado en tu cuenta. Espera unos minutos, recarga la página y vuelve a intentarlo.',
    ])
@endsection
