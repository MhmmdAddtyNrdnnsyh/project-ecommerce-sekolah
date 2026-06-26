import { usePage } from '@inertiajs/react';
import AppHeaderLayout from '@/layouts/app/app-header-layout';
import AppLayoutTemplate from '@/layouts/app/app-sidebar-layout';
import type { BreadcrumbItem } from '@/types';

export default function AppLayout({
    breadcrumbs = [],
    children,
}: {
    breadcrumbs?: BreadcrumbItem[];
    children: React.ReactNode;
}) {
    const { auth } = usePage().props;
    const Layout =
        auth.user?.role === 'buyer' ? AppHeaderLayout : AppLayoutTemplate;

    return <Layout breadcrumbs={breadcrumbs}>{children}</Layout>;
}
