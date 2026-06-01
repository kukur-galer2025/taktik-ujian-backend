<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Category;

class CategoryController extends Controller
{
    public function index()
    {
        return response()->json(Category::withCount('tryouts')->orderBy('id')->get());
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:categories,slug',
            'color' => 'nullable|string|max:255',
        ]);

        $category = Category::create($validated);
        return response()->json(['message' => 'Kategori berhasil ditambahkan', 'category' => $category]);
    }

    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:categories,slug,' . $id,
            'color' => 'nullable|string|max:255',
        ]);

        $category = Category::findOrFail($id);
        $category->update($validated);
        return response()->json(['message' => 'Kategori berhasil diubah', 'category' => $category]);
    }

    public function destroy($id)
    {
        $category = Category::findOrFail($id);
        
        if ($category->tryouts()->count() > 0) {
            return response()->json(['message' => 'Kategori tidak bisa dihapus karena masih memiliki tryout'], 400);
        }

        $category->delete();
        return response()->json(['message' => 'Kategori berhasil dihapus']);
    }
}
