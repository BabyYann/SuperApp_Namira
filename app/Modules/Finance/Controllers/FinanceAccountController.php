<?php

namespace App\Modules\Finance\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Finance\Models\FinanceAccount;
use Illuminate\Http\Request;
use Inertia\Inertia;

class FinanceAccountController extends Controller
{
    public function index(Request $request)
    {
        $accounts = FinanceAccount::query()
            ->when($request->search, function ($query, $search) {
                $query->where('bank_name', 'like', "%{$search}%")
                      ->orWhere('account_number', 'like', "%{$search}%")
                      ->orWhere('account_name', 'like', "%{$search}%");
            })
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('Finance/Accounts/Index', [
            'accounts' => $accounts,
            'filters' => $request->only(['search']),
        ]);
    }

    public function store(Request $request)
    {
        if (!auth()->user()->hasAnyRole(['super_admin_yayasan', 'admin_yayasan', 'staff_admin_keuangan', 'finance'])) {
            abort(403, 'Anda tidak memiliki akses untuk menambah rekening.');
        }

        $validated = $request->validate([
            'bank_name' => 'required|string|max:255',
            'account_number' => 'required|string|max:50',
            'account_name' => 'required|string|max:255',
            'is_active' => 'boolean',
        ]);

        FinanceAccount::create($validated);

        return redirect()->back()->with('success', 'Rekening berhasil ditambahkan.');
    }

    public function update(Request $request, FinanceAccount $financeAccount)
    {
        if (!auth()->user()->hasAnyRole(['super_admin_yayasan', 'admin_yayasan', 'staff_admin_keuangan', 'finance'])) {
            abort(403, 'Anda tidak memiliki akses untuk mengubah rekening.');
        }

        $validated = $request->validate([
            'bank_name' => 'required|string|max:255',
            'account_number' => 'required|string|max:50',
            'account_name' => 'required|string|max:255',
            'is_active' => 'boolean',
        ]);

        $financeAccount->update($validated);

        return redirect()->back()->with('success', 'Rekening berhasil diperbarui.');
    }

    public function destroy(FinanceAccount $financeAccount)
    {
        if (!auth()->user()->hasAnyRole(['super_admin_yayasan', 'admin_yayasan', 'staff_admin_keuangan', 'finance'])) {
            abort(403, 'Anda tidak memiliki akses untuk menghapus rekening.');
        }

        $financeAccount->delete();

        return redirect()->back()->with('success', 'Rekening berhasil dihapus.');
    }
}
