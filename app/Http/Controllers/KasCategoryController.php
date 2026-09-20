<?php

namespace App\Http\Controllers;

use App\Models\KasCategory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class KasCategoryController extends Controller
{
    public function index(Request $request): View
    {
        $categories = KasCategory::withCount('kasTunais')
            ->ordered()
            ->get();

        $keluarCategories = $categories->where('type', 'keluar');
        $masukCategories = $categories->where('type', 'masuk');

        return view('kas_category.index', compact('keluarCategories', 'masukCategories', 'categories'));
    }

    public function store(Request $request): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'type' => ['required', 'in:masuk,keluar'],
            'code' => ['nullable', 'string', 'max:80', 'unique:kas_categories,code'],
            'icon' => ['nullable', 'string', 'max:50'],
            'description' => ['nullable', 'string', 'max:500'],
            'sort_order' => ['nullable', 'integer'],
        ]);

        $code = ! empty($validated['code'])
            ? Str::slug($validated['code'], '_')
            : Str::slug($validated['name'], '_');

        // Ensure uniqueness if slug collision occurs
        if (KasCategory::where('code', $code)->exists()) {
            $code = $code.'_'.Str::lower(Str::random(4));
        }

        $sortOrder = $validated['sort_order'] ?? null;
        if ($sortOrder === null) {
            $maxOrder = KasCategory::where('type', $validated['type'])->where('sort_order', '<', 99)->max('sort_order') ?? 0;
            $sortOrder = $maxOrder + 1;
        }

        $category = KasCategory::create([
            'code' => $code,
            'name' => $validated['name'],
            'type' => $validated['type'],
            'icon' => $validated['icon'] ?? ($validated['type'] === 'masuk' ? '📥' : '🛍️'),
            'description' => $validated['description'] ?? null,
            'sort_order' => (int) $sortOrder,
            'is_system' => false,
            'is_active' => true,
        ]);

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Kategori transaksi berhasil ditambahkan.',
                'category' => $category,
            ], 201);
        }

        return redirect()->route('kas-category.index')->with('success', 'Kategori transaksi berhasil ditambahkan.');
    }

    public function update(Request $request, KasCategory $kasCategory): JsonResponse|RedirectResponse
    {
        $rules = [
            'name' => ['required', 'string', 'max:150'],
            'icon' => ['nullable', 'string', 'max:50'],
            'description' => ['nullable', 'string', 'max:500'],
            'sort_order' => ['nullable', 'integer'],
        ];

        // Non-system categories can change their code and type
        if (! $kasCategory->is_system) {
            $rules['type'] = ['required', 'in:masuk,keluar'];
            $rules['code'] = [
                'nullable',
                'string',
                'max:80',
                Rule::unique('kas_categories', 'code')->ignore($kasCategory->id),
            ];
        }

        $validated = $request->validate($rules);

        $data = [
            'name' => $validated['name'],
            'icon' => $validated['icon'] ?? null,
            'description' => $validated['description'] ?? null,
            'sort_order' => isset($validated['sort_order']) ? (int) $validated['sort_order'] : $kasCategory->sort_order,
        ];

        if (! $kasCategory->is_system) {
            $data['type'] = $validated['type'];
            if (! empty($validated['code'])) {
                $data['code'] = Str::slug($validated['code'], '_');
            }
        }

        $kasCategory->update($data);

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Kategori transaksi berhasil diperbarui.',
                'category' => $kasCategory,
            ]);
        }

        return redirect()->route('kas-category.index')->with('success', 'Kategori transaksi berhasil diperbarui.');
    }

    public function destroy(Request $request, KasCategory $kasCategory): JsonResponse|RedirectResponse
    {
        if ($kasCategory->is_system) {
            $msg = 'Kategori sistem tidak dapat dihapus karena digunakan oleh alur otomatisasi aplikasi.';
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json(['success' => false, 'message' => $msg], 422);
            }

            return redirect()->route('kas-category.index')->with('error', $msg);
        }

        if ($kasCategory->kasTunais()->exists()) {
            $msg = 'Kategori ini telah digunakan dalam riwayat transaksi kas tunai dan tidak dapat dihapus. Anda dapat menonaktifkannya agar tidak muncul lagi di pilihan baru.';
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json(['success' => false, 'message' => $msg], 422);
            }

            return redirect()->route('kas-category.index')->with('error', $msg);
        }

        $kasCategory->delete();

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Kategori transaksi berhasil dihapus.',
            ]);
        }

        return redirect()->route('kas-category.index')->with('success', 'Kategori transaksi berhasil dihapus.');
    }

    public function toggleStatus(Request $request, KasCategory $kasCategory): JsonResponse
    {
        $kasCategory->is_active = ! $kasCategory->is_active;
        $kasCategory->save();

        return response()->json([
            'success' => true,
            'is_active' => $kasCategory->is_active,
            'message' => $kasCategory->is_active
                ? "Kategori '{$kasCategory->name}' berhasil diaktifkan."
                : "Kategori '{$kasCategory->name}' berhasil dinonaktifkan.",
        ]);
    }
}
