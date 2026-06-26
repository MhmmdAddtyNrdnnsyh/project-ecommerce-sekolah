<?php

namespace App\Http\Controllers;

use App\Enums\ProductSalesMethod;
use App\Enums\ProductStatus;
use App\Enums\UserRole;
use App\Models\Category;
use App\Models\Product;
use App\Models\UpJurusan;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class AdminJurusanUpJurusanController extends Controller
{
    public function index(Request $request): Response
    {
        /** @var User $adminJurusan */
        $adminJurusan = $request->user();

        return Inertia::render('admin-jurusan/up-jurusan/index', [
            'upJurusans' => UpJurusan::query()
                ->with('picketOfficers:id,name,email,up_jurusan_id')
                ->where('admin_jurusan_id', $adminJurusan->id)
                ->latest()
                ->get(['id', 'name', 'description', 'admin_jurusan_id'])
                ->all(),
            'picketOptions' => User::query()
                ->where('role', UserRole::PicketOfficer)
                ->where(function ($query) use ($adminJurusan) {
                    $query
                        ->whereNull('up_jurusan_id')
                        ->orWhereHas('upJurusan', fn ($query) => $query->where('admin_jurusan_id', $adminJurusan->id));
                })
                ->orderBy('name')
                ->get(['id', 'name', 'email', 'up_jurusan_id'])
                ->all(),
            'categories' => Category::query()
                ->orderBy('name')
                ->get(['id', 'name'])
                ->all(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        /** @var User $adminJurusan */
        $adminJurusan = $request->user();

        if (UpJurusan::query()->where('admin_jurusan_id', $adminJurusan->id)->exists()) {
            throw ValidationException::withMessages([
                'up_jurusan' => 'Admin jurusan hanya dapat memiliki satu UP Jurusan.',
            ])->redirectTo(route('admin-jurusan.up-jurusan.index'));
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
        ]);

        UpJurusan::query()->create([
            'admin_jurusan_id' => $adminJurusan->id,
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
        ]);

        return to_route('admin-jurusan.up-jurusan.index')
            ->with('success', 'UP Jurusan berhasil dibuat.');
    }

    public function assignPicket(Request $request, UpJurusan $upJurusan): RedirectResponse
    {
        /** @var User $adminJurusan */
        $adminJurusan = $request->user();

        abort_unless($upJurusan->admin_jurusan_id === $adminJurusan->id, 403);

        $validated = $request->validate([
            'picket_id' => ['required', 'integer'],
        ]);

        $picket = User::query()
            ->whereKey($validated['picket_id'])
            ->where('role', UserRole::PicketOfficer)
            ->firstOrFail();

        if ($picket->up_jurusan_id !== null && $picket->up_jurusan_id !== $upJurusan->id) {
            throw ValidationException::withMessages([
                'picket_id' => 'Picket officer sudah ditugaskan ke UP Jurusan lain.',
            ])->redirectTo(route('admin-jurusan.up-jurusan.index'));
        }

        $picket->update(['up_jurusan_id' => $upJurusan->id]);

        return to_route('admin-jurusan.up-jurusan.index')
            ->with('success', 'Picket officer berhasil ditugaskan.');
    }

    public function storeProduct(Request $request): RedirectResponse
    {
        /** @var User $adminJurusan */
        $adminJurusan = $request->user();
        $validated = $request->validate([
            'up_jurusan_id' => ['required', 'integer'],
            'category_id' => ['required', 'integer', 'exists:categories,id'],
            'name' => ['required', 'string', 'min:3', 'max:120'],
            'description' => ['required', 'string', 'min:10', 'max:5000'],
            'price' => ['required', 'integer', 'min:1', 'max:100000000'],
            'stock' => ['required', 'integer', 'min:0', 'max:100000'],
        ]);

        $upJurusan = UpJurusan::query()
            ->whereKey($validated['up_jurusan_id'])
            ->firstOrFail();
        abort_unless($upJurusan->admin_jurusan_id === $adminJurusan->id, 403);

        Product::query()->create([
            'seller_id' => null,
            'up_jurusan_id' => $upJurusan->id,
            'category_id' => $validated['category_id'],
            'name' => $validated['name'],
            'slug' => $this->uniqueSlug($validated['name']),
            'description' => $validated['description'],
            'price' => $validated['price'],
            'stock' => $validated['stock'],
            'sales_method' => ProductSalesMethod::UpJurusan,
            'status' => ProductStatus::Approved,
        ]);

        return to_route('admin-jurusan.up-jurusan.index')
            ->with('success', 'Produk UP Jurusan berhasil dibuat.');
    }

    private function uniqueSlug(string $name): string
    {
        $base = Str::slug($name);
        $slug = $base;
        $counter = 2;

        while (Product::query()->where('slug', $slug)->exists()) {
            $slug = "{$base}-{$counter}";
            $counter++;
        }

        return $slug;
    }
}
