# Seller Header Actions Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Make seller header Search, Notification, Help, and Support controls functional using current seller data and existing routes.

**Architecture:** `HandleInertiaRequests` will expose a small seller-only `sellerHeader` shared prop containing current action items and the configured support email. `AppSidebarHeader` will own the search chooser, notification dropdown, and two static dialogs, reusing existing Wayfinder routes and Radix-based UI components.

**Tech Stack:** Laravel 12, Inertia.js 3, React 19, TypeScript, Wayfinder, Pest, existing shadcn/Radix components.

---

## File Map

- Modify `tests/Feature/DashboardTest.php`: prove notification data is seller-scoped and empty when no action is needed.
- Modify `app/Http/Middleware/HandleInertiaRequests.php`: build the bounded seller header shared prop.
- Modify `resources/js/types/global.d.ts`: type the new shared prop.
- Modify `resources/js/components/app-sidebar-header.tsx`: implement all four header interactions.

### Task 1: Seller Header Shared Data

**Files:**
- Modify: `tests/Feature/DashboardTest.php`
- Modify: `app/Http/Middleware/HandleInertiaRequests.php`

- [ ] **Step 1: Write failing feature tests for scoped and empty notifications**

Append these tests to `tests/Feature/DashboardTest.php`:

```php
test('seller header notifications contain only current seller action items', function () {
    $seller = User::factory()->create(['role' => UserRole::Seller]);
    $otherSeller = User::factory()->create(['role' => UserRole::Seller]);
    $buyer = User::factory()->create();
    $category = Category::factory()->create();

    $lowStockProduct = Product::factory()->for($seller, 'seller')->for($category)->approved()->create([
        'name' => 'Pulpen Biru',
        'stock' => 2,
    ]);
    $normalProduct = Product::factory()->for($seller, 'seller')->for($category)->approved()->create([
        'stock' => 20,
    ]);
    $otherProduct = Product::factory()->for($otherSeller, 'seller')->for($category)->approved()->create([
        'stock' => 0,
    ]);

    $order = Order::factory()->for($buyer)->create();
    $pendingItem = OrderItem::factory()->for($order)->for($lowStockProduct)->create([
        'product_name' => $lowStockProduct->name,
        'status' => OrderItemStatus::Pending,
    ]);
    OrderItem::factory()->for($order)->for($normalProduct)->create([
        'status' => OrderItemStatus::Sent,
    ]);
    OrderItem::factory()->for($order)->for($otherProduct)->create([
        'status' => OrderItemStatus::Pending,
    ]);

    $this->actingAs($seller)
        ->get(route('seller.dashboard'))
        ->assertInertia(fn (Assert $page) => $page
            ->has('sellerHeader.notifications', 2)
            ->where('sellerHeader.notifications.0.type', 'order')
            ->where('sellerHeader.notifications.0.href', route('seller.orders.show', $pendingItem, absolute: false))
            ->where('sellerHeader.notifications.1.type', 'stock')
            ->where('sellerHeader.notifications.1.title', 'Pulpen Biru')
            ->where('sellerHeader.notifications.1.href', route('seller.inventory.index', ['q' => 'Pulpen Biru'], absolute: false))
            ->where('sellerHeader.supportEmail', config('mail.from.address')),
        );
});

test('seller header notifications are empty when no action is needed', function () {
    $seller = User::factory()->create(['role' => UserRole::Seller]);

    $this->actingAs($seller)
        ->get(route('seller.dashboard'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('sellerHeader.notifications', [])
            ->where('sellerHeader.supportEmail', config('mail.from.address')),
        );
});
```

- [ ] **Step 2: Run the tests and verify the missing prop failure**

Run:

```bash
php artisan test --compact tests/Feature/DashboardTest.php
```

Expected: the two new tests fail because `sellerHeader` is absent.

- [ ] **Step 3: Implement the minimal seller-only shared prop**

Add imports to `app/Http/Middleware/HandleInertiaRequests.php`:

```php
use App\Enums\OrderItemStatus;
use App\Enums\UserRole;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
```

Add this entry after `auth` in `share()`:

```php
'sellerHeader' => fn () => $this->sellerHeader($request),
```

Add this method to the middleware:

```php
/**
 * @return array{notifications: array<int, array{type: string, title: string, description: string, href: string}>, supportEmail: string|null}|null
 */
private function sellerHeader(Request $request): ?array
{
    /** @var User|null $seller */
    $seller = $request->user();

    if ($seller?->role !== UserRole::Seller) {
        return null;
    }

    $orders = OrderItem::query()
        ->with('product:id,name,seller_id')
        ->whereHas('product', fn ($query) => $query->where('seller_id', $seller->id))
        ->where('status', OrderItemStatus::Pending)
        ->latest()
        ->limit(3)
        ->get()
        ->map(fn (OrderItem $item) => [
            'type' => 'order',
            'title' => "Pesanan #{$item->order_id}",
            'description' => $item->product_name.' menunggu diproses',
            'href' => route('seller.orders.show', $item, absolute: false),
        ]);

    $stock = Product::query()
        ->where('seller_id', $seller->id)
        ->where('stock', '<=', Product::LOW_STOCK_THRESHOLD)
        ->orderBy('stock')
        ->limit(3)
        ->get(['id', 'name', 'stock'])
        ->map(fn (Product $product) => [
            'type' => 'stock',
            'title' => $product->name,
            'description' => $product->stock === 0 ? 'Stok habis' : "Stok tersisa {$product->stock}",
            'href' => route('seller.inventory.index', ['q' => $product->name], absolute: false),
        ]);

    return [
        'notifications' => $orders->concat($stock)->values()->all(),
        'supportEmail' => config('mail.from.address'),
    ];
}
```

- [ ] **Step 4: Run the focused feature tests**

Run:

```bash
php artisan test --compact tests/Feature/DashboardTest.php
```

Expected: all tests in `DashboardTest.php` pass.

- [ ] **Step 5: Commit the backend contract**

```bash
git add app/Http/Middleware/HandleInertiaRequests.php tests/Feature/DashboardTest.php
git commit -m "feat: share seller header notifications"
```

### Task 2: Functional Seller Header Controls

**Files:**
- Modify: `resources/js/types/global.d.ts`
- Modify: `resources/js/components/app-sidebar-header.tsx`

- [ ] **Step 1: Add the exact shared prop type**

Add this property after `auth` in `resources/js/types/global.d.ts`:

```ts
sellerHeader: {
    notifications: {
        type: 'order' | 'stock';
        title: string;
        description: string;
        href: string;
    }[];
    supportEmail: string | null;
} | null;
```

- [ ] **Step 2: Add imports and local search state to the header**

Change the top imports in `resources/js/components/app-sidebar-header.tsx` to include the existing tools required by the interactions:

```tsx
import { Link, router, usePage } from '@inertiajs/react';
import { Bell, Boxes, ChevronDown, CircleHelp, Package, Search, ShoppingCart } from 'lucide-react';
import { useState } from 'react';
```

Extend the dropdown import with `DropdownMenuItem` and `DropdownMenuLabel`, import the Dialog primitives, and import seller index routes:

```tsx
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { index as inventoryIndex } from '@/routes/seller/inventory';
import { index as ordersIndex } from '@/routes/seller/orders';
import { index as productsIndex } from '@/routes/seller/products';
```

Inside `AppSidebarHeader`, read the prop and add state/helpers:

```tsx
const { auth, sellerHeader } = usePage().props;
const [search, setSearch] = useState('');
const query = search.trim();
const searchTargets = [
    { label: 'Produk', icon: Package, href: productsIndex({ query: { q: query } }) },
    { label: 'Inventori', icon: Boxes, href: inventoryIndex({ query: { q: query } }) },
    { label: 'Pesanan', icon: ShoppingCart, href: ordersIndex({ query: { q: query } }) },
];
const submitSearch = (event: React.FormEvent) => {
    event.preventDefault();
    if (query) router.visit(productsIndex({ query: { q: query } }));
};
```

- [ ] **Step 3: Replace seller search with a native keyboard-friendly chooser**

Replace the current search wrapper with this form. The existing non-seller input remains unchanged in an `else` branch:

```tsx
{auth.user?.role === 'seller' ? (
    <form onSubmit={submitSearch} className="group relative hidden w-full max-w-md sm:block lg:ml-4">
        <Search className="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-slate-400" />
        <Input
            value={search}
            onChange={(event) => setSearch(event.target.value)}
            className="h-10 rounded-[8px] border-slate-200 bg-slate-50 pr-4 pl-10"
            placeholder="Cari pesanan, produk, stok..."
            type="search"
            aria-label="Pencarian seller"
        />
        {query && (
            <div className="absolute top-11 z-20 hidden w-full rounded-[8px] border border-slate-200 bg-white p-1 shadow-lg group-focus-within:block">
                {searchTargets.map(({ label, icon: Icon, href }) => (
                    <Link key={label} href={href} className="flex items-center gap-2 rounded-md px-3 py-2 text-sm text-slate-700 hover:bg-slate-100 focus:bg-slate-100 focus:outline-none">
                        <Icon className="size-4" /> Cari di {label}
                    </Link>
                ))}
            </div>
        )}
    </form>
) : (
    <div className="relative hidden w-full max-w-md sm:block lg:ml-4">
        <Search className="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-slate-400" />
        <Input className="h-10 rounded-[8px] border-slate-200 bg-slate-50 pr-4 pl-10" placeholder="Search orders, products, users..." type="search" />
    </div>
)}
```

- [ ] **Step 4: Replace the seller notification button with a real dropdown**

Render the existing tooltip button inside a `DropdownMenuTrigger`, show the red dot only when `sellerHeader.notifications.length > 0`, then use this content:

```tsx
<DropdownMenuContent align="end" className="w-80">
    <DropdownMenuLabel>Notifikasi</DropdownMenuLabel>
    {sellerHeader?.notifications.length ? (
        sellerHeader.notifications.map((notification) => (
            <DropdownMenuItem key={`${notification.type}-${notification.href}`} asChild>
                <Link href={notification.href} className="flex flex-col items-start gap-1 py-3">
                    <span className="font-medium">{notification.title}</span>
                    <span className="text-xs text-slate-500">{notification.description}</span>
                </Link>
            </DropdownMenuItem>
        ))
    ) : (
        <div className="px-3 py-6 text-center text-sm text-slate-500">Tidak ada tindakan yang diperlukan.</div>
    )}
</DropdownMenuContent>
```

- [ ] **Step 5: Wrap Help and Support controls in existing dialogs**

Use the current Help button as `DialogTrigger` and add this content:

```tsx
<DialogContent>
    <DialogHeader>
        <DialogTitle>Panduan Seller</DialogTitle>
        <DialogDescription>Gunakan Produk untuk mengelola katalog, Inventori untuk memperbarui stok, Pesanan untuk memproses transaksi, dan Dashboard untuk memantau ringkasan toko.</DialogDescription>
    </DialogHeader>
</DialogContent>
```

Use the current Support button as a second `DialogTrigger` and add this content:

```tsx
<DialogContent>
    <DialogHeader>
        <DialogTitle>Support</DialogTitle>
        <DialogDescription>
            {sellerHeader?.supportEmail ? (
                <>Hubungi admin sekolah melalui <a href={`mailto:${sellerHeader.supportEmail}`}>{sellerHeader.supportEmail}</a>.</>
            ) : (
                'Hubungi admin sekolah untuk mendapatkan bantuan.'
            )}
        </DialogDescription>
    </DialogHeader>
</DialogContent>
```

- [ ] **Step 6: Format and run frontend static checks**

Run:

```bash
bunx prettier --write resources/js/components/app-sidebar-header.tsx resources/js/types/global.d.ts
bun run types:check
bun run lint:check
```

Expected: all commands exit with code 0.

- [ ] **Step 7: Commit the functional header UI**

```bash
git add resources/js/components/app-sidebar-header.tsx resources/js/types/global.d.ts
git commit -m "feat: activate seller header actions"
```

### Task 3: Regression Verification

**Files:**
- Verify only; no planned file changes.

- [ ] **Step 1: Run focused seller backend tests**

```bash
php artisan test --compact tests/Feature/DashboardTest.php tests/Feature/SellerProductIndexTest.php tests/Feature/SellerInventoryTest.php tests/Feature/SellerOrderTest.php
```

Expected: all focused tests pass.

- [ ] **Step 2: Run project frontend checks and production build**

```bash
bun run format:check
bun run types:check
bun run lint:check
bun run build
```

Expected: all commands exit with code 0 and Vite produces the production bundle.

- [ ] **Step 3: Run the complete PHP test suite**

```bash
php artisan test --compact
```

Expected: all PHP tests pass.
