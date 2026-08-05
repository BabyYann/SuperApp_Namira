<?php

namespace App\Http\Controllers;

use App\Models\JobApplicant;
use App\Models\JobVacancy;
use App\Modules\Yayasan\Models\Unit;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PublicCareerController extends Controller
{
    public function index(Request $request)
    {
        $query = JobVacancy::with('unit')->where('status', 'open');

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if ($request->filled('unit') && $request->unit !== 'all') {
            $query->where('unit_id', $request->unit);
        }

        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }

        $vacancies = $query->latest()->paginate(9)->withQueryString();
        $units = Unit::select('id', 'name')->get();

        return Inertia::render('Public/CareerIndex', [
            'vacancies' => $vacancies,
            'units' => $units,
            'filters' => $request->only(['search', 'unit', 'category']),
            'categories' => [
                'teacher' => 'Tenaga Pendidik (Guru)',
                'staff' => 'Tenaga Kependidikan (Staf)',
                'operational' => 'Operasional & Sarpar',
                'other' => 'Lainnya',
            ],
        ]);
    }

    public function show(JobVacancy $vacancy)
    {
        if ($vacancy->status !== 'open') {
            return redirect()->route('careers.index')->with('warning', 'Lowongan ini telah ditutup.');
        }

        $vacancy->increment('views_count');
        $vacancy->load('unit');

        $otherVacancies = JobVacancy::with('unit')
            ->where('status', 'open')
            ->where('id', '!=', $vacancy->id)
            ->latest()
            ->take(3)
            ->get();

        return Inertia::render('Public/CareerDetail', [
            'vacancy' => $vacancy,
            'otherVacancies' => $otherVacancies,
        ]);
    }

    public function apply(Request $request, JobVacancy $vacancy)
    {
        if ($vacancy->status !== 'open') {
            return redirect()->route('careers.index')->with('error', 'Lowongan ini telah ditutup.');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'required|string|max:20',
            'gender' => 'required|in:L,P',
            'birth_place' => 'required|string|max:255',
            'birth_date' => 'required|date',
            'address' => 'required|string|max:1000',
            'last_education' => 'required|string|max:50',
            'major' => 'required|string|max:255',
            'institution' => 'required|string|max:255',
            'gpa' => 'nullable|string|max:20',
            'cv' => 'required|file|mimes:pdf|max:5120',
            'cover_letter' => 'nullable|file|mimes:pdf|max:5120',
            'certificate' => 'nullable|file|mimes:pdf|max:5120',
            'ktp' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:3072',
            'photo' => 'nullable|file|mimes:jpg,jpeg,png|max:3072',
            'notes' => 'nullable|string|max:1000',
        ]);

        $cvPath = $request->file('cv')->store('recruitment/cv', 'public');
        $coverLetterPath = $request->hasFile('cover_letter') ? $request->file('cover_letter')->store('recruitment/cover_letters', 'public') : null;
        $certificatePath = $request->hasFile('certificate') ? $request->file('certificate')->store('recruitment/certificates', 'public') : null;
        $ktpPath = $request->hasFile('ktp') ? $request->file('ktp')->store('recruitment/ktp', 'public') : null;
        $photoPath = $request->hasFile('photo') ? $request->file('photo')->store('recruitment/photos', 'public') : null;

        $applicant = JobApplicant::create([
            'job_vacancy_id' => $vacancy->id,
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'],
            'gender' => $validated['gender'],
            'birth_place' => $validated['birth_place'],
            'birth_date' => $validated['birth_date'],
            'address' => $validated['address'],
            'last_education' => $validated['last_education'],
            'major' => $validated['major'],
            'institution' => $validated['institution'],
            'gpa' => $validated['gpa'] ?? null,
            'cv_path' => $cvPath,
            'cover_letter_path' => $coverLetterPath,
            'certificate_path' => $certificatePath,
            'ktp_path' => $ktpPath,
            'photo_path' => $photoPath,
            'notes' => $validated['notes'] ?? null,
            'selection_status' => 'pending',
        ]);

        // Dispatch In-App notification to Yayasan Admins
        try {
            \App\Services\NotificationDispatcher::sendToRoles(
                ['super_admin_yayasan', 'admin_yayasan', 'pembina_yayasan'],
                $vacancy->unit_id,
                '💼 Pelamar Kerja Baru',
                "{$applicant->name} telah mengumpulkan lamaran untuk posisi {$vacancy->title}.",
                'recruitment',
                ['applicant_id' => $applicant->id]
            );
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Recruitment notification dispatch failed: ' . $e->getMessage());
        }

        return redirect()->route('careers.show', $vacancy->slug)->with('success', "Pendaftaran lamaran kerja Anda berhasil dikirim dengan Kode Registrasi: {$applicant->applicant_code}. Tim Yayasan Namira akan meninjau berkas Anda.");
    }
}
