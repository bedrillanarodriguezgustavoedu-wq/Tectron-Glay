<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\AjusteController;
use App\Http\Controllers\AparienciaController;
use App\Http\Controllers\Auth\GoogleAuthController;
use App\Http\Controllers\BannerController;
use App\Http\Controllers\CarritoController;
use App\Http\Controllers\CarruselController;
use App\Http\Controllers\CategoriaController;
use App\Http\Controllers\ContactoController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DepartamentoController;
use App\Http\Controllers\DireccionController;
use App\Http\Controllers\FooterController;
use App\Http\Controllers\GalyAdminLinkController;
use App\Http\Controllers\MarcaController;
use App\Http\Controllers\MobileCheckoutController;
use App\Http\Controllers\MensajeContactoController;
use App\Http\Controllers\MercadoPagoController;
use App\Http\Controllers\NosotrosController;
use App\Http\Controllers\OfertaController;
use App\Http\Controllers\OrdenController;
use App\Http\Controllers\PaisController;
use App\Http\Controllers\ProductoController;
use App\Http\Controllers\ProductoFavoritoController;
use App\Http\Controllers\ProvinciaController;
use App\Http\Controllers\ReservaProductoController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\SeccionHomeController;
use App\Http\Controllers\SoporteTecnicoController;
use App\Http\Controllers\UsuarioController;
use App\Http\Controllers\WebController;
use Illuminate\Support\Facades\Route;

Auth::routes();
Route::prefix('api/mobile')->middleware('throttle:30,1')->group(function (): void {
    Route::get('/payment-methods', [MobileCheckoutController::class, 'paymentMethods'])
        ->name('api.mobile.payment-methods');
    Route::post('/payment-receipts', [MobileCheckoutController::class, 'sendPaymentReceipt'])
        ->name('api.mobile.payment-receipts')
        ->middleware('throttle:5,1');
});

Route::get('/auth/google/redirect', [GoogleAuthController::class, 'redirect'])
    ->name('auth.google.redirect')
    ->middleware('guest');
Route::get('/auth/google/link', [GoogleAuthController::class, 'link'])
    ->name('auth.google.link')
    ->middleware('auth');
Route::get('/auth/google/callback', [GoogleAuthController::class, 'callback'])
    ->name('auth.google.callback');

// Dashboard
Route::get('/home', [AdminController::class, 'index'])
    ->name('home')
    ->middleware(['auth', 'can:Ver dashboard del Admin']);

Route::get('/admin', [AdminController::class, 'index'])
    ->name('admin.index')
    ->middleware(['auth', 'can:Ver dashboard del Admin']);

Route::get('/admin/galy-panel', GalyAdminLinkController::class)
    ->name('admin.galy-panel')
    ->middleware(['auth', 'can:Ver dashboard del Admin']);

// Ajustes
Route::get('/admin/ajustes', [AjusteController::class, 'index'])
    ->name('admin.ajustes.index')
    ->middleware(['auth', 'can:Ajustes del sistema']);

Route::post('/admin/ajustes/create', [AjusteController::class, 'store'])
    ->name('admin.ajustes.store')
    ->middleware(['auth', 'can:Actualizar Ajustes del sistema']);

// Roles
Route::get('/admin/roles', [RoleController::class, 'index'])
    ->name('admin.roles.index')
    ->middleware(['auth', 'can:Listado de Roles']);

Route::get('/admin/roles/create', [RoleController::class, 'create'])
    ->name('admin.roles.create')
    ->middleware(['auth', 'can:Crear Rol']);

Route::post('/admin/roles/create', [RoleController::class, 'store'])
    ->name('admin.roles.store')
    ->middleware(['auth', 'can:Guardar Rol']);

Route::get('/admin/rol/{id}', [RoleController::class, 'show'])
    ->name('admin.roles.show')
    ->middleware(['auth', 'can:Ver detalle de Rol']);

Route::get('/admin/rol/{id}/edit', [RoleController::class, 'edit'])
    ->name('admin.roles.edit')
    ->middleware(['auth', 'can:Editar Rol']);

Route::get('/admin/rol/{id}/permisos', [RoleController::class, 'permisos'])
    ->name('admin.roles.permisos')
    ->middleware(['auth', 'can:Ver detalle de Permisos de rol']);

Route::put('/admin/rol/{id}/update_permisos', [RoleController::class, 'update_permisos'])
    ->name('admin.roles.update_permisos')
    ->middleware(['auth', 'can:Actualizar Permisos de rol']);

Route::put('/admin/rol/{id}', [RoleController::class, 'update'])
    ->name('admin.roles.update')
    ->middleware(['auth', 'can:Actualizar Rol']);

Route::delete('/admin/rol/{id}', [RoleController::class, 'destroy'])
    ->name('admin.roles.destroy')
    ->middleware(['auth', 'can:Eliminar Rol']);

// Usuarios
Route::get('/admin/usuarios', [UsuarioController::class, 'index'])
    ->name('admin.usuarios.index')
    ->middleware(['auth', 'can:Listado de Usuarios']);

Route::get('/admin/usuarios/create', [UsuarioController::class, 'create'])
    ->name('admin.usuarios.create')
    ->middleware(['auth', 'can:Crear Usuario']);

Route::get('/admin/usuario/{id}/perfil', [UsuarioController::class, 'perfil'])
    ->name('admin.usuarios.perfil')
    ->middleware(['auth', 'can:Ver perfil']);

Route::put('/admin/usuario/{id}/update_perfil', [UsuarioController::class, 'update_perfil'])
    ->name('admin.usuarios.update_perfil')
    ->middleware(['auth', 'can:Actualizar perfil']);

Route::put('/admin/usuario/{id}/update_password', [UsuarioController::class, 'update_password'])
    ->name('admin.usuarios.update_password')
    ->middleware(['auth', 'can:Actualizar Password']);

Route::post('/admin/usuarios/create', [UsuarioController::class, 'store'])
    ->name('admin.usuarios.store')
    ->middleware(['auth', 'can:Guardar Usuario']);

Route::get('/admin/usuario/{id}', [UsuarioController::class, 'show'])
    ->name('admin.usuarios.show')
    ->middleware(['auth', 'can:Ver detalle de Usuario']);

Route::get('/admin/usuario/{id}/edit', [UsuarioController::class, 'edit'])
    ->name('admin.usuarios.edit')
    ->middleware(['auth', 'can:Editar Usuario']);

Route::put('/admin/usuario/{id}', [UsuarioController::class, 'update'])
    ->name('admin.usuarios.update')
    ->middleware(['auth', 'can:Actualizar Usuario']);

Route::delete('/admin/usuario/{id}', [UsuarioController::class, 'destroy'])
    ->name('admin.usuarios.destroy')
    ->middleware(['auth', 'can:Eliminar Usuario']);

Route::post('/admin/usuario/{id}/restaurar', [UsuarioController::class, 'restore'])
    ->name('admin.usuarios.restore')
    ->middleware(['auth', 'can:Restaurar Usuario']);

// Categorías
Route::get('/admin/categorias', [CategoriaController::class, 'index'])
    ->name('admin.categorias.index')
    ->middleware(['auth', 'can:Listado de Categorías']);

Route::get('/admin/categorias/create', [CategoriaController::class, 'create'])
    ->name('admin.categorias.create')
    ->middleware(['auth', 'can:Crear Categoría']);

Route::post('/admin/categorias/create', [CategoriaController::class, 'store'])
    ->name('admin.categorias.store')
    ->middleware(['auth', 'can:Guardar Categoría']);

Route::get('/admin/categoria/{id}', [CategoriaController::class, 'show'])
    ->name('admin.categorias.show')
    ->middleware(['auth', 'can:Ver detalle de Categoría']);

Route::get('/admin/categoria/{id}/edit', [CategoriaController::class, 'edit'])
    ->name('admin.categorias.edit')
    ->middleware(['auth', 'can:Editar Categoría']);

Route::put('/admin/categoria/{id}', [CategoriaController::class, 'update'])
    ->name('admin.categorias.update')
    ->middleware(['auth', 'can:Actualizar Categoría']);

Route::delete('/admin/categoria/{id}', [CategoriaController::class, 'destroy'])
    ->name('admin.categorias.destroy')
    ->middleware(['auth', 'can:Eliminar Categoría']);

// Productos
Route::get('/admin/productos', [ProductoController::class, 'index'])
    ->name('admin.productos.index')
    ->middleware(['auth', 'can:Listado de Productos']);

Route::get('/admin/productos/create', [ProductoController::class, 'create'])
    ->name('admin.productos.create')
    ->middleware(['auth', 'can:Crear Producto']);

Route::post('/admin/productos/create', [ProductoController::class, 'store'])
    ->name('admin.productos.store')
    ->middleware(['auth', 'can:Guardar Producto']);

Route::get('/admin/producto/{id}', [ProductoController::class, 'show'])
    ->name('admin.productos.show')
    ->middleware(['auth', 'can:Ver detalle de Producto']);

Route::get('/admin/producto/{id}/imagenes', [ProductoController::class, 'imagenes'])
    ->name('admin.productos.imagenes')
    ->middleware(['auth', 'can:Gestionar imágenes de Producto']);

Route::post('/admin/producto/{id}/upload_imagen', [ProductoController::class, 'upload_imagen'])
    ->name('admin.productos.upload_imagen')
    ->middleware(['auth', 'can:Subir imágenes de Producto']);

Route::delete('/admin/producto/imagen/{id}/destroy_imagen', [ProductoController::class, 'destroy_imagen'])
    ->name('admin.productos.destroy_imagen')
    ->middleware(['auth', 'can:Eliminar imágenes de Producto']);

Route::get('/admin/producto/{id}/edit', [ProductoController::class, 'edit'])
    ->name('admin.productos.edit')
    ->middleware(['auth', 'can:Editar Producto']);

Route::put('/admin/producto/{id}', [ProductoController::class, 'update'])
    ->name('admin.productos.update')
    ->middleware(['auth', 'can:Actualizar Producto']);

Route::delete('/admin/producto/{id}', [ProductoController::class, 'destroy'])
    ->name('admin.productos.destroy')
    ->middleware(['auth', 'can:Eliminar Producto']);

// Pedidos
Route::get('/admin/pedidos', [OrdenController::class, 'index'])
    ->name('admin.pedidos.index')
    ->middleware(['auth', 'can:Listado de Pedidos']);

Route::get('/admin/pagos', [OrdenController::class, 'pagos'])
    ->name('admin.pagos.index')
    ->middleware(['auth', 'can:Listado de Pedidos']);

Route::get('/admin/pedido/{id}', [OrdenController::class, 'create'])
    ->name('admin.pedidos.create')
    ->middleware(['auth', 'can:Crear Pedido']);

Route::post('/admin/pedido/{id}', [OrdenController::class, 'store'])
    ->name('admin.pedidos.store')
    ->middleware(['auth', 'can:Procesar Pedido']);

Route::post('/admin/pedido/{id}/avanzar', [OrdenController::class, 'avanzarEstado'])
    ->name('admin.pedidos.avanzar')
    ->middleware(['auth', 'can:Procesar Pedido']);

Route::post('/admin/pedido/{id}/marcar-pagado', [OrdenController::class, 'marcarPagado'])
    ->name('admin.pedidos.marcar_pagado')
    ->middleware(['auth', 'can:Procesar Pedido']);

Route::get('/admin/pedido/{id}/comprobante-pago', [OrdenController::class, 'verComprobantePagoManual'])
    ->name('admin.pedidos.comprobante')
    ->middleware(['auth', 'can:Procesar Pedido']);

// Zonas de Envío (Países)
Route::get('/admin/paises', [PaisController::class, 'index'])->name('admin.paises.index')->middleware(['auth', 'can:Gestionar países de envío']);
Route::post('/admin/paises', [PaisController::class, 'store'])->name('admin.paises.store')->middleware(['auth', 'can:Gestionar países de envío']);
Route::put('/admin/paises/{id}', [PaisController::class, 'update'])->name('admin.paises.update')->middleware(['auth', 'can:Gestionar países de envío']);
Route::delete('/admin/paises/{id}', [PaisController::class, 'destroy'])->name('admin.paises.destroy')->middleware(['auth', 'can:Gestionar países de envío']);
Route::post('/admin/paises/{id}/toggle-status', [PaisController::class, 'toggleStatus'])->name('admin.paises.toggle')->middleware(['auth', 'can:Gestionar países de envío']);

// Zonas de Envío (Departamentos)
Route::get('/admin/departamentos', [DepartamentoController::class, 'index'])->name('admin.departamentos.index')->middleware(['auth', 'can:Gestionar zonas de envío']);
Route::post('/admin/departamentos', [DepartamentoController::class, 'store'])->name('admin.departamentos.store')->middleware(['auth', 'can:Gestionar zonas de envío']);
Route::put('/admin/departamentos/{id}', [DepartamentoController::class, 'update'])->name('admin.departamentos.update')->middleware(['auth', 'can:Gestionar zonas de envío']);
Route::delete('/admin/departamentos/{id}', [DepartamentoController::class, 'destroy'])->name('admin.departamentos.destroy')->middleware(['auth', 'can:Gestionar zonas de envío']);
Route::post('/admin/departamentos/{id}/toggle-status', [DepartamentoController::class, 'toggleStatus'])->name('admin.departamentos.toggle')->middleware(['auth', 'can:Gestionar zonas de envío']);

// Zonas de Envío (Provincias)
Route::get('/admin/provincias', [ProvinciaController::class, 'index'])->name('admin.provincias.index')->middleware(['auth', 'can:Gestionar zonas de envío']);
Route::post('/admin/provincias', [ProvinciaController::class, 'store'])->name('admin.provincias.store')->middleware(['auth', 'can:Gestionar zonas de envío']);
Route::put('/admin/provincias/{id}', [ProvinciaController::class, 'update'])->name('admin.provincias.update')->middleware(['auth', 'can:Gestionar zonas de envío']);
Route::delete('/admin/provincias/{id}', [ProvinciaController::class, 'destroy'])->name('admin.provincias.destroy')->middleware(['auth', 'can:Gestionar zonas de envío']);
Route::post('/admin/provincias/{id}/toggle-status', [ProvinciaController::class, 'toggleStatus'])->name('admin.provincias.toggle')->middleware(['auth', 'can:Gestionar zonas de envío']);

// Direcciones del Cliente
Route::post('/direcciones', [DireccionController::class, 'store'])->name('web.direcciones.store')->middleware(['auth']);
Route::post('/direcciones/express', [DireccionController::class, 'storeExpress'])->name('web.direcciones.storeExpress')->middleware(['auth']);
Route::put('/direcciones/{id}', [DireccionController::class, 'update'])->name('web.direcciones.update')->middleware(['auth']);
Route::delete('/direcciones/{id}', [DireccionController::class, 'destroy'])->name('web.direcciones.destroy')->middleware(['auth']);
Route::post('/direcciones/{id}/default', [DireccionController::class, 'setDefault'])->name('web.direcciones.default')->middleware(['auth']);

// APIs de cascada geográfica
Route::get('/api/paises/{pais_id}/departamentos', [DireccionController::class, 'getDepartamentos']);
Route::get('/api/departamentos/{departamento_id}/provincias', [DireccionController::class, 'getProvincias']);

// Marcas
Route::get('/admin/marcas', [MarcaController::class, 'index'])
    ->name('admin.marcas.index')
    ->middleware(['auth', 'can:Listado de Marcas']);
Route::get('/admin/marcas/create', [MarcaController::class, 'create'])
    ->name('admin.marcas.create')
    ->middleware(['auth', 'can:Crear Marca']);
Route::post('/admin/marcas/create', [MarcaController::class, 'store'])
    ->name('admin.marcas.store')
    ->middleware(['auth', 'can:Guardar Marca']);
Route::get('/admin/marca/{id}', [MarcaController::class, 'show'])
    ->name('admin.marcas.show')
    ->middleware(['auth', 'can:Ver detalle de Marca']);
Route::get('/admin/marca/{id}/edit', [MarcaController::class, 'edit'])
    ->name('admin.marcas.edit')
    ->middleware(['auth', 'can:Editar Marca']);
Route::put('/admin/marca/{id}', [MarcaController::class, 'update'])
    ->name('admin.marcas.update')
    ->middleware(['auth', 'can:Actualizar Marca']);
Route::delete('/admin/marca/{id}', [MarcaController::class, 'destroy'])
    ->name('admin.marcas.destroy')
    ->middleware(['auth', 'can:Eliminar Marca']);
Route::post('/admin/marca/{id}/toggle-status', [MarcaController::class, 'toggleStatus'])
    ->name('admin.marcas.toggle')
    ->middleware(['auth', 'can:Editar Marca']);

// Ofertas
Route::get('/admin/ofertas', [OfertaController::class, 'index'])
    ->name('admin.ofertas.index')
    ->middleware(['auth', 'can:Listado de Ofertas']);
Route::get('/admin/ofertas/create', [OfertaController::class, 'create'])
    ->name('admin.ofertas.create')
    ->middleware(['auth', 'can:Crear Oferta']);
Route::post('/admin/ofertas/create', [OfertaController::class, 'store'])
    ->name('admin.ofertas.store')
    ->middleware(['auth', 'can:Guardar Oferta']);
Route::get('/admin/oferta/{id}', [OfertaController::class, 'show'])
    ->name('admin.ofertas.show')
    ->middleware(['auth', 'can:Ver detalle de Oferta']);
Route::get('/admin/oferta/{id}/edit', [OfertaController::class, 'edit'])
    ->name('admin.ofertas.edit')
    ->middleware(['auth', 'can:Editar Oferta']);
Route::put('/admin/oferta/{id}', [OfertaController::class, 'update'])
    ->name('admin.ofertas.update')
    ->middleware(['auth', 'can:Actualizar Oferta']);
Route::delete('/admin/oferta/{id}', [OfertaController::class, 'destroy'])
    ->name('admin.ofertas.destroy')
    ->middleware(['auth', 'can:Eliminar Oferta']);
Route::post('/admin/oferta/{id}/toggle-status', [OfertaController::class, 'toggleStatus'])
    ->name('admin.ofertas.toggle')
    ->middleware(['auth', 'can:Editar Oferta']);

// Banners
Route::get('/admin/banners', [BannerController::class, 'index'])
    ->name('admin.banners.index')
    ->middleware(['auth', 'can:Listado de Banners']);
Route::get('/admin/banners/create', [BannerController::class, 'create'])
    ->name('admin.banners.create')
    ->middleware(['auth', 'can:Crear Banner']);
Route::post('/admin/banners/create', [BannerController::class, 'store'])
    ->name('admin.banners.store')
    ->middleware(['auth', 'can:Guardar Banner']);
Route::get('/admin/banner/{id}', [BannerController::class, 'show'])
    ->name('admin.banners.show')
    ->middleware(['auth', 'can:Ver detalle de Banner']);
Route::get('/admin/banner/{id}/edit', [BannerController::class, 'edit'])
    ->name('admin.banners.edit')
    ->middleware(['auth', 'can:Editar Banner']);
Route::put('/admin/banner/{id}', [BannerController::class, 'update'])
    ->name('admin.banners.update')
    ->middleware(['auth', 'can:Actualizar Banner']);
Route::delete('/admin/banner/{id}', [BannerController::class, 'destroy'])
    ->name('admin.banners.destroy')
    ->middleware(['auth', 'can:Eliminar Banner']);
Route::post('/admin/banner/{id}/toggle-status', [BannerController::class, 'toggleStatus'])
    ->name('admin.banners.toggle')
    ->middleware(['auth', 'can:Editar Banner']);

// Carruseles
Route::get('/admin/carruseles', [CarruselController::class, 'index'])
    ->name('admin.carruseles.index')
    ->middleware(['auth', 'can:Listado de Carruseles']);
Route::get('/admin/carruseles/create', [CarruselController::class, 'create'])
    ->name('admin.carruseles.create')
    ->middleware(['auth', 'can:Crear Carrusel']);
Route::post('/admin/carruseles/create', [CarruselController::class, 'store'])
    ->name('admin.carruseles.store')
    ->middleware(['auth', 'can:Guardar Carrusel']);
Route::get('/admin/carrusel/{id}', [CarruselController::class, 'show'])
    ->name('admin.carruseles.show')
    ->middleware(['auth', 'can:Ver detalle de Carrusel']);
Route::get('/admin/carrusel/{id}/edit', [CarruselController::class, 'edit'])
    ->name('admin.carruseles.edit')
    ->middleware(['auth', 'can:Editar Carrusel']);
Route::put('/admin/carrusel/{id}', [CarruselController::class, 'update'])
    ->name('admin.carruseles.update')
    ->middleware(['auth', 'can:Actualizar Carrusel']);
Route::delete('/admin/carrusel/{id}', [CarruselController::class, 'destroy'])
    ->name('admin.carruseles.destroy')
    ->middleware(['auth', 'can:Eliminar Carrusel']);
Route::post('/admin/carrusel/{id}/toggle-status', [CarruselController::class, 'toggleStatus'])
    ->name('admin.carruseles.toggle')
    ->middleware(['auth', 'can:Editar Carrusel']);

// Secciones del Home
Route::get('/admin/secciones-home', [SeccionHomeController::class, 'index'])
    ->name('admin.secciones_home.index')
    ->middleware(['auth', 'can:Listado de Secciones Home']);
Route::get('/admin/secciones-home/{id}/edit', [SeccionHomeController::class, 'edit'])
    ->name('admin.secciones_home.edit')
    ->middleware(['auth', 'can:Editar Sección Home']);
Route::put('/admin/secciones-home/{id}', [SeccionHomeController::class, 'update'])
    ->name('admin.secciones_home.update')
    ->middleware(['auth', 'can:Actualizar Sección Home']);
Route::post('/admin/secciones-home/{id}/toggle-status', [SeccionHomeController::class, 'toggleStatus'])
    ->name('admin.secciones_home.toggle')
    ->middleware(['auth', 'can:Editar Sección Home']);

// Nosotros (Admin)
Route::get('/admin/nosotros', [NosotrosController::class, 'index'])
    ->name('admin.nosotros.index')
    ->middleware(['auth', 'can:Ver Nosotros']);
Route::post('/admin/nosotros', [NosotrosController::class, 'store'])
    ->name('admin.nosotros.store')
    ->middleware(['auth', 'can:Editar Nosotros']);

// Contacto (Admin)
Route::get('/admin/contacto', [ContactoController::class, 'index'])
    ->name('admin.contacto.index')
    ->middleware(['auth', 'can:Ver Contacto']);
Route::post('/admin/contacto', [ContactoController::class, 'store'])
    ->name('admin.contacto.store')
    ->middleware(['auth', 'can:Editar Contacto']);

// Technical support tickets shared with GALY.
Route::get('/admin/soporte-tecnico', [SoporteTecnicoController::class, 'index'])
    ->name('admin.soporte.index')
    ->middleware(['auth', 'can:Ver Soporte Técnico']);
Route::put('/admin/soporte-tecnico/{id}', [SoporteTecnicoController::class, 'update'])
    ->name('admin.soporte.update')
    ->middleware(['auth', 'can:Editar Soporte Técnico']);

// Product reservations shared with GALY.
Route::get('/admin/reservas', [ReservaProductoController::class, 'index'])
    ->name('admin.reservas.index')
    ->middleware(['auth', 'can:Ver Reservas']);
Route::delete('/admin/reservas/{id}', [ReservaProductoController::class, 'cancelAdmin'])
    ->name('admin.reservas.cancel')
    ->middleware(['auth', 'can:Cancelar Reservas']);

// Mensajes de Contacto (Admin)
Route::get('/admin/mensajes', [MensajeContactoController::class, 'index'])
    ->name('admin.mensajes.index')
    ->middleware(['auth', 'can:Listado de Mensajes']);
Route::get('/admin/mensajes/{id}', [MensajeContactoController::class, 'show'])
    ->name('admin.mensajes.show')
    ->middleware(['auth', 'can:Ver Mensaje']);
Route::put('/admin/mensajes/{id}/estado', [MensajeContactoController::class, 'updateStatus'])
    ->name('admin.mensajes.estado')
    ->middleware(['auth', 'can:Ver Mensaje']);
Route::delete('/admin/mensajes/{id}', [MensajeContactoController::class, 'destroy'])
    ->name('admin.mensajes.destroy')
    ->middleware(['auth', 'can:Eliminar Mensaje']);

// ////////////////////////////////////////////////////RUTAS PARA LA WEB////////////////////////////////////////////////////////////
Route::get('/', [WebController::class, 'index'])->name('web.index');
Route::get('/producto/{id}', [ProductoController::class, 'detalle_producto'])->name('web.detalle_producto');
Route::get('/categoria/{categoria:slug}', [WebController::class, 'categoria'])->name('web.categoria');
Route::get('/productos', [WebController::class, 'productos'])->name('web.productos');
Route::get('/ofertas', [WebController::class, 'ofertas'])->name('web.ofertas');
Route::get('/marcas', [WebController::class, 'marcas'])->name('web.marcas');
Route::get('/reservas', [ReservaProductoController::class, 'mine'])->name('web.reservas.index')->middleware('auth');
Route::post('/reservas', [ReservaProductoController::class, 'reserve'])->name('web.reservas.store')->middleware('auth');
Route::delete('/reservas/{id}', [ReservaProductoController::class, 'cancelMine'])->name('web.reservas.cancel')->middleware('auth');
Route::get('/nosotros', [WebController::class, 'nosotros'])->name('web.nosotros');
Route::get('/contacto', [WebController::class, 'contacto'])->name('web.contacto');
Route::post('/contacto', [WebController::class, 'guardar_mensaje'])->name('web.contacto.store');
Route::get('/api/buscar-sugerencias', [WebController::class, 'buscar_sugerencias'])->name('api.buscar_sugerencias');
Route::get('/dashboard', [DashboardController::class, 'index'])->name('web.dashboard')->middleware('auth');
Route::get('/ajustes', [DashboardController::class, 'ajustes'])->name('web.ajustes')->middleware('auth');
Route::put('/ajustes/informacion_personal', [DashboardController::class, 'informacion_personal'])->name('web.informacion_personal')->middleware('auth');
Route::put('/ajustes/actualizar_password', [DashboardController::class, 'actualizar_password'])->name('web.actualizar_password')->middleware('auth');
Route::get('/web/login', [DashboardController::class, 'login'])->name('web.login');
Route::post('/web/login', [DashboardController::class, 'autenticacion'])->name('web.autenticacion');
Route::get('/web/registro', [DashboardController::class, 'registro'])->name('web.registro');
Route::post('/web/registro', [DashboardController::class, 'crear_cuenta'])->name('web.crear_cuenta');
Route::get('/favoritos', [ProductoFavoritoController::class, 'index'])->name('web.favoritos.index')->middleware('auth');
Route::get('/buscar', [WebController::class, 'buscar_producto'])->name('web.buscar_producto');

// ////////////////////////////////////////////////////RUTAS PARA EL CARRITO////////////////////////////////////////////////////////////
Route::get('/carrito', [CarritoController::class, 'index'])->name('web.carrito.index')->middleware('auth');
Route::post('/carrito/agregar', [CarritoController::class, 'store'])->name('web.carrito.store');
Route::put('/carrito/actualizar', [CarritoController::class, 'update'])->name('web.carrito.update')->middleware('auth');
Route::delete('/carrito/{id}', [CarritoController::class, 'destroy'])->name('web.carrito.destroy')->middleware('auth');
Route::post('/carrito/limpiar', [CarritoController::class, 'limpiar'])->name('web.carrito.limpiar')->middleware('auth');

// ////////////////////////////////////////////////////RUTAS PARA PEDIDOS Y PAGOS MANUALES////////////////////////////////////////////////////////////
Route::get('/pedido/{id}/confirmacion', [OrdenController::class, 'confirmacion'])
    ->name('web.pedidos.confirmacion')
    ->middleware('auth');
Route::get('/pedido/{id}/comprobante-pago', [OrdenController::class, 'verMiComprobantePagoManual'])
    ->name('web.pedidos.comprobante.ver')
    ->middleware('auth');
Route::post('/pedido/pago-manual', [OrdenController::class, 'crearPedidoPagoManual'])->name('web.pedidos.pago_manual')->middleware(['auth', 'throttle:10,1']);
Route::post('/pedido/{id}/comprobante-pago', [OrdenController::class, 'enviarComprobantePagoManual'])->name('web.pedidos.comprobante')->middleware(['auth', 'throttle:10,1']);

// Pago con tarjeta (Mercado Pago Checkout Pro): la pasarela redirige al cliente de
// vuelta a /retorno y notifica en /webhooks/mercadopago, que va sin CSRF.
// Cada llamada consulta la API de Mercado Pago, así que se limita para que nadie
// la use como proxy ni repita pedidos en cascada.
Route::get('/pago/tarjeta/retorno', [MercadoPagoController::class, 'retorno'])
    ->name('web.pagos.tarjeta.retorno')
    ->middleware(['auth', 'throttle:30,1']);
Route::post('/pedido/{id}/reintentar-tarjeta', [MercadoPagoController::class, 'reintentar'])
    ->name('web.pedidos.tarjeta.reintentar')
    ->middleware(['auth', 'throttle:6,1']);
Route::post('/webhooks/mercadopago', [MercadoPagoController::class, 'webhook'])
    ->name('web.hook.mercadopago')
    ->middleware('throttle:300,1');

// ////////////////////////////////////////////////////RUTAS DE APARIENCIA Y FOOTER (ADMIN)////////////////////////////////////////////////////////////
Route::get('/admin/apariencia', [AparienciaController::class, 'index'])->name('admin.apariencia.index')->middleware(['auth', 'can:Gestionar Apariencia']);
Route::post('/admin/apariencia', [AparienciaController::class, 'store'])->name('admin.apariencia.store')->middleware(['auth', 'can:Gestionar Apariencia']);

Route::get('/admin/footer', [FooterController::class, 'index'])->name('admin.footer.index')->middleware(['auth', 'can:Gestionar Footer']);
Route::post('/admin/footer/settings', [FooterController::class, 'storeSettings'])->name('admin.footer.store_settings')->middleware(['auth', 'can:Gestionar Footer']);

// ////////////////////////////////////////////////////RUTAS LEGALES PÚBLICAS////////////////////////////////////////////////////////////
Route::get('/terminos-y-condiciones', [WebController::class, 'terminos'])->name('web.terminos');
Route::get('/politica-de-privacidad', [WebController::class, 'privacidad'])->name('web.privacidad');
Route::get('/politica-de-cookies', [WebController::class, 'cookies'])->name('web.cookies');
Route::get('/cambios-y-devoluciones', [WebController::class, 'cambios_devoluciones'])->name('web.cambios_devoluciones');

// ////////////////////////////////////////////////////RUTAS FAVORITOS AJAX////////////////////////////////////////////////////////////
Route::post('/favoritos/toggle', [ProductoFavoritoController::class, 'toggle'])->name('web.favoritos.toggle');
Route::post('/favoritos/agregar-todo-al-carrito', [ProductoFavoritoController::class, 'agregarTodoAlCarrito'])->name('web.favoritos.agregar_todo_al_carrito');

// ////////////////////////////////////////////////////RUTAS DE RESEÑAS / VALORACIONES////////////////////////////////////////////////////////////
Route::middleware('auth')->group(function () {
    Route::post('/reseñas', [ReviewController::class, 'store'])->name('web.reviews.store');
    Route::post('/reseñas/{id}/update', [ReviewController::class, 'update'])->name('web.reviews.update');
    Route::delete('/reseñas/{id}', [ReviewController::class, 'destroy'])->name('web.reviews.destroy');

    Route::get('/admin/reseñas', [ReviewController::class, 'adminIndex'])
        ->name('admin.reviews.index')
        ->middleware('can:Procesar Pedido');
    Route::post('/admin/reseñas/{id}/aprobar', [ReviewController::class, 'approve'])
        ->name('admin.reviews.approve')
        ->middleware('can:Procesar Pedido');
    Route::post('/admin/reseñas/{id}/rechazar', [ReviewController::class, 'reject'])
        ->name('admin.reviews.reject')
        ->middleware('can:Procesar Pedido');
    Route::post('/admin/reseñas/{id}/responder', [ReviewController::class, 'reply'])
        ->name('admin.reviews.reply')
        ->middleware('can:Procesar Pedido');
    Route::delete('/admin/reseñas/{id}/eliminar', [ReviewController::class, 'destroyAdmin'])
        ->name('admin.reviews.destroy_admin')
        ->middleware('can:Procesar Pedido');
});

Route::fallback(function () {
    // Verificar si la URL solicitada comienza con '/admin'
    if (request()->is('admin*')) {

        // 404 para el área de administración
        // Asegúrate que esta vista exista: resources/views/errors/404-admin.blade.php
        return response()->view('errors.404-admin', [], 404);
    }

    // 404 para la web pública
    // Asegúrate que esta vista exista: resources/views/errors/404.blade.php
    return response()->view('errors.404', [], 404);
});
