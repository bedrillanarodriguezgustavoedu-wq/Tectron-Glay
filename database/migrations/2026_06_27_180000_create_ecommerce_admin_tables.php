<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Add activo to marcas table
        Schema::table('marcas', function (Blueprint $table) {
            if (!Schema::hasColumn('marcas', 'activo')) {
                $table->boolean('activo')->default(true)->after('imagen');
            }
        });

        // 2. Add flags to productos table
        Schema::table('productos', function (Blueprint $table) {
            if (!Schema::hasColumn('productos', 'destacado')) {
                $table->boolean('destacado')->default(false)->after('descuento');
            }
            if (!Schema::hasColumn('productos', 'nuevo')) {
                $table->boolean('nuevo')->default(false)->after('destacado');
            }
            if (!Schema::hasColumn('productos', 'recomendado')) {
                $table->boolean('recomendado')->default(false)->after('nuevo');
            }
            if (!Schema::hasColumn('productos', 'mas_vendido')) {
                $table->boolean('mas_vendido')->default(false)->after('recomendado');
            }
            if (!Schema::hasColumn('productos', 'en_oferta')) {
                $table->boolean('en_oferta')->default(false)->after('mas_vendido');
            }
        });

        // 3. Create ofertas table
        if (!Schema::hasTable('ofertas')) {
            Schema::create('ofertas', function (Blueprint $table) {
                $table->id();
                $table->string('nombre');
                $table->string('tipo_descuento'); // 'porcentaje' or 'monto'
                $table->decimal('valor_descuento', 10, 2);
                $table->dateTime('fecha_inicio');
                $table->dateTime('fecha_fin');
                $table->boolean('activo')->default(true);
                $table->timestamps();
            });
        }

        // 4. Create oferta_producto pivot table
        if (!Schema::hasTable('oferta_producto')) {
            Schema::create('oferta_producto', function (Blueprint $table) {
                $table->id();
                $table->foreignId('oferta_id')->constrained('ofertas')->onDelete('cascade');
                $table->foreignId('producto_id')->constrained('productos')->onDelete('cascade');
                $table->timestamps();
            });
        }

        // 5. Create banners table
        if (!Schema::hasTable('banners')) {
            Schema::create('banners', function (Blueprint $table) {
                $table->id();
                $table->string('imagen');
                $table->string('titulo')->nullable();
                $table->string('subtitulo')->nullable();
                $table->string('boton_texto')->nullable();
                $table->string('enlace')->nullable();
                $table->boolean('activo')->default(true);
                $table->integer('orden')->default(0);
                $table->dateTime('fecha_publicacion')->nullable();
                $table->timestamps();
            });
        }

        // 6. Create carruseles table
        if (!Schema::hasTable('carruseles')) {
            Schema::create('carruseles', function (Blueprint $table) {
                $table->id();
                $table->string('imagen');
                $table->string('titulo')->nullable();
                $table->string('subtitulo')->nullable();
                $table->string('boton_texto')->nullable();
                $table->string('enlace')->nullable();
                $table->boolean('activo')->default(true);
                $table->integer('orden')->default(0);
                $table->timestamps();
            });
        }

        // 7. Create secciones_home table
        if (!Schema::hasTable('secciones_home')) {
            Schema::create('secciones_home', function (Blueprint $table) {
                $table->id();
                $table->string('clave')->unique();
                $table->string('titulo');
                $table->boolean('activo')->default(true);
                $table->integer('orden')->default(0);
                $table->integer('limite')->default(8);
                $table->timestamps();
            });
        }

        // 8. Create and assign Spatie permissions
        $permissions = [
            'Listado de Marcas',
            'Crear Marca',
            'Guardar Marca',
            'Ver detalle de Marca',
            'Editar Marca',
            'Actualizar Marca',
            'Eliminar Marca',
            'Listado de Ofertas',
            'Crear Oferta',
            'Guardar Oferta',
            'Ver detalle de Oferta',
            'Editar Oferta',
            'Actualizar Oferta',
            'Eliminar Oferta',
            'Listado de Banners',
            'Crear Banner',
            'Guardar Banner',
            'Ver detalle de Banner',
            'Editar Banner',
            'Actualizar Banner',
            'Eliminar Banner',
            'Listado de Carruseles',
            'Crear Carrusel',
            'Guardar Carrusel',
            'Ver detalle de Carrusel',
            'Editar Carrusel',
            'Actualizar Carrusel',
            'Eliminar Carrusel',
            'Listado de Secciones Home',
            'Editar Sección Home',
            'Actualizar Sección Home'
        ];

        foreach ($permissions as $name) {
            Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
        }

        $superAdmin = Role::where('name', 'SUPER ADMIN')->first();
        if ($superAdmin) {
            $superAdmin->givePermissionTo($permissions);
        }

        $admin = Role::where('name', 'ADMINISTRADOR')->first();
        if ($admin) {
            $admin->givePermissionTo($permissions);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove permissions
        $permissions = [
            'Listado de Marcas',
            'Crear Marca',
            'Guardar Marca',
            'Ver detalle de Marca',
            'Editar Marca',
            'Actualizar Marca',
            'Eliminar Marca',
            'Listado de Ofertas',
            'Crear Oferta',
            'Guardar Oferta',
            'Ver detalle de Oferta',
            'Editar Oferta',
            'Actualizar Oferta',
            'Eliminar Oferta',
            'Listado de Banners',
            'Crear Banner',
            'Guardar Banner',
            'Ver detalle de Banner',
            'Editar Banner',
            'Actualizar Banner',
            'Eliminar Banner',
            'Listado de Carruseles',
            'Crear Carrusel',
            'Guardar Carrusel',
            'Ver detalle de Carrusel',
            'Editar Carrusel',
            'Actualizar Carrusel',
            'Eliminar Carrusel',
            'Listado de Secciones Home',
            'Editar Sección Home',
            'Actualizar Sección Home'
        ];

        foreach ($permissions as $name) {
            $permission = Permission::where('name', $name)->first();
            if ($permission) {
                $permission->delete();
            }
        }

        Schema::dropIfExists('secciones_home');
        Schema::dropIfExists('carruseles');
        Schema::dropIfExists('banners');
        Schema::dropIfExists('oferta_producto');
        Schema::dropIfExists('ofertas');

        Schema::table('productos', function (Blueprint $table) {
            $table->dropColumn(['destacado', 'nuevo', 'recomendado', 'mas_vendido', 'en_oferta']);
        });

        Schema::table('marcas', function (Blueprint $table) {
            $table->dropColumn(['activo']);
        });
    }
};
