<?php

namespace App\Http\Controllers;

use App\Models\Question;
use App\Models\ReviewerAttempt;
use App\Models\ReviewerAttemptSpecification;
use App\Models\ReviewerAttemptQuestion;
use App\Models\ReviewerAttemptAnswer;
use App\Models\Result;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

use Illuminate\Support\Facades\Log;


class ReviewerAttemptController extends Controller
{
    public function generateAttempt(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'reviewer_id' => 'required|exists:reviewers,id',
            'user_id' => 'required|exists:users,id',
            'status' => 'required|string',
            'score' => 'nullable|integer|min:0',
            'topic_ids' => 'nullable|array',
            'topic_ids.*' => 'exists:topics,id',
            'subtopic_ids' => 'nullable|array',
            'subtopic_ids.*' => 'exists:subtopics,id',
            'question_amount' => 'required|integer|min:1,max:70',
            'time_limit' => 'required|integer|min:1,max:120',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => $validator->errors(),
            ], 400);
        }

        // $expireTime = now()->addMinutes($request->time_limit)->timezone('Asia/Manila');
        $expireTime = now()->addMinutes($request->time_limit);

        $reviewerAttempt = ReviewerAttempt::create([
            'user_id' => $request->user_id,
            'reviewer_id' => $request->reviewer_id,
            'status' => $request->status,
            'score' => $request->score ?? 0,
            'time_remaining' => $request->time_limit,
            'expire_time' => $expireTime,
        ]);

        $specification = ReviewerAttemptSpecification::create([
            'reviewer_attempt_id' => $reviewerAttempt->id,
            'question_amount' => $request->question_amount,
            'time_limit' => $request->time_limit,
        ]);
        // $localExpireTime = $reviewerAttempt->expire_time->timezone('Asia/Manila');

        if (!empty($request->topic_ids)) {
            foreach ($request->topic_ids as $topicId) {
                DB::table('reviewer_attempt_specification_topics')->insert([
                    'reviewer_attempt_specification_id' => $specification->id,
                    'topic_id' => $topicId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        if (!empty($request->subtopic_ids)) {
            foreach ($request->subtopic_ids as $subtopicId) {
                DB::table('reviewer_attempt_specification_subtopics')->insert([
                    'reviewer_attempt_specification_id' => $specification->id,
                    'subtopic_id' => $subtopicId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        $questions = $this->generateRandomQuestions(
            $request->question_amount,
            $request->topic_ids,
            $request->subtopic_ids
        );

        foreach ($questions as $topicId => $questionGroup) { // Iterate over each topic group
            foreach ($questionGroup as $question) { // Iterate over questions within the group
                ReviewerAttemptQuestion::create([
                    'reviewer_attempt_id' => $reviewerAttempt->id,
                    'question_id' => $question->id,
                    'isFlagged' => false, // Default value
                    'status' => 'unanswered', // Default value
                ]);
            }
        }

        // Convert the stored expire_time to Manila timezone and format it
        $formattedExpireTime = $reviewerAttempt->expire_time->timezone('Asia/Manila')->format('d/m/Y g:i');

        // Log or return the formatted time
        // Log::info('Formatted expire time: ' . $formattedExpireTime);

        Log::info('reviewer attempt: ', [
            'reviewerAttempt' => $reviewerAttempt,
            'expireTime' => $expireTime->toDateTimeString(),
        ]);
        Log::info('reviewer specification: ', ['reviewerSpecification' => $specification]);
        return response()->json([
            'status' => 'success',
            'message' => 'Reviewer attempt generated successfully!',
            'data' => [
                'reviewer_attempt' => $reviewerAttempt->load('questions'),
                'specification' => $specification,
                'formatted_expire_time' => $formattedExpireTime,
            ],
        ], 201);
    }

    public function getAttempt(Request $request)
    {
        $attemptId = $request->query('attempt_id');

        // Check if attemptId is provided
        if (!$attemptId) {
            return response()->json(['status' => 'error', 'message' => 'Attempt ID is required.'], 400);
        }

        // Fetch the ReviewerAttempt by its ID
        $attempt = ReviewerAttempt::find($attemptId);

        // Check if the attempt exists
        if (!$attempt) {
            return response()->json(['status' => 'error', 'message' => 'Attempt not found.'], 404);
        }

        // Return the attempt data
        return response()->json([
            'status' => 'success',
            'data' => $attempt
        ]);
    }

    public function getAttempts(Request $request)
    {
        $userId = $request->query('user_id');
        $reviewerId = $request->query('reviewer_id');
        $attemptId = $request->query('attempt_id');

        if (!$userId && !$attemptId && !$reviewerId) {
            $attempts = ReviewerAttempt::all();
            return response()->json([
                'status' => 'success',
                'data' => $attempts
            ]);
        }

        if ($userId && !$attemptId && !$reviewerId) {
            $attempts = ReviewerAttempt::where('user_id', $userId)->get();
            return response()->json([
                'status' => 'success',
                'data' => $attempts
            ]);
        }

        if (!$userId && !$attemptId && $reviewerId) {
            $attempts = ReviewerAttempt::where('reviewer_id', $reviewerId)->get();
            return response()->json([
                'status' => 'success',
                'data' => $attempts
            ]);
        }

        if (!$userId && !$reviewerId && $attemptId) {
            $attempt = ReviewerAttempt::find($attemptId);

            if (!$attempt) {
                return response()->json(['status' => 'error', 'message' => 'Attempt not found.'], 404);
            }

            return response()->json([
                'status' => 'success',
                'data' => $attempt
            ]);
        }

        if ($userId && $attemptId && !$reviewerId) {
            $attempt = ReviewerAttempt::where('user_id', $userId)
                ->where('id', $attemptId)
                ->first();

            if (!$attempt) {
                return response()->json(['status' => 'error', 'message' => 'Attempt not found for this user.'], 404);
            }

            return response()->json([
                'status' => 'success',
                'data' => $attempt
            ]);
        }

        if (!$userId && $attemptId && $reviewerId) {
            $attempt = ReviewerAttempt::where('reviewer_id', $reviewerId)
                ->where('id', $attemptId)
                ->first();

            if (!$attempt) {
                return response()->json(['status' => 'error', 'message' => 'Attempt not found for this reviewer.'], 404);
            }

            return response()->json([
                'status' => 'success',
                'data' => $attempt
            ]);
        }

        if ($userId && $attemptId && $reviewerId) {
            $attempt = ReviewerAttempt::where('user_id', $userId)
                ->where('reviewer_id', $reviewerId)
                ->where('id', $attemptId)
                ->first();

            if (!$attempt) {
                return response()->json(['status' => 'error', 'message' => 'Attempt not found with the provided criteria.'], 404);
            }

            return response()->json([
                'status' => 'success',
                'data' => $attempt
            ]);
        }
    }



    public function getTimeRemaining(Request $request)
    {

        $attemptId = $request->query('attempt_id');
        $attempt = ReviewerAttempt::find($attemptId);

        if (!$attempt) {
            return response()->json(['status' => 'error', 'message' => 'Attempt not found.'], 404);
        }

        $expireTime = Carbon::parse($attempt->expire_time)->timezone('Asia/Manila');
        $now = Carbon::now('Asia/Manila');  // Get the current time in Manila timezone

        Log::info('expiretime: ', ['expire time' => $expireTime]);
        Log::info('now: ', ['now' => $now]);

        // Calculate the time remaining in minutes
        $timeRemaining = $now->diffInMinutes($expireTime, false);

        return response()->json([
            'status' => 'success',
            'data' => [
                'time_remaining' => max(0, $timeRemaining),
            ],
        ]);
    }



    private function generateRandomQuestions($questionAmount, $topics, $subtopics)
    {
        $query = Question::query();

        if ($topics) {
            $query->whereIn('topic_id', $topics);
        }

        if ($subtopics) {
            $query->whereIn('subtopic_id', $subtopics);
        }

        // Fetch all questions grouped by topic
        $questionsByTopic = $query->get()->groupBy('topic_id');

        $result = collect();

        foreach ($questionsByTopic as $topicId => $questions) {
            // Randomize and limit questions per topic
            $result[$topicId] = $questions->random(min($questions->count(), $questionAmount))->values();
        }

        return $result;
    }

    public function getAttemptQuestions(Request $request)
    {
        $reviewerAttemptId = $request->query('attempt_id');

        if (!$reviewerAttemptId) {
            return response()->json(['status' => 'error', 'message' => 'Attempt ID is required.'], 400);
        }

        // Fetch the attempt and ensure it exists
        $reviewerAttempt = ReviewerAttempt::with('result')->find($reviewerAttemptId);
        if (!$reviewerAttempt) {
            return response()->json(['status' => 'error', 'message' => 'Attempt not found.'], 404);
        }

        // Fetch all questions for the attempt
        $questions = ReviewerAttemptQuestion::where('reviewer_attempt_id', $reviewerAttemptId)
            ->with(['question.choices', 'question.topic', 'question.subtopic', 'answer'])
            ->get();

        // Check if the attempt is completed based on the existence of the result record
        $isCompleted = $reviewerAttempt->result !== null;

        // Group questions by topic and subtopic
        $groupedTopics = $questions->groupBy(fn($item) => $item->question->topic_id)
            ->map(function ($group, $topicId) use ($isCompleted) {
                $topic = $group->first()->question->topic;

                $subtopics = $group->groupBy(fn($item) => $item->question->subtopic_id)
                    ->map(function ($subgroup, $subtopicId) use ($isCompleted) {
                        $subtopic = $subgroup->first()->question->subtopic ?? (object)[
                            'id' => null,
                            'subtopic_name' => 'No Subtopic',
                            'subtopic_description' => 'Questions not assigned to a subtopic',
                        ];

                        return [
                            'id' => $subtopic->id,
                            'name' => $subtopic->subtopic_name,
                            'description' => $subtopic->subtopic_description,
                            'questions' => $subgroup->map(function ($item) use ($isCompleted) {
                                $question = $item->question;

                                // If completed, include correct answer and correctness check
                                return [
                                    'reviewer_attempt_question_id' => $item->id,
                                    'question_id' => $question->id,
                                    'content' => $question->question_content,
                                    'point' => $question->question_point,
                                    'status' => $item->status,
                                    'isFlagged' => $item->isFlagged,
                                    'choices' => $question->choices->map(fn($choice) => [
                                        'id' => $choice->id,
                                        'index' => $choice->choice_index,
                                        'content' => $choice->choice_content,
                                    ]),
                                    'answer' => $item->answer ? $item->answer->answer : null,
                                    // Only show these fields when the attempt is completed
                                    'correct_answer' => $isCompleted ? $question->correct_answer : null,
                                    'is_correct' => $isCompleted ? ($item->answer && $item->answer->answer === $question->correct_answer) : null,
                                ];
                            })->values(),
                        ];
                    });

                return [
                    'id' => $topic->id,
                    'name' => $topic->topic_name,
                    'description' => $topic->topic_description,
                    'subtopics' => $subtopics->values(),
                ];
            })->values();

        // Prepare the response
        $responseData = [
            'status' => 'success',
            'data' => [
                'topics' => $groupedTopics,
                'total_questions' => $questions->count(),
            ],
        ];

        // If the attempt is completed, include additional result data
        if ($isCompleted) {
            $responseData['data']['reviewer_attempt'] = $reviewerAttempt;
            $responseData['data']['result'] = $reviewerAttempt->result;
            $responseData['message'] = 'Attempt completed. Review data included.';
        }

        return response()->json($responseData);
    }

    // public function getAttemptQuestions(Request $request)
    // {
    //     $reviewerAttemptId = $request->query('attempt_id');

    //     if (!$reviewerAttemptId) {
    //         return response()->json(['status' => 'error', 'message' => 'Attempt ID is required.'], 400);
    //     }

    //     $questions = ReviewerAttemptQuestion::where('reviewer_attempt_id', $reviewerAttemptId)
    //         ->with(['question.choices', 'question.topic', 'question.subtopic', 'answer'])
    //         ->get();

    //     $groupedTopics = $questions->groupBy(fn($item) => $item->question->topic_id)
    //         ->map(function ($group, $topicId) {
    //             $topic = $group->first()->question->topic;

    //             if ($topic === null) {
    //                 return null;
    //             }

    //             $subtopics = $group->groupBy(fn($item) => $item->question->subtopic_id)
    //                 ->map(function ($subgroup, $subtopicId) use ($topic) {
    //                     if ($subtopicId == null) {
    //                         return [
    //                             'id' => null,
    //                             'name' => 'No Subtopic',
    //                             'description' => 'Questions not assigned to a subtopic',
    //                             'questions' => $subgroup->map(function ($item) {
    //                                 $question = $item->question;
    //                                 return [
    //                                     'id' => $question->id,
    //                                     'reviewer_attempt_question_id' => $item->id,
    //                                     'content' => $question->question_content,
    //                                     'point' => $question->question_point,
    //                                     'status' => $item->status, // Fetch and include status
    //                                     'isFlagged' => $item->isFlagged,
    //                                     'choices' => $question->choices->map(function ($choice) {
    //                                         return [
    //                                             'id' => $choice->id,
    //                                             'index' => $choice->choice_index,
    //                                             'content' => $choice->choice_content,
    //                                         ];
    //                                     }),
    //                                     'answer' => $item->answer ? $item->answer->answer : null,
    //                                 ];
    //                             })->values(),
    //                         ];
    //                     }

    //                     $subtopic = $subgroup->first()->question->subtopic ?? (object)[
    //                         'id' => null,
    //                         'subtopic_name' => 'No Subtopic',
    //                         'subtopic_description' => 'Questions not assigned to a subtopic',
    //                     ];

    //                     return [
    //                         'id' => $subtopic->id,
    //                         'name' => $subtopic->subtopic_name,
    //                         'description' => $subtopic->subtopic_description,
    //                         'questions' => $subgroup->map(function ($item) {
    //                             $question = $item->question;
    //                             return [
    //                                 'reviewer_attempt_question_id' => $item->id,
    //                                 'question_id' => $question->id,
    //                                 'content' => $question->question_content,
    //                                 'point' => $question->question_point,
    //                                 'status' => $item->status, // Fetch and include status
    //                                 'isFlagged' => $item->isFlagged,
    //                                 'choices' => $question->choices->map(function ($choice) {
    //                                     return [
    //                                         'id' => $choice->id,
    //                                         'index' => $choice->choice_index,
    //                                         'content' => $choice->choice_content,
    //                                     ];
    //                                 }),
    //                                 'answer' => $item->answer ? $item->answer->answer : null,
    //                             ];
    //                         })->values(),
    //                     ];
    //                 });

    //             return [
    //                 'id' => $topic->id,
    //                 'name' => $topic->topic_name,
    //                 'description' => $topic->topic_description,
    //                 'subtopics' => $subtopics->values(),
    //             ];
    //         })->filter()->values();

    //     $totalQuestions = $questions->count();

    //     return response()->json([
    //         'status' => 'success',
    //         'data' => [
    //             'topics' => $groupedTopics,
    //             'total_questions' => $totalQuestions,
    //         ],
    //     ]);
    // }

    public function submitAnswer(Request $request)
    {
        $validated = $request->validate([
            'reviewer_attempt_question_id' => 'required|exists:reviewer_attempt_questions,id',
            'answer' => 'nullable|string', // Allow null answer
        ]);

        $question = ReviewerAttemptQuestion::find($validated['reviewer_attempt_question_id']);

        if (!$question) {
            return response()->json(['status' => 'error', 'message' => 'Question not found.'], 404);
        }

        if (empty($validated['answer'])) {
            ReviewerAttemptAnswer::where('reviewer_attempt_question_id', $validated['reviewer_attempt_question_id'])->delete();

            $question->update(['status' => 'unanswered']);

            return response()->json(['status' => 'success', 'message' => 'Answer cleared, status remains unanswered.']);
        }

        $answer = ReviewerAttemptAnswer::updateOrCreate(
            ['reviewer_attempt_question_id' => $validated['reviewer_attempt_question_id']],
            ['answer' => $validated['answer']]
        );

        $question->update(['status' => 'answered']);

        return response()->json(['status' => 'success', 'data' => $answer]);
    }

    public function resetAnswer(Request $request)
    {
        // Validate the request
        $validated = $request->validate([
            'reviewer_attempt_question_id' => 'required|exists:reviewer_attempt_questions,id',
        ]);

        // Find the question
        $question = ReviewerAttemptQuestion::find($validated['reviewer_attempt_question_id']);

        if (!$question) {
            return response()->json(['status' => 'error', 'message' => 'Question not found.'], 404);
        }

        // Delete any existing answer for this question
        ReviewerAttemptAnswer::where('reviewer_attempt_question_id', $validated['reviewer_attempt_question_id'])->delete();

        // Set the question status to "unanswered"
        $question->update(['status' => 'unanswered']);

        return response()->json(['status' => 'success', 'message' => 'Answer has been reset, status is now unanswered.']);
    }


    public function setFlagged(Request $request)
    {
        $validated = $request->validate([
            'reviewer_attempt_question_id' => 'required|exists:reviewer_attempt_questions,id',
            'is_flagged' => 'required|boolean',
        ]);

        $question = ReviewerAttemptQuestion::find($validated['reviewer_attempt_question_id']);
        if ($question) {
            $question->update(['isFlagged' => $validated['is_flagged']]);

            return response()->json([
                'status' => 'success',
                'message' => 'Question flag status updated successfully.',
                'data' => [
                    'reviewer_attempt_question_id' => $question->id,
                    'is_flagged' => $question->isFlagged,
                ],
            ]);
        }

        return response()->json(['status' => 'error', 'message' => 'Question not found.'], 404);
    }

    public function submitAttempt(Request $request, $attemptId)
    {
        $attempt = ReviewerAttempt::find($attemptId);

        if (!$attempt) {
            return response()->json(['status' => 'error', 'message' => 'Attempt not found.'], 404);
        }

        if (now()->greaterThan($attempt->expire_time)) {
            $attempt->update(['status' => 'expired', 'time_remaining' => 0]);
            return response()->json(['status' => 'error', 'message' => 'The attempt has expired.'], 403);
        }

        $questions = ReviewerAttemptQuestion::where('reviewer_attempt_id', $attemptId)
            ->with(['question', 'answer'])
            ->get();

        $totalQuestionPoints = $questions->sum(function ($attemptQuestion) {
            return $attemptQuestion->question->question_point ?? 0;
        });
        $totalScore = 0;
        $totalQuestions = $questions->count();
        $results = [];

        // Fetch the specification for the reviewer attempt
        $specification = ReviewerAttemptSpecification::with(['topics', 'subtopics'])
            ->where('reviewer_attempt_id', $attemptId)
            ->first();

        // Initialize scope array
        $scope = [];

        if ($specification) {
            // Collect topics and subtopics
            foreach ($specification->topics as $topic) {
                $scope[$topic->topic_name] = [];

                // Add associated subtopics for each topic
                foreach ($specification->subtopics as $subtopic) {
                    // Assuming that subtopics are linked to topics in some way (like a shared relationship)
                    if ($subtopic->topic_id == $topic->id) {
                        $scope[$topic->topic_name][] = $subtopic->subtopic_name;
                    }
                }
            }
        }

        // The $scope array now contains topics with their corresponding subtopics
        Log::info('Scope of topics and subtopics: ', $scope);

        foreach ($questions as $attemptQuestion) {
            $correctAnswer = $attemptQuestion->question->correct_answer;
            $userAnswer = $attemptQuestion->answer ? $attemptQuestion->answer->answer : null;
            $isCorrect = $userAnswer === $correctAnswer;

            if ($isCorrect) {
                $totalScore += $attemptQuestion->question->question_point;
            }

            // Collect scope data (topics and subtopics)
            $topic = $attemptQuestion->question->topic->topic_name ?? 'No Topic';
            $subtopic = $attemptQuestion->question->subtopic->subtopic_name ?? 'No Subtopic';
            $scope[$topic][] = $subtopic;

            $results[] = [
                'question_id' => $attemptQuestion->question_id,
                'user_answer' => $userAnswer,
                'correct_answer' => $correctAnswer,
                'is_correct' => $isCorrect,
                'question_point' => $attemptQuestion->question->question_point,
            ];
        }

        $grade = ($totalScore / $totalQuestionPoints) * 100;
        $feedback = $grade >= 70 ? 'passed' : 'failed';  //70% pasing grade

        $finishedAt = now();  // Finished time

        // Create the result
        $result = Result::create([
            'reviewer_attempt_id' => $attemptId,
            'user_id' => $attempt->user_id,
            'marks' => $totalScore,
            'total_questions' => $totalQuestions,
            'grade' => (int)$grade,  // Store grade as an integer percentage
            'scope' => $scope,
            'feedback' => $feedback,
            'finished_at' => $finishedAt,
        ]);

        // Update attempt status to completed
        $timeRemaining = max(0, $attempt->expire_time->diffInMinutes(now(), false));
        $attempt->update([
            'score' => $totalScore,
            'status' => 'completed',
            'time_remaining' => $timeRemaining,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Attempt submitted and validated successfully!',
            'data' => [
                'total_score' => $totalScore,
                'results' => $results,
                'result' => $result,  // Include the result in the response
                'max_points' => $totalQuestionPoints
            ],
        ]);
    }




    public function viewResult(Request $request)
    {
        $reviewerAttemptId = $request->query('attempt_id');

        if (!$reviewerAttemptId) {
            return response()->json(['status' => 'error', 'message' => 'Attempt ID is required.'], 400);
        }

        $reviewerAttempt = ReviewerAttempt::with([
            'result',
            'specification.topics',
            'specification.subtopics'
        ])->findOrFail($reviewerAttemptId);

        $questions = ReviewerAttemptQuestion::where('reviewer_attempt_id', $reviewerAttemptId)
            ->with('question')
            ->get();

        $totalQuestionPoints = $questions->sum(fn($q) => $q->question->question_point ?? 0);

        // Initialize scope array
        $scope = [];

        if ($reviewerAttempt->specification) {
            foreach ($reviewerAttempt->specification->topics as $topic) {
                $scope[$topic->topic_name] = [];
                foreach ($reviewerAttempt->specification->subtopics as $subtopic) {
                    if ($subtopic->topic_id == $topic->id) {
                        $scope[$topic->topic_name][] = $subtopic->subtopic_name;
                    }
                }
            }
        }

        Log::info('Scope of topics and subtopics:', $scope);

        $result = $reviewerAttempt->result;

        if ($result) {
            return response()->json([
                'status' => 'success',
                'message' => 'Result fetched successfully!',
                'data' => [
                    'marks' => $result->marks,
                    'total_questions' => $result->total_questions,
                    'grade' => $result->grade,
                    'scope' => $scope,
                    'feedback' => $result->feedback,
                    'finished_at' => $result->finished_at,
                    'max_points' => $totalQuestionPoints
                ]
            ]);
        }

        return response()->json([
            'status' => 'error',
            'message' => 'Result not found for this reviewer attempt.'
        ], 404);
    }


    public function viewResults(Request $request) {}
    // public function getAttemptQuestions(Request $request)
    // {
    //     $reviewerAttemptId = $request->query('attempt_id');

    //     if (!$reviewerAttemptId) {
    //         return response()->json(['status' => 'error', 'message' => 'Attempt ID is required.'], 400);
    //     }

    //     $questions = ReviewerAttemptQuestion::where('reviewer_attempt_id', $reviewerAttemptId)
    //         ->with(['question.choices', 'question.topic', 'question.subtopic', 'answer'])
    //         ->get();

    //     $groupedTopics = $questions->groupBy(fn($item) => $item->question->topic_id)
    //         ->map(function ($group, $topicId) {
    //             $topic = $group->first()->question->topic;

    //             if ($topic === null) {
    //                 return null;
    //             }

    //             $subtopics = $group->groupBy(fn($item) => $item->question->subtopic_id)
    //                 ->map(function ($subgroup, $subtopicId) use ($topic) {
    //                     if ($subtopicId == null) {
    //                         return [
    //                             'id' => null,
    //                             'name' => 'No Subtopic',
    //                             'description' => 'Questions not assigned to a subtopic',
    //                             'questions' => $subgroup->map(function ($item) {
    //                                 $question = $item->question;
    //                                 return [
    //                                     'id' => $question->id,
    //                                     'reviewer_attempt_question_id' => $item->id,
    //                                     'content' => $question->question_content,
    //                                     'point' => $question->question_point,
    //                                     'status' => $item->status, // Fetch and include status
    //                                     'isFlagged' => $item->isFlagged,
    //                                     'choices' => $question->choices->map(function ($choice) {
    //                                         return [
    //                                             'id' => $choice->id,
    //                                             'index' => $choice->choice_index,
    //                                             'content' => $choice->choice_content,
    //                                         ];
    //                                     }),
    //                                     'answer' => $item->answer ? $item->answer->answer : null,
    //                                 ];
    //                             })->values(),
    //                         ];
    //                     }

    //                     $subtopic = $subgroup->first()->question->subtopic ?? (object)[
    //                         'id' => null,
    //                         'subtopic_name' => 'No Subtopic',
    //                         'subtopic_description' => 'Questions not assigned to a subtopic',
    //                     ];

    //                     return [
    //                         'id' => $subtopic->id,
    //                         'name' => $subtopic->subtopic_name,
    //                         'description' => $subtopic->subtopic_description,
    //                         'questions' => $subgroup->map(function ($item) {
    //                             $question = $item->question;
    //                             return [
    //                                 'reviewer_attempt_question_id' => $item->id,
    //                                 'question_id' => $question->id,
    //                                 'content' => $question->question_content,
    //                                 'point' => $question->question_point,
    //                                 'status' => $item->status, // Fetch and include status
    //                                 'isFlagged' => $item->isFlagged,
    //                                 'choices' => $question->choices->map(function ($choice) {
    //                                     return [
    //                                         'id' => $choice->id,
    //                                         'index' => $choice->choice_index,
    //                                         'content' => $choice->choice_content,
    //                                     ];
    //                                 }),
    //                                 'answer' => $item->answer ? $item->answer->answer : null,
    //                             ];
    //                         })->values(),
    //                     ];
    //                 });

    //             return [
    //                 'id' => $topic->id,
    //                 'name' => $topic->topic_name,
    //                 'description' => $topic->topic_description,
    //                 'subtopics' => $subtopics->values(),
    //             ];
    //         })->filter()->values();

    //     $totalQuestions = $questions->count();

    //     return response()->json([
    //         'status' => 'success',
    //         'data' => [
    //             'topics' => $groupedTopics,
    //             'total_questions' => $totalQuestions,
    //         ],
    //     ]);
    // }

    // public function reviewAttempt(Request $request)
    // {
    //     $reviewerAttemptId = $request->query('attempt_id');

    //     if (!$reviewerAttemptId) {
    //         return response()->json(['status' => 'error', 'message' => 'Attempt ID is required.'], 400);
    //     }

    //     // Fetch the attempt and ensure it exists
    //     $reviewerAttempt = ReviewerAttempt::with('result')->find($reviewerAttemptId);
    //     if (!$reviewerAttempt) {
    //         return response()->json(['status' => 'error', 'message' => 'Attempt not found.'], 404);
    //     }

    //     // Fetch all questions along with their choices, topics, subtopics, and answers
    //     $questions = ReviewerAttemptQuestion::where('reviewer_attempt_id', $reviewerAttemptId)
    //         ->with(['question.choices', 'question.topic', 'question.subtopic', 'answer'])
    //         ->get();

    //     // Group questions by topic and subtopic for organized review
    //     $groupedTopics = $questions->groupBy(fn($item) => $item->question->topic_id)
    //         ->map(function ($group, $topicId) {
    //             $topic = $group->first()->question->topic;

    //             $subtopics = $group->groupBy(fn($item) => $item->question->subtopic_id)
    //                 ->map(function ($subgroup, $subtopicId) use ($topic) {
    //                     $subtopic = $subgroup->first()->question->subtopic ?? (object)[
    //                         'id' => null,
    //                         'subtopic_name' => 'No Subtopic',
    //                         'subtopic_description' => 'Questions not assigned to a subtopic',
    //                     ];

    //                     return [
    //                         'id' => $subtopic->id,
    //                         'name' => $subtopic->subtopic_name,
    //                         'description' => $subtopic->subtopic_description,
    //                         'questions' => $subgroup->map(function ($item) {
    //                             $question = $item->question;
    //                             return [
    //                                 'id' => $question->id,
    //                                 'content' => $question->question_content,
    //                                 'point' => $question->question_point,
    //                                 'status' => $item->status,
    //                                 'isFlagged' => $item->isFlagged,
    //                                 'user_answer' => $item->answer ? $item->answer->answer : null,
    //                                 'correct_answer' => $question->correct_answer,
    //                                 'is_correct' => $item->answer && $item->answer->answer === $question->correct_answer,
    //                                 'choices' => $question->choices->map(fn($choice) => [
    //                                     'id' => $choice->id,
    //                                     'index' => $choice->choice_index,
    //                                     'content' => $choice->choice_content,
    //                                 ]),
    //                             ];
    //                         })->values(),
    //                     ];
    //                 });

    //             return [
    //                 'id' => $topic->id,
    //                 'name' => $topic->topic_name,
    //                 'description' => $topic->topic_description,
    //                 'subtopics' => $subtopics->values(),
    //             ];
    //         })->values();

    //     // Return the structured attempt review data
    //     return response()->json([
    //         'status' => 'success',
    //         'message' => 'Review attempt data fetched successfully.',
    //         'data' => [
    //             'reviewer_attempt' => $reviewerAttempt,
    //             'topics' => $groupedTopics,
    //             'result' => $reviewerAttempt->result,
    //         ],
    //     ]);
    // }

    // public function viewResult(Request $request)
    // {
    //     // Retrieve the attempt_id from the query parameter
    //     $reviewerAttemptId = $request->query('attempt_id');

    //     // Check if the attempt_id is provided
    //     if (!$reviewerAttemptId) {
    //         return response()->json([
    //             'status' => 'error',
    //             'message' => 'Attempt ID is required.',
    //         ], 400);
    //     }

    //     // Fetch the reviewer attempt with its related result, topics, and subtopics
    //     $reviewerAttempt = ReviewerAttempt::with([
    //         'result',  // Load the result for this attempt
    //         'specification.topics',  // Load the topics related to the specification
    //         'specification.subtopics' // Load the subtopics related to the specification
    //     ])
    //         ->findOrFail($reviewerAttemptId);  // Find the reviewer attempt by ID


    //     $questions = ReviewerAttemptQuestion::where('reviewer_attempt_id', $reviewerAttemptId)
    //         ->with('question')
    //         ->get();

    //     // Calculate max points
    //     $totalQuestionPoints = $questions->sum(function ($attemptQuestion) {
    //         return $attemptQuestion->question->question_point ?? 0;
    //     });
    //     // Initialize scope array
    //     $scope = [];


    //     // Collect topics and subtopics from the specification
    //     foreach ($reviewerAttempt->specification->topics as $topic) {
    //         $scope[$topic->topic_name] = [];

    //         foreach ($reviewerAttempt->specification->subtopics as $subtopic) {
    //             // Assuming there's a link between topics and subtopics
    //             if ($subtopic->topic_id == $topic->id) {
    //                 $scope[$topic->topic_name][] = $subtopic->subtopic_name;
    //             }
    //         }
    //     }
    //     Log::info('Scope of topics and subtopics: ', $scope);

    //     // Get the result for this reviewer attempt
    //     $result = $reviewerAttempt->result;  // This will give the single result associated with this attempt

    //     // Check if result exists
    //     if ($result) {
    //         $resultData = [
    //             'marks' => $result->marks,
    //             'total_questions' => $result->total_questions,
    //             'grade' => $result->grade,
    //             'scope' => $scope,  // Topics and subtopics
    //             'feedback' => $result->feedback,
    //             'finished_at' => $result->finished_at,
    //             'max_points' => $totalQuestionPoints
    //         ];

    //         return response()->json([
    //             'status' => 'success',
    //             'message' => 'Result fetched successfully!',
    //             'data' => $resultData,
    //         ], 200);
    //     }

    //     // If result not found, return error
    //     return response()->json([
    //         'status' => 'error',
    //         'message' => 'Result not found for this reviewer attempt.',
    //     ], 404);
    // }





    // public function submitAttempt(Request $request, $attemptId)
    // {
    //     $attempt = ReviewerAttempt::find($attemptId);

    //     if (!$attempt) {
    //         return response()->json(['status' => 'error', 'message' => 'Attempt not found.'], 404);
    //     }

    //     if (now()->greaterThan($attempt->expire_time)) {
    //         $attempt->update(['status' => 'expired', 'time_remaining' => 0]);
    //         return response()->json(['status' => 'error', 'message' => 'The attempt has expired.'], 403);
    //     }

    //     $questions = ReviewerAttemptQuestion::where('reviewer_attempt_id', $attemptId)
    //         ->with(['question', 'answer'])
    //         ->get();

    //     $totalScore = 0;
    //     $results = [];

    //     foreach ($questions as $attemptQuestion) {
    //         $correctAnswer = $attemptQuestion->question->correct_answer;
    //         $userAnswer = $attemptQuestion->answer ? $attemptQuestion->answer->answer : null;
    //         $isCorrect = $userAnswer === $correctAnswer;

    //         if ($isCorrect) {
    //             $totalScore += $attemptQuestion->question->question_point;
    //         }

    //         $results[] = [
    //             'question_id' => $attemptQuestion->question_id,
    //             'user_answer' => $userAnswer,
    //             'correct_answer' => $correctAnswer,
    //             'is_correct' => $isCorrect,
    //             'question_point' => $attemptQuestion->question->question_point,
    //         ];
    //     }

    //     $timeRemaining = $attempt->expire_time->diffInMinutes(now(), false);

    //     $attempt->update([
    //         'score' => $totalScore,
    //         'status' => 'completed',
    //         'time_remaining' => max(0, $timeRemaining),
    //     ]);

    //     return response()->json([
    //         'status' => 'success',
    //         'message' => 'Attempt submitted and validated successfully!',
    //         'data' => [
    //             'total_score' => $totalScore,
    //             'results' => $results,
    //         ],
    //     ]);
    // }



    // public function submitAttempt(Request $request, $attemptId)
    // {
    //     $attempt = ReviewerAttempt::find($attemptId);

    //     if (!$attempt) {
    //         return response()->json(['status' => 'error', 'message' => 'Attempt not found.'], 404);
    //     }

    //     if (now()->greaterThan($attempt->expire_time)) {
    //         $attempt->update(['status' => 'expired', 'time_remaining' => 0]);
    //         return response()->json(['status' => 'error', 'message' => 'The attempt has expired.'], 403);
    //     }

    //     $questions = ReviewerAttemptQuestion::where('reviewer_attempt_id', $attemptId)
    //         ->with(['question', 'answers'])
    //         ->get();

    //     $totalScore = 0;
    //     $results = [];

    //     foreach ($questions as $attemptQuestion) {
    //         $correctAnswer = $attemptQuestion->question->correct_answer;
    //         $userAnswer = $attemptQuestion->answers->first()->answer ?? null;
    //         $isCorrect = $userAnswer === $correctAnswer;

    //         if ($isCorrect) {
    //             $totalScore += $attemptQuestion->question->question_point;
    //         }

    //         $results[] = [
    //             'question_id' => $attemptQuestion->question_id,
    //             'user_answer' => $userAnswer,
    //             'correct_answer' => $correctAnswer,
    //             'is_correct' => $isCorrect,
    //             'question_point' => $attemptQuestion->question->question_point,
    //         ];
    //     }

    //     $timeRemaining = $attempt->expire_time->diffInMinutes(now(), false);

    //     $attempt->update([
    //         'score' => $totalScore,
    //         'status' => 'completed',
    //         'time_remaining' => max(0, $timeRemaining),
    //     ]);

    //     return response()->json([
    //         'status' => 'success',
    //         'message' => 'Attempt submitted and validated successfully!',
    //         'data' => [
    //             'total_score' => $totalScore,
    //             'results' => $results,
    //         ],
    //     ]);
    // }

    // public function getAttemptQuestions(Request $request)
    // {
    //     $reviewerAttemptId = $request->query('attempt_id');

    //     if (!$reviewerAttemptId) {
    //         return response()->json(['status' => 'error', 'message' => 'Attempt ID is required.'], 400);
    //     }

    //     // Fetch data with relationships
    //     $questions = ReviewerAttemptQuestion::where('reviewer_attempt_id', $reviewerAttemptId)
    //         ->with(['question.choices', 'question.topic', 'question.subtopic', 'answers'])
    //         ->get();

    //     // Group by topic
    //     $groupedTopics = $questions->groupBy(fn($item) => $item->question->topic_id)
    //         ->map(function ($group, $topicId) {
    //             $topic = $group->first()->question->topic;

    //             // Group questions by subtopic, only if a subtopic exists
    //             $subtopics = $group->groupBy(fn($item) => $item->question->subtopic_id)
    //                 ->map(function ($subgroup, $subtopicId) use ($topic) {
    //                     // Handle cases where no subtopic exists (questions belong only to topic)
    //                     if ($subtopicId == null) {
    //                         return [
    //                             'id' => null, // No subtopic ID for this group of questions
    //                             'name' => 'No Subtopic', // You can customize this name
    //                             'description' => 'Questions not assigned to a subtopic',
    //                             'questions' => $subgroup->map(function ($item) {
    //                                 $question = $item->question;
    //                                 return [
    //                                     'id' => $question->id,
    //                                     'content' => $question->question_content,
    //                                     'point' => $question->question_point,
    //                                     'status' => 'unanswered', // Example status
    //                                     'isFlagged' => false, // Default flag
    //                                     'choices' => $question->choices->map(function ($choice) {
    //                                         return [
    //                                             'id' => $choice->id,
    //                                             'index' => $choice->choice_index,
    //                                             'content' => $choice->choice_content,
    //                                         ];
    //                                     }),
    //                                     'user_answers' => $item->answers->pluck('answer'),
    //                                 ];
    //                             })->values(),
    //                         ];
    //                     }

    //                     // Handle cases where there is a subtopic
    //                     $subtopic = $subgroup->first()->question->subtopic;
    //                     return [
    //                         'id' => $subtopic->id,
    //                         'name' => $subtopic->subtopic_name,
    //                         'description' => $subtopic->subtopic_description,
    //                         'questions' => $subgroup->map(function ($item) {
    //                             $question = $item->question;
    //                             return [
    //                                 'id' => $question->id,
    //                                 'content' => $question->question_content,
    //                                 'point' => $question->question_point,
    //                                 'status' => 'unanswered', // Example status
    //                                 'isFlagged' => false, // Default flag
    //                                 'choices' => $question->choices->map(function ($choice) {
    //                                     return [
    //                                         'id' => $choice->id,
    //                                         'index' => $choice->choice_index,
    //                                         'content' => $choice->choice_content,
    //                                     ];
    //                                 }),
    //                                 'user_answers' => $item->answers->pluck('answer'),
    //                             ];
    //                         })->values(),
    //                     ];
    //                 });

    //             return [
    //                 'id' => $topic->id,
    //                 'name' => $topic->topic_name,
    //                 'description' => $topic->topic_description,
    //                 'subtopics' => $subtopics,
    //             ];
    //         })->values();
    //     $totalQuestions = $questions->count();
    //     return response()->json([
    //         'status' => 'success',
    //         'data' => ['topics' => $groupedTopics, 'total_questions' => $totalQuestions,],
    //     ]);
    // }

    // public function getAttempt(){

    // };


    // public function getAttemptQuestions(Request $request)
    // {
    //     $reviewerAttemptId = $request->query('attempt_id');
    //     $perPage = $request->query('per_page', 1); // Default: 1 topic per page

    //     if (!$reviewerAttemptId) {
    //         return response()->json(['status' => 'error', 'message' => 'Attempt ID is required.'], 400);
    //     }

    //     // Fetch data with relationships
    //     $questions = ReviewerAttemptQuestion::where('reviewer_attempt_id', $reviewerAttemptId)
    //         ->with(['question.choices', 'question.topic', 'question.subtopic','answers'])
    //         ->get();

    //     // Group by topic
    //     $groupedTopics = $questions->groupBy(fn($item) => $item->question->topic_id)
    //         ->map(function ($group, $topicId) {
    //             $topic = $group->first()->question->topic;
    //             $subtopics = $group->groupBy(fn($item) => $item->question->subtopic_id)
    //                 ->map(function ($subgroup, $subtopicId) {
    //                     $subtopic = $subgroup->first()->question->subtopic;
    //                     return [
    //                         'id' => $subtopic->id,
    //                         'name' => $subtopic->subtopic_name,
    //                         'description' => $subtopic->subtopic_description,
    //                         'questions' => $subgroup->map(function ($item) {
    //                             $question = $item->question;
    //                             return [
    //                                 'id' => $question->id,
    //                                 'content' => $question->question_content,
    //                                 'point' => $question->question_point,
    //                                 'status' => 'unanswered', // Example status
    //                                 'isFlagged' => false, // Default flag
    //                                 'choices' => $question->choices->map(function ($choice) {
    //                                     return [
    //                                         'id' => $choice->id,
    //                                         'index' => $choice->choice_index,
    //                                         'content' => $choice->choice_content,
    //                                         // 'is_correct' => false, // Mark correct if required
    //                                     ];
    //                                 }),
    //                                 'user_answers' => $item->answers->pluck('answer'),
    //                             ];
    //                         })->values(),
    //                     ];
    //                 })->values();

    //             return [
    //                 'id' => $topic->id,
    //                 'name' => $topic->topic_name,
    //                 'description' => $topic->topic_description,
    //                 'subtopics' => $subtopics,
    //             ];
    //         })->values();

    //     // Paginate topics
    //     $paginated = $this->paginate($groupedTopics, $perPage);

    //     return response()->json([
    //         'status' => 'success',
    //         'data' => $paginated,
    //     ]);
    // }

    // private function paginate($items, $perPage)
    // {
    //     $page = request()->get('page', 1); // Current page
    //     $total = $items->count();
    //     $items = $items->slice(($page - 1) * $perPage, $perPage)->values();

    //     return [
    //         'current_page' => $page,
    //         'data' => $items,
    //         'per_page' => $perPage,
    //         'total' => $total,
    //         'last_page' => ceil($total / $perPage),
    //     ];
    // }

    // public function attemptProcess(Request $request, $attemptId)
    // {
    //     $attempt = ReviewerAttempt::find($attemptId);

    //     if (!$attempt) {
    //         return response()->json([
    //             'status' => 'error',
    //             'message' => 'Attempt not found.',
    //         ], 404);
    //     }

    //     $attempt->update(['status' => 'in_progress']);

    //     return response()->json([
    //         'status' => 'success',
    //         'message' => 'Attempt status updated.',
    //         'data' => $attempt,
    //     ]);
    // }

    // public function submitAttempt(Request $request, $attemptId)
    // {
    //     $attempt = ReviewerAttempt::find($attemptId);

    //     if (!$attempt) {
    //         return response()->json([
    //             'status' => 'error',
    //             'message' => 'Attempt not found.',
    //         ], 404);
    //     }

    //     $questions = ReviewerAttemptQuestion::where('reviewer_attempt_id', $attemptId)
    //         ->with(['question', 'answers'])
    //         ->get();

    //     $totalScore = 0;
    //     $results = [];

    //     foreach ($questions as $attemptQuestion) {
    //         $correctAnswer = $attemptQuestion->question->correct_answer;
    //         $userAnswer = $attemptQuestion->answers->first()->answer ?? null;
    //         $isCorrect = $userAnswer === $correctAnswer;

    //         if ($isCorrect) {
    //             $totalScore += $attemptQuestion->question->question_point;
    //         }

    //         $results[] = [
    //             'question_id' => $attemptQuestion->question_id,
    //             'user_answer' => $userAnswer,
    //             'correct_answer' => $correctAnswer,
    //             'is_correct' => $isCorrect,
    //             'question_point' => $attemptQuestion->question->question_point,
    //         ];
    //     }

    //     $attempt->update([
    //         'score' => $totalScore,
    //         'status' => 'completed',
    //     ]);

    //     return response()->json([
    //         'status' => 'success',
    //         'message' => 'Attempt submitted and validated successfully!',
    //         'data' => [
    //             'total_score' => $totalScore,
    //             'results' => $results,
    //         ],
    //     ]);
    // }
}
