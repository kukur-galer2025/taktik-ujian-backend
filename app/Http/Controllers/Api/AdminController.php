<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Tryout;
use App\Models\Question;
use App\Models\User;
use App\Models\SubCategory;
use App\Models\Review;

class AdminController extends Controller
{
    // --- USER MANAGEMENT ---
    
    public function getUsers()
    {
        $users = User::withCount('results')->orderBy('created_at', 'desc')->get();
        return response()->json($users);
    }

    public function deleteUser($id)
    {
        $user = User::findOrFail($id);
        if ($user->is_admin) {
            return response()->json(['message' => 'Cannot delete an admin'], 403);
        }
        $user->delete();
        return response()->json(['message' => 'User deleted successfully']);
    }

    public function getStats()
    {
        $totalUsers = User::count();
        $totalTryouts = Tryout::count();
        $totalResults = \App\Models\UserResult::count();
        
        $totalRevenue = \App\Models\Order::where('status', 'confirmed')->sum('final_amount');
        $pendingOrders = \App\Models\Order::where('status', 'pending')->count();

        // Monthly revenue (last 6 months)
        $monthlyRevenue = [];
        for ($i = 5; $i >= 0; $i--) {
            $date = \Carbon\Carbon::now()->subMonths($i);
            $monthStart = $date->copy()->startOfMonth();
            $monthEnd = $date->copy()->endOfMonth();
            
            $revenue = \App\Models\Order::where('status', 'confirmed')
                ->whereBetween('created_at', [$monthStart, $monthEnd])
                ->sum('final_amount');
                
            $monthlyRevenue[] = [
                'month' => $date->translatedFormat('M Y'),
                'revenue' => $revenue
            ];
        }

        // Top 5 bundles
        $topBundles = \App\Models\Bundle::withCount(['orders' => function($q) {
            $q->where('status', 'confirmed');
        }])->orderBy('orders_count', 'desc')->take(5)->get();

        $orderStatusDistribution = [
            'pending' => $pendingOrders,
            'confirmed' => \App\Models\Order::where('status', 'confirmed')->count(),
            'rejected' => \App\Models\Order::where('status', 'rejected')->count(),
        ];
        
        $recentOrders = \App\Models\Order::with(['user:id,name', 'bundle:id,title', 'tryout:id,title'])
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get();

        return response()->json([
            'totalUsers' => $totalUsers,
            'totalTryouts' => $totalTryouts,
            'totalResults' => $totalResults,
            'totalRevenue' => $totalRevenue,
            'pendingOrders' => $pendingOrders,
            'monthlyRevenue' => $monthlyRevenue,
            'topBundles' => $topBundles,
            'orderStatusDistribution' => $orderStatusDistribution,
            'recentOrders' => $recentOrders
        ]);
    }

    // --- TRYOUT MANAGEMENT ---

    public function getTryouts()
    {
        // Add a count of questions for the dashboard
        $tryouts = Tryout::with('category')->withCount('questions')->orderBy('created_at', 'desc')->get();
        return response()->json($tryouts);
    }

    public function createTryout(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'duration_minutes' => 'required|integer|min:1',
            'category_id' => 'required|exists:categories,id',
            'price' => 'nullable|integer|min:0',
            'cover_image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
        ]);
        $validated['price'] = $validated['price'] ?? 0;

        if ($request->hasFile('cover_image')) {
            $path = $request->file('cover_image')->store('covers', 'public');
            $validated['cover_image'] = '/storage/' . $path;
        }

        $tryout = Tryout::create($validated);
        return response()->json(['message' => 'Tryout created successfully', 'tryout' => $tryout]);
    }

    public function updateTryout(Request $request, $id)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'duration_minutes' => 'required|integer|min:1',
            'category_id' => 'required|exists:categories,id',
            'price' => 'nullable|integer|min:0',
            'cover_image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
        ]);
        $validated['price'] = $validated['price'] ?? 0;

        $tryout = Tryout::findOrFail($id);
        
        if ($request->hasFile('cover_image')) {
            // Delete old image if exists
            if ($tryout->cover_image && str_starts_with($tryout->cover_image, '/storage/')) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete(str_replace('/storage/', '', $tryout->cover_image));
            }
            $path = $request->file('cover_image')->store('covers', 'public');
            $validated['cover_image'] = '/storage/' . $path;
        }

        $tryout->update($validated);

        return response()->json(['message' => 'Tryout updated successfully', 'tryout' => $tryout]);
    }

    public function deleteTryout($id)
    {
        $tryout = Tryout::findOrFail($id);
        $tryout->delete();

        return response()->json(['message' => 'Tryout deleted successfully']);
    }

    // --- QUESTION MANAGEMENT ---

    public function getQuestions($tryoutId)
    {
        $questions = Question::where('tryout_id', $tryoutId)->get();
        $tryout = Tryout::findOrFail($tryoutId);
        
        return response()->json([
            'tryout' => $tryout,
            'questions' => $questions
        ]);
    }

    public function createQuestion(Request $request, $tryoutId)
    {
        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'type' => 'required|in:TWK,TIU,TKP',
            'sub_category' => 'nullable|string',
            'text' => 'required|string',
            'option_a' => 'required|string',
            'option_b' => 'required|string',
            'option_c' => 'required|string',
            'option_d' => 'required|string',
            'option_e' => 'required|string',
            'score_a' => 'required|integer',
            'score_b' => 'required|integer',
            'score_c' => 'required|integer',
            'score_d' => 'required|integer',
            'score_e' => 'required|integer',
            'answer_key' => 'nullable|in:A,B,C,D,E',
            'explanation' => 'nullable|string',
        ]);

        $validator->after(function ($validator) use ($request) {
            if ($request->type === 'TKP') {
                $scores = [
                    (int)$request->score_a,
                    (int)$request->score_b,
                    (int)$request->score_c,
                    (int)$request->score_d,
                    (int)$request->score_e,
                ];
                sort($scores);
                if ($scores !== [1, 2, 3, 4, 5]) {
                    $validator->errors()->add('score_a', 'Soal TKP harus memiliki poin unik 1, 2, 3, 4, 5 untuk setiap opsi.');
                }
            }
        });

        $validated = $validator->validate();

        $validated['tryout_id'] = $tryoutId;
        
        $question = Question::create($validated);

        return response()->json(['message' => 'Question created successfully', 'question' => $question]);
    }

    public function updateQuestion(Request $request, $tryoutId, $questionId)
    {
        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'type' => 'required|in:TWK,TIU,TKP',
            'sub_category' => 'nullable|string',
            'text' => 'required|string',
            'option_a' => 'required|string',
            'option_b' => 'required|string',
            'option_c' => 'required|string',
            'option_d' => 'required|string',
            'option_e' => 'required|string',
            'score_a' => 'required|integer',
            'score_b' => 'required|integer',
            'score_c' => 'required|integer',
            'score_d' => 'required|integer',
            'score_e' => 'required|integer',
            'answer_key' => 'nullable|in:A,B,C,D,E',
            'explanation' => 'nullable|string',
        ]);

        $validator->after(function ($validator) use ($request) {
            if ($request->type === 'TKP') {
                $scores = [
                    (int)$request->score_a,
                    (int)$request->score_b,
                    (int)$request->score_c,
                    (int)$request->score_d,
                    (int)$request->score_e,
                ];
                sort($scores);
                if ($scores !== [1, 2, 3, 4, 5]) {
                    $validator->errors()->add('score_a', 'Soal TKP harus memiliki poin unik 1, 2, 3, 4, 5 untuk setiap opsi.');
                }
            }
        });

        $validated = $validator->validate();

        $question = Question::where('tryout_id', $tryoutId)->findOrFail($questionId);
        $question->update($validated);

        return response()->json(['message' => 'Question updated successfully', 'question' => $question]);
    }

    public function deleteQuestion($tryoutId, $questionId)
    {
        $question = Question::where('tryout_id', $tryoutId)->findOrFail($questionId);
        $question->delete();

        return response()->json(['message' => 'Question deleted successfully']);
    }

    // --- SUBCATEGORY MANAGEMENT ---

    public function getSubCategories()
    {
        $subCategories = SubCategory::orderBy('type')->orderBy('name')->get();
        return response()->json($subCategories);
    }

    public function createSubCategory(Request $request)
    {
        $validated = $request->validate([
            'type' => 'required|in:TWK,TIU,TKP',
            'name' => 'required|string|max:255',
        ]);

        $subCategory = SubCategory::create($validated);
        return response()->json(['message' => 'Sub Category created successfully', 'sub_category' => $subCategory]);
    }

    public function updateSubCategory(Request $request, $id)
    {
        $validated = $request->validate([
            'type' => 'required|in:TWK,TIU,TKP',
            'name' => 'required|string|max:255',
        ]);

        $subCategory = SubCategory::findOrFail($id);
        $subCategory->update($validated);
        return response()->json(['message' => 'Sub Category updated successfully', 'sub_category' => $subCategory]);
    }

    public function deleteSubCategory($id)
    {
        $subCategory = SubCategory::findOrFail($id);
        $subCategory->delete();
        return response()->json(['message' => 'Sub Category deleted successfully']);
    }

    // --- REVIEW MANAGEMENT ---

    public function getReviews()
    {
        $reviews = Review::with(['user:id,name,email', 'tryout:id,title'])->orderBy('created_at', 'desc')->get();
        return response()->json($reviews);
    }

    public function deleteReview($id)
    {
        $review = Review::findOrFail($id);
        $review->delete();
        return response()->json(['message' => 'Review deleted successfully']);
    }

    // --- BUNDLE MANAGEMENT ---

    public function getBundles()
    {
        $bundles = \App\Models\Bundle::with('tryouts:id,title')->withCount('tryouts')->orderBy('created_at', 'desc')->get();
        return response()->json($bundles);
    }

    public function createBundle(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'price' => 'required|numeric|min:0',
            'discount_price' => 'nullable|numeric|min:0',
            'is_active' => 'boolean',
            'cover_image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
            'tryout_ids' => 'nullable|array',
            'tryout_ids.*' => 'exists:tryouts,id'
        ]);

        if ($request->hasFile('cover_image')) {
            $path = $request->file('cover_image')->store('covers', 'public');
            $validated['cover_image'] = '/storage/' . $path;
        }

        $bundle = \App\Models\Bundle::create(\Illuminate\Support\Arr::except($validated, ['tryout_ids']));
        
        if (isset($validated['tryout_ids'])) {
            $bundle->tryouts()->sync($validated['tryout_ids']);
        }

        return response()->json(['message' => 'Bundle created successfully', 'bundle' => $bundle]);
    }

    public function updateBundle(Request $request, $id)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'price' => 'required|numeric|min:0',
            'discount_price' => 'nullable|numeric|min:0',
            'is_active' => 'boolean',
            'cover_image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
            'tryout_ids' => 'nullable|array',
            'tryout_ids.*' => 'exists:tryouts,id'
        ]);

        $bundle = \App\Models\Bundle::findOrFail($id);
        
        if ($request->hasFile('cover_image')) {
            if ($bundle->cover_image && str_starts_with($bundle->cover_image, '/storage/')) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete(str_replace('/storage/', '', $bundle->cover_image));
            }
            $path = $request->file('cover_image')->store('covers', 'public');
            $validated['cover_image'] = '/storage/' . $path;
        }

        $bundle->update(\Illuminate\Support\Arr::except($validated, ['tryout_ids']));

        if (isset($validated['tryout_ids'])) {
            $bundle->tryouts()->sync($validated['tryout_ids']);
        }

        return response()->json(['message' => 'Bundle updated successfully', 'bundle' => $bundle]);
    }

    public function deleteBundle($id)
    {
        $bundle = \App\Models\Bundle::findOrFail($id);
        $bundle->delete();
        return response()->json(['message' => 'Bundle deleted successfully']);
    }
}
