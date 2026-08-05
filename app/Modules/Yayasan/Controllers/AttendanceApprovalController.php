<?php

namespace App\Modules\Yayasan\Controllers;

use App\Http\Controllers\Controller;
use App\Models\EmployeeAttendance;
use App\Services\NotificationDispatcher;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class AttendanceApprovalController extends Controller
{
    public function index(Request $request)
    {
        if (!auth()->user()->hasAnyRole(['super_admin_yayasan', 'admin_yayasan', 'admin_unit', 'pembina_yayasan', 'pengawas_yayasan', 'kepala_sekolah'])) {
            abort(403, 'Akses Ditolak: Anda tidak memiliki wewenang untuk melihat persetujuan absensi.');
        }

        $unitId = session('active_unit_id');
        if ($unitId === 'all') {
            $unitId = null;
        }

        // Force unit restriction for non-global admins
        if (!auth()->user()->hasAnyRole(['super_admin_yayasan', 'admin_yayasan', 'pembina_yayasan', 'pengawas_yayasan'])) {
            $unitId = session('active_unit_id');
        }

        $tab = $request->input('status', 'pending'); // 'pending' or 'history'

        // Reusable unit scope closure
        $applyUnitScope = function($q) use ($unitId) {
            if ($unitId) {
                $roleUserIds = \DB::table('model_has_roles')->where('team_id', $unitId)->pluck('model_id');
                $teacherUserIds = \App\Modules\Academic\Models\Teacher::where('unit_id', $unitId)->pluck('user_id');
                $staffUserIds = \App\Modules\Employee\Models\Staff::where('unit_id', $unitId)->pluck('user_id');
                
                $unitUserIds = $roleUserIds->concat($teacherUserIds)->concat($staffUserIds)->unique()->filter();
                
                $q->whereIn('user_id', $unitUserIds);
            }
        };

        // Count pending for badge
        $pendingQuery = EmployeeAttendance::where('approval_status', 'pending');
        $applyUnitScope($pendingQuery);
        $pendingCount = $pendingQuery->count();

        // Base approvals query for table
        $query = EmployeeAttendance::with(['user', 'approver', 'location']);

        if ($tab === 'history') {
            $query->whereIn('approval_status', ['approved', 'rejected']);
        } else {
            $query->where('approval_status', 'pending');
        }

        $applyUnitScope($query);

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->whereHas('user', function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%");
            });
        }

        $approvals = $query->latest('date')->get();

        return Inertia::render('Yayasan/Attendance/Approval', [
            'approvals' => $approvals,
            'pendingCount' => $pendingCount,
            'activeTab' => $tab,
            'filters' => [
                'search' => $request->input('search', ''),
                'status' => $tab,
            ],
        ]);
    }

    public function update(Request $request, EmployeeAttendance $attendance)
    {
        if (!auth()->user()->hasAnyRole(['super_admin_yayasan', 'admin_yayasan', 'kepala_sekolah'])) {
            abort(403, 'Akses Ditolak: Hanya Kepala Sekolah yang memiliki wewenang untuk memproses persetujuan absensi.');
        }

        if (!auth()->user()->hasAnyRole(['super_admin_yayasan', 'admin_yayasan'])) {
            $employeeUnitId = \DB::table('model_has_roles')
                ->where('model_id', $attendance->user_id)
                ->whereNotNull('team_id')
                ->value('team_id');
            if ($employeeUnitId != session('active_unit_id')) {
                abort(403, 'Akses Ditolak: Karyawan ini bukan dari unit Anda.');
            }
        }

        $request->validate([
            'status' => 'required|in:approved,rejected',
            'reason' => 'required_if:status,rejected|nullable|string',
        ]);

        $attendance->update([
            'approval_status' => $request->status,
            'approved_by' => Auth::id(),
            'rejection_reason' => $request->status === 'rejected' ? $request->reason : null,
        ]);

        // Send feedback notification to the employee
        if ($attendance->user) {
            $dateFormatted = Carbon::parse($attendance->date)->translatedFormat('d F Y');
            $statusLabel = [
                'business_trip' => 'Dinas Luar',
                'sick'          => 'Sakit',
                'permit'        => 'Izin',
            ][$attendance->status] ?? $attendance->status;

            if ($request->status === 'approved') {
                NotificationDispatcher::sendToUser(
                    $attendance->user,
                    '✅ Pengajuan Absensi Disetujui',
                    "Pengajuan absensi {$statusLabel} Anda pada tanggal {$dateFormatted} telah disetujui.",
                    'employee',
                    ['attendance_id' => $attendance->id]
                );
            } else {
                $reasonText = $request->reason ? " Catatan: {$request->reason}" : '';
                NotificationDispatcher::sendToUser(
                    $attendance->user,
                    '❌ Pengajuan Absensi Ditolak',
                    "Pengajuan absensi {$statusLabel} Anda pada tanggal {$dateFormatted} tidak disetujui.{$reasonText}",
                    'employee',
                    ['attendance_id' => $attendance->id]
                );
            }
        }

        return redirect()->back()->with('success', 'Status pengajuan diperbarui.');
    }
}
