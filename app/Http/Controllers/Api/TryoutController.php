<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Tryout;
use App\Models\Question;
use App\Models\UserResult;

class TryoutController extends Controller
{
    public function index(Request $request)
    {
        $tryouts = Tryout::with('category')
            ->withCount(['reviews', 'questions'])
            ->withAvg('reviews', 'rating')
            ->get();
            
        $user = $request->user();
        if ($user) {
            $purchasedTryoutIds = \App\Models\Order::where('user_id', $user->id)
                ->where('status', 'confirmed')
                ->whereNotNull('tryout_id')
                ->pluck('tryout_id')
                ->toArray();
                
            $purchasedBundleIds = \App\Models\Order::where('user_id', $user->id)
                ->where('status', 'confirmed')
                ->whereNotNull('bundle_id')
                ->pluck('bundle_id');
                
            if ($purchasedBundleIds->isNotEmpty()) {
                $bundleTryoutIds = \Illuminate\Support\Facades\DB::table('bundle_tryout')
                    ->whereIn('bundle_id', $purchasedBundleIds)
                    ->pluck('tryout_id')
                    ->toArray();
                $purchasedTryoutIds = array_unique(array_merge($purchasedTryoutIds, $bundleTryoutIds));
            }
            
            foreach ($tryouts as $tryout) {
                $tryout->setAttribute('is_accessible', $tryout->price <= 0 || in_array($tryout->id, $purchasedTryoutIds));
            }
        }

        return response()->json($tryouts);
    }

    public function show(Request $request, $id)
    {
        $tryout = Tryout::with(['questions', 'reviews.user:id,name'])->findOrFail($id);
        
        $user = $request->user();
        $isAccessible = false;
        
        if ($tryout->price <= 0) {
            $isAccessible = true;
        } else {
            $directPurchase = \App\Models\Order::where('user_id', $user->id)
                ->where('tryout_id', $id)
                ->where('status', 'confirmed')
                ->exists();
                
            if ($directPurchase) {
                $isAccessible = true;
            } else {
                $bundlePurchase = \App\Models\Order::where('user_id', $user->id)
                    ->where('status', 'confirmed')
                    ->whereHas('bundle.tryouts', function($query) use ($id) {
                        $query->where('tryout_id', $id);
                    })
                    ->exists();
                $isAccessible = $bundlePurchase;
            }
        }

        if (!$isAccessible) {
            // Hide questions if not purchased
            $tryout->setRelation('questions', collect([]));
            $tryout->setAttribute('is_accessible', false);
            
            $pendingOrder = \App\Models\Order::where('user_id', $user->id)
                ->where('tryout_id', $id)
                ->where('status', 'pending')
                ->exists();
            $tryout->setAttribute('pending_order', $pendingOrder);
        } else {
            $tryout->setAttribute('is_accessible', true);
            // Hide answer keys from users when fetching questions
            $tryout->questions->makeHidden(['answer_key', 'score_a', 'score_b', 'score_c', 'score_d', 'score_e']);
        }

        // Calculate average rating
        $avgRating = $tryout->reviews->avg('rating');
        $tryout->setAttribute('average_rating', round($avgRating, 1));

        return response()->json($tryout);
    }

    public function submitReview(Request $request, $id)
    {
        $validated = $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string'
        ]);

        $review = \App\Models\Review::updateOrCreate(
            ['user_id' => $request->user()->id, 'tryout_id' => $id],
            ['rating' => $validated['rating'], 'comment' => $validated['comment']]
        );

        return response()->json(['message' => 'Review submitted successfully', 'review' => $review]);
    }

    public function submit(Request $request, $id)
    {
        $request->validate([
            'answers' => 'required|array',
        ]);

        $tryout = Tryout::findOrFail($id);
        $questions = Question::where('tryout_id', $id)->get()->keyBy('id');
        
        $scoreTwk = 0;
        $scoreTiu = 0;
        $scoreTkp = 0;

        foreach ($request->answers as $questionId => $answer) {
            if (!isset($questions[$questionId])) continue;

            $q = $questions[$questionId];
            $scoreField = 'score_' . strtolower($answer);
            
            // Check if field exists in the model
            $points = $q->$scoreField ?? 0;

            if ($q->type == 'TWK') {
                $scoreTwk += $points;
            } elseif ($q->type == 'TIU') {
                $scoreTiu += $points;
            } elseif ($q->type == 'TKP') {
                $scoreTkp += $points;
            }
        }

        $totalScore = $scoreTwk + $scoreTiu + $scoreTkp;
        
        // Passing grade (Passing Grade SKD CPNS 2024: TWK 65, TIU 80, TKP 166)
        $isPassed = ($scoreTwk >= 65 && $scoreTiu >= 80 && $scoreTkp >= 166);

        $result = UserResult::create([
            'user_id' => $request->user()->id,
            'tryout_id' => $id,
            'score_twk' => $scoreTwk,
            'score_tiu' => $scoreTiu,
            'score_tkp' => $scoreTkp,
            'total_score' => $totalScore,
            'is_passed' => $isPassed,
            'time_taken_minutes' => $request->time_taken_minutes ?? 0,
            'answers' => $request->answers,
        ]);

        \Illuminate\Support\Facades\Cache::forget("user_analytics_{$request->user()->id}");

        return response()->json([
            'message' => 'Tryout submitted successfully',
            'result' => $result
        ]);
    }

    public function getResult($resultId)
    {
        $result = UserResult::with(['tryout', 'user'])->where('user_id', request()->user()->id)->findOrFail($resultId);
        return response()->json($result);
    }

    public function getReview($resultId)
    {
        $result = UserResult::with(['tryout.questions'])->where('user_id', request()->user()->id)->findOrFail($resultId);
        
        // Return full tryout with questions (including explanation and answer keys)
        // Along with the user's answers so frontend can compare them.
        return response()->json([
            'result' => $result,
            'tryout' => $result->tryout,
            'user_answers' => $result->answers
        ]);
    }

    public function getLeaderboard($id)
    {
        // Get the ID of the FIRST attempt for each user on this tryout
        $firstAttemptIds = UserResult::where('tryout_id', $id)
            ->selectRaw('MIN(id) as id')
            ->groupBy('user_id')
            ->pluck('id');

        // 1. Total Score (Desc)
        // 2. TKP (Desc)
        // 3. TIU (Desc)
        // 4. TWK (Desc)
        // 5. Time Taken (Asc)
        $leaderboard = UserResult::with('user:id,name')
            ->whereIn('id', $firstAttemptIds)
            ->orderBy('total_score', 'desc')
            ->orderBy('score_tkp', 'desc')
            ->orderBy('score_tiu', 'desc')
            ->orderBy('score_twk', 'desc')
            ->orderBy('time_taken_minutes', 'asc')
            ->limit(50)
            ->get();

        return response()->json($leaderboard);
    }

    public function getUserAnalytics(Request $request)
    {
        $userId = $request->user()->id;
        $tryoutIdFilter = $request->query('tryout_id', 'all');

        // Fetch available tryouts for the filter dropdown
        $availableTryouts = \App\Models\UserResult::with('tryout:id,title')
            ->where('user_id', $userId)
            ->get()
            ->pluck('tryout')
            ->filter()
            ->unique('id')
            ->values()
            ->toArray();

        $cacheKey = "user_analytics_{$userId}";
        if ($tryoutIdFilter && $tryoutIdFilter !== 'all') {
            $cacheKey .= "_tryout_{$tryoutIdFilter}";
        }

        $analytics = \Illuminate\Support\Facades\Cache::remember($cacheKey, 3600, function () use ($userId, $tryoutIdFilter) {
            $query = UserResult::with('tryout:id,title')
                ->where('user_id', $userId)
                ->orderBy('created_at', 'asc');

            if ($tryoutIdFilter && $tryoutIdFilter !== 'all') {
                $query->where('tryout_id', $tryoutIdFilter);
            }

            $results = $query->get();

            $totalTryouts = $results->count();
            
            if ($totalTryouts === 0) {
                return [
                    'total_tryouts' => 0,
                    'avg_twk' => 0,
                    'avg_tiu' => 0,
                    'avg_tkp' => 0,
                    'pass_rate' => 0,
                    'history' => [],
                    'all_subjects' => [],
                    'weakest_subjects' => [],
                    'strongest_subjects' => []
                ];
            }

            $passedCount = $results->where('is_passed', true)->count();
            $passRate = round(($passedCount / $totalTryouts) * 100);

            $avgTwk = round($results->avg('score_twk'));
            $avgTiu = round($results->avg('score_tiu'));
            $avgTkp = round($results->avg('score_tkp'));

            $history = $results->map(function ($result, $index) use ($tryoutIdFilter) {
                $attemptName = 'Ujian ' . ($index + 1);
                $tryoutName = $result->tryout ? $result->tryout->title : 'Unknown Tryout';
                
                return [
                    'id' => $result->id,
                    'tryout_id' => $result->tryout_id,
                    'attempt' => $attemptName,
                    'tryout_name' => $tryoutName,
                    'total_score' => $result->total_score,
                    'score_twk' => $result->score_twk,
                    'score_tiu' => $result->score_tiu,
                    'score_tkp' => $result->score_tkp,
                    'is_passed' => $result->is_passed,
                    'date' => $result->created_at->format('d M Y, H:i')
                ];
            })->values()->toArray();

            // Calculate Sub-Category Analytics
            $tryoutIds = $results->pluck('tryout_id')->unique();
            
            // Optimize: Only select required fields to save memory
            $questions = \App\Models\Question::whereIn('tryout_id', $tryoutIds)
                ->select('id', 'tryout_id', 'sub_category', 'score_a', 'score_b', 'score_c', 'score_d', 'score_e')
                ->get()->keyBy('id');

            $subCategoryStats = [];

            foreach ($results as $result) {
                $answers = is_string($result->answers) ? json_decode($result->answers, true) : $result->answers;
                if (!is_array($answers)) continue;

                foreach ($answers as $qId => $ans) {
                    if (isset($questions[$qId])) {
                        $q = $questions[$qId];
                        if ($q->sub_category) {
                            $sub = $q->sub_category;
                            if (!isset($subCategoryStats[$sub])) {
                                $subCategoryStats[$sub] = ['earned' => 0, 'max' => 0];
                            }
                            
                            $scoreField = 'score_' . strtolower($ans);
                            $points = $q->$scoreField ?? 0;

                            $subCategoryStats[$sub]['earned'] += $points;
                            $subCategoryStats[$sub]['max'] += 5;
                        }
                    }
                }
            }

            $subCategoryPercentages = [];
            foreach ($subCategoryStats as $sub => $stat) {
                if ($stat['max'] > 0) {
                    $subCategoryPercentages[] = [
                        'name' => $sub,
                        'percentage' => round(($stat['earned'] / $stat['max']) * 100)
                    ];
                }
            }

            usort($subCategoryPercentages, function($a, $b) {
                return $a['percentage'] <=> $b['percentage'];
            });

            $weakestSubjects = array_slice($subCategoryPercentages, 0, 3);
            $strongestSubjects = array_reverse(array_slice($subCategoryPercentages, -3));

            return [
                'total_tryouts' => $totalTryouts,
                'avg_twk' => $avgTwk,
                'avg_tiu' => $avgTiu,
                'avg_tkp' => $avgTkp,
                'pass_rate' => $passRate,
                'history' => $history,
                'all_subjects' => $subCategoryPercentages,
                'weakest_subjects' => $weakestSubjects,
                'strongest_subjects' => $strongestSubjects
            ];
        });

        $analytics['available_tryouts'] = $availableTryouts;

        return response()->json($analytics);
    }



    public function getDashboard(Request $request)
    {
        $user = $request->user();
        
        // 1. Get User Analytics (Re-use logic but return array)
        // Since getUserAnalytics returns a JsonResponse, we fetch original data
        $analyticsRes = $this->getUserAnalytics($request)->getData(true);
        
        // 2. Get Tryouts (Re-use index logic)
        $tryoutsRes = $this->index($request)->getData(true);
        
        return response()->json([
            'user' => $user,
            'analytics' => $analyticsRes,
            'tryouts' => $tryoutsRes
        ]);
    }

    public function getPublicReviews()
    {
        $reviews = \App\Models\Review::with(['user:id,name', 'tryout:id,title'])
            ->where('rating', '>=', 4)
            ->whereNotNull('comment')
            ->where('comment', '!=', '')
            ->orderBy('created_at', 'desc')
            ->limit(6)
            ->get();
            
        return response()->json($reviews);
    }
}
