<?php

namespace App\Http\Controllers\Admin\ResultChecker;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class ResultCheckerController extends Controller
{
    public function index()
    {
        return view('admin.result-checkers.index');
    }

    public function create()
    {
        $categories = DB::table('result_checker_categories')
            ->where('status', 'active')
            ->orderBy('sort_order')
            ->orderBy('category_name')
            ->get();

        return view('admin.result-checkers.create', compact('categories'));
    }

    public function categories()
    {
        $categories = DB::table('result_checker_categories')
            ->orderBy('sort_order')
            ->orderBy('category_name')
            ->get();

        return view('admin.result-checkers.categories', compact('categories'));
    }

    private function uploadCategoryImage(Request $request): ?string
    {
        if (!$request->hasFile('category_image')) {
            return null;
        }

        $file = $request->file('category_image');

        $folder = public_path('uploads/result-checker-categories');

        if (!File::exists($folder)) {
            File::makeDirectory($folder, 0755, true);
        }

        $fileName = 'result-checker-category-' . time() . '-' . Str::random(10) . '.' . $file->getClientOriginalExtension();

        $file->move($folder, $fileName);

        return 'uploads/result-checker-categories/' . $fileName;
    }

    private function deleteCategoryImage(?string $imagePath): void
    {
        if (!empty($imagePath) && File::exists(public_path($imagePath))) {
            File::delete(public_path($imagePath));
        }
    }

    public function storeCategory(Request $request)
    {
        $request->validate([
            'category_name' => 'required|string|max:255',
            'category_description' => 'nullable|string',
            'category_image' => 'nullable|image|mimes:jpg,jpeg,png,webp,avif|max:4096',
            'sort_order' => 'nullable|integer|min:0',
            'status' => 'required|in:active,inactive',
        ]);

        $categoryName = strtoupper(trim($request->category_name));
        $slug = Str::slug($categoryName);

        $exists = DB::table('result_checker_categories')
            ->where('category_slug', $slug)
            ->exists();

        if ($exists) {
            return back()
                ->withInput()
                ->withErrors(['category_name' => 'This category already exists.']);
        }

        $imagePath = $this->uploadCategoryImage($request);

        DB::table('result_checker_categories')->insert([
            'category_id' => (string) Str::uuid(),
            'category_name' => $categoryName,
            'category_slug' => $slug,
            'category_description' => $request->category_description ? strtoupper(trim($request->category_description)) : null,
            'category_image' => $imagePath,
            'sort_order' => $request->sort_order ?? 0,
            'status' => strtolower($request->status),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return back()->with('success', 'Result checker category created successfully.');
    }

    public function updateCategory(Request $request, string $category_id)
    {
        $category = DB::table('result_checker_categories')
            ->where('category_id', $category_id)
            ->first();

        abort_if(!$category, 404);

        $request->validate([
            'category_name' => 'required|string|max:255',
            'category_description' => 'nullable|string',
            'category_image' => 'nullable|image|mimes:jpg,jpeg,png,webp,avif|max:4096',
            'sort_order' => 'nullable|integer|min:0',
            'status' => 'required|in:active,inactive',
        ]);

        $categoryName = strtoupper(trim($request->category_name));
        $slug = Str::slug($categoryName);

        $exists = DB::table('result_checker_categories')
            ->where('category_slug', $slug)
            ->where('category_id', '!=', $category_id)
            ->exists();

        if ($exists) {
            return back()
                ->withInput()
                ->withErrors(['category_name' => 'This category already exists.']);
        }

        $imagePath = $category->category_image;

        if ($request->hasFile('category_image')) {
            $this->deleteCategoryImage($category->category_image);
            $imagePath = $this->uploadCategoryImage($request);
        }

        DB::table('result_checker_categories')
            ->where('category_id', $category_id)
            ->update([
                'category_name' => $categoryName,
                'category_slug' => $slug,
                'category_description' => $request->category_description ? strtoupper(trim($request->category_description)) : null,
                'category_image' => $imagePath,
                'sort_order' => $request->sort_order ?? 0,
                'status' => strtolower($request->status),
                'updated_at' => now(),
            ]);

        return back()->with('success', 'Result checker category updated successfully.');
    }

    public function categoryStatus(string $category_id)
    {
        $category = DB::table('result_checker_categories')
            ->where('category_id', $category_id)
            ->first();

        abort_if(!$category, 404);

        $newStatus = strtolower($category->status) === 'active' ? 'inactive' : 'active';

        DB::table('result_checker_categories')
            ->where('category_id', $category_id)
            ->update([
                'status' => $newStatus,
                'updated_at' => now(),
            ]);

        return back()->with('success', 'Result checker category status updated successfully.');
    }

    public function destroyCategory(string $category_id)
    {
        $category = DB::table('result_checker_categories')
            ->where('category_id', $category_id)
            ->first();

        abort_if(!$category, 404);

        $this->deleteCategoryImage($category->category_image);

        DB::table('result_checker_categories')
            ->where('category_id', $category_id)
            ->delete();

        return back()->with('success', 'Result checker category deleted successfully.');
    }

    public function orders()
    {
        return view('admin.result-checkers.orders');
    }

    public function reports()
    {
        return view('admin.result-checkers.reports');
    }

    public function store(Request $request)
    {
        return back()->with('success', 'Result checker saved successfully.');
    }

    public function show($checker_id)
    {
        return view('admin.result-checkers.show', compact('checker_id'));
    }

    public function edit($checker_id)
    {
        return view('admin.result-checkers.edit', compact('checker_id'));
    }

    public function update(Request $request, $checker_id)
    {
        return back()->with('success', 'Result checker updated successfully.');
    }

    public function status($checker_id)
    {
        return back()->with('success', 'Result checker status updated successfully.');
    }

    public function destroy($checker_id)
    {
        return back()->with('success', 'Result checker deleted successfully.');
    }
}