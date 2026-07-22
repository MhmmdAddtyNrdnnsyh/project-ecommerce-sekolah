<?php

namespace App\Http\Controllers;

use App\Enums\ProductSalesMethod;
use App\Enums\ProductStatus;
use App\Enums\UpJurusanConsignmentStatus;
use App\Enums\UserRole;
use App\Models\Category;
use App\Models\Product;
use App\Models\UpJurusan;
use App\Models\UpJurusanConsignment;
use App\Models\User;
use App\Support\OrganizationLifecycleService;
use App\Support\ReportAggregationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
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
                ->with([
                    'picketOfficers:id,name,email,up_jurusan_id',
                    'products' => fn ($query) => $query
                        ->whereNull('seller_id')
                        ->with('category:id,name')
                        ->latest()
                        ->select(['id', 'up_jurusan_id', 'category_id', 'name', 'price', 'stock', 'status']),
                ])
                ->where('admin_jurusan_id', $adminJurusan->id)
                ->latest()
                ->get(['id', 'name', 'description', 'admin_jurusan_id'])
                ->map(function (UpJurusan $upJurusan) {
                    $revenueChart = $this->revenueChart($upJurusan);

                    return [
                        'id' => $upJurusan->id,
                        'name' => $upJurusan->name,
                        'description' => $upJurusan->description,
                        'picket_officers' => $upJurusan->picketOfficers
                            ->map(fn (User $picket) => [
                                'id' => $picket->id,
                                'name' => $picket->name,
                                'email' => $picket->email,
                                'up_jurusan_id' => $picket->up_jurusan_id,
                            ])
                            ->all(),
                        'products' => $upJurusan->products
                            ->map(fn (Product $product) => [
                                'id' => $product->id,
                                'name' => $product->name,
                                'category_name' => $product->category->name,
                                'price' => $product->price,
                                'stock' => $product->stock,
                                'status' => [
                                    'code' => $product->status->value,
                                    'label' => $product->status->label(),
                                ],
                            ])
                            ->all(),
                        'revenue_chart' => $revenueChart,
                        'summary' => $this->summary($upJurusan, $revenueChart),
                    ];
                })
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

    public function createPicket(Request $request): Response
    {
        /** @var User $adminJurusan */
        $adminJurusan = $request->user();

        $upJurusan = UpJurusan::query()
            ->with('picketOfficers:id,name,email,up_jurusan_id')
            ->where('admin_jurusan_id', $adminJurusan->id)
            ->latest()
            ->first(['id', 'name', 'description', 'admin_jurusan_id']);

        return Inertia::render('admin-jurusan/picket-officer/create', [
            'upJurusan' => $upJurusan ? [
                'id' => $upJurusan->id,
                'name' => $upJurusan->name,
                'description' => $upJurusan->description,
                'picket_officers' => $upJurusan->picketOfficers
                    ->map(fn (User $picket) => [
                        'id' => $picket->id,
                        'name' => $picket->name,
                        'email' => $picket->email,
                    ])
                    ->all(),
            ] : null,
        ]);
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

        OrganizationLifecycleService::assignPicket($upJurusan, $picket, $adminJurusan);

        return to_route('admin-jurusan.up-jurusan.index')
            ->with('success', 'Picket officer berhasil ditugaskan.');
    }

    public function unassignPicket(Request $request, UpJurusan $upJurusan): RedirectResponse
    {
        /** @var User $adminJurusan */
        $adminJurusan = $request->user();

        abort_unless($upJurusan->admin_jurusan_id === $adminJurusan->id, 403);

        OrganizationLifecycleService::unassignPicket($upJurusan, $adminJurusan);

        return to_route('admin-jurusan.up-jurusan.index')
            ->with('success', 'Picket officer berhasil dilepas.');
    }

    public function destroy(Request $request, UpJurusan $upJurusan): RedirectResponse
    {
        /** @var User $adminJurusan */
        $adminJurusan = $request->user();

        abort_unless($upJurusan->admin_jurusan_id === $adminJurusan->id, 403);

        OrganizationLifecycleService::deleteUpJurusan($upJurusan, $adminJurusan);

        return to_route('admin-jurusan.up-jurusan.index')
            ->with('success', 'UP Jurusan berhasil dihapus.');
    }

    public function storePicket(Request $request, UpJurusan $upJurusan): RedirectResponse
    {
        /** @var User $adminJurusan */
        $adminJurusan = $request->user();

        abort_unless($upJurusan->admin_jurusan_id === $adminJurusan->id, 403);

        if ($this->hasPicketOfficer($upJurusan)) {
            throw ValidationException::withMessages([
                'email' => 'UP Jurusan ini sudah memiliki satu picket officer.',
            ])->redirectTo(route('admin-jurusan.picket-officer.create'));
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', Rule::unique(User::class)],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $picket = User::query()->create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'role' => UserRole::PicketOfficer,
            'password' => $validated['password'],
            'up_jurusan_id' => null,
        ]);

        OrganizationLifecycleService::assignPicket($upJurusan, $picket, $adminJurusan);

        return to_route('admin-jurusan.picket-officer.create')
            ->with('success', 'Akun picket officer berhasil dibuat.');
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

    private function hasPicketOfficer(UpJurusan $upJurusan): bool
    {
        return User::query()
            ->where('role', UserRole::PicketOfficer)
            ->where('up_jurusan_id', $upJurusan->id)
            ->exists();
    }

    /**
     * @return array<int, array{day: string, revenue: int}>
     */
    private function revenueChart(UpJurusan $upJurusan): array
    {
        return ReportAggregationService::upRevenueChart((int) $upJurusan->id, 7);
    }

    /**
     * @param  array<int, array{day: string, revenue: int}>  $revenueChart
     * @return array{revenue_7_days: int, up_product_count: int, active_consignment_count: int, available_stock: int, picket_names: array<int, string>}
     */
    private function summary(UpJurusan $upJurusan, array $revenueChart): array
    {
        $activeConsignments = UpJurusanConsignment::query()
            ->where('up_jurusan_id', $upJurusan->id)
            ->whereIn('status', [
                UpJurusanConsignmentStatus::Approved,
                UpJurusanConsignmentStatus::Received,
                UpJurusanConsignmentStatus::Completed,
            ])
            ->get(['received_quantity', 'sold_quantity']);

        return [
            'revenue_7_days' => (int) collect($revenueChart)->sum('revenue'),
            'up_product_count' => $upJurusan->products->count(),
            'active_consignment_count' => $activeConsignments->count(),
            'available_stock' => (int) $upJurusan->products->sum('stock')
                + (int) $activeConsignments->sum(fn (UpJurusanConsignment $consignment) => max(0, $consignment->received_quantity - $consignment->sold_quantity)),
            'picket_names' => $upJurusan->picketOfficers
                ->pluck('name')
                ->values()
                ->all(),
        ];
    }
}
