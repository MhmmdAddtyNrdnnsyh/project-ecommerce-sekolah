import { usePage } from '@inertiajs/react';
import { Bell, ChevronDown, CircleHelp, Search } from 'lucide-react';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Input } from '@/components/ui/input';
import { SidebarTrigger } from '@/components/ui/sidebar';
import {
    Tooltip,
    TooltipContent,
    TooltipTrigger,
} from '@/components/ui/tooltip';
import { UserMenuContent } from '@/components/user-menu-content';
import { useInitials } from '@/hooks/use-initials';
import type { BreadcrumbItem as BreadcrumbItemType } from '@/types';

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

export function AppSidebarHeader({
    breadcrumbs = [],
}: {
    breadcrumbs?: BreadcrumbItemType[];
}) {
    const { auth } = usePage().props;
    const getInitials = useInitials();
    const currentBreadcrumb = breadcrumbs[breadcrumbs.length - 1];
    const title =
        currentBreadcrumb?.title === 'Dashboard'
            ? 'Dashboard Overview'
            : (currentBreadcrumb?.title ?? 'Dashboard Overview');
    const userRole = auth.user?.role ? roleLabels[auth.user.role] : undefined;

    return (
        <header className="sticky top-0 z-10 flex h-16 shrink-0 items-center justify-between gap-4 border-b border-slate-100 bg-white px-4 md:px-6">
            <div className="flex min-w-0 flex-1 items-center gap-4">
                <SidebarTrigger className="-ml-1 text-slate-500 hover:bg-slate-100 hover:text-slate-900" />
                <h1 className="hidden shrink-0 text-2xl font-semibold text-slate-800 lg:block">
                    {title}
                </h1>
                <div className="relative hidden w-full max-w-md sm:block lg:ml-4">
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
            </div>

            <div className="flex shrink-0 items-center gap-2 sm:gap-3">
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

                <Button
                    type="button"
                    variant="ghost"
                    className="hidden rounded-[8px] px-2 text-sm font-medium text-slate-500 hover:bg-slate-100 hover:text-blue-600 aria-expanded:bg-slate-100 md:inline-flex"
                >
                    Support
                </Button>

                <div className="hidden h-8 w-px bg-slate-200 md:block" />

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
