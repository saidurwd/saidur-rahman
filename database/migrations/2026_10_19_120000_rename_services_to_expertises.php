<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::rename('services', 'expertises');

        DB::table('activity_log')->where('subject_type', 'App\\Models\\Service')->update(['subject_type' => 'App\\Models\\Expertise']);
        DB::table('menus')->where('url', '/services')->update(['url' => '/expertise']);
        DB::table('menus')->where('route', 'services.index')->update(['route' => 'expertise.index']);
        $this->moveRedirects('/services/', '/expertise/');
    }

    public function down(): void
    {
        $this->moveRedirects('/expertise/', '/services/');
        DB::table('menus')->where('route', 'expertise.index')->update(['route' => 'services.index']);
        DB::table('menus')->where('url', '/expertise')->update(['url' => '/services']);
        DB::table('activity_log')->where('subject_type', 'App\\Models\\Expertise')->update(['subject_type' => 'App\\Models\\Service']);

        Schema::rename('expertises', 'services');
    }

    private function moveRedirects(string $from, string $to): void
    {
        foreach (DB::table('redirects')->where('from_path', 'like', $from.'%')->orWhere('to_path', 'like', $from.'%')->get() as $row) {
            DB::table('redirects')->where('id', $row->id)->update([
                'from_path' => str_starts_with($row->from_path, $from) ? $to.substr($row->from_path, strlen($from)) : $row->from_path,
                'to_path' => str_starts_with($row->to_path, $from) ? $to.substr($row->to_path, strlen($from)) : $row->to_path,
            ]);
        }
    }
};
