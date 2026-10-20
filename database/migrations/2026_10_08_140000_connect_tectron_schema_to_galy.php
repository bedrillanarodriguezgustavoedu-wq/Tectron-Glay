<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'galy_user_id')) {
                $table->unsignedBigInteger('galy_user_id')->nullable()->unique();
            }
            if (! Schema::hasColumn('users', 'galy_es_admin')) {
                $table->boolean('galy_es_admin')->default(false);
            }
        });

        foreach ([
            'tectron_galy_product_map' => ['tectron_product_id', 'galy_product_id'],
            'tectron_galy_user_map' => ['tectron_user_id', 'galy_user_id'],
            'tectron_galy_category_map' => ['tectron_category_id', 'galy_category_id'],
            'tectron_galy_brand_map' => ['tectron_brand_id', 'galy_brand_id'],
            'tectron_galy_order_map' => ['tectron_order_id', 'galy_order_id'],
            'tectron_galy_offer_map' => ['tectron_offer_id', 'galy_offer_id'],
        ] as $mapTable => [$sourceId, $targetId]) {
            if (Schema::hasTable($mapTable)) {
                continue;
            }

            $uniqueTarget = in_array($mapTable, [
                'tectron_galy_product_map',
                'tectron_galy_user_map',
                'tectron_galy_order_map',
                'tectron_galy_offer_map',
            ], true);
            Schema::create($mapTable, function (Blueprint $table) use ($sourceId, $targetId, $uniqueTarget) {
                $table->unsignedBigInteger($sourceId)->primary();
                $table->unsignedBigInteger($targetId);
                if ($uniqueTarget) {
                    $table->unique($targetId);
                }
                $table->timestamps();
            });
        }

        if (app()->environment('testing')) {
            return;
        }

        $galy = Schema::connection('galy');

        $galy->table('usuarios', function (Blueprint $table) use ($galy) {
            if (! $galy->hasColumn('usuarios', 'email_verified_at')) {
                $table->timestamp('email_verified_at')->nullable();
            }
            if (! $galy->hasColumn('usuarios', 'remember_token')) {
                $table->rememberToken();
            }
            if (! $galy->hasColumn('usuarios', 'estado')) {
                $table->boolean('estado')->default(true);
            }
            if (! $galy->hasColumn('usuarios', 'created_at')) {
                $table->timestamp('created_at')->nullable();
            }
            if (! $galy->hasColumn('usuarios', 'updated_at')) {
                $table->timestamp('updated_at')->nullable();
            }
            if (! $galy->hasColumn('usuarios', 'deleted_at')) {
                $table->softDeletes();
            }
        });

        $galy->table('categorias', function (Blueprint $table) use ($galy) {
            if (! $galy->hasColumn('categorias', 'slug')) {
                $table->string('slug')->nullable()->unique();
            }
            if (! $galy->hasColumn('categorias', 'descripcion')) {
                $table->text('descripcion')->nullable();
            }
            if (! $galy->hasColumn('categorias', 'icono')) {
                $table->string('icono')->nullable();
            }
            if (! $galy->hasColumn('categorias', 'created_at')) {
                $table->timestamps();
            }
        });

        $galy->table('productos', function (Blueprint $table) use ($galy) {
            if (! $galy->hasColumn('productos', 'codigo')) {
                $table->string('codigo', 100)->nullable()->unique();
            }
            if (! $galy->hasColumn('productos', 'descripcion_corta')) {
                $table->string('descripcion_corta', 500)->nullable();
            }
            if (! $galy->hasColumn('productos', 'descripcion_larga')) {
                $table->longText('descripcion_larga')->nullable();
            }
            if (! $galy->hasColumn('productos', 'detalles_tecnicos')) {
                $table->longText('detalles_tecnicos')->nullable();
            }
            if (! $galy->hasColumn('productos', 'marca_id')) {
                $table->unsignedBigInteger('marca_id')->nullable()->index();
            }
            if (! $galy->hasColumn('productos', 'imagen_url')) {
                $table->string('imagen_url')->nullable();
            }
            if (! $galy->hasColumn('productos', 'precio_oferta_manual')) {
                $table->decimal('precio_oferta_manual', 10, 2)->nullable();
            }
            foreach (['destacado', 'nuevo', 'recomendado', 'mas_vendido', 'en_oferta'] as $flag) {
                if (! $galy->hasColumn('productos', $flag)) {
                    $table->boolean($flag)->default(false);
                }
            }
            if (! $galy->hasColumn('productos', 'created_at')) {
                $table->timestamps();
            }
            if (! $galy->hasColumn('productos', 'deleted_at')) {
                $table->softDeletes();
            }
        });

        if (! $galy->hasTable('marcas')) {
            $galy->create('marcas', function (Blueprint $table) {
                $table->id();
                $table->string('nombre');
                $table->string('slug')->unique();
                $table->string('imagen')->nullable();
                $table->boolean('activo')->default(true);
                $table->timestamps();
            });
        }

        if (! $galy->hasTable('producto_imagenes')) {
            $galy->create('producto_imagenes', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('producto_id')->index();
                $table->string('imagen');
                $table->timestamps();
            });
        }

        if (! $galy->hasTable('ofertas')) {
            $galy->create('ofertas', function (Blueprint $table) {
                $table->id();
                $table->string('nombre');
                $table->string('tipo_descuento');
                $table->decimal('valor_descuento', 10, 2);
                $table->dateTime('fecha_inicio');
                $table->dateTime('fecha_fin');
                $table->boolean('activo')->default(true);
                $table->timestamps();
            });
        }

        if (! $galy->hasTable('oferta_producto')) {
            $galy->create('oferta_producto', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('oferta_id')->index();
                $table->unsignedBigInteger('producto_id')->index();
                $table->timestamps();
                $table->unique(['oferta_id', 'producto_id']);
            });
        }

        $galy->table('pedidos', function (Blueprint $table) use ($galy) {
            if (! $galy->hasColumn('pedidos', 'divisa')) {
                $table->string('divisa', 10)->default('USD');
            }
            if (! $galy->hasColumn('pedidos', 'estado_pago')) {
                $table->string('estado_pago', 50)->default('Pendiente');
            }
            if (! $galy->hasColumn('pedidos', 'estado_orden')) {
                $table->string('estado_orden', 50)->default('En proceso');
            }
            if (! $galy->hasColumn('pedidos', 'transaccion_id')) {
                $table->string('transaccion_id')->nullable();
            }
            if (! $galy->hasColumn('pedidos', 'direccion_envio')) {
                $table->text('direccion_envio')->nullable();
            }
            if (! $galy->hasColumn('pedidos', 'modalidad_entrega')) {
                $table->string('modalidad_entrega', 30)->default('envio');
            }
            if (! $galy->hasColumn('pedidos', 'direccion_id')) {
                $table->unsignedBigInteger('direccion_id')->nullable();
            }
            if (! $galy->hasColumn('pedidos', 'nota')) {
                $table->text('nota')->nullable();
            }
            if (! $galy->hasColumn('pedidos', 'created_at')) {
                $table->timestamps();
            }
        });
        $galy->table('pedidos', function (Blueprint $table) use ($galy) {
            if (! $galy->hasColumn('pedidos', 'updated_at')) {
                $table->timestamp('updated_at')->nullable();
            }
        });

        $galy->table('pedido_productos', function (Blueprint $table) use ($galy) {
            if (! $galy->hasColumn('pedido_productos', 'created_at')) {
                $table->timestamps();
            }
        });

        foreach (['carrito', 'favoritos'] as $tableName) {
            $galy->table($tableName, function (Blueprint $table) use ($galy, $tableName) {
                if (! $galy->hasColumn($tableName, 'created_at')) {
                    $table->timestamp('created_at')->nullable();
                }
                if (! $galy->hasColumn($tableName, 'updated_at')) {
                    $table->timestamp('updated_at')->nullable();
                }
            });
        }

        if (! $galy->hasTable('resenas')) {
            $galy->create('resenas', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('producto_id')->index();
                $table->unsignedBigInteger('usuario_id')->index();
                $table->unsignedBigInteger('orden_id')->nullable()->index();
                $table->unsignedTinyInteger('estrellas');
                $table->string('titulo')->nullable();
                $table->text('comentario')->nullable();
                $table->boolean('aprobado')->default(false);
                $table->text('respuesta_admin')->nullable();
                $table->dateTime('fecha_respuesta')->nullable();
                $table->timestamps();
            });
        }

        if (! $galy->hasTable('resena_imagenes')) {
            $galy->create('resena_imagenes', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('resena_id')->index();
                $table->string('imagen');
                $table->unsignedInteger('orden')->default(0);
                $table->timestamps();
            });
        } elseif (! $galy->hasColumn('resena_imagenes', 'orden')) {
            $galy->table('resena_imagenes', function (Blueprint $table) {
                $table->unsignedInteger('orden')->default(0);
            });
        }

        if (! $galy->hasTable('pedido_historials')) {
            $galy->create('pedido_historials', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('orden_id')->index();
                $table->string('estado_anterior')->nullable();
                $table->string('estado_nuevo');
                $table->date('fecha')->nullable();
                $table->time('hora')->nullable();
                $table->text('nota')->nullable();
                $table->unsignedBigInteger('usuario_id')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('tectron_galy_brand_map');
        Schema::dropIfExists('tectron_galy_category_map');
        Schema::dropIfExists('tectron_galy_offer_map');
        Schema::dropIfExists('tectron_galy_order_map');
        Schema::dropIfExists('tectron_galy_user_map');
        Schema::dropIfExists('tectron_galy_product_map');
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['galy_user_id']);
            $table->dropColumn(['galy_user_id', 'galy_es_admin']);
        });

        // GALY schema and imported data are intentionally retained on rollback.
    }
};
