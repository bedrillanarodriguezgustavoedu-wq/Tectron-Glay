<?php

namespace App\Http\Controllers;

use App\Models\Ajuste;
use Illuminate\Http\Request;

class AparienciaController extends Controller
{
    public function index()
    {
        $ajuste = Ajuste::first();
        return view('admin.apariencia.index', compact('ajuste'));
    }

    public function store(Request $request)
    {
        $request->validate([
            // Colores
            'color_primario' => 'required|string|regex:/^#[0-9A-Fa-f]{6}$/',
            'color_secundario' => 'required|string|regex:/^#[0-9A-Fa-f]{6}$/',
            'color_acento' => 'required|string|regex:/^#[0-9A-Fa-f]{6}$/',
            'color_botones' => 'required|string|regex:/^#[0-9A-Fa-f]{6}$/',
            'color_enlaces' => 'required|string|regex:/^#[0-9A-Fa-f]{6}$/',
            'color_textos_principales' => 'required|string|regex:/^#[0-9A-Fa-f]{6}$/',
            'color_encabezado' => 'required|string|regex:/^#[0-9A-Fa-f]{6}$/',
            'color_pie_pagina' => 'required|string|regex:/^#[0-9A-Fa-f]{6}$/',
            'color_tarjetas' => 'required|string|regex:/^#[0-9A-Fa-f]{6}$/',
            'color_badges' => 'required|string|regex:/^#[0-9A-Fa-f]{6}$/',
            'color_etiquetas_ofertas' => 'required|string|regex:/^#[0-9A-Fa-f]{6}$/',
            'color_botones_carrito' => 'required|string|regex:/^#[0-9A-Fa-f]{6}$/',
            'color_botones_principales' => 'required|string|regex:/^#[0-9A-Fa-f]{6}$/',

            // Menú
            'menu_bg_color' => 'required|string|regex:/^#[0-9A-Fa-f]{6}$/',
            'menu_text_color' => 'required|string|regex:/^#[0-9A-Fa-f]{6}$/',
            'menu_hover_color' => 'required|string|regex:/^#[0-9A-Fa-f]{6}$/',
            'menu_active_color' => 'required|string|regex:/^#[0-9A-Fa-f]{6}$/',
            'menu_hover_bg_color' => 'required|string|regex:/^#[0-9A-Fa-f]{6}$/',
            'menu_icon_color' => 'required|string|regex:/^#[0-9A-Fa-f]{6}$/',
            'menu_active_border_color' => 'required|string|regex:/^#[0-9A-Fa-f]{6}$/',
            'menu_opacity' => 'required|numeric|between:0,1',
        ]);

        $ajuste = Ajuste::first();
        if (!$ajuste) {
            $ajuste = new Ajuste();
        }

        $ajuste->fill($request->only([
            'color_primario', 'color_secundario', 'color_acento', 'color_botones',
            'color_enlaces', 'color_textos_principales', 'color_encabezado',
            'color_pie_pagina', 'color_tarjetas', 'color_badges', 'color_etiquetas_ofertas',
            'color_botones_carrito', 'color_botones_principales',
            'menu_bg_color', 'menu_text_color', 'menu_hover_color', 'menu_active_color',
            'menu_hover_bg_color', 'menu_icon_color', 'menu_active_border_color', 'menu_opacity'
        ]));
        $ajuste->save();

        return redirect()->back()
            ->with('mensaje', 'Apariencia y colores actualizados correctamente.')
            ->with('icono', 'success');
    }
}
