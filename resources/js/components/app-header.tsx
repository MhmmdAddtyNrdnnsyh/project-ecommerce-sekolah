import { Link, usePage } from '@inertiajs/react';
import {
    BarChart3,
    Bell,
    Boxes,
    ChevronDown,
    CircleHelp,
    LayoutDashboard,
    Menu,
    MessageSquareText,
    Package,
    Search,
    ShoppingCart,
    Tags,
    Users,
} from 'lucide-react';
import AppLogo from '@/components/app-logo';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Input } from '@/components/ui/input';
import {
    Sheet,
    SheetContent,
    SheetHeader,
    SheetTitle,
    SheetTrigger,
} from '@/components/ui/sheet';
import {
    Tooltip,
    TooltipContent,
    TooltipTrigger,
} from '@/components/ui/tooltip';
import { UserMenuContent } from '@/components/user-menu-content';
import { useCurrentUrl } from '@/hooks/use-current-url';
import { useInitials } from '@/hooks/use-initials';
import { cn, toUrl } from '@/lib/utils';
import { dashboard } from '@/routes';
import { dashboard as sellerDashboard } from '@/routes/seller';
import type { BreadcrumbItem, NavItem } from '@/types';

type Props = {
    breadcrumbs?: BreadcrumbItem[];
};

const roleLabels: Record<string, string> = {
    admin: 'Super Admin',
    seller: 'Seller',
    buyer: 'Buyer',
    picket_officer: 'Picket Officer',
};

const userMenuClassName =
    'w-56 rounded-[8px] bg-white text-slate-900 ring-slate-200 [&_[data-slot=dropdown-menu-item]]:text-slate-700 [&_[data-slot=dropdown-menu-item]]:focus:bg-slate-100 [&_[data-slot=dropdown-menu-item]]:focus:text-slate-900 [&_[data-slot=dropdown-menu-label]]:text-slate-500 [&_[data-slot=dropdown-menu-separator]]:bg-slate-200';
const tooltipClassName =
    'bg-white text-slate-700 ring-1 ring-slate-200 shadow-sm [&>svg]:bg-white [&>svg]:fill-white';

export function AppHeader({ breadcrumbs = [] }: Props) {
    const { auth } = usePage().props;
    const { isCurrentUrl } = useCurrentUrl();
    const getInitials = useInitials();
    const dashboardHref =
        auth.user?.role === 'seller' ? sellerDashboard() : dashboard();
    const mainNavItems = getMainNavItems(auth.user?.role, dashboardHref);
    const currentBreadcrumb = breadcrumbs[breadcrumbs.length - 1];
    const title =
        currentBreadcrumb?.title === 'Dashboard'
            ? 'Dashboard Overview'
            : (currentBreadcrumb?.title ?? 'Dashboard Overview');
    const userRole = auth.user?.role ? roleLabels[auth.user.role] : undefined;

    return (
        <header className="sticky top-0 z-10 border-b border-slate-100 bg-white">
            <div className="mx-auto flex h-16 w-full items-center gap-4 px-4 md:px-6">
                <Sheet>
                    <SheetTrigger asChild>
                        <Button
                            type="button"
                            variant="ghost"
                            size="icon"
                            className="rounded-[8px] text-slate-500 hover:bg-slate-100 hover:text-slate-900 aria-expanded:bg-slate-100 lg:hidden"
                            aria-label="Open navigation"
                        >
                            <Menu className="size-5" />
                        </Button>
                    </SheetTrigger>
                    <SheetContent
                        side="left"
                        className="flex h-full w-64 flex-col border-slate-200 bg-white p-4 text-slate-900"
                    >
                        <SheetTitle className="sr-only">
                            Navigation menu
                        </SheetTitle>
                        <SheetHeader className="mb-6 p-0 text-left">
                            <Link
                                href={dashboardHref}
                                prefetch
                                className="flex items-center gap-2 text-slate-900"
                            >
                                <AppLogo />
                            </Link>
                        </SheetHeader>
                        <nav className="flex flex-col gap-1">
                            {mainNavItems.map((item) => {
                                const href = toUrl(item.href);
                                const isActive = isCurrentUrl(item.href);
                                const Icon = item.icon;
                                const className = cn(
                                    'flex h-11 items-center gap-3 rounded-[8px] px-3 text-sm text-slate-600 transition hover:bg-slate-100 hover:text-slate-900',
                                    isActive &&
                                        'bg-blue-50 text-blue-700 hover:bg-blue-50',
                                );

                                return href.startsWith('#') ? (
                                    <a
                                        key={item.title}
                                        href={href}
                                        className={className}
                                    >
                                        {Icon && <Icon className="size-4" />}
                                        <span>{item.title}</span>
                                    </a>
                                ) : (
                                    <Link
                                        key={item.title}
                                        href={item.href}
                                        prefetch
                                        className={className}
                                    >
                                        {Icon && <Icon className="size-4" />}
                                        <span>{item.title}</span>
                                    </Link>
                                );
                            })}
                        </nav>
                    </SheetContent>
                </Sheet>

                <Link
                    href={dashboardHref}
                    prefetch
                    className="hidden items-center gap-2 text-slate-900 sm:flex"
                >
                    <AppLogo />
                </Link>

                <nav className="hidden items-center gap-1 lg:flex">
                    {mainNavItems.slice(0, 4).map((item) => {
                        const href = toUrl(item.href);
                        const isActive = isCurrentUrl(item.href);
                        const Icon = item.icon;
                        const className = cn(
                            'flex h-9 items-center gap-2 rounded-[8px] px-3 text-sm font-medium text-slate-500 transition hover:bg-slate-100 hover:text-slate-900',
                            isActive && 'bg-blue-50 text-blue-700',
                        );

                        return href.startsWith('#') ? (
                            <a
                                key={item.title}
                                href={href}
                                className={className}
                            >
                                {Icon && <Icon className="size-4" />}
                                {item.title}
                            </a>
                        ) : (
                            <Link
                                key={item.title}
                                href={item.href}
                                prefetch
                                className={className}
                            >
                                {Icon && <Icon className="size-4" />}
                                {item.title}
                            </Link>
                        );
                    })}
                </nav>

                <h1 className="hidden shrink-0 text-xl font-semibold text-slate-800 xl:block">
                    {title}
                </h1>

                <div className="relative ml-auto hidden w-full max-w-sm md:block">
                    <Search className="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-slate-400" />
                    <Input
                        className="h-10 rounded-[8px] border-slate-200 bg-slate-50 pr-4 pl-10 text-sm text-slate-700 focus-visible:border-blue-400 focus-visible:bg-white focus-visible:ring-blue-100"
                        placeholder={
                            auth.user?.role === 'seller'
                                ? 'Search orders, products, stock...'
                                : 'Search orders, products, users...'
                        }
                        type="search"
                    />
                </div>

                <div className="flex shrink-0 items-center gap-2">
                    <Tooltip>
                        <TooltipTrigger asChild>
                            <Button
                                type="button"
                                variant="ghost"
                                size="icon"
                                className="relative rounded-full text-slate-500 hover:bg-slate-100 hover:text-blue-600 aria-expanded:bg-slate-100 aria-expanded:text-blue-600"
                                aria-label="Notifications"
                            >
                                <Bell className="size-5" />
                                <span className="absolute top-2 right-2 size-2 rounded-full bg-red-500 ring-2 ring-white" />
                            </Button>
                        </TooltipTrigger>
                        <TooltipContent className={tooltipClassName}>
                            Notifications
                        </TooltipContent>
                    </Tooltip>

                    <Tooltip>
                        <TooltipTrigger asChild>
                            <Button
                                type="button"
                                variant="ghost"
                                size="icon"
                                className="hidden rounded-full text-slate-500 hover:bg-slate-100 hover:text-blue-600 aria-expanded:bg-slate-100 aria-expanded:text-blue-600 sm:inline-flex"
                                aria-label="Help"
                            >
                                <CircleHelp className="size-5" />
                            </Button>
                        </TooltipTrigger>
                        <TooltipContent className={tooltipClassName}>
                            Help
                        </TooltipContent>
                    </Tooltip>

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
            </div>
        </header>
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
            { title: 'Products', href: '#products', icon: Package },
            { title: 'Orders', href: '#orders', icon: ShoppingCart },
            { title: 'Inventory', href: '#inventory', icon: Boxes },
            { title: 'Reviews', href: '#reviews', icon: MessageSquareText },
            { title: 'Reports', href: '#reports', icon: BarChart3 },
        ];
    }

    return [
        {
            title: 'Dashboard',
            href: dashboardHref,
            icon: LayoutDashboard,
        },
        { title: 'Products', href: '#products', icon: Package },
        { title: 'Categories', href: '#categories', icon: Tags },
        { title: 'Orders', href: '#orders', icon: ShoppingCart },
        { title: 'Users', href: '#users', icon: Users },
        { title: 'Reviews', href: '#reviews', icon: MessageSquareText },
        { title: 'Reports', href: '#reports', icon: BarChart3 },
    ];
}
