<?php

namespace Tests\Unit;

use App\Models\Banner;
use App\Models\ProductoImagen;
use Tests\TestCase;

class GalySharedImageUrlTest extends TestCase
{
    /**
     * En Windows el manejador del archivo recién borrado puede seguir abierto y
     * rmdir falla con "Directory not empty". Son directorios temporales creados por
     * esta prueba, así que se intentan borrar sin hacer fallar el test.
     */
    private function borrarArbol(string $ruta): void
    {
        if (! is_dir($ruta)) {
            return;
        }

        foreach (scandir($ruta) ?: [] as $entrada) {
            if ($entrada === '.' || $entrada === '..') {
                continue;
            }
            $hijo = $ruta.DIRECTORY_SEPARATOR.$entrada;
            is_dir($hijo) ? $this->borrarArbol($hijo) : @unlink($hijo);
        }

        @rmdir($ruta);
    }

    public function test_shared_images_are_served_by_the_store_host_and_not_by_the_local_api_host(): void
    {
        $public = sys_get_temp_dir().DIRECTORY_SEPARATOR.'galy-public-'.uniqid('', true);
        mkdir($public.DIRECTORY_SEPARATOR.'galy_api'.DIRECTORY_SEPARATOR.'img', 0777, true);
        file_put_contents($public.DIRECTORY_SEPARATOR.'galy_api/img/product.png', 'image');
        $this->app->usePublicPath($public);

        config()->set('galy.api_url', 'http://127.0.0.1:9999/galy_api');

        try {
            $url = ProductoImagen::resolveUrl('product.png');

            $this->assertSame(asset('galy_api/img/product.png'), $url);
            $this->assertStringNotContainsString('127.0.0.1:9999', $url);
        } finally {
            $this->borrarArbol($public);
        }
    }

    public function test_the_shared_image_base_follows_the_store_host_when_the_folder_is_local(): void
    {
        $public = sys_get_temp_dir().DIRECTORY_SEPARATOR.'galy-public-'.uniqid('', true);
        mkdir($public.DIRECTORY_SEPARATOR.'galy_api'.DIRECTORY_SEPARATOR.'img', 0777, true);
        $this->app->usePublicPath($public);

        config()->set('galy.public_url', 'http://localhost/galy-admin');

        try {
            $base = ProductoImagen::sharedImagesBaseUrl();

            $this->assertSame(asset('galy_api/img'), $base);
            $this->assertStringNotContainsString('localhost/galy-admin', $base);
        } finally {
            $this->borrarArbol($public);
        }
    }

    public function test_the_shared_image_base_falls_back_to_the_panel_url_without_the_local_folder(): void
    {
        $public = sys_get_temp_dir().DIRECTORY_SEPARATOR.'galy-public-'.uniqid('', true);
        mkdir($public, 0777, true);
        $this->app->usePublicPath($public);

        config()->set('galy.public_url', 'http://localhost/galy-admin');

        try {
            $this->assertSame(
                'http://localhost/galy-admin/img',
                ProductoImagen::sharedImagesBaseUrl()
            );
        } finally {
            $this->borrarArbol($public);
        }
    }

    public function test_the_configured_images_folder_is_used_only_when_it_really_exists(): void
    {
        $carpeta = sys_get_temp_dir().DIRECTORY_SEPARATOR.'galy-images-'.uniqid('', true);
        mkdir($carpeta);

        $this->definirEnv('GALY_IMAGES_PATH', $carpeta);

        try {
            $config = require base_path('config/galy.php');
            $this->assertSame($carpeta, $config['images_path']);

            $this->definirEnv('GALY_IMAGES_PATH', $carpeta.'-inexistente');
            $this->assertSame(
                public_path('galy_api').DIRECTORY_SEPARATOR.'img',
                (require base_path('config/galy.php'))['images_path']
            );
        } finally {
            $this->definirEnv('GALY_IMAGES_PATH', null);
            $this->borrarArbol($carpeta);
        }
    }

    private function definirEnv(string $clave, ?string $valor): void
    {
        if ($valor === null) {
            putenv($clave);
            unset($_ENV[$clave], $_SERVER[$clave]);

            return;
        }

        putenv($clave.'='.$valor);
        $_ENV[$clave] = $valor;
        $_SERVER[$clave] = $valor;
    }

    public function test_banner_image_url_uses_the_galy_api_not_the_admin_panel_url(): void
    {
        config()->set('galy.api_url', 'http://localhost/galy_api');
        config()->set('galy.public_url', 'http://localhost/galy-admin');

        $banner = new Banner(['imagen' => 'banner image.png']);

        $this->assertSame(
            'http://localhost/galy_api/img/banner%20image.png',
            $banner->image_url
        );
    }

    public function test_product_image_url_uses_the_galy_api_not_the_admin_panel_url(): void
    {
        $imagesPath = sys_get_temp_dir().DIRECTORY_SEPARATOR.'galy-images-'.uniqid('', true);
        mkdir($imagesPath);
        file_put_contents($imagesPath.DIRECTORY_SEPARATOR.'product.png', 'image');

        config()->set('galy.api_url', 'http://localhost/galy_api');
        config()->set('galy.public_url', 'http://localhost/galy-admin');
        config()->set('galy.images_path', $imagesPath);

        try {
            $this->assertSame(
                'http://localhost/galy_api/img/product.png',
                ProductoImagen::resolveUrl('product.png')
            );
        } finally {
            $this->borrarArbol($imagesPath);
        }
    }
}
