<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('spmb_settings') || !Schema::hasColumn('spmb_settings', 'whatsapp_group_link')) {
            return;
        }

        // Cari unit SD Namira Kraksaan (code = 'SD' dan bukan SD-02 / Kota)
        $sdKraksaanUnitIds = DB::table('units')
            ->where(function ($q) {
                $q->where('code', 'SD')
                  ->orWhere(function ($sq) {
                      $sq->where('level', 'SD')
                         ->where('code', '!=', 'SD-02')
                         ->whereRaw('LOWER(name) NOT LIKE ?', ['%kota%']);
                  });
            })
            ->pluck('id');

        if ($sdKraksaanUnitIds->isNotEmpty()) {
            DB::table('spmb_settings')
                ->whereIn('unit_id', $sdKraksaanUnitIds)
                ->where(function ($q) {
                    $q->whereNull('whatsapp_group_link')
                      ->orWhere('whatsapp_group_link', '');
                })
                ->update([
                    'whatsapp_group_link' => 'https://chat.whatsapp.com/IicQlBFKBdD8UY5ma0Dzi8',
                    'updated_at' => now(),
                ]);
        }
    }

    public function down(): void
    {
        // Keep user-configured setting intact on rollback
    }
};
