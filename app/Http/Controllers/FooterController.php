<?php

namespace App\Http\Controllers;

use App\Models\FooterSettings;
use App\Models\FooterEnlace;
use App\Models\Categoria;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class FooterController extends Controller
{
    public function index()
    {
        $settings = FooterSettings::first();
        if (!$settings) {
            $settings = new FooterSettings();
        }
        $enlaces = FooterEnlace::orderBy('orden', 'asc')->get();
        $categorias = Categoria::all();

        return view('admin.footer.index', compact('settings', 'enlaces', 'categorias'));
    }

    public function storeSettings(Request $request)
    {
        $request->validate([
            'nombre_empresa' => 'required|string|max:255',
            'descripcion' => 'nullable|string',
            'copyright_texto' => 'nullable|string|max:255',
            'logo' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',

            // Redes Sociales
            'facebook_link' => 'nullable|url|max:255',
            'instagram_link' => 'nullable|url|max:255',
            'tiktok_link' => 'nullable|url|max:255',
            'twitter_link' => 'nullable|url|max:255',
            'youtube_link' => 'nullable|url|max:255',
            'linkedin_link' => 'nullable|url|max:255',
            'whatsapp_link' => 'nullable|string|max:255',

            // Contacto
            'direccion' => 'nullable|string|max:255',
            'telefono' => 'nullable|string|max:100',
            'email' => 'nullable|email|max:255',
            'horario_atencion' => 'nullable|string',

            // Aplicaciones
            'app_store_link' => 'nullable|url|max:255',
            'google_play_link' => 'nullable|url|max:255',

            // Legal
            'terminos_condiciones_contenido' => 'nullable|string',
            'politica_privacidad_contenido' => 'nullable|string',
            'politica_cookies_contenido' => 'nullable|string',
            'cambios_devoluciones_contenido' => 'nullable|string',

            // Categorías destacadas
            'categorias_destacadas' => 'nullable|array',

            // Enlaces rápidos (Existentes)
            'enlaces' => 'nullable|array',
            'enlaces.*.titulo' => 'required|string|max:255',
            'enlaces.*.url' => 'required|string|max:255',
            'enlaces.*.orden' => 'required|integer|min:0',

            // Enlaces rápidos (Nuevos)
            'enlaces_nuevos' => 'nullable|array',
            'enlaces_nuevos.*.titulo' => 'required|string|max:255',
            'enlaces_nuevos.*.url' => 'required|string|max:255',
            'enlaces_nuevos.*.orden' => 'required|integer|min:0',

            // Enlaces eliminados
            'enlaces_eliminados' => 'nullable|array',
            'enlaces_eliminados.*' => 'integer|exists:footer_enlaces,id',
        ]);

        $settings = FooterSettings::first();
        if (!$settings) {
            $settings = new FooterSettings();
        }

        // Fill all columns except special arrays & file fields
        $settings->fill($request->except(['logo', 'categorias_destacadas', 'enlaces', 'enlaces_nuevos', 'enlaces_eliminados']));
        $settings->categorias_destacadas = $request->categorias_destacadas ?? [];

        if ($request->hasFile('logo')) {
            if ($settings->logo && Storage::disk('public')->exists($settings->logo)) {
                Storage::disk('public')->delete($settings->logo);
            }
            $settings->logo = $request->file('logo')->store('logos', 'public');
        }

        $settings->save();

        // 1. Process existing links updates
        if ($request->has('enlaces')) {
            foreach ($request->enlaces as $link_id => $link_data) {
                $link = FooterEnlace::find($link_id);
                if ($link) {
                    $link->update([
                        'titulo' => $link_data['titulo'],
                        'url' => $link_data['url'],
                        'orden' => $link_data['orden'],
                        'activo' => isset($link_data['activo']),
                    ]);
                }
            }
        }

        // 2. Process new links creations
        if ($request->has('enlaces_nuevos')) {
            foreach ($request->enlaces_nuevos as $link_data) {
                FooterEnlace::create([
                    'titulo' => $link_data['titulo'],
                    'url' => $link_data['url'],
                    'orden' => $link_data['orden'],
                    'activo' => isset($link_data['activo']),
                ]);
            }
        }

        // 3. Process deleted links
        if ($request->has('enlaces_eliminados')) {
            FooterEnlace::whereIn('id', $request->enlaces_eliminados)->delete();
        }

        return redirect()->back()
            ->with('mensaje', 'Configuración general del pie de página guardada correctamente.')
            ->with('icono', 'success');
    }
}
