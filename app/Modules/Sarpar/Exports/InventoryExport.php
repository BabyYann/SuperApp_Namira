<?php

namespace App\Modules\Sarpar\Exports;

use App\Modules\Sarpar\Models\Inventory;
use Illuminate\Http\Request;

class InventoryExport
{
    protected $unitId;
    protected $filters;

    public function __construct($unitId, $filters = [])
    {
        $this->unitId = $unitId;
        $this->filters = $filters;
    }

    public function export()
    {
        $inventories = Inventory::with(['category', 'room', 'classroom'])
            ->where('unit_id', $this->unitId)
            ->when($this->filters['category_id'] ?? null, fn($q, $v) => $q->where('category_id', $v))
            ->when($this->filters['funding_source'] ?? null, fn($q, $v) => $q->where('funding_source', $v))
            ->when($this->filters['item_type'] ?? null, fn($q, $v) => $q->where('item_type', $v))
            ->when($this->filters['status'] ?? null, fn($q, $v) => $q->where('status', $v))
            ->when($this->filters['condition'] ?? null, fn($q, $v) => $q->where('condition', $v))
            ->orderBy('code')
            ->get();

        // Create CSV content
        $headers = [
            'Kode',
            'Nama Barang',
            'Jenis',
            'Kategori',
            'Sumber Dana',
            'Merk',
            'Model',
            'Tahun Perolehan',
            'Jumlah',
            'Harga Satuan',
            'Total Nilai',
            'Kondisi',
            'Status',
            'Lokasi',
            'Catatan',
        ];

        $rows = [];
        foreach ($inventories as $item) {
            $rows[] = [
                $item->code,
                $item->name,
                $item->item_type === 'asset' ? 'Aset Tetap' : 'Habis Pakai',
                $item->category?->name ?? '-',
                $item->funding_source === 'BOS' ? 'Dana BOS' : 'Dana Yayasan',
                $item->brand ?? '-',
                $item->model ?? '-',
                $item->year_acquired,
                $item->quantity,
                $item->unit_price ?? 0,
                ($item->quantity * ($item->unit_price ?? 0)),
                $item->condition_label,
                $item->status_label,
                $item->location_name,
                $item->notes ?? '-',
            ];
        }

        return [
            'headers' => $headers,
            'rows' => $rows,
            'count' => count($rows),
        ];
    }

    public function toCsv()
    {
        $data = $this->export();
        
        $output = fopen('php://temp', 'r+');
        
        // Add BOM for Excel UTF-8 compatibility
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
        
        // Headers
        fputcsv($output, $data['headers']);
        
        // Rows
        foreach ($data['rows'] as $row) {
            fputcsv($output, $row);
        }
        
        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);
        
        return $csv;
    }
}
