<?php

namespace App\Modules\Yayasan\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Yayasan\Models\Holiday;
use App\Modules\Yayasan\Models\Unit;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Carbon\Carbon;

class HolidayController extends Controller
{
    // Event type labels
    public static array $eventTypes = [
        'libur' => 'Hari Libur',
        'ujian' => 'Ujian',
        'event' => 'Event/Acara',
        'rapat' => 'Rapat',
    ];

    public function index(Request $request)
    {
        $year = $request->input('year', Carbon::now()->year);
        $month = $request->input('month'); // Optional month filter
        $unitId = session('active_unit_id');
        $units = Unit::all();

        $query = Holiday::with('unit')
            ->whereYear('date', $year)
            // Show global events (unit_id = null) OR events for this unit
            ->where(function ($q) use ($unitId) {
                $q->whereNull('unit_id')
                  ->orWhere('unit_id', $unitId);
            })
            ->orderBy('date', 'asc');

        if ($month) {
            $query->whereMonth('date', $month);
        }

        $holidays = $query->get()->map(function ($h) {
            $h->display_color = $h->displayColor;
            return $h;
        });

        return Inertia::render('Yayasan/Holiday/Index', [
            'holidays' => $holidays,
            'units' => $units,
            'eventTypes' => self::$eventTypes,
            'activeUnitId' => $unitId,
            'filters' => [
                'year' => (int)$year,
                'month' => $month ? (int)$month : null,
            ],
        ]);
    }

    public function store(Request $request)
    {
        if (!auth()->user()->hasAnyRole(['super_admin_yayasan', 'admin_yayasan', 'admin_unit', 'staff_yayasan', 'staff_unit'])) {
            abort(403, 'Akses Ditolak: Anda tidak memiliki wewenang untuk mengelola kalender akademik.');
        }

        $request->validate([
            'date' => 'required|date',
            'description' => 'required|string|max:255',
            'unit_id' => 'nullable|exists:units,id',
            'is_recurring' => 'boolean',
            'event_type' => 'required|in:libur,ujian,event,rapat',
            'color' => 'nullable|string|max:7',
            'start_time' => 'nullable|date_format:H:i',
            'end_time' => 'nullable|date_format:H:i|after:start_time',
        ]);

        // Non-global admin cannot create global events or events for other units
        if (!auth()->user()->hasAnyRole(['super_admin_yayasan', 'admin_yayasan'])) {
            $targetUnitId = $request->input('unit_id');
            if ($targetUnitId === null || $targetUnitId != session('active_unit_id')) {
                abort(403, 'Akses Ditolak: Anda hanya dapat menambahkan event untuk unit Anda.');
            }
        }

        Holiday::create($request->all());

        return redirect()->back()->with('success', 'Event berhasil ditambahkan.');
    }

    public function update(Request $request, Holiday $holiday)
    {
        if (!auth()->user()->hasAnyRole(['super_admin_yayasan', 'admin_yayasan', 'admin_unit', 'staff_yayasan', 'staff_unit'])) {
            abort(403, 'Akses Ditolak: Anda tidak memiliki wewenang untuk mengelola kalender akademik.');
        }

        if (!auth()->user()->hasAnyRole(['super_admin_yayasan', 'admin_yayasan'])) {
            if ($holiday->unit_id === null || $holiday->unit_id != session('active_unit_id')) {
                abort(403, 'Akses Ditolak: Anda tidak dapat mengubah event global atau event unit lain.');
            }
            if ($request->has('unit_id')) {
                $targetUnitId = $request->input('unit_id');
                if ($targetUnitId === null || $targetUnitId != session('active_unit_id')) {
                    abort(403, 'Akses Ditolak: Anda hanya dapat menetapkan event untuk unit Anda.');
                }
            }
        }

        $request->validate([
            'date' => 'required|date',
            'description' => 'required|string|max:255',
            'unit_id' => 'nullable|exists:units,id',
            'is_recurring' => 'boolean',
            'event_type' => 'required|in:libur,ujian,event,rapat',
            'color' => 'nullable|string|max:7',
            'start_time' => 'nullable|date_format:H:i',
            'end_time' => 'nullable|date_format:H:i',
        ]);

        $holiday->update($request->all());

        return redirect()->back()->with('success', 'Event berhasil diperbarui.');
    }

    public function destroy(Holiday $holiday)
    {
        if (!auth()->user()->hasAnyRole(['super_admin_yayasan', 'admin_yayasan', 'admin_unit', 'staff_yayasan', 'staff_unit'])) {
            abort(403, 'Akses Ditolak: Anda tidak memiliki wewenang untuk menghapus event kalender.');
        }

        if (!auth()->user()->hasAnyRole(['super_admin_yayasan', 'admin_yayasan'])) {
            if ($holiday->unit_id === null || $holiday->unit_id != session('active_unit_id')) {
                abort(403, 'Akses Ditolak: Anda tidak dapat menghapus event global atau event unit lain.');
            }
        }

        $holiday->delete();
        return redirect()->back()->with('success', 'Event dihapus.');
    }

    /**
     * Export calendar events to iCal format (.ics)
     */
    public function exportIcal(Request $request)
    {
        $year = $request->input('year', Carbon::now()->year);
        $unitId = session('active_unit_id');
        
        $holidays = Holiday::whereYear('date', $year)
            ->where(function ($q) use ($unitId) {
                $q->whereNull('unit_id')
                  ->orWhere('unit_id', $unitId);
            })
            ->orderBy('date', 'asc')
            ->get();

        $unit = Unit::find($unitId);
        $unitName = $unit ? $unit->name : 'Yayasan Namira';

        // Build iCal content
        $ical = "BEGIN:VCALENDAR\r\n";
        $ical .= "VERSION:2.0\r\n";
        $ical .= "PRODID:-//Yayasan Namira SuperApp//NONSGML Calendar//ID\r\n";
        $ical .= "CALSCALE:GREGORIAN\r\n";
        $ical .= "METHOD:PUBLISH\r\n";
        $ical .= "X-WR-CALNAME:Kalender " . $unitName . " " . $year . "\r\n";
        $ical .= "X-WR-TIMEZONE:Asia/Jakarta\r\n";

        foreach ($holidays as $event) {
            $uid = 'event-' . $event->id . '@namira.sch.id';
            $dtstart = Carbon::parse($event->date)->format('Ymd');
            $dtend = Carbon::parse($event->date)->addDay()->format('Ymd');
            $created = Carbon::parse($event->created_at)->format('Ymd\THis\Z');
            $summary = $this->escapeIcal($event->description);
            $category = self::$eventTypes[$event->event_type] ?? $event->event_type;

            $ical .= "BEGIN:VEVENT\r\n";
            $ical .= "UID:" . $uid . "\r\n";
            $ical .= "DTSTAMP:" . $created . "\r\n";
            $ical .= "DTSTART;VALUE=DATE:" . $dtstart . "\r\n";
            $ical .= "DTEND;VALUE=DATE:" . $dtend . "\r\n";
            $ical .= "SUMMARY:" . $summary . "\r\n";
            $ical .= "CATEGORIES:" . $category . "\r\n";
            
            if ($event->start_time) {
                $ical .= "X-START-TIME:" . $event->start_time . "\r\n";
            }
            if ($event->end_time) {
                $ical .= "X-END-TIME:" . $event->end_time . "\r\n";
            }
            
            $ical .= "END:VEVENT\r\n";
        }

        $ical .= "END:VCALENDAR\r\n";

        $filename = 'Kalender_' . preg_replace('/[^a-zA-Z0-9]/', '_', $unitName) . '_' . $year . '.ics';

        return response($ical, 200)
            ->header('Content-Type', 'text/calendar; charset=utf-8')
            ->header('Content-Disposition', 'attachment; filename="' . $filename . '"');
    }

    private function escapeIcal($string)
    {
        return str_replace(["\n", "\r", ";", ","], ["\\n", "", "\\;", "\\,"], $string);
    }
}
