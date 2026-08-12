<?php

namespace App\Modules\Sarpar\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Sarpar\Models\Inventory;
use App\Modules\Sarpar\Services\QrCodeService;
use Illuminate\Http\Request;

class StickerController extends Controller
{
    public function printStickers(Request $request)
    {
        $unitId = session('active_unit_id');
        $ids = $request->input('ids');

        $query = Inventory::with(['unit', 'category', 'room', 'classroom'])
            ->where('unit_id', $unitId);

        if ($ids && $ids !== 'all') {
            $idArray = explode(',', $ids);
            $query->whereIn('id', $idArray);
        }

        $inventories = $query->orderBy('name')->get();

        $items = $inventories->map(function ($item) {
            $qrUrl = route('sarpar.inventories.show', $item->id);
            $item->qr_svg = QrCodeService::generateSvg($qrUrl, 75);
            return $item;
        });

        return view('sarpar.stickers', [
            'items' => $items,
            'unitName' => session('active_unit_name', 'YAYASAN NAMIRA'),
        ]);
    }
}
