<?php

namespace App\Http\Controllers;

use App\Models\Banner;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use RuntimeException;

class BannerController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $buscar = $request->get('buscar');
        $query = Banner::query();

        if ($buscar) {
            $query->where('titulo', 'like', '%' . $buscar . '%')
                  ->orWhere('subtitulo', 'like', '%' . $buscar . '%');
        }

        $banners = $query->orderBy('orden', 'asc')->paginate(10);
        return view('admin.banners.index', compact('banners'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('admin.banners.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'imagen' => 'required|image|mimes:jpeg,png,jpg,webp|max:3072',
            'titulo' => 'nullable|string|max:255',
            'subtitulo' => 'nullable|string|max:255',
            'boton_texto' => 'nullable|string|max:100',
            'enlace' => 'nullable|string|max:500',
            'color_fondo' => ['nullable', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'activo' => 'nullable|boolean',
            'portada' => 'nullable|boolean',
            'orden' => 'required|integer|min:0',
            'fecha_publicacion' => 'nullable|date'
        ]);

        $banner = new Banner();
        $banner->titulo = $request->titulo;
        $banner->subtitulo = $request->subtitulo;
        $banner->boton_texto = $request->boton_texto;
        $banner->enlace = $request->enlace;
        $banner->color_fondo = $request->color_fondo;
        $banner->activo = $request->has('activo') ? (bool)$request->activo : true;
        $banner->portada = $request->boolean('portada');
        $banner->orden = $request->orden;
        $banner->fecha_publicacion = $request->fecha_publicacion;

        if ($request->hasFile('imagen')) {
            $banner->imagen = $this->storeSharedImage($request);
        }

        $banner->save();

        if ($banner->portada) {
            Banner::whereKeyNot($banner->getKey())->where('portada', true)->update(['portada' => false]);
        }

        return redirect()->route('admin.banners.index')
            ->with('mensaje', 'Banner creado exitosamente')
            ->with('icono', 'success');
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $banner = Banner::findOrFail($id);
        return view('admin.banners.show', compact('banner'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        $banner = Banner::findOrFail($id);
        return view('admin.banners.edit', compact('banner'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'imagen' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:3072',
            'titulo' => 'nullable|string|max:255',
            'subtitulo' => 'nullable|string|max:255',
            'boton_texto' => 'nullable|string|max:100',
            'enlace' => 'nullable|string|max:500',
            'color_fondo' => ['nullable', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'activo' => 'nullable|boolean',
            'portada' => 'nullable|boolean',
            'orden' => 'required|integer|min:0',
            'fecha_publicacion' => 'nullable|date'
        ]);

        $banner = Banner::findOrFail($id);
        $banner->titulo = $request->titulo;
        $banner->subtitulo = $request->subtitulo;
        $banner->boton_texto = $request->boton_texto;
        $banner->enlace = $request->enlace;
        $banner->color_fondo = $request->color_fondo;
        $banner->activo = $request->has('activo') ? (bool)$request->activo : false;
        $banner->portada = $request->boolean('portada');
        $banner->orden = $request->orden;
        $banner->fecha_publicacion = $request->fecha_publicacion;

        if ($request->hasFile('imagen')) {
            $banner->imagen = $this->storeSharedImage($request);
        }

        $banner->save();

        if ($banner->portada) {
            Banner::whereKeyNot($banner->getKey())->where('portada', true)->update(['portada' => false]);
        }

        return redirect()->route('admin.banners.index')
            ->with('mensaje', 'Banner actualizado exitosamente')
            ->with('icono', 'success');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $banner = Banner::findOrFail($id);

        $banner->delete();

        return redirect()->route('admin.banners.index')
            ->with('mensaje', 'Banner eliminado exitosamente')
            ->with('icono', 'success');
    }

    /**
     * Toggle banner active status.
     */
    public function toggleStatus($id)
    {
        $banner = Banner::findOrFail($id);
        $banner->activo = !$banner->activo;
        $banner->save();

        return redirect()->route('admin.banners.index')
            ->with('mensaje', 'Estado del banner actualizado exitosamente')
            ->with('icono', 'success');
    }

    private function storeSharedImage(Request $request): string
    {
        $directory = (string) config('galy.images_path');
        if ($directory === '' || ! is_dir($directory) || ! is_writable($directory)) {
            throw new RuntimeException('La carpeta de imágenes compartidas con GALY no existe o no permite escritura.');
        }

        $file = $request->file('imagen');
        $filename = Str::uuid().'.'.$file->extension();
        $file->move($directory, $filename);

        return $filename;
    }
}
