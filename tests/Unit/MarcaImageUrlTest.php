<?php

namespace Tests\Unit;

use App\Models\Marca;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MarcaImageUrlTest extends TestCase
{
    public function test_image_url_is_returned_only_when_the_public_logo_exists(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('marcas/logo.png', 'logo');

        $marca = new Marca(['nombre' => 'MSI', 'imagen' => 'marcas/logo.png']);

        $this->assertSame(asset('storage/marcas/logo.png'), $marca->image_url);
    }

    public function test_image_url_is_null_when_logo_path_is_missing_or_empty(): void
    {
        Storage::fake('public');

        $missingLogo = new Marca(['nombre' => 'ASUS', 'imagen' => 'marcas/asus.webp']);
        $noLogo = new Marca(['nombre' => 'Apple', 'imagen' => null]);

        $this->assertNull($missingLogo->image_url);
        $this->assertNull($noLogo->image_url);
    }
}
