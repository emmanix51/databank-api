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
    public function index(Request $request)
    {
        $query = Topic::with(['reviewer']);

        if ($request->has('reviewer_id')) {
            $query->where('reviewer_id', $request->reviewer_id);
        }
        if ($request->has('id')) {
            $query->where('id', $request->id);
        }
        // if ($request->has('topic_id')) {
        //     $query->where('topic_id', $request->topic_id);
        // }

        $topics = $query->paginate(6);

        if ($topics->isEmpty()) {
            return response()->json([
                'status' => 'error',
                'message' => 'No topics found.',
                'data' => []
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Topics retrieved successfully.',
            'data' => [
                'topics' => $topics->items(),
                'pagination' => [
                    'current_page' => $topics->currentPage(),
                    'total_pages' => $topics->lastPage(),
                    'total_items' => $topics->total(),
                    'per_page' => $topics->perPage(),
                    'first_page_url' => $topics->url(1),
                    'last_page_url' => $topics->url($topics->lastPage()),
                    'next_page_url' => $topics->nextPageUrl(),
                    'prev_page_url' => $topics->previousPageUrl(),
                ],
            ],
        ], 200);
    }


    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // Validate the request
        $validator = Validator::make($request->all(), [
            'topic_name' => 'required|string|max:255',
            'topic_description' => 'required|string',
            'program_id' => 'required|exists:programs,id',
            'reviewer_id' => 'required|exists:reviewers,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => $validator->errors()
            ], 400);
        }

        // Create a topic
        $topic = Topic::create([
            'topic_name' => $request->topic_name,
            'topic_description' => $request->topic_description,
            'program_id' => $request->program_id,
            'reviewer_id' => $request->reviewer_id,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Topic created successfully!',
            'data' => $topic
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        // Retrieve topic with relationships
        $topic = Topic::with(['program', 'reviewer'])->find($id);

        if (!$topic) {
            return response()->json([
                'status' => 'error',
                'message' => 'Topic not found.'
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Topic retrieved successfully.',
            'data' => $topic
        ], 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $topic = Topic::find($id);

        if (!$topic) {
            return response()->json([
                'status' => 'error',
                'message' => 'Topic not found.'
            ], 404);
        }

        // Validate the request
        $validator = Validator::make($request->all(), [
            'topic_name' => 'required|string|max:255',
            'topic_description' => 'required|string',
            'program_id' => 'required|exists:programs,id',
            'reviewer_id' => 'required|exists:reviewers,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => $validator->errors()
            ], 400);
        }

        // Update the topic
        $topic->update([
            'topic_name' => $request->topic_name,
            'topic_description' => $request->topic_description,
            'program_id' => $request->program_id,
            'reviewer_id' => $request->reviewer_id,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Topic updated successfully!',
            'data' => $topic
        ], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $topic = Topic::find($id);

        if (!$topic) {
            return response()->json([
                'status' => 'error',
                'message' => 'Topic not found.'
            ], 404);
        }

        $topic->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Topic deleted successfully!'
        ], 200);
    }

    public function getTopics(Request $request)
    {
        $revieweId = $request->query('reviewer_id');
        $page = $request->query('page', 1); 

        $query = Topic::query();

        if ($revieweId) {
            $query->where('reviewer_id', $revieweId);
        }

        

        $topics = $query->paginate(6, ['*'], 'page', $page);

        return response()->json([
            'status' => 'success',
            'message' => 'Topics retrieved successfully.',
            'data' => [
                'topics' => $topics->items(),
                'pagination' => [
                    'current_page' => $topics->currentPage(),
                    'total_pages' => $topics->lastPage(),
                    'total_items' => $topics->total(),
                    'per_page' => $topics->perPage(),
                    'first_page_url' => $topics->url(1),
                    'last_page_url' => $topics->url($topics->lastPage()),
                    'next_page_url' => $topics->nextPageUrl(),
                    'prev_page_url' => $topics->previousPageUrl(),
                ]
            ]
        ], 200);
    }


    // public function getTopicsByReviewer(Request $request)
    // {
    //     $reviewerId = $request->query('reviewer_id');

    //     if (!$reviewerId) {
    //         return response()->json([
    //             'status' => 'error',
    //             'message' => 'Reviewer ID is required.',
    //         ], 400);
    //     }

    //     $topics = Topic::with(['program', 'reviewer'])
    //         ->where('reviewer_id', $reviewerId)
    //         ->paginate(6);

    //     if ($topics->isEmpty()) {
    //         return response()->json([
    //             'status' => 'error',
    //             'message' => 'No topics found for this reviewer.',
    //             'data' => []
    //         ], 404);
    //     }

    //     return response()->json([
    //         'status' => 'success',
    //         'message' => 'Topics retrieved successfully.',
    //         'data' => [
    //             'topics' => $topics->items(),
    //             'pagination' => [
    //                 'current_page' => $topics->currentPage(),
    //                 'total_pages' => $topics->lastPage(),
    //                 'total_items' => $topics->total(),
    //                 'per_page' => $topics->perPage(),
    //                 'first_page_url' => $topics->url(1),
    //                 'last_page_url' => $topics->url($topics->lastPage()),
    //                 'next_page_url' => $topics->nextPageUrl(),
    //                 'prev_page_url' => $topics->previousPageUrl(),
    //             ],
    //         ],
    //     ], 200);
    // }
}
