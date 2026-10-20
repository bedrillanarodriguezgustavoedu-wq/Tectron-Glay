<?php

namespace App\Http\Controllers;

use App\Models\Banner;
use App\Models\Carrusel;
use App\Models\Categoria;
use App\Models\Marca;
use App\Models\Oferta;
use App\Models\Orden;
use App\Models\Producto;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;

class AdminController extends Controller
{
    public function index()
    {
        $total_roles = Role::count();
        $total_usuarios = User::whereDoesntHave('roles', function ($query) {
            $query->where('name', 'SUPER ADMIN');
        })->count();
        $total_categorias = Categoria::count();
        $total_productos = Producto::count();

        $total_pedidos_nuevos = Orden::where('estado_orden', 'En proceso')->count();
        $total_pedidos_enviados = Orden::where('estado_orden', 'Enviado')->count();

        $total_pedidos = Orden::count();

        // Nuevos Contadores para el Dashboard
        $total_marcas = Marca::count();
        $total_ofertas_activas = Oferta::where('activo', true)->count();
        $total_productos_destacados = Producto::where('destacado', true)->count();
        $total_productos_nuevos = Producto::where('nuevo', true)->count();
        $total_productos_en_oferta = Producto::where('en_oferta', true)->count();
        $total_banners = Banner::count();
        $total_carruseles = Carrusel::count();
        $total_productos_mas_vendidos = Producto::where('mas_vendido', true)->count();

        $monthExpression = DB::connection()->getDriverName() === 'sqlite'
            ? "CAST(strftime('%m', created_at) AS INTEGER)"
            : 'MONTH(created_at)';

        $usuarios_mensuales = User::select(
            DB::raw($monthExpression.' as mes'),
            DB::raw('COUNT(*) as total')
        )
            ->whereYear('created_at', now()->year)
            ->groupBy('mes')
            ->orderBy('mes')
            ->get()
            ->toArray();

        $usuarios_data = array_fill(1, 12, 0);
        foreach ($usuarios_mensuales as $data) {
            $usuarios_data[$data['mes']] = $data['total'];
        }

        $compras_mensuales = Orden::select(
            DB::raw($monthExpression.' as mes'),
            DB::raw('SUM(total) as total_monto')
        )
            ->whereYear('created_at', now()->year)
            ->groupBy('mes')
            ->orderBy('mes')
            ->get()
            ->toArray();

        $ordenesdata = array_fill(1, 12, 0);
        foreach ($compras_mensuales as $data) {
            $ordenesdata[$data['mes']] = $data['total_monto'];
        }

        $limiteStockBajo = 10;
        $limiteStockAlto = 50;

        $stockBajo = Producto::where('stock', '<=', $limiteStockBajo)->count();
        $porcentajeStockBajo = $total_productos > 0 ? ($stockBajo / $total_productos) * 100 : 0;

        $stockAlto = Producto::where('stock', '>', $limiteStockAlto)->count();
        $porcentajeStockAlto = $total_productos > 0 ? ($stockAlto / $total_productos) * 100 : 0;

        return view('admin.index', compact(
            'total_roles', 'total_usuarios', 'total_categorias', 'total_productos', 'total_pedidos_nuevos', 'total_pedidos_enviados',
            'total_pedidos', 'usuarios_data', 'ordenesdata', 'porcentajeStockBajo', 'porcentajeStockAlto',
            'total_marcas', 'total_ofertas_activas', 'total_productos_destacados', 'total_productos_nuevos',
            'total_productos_en_oferta', 'total_banners', 'total_carruseles', 'total_productos_mas_vendidos'
        ));
    }
}
