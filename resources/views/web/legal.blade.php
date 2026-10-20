@extends('layouts.web')

@section('content')
    <section class="section py-5">
        <div class="container py-4">
            <div class="row justify-content-center">
                <div class="col-lg-10 col-md-12">
                    <div class="card border-0 shadow-sm rounded-4 p-5 bg-white">
                        <div class="legal-content text-dark fs-5" style="line-height: 1.8;">
                            {!! $contenido !!}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
