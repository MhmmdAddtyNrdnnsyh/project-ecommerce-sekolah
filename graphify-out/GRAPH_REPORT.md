# Graph Report - project-ecommerce-sekolah  (2026-07-22)

## Corpus Check
- 356 files · ~135,834 words
- Verdict: corpus is large enough that graph structure adds value.

## Summary
- 1965 nodes · 5052 edges · 197 communities (123 shown, 74 thin omitted)
- Extraction: 96% EXTRACTED · 4% INFERRED · 0% AMBIGUOUS · INFERRED: 213 edges (avg confidence: 0.79)
- Token cost: 0 input · 0 output

## Graph Freshness
- Built from commit: `2b3af76e`
- Run `git rev-parse HEAD` and compare to check if the graph is stale.
- Run `graphify update .` after code changes (no API cost).

## Community Hubs (Navigation)
- categories/index.tsx
- cn
- card.tsx
- button.tsx
- utils.ts
- FortifyServiceProvider.php
- Illuminate\Foundation\Http\FormRequest
- confirm.tsx
- Illuminate\Database\Eloquent\Relations\BelongsTo
- Product
- Illuminate\Database\Eloquent\Relations\HasMany
- OrderItem
- OrderItemStatus.php
- Illuminate\Http\Request
- Inertia\Response
- UpJurusan
- app-header.tsx
- Product.php
- devDependencies
- up-jurusan/index.tsx
- two-factor-setup-modal.tsx
- dropdown-menu.tsx
- User.php
- User
- index.ts
- app-sidebar.tsx
- sidebar.tsx
- ReportAggregationService
- Order
- dependencies
- OrderLivenessService
- CheckoutController.php
- components.json
- Illuminate\Http\RedirectResponse
- Controller
- Illuminate\Database\Eloquent\Factories\Factory
- compilerOptions
- SellerProductController
- OrderStatus
- Closure
- reports/index.tsx
- Illuminate\Database\Eloquent\Model
- UpJurusanDailyReport
- UpJurusanStockMovement
- composer.json
- scripts
- scripts
- auth-simple-layout.tsx
- optionalDependencies
- seller/orders/index.tsx
- AdminDashboardController
- require-dev
- ProductCatalogSeeder
- breadcrumbs.tsx
- cart/index.tsx
- chart.tsx
- HandleInertiaRequests
- StoreProductRequest
- setup
- CartController
- require
- ci:check
- Illuminate\Database\Seeder
- alert.tsx
- StockMovementSource.php
- config
- admin-jurusan/dashboard.tsx
- Illuminate\Console\Command
- TransactionCode
- TestCase.php
- use-clipboard.ts
- psr-4
- laravel
- test
- 2026_06_26_000002_add_up_jurusan_owner_to_products.php
- 2026_07_01_000001_create_up_jurusan_daily_report_transaction_snapshots.php
- use-mobile.tsx
- 2026_06_30_000002_add_completed_to_order_items_status.php
- post-create-project-cmd
- 2026_07_22_000001_expand_order_settlement_statuses.php
- seller/consignments/index.tsx
- 2026_07_01_000004_add_pre_order_fields_to_products.php
- eslint.config.js
- icon.tsx
- placeholder-pattern.tsx
- class-variance-authority
- clsx
- concurrently
- @fontsource-variable/inter
- globals
- receiving.tsx
- @inertiajs/vite
- input-otp
- lucide-react
- radix-ui
- @radix-ui/react-avatar
- @radix-ui/react-checkbox
- @radix-ui/react-dialog
- @radix-ui/react-dropdown-menu
- @radix-ui/react-select
- @radix-ui/react-toggle
- @radix-ui/react-toggle-group
- @radix-ui/react-tooltip
- react
- recharts
- shadcn
- sonner
- tailwind-merge
- tailwindcss
- @tailwindcss/vite
- @types/react
- typescript
- vite
- @vitejs/plugin-react
- @types/react-dom
- 9. Layout
- package.json
- 16. Responsive Design
- 3. Brand Identity
- 5. Typography
- 10.2 Input
- 13. Navigation and User Flow
- 18. Motion and Animation
- 1. Product Overview
- 20. Image Guidelines
- 11.1 Home Page
- 11.3 Product Detail Page
- 21. Content and Copywriting
- 2. Design Direction
- 6. Spacing System
- 8. Shadow System
- eslint-import-resolver-typescript
- eslint-plugin-import
- eslint-plugin-react
- @inertiajs/react
- laravel-vite-plugin
- @radix-ui/react-collapsible
- @radix-ui/react-label
- @radix-ui/react-navigation-menu
- @types/react-dom
- UpdateOrderItemStatusRequest
- TwoFactorAuthenticationRequest
- 2026_07_21_000001_expand_order_lifecycle_statuses.php
- fromStorage
- @base-ui/react
- class-variance-authority
- eslint-import-resolver-typescript
- eslint-plugin-import
- laravel-vite-plugin
- @radix-ui/react-collapsible
- @radix-ui/react-label
- @radix-ui/react-navigation-menu
- @radix-ui/react-separator
- @radix-ui/react-slot
- react-dom
- tw-animate-css
- prettier-plugin-tailwindcss
- @stylistic/eslint-plugin
- @types/node
- typescript-eslint

## God Nodes (most connected - your core abstractions)
1. `cn()` - 201 edges
2. `User` - 116 edges
3. `Product` - 85 edges
4. `Button()` - 62 edges
5. `Order` - 58 edges
6. `OrderItem` - 57 edges
7. `EduCart Design System` - 50 edges
8. `UpJurusanConsignment` - 49 edges
9. `UpJurusan` - 42 edges
10. `Category` - 38 edges

## Surprising Connections (you probably didn't know these)
- `settlementOrder()` --calls--> `OrderItem`  [INFERRED]
  tests/Feature/OrderSettlementServiceTest.php → app/Models/OrderItem.php
- `makePaymentItem()` --calls--> `OrderItem`  [INFERRED]
  tests/Unit/PaymentTransitionServiceTest.php → app/Models/OrderItem.php
- `settlementOrder()` --calls--> `Product`  [INFERRED]
  tests/Feature/OrderSettlementServiceTest.php → app/Models/Product.php
- `makeConsignment()` --calls--> `Product`  [INFERRED]
  tests/Unit/ConsignmentTransitionServiceTest.php → app/Models/Product.php
- `makePaymentItem()` --calls--> `Product`  [INFERRED]
  tests/Unit/PaymentTransitionServiceTest.php → app/Models/Product.php

## Import Cycles
- None detected.

## Communities (197 total, 74 thin omitted)

### Community 0 - "categories/index.tsx"
Cohesion: 0.06
Nodes (61): Badge(), badgeVariants, Table(), TableBody(), TableCell(), TableHead(), TableHeader(), TableRow() (+53 more)

### Community 1 - "cn"
Cohesion: 0.06
Nodes (49): PasswordInput(), Props, TextLink(), CardFooter(), ComboboxChip(), ComboboxChips(), ComboboxChipsInput(), ComboboxClear() (+41 more)

### Community 2 - "card.tsx"
Cohesion: 0.07
Nodes (34): Props, Card(), CardContent(), CardDescription(), CardHeader(), CardTitle(), Props, UpJurusan (+26 more)

### Community 3 - "button.tsx"
Cohesion: 0.08
Nodes (22): Heading(), InputError(), Props, ManageTwoFactor(), Props, PasskeyRegistration(), Props, Props (+14 more)

### Community 4 - "utils.ts"
Cohesion: 0.06
Nodes (51): CardAction(), Select(), SelectContent(), SelectGroup(), SelectItem(), SelectLabel(), SelectTrigger(), SelectValue() (+43 more)

### Community 5 - "FortifyServiceProvider.php"
Cohesion: 0.11
Nodes (9): LoginResponse, PasskeyLoginResponse, PasswordConfirmedResponse, TwoFactorLoginResponse, AuthRedirect, Laravel\Fortify\Contracts\LoginResponse, Laravel\Fortify\Contracts\PasswordConfirmedResponse, Laravel\Fortify\Contracts\TwoFactorLoginResponse (+1 more)

### Community 6 - "Illuminate\Foundation\Http\FormRequest"
Cohesion: 0.27
Nodes (3): ResetUserPassword, ProfileDeleteRequest, Laravel\Fortify\Contracts\ResetsUserPasswords

### Community 7 - "confirm.tsx"
Cohesion: 0.12
Nodes (27): AlertDialog(), AlertDialogAction(), AlertDialogCancel(), AlertDialogContent(), AlertDialogDescription(), AlertDialogFooter(), AlertDialogHeader(), AlertDialogOverlay() (+19 more)

### Community 8 - "Illuminate\Database\Eloquent\Relations\BelongsTo"
Cohesion: 0.06
Nodes (5): DomainEvent, UpJurusanDailyReportTransaction, UpJurusanDailyReportTransactionItem, UpJurusanPayout, Illuminate\Database\Eloquent\Relations\BelongsTo

### Community 9 - "Product"
Cohesion: 0.10
Nodes (7): AdminProductController, AdminProductModerationController, BuyerProductDetailController, SellerProductController, Product, PreOrderRules, ProductStatus

### Community 11 - "OrderItem"
Cohesion: 0.12
Nodes (8): SellerOrderController, OrderItem, OrderItemFulfillment, OrderPaymentSync, OrderStatusSync, PaymentTransitionService, OrderItemStatus, PaymentStatus

### Community 12 - "OrderItemStatus.php"
Cohesion: 0.10
Nodes (6): next(), nextForPreOrder(), self, values(), up(), up()

### Community 13 - "Illuminate\Http\Request"
Cohesion: 0.18
Nodes (3): AdminJurusanConsignmentController, PicketUpJurusanConsignmentController, Illuminate\Http\Request

### Community 14 - "Inertia\Response"
Cohesion: 0.06
Nodes (32): 10.10 Skeleton, 10.3 Search Bar, 10.5 Badge, 10.6 Navbar, 10.7 Breadcrumb, 10.8 Modal dan Dialog, 10.9 Toast, 10. Core Components (+24 more)

### Community 15 - "UpJurusan"
Cohesion: 0.12
Nodes (3): UpJurusanPolicy, UserPolicy, ActorLifecycle

### Community 16 - "app-header.tsx"
Cohesion: 0.10
Nodes (22): AppHeader(), BuyerNavLink(), getBuyerNavItems(), AppSidebarHeader(), getSearchConfig(), Avatar(), AvatarBadge(), AvatarFallback() (+14 more)

### Community 18 - "devDependencies"
Cohesion: 0.12
Nodes (17): babel-plugin-react-compiler, eslint, eslint-config-prettier, @eslint/js, eslint-plugin-react, eslint-plugin-react-hooks, @laravel/vite-plugin-wayfinder, devDependencies (+9 more)

### Community 19 - "up-jurusan/index.tsx"
Cohesion: 0.08
Nodes (36): HeaderNotification, notificationMenuStyle, roleLabels, Props, Button(), buttonVariants, Dialog(), DialogClose() (+28 more)

### Community 20 - "two-factor-setup-modal.tsx"
Cohesion: 0.14
Nodes (6): UpJurusanConsignment, ConsignmentTransitionService, DomainEventService, up(), makeConsignment(), UpJurusanConsignmentStatus

### Community 21 - "dropdown-menu.tsx"
Cohesion: 0.11
Nodes (20): NavUser(), DropdownMenu(), DropdownMenuCheckboxItem(), DropdownMenuContent(), DropdownMenuGroup(), DropdownMenuItem(), DropdownMenuLabel(), DropdownMenuRadioItem() (+12 more)

### Community 23 - "User"
Cohesion: 0.12
Nodes (8): SellerDashboardController, User, OrderItemCancellation, Illuminate\Foundation\Auth\User, Illuminate\Notifications\Notifiable, Laravel\Fortify\Contracts\PasskeyUser, Laravel\Fortify\PasskeyAuthenticatable, Laravel\Fortify\TwoFactorAuthenticatable

### Community 24 - "index.ts"
Cohesion: 0.14
Nodes (14): AppContent(), Props, AppShell(), Props, SidebarInset(), SidebarProvider(), Toaster(), AppHeaderLayout() (+6 more)

### Community 25 - "app-sidebar.tsx"
Cohesion: 0.13
Nodes (22): AppSidebar(), getMainNavItems(), lightTooltip, NavFooter(), NavMain(), SidebarContent(), SidebarFooter(), SidebarGroup() (+14 more)

### Community 26 - "sidebar.tsx"
Cohesion: 0.16
Nodes (6): BuyerOrderController, Order, OrderPolicy, OrderSettlementService, settlementOrder(), makePaymentItem()

### Community 27 - "ReportAggregationService"
Cohesion: 0.13
Nodes (3): Collection, ReportAggregationService, Illuminate\Support\Collection

### Community 28 - "Order"
Cohesion: 0.33
Nodes (3): AdminJurusanDashboardController, AdminJurusanReportController, UpJurusanDailyReport

### Community 29 - "dependencies"
Cohesion: 0.18
Nodes (11): clsx, @inertiajs/vite, @laravel/passkeys, dependencies, clsx, @inertiajs/vite, @laravel/passkeys, react (+3 more)

### Community 30 - "OrderLivenessService"
Cohesion: 0.17
Nodes (3): OrderLivenessService, Carbon\CarbonInterface, Illuminate\Database\Eloquent\Builder

### Community 31 - "CheckoutController.php"
Cohesion: 0.17
Nodes (4): PasswordResetResponse, RegisterResponse, Laravel\Fortify\Contracts\PasswordResetResponse, Laravel\Fortify\Contracts\RegisterResponse

### Community 32 - "components.json"
Cohesion: 0.09
Nodes (21): aliases, components, hooks, lib, ui, utils, iconLibrary, menuAccent (+13 more)

### Community 33 - "Illuminate\Http\RedirectResponse"
Cohesion: 0.17
Nodes (3): AdminUserController, SellerConsignmentController, Inertia\Response

### Community 34 - "Controller"
Cohesion: 0.22
Nodes (9): Auth, Passkey, TwoFactorSecretKey, TwoFactorSetupData, User, InertiaConfig, @inertiajs/core, InputHTMLAttributes (+1 more)

### Community 35 - "Illuminate\Database\Eloquent\Factories\Factory"
Cohesion: 0.09
Nodes (13): SellerApplication, CategoryFactory, OrderFactory, OrderItemFactory, static, ProductFactory, SellerApplicationFactory, UpJurusanConsignmentFactory (+5 more)

### Community 36 - "compilerOptions"
Cohesion: 0.10
Nodes (19): resources/js/**/*.d.ts, resources/js/**/*.ts, resources/js/**/*.tsx, compilerOptions, allowJs, baseUrl, esModuleInterop, forceConsistentCasingInFileNames (+11 more)

### Community 37 - "SellerProductController"
Cohesion: 0.10
Nodes (23): Separator(), Sidebar(), SidebarContext, SidebarContextProps, SidebarGroupAction(), SidebarInput(), SidebarMenuAction(), SidebarMenuBadge() (+15 more)

### Community 38 - "OrderStatus"
Cohesion: 0.15
Nodes (8): Collection, Attribute, up(), down(), expandEnumColumn(), up(), Illuminate\Database\Eloquent\Casts\Attribute, OrderStatus

### Community 39 - "Closure"
Cohesion: 0.22
Nodes (7): EnsureUserIsAdmin, EnsureUserIsAdminJurusan, EnsureUserIsBuyer, EnsureUserIsPicketOfficer, EnsureUserIsSeller, Closure, Symfony\Component\HttpFoundation\Response

### Community 40 - "reports/index.tsx"
Cohesion: 0.13
Nodes (11): DailyReport, DateTimeProps, EmptyStateProps, formatRupiah(), Props, ReportHeaderProps, ReportsSection(), ReportsSectionProps (+3 more)

### Community 41 - "Illuminate\Database\Eloquent\Model"
Cohesion: 0.27
Nodes (4): CartItem, NotificationDismissal, Illuminate\Database\Eloquent\Model, Illuminate\Foundation\Configuration\Middleware

### Community 43 - "UpJurusanStockMovement"
Cohesion: 0.09
Nodes (3): UpJurusanPosSale, UpJurusanStockMovement, MoneyCalculationService

### Community 44 - "composer.json"
Cohesion: 0.14
Nodes (13): autoload-dev, psr-4, description, keywords, license, minimum-stability, name, prefer-stable (+5 more)

### Community 45 - "scripts"
Cohesion: 0.11
Nodes (18): scripts, lint, lint:check, post-autoload-dump, post-create-project-cmd, post-update-cmd, pre-package-uninstall, types:check (+10 more)

### Community 46 - "scripts"
Cohesion: 0.22
Nodes (9): scripts, build, build:ssr, dev, format, format:check, lint, lint:check (+1 more)

### Community 47 - "auth-simple-layout.tsx"
Cohesion: 0.20
Nodes (6): AppLogoIcon(), Props, AuthSimpleLayout(), AuthTheme, lightAuthTheme, AuthLayoutProps

### Community 48 - "optionalDependencies"
Cohesion: 0.15
Nodes (13): lightningcss-linux-x64-gnu, lightningcss-win32-x64-msvc, optionalDependencies, lightningcss-linux-x64-gnu, lightningcss-win32-x64-msvc, @rollup/rollup-linux-x64-gnu, @rollup/rollup-win32-x64-msvc, @tailwindcss/oxide-linux-x64-gnu (+5 more)

### Community 51 - "require-dev"
Cohesion: 0.18
Nodes (11): require-dev, fakerphp/faker, larastan/larastan, laravel/pail, laravel/pao, laravel/pint, laravel/sail, mockery/mockery (+3 more)

### Community 53 - "breadcrumbs.tsx"
Cohesion: 0.33
Nodes (7): Breadcrumb(), BreadcrumbEllipsis(), BreadcrumbItem(), BreadcrumbLink(), BreadcrumbList(), BreadcrumbPage(), BreadcrumbSeparator()

### Community 55 - "chart.tsx"
Cohesion: 0.08
Nodes (27): ChartConfig, ChartContainer(), ChartContext, ChartContextProps, ChartLegendContent(), ChartTooltipContent(), getPayloadConfigFromPayload(), INITIAL_DIMENSION (+19 more)

### Community 57 - "StoreProductRequest"
Cohesion: 0.60
Nodes (3): DatabaseSeeder, Illuminate\Database\Console\Seeds\WithoutModelEvents, Illuminate\Database\Seeder

### Community 58 - "setup"
Cohesion: 0.16
Nodes (5): AdminJurusanUpJurusanController, NotificationDismissalController, SellerApplicationController, UpJurusan, Illuminate\Http\RedirectResponse

### Community 60 - "require"
Cohesion: 0.25
Nodes (8): require, inertiajs/inertia-laravel, laravel/chisel, laravel/fortify, laravel/framework, laravel/tinker, laravel/wayfinder, php

### Community 61 - "ci:check"
Cohesion: 0.25
Nodes (8): ci:check, dev, bun run format:check, bun run lint:check, bun run types:check, bunx concurrently -c \"#93c5fd,#c4b5fd,#fb7185,#fdba74\" \"php artisan serve --host=localhost\" \"php artisan queue:listen --tries=1 --timeout=0\" \"php artisan pail --timeout=0\" \"bun run dev\" --names=server,queue,logs,vite --kill-others, Composer\\Config::disableProcessTimeout, @test

### Community 63 - "alert.tsx"
Cohesion: 0.39
Nodes (6): AlertError(), Alert(), AlertAction(), AlertDescription(), AlertTitle(), alertVariants

### Community 64 - "StockMovementSource.php"
Cohesion: 0.12
Nodes (7): AdminSellerApplicationController, BuyerCatalogController, Controller, SellerInventoryController, ProfileController, SecurityController, Illuminate\Foundation\Auth\Access\AuthorizesRequests

### Community 65 - "config"
Cohesion: 0.29
Nodes (7): pestphp/pest-plugin, php-http/discovery, config, allow-plugins, optimize-autoloader, preferred-install, sort-packages

### Community 66 - "admin-jurusan/dashboard.tsx"
Cohesion: 0.22
Nodes (3): Position, SchoolClass, TestingUserSeeder

### Community 67 - "Illuminate\Console\Command"
Cohesion: 0.40
Nodes (3): DetectStuckOrdersCommand, ExpireUnpaidOrdersCommand, Illuminate\Console\Command

### Community 68 - "TransactionCode"
Cohesion: 0.22
Nodes (8): Demo Data, Deployment Checklist, EduCart, Fitur Utama, Production Notes, Quality Checks, Role Pengguna, Setup Local

### Community 70 - "use-clipboard.ts"
Cohesion: 0.38
Nodes (6): AdminJurusanDashboard(), Dashboard, formatRupiah(), formatTime(), Props, statusStyles

### Community 71 - "psr-4"
Cohesion: 0.40
Nodes (5): autoload, psr-4, App\\, Database\\Factories\\, Database\\Seeders\\

### Community 72 - "laravel"
Cohesion: 0.40
Nodes (5): extra, laravel, post-create-project, dont-discover, installer

### Community 73 - "test"
Cohesion: 0.40
Nodes (5): test, @lint:check, @php artisan config:clear --ansi, @php artisan test, @types:check

### Community 74 - "2026_06_26_000002_add_up_jurusan_owner_to_products.php"
Cohesion: 0.60
Nodes (4): addOwnerConstraint(), down(), dropOwnerConstraint(), up()

### Community 75 - "2026_07_01_000001_create_up_jurusan_daily_report_transaction_snapshots.php"
Cohesion: 0.60
Nodes (3): backfillExistingReports(), movementProductName(), up()

### Community 76 - "use-mobile.tsx"
Cohesion: 0.70
Nodes (4): getServerSnapshot(), isSmallerThanBreakpoint(), mediaQueryListener(), useIsMobile()

### Community 77 - "2026_06_30_000002_add_completed_to_order_items_status.php"
Cohesion: 0.25
Nodes (6): CartItem, DailyReportItem, formatRupiah(), PicketUpJurusanConsignments(), PosProduct, Props

### Community 78 - "post-create-project-cmd"
Cohesion: 0.28
Nodes (3): RejectProductRequest, PasswordUpdateRequest, Illuminate\Foundation\Http\FormRequest

### Community 94 - "2026_07_01_000004_add_pre_order_fields_to_products.php"
Cohesion: 0.22
Nodes (9): post-root-package-install, setup, bun install, bun run build, composer install, @php artisan key:generate, @php artisan migrate --force --seed, @php artisan storage:link (+1 more)

### Community 102 - "clsx"
Cohesion: 0.33
Nodes (6): 10.1 Button, Button states, Destructive button, Outline button, Primary button, Secondary button

### Community 106 - "receiving.tsx"
Cohesion: 0.31
Nodes (6): CreateNewUser, emailRules(), nameRules(), profileRules(), ProfileUpdateRequest, Laravel\Fortify\Contracts\CreatesNewUsers

### Community 119 - "react"
Cohesion: 0.40
Nodes (5): 10.4 Product Card, Hover, Price, Product image, Product name

### Community 122 - "sonner"
Cohesion: 0.40
Nodes (5): 4. Color System, Aturan penggunaan warna, Neutral color, Primary color, Semantic color

### Community 148 - "@types/react-dom"
Cohesion: 0.38
Nodes (6): CatalogProduct, CatalogShow(), CatalogShowProps, formatRupiah(), imageSource(), PageProps

### Community 149 - "9. Layout"
Cohesion: 0.40
Nodes (5): 9. Layout, Breakpoints, Container, Grid produk, Header layout

### Community 151 - "16. Responsive Design"
Cohesion: 0.50
Nodes (4): 16. Responsive Design, Desktop rules, Mobile first, Mobile rules

### Community 152 - "3. Brand Identity"
Cohesion: 0.50
Nodes (4): 3. Brand Identity, Brand personality, Logo, Nama brand

### Community 153 - "5. Typography"
Cohesion: 0.50
Nodes (4): 5. Typography, Aturan tipografi, Font utama, Typography scale

### Community 154 - "10.2 Input"
Cohesion: 0.67
Nodes (3): 10.2 Input, State, Style

### Community 155 - "13. Navigation and User Flow"
Cohesion: 0.67
Nodes (3): 13. Navigation and User Flow, Flow admin produk, Flow pembelian utama

### Community 156 - "18. Motion and Animation"
Cohesion: 0.67
Nodes (3): 18. Motion and Animation, Durasi, Easing

### Community 157 - "1. Product Overview"
Cohesion: 0.67
Nodes (3): 1. Product Overview, Target pengguna, Tujuan desain

### Community 158 - "20. Image Guidelines"
Cohesion: 0.67
Nodes (3): 20. Image Guidelines, Banner, Product image

### Community 166 - "eslint-plugin-import"
Cohesion: 0.33
Nodes (5): TwoFactorSetupStep(), CopiedValue, CopyFn, useClipboard(), UseClipboardReturn

### Community 172 - "@radix-ui/react-navigation-menu"
Cohesion: 0.40
Nodes (4): packageManager, private, $schema, type

### Community 178 - "2026_07_21_000001_expand_order_lifecycle_statuses.php"
Cohesion: 0.83
Nodes (3): down(), expandEnumColumn(), up()

### Community 179 - "fromStorage"
Cohesion: 0.67
Nodes (3): fromStorage(), self, values()

## Knowledge Gaps
- **431 isolated node(s):** `$schema`, `style`, `rsc`, `tsx`, `config` (+426 more)
  These have ≤1 connection - possible missing edges or undocumented components.
- **74 thin communities (<3 nodes) omitted from report** — run `graphify query` to explore isolated nodes.

## Suggested Questions
_Questions this graph is uniquely positioned to answer:_

- **Why does `cn()` connect `cn` to `categories/index.tsx`, `card.tsx`, `button.tsx`, `utils.ts`, `SellerProductController`, `use-clipboard.ts`, `confirm.tsx`, `auth-simple-layout.tsx`, `app-header.tsx`, `up-jurusan/index.tsx`, `breadcrumbs.tsx`, `dropdown-menu.tsx`, `chart.tsx`, `index.ts`, `app-sidebar.tsx`, `alert.tsx`?**
  _High betweenness centrality (0.041) - this node is a cross-community bridge._
- **Why does `User` connect `User` to `FortifyServiceProvider.php`, `Illuminate\Foundation\Http\FormRequest`, `Illuminate\Database\Eloquent\Relations\BelongsTo`, `Product`, `Illuminate\Database\Eloquent\Relations\HasMany`, `OrderItem`, `OrderItemStatus.php`, `Illuminate\Http\Request`, `UpJurusan`, `Product.php`, `two-factor-setup-modal.tsx`, `User.php`, `sidebar.tsx`, `OrderLivenessService`, `Illuminate\Http\RedirectResponse`, `Illuminate\Database\Eloquent\Factories\Factory`, `Illuminate\Database\Eloquent\Model`, `UpJurusanStockMovement`, `AdminDashboardController`, `ProductCatalogSeeder`, `HandleInertiaRequests`, `setup`, `admin-jurusan/dashboard.tsx`, `Illuminate\Console\Command`, `receiving.tsx`, `@inertiajs/vite`?**
  _High betweenness centrality (0.036) - this node is a cross-community bridge._
- **Why does `Button()` connect `up-jurusan/index.tsx` to `categories/index.tsx`, `cn`, `card.tsx`, `button.tsx`, `utils.ts`, `SellerProductController`, `use-clipboard.ts`, `confirm.tsx`, `reports/index.tsx`, `2026_06_30_000002_add_completed_to_order_items_status.php`, `app-header.tsx`, `@types/react-dom`, `chart.tsx`, `app-sidebar.tsx`?**
  _High betweenness centrality (0.017) - this node is a cross-community bridge._
- **Are the 20 inferred relationships involving `User` (e.g. with `.handle()` and `.activities()`) actually correct?**
  _`User` has 20 INFERRED edges - model-reasoned connections that need verification._
- **Are the 15 inferred relationships involving `Product` (e.g. with `.adminQueue()` and `.stats()`) actually correct?**
  _`Product` has 15 INFERRED edges - model-reasoned connections that need verification._
- **What connects `$schema`, `style`, `rsc` to the rest of the system?**
  _431 weakly-connected nodes found - possible documentation gaps or missing edges._
- **Should `categories/index.tsx` be split into smaller, more focused modules?**
  _Cohesion score 0.05649122807017544 - nodes in this community are weakly interconnected._