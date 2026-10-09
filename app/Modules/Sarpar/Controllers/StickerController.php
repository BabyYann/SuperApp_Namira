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
        $user = auth()->user();
        $isGlobalAdmin = $user && $user->hasAnyRole(['super_admin_yayasan', 'admin_yayasan', 'pembina_yayasan', 'pengawas_yayasan', 'staff_yayasan']);
        $unitId = $isGlobalAdmin ? (request('unit_id') ?: session('active_unit_id')) : (session('active_unit_id') ?: ($user?->unit_id ?: $user?->teacher_profile?->unit_id));
        if (!$unitId && $isGlobalAdmin) {
            $unitId = \App\Modules\Yayasan\Models\Unit::first()?->id;
        }

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
