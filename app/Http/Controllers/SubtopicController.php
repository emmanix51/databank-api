<?php

namespace App\Http\Controllers;

use App\Models\Subtopic;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class SubtopicController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        // Fetch all subtopics with related topic, program, and college (eager loading)
        $subtopics = Subtopic::with('topic', 'program', 'college')->get();

        // Check if no subtopics are found
        if ($subtopics->isEmpty()) {
            return response()->json([
                'status' => 'error',
                'message' => 'No subtopics found.',
                'data' => []
            ], 404); // HTTP 404 for "Not Found"
        }

        // Return subtopics with success message
        return response()->json([
            'status' => 'success',
            'message' => 'Subtopics retrieved successfully.',
            'data' => $subtopics
        ], 200); // HTTP 200 for "OK"
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // Validate incoming request data
        $validator = Validator::make($request->all(), [
            'subtopic_name' => 'required|string|max:255',
            'topic_id' => 'required|exists:topics,id',
            'program_id' => 'required|exists:programs,id',
            'college_id' => 'required|exists:colleges,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => $validator->errors()
            ], 400); // HTTP 400 for "Bad Request"
        }

        // Create a new subtopic
        $subtopic = Subtopic::create([
            'subtopic_name' => $request->subtopic_name,
            'topic_id' => $request->topic_id,
            'program_id' => $request->program_id,
            'college_id' => $request->college_id,
        ]);

        // Return success response with the newly created subtopic
        return response()->json([
            'status' => 'success',
            'message' => 'Subtopic created successfully!',
            'data' => $subtopic
        ], 201); // HTTP 201 for "Created"
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        // Fetch a specific subtopic by ID
        $subtopic = Subtopic::with('topic', 'program', 'college')->find($id);

        // Check if subtopic exists
        if (!$subtopic) {
            return response()->json([
                'status' => 'error',
                'message' => 'Subtopic not found.',
                'data' => null
            ], 404); // HTTP 404 for "Not Found"
        }

        // Return the subtopic data
        return response()->json([
            'status' => 'success',
            'message' => 'Subtopic retrieved successfully.',
            'data' => $subtopic
        ], 200); // HTTP 200 for "OK"
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        // Fetch the subtopic to update
        $subtopic = Subtopic::find($id);

        // Check if subtopic exists
        if (!$subtopic) {
            return response()->json([
                'status' => 'error',
                'message' => 'Subtopic not found.',
                'data' => null
            ], 404); // HTTP 404 for "Not Found"
        }

        // Validate incoming request data
        $validator = Validator::make($request->all(), [
            'subtopic_name' => 'required|string|max:255',
            'topic_id' => 'required|exists:topics,id',
            'program_id' => 'required|exists:programs,id',
            'college_id' => 'required|exists:colleges,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => $validator->errors()
            ], 400); // HTTP 400 for "Bad Request"
        }

        // Update the subtopic with validated data
        $subtopic->update([
            'subtopic_name' => $request->subtopic_name,
            'topic_id' => $request->topic_id,
            'program_id' => $request->program_id,
            'college_id' => $request->college_id,
        ]);

        // Return success response
        return response()->json([
            'status' => 'success',
            'message' => 'Subtopic updated successfully!',
            'data' => $subtopic
        ], 200); // HTTP 200 for "OK"
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        // Fetch the subtopic to delete
        $subtopic = Subtopic::find($id);

        // Check if subtopic exists
        if (!$subtopic) {
            return response()->json([
                'status' => 'error',
                'message' => 'Subtopic not found.',
                'data' => null
            ], 404); // HTTP 404 for "Not Found"
        }

        // Delete the subtopic
        $subtopic->delete();

        // Return success response
        return response()->json([
            'status' => 'success',
            'message' => 'Subtopic deleted successfully!'
        ], 200); // HTTP 200 for "OK"
    }
}
