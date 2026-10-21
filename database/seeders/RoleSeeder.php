<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $super_admin = Role::firstOrCreate(['name' => 'SUPER ADMIN']);
        Role::firstOrCreate(['name' => 'ADMINISTRADOR']);
        Role::firstOrCreate(['name' => 'VENDEDOR']);
        Role::firstOrCreate(['name' => 'CLIENTE']);
        Role::firstOrCreate(['name' => 'INVITADO']);

        $permisos = [
            'Ver dashboard del Admin',
            'Ajustes del sistema',
            'Actualizar Ajustes del sistema',
            'Listado de Roles',
            'Crear Rol',
            'Guardar Rol',
            'Ver detalle de Rol',
            'Ver detalle de Permisos de rol',
            'Actualizar Permisos de rol',
            'Editar Rol',
            'Actualizar Rol',
            'Eliminar Rol',
            'Listado de Usuarios',
            'Crear Usuario',
            'Guardar Usuario',
            'Ver perfil',
            'Actualizar perfil',
            'Actualizar Password',
            'Ver detalle de Usuario',
            'Editar Usuario',
            'Actualizar Usuario',
            'Eliminar Usuario',
            'Restaurar Usuario',
            'Listado de Categorías',
            'Crear Categoría',
            'Guardar Categoría',
            'Ver detalle de Categoría',
            'Editar Categoría',
            'Actualizar Categoría',
            'Eliminar Categoría',
            'Listado de Productos',
            'Crear Producto',
            'Guardar Producto',
            'Ver detalle de Producto',
            'Gestionar imágenes de Producto',
            'Subir imágenes de Producto',
            'Eliminar imágenes de Producto',
            'Editar Producto',
            'Actualizar Producto',
            'Eliminar Producto',
            'Listado de Pedidos',
            'Crear Pedido',
            'Procesar Pedido',

            // Nuevos módulos administrativos
            'Listado de Marcas', 'Crear Marca', 'Guardar Marca', 'Ver detalle de Marca', 'Editar Marca', 'Actualizar Marca', 'Eliminar Marca',
            'Listado de Ofertas', 'Crear Oferta', 'Guardar Oferta', 'Ver detalle de Oferta', 'Editar Oferta', 'Actualizar Oferta', 'Eliminar Oferta',
            'Listado de Cupones', 'Crear Cupon', 'Guardar Cupon', 'Editar Cupon', 'Actualizar Cupon', 'Eliminar Cupon',
            'Listado de Asesores', 'Crear Asesor', 'Guardar Asesor', 'Editar Asesor', 'Actualizar Asesor', 'Eliminar Asesor',
            'Listado de Punto de Recojo', 'Crear Punto de Recojo', 'Guardar Punto de Recojo', 'Editar Punto de Recojo', 'Actualizar Punto de Recojo', 'Eliminar Punto de Recojo',
            'Listado de Banners', 'Crear Banner', 'Guardar Banner', 'Ver detalle de Banner', 'Editar Banner', 'Actualizar Banner', 'Eliminar Banner',
            'Listado de Carruseles', 'Crear Carrusel', 'Guardar Carrusel', 'Ver detalle de Carrusel', 'Editar Carrusel', 'Actualizar Carrusel', 'Eliminar Carrusel',
            'Listado de Secciones Home', 'Editar Sección Home', 'Actualizar Sección Home',
            'Ver Nosotros', 'Editar Nosotros', 'Ver Contacto', 'Editar Contacto', 'Listado de Mensajes', 'Ver Mensaje', 'Eliminar Mensaje',
            'Gestionar Apariencia', 'Gestionar Footer', 'Gestionar países de envío',
            'Gestionar zonas de envío',
        ];

        foreach ($permisos as $permiso) {
            Permission::firstOrCreate(['name' => $permiso])->syncRoles($super_admin);
        }
    }
}
