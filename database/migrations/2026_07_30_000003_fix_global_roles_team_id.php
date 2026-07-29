<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Global roles should always have team_id = NULL in model_has_roles
        // so Spatie Permission evaluates them globally across all units.
        $globalRoles = [
            'super_admin_yayasan',
            'admin_yayasan',
            'staff_yayasan',
            'pengawas_yayasan',
            'humas_yayasan',
        ];

        $globalRoleIds = DB::table('roles')
            ->whereIn('name', $globalRoles)
            ->pluck('id');

        if ($globalRoleIds->isNotEmpty()) {
            DB::table('model_has_roles')
                ->whereIn('role_id', $globalRoleIds)
                ->update(['team_id' => null]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No reverse needed
    }
};
