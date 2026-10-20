<?php

namespace App\Http\Controllers;

use App\Models\Ajuste;
use App\Models\Orden;
use App\Models\Producto;
use App\Models\Resena;
use App\Models\ResenaImagen;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class ReviewController extends Controller
{
    /**
     * Store a newly created review in storage.
     */
    public function store(Request $request)
    {
        $ajuste = Ajuste::first() ?? new Ajuste;
        $max_char = $ajuste->review_longitud_maxima ?? 1000;
        $max_imgs = $ajuste->review_max_imagenes ?? 5;
        $requires_approval = $ajuste->review_requiere_aprobacion ?? true;

        $request->validate([
            'producto_id' => ['required', Rule::exists('galy.productos', 'id')],
            'orden_id' => ['required', Rule::exists('galy.pedidos', 'id')],
            'estrellas' => 'required|integer|min:1|max:5',
            'titulo' => 'nullable|string|max:100',
            'comentario' => 'required|string|max:' . $max_char,
            'imagenes.*' => 'image|mimes:jpeg,png,jpg,webp|max:2048',
        ]);

        $usuario_id = Auth::user()->galy_user_id;
        $producto_id = $request->producto_id;
        $orden_id = $request->orden_id;

        // 1. Verify that the order belongs to the user and is marked as 'Entregado'
        $orden = Orden::where('id', $orden_id)
            ->where('usuario_id', $usuario_id)
            ->first();

        if (!$orden || $orden->estado_orden !== 'Entregado') {
            return redirect()->back()
                ->with('mensaje', 'Solo puede valorar productos de pedidos que hayan sido entregados.')
                ->with('icono', 'error');
        }

        // 2. Verify that the product is part of this order
        $comprado = $orden->detalles()->where('producto_id', $producto_id)->exists();
        if (!$comprado) {
            return redirect()->back()
                ->with('mensaje', 'Este producto no forma parte del pedido especificado.')
                ->with('icono', 'error');
        }

        // 3. Verify that the user has not reviewed this product for this order already
        $existe_resena = Resena::where('producto_id', $producto_id)
            ->where('usuario_id', $usuario_id)
            ->where('orden_id', $orden_id)
            ->exists();

        if ($existe_resena) {
            return redirect()->back()
                ->with('mensaje', 'Ya ha dejado una valoración para este producto en este pedido.')
                ->with('icono', 'warning');
        }

        // 4. Validate images count limit
        if ($request->hasFile('imagenes') && count($request->file('imagenes')) > $max_imgs) {
            return redirect()->back()
                ->with('mensaje', 'La cantidad máxima de imágenes permitidas es ' . $max_imgs)
                ->with('icono', 'warning');
        }

        // 5. Create Review
        $resena = new Resena;
        $resena->producto_id = $producto_id;
        $resena->usuario_id = $usuario_id;
        $resena->orden_id = $orden_id;
        $resena->estrellas = $request->estrellas;
        $resena->titulo = $request->titulo;
        $resena->comentario = $request->comentario;
        $resena->aprobado = !$requires_approval;
        $resena->save();

        // 6. Handle Images upload
        if ($ajuste->review_permitir_imagenes && $request->hasFile('imagenes')) {
            $cont = 0;
            foreach ($request->file('imagenes') as $file) {
                if ($cont < $max_imgs) {
                    $path = $file->store('reviews', 'public');
                    ResenaImagen::create([
                        'resena_id' => $resena->id,
                        'imagen' => $path,
                        'orden' => $cont,
                    ]);
                    $cont++;
                }
            }
        }

        $mensaje = $requires_approval
            ? 'Su valoración ha sido enviada con éxito y está pendiente de aprobación por el administrador.'
            : 'Su valoración ha sido publicada con éxito.';

        return redirect()->back()
            ->with('mensaje', $mensaje)
            ->with('icono', 'success');
    }

    /**
     * Update the specified review in storage.
     */
    public function update(Request $request, $id)
    {
        $resena = Resena::findOrFail($id);

        if ((int) $resena->usuario_id !== (int) Auth::user()->galy_user_id) {
            abort(403);
        }

        $ajuste = Ajuste::first() ?? new Ajuste;
        $max_char = $ajuste->review_longitud_maxima ?? 1000;
        $max_imgs = $ajuste->review_max_imagenes ?? 5;
        $limit_days = $ajuste->review_tiempo_edicion_dias ?? 30;

        // Check if the editing window has expired
        if ($resena->created_at->diffInDays(now()) > $limit_days) {
            return redirect()->back()
                ->with('mensaje', 'El plazo máximo de ' . $limit_days . ' días para editar su reseña ha expirado.')
                ->with('icono', 'error');
        }

        $request->validate([
            'estrellas' => 'required|integer|min:1|max:5',
            'titulo' => 'nullable|string|max:100',
            'comentario' => 'required|string|max:' . $max_char,
            'imagenes.*' => 'image|mimes:jpeg,png,jpg,webp|max:2048',
            'eliminar_imagenes' => 'nullable|array',
            'eliminar_imagenes.*' => ['integer', Rule::exists('galy.resena_imagenes', 'id')],
        ]);

        // Delete requested images
        if ($request->has('eliminar_imagenes')) {
            foreach ($request->eliminar_imagenes as $img_id) {
                $img = ResenaImagen::where('resena_id', $resena->id)->where('id', $img_id)->first();
                if ($img) {
                    Storage::disk('public')->delete($img->imagen);
                    $img->delete();
                }
            }
        }

        // Validate total images limit after potential deletion
        $current_imgs_count = $resena->imagenes()->count();
        $new_imgs_count = $request->hasFile('imagenes') ? count($request->file('imagenes')) : 0;
        if (($current_imgs_count + $new_imgs_count) > $max_imgs) {
            return redirect()->back()
                ->with('mensaje', 'La cantidad máxima de imágenes acumuladas permitida es ' . $max_imgs)
                ->with('icono', 'warning');
        }

        // Update Review
        $resena->estrellas = $request->estrellas;
        $resena->titulo = $request->titulo;
        $resena->comentario = $request->comentario;
        
        // Re-set approved status if modifications require new moderation
        if ($ajuste->review_requiere_aprobacion) {
            $resena->aprobado = false;
        }

        $resena->save();

        // Handle new images
        if ($ajuste->review_permitir_imagenes && $request->hasFile('imagenes')) {
            $cont = $current_imgs_count;
            foreach ($request->file('imagenes') as $file) {
                if ($cont < $max_imgs) {
                    $path = $file->store('reviews', 'public');
                    ResenaImagen::create([
                        'resena_id' => $resena->id,
                        'imagen' => $path,
                        'orden' => $cont,
                    ]);
                    $cont++;
                }
            }
        }

        // Trigger manual rating refresh in case status changed
        $resena->producto->recalculateRatingCache();

        $mensaje = $ajuste->review_requiere_aprobacion
            ? 'Su valoración ha sido actualizada con éxito y pasará por aprobación del administrador.'
            : 'Su valoración ha sido actualizada con éxito.';

        return redirect()->back()
            ->with('mensaje', $mensaje)
            ->with('icono', 'success');
    }

    /**
     * Remove the specified review from storage.
     */
    public function destroy($id)
    {
        $resena = Resena::findOrFail($id);

        if ((int) $resena->usuario_id !== (int) Auth::user()->galy_user_id) {
            abort(403);
        }

        $ajuste = Ajuste::first() ?? new Ajuste;
        if (!$ajuste->review_permitir_eliminacion) {
            return redirect()->back()
                ->with('mensaje', 'La eliminación de reseñas está desactivada por el administrador.')
                ->with('icono', 'error');
        }

        // Delete images
        foreach ($resena->imagenes as $img) {
            Storage::disk('public')->delete($img->imagen);
        }

        $resena->delete();

        return redirect()->back()
            ->with('mensaje', 'Su valoración ha sido eliminada con éxito.')
            ->with('icono', 'success');
    }

    // ==========================================
    // ADMIN MODERATION METHODS
    // ==========================================

    /**
     * Display a listing of reviews for the administrator.
     */
    public function adminIndex(Request $request)
    {
        $query = Resena::with(['producto', 'usuario', 'orden']);

        // Filter by stars
        if ($request->filled('estrellas')) {
            $query->where('estrellas', $request->estrellas);
        }

        // Filter by status
        if ($request->filled('estado')) {
            $query->where('aprobado', $request->estado === 'aprobado' ? 1 : 0);
        }

        // Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('titulo', 'LIKE', "%{$search}%")
                  ->orWhere('comentario', 'LIKE', "%{$search}%")
                  ->orWhereHas('producto', function($qp) use ($search) {
                      $qp->where('nombre', 'LIKE', "%{$search}%");
                  })
                  ->orWhereHas('usuario', function($qu) use ($search) {
                      $qu->where('name', 'LIKE', "%{$search}%")
                        ->orWhere('email', 'LIKE', "%{$search}%");
                  });
            });
        }

        $resenas = $query->orderBy('created_at', 'desc')->paginate(15);
        return view('admin.reviews.index', compact('resenas'));
    }

    /**
     * Approve a review.
     */
    public function approve($id)
    {
        $resena = Resena::findOrFail($id);
        $resena->aprobado = true;
        $resena->save();

        return redirect()->back()
            ->with('mensaje', 'La reseña ha sido aprobada con éxito.')
            ->with('icono', 'success');
    }

    /**
     * Disapprove / Reject a review.
     */
    public function reject($id)
    {
        $resena = Resena::findOrFail($id);
        $resena->aprobado = false;
        $resena->save();

        return redirect()->back()
            ->with('mensaje', 'La reseña ha sido desaprobada/ocultada.')
            ->with('icono', 'info');
    }

    /**
     * Post/Update an official administrator response.
     */
    public function reply(Request $request, $id)
    {
        $request->validate([
            'respuesta_admin' => 'required|string|max:1000',
        ]);

        $resena = Resena::findOrFail($id);
        $resena->respuesta_admin = $request->respuesta_admin;
        $resena->fecha_respuesta = now();
        $resena->save();

        return redirect()->back()
            ->with('mensaje', 'Respuesta guardada con éxito.')
            ->with('icono', 'success');
    }

    /**
     * Admin delete review completely.
     */
    public function destroyAdmin($id)
    {
        $resena = Resena::findOrFail($id);

        foreach ($resena->imagenes as $img) {
            Storage::disk('public')->delete($img->imagen);
        }

        $resena->delete();

        return redirect()->back()
            ->with('mensaje', 'La reseña ha sido eliminada permanentemente.')
            ->with('icono', 'success');
    }
}
