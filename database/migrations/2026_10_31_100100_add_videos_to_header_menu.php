<?php

use App\Services\CacheService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/** Puts "Videos" in the top navigation of a site that already has one. Fresh installs get it from the seeder. */
return new class extends Migration
{
    public function up(): void
    {
        $header = DB::table('menus')->where('location', 'header');

        if (! $header->exists() || DB::table('menus')->where('url', '/videos')->exists()) {
            return;
        }

        // Sits just before "Contact" when that link exists, so Contact stays last.
        $contact = DB::table('menus')->where('location', 'header')->where('url', '/contact')->first();
        $order = $contact ? (int) $contact->sort_order : (int) $header->max('sort_order') + 1;

        if ($contact) {
            DB::table('menus')->where('location', 'header')->where('sort_order', '>=', $order)->increment('sort_order');
        }

        DB::table('menus')->insert([
            'label' => 'Videos', 'url' => '/videos', 'route' => null, 'location' => 'header',
            'sort_order' => $order, 'is_published' => true, 'created_at' => now(), 'updated_at' => now(),
        ]);

        app(CacheService::class)->flush();
    }

    public function down(): void
    {
        DB::table('menus')->where('location', 'header')->where('url', '/videos')->delete();
    }
};
