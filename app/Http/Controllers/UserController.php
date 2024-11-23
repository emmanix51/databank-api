<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
        $validator = Validator::make($request->all(), [
            'idnum' => 'required|integer|unique:users',
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8',
            'role' => 'required|in:admin,faculty,student',
            'position' => 'nullabe|string',
            'year_level'=>'nullable|integer|min:1|max:4',
            'college_id' => 'required|exists:colleges,id',  
            'program_id' => 'required|exists:programs,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 400);
        }

        $user = new User();
        $user->idnum = $request->idnum;
        $user->first_name = $request->first_name;
        $user->last_name = $request->last_name;
        $user->email = $request->email;
        $user->password = bcrypt($request->password);
        $user->role = $request->role;
        $user->position = $request->position;
        $user->year_level = $request->year_level;
        $user->college_id = $request->college_id;  
        $user->program_id = $request->program_id;
        $user->save();

        return response()->json(['user' => $user], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
        $user = User::find($id);
        if (!$user) {
            return response()->json(['error' => 'User not found'], 404);
        }
        return response()->json(['user' => $user], 200);
    }
    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        //
        $user = User::find($id);

        if (!$user) {
            return response()->json(['error' => 'User not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $user->id,
            'role' => 'required|in:admin,faculty,student',
            'position' => 'nullabe|string',
            'year_level'=>'nullable|integer|min:1|max:4',
            'college_id' => 'required|exists:colleges,id',  
            'program_id' => 'required|exists:programs,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 400);
        }

        $user->first_name = $request->first_name;
        $user->last_name = $request->last_name;
        $user->email = $request->email;
        $user->role = $request->role;
        $user->position = $request->position;
        $user->year_level = $request->year_level;
        $user->college_id = $request->college_id;  
        $user->program_id = $request->program_id;
        $user->save();

        return response()->json(['user' => $user], 201);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy( $id)
    {
        //
        $user = User::find($id);

        if (!$user) {
            return response()->json(['error' => 'User not found'], 404);
        }

        $user->delete();

        return response()->json(['message' => 'User deleted successfully'], 200);
    }

    public function searchUser(){

    }

    public function getHeads()
    {
        // Retrieve all users where the 'role' is 'faculty'
        $heads = User::whereIn('role', ['dean','programhead'])->with('college')->get();

        // Check if there are any users
        if ($heads->isEmpty()) {
            return response()->json(['error' => 'No faculty members found'], 404);
        }

        // Return the list of faculty members
        return response()->json(['heads' => $heads], 200);
    }


    public function getFaculty()
    {
        // Retrieve all users where the 'role' is 'faculty'
        $faculty = User::where('role', 'faculty')->get();

        // Check if there are any users
        if ($faculty->isEmpty()) {
            return response()->json(['error' => 'No faculty members found'], 404);
        }

        // Return the list of faculty members
        return response()->json(['faculty' => $faculty], 200);
    }

    public function getStudents()
    {
        // Retrieve all users where the 'role' is 'faculty'
        $students = User::where('role', 'student')->with('college','program')->get();

        // Check if there are any users
        if ($students->isEmpty()) {
            return response()->json(['error' => 'No student members found'], 404);
        }

        // Return the list of faculty members
        return response()->json(['students' => $students], 200);
    }
    public function getByCollege($id)
    {
        // Retrieve all users where the 'role' is 'faculty'
        $collegeUsers = User::where('college_id', $id)->get();

        // Check if there are any users
        if ($collegeUsers->isEmpty()) {
            return response()->json(['error' => 'No members from this college found'], 404);
        }

        // Return the list of faculty members
        return response()->json(['collegeUsers' => $collegeUsers], 200);
    }
    public function getByProgram($id)
    {
        // Retrieve all users where the 'role' is 'faculty'
        $programUsers = User::where('program_id', $id)->get();

        // Check if there are any users
        if ($programUsers->isEmpty()) {
            return response()->json(['error' => 'No members from this program found'], 404);
        }

        // Return the list of faculty members
        return response()->json(['programUsers' => $programUsers], 200);
    }
}
