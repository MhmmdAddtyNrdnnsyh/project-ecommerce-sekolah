import { Link, usePage } from '@inertiajs/react';
import {
    BarChart3,
    Boxes,
    ClipboardCheck,
    LayoutDashboard,
    MessageSquareText,
    Package,
    Settings,
    ShoppingCart,
    Tags,
    Users,
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
import { edit } from '@/routes/profile';
import { dashboard as sellerDashboard } from '@/routes/seller';
import { index as sellerProductsIndex } from '@/routes/seller/products';
import type { NavItem } from '@/types';

const lightTooltip = {
    className:
        'bg-white text-slate-700 ring-1 ring-slate-200 shadow-sm [&>svg]:bg-white [&>svg]:fill-white',
};

export function AppSidebar() {
    const { auth } = usePage().props;
    const { isCurrentUrl } = useCurrentUrl();
    const dashboardHref =
        auth.user?.role === 'seller' ? sellerDashboard() : dashboard();
    const mainNavItems = getMainNavItems(auth.user?.role, dashboardHref);

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
                        const isActive = isCurrentUrl(item.href);

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
                                        </Link>
                                    )}
                                </SidebarMenuButton>
                            </SidebarMenuItem>
                        );
                    })}
                </SidebarMenu>
            </SidebarContent>

            <SidebarFooter className="border-t border-slate-100 p-3">
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton
                            asChild
                            tooltip={{
                                children: 'Settings',
                                ...lightTooltip,
                            }}
                            className="h-11 rounded-[8px] px-3 text-slate-600 hover:bg-slate-100 hover:text-slate-900"
                        >
                            <Link href={edit()} prefetch>
                                <Settings />
                                <span>Settings</span>
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarFooter>
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
            { title: 'Products', href: sellerProductsIndex(), icon: Package },
            { title: 'Orders', href: '#orders', icon: ShoppingCart },
            { title: 'Inventory', href: '#inventory', icon: Boxes },
            { title: 'Reviews', href: '#reviews', icon: MessageSquareText },
            { title: 'Reports', href: '#reports', icon: BarChart3 },
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
                title: 'Cart',
                href: cartIndex(),
                icon: ShoppingCart,
            },
            {
                title: 'Orders',
                href: '#orders',
                icon: ShoppingCart,
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
        { title: 'Products', href: '#products', icon: Package },
        { title: 'Categories', href: '#categories', icon: Tags },
        { title: 'Orders', href: '#orders', icon: ShoppingCart },
        { title: 'Users', href: '#users', icon: Users },
        { title: 'Reviews', href: '#reviews', icon: MessageSquareText },
        { title: 'Reports', href: '#reports', icon: BarChart3 },
    ];
}
