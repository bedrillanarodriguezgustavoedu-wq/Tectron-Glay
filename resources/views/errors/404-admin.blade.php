@extends('layouts.admin')

@section('content')

<style>
    .error-page{
        min-height: 85vh;
        display:flex;
        justify-content:center;
        align-items:center;
    }

    .error-container{
        text-align:center;
    }

    .error-number{
        font-size:150px;
        font-weight:900;
        color:#435ebe;
        line-height:1;
        text-shadow:0 10px 30px rgba(67,94,190,.25);
    }

    .astronaut{
        font-size:120px;
        display:inline-block;
        animation: float 4s ease-in-out infinite;
        margin:20px 0;
    }

    .planet{
        position:absolute;
        width:120px;
        height:120px;
        border-radius:50%;
        background:linear-gradient(135deg,#435ebe,#6f42c1);
        opacity:.1;
        top:15%;
        right:20%;
        animation: rotatePlanet 20s linear infinite;
    }

    .planet::before{
        content:'';
        position:absolute;
        width:150px;
        height:25px;
        border:4px solid #435ebe;
        border-radius:50%;
        top:45px;
        left:-15px;
        transform:rotate(-15deg);
    }

    @keyframes float{
        0%,100%{
            transform:translateY(0px) rotate(0deg);
        }
        50%{
            transform:translateY(-20px) rotate(-5deg);
        }
    }

    @keyframes rotatePlanet{
        from{
            transform:rotate(0deg);
        }
        to{
            transform:rotate(360deg);
        }
    }

    .stars{
        position:absolute;
        inset:0;
        overflow:hidden;
        pointer-events:none;
    }

    .stars span{
        position:absolute;
        width:4px;
        height:4px;
        background:#435ebe;
        border-radius:50%;
        opacity:.3;
        animation: twinkle 2s infinite alternate;
    }

    @keyframes twinkle{
        from{
            opacity:.2;
        }
        to{
            opacity:1;
        }
    }

    .btn-home{
        padding:12px 30px;
        border-radius:50px;
    }
</style>

<div class="error-page position-relative">

    <div class="planet"></div>

    <div class="stars">
        <span style="top:10%;left:20%"></span>
        <span style="top:25%;left:80%"></span>
        <span style="top:15%;left:60%"></span>
        <span style="top:70%;left:15%"></span>
        <span style="top:80%;left:75%"></span>
        <span style="top:60%;left:90%"></span>
        <span style="top:35%;left:10%"></span>
        <span style="top:55%;left:40%"></span>
    </div>

    <div class="error-container">

        <div class="error-number">
            404
        </div>

        <div class="astronaut">
            👨‍🚀
        </div>

        <h1 class="fw-bold">
            Página no encontrada
        </h1>

        <p class="text-muted fs-5">
            Parece que esta página se perdió en el espacio.
            La dirección puede ser incorrecta o el contenido ya no existe.
        </p>

        <a href="{{ url('/admin') }}" class="btn btn-primary btn-lg btn-home">
            <i class="bi bi-house-fill"></i>
            Volver al panel
        </a>

    </div>

</div>

@endsection

