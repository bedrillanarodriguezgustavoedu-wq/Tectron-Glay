<?php

namespace Tests\Feature;

use App\Models\Banner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HomeBannerPortadaTest extends TestCase
{
    use RefreshDatabase;

    public function test_portada_banner_opens_the_single_home_carousel(): void
    {
        Banner::create([
            'imagen' => 'assets/img/banner-bienvenida.jpg',
            'titulo' => 'Bienvenida Portada',
            'subtitulo' => 'Subtitulo de portada',
            'boton_texto' => 'Comprar ahora',
            'enlace' => '/productos',
            'activo' => true,
            'portada' => true,
            'orden' => 5,
        ]);

        Banner::create([
            'imagen' => 'banner-normal.jpg',
            'titulo' => 'Banner Normal Carrusel',
            'activo' => true,
            'portada' => false,
            'orden' => 1,
        ]);

        $response = $this->get(route('web.index'));

        $response->assertOk()
            ->assertSee('Bienvenida Portada')
            ->assertSee('Subtitulo de portada')
            ->assertSee('Banner Normal Carrusel')
            ->assertSee('assets/img/banner-bienvenida.jpg')
            ->assertSee('Bienvenido');

        // Ya no existe el bloque fijo de bienvenida: todo va en un solo carrusel.
        $response->assertDontSee('welcome-hero');

        // La portada se ordena primero aunque su "orden" sea mayor.
        $html = $response->getContent();
        $this->assertLessThan(
            strpos($html, 'Banner Normal Carrusel'),
            strpos($html, 'Bienvenida Portada')
        );
    }
}
