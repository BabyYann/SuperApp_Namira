<?php

namespace App\Modules\Sarpar\Services;

use App\Modules\Sarpar\Models\Inventory;
use App\Modules\Sarpar\Models\Category;
use App\Modules\Yayasan\Models\Unit;

class InventoryCodeGenerator
{
    /**
     * Generate unique inventory code
     * Format: [SOURCE]-[UNIT_CODE]-[CATEGORY_CODE]-[YEAR]-[SEQUENCE]
     * Example: BOS-SD01-ELK-2024-001
     */
    public static function generate(Unit $unit, Category $category, string $source, int $year): string
    {
        $prefix = strtoupper($source); // BOS or YYS
        $unitCode = strtoupper($unit->code ?? 'UNK');
        $catCode = strtoupper($category->code ?? 'OTH');
        
        // Find last code in this sequence
        $lastInventory = Inventory::where('unit_id', $unit->id)
            ->where('funding_source', $source)
            ->where('category_id', $category->id)
            ->where('year_acquired', $year)
            ->orderByDesc('id')
            ->first();
            
        // Calculate next sequence number
        if ($lastInventory) {
            $parts = explode('-', $lastInventory->code);
            $lastSequence = (int) end($parts);
            $sequence = $lastSequence + 1;
        } else {
            $sequence = 1;
        }
        
        return sprintf(
            '%s-%s-%s-%d-%03d',
            $prefix,
            $unitCode,
            $catCode,
            $year,
            $sequence
        );
    }
}
