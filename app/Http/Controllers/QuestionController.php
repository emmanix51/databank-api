<?php

namespace App\Http\Controllers;

use App\Models\Question;
use App\Models\QuestionChoice;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;

class QuestionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Question::with('choices');

        if ($request->has('reviewer_id')) {
            $query->where('reviewer_id', $request->reviewer_id);
        }
        if ($request->has('subtopic_id')) {
            $query->where('subtopic_id', $request->subtopic_id);
        }
        if ($request->has('id')) {
            $query->where('id', $request->id);
        }

        $questions = $query->paginate(6);

        if ($questions->isEmpty()) {
            return response()->json([
                'status' => 'error',
                'message' => 'No questions found.',
                'data' => []
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Questions retrieved successfully.',
            'data' => [
                'questions' => $questions->items(),
                'pagination' => [
                    'current_page' => $questions->currentPage(),
                    'total_pages' => $questions->lastPage(),
                    'total_items' => $questions->total(),
                    'per_page' => $questions->perPage(),
                ],
            ],
        ], 200);
    }


    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'questions' => 'required|array|min:1',
            'questions.*.question_content' => 'required|string',
            'questions.*.correct_answer' => 'required|string|max:255',
            'questions.*.question_point' => 'required|numeric|min:0',
            'questions.*.reviewer_id' => 'required|exists:reviewers,id',
            'questions.*.topic_id' => 'nullable|exists:topics,id',
            'questions.*.subtopic_id' => 'nullable|exists:subtopics,id',
            'questions.*.choices' => 'required|array|min:2',
            'questions.*.choices.*.choice_index' => 'required|string|max:1',
            'questions.*.choices.*.choice_content' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => $validator->errors(),
            ], 400);
        }

        $createdQuestions = [];

        foreach ($request->questions as $questionData) {
            $question = Question::create([
                'question_content' => $questionData['question_content'],
                'correct_answer' => $questionData['correct_answer'],
                'question_point' => $questionData['question_point'],
                'reviewer_id' => $questionData['reviewer_id'],
                'topic_id' => $questionData['topic_id'] ?? null,
                'subtopic_id' => $questionData['subtopic_id'] ?? null,
            ]);

            foreach ($questionData['choices'] as $choice) {
                QuestionChoice::create([
                    'question_id' => $question->id,
                    'choice_index' => $choice['choice_index'],
                    'choice_content' => $choice['choice_content'],
                ]);
            }

            $createdQuestions[] = $question->load('choices', 'subtopic', 'topic');
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Questions created successfully!',
            'data' => $createdQuestions,
        ], 201);
    }


    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $question = Question::with('topic', 'subtopic', 'choices')->find($id);

        if (!$question) {
            return response()->json([
                'status' => 'error',
                'message' => 'Question not found.',
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Question retrieved successfully.',
            'data' => $question,
        ], 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $question = Question::find($id);

        if (!$question) {
            return response()->json([
                'status' => 'error',
                'message' => 'Question not found.',
            ], 404);
        }

        if ($question->status === 'locked') {
            if (!$request->user()->isAdmin()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'This question is locked and only admins can update it.',
                ], 403);
            }
        }

        $validator = Validator::make($request->all(), [
            'question_content' => 'required|string',
            'correct_answer' => 'required|string|max:255',
            'question_point' => 'required|numeric|min:0',
            'reviewer_id' => 'required|exists:reviewers,id',
            'topic_id' => 'nullable|exists:topics,id',
            'subtopic_id' => 'nullable|exists:subtopics,id',
            'choices' => 'required|array|min:2',
            'choices.*.choice_index' => 'required|string|max:1',
            'choices.*.choice_content' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => $validator->errors(),
            ], 400);
        }

        $question->update([
            'question_content' => $request->question_content,
            'correct_answer' => $request->correct_answer,
            'question_point' => $request->question_point,
            'reviewer_id' => $request->reviewer_id,
            'topic_id' => $request->topic_id,
            'subtopic_id' => $request->subtopic_id,
        ]);

        // Updating choices
        $existingChoices = $question->choices()->pluck('id')->toArray();
        $newChoices = $request->choices;

        foreach ($newChoices as $index => $choice) {
            if (isset($existingChoices[$index])) {
                // Update existing choice
                QuestionChoice::where('id', $existingChoices[$index])->update([
                    'choice_index' => $choice['choice_index'],
                    'choice_content' => $choice['choice_content'],
                ]);
            } else {
                // Create a new choice if it exceeds existing count
                QuestionChoice::create([
                    'question_id' => $question->id,
                    'choice_index' => $choice['choice_index'],
                    'choice_content' => $choice['choice_content'],
                ]);
            }
        }

        // Delete any extra choices if the number was reduced
        if (count($newChoices) < count($existingChoices)) {
            $choicesToDelete = array_slice($existingChoices, count($newChoices));
            QuestionChoice::whereIn('id', $choicesToDelete)->delete();
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Question updated successfully!',
            'data' => $question->load('choices', 'subtopic'),
        ], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $question = Question::find($id);

        if (!$question) {
            return response()->json([
                'status' => 'error',
                'message' => 'Question not found.',
            ], 404);
        }

        if ($question->status === 'locked') {
            // Ensure only admins can delete a locked question
            if (!auth()->user()->isAdmin()) {  // Check if the user is an admin
                return response()->json([
                    'status' => 'error',
                    'message' => 'This question is locked and only admins can delete it.',
                ], 403);
            }
        }

        $question->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Question deleted successfully!',
        ], 200);
    }

    public function getQuestions(Request $request)
    {
        $reviewerId = $request->query('reviewer_id');
        $topicId = $request->query('topic_id');
        $subtopicId = $request->query('subtopic_id');
        $page = $request->query('page', 1);

        $query = Question::query();

        if ($reviewerId) {
            $query->where('reviewer_id', $reviewerId);
        }
        if ($topicId) {
            $query->where('topic_id', $topicId);
        }
        if ($subtopicId) {
            $query->where('subtopic_id', $subtopicId);
        }



        $questions = $query->paginate(6, ['*'], 'page', $page);

        return response()->json([
            'status' => 'success',
            'message' => 'Questions retrieved successfully.',
            'data' => [
                'questions' => $questions->items(),
                'pagination' => [
                    'current_page' => $questions->currentPage(),
                    'total_pages' => $questions->lastPage(),
                    'total_items' => $questions->total(),
                    'per_page' => $questions->perPage(),
                    'first_page_url' => $questions->url(1),
                    'last_page_url' => $questions->url($questions->lastPage()),
                    'next_page_url' => $questions->nextPageUrl(),
                    'prev_page_url' => $questions->previousPageUrl(),
                ]
            ]
        ], 200);
    }

    public function bulkStore(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'questions' => 'required|array|min:1',
            'questions.*.question_content' => 'required|string',
            'questions.*.correct_answer' => 'required|string|max:255',
            'questions.*.question_point' => 'required|numeric|min:0',
            'questions.*.reviewer_id' => 'required|exists:reviewers,id',
            'questions.*.topic_id' => 'nullable|exists:topics,id',
            'questions.*.subtopic_id' => 'nullable|exists:subtopics,id',
            'questions.*.choices' => 'required|array|min:2',
            'questions.*.choices.*.choice_index' => 'required|string|max:1',
            'questions.*.choices.*.choice_content' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => $validator->errors(),
            ], 400);
        }

        $createdQuestions = [];

        foreach ($request->questions as $questionData) {
            $question = Question::create([
                'question_content' => $questionData['question_content'],
                'correct_answer' => $questionData['correct_answer'],
                'question_point' => $questionData['question_point'],
                'reviewer_id' => $questionData['reviewer_id'],
                'topic_id' => $questionData['topic_id'] ?? null,
                'subtopic_id' => $questionData['subtopic_id'] ?? null,
            ]);

            foreach ($questionData['choices'] as $choice) {
                QuestionChoice::create([
                    'question_id' => $question->id,
                    'choice_index' => $choice['choice_index'],
                    'choice_content' => $choice['choice_content'],
                ]);
            }

            $createdQuestions[] = $question->load('choices', 'subtopic', 'topic');
            Log::info('Created Questions Data:', $createdQuestions);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Bulk Questions created successfully!',
            'data' => $createdQuestions,
        ], 201);
    }

    public function updateStatus(Request $request, string $id)
    {
        $question = Question::find($id);

        if (!$question) {
            return response()->json([
                'status' => 'error',
                'message' => 'Question not found.',
            ], 404);
        }

        if (!in_array($request->user()->role, ['dean', 'programhead'])) {
            return response()->json([
                'status' => 'error',
                'message' => 'You do not have permission to change the status of this question.',
            ], 403);
        }

        // Ensure the new status is valid
        $status = $request->input('status');
        if (!in_array($status, ['active', 'locked'])) {
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid status provided.',
            ], 400);
        }

        $question->status = $status;
        $question->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Question status updated successfully!',
            'data' => $question,
        ], 200);
    }

    // public function update(Request $request, string $id)
    // {
    //     $question = Question::find($id);

    //     if (!$question) {
    //         return response()->json([
    //             'status' => 'error',
    //             'message' => 'Question not found.',
    //         ], 404);
    //     }

    //     if ($question->status === 'locked') {
    //         // Ensure only admins can update a locked question
    //         if (!$request->user()->isAdmin()) {  // Assuming you have an `isAdmin()` method in your User model
    //             return response()->json([
    //                 'status' => 'error',
    //                 'message' => 'This question is locked and only admins can update it.',
    //             ], 403);
    //         }
    //     }

    //     $validator = Validator::make($request->all(), [
    //         'question_content' => 'required|string',
    //         'correct_answer' => 'required|string|max:255',
    //         'question_point' => 'required|numeric|min:0',
    //         'reviewer_id' => 'required|exists:reviewers,id',
    //         'topic_id' => 'nullable|exists:topics,id',
    //         'subtopic_id' => 'nullable|exists:subtopics,id',
    //         'choices' => 'required|array|min:2',
    //         'choices.*.choice_index' => 'required|string|max:1',
    //         'choices.*.choice_content' => 'required|string|max:255',
    //     ]);

    //     if ($validator->fails()) {
    //         return response()->json([
    //             'status' => 'error',
    //             'message' => $validator->errors(),
    //         ], 400);
    //     }

    //     $question->update([
    //         'question_content' => $request->question_content,
    //         'correct_answer' => $request->correct_answer,
    //         'question_point' => $request->question_point,
    //         'reviewer_id' => $request->reviewer_id,
    //         'topic_id' => $request->topic_id,
    //         'subtopic_id' => $request->subtopic_id,
    //     ]);

    //     $question->choices()->delete();
    //     foreach ($request->choices as $choice) {
    //         QuestionChoice::create([
    //             'question_id' => $question->id,
    //             'choice_index' => $choice['choice_index'],
    //             'choice_content' => $choice['choice_content'],
    //         ]);
    //     }

    //     return response()->json([
    //         'status' => 'success',
    //         'message' => 'Question updated successfully!',
    //         'data' => $question->load('choices', 'subtopic'),
    //     ], 200);
    // }


    //     public function getQuestionsByReviewer(Request $request)
    // {
    //     $validator = Validator::make($request->all(), [
    //         'reviewer_id' => 'required|exists:reviewers,id',
    //     ]);

    //     if ($validator->fails()) {
    //         return response()->json([
    //             'status' => 'error',
    //             'message' => $validator->errors(),
    //         ], 400);
    //     }

    //     $reviewer_id = $request->query('reviewer_id');

    //     $questions = Question::with('subtopic', 'choices', 'topic')
    //         ->where('reviewer_id', $reviewer_id)
    //         ->paginate(6);

    //     if ($questions->isEmpty()) {
    //         return response()->json([
    //             'status' => 'error',
    //             'message' => 'No questions found for this reviewer.',
    //             'data' => [],
    //         ], 404);
    //     }

    //     return response()->json([
    //         'status' => 'success',
    //         'message' => 'Questions retrieved successfully.',
    //         'data' => [
    //             'questions' => $questions->items(),
    //             'pagination' => [
    //                 'current_page' => $questions->currentPage(),
    //                 'total_pages' => $questions->lastPage(),
    //                 'total_items' => $questions->total(),
    //                 'per_page' => $questions->perPage(),
    //             ],
    //         ],
    //     ], 200);
    // }

}



// {
//     "questions": [
//         {
//             "question_content": "what?", 
//             "correct_answer": "A",
//             "question_point": 1, 
//             "reviewer_id": 1,
//             "topic_id": 2,
//             "subtopic_id": 3,
//             "choices": [
//                 {"choice_index": "A", "choice_content": "ha"}, 
//                 {"choice_index": "B", "choice_content": "o"} 
//             ]
//         },
//         {
//             "question_content": "what is 2 + 2?", 
//             "correct_answer": "B",
//             "question_point": 3,
//             "reviewer_id": 1,
//             "choices": [
//                 {"choice_index": "A", "choice_content": "3"},
//                 {"choice_index": "B", "choice_content": "4"}
//             ]
//         }
//     ]
// }
