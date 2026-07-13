<?php

namespace App\Modules\Academic\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Academic\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;

class StudentController extends Controller
{
    public function index()
    {
        $unitId = session('active_unit_id');
        $activeYear = \App\Modules\Yayasan\Models\AcademicYear::where('is_active', true)->first();
        
        $students = Student::with(['user', 'classroom', 'academicYear'])
            ->where('unit_id', $unitId)
            ->when(request('search'), function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('full_name', 'like', "%{$search}%")
                      ->orWhere('nis', 'like', "%{$search}%")
                      ->orWhere('nisn', 'like', "%{$search}%");
                });
            })
            ->when(request('gender'), function ($query, $gender) {
                $query->where('gender', $gender);
            })
            ->when(request('classroom_id'), function ($query, $classId) {
                $query->where('classroom_id', $classId);
            })
            ->when(request('academic_year_id'), function ($query, $yearId) {
                $query->where('academic_year_id', $yearId);
            })
            ->latest()
            ->paginate(30)
            ->withQueryString();

        // Classrooms are now permanent - no year filter needed
        $classrooms = \App\Modules\Academic\Models\Classroom::where('unit_id', $unitId)
             ->orderBy('level')
             ->orderBy('name')
             ->get();
        
        $academicYears = \App\Modules\Yayasan\Models\AcademicYear::orderBy('id', 'desc')->get();

        return Inertia::render('Academic/Students/Index', [
            'students' => $students,
            'classrooms' => $classrooms,
            'academicYears' => $academicYears,
            'activeYear' => $activeYear,
            'filters' => request()->only(['search', 'gender', 'classroom_id', 'academic_year_id']),
        ]);
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,txt|max:2048',
        ]);

        $unitId = session('active_unit_id') ?? \App\Modules\Yayasan\Models\Unit::first()->id;
        $file = $request->file('file');
        $handle = fopen($file->getPathname(), 'r');
        
        // Read and validate header
        $header = fgetcsv($handle);
        if (!$header) {
            fclose($handle);
            return redirect()->back()->with('error', 'File CSV kosong.');
        }

        $headerText = strtolower(implode(' ', $header));
        if (!str_contains($headerText, 'nama') && !str_contains($headerText, 'name') && !str_contains($headerText, 'nis')) {
            fclose($handle);
            return redirect()->back()->with('error', 'Format header CSV tidak valid. Pastikan terdapat kolom Nama, Email, dan NIS.');
        }

        $count = 0;
        \DB::transaction(function () use ($handle, $unitId, &$count) {
            while (($row = fgetcsv($handle)) !== false) {
                // Expected CSV Format: Name, Email, NIS, Gender (L/P)
                // Adjust index based on CSV structure. Assuming: 0=Name, 1=Email, 2=NIS, 3=Gender
                if (count($row) < 4) continue;

                $name = $row[0];
                $email = $row[1];
                $nis = $row[2];
                $gender = strtoupper($row[3]);

                // Basic Validation
                if (!$email || User::where('email', $email)->exists()) continue;

                // Create User
                $password = $nis ? $nis : 'siswa123';
                $user = User::create([
                    'name' => $name,
                    'email' => $email,
                    'password' => Hash::make($password),
                ]);

                setPermissionsTeamId($unitId);
                $user->assignRole('siswa');

                // Create Student Profile
                Student::create([
                    'user_id' => $user->id,
                    'unit_id' => $unitId,
                    'full_name' => $name,
                    'nis' => $nis,
                    'gender' => in_array($gender, ['L', 'P']) ? $gender : 'L',
                ]);
                
                $count++;
            }
        });

        fclose($handle);

        return redirect()->back()->with('success', "$count Siswa berhasil diimport.");
    }

    public function importVa(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,txt|max:2048',
        ]);

        $unitId = session('active_unit_id');
        $file = $request->file('file');
        $handle = fopen($file->getPathname(), 'r');
        
        // Skip header if present (Assuming row 1 is header)
        // Heuristic: Check if first row contains "NIS" or "VA"
        $firstRow = fgetcsv($handle);
        if ($firstRow && !is_numeric($firstRow[0])) {
            // It's likely a header, do nothing (we already consumed it)
        } else {
            // It's data, rewind so we don't miss it
            rewind($handle);
        }

        $count = 0;
        $failed = 0;

        DB::transaction(function () use ($handle, $unitId, &$count) {
            while (($row = fgetcsv($handle)) !== false) {
                // Expected CSV Format: NIS, VA Number
                if (count($row) < 2) continue;

                $nis = trim($row[0]);
                $vaNumber = trim($row[1]);

                if (empty($nis) || empty($vaNumber)) continue;

                $student = Student::where('unit_id', $unitId)
                    ->where('nis', $nis)
                    ->first();

                if ($student) {
                    $student->update(['va_number' => $vaNumber]);
                    $count++;
                }
            }
        });

        fclose($handle);

        return redirect()->back()->with('success', "$count data VA Siswa berhasil diperbarui.");
    }

    public function show(Student $student)
    {
        if (
            !auth()->user()->hasAnyRole(['super_admin_yayasan', 'admin_yayasan'])
            && $student->unit_id != session('active_unit_id')
        ) {
            abort(403, 'Akses Ditolak: Anda tidak memiliki akses untuk data siswa di unit lain.');
        }

        $student->load([
            'user', 
            'classroom', 
            'academicYear',
            'unit',
            'bills' => function ($q) {
                $q->with('financeType')->whereIn('status', ['unpaid', 'partial'])->orderBy('due_date');
            },
            'transactions' => function ($q) {
                $q->latest('transaction_date')->take(10);
            }
        ]);
        
        // Calculate Total Arrears
        $student->total_arrears = $student->bills->sum(function($bill) {
             return $bill->final_amount - $bill->paid_amount;
        });
        
        // Calculate Total Paid (This Year / All Time)
        $student->total_paid = $student->transactions()->sum('amount');

        return Inertia::render('Academic/Students/Show', [
            'student' => $student,
        ]);
    }

    public function store(Request $request)
    {
        $unitId = session('active_unit_id') ?? \App\Modules\Yayasan\Models\Unit::first()->id;

        $validated = $request->validate([
            'full_name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email', 
            'nis' => 'nullable|string|max:20',
            'nisn' => 'nullable|string|max:20',
            'gender' => 'required|in:L,P',
            'classroom_id' => 'nullable|exists:classrooms,id',
            'photo' => 'nullable|image|max:2048',
            'pob' => 'nullable|string|max:100',
            'dob' => 'nullable|date',
            'address' => 'nullable|string',
            'parent_name' => 'nullable|string|max:255',
            'parent_phone' => 'nullable|string|max:20',
            'guardian_name' => 'nullable|string|max:255',
            'guardian_phone' => 'nullable|string|max:20',
        ]);

        try {
            \DB::transaction(function () use ($validated, $unitId, $request) {
                // 1. Find or Create User
                $user = User::where('email', $validated['email'])->first();

                if (!$user) {
                    // Generate password from NIS or default
                    $password = $validated['nis'] ? $validated['nis'] : 'siswa123';
                    $user = User::create([
                        'name' => $validated['full_name'],
                        'email' => $validated['email'],
                        'password' => Hash::make($password), 
                        'email_verified_at' => now(),
                    ]);
                }
                
                if (!$user->hasRole('siswa')) {
                    setPermissionsTeamId($unitId);
                    $user->assignRole('siswa');
                }

                // 2. Handle Upload
                $photoPath = null;
                if ($request->hasFile('photo')) {
                    $photoPath = $request->file('photo')->store('students', 'public');
                }

                // Get active academic year
                $activeYear = \App\Modules\Yayasan\Models\AcademicYear::where('is_active', true)->first();

                // 3. Create Student Profile
                Student::create([
                    'user_id' => $user->id,
                    'unit_id' => $unitId,
                    'classroom_id' => $validated['classroom_id'] ?? null,
                    'academic_year_id' => $activeYear?->id,
                    'full_name' => $validated['full_name'],
                    'nis' => $validated['nis'],
                    'nisn' => $validated['nisn'],
                    'va_number' => $request->va_number, // Optional
                    'gender' => $validated['gender'],
                    'pob' => $validated['pob'] ?? null,
                    'dob' => $validated['dob'] ?? null,
                    'address' => $validated['address'] ?? null,
                    'parent_name' => $validated['parent_name'] ?? null,
                    'parent_phone' => $validated['parent_phone'] ?? null,
                    'guardian_name' => $validated['guardian_name'] ?? null,
                    'guardian_phone' => $validated['guardian_phone'] ?? null,
                    'photo' => $photoPath,
                ]);
            });

            // Automated WhatsApp Welcome Notification to parents
            try {
                $unit = \App\Modules\Yayasan\Models\Unit::find($unitId);
                $unitName = $unit->name ?? 'Namira School';

                if (!empty($validated['parent_phone'])) {
                    $password = $validated['nis'] ? $validated['nis'] : 'siswa123';
                    $message = "📝 *Pemberitahuan Pendaftaran Akun Siswa*\n\n"
                        . "Yth. Orang Tua/Wali dari *{$validated['full_name']}*.\n\n"
                        . "Selamat, ananda telah terdaftar sebagai siswa di *{$unitName}*.\n"
                        . "Kami juga telah membuatkan akun portal akademik untuk memantau kehadiran, nilai, dan tagihan ananda.\n\n"
                        . "• *Website Portal*: " . url('/login') . "\n"
                        . "• *Username (Email)*: {$validated['email']}\n"
                        . "• *Password*: {$password}\n\n"
                        . "Silakan simpan informasi login ini dengan baik dan ubah password setelah login pertama kali.\n\n"
                        . "Terima kasih.\n-- *{$unitName}*";

                    \App\Helpers\WhatsAppHelper::send($validated['parent_phone'], $message);
                }
            } catch (\Exception $e) {
                \Log::error("Failed to send automated WA student registration welcome: " . $e->getMessage());
            }

            return redirect()->back()->with('success', 'Siswa berhasil ditambahkan.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal menambahkan siswa. Silakan coba lagi.');
        }
    }

    public function update(Request $request, Student $student)
    {
        if ($student->unit_id != session('active_unit_id')) abort(403);

        $validated = $request->validate([
            'full_name' => 'required|string|max:255',
            'nis' => 'nullable|string|max:20',
            'nisn' => 'nullable|string|max:20',
            'va_number' => 'nullable|string|max:30',
            'gender' => 'required|in:L,P',
            'classroom_id' => 'nullable|exists:classrooms,id',
            'photo' => 'nullable|image|max:2048',
            'pob' => 'nullable|string|max:100',
            'dob' => 'nullable|date',
            'address' => 'nullable|string',
            'parent_name' => 'nullable|string|max:255',
            'parent_phone' => 'nullable|string|max:20',
            'guardian_name' => 'nullable|string|max:255',
            'guardian_phone' => 'nullable|string|max:20',
        ]);

        $student->full_name = $validated['full_name'];
        $student->nis = $validated['nis'];
        $student->nisn = $validated['nisn'];
        $student->va_number = $request->va_number;
        $student->gender = $validated['gender'];
        $student->classroom_id = $validated['classroom_id'] ?? null;
        $student->pob = $validated['pob'] ?? null;
        $student->dob = $validated['dob'] ?? null;
        $student->address = $validated['address'] ?? null;
        $student->parent_name = $validated['parent_name'] ?? null;
        $student->parent_phone = $validated['parent_phone'] ?? null;
        $student->guardian_name = $validated['guardian_name'] ?? null;
        $student->guardian_phone = $validated['guardian_phone'] ?? null;

        if ($request->hasFile('photo')) {
            if ($student->photo) {
                \Storage::disk('public')->delete($student->photo);
            }
            $student->photo = $request->file('photo')->store('students', 'public');
        }

        $student->save();
        $student->user->update(['name' => $validated['full_name']]);

        return redirect()->back()->with('success', 'Data siswa diperbarui.');
    }

    public function destroy(Student $student)
    {
        if ($student->unit_id != session('active_unit_id')) abort(403);

        if ($student->photo) {
            \Storage::disk('public')->delete($student->photo);
        }

        $student->user->delete();
        $student->delete();

        return redirect()->back()->with('success', 'Siswa dihapus.');
    }

    /**
     * Bulk update academic year for multiple students
     */
    public function bulkUpdateYear(Request $request)
    {
        $request->validate([
            'student_ids' => 'required|array|min:1',
            'student_ids.*' => 'exists:students,id',
            'academic_year_id' => 'required|exists:academic_years,id',
        ]);

        $unitId = session('active_unit_id');
        
        $count = Student::whereIn('id', $request->student_ids)
            ->where('unit_id', $unitId)
            ->update(['academic_year_id' => $request->academic_year_id]);

        return redirect()->back()->with('success', "$count siswa berhasil diupdate tahun ajarannya.");
    }

    /**
     * Import from Excel (XLSX)
     */
    public function importExcel(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls|max:5120',
            'classroom_id' => 'nullable|exists:classrooms,id',
        ]);

        $unitId = session('active_unit_id');
        $activeYear = \App\Modules\Yayasan\Models\AcademicYear::where('is_active', true)->first();
        
        if (!$activeYear) {
            return redirect()->back()->with('error', 'Tidak ada tahun ajaran aktif.');
        }

        $file = $request->file('file');
        $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($file->getPathname());
        $worksheet = $spreadsheet->getActiveSheet();
        $rows = $worksheet->toArray();
        
        $header = array_shift($rows);
        if (!$header) {
            return redirect()->back()->with('error', 'File Excel kosong.');
        }

        $headerText = strtolower(implode(' ', array_filter($header)));
        if (!str_contains($headerText, 'nama') && !str_contains($headerText, 'name') && !str_contains($headerText, 'nis')) {
            return redirect()->back()->with('error', 'Format header Excel tidak valid. Pastikan baris pertama adalah judul kolom seperti Nama, Email, NIS.');
        }

        $count = 0;
        $errors = [];

        \DB::transaction(function () use ($rows, $unitId, $activeYear, $request, &$count, &$errors) {
            foreach ($rows as $index => $row) {
                // Expected format: Name, Email, NIS, NISN, Gender (L/P)
                if (empty($row[0]) || empty($row[1])) continue;

                $name = trim($row[0]);
                $email = trim($row[1]);
                $nis = trim($row[2] ?? '');
                $nisn = trim($row[3] ?? '');
                $gender = strtoupper(trim($row[4] ?? 'L'));

                // Skip if email exists
                if (User::where('email', $email)->exists()) {
                    $errors[] = "Baris " . ($index + 2) . ": Email $email sudah terdaftar.";
                    continue;
                }

                // Create User
                $password = $nis ?: 'siswa123';
                $user = User::create([
                    'name' => $name,
                    'email' => $email,
                    'password' => Hash::make($password),
                    'email_verified_at' => now(),
                ]);

                setPermissionsTeamId($unitId);
                $user->assignRole('siswa');

                // Create Student
                Student::create([
                    'user_id' => $user->id,
                    'unit_id' => $unitId,
                    'academic_year_id' => $activeYear->id,
                    'classroom_id' => $request->classroom_id,
                    'full_name' => $name,
                    'nis' => $nis,
                    'nisn' => $nisn,
                    'gender' => in_array($gender, ['L', 'P']) ? $gender : 'L',
                ]);

                $count++;
            }
        });

        $message = "$count siswa berhasil diimport.";
        if (count($errors) > 0) {
            $message .= " " . count($errors) . " baris dilewati.";
        }

        return redirect()->back()->with('success', $message);
    }
}

