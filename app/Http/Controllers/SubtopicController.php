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
    public function index(Request $request)
    {
        $query = Subtopic::with('topic');

        if ($request->has('topic_id')) {
            $query->where('topic_id', $request->topic_id);
        }
        if ($request->has('id')) {
            $query->where('id', $request->id);
        }

        $subtopics = $query->paginate(6);

        if ($subtopics->isEmpty()) {
            return response()->json([
                'status' => 'error',
                'message' => 'No subtopics found.',
                'data' => []
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Subtopics retrieved successfully.',
            'data' => [
                'subtopics' => $subtopics->items(),
                'pagination' => [
                    'current_page' => $subtopics->currentPage(),
                    'total_pages' => $subtopics->lastPage(),
                    'total_items' => $subtopics->total(),
                    'per_page' => $subtopics->perPage(),
                    'first_page_url' => $subtopics->url(1),
                    'last_page_url' => $subtopics->url($subtopics->lastPage()),
                    'next_page_url' => $subtopics->nextPageUrl(),
                    'prev_page_url' => $subtopics->previousPageUrl(),
                ],
            ],
        ], 200);
    }


    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // Validate the incoming request data
        $validator = Validator::make($request->all(), [
            'subtopic_name' => 'required|string|max:255',
            'subtopic_description' => 'required|string',
            'topic_id' => 'required|exists:topics,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => $validator->errors(),
            ], 400);
        }

        // Create a new subtopic
        $subtopic = Subtopic::create([
            'subtopic_name' => $request->subtopic_name,
            'subtopic_description' => $request->subtopic_description,
            'topic_id' => $request->topic_id,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Subtopic created successfully!',
            'data' => $subtopic,
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        // Fetch a specific subtopic with its related topic
        $subtopic = Subtopic::with('topic')->find($id);

        if (!$subtopic) {
            return response()->json([
                'status' => 'error',
                'message' => 'Subtopic not found.',
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Subtopic retrieved successfully.',
            'data' => $subtopic,
        ], 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        // Fetch the subtopic to update
        $subtopic = Subtopic::find($id);

        if (!$subtopic) {
            return response()->json([
                'status' => 'error',
                'message' => 'Subtopic not found.',
            ], 404);
        }

        // Validate the incoming request data
        $validator = Validator::make($request->all(), [
            'subtopic_name' => 'required|string|max:255',
            'subtopic_description' => 'required|string',
            'topic_id' => 'required|exists:topics,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => $validator->errors(),
            ], 400);
        }

        // Update the subtopic
        $subtopic->update([
            'subtopic_name' => $request->subtopic_name,
            'subtopic_description' => $request->subtopic_description,
            'topic_id' => $request->topic_id,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Subtopic updated successfully!',
            'data' => $subtopic,
        ], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        // Fetch the subtopic
        $subtopic = Subtopic::find($id);

        if (!$subtopic) {
            return response()->json([
                'status' => 'error',
                'message' => 'Subtopic not found.',
            ], 404);
        }

        // Delete the subtopic
        $subtopic->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Subtopic deleted successfully!',
        ], 200);
    }

    public function getSubtopics(Request $request)
    {
        $topicId = $request->query('topic_id');
        $page = $request->query('page', 1); 

        $query = Subtopic::query();

        if ($topicId) {
            $query->where('topic_id', $topicId);
        }

        

        $subtopics = $query->paginate(6, ['*'], 'page', $page);

        return response()->json([
            'status' => 'success',
            'message' => 'Subtopics retrieved successfully.',
            'data' => [
                'subtopics' => $subtopics->items(),
                'pagination' => [
                    'current_page' => $subtopics->currentPage(),
                    'total_pages' => $subtopics->lastPage(),
                    'total_items' => $subtopics->total(),
                    'per_page' => $subtopics->perPage(),
                    'first_page_url' => $subtopics->url(1),
                    'last_page_url' => $subtopics->url($subtopics->lastPage()),
                    'next_page_url' => $subtopics->nextPageUrl(),
                    'prev_page_url' => $subtopics->previousPageUrl(),
                ]
            ]
        ], 200);
    }

    /**
     * Get subtopics by topic ID.
     */
    // public function getSubtopicsByTopic(Request $request)
    // {
    //     $topicId = $request->query('topic_id');

    //     if (!$topicId) {
    //         return response()->json([
    //             'status' => 'error',
    //             'message' => 'Topic ID is required.',
    //         ], 400);
    //     }

    //     $subtopics = Subtopic::with('topic')
    //         ->where('topic_id', $topicId)
    //         ->paginate(6);

    //     if ($subtopics->isEmpty()) {
    //         return response()->json([
    //             'status' => 'error',
    //             'message' => 'No subtopics found for this topic.',
    //             'data' => []
    //         ], 404);
    //     }

    //     return response()->json([
    //         'status' => 'success',
    //         'message' => 'Subtopics retrieved successfully.',
    //         'data' => [
    //             'subtopics' => $subtopics->items(),
    //             'pagination' => [
    //                 'current_page' => $subtopics->currentPage(),
    //                 'total_pages' => $subtopics->lastPage(),
    //                 'total_items' => $subtopics->total(),
    //                 'per_page' => $subtopics->perPage(),
    //                 'first_page_url' => $subtopics->url(1),
    //                 'last_page_url' => $subtopics->url($subtopics->lastPage()),
    //                 'next_page_url' => $subtopics->nextPageUrl(),
    //                 'prev_page_url' => $subtopics->previousPageUrl(),
    //             ],
    //         ],
    //     ], 200);
    // }
}
