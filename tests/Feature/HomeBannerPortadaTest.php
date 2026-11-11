<?php

namespace Tests\Feature;

use App\Models\Banner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HomeBannerPortadaTest extends TestCase
{
    use RefreshDatabase;

    public function test_portada_banner_is_shown_in_the_welcome_hero_and_not_repeated_in_the_carousel(): void
    {
        Banner::create([
            'imagen' => 'assets/img/banner-bienvenida.jpg',
            'titulo' => 'Bienvenida Portada',
            'subtitulo' => 'Subtitulo de portada',
            'boton_texto' => 'Comprar ahora',
            'enlace' => '/productos',
            'activo' => true,
            'portada' => true,
            'orden' => 0,
        ]);

        $response = $this->get(route('web.index'));

        $response->assertOk()
            ->assertSee('Bienvenida Portada')
            ->assertSee('Subtitulo de portada')
            ->assertSee('assets/img/banner-bienvenida.jpg');

        // La portada se muestra en el hero (una imagen) y no se repite en el carrusel.
        $this->assertSame(1, substr_count($response->getContent(), 'alt="Bienvenida Portada"'));
    }

    public function test_regular_banner_stays_in_the_carousel_when_another_banner_is_the_portada(): void
    {
        Banner::create([
            'imagen' => 'assets/img/banner-bienvenida.jpg',
            'titulo' => 'Portada Principal',
            'activo' => true,
            'portada' => true,
            'orden' => 0,
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
            ->assertSee('Portada Principal')
            ->assertSee('Banner Normal Carrusel');
    }
}
