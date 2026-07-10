import { Link, usePage } from '@inertiajs/react';
import {
    Boxes,
    ClipboardCheck,
    ClipboardList,
    FileText,
    LayoutDashboard,
    Package,
    Settings,
    ShoppingCart,
    Store,
    Tags,
    UserRoundCheck,
    Users,
    Warehouse,
} from 'lucide-react';
import type { CSSProperties } from 'react';
import AppLogo from '@/components/app-logo';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { useCurrentUrl } from '@/hooks/use-current-url';
import { cn, toUrl } from '@/lib/utils';
import { dashboard } from '@/routes';
import { index as adminProductModerationIndex } from '@/routes/admin/products/moderation';
import { index as cartIndex } from '@/routes/cart';
import { index as catalogIndex } from '@/routes/catalog';
import { index as ordersIndex } from '@/routes/orders';
import { edit } from '@/routes/profile';
import { dashboard as sellerDashboard } from '@/routes/seller';
import { index as sellerInventoryIndex } from '@/routes/seller/inventory';
import { index as sellerOrdersIndex } from '@/routes/seller/orders';
import { index as sellerProductsIndex } from '@/routes/seller/products';
import type { NavItem } from '@/types';

const lightTooltip = {
    className:
        'bg-white text-slate-700 ring-1 ring-slate-200 shadow-sm [&>svg]:bg-white [&>svg]:fill-white',
};

export function AppSidebar() {
    const { auth, buyerHeader } = usePage().props;
    const { isCurrentOrParentUrl } = useCurrentUrl();
    const dashboardHref =
        auth.user?.role === 'seller'
            ? sellerDashboard()
            : auth.user?.role === 'admin'
              ? dashboard()
              : auth.user?.role === 'admin_jurusan'
                ? '/admin-jurusan/dashboard'
                : auth.user?.role === 'picket_officer'
                  ? '/picket/dashboard'
                  : catalogIndex();
    const mainNavItems = getMainNavItems(auth.user?.role, dashboardHref);
    const activeHref = mainNavItems
        .filter((item) => isCurrentOrParentUrl(item.href))
        .map((item) => toUrl(item.href))
        .sort((a, b) => b.length - a.length)[0];

    return (
        <Sidebar
            collapsible="icon"
            variant="sidebar"
            className="border-r border-slate-200 bg-white"
            style={
                {
                    '--sidebar': '#ffffff',
                    '--sidebar-foreground': '#0f172a',
                    '--sidebar-accent': '#f1f5f9',
                    '--sidebar-accent-foreground': '#0f172a',
                    '--sidebar-border': '#e2e8f0',
                    '--sidebar-primary': '#eff6ff',
                    '--sidebar-primary-foreground': '#1d4ed8',
                } as CSSProperties
            }
        >
            <SidebarHeader className="p-4 pb-6">
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton
                            size="lg"
                            asChild
                            className="h-auto rounded-[8px] p-2 text-slate-900 group-data-[collapsible=icon]:size-10 group-data-[collapsible=icon]:p-0 hover:bg-slate-100 hover:text-slate-900 data-[state=open]:bg-slate-100"
                        >
                            <Link href={dashboardHref} prefetch>
                                <AppLogo />
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarHeader>

            <SidebarContent className="px-3 pb-3">
                <SidebarMenu className="gap-1">
                    {mainNavItems.map((item) => {
                        const href = toUrl(item.href);
                        const isActive = href === activeHref;

                        return (
                            <SidebarMenuItem key={item.title}>
                                <SidebarMenuButton
                                    asChild
                                    isActive={isActive}
                                    tooltip={{
                                        children: item.title,
                                        ...lightTooltip,
                                    }}
                                    className={cn(
                                        'h-11 rounded-[8px] px-3 text-slate-600 transition-colors hover:bg-slate-100 hover:text-slate-900 active:scale-[0.99]',
                                        'data-open:bg-slate-100 data-active:bg-blue-50 data-active:text-blue-700 data-active:hover:bg-blue-50',
                                    )}
                                >
                                    {href.startsWith('#') ? (
                                        <a href={href}>
                                            {item.icon && <item.icon />}
                                            <span>{item.title}</span>
                                        </a>
                                    ) : (
                                        <Link href={item.href} prefetch>
                                            {item.icon && <item.icon />}
                                            <span>{item.title}</span>
                                            {auth.user?.role === 'buyer' &&
                                                item.title === 'Keranjang' &&
                                                Boolean(
                                                    buyerHeader?.cartItemsCount,
                                                ) && (
                                                    <span className="ml-auto rounded-full bg-blue-100 px-2 py-0.5 text-xs font-semibold text-blue-700">
                                                        {
                                                            buyerHeader?.cartItemsCount
                                                        }
                                                    </span>
                                                )}
                                        </Link>
                                    )}
                                </SidebarMenuButton>
                            </SidebarMenuItem>
                        );
                    })}
                </SidebarMenu>
            </SidebarContent>

            {auth.user && (
                <SidebarFooter className="border-t border-slate-100 p-3">
                    <SidebarMenu>
                        <SidebarMenuItem>
                            <SidebarMenuButton
                                asChild
                                tooltip={{
                                    children: 'Pengaturan',
                                    ...lightTooltip,
                                }}
                                className="h-11 rounded-[8px] px-3 text-slate-600 hover:bg-slate-100 hover:text-slate-900"
                            >
                                <Link href={edit()} prefetch>
                                    <Settings />
                                    <span>Pengaturan</span>
                                </Link>
                            </SidebarMenuButton>
                        </SidebarMenuItem>
                    </SidebarMenu>
                </SidebarFooter>
            )}
        </Sidebar>
    );
}

function getMainNavItems(
    role: string | undefined,
    dashboardHref: NavItem['href'],
): NavItem[] {
    if (role === 'seller') {
        return [
            {
                title: 'Dashboard',
                href: dashboardHref,
                icon: LayoutDashboard,
            },
            { title: 'Produk', href: sellerProductsIndex(), icon: Package },
            { title: 'Pesanan', href: sellerOrdersIndex(), icon: ShoppingCart },
            { title: 'Inventori', href: sellerInventoryIndex(), icon: Boxes },
            {
                title: 'Titip Barang',
                href: '/seller/consignments',
                icon: ClipboardList,
            },
        ];
    }

    if (role === 'buyer') {
        return [
            {
                title: 'Katalog',
                href: catalogIndex(),
                icon: Package,
            },
            {
                title: 'Keranjang',
                href: cartIndex(),
                icon: ShoppingCart,
            },
            {
                title: 'Pesanan',
                href: ordersIndex(),
                icon: ShoppingCart,
            },
        ];
    }

    if (!role) {
        return [
            {
                title: 'Katalog',
                href: catalogIndex(),
                icon: Package,
            },
        ];
    }

    if (role === 'admin_jurusan') {
        return [
            {
                title: 'Dashboard',
                href: dashboardHref,
                icon: LayoutDashboard,
            },
            {
                title: 'UP Jurusan',
                href: '/admin-jurusan/up-jurusan',
                icon: Warehouse,
            },
            {
                title: 'Picket Officer',
                href: '/admin-jurusan/picket-officer/create',
                icon: UserRoundCheck,
            },
            {
                title: 'Pengajuan Titip',
                href: '/admin-jurusan/consignments',
                icon: ClipboardCheck,
            },
            {
                title: 'Laporan',
                href: '/admin-jurusan/reports',
                icon: FileText,
            },
        ];
    }

    if (role === 'picket_officer') {
        return [
            {
                title: 'Dashboard',
                href: '/picket/dashboard',
                icon: LayoutDashboard,
            },
            {
                title: 'POS Terminal',
                href: '/picket/pos',
                icon: ShoppingCart,
            },
            {
                title: 'Terima Barang',
                href: '/picket/receiving',
                icon: ClipboardCheck,
            },
            {
                title: 'Pesanan',
                href: '/picket/orders',
                icon: ClipboardList,
            },
            {
                title: 'Laporan',
                href: '/picket/reports',
                icon: FileText,
            },
        ];
    }

    return [
        {
            title: 'Dashboard',
            href: dashboardHref,
            icon: LayoutDashboard,
        },
        {
            title: 'Moderasi Produk',
            href: adminProductModerationIndex(),
            icon: ClipboardCheck,
        },
        {
            title: 'Pengajuan Seller',
            href: '/admin/seller-applications',
            icon: Store,
        },
        { title: 'Produk', href: '/admin/products', icon: Package },
        { title: 'Kategori', href: '/admin/categories', icon: Tags },
        { title: 'Pesanan', href: '/admin/orders', icon: ShoppingCart },
        { title: 'Pengguna', href: '/admin/users', icon: Users },
    ];
}
