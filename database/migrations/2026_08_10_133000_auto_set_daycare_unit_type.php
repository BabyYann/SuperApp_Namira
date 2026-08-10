<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $daycareFeatures = json_encode([
            'academic' => false,
            'daycare' => true,
            'finance' => true,
            'sarpar' => true,
            'counseling' => false,
            'public_relations' => true,
        ]);

        DB::table('units')
            ->where(function ($query) {
                $query->where('name', 'LIKE', '%daycare%')
                    ->orWhere('name', 'LIKE', '%day care%')
                    ->orWhere('name', 'LIKE', '%DAY CARE%')
                    ->orWhere('name', 'LIKE', '%DAYCARE%')
                    ->orWhere('name', 'LIKE', '%pavlov%')
                    ->orWhere('name', 'LIKE', '%Pavlov%')
                    ->orWhere('category', 'LIKE', '%daycare%')
                    ->orWhere('category', 'LIKE', '%day care%');
            })
            ->update([
                'unit_type' => 'daycare',
                'features' => $daycareFeatures,
            ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No action needed
    }
};
