<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>403 - Acceso Denegado</title>

    <style>
        *{
            margin:0;
            padding:0;
            box-sizing:border-box;
            font-family:'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body{
            background: linear-gradient(135deg,#0f172a,#1e293b);
            min-height:100vh;
            display:flex;
            justify-content:center;
            align-items:center;
            color:white;
            padding:20px;
        }

        .container{
            max-width:700px;
            width:100%;
            text-align:center;
            background:rgba(255,255,255,0.05);
            backdrop-filter: blur(15px);
            border:1px solid rgba(255,255,255,0.1);
            border-radius:20px;
            padding:50px;
            box-shadow:0 20px 50px rgba(0,0,0,.3);
        }

        .error-code{
            font-size:140px;
            font-weight:900;
            color:#ef4444;
            line-height:1;
            margin-bottom:10px;
        }

        .title{
            font-size:35px;
            margin-bottom:15px;
        }

        .description{
            color:#cbd5e1;
            font-size:18px;
            line-height:1.7;
            margin-bottom:35px;
        }

        .buttons{
            display:flex;
            justify-content:center;
            gap:15px;
            flex-wrap:wrap;
        }

        .btn{
            text-decoration:none;
            padding:14px 28px;
            border-radius:10px;
            font-weight:600;
            transition:.3s;
        }

        .btn-primary{
            background:#2563eb;
            color:white;
        }

        .btn-primary:hover{
            background:#1d4ed8;
            transform:translateY(-3px);
        }

        .btn-secondary{
            background:#475569;
            color:white;
        }

        .btn-secondary:hover{
            background:#334155;
            transform:translateY(-3px);
        }

        @media(max-width:768px){
            .error-code{
                font-size:100px;
            }

            .title{
                font-size:28px;
            }

            .description{
                font-size:16px;
            }

            .container{
                padding:35px 25px;
            }
        }
    </style>
</head>
<body>

    <div class="container">
        <div class="error-code">403</div>

        <h1 class="title">Acceso Denegado</h1>

        <p class="description">
            No tienes permisos suficientes para acceder a esta sección del sistema.
            Contacta con el administrador si consideras que esto es un error.
        </p>

        <div class="buttons">
            <a href="/admin" class="btn btn-primary">
                Volver al Dashboard
            </a>

            <a href="javascript:history.back()" class="btn btn-secondary">
                Regresar
            </a>
        </div>
    </div>

</body>
</html><?php /**PATH E:\ecommerce (4)\ecommerce\resources\views/errors/403.blade.php ENDPATH**/ ?>