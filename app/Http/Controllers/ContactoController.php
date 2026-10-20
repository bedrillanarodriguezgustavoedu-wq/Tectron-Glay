<?php

namespace App\Http\Controllers;

use App\Models\Contacto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ContactoController extends Controller
{
    public function index()
    {
        $contacto = Contacto::first();
        return view('admin.contacto.index', compact('contacto'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'nombre_empresa' => 'required|string|max:255',
            'direccion' => 'required|string|max:255',
            'pais' => 'required|string|max:100',
            'departamento' => 'required|string|max:100',
            'provincia' => 'required|string|max:100',
            'telefono_principal' => 'required|string|max:50',
            'telefono_secundario' => 'nullable|string|max:50',
            'whatsapp' => 'nullable|string|max:50',
            'email' => 'required|email|max:255',
            'horario_atencion' => 'required|string|max:255',
            'google_maps_link' => 'nullable|string',
            'facebook_link' => 'nullable|url|max:255',
            'instagram_link' => 'nullable|url|max:255',
            'youtube_link' => 'nullable|url|max:255',
            'twitter_link' => 'nullable|url|max:255',
            'mensaje_bienvenida' => 'nullable|string',
            'imagen_banner' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
        ]);

        $contacto = Contacto::first();
        if (! $contacto) {
            $contacto = new Contacto;
        }

        $contacto->nombre_empresa = $request->nombre_empresa;
        $contacto->direccion = $request->direccion;
        $contacto->pais = $request->pais;
        $contacto->departamento = $request->departamento;
        $contacto->provincia = $request->provincia;
        $contacto->telefono_principal = $request->telefono_principal;
        $contacto->telefono_secundario = $request->telefono_secundario;
        $contacto->whatsapp = $request->whatsapp;
        $contacto->email = $request->email;
        $contacto->horario_atencion = $request->horario_atencion;
        $contacto->google_maps_link = $request->google_maps_link;
        $contacto->facebook_link = $request->facebook_link;
        $contacto->instagram_link = $request->instagram_link;
        $contacto->youtube_link = $request->youtube_link;
        $contacto->twitter_link = $request->twitter_link;
        $contacto->mensaje_bienvenida = $request->mensaje_bienvenida;
        $contacto->activo = $request->has('activo') ? true : false;

        if ($request->hasFile('imagen_banner')) {
            if ($contacto->imagen_banner && Storage::disk('public')->exists($contacto->imagen_banner)) {
                Storage::disk('public')->delete($contacto->imagen_banner);
            }
            $contacto->imagen_banner = $request->file('imagen_banner')->store('contacto', 'public');
        }

        $contacto->getConnection()->transaction(fn () => $contacto->save());

        return redirect()->back()
            ->with('mensaje', 'Información de Contacto actualizada exitosamente.')
            ->with('icono', 'success');
    }
}
