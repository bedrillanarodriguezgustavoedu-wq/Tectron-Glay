<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        if (app()->environment('testing')) {
            return;
        }

        $schema = Schema::connection('galy');
        if (! $schema->hasTable('categorias') || ! $schema->hasColumn('categorias', 'slug')) {
            return;
        }

        $categories = DB::connection('galy')
            ->table('categorias')
            ->select('id', 'nombre', 'slug')
            ->orderBy('id')
            ->get();
        $usedSlugs = $categories
            ->pluck('slug')
            ->filter()
            ->map(fn (string $slug): string => mb_strtolower($slug))
            ->all();

        foreach ($categories as $category) {
            if (is_string($category->slug) && trim($category->slug) !== '') {
                continue;
            }

            $baseSlug = Str::slug($category->nombre ?: 'categoria');
            $baseSlug = $baseSlug !== '' ? $baseSlug : 'categoria';
            $slug = $baseSlug;
            $suffix = 1;

            while (in_array(mb_strtolower($slug), $usedSlugs, true)) {
                $slug = "{$baseSlug}-{$suffix}";
                $suffix++;
            }

            DB::connection('galy')
                ->table('categorias')
                ->where('id', $category->id)
                ->update(['slug' => $slug]);

            $usedSlugs[] = mb_strtolower($slug);
        }
    }

    public function down(): void
    {
        // This data migration intentionally preserves generated category slugs.
    }
};
