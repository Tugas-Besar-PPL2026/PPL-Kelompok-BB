<?php

namespace App\Http\Controllers;

use App\Models\Category;
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

        return view('admin.master.index', compact('categories', 'activeCategoriesCount'));
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
}