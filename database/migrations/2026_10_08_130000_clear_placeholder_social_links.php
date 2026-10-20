<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        foreach ([
            'facebook_link',
            'instagram_link',
            'tiktok_link',
            'twitter_link',
            'youtube_link',
            'linkedin_link',
            'whatsapp_link',
        ] as $column) {
            DB::table('footer_settings')
                ->where($column, '#')
                ->update([$column => null]);
        }
    }

    public function down(): void
    {
        //
    }
};
