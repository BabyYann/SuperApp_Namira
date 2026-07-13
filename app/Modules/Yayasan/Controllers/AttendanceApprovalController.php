<?php

namespace App\Modules\Yayasan\Controllers;

use App\Http\Controllers\Controller;
use App\Models\EmployeeAttendance;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;

class AttendanceApprovalController extends Controller
{
    public function index()
    {
        if (!auth()->user()->hasAnyRole(['super_admin_yayasan', 'admin_yayasan', 'admin_unit', 'staff_yayasan', 'staff_unit'])) {
            abort(403, 'Akses Ditolak: Anda tidak memiliki wewenang untuk mengakses persetujuan absensi.');
        }

        $unitId = session('active_unit_id');

        $query = EmployeeAttendance::with('user')
            ->where('approval_status', 'pending');

        if (!auth()->user()->hasAnyRole(['super_admin_yayasan', 'admin_yayasan'])) {
            $query->whereHas('user', function ($q) use ($unitId) {
                $q->whereHas('roles', function ($sub) use ($unitId) {
                     $sub->whereRaw("model_has_roles.model_id = users.id AND model_has_roles.team_id = ?", [$unitId]);
                });
            });
        } else {
            if ($unitId) {
                $query->whereHas('user', function ($q) use ($unitId) {
                    $q->whereHas('roles', function ($sub) use ($unitId) {
                         $sub->whereRaw("model_has_roles.model_id = users.id AND model_has_roles.team_id = ?", [$unitId]);
                    });
                });
            }
        }

        $approvals = $query->latest('date')->get();

        return Inertia::render('Yayasan/Attendance/Approval', [
            'approvals' => $approvals,
        ]);
    }

    public function update(Request $request, EmployeeAttendance $attendance)
    {
        if (!auth()->user()->hasAnyRole(['super_admin_yayasan', 'admin_yayasan', 'admin_unit', 'staff_yayasan', 'staff_unit'])) {
            abort(403, 'Akses Ditolak: Anda tidak memiliki wewenang untuk memproses persetujuan absensi.');
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
            // If rejected, does 'status' column change? 
            // Maybe status remains 'sick'/'permit' but approval is 'rejected'.
            // OR status changes to 'alpha' or 'rejected'?
            // Start simple: Keep original status, just mark approval. 
            // Display logic will showing 'Rejected'.
        ]);

        return redirect()->back()->with('success', 'Status pengajuan diperbarui.');
    }
}
