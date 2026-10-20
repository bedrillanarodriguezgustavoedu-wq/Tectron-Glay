<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SeguridadWebTest extends TestCase
{
    use RefreshDatabase;
    public function test_every_web_response_carries_the_safety_headers(): void
    {
        $respuesta = $this->get('/')->assertOk();

        $respuesta->assertHeader('X-Content-Type-Options', 'nosniff');
        $respuesta->assertHeader('X-Frame-Options', 'SAMEORIGIN');
        $respuesta->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
        $respuesta->assertSee('<meta name="robots" content="index, follow">', false);
    }

    public function test_failure_pages_explain_the_problem_without_leaking_details(): void
    {
        $mensajes = [419 => 'caducó', 429 => 'intentos', 500 => 'nuestro lado', 503 => 'mantenimiento'];

        foreach ($mensajes as $codigo => $palabra) {
            $html = view("errors.$codigo")->render();

            $this->assertStringContainsString((string) $codigo, $html);
            $this->assertStringContainsString($palabra, $html);
            $this->assertStringContainsString('noindex', $html);
            $this->assertStringNotContainsString('SQLSTATE', $html);
        }
    }
}
