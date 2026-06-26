<?php

namespace App\Http\Controllers;

use App\Http\Requests\Admin\SaveCategoryRequest;
use App\Models\Category;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class AdminCategoryController extends Controller
{
    public function index(Request $request): Response
    {
        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:255'],
            'page' => ['nullable', 'integer', 'min:1'],
        ]);

        $query = Category::query()->withCount('products');

        if ($search = $validated['q'] ?? null) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('slug', 'like', "%{$search}%");
            });
        }

        $categories = $query->orderBy('name')->paginate(10)->withQueryString();

        return Inertia::render('admin/categories/index', [
            'categories' => $categories->through(fn (Category $category) => [
                'id' => $category->id,
                'name' => $category->name,
                'slug' => $category->slug,
                'products_count' => $category->products_count,
            ]),
            'filters' => [
                'q' => $validated['q'] ?? '',
            ],
        ]);
    }

    public function store(SaveCategoryRequest $request): RedirectResponse
    {
        Category::query()->create([
            'name' => $request->string('name')->toString(),
            'slug' => $this->uniqueSlug($request->string('name')->toString()),
        ]);

        return to_route('admin.categories.index')
            ->with('success', 'Kategori berhasil ditambahkan.');
    }

    public function update(SaveCategoryRequest $request, Category $category): RedirectResponse
    {
        $name = $request->string('name')->toString();

        $category->update([
            'name' => $name,
            'slug' => $this->uniqueSlug($name, $category),
        ]);

        return to_route('admin.categories.index')
            ->with('success', 'Kategori berhasil diperbarui.');
    }

    public function destroy(Category $category): RedirectResponse
    {
        if ($category->products()->exists()) {
            throw ValidationException::withMessages([
                'category' => 'Kategori tidak dapat dihapus karena masih memiliki produk.',
            ])->redirectTo(route('admin.categories.index'));
        }

        $category->delete();

        return to_route('admin.categories.index')
            ->with('success', 'Kategori berhasil dihapus.');
    }

    private function uniqueSlug(string $name, ?Category $ignoredCategory = null): string
    {
        $baseSlug = Str::slug($name) ?: 'category';
        $slug = $baseSlug;
        $suffix = 2;

        while ($this->slugExists($slug, $ignoredCategory)) {
            $slug = $baseSlug.'-'.$suffix;
            $suffix++;
        }

        return $slug;
    }

    private function slugExists(string $slug, ?Category $ignoredCategory = null): bool
    {
        return Category::query()
            ->where('slug', $slug)
            ->when(
                $ignoredCategory,
                fn ($query) => $query->whereKeyNot($ignoredCategory->getKey()),
            )
            ->exists();
    }
}
