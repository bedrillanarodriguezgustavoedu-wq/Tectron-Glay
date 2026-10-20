<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use App\Models\Categoria;
use App\Models\Marca;
use App\Models\Producto;
use App\Models\Carrito;
use App\Models\ProductoFavorito;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Schema::defaultStringLength(191);
        Gate::before(function ($user) {
            if ($user->galy_user_id !== null) {
                return $user->galy_es_admin ? true : false;
            }

            return null;
        });

        // Safe View Composer for layout sharing
        View::composer('layouts.admin', function ($view) {
                $ajuste = null;
                if (Schema::hasTable('ajustes')) {
                    $ajuste = \App\Models\Ajuste::first();
                }
                $view->with('ajuste', $ajuste);
            });

            View::composer('layouts.web', function ($view) {
                
                $menu_categorias = collect();
                $menu_marcas = collect();
                $productos_destacados = collect();
                $productos_nuevos = collect();
                $productos_ofertas = collect();
                $carrito_items = collect();
                $carrito_cantidad = 0;
                $carrito_subtotal = 0;
                $cantidad_favoritos = 0;
                $contact_info = null;
                $contact_model = new \App\Models\Contacto();

                if ($contact_model->getConnection()->getSchemaBuilder()->hasTable($contact_model->getTable())) {
                    $contact_info = $contact_model->newQuery()->first();
                }

                if (\Illuminate\Support\Facades\Schema::connection('galy')->hasTable('ofertas')
                    && \Illuminate\Support\Facades\Schema::connection('galy')->hasTable('productos')) {
                    \App\Models\Oferta::refreshProductPrices();
                }

                if (\Illuminate\Support\Facades\Schema::connection('galy')->hasTable('categorias')
                    && \Illuminate\Support\Facades\Schema::connection('galy')->hasTable('productos')) {
                    $menu_categorias = Categoria::all()->take(8);
                    foreach ($menu_categorias as $cat) {
                        $cat->setRelation('productos', $cat->productos()->with('imagenes')->latest()->limit(4)->get());
                    }
                    
                    $productos_nuevos = Producto::with('imagenes')->where('nuevo', true)->latest()->limit(4)->get();
                    if ($productos_nuevos->isEmpty()) {
                        $productos_nuevos = Producto::with('imagenes')->latest()->limit(4)->get();
                    }

                    $productos_ofertas = Producto::with('imagenes')
                        ->where('en_oferta', true)
                        ->latest()
                        ->limit(4)
                        ->get();
                }

                if (\Illuminate\Support\Facades\Schema::connection('galy')->hasTable('marcas')) {
                    $menu_marcas = Marca::where('activo', true)->get();
                }

                if (\Illuminate\Support\Facades\Schema::connection('galy')->hasTable('productos')) {
                    $productos_destacados = Producto::with('imagenes')
                        ->where('destacado', true)
                        ->limit(4)
                        ->get();

                    if ($productos_destacados->isEmpty() && \Illuminate\Support\Facades\Schema::connection('galy')->hasTable('pedido_productos')) {
                        $top_producto_ids = DB::connection('galy')->table('pedido_productos')
                            ->select('producto_id', DB::raw('SUM(cantidad) as total_ventas'))
                            ->groupBy('producto_id')
                            ->orderByDesc('total_ventas')
                            ->limit(4)
                            ->pluck('producto_id');

                        $productos_destacados = Producto::with('imagenes')
                            ->whereIn('id', $top_producto_ids)
                            ->get();
                    }

                    if ($productos_destacados->isEmpty()) {
                        $productos_destacados = $productos_nuevos->take(4);
                    }
                }

                if (Auth::check()) {
                    $galyUserId = Auth::user()->resolveGalyUserId();
                    if (\Illuminate\Support\Facades\Schema::connection('galy')->hasTable('carrito')) {
                        $carrito_items = $galyUserId
                            ? Carrito::where('usuario_id', $galyUserId)->with('producto.imagenes')->get()
                            : collect();
                        $carrito_cantidad = $carrito_items->sum('cantidad');
                        $carrito_subtotal = $carrito_items->sum(function($item) {
                            if (!$item->producto) return 0;
                            $precio = $item->producto->precio_oferta ?? $item->producto->precio_venta;
                            return $precio * $item->cantidad;
                        });
                    }
                    if (\Illuminate\Support\Facades\Schema::connection('galy')->hasTable('favoritos')) {
                        if ($galyUserId !== null) {
                            $cantidad_favoritos = ProductoFavorito::where('usuario_id', $galyUserId)->count();
                            $favoritos_ids = ProductoFavorito::where('usuario_id', $galyUserId)->pluck('producto_id');
                        }
                    }
                }

                $footer_settings = null;
                $footer_enlaces = collect();
                if (Schema::hasTable('footer_settings')) {
                    $footer_settings = \App\Models\FooterSettings::first();
                }
                if (Schema::hasTable('footer_enlaces')) {
                    $footer_enlaces = \App\Models\FooterEnlace::where('activo', true)->orderBy('orden', 'asc')->get();
                }

                $view->with([
                    'menu_categorias' => $menu_categorias,
                    'menu_marcas' => $menu_marcas,
                    'productos_destacados' => $productos_destacados,
                    'productos_nuevos' => $productos_nuevos,
                    'productos_ofertas' => $productos_ofertas,
                    'carrito_items' => $carrito_items,
                    'carrito_cantidad' => $carrito_cantidad,
                    'carrito_subtotal' => $carrito_subtotal,
                    'cantidad_favoritos' => $cantidad_favoritos,
                    'favoritos_ids' => $favoritos_ids ?? collect(),
                    'contact_info' => $contact_info,
                    'footer_settings' => $footer_settings,
                    'footer_enlaces' => $footer_enlaces,
                ]);
            });
    }
}
