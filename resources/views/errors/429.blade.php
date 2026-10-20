@extends('layouts.web')

@section('meta_robots', 'noindex, follow')
@section('title', 'Demasiados intentos')

@section('content')
    @include('errors.parcial', [
        'codigo' => '429',
        'titulo' => 'Demasiados intentos seguidos',
        'texto' => 'Hemos bloqueado temporalmente las peticiones desde tu conexión para proteger las cuentas. Espera unos minutos y vuelve a intentarlo; si necesitas ayuda, escríbenos.',
    ])
@endsection
