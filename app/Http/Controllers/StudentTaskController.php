<?php

namespace App\Http\Controllers;

use App\Models\StudentTask;
use App\Modules\Academic\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Carbon\Carbon;

class StudentTaskController extends Controller
{
    private function getStudent()
    {
        return Student::where('user_id', Auth::id())->first();
    }

    public function index()
    {
        $student = $this->getStudent();
        if (!$student) abort(403);

        $tasks = StudentTask::where('student_id', $student->id)
            ->whereDate('date', Carbon::today())
            ->orderBy('is_completed', 'asc') // Unfinished first
            ->orderBy('created_at', 'desc')
            ->get();

        return Inertia::render('Student/Productivity/Index', [
            'tasks' => $tasks,
            'student' => $student,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
        ]);

        $student = $this->getStudent();

        StudentTask::create([
            'student_id' => $student->id,
            'title' => $request->title,
            'date' => Carbon::today(),
            'is_completed' => false,
        ]);

        return redirect()->back();
    }

    public function update(Request $request, StudentTask $task)
    {
        $student = $this->getStudent();
        
        // Ownership check
        if ($task->student_id !== $student->id) abort(403);

        $task->update([
            'is_completed' => $request->is_completed,
            'completed_at' => $request->is_completed ? now() : null,
        ]);

        return redirect()->back();
    }

    public function destroy(StudentTask $task)
    {
        $student = $this->getStudent();
        
        if ($task->student_id !== $student->id) abort(403);

        $task->delete();

        return redirect()->back();
    }
}
