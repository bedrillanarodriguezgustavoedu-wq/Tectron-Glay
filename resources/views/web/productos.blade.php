@extends('layouts.web')

@section('content')
    @include('partials.catalogo', [
        'titulo' => isset($categoria) ? $categoria->nombre : 'Nuestros productos',
        'descripcion' => isset($categoria)
            ? 'Explora los productos de la categoría ' . $categoria->nombre
            : 'Explora todo nuestro catálogo tecnológico con las mejores marcas.',
        'eyebrow' => isset($categoria) ? 'Categoría' : 'Catálogo completo',
        'icono' => isset($categoria) ? ($categoria->icono ?? 'bi-tag') : 'bi-grid-1x2',
    ])
@endsection
