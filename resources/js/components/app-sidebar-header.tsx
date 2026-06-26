import { Link, router, usePage } from '@inertiajs/react';
import {
    Bell,
    Boxes,
    ChevronDown,
    CircleHelp,
    Package,
    Search,
    ShoppingCart,
    Tags,
    Users,
} from 'lucide-react';
import { useState } from 'react';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
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
import { Input } from '@/components/ui/input';
import { SidebarTrigger } from '@/components/ui/sidebar';
import { UserMenuContent } from '@/components/user-menu-content';
import { useInitials } from '@/hooks/use-initials';
import { login, register } from '@/routes';
import { index as cartIndex } from '@/routes/cart';
import { index as catalogIndex } from '@/routes/catalog';
import { index as buyerOrdersIndex } from '@/routes/orders';
import { index as inventoryIndex } from '@/routes/seller/inventory';
import { index as sellerOrdersIndex } from '@/routes/seller/orders';
import { index as productsIndex } from '@/routes/seller/products';
import type { BreadcrumbItem as BreadcrumbItemType } from '@/types';

const roleLabels: Record<string, string> = {
    admin: 'Super Admin',
    admin_jurusan: 'Admin Jurusan',
    seller: 'Seller',
    buyer: 'Buyer',
    picket_officer: 'Picket Officer',
};

const userMenuClassName =
    'w-56 rounded-[8px] bg-white text-slate-900 ring-slate-200 [&_[data-slot=dropdown-menu-item]]:text-slate-700 [&_[data-slot=dropdown-menu-item]]:focus:bg-slate-100 [&_[data-slot=dropdown-menu-item]]:focus:text-slate-900 [&_[data-slot=dropdown-menu-label]]:text-slate-500 [&_[data-slot=dropdown-menu-separator]]:bg-slate-200';
const notificationMenuClassName = 'w-80 overflow-y-auto';
const notificationMenuStyle = {
    maxHeight:
        'min(24rem, var(--radix-dropdown-menu-content-available-height))',
};
export function AppSidebarHeader({
    breadcrumbs = [],
}: {
    breadcrumbs?: BreadcrumbItemType[];
}) {
    const { adminHeader, auth, buyerHeader, sellerHeader } = usePage().props;
    const [search, setSearch] = useState('');
    const getInitials = useInitials();
    const currentBreadcrumb = breadcrumbs[breadcrumbs.length - 1];
    const title =
        currentBreadcrumb?.title === 'Dashboard'
            ? 'Dashboard Overview'
            : (currentBreadcrumb?.title ?? 'Dashboard Overview');
    const userRole = auth.user?.role ? roleLabels[auth.user.role] : undefined;
    const query = search.trim();
    const role = auth.user?.role;
    const isAdmin = auth.user?.role === 'admin';
    const searchTargets =
        role === 'buyer' || !role
            ? [
                  {
                      label: 'Katalog',
                      icon: Package,
                      href: catalogIndex({ query: { search: query } }),
                  },
              ]
            : role === 'seller'
              ? [
                    {
                        label: 'Produk',
                        icon: Package,
                        href: productsIndex({ query: { q: query } }),
                    },
                    {
                        label: 'Inventori',
                        icon: Boxes,
                        href: inventoryIndex({ query: { q: query } }),
                    },
                    {
                        label: 'Pesanan',
                        icon: ShoppingCart,
                        href: sellerOrdersIndex({ query: { q: query } }),
                    },
                ]
              : [
                    {
                        label: 'Produk',
                        icon: Package,
                        href: `/admin/products?q=${encodeURIComponent(query)}`,
                    },
                    {
                        label: 'Orders',
                        icon: ShoppingCart,
                        href: `/admin/orders?q=${encodeURIComponent(query)}`,
                    },
                    {
                        label: 'Users',
                        icon: Users,
                        href: `/admin/users?q=${encodeURIComponent(query)}`,
                    },
                    {
                        label: 'Categories',
                        icon: Tags,
                        href: `/admin/categories?q=${encodeURIComponent(query)}`,
                    },
                ];

    const submitSearch = (event: React.FormEvent) => {
        event.preventDefault();

        if (query) {
            router.visit(searchTargets[0].href);
        }
    };

    return (
        <header className="sticky top-0 z-10 flex h-16 shrink-0 items-center justify-between gap-4 border-b border-slate-100 bg-white px-4 md:px-6">
            <div className="flex min-w-0 flex-1 items-center gap-4">
                <SidebarTrigger className="-ml-1 text-slate-500 hover:bg-slate-100 hover:text-slate-900" />
                <h1 className="hidden shrink-0 text-2xl font-semibold text-slate-800 lg:block">
                    {title}
                </h1>
                <form
                    onSubmit={submitSearch}
                    className="group relative hidden w-full max-w-md sm:block lg:ml-4"
                >
                    <Search className="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-slate-400" />
                    <Input
                        value={search}
                        onChange={(event) => setSearch(event.target.value)}
                        className="h-10 rounded-[8px] border-slate-200 bg-slate-50 pr-4 pl-10 text-sm text-slate-700 focus-visible:border-blue-400 focus-visible:bg-white focus-visible:ring-blue-100"
                        placeholder={
                            auth.user?.role === 'buyer'
                                ? 'Cari produk di katalog...'
                                : isAdmin
                                  ? 'Cari produk, order, user, kategori...'
                                  : 'Cari pesanan, produk, stok...'
                        }
                        type="search"
                        aria-label={
                            auth.user?.role === 'buyer'
                                ? 'Pencarian katalog'
                                : isAdmin
                                  ? 'Pencarian admin'
                                  : 'Pencarian seller'
                        }
                    />
                    {query && (
                        <div className="absolute top-11 z-20 hidden w-full rounded-[8px] border border-slate-200 bg-white p-1 shadow-lg group-focus-within:block">
                            {searchTargets.map(
                                ({ label, icon: Icon, href }) => (
                                    <Link
                                        key={label}
                                        href={href}
                                        className="flex items-center gap-2 rounded-md px-3 py-2 text-sm text-slate-700 hover:bg-slate-100 focus:bg-slate-100 focus:outline-none"
                                    >
                                        <Icon className="size-4" />
                                        Cari di {label}
                                    </Link>
                                ),
                            )}
                        </div>
                    )}
                </form>
            </div>

            <div className="flex shrink-0 items-center gap-2 sm:gap-3">
                {role === 'buyer' ? (
                    <>
                        <Button
                            asChild
                            type="button"
                            variant="ghost"
                            size="icon"
                            className="relative rounded-full text-slate-500 hover:bg-slate-100 hover:text-blue-600"
                        >
                            <Link href={cartIndex()} aria-label="Cart">
                                <ShoppingCart className="size-5" />
                                {Boolean(buyerHeader?.cartItemsCount) && (
                                    <span className="absolute -top-0.5 -right-0.5 min-w-5 rounded-full bg-blue-600 px-1.5 text-center text-[11px] leading-5 font-semibold text-white ring-2 ring-white">
                                        {buyerHeader?.cartItemsCount}
                                    </span>
                                )}
                            </Link>
                        </Button>

                        <Button
                            asChild
                            variant="ghost"
                            className="hidden rounded-[8px] px-2 text-sm font-medium text-slate-500 hover:bg-slate-100 hover:text-blue-600 md:inline-flex"
                        >
                            <Link href={buyerOrdersIndex()}>Orders</Link>
                        </Button>
                    </>
                ) : role === 'seller' ? (
                    <>
                        <DropdownMenu>
                            <DropdownMenuTrigger asChild>
                                <Button
                                    type="button"
                                    variant="ghost"
                                    size="icon"
                                    className="relative rounded-full text-slate-500 hover:bg-slate-100 hover:text-blue-600 aria-expanded:bg-slate-100 aria-expanded:text-blue-600"
                                    aria-label="Notifikasi"
                                >
                                    <Bell className="size-5" />
                                    {Boolean(
                                        sellerHeader?.notifications.length,
                                    ) && (
                                        <span className="absolute top-2 right-2 size-2 rounded-full bg-red-500 ring-2 ring-white" />
                                    )}
                                </Button>
                            </DropdownMenuTrigger>
                            <DropdownMenuContent
                                align="end"
                                className={notificationMenuClassName}
                                style={notificationMenuStyle}
                            >
                                <DropdownMenuLabel>
                                    Notifikasi
                                </DropdownMenuLabel>
                                {sellerHeader?.notifications.length ? (
                                    sellerHeader.notifications.map(
                                        (notification) => (
                                            <DropdownMenuItem
                                                key={`${notification.type}-${notification.href}`}
                                                asChild
                                            >
                                                <Link
                                                    href={notification.href}
                                                    className="flex flex-col items-start gap-1 py-3"
                                                >
                                                    <span className="font-medium">
                                                        {notification.title}
                                                    </span>
                                                    <span className="text-xs text-slate-500">
                                                        {
                                                            notification.description
                                                        }
                                                    </span>
                                                </Link>
                                            </DropdownMenuItem>
                                        ),
                                    )
                                ) : (
                                    <div className="px-3 py-6 text-center text-sm text-slate-500">
                                        Tidak ada tindakan yang diperlukan.
                                    </div>
                                )}
                            </DropdownMenuContent>
                        </DropdownMenu>

                        <Dialog>
                            <DialogTrigger asChild>
                                <Button
                                    type="button"
                                    variant="ghost"
                                    size="icon"
                                    className="hidden rounded-full text-slate-500 hover:bg-slate-100 hover:text-blue-600 aria-expanded:bg-slate-100 aria-expanded:text-blue-600 sm:inline-flex"
                                    aria-label="Bantuan"
                                >
                                    <CircleHelp className="size-5" />
                                </Button>
                            </DialogTrigger>
                            <DialogContent>
                                <DialogHeader>
                                    <DialogTitle>Panduan Seller</DialogTitle>
                                    <DialogDescription>
                                        Gunakan Produk untuk mengelola katalog,
                                        Inventori untuk memperbarui stok,
                                        Pesanan untuk memproses transaksi, dan
                                        Dashboard untuk memantau ringkasan toko.
                                    </DialogDescription>
                                </DialogHeader>
                            </DialogContent>
                        </Dialog>

                        <Dialog>
                            <DialogTrigger asChild>
                                <Button
                                    type="button"
                                    variant="ghost"
                                    className="hidden rounded-[8px] px-2 text-sm font-medium text-slate-500 hover:bg-slate-100 hover:text-blue-600 aria-expanded:bg-slate-100 md:inline-flex"
                                >
                                    Support
                                </Button>
                            </DialogTrigger>
                            <DialogContent>
                                <DialogHeader>
                                    <DialogTitle>Support</DialogTitle>
                                    <DialogDescription>
                                        {sellerHeader?.supportEmail ? (
                                            <>
                                                Hubungi admin sekolah melalui{' '}
                                                <a
                                                    href={`mailto:${sellerHeader.supportEmail}`}
                                                >
                                                    {sellerHeader.supportEmail}
                                                </a>
                                                .
                                            </>
                                        ) : (
                                            'Hubungi admin sekolah untuk mendapatkan bantuan.'
                                        )}
                                    </DialogDescription>
                                </DialogHeader>
                            </DialogContent>
                        </Dialog>
                    </>
                ) : role === 'admin' ? (
                    <>
                        <DropdownMenu>
                            <DropdownMenuTrigger asChild>
                                <Button
                                    type="button"
                                    variant="ghost"
                                    size="icon"
                                    className="relative rounded-full text-slate-500 hover:bg-slate-100 hover:text-blue-600 aria-expanded:bg-slate-100 aria-expanded:text-blue-600"
                                    aria-label="Notifikasi admin"
                                >
                                    <Bell className="size-5" />
                                    {Boolean(
                                        adminHeader?.notifications.length,
                                    ) && (
                                        <span className="absolute top-2 right-2 size-2 rounded-full bg-red-500 ring-2 ring-white" />
                                    )}
                                </Button>
                            </DropdownMenuTrigger>
                            <DropdownMenuContent
                                align="end"
                                className={notificationMenuClassName}
                                style={notificationMenuStyle}
                            >
                                <DropdownMenuLabel>
                                    Notifikasi Admin
                                </DropdownMenuLabel>
                                {adminHeader?.notifications.length ? (
                                    adminHeader.notifications.map(
                                        (notification) => (
                                            <DropdownMenuItem
                                                key={`${notification.type}-${notification.title}`}
                                                asChild
                                            >
                                                <Link
                                                    href={notification.href}
                                                    className="flex flex-col items-start gap-1 py-3"
                                                >
                                                    <span className="font-medium">
                                                        {notification.title}
                                                    </span>
                                                    <span className="text-xs text-slate-500">
                                                        {
                                                            notification.description
                                                        }
                                                    </span>
                                                </Link>
                                            </DropdownMenuItem>
                                        ),
                                    )
                                ) : (
                                    <div className="px-3 py-6 text-center text-sm text-slate-500">
                                        Tidak ada tindakan admin.
                                    </div>
                                )}
                            </DropdownMenuContent>
                        </DropdownMenu>

                        <Dialog>
                            <DialogTrigger asChild>
                                <Button
                                    type="button"
                                    variant="ghost"
                                    size="icon"
                                    className="hidden rounded-full text-slate-500 hover:bg-slate-100 hover:text-blue-600 aria-expanded:bg-slate-100 aria-expanded:text-blue-600 sm:inline-flex"
                                    aria-label="Bantuan admin"
                                >
                                    <CircleHelp className="size-5" />
                                </Button>
                            </DialogTrigger>
                            <DialogContent>
                                <DialogHeader>
                                    <DialogTitle>Panduan Admin</DialogTitle>
                                    <DialogDescription>
                                        Gunakan Moderasi Produk untuk approve
                                        atau reject produk pending, Products
                                        untuk memantau semua produk, Orders
                                        untuk transaksi, Users untuk akun, dan
                                        Categories untuk data kategori.
                                    </DialogDescription>
                                </DialogHeader>
                            </DialogContent>
                        </Dialog>

                        <Dialog>
                            <DialogTrigger asChild>
                                <Button
                                    type="button"
                                    variant="ghost"
                                    className="hidden rounded-[8px] px-2 text-sm font-medium text-slate-500 hover:bg-slate-100 hover:text-blue-600 aria-expanded:bg-slate-100 md:inline-flex"
                                >
                                    Support
                                </Button>
                            </DialogTrigger>
                            <DialogContent>
                                <DialogHeader>
                                    <DialogTitle>Support Admin</DialogTitle>
                                    <DialogDescription>
                                        {adminHeader?.supportEmail ? (
                                            <>
                                                Hubungi support melalui{' '}
                                                <a
                                                    href={`mailto:${adminHeader.supportEmail}`}
                                                >
                                                    {adminHeader.supportEmail}
                                                </a>
                                                .
                                            </>
                                        ) : (
                                            'Hubungi tim pengembang untuk bantuan admin.'
                                        )}
                                    </DialogDescription>
                                </DialogHeader>
                            </DialogContent>
                        </Dialog>
                    </>
                ) : !auth.user ? (
                    <>
                        <Button
                            asChild
                            variant="ghost"
                            className="hidden rounded-[8px] px-2 text-sm font-medium text-slate-500 hover:bg-slate-100 hover:text-blue-600 sm:inline-flex"
                        >
                            <Link href={login()}>Login</Link>
                        </Button>
                        <Button
                            asChild
                            className="h-9 rounded-[8px] bg-[#0080FF] px-3 text-sm hover:bg-[#006FE0]"
                        >
                            <Link href={register()}>Register</Link>
                        </Button>
                    </>
                ) : null}

                {auth.user && (
                    <div className="hidden h-8 w-px bg-slate-200 md:block" />
                )}

                {auth.user && (
                    <DropdownMenu>
                        <DropdownMenuTrigger asChild>
                            <Button
                                type="button"
                                variant="ghost"
                                className="h-10 rounded-[8px] px-1.5 text-slate-900 hover:bg-slate-100 aria-expanded:bg-slate-100 aria-expanded:text-slate-900 md:px-2"
                            >
                                <Avatar className="size-8 overflow-hidden rounded-full border border-slate-200">
                                    <AvatarImage
                                        src={auth.user.avatar}
                                        alt={auth.user.name}
                                    />
                                    <AvatarFallback className="rounded-full bg-blue-100 text-sm font-semibold text-blue-700">
                                        {getInitials(auth.user.name)}
                                    </AvatarFallback>
                                </Avatar>
                                <span className="hidden min-w-0 flex-col items-start md:flex">
                                    <span className="max-w-36 truncate text-sm leading-none font-medium text-slate-800">
                                        {auth.user.name}
                                    </span>
                                    {userRole && (
                                        <span className="mt-1 max-w-36 truncate text-xs leading-none text-slate-500">
                                            {userRole}
                                        </span>
                                    )}
                                </span>
                                <ChevronDown className="hidden size-4 text-slate-400 md:block" />
                            </Button>
                        </DropdownMenuTrigger>
                        <DropdownMenuContent
                            className={userMenuClassName}
                            align="end"
                        >
                            <UserMenuContent user={auth.user} />
                        </DropdownMenuContent>
                    </DropdownMenu>
                )}
            </div>
        </header>
    );
}
