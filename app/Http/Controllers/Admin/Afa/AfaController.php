<?php

namespace App\Http\Controllers\Admin\Afa;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;

class AfaController extends Controller
{
    public function index(): View
    {
        $afas = DB::table('afa_registrations')
            ->orderByDesc('created_at')
            ->get();

        $stats = [
            'total' => $afas->count(),
            'active' => $afas->where('status', 'active')->count(),
            'inactive' => $afas->where('status', 'inactive')->count(),
        ];

        return view('admin.afa.index', compact('afas', 'stats'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'registration_name' => ['required', 'string', 'max:255'],
            'registration_amount' => ['required', 'numeric', 'min:0'],
            'registration_image' => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp', 'max:2048'],
            'status' => ['required', 'in:active,inactive'],
        ]);

        $name = strtoupper(trim($request->registration_name));
        $slug = Str::slug($name);

        $duplicate = DB::table('afa_registrations')
            ->whereRaw('LOWER(registration_name) = ?', [strtolower($name)])
            ->orWhere('registration_slug', $slug)
            ->first();

        if ($duplicate) {
            return back()
                ->withInput()
                ->with('error', 'AFA registration with the same name already exists.');
        }

        $imagePath = null;

        if ($request->hasFile('registration_image')) {
            $imagePath = $request->file('registration_image')->store('afa-registrations', 'public');
            $imagePath = 'storage/' . $imagePath;
        }

        DB::table('afa_registrations')->insert([
            'afa_registration_id' => (string) Str::uuid(),
            'registration_name' => $name,
            'registration_slug' => $slug,
            'registration_amount' => $request->registration_amount,
            'registration_image' => $imagePath,
            'status' => $request->status,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return back()->with('success', 'AFA registration created successfully.');
    }

    public function show($id)
    {
        $afa = DB::table('afa_registrations')
            ->where('afa_registration_id', $id)
            ->first();

        if (!$afa) {
            return response()->json([
                'status' => false,
                'message' => 'AFA registration not found.',
            ], 404);
        }

        return response()->json([
            'status' => true,
            'afa' => $afa,
        ]);
    }

    public function update(Request $request, $id)
    {
        $afa = DB::table('afa_registrations')
            ->where('afa_registration_id', $id)
            ->first();

        if (!$afa) {
            return back()->with('error', 'AFA registration not found.');
        }

        $request->validate([
            'registration_name' => ['required', 'string', 'max:255'],
            'registration_amount' => ['required', 'numeric', 'min:0'],
            'registration_image' => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp', 'max:2048'],
            'status' => ['required', 'in:active,inactive'],
        ]);

        $name = strtoupper(trim($request->registration_name));
        $slug = Str::slug($name);

        $duplicate = DB::table('afa_registrations')
            ->where('afa_registration_id', '<>', $id)
            ->where(function ($query) use ($name, $slug) {
                $query->whereRaw('LOWER(registration_name) = ?', [strtolower($name)])
                    ->orWhere('registration_slug', $slug);
            })
            ->first();

        if ($duplicate) {
            return back()
                ->withInput()
                ->with('error', 'Another AFA registration with the same name already exists.');
        }

        $imagePath = $afa->registration_image;

        if ($request->hasFile('registration_image')) {
            if ($afa->registration_image) {
                $oldPath = str_replace('storage/', '', $afa->registration_image);

                if (Storage::disk('public')->exists($oldPath)) {
                    Storage::disk('public')->delete($oldPath);
                }
            }

            $newPath = $request->file('registration_image')->store('afa-registrations', 'public');
            $imagePath = 'storage/' . $newPath;
        }

        DB::table('afa_registrations')
            ->where('afa_registration_id', $id)
            ->update([
                'registration_name' => $name,
                'registration_slug' => $slug,
                'registration_amount' => $request->registration_amount,
                'registration_image' => $imagePath,
                'status' => $request->status,
                'updated_at' => now(),
            ]);

        return back()->with('success', 'AFA registration updated successfully.');
    }

    public function status(Request $request, $id)
    {
        $request->validate([
            'status' => ['required', 'in:active,inactive'],
        ]);

        $afa = DB::table('afa_registrations')
            ->where('afa_registration_id', $id)
            ->first();

        if (!$afa) {
            return back()->with('error', 'AFA registration not found.');
        }

        DB::table('afa_registrations')
            ->where('afa_registration_id', $id)
            ->update([
                'status' => $request->status,
                'updated_at' => now(),
            ]);

        return back()->with('success', 'AFA status updated successfully.');
    }

    public function destroy($id)
    {
        $afa = DB::table('afa_registrations')
            ->where('afa_registration_id', $id)
            ->first();

        if (!$afa) {
            return back()->with('error', 'AFA registration not found.');
        }

        if ($afa->registration_image) {
            $oldPath = str_replace('storage/', '', $afa->registration_image);

            if (Storage::disk('public')->exists($oldPath)) {
                Storage::disk('public')->delete($oldPath);
            }
        }

        DB::table('afa_registrations')
            ->where('afa_registration_id', $id)
            ->delete();

        return back()->with('success', 'AFA registration deleted successfully.');
    }
}