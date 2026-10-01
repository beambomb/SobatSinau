<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Classroom;
use App\Http\Requests\StoreClassroomRequest;
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
        return view('classrooms.index',compact('classrooms'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('classrooms.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreClassroomRequest $request)
    {
        $code = Str::lower(Str::random(6));
        Auth::user()->teachingClassrooms()->create([
            'title' => $request->title,
            'subject' => $request->subject,
            'code' => $code,
        ]);
        return redirect()->route('classrooms.index')->with('success', 'Kelas berhasil dibuat!');
    }

    /**
     * Display the specified resource.
     */
    public function show(Classroom $classroom)
    {
        // Otorisasi: Pastikan hanya guru pemilik kelas (atau admin) yang bisa membuka
        if (!Auth::user()->hasRole('admin') && $classroom->teacher_id !== Auth::id()) {
            abort(403, 'Kamu bukan pengajar di kelas ini.');
        }
        return view('classrooms.show', compact('classroom'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Classroom $classroom)
    {
        if (!Auth::user()->hasRole('admin') && $classroom->teacher_id !== Auth::id()) {
            abort(403, 'Kamu tidak berhak menghapus kelas ini.');
        }

        $classroom->delete();

        return redirect()->route('classrooms.index')->with('success', 'Kelas berhasil dihapus!');
    }

}
