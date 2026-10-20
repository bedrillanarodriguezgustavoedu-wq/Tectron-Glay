<?php

namespace Tests\Feature;

use App\Models\Contacto;
use App\Models\FooterSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FooterSocialLinksTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Contacto::firstOrFail()->update([
            'whatsapp' => null,
            'facebook_link' => null,
            'instagram_link' => null,
            'youtube_link' => null,
            'twitter_link' => null,
        ]);
    }

    public function test_placeholder_social_links_are_not_rendered_as_working_links(): void
    {
        $response = $this->get(route('web.index'));

        $response->assertOk()
            ->assertDontSee('aria-label="Facebook"')
            ->assertDontSee('aria-label="Instagram"')
            ->assertDontSee('aria-label="TikTok"')
            ->assertDontSee('aria-label="Twitter"')
            ->assertDontSee('aria-label="YouTube"')
            ->assertDontSee('href="#" target="_blank"');
    }

    public function test_configured_social_profile_is_rendered_as_a_safe_external_link(): void
    {
        FooterSettings::firstOrFail()->update([
            'facebook_link' => 'https://www.facebook.com/old-footer-profile',
        ]);
        Contacto::firstOrFail()->update([
            'facebook_link' => 'https://www.facebook.com/tectrongaly',
        ]);

        $this->get(route('web.index'))
            ->assertOk()
            ->assertSee('href="https://www.facebook.com/tectrongaly" target="_blank" rel="noopener noreferrer" aria-label="Facebook"', false)
            ->assertDontSee('https://www.facebook.com/old-footer-profile');
    }
}
