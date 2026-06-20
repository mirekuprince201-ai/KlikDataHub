<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class CategoryController extends Controller
{
    public function index()
    {
        $categories = DB::table('categories')
            ->orderBy('sort_order')
            ->orderBy('category_name')
            ->get();

        return view('admin.categories.index', compact('categories'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'category_name' => [
                'required',
                'string',
                'max:150',
                Rule::unique('categories', 'category_name'),
            ],
            'category_description' => [
                'nullable',
                'string',
                'max:5000',
            ],
            'category_image' => [
                'nullable',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:2048',
            ],
            'sort_order' => [
                'nullable',
                'integer',
                'min:0',
                'max:999999',
            ],
            'status' => [
                'required',
                Rule::in(['active', 'inactive']),
            ],
        ]);

        $categoryName = trim($validated['category_name']);
        $slug = $this->generateUniqueSlug($categoryName);

        DB::beginTransaction();

        try {
            $imagePath = null;

            if ($request->hasFile('category_image')) {
                $imagePath = $this->uploadImage($request->file('category_image'));
            }

            DB::table('categories')->insert([
                'category_id' => (string) Str::uuid(),
                'category_name' => strtoupper($categoryName),
                'category_slug' => $slug,
                'category_description' => $validated['category_description'] ?? null,
                'category_image' => $imagePath,
                'sort_order' => $validated['sort_order'] ?? 0,
                'status' => $validated['status'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::commit();

            return back()->with('success', 'Category created successfully.');
        } catch (\Throwable $e) {
            DB::rollBack();

            if (!empty($imagePath)) {
                $this->deleteImage($imagePath);
            }

            return back()
                ->withInput()
                ->with('error', 'Unable to create category. Please try again.');
        }
    }

    public function show(string $category_id)
    {
        $category = DB::table('categories')
            ->where('category_id', $category_id)
            ->first();

        if (!$category) {
            return response()->json([
                'status' => false,
                'message' => 'Category not found.',
            ], 404);
        }

        return response()->json([
            'status' => true,
            'category' => $category,
        ]);
    }

    public function update(Request $request, string $category_id)
    {
        $category = DB::table('categories')
            ->where('category_id', $category_id)
            ->first();

        if (!$category) {
            return back()->with('error', 'Category not found.');
        }

        $validated = $request->validate([
            'category_name' => [
                'required',
                'string',
                'max:150',
                Rule::unique('categories', 'category_name')
                    ->ignore($category_id, 'category_id'),
            ],
            'category_description' => [
                'nullable',
                'string',
                'max:5000',
            ],
            'category_image' => [
                'nullable',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:2048',
            ],
            'sort_order' => [
                'nullable',
                'integer',
                'min:0',
                'max:999999',
            ],
            'status' => [
                'required',
                Rule::in(['active', 'inactive']),
            ],
        ]);

        $categoryName = trim($validated['category_name']);

        $slug = $category->category_slug;

        if (strtolower($categoryName) !== strtolower($category->category_name)) {
            $slug = $this->generateUniqueSlug($categoryName, $category_id);
        }

        DB::beginTransaction();

        try {
            $imagePath = $category->category_image;

            if ($request->hasFile('category_image')) {
                $newImagePath = $this->uploadImage($request->file('category_image'));

                if (!empty($category->category_image)) {
                    $this->deleteImage($category->category_image);
                }

                $imagePath = $newImagePath;
            }

            DB::table('categories')
                ->where('category_id', $category_id)
                ->update([
                    'category_name' => strtoupper($categoryName),
                    'category_slug' => $slug,
                    'category_description' => $validated['category_description'] ?? null,
                    'category_image' => $imagePath,
                    'sort_order' => $validated['sort_order'] ?? 0,
                    'status' => $validated['status'],
                    'updated_at' => now(),
                ]);

            DB::commit();

            return back()->with('success', 'Category updated successfully.');
        } catch (\Throwable $e) {
            DB::rollBack();

            return back()
                ->withInput()
                ->with('error', 'Unable to update category. Please try again.');
        }
    }

    public function updateStatus(Request $request, string $category_id)
    {
        $validated = $request->validate([
            'status' => [
                'required',
                Rule::in(['active', 'inactive']),
            ],
        ]);

        $category = DB::table('categories')
            ->where('category_id', $category_id)
            ->first();

        if (!$category) {
            return back()->with('error', 'Category not found.');
        }

        DB::table('categories')
            ->where('category_id', $category_id)
            ->update([
                'status' => $validated['status'],
                'updated_at' => now(),
            ]);

        return back()->with('success', 'Category status updated successfully.');
    }

    public function destroy(string $category_id)
    {
        $category = DB::table('categories')
            ->where('category_id', $category_id)
            ->first();

        if (!$category) {
            return back()->with('error', 'Category not found.');
        }

        $plansCount = DB::table('plans')
            ->where('category_id', $category_id)
            ->count();

        if ($plansCount > 0) {
            return back()->with('error', 'This category cannot be deleted because it has plans attached.');
        }

        DB::beginTransaction();

        try {
            DB::table('categories')
                ->where('category_id', $category_id)
                ->delete();

            if (!empty($category->category_image)) {
                $this->deleteImage($category->category_image);
            }

            DB::commit();

            return back()->with('success', 'Category deleted successfully.');
        } catch (\Throwable $e) {
            DB::rollBack();

            return back()->with('error', 'Unable to delete category. Please try again.');
        }
    }

    private function generateUniqueSlug(string $name, ?string $ignoreCategoryId = null): string
    {
        $baseSlug = Str::slug($name);
        $slug = $baseSlug;
        $counter = 1;

        while (
            DB::table('categories')
                ->where('category_slug', $slug)
                ->when($ignoreCategoryId, function ($query) use ($ignoreCategoryId) {
                    $query->where('category_id', '!=', $ignoreCategoryId);
                })
                ->exists()
        ) {
            $slug = $baseSlug . '-' . $counter;
            $counter++;
        }

        return $slug;
    }

    private function uploadImage($image): string
    {
        $folder = public_path('uploads/categories');

        if (!File::exists($folder)) {
            File::makeDirectory($folder, 0755, true);
        }

        $filename = 'category-' . time() . '-' . Str::random(10) . '.' . $image->getClientOriginalExtension();

        $image->move($folder, $filename);

        return 'uploads/categories/' . $filename;
    }

    private function deleteImage(?string $path): void
    {
        if (!$path) {
            return;
        }

        $fullPath = public_path($path);

        if (File::exists($fullPath)) {
            File::delete($fullPath);
        }
    }

    public function status($id)
{
    $category = DB::table('categories')->where('category_id', $id)->first();

    if (!$category) {
        return back()->with('error', 'Category not found.');
    }

    DB::table('categories')
        ->where('category_id', $id)
        ->update([
            'status' => $category->status === 'active' ? 'inactive' : 'active',
            'updated_at' => now(),
        ]);

    return back()->with('success', 'Category status updated successfully.');
}
}