<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Skill;
use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class MasterDataController extends Controller
{
    public function index(): View
    {
        $categories = Category::withCount('projects')->orderBy('id')->get();
        $activeCategoriesCount = $categories->where('is_active', true)->count();

        // PBI-07: DIPAKAI = jumlah kandidat + jumlah proyek yang memakai keterampilan
        $skills = Skill::withCount(['candidateProfiles', 'projects'])->orderBy('id')->get();
        $activeSkillsCount = $skills->where('is_active', true)->count();

        return view('admin.master.index', compact(
            'categories', 'activeCategoriesCount', 'skills', 'activeSkillsCount'
        ));
    }

    public function storeCategory(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100', 'unique:categories,name'],
        ], [
            'name.required' => 'Nama kategori wajib diisi.',
            'name.unique' => 'Kategori "' . $request->input('name') . '" sudah terdaftar.',
        ]);

        Category::create([
            'name' => trim($validated['name']),
            'is_active' => true,
        ]);

        return redirect()->route('admin.master.index')->with('status', 'Kategori baru berhasil ditambahkan.');
    }

    public function updateCategory(Request $request, Category $category): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100', Rule::unique('categories', 'name')->ignore($category->id)],
        ], [
            'name.required' => 'Nama kategori wajib diisi.',
            'name.unique' => 'Kategori "' . $request->input('name') . '" sudah terdaftar.',
        ]);

        $category->update(['name' => trim($validated['name'])]);

        return redirect()->route('admin.master.index')->with('status', 'Nama kategori berhasil diperbarui.');
    }

    public function toggleCategory(Category $category): RedirectResponse
    {
        $category->update(['is_active' => !$category->is_active]);

        return redirect()->route('admin.master.index')->with('status', 'Status kategori berhasil diperbarui.');
    }

    // ------------------------------------------------------------------
    // PBI-07: Mengelola Data Master Keterampilan
    // ------------------------------------------------------------------

    public function storeSkill(Request $request): RedirectResponse
    {
        $validated = $request->validateWithBag('skillCreate', [
            'skill_name' => $this->skillNameRules(),
        ], $this->skillMessages());

        Skill::create([
            'name' => trim($validated['skill_name']),
            'is_active' => true,
        ]);

        return redirect()->route('admin.master.index')->with('success', 'Keterampilan baru berhasil ditambahkan.');
    }

    public function updateSkill(Request $request, Skill $skill): RedirectResponse
    {
        $validated = $request->validateWithBag('skillUpdate-' . $skill->id, [
            'skill_name' => $this->skillNameRules($skill),
        ], $this->skillMessages());

        $skill->update(['name' => trim($validated['skill_name'])]);

        return redirect()->route('admin.master.index')->with('success', 'Nama keterampilan berhasil diperbarui.');
    }

    /** Keterampilan tidak dihapus, hanya dinonaktifkan atau diaktifkan kembali. */
    public function toggleSkill(Skill $skill): RedirectResponse
    {
        $skill->update(['is_active' => ! $skill->is_active]);

        return redirect()->route('admin.master.index')->with('success', 'Status keterampilan berhasil diperbarui.');
    }

    /**
     * Nama wajib unik TANPA membedakan huruf besar/kecil ("figma" dianggap sama dengan "Figma"),
     * supaya hasilnya sama di SQLite (test) dan MySQL. $ignore = keterampilan yang sedang diubah.
     */
    private function skillNameRules(?Skill $ignore = null): array
    {
        return [
            'bail', 'required', 'string', 'max:100',
            function (string $attribute, mixed $value, Closure $fail) use ($ignore) {
                $existing = Skill::query()
                    ->whereRaw('LOWER(name) = ?', [mb_strtolower(trim($value))])
                    ->when($ignore, fn ($q) => $q->whereKeyNot($ignore->id))
                    ->first();

                if ($existing) {
                    $fail('Keterampilan "' . $existing->name . '" sudah terdaftar. Nama keterampilan harus unik.');
                }
            },
        ];
    }

    private function skillMessages(): array
    {
        return [
            'skill_name.required' => 'Nama keterampilan wajib diisi.',
            'skill_name.string' => 'Nama keterampilan tidak valid.',
            'skill_name.max' => 'Nama keterampilan maksimal :max karakter.',
        ];
    }
}