<?php

namespace App\Http\Controllers;

use App\Models\Topic;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class TopicController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        // Fetch all topics with related program and college (eager loading)
        $topics = Topic::with('program', 'college')->get(); // Use paginate() if needed

        // Check if topics are found
        if ($topics->isEmpty()) {
            return response()->json([
                'status' => 'error',
                'message' => 'No topics found.',
                'data' => []
            ], 404); // HTTP 404 for "not found"
        }

        // Return topics with success message
        return response()->json([
            'status' => 'success',
            'message' => 'Topics retrieved successfully.',
            'data' => $topics
        ], 200); // HTTP 200 for "OK"
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // Validate incoming request data
        $validator = Validator::make($request->all(), [
            'topic_name' => 'required|string|max:255',
            'program_id' => 'required|exists:programs,id',
            'college_id' => 'required|exists:colleges,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => $validator->errors()
            ], 400); // HTTP 400 for "Bad Request"
        }

        // Create a new topic
        $topic = Topic::create([
            'topic_name' => $request->topic_name,
            'program_id' => $request->program_id,
            'college_id' => $request->college_id,
        ]);

        // Return success response
        return response()->json([
            'status' => 'success',
            'message' => 'Topic created successfully!',
            'data' => $topic
        ], 201); // HTTP 201 for "Created"
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        // Fetch a specific topic by ID
        $topic = Topic::with('program', 'college')->find($id);

        // Check if topic exists
        if (!$topic) {
            return response()->json([
                'status' => 'error',
                'message' => 'Topic not found.',
                'data' => null
            ], 404); // HTTP 404 for "not found"
        }

        // Return the topic data
        return response()->json([
            'status' => 'success',
            'message' => 'Topic retrieved successfully.',
            'data' => $topic
        ], 200); // HTTP 200 for "OK"
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        // Fetch the topic to update
        $topic = Topic::find($id);

        // Check if topic exists
        if (!$topic) {
            return response()->json([
                'status' => 'error',
                'message' => 'Topic not found.',
                'data' => null
            ], 404); // HTTP 404 for "not found"
        }

        // Validate incoming data
        $validator = Validator::make($request->all(), [
            'topic_name' => 'required|string|max:255',
            'program_id' => 'required|exists:programs,id',
            'college_id' => 'required|exists:colleges,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => $validator->errors()
            ], 400); // HTTP 400 for "Bad Request"
        }

        // Update the topic with the validated data
        $topic->update([
            'topic_name' => $request->topic_name,
            'program_id' => $request->program_id,
            'college_id' => $request->college_id,
        ]);

        // Return success response
        return response()->json([
            'status' => 'success',
            'message' => 'Topic updated successfully!',
            'data' => $topic
        ], 200); // HTTP 200 for "OK"
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        // Fetch the topic to delete
        $topic = Topic::find($id);

        // Check if topic exists
        if (!$topic) {
            return response()->json([
                'status' => 'error',
                'message' => 'Topic not found.',
                'data' => null
            ], 404); // HTTP 404 for "not found"
        }

        // Delete the topic
        $topic->delete();

        // Return success response
        return response()->json([
            'status' => 'success',
            'message' => 'Topic deleted successfully!'
        ], 200); // HTTP 200 for "OK"
    }
}
