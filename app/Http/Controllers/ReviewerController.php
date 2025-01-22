<?php

namespace App\Http\Controllers;

use App\Models\Reviewer;
use App\Models\Topic;
use App\Models\Subtopic;
use App\Models\Program;
use App\Models\College;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class ReviewerController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Reviewer::with(['college', 'program']);

        if ($request->has('college_id')) {
            $query->where('college_id', $request->college_id);
        }

        if ($request->has('program_id')) {
            $query->where('program_id', $request->program_id);
        }
        if ($request->has('id')) {
            $query->where('id', $request->id);
        }

        $reviewers = $query->paginate(6);

        if ($reviewers->isEmpty()) {
            return response()->json([
                'status' => 'error',
                'message' => 'No reviewers found with the specified criteria.',
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
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Reviewers retrieved successfully.',
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
        ], 200);
    }



    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'reviewer_name' => 'required|string|max:255',
            'reviewer_description' => 'required',
            'program_id' => 'required|exists:programs,id',
            'college_id' => 'required|exists:colleges,id',
            'school_year' => 'nullable|integer',
            'topic_ids' => 'nullable|array',
            'topic_ids.*' => 'exists:topics,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => $validator->errors()], 400);
        }

        $reviewer = Reviewer::create([
            'reviewer_name' => $request->reviewer_name,
            'reviewer_description' => $request->reviewer_description,
            'program_id' => $request->program_id,
            'college_id' => $request->college_id,
            'school_year' => $request->school_year,
        ]);

        if ($request->topic_ids) {
            $reviewer->topics()->attach($request->topic_ids);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Reviewer created successfully!',
            'data' => $reviewer->load('topics', 'college', 'program'),
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Reviewer $reviewer)
    {
        return response()->json([
            'status' => 'success',
            'message' => 'Reviewer retrieved successfully.',
            'data' => $reviewer->load(['college', 'program']),
        ], 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Reviewer $reviewer)
    {
        $validator = Validator::make($request->all(), [
            'reviewer_name' => 'required|string|max:255',
            'reviewer_description' => 'required',
            'program_id' => 'required|exists:programs,id',
            'college_id' => 'required|exists:colleges,id',
            'school_year' => 'nullable|integer',
            'topic_ids' => 'nullable|array',
            'topic_ids.*' => 'exists:topics,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => $validator->errors()], 400);
        }

        $reviewer->update([
            'reviewer_name' => $request->reviewer_name,
            'reviewer_description' => $request->reviewer_description,
            'program_id' => $request->program_id,
            'college_id' => $request->college_id,
            'school_year' => $request->school_year,
        ]);

        $reviewer->topics()->sync($request->topic_ids);

        return response()->json([
            'status' => 'success',
            'message' => 'Reviewer updated successfully.',
            'data' => $reviewer->load('topics', 'college', 'program'),
        ], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Reviewer $reviewer)
    {
        $reviewer->topics()->detach();

        $reviewer->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Reviewer deleted successfully.',
        ], 200);
    }

    public function getQuestions() {}

    public function getByCollege($id)
    {
        $reviewers = Reviewer::where('college_id', $id)
            ->with(['topics', 'college', 'program'])
            ->paginate(6);

        if ($reviewers->isEmpty()) {
            return response()->json([
                'status' => 'error',
                'message' => 'No reviewers found for the specified college.',
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
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Reviewers retrieved successfully.',
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
        ], 200);
    }
    public function getByProgram($id)
    {
        $reviewers = Reviewer::where('program_id', $id)
            ->with(['topics', 'college', 'program'])
            ->paginate(6);

        if ($reviewers->isEmpty()) {
            return response()->json([
                'status' => 'error',
                'message' => 'No reviewers found for the specified program.',
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
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Reviewers retrieved successfully.',
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
        ], 200);
    }

    public function getReviewer(Request $request)
    {
        $collegeId = $request->query('college_id');
        $programId = $request->query('program_id');
        // $page = $request->query('page', 1);

        $query = Reviewer::query();

        if ($collegeId) {
            $query->where('college_id', $collegeId);
        }
        if ($programId) {
            $query->where('program_id', $programId);
        }


        $reviewer = $query->firstOrFail();
        // Log::info('Reviewer', $reviewer);

        return response()->json([
            'status' => 'success',
            'message' => 'Reviewer retrieved successfully.',
            'data' => [
                'reviewer' => $reviewer,
            ]
        ], 200);
    }
}
