<?php

namespace App\Http\Controllers;

use App\Models\Ajuste;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Http;

class AjusteController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $ajuste = Ajuste::first();
        $ofertas = \App\Models\Oferta::where('activo', true)->get();

        $divisas = [
            [
                'symbol' => 'PEN',
                'name' => 'Sol Peruano'
            ],
            [
                'symbol' => 'USD',
                'name' => 'Dólar Estadounidense'
            ],
            [
                'symbol' => 'EUR',
                'name' => 'Euro'
            ],
            [
                'symbol' => 'CLP',
                'name' => 'Peso Chileno'
            ],
            [
                'symbol' => 'COP',
                'name' => 'Peso Colombiano'
            ]
        ];

        return view('admin.ajustes.index', compact('divisas', 'ajuste', 'ofertas'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $ajuste = Ajuste::first();

        $noEsTarjeta = function (string $attribute, $value, callable $fail): void {
            if (Ajuste::esNumeroDeTarjeta($value)) {
                $fail('Eso es un número de tarjeta, no una cuenta bancaria. Aquí va el número de cuenta que aparece sobre el CCI en la app del BCP, o deja el campo vacío.');
            }
        };

        $rules = [
            'nombre' => 'required|string|max:255',
            'descripcion' => 'required|string|max:255',
            'sucursal' => 'required|string|max:255',
            'direccion' => 'required|string',
            'telefonos' => 'required|string|max:255', 
            'email' => 'required|email|max:255',
            'divisa' => 'required|string|max:10',
            'pagina_web' => 'nullable|url|max:255',
            'home_slider_velocidad' => 'required|integer|min:1000',
            'home_slider_altura' => 'required|integer|min:300|max:900',
            'home_carrusel_velocidad' => 'required|integer|min:1000',
            'home_max_categorias' => 'required|integer|min:1|max:10',
            'home_max_productos_categoria' => 'required|integer|min:1|max:50',
            'home_destacado_oferta_id' => 'nullable|integer|exists:ofertas,id',
            'yape_numero' => 'nullable|string|max:30',
            'yape_titular' => [$request->filled('yape_numero') ? 'required' : 'nullable', 'string', 'max:120'],
            'plin_numero' => 'nullable|string|max:30',
            'plin_titular' => [$request->filled('plin_numero') ? 'required' : 'nullable', 'string', 'max:120'],
            'app_bcp_cci' => 'nullable|string|max:30',
            'app_bcp_cuenta' => ['nullable', 'string', 'max:30', $noEsTarjeta],
            'app_bcp_titular' => [$request->filled('app_bcp_cci') ? 'required' : 'nullable', 'string', 'max:120'],
            'web_bcp_cci' => 'nullable|string|max:30',
            'web_bcp_cuenta' => ['nullable', 'string', 'max:30', $noEsTarjeta],
            'web_bcp_titular' => [$request->filled('web_bcp_cci') ? 'required' : 'nullable', 'string', 'max:120'],
            'web_mercadopago_activa' => 'boolean',
            'web_izipay_activa' => 'boolean',
            'yape_qr' => [
                'nullable',
                'image',
                'mimes:jpeg,png,webp',
                'max:3072',
            ],
            'plin_qr' => [
                'nullable',
                'image',
                'mimes:jpeg,png,webp',
                'max:3072',
            ],
            'moneda_nombre' => 'required|string|max:100',
            'moneda_simbolo' => ['required', 'string', 'max:10', 'regex:/^[^\d]+$/u'],
            'moneda_decimales' => 'required|integer|min:0|max:4',
            'moneda_posicion' => 'required|string|in:antes,despues',
            'review_max_imagenes' => 'required|integer|min:1|max:10',
            'review_longitud_maxima' => 'required|integer|min:50|max:5000',
            'review_tiempo_edicion_dias' => 'required|integer|min:1|max:365',
        ];

        foreach (['web_yape', 'web_plin'] as $method) {
            $numberField = $method.'_numero';
            $holderField = $method.'_titular';
            $qrField = $method.'_qr';
            $rules[$numberField] = 'nullable|string|max:30';
            $rules[$holderField] = [
                $request->filled($numberField) ? 'required' : 'nullable',
                'string',
                'max:120',
            ];
            $rules[$qrField] = [
                $request->filled($numberField) && ! filled($ajuste?->{$qrField}) ? 'required' : 'nullable',
                'image',
                'mimes:jpeg,png,webp',
                'max:3072',
            ];
        }

        if ($ajuste) {
            $rules['logo'] = 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048';
            $rules['imagen_login'] = 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048';
        } else {
            $rules['logo'] = 'required|image|mimes:jpeg,png,jpg,gif,svg|max:2048';
            $rules['imagen_login'] = 'required|image|mimes:jpeg,png,jpg,gif,svg|max:2048';
        }
        
        $request->validate($rules, [
            'moneda_simbolo.regex' => 'Escribe solo el símbolo de la moneda, sin números ni montos.',
        ]);

        if (!$ajuste) { 
            $ajuste = new Ajuste();
        }

        $ajuste->nombre = $request->nombre;
        $ajuste->descripcion = $request->descripcion;
        $ajuste->sucursal = $request->sucursal;
        $ajuste->direccion = $request->direccion;
        $ajuste->telefono = $request->telefonos; 
        $ajuste->email = $request->email;
        $ajuste->divisa = $request->divisa;
        $ajuste->pagina_web = $request->pagina_web;
        $ajuste->home_slider_velocidad = $request->home_slider_velocidad;
        $ajuste->home_slider_altura = $request->home_slider_altura;
        $ajuste->home_carrusel_velocidad = $request->home_carrusel_velocidad;
        $ajuste->home_max_categorias = $request->home_max_categorias;
        $ajuste->home_max_productos_categoria = $request->home_max_productos_categoria;
        $ajuste->home_destacado_oferta_id = $request->home_destacado_oferta_id;
        $ajuste->home_mostrar_contador = $request->has('home_mostrar_contador') ? true : false;
        $ajuste->yape_numero = $request->yape_numero;
        $ajuste->yape_titular = $request->yape_titular;
        $ajuste->plin_numero = $request->plin_numero;
        $ajuste->plin_titular = $request->plin_titular;
        $ajuste->app_bcp_cci = $request->app_bcp_cci;
        $ajuste->app_bcp_cuenta = $request->app_bcp_cuenta;
        $ajuste->app_bcp_titular = $request->app_bcp_titular;
        $ajuste->web_bcp_cci = $request->web_bcp_cci;
        $ajuste->web_bcp_cuenta = $request->web_bcp_cuenta;
        $ajuste->web_bcp_titular = $request->web_bcp_titular;
        $ajuste->web_mercadopago_activa = $request->boolean('web_mercadopago_activa');
        $ajuste->web_izipay_activa = $request->boolean('web_izipay_activa');
        foreach (['web_yape', 'web_plin'] as $method) {
            $ajuste->{$method.'_numero'} = $request->input($method.'_numero');
            $ajuste->{$method.'_titular'} = $request->input($method.'_titular');
        }
        $ajuste->moneda_nombre = $request->moneda_nombre;
        $ajuste->moneda_simbolo = $request->moneda_simbolo;
        $ajuste->moneda_decimales = $request->moneda_decimales;
        $ajuste->moneda_posicion = $request->moneda_posicion;

        $ajuste->review_requiere_aprobacion = $request->has('review_requiere_aprobacion') ? true : false;
        $ajuste->review_permitir_imagenes = $request->has('review_permitir_imagenes') ? true : false;
        $ajuste->review_max_imagenes = $request->review_max_imagenes;
        $ajuste->review_longitud_maxima = $request->review_longitud_maxima;
        $ajuste->review_tiempo_edicion_dias = $request->review_tiempo_edicion_dias;
        $ajuste->review_permitir_eliminacion = $request->has('review_permitir_eliminacion') ? true : false;

        if ($request->hasFile('logo')) {
            if ($ajuste->logo && Storage::disk('public')->exists($ajuste->logo)) {
                Storage::disk('public')->delete($ajuste->logo);
            }
            $ajuste->logo = $request->file('logo')->store('logos', 'public');
        }

        if ($request->hasFile('imagen_login')) {
            if ($ajuste->imagen_login && Storage::disk('public')->exists($ajuste->imagen_login)) {
                Storage::disk('public')->delete($ajuste->imagen_login);
            }
            $ajuste->imagen_login = $request->file('imagen_login')->store('imagenes_login', 'public');
        }

        foreach ([
            'yape_qr' => 'app',
            'plin_qr' => 'app',
            'web_yape_qr' => 'web',
            'web_plin_qr' => 'web',
        ] as $qrField => $channel) {
            if ($request->hasFile($qrField)) {
                $previousQr = $ajuste->{$qrField};
                $newQr = $request->file($qrField)->store('pagos/qr/'.$channel, 'public');
                if (! $newQr) {
                    throw new \RuntimeException("No se pudo guardar el código QR de {$qrField}.");
                }

                if ($previousQr && Storage::disk('public')->exists($previousQr)) {
                    Storage::disk('public')->delete($previousQr);
                }

                $ajuste->{$qrField} = $newQr;
            }
        }

        $ajuste->save();

        return redirect()
            ->route('admin.ajustes.index')
            ->with('mensaje', 'Ajustes guardados exitosamente')
            ->with('icono', 'success');
    }

    /**
     * Display the specified resource.
     */
    public function show(Ajuste $ajuste)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Ajuste $ajuste)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Ajuste $ajuste)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Ajuste $ajuste)
    {
        //
    }
}
