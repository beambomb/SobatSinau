<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreClassroomRequest;
use App\Models\Classroom;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class ClassroomController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $classrooms = Auth::user()->teachingClassrooms()->latest()->get();

        return response()->json([
            'status' => 'success',
            'data' => $classrooms,
        ]);
    }

    public function store(StoreClassroomRequest $request)
    {
        $code = Str::lower(Str::random(6));

        $classroom = Auth::user()->teachingClassrooms()->create([
            'title' => $request->title,
            'subject' => $request->subject,
            'code' => $code,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Kelas berhasil dibuat!',
            'data' => $classroom,
        ], 201);
    }

    public function show(Classroom $classroom)
    {
        if (! Auth::user()->hasRole('admin') && $classroom->teacher_id !== Auth::id()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Kamu bukan pengajar di kelas ini.',
            ], 403);
        }

        return response()->json([
            'status' => 'success',
            'data' => $classroom->load('teacher:id,name,email'),
        ]);
    }

    public function update(Request $request, Classroom $classroom)
    {
        if (! Auth::user()->hasRole('admin') && $classroom->teacher_id !== Auth::id()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Kamu tidak berhak mengedit kelas ini.',
            ], 403);
        }

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'subject' => 'nullable|string|max:255',
        ]);

        $classroom->update($validated);

        return response()->json([
            'status' => 'success',
            'message' => 'Kelas berhasil diperbarui!',
            'data' => $classroom,
        ]);
    }

    public function destroy(Classroom $classroom)
    {
        if (! Auth::user()->hasRole('admin') && $classroom->teacher_id !== Auth::id()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Kamu tidak berhak menghapus kelas ini.',
            ], 403);
        }

        $classroom->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Kelas berhasil dihapus!',
        ]);
    }
}
