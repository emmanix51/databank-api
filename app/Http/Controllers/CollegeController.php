<?php

namespace App\Http\Controllers;

use App\Models\College;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;


class CollegeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
        $college = College::with('programs')->paginate(6);
        // Check if no college are found
        if ($college->isEmpty()) {
            return response()->json([
                'status' => 'error',
                'message' => 'No college found.',
                'data' => [
                    'college' => [], // Return an empty array in 'college'
                    'pagination' => [
                        'current_page' => $college->currentPage(),
                        'total_pages' => $college->lastPage(),
                        'total_items' => $college->total(),
                        'per_page' => $college->perPage(),
                        'first_page_url' => $college->url(1),
                        'last_page_url' => $college->url($college->lastPage()),
                        'next_page_url' => $college->nextPageUrl(),
                        'prev_page_url' => $college->previousPageUrl(),
                    ]
                ]
            ], 404); // HTTP 404 for "not found"
        }

        // If college are found, return success with user data and pagination
        return response()->json([
            'status' => 'success',
            'message' => 'Request processed successfully.',
            'data' => [
                'college' => $college->items(), // Get the actual user data
                'pagination' => [
                    'current_page' => $college->currentPage(),
                    'total_pages' => $college->lastPage(),
                    'total_items' => $college->total(),
                    'per_page' => $college->perPage(),
                    'first_page_url' => $college->url(1),
                    'last_page_url' => $college->url($college->lastPage()),
                    'next_page_url' => $college->nextPageUrl(),
                    'prev_page_url' => $college->previousPageUrl(),
                ]
            ]
        ], 200); // HTTP 200 for "OK"

        // return response()->json(['test custom api'=>'works my nig word']);

    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    public function test()
    {
        //
        return response()->json(['test custom api' => 'works my n word']);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
        //
        $validator = Validator::make($request->all(), [
            'college_name' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => $validator->errors(),
            ], 400);
        }

        $college = new College();

        $college->college_name = $request->college_name;
        $college->save();

        return response()->json([
            'status' => 'success',
            'message' => 'college created successfully!',
            'data' => $college
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(College $college)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(College $college)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, College $college)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(College $college)
    {
        //
    }
}
