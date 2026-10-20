<?php

namespace Tests\Unit;

use App\Models\Banner;
use App\Models\ProductoImagen;
use Tests\TestCase;

class GalySharedImageUrlTest extends TestCase
{
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
            unlink($public.DIRECTORY_SEPARATOR.'galy_api/img/product.png');
            rmdir($public.DIRECTORY_SEPARATOR.'galy_api'.DIRECTORY_SEPARATOR.'img');
            rmdir($public.DIRECTORY_SEPARATOR.'galy_api');
            rmdir($public);
        }
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
            unlink($imagesPath.DIRECTORY_SEPARATOR.'product.png');
            rmdir($imagesPath);
        }
    }
}
