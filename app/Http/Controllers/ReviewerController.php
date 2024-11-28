<?php

namespace App\Http\Controllers;

use App\Models\Reviewer;
use App\Models\Topic;
use App\Models\Subtopic;
use App\Models\Program;
use App\Models\College;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ReviewerController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        // Retrieve reviewers with related data (you can adjust the relationships as needed)
        $reviewers = Reviewer::with(['topic', 'subtopic', 'program', 'college'])->paginate(6);

        // Check if no reviewers are found
        if ($reviewers->isEmpty()) {
            return response()->json([
                'status' => 'error',
                'message' => 'No reviewers found.',
                'data' => [
                    'reviewers' => [],
                    'pagination' => [
                        'current_page' => $reviewers->currentPage(),
                        'total_pages' => $reviewers->lastPage(),
                        'total_items' => $reviewers->total(),
                        'per_page' => $reviewers->perPage(),
                        'first_page_url' => $reviewers->url(1),
                        'last_page_url' => $reviewers->url($reviewers->lastPage()),
                        'next_page_url' => $reviewers->nextPageUrl(),
                        'prev_page_url' => $reviewers->previousPageUrl(),
                    ]
                ]
            ], 404); // HTTP 404 for "not found"
        }

        // Return success response with reviewers data and pagination
        return response()->json([
            'status' => 'success',
            'message' => 'Request processed successfully.',
            'data' => [
                'reviewers' => $reviewers->items(),
                'pagination' => [
                    'current_page' => $reviewers->currentPage(),
                    'total_pages' => $reviewers->lastPage(),
                    'total_items' => $reviewers->total(),
                    'per_page' => $reviewers->perPage(),
                    'first_page_url' => $reviewers->url(1),
                    'last_page_url' => $reviewers->url($reviewers->lastPage()),
                    'next_page_url' => $reviewers->nextPageUrl(),
                    'prev_page_url' => $reviewers->previousPageUrl(),
                ]
            ]
        ], 200); // HTTP 200 for "OK"
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // Validate incoming data
        $validator = Validator::make($request->all(), [
            'reviewer_name' => 'required|string|max:255',
            'topic_id' => 'required|exists:topics,id',
            'subtopic_id' => 'required|exists:subtopics,id',
            'program_id' => 'required|exists:programs,id',
            'college_id' => 'required|exists:colleges,id',
            'school_year' => 'nullable|integer',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => $validator->errors()
            ], 400); // Bad Request
        }

        // Create new reviewer record
        $reviewer = new Reviewer();
        $reviewer->reviewer_name = $request->reviewer_name;
        $reviewer->topic_id = $request->topic_id;
        $reviewer->subtopic_id = $request->subtopic_id;
        $reviewer->program_id = $request->program_id;
        $reviewer->college_id = $request->college_id;
        $reviewer->school_year = $request->school_year;
        $reviewer->save();

        // Return success response with created reviewer data
        return response()->json([
            'status' => 'success',
            'message' => 'Reviewer created successfully!',
            'data' => $reviewer
        ], 201); // HTTP 201 for "Created"
    }

    /**
     * Display the specified resource.
     */
    public function show(Reviewer $reviewer)
    {
        // Return the reviewer data along with related data
        return response()->json([
            'status' => 'success',
            'message' => 'Reviewer details retrieved successfully.',
            'data' => $reviewer->load(['topic', 'subtopic', 'program', 'college'])
        ], 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Reviewer $reviewer)
    {
        // Validate incoming data
        $validator = Validator::make($request->all(), [
            'reviewer_name' => 'required|string|max:255',
            'topic_id' => 'required|exists:topics,id',
            'subtopic_id' => 'required|exists:subtopics,id',
            'program_id' => 'required|exists:programs,id',
            'college_id' => 'required|exists:colleges,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => $validator->errors()
            ], 400); // Bad Request
        }

        // Update the reviewer record
        $reviewer->reviewer_name = $request->reviewer_name;
        $reviewer->topic_id = $request->topic_id;
        $reviewer->subtopic_id = $request->subtopic_id;
        $reviewer->program_id = $request->program_id;
        $reviewer->college_id = $request->college_id;
        $reviewer->save();

        // Return success response with updated reviewer data
        return response()->json([
            'status' => 'success',
            'message' => 'Reviewer updated successfully!',
            'data' => $reviewer
        ], 200); // HTTP 200 for "OK"
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Reviewer $reviewer)
    {
        // Delete the reviewer record
        $reviewer->delete();

        // Return success response after deletion
        return response()->json([
            'status' => 'success',
            'message' => 'Reviewer deleted successfully.'
        ], 200); // HTTP 200 for "OK"
    }
}
