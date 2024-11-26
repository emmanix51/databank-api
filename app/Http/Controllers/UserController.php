<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Validator;


class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
{
    // Paginate the users (you can adjust the per page value as needed)
    $users = User::paginate(1);

    // Check if no users are found
    if ($users->isEmpty()) {
        return response()->json([
            'status' => 'error',
            'message' => 'No users found.',
            'data' => [
                'users' => [], // Return an empty array in 'users'
                'pagination' => [
                    'current_page' => $users->currentPage(),
                    'total_pages' => $users->lastPage(),
                    'total_items' => $users->total(),
                    'per_page' => $users->perPage(),
                    'first_page_url' => $users->url(1),
                    'last_page_url' => $users->url($users->lastPage()),
                    'next_page_url' => $users->nextPageUrl(),
                    'prev_page_url' => $users->previousPageUrl(),
                ]
            ]
        ], 404); // HTTP 404 for "not found"
    }

    // If users are found, return success with user data and pagination
    return response()->json([
        'status' => 'success',
        'message' => 'Request processed successfully.',
        'data' => [
            'users' => $users->items(), // Get the actual user data
            'pagination' => [
                'current_page' => $users->currentPage(),
                'total_pages' => $users->lastPage(),
                'total_items' => $users->total(),
                'per_page' => $users->perPage(),
                'first_page_url' => $users->url(1),
                'last_page_url' => $users->url($users->lastPage()),
                'next_page_url' => $users->nextPageUrl(),
                'prev_page_url' => $users->previousPageUrl(),
            ]
        ]
    ], 200); // HTTP 200 for "OK"
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
            'password' => 'required|string|confirmed|min:6',
            'role' => 'required|in:admin,faculty,student,dean,programhead',
            // 'position' => 'nullabe|string',
            'year_level'=>'nullable|integer|max:4',
            'college_id' => 'required|exists:colleges,id',  
            'program_id' => 'required|exists:programs,id',
            'phone_number' => 'required|integer|digits_between:10,15'
        ]);

        if ($validator->fails()) {
            return response()->json(['status'=>'error',
                                    'message' => $validator->errors(),], 400);
        }

        $user = new User();
        $user->idnum = $request->idnum;
        $user->first_name = $request->first_name;
        $user->last_name = $request->last_name;
        $user->email = $request->email;
        $user->password = bcrypt($request->password);
        $user->role = $request->role;
        // $user->position = $request->position;
        $user->year_level = $request->year_level;
        $user->college_id = $request->college_id;  
        $user->program_id = $request->program_id;
        $user->phone_number = $request->phone_number;
        $user->save();

        return response()->json(['status'=>'success',
                                'message'=>'user created successfully!',
                                'data' => $user], 201);
    }

    

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
        $user = User::find($id);
        if (!$user) {
            return response()->json(['status'=>'error',
                                    'message'=>'user not found'], 404);
        }
        return response()->json(['status'=>'success',
                                 'message'=>'user found',
                                 'data'=>['user' => $user]], 200);
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
            'role' => 'required|in:admin,faculty,student,dean,programhead',
            // 'position' => 'nullabe|string',
            'year_level'=>'nullable|integer|max:4',
            'college_id' => 'required|exists:colleges,id',  
            'program_id' => 'required|exists:programs,id',
            'phone_number' => 'required|integer|digits_between:10,15'
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 400);
        }

        $user->first_name = $request->first_name;
        $user->last_name = $request->last_name;
        $user->email = $request->email;
        $user->role = $request->role;
        // $user->position = $request->position;
        $user->year_level = $request->year_level;
        $user->college_id = $request->college_id;  
        $user->program_id = $request->program_id;
        $user->phone_number = $request->phone_number;
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
            return response()->json(['status'=>'error','message' => 'User not found'], 404);
        }

        $user->delete();

        return response()->json(['status'=>'success','message' => 'User deleted successfully'], 200);
    }

    public function searchUser(){

    }

    public function getByRole(Request $request)
{
    // Get the role from the query parameter, default to 'student' if not provided
    $role = $request->query('role');  // Default to 'student' if no role is passed

    // Validate that the role is one of the accepted values
    if (!in_array($role, ['student', 'faculty', 'dean','programhead'])) {
        return response()->json([
            'status' => 'error',
            'message' => 'Invalid role provided. Allowed roles are student, faculty, head.',
        ], 400);
    }

    // Retrieve users based on college_id and role
    $users = User:: where('role', $role)
                 ->with('college', 'program') // You can load relationships if needed
                 ->get();

    // Check if users are found
    if ($users->isEmpty()) {
        return response()->json([
            'status' => 'error',
            'message' => "No {$role}s found in the specified college.",
            'data' => []
        ], 404);
    }

    // Return the users for the specified role and college
    return response()->json([
        'status' => 'success',
        'message' => "{$role}s retrieved successfully.",
        'data' => [
            'users' => $users
        ]
    ], 200);
}


public function getByCollegeWithRole(Request $request)
{
    
    $role = $request->query('role'); 
    $college_id = $request->query('college_id'); 
    $perPage = $request->query('per_page', 3); 

    
    $query = User::query(); 

    
    if ($role) {
        // Validate the role
        if (!in_array($role, ['student', 'faculty', 'programhead', 'dean','admin'])) {
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid role provided. Allowed roles are student, faculty, program head, and dean.',
            ], 400);
        }
        $query->where('role', $role);
    }

    // If 'college_id' is provided, filter by college_id
    if ($college_id) {
        $query->where('college_id', $college_id); 
    }

    $users = $query->with('college', 'program') 
                   ->paginate($perPage);

    if ($users->isEmpty()) {
        return response()->json([
            'status' => 'error',
            'message' => 'No users found with the specified criteria.',
            'data' => []
        ], 404);
    }

    return response()->json([
        'status' => 'success',
        'message' => 'Users retrieved successfully.',
        'data' => [
            'users' => $users->items(), 
            'pagination' => [
                'current_page' => $users->currentPage(),
                'total_pages' => $users->lastPage(),
                'total_items' => $users->total(),
                'per_page' => $users->perPage(),
                'first_page_url' => $users->url(1),
                'last_page_url' => $users->url($users->lastPage()),
                'next_page_url' => $users->nextPageUrl(),
                'prev_page_url' => $users->previousPageUrl(),
            ]
        ]
    ], 200);
}




// public function getByCollegeWithRole(Request $request, $college_id, $role)
// {
//     // Validate the role
//     if (!in_array($role, ['student', 'faculty', 'programhead','dean'])) {
//         return response()->json([
//             'status' => 'error',
//             'message' => 'Invalid role provided. Allowed roles are student, faculty, program head, and dean.',
//         ], 400);
//     }

//     // Define the number of results per page (you can adjust this number)
//     $perPage = $request->query('per_page', 1); // Default to 10 if not specified

//     // Retrieve users based on college_id and role, with pagination
//     $users = User::where('college_id', $college_id)
//                  ->where('role', $role)
//                  ->with('college', 'program') // You can load relationships if needed
//                  ->paginate($perPage);

//     // Check if users are found
//     if ($users->isEmpty()) {
//         return response()->json([
//             'status' => 'error',
//             'message' => "No {$role}s found in the specified college.",
//             'data' => []
//         ], 404);
//     }

//     // Return paginated users for the specified role and college, including pagination links
//     return response()->json([
//         'status' => 'success',
//         'message' => "{$role}s retrieved successfully.",
//         'data' => [
//             'users' => $users->items(), // The actual user data
//             'pagination' => [
//                 'current_page' => $users->currentPage(),
//                 'total_pages' => $users->lastPage(),
//                 'total_items' => $users->total(),
//                 'per_page' => $users->perPage(),
//                 'first_page_url' => $users->url(1), // URL for the first page
//                 'last_page_url' => $users->url($users->lastPage()), // URL for the last page
//                 'next_page_url' => $users->nextPageUrl(), // URL for the next page (if exists)
//                 'prev_page_url' => $users->previousPageUrl(), // URL for the previous page (if exists)
//             ]
//         ]
//     ], 200);
// }



//     public function getHeads()
//     {
//         // Retrieve all users where the 'role' is 'faculty'
//         $heads = User::whereIn('role', ['dean','programhead'])->with('college')->paginate(1);

//     // Check if no students are found
//     if ($heads->isEmpty()) {
//         return response()->json([
//             'status' => 'error',
//             'message' => 'No student members found.',
//             'data' => [
//                 'heads' => [], // Return an empty array in 'heads'
//                 'pagination' => [
//                     'current_page' => $heads->currentPage(),
//                     'total_pages' => $heads->lastPage(),
//                     'total_items' => $heads->total(),
//                     'per_page' => $heads->perPage(),
//                     'first_page_url' => $heads->url(1),
//                     'last_page_url' => $heads->url($heads->lastPage()),
//                     'next_page_url' => $heads->nextPageUrl(),
//                     'prev_page_url' => $heads->previousPageUrl(),
//                 ]
//             ]
//         ], 404); // HTTP 404 for "not found"
//     }

//     // If heads are found, return success with student data
//     return response()->json([
//         'status' => 'success',
//         'message' => 'Request processed successfully.',
//         'data' => [
//             'heads' => $heads->items(), // Get the student data
//             'pagination' => [
//                 'current_page' => $heads->currentPage(),
//                     'total_pages' => $heads->lastPage(),
//                     'total_items' => $heads->total(),
//                     'per_page' => $heads->perPage(),
//                     'first_page_url' => $heads->url(1),
//                     'last_page_url' => $heads->url($heads->lastPage()),
//                     'next_page_url' => $heads->nextPageUrl(),
//                     'prev_page_url' => $heads->previousPageUrl(),
//             ]
//         ]
//     ], 200); // HTTP 200 for "OK"
//     }


//     public function getFaculty()
//     {
//         // Retrieve all users where the 'role' is 'faculty'
//         $faculty = User::where('role', 'faculty')->paginate(1);

//              // Check if no students are found
//     // Check if no students are found
//     if ($faculty->isEmpty()) {
//         return response()->json([
//             'status' => 'error',
//             'message' => 'No student members found.',
//             'data' => [
//                 'faculty' => [], // Return an empty array in 'faculty'
//                 'pagination' => [
//                     'current_page' => $faculty->currentPage(),
//                     'total_pages' => $faculty->lastPage(),
//                     'total_items' => $faculty->total(),
//                     'per_page' => $faculty->perPage(),
//                     'first_page_url' => $faculty->url(1),
//                     'last_page_url' => $faculty->url($faculty->lastPage()),
//                     'next_page_url' => $faculty->nextPageUrl(),
//                     'prev_page_url' => $faculty->previousPageUrl(),
//                 ]
//             ]
//         ], 404); // HTTP 404 for "not found"
//     }

//     // If faculty are found, return success with student data
//     return response()->json([
//         'status' => 'success',
//         'message' => 'Request processed successfully.',
//         'data' => [
//             'faculty' => $faculty->items(), // Get the student data
//             'pagination' => [
//                 'current_page' => $faculty->currentPage(),
//                     'total_pages' => $faculty->lastPage(),
//                     'total_items' => $faculty->total(),
//                     'per_page' => $faculty->perPage(),
//                     'first_page_url' => $faculty->url(1),
//                     'last_page_url' => $faculty->url($faculty->lastPage()),
//                     'next_page_url' => $faculty->nextPageUrl(),
//                     'prev_page_url' => $faculty->previousPageUrl(),
//             ]
//         ]
//     ], 200); // HTTP 200 for "OK"
//     }

//     public function getStudents()
// {
//     // Retrieve all users where the 'role' is 'student' with pagination
//     $students = User::where('role', 'student')
//                     ->with('college', 'program')
//                     ->paginate(10);

//     // Check if no students are found
//     if ($students->isEmpty()) {
//         return response()->json([
//             'status' => 'error',
//             'message' => 'No student members found.',
//             'data' => [
//                 'students' => [], // Return an empty array in 'students'
//                 'pagination' => [
//                     'current_page' => $students->currentPage(),
//                     'total_pages' => $students->lastPage(),
//                     'total_items' => $students->total(),
//                     'per_page' => $students->perPage(),
//                     'first_page_url' => $students->url(1),
//                     'last_page_url' => $students->url($students->lastPage()),
//                     'next_page_url' => $students->nextPageUrl(),
//                     'prev_page_url' => $students->previousPageUrl(),
//                 ]
//             ]
//         ], 404); // HTTP 404 for "not found"
//     }

//     // If students are found, return success with student data
//     return response()->json([
//         'status' => 'success',
//         'message' => 'Request processed successfully.',
//         'data' => [
//             'students' => $students->items(), // Get the student data
//             'pagination' => [
//                 'current_page' => $students->currentPage(),
//                     'total_pages' => $students->lastPage(),
//                     'total_items' => $students->total(),
//                     'per_page' => $students->perPage(),
//                     'first_page_url' => $students->url(1),
//                     'last_page_url' => $students->url($students->lastPage()),
//                     'next_page_url' => $students->nextPageUrl(),
//                     'prev_page_url' => $students->previousPageUrl(),
//             ]
//         ]
//     ], 200); // HTTP 200 for "OK"
// }

//     public function getByCollege($id)
//     {

//         $collegeUsers = User::where('college_id', $id)->paginate(1);

        
//         // Check if no students are found
//     if ($collegeUsers->isEmpty()) {
//         return response()->json([
//             'status' => 'error',
//             'message' => 'No student members found.',
//             'data' => [
//                 'coll$collegeUsers' => [], // Return an empty array in 'coll$collegeUsers'
//                 'pagination' => [
//                     'current_page' => $collegeUsers->currentPage(),
//                     'total_pages' => $collegeUsers->lastPage(),
//                     'total_items' => $collegeUsers->total(),
//                     'per_page' => $collegeUsers->perPage(),
//                     'first_page_url' => $collegeUsers->url(1),
//                     'last_page_url' => $collegeUsers->url($collegeUsers->lastPage()),
//                     'next_page_url' => $collegeUsers->nextPageUrl(),
//                     'prev_page_url' => $collegeUsers->previousPageUrl(),
//                 ]
//             ]
//         ], 404); // HTTP 404 for "not found"
//     }

//     // If students are found, return success with student data
//     return response()->json([
//         'status' => 'success',
//         'message' => 'Request processed successfully.',
//         'data' => [
//             'collegeUsers' => $collegeUsers->items(), // Get the student data
//             'pagination' => [
//                 'current_page' => $collegeUsers->currentPage(),
//                     'total_pages' => $collegeUsers->lastPage(),
//                     'total_items' => $collegeUsers->total(),
//                     'per_page' => $collegeUsers->perPage(),
//                     'first_page_url' => $collegeUsers->url(1),
//                     'last_page_url' => $collegeUsers->url($collegeUsers->lastPage()),
//                     'next_page_url' => $collegeUsers->nextPageUrl(),
//                     'prev_page_url' => $collegeUsers->previousPageUrl(),
//             ]
//         ]
//     ], 200); // HTTP 200 for "OK"
//     }
//     public function getByProgram($id)
//     {
//         // Retrieve all users where the 'role' is 'faculty'
//         $programUsers = User::where('program_id', $id)->paginate(1);

//             // Check if no students are found
//     if ($programUsers->isEmpty()) {
//         return response()->json([
//             'status' => 'error',
//             'message' => 'No student members found.',
//             'data' => [
//                 'programUsers' => [], 
//                 'pagination' => [
//                     'current_page' => $programUsers->currentPage(),
//                     'total_pages' => $programUsers->lastPage(),
//                     'total_items' => $programUsers->total(),
//                     'per_page' => $programUsers->perPage(),
//                     'first_page_url' => $programUsers->url(1),
//                     'last_page_url' => $programUsers->url($programUsers->lastPage()),
//                     'next_page_url' => $programUsers->nextPageUrl(),
//                     'prev_page_url' => $programUsers->previousPageUrl(),
//                 ]
//             ]
//         ], 404); // HTTP 404 for "not found"
//     }

//     // If students are found, return success with student data
//     return response()->json([
//         'status' => 'success',
//         'message' => 'Request processed successfully.',
//         'data' => [
//             'programUsers' => $programUsers->items(), // Get the student data
//             'pagination' => [
//                 'current_page' => $programUsers->currentPage(),
//                     'total_pages' => $programUsers->lastPage(),
//                     'total_items' => $programUsers->total(),
//                     'per_page' => $programUsers->perPage(),
//                     'first_page_url' => $programUsers->url(1),
//                     'last_page_url' => $programUsers->url($programUsers->lastPage()),
//                     'next_page_url' => $programUsers->nextPageUrl(),
//                     'prev_page_url' => $programUsers->previousPageUrl(),
//             ]
//         ]
//     ], 200); // HTTP 200 for "OK"
//     }

    
}
