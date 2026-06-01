<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class BundleController extends Controller
{
    public function index()
    {
        $bundles = \App\Models\Bundle::where('is_active', true)
            ->with(['tryouts' => function($q) {
                $q->select('tryouts.id', 'title', 'duration_minutes', 'price', 'category_id')
                  ->withCount('questions');
            }])
            ->withCount('tryouts')
            ->get();
        return response()->json($bundles);
    }

    public function show($id)
    {
        $bundle = \App\Models\Bundle::where('is_active', true)
            ->with(['tryouts' => function($q) {
                $q->select('tryouts.id', 'title', 'description', 'duration_minutes', 'price', 'category_id', 'cover_image')
                  ->withCount('questions')
                  ->with('category:id,name,color');
            }])
            ->withCount('tryouts')
            ->findOrFail($id);

        return response()->json($bundle);
    }
}

